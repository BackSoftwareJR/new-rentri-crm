<?php

namespace App\Models;

use Database\Factories\AuthorizationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Authorization extends Model
{
    /** @use HasFactory<AuthorizationFactory> */
    use HasFactory;
    protected $fillable = [
        'anagrafica_id',
        'numero',
        'rilasciata_il',
        'scade_il',
        'tipo',
        'documento_path',
    ];

    protected function casts(): array
    {
        return [
            'rilasciata_il' => 'date',
            'scade_il'      => 'date',
        ];
    }

    public function anagrafica(): BelongsTo
    {
        return $this->belongsTo(Anagrafica::class, 'anagrafica_id');
    }
}
