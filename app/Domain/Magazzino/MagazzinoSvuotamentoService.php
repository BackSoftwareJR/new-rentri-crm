<?php

namespace App\Domain\Magazzino;

use App\Domain\Anagrafiche\AuthorizationComplianceService;
use App\Domain\Trasporti\TrasportoService;
use App\Enums\SvuotamentoStato;
use App\Models\Anagrafica;
use App\Models\CodiceCer;
use App\Models\MagazzinoRifiuto;
use App\Models\MagazzinoSvuotamento;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class MagazzinoSvuotamentoService
{
    public function __construct(
        private MagazzinoService $magazzino,
        private AuthorizationComplianceService $compliance,
        private TrasportoService $trasportoService,
    ) {}

    /**
     * @return Collection<int, Anagrafica>
     */
    public function listImpiantiDestinazione(): Collection
    {
        return Anagrafica::query()
            ->where('tipo', 'impianto')
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->orderBy('ragione_sociale')
            ->get();
    }

    /**
     * @return Collection<int, Anagrafica>
     */
    public function listTrasportatoriConformi(): Collection
    {
        return Anagrafica::query()
            ->with('authorizations')
            ->where(function ($q) {
                $q->where('tipo', 'trasportatore')
                    ->orWhere(function ($q2) {
                        $q2->where('tipo', 'impianto')->where('gestisce_trasporti', true);
                    });
            })
            ->orderBy('ragione_sociale')
            ->get()
            ->filter(fn (Anagrafica $a) => $this->puoFareTrasporto($a))
            ->values();
    }

    public function quantitaImpegnata(int $codiceCerId): float
    {
        return (float) MagazzinoSvuotamento::query()
            ->where('codice_cer_id', $codiceCerId)
            ->where('stato', SvuotamentoStato::Richiesto)
            ->sum('quantita_kg');
    }

    public function quantitaDisponibile(int $codiceCerId): float
    {
        $giacenza = (float) (MagazzinoRifiuto::query()
            ->where('codice_cer_id', $codiceCerId)
            ->value('quantita_attuale_kg') ?? 0);

        return max(0, round($giacenza - $this->quantitaImpegnata($codiceCerId), 4));
    }

    public function puoRichiedereSvuotamento(CodiceCer $codiceCer): bool
    {
        if (! $codiceCer->attivo) {
            return false;
        }

        if ($this->quantitaDisponibile($codiceCer->id) <= 0) {
            return false;
        }

        return $this->listImpiantiDestinazione()->isNotEmpty();
    }

    /**
     * @return \Illuminate\Support\Collection<int, MagazzinoSvuotamento>
     */
    public function storicoPerCer(int $codiceCerId, int $limit = 10)
    {
        return MagazzinoSvuotamento::query()
            ->where('codice_cer_id', $codiceCerId)
            ->with(['impianto:id,ragione_sociale', 'trasportatore:id,ragione_sociale', 'user:id,name'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function richiediSvuotamento(
        int $codiceCerId,
        int $impiantoId,
        ?int $trasportatoreId,
        bool $trasportatoreOmesso,
        float $quantitaKg,
        ?string $note,
        int $userId,
    ): MagazzinoSvuotamento {
        $cer = $this->magazzino->findSerbatoio($codiceCerId);
        $quantitaKg = round($quantitaKg, 4);
        $note = $note !== null ? trim($note) : null;

        if ($quantitaKg <= 0) {
            throw new \InvalidArgumentException('Indicare una quantità da svuotare maggiore di zero.');
        }

        $disponibile = $this->quantitaDisponibile($cer->id);
        if ($disponibile <= 0) {
            throw new \InvalidArgumentException('Il serbatoio non ha giacenza disponibile per uno svuotamento.');
        }

        if ($quantitaKg > $disponibile + 0.0001) {
            throw new \InvalidArgumentException(
                'La quantità richiesta supera la giacenza disponibile ('.number_format($disponibile, 2, ',', '.').' '.$cer->um.').'
            );
        }

        $impianto = Anagrafica::query()->findOrFail($impiantoId);
        if ($impianto->tipo !== 'impianto') {
            throw new \InvalidArgumentException('Selezionare un impianto di destinazione.');
        }

        if (blank($impianto->email)) {
            throw new \InvalidArgumentException('L\'impianto selezionato non ha un indirizzo email.');
        }

        $trasportatore = null;
        if ($trasportatoreId !== null) {
            $trasportatore = Anagrafica::query()->findOrFail($trasportatoreId);
            if (! $this->puoFareTrasporto($trasportatore)) {
                throw new \InvalidArgumentException('Il trasportatore selezionato non ha autorizzazione valida.');
            }
            if ($trasportatore->id === $impianto->id && ! $impianto->gestisce_trasporti) {
                throw new \InvalidArgumentException('Non è possibile indicare lo stesso impianto come trasportatore se non gestisce i trasporti.');
            }
            $trasportatoreOmesso = false;
        } elseif (! $trasportatoreOmesso) {
            throw new \InvalidArgumentException('Indicare un trasportatore conforme oppure confermare che non è stato selezionato.');
        }

        return DB::transaction(function () use ($cer, $impianto, $trasportatore, $trasportatoreOmesso, $quantitaKg, $note, $userId) {
            $svuotamento = MagazzinoSvuotamento::create([
                'codice_cer_id'              => $cer->id,
                'anagrafica_id'              => $impianto->id,
                'trasportatore_anagrafica_id'=> $trasportatore?->id,
                'trasportatore_omesso'       => $trasportatoreOmesso,
                'stato'                      => SvuotamentoStato::Richiesto,
                'quantita_kg'                => $quantitaKg,
                'quantita_impegnata_kg'      => $quantitaKg,
                'note_interne'               => $note !== '' ? $note : null,
                'user_id'                    => $userId,
            ]);

            $this->trasportoService->creaDaSvuotamento($svuotamento);

            return $svuotamento;
        });
    }

    public function puoFareTrasporto(Anagrafica $anagrafica): bool
    {
        if ($anagrafica->tipo === 'trasportatore') {
            return $this->compliance->hasValidAuthorization($anagrafica);
        }

        if ($anagrafica->tipo === 'impianto' && $anagrafica->gestisce_trasporti) {
            return $this->compliance->hasValidAuthorization($anagrafica);
        }

        return false;
    }
}
