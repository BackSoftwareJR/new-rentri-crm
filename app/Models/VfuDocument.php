<?php

namespace App\Models;

use App\Enums\VfuTipoDocumento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VfuDocument extends Model
{
    protected $table = 'vfu_documents';

    protected $fillable = [
        'vfu_registration_id',
        'tipo',
        'path',
        'original_name',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => VfuTipoDocumento::class,
        ];
    }

    public function vfuRegistration(): BelongsTo
    {
        return $this->belongsTo(VfuRegistration::class, 'vfu_registration_id');
    }
}
