<?php

namespace Tests\Feature\Sprint103;

use App\Domain\Ecommerce\EcommercePaymentRuntimeModeService;
use App\Domain\Ecommerce\StripeProductionPreflightService;
use App\Enums\NotificationEvent;
use App\Enums\OrdineEcommerceStato;
use App\Http\Livewire\Segreteria\Ecommerce\EcommerceCarrello;
use App\Mail\EcommerceStripeReconciliationMail;
use App\Models\EcommerceOrdine;
use App\Models\EcommerceProdotto;
use App\Models\StripeWebhookEvent;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class StripeProductionPreflightTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.ecommerce.payment_stub', true);
    }

    public function test_stripe_preflight_sandbox_requires_sk_test(): void
    {
        Config::set('services.stripe.secret', 'sk_test_s103');
        Config::set('services.stripe.webhook_secret', 'whsec_s103');
        Config::set('services.stripe.currency', 'eur');
        Config::set('services.stripe.live_mode', false);

        $preflight = app(StripeProductionPreflightService::class);

        $this->assertFalse($preflight->isProductionEnvironment());
        $this->assertTrue($preflight->isReady());
        $this->assertSame('https://dashboard.stripe.com/test/', $preflight->dashboardUrl());
    }

    public function test_stripe_preflight_production_requires_sk_live(): void
    {
        Config::set('services.stripe.live_mode', true);
        Config::set('services.stripe.secret', 'sk_live_s103');
        Config::set('services.stripe.webhook_secret', 'whsec_prod_s103');
        Config::set('services.stripe.currency', 'eur');

        $preflight = app(StripeProductionPreflightService::class);

        $this->assertTrue($preflight->isProductionEnvironment());
        $this->assertTrue($preflight->isReady());
    }

    public function test_payment_runtime_shows_sandbox_vs_production_badge(): void
    {
        Config::set('services.ecommerce.payment_stub', false);
        Config::set('services.stripe.secret', 'sk_test_s103');
        Config::set('services.stripe.webhook_secret', 'whsec_s103');
        Config::set('services.stripe.currency', 'eur');

        $runtime = app(EcommercePaymentRuntimeModeService::class);
        $this->assertSame('Stripe sandbox', $runtime->modeDisplayLabel());
        $this->assertTrue($runtime->isStripeSandbox());

        Config::set('services.stripe.live_mode', true);
        Config::set('services.stripe.secret', 'sk_live_s103');

        $runtimeProd = app(EcommercePaymentRuntimeModeService::class);
        $this->assertSame('Stripe produzione', $runtimeProd->modeDisplayLabel());
    }

    public function test_webhook_idempotency_skips_duplicate_event(): void
    {
        Config::set('services.stripe.webhook_secret', '');
        Config::set('notifications.live', false);

        Log::shouldReceive('shareContext')->andReturnSelf();
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('info');
        Log::shouldReceive('log');

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $ordine = EcommerceOrdine::create([
            'user_id'                    => $user->id,
            'stato'                      => OrdineEcommerceStato::PagamentoInAttesa,
            'totale'                     => 25,
            'righe'                      => [],
            'payment_gateway'            => 'stripe',
            'stripe_checkout_session_id' => 'cs_idempotent_s103',
        ]);

        $payload = [
            'id'   => 'evt_idempotent_s103',
            'type' => 'checkout.session.completed',
            'data' => ['object' => ['id' => 'cs_idempotent_s103']],
        ];

        $this->postJson('/webhooks/stripe/ecommerce', $payload)->assertOk();
        $this->postJson('/webhooks/stripe/ecommerce', $payload)->assertOk();

        $this->assertSame(OrdineEcommerceStato::Confermato, $ordine->fresh()->stato);
        $this->assertSame(1, StripeWebhookEvent::query()->where('stripe_event_id', 'evt_idempotent_s103')->count());
    }

    public function test_webhook_reconciliation_sends_mail_in_live_mode(): void
    {
        Mail::fake();
        Config::set('services.stripe.webhook_secret', '');
        Config::set('notifications.live', true);
        Config::set('mail.default', 'smtp');
        Config::set('services.stripe.secret', 'sk_test_s103');

        Log::shouldReceive('shareContext')->andReturnSelf();
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('info');
        Log::shouldReceive('log');

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        EcommerceOrdine::create([
            'user_id'                    => $user->id,
            'stato'                      => OrdineEcommerceStato::PagamentoInAttesa,
            'totale'                     => 33.50,
            'righe'                      => [],
            'payment_gateway'            => 'stripe',
            'stripe_checkout_session_id' => 'cs_mail_s103',
        ]);

        $this->postJson('/webhooks/stripe/ecommerce', [
            'id'   => 'evt_mail_s103',
            'type' => 'checkout.session.completed',
            'data' => ['object' => ['id' => 'cs_mail_s103']],
        ])->assertOk();

        Mail::assertSent(EcommerceStripeReconciliationMail::class, function (EcommerceStripeReconciliationMail $mail) {
            return $mail->hasTo(config('notifications.default_recipient'))
                && $mail->ordine->stripe_checkout_session_id === 'cs_mail_s103';
        });
    }

    public function test_carrello_displays_stripe_sandbox_preflight_and_dashboard(): void
    {
        Config::set('services.ecommerce.payment_stub', false);
        Config::set('services.stripe.secret', 'sk_test_s103');
        Config::set('services.stripe.webhook_secret', '');
        Config::set('services.stripe.currency', 'eur');

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        app(\App\Domain\Ecommerce\EcommerceService::class)
            ->addToCart(EcommerceProdotto::factory()->create(['giacenza' => 3, 'prezzo' => 12])->id, 1);

        Livewire::actingAs($user)
            ->test(EcommerceCarrello::class)
            ->assertSee('Stripe sandbox')
            ->assertSee('Dashboard Stripe')
            ->assertSee('STRIPE_WEBHOOK_SECRET');
    }

    public function test_fixture_documents_webhook_reconciliation_contract(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('tests/fixtures/stripe/webhook-reconciliation.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertSame('evt_s103_test', $contract['example_event']['id']);
        $this->assertSame('sk_live_', $contract['environments']['production']['stripe_key_prefix']);
    }

    public function test_sprint_103_audit_notes_document_stripe_production(): void
    {
        $content = file_get_contents(base_path('docs/SPRINT-103-AUDIT-NOTES.md'));

        $this->assertStringContainsString('StripeProductionPreflightService', $content);
        $this->assertStringContainsString('STRIPE_LIVE_MODE', $content);
        $this->assertStringContainsString('stripe_webhook_events', $content);
    }
}
