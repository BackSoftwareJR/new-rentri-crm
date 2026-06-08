<?php

namespace App\Models;

use Database\Factories\CodiceCerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CodiceCer extends Model
{
    /** @use HasFactory<CodiceCerFactory> */
    use HasFactory;

    protected $table = 'codici_cer';

    protected $fillable = [
        'codice',
        'descrizione',
        'categoria',
        'um',
        'limite_kg',
        'rentri_codice_ref',
        'attivo',
    ];

    protected function casts(): array
    {
        return [
            'limite_kg' => 'decimal:2',
            'attivo'    => 'boolean',
        ];
    }

    public function bonificaMovimenti(): HasMany
    {
        return $this->hasMany(BonificaVfuMovimento::class, 'codice_cer_id');
    }

    public function magazzino(): HasOne
    {
        return $this->hasOne(MagazzinoRifiuto::class, 'codice_cer_id');
    }

    public function registroMovimenti(): HasMany
    {
        return $this->hasMany(RegistroMovimento::class, 'codice_cer_id');
    }

    public function carichiManuali(): HasMany
    {
        return $this->hasMany(MagazzinoCaricoManuale::class, 'codice_cer_id');
    }

    public function svuotamenti(): HasMany
    {
        return $this->hasMany(MagazzinoSvuotamento::class, 'codice_cer_id');
    }

    public function trasporti(): HasMany
    {
        return $this->hasMany(Trasporto::class, 'codice_cer_id');
    }
}
