<?php

namespace Tests\Feature\Sprint120;

use App\Http\Livewire\Shop\ShopCart;
use App\Http\Livewire\Shop\ShopCheckout;
use App\Models\EcommerceOrdine;
use App\Models\EcommerceProdotto;
use App\Services\Ecommerce\CartService;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Tests\TestCase;

class PublicShopPhase2Test extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('shop.enabled', true);
        Config::set('services.ecommerce.payment_stub', true);
    }

    public function test_cart_service_stores_items_in_session(): void
    {
        $prodotto = EcommerceProdotto::factory()->create([
            'attivo' => true,
            'giacenza' => 5,
            'prezzo' => 25.00,
        ]);

        $cart = app(CartService::class);
        $cart->add($prodotto, 2);

        $this->assertSame(2, $cart->count());
        $this->assertSame(50.0, $cart->total());

        $items = $cart->items();
        $this->assertCount(1, $items);
        $this->assertSame($prodotto->id, $items->first()['prodotto']->id);

        $cart->remove($prodotto->id);
        $this->assertTrue($cart->isEmpty());
    }

    public function test_shop_cart_adds_product_and_shows_badge(): void
    {
        $prodotto = EcommerceProdotto::factory()->create([
            'nome' => 'Alternatore test',
            'attivo' => true,
            'giacenza' => 3,
        ]);

        Livewire::test(ShopCart::class)
            ->call('addToCart', $prodotto->id)
            ->assertSet('open', true)
            ->assertSee('Alternatore test')
            ->assertSee('1');
    }

    public function test_guest_checkout_stub_flow_creates_confirmed_order(): void
    {
        $prodotto = EcommerceProdotto::factory()->create([
            'attivo' => true,
            'giacenza' => 2,
            'prezzo' => 40.00,
        ]);

        app(CartService::class)->add($prodotto, 1);

        Livewire::test(ShopCheckout::class)
            ->set('nome', 'Mario Rossi')
            ->set('email', 'mario@example.com')
            ->set('telefono', '3331234567')
            ->call('proceedToPayment')
            ->assertSet('step', 2);

        $ordine = EcommerceOrdine::query()->latest('id')->first();
        $this->assertNotNull($ordine);
        $this->assertSame('40.00', $ordine->totale);
        $this->assertNull($ordine->user_id);

        Livewire::test(ShopCheckout::class)
            ->set('ordineId', $ordine->id)
            ->set('checkoutToken', $ordine->checkout_token)
            ->call('confirmStubPayment')
            ->assertSet('step', 3)
            ->assertSet('paymentComplete', true)
            ->assertSee('Ordine confermato')
            ->assertSee('#'.$ordine->id);

        $this->assertSame('confermato', $ordine->fresh()->stato->value);
    }

    public function test_shop_carrello_route_renders_full_page(): void
    {
        Config::set('shop.enabled', true);

        $prodotto = EcommerceProdotto::factory()->create([
            'attivo' => true,
            'giacenza' => 2,
            'nome' => 'Faro anteriore',
        ]);

        app(CartService::class)->add($prodotto, 1);

        $this->get(route('shop.carrello'))
            ->assertOk()
            ->assertSee('Carrello')
            ->assertSee('Faro anteriore')
            ->assertDontSee('Vai al carrello');
    }

    public function test_shop_index_shows_add_to_cart_button(): void
    {
        EcommerceProdotto::factory()->create([
            'nome' => 'Faro posteriore',
            'attivo' => true,
            'giacenza' => 1,
        ]);

        $this->get(route('shop.index'))
            ->assertOk()
            ->assertSee('Aggiungi al carrello');
    }
}
