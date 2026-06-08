<?php

namespace Tests\Feature\Sprint72;

use App\Domain\Legacy\LegacyImportDiffReportService;
use App\Domain\Legacy\LegacyImportService;
use App\Domain\Legacy\LegacyImportSyncService;
use App\Http\Livewire\Segreteria\Dashboard;
use App\Jobs\LegacyIncrementalSyncJob;
use App\Models\Anagrafica;
use App\Models\LegacyImportSyncRun;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class LegacyImportAdvancedTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget('legacy:sync-incremental');
    }

    public function test_sync_incremental_imports_entities_on_first_run(): void
    {
        $results = app(LegacyImportSyncService::class)->syncIncremental(dryRun: false);

        $this->assertArrayHasKey('codici_cer', $results);
        $this->assertArrayHasKey('anagrafiche', $results);
        $this->assertArrayHasKey('movimenti', $results);
        $this->assertGreaterThan(0, $results['anagrafiche']['imported'] + $results['anagrafiche']['updated']);

        $this->assertDatabaseCount('legacy_import_sync_runs', 1);
        $run = LegacyImportSyncRun::first();
        $this->assertSame('completed', $run->status);
        $this->assertGreaterThan(0, $run->total_new + $run->total_updated);
    }

    public function test_second_sync_run_is_idempotent_with_skipped_records(): void
    {
        $sync = app(LegacyImportSyncService::class);
        $sync->syncIncremental(dryRun: false);

        $second = $sync->syncIncremental(dryRun: false);

        $this->assertSame(0, $second['anagrafiche']['imported']);
        $this->assertSame(0, $second['movimenti']['imported']);
        $this->assertGreaterThan(0, $second['anagrafiche']['skipped'] + $second['codici_cer']['skipped']);

        $this->assertDatabaseCount('legacy_import_sync_runs', 2);
    }

    public function test_sync_updates_existing_anagrafica_when_fixture_fields_change(): void
    {
        $import = app(LegacyImportService::class);
        $path = $import->defaultFixturePath('anagrafiche');

        $import->syncAnagrafiche($path, dryRun: false);

        Anagrafica::query()
            ->where('note', 'legacy_id:LEG-A-1001')
            ->update(['email' => 'vecchia@rossi.it']);

        $result = $import->syncAnagrafiche($path, dryRun: false);

        $this->assertSame(1, $result['updated']);
        $this->assertSame('info@rossi.it', Anagrafica::query()->where('note', 'legacy_id:LEG-A-1001')->value('email'));
    }

    public function test_diff_report_service_summarizes_entity_results(): void
    {
        app(LegacyImportSyncService::class)->syncIncremental(dryRun: false);

        $diff = app(LegacyImportDiffReportService::class);
        $last = $diff->lastRun();

        $this->assertNotNull($last);
        $this->assertNotEmpty($last->diff_summary);
        $totals = $diff->totals($last->diff_summary);
        $this->assertArrayHasKey('new', $totals);
        $this->assertSame($last->total_new, $totals['new']);
        $this->assertCount(1, $diff->runLogRows(1));
    }

    public function test_legacy_sync_incremental_command_runs_successfully(): void
    {
        $exitCode = Artisan::call('legacy:sync-incremental', ['--dry-run' => true]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Sync incrementale legacy', Artisan::output());
        $this->assertDatabaseHas('legacy_import_sync_runs', ['dry_run' => true]);
    }

    public function test_incremental_sync_job_is_unique_and_dispatchable(): void
    {
        Bus::fake();

        LegacyIncrementalSyncJob::dispatch(dryRun: true, triggeredBy: 1);

        Bus::assertDispatched(LegacyIncrementalSyncJob::class, function (LegacyIncrementalSyncJob $job) {
            return $job->dryRun === true && $job->triggeredBy === 1;
        });
    }

    public function test_dashboard_shows_last_sync_and_diff_summary(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        app(LegacyImportSyncService::class)->syncIncremental(dryRun: false);

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertSee('Ultimo sync incrementale')
            ->assertSee('Log run recenti')
            ->assertSee('Nuovi')
            ->assertSee('Aggiornati')
            ->assertSee('legacy:sync-incremental');
    }

    public function test_operatore_cannot_sync_legacy(): void
    {
        $user = User::where('email', 'operatore@example.com')->firstOrFail();

        $this->assertFalse(Gate::forUser($user)->allows('legacy.sync'));
        $this->assertFalse(Gate::forUser($user)->allows('legacy.viewRuns'));
    }
}
