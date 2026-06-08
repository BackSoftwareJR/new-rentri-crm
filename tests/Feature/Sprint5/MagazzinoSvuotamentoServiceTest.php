<?php

namespace Tests\Feature\Sprint5;

use App\Domain\Magazzino\MagazzinoSvuotamentoService;
use App\Enums\SvuotamentoStato;
use App\Models\Anagrafica;
use App\Models\Authorization;
use App\Models\CodiceCer;
use App\Models\MagazzinoRifiuto;
use App\Models\MagazzinoSvuotamento;
use App\Models\User;
use Tests\TestCase;

class MagazzinoSvuotamentoServiceTest extends TestCase
{
    private MagazzinoSvuotamentoService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MagazzinoSvuotamentoService::class);
    }

    public function test_richiedi_svuotamento_creates_record_with_stato_richiesto(): void
    {
        $user = User::factory()->create();
        $cer = CodiceCer::factory()->create();
        MagazzinoRifiuto::create(['codice_cer_id' => $cer->id, 'quantita_attuale_kg' => 100]);

        [$impianto, $trasportatore] = $this->seedImpiantoAndTrasportatore();

        $svuotamento = $this->service->richiediSvuotamento(
            $cer->id,
            $impianto->id,
            $trasportatore->id,
            false,
            50,
            'Note test',
            $user->id,
        );

        $this->assertInstanceOf(MagazzinoSvuotamento::class, $svuotamento);
        $this->assertSame(SvuotamentoStato::Richiesto, $svuotamento->stato);
        $this->assertSame(50.0, (float) $svuotamento->quantita_kg);
        $this->assertSame($impianto->id, $svuotamento->anagrafica_id);
        $this->assertSame($trasportatore->id, $svuotamento->trasportatore_anagrafica_id);
        $this->assertDatabaseHas('trasporti', [
            'magazzino_svuotamento_id' => $svuotamento->id,
            'stato' => 'in_preparazione',
        ]);
        $this->assertSame(100.0, (float) MagazzinoRifiuto::where('codice_cer_id', $cer->id)->value('quantita_attuale_kg'));
    }

    public function test_quantita_disponibile_subtracts_impegnata(): void
    {
        $user = User::factory()->create();
        $cer = CodiceCer::factory()->create();
        MagazzinoRifiuto::create(['codice_cer_id' => $cer->id, 'quantita_attuale_kg' => 100]);
        [$impianto, $trasportatore] = $this->seedImpiantoAndTrasportatore();

        $this->service->richiediSvuotamento($cer->id, $impianto->id, $trasportatore->id, false, 30, null, $user->id);

        $this->assertSame(70.0, $this->service->quantitaDisponibile($cer->id));
        $this->assertSame(30.0, $this->service->quantitaImpegnata($cer->id));
    }

    public function test_rejects_quantity_above_disponibile(): void
    {
        $user = User::factory()->create();
        $cer = CodiceCer::factory()->create();
        MagazzinoRifiuto::create(['codice_cer_id' => $cer->id, 'quantita_attuale_kg' => 10]);
        [$impianto, $trasportatore] = $this->seedImpiantoAndTrasportatore();

        $this->expectException(\InvalidArgumentException::class);
        $this->service->richiediSvuotamento($cer->id, $impianto->id, $trasportatore->id, false, 20, null, $user->id);
    }

    public function test_rejects_trasportatore_without_valid_authorization(): void
    {
        $user = User::factory()->create();
        $cer = CodiceCer::factory()->create();
        MagazzinoRifiuto::create(['codice_cer_id' => $cer->id, 'quantita_attuale_kg' => 50]);
        [$impianto] = $this->seedImpiantoAndTrasportatore();

        $nonConforme = Anagrafica::factory()->create([
            'tipo' => 'trasportatore',
            'email' => 'bad@example.com',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->richiediSvuotamento($cer->id, $impianto->id, $nonConforme->id, false, 10, null, $user->id);
    }

    /**
     * @return array{0: Anagrafica, 1: Anagrafica}
     */
    private function seedImpiantoAndTrasportatore(): array
    {
        $impianto = Anagrafica::factory()->create([
            'tipo' => 'impianto',
            'email' => 'impianto@test.local',
        ]);

        $trasportatore = Anagrafica::factory()->create([
            'tipo' => 'trasportatore',
            'email' => 'trasporti@test.local',
            'gestisce_trasporti' => true,
        ]);

        Authorization::factory()->create([
            'anagrafica_id' => $trasportatore->id,
            'scade_il' => now()->addYear(),
        ]);

        return [$impianto, $trasportatore];
    }
}
