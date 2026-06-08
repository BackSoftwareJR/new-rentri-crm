<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BonificaVfu extends Model
{
    protected $table = 'bonifica_vfu';

    protected $fillable = [
        'vfu_registration_id',
        'stato',
        'fase',
        'checklist_pericolosi',
        'data_inizio',
        'data_completamento',
    ];

    protected function casts(): array
    {
        return [
            'checklist_pericolosi' => 'array',
            'data_inizio'          => 'datetime',
            'data_completamento'   => 'datetime',
        ];
    }

    public function vfuRegistration(): BelongsTo
    {
        return $this->belongsTo(VfuRegistration::class, 'vfu_registration_id');
    }

    public function movimenti(): HasMany
    {
        return $this->hasMany(BonificaVfuMovimento::class, 'bonifica_vfu_id');
    }
}
