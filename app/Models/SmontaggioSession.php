<?php

namespace App\Models;

use App\Models\Concerns\HasDemoScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmontaggioSession extends Model
{
    use HasDemoScope;

    protected $table = 'smontaggio_sessions';

    protected $fillable = [
        'vfu_registration_id',
        'operatore_id',
        'stato',
        'note',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'is_demo' => 'boolean',
        ];
    }

    public function vfuRegistration(): BelongsTo
    {
        return $this->belongsTo(VfuRegistration::class, 'vfu_registration_id');
    }

    public function operatore(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operatore_id');
    }

    public function ricambi(): HasMany
    {
        return $this->hasMany(SmontaggioRicambio::class, 'smontaggio_session_id');
    }

    public function isCompletata(): bool
    {
        return $this->stato === 'completato';
    }
}
