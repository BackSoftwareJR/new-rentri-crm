<?php

namespace Tests\Feature\Sprint46;

use App\Models\RentriSetting;
use App\Models\User;
use App\Services\Rentri\Contracts\RentriApiClientInterface;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\Support\SeedsRentriCertificate;
use Tests\TestCase;

class DemoSessionApiAndUiTest extends TestCase
{
    use SeedsRentriCertificate;

    public function test_session_demo_forces_sandbox_url_with_certificate(): void
    {
        Config::set('demo.enabled', false);
        Config::set('services.rentri.api_stub', false);
        session([config('demo.session.key') => true]);

        $this->seedRentriCertificate(['ambiente' => 'produzione']);

        Http::fake([
            'demoapi.rentri.gov.it/vidimazione-formulari/v1.0*' => Http::response(['items' => []], 200),
        ]);

        $result = app(RentriApiClientInterface::class)->healthCheck();

        $this->assertSame('ok', $result['status']);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'demoapi.rentri.gov.it'));
    }

    public function test_sidebar_shows_palestra_toggle_for_segreteria(): void
    {
        Config::set('demo.allow_session_toggle', true);

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get('/segreteria')
            ->assertOk()
            ->assertSee('Palestra operativa');
    }

    public function test_sidebar_hides_toggle_for_operatore(): void
    {
        Config::set('demo.allow_session_toggle', true);

        $user = User::where('email', 'operatore@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get('/operatore')
            ->assertOk()
            ->assertDontSee('Palestra operativa');
    }

    public function test_session_demo_banner_visible(): void
    {
        Config::set('demo.enabled', false);
        session([config('demo.session.key') => true]);

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get('/segreteria')
            ->assertOk()
            ->assertSee('Palestra operativa')
            ->assertSee('scope demo attivo in sessione');
    }

    public function test_livewire_toggle_activates_session_demo(): void
    {
        Config::set('demo.allow_session_toggle', true);

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(\App\Http\Livewire\Segreteria\DemoModeToggle::class)
            ->call('confirmActivate')
            ->assertHasNoErrors();

        $this->assertTrue(session(config('demo.session.key')));
    }
}
