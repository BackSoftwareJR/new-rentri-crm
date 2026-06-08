<?php

namespace App\Models;

use App\Models\Concerns\HasDemoScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmontaggioRicambio extends Model
{
    use HasDemoScope;

    protected $table = 'smontaggio_ricambi';

    protected $fillable = [
        'smontaggio_session_id',
        'numero_parte',
        'descrizione',
        'condizione',
        'valore_stimato',
        'foto_path',
    ];

    protected function casts(): array
    {
        return [
            'valore_stimato' => 'decimal:2',
            'is_demo' => 'boolean',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(SmontaggioSession::class, 'smontaggio_session_id');
    }

    public function condizioneLabel(): string
    {
        return match ($this->condizione) {
            'buono' => 'Buono',
            'accettabile' => 'Accettabile',
            'per_ricambi' => 'Solo per ricambi',
            default => ucfirst($this->condizione),
        };
    }

    public function fotoUrl(): ?string
    {
        if (blank($this->foto_path)) {
            return null;
        }

        return route('operatore.ricambi.foto', $this);
    }
}
