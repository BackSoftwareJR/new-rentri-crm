<?php

namespace Tests\Feature\Sprint49;

use App\Domain\Demo\DemoSeedService;
use App\Domain\Magazzino\MagazzinoService;
use App\Models\CodiceCer;
use App\Models\MagazzinoRifiuto;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class DemoMagazzinoStockIsolationTest extends TestCase
{
    public function test_palestra_uses_demo_movimenti_kg_not_prod_magazzino_rifiuti(): void
    {
        Config::set('demo.enabled', false);
        session([config('demo.session.key') => true]);

        $prodCer = CodiceCer::factory()->create(['codice' => '99.99.01', 'attivo' => true]);
        MagazzinoRifiuto::create([
            'codice_cer_id'       => $prodCer->id,
            'quantita_attuale_kg' => 999.0,
        ]);

        Artisan::call('rentri:demo-seed');

        $demoCer = app(DemoSeedService::class)->demoTrasporto()?->codiceCer;
        $this->assertNotNull($demoCer);

        MagazzinoRifiuto::create([
            'codice_cer_id'       => $demoCer->id,
            'quantita_attuale_kg' => 888.0,
        ]);

        $rows = app(MagazzinoService::class)->listSerbatoi();
        $demoRow = $rows->firstWhere('codice', $demoCer->codice);

        $this->assertNotNull($demoRow);
        $this->assertSame(250.0, $demoRow['quantita_attuale_kg']);
        $this->assertFalse($rows->contains('codice', $prodCer->codice));
    }

    public function test_add_peso_does_not_mutate_prod_stock_in_palestra(): void
    {
        Config::set('demo.enabled', false);
        session([config('demo.session.key') => true]);

        $cer = CodiceCer::factory()->create(['attivo' => true]);
        MagazzinoRifiuto::create([
            'codice_cer_id'       => $cer->id,
            'quantita_attuale_kg' => 40.0,
        ]);

        app(MagazzinoService::class)->addPeso($cer->id, 10.0);

        $this->assertSame(40.0, (float) MagazzinoRifiuto::where('codice_cer_id', $cer->id)->value('quantita_attuale_kg'));
    }
}
