<?php

namespace Tests\Feature\Sprint19;

use App\Domain\Legacy\LegacyImportService;
use App\Models\Anagrafica;
use App\Models\CodiceCer;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class LegacyImportCommandTest extends TestCase
{
    public function test_dry_run_anagrafiche_counts_without_db_writes(): void
    {
        $before = Anagrafica::query()->count();

        $exit = Artisan::call('rentri:import-legacy', [
            'entity'   => 'anagrafiche',
            '--dry-run'=> true,
        ]);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Importate: 3', Artisan::output());
        $this->assertSame($before, Anagrafica::query()->count());
    }

    public function test_import_anagrafiche_creates_records_from_fixture(): void
    {
        $service = app(LegacyImportService::class);
        $result = $service->import('anagrafiche', $service->defaultFixturePath('anagrafiche'));

        $this->assertSame(3, $result['imported']);
        $this->assertSame(0, $result['skipped']);
        $this->assertDatabaseHas('anagrafiche', [
            'ragione_sociale' => 'Trasporti Rossi Srl',
            'piva'            => '12345678901',
        ]);
        $this->assertDatabaseHas('anagrafiche', [
            'note' => 'legacy_id:LEG-A-1001',
        ]);
    }

    public function test_import_with_limit_processes_subset(): void
    {
        $service = app(LegacyImportService::class);
        $result = $service->import(
            'anagrafiche',
            $service->defaultFixturePath('anagrafiche'),
            dryRun: false,
            limit: 1,
        );

        $this->assertSame(1, $result['processed']);
        $this->assertSame(1, $result['imported']);
        $this->assertSame(1, Anagrafica::query()->count());
    }

    public function test_second_import_skips_duplicates(): void
    {
        $service = app(LegacyImportService::class);
        $path = $service->defaultFixturePath('anagrafiche');

        $service->import('anagrafiche', $path);
        $second = $service->import('anagrafiche', $path);

        $this->assertSame(3, $second['skipped']);
        $this->assertSame(0, $second['imported']);
        $this->assertSame(3, Anagrafica::query()->count());
    }

    public function test_import_codici_cer_from_json_fixture(): void
    {
        Artisan::call('rentri:import-legacy', ['entity' => 'codici_cer']);

        $this->assertDatabaseHas('codici_cer', [
            'codice'    => '16.01.04',
            'categoria' => 'altro',
            'attivo'    => true,
        ]);
        $this->assertDatabaseHas('magazzino_rifiuti', [
            'codice_cer_id' => CodiceCer::where('codice', '16.01.04')->value('id'),
        ]);
        $this->assertSame(2, CodiceCer::query()->whereIn('codice', ['16.01.04', '13.02.05*'])->count());
    }

    public function test_invalid_entity_fails(): void
    {
        $exit = Artisan::call('rentri:import-legacy', ['entity' => 'ordini']);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('Import fallito', Artisan::output());
    }
}
