<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Sito extends Model
{
    protected $table = 'siti';

    protected $fillable = [
        'nome',
        'indirizzo',
        'num_iscr_sito',
        'cf_operatore',
        'is_active',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function rentriSetting(): HasOne
    {
        return $this->hasOne(RentriSetting::class);
    }
}
