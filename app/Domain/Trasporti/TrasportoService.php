<?php

namespace App\Domain\Trasporti;

use App\Domain\Magazzino\MagazzinoService;
use App\Enums\FirStato;
use App\Enums\RegistroMovimentoTipo;
use App\Enums\SvuotamentoStato;
use App\Enums\TrasportoStato;
use App\Models\MagazzinoRifiuto;
use App\Models\MagazzinoSvuotamento;
use App\Models\RegistroMovimento;
use App\Models\Trasporto;
use App\Services\Push\WebPushService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class TrasportoService
{
    public function __construct(
        private MagazzinoService $magazzino,
        private WebPushService $webPush,
    ) {}

    /**
     * @param  array{
     *   codice_cer_id?: int|null,
     *   stato?: string|null,
     *   q?: string|null,
     *   per_page?: int
     * }  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(10, min(100, (int) ($filters['per_page'] ?? 25)));

        return $this->query($filters)->paginate($perPage);
    }

    /**
     * @param  array{
     *   codice_cer_id?: int|null,
     *   stato?: string|null,
     *   q?: string|null,
     * }  $filters
     * @return \Illuminate\Support\Collection<int, Trasporto>
     */
    public function exportAll(array $filters = []): \Illuminate\Support\Collection
    {
        return $this->query($filters)->get();
    }

    /**
     * @param  array{stato?: string|null, codice_cer_id?: int|null, q?: string|null}  $filters
     * @return array<string, int>
     */
    public function contatori(array $filters = []): array
    {
        $base = $this->query(array_diff_key($filters, ['stato' => null]));

        return [
            'totale'          => (clone $base)->count(),
            'in_preparazione' => (clone $base)->where('stato', TrasportoStato::InPreparazione)->count(),
            'in_transito'     => (clone $base)->where('stato', TrasportoStato::InTransito)->count(),
            'completati'      => (clone $base)->where('stato', TrasportoStato::Completato)->count(),
        ];
    }

    /**
     * @param  array{
     *   anagrafica_trasportatore_id?: int|null,
     *   anagrafica_destinatario_id: int,
     *   codice_cer_id: int,
     *   quantita_kg: float|int|string,
     *   targa_mezzo?: string|null,
     *   conducente?: string|null,
     *   data_trasporto?: string|null,
     *   vfu_registration_id?: int|null,
     *   fir_blocco_id?: int|null,
     *   note?: string|null,
     * }  $data
     */
    public function crea(array $data): Trasporto
    {
        $quantita = round((float) $data['quantita_kg'], 4);

        if ($quantita <= 0) {
            throw new \InvalidArgumentException('Indicare una quantità maggiore di zero.');
        }

        return Trasporto::create([
            'anagrafica_trasportatore_id' => $data['anagrafica_trasportatore_id'] ?? null,
            'anagrafica_destinatario_id'  => (int) $data['anagrafica_destinatario_id'],
            'codice_cer_id'               => (int) $data['codice_cer_id'],
            'quantita_kg'                 => $quantita,
            'targa_mezzo'                 => isset($data['targa_mezzo']) ? strtoupper(trim((string) $data['targa_mezzo'])) ?: null : null,
            'conducente'                  => isset($data['conducente']) ? trim((string) $data['conducente']) ?: null : null,
            'data_trasporto'              => $data['data_trasporto'] ?? now()->toDateString(),
            'vfu_registration_id'         => $data['vfu_registration_id'] ?? null,
            'fir_blocco_id'               => $data['fir_blocco_id'] ?? null,
            'note'                        => isset($data['note']) ? trim((string) $data['note']) ?: null : null,
            'stato'                       => TrasportoStato::InPreparazione,
        ]);
    }

    public function creaDaSvuotamento(MagazzinoSvuotamento $svuotamento): Trasporto
    {
        if ($svuotamento->stato !== SvuotamentoStato::Richiesto) {
            throw new \InvalidArgumentException('Solo gli svuotamenti in stato richiesto possono generare un trasporto.');
        }

        if ($svuotamento->anagrafica_id === null) {
            throw new \InvalidArgumentException('Svuotamento senza impianto di destinazione.');
        }

        if (Trasporto::query()->where('magazzino_svuotamento_id', $svuotamento->id)->exists()) {
            throw new \InvalidArgumentException('Esiste già un trasporto collegato a questo svuotamento.');
        }

        return Trasporto::create([
            'magazzino_svuotamento_id'     => $svuotamento->id,
            'codice_cer_id'                => $svuotamento->codice_cer_id,
            'anagrafica_destinatario_id'   => $svuotamento->anagrafica_id,
            'quantita_kg'                  => $svuotamento->quantita_kg,
            'stato'                        => TrasportoStato::InPreparazione,
            'note'                         => $svuotamento->note_interne,
        ]);
    }

    public function avviaTransito(Trasporto $trasporto): Trasporto
    {
        if ($trasporto->stato !== TrasportoStato::InPreparazione) {
            throw new \InvalidArgumentException('Il trasporto non è in preparazione.');
        }

        $trasporto->update(['stato' => TrasportoStato::InTransito]);

        $fresh = $trasporto->fresh();

        try {
            $this->webPush->sendToRoles(
                'segreteria',
                'Trasporto #'.$fresh->id.' in corso',
                'Il trasporto è stato avviato ed è in transito.',
                route('segreteria.trasporti.show', $fresh),
            );
        } catch (\Throwable) {
            // Push failures must never break core workflow.
        }

        return $fresh;
    }

    /**
     * @return list<string>
     */
    public function completionBlockers(Trasporto $trasporto): array
    {
        $trasporto->loadMissing(['firCollegato', 'fir', 'svuotamento']);

        $blockers = [];

        if ($trasporto->stato !== TrasportoStato::InTransito) {
            $blockers[] = 'Il trasporto non è in transito.';
        }

        if (! $this->hasFirVidimato($trasporto)) {
            $blockers[] = 'Vidimare il FIR prima del completamento.';
        }

        $quantita = (float) $trasporto->quantita_kg;
        if ($quantita <= 0) {
            $blockers[] = 'Quantità trasporto non valida.';
        } elseif ($trasporto->codice_cer_id) {
            $giacenza = (float) (MagazzinoRifiuto::query()
                ->where('codice_cer_id', $trasporto->codice_cer_id)
                ->value('quantita_attuale_kg') ?? 0);

            if ($giacenza < $quantita - 0.0001) {
                $blockers[] = 'Giacenza magazzino insufficiente rispetto alla quantità trasportata.';
            }
        }

        return $blockers;
    }

    public function canComplete(Trasporto $trasporto): bool
    {
        return $this->completionBlockers($trasporto) === [];
    }

    /**
     * Chiusura trasporto: scarico magazzino, movimento registro, svuotamento completato.
     */
    public function completa(Trasporto $trasporto): Trasporto
    {
        $blockers = $this->completionBlockers($trasporto);
        if ($blockers !== []) {
            throw new \InvalidArgumentException($blockers[0]);
        }

        $quantita = round((float) $trasporto->quantita_kg, 4);

        return DB::transaction(function () use ($trasporto, $quantita) {
            RegistroMovimento::create([
                'tipo'           => RegistroMovimentoTipo::Scarico,
                'codice_cer_id'  => $trasporto->codice_cer_id,
                'peso_kg'        => $quantita,
                'data_movimento' => now(),
                'source_type'    => RegistroMovimento::SOURCE_TRASPORTO,
                'source_id'      => $trasporto->id,
                'note'           => sprintf('Scarico trasporto #%d verso impianto destinazione.', $trasporto->id),
            ]);

            $this->magazzino->removePeso($trasporto->codice_cer_id, $quantita);

            $trasporto->update(['stato' => TrasportoStato::Completato]);

            $svuotamento = $trasporto->svuotamento;
            if ($svuotamento && $svuotamento->stato === SvuotamentoStato::Richiesto) {
                $svuotamento->update(['stato' => SvuotamentoStato::Completato]);
            }

            return $trasporto->fresh(['codiceCer', 'destinatario', 'svuotamento.trasportatore', 'firCollegato']);
        });
    }

    public function annulla(Trasporto $trasporto): Trasporto
    {
        if (! in_array($trasporto->stato, [TrasportoStato::InPreparazione, TrasportoStato::InTransito], true)) {
            throw new \InvalidArgumentException('Il trasporto non può essere annullato nello stato attuale.');
        }

        return DB::transaction(function () use ($trasporto) {
            $trasporto->update(['stato' => TrasportoStato::Annullato]);

            $svuotamento = $trasporto->svuotamento;
            if ($svuotamento && $svuotamento->stato === SvuotamentoStato::Richiesto) {
                $svuotamento->update(['stato' => SvuotamentoStato::Annullato]);
            }

            return $trasporto->fresh(['codiceCer', 'destinatario', 'svuotamento.trasportatore']);
        });
    }

    public function statoBadgeVariant(TrasportoStato $stato): string
    {
        return match ($stato) {
            TrasportoStato::Completato      => 'success',
            TrasportoStato::InTransito      => 'info',
            TrasportoStato::InPreparazione  => 'warning',
            TrasportoStato::Annullato       => 'muted',
            TrasportoStato::Bozza           => 'muted',
        };
    }

    public function statoLabel(TrasportoStato $stato): string
    {
        return match ($stato) {
            TrasportoStato::Bozza          => 'Bozza',
            TrasportoStato::InPreparazione => 'In preparazione',
            TrasportoStato::InTransito     => 'In transito',
            TrasportoStato::Completato     => 'Completato',
            TrasportoStato::Annullato      => 'Annullato',
        };
    }

    private function hasFirVidimato(Trasporto $trasporto): bool
    {
        $fir = $trasporto->firCollegato ?? $trasporto->fir;

        return $fir !== null && $fir->stato === FirStato::Vidimato;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function query(array $filters): Builder
    {
        $query = Trasporto::query()
            ->forActiveSito()
            ->with([
                'codiceCer:id,codice,descrizione,um',
                'destinatario:id,ragione_sociale',
                'svuotamento:id,trasportatore_anagrafica_id,trasportatore_omesso',
                'svuotamento.trasportatore:id,ragione_sociale',
                'firCollegato:id,trasporto_id,stato',
                'fir:id,stato',
            ]);

        if (! empty($filters['codice_cer_id'])) {
            $query->where('codice_cer_id', (int) $filters['codice_cer_id']);
        }

        if (! empty($filters['stato'])) {
            $query->where('stato', $filters['stato']);
        }

        $q = isset($filters['q']) ? trim((string) $filters['q']) : '';
        if ($q !== '') {
            $term = '%'.addcslashes($q, '%_\\').'%';
            $query->where(function (Builder $sub) use ($term) {
                $sub->where('note', 'like', $term)
                    ->orWhereHas('codiceCer', fn (Builder $cer) => $cer
                        ->where('codice', 'like', $term)
                        ->orWhere('descrizione', 'like', $term))
                    ->orWhereHas('destinatario', fn (Builder $a) => $a
                        ->where('ragione_sociale', 'like', $term));
            });
        }

        return $query->orderByDesc('created_at')->orderByDesc('id');
    }
}
