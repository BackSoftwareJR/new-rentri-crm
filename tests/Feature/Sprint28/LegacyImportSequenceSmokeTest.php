<?php

namespace Tests\Feature\Sprint28;

use App\Domain\Legacy\LegacyImportService;
use App\Http\Livewire\Admin\AuditIndex;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class LegacyImportSequenceSmokeTest extends TestCase
{
    /** @var list<string> */
    private const IMPORT_ORDER = ['codici_cer', 'anagrafiche', 'vfu', 'movimenti', 'ricambi'];

    public function test_full_fixture_sequence_report_and_audit(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin);

        $service = app(LegacyImportService::class);

        foreach (self::IMPORT_ORDER as $entity) {
            $service->import($entity, $service->defaultFixturePath($entity));
        }

        $report = $service->report();
        $this->assertSame(3, $report['anagrafiche']);
        $this->assertSame(2, $report['codici_cer']);
        $this->assertSame(3, $report['vfu']);
        $this->assertSame(3, $report['movimenti']);
        $this->assertSame(3, $report['ricambi']);
        $this->assertSame(14, $service->reportTotal());

        $exit = Artisan::call('rentri:import-legacy', ['--report' => true]);
        $output = Artisan::output();

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Report import legacy', $output);
        $this->assertStringContainsString('Totale record legacy tracciati: 14', $output);

        $legacyAudits = Activity::query()->where('log_name', 'legacy')->get();
        $this->assertCount(5, $legacyAudits);

        $entities = $legacyAudits->pluck('properties.entity')->sort()->values()->all();
        $this->assertSame(['anagrafiche', 'codici_cer', 'movimenti', 'ricambi', 'vfu'], $entities);

        foreach ($legacyAudits as $activity) {
            $this->assertFalse($activity->properties->get('dry_run'));
            $this->assertContains($activity->properties->get('entity'), self::IMPORT_ORDER);
            $this->assertGreaterThanOrEqual(0, $activity->properties->get('imported'));
            $this->assertGreaterThanOrEqual(0, $activity->properties->get('skipped'));
        }
    }

    public function test_audit_index_shows_legacy_import_properties(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin);

        $service = app(LegacyImportService::class);
        $service->import('anagrafiche', $service->defaultFixturePath('anagrafiche'));

        Livewire::actingAs($admin)
            ->test(AuditIndex::class)
            ->set('modulo', 'legacy')
            ->assertSee('Import legacy anagrafiche completato')
            ->assertSee('anagrafiche · imp: 3 · skp: 0 · dry-run: no');
    }
}
