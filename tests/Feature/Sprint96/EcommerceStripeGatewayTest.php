<?php

namespace Tests\Feature\Sprint96;

use App\Domain\Ecommerce\Contracts\StripeCheckoutClientInterface;
use App\Domain\Ecommerce\EcommerceCheckoutService;
use App\Domain\Ecommerce\EcommercePaymentGatewayService;
use App\Domain\Ecommerce\EcommercePaymentRuntimeModeService;
use App\Domain\Ecommerce\EcommerceService;
use App\Enums\OrdineEcommerceStato;
use App\Http\Livewire\Segreteria\Ecommerce\EcommerceCarrello;
use App\Http\Livewire\Segreteria\Ecommerce\EcommerceOrdineShow;
use App\Models\EcommerceOrdine;
use App\Models\EcommerceProdotto;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class EcommerceStripeGatewayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.ecommerce.payment_stub', true);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_payment_runtime_defaults_to_stub(): void
    {
        $runtime = app(EcommercePaymentRuntimeModeService::class);

        $this->assertTrue($runtime->isStub());
        $this->assertSame('Pagamenti stub', $runtime->modeDisplayLabel());
    }

    public function test_payment_runtime_live_requires_stripe_preflight(): void
    {
        Config::set('services.ecommerce.payment_stub', false);
        Config::set('services.stripe.secret', '');
        Config::set('services.stripe.webhook_secret', '');

        $runtime = app(EcommercePaymentRuntimeModeService::class);

        $this->assertFalse($runtime->isStub());
        $this->assertFalse($runtime->preflightReady());
        $this->assertCount(4, $runtime->preflightChecklist());
    }

    public function test_gateway_stub_initiate_returns_checkout_token(): void
    {
        $ordine = $this->ordineBozza(40.00);

        $result = app(EcommercePaymentGatewayService::class)->initiatePayment($ordine, 'bonifico');

        $this->assertSame('stub', $result['gateway']);
        $this->assertNotEmpty($result['checkout_token']);
        $this->assertSame(32, strlen((string) $result['checkout_token']));

        $ordine->refresh();
        $this->assertSame('stub', $ordine->payment_gateway);
        $this->assertSame($result['checkout_token'], $ordine->checkout_token);
    }

    public function test_gateway_live_creates_stripe_checkout_session(): void
    {
        Config::set('services.ecommerce.payment_stub', false);
        Config::set('services.stripe.secret', 'sk_test_s96');
        Config::set('services.stripe.webhook_secret', 'whsec_s96');

        $mock = Mockery::mock(StripeCheckoutClientInterface::class);
        $mock->shouldReceive('createCheckoutSession')
            ->once()
            ->with(Mockery::on(fn (array $params) => ($params['mode'] ?? '') === 'payment'
                && ($params['metadata']['ordine_id'] ?? '') !== ''))
            ->andReturn((object) [
                'id'  => 'cs_test_s96',
                'url' => 'https://checkout.stripe.com/c/pay/cs_test_s96',
            ]);
        $this->app->instance(StripeCheckoutClientInterface::class, $mock);

        $ordine = $this->ordineBozza(55.50);
        $result = app(EcommercePaymentGatewayService::class)->initiatePayment($ordine, 'stripe');

        $this->assertSame('stripe', $result['gateway']);
        $this->assertSame('cs_test_s96', $result['stripe_session_id']);
        $this->assertStringContainsString('checkout.stripe.com', (string) $result['checkout_url']);

        $ordine->refresh();
        $this->assertSame('cs_test_s96', $ordine->stripe_checkout_session_id);
    }

    public function test_webhook_stub_payload_confirms_stripe_order(): void
    {
        Config::set('services.stripe.webhook_secret', '');

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $ordine = EcommerceOrdine::create([
            'user_id'                      => $user->id,
            'stato'                        => OrdineEcommerceStato::PagamentoInAttesa,
            'totale'                       => 25,
            'righe'                        => [],
            'payment_gateway'              => 'stripe',
            'stripe_checkout_session_id'   => 'cs_webhook_s96',
        ]);

        $this->postJson('/webhooks/stripe/ecommerce', [
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_webhook_s96',
                ],
            ],
        ])->assertOk()->assertSee('ok');

        $this->assertSame(OrdineEcommerceStato::Confermato, $ordine->fresh()->stato);
        $this->assertNotNull($ordine->fresh()->confermato_at);
    }

    public function test_webhook_rejects_invalid_signature_when_secret_set(): void
    {
        Config::set('services.stripe.webhook_secret', 'whsec_test_secret');

        $this->call(
            'POST',
            '/webhooks/stripe/ecommerce',
            [],
            [],
            [],
            ['HTTP_Stripe-Signature' => 'invalid', 'CONTENT_TYPE' => 'application/json'],
            '{"type":"checkout.session.completed"}',
        )->assertStatus(400);
    }

    public function test_ordine_show_displays_payment_stub_badge(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $ordine = $this->ordineBozza(10);

        Livewire::actingAs($user)
            ->test(EcommerceOrdineShow::class, ['ordine' => $ordine])
            ->assertSee('Pagamenti stub')
            ->assertSee('Checkout sicuro')
            ->assertSee('Avvia checkout');
    }

    public function test_carrello_displays_live_stripe_preflight_warning(): void
    {
        Config::set('services.ecommerce.payment_stub', false);
        Config::set('services.stripe.secret', '');
        Config::set('services.stripe.webhook_secret', '');

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        app(EcommerceService::class)->addToCart(EcommerceProdotto::factory()->create(['giacenza' => 3, 'prezzo' => 12])->id, 1);

        Livewire::actingAs($user)
            ->test(EcommerceCarrello::class)
            ->assertSee('Stripe sandbox')
            ->assertSee('Stripe non configurato');
    }

    public function test_checkout_service_stub_flow_unchanged_for_sprint_61(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $ordine = $this->ordineBozza(20);

        $checkout = app(EcommerceCheckoutService::class);
        $ordine = $checkout->avviaCheckout($ordine, 'bonifico', 'Sprint 96 regression');
        $token = $ordine->checkout_token;

        $ordine = $checkout->confermaPagamentoStub($ordine, (string) $token, $user->id);

        $this->assertSame(OrdineEcommerceStato::Confermato, $ordine->stato);
    }

    public function test_sprint_96_audit_notes_document_m96_gap(): void
    {
        $path = base_path('docs/SPRINT-96-AUDIT-NOTES.md');
        $content = file_get_contents($path);

        $this->assertFileExists($path);
        $this->assertStringContainsString('M-96-1', $content);
        $this->assertStringContainsString('EcommercePaymentGatewayService', $content);
        $this->assertStringContainsString('ECOMMERCE_PAYMENT_STUB', $content);
    }

    private function ordineBozza(float $totale): EcommerceOrdine
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        return EcommerceOrdine::create([
            'user_id' => $user->id,
            'stato'   => OrdineEcommerceStato::Bozza,
            'totale'  => $totale,
            'righe'   => [],
        ]);
    }
}
