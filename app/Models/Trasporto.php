<?php

namespace App\Models;

use App\Enums\TrasportoStato;
use App\Models\Concerns\HasDemoScope;
use App\Support\Demo\DemoContext;
use App\Support\Demo\DemoIsolationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Trasporto extends Model
{
    use HasDemoScope;

    protected $table = 'trasporti';

    protected $fillable = [
        'magazzino_svuotamento_id',
        'codice_cer_id',
        'anagrafica_destinatario_id',
        'quantita_kg',
        'peso_destinazione_kg',
        'stato',
        'fir_partenza_path',
        'fir_arrivo_path',
        'note',
        'gps_last_position',
        'gps_tracked_at',
    ];

    /** @var list<string> */
    protected $guarded = [
        'id',
        'is_demo',
        'fir_id',
    ];

    protected function casts(): array
    {
        return [
            'stato'                => TrasportoStato::class,
            'quantita_kg'          => 'decimal:4',
            'peso_destinazione_kg' => 'decimal:4',
            'is_demo'              => 'boolean',
            'gps_last_position'    => 'array',
            'gps_tracked_at'       => 'datetime',
        ];
    }

    public function codiceCer(): BelongsTo
    {
        return $this->belongsTo(CodiceCer::class, 'codice_cer_id');
    }

    public function destinatario(): BelongsTo
    {
        return $this->belongsTo(Anagrafica::class, 'anagrafica_destinatario_id');
    }

    public function svuotamento(): BelongsTo
    {
        return $this->belongsTo(MagazzinoSvuotamento::class, 'magazzino_svuotamento_id');
    }

    public function fir(): BelongsTo
    {
        return $this->belongsTo(Fir::class, 'fir_id');
    }

    public function firCollegato(): HasOne
    {
        return $this->hasOne(Fir::class, 'trasporto_id');
    }

    protected static function booted(): void
    {
        static::creating(function (Trasporto $trasporto): void {
            static::assertSvuotamentoSameDemoMode($trasporto, $trasporto->magazzino_svuotamento_id);
        });

        static::updating(function (Trasporto $trasporto): void {
            if (! $trasporto->isDirty('magazzino_svuotamento_id')) {
                return;
            }

            static::assertSvuotamentoSameDemoMode($trasporto, $trasporto->magazzino_svuotamento_id);
        });
    }

    private static function assertSvuotamentoSameDemoMode(Trasporto $trasporto, ?int $svuotamentoId): void
    {
        if ($svuotamentoId === null) {
            return;
        }

        $svuotamento = MagazzinoSvuotamento::includingAllDemoModes()->find($svuotamentoId);

        if ($svuotamento === null) {
            return;
        }

        $trasportoDemo = array_key_exists('is_demo', $trasporto->getAttributes())
            ? (bool) $trasporto->is_demo
            : DemoContext::isActive();

        if ((bool) $svuotamento->is_demo !== $trasportoDemo) {
            throw DemoIsolationException::crossReference('trasporto', 'svuotamento magazzino');
        }
    }
}
