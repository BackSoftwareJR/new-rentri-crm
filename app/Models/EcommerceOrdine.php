<?php

namespace App\Models;

use App\Enums\OrdineEcommerceStato;
use App\Models\Concerns\HasDemoScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EcommerceOrdine extends Model
{
    use HasDemoScope;

    protected $table = 'ecommerce_ordini';

    protected $fillable = [
        'user_id',
        'stato',
        'totale',
        'righe',
        'checkout_token',
        'payment_gateway',
        'stripe_checkout_session_id',
        'payment_checkout_url',
        'pagamento_metodo',
        'note_checkout',
        'confermato_at',
        'annullato_at',
        'is_demo',
    ];

    protected function casts(): array
    {
        return [
            'stato'   => OrdineEcommerceStato::class,
            'totale'  => 'decimal:2',
            'righe'   => 'array',
            'confermato_at' => 'datetime',
            'annullato_at'  => 'datetime',
            'is_demo' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stripeWebhookEvents(): HasMany
    {
        return $this->hasMany(StripeWebhookEvent::class, 'ecommerce_ordine_id');
    }

    public function stripeDisputes(): HasMany
    {
        return $this->hasMany(StripeDispute::class, 'ordine_id');
    }

    public function fattura(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Fattura::class, 'ecommerce_ordine_id');
    }
}
