<?php

namespace App\Domain\Magazzino;

use App\Enums\RegistroMovimentoTipo;
use App\Models\CodiceCer;
use App\Support\Demo\DemoContext;
use App\Support\Demo\DemoTrainingScope;
use App\Models\MagazzinoCaricoManuale;
use App\Models\MagazzinoRifiuto;
use App\Models\RegistroMovimento;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MagazzinoService
{
    public const SOGLIA_ATTENZIONE_PCT = 70;

    /**
     * Incrementa la giacenza per codice CER con lock pessimistico (SPEC).
     */
    public function addPeso(int $codiceCerId, float $pesoKg): MagazzinoRifiuto
    {
        if ($pesoKg <= 0) {
            return MagazzinoRifiuto::firstOrCreate(
                ['codice_cer_id' => $codiceCerId],
                ['quantita_attuale_kg' => 0]
            );
        }

        if (DemoTrainingScope::skipsSharedStockMutations()) {
            return MagazzinoRifiuto::firstOrCreate(
                ['codice_cer_id' => $codiceCerId],
                ['quantita_attuale_kg' => 0],
            );
        }

        return DB::transaction(function () use ($codiceCerId, $pesoKg) {
            $stock = MagazzinoRifiuto::query()
                ->where('codice_cer_id', $codiceCerId)
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                MagazzinoRifiuto::create([
                    'codice_cer_id'       => $codiceCerId,
                    'quantita_attuale_kg' => 0,
                ]);

                $stock = MagazzinoRifiuto::query()
                    ->where('codice_cer_id', $codiceCerId)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            $stock->quantita_attuale_kg = (float) $stock->quantita_attuale_kg + $pesoKg;

            if ($stock->oldest_load_date === null) {
                $stock->oldest_load_date = now()->toDateString();
            }

            $stock->save();

            return $stock->fresh();
        });
    }

    /**
     * Decrementa la giacenza per codice CER con lock pessimistico.
     */
    public function removePeso(int $codiceCerId, float $pesoKg): MagazzinoRifiuto
    {
        $pesoKg = round($pesoKg, 4);

        if ($pesoKg <= 0) {
            throw new \InvalidArgumentException('La quantità da scaricare deve essere maggiore di zero.');
        }

        if (DemoTrainingScope::skipsSharedStockMutations()) {
            $available = DemoTrainingScope::resolveGiacenzaKg($codiceCerId) ?? 0.0;

            if ($available < $pesoKg - 0.0001) {
                throw new \InvalidArgumentException('Giacenza magazzino insufficiente per lo scarico.');
            }

            return MagazzinoRifiuto::firstOrCreate(
                ['codice_cer_id' => $codiceCerId],
                ['quantita_attuale_kg' => 0],
            );
        }

        return DB::transaction(function () use ($codiceCerId, $pesoKg) {
            $stock = MagazzinoRifiuto::query()
                ->where('codice_cer_id', $codiceCerId)
                ->lockForUpdate()
                ->first();

            if ($stock === null || (float) $stock->quantita_attuale_kg < $pesoKg - 0.0001) {
                throw new \InvalidArgumentException('Giacenza magazzino insufficiente per lo scarico.');
            }

            $stock->quantita_attuale_kg = round((float) $stock->quantita_attuale_kg - $pesoKg, 4);
            $stock->save();

            return $stock->fresh();
        });
    }

    /**
     * Elenco serbatoi (codici CER attivi) con giacenza e stato soglia.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function listSerbatoi(?string $search = null): Collection
    {
        $query = DemoTrainingScope::applyMagazzinoCerFilter(
            CodiceCer::query()
                ->with('magazzino')
                ->where('attivo', true),
        );

        if ($search !== null && trim($search) !== '') {
            $tokens = preg_split('/\s+/u', trim($search), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            foreach ($tokens as $token) {
                $term = '%'.addcslashes($token, '%_\\').'%';
                $query->where(function ($q) use ($term) {
                    $q->where('codice', 'like', $term)
                        ->orWhere('descrizione', 'like', $term)
                        ->orWhere('categoria', 'like', $term)
                        ->orWhere('um', 'like', $term);
                });
            }
        }

        return $query
            ->orderBy('categoria')
            ->orderBy('codice')
            ->get()
            ->map(fn (CodiceCer $c) => $this->formatSerbatoioRow($c));
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array{
     *   totale_kg: float,
     *   codici_attivi: int,
     *   in_attenzione: int,
     *   soglia_superata: int
     * }
     */
    public function summary(Collection $rows): array
    {
        return [
            'totale_kg'       => round($rows->sum('quantita_attuale_kg'), 4),
            'codici_attivi'   => $rows->count(),
            'in_attenzione'   => $rows->where('stato', 'attenzione')->count(),
            'soglia_superata' => $rows->where('stato', 'superata')->count(),
        ];
    }

    /**
     * Contatori sidebar (attenzione / superata).
     *
     * @return array{in_attenzione: int, soglia_superata: int}
     */
    public function contatori(): array
    {
        $rows = $this->listSerbatoi();
        $summary = $this->summary($rows);

        return [
            'in_attenzione'   => $summary['in_attenzione'],
            'soglia_superata' => $summary['soglia_superata'],
        ];
    }

    public function findSerbatoio(int $codiceCerId): CodiceCer
    {
        $restrictToDemoCer = DemoContext::isActive() && DemoTrainingScope::demoCerIds()->isNotEmpty();

        $query = DemoTrainingScope::applyMagazzinoCerFilter(
            CodiceCer::query()
                ->with('magazzino')
                ->where('attivo', true),
            strictWhenEmpty: $restrictToDemoCer,
        );

        return $query->findOrFail($codiceCerId);
    }

    /**
     * @return array<string, mixed>
     */
    public function getSerbatoioDetail(int $codiceCerId): array
    {
        return $this->formatSerbatoioRow($this->findSerbatoio($codiceCerId));
    }

    /**
     * Carico manuale: record storico + movimento registro + incremento giacenza.
     */
    public function caricoManuale(
        int $codiceCerId,
        float $pesoKg,
        string $note,
        int $userId,
    ): MagazzinoCaricoManuale {
        $pesoKg = round($pesoKg, 4);
        $note = trim($note);

        if ($pesoKg <= 0) {
            throw new \InvalidArgumentException('La quantità deve essere maggiore di zero.');
        }

        if (strlen($note) < 3) {
            throw new \InvalidArgumentException('Inserire una descrizione di almeno 3 caratteri.');
        }

        $cer = $this->findSerbatoio($codiceCerId);

        $carico = DB::transaction(function () use ($cer, $pesoKg, $note, $userId) {
            $carico = MagazzinoCaricoManuale::create([
                'codice_cer_id' => $cer->id,
                'peso_kg'       => $pesoKg,
                'data'          => now()->toDateString(),
                'note'          => $note,
                'user_id'       => $userId,
            ]);

            RegistroMovimento::create([
                'tipo'           => RegistroMovimentoTipo::Carico,
                'codice_cer_id'  => $cer->id,
                'peso_kg'        => $pesoKg,
                'data_movimento' => now(),
                'source_type'    => RegistroMovimento::SOURCE_CARICO_MANUALE,
                'source_id'      => $carico->id,
                'note'           => 'Carico manuale — '.$note,
            ]);

            $this->addPeso($cer->id, $pesoKg);

            return $carico;
        });

        resolve(SerbatoioAlertNotificationService::class)
            ->maybeNotifyAfterCarico($this->findSerbatoio($codiceCerId));

        return $carico;
    }

    public function calcolaPercentuale(float $giacenzaKg, ?float $limiteKg): ?float
    {
        if ($limiteKg === null || $limiteKg <= 0) {
            return null;
        }

        return round(($giacenzaKg / $limiteKg) * 100, 1);
    }

    public function calcolaStatoSoglia(?float $percentuale): string
    {
        if ($percentuale === null) {
            return 'regolare';
        }

        if ($percentuale > 100) {
            return 'superata';
        }

        if ($percentuale >= self::SOGLIA_ATTENZIONE_PCT) {
            return 'attenzione';
        }

        return 'regolare';
    }

    /**
     * @return array<string, mixed>
     */
    public function formatSerbatoioRow(CodiceCer $c): array
    {
        $giacenza = DemoTrainingScope::resolveGiacenzaKg($c->id)
            ?? (float) ($c->magazzino?->quantita_attuale_kg ?? 0);
        $limite = $c->limite_kg !== null ? (float) $c->limite_kg : null;
        $pct = $this->calcolaPercentuale($giacenza, $limite);
        $stato = $this->calcolaStatoSoglia($pct);

        return [
            'id'                      => $c->id,
            'codice'                  => $c->codice,
            'descrizione'             => $c->descrizione,
            'categoria'               => $c->categoria,
            'um'                      => $c->um,
            'quantita_attuale_kg'     => $giacenza,
            'limite_kg'               => $limite,
            'percentuale'             => $pct,
            'stato'                   => $stato,
            'data_ultimo_aggiornamento' => $c->magazzino?->updated_at,
        ];
    }

    public function statoBadgeVariant(string $stato): string
    {
        return match ($stato) {
            'superata'   => 'danger',
            'attenzione' => 'warning',
            default      => 'success',
        };
    }

    public function statoBadgeLabel(string $stato): string
    {
        return match ($stato) {
            'superata'   => 'Soglia superata',
            'attenzione' => 'Attenzione',
            default      => 'Regolare',
        };
    }
}
