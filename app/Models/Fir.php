<?php

namespace App\Models;

use App\Enums\FirStato;
use App\Models\Concerns\HasDemoScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fir extends Model
{
    use HasDemoScope;

    protected $table = 'firs';

    protected $fillable = [
        'numero_fir',
        'codice_blocco',
        'progressivo',
        'stato',
        'vidimato_at',
        'firmato_at',
        'qr_payload',
        'xfir_payload',
        'xfir_signed_payload',
        'trasporto_id',
        'peso_partenza_kg',
        'peso_arrivo_kg',
    ];

    /** @var list<string> */
    protected $guarded = [
        'id',
        'is_demo',
        'xfir_trasmesso_at',
        'xfir_protocollo',
        'xfir_transazione_id',
    ];

    protected function casts(): array
    {
        return [
            'stato'             => FirStato::class,
            'progressivo'       => 'integer',
            'vidimato_at'       => 'datetime',
            'firmato_at'        => 'datetime',
            'xfir_trasmesso_at' => 'datetime',
            'peso_partenza_kg'  => 'decimal:4',
            'peso_arrivo_kg'    => 'decimal:4',
            'is_demo'           => 'boolean',
        ];
    }

    public function trasporto(): BelongsTo
    {
        return $this->belongsTo(Trasporto::class, 'trasporto_id');
    }

    public function blocco(): BelongsTo
    {
        return $this->belongsTo(FirBlocco::class, 'codice_blocco', 'codice_blocco');
    }
}
