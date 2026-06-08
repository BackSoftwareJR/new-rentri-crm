<?php

namespace App\Domain\Magazzino;

use App\Models\CodiceCer;
use App\Models\MagazzinoRifiuto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CodiceCerService
{
    public function query(): Builder
    {
        return CodiceCer::query()
            ->with('magazzino')
            ->orderBy('codice');
    }

    public function find(int $id): CodiceCer
    {
        return CodiceCer::with('magazzino')->findOrFail($id);
    }

    public function create(array $data): CodiceCer
    {
        return DB::transaction(function () use ($data) {
            $codice = CodiceCer::create($data);

            MagazzinoRifiuto::create([
                'codice_cer_id' => $codice->id,
                'quantita_attuale_kg' => 0,
            ]);

            return $codice->fresh('magazzino');
        });
    }

    public function update(CodiceCer $codice, array $data): CodiceCer
    {
        $codice->update($data);

        return $codice->fresh('magazzino');
    }

    /**
     * @return 'deleted'|'deactivated'
     */
    public function delete(CodiceCer $codice): string
    {
        if ($this->hasMovements($codice)) {
            $codice->update(['attivo' => false]);

            return 'deactivated';
        }

        DB::transaction(function () use ($codice) {
            $codice->magazzino()?->delete();
            $codice->delete();
        });

        return 'deleted';
    }

    public function hasMovements(CodiceCer $codice): bool
    {
        return $codice->bonificaMovimenti()->exists()
            || $codice->registroMovimenti()->exists()
            || $codice->carichiManuali()->exists()
            || $codice->trasporti()->exists();
    }

    public function quantitaAttualeKg(CodiceCer $codice): float
    {
        return (float) ($codice->magazzino?->quantita_attuale_kg ?? 0);
    }
}
