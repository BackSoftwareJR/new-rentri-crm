<?php

namespace App\Domain\Anagrafiche;

use App\Models\Anagrafica;
use App\Models\Authorization;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class AnagraficaService
{
    public function __construct(
        private readonly AuthorizationComplianceService $compliance,
    ) {}

    public function query(array $filters = []): Builder
    {
        $query = Anagrafica::query()
            ->with('authorizations')
            ->orderByDesc('created_at');

        if (! empty($filters['tipo']) && in_array($filters['tipo'], Anagrafica::TIPI, true)) {
            $query->where('tipo', $filters['tipo']);
        }

        if (! empty($filters['search'])) {
            $term = '%'.trim($filters['search']).'%';
            $query->where(function (Builder $q) use ($term) {
                $q->where('ragione_sociale', 'like', $term)
                    ->orWhere('piva', 'like', $term)
                    ->orWhere('codice_sdi', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('telefono', 'like', $term)
                    ->orWhere('codice_fiscale', 'like', $term);
            });
        }

        return $query;
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->query($filters)->paginate($perPage);
    }

    public function find(int $id): Anagrafica
    {
        return Anagrafica::with('authorizations')->findOrFail($id);
    }

    public function create(array $data, array $authorizations = []): Anagrafica
    {
        return DB::transaction(function () use ($data, $authorizations) {
            $anagrafica = Anagrafica::create($this->normalizePayload($data));
            $this->syncAuthorizations($anagrafica, $authorizations);

            return $anagrafica->fresh('authorizations');
        });
    }

    public function update(Anagrafica $anagrafica, array $data, array $authorizations = []): Anagrafica
    {
        return DB::transaction(function () use ($anagrafica, $data, $authorizations) {
            $anagrafica->update($this->normalizePayload($data, $anagrafica));
            $this->syncAuthorizations($anagrafica, $authorizations);

            return $anagrafica->fresh('authorizations');
        });
    }

    public function delete(Anagrafica $anagrafica): void
    {
        $anagrafica->delete();
    }

    public function compliance(): AuthorizationComplianceService
    {
        return $this->compliance;
    }

    private function normalizePayload(array $data, ?Anagrafica $existing = null): array
    {
        $payload = Arr::only($data, [
            'tipo',
            'ragione_sociale',
            'piva',
            'codice_fiscale',
            'codice_sdi',
            'pec',
            'indirizzo',
            'citta',
            'cap',
            'provincia',
            'telefono',
            'email',
            'note',
            'rentri_soggetto_id',
        ]);

        $tipo = $payload['tipo'] ?? $existing?->tipo;
        $payload['gestisce_trasporti'] = ($tipo === 'impianto')
            && (bool) ($data['gestisce_trasporti'] ?? false);

        return $payload;
    }

    private function syncAuthorizations(Anagrafica $anagrafica, array $rows): void
    {
        $keepIds = [];

        foreach ($rows as $row) {
            if ($this->authorizationRowIsEmpty($row)) {
                continue;
            }

            $attributes = [
                'numero' => $row['numero'],
                'rilasciata_il' => $row['rilasciata_il'],
                'scade_il' => $row['scade_il'] ?: null,
                'tipo' => $row['tipo'] ?? 'trasporto_rifiuti',
                'documento_path' => $row['documento_path'] ?? null,
            ];

            if (! empty($row['id'])) {
                $auth = $anagrafica->authorizations()->whereKey($row['id'])->firstOrFail();
                $auth->update($attributes);
                $keepIds[] = $auth->id;
            } else {
                $auth = $anagrafica->authorizations()->create($attributes);
                $keepIds[] = $auth->id;
            }
        }

        $anagrafica->authorizations()->whereNotIn('id', $keepIds)->delete();
    }

    private function authorizationRowIsEmpty(array $row): bool
    {
        return blank($row['numero'] ?? null)
            && blank($row['rilasciata_il'] ?? null)
            && blank($row['scade_il'] ?? null);
    }
}
