<?php

namespace App\Models;

use App\Domain\Registro\Exceptions\RegistroMovimentoLockedException;
use App\Enums\RegistroMovimentoTipo;
use App\Models\Concerns\HasDemoScope;
use App\Traits\BelongsToSito;
use Database\Factories\RegistroMovimentoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class RegistroMovimento extends Model
{
    /** @use HasFactory<RegistroMovimentoFactory> */
    use BelongsToSito, HasDemoScope, HasFactory;

    /**
     * Valori ammessi per source_type (morph verso origine movimento registro).
     * Usare queste costanti per evitare stringhe magiche.
     */
    public const SOURCE_BONIFICA_MOVIMENTO = BonificaVfuMovimento::class;

    public const SOURCE_CARICO_MANUALE = MagazzinoCaricoManuale::class;

    public const SOURCE_TRASPORTO = Trasporto::class;

    public const SOURCE_VFU_REGISTRATION = VfuRegistration::class;

    protected $table = 'registro_movimenti';

    protected $fillable = [
        'tipo',
        'codice_cer_id',
        'peso_kg',
        'data_movimento',
        'source_type',
        'source_id',
        'note',
        'rentri_trasmesso',
        'rentri_transmission_id',
        'locked_at',
        'is_demo',
    ];

    protected function casts(): array
    {
        return [
            'tipo'              => RegistroMovimentoTipo::class,
            'peso_kg'           => 'decimal:4',
            'data_movimento'    => 'datetime',
            'rentri_trasmesso'  => 'boolean',
            'locked_at'         => 'datetime',
            'is_demo'           => 'boolean',
        ];
    }

    public function codiceCer(): BelongsTo
    {
        return $this->belongsTo(CodiceCer::class, 'codice_cer_id');
    }

    public function rentriTransmissione(): BelongsTo
    {
        return $this->belongsTo(RentriTransmissione::class, 'rentri_transmission_id');
    }

    public function source(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'source_type', 'source_id');
    }

    public function isLocked(): bool
    {
        return $this->locked_at !== null || $this->rentri_trasmesso;
    }

    protected static function booted(): void
    {
        static::updating(function (RegistroMovimento $movimento): void {
            if ($movimento->getOriginal('locked_at') !== null || (bool) $movimento->getOriginal('rentri_trasmesso')) {
                throw RegistroMovimentoLockedException::cannotModify();
            }
        });

        static::deleting(function (RegistroMovimento $movimento): void {
            if ($movimento->isLocked()) {
                throw RegistroMovimentoLockedException::cannotModify();
            }
        });
    }
}
