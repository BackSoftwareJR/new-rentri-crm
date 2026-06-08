<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BonificaVfuMovimento extends Model
{
    protected $table = 'bonifica_vfu_movimenti';

    protected $fillable = [
        'bonifica_vfu_id',
        'codice_cer_id',
        'quantita',
        'peso_kg',
        'um',
    ];

    protected function casts(): array
    {
        return [
            'quantita' => 'decimal:4',
            'peso_kg'  => 'decimal:2',
        ];
    }

    public function bonifica(): BelongsTo
    {
        return $this->belongsTo(BonificaVfu::class, 'bonifica_vfu_id');
    }

    public function codiceCer(): BelongsTo
    {
        return $this->belongsTo(CodiceCer::class, 'codice_cer_id');
    }
}
