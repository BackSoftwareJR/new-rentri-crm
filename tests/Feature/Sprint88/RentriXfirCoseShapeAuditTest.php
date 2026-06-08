<?php

namespace Tests\Feature\Sprint88;

use App\Domain\Magazzino\MagazzinoSvuotamentoService;
use App\Enums\FirStato;
use App\Models\Anagrafica;
use App\Models\Authorization;
use App\Models\CodiceCer;
use App\Models\Fir;
use App\Models\FirBlocco;
use App\Models\MagazzinoRifiuto;
use App\Models\RentriSetting;
use App\Models\Trasporto;
use App\Models\User;
use App\Services\Rentri\Contracts\RentriFirServiceInterface;
use App\Services\Rentri\Contracts\RentriFirSigningServiceInterface;
use App\Services\Rentri\Dto\RentriXfirTrasmissioneRequest;
use App\Services\Rentri\RentriXfirCoseSigner;
use App\Services\Rentri\RentriXfirCoseTransmissionMapper;
use App\Services\Rentri\RentriXfirPayloadBuilder;
use Illuminate\Support\Facades\Config;
use Tests\Support\LoadsMaseFixtures;
use Tests\Support\SeedsRentriCertificate;
use Tests\TestCase;

class RentriXfirCoseShapeAuditTest extends TestCase
{
    use LoadsMaseFixtures;
    use SeedsRentriCertificate;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.rentri.firma_stub', true);
        $this->seedRentriFirmaCertificate(['num_iscr_sito' => 'SITE-S88', 'onboarding_step_completed' => 3]);
    }

    public function test_mase_cose_fixture_defines_required_fields(): void
    {
        $contract = $this->maseFixture('xfir-cose-sign1');

        $this->assertContains('typ', $contract['required']);
        $this->assertContains('protected', $contract['required']);
        $this->assertContains('payload', $contract['required']);
        $this->assertContains('signature', $contract['required']);
        $this->assertSame(['api_mode', 'numero_fir', 'firmato_at', 'stub'], $contract['crm_excluded_keys']);
    }

    public function test_stub_signer_output_satisfies_mase_cose_contract(): void
    {
        $fir = $this->seedSignedFir();
        $signed = json_decode($fir->xfir_signed_payload, true, 512, JSON_THROW_ON_ERROR);
        $mase = RentriXfirCoseTransmissionMapper::forTransmission($signed);
        $contract = $this->maseFixture('xfir-cose-sign1');

        foreach ($contract['required'] as $field) {
            $this->assertArrayHasKey($field, $mase, "Missing COSE field: {$field}");
        }

        $this->assertSame('COSE_Sign1', $mase['typ']);
        $this->assertSame('STUB-HMAC-SHA256', $mase['alg']);
        $this->assertNotSame('', $mase['protected']);
        $this->assertNotSame('', $mase['payload']);
        $this->assertStringStartsWith('stub:', (string) $mase['signature']);
    }

    public function test_transmission_mapper_strips_crm_metadata(): void
    {
        $signed = [
            'typ'        => 'COSE_Sign1',
            'alg'        => 'STUB-HMAC-SHA256',
            'protected'  => 'abc',
            'payload'    => 'def',
            'signature'  => 'stub:xyz',
            'stub'       => true,
            'api_mode'   => 'stub',
            'numero_fir' => 'FIR-TEST',
            'firmato_at' => '2026-06-04T12:00:00+00:00',
        ];

        $mase = RentriXfirCoseTransmissionMapper::forTransmission($signed);

        foreach (RentriXfirCoseTransmissionMapper::crmMetadataKeys() as $key) {
            $this->assertArrayNotHasKey($key, $mase);
        }

        $this->assertCount(5, $mase);
    }

    public function test_transmission_request_payload_firmato_is_mase_cose_only(): void
    {
        $fir = $this->seedSignedFir();
        $signed = json_decode($fir->xfir_signed_payload, true, 512, JSON_THROW_ON_ERROR);

        $body = (new RentriXfirTrasmissioneRequest(
            $fir,
            $signed,
            RentriSetting::instance(),
        ))->body();

        foreach (RentriXfirCoseTransmissionMapper::crmMetadataKeys() as $key) {
            $this->assertArrayNotHasKey($key, $body['payload_firmato']);
        }

        $this->assertSame($body['typ'], $body['payload_firmato']['typ']);
    }

    public function test_protected_header_decodes_to_cose_sign1(): void
    {
        $fir = $this->seedSignedFir();
        $signed = json_decode($fir->xfir_signed_payload, true, 512, JSON_THROW_ON_ERROR);
        $protectedB64 = (string) $signed['protected'];
        $padded = str_pad(strtr($protectedB64, '-_', '+/'), (int) (4 * ceil(strlen($protectedB64) / 4)), '=', STR_PAD_RIGHT);
        /** @var array<string, mixed> $protected */
        $protected = json_decode(base64_decode($padded, true) ?: '{}', true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('COSE_Sign1', $protected['typ']);
        $this->assertSame('STUB-HMAC-SHA256', $protected['alg']);
    }

    public function test_cose_signer_direct_output_has_all_mase_keys(): void
    {
        $fir = $this->seedVidimatoFir();
        $payload = app(RentriXfirPayloadBuilder::class)->build($fir);
        $signed = app(RentriXfirCoseSigner::class)->sign($payload, RentriSetting::instance());

        foreach (RentriXfirCoseTransmissionMapper::maseCoseKeys() as $key) {
            $this->assertArrayHasKey($key, $signed);
        }
    }

    public function test_xfir_trasmissione_fixture_example_aligns_with_cose_contract(): void
    {
        $transmission = $this->maseFixture('xfir-trasmissione');
        $cose = $this->maseFixture('xfir-cose-sign1');
        $example = $transmission['example']['payload_firmato'];

        foreach ($cose['required'] as $field) {
            $this->assertArrayHasKey($field, $example);
        }
    }

    private function seedSignedFir(): Fir
    {
        return app(RentriFirSigningServiceInterface::class)->sign($this->seedVidimatoFir());
    }

    private function seedVidimatoFir(): Fir
    {
        FirBlocco::create([
            'codice_blocco'      => 'BLK-S88',
            'num_iscr_sito'      => 'SITE-S88',
            'progressivo_ultimo' => 0,
        ]);

        $trasporto = $this->seedTrasporto();
        app(RentriFirServiceInterface::class)->vidima($trasporto);

        return $trasporto->fresh()->firCollegato;
    }

    private function seedTrasporto(): Trasporto
    {
        $cer = CodiceCer::factory()->create();
        MagazzinoRifiuto::create(['codice_cer_id' => $cer->id, 'quantita_attuale_kg' => 50]);
        $impianto = Anagrafica::factory()->create(['tipo' => 'impianto']);
        $trasportatore = Anagrafica::factory()->create(['tipo' => 'trasportatore', 'gestisce_trasporti' => true]);
        Authorization::factory()->create(['anagrafica_id' => $trasportatore->id, 'scade_il' => now()->addYear()]);

        app(MagazzinoSvuotamentoService::class)->richiediSvuotamento(
            $cer->id, $impianto->id, $trasportatore->id, false, 20, null,
            User::factory()->create()->id,
        );

        return Trasporto::firstOrFail();
    }
}
