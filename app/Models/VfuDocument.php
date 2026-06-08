<?php

namespace App\Models;

use App\Enums\VfuTipoDocumento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Procedural documents attached during the VFU acceptance wizard.
 *
 * This model maps to `vfu_documents` and is intentionally distinct from
 * VfuDocumento (`vfu_documenti`).  Each record here represents a typed,
 * legally required document uploaded as part of the acceptance workflow
 * (e.g. documento identità, carta di circolazione, certificato rottamazione
 * provvisorio/definitivo, delega).  The type is tracked via VfuTipoDocumento,
 * and there is intentionally no `uploaded_by` FK because these uploads are
 * performed by the system / wizard, not by a named operator.
 *
 * Do NOT consolidate with VfuDocumento — the two models serve different
 * purposes and use different enums (VfuTipoDocumento vs VfuAllegatoTipo).
 */
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
