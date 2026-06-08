<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class CompanySetting extends Model
{
    protected $table = 'company_settings';

    protected $fillable = ['key', 'value', 'type', 'is_sensitive', 'label', 'group'];

    protected function casts(): array
    {
        return [
            'is_sensitive' => 'boolean',
        ];
    }

    /** @var array<string, array<string, mixed>> */
    private static array $defaults = [
        // Azienda
        'company_ragione_sociale'   => ['type' => 'string', 'label' => 'Ragione Sociale',             'group' => 'azienda', 'is_sensitive' => false],
        'company_piva'              => ['type' => 'string', 'label' => 'Partita IVA',                  'group' => 'azienda', 'is_sensitive' => false],
        'company_cf'                => ['type' => 'string', 'label' => 'Codice Fiscale',               'group' => 'azienda', 'is_sensitive' => false],
        'company_indirizzo'         => ['type' => 'string', 'label' => 'Indirizzo',                    'group' => 'azienda', 'is_sensitive' => false],
        'company_cap'               => ['type' => 'string', 'label' => 'CAP',                          'group' => 'azienda', 'is_sensitive' => false],
        'company_citta'             => ['type' => 'string', 'label' => 'Città',                        'group' => 'azienda', 'is_sensitive' => false],
        'company_provincia'         => ['type' => 'string', 'label' => 'Provincia',                    'group' => 'azienda', 'is_sensitive' => false],
        'company_pec'               => ['type' => 'string', 'label' => 'PEC aziendale',                'group' => 'azienda', 'is_sensitive' => false],
        'company_email'             => ['type' => 'string', 'label' => 'Email ordinaria',              'group' => 'azienda', 'is_sensitive' => false],
        'company_telefono'          => ['type' => 'string', 'label' => 'Telefono',                     'group' => 'azienda', 'is_sensitive' => false],
        'company_num_albo'          => ['type' => 'string', 'label' => 'N. iscrizione Albo Gestori',   'group' => 'azienda', 'is_sensitive' => false],
        'company_codice_sdi'        => ['type' => 'string', 'label' => 'Codice SDI',                   'group' => 'azienda', 'is_sensitive' => false],
        'company_logo_path'         => ['type' => 'file',   'label' => 'Logo aziendale',               'group' => 'azienda', 'is_sensitive' => false],
        'company_formato_numerazione_fattura' => ['type' => 'string', 'label' => 'Formato numerazione fattura', 'group' => 'azienda', 'is_sensitive' => false],
        // Stripe
        'stripe_live_mode'          => ['type' => 'bool',   'label' => 'Modalità live Stripe',         'group' => 'pagamenti', 'is_sensitive' => false],
        'stripe_key'                => ['type' => 'string', 'label' => 'Stripe Publishable Key',       'group' => 'pagamenti', 'is_sensitive' => true],
        'stripe_secret'             => ['type' => 'string', 'label' => 'Stripe Secret Key',            'group' => 'pagamenti', 'is_sensitive' => true],
        'stripe_webhook_secret'     => ['type' => 'string', 'label' => 'Stripe Webhook Secret',        'group' => 'pagamenti', 'is_sensitive' => true],
        'stripe_dispute_stub'       => ['type' => 'bool',   'label' => 'Dispute stub mode',            'group' => 'pagamenti', 'is_sensitive' => false],
        'stripe_payment_card'       => ['type' => 'bool',   'label' => 'Pagamento con carta',          'group' => 'pagamenti', 'is_sensitive' => false],
        'stripe_payment_sepa'       => ['type' => 'bool',   'label' => 'Pagamento SEPA Debit',         'group' => 'pagamenti', 'is_sensitive' => false],
        // Email
        'mail_host'                 => ['type' => 'string', 'label' => 'SMTP Host',                    'group' => 'email', 'is_sensitive' => false],
        'mail_port'                 => ['type' => 'integer','label' => 'SMTP Port',                    'group' => 'email', 'is_sensitive' => false],
        'mail_username'             => ['type' => 'string', 'label' => 'SMTP Username',                'group' => 'email', 'is_sensitive' => false],
        'mail_password'             => ['type' => 'string', 'label' => 'SMTP Password',                'group' => 'email', 'is_sensitive' => true],
        'mail_encryption'           => ['type' => 'string', 'label' => 'Cifratura SMTP',               'group' => 'email', 'is_sensitive' => false],
        'mail_from_name'            => ['type' => 'string', 'label' => 'Nome mittente',                'group' => 'email', 'is_sensitive' => false],
        'mail_from_address'         => ['type' => 'string', 'label' => 'Indirizzo mittente',           'group' => 'email', 'is_sensitive' => false],
        'notifications_live'        => ['type' => 'bool',   'label' => 'Modalità live notifiche',      'group' => 'email', 'is_sensitive' => false],
        // Integrazioni
        'gps_provider_url'          => ['type' => 'string', 'label' => 'GPS Provider URL',             'group' => 'integrazioni', 'is_sensitive' => false],
        'gps_stub_mode'             => ['type' => 'bool',   'label' => 'GPS stub mode',                'group' => 'integrazioni', 'is_sensitive' => false],
        'mud_endpoint'              => ['type' => 'string', 'label' => 'MUD Telematico endpoint',      'group' => 'integrazioni', 'is_sensitive' => false],
        'mud_stub_mode'             => ['type' => 'bool',   'label' => 'MUD stub mode',                'group' => 'integrazioni', 'is_sensitive' => false],
        'shop_enabled'              => ['type' => 'bool',   'label' => 'Shop pubblico abilitato',      'group' => 'integrazioni', 'is_sensitive' => false],
        // Sistema
        'demo_mode'                 => ['type' => 'bool',   'label' => 'Modalità demo/palestra',       'group' => 'sistema', 'is_sensitive' => false],
        'app_debug'                 => ['type' => 'bool',   'label' => 'APP_DEBUG',                    'group' => 'sistema', 'is_sensitive' => false],
        'log_level'                 => ['type' => 'string', 'label' => 'Log level',                    'group' => 'sistema', 'is_sensitive' => false],
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        $cached = Cache::remember("company_setting:{$key}", 300, function () use ($key) {
            return static::query()->where('key', $key)->first();
        });

        if (! $cached) {
            return $default;
        }

        $value = $cached->value;

        if ($cached->is_sensitive && $value !== null) {
            try {
                $value = decrypt($value);
            } catch (\Throwable) {
                $value = null;
            }
        }

        return match ($cached->type) {
            'bool'    => filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? (bool) $value,
            'integer' => $value !== null ? (int) $value : null,
            'json'    => $value !== null ? json_decode($value, true) : null,
            default   => $value,
        };
    }

    public static function set(string $key, mixed $value): void
    {
        $meta = static::$defaults[$key] ?? [];

        $isSensitive = $meta['is_sensitive'] ?? false;
        $type        = $meta['type'] ?? 'string';

        $storedValue = match ($type) {
            'bool'    => $value ? '1' : '0',
            'json'    => is_array($value) ? json_encode($value) : $value,
            default   => (string) $value,
        };

        if ($isSensitive && $storedValue !== null) {
            $storedValue = encrypt($storedValue);
        }

        static::query()->updateOrCreate(
            ['key' => $key],
            [
                'value'        => $storedValue,
                'type'         => $type,
                'is_sensitive' => $isSensitive,
                'label'        => $meta['label'] ?? null,
                'group'        => $meta['group'] ?? null,
            ]
        );

        Cache::forget("company_setting:{$key}");
    }

    /** @return array<string, mixed> */
    public static function group(string $group): array
    {
        return collect(static::$defaults)
            ->filter(fn ($meta) => ($meta['group'] ?? null) === $group)
            ->mapWithKeys(fn ($meta, $key) => [$key => static::get($key)])
            ->all();
    }

    public static function seedDefaults(): void
    {
        foreach (static::$defaults as $key => $meta) {
            if (! static::query()->where('key', $key)->exists()) {
                static::set($key, null);
            }
        }
    }

    public static function isSensitive(string $key): bool
    {
        return (bool) (static::$defaults[$key]['is_sensitive'] ?? false);
    }

    /** @return array<string, array<string, mixed>> */
    public static function defaults(): array
    {
        return static::$defaults;
    }
}
