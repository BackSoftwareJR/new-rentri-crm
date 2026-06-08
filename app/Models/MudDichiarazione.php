<?php

namespace App\Models;

use App\Enums\MudStato;
use App\Models\Concerns\HasDemoScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MudDichiarazione extends Model
{
    use HasDemoScope;

    protected $table = 'mud_dichiarazioni';

    protected $fillable = [
        'anno_riferimento',
        'stato',
        'righe',
        'export_payload',
        'completata_at',
        'inviata_at',
        'invio_protocollo',
        'invio_risposta',
        'user_id',
        'is_demo',
    ];

    protected function casts(): array
    {
        return [
            'stato'            => MudStato::class,
            'righe'            => 'array',
            'export_payload'   => 'array',
            'invio_risposta'   => 'array',
            'completata_at'    => 'datetime',
            'inviata_at'       => 'datetime',
            'anno_riferimento' => 'integer',
            'is_demo'          => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
