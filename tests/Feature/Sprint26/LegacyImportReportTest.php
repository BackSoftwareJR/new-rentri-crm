<?php

namespace Tests\Feature\Sprint26;

use App\Domain\Legacy\LegacyImportService;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class LegacyImportReportTest extends TestCase
{
    public function test_report_shows_zero_on_fresh_database(): void
    {
        $report = app(LegacyImportService::class)->report();

        $this->assertSame(0, $report['anagrafiche']);
        $this->assertSame(0, $report['vfu']);
        $this->assertSame(0, $report['movimenti']);
        $this->assertSame(0, $report['ricambi']);
        $this->assertSame(0, app(LegacyImportService::class)->reportTotal());
    }

    public function test_report_reflects_imported_anagrafiche(): void
    {
        $service = app(LegacyImportService::class);
        $service->import('anagrafiche', $service->defaultFixturePath('anagrafiche'));

        $report = $service->report();

        $this->assertSame(3, $report['anagrafiche']);
        $this->assertSame(3, $service->reportTotal());
    }

    public function test_artisan_report_command_outputs_summary(): void
    {
        $service = app(LegacyImportService::class);
        $service->import('codici_cer', $service->defaultFixturePath('codici_cer'));

        $exit = Artisan::call('rentri:import-legacy', ['--report' => true]);
        $output = Artisan::output();

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Report import legacy', $output);
        $this->assertStringContainsString('Anagrafiche', $output);
        $this->assertStringContainsString('Totale record legacy tracciati:', $output);
    }
}
