<?php

namespace Tests\Feature\Sprint102;

use App\Domain\Vfu\SmontaggioService;
use App\Enums\VfuStato;
use App\Http\Livewire\Operatore\SmontaggioWizard;
use App\Models\SmontaggioRicambio;
use App\Models\SmontaggioSession;
use App\Models\User;
use App\Models\VfuRegistration;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SmontaggioWorkflowTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Service tests
    // -------------------------------------------------------------------------

    public function test_avvia_crea_sessione_e_aggiorna_stato_vfu(): void
    {
        $operatore = User::where('email', 'operatore@example.com')->firstOrFail();
        $vfu = VfuRegistration::factory()->bonificato()->create(['targa' => 'SM102AA']);

        $service = app(SmontaggioService::class);
        $session = $service->avvia($vfu, $operatore);

        $this->assertInstanceOf(SmontaggioSession::class, $session);
        $this->assertSame($vfu->id, $session->vfu_registration_id);
        $this->assertSame($operatore->id, $session->operatore_id);
        $this->assertContains($session->stato, ['avviato', 'in_corso']);

        $this->assertSame(VfuStato::InSmontaggio, $vfu->fresh()->stato);
    }

    public function test_avvia_idempotente_per_vfu_in_smontaggio(): void
    {
        $operatore = User::where('email', 'operatore@example.com')->firstOrFail();
        $vfu = VfuRegistration::factory()->bonificato()->create(['targa' => 'SM102BB']);

        $service = app(SmontaggioService::class);
        $session1 = $service->avvia($vfu, $operatore);
        $session2 = $service->avvia($vfu->fresh(), $operatore);

        $this->assertSame($session1->id, $session2->id);
        $this->assertDatabaseCount('smontaggio_sessions', 1);
    }

    public function test_avvia_lancia_eccezione_se_vfu_non_bonificato(): void
    {
        $operatore = User::where('email', 'operatore@example.com')->firstOrFail();
        $vfu = VfuRegistration::factory()->create([
            'targa' => 'SM102CC',
            'stato' => VfuStato::InBonifica,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/bonificato/i');

        app(SmontaggioService::class)->avvia($vfu, $operatore);
    }

    public function test_aggiungi_ricambio_salva_correttamente(): void
    {
        $operatore = User::where('email', 'operatore@example.com')->firstOrFail();
        $vfu = VfuRegistration::factory()->bonificato()->create(['targa' => 'SM102DD']);
        $session = app(SmontaggioService::class)->avvia($vfu, $operatore);

        $ricambio = app(SmontaggioService::class)->aggiungiRicambio($session, [
            'descrizione' => 'Porta anteriore sinistra',
            'numero_parte' => 'VW-3C0-837-461',
            'condizione' => 'buono',
            'valore_stimato' => 75.50,
        ]);

        $this->assertInstanceOf(SmontaggioRicambio::class, $ricambio);
        $this->assertDatabaseHas('smontaggio_ricambi', [
            'smontaggio_session_id' => $session->id,
            'descrizione' => 'Porta anteriore sinistra',
            'numero_parte' => 'VW-3C0-837-461',
            'condizione' => 'buono',
        ]);

        $this->assertSame('in_corso', $session->fresh()->stato);
    }

    public function test_aggiungi_ricambio_con_foto(): void
    {
        Storage::fake('local');

        $operatore = User::where('email', 'operatore@example.com')->firstOrFail();
        $vfu = VfuRegistration::factory()->bonificato()->create(['targa' => 'SM102EE']);
        $session = app(SmontaggioService::class)->avvia($vfu, $operatore);

        $foto = UploadedFile::fake()->image('ricambio.jpg');

        $ricambio = app(SmontaggioService::class)->aggiungiRicambio($session, [
            'descrizione' => 'Specchietto dx',
            'condizione' => 'accettabile',
            'foto' => $foto,
        ]);

        $this->assertNotNull($ricambio->foto_path);
        Storage::disk('local')->assertExists($ricambio->foto_path);
    }

    public function test_completa_aggiorna_stato_sessione_e_vfu(): void
    {
        $operatore = User::where('email', 'operatore@example.com')->firstOrFail();
        $vfu = VfuRegistration::factory()->bonificato()->create(['targa' => 'SM102FF']);
        $service = app(SmontaggioService::class);
        $session = $service->avvia($vfu, $operatore);

        $service->aggiungiRicambio($session, [
            'descrizione' => 'Motore 1.6',
            'condizione' => 'per_ricambi',
        ]);

        $service->completa($session->fresh());

        $this->assertSame('completato', $session->fresh()->stato);
        $this->assertSame(VfuStato::Smontato, $vfu->fresh()->stato);
        $this->assertNotNull($session->fresh()->completed_at);
    }

    public function test_completa_lancia_eccezione_se_gia_completata(): void
    {
        $operatore = User::where('email', 'operatore@example.com')->firstOrFail();
        $vfu = VfuRegistration::factory()->bonificato()->create(['targa' => 'SM102GG']);
        $service = app(SmontaggioService::class);
        $session = $service->avvia($vfu, $operatore);
        $service->completa($session->fresh());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/già stata completata/i');

        $service->completa($session->fresh());
    }

    public function test_rimuovi_ricambio_cancella_foto(): void
    {
        Storage::fake('local');

        $operatore = User::where('email', 'operatore@example.com')->firstOrFail();
        $vfu = VfuRegistration::factory()->bonificato()->create(['targa' => 'SM102HH']);
        $service = app(SmontaggioService::class);
        $session = $service->avvia($vfu, $operatore);

        $foto = UploadedFile::fake()->image('foto.jpg');
        $ricambio = $service->aggiungiRicambio($session, [
            'descrizione' => 'Faro dx',
            'condizione' => 'buono',
            'foto' => $foto,
        ]);

        Storage::disk('local')->assertExists($ricambio->foto_path);

        $service->rimuoviRicambio($session, $ricambio->id);

        $this->assertDatabaseMissing('smontaggio_ricambi', ['id' => $ricambio->id]);
        Storage::disk('local')->assertMissing($ricambio->foto_path);
    }

    // -------------------------------------------------------------------------
    // Livewire wizard tests
    // -------------------------------------------------------------------------

    public function test_wizard_accessibile_solo_a_operatore(): void
    {
        $vfu = VfuRegistration::factory()->bonificato()->create(['targa' => 'SM102WW']);
        $segreteria = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($segreteria)
            ->test(SmontaggioWizard::class, ['vfu' => $vfu])
            ->assertForbidden();
    }

    public function test_wizard_mount_avvia_sessione(): void
    {
        $vfu = VfuRegistration::factory()->bonificato()->create(['targa' => 'SM102MO']);
        $operatore = User::where('email', 'operatore@example.com')->firstOrFail();

        Livewire::actingAs($operatore)
            ->test(SmontaggioWizard::class, ['vfu' => $vfu])
            ->assertSet('step', 1)
            ->assertSeeText($vfu->targa);

        $this->assertDatabaseHas('smontaggio_sessions', ['vfu_registration_id' => $vfu->id]);
    }

    public function test_wizard_step1_to_step2(): void
    {
        $vfu = VfuRegistration::factory()->bonificato()->create(['targa' => 'SM102S1']);
        $operatore = User::where('email', 'operatore@example.com')->firstOrFail();

        Livewire::actingAs($operatore)
            ->test(SmontaggioWizard::class, ['vfu' => $vfu])
            ->assertSet('step', 1)
            ->call('goToStep', 2)
            ->assertSet('step', 2);
    }

    public function test_wizard_aggiunge_ricambio(): void
    {
        $vfu = VfuRegistration::factory()->bonificato()->create(['targa' => 'SM102RIC']);
        $operatore = User::where('email', 'operatore@example.com')->firstOrFail();

        Livewire::actingAs($operatore)
            ->test(SmontaggioWizard::class, ['vfu' => $vfu])
            ->call('goToStep', 2)
            ->set('nuovaDescrizione', 'Paraurti posteriore')
            ->set('nuovaCondizione', 'buono')
            ->set('nuovoValore', '45.00')
            ->call('aggiungiRicambio')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('smontaggio_ricambi', [
            'descrizione' => 'Paraurti posteriore',
            'condizione' => 'buono',
        ]);
    }

    public function test_wizard_completa_smontaggio(): void
    {
        $vfu = VfuRegistration::factory()->bonificato()->create(['targa' => 'SM102COM']);
        $operatore = User::where('email', 'operatore@example.com')->firstOrFail();

        Livewire::actingAs($operatore)
            ->test(SmontaggioWizard::class, ['vfu' => $vfu])
            ->call('goToStep', 3)
            ->set('note', 'Smontaggio completato senza problemi')
            ->call('completa')
            ->assertSet('success', true);

        $this->assertSame(VfuStato::Smontato, $vfu->fresh()->stato);
    }

    public function test_wizard_validazione_ricambio_descrizione_obbligatoria(): void
    {
        $vfu = VfuRegistration::factory()->bonificato()->create(['targa' => 'SM102VAL']);
        $operatore = User::where('email', 'operatore@example.com')->firstOrFail();

        Livewire::actingAs($operatore)
            ->test(SmontaggioWizard::class, ['vfu' => $vfu])
            ->call('goToStep', 2)
            ->set('nuovaDescrizione', '')
            ->call('aggiungiRicambio')
            ->assertHasErrors(['nuovaDescrizione']);
    }

    public function test_vfu_stato_enum_includes_smontaggio_cases(): void
    {
        $this->assertSame('in_smontaggio', VfuStato::InSmontaggio->value);
        $this->assertSame('smontato', VfuStato::Smontato->value);
        $this->assertSame('In smontaggio', VfuStato::InSmontaggio->label());
        $this->assertSame('Smontato', VfuStato::Smontato->label());
    }

    public function test_ricambio_foto_non_accessibile_senza_auth(): void
    {
        Storage::fake('local');

        $operatore = User::where('email', 'operatore@example.com')->firstOrFail();
        $vfu = VfuRegistration::factory()->bonificato()->create(['targa' => 'SM102PH']);
        $session = app(SmontaggioService::class)->avvia($vfu, $operatore);

        $ricambio = app(SmontaggioService::class)->aggiungiRicambio($session, [
            'descrizione' => 'Paraurti',
            'condizione' => 'buono',
            'foto' => UploadedFile::fake()->image('paraurti.jpg'),
        ]);

        $this->get(route('operatore.ricambi.foto', $ricambio))
            ->assertRedirect(route('login'));
    }

    public function test_ricambio_foto_accessibile_ad_operatore_autenticato(): void
    {
        Storage::fake('local');

        $operatore = User::where('email', 'operatore@example.com')->firstOrFail();
        $vfu = VfuRegistration::factory()->bonificato()->create(['targa' => 'SM102PF']);
        $session = app(SmontaggioService::class)->avvia($vfu, $operatore);

        $ricambio = app(SmontaggioService::class)->aggiungiRicambio($session, [
            'descrizione' => 'Cofano',
            'condizione' => 'buono',
            'foto' => UploadedFile::fake()->image('cofano.jpg'),
        ]);

        $this->actingAs($operatore)
            ->get(route('operatore.ricambi.foto', $ricambio))
            ->assertOk();
    }

    public function test_ricambio_foto_non_accessibile_via_public_disk(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $operatore = User::where('email', 'operatore@example.com')->firstOrFail();
        $vfu = VfuRegistration::factory()->bonificato()->create(['targa' => 'SM102PU']);
        $session = app(SmontaggioService::class)->avvia($vfu, $operatore);

        $ricambio = app(SmontaggioService::class)->aggiungiRicambio($session, [
            'descrizione' => 'Sedile',
            'condizione' => 'accettabile',
            'foto' => UploadedFile::fake()->image('sedile.jpg'),
        ]);

        Storage::disk('public')->assertMissing($ricambio->foto_path);
    }

    public function test_pubblica_in_vetrina_crea_prodotto_bozza(): void
    {
        $operatore = User::where('email', 'operatore@example.com')->firstOrFail();
        $vfu = VfuRegistration::factory()->bonificato()->create(['targa' => 'SM102VT']);
        $session = app(SmontaggioService::class)->avvia($vfu, $operatore);

        $ricambio = app(SmontaggioService::class)->aggiungiRicambio($session, [
            'descrizione' => 'Alternatore',
            'numero_parte' => 'ALT-123',
            'condizione' => 'buono',
            'valore_stimato' => 120.00,
        ]);

        $prodotto = app(\App\Domain\Vfu\SmontaggioVetrinaService::class)->pubblicaInVetrina($ricambio);

        $this->assertSame('Alternatore', $prodotto->nome);
        $this->assertEquals(120.00, (float) $prodotto->prezzo);
        $this->assertFalse($prodotto->attivo);
        $this->assertSame($vfu->id, $prodotto->vfu_registration_id);
        $this->assertStringContainsString('Condizione: Buono', $prodotto->descrizione);
    }

    public function test_wizard_pubblica_selezionati_in_vetrina(): void
    {
        $vfu = VfuRegistration::factory()->bonificato()->create(['targa' => 'SM102WB']);
        $operatore = User::where('email', 'operatore@example.com')->firstOrFail();

        $component = Livewire::actingAs($operatore)
            ->test(SmontaggioWizard::class, ['vfu' => $vfu])
            ->call('goToStep', 2)
            ->set('nuovaDescrizione', 'Radiatore')
            ->set('nuovaCondizione', 'buono')
            ->set('nuovoValore', '85.00')
            ->call('aggiungiRicambio')
            ->call('goToStep', 3);

        $ricambioId = SmontaggioRicambio::query()->where('descrizione', 'Radiatore')->value('id');

        $component
            ->set('pubblicaInVetrina.'.$ricambioId, true)
            ->call('pubblicaSelezionatiInVetrina')
            ->assertHasNoErrors()
            ->assertSee('Vedi prodotto vetrina');

        $this->assertDatabaseHas('ecommerce_prodotti', [
            'nome'   => 'Radiatore',
            'attivo' => false,
        ]);
    }
}
