<?php

namespace App\Models;

use App\Models\Concerns\HasDemoScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditExportRun extends Model
{
    use HasDemoScope;

    protected $table = 'audit_export_runs';

    protected $fillable = [
        'export_id',
        'disk',
        'path',
        'checksum_sha256',
        'row_count',
        'file_size',
        'status',
        'period_from',
        'period_to',
        'dry_run',
        'triggered_by',
        'is_demo',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'row_count'   => 'integer',
            'file_size'   => 'integer',
            'dry_run'     => 'boolean',
            'is_demo'     => 'boolean',
            'period_from' => 'date',
            'period_to'   => 'date',
            'expires_at'  => 'datetime',
        ];
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
