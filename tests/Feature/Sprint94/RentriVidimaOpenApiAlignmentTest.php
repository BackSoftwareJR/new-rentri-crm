<?php

namespace Tests\Feature\Sprint94;

use App\Domain\Magazzino\MagazzinoSvuotamentoService;
use App\Models\Anagrafica;
use App\Models\Authorization;
use App\Models\CodiceCer;
use App\Models\FirBlocco;
use App\Models\MagazzinoRifiuto;
use App\Models\RentriTransazione;
use App\Models\Trasporto;
use App\Models\User;
use App\Services\Rentri\Contracts\RentriApiClientInterface;
use App\Services\Rentri\Contracts\RentriFirServiceInterface;
use App\Services\Rentri\Dto\RentriFirVidimaRequest;
use App\Services\Rentri\RentriFirVidimaTransmissionMapper;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\Support\LoadsMaseFixtures;
use Tests\Support\SeedsRentriCertificate;
use Tests\TestCase;

class RentriVidimaOpenApiAlignmentTest extends TestCase
{
    use LoadsMaseFixtures;
    use SeedsRentriCertificate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRentriCertificate(['num_iscr_sito' => 'OP12345678901-PD00001']);
    }

    public function test_vidima_fixture_documents_crm_excluded_keys(): void
    {
        $contract = $this->maseFixture('vidima-submit');

        $this->assertSame(['trasporto_id', 'codice_blocco'], $contract['crm_excluded_keys']);
        $this->assertContains('codice_blocco', $contract['path_params']);
        $this->assertArrayHasKey('example_mase', $contract);
        $this->assertArrayHasKey('example_crm_payload', $contract);
    }

    public function test_vidima_mapper_strips_crm_fields_from_transmission_body(): void
    {
        $contract = $this->maseFixture('vidima-submit');
        $crm = $contract['example_crm_payload'];

        $body = RentriFirVidimaTransmissionMapper::forTransmission(
            (string) $crm['num_iscr_sito'],
            $crm,
        );

        $this->assertSame($contract['example_mase'], $body);
        $this->assertArrayNotHasKey('trasporto_id', $body);
        $this->assertArrayNotHasKey('codice_blocco', $body);
    }

    public function test_vidima_request_crm_audit_payload_preserves_local_metadata(): void
    {
        $request = new RentriFirVidimaRequest(
            codiceBlocco: 'BLK-MASE-01',
            numIscrSito: 'OP12345678901-PD00001',
            payload: [
                'progressivo'   => 3,
                'codice_blocco' => 'BLK-MASE-01',
                'trasporto_id'  => 99,
            ],
        );

        $this->assertSame([
            'trasporto_id'  => 99,
            'codice_blocco' => 'BLK-MASE-01',
        ], $request->crmAuditPayload());

        $this->assertSame([
            'num_iscr_sito' => 'OP12345678901-PD00001',
            'progressivo'   => 3,
        ], $request->body());
    }

    public function test_live_vidima_submit_sends_mase_body_without_trasporto_id(): void
    {
        Config::set('services.rentri.api_stub', false);

        Http::fake([
            'demoapi.rentri.gov.it/vidimazione-formulari/v1.0/BLK-S94*' => Http::response([
                'transazione_id' => 'tx-s94-vidima',
            ], 202),
        ]);

        app(RentriApiClientInterface::class)->submitFirVidima(new RentriFirVidimaRequest(
            codiceBlocco: 'BLK-S94',
            numIscrSito: 'OP12345678901-PD00001',
            payload: [
                'progressivo'   => 7,
                'codice_blocco' => 'BLK-S94',
                'trasporto_id'  => 501,
            ],
        ));

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $request->method() === 'POST'
                && str_contains($request->url(), '/vidimazione-formulari/v1.0/BLK-S94')
                && ($body['num_iscr_sito'] ?? null) === 'OP12345678901-PD00001'
                && ($body['progressivo'] ?? null) === 7
                && ! array_key_exists('trasporto_id', $body)
                && ! array_key_exists('codice_blocco', $body);
        });

        $tx = RentriTransazione::query()->where('tipo_api', 'fir')->latest('id')->first();
        $this->assertNotNull($tx);
        $this->assertSame(501, $tx->request_json['crm_audit']['trasporto_id'] ?? null);
    }

    public function test_fir_service_vidima_uses_mapper_for_api_body(): void
    {
        Config::set('services.rentri.api_stub', false);
        Config::set('services.rentri.fir_poll_max_attempts', 3);
        Config::set('services.rentri.fir_poll_interval_ms', 1);

        Http::fake([
            'demoapi.rentri.gov.it/vidimazione-formulari/v1.0/BLK-S94*' => Http::response([
                'transazione_id' => 'tx-s94-service',
            ], 202),
            'demoapi.rentri.gov.it/vidimazione-formulari/v1.0/tx-s94-service/status' => Http::response([
                'stato' => 'COMPLETATA',
            ], 200),
            'demoapi.rentri.gov.it/vidimazione-formulari/v1.0/verifica/result*' => Http::response([
                'numero_fir'  => 'OP12345678901-PD00001-BLK-S94-0001',
                'progressivo' => 1,
                'protocollo'  => 'RENTRI-S94',
                'qr_code'     => 'QR-S94-BASE45',
            ], 200),
        ]);

        FirBlocco::create([
            'codice_blocco'      => 'BLK-S94',
            'num_iscr_sito'      => 'OP12345678901-PD00001',
            'progressivo_ultimo' => 0,
        ]);

        $trasporto = $this->seedTrasportoForVidima();

        app(RentriFirServiceInterface::class)->vidima($trasporto);

        Http::assertSent(function ($request) {
            if ($request->method() !== 'POST' || ! str_contains($request->url(), '/vidimazione-formulari/v1.0/BLK-S94')) {
                return false;
            }

            $body = $request->data();

            return ! array_key_exists('trasporto_id', $body);
        });
    }

    public function test_sprint_94_audit_notes_document_m94_fix(): void
    {
        $path = base_path('docs/SPRINT-94-AUDIT-NOTES.md');
        $content = file_get_contents($path);

        $this->assertFileExists($path);
        $this->assertStringContainsString('M-94-1', $content);
        $this->assertStringContainsString('RentriFirVidimaTransmissionMapper', $content);
        $this->assertStringContainsString('trasporto_id', $content);
        $this->assertStringContainsString('crm_audit', $content);
    }

    public function test_stub_vidima_preserves_crm_context_for_result_synthesis(): void
    {
        Config::set('services.rentri.api_stub', true);

        FirBlocco::create([
            'codice_blocco'      => 'BLK-STUB-94',
            'num_iscr_sito'      => 'OP12345678901-PD00001',
            'progressivo_ultimo' => 0,
        ]);

        $trasporto = $this->seedTrasportoForVidima();

        $fir = app(RentriFirServiceInterface::class)->vidima($trasporto);

        $this->assertSame('OP12345678901-PD00001-BLK-STUB-94-0001', $fir->numero_fir);
    }

    private function seedTrasportoForVidima(): Trasporto
    {
        $cer = CodiceCer::factory()->create();
        MagazzinoRifiuto::create(['codice_cer_id' => $cer->id, 'quantita_attuale_kg' => 50]);
        $impianto = Anagrafica::factory()->create(['tipo' => 'impianto', 'email' => 'imp-s94@test.local']);
        $trasportatore = Anagrafica::factory()->create(['tipo' => 'trasportatore', 'gestisce_trasporti' => true]);
        Authorization::factory()->create(['anagrafica_id' => $trasportatore->id, 'scade_il' => now()->addYear()]);

        app(MagazzinoSvuotamentoService::class)->richiediSvuotamento(
            $cer->id,
            $impianto->id,
            $trasportatore->id,
            false,
            20,
            null,
            User::factory()->create()->id,
        );

        return Trasporto::firstOrFail();
    }
}
