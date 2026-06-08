<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StripeWebhookEvent extends Model
{
    protected $fillable = [
        'stripe_event_id',
        'event_type',
        'ecommerce_ordine_id',
        'checkout_session_id',
        'reconciliation',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'reconciliation' => 'array',
            'processed_at'   => 'datetime',
        ];
    }

    public function ordine(): BelongsTo
    {
        return $this->belongsTo(EcommerceOrdine::class, 'ecommerce_ordine_id');
    }
}
