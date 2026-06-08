<?php

namespace Tests\Feature\Sprint13;

use App\Domain\Ecommerce\EcommerceService;
use App\Enums\OrdineEcommerceStato;
use App\Http\Livewire\Segreteria\Ecommerce\EcommerceCarrello;
use App\Http\Livewire\Segreteria\Ecommerce\EcommerceIndex;
use App\Http\Livewire\Segreteria\Ecommerce\EcommerceOrdineShow;
use App\Http\Livewire\Segreteria\Ecommerce\EcommerceProdottoShow;
use App\Models\EcommerceOrdine;
use App\Models\EcommerceProdotto;
use App\Models\User;
use App\Models\VfuRegistration;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EcommerceHttpTest extends TestCase
{
    public function test_segreteria_can_access_catalog_and_product_detail(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $prodotto = EcommerceProdotto::factory()->create([
            'codice' => 'RIC-TEST',
            'nome'   => 'Alternatore usato',
        ]);

        $this->actingAs($user)
            ->get(route('segreteria.ecommerce'))
            ->assertOk()
            ->assertSee('E-commerce ricambi')
            ->assertSee('RIC-TEST');

        $this->actingAs($user)
            ->get(route('segreteria.ecommerce.prodotti.show', $prodotto))
            ->assertOk()
            ->assertSee('Alternatore usato');
    }

    public function test_segreteria_cart_and_ordine_bozza_flow(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $prodotto = EcommerceProdotto::factory()->create([
            'nome'     => 'Faro anteriore',
            'prezzo'   => 40.00,
            'giacenza' => 4,
        ]);

        Livewire::actingAs($user)
            ->test(EcommerceProdottoShow::class, ['prodotto' => $prodotto])
            ->set('qty', 2)
            ->call('aggiungiAlCarrello')
            ->assertHasNoErrors();

        $this->actingAs($user)
            ->get(route('segreteria.ecommerce.carrello'))
            ->assertOk()
            ->assertSee('Faro anteriore');

        Livewire::actingAs($user)
            ->test(EcommerceCarrello::class)
            ->call('creaOrdineBozza')
            ->assertHasNoErrors()
            ->assertRedirect(route('segreteria.ecommerce.ordini.show', EcommerceOrdine::first()));

        $ordine = EcommerceOrdine::firstOrFail();
        $this->assertSame(OrdineEcommerceStato::Bozza, $ordine->stato);
        $this->assertSame(80.0, (float) $ordine->totale);
        $this->assertSame(2, $prodotto->fresh()->giacenza);

        Livewire::actingAs($user)
            ->test(EcommerceOrdineShow::class, ['ordine' => $ordine])
            ->assertSee('Ordine #'.$ordine->id)
            ->assertSee('Faro anteriore');
    }

    public function test_prodotto_with_vfu_link_visible_in_catalog(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $vfu = VfuRegistration::factory()->create(['targa' => 'AB123CD']);
        $prodotto = EcommerceProdotto::factory()->create([
            'vfu_registration_id' => $vfu->id,
        ]);

        Livewire::actingAs($user)
            ->test(EcommerceIndex::class)
            ->assertSee('AB123CD');

        Livewire::actingAs($user)
            ->test(EcommerceProdottoShow::class, ['prodotto' => $prodotto])
            ->assertSee('Provenienza VFU')
            ->assertSee('AB123CD');
    }

    public function test_operatore_cannot_access_segreteria_ecommerce_but_can_ricambi(): void
    {
        Role::findOrCreate('operatore');
        $user = User::where('email', 'operatore@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('segreteria.ecommerce'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('operatore.ricambi'))
            ->assertOk();

        $this->assertFalse(Gate::forUser($user)->allows('create', EcommerceOrdine::class));
    }

    public function test_policy_allows_segreteria_view_and_create(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $prodotto = EcommerceProdotto::factory()->create();
        $ordine = EcommerceOrdine::create([
            'user_id' => $user->id,
            'stato'   => OrdineEcommerceStato::Bozza,
            'totale'  => 10,
            'righe'   => [],
        ]);

        $this->assertTrue(Gate::forUser($user)->allows('viewAny', EcommerceProdotto::class));
        $this->assertTrue(Gate::forUser($user)->allows('view', $prodotto));
        $this->assertTrue(Gate::forUser($user)->allows('create', EcommerceOrdine::class));
        $this->assertTrue(Gate::forUser($user)->allows('view', $ordine));
    }
}
