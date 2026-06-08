<?php

namespace App\Models;

use App\Enums\VfuStato;
use App\Models\Concerns\HasDemoScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class VfuRegistration extends Model
{
    /** @use HasFactory<\Database\Factories\VfuRegistrationFactory> */
    use HasDemoScope;
    use HasFactory;

    protected $table = 'vfu_registrations';

    protected $fillable = [
        'tipo_veicolo',
        'nazione',
        'targa',
        'telaio',
        'codice_motore',
        'marca',
        'modello',
        'nome',
        'cognome',
        'proprietario',
        'codice_fiscale',
        'regione',
        'indirizzo',
        'comune',
        'provincia',
        'data_nascita',
        'luogo_nascita',
        'stato',
        'certificato_provvisorio_caricato',
        'peso_kg',
        'data_consegna',
        'agenzia_anagrafica_id',
        'note',
    ];

    /** @var list<string> */
    protected $guarded = [
        'id',
        'is_demo',
        'data_accettazione',
        'data_invio_agenzia',
        'bonifica_pericolosi_completata_at',
    ];

    protected function casts(): array
    {
        return [
            'stato'                             => VfuStato::class,
            'peso_kg'                           => 'decimal:2',
            'data_consegna'                     => 'date',
            'data_accettazione'                 => 'date',
            'data_nascita'                      => 'date',
            'data_invio_agenzia'                => 'datetime',
            'certificato_provvisorio_caricato'  => 'boolean',
            'bonifica_pericolosi_completata_at' => 'datetime',
            'is_demo'                           => 'boolean',
        ];
    }

    public function documents(): HasMany
    {
        return $this->hasMany(VfuDocument::class, 'vfu_registration_id');
    }

    public function documenti(): HasMany
    {
        return $this->hasMany(VfuDocumento::class, 'vfu_registration_id');
    }

    public function bonifica(): HasOne
    {
        return $this->hasOne(BonificaVfu::class, 'vfu_registration_id');
    }

    public function agenzia(): BelongsTo
    {
        return $this->belongsTo(Anagrafica::class, 'agenzia_anagrafica_id');
    }

    public function registroMovimenti(): MorphMany
    {
        return $this->morphMany(RegistroMovimento::class, 'source', 'source_type', 'source_id');
    }

    public function veicoloLabel(): string
    {
        $parts = array_filter([$this->marca, $this->modello]);

        return $parts !== [] ? implode(' ', $parts) : '—';
    }
}
