<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'trace_id',
        'level',
        'module',
        'channel',
        'action',
        'message',
        'entity_type',
        'entity_id',
        'user_id',
        'demo_mode',
        'outcome',
        'duration_ms',
        'context',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'demo_mode'  => 'boolean',
            'context'    => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
