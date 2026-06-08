<?php

namespace App\Models;

use App\Models\Concerns\HasDemoScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RentriTransmissione extends Model
{
    use HasDemoScope;

    protected $table = 'rentri_transmissioni';

    protected $fillable = [
        'periodo_da',
        'periodo_a',
        'payload_hash',
        'esito',
        'trasmesso_at',
        'note',
        'response_json',
        'is_demo',
    ];

    protected function casts(): array
    {
        return [
            'periodo_da'    => 'date',
            'periodo_a'     => 'date',
            'trasmesso_at'  => 'datetime',
            'response_json' => 'array',
            'is_demo'       => 'boolean',
        ];
    }

    public function movimenti(): HasMany
    {
        return $this->hasMany(RegistroMovimento::class, 'rentri_transmission_id');
    }
}
