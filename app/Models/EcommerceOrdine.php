<?php

namespace App\Models;

use App\Enums\OrdineEcommerceStato;
use App\Models\Concerns\HasDemoScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
