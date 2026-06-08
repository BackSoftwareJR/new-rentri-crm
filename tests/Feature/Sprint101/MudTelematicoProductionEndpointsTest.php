<?php

namespace Tests\Feature\Sprint101;

use App\Domain\Mud\MudInvioTelematicoService;
use App\Domain\Mud\MudService;
use App\Domain\Mud\MudTelematicoEndpoints;
use App\Domain\Mud\MudTelematicoTransmissionService;
use App\Enums\MudStato;
use App\Http\Livewire\Segreteria\Mud\MudShow;
use App\Models\MudDichiarazione;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class MudTelematicoProductionEndpointsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.mud_telematico.stub', true);
    }

    public function test_endpoints_default_sandbox_rentri_gateway(): void
    {
        Config::set('services.mud_telematico.env', 'sandbox');
        Config::set('services.mud_telematico.base_url', null);
        Config::set('services.rentri.base_url_sandbox', 'https://demoapi.rentri.gov.it');

        $endpoints = app(MudTelematicoEndpoints::class);

        $this->assertSame('sandbox', $endpoints->environment());
        $this->assertSame('https://demoapi.rentri.gov.it', $endpoints->baseUrl());
        $this->assertSame(
            'https://demoapi.rentri.gov.it/mud/v1.0/dichiarazioni/trasmissione',
            $endpoints->submitUrl(),
        );
        $this->assertSame('https://www.mudtelematico.it', MudTelematicoEndpoints::PORTAL_URL);
    }

    public function test_endpoints_production_resolves_api_rentri(): void
    {
        Config::set('services.mud_telematico.env', 'production');
        Config::set('services.rentri.base_url_production', 'https://api.rentri.gov.it');

        $endpoints = app(MudTelematicoEndpoints::class);

        $this->assertTrue($endpoints->isProduction());
        $this->assertSame('https://api.rentri.gov.it/mud/v1.0/dichiarazioni/trasmissione', $endpoints->submitUrl());
    }

    public function test_fixture_documents_rentri_aligned_contract(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('tests/fixtures/mud/mase-invio-submit.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertStringContainsString('/mud/v1.0/dichiarazioni/trasmissione', $contract['endpoint']);
        $this->assertSame('https://demoapi.rentri.gov.it', $contract['environments']['sandbox']['base_url']);
        $this->assertSame('https://www.mudtelematico.it', $contract['environments']['sandbox']['portal_url']);
    }

    public function test_transmission_live_uses_endpoints_paths_and_result_query(): void
    {
        Config::set('services.mud_telematico.stub', false);
        Config::set('services.mud_telematico.base_url', 'https://demoapi.rentri.gov.it');
        Config::set('services.mud_telematico.poll_interval_ms', 1);

        Http::fake([
            'demoapi.rentri.gov.it/mud/v1.0/dichiarazioni/trasmissione' => Http::response([
                'transazione_id' => 'tx-mud-s101',
            ], 202),
            'demoapi.rentri.gov.it/mud/v1.0/dichiarazioni/tx-mud-s101/status' => Http::response([
                'stato' => 'COMPLETATA',
            ], 200),
            'demoapi.rentri.gov.it/mud/v1.0/dichiarazioni/verifica/result*' => Http::response([
                'protocollo' => 'MUD-2025-PROD001',
                'esito'      => 'accettato',
            ], 200),
        ]);

        $mud = $this->completataSample();
        $xml = app(\App\Domain\Mud\MudXmlValidationService::class)->buildXml($mud, app(MudService::class));

        $result = app(MudTelematicoTransmissionService::class)->submitAndWait($mud, $xml);

        $this->assertSame('MUD-2025-PROD001', $result['protocollo']);

        Http::assertSent(function ($request) {
            return $request->method() === 'GET'
                && str_contains($request->url(), '/verifica/result')
                && str_contains($request->url(), 'transazione_id=tx-mud-s101');
        });
    }

    public function test_probe_reachability_head_success(): void
    {
        Config::set('services.mud_telematico.base_url', 'https://demoapi.rentri.gov.it');

        Http::fake([
            'demoapi.rentri.gov.it' => Http::response('', 200),
        ]);

        $probe = app(MudTelematicoEndpoints::class)->probeReachability();

        $this->assertTrue($probe['reachable']);
        $this->assertSame('HEAD', $probe['method']);
    }

    public function test_mud_show_live_displays_submit_url_and_portal(): void
    {
        Config::set('services.mud_telematico.stub', false);
        Config::set('services.mud_telematico.env', 'sandbox');
        Config::set('services.mud_telematico.base_url', 'https://demoapi.rentri.gov.it');

        Http::fake([
            'demoapi.rentri.gov.it' => Http::response('', 200),
        ]);

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $mud = $this->completataSample();

        Livewire::actingAs($user)
            ->test(MudShow::class, ['dichiarazione' => $mud])
            ->assertSee('demoapi.rentri.gov.it/mud/v1.0/dichiarazioni/trasmissione')
            ->assertSee('mudtelematico.it')
            ->assertSee('Reachability');
    }

    public function test_sprint_101_audit_notes_document_research(): void
    {
        $path = base_path('docs/SPRINT-101-AUDIT-NOTES.md');
        $content = file_get_contents($path);

        $this->assertFileExists($path);
        $this->assertStringContainsString('mudtelematico.it', $content);
        $this->assertStringContainsString('MudTelematicoEndpoints', $content);
        $this->assertStringContainsString('demoapi.rentri.gov.it', $content);
    }

    private function completataSample(): MudDichiarazione
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $mud = MudDichiarazione::create([
            'anno_riferimento' => 2025,
            'stato'            => MudStato::Bozza,
            'righe'            => [
                [
                    'codice_cer_id' => 1,
                    'codice'        => '16.01.04',
                    'descrizione'   => 'Rifiuti ferrosi',
                    'carichi_kg'    => 100,
                    'scarichi_kg'   => 20,
                    'saldo_kg'      => 80,
                ],
            ],
            'user_id'          => $user->id,
        ]);

        return app(MudService::class)->completa($mud);
    }
}
