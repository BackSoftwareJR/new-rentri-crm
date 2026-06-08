<?php

namespace Tests\Feature\Sprint25;

use App\Domain\Legacy\LegacyImportService;
use App\Models\EcommerceProdotto;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class LegacyImportRicambiCommandTest extends TestCase
{
    public function test_dry_run_ricambi_counts_without_db_writes(): void
    {
        $before = EcommerceProdotto::query()->count();

        $exit = Artisan::call('rentri:import-legacy', [
            'entity'    => 'ricambi',
            '--dry-run' => true,
        ]);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Importate: 3', Artisan::output());
        $this->assertSame($before, EcommerceProdotto::query()->count());
    }

    public function test_import_ricambi_creates_records_from_fixture(): void
    {
        $service = app(LegacyImportService::class);
        $result = $service->import('ricambi', $service->defaultFixturePath('ricambi'));

        $this->assertSame(3, $result['imported']);
        $this->assertSame(0, $result['skipped']);
        $this->assertDatabaseHas('ecommerce_prodotti', [
            'codice'    => 'RIC-LM001',
            'nome'      => 'Motore 1.2 8V',
            'categoria' => 'motore',
            'prezzo'    => 450.00,
            'giacenza'  => 2,
            'attivo'    => true,
        ]);
        $this->assertDatabaseHas('ecommerce_prodotti', [
            'codice'      => 'RIC-AS002',
            'descrizione' => 'legacy_id:LEG-R-4002 — Alternatore ricondizionato',
        ]);
    }

    public function test_import_ricambi_with_limit_processes_subset(): void
    {
        $service = app(LegacyImportService::class);
        $result = $service->import(
            'ricambi',
            $service->defaultFixturePath('ricambi'),
            dryRun: false,
            limit: 1,
        );

        $this->assertSame(1, $result['processed']);
        $this->assertSame(1, $result['imported']);
        $this->assertSame(1, EcommerceProdotto::query()->count());
    }

    public function test_second_ricambi_import_skips_duplicates(): void
    {
        $service = app(LegacyImportService::class);
        $path = $service->defaultFixturePath('ricambi');

        $service->import('ricambi', $path);
        $second = $service->import('ricambi', $path);

        $this->assertSame(3, $second['skipped']);
        $this->assertSame(0, $second['imported']);
        $this->assertSame(3, EcommerceProdotto::query()->count());
    }

    public function test_invalid_entity_fails(): void
    {
        $exit = Artisan::call('rentri:import-legacy', ['entity' => 'ordini']);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('Import fallito', Artisan::output());
    }
}
