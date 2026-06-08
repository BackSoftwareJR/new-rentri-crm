<?php

namespace Tests\Feature\Sprint100;

use App\Domain\Bonifica\BonificaService;
use App\Domain\Magazzino\MagazzinoService;
use App\Domain\Trasporti\TrasportoService;
use App\Domain\Vfu\VfuAccettazioneService;
use App\Enums\FirStato;
use App\Enums\RegistroMovimentoTipo;
use App\Enums\TrasportoStato;
use App\Enums\VfuStato;
use App\Enums\VfuTipoDocumento;
use App\Models\Anagrafica;
use App\Models\CodiceCer;
use App\Models\FirBlocco;
use App\Models\MagazzinoRifiuto;
use App\Models\RegistroMovimento;
use App\Models\RentriTransmissione;
use App\Models\Trasporto;
use App\Models\User;
use App\Models\VfuDocument;
use App\Models\VfuRegistration;
use App\Services\Rentri\Contracts\RentriApiClientInterface;
use App\Services\Rentri\RentriFirService;
use App\Services\Rentri\RentriRegistryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Mockery\MockInterface;
use Spatie\Permission\Models\Role;
use Tests\Support\SeedsRentriCertificate;
use Tests\TestCase;

/**
 * Full end-to-end lifecycle test for the autodemolitori workflow.
 *
 * Lifecycle under test:
 *   VFU accepted → bonifica completata → magazzino scaricato →
 *   trasporto creato → FIR vidimato → registro trasmesso
 *
 * Uses RefreshDatabase + stub RENTRI mode.
 * The RENTRI API client is fully mocked — no HTTP calls are made.
 */
class DemoWalkthroughE2ETest extends TestCase
{
    use RefreshDatabase;
    use SeedsRentriCertificate;

    private User $segreteriaUser;
    private CodiceCer $cerVfu;
    private CodiceCer $cerPericoloso;
    private CodiceCer $cerAltro;
    private FirBlocco $firBlocco;
    private Anagrafica $destinatario;

    protected function setUp(): void
    {
        parent::setUp();

        // Stub RENTRI mode — no live API calls
        Config::set('services.rentri.api_stub', true);
        Config::set('services.rentri.firma_stub', true);

        // Seed RentriSetting with valid cert data and onboarding completed
        $this->seedRentriCertificate([
            'cf_operatore'              => 'TSTCMP80A01H501Z',
            'num_iscr_sito'             => 'SITE-E2E-TEST',
            'onboarding_step_completed' => 3,
            'last_health_status'        => ['status' => 'ok'],
            'last_health_check_at'      => now()->subMinutes(5),
        ]);

        // Destinatario anagrafica required for Trasporto
        $this->destinatario = Anagrafica::create([
            'tipo'            => 'impianto',
            'ragione_sociale' => 'Impianto E2E Test S.r.l.',
            'codice_fiscale'  => 'E2ETIMPIANTO001',
            'piva'            => '11111111111',
            'email'           => 'impianto.e2e@test.local',
        ]);

        // CER codes — VFU acceptance CER is NOT pericoloso (it's the whole car chassis)
        $this->cerVfu = CodiceCer::create([
            'codice'      => '16.01.04*',
            'descrizione' => 'Veicoli fuori uso',
            'categoria'   => 'altro',
            'um'          => 'kg',
            'attivo'      => true,
        ]);

        $this->cerPericoloso = CodiceCer::create([
            'codice'      => '16.01.07*',
            'descrizione' => 'Filtri olio',
            'categoria'   => 'pericoloso',
            'um'          => 'kg',
            'attivo'      => true,
        ]);

        $this->cerAltro = CodiceCer::create([
            'codice'      => '16.01.99',
            'descrizione' => 'Rifiuti non specificati altrimenti',
            'categoria'   => 'altro',
            'um'          => 'kg',
            'attivo'      => true,
        ]);

        // Magazzino initial stock entries
        MagazzinoRifiuto::create(['codice_cer_id' => $this->cerVfu->id, 'quantita_attuale_kg' => 0]);
        MagazzinoRifiuto::create(['codice_cer_id' => $this->cerPericoloso->id, 'quantita_attuale_kg' => 0]);
        MagazzinoRifiuto::create(['codice_cer_id' => $this->cerAltro->id, 'quantita_attuale_kg' => 0]);

        // FIR blocco for vidima
        $this->firBlocco = FirBlocco::create([
            'codice_blocco'      => 'BLOCCO-E2E-01',
            'num_iscr_sito'      => 'SITE-E2E-TEST',
            'progressivo_ultimo' => 0,
        ]);

        // Segreteria user
        Role::findOrCreate('segreteria');
        $this->segreteriaUser = User::factory()->create([
            'name'  => 'Segreteria E2E',
            'email' => 'segreteria.e2e@test.local',
        ]);
        $this->segreteriaUser->assignRole('segreteria');

        $this->actingAs($this->segreteriaUser);
    }

    /**
     * Full lifecycle:
     * VFU accepted → bonifica → trasporto → FIR vidimato → scarico → registro trasmesso
     */
    public function test_complete_autodemolitori_lifecycle(): void
    {
        // ── Step 1: VFU Accettazione ─────────────────────────────────────────

        $vfu = $this->createVfuWithDocuments();

        $accettazioneService = app(VfuAccettazioneService::class);
        $vfu = $accettazioneService->completeAccettazione($vfu);

        $this->assertSame(VfuStato::Accettato, $vfu->stato);
        $this->assertNotNull($vfu->data_accettazione);

        // Magazzino carico from VFU acceptance
        $this->assertDatabaseHas('registro_movimenti', [
            'tipo'          => RegistroMovimentoTipo::Carico->value,
            'codice_cer_id' => $this->cerVfu->id,
            'source_type'   => VfuRegistration::class,
            'source_id'     => $vfu->id,
        ]);

        $giacenzaAfterAccettazione = (float) MagazzinoRifiuto::where('codice_cer_id', $this->cerVfu->id)
            ->value('quantita_attuale_kg');

        $this->assertGreaterThan(0, $giacenzaAfterAccettazione, 'Giacenza VFU CER deve essere > 0 dopo accettazione');

        // ── Step 2: Bonifica ─────────────────────────────────────────────────

        $bonificaService = app(BonificaService::class);
        $bonifica = $bonificaService->startBonifica($vfu);

        $this->assertSame(VfuStato::InBonifica, $vfu->fresh()->stato);

        // Register hazardous and non-hazardous material movements
        $bonifica = $bonificaService->saveMovimenti($bonifica, [
            ['codice_cer_id' => $this->cerPericoloso->id, 'quantita' => 5.0, 'um' => 'kg', 'peso_kg' => 5.0],
            ['codice_cer_id' => $this->cerAltro->id, 'quantita' => 15.0, 'um' => 'kg', 'peso_kg' => 15.0],
        ]);

        // Complete pericolosi phase
        $bonifica = $bonificaService->saveChecklistPericolosi($bonifica, [
            'dpi'            => true,
            'contenitori'    => true,
            'area_ventilata' => true,
        ]);
        $bonifica = $bonificaService->completePericolosi($bonifica);

        $this->assertNotNull($vfu->fresh()->bonifica_pericolosi_completata_at);

        // Complete full bonifica
        $bonifica = $bonificaService->completeBonifica($bonifica->fresh());

        $vfu->refresh();
        $this->assertSame(VfuStato::Bonificato, $vfu->stato);
        $this->assertSame('completata', $bonifica->stato);

        // Magazzino carico for bonifica pericolosi and altri
        $this->assertDatabaseHas('registro_movimenti', [
            'tipo'          => RegistroMovimentoTipo::Carico->value,
            'codice_cer_id' => $this->cerPericoloso->id,
        ]);

        $this->assertDatabaseHas('registro_movimenti', [
            'tipo'          => RegistroMovimentoTipo::Carico->value,
            'codice_cer_id' => $this->cerAltro->id,
        ]);

        $giacenzaAltro = (float) MagazzinoRifiuto::where('codice_cer_id', $this->cerAltro->id)
            ->value('quantita_attuale_kg');
        $this->assertGreaterThan(0, $giacenzaAltro, 'Giacenza cerAltro deve essere > 0 dopo bonifica');

        // ── Step 3: Trasporto ────────────────────────────────────────────────

        $trasporto = Trasporto::create([
            'codice_cer_id'              => $this->cerAltro->id,
            'anagrafica_destinatario_id' => $this->destinatario->id,
            'quantita_kg'                => 15.0,
            'stato'                      => TrasportoStato::InPreparazione,
            'note'                       => 'E2E test trasporto',
        ]);

        $this->assertSame(TrasportoStato::InPreparazione, $trasporto->stato);

        // Move to transito before FIR vidima
        $trasportoService = app(TrasportoService::class);
        $trasporto = $trasportoService->avviaTransito($trasporto);

        $this->assertSame(TrasportoStato::InTransito, $trasporto->stato);

        // ── Step 4: FIR Vidimazione ──────────────────────────────────────────

        $this->mockRentriApiClientForFirVidima();

        $firService = app(RentriFirService::class);
        $fir = $firService->vidima($trasporto);

        $this->assertSame(FirStato::Vidimato, $fir->stato);
        $this->assertNotNull($fir->vidimato_at);
        $this->assertNotNull($fir->numero_fir);
        $this->assertNotNull($fir->qr_payload);
        $this->assertSame($trasporto->id, $fir->trasporto_id);

        // Verify trasporto has fir_id set
        $trasporto->refresh();
        $this->assertSame($fir->id, $trasporto->fir_id);

        // ── Step 5: Completamento Trasporto (Scarico Magazzino) ──────────────

        $giacenzaBeforeScarico = (float) MagazzinoRifiuto::where('codice_cer_id', $this->cerAltro->id)
            ->value('quantita_attuale_kg');

        $trasporto = $trasportoService->completa($trasporto->fresh());

        $this->assertSame(TrasportoStato::Completato, $trasporto->stato);

        $giacenzaAfterScarico = (float) MagazzinoRifiuto::where('codice_cer_id', $this->cerAltro->id)
            ->value('quantita_attuale_kg');

        $this->assertLessThan(
            $giacenzaBeforeScarico,
            $giacenzaAfterScarico,
            'Giacenza deve diminuire dopo completamento trasporto',
        );

        // ── Step 6: Registro Movimenti ───────────────────────────────────────

        $caricoVfu = RegistroMovimento::query()
            ->where('tipo', RegistroMovimentoTipo::Carico)
            ->where('source_type', VfuRegistration::class)
            ->where('source_id', $vfu->id)
            ->first();

        $this->assertNotNull($caricoVfu, 'Deve esistere un movimento di carico per il VFU accettato');

        $scaricoTrasporto = RegistroMovimento::query()
            ->where('tipo', RegistroMovimentoTipo::Scarico)
            ->where('source_type', Trasporto::class)
            ->where('source_id', $trasporto->id)
            ->first();

        $this->assertNotNull($scaricoTrasporto, 'Deve esistere un movimento di scarico per il trasporto completato');
        $this->assertSame($this->cerAltro->id, $scaricoTrasporto->codice_cer_id);
        $this->assertEqualsWithDelta(15.0, (float) $scaricoTrasporto->peso_kg, 0.001);

        // ── Step 7: RENTRI Registry Transmission ────────────────────────────

        $this->mockRentriApiClientForRegistroTrasmissione();

        $registryService = app(RentriRegistryService::class);

        $periodoDa = Carbon::today()->subMonth()->startOfMonth();
        $periodoA = Carbon::today()->endOfMonth();

        $payload = $registryService->buildTransmissionPayload($periodoDa, $periodoA);

        $this->assertGreaterThan(0, $payload->metadata['count'], 'Deve esserci almeno un movimento da trasmettere');

        $transmissione = $registryService->transmit($payload);

        $this->assertInstanceOf(RentriTransmissione::class, $transmissione);
        $this->assertContains($transmissione->esito, ['accettato', 'completata', 'ok']);

        $this->assertDatabaseHas('rentri_transmissioni', [
            'id'   => $transmissione->id,
        ]);

        // Verify movimenti are locked after successful transmission
        $scaricoTrasporto->refresh();
        $this->assertTrue((bool) $scaricoTrasporto->rentri_trasmesso, 'Movimento scarico deve essere bloccato post-trasmissione');
        $this->assertNotNull($scaricoTrasporto->locked_at);
        $this->assertSame($transmissione->id, $scaricoTrasporto->rentri_transmission_id);
    }

    /**
     * Verify individual steps can be tested in isolation.
     * Step 1 only: VFU accettazione.
     */
    public function test_vfu_accettazione_creates_registro_movimento_and_magazzino_carico(): void
    {
        $vfu = $this->createVfuWithDocuments(pesoKg: 1200.0);

        $service = app(VfuAccettazioneService::class);
        $vfu = $service->completeAccettazione($vfu);

        $this->assertSame(VfuStato::Accettato, $vfu->stato);
        $this->assertNotNull($vfu->data_accettazione);

        $this->assertDatabaseHas('registro_movimenti', [
            'tipo'          => RegistroMovimentoTipo::Carico->value,
            'codice_cer_id' => $this->cerVfu->id,
            'peso_kg'       => 1200.0,
            'source_type'   => VfuRegistration::class,
            'source_id'     => $vfu->id,
        ]);

        $giacenza = (float) MagazzinoRifiuto::where('codice_cer_id', $this->cerVfu->id)
            ->value('quantita_attuale_kg');

        $this->assertEqualsWithDelta(1200.0, $giacenza, 0.001);
    }

    /**
     * Verify FIR vidima with mocked client creates a FIR in stato vidimato.
     */
    public function test_fir_vidima_in_stub_mode_creates_fir_vidimato(): void
    {
        $this->mockRentriApiClientForFirVidima();

        $trasporto = Trasporto::create([
            'codice_cer_id'              => $this->cerAltro->id,
            'anagrafica_destinatario_id' => $this->destinatario->id,
            'quantita_kg'                => 10.0,
            'stato'                      => TrasportoStato::InTransito,
        ]);

        $firService = app(RentriFirService::class);
        $fir = $firService->vidima($trasporto);

        $this->assertSame(FirStato::Vidimato, $fir->stato);
        $this->assertNotNull($fir->numero_fir);
        $this->assertSame($this->firBlocco->codice_blocco, $fir->codice_blocco);
        $this->assertSame(1, $fir->progressivo);

        $this->firBlocco->refresh();
        $this->assertSame(1, $this->firBlocco->progressivo_ultimo);
    }

    /**
     * Verify RENTRI registro transmission in stub mode creates RentriTransmissione.
     */
    public function test_registro_trasmissione_in_stub_mode_creates_transmissione(): void
    {
        // Create a pending movimento
        RegistroMovimento::create([
            'tipo'             => RegistroMovimentoTipo::Carico,
            'codice_cer_id'    => $this->cerAltro->id,
            'peso_kg'          => 50.0,
            'data_movimento'   => now(),
            'source_type'      => Trasporto::class,
            'source_id'        => 999,
            'rentri_trasmesso'  => false,
        ]);

        $this->mockRentriApiClientForRegistroTrasmissione();

        $service = app(RentriRegistryService::class);
        $payload = $service->buildTransmissionPayload(
            Carbon::today()->subMonth(),
            Carbon::today()->addDay(),
        );

        $this->assertSame(1, $payload->metadata['count']);

        $transmissione = $service->transmit($payload);

        $this->assertContains($transmissione->esito, ['accettato', 'completata', 'ok']);

        $this->assertDatabaseCount('rentri_transmissioni', 1);
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function createVfuWithDocuments(float $pesoKg = 980.0): VfuRegistration
    {
        $vfu = VfuRegistration::factory()->create([
            'stato'          => VfuStato::InAccettazione,
            'peso_kg'        => $pesoKg,
            'targa'          => 'E2ETEST',
            'telaio'         => 'VFU-E2E-'.uniqid(),
            'marca'          => 'FIAT',
            'modello'        => 'Panda',
            'proprietario'   => 'Mario Rossi E2E',
            'data_consegna'  => now()->toDateString(),
        ]);

        foreach (VfuTipoDocumento::requiredForAccettazione() as $tipo) {
            VfuDocument::create([
                'vfu_registration_id' => $vfu->id,
                'tipo'                => $tipo,
                'path'                => "vfu-documents/e2e-test/{$tipo->value}.pdf",
                'original_name'       => "documento-{$tipo->value}.pdf",
            ]);
        }

        return $vfu;
    }

    /**
     * Mock the API client for FIR vidima: submitFirVidima + waitFirVidimaResult.
     * Bound before each test that calls RentriFirService::vidima().
     */
    private function mockRentriApiClientForFirVidima(): void
    {
        $this->mock(RentriApiClientInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('submitFirVidima')
                ->once()
                ->andReturn(['transazione_id' => 'stub-tx-fir-e2e-001']);

            $mock->shouldReceive('waitFirVidimaResult')
                ->once()
                ->with('stub-tx-fir-e2e-001')
                ->andReturn([
                    'progressivo'  => 1,
                    'numero_fir'   => 'SITE-E2E-TEST-BLOCCO-E2E-01-0001',
                    'protocollo'   => 'PROT-E2E-FIR-2026-001',
                    'qr_code'      => base64_encode('stub-qr-content-e2e'),
                    'correlation_id' => 'corr-e2e-001',
                ]);
        });
    }

    /**
     * Mock the API client for registro trasmissione.
     * Bound before each test that calls RentriRegistryService::transmit().
     */
    private function mockRentriApiClientForRegistroTrasmissione(): void
    {
        $this->mock(RentriApiClientInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('submitRegistroTrasmissione')
                ->once()
                ->andReturn(['transazione_id' => 'stub-tx-reg-e2e-001']);

            $mock->shouldReceive('waitRegistroTrasmissioneResult')
                ->once()
                ->with('stub-tx-reg-e2e-001')
                ->andReturn([
                    'esito'      => 'accettato',
                    'protocollo' => 'PROT-E2E-REG-2026-001',
                ]);
        });
    }
}
