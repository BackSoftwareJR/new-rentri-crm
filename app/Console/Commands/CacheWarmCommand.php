<?php

namespace App\Console\Commands;

use App\Domain\Azienda\AziendaSettingService;
use App\Domain\Dashboard\DashboardKpiService;
use App\Domain\Dashboard\KpiRedisCacheService;
use App\Domain\Magazzino\CodiceCerService;
use App\Models\CodiceCer;
use App\Models\CompanySetting;
use App\Models\RentriSetting;
use App\Models\Sito;
use App\Support\Sito\SitoContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CacheWarmCommand extends Command
{
    protected $signature = 'cache:warm';

    protected $description = 'Pre-riscalda cache applicative dopo deploy (KPI, CER, RENTRI, azienda)';

    public function handle(
        DashboardKpiService $kpi,
        KpiRedisCacheService $kpiRedis,
        CodiceCerService $codiciCer,
        AziendaSettingService $azienda,
    ): int {
        $this->info('Cache warm — avvio');
        $warmed = 0;

        $siti = Sito::query()->where('is_active', true)->orderBy('id')->get();

        if ($siti->isEmpty()) {
            $this->warmDashboardKpi($kpi, $kpiRedis, null, $warmed);
        } else {
            foreach ($siti as $sito) {
                $this->warmDashboardKpi($kpi, $kpiRedis, $sito->id, $warmed);
                $this->warmRentriSetting($sito->id, $warmed);
            }
        }

        $this->warmCodiciCerCatalog($codiciCer, $warmed);
        $this->warmCompanySettings($azienda, $warmed);

        $this->newLine();
        $this->info(sprintf('Cache warm completato — %d entry riscaldate.', $warmed));

        return self::SUCCESS;
    }

    private function warmDashboardKpi(
        DashboardKpiService $kpi,
        KpiRedisCacheService $kpiRedis,
        ?int $sitoId,
        int &$warmed,
    ): void {
        SitoContext::withSitoId($sitoId, function () use ($kpi, $kpiRedis, $sitoId, &$warmed): void {
            $label = $sitoId !== null ? 'sito #'.$sitoId : 'default';
            $this->line('  KPI dashboard ('.$label.')…');

            $kpi->aggregate();
            $kpiRedis->aggregate($kpi);
            $warmed += 2;
        });
    }

    private function warmRentriSetting(int $sitoId, int &$warmed): void
    {
        foreach ([false, true] as $isDemo) {
            $scope = $isDemo ? 'demo' : 'prod';
            $cacheKey = "rentri_setting:warm:{$scope}:{$sitoId}";

            Cache::remember($cacheKey, 300, function () use ($sitoId, $isDemo) {
                return RentriSetting::query()
                    ->where('sito_id', $sitoId)
                    ->where('is_demo', $isDemo)
                    ->first()
                    ?->toArray();
            });

            $warmed++;
        }

        $this->line('  RentriSetting sito #'.$sitoId.'…');
    }

    private function warmCodiciCerCatalog(CodiceCerService $codiciCer, int &$warmed): void
    {
        $this->line('  Catalogo Codici CER…');

        Cache::remember('codici_cer:catalog', 300, function () use ($codiciCer) {
            return $codiciCer->query()
                ->where('attivo', true)
                ->get(['id', 'codice', 'descrizione', 'categoria', 'um', 'attivo'])
                ->map(fn (CodiceCer $cer) => [
                    'id'          => $cer->id,
                    'codice'      => $cer->codice,
                    'descrizione' => $cer->descrizione,
                    'categoria'   => $cer->categoria,
                    'um'          => $cer->um,
                    'attivo'      => $cer->attivo,
                ])
                ->all();
        });

        $warmed++;
    }

    private function warmCompanySettings(AziendaSettingService $azienda, int &$warmed): void
    {
        $this->line('  CompanySetting / AziendaSetting…');

        $azienda->all();

        foreach (array_keys(CompanySetting::defaults()) as $key) {
            CompanySetting::get($key);
            $warmed++;
        }
    }
}
