<?php

namespace Tests\Feature\Sprint84;

use App\Enums\FirStato;
use App\Enums\RegistroMovimentoTipo;
use App\Models\CodiceCer;
use App\Models\Fir;
use App\Models\MagazzinoCaricoManuale;
use App\Models\RegistroMovimento;
use App\Models\RentriSetting;
use App\Services\Rentri\Contracts\RentriRegistryServiceInterface;
use App\Services\Rentri\Dto\RentriFirVidimaRequest;
use App\Services\Rentri\Dto\RentriRegistroTrasmissioneRequest;
use App\Services\Rentri\Dto\RentriXfirTrasmissioneRequest;
use App\Services\Rentri\Dto\TransmissionPayload;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\LoadsMaseFixtures;
use Tests\Support\SeedsRentriCertificate;
use Tests\TestCase;

class RentriMasePayloadContractTest extends TestCase
{
    use LoadsMaseFixtures;
    use SeedsRentriCertificate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRentriCertificate(['num_iscr_sito' => 'OP12345678901-PD00001']);
    }

    public function test_vidima_request_body_matches_mase_contract(): void
    {
        $contract = $this->maseFixture('vidima-submit');
        $example = $contract['example_crm_payload'];

        $request = new RentriFirVidimaRequest(
            codiceBlocco: (string) $example['codice_blocco'],
            numIscrSito: (string) $example['num_iscr_sito'],
            payload: [
                'progressivo'   => $example['progressivo'],
                'codice_blocco' => $example['codice_blocco'],
                'trasporto_id'  => $example['trasporto_id'],
            ],
        );

        $body = $request->body();

        $this->assertBodyMatchesContract($body, $contract);
        $this->assertSame($example['num_iscr_sito'], $body['num_iscr_sito']);
        $this->assertSame($example['progressivo'], $body['progressivo']);
        $this->assertArrayNotHasKey('trasporto_id', $body);
        $this->assertArrayNotHasKey('codice_blocco', $body);
        $this->assertSame($example['trasporto_id'], $request->crmAuditPayload()['trasporto_id']);
    }

    public function test_registro_request_body_matches_mase_contract_with_carico_and_scarico(): void
    {
        $contract = $this->maseFixture('registro-trasmissione');
        $settings = RentriSetting::instance();

        $cer = CodiceCer::factory()->create(['codice' => '16 01 04']);

        RegistroMovimento::create([
            'tipo'           => RegistroMovimentoTipo::Carico,
            'codice_cer_id'  => $cer->id,
            'peso_kg'        => 120.5,
            'data_movimento' => Carbon::parse('2026-06-02'),
            'source_type'    => MagazzinoCaricoManuale::class,
            'source_id'      => 101,
        ]);

        RegistroMovimento::create([
            'tipo'           => RegistroMovimentoTipo::Scarico,
            'codice_cer_id'  => $cer->id,
            'peso_kg'        => 80,
            'data_movimento' => Carbon::parse('2026-06-15'),
            'source_type'    => MagazzinoCaricoManuale::class,
            'source_id'      => 102,
        ]);

        $payload = app(RentriRegistryServiceInterface::class)->buildTransmissionPayload(
            Carbon::parse('2026-06-01'),
            Carbon::parse('2026-06-30'),
        );

        $body = RentriRegistroTrasmissioneRequest::fromPayload($payload, $settings)->body();

        $this->assertBodyMatchesContract($body, $contract);
        $this->assertCount(2, $body['movimenti']);
        $this->assertSame('CARICO', $body['movimenti'][0]['tipo_movimento']);
        $this->assertSame('SCARICO', $body['movimenti'][1]['tipo_movimento']);
    }

    public function test_xfir_request_body_matches_mase_contract(): void
    {
        $contract = $this->maseFixture('xfir-trasmissione');
        $example = $contract['example'];
        $settings = RentriSetting::instance();

        $fir = Fir::create([
            'numero_fir'       => $example['numero_fir'],
            'codice_blocco'    => $example['codice_blocco'],
            'progressivo'      => $example['progressivo'],
            'stato'            => FirStato::Firmato,
            'firmato_at'       => now(),
            'peso_partenza_kg' => 10,
        ]);

        $body = (new RentriXfirTrasmissioneRequest(
            $fir,
            $example['payload_firmato'],
            $settings,
        ))->body();

        $this->assertBodyMatchesContract($body, $contract);
        $this->assertSame('COSE_Sign1', $body['typ']);
        $this->assertSame($settings->cf_operatore, $body['identificativo']);
    }

    #[DataProvider('registroMovimentoTipoProvider')]
    public function test_registro_tipo_movimento_maps_to_mase_enum(RegistroMovimentoTipo $tipo, string $expected): void
    {
        $cer = CodiceCer::factory()->create(['codice' => '16 01 04']);

        RegistroMovimento::create([
            'tipo'           => $tipo,
            'codice_cer_id'  => $cer->id,
            'peso_kg'        => 10,
            'data_movimento' => now()->subDay(),
            'source_type'    => MagazzinoCaricoManuale::class,
            'source_id'      => 1,
        ]);

        $payload = app(RentriRegistryServiceInterface::class)->buildTransmissionPayload(
            Carbon::now()->startOfMonth(),
            Carbon::now(),
        );

        $body = RentriRegistroTrasmissioneRequest::fromPayload($payload, RentriSetting::instance())->body();
        $enum = $this->maseFixture('registro-trasmissione')['movimento']['tipo_movimento_enum'];

        $this->assertSame($expected, $body['movimenti'][0]['tipo_movimento']);
        $this->assertContains($body['movimenti'][0]['tipo_movimento'], $enum);
    }

    /**
     * @return array<string, array{0: RegistroMovimentoTipo, 1: string}>
     */
    public static function registroMovimentoTipoProvider(): array
    {
        return [
            'carico'  => [RegistroMovimentoTipo::Carico, 'CARICO'],
            'scarico' => [RegistroMovimentoTipo::Scarico, 'SCARICO'],
        ];
    }

    public function test_registro_movimento_field_types_match_mase_contract(): void
    {
        $contract = $this->maseFixture('registro-trasmissione');
        $movimentoContract = $contract['movimento'];
        $exampleMovimento = $contract['example']['movimenti'][0];

        $payload = new TransmissionPayload(
            periodoDa: Carbon::parse('2026-06-01'),
            periodoA: Carbon::parse('2026-06-30'),
            payloadHash: 'fixture-hash',
            movimenti: [[
                'id'         => 101,
                'tipo'       => 'carico',
                'codice_cer' => $exampleMovimento['codice_cer'],
                'peso_kg'    => $exampleMovimento['quantita_kg'],
                'data'       => Carbon::parse($exampleMovimento['data_movimento'])->toIso8601String(),
            ]],
            metadata: ['count' => 1],
        );

        $movimento = RentriRegistroTrasmissioneRequest::fromPayload($payload, RentriSetting::instance())
            ->body()['movimenti'][0];

        foreach ($movimentoContract['required'] as $field) {
            $this->assertArrayHasKey($field, $movimento);
        }

        $this->assertIsString($movimento['codice_cer']);
        $this->assertIsString($movimento['tipo_movimento']);
        $this->assertIsFloat($movimento['quantita_kg']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $movimento['data_movimento']);
    }

    public function test_fixture_examples_document_ministerial_endpoints(): void
    {
        $this->assertStringContainsString('vidimazione-formulari', $this->maseFixture('vidima-submit')['endpoint']);
        $this->assertStringContainsString('xfir/trasmissione', $this->maseFixture('xfir-trasmissione')['endpoint']);
        $this->assertStringContainsString('registro/v1.0/trasmissione', $this->maseFixture('registro-trasmissione')['endpoint']);
    }

    /**
     * @param  array<string, mixed>  $body
     * @param  array<string, mixed>  $contract
     */
    private function assertBodyMatchesContract(array $body, array $contract): void
    {
        foreach ($contract['required'] as $field) {
            $this->assertArrayHasKey($field, $body, "Missing required MASE field: {$field}");
            $this->assertNotNull($body[$field], "Required MASE field null: {$field}");
        }

        foreach ($contract['properties'] as $field => $type) {
            if (! array_key_exists($field, $body)) {
                continue;
            }

            $this->assertMaseFieldType($body[$field], $type, $field);
        }

        if (isset($contract['movimento'], $body['movimenti']) && is_array($body['movimenti'])) {
            foreach ($body['movimenti'] as $index => $movimento) {
                $this->assertMovimentoMatchesContract($movimento, $contract['movimento'], $index);
            }
        }

        if (isset($contract['typ_enum'], $body['typ'])) {
            $this->assertContains($body['typ'], $contract['typ_enum']);
        }
    }

    /**
     * @param  array<string, mixed>  $movimento
     * @param  array<string, mixed>  $contract
     */
    private function assertMovimentoMatchesContract(array $movimento, array $contract, int $index): void
    {
        foreach ($contract['required'] as $field) {
            $this->assertArrayHasKey($field, $movimento, "Movimento #{$index} missing {$field}");
        }

        if (isset($contract['tipo_movimento_enum'])) {
            $this->assertContains($movimento['tipo_movimento'], $contract['tipo_movimento_enum']);
        }

        foreach ($contract['properties'] as $field => $type) {
            if (! array_key_exists($field, $movimento)) {
                continue;
            }

            $this->assertMaseFieldType($movimento[$field], $type, "movimenti[{$index}].{$field}");
        }
    }

    private function assertMaseFieldType(mixed $value, string $type, string $path): void
    {
        match ($type) {
            'string'       => $this->assertIsString($value, $path),
            'integer'      => $this->assertIsInt($value, $path),
            'number'       => $this->assertIsNumeric($value, $path),
            'array'        => $this->assertIsArray($value, $path),
            'object'       => $this->assertIsArray($value, $path),
            'date'         => $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', (string) $value, $path),
            'string|null'  => $this->assertTrue(is_string($value) || $value === null, $path),
            default        => $this->fail("Unknown MASE contract type {$type} for {$path}"),
        };
    }
}
