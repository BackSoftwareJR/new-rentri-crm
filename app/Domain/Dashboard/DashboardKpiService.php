<?php

namespace App\Domain\Dashboard;

use App\Domain\Ecommerce\EcommerceService;
use App\Domain\Legacy\LegacyImportService;
use App\Domain\Magazzino\MagazzinoService;
use App\Domain\Magazzino\SerbatoioAlertService;
use App\Domain\Mud\MudService;
use App\Domain\Rentri\RentriTransazioneService;
use App\Enums\OrdineEcommerceStato;
use App\Enums\TrasportoStato;
use App\Enums\VfuStato;
use App\Models\Anagrafica;
use App\Models\CodiceCer;
use App\Models\EcommerceOrdine;
use App\Models\Fattura;
use App\Models\RegistroMovimento;
use App\Models\RentriSetting;
use App\Models\Trasporto;
use App\Models\VfuRegistration;
use App\Support\Demo\DemoContext;
use App\Support\Sito\SitoContext;
use Illuminate\Support\Carbon;

class DashboardKpiService
{
    private const CACHE_TTL_SECONDS = 300;

    /** @var list<string> */
    public const CACHE_SUFFIXES = [
        'vfu_metrics',
        'vfu_oggi',
        'magazzino_summary',
        'serbatoi_alert',
        'mud',
        'rentri_api',
        'rentri_pending',
        'rentri_status',
        'trasporti_in_transito',
        'fatture_in_scadenza',
        'fatturazione_mese',
        'fatture_scadute',
        'prossima_scadenza',
        'revenue_mese',
        'movimenti_mese',
        'ordini_bozza',
        'ecommerce_catalogo',
        'anagrafiche',
        'codici_cer',
        'legacy_report',
    ];

    public function __construct(
        private MagazzinoService $magazzino,
        private MudService $mud,
        private RentriTransazioneService $rentriTransazioni,
        private EcommerceService $ecommerce,
        private LegacyImportService $legacyImport,
        private SerbatoioAlertService $serbatoioAlerts,
    ) {}

    /**
     * KPI aggregati cross-modulo per la dashboard segreteria.
     *
     * @return array<string, mixed>
     */
    public function aggregate(): array
    {
        $vfuMetrics = $this->vfuMetrics();
        $magazzinoSummary = $this->magazzinoSummary();
        $mud = $this->mudCounters();
        $rentriApi = $this->rentriApiCounters();
        $legacyReport = $this->legacyReport();

        return [
            'vfu_attivi'               => $vfuMetrics['attivi'],
            'vfu_oggi'                 => $this->vfuOggi(),
            'vfu_in_bonifica'          => $vfuMetrics['in_bonifica'],
            'vfu_in_smontaggio'        => $vfuMetrics['in_smontaggio'],
            'bonifiche_pending'        => $vfuMetrics['bonifiche'],
            'magazzino_kg'             => $magazzinoSummary['totale_kg'],
            'magazzino_alert'          => $this->serbatoiAlertCount(),
            'magazzino_serbatoi'       => $magazzinoSummary['codici_attivi'],
            'trasporti_in_transito'    => $this->trasportiInTransito(),
            'fatture_in_scadenza'      => $this->fattureInScadenza(),
            'fatturazione_mese'        => $this->fatturazioneMese(),
            'fatture_scadute'          => $this->fattureScadute(),
            'prossima_scadenza'        => $this->prossimaScadenza(),
            'revenue_mese_corrente'    => $this->revenueMeseCorrente(),
            'movimenti_mese'           => $this->movimentiMese(),
            'rentri_pending'           => $this->rentriPending(),
            'rentri_status'            => $this->rentriStatus(),
            'rentri_transazioni'       => $rentriApi['totale'],
            'rentri_errori'            => $rentriApi['errori'],
            'rentri_dead_letter'       => $rentriApi['dead_letter'],
            'rentri_retry_pianificati' => $rentriApi['retry_pianificati'],
            'mud_totale'               => $mud['totale'],
            'mud_bozze'                => $mud['bozze'],
            'ecommerce_prodotti'       => $this->ecommerceCatalogo()['totale'],
            'ecommerce_disponibili'    => $this->ecommerceCatalogo()['disponibili'],
            'ecommerce_ordini_bozza'   => $this->ordiniBozza(),
            'anagrafiche'              => $this->anagraficheCount(),
            'codici_cer'               => $this->codiciCerCount(),
            'legacy_total'             => array_sum($legacyReport),
            'legacy_report'            => $legacyReport,
        ];
    }

    public function forgetCache(): void
    {
        foreach (self::CACHE_SUFFIXES as $suffix) {
            cache()->forget($this->cacheKey($suffix));
        }
    }

    private function vfuOggi(): int
    {
        return (int) $this->remember('vfu_oggi', fn () => VfuRegistration::query()
            ->forActiveSito()
            ->whereNotNull('data_accettazione')
            ->where('data_accettazione', '>=', now()->subDay())
            ->count());
    }

    /**
     * @return array{attivi: int, bonifiche: int, in_bonifica: int, in_smontaggio: int}
     */
    private function vfuMetrics(): array
    {
        return $this->remember('vfu_metrics', function (): array {
            $terminated = [VfuStato::Rottamato->value, VfuStato::Annullato->value];
            $pending = [VfuStato::AttesaBonifica->value, VfuStato::InBonifica->value];

            $byStato = VfuRegistration::query()
                ->forActiveSito()
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

            return [
                'attivi'         => $attivi,
                'bonifiche'      => $bonifiche,
                'in_bonifica'    => (int) ($byStato[VfuStato::InBonifica->value] ?? 0),
                'in_smontaggio'  => (int) ($byStato[VfuStato::InSmontaggio->value] ?? 0),
            ];
        });
    }

    /**
     * @return array{totale_kg: float, codici_attivi: int}
     */
    private function magazzinoSummary(): array
    {
        return $this->remember('magazzino_summary', function (): array {
            $summary = $this->magazzino->summary($this->magazzino->listSerbatoi());

            return [
                'totale_kg'     => $summary['totale_kg'],
                'codici_attivi' => $summary['codici_attivi'],
            ];
        });
    }

    private function serbatoiAlertCount(): int
    {
        return (int) $this->remember('serbatoi_alert', fn () => $this->serbatoioAlerts->summary()['totale_alert']);
    }

    private function trasportiInTransito(): int
    {
        return (int) $this->remember('trasporti_in_transito', fn () => Trasporto::query()
            ->forActiveSito()
            ->where('stato', TrasportoStato::InTransito)
            ->count());
    }

    private function fattureInScadenza(): int
    {
        return (int) $this->remember('fatture_in_scadenza', fn () => Fattura::query()
            ->forActiveSito()
            ->where('stato', 'emessa')
            ->whereNotNull('data_scadenza')
            ->whereBetween('data_scadenza', [now()->toDateString(), now()->addDays(7)->toDateString()])
            ->count());
    }

    /**
     * @return array{count: int, totale: float}
     */
    private function fatturazioneMese(): array
    {
        return $this->remember('fatturazione_mese', function (): array {
            $start = now()->startOfMonth()->toDateString();
            $end   = now()->endOfMonth()->toDateString();

            $query = Fattura::query()
                ->forActiveSito()
                ->whereIn('stato', ['emessa', 'pagata', 'scaduta'])
                ->whereBetween('data_emissione', [$start, $end]);

            return [
                'count'  => (int) (clone $query)->count(),
                'totale' => (float) (clone $query)->sum('totale'),
            ];
        });
    }

    private function fattureScadute(): int
    {
        return (int) $this->remember('fatture_scadute', fn () => Fattura::query()->forActiveSito()->scadute()->count());
    }

    private function prossimaScadenza(): ?string
    {
        return $this->remember('prossima_scadenza', fn () => Fattura::query()
            ->forActiveSito()
            ->where('stato', 'emessa')
            ->whereNotNull('data_scadenza')
            ->where('data_scadenza', '>=', now()->toDateString())
            ->orderBy('data_scadenza')
            ->value('data_scadenza'));
    }

    private function revenueMeseCorrente(): float
    {
        return (float) $this->remember('revenue_mese', fn () => Fattura::query()
            ->forActiveSito()
            ->where('stato', 'pagata')
            ->where('data_pagamento', '>=', now()->startOfMonth()->toDateString())
            ->sum('totale'));
    }

    private function movimentiMese(): int
    {
        return (int) $this->remember('movimenti_mese', fn () => RegistroMovimento::query()
            ->forActiveSito()
            ->where('data_movimento', '>=', now()->startOfMonth())
            ->count());
    }

    private function rentriPending(): int
    {
        return (int) $this->remember('rentri_pending', fn () => RegistroMovimento::query()
            ->forActiveSito()
            ->whereNull('locked_at')
            ->where('rentri_trasmesso', false)
            ->count());
    }

    /**
     * @return array{ambiente: string, cert_days: ?int, firma_days: ?int, health_ok: bool}
     */
    private function rentriStatus(): array
    {
        return $this->remember('rentri_status', function (): array {
            $settings = RentriSetting::instance();
            $lastHealth = (array) ($settings->last_health_status ?? []);

            return [
                'ambiente'   => (string) ($settings->ambiente ?? 'sandbox'),
                'cert_days'  => $this->daysUntil($settings->cert_scadenza),
                'firma_days' => $this->daysUntil($settings->firma_cert_scadenza),
                'health_ok'  => ($lastHealth['status'] ?? null) === 'ok',
            ];
        });
    }

    /**
     * @return array{totale: int, errori: int, dead_letter: int, retry_pianificati: int}
     */
    private function rentriApiCounters(): array
    {
        return $this->remember('rentri_api', fn () => $this->rentriTransazioni->contatori());
    }

    /**
     * @return array{totale: int, bozze: int}
     */
    private function mudCounters(): array
    {
        return $this->remember('mud', fn () => $this->mud->contatori());
    }

    /**
     * @return array{totale: int, disponibili: int}
     */
    private function ecommerceCatalogo(): array
    {
        return $this->remember('ecommerce_catalogo', fn () => $this->ecommerce->contatoriCatalogo());
    }

    private function ordiniBozza(): int
    {
        return (int) $this->remember('ordini_bozza', fn () => EcommerceOrdine::query()
            ->where('stato', OrdineEcommerceStato::Bozza)
            ->count());
    }

    private function anagraficheCount(): int
    {
        return (int) $this->remember('anagrafiche', fn () => Anagrafica::query()->count());
    }

    private function codiciCerCount(): int
    {
        return (int) $this->remember('codici_cer', fn () => CodiceCer::query()->where('attivo', true)->count());
    }

    /**
     * @return array<string, int>
     */
    private function legacyReport(): array
    {
        return $this->remember('legacy_report', fn () => $this->legacyImport->report());
    }

    private function daysUntil(?Carbon $date): ?int
    {
        if ($date === null) {
            return null;
        }

        return (int) now()->diffInDays($date, false);
    }

    private function remember(string $suffix, callable $callback): mixed
    {
        return cache()->remember(
            $this->cacheKey($suffix),
            self::CACHE_TTL_SECONDS,
            $callback,
        );
    }

    private function cacheKey(string $suffix): string
    {
        $scope = DemoContext::isActive() ? 'demo' : 'prod';
        $sitoId = SitoContext::activeSitoId() ?? 'all';

        return 'dashboard:kpi:'.$scope.':'.$sitoId.':'.$suffix;
    }
}
