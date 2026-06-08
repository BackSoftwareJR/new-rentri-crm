<?php

namespace App\Http\Livewire\Segreteria;

use App\Domain\Anagrafiche\AuthorizationAlertService;
use App\Domain\Dashboard\BusinessKpiDashboardService;
use App\Domain\Dashboard\BusinessKpiExportService;
use App\Domain\Dashboard\BusinessKpiAlertService;
use App\Domain\Dashboard\DashboardAnalyticsService;
use App\Domain\Dashboard\DashboardKpiService;
use App\Domain\Dashboard\KpiExportService;
use App\Domain\Dashboard\KpiRedisCacheService;
use App\Domain\Demo\DemoSeedService;
use App\Domain\Legacy\LegacyImportDiffReportService;
use App\Domain\Legacy\LegacyImportService;
use App\Domain\Legacy\LegacyImportSyncService;
use App\Domain\Magazzino\SerbatoioAlertService;
use App\Domain\Rentri\RentriProdReadinessService;
use App\Support\DashboardReport;
use App\Support\Demo\DemoContext;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Title('Dashboard Segreteria')]
class Dashboard extends SegreteriaPage
{
    use AuthorizesRequests;

    #[Url]
    public string $periodo = 'current_month';

    #[Url]
    public string $businessPeriodo = 'last_7_days';

    public function mount(): void
    {
        $this->authorize('view', DashboardReport::instance());

        if (! in_array($this->periodo, app(DashboardAnalyticsService::class)->periodOptions(), true)) {
            $this->periodo = 'current_month';
        }

        if (! in_array($this->businessPeriodo, app(BusinessKpiDashboardService::class)->periodOptions(), true)) {
            $this->businessPeriodo = 'last_7_days';
        }
    }

    public function exportKpiCsv(KpiExportService $export): StreamedResponse
    {
        $this->authorize('export', DashboardReport::instance());

        return $export->exportMonthlyTrend(6);
    }

    public function exportBusinessKpiCsv(BusinessKpiExportService $export): StreamedResponse
    {
        $this->authorize('export', DashboardReport::instance());

        $csv = $export->toCsv($this->businessPeriodo);
        $filename = 'kpi-business-'.str_replace('_', '-', $this->businessPeriodo).'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(
            static function () use ($csv): void {
                echo $csv;
            },
            $filename,
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }

    public function refreshKpi(KpiRedisCacheService $kpiCache): void
    {
        $this->authorize('refreshKpi', DashboardReport::instance());

        $kpiCache->forget();
        session()->flash('success', 'Cache KPI invalidata — metriche ricalcolate.');
    }

    public function render(
        DashboardKpiService $kpiService,
        KpiRedisCacheService $kpiCache,
        DashboardAnalyticsService $analytics,
        BusinessKpiDashboardService $businessKpi,
        BusinessKpiAlertService $businessKpiAlerts,
        AuthorizationAlertService $authAlerts,
        SerbatoioAlertService $serbatoioAlerts,
        DemoSeedService $demoSeed,
        LegacyImportService $legacyImport,
        LegacyImportSyncService $legacySync,
        LegacyImportDiffReportService $legacyDiff,
        RentriProdReadinessService $rentriProdReadiness,
    ): View {
        $lastSyncRun = $legacyDiff->lastRun();
        $kpiPayload = $kpiCache->aggregate($kpiService);

        $data = [
            'kpi'                  => $kpiPayload['kpi'],
            'kpiCache'             => $kpiPayload['cache'],
            'analytics'            => $analytics->comparisonForPeriod($this->periodo),
            'monthlyTrend'         => $analytics->monthlyTrend(6),
            'businessKpi'          => $businessKpi->comparisonForPeriod($this->businessPeriodo),
            'businessKpiAlert'     => $businessKpiAlerts->lastCheck(),
            'businessKpiBreaches'  => $businessKpiAlerts->recentBreaches(5),
            'businessPeriodo'      => $this->businessPeriodo,
            'businessPeriodOptions' => [
                'last_7_days'  => 'Ultimi 7 giorni',
                'last_30_days' => 'Ultimi 30 giorni',
            ],
            'periodo'              => $this->periodo,
            'periodOptions'        => [
                'current_month'  => 'Mese corrente',
                'previous_month' => 'Mese precedente',
                'last_3_months'  => 'Ultimi 3 mesi',
                'last_6_months'  => 'Ultimi 6 mesi',
            ],
            'showRentriProdStubBanner' => $rentriProdReadiness->shouldShowProdStubBanner(),
            'authAlerts'           => $authAlerts->summary(),
            'authAlertItems'       => $authAlerts->recentAlerts(),
            'serbatoioAlerts'      => $serbatoioAlerts->summary(),
            'serbatoioAlertItems'  => $serbatoioAlerts->recentAlerts(),
            'legacyReportRows'     => $legacyImport->reportRows(),
            'legacyLastSyncRun'    => $lastSyncRun,
            'legacyDiffSummary'    => $lastSyncRun?->diff_summary ?? [],
            'legacySyncRunLog'     => $legacyDiff->runLogRows(5),
        ];

        if (DemoContext::isActive()) {
            $data['demoWalkthrough'] = [
                'seeded'   => $demoSeed->isSeeded(),
                'steps'    => $demoSeed->walkthroughSteps(),
                'progress' => $demoSeed->walkthroughProgress(),
            ];
        }

        return $this->segreteriaView(
            'livewire.segreteria.dashboard',
            $data,
            'dashboard',
            'Dashboard',
            'Home',
            'Dashboard Segreteria',
        );
    }
}
