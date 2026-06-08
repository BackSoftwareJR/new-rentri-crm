<?php

namespace App\Models;

use App\Models\Concerns\HasDemoScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EcommerceProdotto extends Model
{
    /** @use HasFactory<\Database\Factories\EcommerceProdottoFactory> */
    use HasDemoScope;
    use HasFactory;

    protected $table = 'ecommerce_prodotti';

    protected $fillable = [
        'codice',
        'nome',
        'descrizione',
        'categoria',
        'prezzo',
        'giacenza',
        'vfu_registration_id',
        'attivo',
        'immagine_path',
        'is_demo',
    ];

    protected function casts(): array
    {
        return [
            'prezzo'   => 'decimal:2',
            'giacenza' => 'integer',
            'attivo'   => 'boolean',
            'is_demo'  => 'boolean',
        ];
    }

    public function vfuRegistration(): BelongsTo
    {
        return $this->belongsTo(VfuRegistration::class, 'vfu_registration_id');
    }

    public function fotoOperatore(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EcommerceProdottoFotoOperatore::class, 'ecommerce_prodotto_id');
    }
}
