<?php

namespace Tests\Feature\Sprint95;

use App\Domain\Mud\MudInvioTelematicoService;
use App\Domain\Mud\MudService;
use App\Domain\Mud\MudTelematicoRuntimeModeService;
use App\Domain\Mud\MudTelematicoTransmissionService;
use App\Enums\MudStato;
use App\Http\Livewire\Segreteria\Mud\MudShow;
use App\Models\MudDichiarazione;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class MudTelematicoLivePrepTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.mud_telematico.stub', true);
    }

    public function test_runtime_mode_defaults_to_stub(): void
    {
        $runtime = app(MudTelematicoRuntimeModeService::class);

        $this->assertTrue($runtime->isStub());
        $this->assertSame('stub', $runtime->modeLabel());
        $this->assertSame('MUD stub', $runtime->modeDisplayLabel());
        $this->assertSame('info', $runtime->modeDisplayVariant());
    }

    public function test_runtime_mode_live_when_stub_disabled(): void
    {
        Config::set('services.mud_telematico.stub', false);

        $runtime = app(MudTelematicoRuntimeModeService::class);

        $this->assertFalse($runtime->isStub());
        $this->assertSame('live', $runtime->modeLabel());
        $this->assertSame('MUD telematico live', $runtime->modeDisplayLabel());
        $this->assertSame('success', $runtime->modeDisplayVariant());
    }

    public function test_mud_fixture_documents_async_contract(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('tests/fixtures/mud/mase-invio-submit.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertSame(['dichiarazione_id', 'totali'], $contract['crm_excluded_keys']);
        $this->assertContains('anno_riferimento', $contract['mase_body_keys']);
        $this->assertArrayHasKey('example_mase', $contract);
    }

    public function test_transmission_stub_async_returns_mud_stub_protocol(): void
    {
        $mud = $this->completataSample();
        $xml = app(\App\Domain\Mud\MudXmlValidationService::class)->buildXml($mud, app(MudService::class));

        $submit = app(MudTelematicoTransmissionService::class)->submit($mud, $xml);
        $this->assertNotEmpty($submit['transazione_id']);

        $result = app(MudTelematicoTransmissionService::class)->waitResult($submit['transazione_id']);

        $this->assertTrue($result['stub']);
        $this->assertSame('accettato', $result['esito']);
        $this->assertStringStartsWith('MUD-STUB-2025-', $result['protocollo']);
    }

    public function test_transmission_live_http_submit_and_poll(): void
    {
        Config::set('services.mud_telematico.stub', false);
        Config::set('services.mud_telematico.base_url', 'https://sandbox.mud-telematico.test');
        Config::set('services.mud_telematico.poll_interval_ms', 1);

        Http::fake([
            'sandbox.mud-telematico.test/mud/v1.0/dichiarazioni/trasmissione' => Http::response([
                'transazione_id' => 'tx-mud-s95',
            ], 202),
            'sandbox.mud-telematico.test/mud/v1.0/dichiarazioni/tx-mud-s95/status' => Http::response([
                'stato' => 'COMPLETATA',
            ], 200),
            'sandbox.mud-telematico.test/mud/v1.0/dichiarazioni/verifica/result*' => Http::response([
                'esito'      => 'accettato',
                'protocollo' => 'MUD-2025-LIVE0001',
                'ricevuto_il' => now()->toIso8601String(),
            ], 200),
        ]);

        $mud = $this->completataSample();
        $xml = app(\App\Domain\Mud\MudXmlValidationService::class)->buildXml($mud, app(MudService::class));

        $result = app(MudTelematicoTransmissionService::class)->submitAndWait($mud, $xml);

        $this->assertFalse($result['stub']);
        $this->assertSame('MUD-2025-LIVE0001', $result['protocollo']);
        $this->assertSame('ministero_http', $result['canale']);

        Http::assertSent(function ($request) {
            if ($request->method() !== 'POST') {
                return false;
            }

            $body = $request->data();

            return str_contains($request->url(), '/mud/v1.0/dichiarazioni/trasmissione')
                && ($body['anno_riferimento'] ?? null) === 2025
                && ($body['xml_encoding'] ?? null) === 'base64'
                && ! array_key_exists('dichiarazione_id', $body)
                && ! array_key_exists('totali', $body);
        });
    }

    public function test_pre_invio_checklist_requires_gateway_in_live_mode(): void
    {
        Config::set('services.mud_telematico.stub', false);
        Config::set('services.mud_telematico.base_url', null);
        Config::set('services.rentri.base_url_sandbox', '');
        Config::set('services.rentri.base_url_production', '');

        $mud = $this->completataSample();
        $checklist = app(MudInvioTelematicoService::class)->preInvioChecklist($mud);

        $endpoint = collect($checklist)->firstWhere('key', 'endpoint_configurato');
        $this->assertNotNull($endpoint);
        $this->assertFalse($endpoint['ok']);
        $this->assertFalse(app(MudInvioTelematicoService::class)->canInviare($mud));
    }

    public function test_mud_show_displays_stub_badge_and_checklist(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $mud = $this->completataSample();

        Livewire::actingAs($user)
            ->test(MudShow::class, ['dichiarazione' => $mud])
            ->assertSee('MUD stub')
            ->assertSee('Checklist pre-invio telematico')
            ->assertSee('Invia telematico (stub)');
    }

    public function test_mud_show_live_mode_button_and_badge(): void
    {
        Config::set('services.mud_telematico.stub', false);
        Config::set('services.mud_telematico.base_url', 'https://sandbox.mud-telematico.test');

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $mud = $this->completataSample();

        Livewire::actingAs($user)
            ->test(MudShow::class, ['dichiarazione' => $mud])
            ->assertSee('MUD telematico live')
            ->assertSee('Invia telematico MASE')
            ->assertSee('sandbox.mud-telematico.test')
            ->assertSee('/mud/v1.0/dichiarazioni/trasmissione');
    }

    public function test_sprint_95_audit_notes_document_m95_gap(): void
    {
        $path = base_path('docs/SPRINT-95-AUDIT-NOTES.md');
        $content = file_get_contents($path);

        $this->assertFileExists($path);
        $this->assertStringContainsString('M-95-1', $content);
        $this->assertStringContainsString('MudTelematicoTransmissionService', $content);
        $this->assertStringContainsString('MUD_TELEMATICO_STUB', $content);
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
