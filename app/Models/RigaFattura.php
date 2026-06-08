<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RigaFattura extends Model
{
    protected $table = 'righe_fattura';

    protected $fillable = [
        'fattura_id',
        'descrizione',
        'quantita',
        'prezzo_unitario',
        'iva_percentuale',
        'totale_riga',
        'ordine',
    ];

    protected function casts(): array
    {
        return [
            'quantita'       => 'decimal:3',
            'prezzo_unitario' => 'decimal:2',
            'totale_riga'    => 'decimal:2',
            'iva_percentuale' => 'integer',
            'ordine'         => 'integer',
        ];
    }

    public function fattura(): BelongsTo
    {
        return $this->belongsTo(Fattura::class);
    }

    public function calcolaTotale(): void
    {
        $this->totale_riga = round((float) $this->quantita * (float) $this->prezzo_unitario, 2);
    }
}
