<?php

namespace Tests\Feature\Sprint41;

use App\Domain\Rentri\RentriRegistroConformitaValidator;
use App\Enums\RegistroMovimentoTipo;
use App\Models\CodiceCer;
use App\Models\MagazzinoCaricoManuale;
use App\Models\RegistroMovimento;
use App\Models\RentriSetting;
use App\Services\Rentri\Contracts\RentriRegistryServiceInterface;
use App\Services\Rentri\Exceptions\RentriRegistroConformitaException;
use Illuminate\Support\Carbon;
use Tests\Support\SeedsRentriCertificate;
use Tests\TestCase;

class RentriRegistroConformitaValidatorTest extends TestCase
{
    use SeedsRentriCertificate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRentriCertificate(['num_iscr_sito' => 'OP12345678901-PD00001']);
    }

    public function test_checklist_passes_with_valid_payload(): void
    {
        $cer = CodiceCer::factory()->create(['codice' => '16 01 04']);
        RegistroMovimento::create([
            'tipo'           => RegistroMovimentoTipo::Carico,
            'codice_cer_id'  => $cer->id,
            'peso_kg'        => 12,
            'data_movimento' => now()->subDay(),
            'source_type'    => MagazzinoCaricoManuale::class,
            'source_id'      => 1,
        ]);

        $payload = app(RentriRegistryServiceInterface::class)->buildTransmissionPayload(
            Carbon::now()->startOfMonth(),
            Carbon::now(),
        );

        $checklist = app(RentriRegistroConformitaValidator::class)->checklist($payload, RentriSetting::instance());

        $this->assertTrue(collect($checklist)->every(fn (array $item) => $item['ok']));
        app(RentriRegistroConformitaValidator::class)->assertConforme($payload, RentriSetting::instance());
    }

    public function test_checklist_fails_without_num_iscr_sito(): void
    {
        RentriSetting::instance()->update(['num_iscr_sito' => null]);

        $cer = CodiceCer::factory()->create();
        RegistroMovimento::create([
            'tipo'           => RegistroMovimentoTipo::Carico,
            'codice_cer_id'  => $cer->id,
            'peso_kg'        => 5,
            'data_movimento' => now()->subDay(),
            'source_type'    => MagazzinoCaricoManuale::class,
            'source_id'      => 1,
        ]);

        $payload = app(RentriRegistryServiceInterface::class)->buildTransmissionPayload(
            Carbon::now()->startOfMonth(),
            Carbon::now(),
        );

        $this->expectException(RentriRegistroConformitaException::class);
        app(RentriRegistroConformitaValidator::class)->assertConforme($payload, RentriSetting::instance());
    }

    public function test_transmit_rejects_zero_weight_movimento(): void
    {
        $cer = CodiceCer::factory()->create();
        RegistroMovimento::create([
            'tipo'           => RegistroMovimentoTipo::Carico,
            'codice_cer_id'  => $cer->id,
            'peso_kg'        => 0,
            'data_movimento' => now()->subDay(),
            'source_type'    => MagazzinoCaricoManuale::class,
            'source_id'      => 1,
        ]);

        $service = app(RentriRegistryServiceInterface::class);
        $payload = $service->buildTransmissionPayload(Carbon::now()->startOfMonth(), Carbon::now());

        $this->expectException(RentriRegistroConformitaException::class);
        $service->transmit($payload);
    }
}
