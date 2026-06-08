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
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MagazzinoSvuotamentoDemoScopeTest extends TestCase
{
    public function test_production_mode_only_sees_non_demo_svuotamenti(): void
    {
        Config::set('demo.enabled', false);

        $prodId = $this->insertSvuotamento(['note_interne' => 'prod', 'is_demo' => false]);
        $this->insertSvuotamento(['note_interne' => 'demo', 'is_demo' => true]);

        $this->assertSame(1, MagazzinoSvuotamento::count());
        $this->assertSame($prodId, MagazzinoSvuotamento::first()->id);
    }

    public function test_demo_mode_only_sees_demo_svuotamenti(): void
    {
        Config::set('demo.enabled', true);

        $this->insertSvuotamento(['note_interne' => 'prod', 'is_demo' => false]);
        $demoId = MagazzinoSvuotamento::create([
            'codice_cer_id'         => $this->cerId(),
            'anagrafica_id'         => $this->impiantoId(),
            'stato'                 => SvuotamentoStato::Richiesto->value,
            'quantita_kg'           => 10,
            'quantita_impegnata_kg' => 10,
            'note_interne'          => 'demo',
        ])->id;

        $this->assertSame(1, MagazzinoSvuotamento::count());
        $this->assertSame($demoId, MagazzinoSvuotamento::first()->id);
    }

    public function test_production_cannot_update_demo_svuotamento(): void
    {
        Config::set('demo.enabled', false);

        $id = $this->insertSvuotamento(['is_demo' => true]);
        $sv = MagazzinoSvuotamento::includingAllDemoModes()->findOrFail($id);

        $this->expectException(DemoIsolationException::class);
        $sv->update(['quantita_kg' => 99]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function insertSvuotamento(array $overrides): int
    {
        return (int) DB::table('magazzino_svuotamenti')->insertGetId(array_merge([
            'codice_cer_id'         => $this->cerId(),
            'anagrafica_id'         => $this->impiantoId(),
            'trasportatore_omesso'  => true,
            'stato'                 => SvuotamentoStato::Richiesto->value,
            'quantita_kg'           => 10,
            'quantita_impegnata_kg' => 10,
            'is_demo'               => false,
            'created_at'            => now(),
            'updated_at'            => now(),
        ], $overrides));
    }

    private function cerId(): int
    {
        return CodiceCer::factory()->create()->id;
    }

    private function impiantoId(): int
    {
        return Anagrafica::factory()->create(['tipo' => 'impianto', 'email' => 'imp@test.local'])->id;
    }
}
