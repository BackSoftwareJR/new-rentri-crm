<?php

namespace App\Models;

use App\Enums\VfuStato;
use App\Models\Concerns\HasDemoScope;
use App\Traits\BelongsToSito;
use Database\Factories\VfuRegistrationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class VfuRegistration extends Model
{
    /** @use HasFactory<VfuRegistrationFactory> */
    use BelongsToSito, HasDemoScope, HasFactory, SoftDeletes;

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
        'email_proprietario',
        'pec_proprietario',
        'codice_fiscale',
        'regione',
        'indirizzo',
        'comune',
        'provincia',
        'data_nascita',
        'luogo_nascita',
        'nazionalita_proprietario',
        'provincia_nascita',
        'tipo_documento_identita',
        'numero_documento_identita',
        'note_carrozzeria',
        'provenienza_veicolo',
        'targa_estera',
        'targa_estera_valore',
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
        'rottamato_at',
        'bonifica_pericolosi_completata_at',
    ];

    protected function casts(): array
    {
        return [
            'stato' => VfuStato::class,
            'peso_kg' => 'decimal:2',
            'data_consegna' => 'date',
            'data_accettazione' => 'date',
            'data_nascita' => 'date',
            'data_invio_agenzia' => 'datetime',
            'rottamato_at' => 'datetime',
            'certificato_provvisorio_caricato' => 'boolean',
            'targa_estera' => 'boolean',
            'bonifica_pericolosi_completata_at' => 'datetime',
            'is_demo' => 'boolean',
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

    public function smontaggioSessions(): HasMany
    {
        return $this->hasMany(SmontaggioSession::class, 'vfu_registration_id');
    }

    public function smontaggioAttivo(): HasOne
    {
        return $this->hasOne(SmontaggioSession::class, 'vfu_registration_id')
            ->whereIn('stato', ['avviato', 'in_corso'])
            ->latest();
    }

    public function agenzia(): BelongsTo
    {
        return $this->belongsTo(Anagrafica::class, 'agenzia_anagrafica_id');
    }

    public function operatoreAssegnato(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operatore_assegnato_id');
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
