<?php

namespace App\Domain\Rentri;

use App\Models\RentriSetting;
use Illuminate\Support\Carbon;

final class RentriCertPreviewService
{
    /**
     * @return array{
     *   mtls: array<string, mixed>,
     *   firma: array<string, mixed>
     * }
     */
    public function previews(RentriSetting $settings): array
    {
        return [
            'mtls'  => $this->preview(
                'Certificato interoperabilità (mTLS)',
                $settings->cert_path_encrypted,
                $settings->cert_scadenza,
            ),
            'firma' => $this->preview(
                'Certificato firma remota xFIR',
                $settings->firma_cert_path_encrypted,
                $settings->firma_cert_scadenza,
            ),
        ];
    }

    /**
     * @return array{
     *   label: string,
     *   state: string,
     *   badge: string,
     *   message: string,
     *   scadenza: string|null,
     *   days_remaining: int|null
     * }
     */
    public function preview(string $label, ?string $pathEncrypted, ?Carbon $scadenza): array
    {
        if (blank($pathEncrypted)) {
            return [
                'label'          => $label,
                'state'          => 'missing',
                'badge'          => 'danger',
                'message'        => 'Non caricato',
                'scadenza'       => null,
                'days_remaining' => null,
            ];
        }

        if ($scadenza === null) {
            return [
                'label'          => $label,
                'state'          => 'configured',
                'badge'          => 'success',
                'message'        => 'Configurato — scadenza non disponibile',
                'scadenza'       => null,
                'days_remaining' => null,
            ];
        }

        $formatted = $scadenza->format('d/m/Y');
        $daysRemaining = (int) now()->startOfDay()->diffInDays($scadenza->startOfDay(), false);

        if ($scadenza->isPast()) {
            return [
                'label'          => $label,
                'state'          => 'expired',
                'badge'          => 'danger',
                'message'        => 'Scaduto il '.$formatted,
                'scadenza'       => $formatted,
                'days_remaining' => $daysRemaining,
            ];
        }

        if ($daysRemaining <= 30) {
            return [
                'label'          => $label,
                'state'          => 'expiring',
                'badge'          => 'warning',
                'message'        => 'Scade il '.$formatted.' ('.$daysRemaining.' gg)',
                'scadenza'       => $formatted,
                'days_remaining' => $daysRemaining,
            ];
        }

        return [
            'label'          => $label,
            'state'          => 'valid',
            'badge'          => 'success',
            'message'        => 'Valido fino al '.$formatted,
            'scadenza'       => $formatted,
            'days_remaining' => $daysRemaining,
        ];
    }
}
