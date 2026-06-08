<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MagazzinoRifiuto extends Model
{
    protected $table = 'magazzino_rifiuti';

    protected $fillable = [
        'codice_cer_id',
        'quantita_attuale_kg',
        'oldest_load_date',
    ];

    protected function casts(): array
    {
        return [
            'quantita_attuale_kg' => 'decimal:4',
            'oldest_load_date'    => 'date',
        ];
    }

    public function codiceCer(): BelongsTo
    {
        return $this->belongsTo(CodiceCer::class, 'codice_cer_id');
    }
}
