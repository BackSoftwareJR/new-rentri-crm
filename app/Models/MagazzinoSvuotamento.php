<?php

namespace App\Models;

use App\Enums\SvuotamentoStato;
use App\Models\Concerns\HasDemoScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MagazzinoSvuotamento extends Model
{
    use HasDemoScope;

    protected $table = 'magazzino_svuotamenti';

    protected $fillable = [
        'codice_cer_id',
        'anagrafica_id',
        'trasportatore_anagrafica_id',
        'trasportatore_omesso',
        'stato',
        'quantita_kg',
        'quantita_impegnata_kg',
        'note_interne',
        'user_id',
        'is_demo',
    ];

    protected function casts(): array
    {
        return [
            'stato'                 => SvuotamentoStato::class,
            'quantita_kg'           => 'decimal:4',
            'quantita_impegnata_kg' => 'decimal:4',
            'trasportatore_omesso'  => 'boolean',
            'is_demo'               => 'boolean',
        ];
    }

    public function codiceCer(): BelongsTo
    {
        return $this->belongsTo(CodiceCer::class, 'codice_cer_id');
    }

    /** Impianto di destinazione (scarico / ricezione). */
    public function impianto(): BelongsTo
    {
        return $this->belongsTo(Anagrafica::class, 'anagrafica_id');
    }

    public function trasportatore(): BelongsTo
    {
        return $this->belongsTo(Anagrafica::class, 'trasportatore_anagrafica_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trasporto(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Trasporto::class, 'magazzino_svuotamento_id');
    }
}
