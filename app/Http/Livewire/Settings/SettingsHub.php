<?php

namespace App\Http\Livewire\Settings;

use App\Domain\Azienda\AziendaSettingService;
use App\Domain\Audit\ActivityLogService;
use App\Domain\Notifications\MailTransportRuntimeService;
use App\Domain\Notifications\NotificationPreferenceService;
use App\Domain\Notifications\NotificationService;
use App\Enums\NotificationEvent;
use App\Http\Livewire\Segreteria\SegreteriaPage;
use App\Models\CompanySetting;
use App\Models\RentriSetting;
use App\Services\EnvWriter;
use App\Support\Horizon\QueueWorkerStatusService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\WithFileUploads;

#[Title('Impostazioni')]
class SettingsHub extends SegreteriaPage
{
    use AuthorizesRequests;
    use WithFileUploads;

    #[Url(as: 'tab')]
    public string $activeTab = 'azienda';

    // ── Tab: Azienda ────────────────────────────────────────────────────────
    public string $company_ragione_sociale = '';
    public string $company_piva            = '';
    public string $company_cf              = '';
    public string $company_indirizzo       = '';
    public string $company_cap             = '';
    public string $company_citta           = '';
    public string $company_provincia       = '';
    public string $company_pec             = '';
    public string $company_email           = '';
    public string $company_telefono        = '';
    public string $company_num_albo        = '';
    public string $company_codice_sdi      = '';
    public string $company_formato_numerazione_fattura = '';
    public ?string $company_logo_path      = null;
    /** @var mixed */
    public $company_logo;

    // ── Tab: Pagamenti (Stripe) ──────────────────────────────────────────────
    public bool $stripe_live_mode       = false;
    public string $stripe_key           = '';
    public string $stripe_secret        = '';
    public string $stripe_webhook_secret = '';
    public bool $stripe_dispute_stub    = true;
    public bool $stripe_payment_card    = true;
    public bool $stripe_payment_sepa    = false;
    public ?string $stripeTestResult    = null;
    public bool $stripeTestOk           = false;

    // ── Tab: Email & Notifiche ───────────────────────────────────────────────
    public string $mail_host         = '';
    public string $mail_port         = '587';
    public string $mail_username     = '';
    public string $mail_password     = '';
    public string $mail_encryption   = 'tls';
    public string $mail_from_name    = '';
    public string $mail_from_address = '';
    public bool $notifications_live  = false;
    public string $testEmailRecipient = '';
    /** @var array<string, bool> */
    public array $notifToggles = [];

    // ── Tab: Integrazioni ───────────────────────────────────────────────────
    public string $gps_provider_url = '';
    public bool $gps_stub_mode      = true;
    public string $mud_endpoint     = '';
    public bool $mud_stub_mode      = true;
    public bool $shop_enabled     = false;
    public ?string $gpsTestResult   = null;
    public bool $gpsTestOk          = false;

    // ── Tab: Sistema ────────────────────────────────────────────────────────
    public bool $demo_mode     = false;
    public bool $app_debug     = false;
    public string $log_level   = 'debug';

    // UI state
    public bool $showStripeKey            = false;
    public bool $showStripeSecret         = false;
    public bool $showStripeWebhookSecret  = false;
    public bool $showMailPassword         = false;
    public bool $showMigrazioniConfirm    = false;

    public function mount(NotificationPreferenceService $preferences): void
    {
        abort_unless(
            auth()->user()?->hasAnyRole(['admin', 'segreteria']),
            403,
        );

        $this->loadAzienda();
        $this->loadPagamenti();
        $this->loadEmail();
        $this->loadIntegrazioni();
        $this->loadSistema();

        $this->testEmailRecipient = (string) config('notifications.default_recipient');

        foreach (NotificationEvent::all() as $event) {
            $this->notifToggles[$this->toggleKey($event)] = $preferences->isEnabled($event);
        }
    }

    // ── Tab switching ────────────────────────────────────────────────────────

    public function switchTab(string $tab): void
    {
        $allowed = ['azienda', 'rentri', 'pagamenti', 'email', 'integrazioni', 'sistema'];
        if (in_array($tab, $allowed, true)) {
            $this->activeTab = $tab;
        }
    }

    // ── Azienda ─────────────────────────────────────────────────────────────

    public function saveAzienda(): void
    {
        $this->requireAdmin();

        $data = $this->validate([
            'company_ragione_sociale' => ['nullable', 'string', 'max:200'],
            'company_piva'            => ['nullable', 'string', 'max:20'],
            'company_cf'              => ['nullable', 'string', 'max:16'],
            'company_indirizzo'       => ['nullable', 'string', 'max:300'],
            'company_cap'             => ['nullable', 'string', 'max:10'],
            'company_citta'           => ['nullable', 'string', 'max:100'],
            'company_provincia'       => ['nullable', 'string', 'max:2'],
            'company_pec'             => ['nullable', 'email', 'max:200'],
            'company_email'           => ['nullable', 'email', 'max:200'],
            'company_telefono'        => ['nullable', 'string', 'max:30'],
            'company_num_albo'        => ['nullable', 'string', 'max:80'],
            'company_codice_sdi'      => ['nullable', 'string', 'max:7'],
            'company_formato_numerazione_fattura' => ['nullable', 'string', 'max:100'],
            'company_logo'            => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'mimetypes:image/jpeg,image/png,image/webp,image/svg+xml', 'max:2048'],
        ]);

        if ($this->company_logo) {
            $path = $this->company_logo->store('company', 'public');
            CompanySetting::set('company_logo_path', $path);
            $this->company_logo_path = $path;
        }

        unset($data['company_logo']);

        foreach ($data as $key => $value) {
            CompanySetting::set($key, (string) ($value ?? ''));
        }

        app(ActivityLogService::class)->record(
            'settings',
            'Impostazioni azienda salvate',
            properties: ['tab' => 'azienda'],
        );

        session()->flash('success', 'Dati azienda salvati.');
    }

    public function removeLogo(): void
    {
        $this->requireAdmin();

        $current = CompanySetting::get('company_logo_path');
        if ($current) {
            Storage::disk('public')->delete($current);
        }

        CompanySetting::set('company_logo_path', '');
        $this->company_logo_path = null;

        session()->flash('success', 'Logo rimosso.');
    }

    public function getProssimoNumeroFatturaProperty(): string
    {
        $format = filled($this->company_formato_numerazione_fattura)
            ? $this->company_formato_numerazione_fattura
            : null;

        return app(AziendaSettingService::class)->prossimoNumero('fattura', $format);
    }

    // ── Pagamenti ────────────────────────────────────────────────────────────

    public function savePagamenti(): void
    {
        $this->requireAdmin();

        $this->validate([
            'stripe_key'             => ['nullable', 'string', 'max:200'],
            'stripe_secret'          => ['nullable', 'string', 'max:200'],
            'stripe_webhook_secret'  => ['nullable', 'string', 'max:200'],
        ]);

        CompanySetting::set('stripe_live_mode', $this->stripe_live_mode);
        CompanySetting::set('stripe_dispute_stub', $this->stripe_dispute_stub);
        CompanySetting::set('stripe_payment_card', $this->stripe_payment_card);
        CompanySetting::set('stripe_payment_sepa', $this->stripe_payment_sepa);

        if ($this->stripe_key !== '') {
            CompanySetting::set('stripe_key', $this->stripe_key);
        }
        if ($this->stripe_secret !== '') {
            CompanySetting::set('stripe_secret', $this->stripe_secret);
        }
        if ($this->stripe_webhook_secret !== '') {
            CompanySetting::set('stripe_webhook_secret', $this->stripe_webhook_secret);
        }

        // Persist to .env
        $envWriter = new EnvWriter(base_path('.env'));

        $envValues = [
            'STRIPE_LIVE_MODE'    => $this->stripe_live_mode ? 'true' : 'false',
            'ECOMMERCE_PAYMENT_STUB' => $this->stripe_live_mode ? 'false' : 'true',
            'STRIPE_DISPUTE_STUB' => $this->stripe_dispute_stub ? 'true' : 'false',
        ];

        if ($this->stripe_key !== '') {
            $envValues['STRIPE_KEY'] = $this->stripe_key;
        }
        if ($this->stripe_secret !== '') {
            $envValues['STRIPE_SECRET'] = $this->stripe_secret;
        }
        if ($this->stripe_webhook_secret !== '') {
            $envValues['STRIPE_WEBHOOK_SECRET'] = $this->stripe_webhook_secret;
        }

        $envWriter->write($envValues);

        // Reset plaintext inputs after save
        $this->stripe_key            = '';
        $this->stripe_secret         = '';
        $this->stripe_webhook_secret = '';
        $this->showStripeKey         = false;
        $this->showStripeSecret      = false;
        $this->showStripeWebhookSecret = false;

        app(ActivityLogService::class)->record(
            'settings',
            'Impostazioni pagamenti Stripe salvate',
            properties: [
                'tab'               => 'pagamenti',
                'stripe_live_mode'  => $this->stripe_live_mode,
                'stripe_dispute_stub' => $this->stripe_dispute_stub,
            ],
        );

        session()->flash('success', 'Impostazioni Stripe salvate.');
    }

    public function testStripeConnection(): void
    {
        $this->requireAdmin();

        $secret = CompanySetting::get('stripe_secret')
            ?? env('STRIPE_SECRET');

        if (blank($secret)) {
            $this->stripeTestResult = 'Chiave Stripe Secret non configurata.';
            $this->stripeTestOk     = false;

            return;
        }

        try {
            $ch = curl_init('https://api.stripe.com/v1/balance');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_USERPWD        => $secret.':',
                CURLOPT_TIMEOUT        => 10,
            ]);
            $body    = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $decoded = json_decode($body ?: '{}', true);

            if ($httpCode === 200 && isset($decoded['object']) && $decoded['object'] === 'balance') {
                $this->stripeTestOk     = true;
                $this->stripeTestResult = 'Connessione Stripe verificata. Account: '.(isset($decoded['livemode']) ? ($decoded['livemode'] ? 'LIVE' : 'TEST') : '?');
            } else {
                $this->stripeTestOk     = false;
                $this->stripeTestResult = 'Risposta inattesa ('.$httpCode.'): '.($decoded['error']['message'] ?? $body);
            }
        } catch (\Throwable $e) {
            $this->stripeTestOk     = false;
            $this->stripeTestResult = 'Errore connessione: '.$e->getMessage();
        }
    }

    // ── Email & Notifiche ────────────────────────────────────────────────────

    public function saveEmail(): void
    {
        $this->requireAdmin();

        $this->validate([
            'mail_host'         => ['nullable', 'string', 'max:200'],
            'mail_port'         => ['nullable', 'integer', 'min:1', 'max:65535'],
            'mail_username'     => ['nullable', 'string', 'max:200'],
            'mail_password'     => ['nullable', 'string', 'max:200'],
            'mail_encryption'   => ['nullable', 'in:tls,ssl,null,'],
            'mail_from_name'    => ['nullable', 'string', 'max:100'],
            'mail_from_address' => ['nullable', 'email', 'max:200'],
        ]);

        $fields = [
            'mail_host', 'mail_port', 'mail_username', 'mail_encryption',
            'mail_from_name', 'mail_from_address',
        ];

        foreach ($fields as $f) {
            CompanySetting::set($f, (string) ($this->{$f} ?? ''));
        }

        if ($this->mail_password !== '') {
            CompanySetting::set('mail_password', $this->mail_password);
        }

        CompanySetting::set('notifications_live', $this->notifications_live);

        $envWriter = new EnvWriter(base_path('.env'));

        $envValues = [
            'MAIL_HOST'       => $this->mail_host,
            'MAIL_PORT'       => (string) $this->mail_port,
            'MAIL_USERNAME'   => $this->mail_username,
            'MAIL_SCHEME'     => $this->mail_encryption === 'ssl' ? 'ssl' : ($this->mail_encryption === 'tls' ? 'tls' : 'null'),
            'MAIL_FROM_NAME'  => $this->mail_from_name,
            'MAIL_FROM_ADDRESS' => $this->mail_from_address,
            'NOTIFICATIONS_LIVE' => $this->notifications_live ? 'true' : 'false',
        ];

        if ($this->mail_password !== '') {
            $envValues['MAIL_PASSWORD'] = $this->mail_password;
        }

        if ($this->mail_host !== '' && $this->mail_host !== '127.0.0.1') {
            $envValues['MAIL_MAILER'] = 'smtp';
        }

        $envWriter->write($envValues);

        $this->mail_password  = '';
        $this->showMailPassword = false;

        app(ActivityLogService::class)->record(
            'settings',
            'Configurazione email salvata',
            properties: [
                'tab'                => 'email',
                'notifications_live' => $this->notifications_live,
            ],
        );

        session()->flash('success', 'Configurazione email salvata. Config cache pulita.');
    }

    public function saveNotifiche(NotificationPreferenceService $preferences): void
    {
        $this->requireAdmin();

        $validated = [];
        foreach (NotificationEvent::all() as $event) {
            $validated[$event->value] = (bool) ($this->notifToggles[$this->toggleKey($event)] ?? false);
        }

        $preferences->save($validated);

        foreach (NotificationEvent::all() as $event) {
            $this->notifToggles[$this->toggleKey($event)] = $preferences->isEnabled($event);
        }

        app(ActivityLogService::class)->record(
            'settings',
            'Preferenze notifiche salvate',
            properties: ['tab' => 'email', 'section' => 'notifiche'],
        );

        session()->flash('success', 'Preferenze notifiche salvate.');
    }

    public function sendTestEmail(
        NotificationService $notifications,
        MailTransportRuntimeService $mailRuntime,
    ): void {
        $this->requireAdmin();

        $this->validate([
            'testEmailRecipient' => ['required', 'email'],
        ]);

        if ($mailRuntime->isLive() && ! $mailRuntime->preflightReady()) {
            session()->flash('error', 'SMTP non configurato correttamente. Completare la checklist MAIL_*.');

            return;
        }

        $sentBy = auth()->user()?->email ?? 'sistema';
        $notifications->sendTestEmail($this->testEmailRecipient, $sentBy);

        session()->flash('success', $mailRuntime->isLive()
            ? 'Email di test inviata a '.$this->testEmailRecipient.'.'
            : 'Test registrato in log (modalità stub — nessun SMTP).',
        );
    }

    // ── Integrazioni ─────────────────────────────────────────────────────────

    public function saveIntegrazioni(): void
    {
        $this->requireAdmin();

        $this->validate([
            'gps_provider_url' => ['nullable', 'url', 'max:500'],
            'mud_endpoint'     => ['nullable', 'url', 'max:500'],
        ]);

        CompanySetting::set('gps_provider_url', $this->gps_provider_url);
        CompanySetting::set('gps_stub_mode', $this->gps_stub_mode);
        CompanySetting::set('mud_endpoint', $this->mud_endpoint);
        CompanySetting::set('mud_stub_mode', $this->mud_stub_mode);
        CompanySetting::set('shop_enabled', $this->shop_enabled);

        $envWriter = new EnvWriter(base_path('.env'));
        $envWriter->write([
            'TRASPORTO_GPS_STUB'        => $this->gps_stub_mode ? 'true' : 'false',
            'TRASPORTO_GPS_PROVIDER_URL' => $this->gps_provider_url,
            'MUD_TELEMATICO_STUB'       => $this->mud_stub_mode ? 'true' : 'false',
            'SHOP_ENABLED'                => $this->shop_enabled ? 'true' : 'false',
        ]);

        if ($this->mud_endpoint !== '') {
            $envWriter->write(['MUD_TELEMATICO_BASE_URL' => $this->mud_endpoint]);
        }

        app(ActivityLogService::class)->record(
            'settings',
            'Impostazioni integrazioni salvate',
            properties: [
                'tab'           => 'integrazioni',
                'gps_stub_mode' => $this->gps_stub_mode,
                'mud_stub_mode' => $this->mud_stub_mode,
                'shop_enabled'  => $this->shop_enabled,
            ],
        );

        Artisan::call('config:clear');

        session()->flash('success', 'Impostazioni integrazioni salvate.');
    }

    public function testGpsConnection(): void
    {
        $this->requireAdmin();

        $url = $this->gps_provider_url ?: CompanySetting::get('gps_provider_url') ?: env('TRASPORTO_GPS_PROVIDER_URL');

        if (blank($url)) {
            $this->gpsTestOk     = false;
            $this->gpsTestResult = 'URL provider GPS non configurato.';

            return;
        }

        try {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 5,
                CURLOPT_NOBODY         => true,
            ]);
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error    = curl_error($ch);
            curl_close($ch);

            if ($error) {
                $this->gpsTestOk     = false;
                $this->gpsTestResult = 'Errore connessione: '.$error;
            } elseif ($httpCode > 0 && $httpCode < 500) {
                $this->gpsTestOk     = true;
                $this->gpsTestResult = "Provider raggiungibile (HTTP {$httpCode}).";
            } else {
                $this->gpsTestOk     = false;
                $this->gpsTestResult = "Risposta inattesa: HTTP {$httpCode}.";
            }
        } catch (\Throwable $e) {
            $this->gpsTestOk     = false;
            $this->gpsTestResult = 'Errore: '.$e->getMessage();
        }
    }

    // ── Sistema ──────────────────────────────────────────────────────────────

    public function saveSistema(): void
    {
        $this->requireAdmin();

        $this->validate([
            'log_level' => ['required', 'in:debug,info,notice,warning,error,critical,alert,emergency'],
        ]);

        CompanySetting::set('log_level', $this->log_level);
        CompanySetting::set('demo_mode', $this->demo_mode);
        CompanySetting::set('app_debug', $this->app_debug);

        $envWriter = new EnvWriter(base_path('.env'));
        $envWriter->write([
            'LOG_LEVEL'     => $this->log_level,
            'APP_DEMO_MODE' => $this->demo_mode ? 'true' : 'false',
            'APP_DEBUG'     => $this->app_debug ? 'true' : 'false',
        ]);

        app(ActivityLogService::class)->record(
            'settings',
            'Impostazioni sistema salvate',
            properties: [
                'tab'       => 'sistema',
                'log_level' => $this->log_level,
                'demo_mode' => $this->demo_mode,
                'app_debug' => $this->app_debug,
            ],
        );

        Artisan::call('config:clear');

        session()->flash('success', 'Impostazioni sistema salvate.');
    }

    public function clearAppCache(): void
    {
        $this->requireAdmin();

        Artisan::call('cache:clear');
        session()->flash('success', 'Cache applicazione svuotata.');
    }

    public function clearConfigCache(): void
    {
        $this->requireAdmin();

        Artisan::call('config:clear');
        session()->flash('success', 'Cache configurazione svuotata.');
    }

    public function runMigrations(): void
    {
        $this->requireAdmin();

        $this->showMigrazioniConfirm = false;

        try {
            Artisan::call('migrate', ['--force' => true]);
            $output = Artisan::output();
            session()->flash('success', 'Migrazioni eseguite. '.str_replace("\n", ' ', trim($output)));
        } catch (\Throwable $e) {
            session()->flash('error', 'Errore migrazioni: '.$e->getMessage());
        }
    }

    public function runPreflight(): void
    {
        $this->requireAdmin();

        try {
            Artisan::call('rentri:preflight');
            $output = Artisan::output();
            session()->flash('success', 'Preflight completato. '.str_replace("\n", ' ', trim($output)));
        } catch (\Throwable $e) {
            session()->flash('error', 'Errore preflight: '.$e->getMessage());
        }
    }

    // ── Render ───────────────────────────────────────────────────────────────

    public function render(MailTransportRuntimeService $mailRuntime, QueueWorkerStatusService $queueStatus): View
    {
        $rentriSettings = RentriSetting::instance();

        $certDays  = $rentriSettings->cert_scadenza !== null
            ? (int) now()->diffInDays($rentriSettings->cert_scadenza, false)
            : null;

        $lastHealth     = (array) ($rentriSettings->last_health_status ?? []);
        $healthOk       = ($lastHealth['status'] ?? null) === 'ok';
        $isLiveRentri   = $rentriSettings->live_mode_enabled_at !== null;

        return $this->segreteriaView(
            'livewire.settings.settings-hub',
            [
                'events'             => NotificationEvent::all(),
                'mailRuntime'        => $mailRuntime,
                'mailPreflightOk'    => $mailRuntime->preflightReady(),
                'rentriSettings'     => $rentriSettings,
                'certDays'           => $certDays,
                'healthOk'           => $healthOk,
                'healthStatus'       => $lastHealth['status'] ?? 'unknown',
                'isLiveRentri'       => $isLiveRentri,
                'webhookUrl'         => url('/webhooks/stripe/ecommerce'),
                'appEnv'             => app()->environment(),
                'queueConnection'    => config('queue.default'),
                'queueWorkerStatus'  => $queueStatus->snapshot(),
                'appDebug'           => (bool) config('app.debug'),
                'logoUrl'            => $this->company_logo_path
                    ? Storage::disk('public')->url($this->company_logo_path)
                    : null,
            ],
            'impostazioni',
            'Impostazioni',
        );
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function requireAdmin(): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403, 'Solo gli amministratori possono modificare le impostazioni.');
    }

    private function toggleKey(NotificationEvent $event): string
    {
        return str_replace('.', '__', $event->value);
    }

    private function loadAzienda(): void
    {
        $this->company_ragione_sociale = (string) CompanySetting::get('company_ragione_sociale', '');
        $this->company_piva            = (string) CompanySetting::get('company_piva', '');
        $this->company_cf              = (string) CompanySetting::get('company_cf', '');
        $this->company_indirizzo       = (string) CompanySetting::get('company_indirizzo', '');
        $this->company_cap             = (string) CompanySetting::get('company_cap', '');
        $this->company_citta           = (string) CompanySetting::get('company_citta', '');
        $this->company_provincia       = (string) CompanySetting::get('company_provincia', '');
        $this->company_pec             = (string) CompanySetting::get('company_pec', '');
        $this->company_email           = (string) CompanySetting::get('company_email', '');
        $this->company_telefono        = (string) CompanySetting::get('company_telefono', '');
        $this->company_num_albo        = (string) CompanySetting::get('company_num_albo', '');
        $this->company_codice_sdi      = (string) CompanySetting::get('company_codice_sdi', '');
        $this->company_formato_numerazione_fattura = (string) (
            CompanySetting::get('company_formato_numerazione_fattura')
            ?: AziendaSettingService::DEFAULT_FORMATO_NUMERAZIONE
        );
        $logoPath                      = CompanySetting::get('company_logo_path');
        $this->company_logo_path       = $logoPath ?: null;
    }

    private function loadPagamenti(): void
    {
        $this->stripe_live_mode      = (bool) CompanySetting::get('stripe_live_mode', false);
        $this->stripe_dispute_stub   = (bool) CompanySetting::get('stripe_dispute_stub', true);
        $this->stripe_payment_card   = (bool) CompanySetting::get('stripe_payment_card', true);
        $this->stripe_payment_sepa   = (bool) CompanySetting::get('stripe_payment_sepa', false);
        // Do not pre-fill sensitive key inputs — show masked instead.
        $this->stripe_key            = '';
        $this->stripe_secret         = '';
        $this->stripe_webhook_secret = '';
    }

    private function loadEmail(): void
    {
        $this->mail_host         = (string) CompanySetting::get('mail_host', config('mail.mailers.smtp.host', ''));
        $this->mail_port         = (string) (CompanySetting::get('mail_port') ?? config('mail.mailers.smtp.port', 587));
        $this->mail_username     = (string) CompanySetting::get('mail_username', config('mail.mailers.smtp.username', ''));
        $this->mail_encryption   = (string) CompanySetting::get('mail_encryption', config('mail.mailers.smtp.encryption', 'tls'));
        $this->mail_from_name    = (string) CompanySetting::get('mail_from_name', config('mail.from.name', ''));
        $this->mail_from_address = (string) CompanySetting::get('mail_from_address', config('mail.from.address', ''));
        $this->notifications_live = (bool) CompanySetting::get('notifications_live', config('notifications.live', false));
        $this->mail_password     = '';
    }

    private function loadIntegrazioni(): void
    {
        $this->gps_provider_url = (string) CompanySetting::get('gps_provider_url', env('TRASPORTO_GPS_PROVIDER_URL', ''));
        $this->gps_stub_mode    = (bool) CompanySetting::get('gps_stub_mode', (bool) env('TRASPORTO_GPS_STUB', true));
        $this->mud_endpoint     = (string) CompanySetting::get('mud_endpoint', env('MUD_TELEMATICO_BASE_URL', ''));
        $this->mud_stub_mode    = (bool) CompanySetting::get('mud_stub_mode', (bool) env('MUD_TELEMATICO_STUB', true));
        $this->shop_enabled     = (bool) CompanySetting::get('shop_enabled', (bool) env('SHOP_ENABLED', false));
    }

    private function loadSistema(): void
    {
        $this->demo_mode  = (bool) CompanySetting::get('demo_mode', (bool) env('APP_DEMO_MODE', false));
        $this->app_debug  = (bool) CompanySetting::get('app_debug', (bool) env('APP_DEBUG', false));
        $this->log_level  = (string) CompanySetting::get('log_level', env('LOG_LEVEL', 'debug'));
    }
}
