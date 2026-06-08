<?php

namespace Tests\Feature\Sprint21;

use App\Domain\Legacy\LegacyImportService;
use App\Enums\VfuStato;
use App\Models\VfuRegistration;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class LegacyImportVfuCommandTest extends TestCase
{
    public function test_dry_run_vfu_counts_without_db_writes(): void
    {
        $before = VfuRegistration::query()->count();

        $exit = Artisan::call('rentri:import-legacy', [
            'entity'    => 'vfu',
            '--dry-run' => true,
        ]);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Importate: 3', Artisan::output());
        $this->assertSame($before, VfuRegistration::query()->count());
    }

    public function test_import_vfu_creates_records_from_fixture(): void
    {
        $service = app(LegacyImportService::class);
        $result = $service->import('vfu', $service->defaultFixturePath('vfu'));

        $this->assertSame(3, $result['imported']);
        $this->assertSame(0, $result['skipped']);
        $this->assertDatabaseHas('vfu_registrations', [
            'targa'  => 'AB123CD',
            'marca'  => 'FIAT',
            'modello'=> 'Panda',
            'stato'  => VfuStato::Accettato->value,
        ]);
        $this->assertDatabaseHas('vfu_registrations', [
            'note' => 'legacy_id:LEG-V-2001',
        ]);
    }

    public function test_import_vfu_with_limit_processes_subset(): void
    {
        $service = app(LegacyImportService::class);
        $result = $service->import(
            'vfu',
            $service->defaultFixturePath('vfu'),
            dryRun: false,
            limit: 1,
        );

        $this->assertSame(1, $result['processed']);
        $this->assertSame(1, $result['imported']);
        $this->assertSame(1, VfuRegistration::query()->count());
    }

    public function test_second_vfu_import_skips_duplicates(): void
    {
        $service = app(LegacyImportService::class);
        $path = $service->defaultFixturePath('vfu');

        $service->import('vfu', $path);
        $second = $service->import('vfu', $path);

        $this->assertSame(3, $second['skipped']);
        $this->assertSame(0, $second['imported']);
        $this->assertSame(3, VfuRegistration::query()->count());
    }

    public function test_invalid_entity_fails(): void
    {
        $exit = Artisan::call('rentri:import-legacy', ['entity' => 'ordini']);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('Import fallito', Artisan::output());
    }
}
