<?php

namespace Tests\Feature\Sprint2;

use App\Domain\Bonifica\BonificaService;
use App\Enums\RegistroMovimentoTipo;
use App\Enums\VfuStato;
use App\Models\CodiceCer;
use App\Models\MagazzinoRifiuto;
use App\Models\RegistroMovimento;
use App\Models\VfuRegistration;
use Tests\TestCase;

class BonificaServiceTest extends TestCase
{
  private BonificaService $service;

  protected function setUp(): void
  {
    parent::setUp();
    $this->service = app(BonificaService::class);
  }

  public function test_pericolosi_phase_creates_movimenti_and_magazzino(): void
  {
    $cerPer = CodiceCer::factory()->pericoloso()->create(['codice' => '16.01.99*']);
    $cerAlt = CodiceCer::factory()->create(['codice' => '16.01.99', 'categoria' => 'altro']);
    MagazzinoRifiuto::create(['codice_cer_id' => $cerPer->id, 'quantita_attuale_kg' => 0]);
    MagazzinoRifiuto::create(['codice_cer_id' => $cerAlt->id, 'quantita_attuale_kg' => 0]);

    $vfu = VfuRegistration::factory()->accettatoPerBonifica()->create();
    $bonifica = $this->service->startBonifica($vfu);

    $this->service->saveMovimenti($bonifica, [
      ['codice_cer_id' => $cerPer->id, 'quantita' => 12, 'um' => 'kg', 'peso_kg' => 12],
      ['codice_cer_id' => $cerAlt->id, 'quantita' => 0, 'um' => 'kg', 'peso_kg' => 0],
    ]);

    $this->completeChecklist($bonifica->fresh());
    $this->service->completePericolosi($bonifica->fresh());

    $this->assertDatabaseHas('registro_movimenti', [
      'codice_cer_id' => $cerPer->id,
      'tipo'          => RegistroMovimentoTipo::Carico->value,
      'peso_kg'       => 12,
    ]);

    $this->assertDatabaseMissing('registro_movimenti', [
      'codice_cer_id' => $cerAlt->id,
    ]);

    $this->assertSame(12.0, (float) MagazzinoRifiuto::where('codice_cer_id', $cerPer->id)->value('quantita_attuale_kg'));
    $this->assertNotNull($vfu->fresh()->bonifica_pericolosi_completata_at);
  }

  public function test_cannot_skip_pericolosi_if_deadline_active(): void
  {
    $cerPer = CodiceCer::factory()->pericoloso()->create(['codice' => '16.01.98*']);
    MagazzinoRifiuto::create(['codice_cer_id' => $cerPer->id, 'quantita_attuale_kg' => 0]);

    $vfu = VfuRegistration::factory()->accettatoPerBonifica()->create([
      'data_accettazione' => now()->subDays(5)->toDateString(),
    ]);

    $bonifica = $this->service->startBonifica($vfu);
    $this->service->saveMovimenti($bonifica, [
      ['codice_cer_id' => $cerPer->id, 'quantita' => 5, 'um' => 'kg', 'peso_kg' => 5],
    ]);

    $this->assertTrue($this->service->isPericolosiDeadlineActive($vfu));

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Completa prima la bonifica dei liquidi e rifiuti pericolosi.');

    $this->service->completeBonifica($bonifica->fresh());
  }

  public function test_complete_bonifica_updates_vfu_state(): void
  {
    $cerPer = CodiceCer::factory()->pericoloso()->create(['codice' => '16.01.97*']);
    $cerAlt = CodiceCer::factory()->create(['codice' => '16.01.97', 'categoria' => 'altro']);
    MagazzinoRifiuto::create(['codice_cer_id' => $cerPer->id, 'quantita_attuale_kg' => 0]);
    MagazzinoRifiuto::create(['codice_cer_id' => $cerAlt->id, 'quantita_attuale_kg' => 0]);

    $vfu = VfuRegistration::factory()->accettatoPerBonifica()->create();
    $bonifica = $this->service->startBonifica($vfu);

    $this->assertSame(VfuStato::InBonifica, $vfu->fresh()->stato);

    $this->service->saveMovimenti($bonifica, [
      ['codice_cer_id' => $cerPer->id, 'quantita' => 8, 'um' => 'kg', 'peso_kg' => 8],
      ['codice_cer_id' => $cerAlt->id, 'quantita' => 20, 'um' => 'kg', 'peso_kg' => 20],
    ]);

    $this->completeChecklist($bonifica->fresh());
    $this->service->completePericolosi($bonifica->fresh());
    $this->service->completeBonifica($bonifica->fresh());

    $vfu->refresh();
    $bonifica->refresh();

    $this->assertSame(VfuStato::Bonificato, $vfu->stato);
    $this->assertSame('completata', $bonifica->stato);
    $this->assertDatabaseHas('registro_movimenti', ['codice_cer_id' => $cerAlt->id, 'peso_kg' => 20]);
    $this->assertSame(20.0, (float) MagazzinoRifiuto::where('codice_cer_id', $cerAlt->id)->value('quantita_attuale_kg'));
  }

  private function completeChecklist(\App\Models\BonificaVfu $bonifica): void
  {
    $this->service->saveChecklistPericolosi($bonifica, [
      'dpi'            => true,
      'contenitori'    => true,
      'area_ventilata' => true,
    ]);
  }
}
