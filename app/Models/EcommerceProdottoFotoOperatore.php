<?php

namespace App\Models;

use App\Models\Concerns\HasDemoScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EcommerceProdottoFotoOperatore extends Model
{
    use HasDemoScope;

    protected $table = 'ecommerce_prodotto_foto_operatore';

    protected $fillable = [
        'ecommerce_prodotto_id',
        'uploaded_by',
        'path',
        'is_demo',
    ];

    protected function casts(): array
    {
        return [
            'is_demo' => 'boolean',
        ];
    }

    public function prodotto(): BelongsTo
    {
        return $this->belongsTo(EcommerceProdotto::class, 'ecommerce_prodotto_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
