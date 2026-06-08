<?php

namespace App\Models;

use App\Models\Concerns\HasDemoScope;
use App\Support\Demo\DemoContext;
use App\Support\Sito\SitoContext;
use Illuminate\Database\Eloquent\Model;

class RentriSetting extends Model
{
    use HasDemoScope;

    protected $table = 'rentri_settings';

    protected $fillable = [
        'sito_id',
        'ambiente',
        'cf',
        'cf_operatore',
        'piva',
        'ragione_sociale',
        'num_iscr_sito',
        'registro_vidimato_at',
        'cert_path_encrypted',
        'cert_password_encrypted',
        'cert_scadenza',
        'firma_cert_path_encrypted',
        'firma_cert_password_encrypted',
        'firma_cert_scadenza',
        'onboarding_step_completed',
        'last_health_check_at',
        'last_health_status',
        'note_operatore',
        'live_mode_enabled_at',
        'firma_live_enabled_at',
        'is_demo',
    ];

    protected function casts(): array
    {
        return [
            'registro_vidimato_at'        => 'datetime',
            'cert_scadenza'               => 'date',
            'firma_cert_scadenza'         => 'date',
            'onboarding_step_completed'   => 'integer',
            'last_health_check_at'        => 'datetime',
            'last_health_status'          => 'array',
            'live_mode_enabled_at'        => 'datetime',
            'firma_live_enabled_at'       => 'datetime',
            'is_demo'                     => 'boolean',
        ];
    }

    public function sito(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Sito::class);
    }

    /** Ritorna la configurazione per sito attivo e modalità corrente (demo o produzione). */
    public static function instance(): self
    {
        $isDemo = DemoContext::isActive();
        $sitoId = SitoContext::activeSitoId();
        $key = ($isDemo ? 'demo' : 'prod').':'.($sitoId ?? 'global');

        if (isset(static::$instanceCache[$key])) {
            $found = static::query()->find(static::$instanceCache[$key]);

            if ($found !== null) {
                return $found;
            }

            unset(static::$instanceCache[$key]);
        }

        $baseQuery = static::query()->where('is_demo', $isDemo);

        if ($sitoId !== null) {
            $setting = (clone $baseQuery)->where('sito_id', $sitoId)->first();

            if ($setting !== null) {
                static::$instanceCache[$key] = $setting->getKey();

                return $setting;
            }
        }

        $setting = (clone $baseQuery)->whereNull('sito_id')->first();

        if ($setting === null) {
            $setting = static::query()->create([
                'is_demo'  => $isDemo,
                'sito_id'  => $sitoId,
                'ambiente' => 'sandbox',
            ]);
        }

        static::$instanceCache[$key] = $setting->getKey();

        return $setting;
    }

    public static function flushInstanceCache(): void
    {
        static::$instanceCache = [];
    }

    /** @var array<string, int|string> */
    private static array $instanceCache = [];
}
