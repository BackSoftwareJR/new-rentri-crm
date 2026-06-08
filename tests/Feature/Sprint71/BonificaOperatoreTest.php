<?php

namespace Tests\Feature\Sprint71;

use App\Domain\Bonifica\BonificaPericolosiChecklistService;
use App\Domain\Bonifica\BonificaService;
use App\Domain\Ecommerce\OperatoreFotoCatalogoService;
use App\Http\Livewire\Operatore\BonificaWizard;
use App\Http\Livewire\Operatore\Ricambi;
use App\Models\CodiceCer;
use App\Models\EcommerceProdotto;
use App\Models\EcommerceProdottoFotoOperatore;
use App\Models\MagazzinoRifiuto;
use App\Models\User;
use App\Models\VfuRegistration;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class BonificaOperatoreTest extends TestCase
{
    public function test_operatore_links_foto_to_catalog_prodotto(): void
    {
        Storage::fake('public');

        $user = User::where('email', 'operatore@example.com')->firstOrFail();
        $prodotto = EcommerceProdotto::factory()->create(['codice' => 'RIC-S71', 'giacenza' => 3]);

        $this->assertTrue(Gate::forUser($user)->allows('linkPhoto', $prodotto));

        Livewire::actingAs($user)
            ->test(Ricambi::class)
            ->set('prodottoSelezionato', $prodotto->id)
            ->set('fotoBulk', [UploadedFile::fake()->image('motore.jpg')])
            ->call('uploadFotoBulk')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('ecommerce_prodotto_foto_operatore', 1);
        $foto = EcommerceProdottoFotoOperatore::first();
        $this->assertSame($prodotto->id, $foto->ecommerce_prodotto_id);
        Storage::disk('public')->assertExists($foto->path);
    }

    public function test_link_photo_denied_for_cross_demo_scope_prodotto(): void
    {
        $user = User::where('email', 'operatore@example.com')->firstOrFail();
        $prodotto = EcommerceProdotto::factory()->make();
        $prodotto->is_demo = ! \App\Support\Demo\DemoContext::isActive();

        $this->assertFalse(Gate::forUser($user)->allows('linkPhoto', $prodotto));
    }

    public function test_checklist_blocks_pericolosi_advance_without_manual_steps(): void
    {
        $bonifica = $this->seedBonificaSession();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Checklist pericolosi incompleta');

        app(BonificaService::class)->completePericolosi($bonifica);
    }

    public function test_checklist_allows_advance_when_complete(): void
    {
        $bonifica = $this->seedBonificaSession();

        app(BonificaService::class)->saveChecklistPericolosi($bonifica, [
            'dpi'            => true,
            'contenitori'    => true,
            'area_ventilata' => true,
        ]);

        app(BonificaService::class)->completePericolosi($bonifica->fresh());

        $this->assertNotNull($bonifica->vfuRegistration->fresh()->bonifica_pericolosi_completata_at);
    }

    public function test_wizard_renders_checklist_and_blocks_incomplete_confirm(): void
    {
        $user = User::where('email', 'operatore@example.com')->firstOrFail();
        [, $vfu, $cerPer] = $this->seedBonificaData('CHK71AB');

        Livewire::actingAs($user)
            ->test(BonificaWizard::class, ['vfu' => $vfu])
            ->assertSee('Checklist pericolosi')
            ->assertSee('/4')
            ->set('quantita.'.$cerPer->id, 5)
            ->call('confirmPericolosi')
            ->assertSet('step', 1)
            ->assertSee('Checklist pericolosi incompleta');

        Livewire::actingAs($user)
            ->test(BonificaWizard::class, ['vfu' => $vfu])
            ->set('quantita.'.$cerPer->id, 5)
            ->set('checklist.dpi', true)
            ->set('checklist.contenitori', true)
            ->set('checklist.area_ventilata', true)
            ->call('confirmPericolosi')
            ->assertSet('step', 2)
            ->assertSet('pericolosiCompletata', true);
    }

    public function test_checklist_service_quantita_step_auto_completes_with_movimenti(): void
    {
        $cerPer = CodiceCer::factory()->pericoloso()->create(['codice' => '16.01.72*']);
        MagazzinoRifiuto::create(['codice_cer_id' => $cerPer->id, 'quantita_attuale_kg' => 0]);

        $vfu = VfuRegistration::factory()->accettatoPerBonifica()->create(['targa' => 'QTY71XY']);
        $bonifica = app(BonificaService::class)->startBonifica($vfu);
        app(BonificaService::class)->saveMovimenti($bonifica, [
            ['codice_cer_id' => $cerPer->id, 'quantita' => 0, 'um' => 'kg', 'peso_kg' => 0],
        ]);

        $checklist = app(BonificaPericolosiChecklistService::class);
        $this->assertFalse($checklist->quantitaPericolosiComplete($bonifica->fresh()));

        app(BonificaService::class)->saveMovimenti($bonifica->fresh(), [
            ['codice_cer_id' => $cerPer->id, 'quantita' => 3, 'um' => 'kg', 'peso_kg' => 3],
        ]);

        $this->assertTrue($checklist->quantitaPericolosiComplete($bonifica->fresh()));
    }

    public function test_operatore_foto_catalogo_service_links_bulk(): void
    {
        Storage::fake('public');

        $user = User::where('email', 'operatore@example.com')->firstOrFail();
        $prodotto = EcommerceProdotto::factory()->create();

        $linked = app(OperatoreFotoCatalogoService::class)->linkBulk(
            $prodotto,
            [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
            $user,
        );

        $this->assertCount(2, $linked);
        $this->assertSame(2, app(OperatoreFotoCatalogoService::class)->fotoForProdotto($prodotto)->count());
    }

    private function seedBonificaSession(): \App\Models\BonificaVfu
    {
        [$bonifica] = $this->seedBonificaData('SRV71CD');

        return $bonifica;
    }

    /**
     * @return array{0: \App\Models\BonificaVfu, 1: VfuRegistration, 2: CodiceCer}
     */
    private function seedBonificaData(string $targa): array
    {
        $cerPer = CodiceCer::factory()->pericoloso()->create(['codice' => '16.01.71*']);
        MagazzinoRifiuto::create(['codice_cer_id' => $cerPer->id, 'quantita_attuale_kg' => 0]);

        $vfu = VfuRegistration::factory()->accettatoPerBonifica()->create(['targa' => $targa]);
        $bonifica = app(BonificaService::class)->startBonifica($vfu);
        app(BonificaService::class)->saveMovimenti($bonifica, [
            ['codice_cer_id' => $cerPer->id, 'quantita' => 5, 'um' => 'kg', 'peso_kg' => 5],
        ]);

        return [$bonifica->fresh(), $vfu, $cerPer];
    }
}
