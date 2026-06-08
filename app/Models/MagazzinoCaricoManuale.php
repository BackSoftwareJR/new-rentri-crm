<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MagazzinoCaricoManuale extends Model
{
    protected $table = 'magazzino_carichi_manuali';

    protected $fillable = [
        'codice_cer_id',
        'peso_kg',
        'data',
        'note',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'peso_kg' => 'decimal:4',
            'data'    => 'date',
        ];
    }

    public function codiceCer(): BelongsTo
    {
        return $this->belongsTo(CodiceCer::class, 'codice_cer_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
