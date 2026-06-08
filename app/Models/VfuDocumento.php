<?php

namespace App\Models;

use App\Enums\VfuAllegatoTipo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VfuDocumento extends Model
{
    protected $table = 'vfu_documenti';

    protected $fillable = [
        'vfu_registration_id',
        'tipo',
        'path',
        'original_name',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => VfuAllegatoTipo::class,
        ];
    }

    public function vfuRegistration(): BelongsTo
    {
        return $this->belongsTo(VfuRegistration::class, 'vfu_registration_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
