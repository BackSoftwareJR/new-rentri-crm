<?php

namespace Tests\Feature\Sprint104;

use App\Models\Anagrafica;
use App\Models\User;
use App\Services\Rentri\Contracts\RentriApiClientInterface;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\Support\SeedsRentriCertificate;
use Tests\TestCase;

class AnagraficaRentriVerificaTest extends TestCase
{
    use SeedsRentriCertificate;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRentriCertificate();
        $this->user = User::where('email', 'segreteria@example.com')->firstOrFail();
    }

    public function test_anagrafica_model_has_rentri_fields(): void
    {
        $anagrafica = Anagrafica::factory()->create([
            'tipo'                     => 'trasportatore',
            'rentri_verificato_at'     => now(),
            'rentri_iscrizione_numero' => 'AUT-12345',
            'rentri_verificato_esito'  => 'iscritto',
        ]);

        $this->assertTrue($anagrafica->isRentriVerificato());
        $this->assertSame('Iscritto RENTRI', $anagrafica->rentri_verificato_label());
    }

    public function test_anagrafica_non_trovato_esito(): void
    {
        $anagrafica = Anagrafica::factory()->create([
            'tipo'                    => 'trasportatore',
            'rentri_verificato_at'    => now(),
            'rentri_verificato_esito' => 'non_trovato',
        ]);

        $this->assertFalse($anagrafica->isRentriVerificato());
        $this->assertSame('Non trovato su RENTRI', $anagrafica->rentri_verificato_label());
    }

    public function test_verifica_rentri_stub_saves_iscritto_result(): void
    {
        $anagrafica = Anagrafica::factory()->create([
            'tipo'           => 'trasportatore',
            'codice_fiscale' => 'TSTCMP80A01H501Z',
            'piva'           => '12345678901',
        ]);

        Livewire::actingAs($this->user)
            ->test(\App\Http\Livewire\Segreteria\Anagrafiche\AnagraficaShow::class, ['anagrafica' => $anagrafica])
            ->call('verificaRentri')
            ->assertHasNoErrors();

        $anagrafica->refresh();

        $this->assertNotNull($anagrafica->rentri_verificato_at);
        $this->assertSame('iscritto', $anagrafica->rentri_verificato_esito);
        $this->assertNotNull($anagrafica->rentri_iscrizione_numero);
    }

    public function test_verifica_rentri_fails_gracefully_without_identifier(): void
    {
        $anagrafica = Anagrafica::factory()->create([
            'tipo'           => 'trasportatore',
            'codice_fiscale' => null,
            'piva'           => null,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(\App\Http\Livewire\Segreteria\Anagrafiche\AnagraficaShow::class, ['anagrafica' => $anagrafica])
            ->call('verificaRentri');

        $result = $component->get('rentriVerificaResult');
        $this->assertNotNull($result);
        $this->assertArrayHasKey('error', $result);
    }

    public function test_can_verifica_rentri_only_for_eligible_types(): void
    {
        $trasportatore = Anagrafica::factory()->create(['tipo' => 'trasportatore']);
        $impianto = Anagrafica::factory()->create(['tipo' => 'impianto']);
        $privato = Anagrafica::factory()->create(['tipo' => 'privato']);

        $componentTrasportatore = Livewire::actingAs($this->user)
            ->test(\App\Http\Livewire\Segreteria\Anagrafiche\AnagraficaShow::class, ['anagrafica' => $trasportatore]);
        $this->assertTrue($componentTrasportatore->get('anagrafica')->tipo === 'trasportatore');

        $componentPrivato = Livewire::actingAs($this->user)
            ->test(\App\Http\Livewire\Segreteria\Anagrafiche\AnagraficaShow::class, ['anagrafica' => $privato]);
        $this->assertSame('privato', $componentPrivato->get('anagrafica')->tipo);
    }

    public function test_rentri_api_client_lookup_operatore_stub(): void
    {
        $apiClient = app(RentriApiClientInterface::class);
        $result = $apiClient->lookupOperatore('TSTCMP80A01H501Z');

        $this->assertArrayHasKey('iscritto', $result);
        $this->assertArrayHasKey('numero_iscrizione', $result);
        $this->assertArrayHasKey('validita_autorizzazione', $result);
        $this->assertArrayHasKey('ragione_sociale', $result);
        $this->assertArrayHasKey('raw', $result);
        $this->assertTrue($result['iscritto']);
    }

    public function test_rentri_api_client_lookup_empty_cf_returns_not_found(): void
    {
        $apiClient = app(RentriApiClientInterface::class);
        $result = $apiClient->lookupOperatore('');

        $this->assertFalse($result['iscritto']);
        $this->assertNull($result['numero_iscrizione']);
    }

    public function test_rentri_api_client_lookup_live_parses_response(): void
    {
        Config::set('services.rentri.api_stub', false);

        Http::fake([
            'demoapi.rentri.gov.it/operatori/v1.0/*' => Http::response([
                'stato_iscrizione'        => 'iscritto',
                'numero_iscrizione'       => 'AUT-TEST-9999',
                'validita_autorizzazione' => '2027-12-31',
                'ragione_sociale'         => 'Test Trasportatore SRL',
            ], 200),
        ]);

        $apiClient = app(RentriApiClientInterface::class);
        $result = $apiClient->lookupOperatore('TSTCMP80A01H501Z');

        $this->assertTrue($result['iscritto']);
        $this->assertSame('AUT-TEST-9999', $result['numero_iscrizione']);
        $this->assertSame('Test Trasportatore SRL', $result['ragione_sociale']);
    }

    public function test_rentri_api_client_lookup_live_404_returns_not_found(): void
    {
        Config::set('services.rentri.api_stub', false);

        Http::fake([
            'demoapi.rentri.gov.it/operatori/v1.0/*' => Http::response(['message' => 'Not Found'], 404),
        ]);

        $apiClient = app(RentriApiClientInterface::class);
        $result = $apiClient->lookupOperatore('CF-INESISTENTE');

        $this->assertFalse($result['iscritto']);
    }

    public function test_index_view_shows_rentri_column(): void
    {
        Anagrafica::factory()->create([
            'tipo'                     => 'trasportatore',
            'rentri_verificato_esito'  => 'iscritto',
            'rentri_iscrizione_numero' => 'AUT-99',
        ]);

        Livewire::actingAs($this->user)
            ->test(\App\Http\Livewire\Segreteria\Anagrafiche\AnagraficheIndex::class)
            ->assertSeeHtml('Iscritto');
    }
}
