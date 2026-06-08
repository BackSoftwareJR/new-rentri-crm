<?php

namespace App\Http\Livewire\Admin;

use App\Models\RentriSetting;
use App\Models\RentriTransmissione;
use App\Services\Rentri\Contracts\RentriApiClientInterface;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;

/**
 * RENTRI status widget — usable as a standalone admin page or embedded component.
 *
 * Shows: API mode (sandbox/live), cert expiry days,
 * last health check status + time, last trasmissione status.
 *
 * Route: GET /admin/rentri-status (admin.rentri-status)
 * Embeddable as: @livewire('admin.rentri-status-widget')
 */
#[Title('Stato RENTRI')]
class RentriStatusWidget extends AdminPage
{
    public bool $refreshing = false;

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);
    }

    public function refreshHealthCheck(): void
    {
        $this->refreshing = true;

        try {
            $apiClient = app(RentriApiClientInterface::class);
            $result    = $apiClient->healthCheck();

            $settings = RentriSetting::instance();
            $settings->update([
                'last_health_check_at' => now(),
                'last_health_status'   => $result,
            ]);
        } catch (\Throwable $e) {
            $settings = RentriSetting::instance();
            $settings->update([
                'last_health_check_at' => now(),
                'last_health_status'   => ['status' => 'error', 'message' => $e->getMessage()],
            ]);
        } finally {
            $this->refreshing = false;
        }
    }

    public function render(): View
    {
        $settings = RentriSetting::instance();

        $certDays   = $this->daysUntil($settings->cert_scadenza);
        $firmaDays  = $this->daysUntil($settings->firma_cert_scadenza);

        $lastHealth = (array) ($settings->last_health_status ?? []);
        $healthOk   = ($lastHealth['status'] ?? null) === 'ok';

        $lastTrasmissione = RentriTransmissione::query()
            ->orderByDesc('created_at')
            ->first();

        $data = [
            'ambiente'         => $settings->ambiente ?? 'sandbox',
            'certDays'         => $certDays,
            'firmaDays'        => $firmaDays,
            'healthOk'         => $healthOk,
            'healthStatus'     => $lastHealth['status'] ?? 'unknown',
            'healthMessage'    => $lastHealth['message'] ?? null,
            'healthCheckedAt'  => $settings->last_health_check_at,
            'lastTrasmissione' => $lastTrasmissione,
            'certScadenza'     => $settings->cert_scadenza,
            'firmaScadenza'    => $settings->firma_cert_scadenza,
        ];

        return $this->adminView(
            'livewire.admin.rentri-status-widget',
            $data,
            'rentri-status',
            'Admin',
            'Stato RENTRI',
        );
    }

    private function daysUntil(?\Illuminate\Support\Carbon $date): ?int
    {
        if ($date === null) {
            return null;
        }

        return (int) now()->diffInDays($date, false);
    }
}
