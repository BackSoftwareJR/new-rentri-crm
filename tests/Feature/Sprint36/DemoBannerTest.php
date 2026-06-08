<?php

namespace Tests\Feature\Sprint36;

use App\Models\User;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class DemoBannerTest extends TestCase
{
    public function test_demo_banner_visible_on_segreteria_when_demo_enabled(): void
    {
        Config::set('demo.enabled', true);

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $response = $this->actingAs($user)->get('/segreteria');

        $response->assertOk();
        $response->assertSee('Modalità DEMO');
        $response->assertSee('i dati RENTRI/FIR non sono produzione');
    }

    public function test_demo_banner_hidden_in_production_mode(): void
    {
        Config::set('demo.enabled', false);

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $response = $this->actingAs($user)->get('/segreteria');

        $response->assertOk();
        $response->assertDontSee('Modalità DEMO');
    }
}
