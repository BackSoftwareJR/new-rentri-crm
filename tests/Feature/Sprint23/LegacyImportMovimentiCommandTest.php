<?php

namespace Tests\Feature\Sprint23;

use App\Domain\Legacy\LegacyImportService;
use App\Enums\RegistroMovimentoTipo;
use App\Models\RegistroMovimento;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class LegacyImportMovimentiCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $service = app(LegacyImportService::class);
        $service->import('codici_cer', $service->defaultFixturePath('codici_cer'));
    }

    public function test_dry_run_movimenti_counts_without_db_writes(): void
    {
        $before = RegistroMovimento::query()->count();

        $exit = Artisan::call('rentri:import-legacy', [
            'entity'    => 'movimenti',
            '--dry-run' => true,
        ]);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Importate: 3', Artisan::output());
        $this->assertSame($before, RegistroMovimento::query()->count());
    }

    public function test_import_movimenti_creates_records_from_fixture(): void
    {
        $service = app(LegacyImportService::class);
        $result = $service->import('movimenti', $service->defaultFixturePath('movimenti'));

        $this->assertSame(3, $result['imported']);
        $this->assertSame(0, $result['skipped']);
        $this->assertDatabaseHas('registro_movimenti', [
            'tipo'    => RegistroMovimentoTipo::Carico->value,
            'peso_kg' => 120.5,
            'note'    => 'legacy_id:LEG-M-3001 — Carico manuale legacy',
        ]);
        $this->assertDatabaseHas('registro_movimenti', [
            'tipo' => RegistroMovimentoTipo::Scarico->value,
            'note' => 'legacy_id:LEG-M-3002',
        ]);
    }

    public function test_import_movimenti_with_limit_processes_subset(): void
    {
        $service = app(LegacyImportService::class);
        $result = $service->import(
            'movimenti',
            $service->defaultFixturePath('movimenti'),
            dryRun: false,
            limit: 1,
        );

        $this->assertSame(1, $result['processed']);
        $this->assertSame(1, $result['imported']);
        $this->assertSame(1, RegistroMovimento::query()->count());
    }

    public function test_second_movimenti_import_skips_duplicates(): void
    {
        $service = app(LegacyImportService::class);
        $path = $service->defaultFixturePath('movimenti');

        $service->import('movimenti', $path);
        $second = $service->import('movimenti', $path);

        $this->assertSame(3, $second['skipped']);
        $this->assertSame(0, $second['imported']);
        $this->assertSame(3, RegistroMovimento::query()->count());
    }

    public function test_invalid_entity_fails(): void
    {
        $exit = Artisan::call('rentri:import-legacy', ['entity' => 'ordini']);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('Import fallito', Artisan::output());
    }
}
