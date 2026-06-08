<?php

namespace Tests\Feature\Sprint115;

use App\Enums\VfuStato;
use App\Models\EcommerceProdotto;
use App\Models\User;
use App\Models\VfuRegistration;
use App\Support\Demo\DemoContext;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class OperatorePwaTest extends TestCase
{
    public function test_operatore_api_bonifica_returns_json_for_operatore(): void
    {
        VfuRegistration::factory()->create([
            'stato' => VfuStato::Accettato,
            'targa' => 'PW115AB',
        ]);

        $operatore = User::where('email', 'operatore@example.com')->firstOrFail();

        $response = $this->actingAs($operatore)
            ->getJson(route('operatore.api.bonifica', ['q' => 'PW115']));

        $response->assertOk()
            ->assertJsonPath('api_version', 1)
            ->assertJsonPath('demo_mode', DemoContext::isActive())
            ->assertJsonStructure(['veicoli', 'count', 'generated_at']);

        $this->assertGreaterThanOrEqual(1, $response->json('count'));
        $this->assertSame('PW115AB', $response->json('veicoli.0.targa'));
    }

    public function test_operatore_api_bonifica_denied_for_segreteria(): void
    {
        $segreteria = User::where('email', 'segreteria@example.com')->firstOrFail();

        $this->actingAs($segreteria)
            ->getJson(route('operatore.api.bonifica'))
            ->assertForbidden();
    }

    public function test_operatore_api_ricambi_lists_available_prodotti(): void
    {
        EcommerceProdotto::factory()->create([
            'codice'   => 'RIC-PWA115',
            'nome'     => 'Alternatore test',
            'giacenza' => 2,
            'attivo'   => true,
        ]);

        EcommerceProdotto::factory()->create([
            'codice'   => 'RIC-ESAUR',
            'giacenza' => 0,
            'attivo'   => true,
        ]);

        $operatore = User::where('email', 'operatore@example.com')->firstOrFail();

        $response = $this->actingAs($operatore)
            ->getJson(route('operatore.api.ricambi', ['q' => 'RIC-PWA']));

        $response->assertOk()
            ->assertJsonStructure(['prodotti', 'contatori', 'pagination'])
            ->assertJsonPath('prodotti.0.codice', 'RIC-PWA115');
    }

    public function test_operatore_api_vetrina_returns_evidenza_prodotti(): void
    {
        EcommerceProdotto::factory()->create([
            'codice'   => 'VET-PWA115',
            'giacenza' => 5,
            'attivo'   => true,
        ]);

        $operatore = User::where('email', 'operatore@example.com')->firstOrFail();

        $response = $this->actingAs($operatore)
            ->getJson(route('operatore.api.vetrina'));

        $response->assertOk()
            ->assertJsonStructure(['prodotti', 'contatori', 'count']);

        $codici = collect($response->json('prodotti'))->pluck('codice');
        $this->assertTrue($codici->contains('VET-PWA115'));
    }

    public function test_operatore_api_respects_demo_scope_in_response(): void
    {
        Config::set('demo.enabled', true);

        $operatore = User::where('email', 'operatore@example.com')->firstOrFail();

        $response = $this->actingAs($operatore)
            ->getJson(route('operatore.api.vetrina'));

        $response->assertOk()
            ->assertJsonPath('demo_mode', true);
    }

    public function test_operatore_manifest_accessible_for_operatore(): void
    {
        $operatore = User::where('email', 'operatore@example.com')->firstOrFail();

        $response = $this->actingAs($operatore)
            ->get(route('operatore.manifest'));

        $response->assertOk();
        $this->assertStringContainsString('application/manifest+json', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('Operatore', $response->getContent());
    }

    public function test_operatore_layout_includes_pwa_manifest_link(): void
    {
        $operatore = User::where('email', 'operatore@example.com')->firstOrFail();

        $this->actingAs($operatore)
            ->get(route('operatore.bonifica'))
            ->assertOk()
            ->assertSee('rel="manifest"', false)
            ->assertSee('operatore-sw.js', false);
    }

    public function test_operatore_pwa_doc_and_service_worker_exist(): void
    {
        $this->assertFileExists(base_path('docs/OPERATORE-PWA.md'));
        $this->assertFileExists(public_path('operatore-sw.js'));
        $this->assertFileExists(public_path('operatore-offline.html'));

        $doc = file_get_contents(base_path('docs/OPERATORE-PWA.md'));
        $this->assertStringContainsString('/operatore/api/bonifica', $doc);
        $this->assertStringContainsString('offline', strtolower($doc));
    }

    public function test_ciclo_10_piano_documents_sprint_115_operatore_pwa(): void
    {
        $content = file_get_contents(base_path('docs/CICLO-10-PIANO.md'));

        $this->assertStringContainsString('OperatoreMobileApiService', $content);
        $this->assertStringContainsString('OPERATORE-PWA.md', $content);
    }
}
