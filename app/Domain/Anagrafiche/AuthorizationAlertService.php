<?php

namespace App\Domain\Anagrafiche;

use App\Models\Authorization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AuthorizationAlertService
{
    public function __construct(
        private AuthorizationComplianceService $compliance,
    ) {}

    /**
     * @return array{in_scadenza: int, scadute: int}
     */
    public function summary(): array
    {
        return [
            'in_scadenza' => $this->expiringSoonQuery()->count(),
            'scadute'     => $this->expiredQuery()->count(),
        ];
    }

    /**
     * @return Collection<int, array{
     *     authorization: Authorization,
     *     anagrafica_id: int,
     *     ragione_sociale: string,
     *     numero: string,
     *     scade_il: \Carbon\Carbon|null,
     *     giorni: int|null,
     *     stato: string
     * }>
     */
    public function recentAlerts(int $limit = 8): Collection
    {
        $rows = $this->expiredQuery()
            ->get()
            ->concat($this->expiringSoonQuery()->get())
            ->unique('id')
            ->sortBy('scade_il')
            ->take($limit);

        return $rows->map(function (Authorization $auth): array {
            $stato = $this->compliance->authorizationStatus($auth);

            return [
                'authorization'   => $auth,
                'anagrafica_id'   => $auth->anagrafica_id,
                'ragione_sociale' => $auth->anagrafica->ragione_sociale,
                'numero'          => $auth->numero,
                'scade_il'        => $auth->scade_il,
                'giorni'          => $this->compliance->daysUntilExpiry($auth),
                'stato'           => $stato,
            ];
        })->values();
    }

    public function expiringSoonQuery(): Builder
    {
        $threshold = today()->addDays(AuthorizationComplianceService::EXPIRY_WARNING_DAYS);

        return $this->requiringAuthQuery()
            ->whereDate('scade_il', '>=', today())
            ->whereDate('scade_il', '<=', $threshold);
    }

    public function expiredQuery(): Builder
    {
        return $this->requiringAuthQuery()
            ->whereDate('scade_il', '<', today());
    }

    private function requiringAuthQuery(): Builder
    {
        return Authorization::query()
            ->with('anagrafica')
            ->whereNotNull('scade_il')
            ->whereHas('anagrafica', function (Builder $query): void {
                $query->where(function (Builder $sub): void {
                    $sub->where('tipo', 'trasportatore')
                        ->orWhere(function (Builder $impianto): void {
                            $impianto->where('tipo', 'impianto')
                                ->where('gestisce_trasporti', true);
                        });
                });
            });
    }
}
