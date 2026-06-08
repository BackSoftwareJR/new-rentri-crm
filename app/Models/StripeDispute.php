<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StripeDispute extends Model
{
    protected $table = 'stripe_disputes';

    protected $fillable = [
        'stripe_dispute_id',
        'ordine_id',
        'amount',
        'currency',
        'reason',
        'status',
        'evidence_due_by',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount'          => 'integer',
            'evidence_due_by' => 'datetime',
            'metadata'        => 'array',
        ];
    }

    public function ordine(): BelongsTo
    {
        return $this->belongsTo(EcommerceOrdine::class, 'ordine_id');
    }

    public function amountEur(): float
    {
        return round($this->amount / 100, 2);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['warning_needs_response', 'needs_response', 'under_review'], true);
    }

    public function evidenceDueSoon(int $days = 3): bool
    {
        return $this->evidence_due_by !== null
            && $this->evidence_due_by->diffInDays(now()) <= $days
            && $this->evidence_due_by->isFuture();
    }

    public function scopeOpen(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereIn('status', ['warning_needs_response', 'needs_response', 'under_review']);
    }
}
