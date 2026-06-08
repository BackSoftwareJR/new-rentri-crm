<?php

namespace Tests\Feature\Sprint3;

use App\Domain\Magazzino\MagazzinoService;
use App\Enums\RegistroMovimentoTipo;
use App\Models\CodiceCer;
use App\Models\MagazzinoCaricoManuale;
use App\Models\MagazzinoRifiuto;
use App\Models\RegistroMovimento;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MagazzinoServiceTest extends TestCase
{
    private MagazzinoService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MagazzinoService::class);
    }

    public function test_carico_manuale_increments_giacenza_and_creates_registro(): void
    {
        $user = User::factory()->create();
        $cer = CodiceCer::factory()->create(['limite_kg' => 500]);
        MagazzinoRifiuto::create(['codice_cer_id' => $cer->id, 'quantita_attuale_kg' => 10]);

        $carico = $this->service->caricoManuale($cer->id, 25.5, 'Carico da fornitore test', $user->id);

        $this->assertInstanceOf(MagazzinoCaricoManuale::class, $carico);
        $this->assertSame(25.5, (float) $carico->peso_kg);

        $this->assertSame(35.5, (float) MagazzinoRifiuto::where('codice_cer_id', $cer->id)->value('quantita_attuale_kg'));

        $this->assertDatabaseHas('registro_movimenti', [
            'codice_cer_id' => $cer->id,
            'tipo'          => RegistroMovimentoTipo::Carico->value,
            'peso_kg'       => 25.5,
            'source_type'   => RegistroMovimento::SOURCE_CARICO_MANUALE,
            'source_id'     => $carico->id,
        ]);
    }

    public function test_sequential_add_peso_with_lock_preserves_both_increments(): void
    {
        $cer = CodiceCer::factory()->create();
        MagazzinoRifiuto::create(['codice_cer_id' => $cer->id, 'quantita_attuale_kg' => 0]);

        DB::transaction(function () use ($cer) {
            $this->service->addPeso($cer->id, 10);
            $this->service->addPeso($cer->id, 15);
        });

        $this->assertSame(25.0, (float) MagazzinoRifiuto::where('codice_cer_id', $cer->id)->value('quantita_attuale_kg'));
    }

    public function test_soglia_alert_calculation(): void
    {
        $this->assertSame('regolare', $this->service->calcolaStatoSoglia(null));
        $this->assertSame('regolare', $this->service->calcolaStatoSoglia(50.0));
        $this->assertSame('attenzione', $this->service->calcolaStatoSoglia(70.0));
        $this->assertSame('attenzione', $this->service->calcolaStatoSoglia(99.9));
        $this->assertSame('superata', $this->service->calcolaStatoSoglia(100.1));
    }

    public function test_list_serbatoi_summary_counts_alerts(): void
    {
        $cerOk = CodiceCer::factory()->create(['limite_kg' => 100, 'codice' => '16.01.01']);
        $cerWarn = CodiceCer::factory()->create(['limite_kg' => 100, 'codice' => '16.01.02']);
        $cerFull = CodiceCer::factory()->create(['limite_kg' => 100, 'codice' => '16.01.03']);

        MagazzinoRifiuto::create(['codice_cer_id' => $cerOk->id, 'quantita_attuale_kg' => 50]);
        MagazzinoRifiuto::create(['codice_cer_id' => $cerWarn->id, 'quantita_attuale_kg' => 75]);
        MagazzinoRifiuto::create(['codice_cer_id' => $cerFull->id, 'quantita_attuale_kg' => 110]);

        $rows = $this->service->listSerbatoi();
        $summary = $this->service->summary($rows);

        $this->assertSame(235.0, $summary['totale_kg']);
        $this->assertSame(3, $summary['codici_attivi']);
        $this->assertSame(1, $summary['in_attenzione']);
        $this->assertSame(1, $summary['soglia_superata']);

        $warnRow = $rows->firstWhere('id', $cerWarn->id);
        $this->assertSame('attenzione', $warnRow['stato']);
        $this->assertSame(75.0, $warnRow['percentuale']);
    }
}
