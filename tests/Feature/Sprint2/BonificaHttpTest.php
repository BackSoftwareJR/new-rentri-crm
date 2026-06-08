<?php

namespace Tests\Feature\Sprint2;

use App\Domain\Bonifica\BonificaService;
use App\Models\User;
use App\Models\VfuRegistration;
use Tests\TestCase;

class BonificaHttpTest extends TestCase
{
    public function test_operatore_can_access_bonifica_list(): void
    {
        $user = User::where('email', 'operatore@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('operatore.bonifica'))
            ->assertOk();
    }

    public function test_operatore_can_access_bonifica_wizard(): void
    {
        $user = User::where('email', 'operatore@example.com')->firstOrFail();
        $vfu = VfuRegistration::factory()->accettatoPerBonifica()->create();

        $this->actingAs($user)
            ->get(route('operatore.bonifica.wizard', $vfu))
            ->assertOk();
    }

    public function test_attesa_bonifica_veicolo_in_lista_da_bonificare(): void
    {
        $vfu = VfuRegistration::factory()->attesaBonifica()->create(['targa' => 'AT123BC']);

        $ids = app(BonificaService::class)
            ->queryVeicoliDaBonificare()
            ->pluck('id');

        $this->assertTrue($ids->contains($vfu->id));
    }
}
