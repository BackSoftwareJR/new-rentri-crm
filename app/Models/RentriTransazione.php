<?php

namespace App\Models;

use App\Models\Concerns\HasDemoScope;
use Illuminate\Database\Eloquent\Model;

class RentriTransazione extends Model
{
    use HasDemoScope;

    protected $table = 'rentri_transazioni';

    protected $fillable = [
        'transazione_id',
        'tipo_api',
        'stato',
        'request_json',
        'response_json',
        'completed_at',
        'retry_count',
        'next_retry_at',
        'dead_letter_at',
        'is_demo',
    ];

    protected function casts(): array
    {
        return [
            'request_json'  => 'array',
            'response_json' => 'array',
            'completed_at'  => 'datetime',
            'next_retry_at'   => 'datetime',
            'dead_letter_at'  => 'datetime',
            'retry_count'     => 'integer',
            'is_demo'         => 'boolean',
        ];
    }
}
