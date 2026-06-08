<?php

namespace Tests\Feature\Sprint61;

use App\Domain\Ecommerce\EcommerceCheckoutService;
use App\Domain\Ecommerce\EcommerceService;
use App\Enums\OrdineEcommerceStato;
use App\Http\Livewire\Segreteria\Ecommerce\EcommerceOrdineShow;
use App\Http\Livewire\Segreteria\Ecommerce\EcommerceProdottoShow;
use App\Models\EcommerceOrdine;
use App\Models\EcommerceProdotto;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class EcommerceCompletoTest extends TestCase
{
    public function test_segreteria_can_upload_product_image(): void
    {
        Storage::fake('public');

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $prodotto = EcommerceProdotto::factory()->create(['codice' => 'RIC-IMG-61']);

        $this->assertTrue(Gate::forUser($user)->allows('uploadImage', $prodotto));

        Livewire::actingAs($user)
            ->test(EcommerceProdottoShow::class, ['prodotto' => $prodotto])
            ->set('immagineUpload', UploadedFile::fake()->image('ricambio.jpg'))
            ->call('salvaImmagine')
            ->assertHasNoErrors();

        $prodotto->refresh();
        $this->assertNotNull($prodotto->immagine_path);
        Storage::disk('public')->assertExists($prodotto->immagine_path);
    }

    public function test_checkout_flow_bozza_to_confermato_with_token(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $prodotto = EcommerceProdotto::factory()->create(['giacenza' => 5, 'prezzo' => 20]);

        app(EcommerceService::class)->addToCart($prodotto->id, 2);
        $ordine = app(EcommerceService::class)->createOrdineBozza($user->id);
        $this->assertSame(OrdineEcommerceStato::Bozza, $ordine->stato);
        $this->assertSame(3, $prodotto->fresh()->giacenza);

        $checkout = app(EcommerceCheckoutService::class);
        $ordine = $checkout->avviaCheckout($ordine, 'bonifico', 'Ordine test Sprint 61');
        $token = $ordine->checkout_token;

        $this->assertSame(OrdineEcommerceStato::PagamentoInAttesa, $ordine->stato);
        $this->assertNotEmpty($token);

        $ordine = $checkout->confermaPagamentoStub($ordine, $token, $user->id);
        $this->assertSame(OrdineEcommerceStato::Confermato, $ordine->stato);
        $this->assertNotNull($ordine->confermato_at);
    }

    public function test_annulla_ordine_restores_stock(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $prodotto = EcommerceProdotto::factory()->create(['giacenza' => 4, 'prezzo' => 10]);

        app(EcommerceService::class)->addToCart($prodotto->id, 2);
        $ordine = app(EcommerceService::class)->createOrdineBozza($user->id);

        $this->assertSame(2, $prodotto->fresh()->giacenza);

        app(EcommerceCheckoutService::class)->annullaOrdine($ordine, $user->id);

        $this->assertSame(OrdineEcommerceStato::Annullato, $ordine->fresh()->stato);
        $this->assertSame(4, $prodotto->fresh()->giacenza);
    }

    public function test_ordine_show_checkout_ui_for_bozza_state(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $ordine = EcommerceOrdine::create([
            'user_id' => $user->id,
            'stato'   => OrdineEcommerceStato::Bozza,
            'totale'  => 50,
            'righe'   => [],
        ]);

        Livewire::actingAs($user)
            ->test(EcommerceOrdineShow::class, ['ordine' => $ordine])
            ->assertSee('Checkout sicuro')
            ->assertSee('Avvia checkout')
            ->call('avviaCheckout')
            ->assertHasNoErrors()
            ->assertSee('Pagamento in attesa');
    }

    public function test_invalid_checkout_token_rejected(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $ordine = EcommerceOrdine::create([
            'user_id'        => $user->id,
            'stato'          => OrdineEcommerceStato::PagamentoInAttesa,
            'totale'         => 30,
            'righe'          => [],
            'checkout_token' => 'abcdefghijklmnopqrstuvwxyz123456',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        app(EcommerceCheckoutService::class)->confermaPagamentoStub($ordine, str_repeat('x', 32), $user->id);
    }

    public function test_ciclo_6_piano_exists_with_sprint_61(): void
    {
        $path = base_path('docs/CICLO-6-PIANO-MODULI-COMPLETI.md');

        $this->assertFileExists($path);
        $content = file_get_contents($path);
        $this->assertStringContainsString('Sprint 61', $content);
        $this->assertStringContainsString('E-commerce', $content);
    }
}
