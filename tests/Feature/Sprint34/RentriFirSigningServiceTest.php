<?php

namespace Tests\Feature\Sprint34;

use App\Domain\Magazzino\MagazzinoSvuotamentoService;
use App\Enums\FirStato;
use App\Models\Anagrafica;
use App\Models\Authorization;
use App\Models\CodiceCer;
use App\Models\Fir;
use App\Models\MagazzinoRifiuto;
use App\Models\Trasporto;
use App\Models\User;
use App\Services\Rentri\Contracts\RentriFirSigningServiceInterface;
use App\Services\Rentri\Contracts\RentriFirServiceInterface;
use App\Services\Rentri\Exceptions\RentriXfirValidationException;
use App\Services\Rentri\RentriXfirCoseSigner;
use App\Services\Rentri\RentriXfirPayloadBuilder;
use App\Services\Rentri\RentriXfirValidator;
use Illuminate\Support\Facades\Config;
use Tests\Support\SeedsRentriCertificate;
use Tests\TestCase;

class RentriFirSigningServiceTest extends TestCase
{
    use SeedsRentriCertificate;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.rentri.firma_stub', true);
        $this->seedRentriFirmaCertificate(['num_iscr_sito' => 'SITE-001', 'onboarding_step_completed' => 3]);
    }

    public function test_sign_creates_cose_sign1_stub_payload(): void
    {
        $fir = $this->seedVidimatoFir();

        $signed = app(RentriFirSigningServiceInterface::class)->sign($fir);

        $this->assertSame(FirStato::Firmato, $signed->stato);
        $this->assertNotNull($signed->firmato_at);
        $this->assertNotNull($signed->xfir_payload);
        $this->assertNotNull($signed->xfir_signed_payload);

        /** @var array<string, mixed> $cose */
        $cose = json_decode($signed->xfir_signed_payload, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('COSE_Sign1', $cose['typ']);
        $this->assertSame('stub', $cose['api_mode']);
        $this->assertStringStartsWith('stub:', (string) $cose['signature']);
    }

    public function test_cose_signer_produces_deterministic_stub_with_same_password(): void
    {
        $fir = $this->seedVidimatoFir();
        $payload = app(RentriXfirPayloadBuilder::class)->build($fir);
        app(RentriXfirValidator::class)->validate($payload);

        $signer = app(RentriXfirCoseSigner::class);
        $settings = \App\Models\RentriSetting::instance();

        $a = $signer->sign($payload, $settings);
        $b = $signer->sign($payload, $settings);

        $this->assertSame($a['signature'], $b['signature']);
    }

    public function test_rejects_double_sign(): void
    {
        $fir = $this->seedVidimatoFir();
        $service = app(RentriFirSigningServiceInterface::class);
        $service->sign($fir);

        $this->expectException(\RuntimeException::class);
        $service->sign($fir->fresh());
    }

    public function test_validator_rejects_missing_trasporto_fields(): void
    {
        $this->expectException(RentriXfirValidationException::class);

        app(RentriXfirValidator::class)->validate([
            'versione' => '1.0',
            'numero_fir' => 'FIR-001',
            'codice_blocco' => 'BLK',
            'progressivo' => 1,
            'identificativo' => 'RSSMRA80A01H501Z',
            'num_iscr_sito' => 'SITE',
            'data_vidimazione' => '2026-06-01',
            'trasporto' => ['quantita_kg' => 10],
        ]);
    }

    private function seedVidimatoFir(): Fir
    {
        \App\Models\FirBlocco::create([
            'codice_blocco' => 'BLK-A',
            'num_iscr_sito' => 'SITE-001',
            'progressivo_ultimo' => 0,
        ]);

        return app(RentriFirServiceInterface::class)->vidima($this->seedTrasporto());
    }

    private function seedTrasporto(): Trasporto
    {
        $cer = CodiceCer::factory()->create(['codice' => '16 01 04']);
        MagazzinoRifiuto::create(['codice_cer_id' => $cer->id, 'quantita_attuale_kg' => 50]);
        $impianto = Anagrafica::factory()->create(['tipo' => 'impianto', 'email' => 'imp@test.local']);
        $trasportatore = Anagrafica::factory()->create(['tipo' => 'trasportatore', 'gestisce_trasporti' => true]);
        Authorization::factory()->create(['anagrafica_id' => $trasportatore->id, 'scade_il' => now()->addYear()]);

        app(MagazzinoSvuotamentoService::class)->richiediSvuotamento(
            $cer->id, $impianto->id, $trasportatore->id, false, 20, null,
            User::factory()->create()->id,
        );

        return Trasporto::firstOrFail();
    }
}
