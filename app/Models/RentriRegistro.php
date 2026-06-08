<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Registro annuale RENTRI (anno/vidimato/codice).
 *
 * @deprecated Questo model è orfano: nessun controller, service o comando lo
 *             istanzia. Il workflow di trasmissione registro è interamente
 *             gestito da {@see \App\Models\RentriTransmissione} (trasmissione
 *             periodica movimenti) e {@see \App\Models\RegistroMovimento}
 *             (singoli movimenti carico/scarico).
 *
 *             La tabella `rentri_registri` (anno + vidimato + codice_registro_rentri)
 *             risale a una prima progettazione (sprint 31) mai portata in produzione;
 *             è stata sostituita dall'approccio basato su trasmissioni con polling
 *             asincrono introdotto negli sprint 33–35.
 *
 *             TODO: remove in v2 — replaced by RentriTransmissione + RegistroMovimento.
 *             Prima della rimozione verificare che la migrazione
 *             2026_05_24_100010_create_rentri_registri_table.php non sia referenziata
 *             da alcun rollback critico, quindi eseguire `Schema::dropIfExists('rentri_registri')`.
 */
class RentriRegistro extends Model
{
    protected $table = 'rentri_registri';

    protected $fillable = [
        'anno',
        'vidimato',
        'codice_registro_rentri',
    ];

    protected function casts(): array
    {
        return [
            'anno'     => 'integer',
            'vidimato' => 'boolean',
        ];
    }
}
