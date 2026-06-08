<?php

namespace Tests\Feature\Sprint13;

use App\Domain\Ecommerce\EcommerceService;
use App\Enums\OrdineEcommerceStato;
use App\Models\EcommerceProdotto;
use App\Models\User;
use Tests\TestCase;

class EcommerceServiceTest extends TestCase
{
    public function test_add_to_cart_and_create_ordine_bozza_clears_session(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $prodotto = EcommerceProdotto::factory()->create([
            'prezzo'   => 25.00,
            'giacenza' => 5,
        ]);

        $service = app(EcommerceService::class);
        $service->addToCart($prodotto->id, 2);

        $this->assertSame(2, $service->cartCount());
        $this->assertSame(50.0, $service->cartTotale());

        $ordine = $service->createOrdineBozza($user->id);

        $this->assertSame(OrdineEcommerceStato::Bozza, $ordine->stato);
        $this->assertSame(50.0, (float) $ordine->totale);
        $this->assertCount(1, $ordine->righe);
        $this->assertSame(0, $service->cartCount());
    }

    public function test_create_ordine_decrements_giacenza(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $prodotto = EcommerceProdotto::factory()->create(['giacenza' => 10]);

        $service = app(EcommerceService::class);
        $service->addToCart($prodotto->id, 3);
        $service->createOrdineBozza($user->id);

        $this->assertSame(7, $prodotto->fresh()->giacenza);
    }

    public function test_add_to_cart_rejects_insufficient_stock(): void
    {
        $prodotto = EcommerceProdotto::factory()->create(['giacenza' => 1]);

        $this->expectException(\InvalidArgumentException::class);

        app(EcommerceService::class)->addToCart($prodotto->id, 5);
    }
}
