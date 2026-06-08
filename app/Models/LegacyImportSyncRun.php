<?php

namespace App\Models;

use App\Models\Concerns\HasDemoScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegacyImportSyncRun extends Model
{
    use HasDemoScope;

    protected $table = 'legacy_import_sync_runs';

    protected $fillable = [
        'run_id',
        'status',
        'dry_run',
        'entities',
        'diff_summary',
        'total_new',
        'total_updated',
        'total_skipped',
        'total_errors',
        'triggered_by',
        'is_demo',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'dry_run'       => 'boolean',
            'entities'      => 'array',
            'diff_summary'  => 'array',
            'is_demo'       => 'boolean',
            'started_at'    => 'datetime',
            'finished_at'   => 'datetime',
        ];
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }
}
