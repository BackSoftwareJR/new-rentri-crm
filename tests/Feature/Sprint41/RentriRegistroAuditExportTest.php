<?php

namespace Tests\Feature\Sprint41;

use App\Domain\Rentri\RentriRegistroAuditExportService;
use App\Enums\RegistroMovimentoTipo;
use App\Http\Livewire\Segreteria\Rentri;
use App\Models\CodiceCer;
use App\Models\MagazzinoCaricoManuale;
use App\Models\RegistroMovimento;
use App\Models\RentriTransazione;
use App\Models\RentriTransmissione;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\Support\SeedsRentriCertificate;
use Tests\TestCase;

class RentriRegistroAuditExportTest extends TestCase
{
    use SeedsRentriCertificate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRentriCertificate();
    }

    public function test_audit_export_includes_protocollo_movimenti_and_transazione(): void
    {
        $cer = CodiceCer::factory()->create(['codice' => '16 01 06']);
        $transazioneUuid = (string) Str::uuid();

        RentriTransazione::create([
            'transazione_id' => $transazioneUuid,
            'tipo_api'       => 'registro',
            'stato'          => 'completata',
            'completed_at'   => now(),
        ]);

        $transmissione = RentriTransmissione::create([
            'periodo_da'   => now()->startOfMonth(),
            'periodo_a'    => now(),
            'payload_hash' => hash('sha256', 'audit'),
            'esito'        => 'accettato',
            'trasmesso_at' => now(),
            'response_json'=> [
                'protocollo'     => 'RENTRI-AUDIT-001',
                'transazione_id' => $transazioneUuid,
                'api_mode'       => 'stub',
            ],
        ]);

        RegistroMovimento::create([
            'tipo'                   => RegistroMovimentoTipo::Carico,
            'codice_cer_id'          => $cer->id,
            'peso_kg'                => 22,
            'data_movimento'         => now()->subDay(),
            'source_type'            => MagazzinoCaricoManuale::class,
            'source_id'              => 1,
            'rentri_trasmesso'       => true,
            'rentri_transmission_id' => $transmissione->id,
            'locked_at'              => now(),
        ]);

        $payload = app(RentriRegistroAuditExportService::class)->buildPayload($transmissione->fresh(['movimenti.codiceCer']));

        $this->assertSame('RENTRI-AUDIT-001', $payload['trasmissione']['protocollo']);
        $this->assertSame($transazioneUuid, $payload['transazione_rentri']['transazione_id']);
        $this->assertSame('registro', $payload['transazione_rentri']['tipo_api']);
        $this->assertCount(1, $payload['movimenti']);
        $this->assertSame('16 01 06', $payload['movimenti'][0]['codice_cer']);
    }

    public function test_ui_shows_conformita_checklist_and_audit_export_buttons(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $cer = CodiceCer::factory()->create();

        RegistroMovimento::create([
            'tipo'           => RegistroMovimentoTipo::Carico,
            'codice_cer_id'  => $cer->id,
            'peso_kg'        => 15,
            'data_movimento' => now()->subDay(),
            'source_type'    => MagazzinoCaricoManuale::class,
            'source_id'      => 1,
        ]);

        $transmissione = RentriTransmissione::create([
            'periodo_da'   => now()->startOfMonth(),
            'periodo_a'    => now(),
            'payload_hash' => hash('sha256', 'ui'),
            'esito'        => 'accettato',
            'trasmesso_at' => now(),
            'response_json'=> ['protocollo' => 'UI-PROTO'],
        ]);

        Livewire::actingAs($user)
            ->test(Rentri::class)
            ->assertSee('Checklist conformità ministeriale')
            ->assertSee('Identificativo operatore')
            ->assertSee('JSON')
            ->assertSee('CSV');

        Livewire::actingAs($user)
            ->test(Rentri::class)
            ->call('exportAuditJson', $transmissione->id)
            ->assertSuccessful();
    }
}
