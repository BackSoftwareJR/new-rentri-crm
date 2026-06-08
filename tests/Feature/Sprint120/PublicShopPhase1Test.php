<?php

namespace Tests\Feature\Sprint120;

use App\Models\EcommerceProdotto;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class PublicShopPhase1Test extends TestCase
{
    public function test_shop_returns_404_when_disabled(): void
    {
        Config::set('shop.enabled', false);

        $this->get(route('shop.index'))->assertNotFound();
    }

    public function test_guest_can_browse_active_products_when_enabled(): void
    {
        Config::set('shop.enabled', true);

        $prodotto = EcommerceProdotto::factory()->create([
            'codice' => 'SHOP-001',
            'nome'   => 'Faro anteriore shop',
            'attivo' => true,
        ]);

        EcommerceProdotto::factory()->create([
            'codice' => 'SHOP-OFF',
            'nome'   => 'Prodotto nascosto',
            'attivo' => false,
        ]);

        $this->get(route('shop.index'))
            ->assertOk()
            ->assertSee('Ricambi usati')
            ->assertSee('Faro anteriore shop')
            ->assertDontSee('Prodotto nascosto');

        $this->get(route('shop.prodotto', $prodotto))
            ->assertOk()
            ->assertSee('Faro anteriore shop')
            ->assertSee('Contatta per acquistare');
    }

    public function test_inactive_product_detail_returns_404(): void
    {
        Config::set('shop.enabled', true);

        $prodotto = EcommerceProdotto::factory()->create(['attivo' => false]);

        $this->get(route('shop.prodotto', $prodotto))->assertNotFound();
    }
}
