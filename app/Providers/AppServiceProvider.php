<?php

namespace App\Providers;

use App\Domain\Azienda\AziendaSettingService;
use App\Services\Rentri\Contracts\RentriApiClientInterface;
use App\Services\Rentri\Contracts\RentriCertificateServiceInterface;
use App\Services\Rentri\Contracts\RentriCodificheSyncInterface;
use App\Services\Rentri\Contracts\RentriFirBlocchiSyncInterface;
use App\Services\Rentri\Contracts\RentriFirSigningServiceInterface;
use App\Services\Rentri\Contracts\RentriFirmaCertificateServiceInterface;
use App\Services\Rentri\Contracts\RentriFirServiceInterface;
use App\Services\Rentri\Contracts\RentriRegistryServiceInterface;
use App\Services\Rentri\RentriApiClient;
use App\Services\Rentri\RentriCertificateService;
use App\Services\Rentri\RentriCodificheSync;
use App\Services\Rentri\RentriFirBlocchiSync;
use App\Services\Rentri\RentriFirSigningService;
use App\Services\Rentri\RentriFirmaCertificateService;
use App\Services\Rentri\RentriFirService;
use App\Services\Rentri\RentriRegistryService;
use App\Services\Rentri\Contracts\RentriXfirTransmissionServiceInterface;
use App\Services\Rentri\RentriXfirTransmissionService;
use App\Services\Pec\PecMailService;
use App\Support\Horizon\HorizonMonitorService;
use App\Support\DashboardReport;
use App\Support\NotificationSettings;
use App\Support\TwoFactorSettings;
use App\Support\Operatore\OperatoreNavBadgeService;
use Illuminate\Support\Facades\View;
use App\Support\Logging\RequestContext;
use App\Domain\Infrastructure\ApplicationHealthService;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use App\Models\Anagrafica;
use App\Domain\Dashboard\DashboardKpiCacheInvalidator;
use App\Domain\Demo\DemoModeSessionService;
use App\Models\CodiceCer;
use App\Models\User;
use App\Models\VfuRegistration;
use App\Models\VfuDocumento;
use App\Models\RegistroMovimento;
use App\Models\ApplicationLog;
use App\Policies\ActivityPolicy;
use App\Policies\ApplicationLogPolicy;
use App\Policies\AnagraficaPolicy;
use App\Policies\CodiceCerPolicy;
use App\Policies\MagazzinoPolicy;
use App\Models\MagazzinoSvuotamento;
use App\Models\EcommerceOrdine;
use App\Models\EcommerceProdotto;
use App\Models\MudDichiarazione;
use App\Models\RentriSetting;
use App\Models\Sito;
use App\Models\RentriTransmissione;
use App\Models\RentriTransazione;
use App\Models\Fattura;
use App\Policies\FatturaPolicy;
use App\Policies\EcommerceOrdinePolicy;
use App\Policies\EcommerceProdottoPolicy;
use App\Policies\MudDichiarazionePolicy;
use App\Policies\MagazzinoSvuotamentoPolicy;
use App\Policies\RegistroMovimentoPolicy;
use App\Policies\DashboardReportPolicy;
use App\Policies\NotificationSettingsPolicy;
use App\Policies\TwoFactorSettingsPolicy;
use App\Policies\RentriSettingPolicy;
use App\Policies\SitoPolicy;
use App\Policies\RentriTransazionePolicy;
use App\Policies\RentriTransmissionePolicy;
use App\Policies\UserPolicy;
use App\Policies\VfuRegistrationPolicy;
use App\Policies\VfuDocumentoPolicy;
use App\Models\Fir;
use App\Models\FirBlocco;
use App\Models\SmontaggioSession;
use App\Models\Trasporto;
use App\Policies\BonificaPolicy;
use App\Policies\FirBloccoPolicy;
use App\Policies\FirPolicy;
use App\Policies\SmontaggioPolicy;
use App\Policies\TrasportoPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use App\Http\Livewire\GlobalSearch;
use App\Http\Livewire\NotificationBell;
use App\Http\Livewire\TimelineWidget;
use Livewire\Livewire;
use Livewire\Mechanisms\HandleRequests\EndpointResolver;
use Spatie\Activitylog\Models\Activity;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(RentriApiClientInterface::class, RentriApiClient::class);
        $this->app->singleton(RentriCertificateServiceInterface::class, RentriCertificateService::class);
        $this->app->singleton(RentriRegistryServiceInterface::class, RentriRegistryService::class);
        $this->app->singleton(RentriFirServiceInterface::class, RentriFirService::class);
        $this->app->singleton(RentriCodificheSyncInterface::class, RentriCodificheSync::class);
        $this->app->singleton(RentriFirBlocchiSyncInterface::class, RentriFirBlocchiSync::class);
        $this->app->singleton(RentriFirmaCertificateServiceInterface::class, RentriFirmaCertificateService::class);
        $this->app->singleton(RentriFirSigningServiceInterface::class, RentriFirSigningService::class);
        $this->app->singleton(RentriXfirTransmissionServiceInterface::class, RentriXfirTransmissionService::class);
        $this->app->singleton(\App\Domain\Ecommerce\Contracts\StripeCheckoutClientInterface::class, \App\Domain\Ecommerce\StripeCheckoutClient::class);
        $this->app->singleton(AziendaSettingService::class);
        $this->app->singleton(PecMailService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Activity::class, ActivityPolicy::class);
        Gate::policy(ApplicationLog::class, ApplicationLogPolicy::class);
        Gate::policy(Anagrafica::class, AnagraficaPolicy::class);
        Gate::policy(CodiceCer::class, CodiceCerPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(VfuRegistration::class, VfuRegistrationPolicy::class);
        Gate::policy(VfuDocumento::class, VfuDocumentoPolicy::class);
        Gate::policy(RegistroMovimento::class, RegistroMovimentoPolicy::class);
        Gate::policy(RentriTransmissione::class, RentriTransmissionePolicy::class);
        Gate::policy(RentriTransazione::class, RentriTransazionePolicy::class);
        Gate::policy(RentriSetting::class, RentriSettingPolicy::class);
        Gate::policy(Sito::class, SitoPolicy::class);
        Gate::policy(DashboardReport::class, DashboardReportPolicy::class);
        Gate::policy(NotificationSettings::class, NotificationSettingsPolicy::class);
        Gate::policy(TwoFactorSettings::class, TwoFactorSettingsPolicy::class);
        Gate::policy(MagazzinoSvuotamento::class, MagazzinoSvuotamentoPolicy::class);
        Gate::policy(MudDichiarazione::class, MudDichiarazionePolicy::class);
        Gate::policy(EcommerceProdotto::class, EcommerceProdottoPolicy::class);
        Gate::policy(Fattura::class, FatturaPolicy::class);
        Gate::policy(EcommerceOrdine::class, EcommerceOrdinePolicy::class);
        Gate::policy(Trasporto::class, TrasportoPolicy::class);
        Gate::policy(Fir::class, FirPolicy::class);
        Gate::policy(FirBlocco::class, FirBloccoPolicy::class);

        $firPolicy = app(FirPolicy::class);
        Gate::define('fir.vidima', fn ($user) => $firPolicy->before($user, 'vidima') ?? $firPolicy->vidima($user));
        Gate::define('fir.firma', fn ($user) => $firPolicy->before($user, 'firma') ?? $firPolicy->firma($user));

        $magazzinoPolicy = app(MagazzinoPolicy::class);
        Gate::define('magazzino.viewAny', fn ($user) => $magazzinoPolicy->before($user, 'viewAny') ?? $magazzinoPolicy->viewAny($user));
        Gate::define('magazzino.view', fn ($user, CodiceCer $cer) => $magazzinoPolicy->before($user, 'view') ?? $magazzinoPolicy->view($user, $cer));
        Gate::define('magazzino.caricoManuale', fn ($user, CodiceCer $cer) => $magazzinoPolicy->before($user, 'caricoManuale') ?? $magazzinoPolicy->caricoManuale($user, $cer));
        Gate::define('magazzino.richiediSvuotamento', fn ($user, CodiceCer $cer) => $magazzinoPolicy->before($user, 'richiediSvuotamento') ?? $magazzinoPolicy->richiediSvuotamento($user, $cer));

        $trasportoPolicy = app(TrasportoPolicy::class);
        Gate::define('trasporto.complete', fn ($user, Trasporto $trasporto) => $trasportoPolicy->before($user, 'complete') ?? $trasportoPolicy->complete($user, $trasporto));

        $codiceCerPolicy = app(CodiceCerPolicy::class);
        Gate::define('codice-cer.sync-rentri', fn ($user) => $codiceCerPolicy->before($user, 'syncRentri') ?? $codiceCerPolicy->syncRentri($user));

        Gate::define('demo.toggle', fn ($user) => app(DemoModeSessionService::class)->canToggle($user));

        $bonificaPolicy = app(BonificaPolicy::class);
        Gate::define('bonifica.viewAny', fn (User $user) => $bonificaPolicy->viewAny($user));
        Gate::define('bonifica.perform', fn (User $user, VfuRegistration $vfu) => $bonificaPolicy->perform($user, $vfu));
        Gate::define('bonifica.saveChecklist', fn (User $user, VfuRegistration $vfu) => $bonificaPolicy->saveChecklist($user, $vfu));
        Gate::define('bonifica.advancePericolosi', fn (User $user, VfuRegistration $vfu) => $bonificaPolicy->advancePericolosi($user, $vfu));

        $smontaggioPolicy = app(SmontaggioPolicy::class);
        Gate::define('smontaggio.viewAny', fn (User $user) => $smontaggioPolicy->viewAny($user));
        Gate::define('smontaggio.avvia', fn (User $user, VfuRegistration $vfu) => $smontaggioPolicy->avvia($user, $vfu));
        Gate::define('smontaggio.gestisci', fn (User $user, SmontaggioSession $session) => $smontaggioPolicy->gestisci($user, $session));
        Gate::define('smontaggio.completa', fn (User $user, SmontaggioSession $session) => $smontaggioPolicy->completa($user, $session));

        $legacySyncPolicy = app(\App\Policies\LegacyImportSyncPolicy::class);
        Gate::define('legacy.sync', fn (User $user) => $legacySyncPolicy->sync($user));
        Gate::define('legacy.viewRuns', fn (User $user) => $legacySyncPolicy->viewRuns($user));

        Livewire::component('notification-bell', NotificationBell::class);
        Livewire::component('global-search', GlobalSearch::class);
        Livewire::component('timeline-widget', TimelineWidget::class);
        Livewire::component('sito-switcher', \App\Http\Livewire\SitoSwitcher::class);
        Livewire::component('shop-cart', \App\Http\Livewire\Shop\ShopCart::class);
        Livewire::component('segreteria.vfu.vfu-import-csv', \App\Http\Livewire\Segreteria\Vfu\VfuImportCsv::class);

        Livewire::setUpdateRoute(function ($handle, $path) {
            return Route::post($path, $handle)
                ->middleware(['web', 'auth'])
                ->name('default-livewire.update');
        });

        Livewire::addPersistentMiddleware([
            \Illuminate\Auth\Middleware\Authenticate::class,
        ]);

        View::composer('layouts.operatore', function ($view) {
            $badges = app(OperatoreNavBadgeService::class);
            $view->with([
                'bonificaNavBadge'    => $badges->bonificaPendingCount(),
                'smontaggioNavBadge'  => $badges->smontaggioPendingCount(),
            ]);
        });

        View::composer(['layouts.segreteria', 'components.topbar'], function ($view) {
            $horizon = app(HorizonMonitorService::class);
            $view->with([
                'horizonMonitorAvailable' => $horizon->isInstalled(),
                'horizonMonitorUrl'       => $horizon->dashboardUrl(),
                'horizonMonitorCanAccess' => $horizon->canAccess(),
            ]);
        });

        app(DashboardKpiCacheInvalidator::class)->register();

        Event::listen(DiagnosingHealth::class, function (): void {
            app(ApplicationHealthService::class)->assertBootstrapHealthy();
        });

        Queue::createPayloadUsing(function (string $connection, ?string $queue, array $payload): array {
            return array_merge($payload, [
                'traceId' => RequestContext::traceId(),
            ]);
        });

        Event::listen(JobProcessing::class, function (JobProcessing $event): void {
            $traceId = $event->job->payload()['traceId'] ?? null;

            if (is_string($traceId) && $traceId !== '') {
                RequestContext::setTraceId($traceId);
                Log::shareContext(['trace_id' => $traceId]);
            }
        });
    }
}
