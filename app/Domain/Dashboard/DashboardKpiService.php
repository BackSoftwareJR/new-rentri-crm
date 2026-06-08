<?php

namespace App\Domain\Dashboard;

use App\Domain\Ecommerce\EcommerceService;
use App\Domain\Legacy\LegacyImportService;
use App\Domain\Magazzino\MagazzinoService;
use App\Domain\Mud\MudService;
use App\Domain\Rentri\RentriTransazioneService;
use App\Enums\OrdineEcommerceStato;
use App\Enums\VfuStato;
use App\Models\Anagrafica;
use App\Models\CodiceCer;
use App\Models\EcommerceOrdine;
use App\Models\RegistroMovimento;
use App\Models\VfuRegistration;

class DashboardKpiService
{
    public function __construct(
        private MagazzinoService $magazzino,
        private MudService $mud,
        private RentriTransazioneService $rentriTransazioni,
        private EcommerceService $ecommerce,
        private LegacyImportService $legacyImport,
    ) {}

    /**
     * KPI aggregati cross-modulo per la dashboard segreteria.
     *
     * @return array<string, mixed>
     */
    public function aggregate(): array
    {
        $vfuCounts = $this->vfuCounts();
        $magazzinoRows = $this->magazzino->listSerbatoi();
        $magazzinoSummary = $this->magazzino->summary($magazzinoRows);
        $mud = $this->mud->contatori();
        $rentriApi = $this->rentriTransazioni->contatori();
        $ecommerceCatalogo = $this->ecommerce->contatoriCatalogo();

        $rentriPending = RegistroMovimento::query()
            ->where('rentri_trasmesso', false)
            ->whereNull('locked_at')
            ->count();

        $ordiniBozza = EcommerceOrdine::query()
            ->where('stato', OrdineEcommerceStato::Bozza)
            ->count();

        $legacyReport = $this->legacyImport->report();

        return [
            'vfu_attivi'            => $vfuCounts['attivi'],
            'bonifiche_pending'     => $vfuCounts['bonifiche'],
            'magazzino_kg'          => $magazzinoSummary['totale_kg'],
            'magazzino_alert'       => $magazzinoSummary['in_attenzione'] + $magazzinoSummary['soglia_superata'],
            'magazzino_serbatoi'    => $magazzinoSummary['codici_attivi'],
            'movimenti_mese'        => RegistroMovimento::query()
                ->where('data_movimento', '>=', now()->startOfMonth())
                ->count(),
            'rentri_pending'        => $rentriPending,
            'rentri_transazioni'    => $rentriApi['totale'],
            'rentri_errori'         => $rentriApi['errori'],
            'rentri_dead_letter'    => $rentriApi['dead_letter'],
            'rentri_retry_pianificati' => $rentriApi['retry_pianificati'],
            'mud_totale'            => $mud['totale'],
            'mud_bozze'             => $mud['bozze'],
            'ecommerce_prodotti'    => $ecommerceCatalogo['totale'],
            'ecommerce_disponibili' => $ecommerceCatalogo['disponibili'],
            'ecommerce_ordini_bozza'=> $ordiniBozza,
            'anagrafiche'           => Anagrafica::query()->count(),
            'codici_cer'            => CodiceCer::query()->where('attivo', true)->count(),
            'legacy_total'          => array_sum($legacyReport),
            'legacy_report'         => $legacyReport,
        ];
    }

    /**
     * @return array{attivi: int, bonifiche: int}
     */
    private function vfuCounts(): array
    {
        $terminated = [VfuStato::Rottamato->value, VfuStato::Annullato->value];
        $pending = [VfuStato::AttesaBonifica->value, VfuStato::InBonifica->value];

        $byStato = VfuRegistration::query()
            ->selectRaw('stato, COUNT(*) as total')
            ->groupBy('stato')
            ->pluck('total', 'stato');

        $attivi = 0;
        $bonifiche = 0;

        foreach ($byStato as $stato => $total) {
            $count = (int) $total;
            if (! in_array($stato, $terminated, true)) {
                $attivi += $count;
            }
            if (in_array($stato, $pending, true)) {
                $bonifiche += $count;
            }
        }

        return ['attivi' => $attivi, 'bonifiche' => $bonifiche];
    }
}
