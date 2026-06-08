<?php

namespace App\Models;

use App\Enums\VfuAllegatoTipo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * General-purpose attachments uploaded by operators from the VFU detail page.
 *
 * This model maps to `vfu_documenti` and is intentionally distinct from
 * VfuDocument (`vfu_documents`).  Each record here represents a free-form
 * attachment (foto, verbale, contratto, altro) uploaded by an authenticated
 * operator after the acceptance phase.  The `uploaded_by` FK tracks which
 * user performed the upload; the type is tracked via VfuAllegatoTipo.
 * Managed by VfuDocumentoService.
 *
 * Do NOT consolidate with VfuDocument — the two models serve different
 * purposes and use different enums (VfuAllegatoTipo vs VfuTipoDocumento).
 */
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
