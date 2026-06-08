<?php

namespace Tests\Feature\Sprint4;

use App\Enums\RegistroMovimentoTipo;
use App\Models\CodiceCer;
use App\Models\MagazzinoCaricoManuale;
use App\Models\RegistroMovimento;
use App\Models\RentriTransmissione;
use App\Models\User;
use App\Services\Rentri\Contracts\RentriRegistryServiceInterface;
use Illuminate\Support\Carbon;
use Tests\Support\SeedsRentriCertificate;
use Tests\TestCase;

class RentriRegistryServiceTest extends TestCase
{
    use SeedsRentriCertificate;

    private RentriRegistryServiceInterface $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRentriCertificate();
        $this->service = app(RentriRegistryServiceInterface::class);
    }

    public function test_build_transmission_payload_includes_pending_movimenti(): void
    {
        $cer = CodiceCer::factory()->create();
        $inPeriod = RegistroMovimento::create([
            'tipo'           => RegistroMovimentoTipo::Carico,
            'codice_cer_id'  => $cer->id,
            'peso_kg'        => 10,
            'data_movimento' => now()->subDays(2),
            'source_type'    => MagazzinoCaricoManuale::class,
            'source_id'      => 1,
            'note'           => 'Test carico',
        ]);
        RegistroMovimento::create([
            'tipo'              => RegistroMovimentoTipo::Carico,
            'codice_cer_id'     => $cer->id,
            'peso_kg'           => 5,
            'data_movimento'    => now()->subDays(2),
            'source_type'       => MagazzinoCaricoManuale::class,
            'source_id'         => 2,
            'rentri_trasmesso'  => true,
            'locked_at'         => now(),
        ]);

        $payload = $this->service->buildTransmissionPayload(
            Carbon::now()->startOfMonth(),
            Carbon::now(),
        );

        $this->assertSame(1, $payload->metadata['count']);
        $this->assertSame($inPeriod->id, $payload->movimenti[0]['id']);
        $this->assertSame('carico', $payload->movimenti[0]['tipo']);
    }

    public function test_transmit_creates_record_and_locks_movimenti(): void
    {
        $cer = CodiceCer::factory()->create();
        $movimento = RegistroMovimento::create([
            'tipo'           => RegistroMovimentoTipo::Carico,
            'codice_cer_id'  => $cer->id,
            'peso_kg'        => 42.5,
            'data_movimento' => now()->subDay(),
            'source_type'    => MagazzinoCaricoManuale::class,
            'source_id'      => 1,
        ]);

        $payload = $this->service->buildTransmissionPayload(
            Carbon::now()->startOfMonth(),
            Carbon::now(),
        );

        $transmissione = $this->service->transmit($payload);

        $this->assertInstanceOf(RentriTransmissione::class, $transmissione);
        $this->assertSame('accettato', $transmissione->esito);
        $this->assertNotNull($transmissione->trasmesso_at);
        $this->assertArrayHasKey('protocollo', $transmissione->response_json ?? []);

        $movimento->refresh();
        $this->assertTrue($movimento->rentri_trasmesso);
        $this->assertNotNull($movimento->locked_at);
        $this->assertSame($transmissione->id, $movimento->rentri_transmission_id);
    }

    public function test_transmit_rejects_empty_period(): void
    {
        $payload = $this->service->buildTransmissionPayload(
            Carbon::now()->startOfMonth(),
            Carbon::now(),
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->service->transmit($payload);
    }
}
