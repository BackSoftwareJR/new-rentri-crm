<?php

namespace Tests\Feature\Sprint62;

use App\Domain\Anagrafiche\AuthorizationAlertService;
use App\Http\Livewire\Segreteria\Anagrafiche\AnagraficaForm;
use App\Http\Livewire\Segreteria\Anagrafiche\AnagraficheIndex;
use App\Http\Livewire\Segreteria\Dashboard;
use App\Models\Anagrafica;
use App\Models\Authorization;
use App\Models\User;
use App\Support\ItalianFiscalValidator;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class AnagraficheAvanzateTest extends TestCase
{
    public function test_valid_partita_iva_passes_checksum(): void
    {
        $this->assertTrue(ItalianFiscalValidator::isValidPartitaIva('12345678903'));
        $this->assertTrue(ItalianFiscalValidator::isValidPartitaIva('IT 12345678903'));
    }

    public function test_invalid_partita_iva_fails_checksum(): void
    {
        $this->assertFalse(ItalianFiscalValidator::isValidPartitaIva('12345678901'));
        $this->assertFalse(ItalianFiscalValidator::isValidPartitaIva('ABCDEF'));
    }

    public function test_valid_codice_fiscale_passes_checksum(): void
    {
        $this->assertTrue(ItalianFiscalValidator::isValidCodiceFiscale('RSSMRA80A01H501U'));
    }

    public function test_invalid_codice_fiscale_fails_checksum(): void
    {
        $this->assertFalse(ItalianFiscalValidator::isValidCodiceFiscale('RSSMRA80A01H501A'));
    }

    public function test_anagrafica_form_rejects_invalid_fiscal_fields(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(AnagraficaForm::class)
            ->set('tipo', 'trasportatore')
            ->set('ragione_sociale', 'Trasporti Test Srl')
            ->set('piva', '12345678901')
            ->set('codice_fiscale', 'RSSMRA80A01H501A')
            ->call('save')
            ->assertHasErrors(['piva', 'codice_fiscale']);
    }

    public function test_anagrafica_form_accepts_valid_fiscal_fields(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(AnagraficaForm::class)
            ->set('tipo', 'privato')
            ->set('ragione_sociale', 'Mario Rossi')
            ->set('piva', '12345678903')
            ->set('codice_fiscale', 'RSSMRA80A01H501U')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('anagrafiche', [
            'ragione_sociale' => 'Mario Rossi',
            'piva' => '12345678903',
            'codice_fiscale' => 'RSSMRA80A01H501U',
        ]);
    }

    public function test_authorization_alert_service_counts_expiring_and_expired(): void
    {
        $expiredAnagrafica = Anagrafica::factory()->create(['tipo' => 'trasportatore']);
        Authorization::factory()->for($expiredAnagrafica)->expired()->create();

        $expiringAnagrafica = Anagrafica::factory()->create(['tipo' => 'trasportatore']);
        Authorization::factory()->for($expiringAnagrafica)->expiringSoon()->create();

        $privato = Anagrafica::factory()->create(['tipo' => 'privato']);
        Authorization::factory()->for($privato)->expired()->create();

        $service = app(AuthorizationAlertService::class);
        $summary = $service->summary();

        $this->assertSame(1, $summary['scadute']);
        $this->assertSame(1, $summary['in_scadenza']);
    }

    public function test_dashboard_shows_auth_alert_widget(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $anagrafica = Anagrafica::factory()->create(['tipo' => 'trasportatore']);
        Authorization::factory()->for($anagrafica)->expired()->create();

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertSee('Autorizzazioni trasporto')
            ->assertSee('Scadute')
            ->assertSee($anagrafica->ragione_sociale);
    }

    public function test_anagrafiche_index_shows_alert_banner(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $anagrafica = Anagrafica::factory()->create(['tipo' => 'trasportatore']);
        Authorization::factory()->for($anagrafica)->expiringSoon()->create();

        Livewire::actingAs($user)
            ->test(AnagraficheIndex::class)
            ->assertSee('Autorizzazioni trasporto')
            ->assertSee('in scadenza');
    }

    public function test_anagrafica_policy_unchanged_for_segretaria(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $anagrafica = Anagrafica::factory()->create();

        $this->assertTrue(Gate::forUser($user)->allows('viewAny', Anagrafica::class));
        $this->assertTrue(Gate::forUser($user)->allows('view', $anagrafica));
        $this->assertTrue(Gate::forUser($user)->allows('update', $anagrafica));
        $this->assertTrue(Gate::forUser($user)->allows('delete', $anagrafica));
    }
}
