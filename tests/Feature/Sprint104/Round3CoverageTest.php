<?php

namespace Tests\Feature\Sprint104;

use App\Domain\Azienda\AziendaSettingService;
use App\Domain\Fir\FirPdfGeneratorService;
use App\Domain\Rentri\RentriOnboardingService;
use App\Domain\Vfu\SmontaggioVetrinaService;
use App\Domain\Vfu\VfuAccettazioneService;
use App\Domain\Vfu\VfuTimelineService;
use App\Enums\FirStato;
use App\Enums\RegistroMovimentoTipo;
use App\Enums\VfuStato;
use App\Http\Livewire\Settings\SettingsHub;
use App\Models\Anagrafica;
use App\Models\CodiceCer;
use App\Models\Fattura;
use App\Models\Fir;
use App\Models\MagazzinoCaricoManuale;
use App\Models\RegistroMovimento;
use App\Models\SmontaggioRicambio;
use App\Models\SmontaggioSession;
use App\Models\User;
use App\Models\VfuRegistration;
use App\Services\Pec\PecMailService;
use App\Services\Rentri\Contracts\RentriApiClientInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Support\SeedsRentriCertificate;
use Tests\TestCase;

class Round3CoverageTest extends TestCase
{
    use SeedsRentriCertificate;

    // ─── 1. VFU rottama() ────────────────────────────────────────────────────

    public function test_vfu_rottama_changes_stato_to_rottamato_and_sets_rottamato_at(): void
    {
        Config::set('application_log.modules', array_merge(
            config('application_log.modules', []),
            ['vfu'],
        ));

        $vfu = VfuRegistration::factory()->create([
            'stato' => VfuStato::Smontato,
            'targa' => 'R3ROT01',
        ]);

        $updated = app(VfuAccettazioneService::class)->rottama($vfu);

        $this->assertSame(VfuStato::Rottamato, $updated->stato);
        $this->assertNotNull($updated->rottamato_at);
        $this->assertDatabaseHas('vfu_registrations', [
            'id'    => $vfu->id,
            'stato' => VfuStato::Rottamato->value,
        ]);
    }

    // ─── 2. VFU timeline InSmontaggio / Smontato ─────────────────────────────

    public function test_vfu_timeline_handles_in_smontaggio_and_smontato_without_crashing(): void
    {
        $timeline = app(VfuTimelineService::class);

        $inSmontaggio = VfuRegistration::factory()->create([
            'stato' => VfuStato::InSmontaggio,
            'targa' => 'R3TIM01',
        ]);

        $smontato = VfuRegistration::factory()->create([
            'stato' => VfuStato::Smontato,
            'targa' => 'R3TIM02',
        ]);

        $stepsInSmontaggio = $timeline->steps($inSmontaggio);
        $stepsSmontato     = $timeline->steps($smontato);

        $this->assertNotEmpty($stepsInSmontaggio);
        $this->assertNotEmpty($stepsSmontato);

        foreach ([$stepsInSmontaggio, $stepsSmontato] as $steps) {
            foreach ($steps as $step) {
                $this->assertArrayHasKey('key', $step);
                $this->assertArrayHasKey('label', $step);
                $this->assertArrayHasKey('status', $step);
            }
        }

        $this->assertContains(
            'bonificato',
            collect($stepsInSmontaggio)->pluck('key')->all(),
        );

        $currentSmontato = collect($stepsSmontato)->firstWhere('status', 'current');
        $this->assertSame('chiusura', $currentSmontato['key']);
    }

    // ─── 3. FIR PDF generator ────────────────────────────────────────────────

    public function test_fir_pdf_generator_creates_file_for_vidimated_fir(): void
    {
        Storage::fake('local');

        $fir = Fir::create([
            'numero_fir'       => 'FIR-R3-PDF',
            'codice_blocco'    => 'BLK-R3',
            'progressivo'      => 1,
            'stato'            => FirStato::Vidimato,
            'vidimato_at'      => now(),
            'peso_partenza_kg' => 120,
            'qr_payload'       => json_encode(['protocollo' => 'VID-R3-001']),
        ]);

        $path = app(FirPdfGeneratorService::class)->generate($fir);

        $this->assertNotEmpty($path);
        Storage::disk('local')->assertExists($path);
    }

    // ─── 4. AziendaSettingService get/set ────────────────────────────────────

    public function test_azienda_setting_service_get_set_works_correctly(): void
    {
        $service = app(AziendaSettingService::class);

        $service->set('ragione_sociale', 'Round3 Test SRL');
        $service->set('piva', '12345678901');

        $this->assertSame('Round3 Test SRL', $service->get('ragione_sociale'));
        $this->assertSame('12345678901', $service->get('piva'));
        $this->assertArrayHasKey('ragione_sociale', $service->all());
    }

    // ─── 5. Fattura number format tokens ─────────────────────────────────────

    public function test_fattura_number_format_token_substitution(): void
    {
        $service = app(AziendaSettingService::class);
        $date    = Carbon::parse('2026-06-08');

        $numero = $service->applicaFormatoNumerazione(
            'FT-{YEAR}-{COUNTER:3}',
            7,
            $date,
        );

        $this->assertSame('FT-2026-007', $numero);
    }

    // ─── 6. fatture:segna-scadute command ────────────────────────────────────

    public function test_fatture_segna_scadute_command_marks_correct_fatture_as_scadute(): void
    {
        $ana = Anagrafica::factory()->create();

        $scaduta = Fattura::create([
            'numero_fattura'  => 'FT-R3-SCAD',
            'tipo'            => 'fattura',
            'anagrafica_id'   => $ana->id,
            'data_emissione'  => now()->subDays(30)->toDateString(),
            'data_scadenza'   => now()->subDays(5)->toDateString(),
            'stato'           => 'emessa',
            'imponibile'      => 100,
            'iva_percentuale' => 22,
            'iva_importo'     => 22,
            'totale'          => 122,
        ]);

        $valida = Fattura::create([
            'numero_fattura'  => 'FT-R3-OK',
            'tipo'            => 'fattura',
            'anagrafica_id'   => $ana->id,
            'data_emissione'  => now()->toDateString(),
            'data_scadenza'   => now()->addDays(30)->toDateString(),
            'stato'           => 'emessa',
            'imponibile'      => 50,
            'iva_percentuale' => 22,
            'iva_importo'     => 11,
            'totale'          => 61,
        ]);

        Artisan::call('fatture:segna-scadute');

        $this->assertSame('scaduta', $scaduta->fresh()->stato);
        $this->assertSame('emessa', $valida->fresh()->stato);
        $this->assertStringContainsString('1 fatture segnate come scadute', Artisan::output());
    }

    // ─── 7. rentri:trasmetti-registro --dry-run ──────────────────────────────

    public function test_rentri_trasmetti_registro_dry_run_outputs_without_transmitting(): void
    {
        $this->seedRentriCertificate();

        $cer = CodiceCer::factory()->create();
        $movimento = RegistroMovimento::create([
            'tipo'           => RegistroMovimentoTipo::Carico,
            'codice_cer_id'  => $cer->id,
            'peso_kg'        => 25,
            'data_movimento' => now()->subDay(),
            'source_type'    => MagazzinoCaricoManuale::class,
            'source_id'      => 1,
        ]);

        $da = now()->startOfMonth()->toDateString();
        $a  = now()->toDateString();

        $exitCode = Artisan::call('rentri:trasmetti-registro', [
            '--da'      => $da,
            '--a'       => $a,
            '--dry-run' => true,
        ]);

        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('[dry-run]', $output);
        $this->assertStringContainsString('Trasmissione non eseguita', $output);
        $this->assertFalse($movimento->fresh()->rentri_trasmesso);
    }

    // ─── 8. Settings Hub RBAC ────────────────────────────────────────────────

    public function test_settings_hub_renders_for_admin(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        Livewire::actingAs($admin)
            ->test(SettingsHub::class)
            ->assertOk()
            ->assertSee('Dati azienda');
    }

    public function test_settings_hub_forbidden_for_operatore(): void
    {
        $operatore = User::where('email', 'operatore@example.com')->firstOrFail();

        $this->actingAs($operatore)
            ->get('/segreteria/impostazioni')
            ->assertForbidden();
    }

    // ─── 9. SmontaggioVetrinaService ─────────────────────────────────────────

    public function test_smontaggio_vetrina_service_creates_ecommerce_prodotto_from_ricambio(): void
    {
        $vfu = VfuRegistration::factory()->bonificato()->create(['targa' => 'R3VET01']);
        $session = SmontaggioSession::create([
            'vfu_registration_id' => $vfu->id,
            'operatore_id'        => User::where('email', 'operatore@example.com')->firstOrFail()->id,
            'stato'               => 'in_corso',
        ]);

        $ricambio = SmontaggioRicambio::create([
            'smontaggio_session_id' => $session->id,
            'descrizione'           => 'Alternatore Bosch',
            'numero_parte'          => 'ALT-12345',
            'condizione'            => 'buono',
            'valore_stimato'        => 45.00,
        ]);

        $prodotto = app(SmontaggioVetrinaService::class)->pubblicaInVetrina($ricambio, [
            'attivo' => true,
        ]);

        $this->assertDatabaseHas('ecommerce_prodotti', [
            'id'                  => $prodotto->id,
            'nome'                => 'Alternatore Bosch',
            'vfu_registration_id' => $vfu->id,
        ]);
        $this->assertStringContainsString('SMR-', $prodotto->codice);
    }

    // ─── 10. PecMailService stub mode ────────────────────────────────────────

    public function test_pec_mail_service_stub_mode_logs_but_does_not_send(): void
    {
        Config::set('pec.enabled', false);
        Mail::fake();

        Log::shouldReceive('channel')
            ->once()
            ->with(config('notifications.log_channel', 'notifications'))
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->with('pec.stub', \Mockery::on(function (array $context): bool {
                return ($context['to'] ?? null) === 'cliente@pec.it'
                    && ($context['mode'] ?? null) === 'stub';
            }));

        $result = app(PecMailService::class)->send(
            'cliente@pec.it',
            'Test PEC Round 3',
            '<p>Corpo messaggio</p>',
        );

        $this->assertTrue($result);
        Mail::assertNothingSent();
    }

    // ─── 11. Anagrafica RENTRI verification ──────────────────────────────────

    public function test_anagrafica_rentri_verification_saves_result_correctly(): void
    {
        $this->seedRentriCertificate();
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $anagrafica = Anagrafica::factory()->create([
            'tipo'           => 'trasportatore',
            'codice_fiscale' => 'TSTCMP80A01H501Z',
            'piva'           => '12345678901',
        ]);

        Livewire::actingAs($user)
            ->test(\App\Http\Livewire\Segreteria\Anagrafiche\AnagraficaShow::class, ['anagrafica' => $anagrafica])
            ->call('verificaRentri')
            ->assertHasNoErrors();

        $anagrafica->refresh();

        $this->assertNotNull($anagrafica->rentri_verificato_at);
        $this->assertSame('iscritto', $anagrafica->rentri_verificato_esito);
        $this->assertNotNull($anagrafica->rentri_iscrizione_numero);
    }

    // ─── 12. RENTRI onboarding auto-sync serbatoi ────────────────────────────

    public function test_rentri_onboarding_auto_sync_creates_serbatoi_after_cer_sync(): void
    {
        $this->seedRentriCertificate();
        $cer = CodiceCer::factory()->create(['attivo' => true]);

        app(RentriOnboardingService::class)->testConnection(
            app(RentriApiClientInterface::class),
        );

        $this->assertDatabaseHas('magazzino_rifiuti', [
            'codice_cer_id' => $cer->id,
        ]);
    }
}
