<?php

namespace Tests\Feature\Sprint43;

use App\Domain\Demo\DemoSeedService;
use App\Enums\SvuotamentoStato;
use App\Enums\TrasportoStato;
use App\Models\Anagrafica;
use App\Models\CodiceCer;
use App\Models\MagazzinoSvuotamento;
use App\Models\Trasporto;
use App\Support\Demo\DemoIsolationException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TrasportoSvuotamentoCrossRefTest extends TestCase
{
    public function test_production_trasporto_cannot_link_demo_svuotamento(): void
    {
        Config::set('demo.enabled', false);

        $svuotamentoId = $this->insertDemoSvuotamento();
        $cerId = CodiceCer::factory()->create()->id;
        $destId = Anagrafica::factory()->create(['tipo' => 'impianto'])->id;

        $this->expectException(DemoIsolationException::class);
        $this->expectExceptionMessage('svuotamento magazzino');

        Trasporto::create([
            'magazzino_svuotamento_id'     => $svuotamentoId,
            'codice_cer_id'                => $cerId,
            'anagrafica_destinatario_id'   => $destId,
            'quantita_kg'                  => 10,
            'stato'                        => TrasportoStato::InPreparazione,
        ]);
    }

    public function test_demo_trasporto_cannot_link_production_svuotamento(): void
    {
        Config::set('demo.enabled', true);

        $svuotamentoId = $this->insertProdSvuotamento();
        $cerId = CodiceCer::factory()->create()->id;
        $destId = Anagrafica::factory()->create(['tipo' => 'impianto'])->id;

        $this->expectException(DemoIsolationException::class);

        Trasporto::create([
            'magazzino_svuotamento_id'     => $svuotamentoId,
            'codice_cer_id'                => $cerId,
            'anagrafica_destinatario_id'   => $destId,
            'quantita_kg'                  => 10,
            'stato'                        => TrasportoStato::InPreparazione,
        ]);
    }

    public function test_demo_seed_links_svuotamento_and_trasporto(): void
    {
        Config::set('demo.enabled', true);

        Artisan::call('rentri:demo-seed');

        $trasporto = app(DemoSeedService::class)->demoTrasporto();
        $svuotamento = app(DemoSeedService::class)->demoSvuotamento();

        $this->assertNotNull($trasporto);
        $this->assertNotNull($svuotamento);
        $this->assertSame($svuotamento->id, $trasporto->magazzino_svuotamento_id);
        $this->assertTrue($trasporto->is_demo);
        $this->assertTrue($svuotamento->is_demo);
    }

    public function test_demo_seed_is_idempotent_for_svuotamenti(): void
    {
        Config::set('demo.enabled', true);

        Artisan::call('rentri:demo-seed');
        $svuotamenti = MagazzinoSvuotamento::count();
        $trasporti = Trasporto::count();

        Artisan::call('rentri:demo-seed');

        $this->assertSame($svuotamenti, MagazzinoSvuotamento::count());
        $this->assertSame($trasporti, Trasporto::count());
    }

    private function insertDemoSvuotamento(): int
    {
        return $this->insertSvuotamento(true);
    }

    private function insertProdSvuotamento(): int
    {
        return $this->insertSvuotamento(false);
    }

    private function insertSvuotamento(bool $isDemo): int
    {
        $cerId = CodiceCer::factory()->create()->id;
        $impId = Anagrafica::factory()->create(['tipo' => 'impianto', 'email' => 'x@test.local'])->id;

        return (int) DB::table('magazzino_svuotamenti')->insertGetId([
            'codice_cer_id'         => $cerId,
            'anagrafica_id'         => $impId,
            'trasportatore_omesso'  => true,
            'stato'                 => SvuotamentoStato::Richiesto->value,
            'quantita_kg'           => 10,
            'quantita_impegnata_kg' => 10,
            'is_demo'               => $isDemo,
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);
    }
}
