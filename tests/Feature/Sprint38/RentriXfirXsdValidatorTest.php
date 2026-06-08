<?php

namespace Tests\Feature\Sprint38;

use App\Services\Rentri\Exceptions\RentriXfirValidationException;
use App\Services\Rentri\RentriXfirValidator;
use Tests\TestCase;

class RentriXfirXsdValidatorTest extends TestCase
{
    private function fixture(string $name): string
    {
        $path = base_path("tests/fixtures/xfir/{$name}");

        return (string) file_get_contents($path);
    }

    public function test_valid_fixture_passes_xsd_validation(): void
    {
        app(RentriXfirValidator::class)->validateXml($this->fixture('valid.xml'));

        $this->assertTrue(true);
    }

    public function test_invalid_missing_quantita_returns_italian_error(): void
    {
        try {
            app(RentriXfirValidator::class)->validateXml($this->fixture('invalid-missing-quantita.xml'));
            $this->fail('Expected RentriXfirValidationException');
        } catch (RentriXfirValidationException $e) {
            $this->assertNotEmpty($e->italianMessages);
            $this->assertTrue(
                collect($e->italianMessages)->contains(
                    fn (string $msg) => str_contains($msg, 'Quantità') || str_contains($msg, 'quantita')
                        || str_contains($msg, 'obbligatorio'),
                ),
            );
        }
    }

    public function test_invalid_versione_returns_italian_error(): void
    {
        $this->expectException(RentriXfirValidationException::class);

        app(RentriXfirValidator::class)->validateXml($this->fixture('invalid-versione.xml'));
    }

    public function test_invalid_zero_quantita_returns_italian_error(): void
    {
        try {
            app(RentriXfirValidator::class)->validateXml($this->fixture('invalid-zero-quantita.xml'));
            $this->fail('Expected RentriXfirValidationException');
        } catch (RentriXfirValidationException $e) {
            $this->assertNotEmpty($e->italianMessages);
            $this->assertTrue(
                collect($e->italianMessages)->contains(
                    fn (string $msg) => str_contains($msg, 'maggiore di zero') || str_contains($msg, 'minExclusive'),
                ),
            );
        }
    }

    public function test_payload_array_validates_against_xsd_after_serialization(): void
    {
        app(RentriXfirValidator::class)->validate([
            'versione'         => '1.0',
            'numero_fir'       => 'FIR-DEMO-001',
            'codice_blocco'    => 'DEMO-BLK',
            'progressivo'      => 1,
            'identificativo'   => 'RSSMRA80A01H501Z',
            'num_iscr_sito'    => 'SITE-DEMO',
            'data_vidimazione' => '2026-06-04',
            'trasporto'        => [
                'codice_cer'  => '16.01.04',
                'quantita_kg' => 100,
            ],
        ]);

        $this->assertTrue(true);
    }

    public function test_field_validation_runs_before_xsd(): void
    {
        $this->expectException(RentriXfirValidationException::class);
        $this->expectExceptionMessage('Campo xFIR trasporto obbligatorio mancante: codice_cer');

        app(RentriXfirValidator::class)->validate([
            'versione'         => '1.0',
            'numero_fir'       => 'FIR-001',
            'codice_blocco'    => 'BLK',
            'progressivo'      => 1,
            'identificativo'   => 'RSSMRA80A01H501Z',
            'num_iscr_sito'    => 'SITE',
            'data_vidimazione' => '2026-06-01',
            'trasporto'        => ['quantita_kg' => 10],
        ]);
    }
}
