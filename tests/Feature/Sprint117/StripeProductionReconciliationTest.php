<?php

namespace Tests\Feature\Sprint117;

use App\Domain\Ecommerce\StripeDisputeStubService;
use App\Domain\Ecommerce\StripeProductionSwitchService;
use App\Domain\Ecommerce\StripeReconciliationReportService;
use App\Enums\OrdineEcommerceStato;
use App\Http\Livewire\Segreteria\Ecommerce\EcommerceIndex;
use App\Models\EcommerceOrdine;
use App\Models\StripeWebhookEvent;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Tests\TestCase;

class StripeProductionReconciliationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.ecommerce.payment_stub', true);
    }

    private function configureProductionStripe(): void
    {
        Config::set('services.ecommerce.payment_stub', false);
        Config::set('services.stripe.live_mode', true);
        Config::set('services.stripe.secret', 'sk_live_s117');
        Config::set('services.stripe.webhook_secret', 'whsec_s117');
        Config::set('services.stripe.currency', 'eur');
        Config::set('services.stripe.dispute_stub', true);
    }

    public function test_stub_mode_switch_checklist_passes_dry_run(): void
    {
        $switch = app(StripeProductionSwitchService::class);
        $report = $switch->dryRunReport();

        $this->assertTrue($report['passed']);
        $this->assertFalse($report['production_active']);
    }

    public function test_production_switch_requires_sk_live_and_webhook(): void
    {
        Config::set('services.ecommerce.payment_stub', false);
        Config::set('services.stripe.live_mode', true);
        Config::set('services.stripe.secret', 'sk_test_wrong');
        Config::set('services.stripe.webhook_secret', '');
        Config::set('services.stripe.currency', 'eur');

        $switch = app(StripeProductionSwitchService::class);

        $this->assertFalse($switch->canSwitchToProduction());
        $keys = array_column($switch->unifiedChecklist(), 'key');
        $this->assertContains('preflight_stripe_key_mode', $keys);
        $this->assertContains('preflight_stripe_webhook', $keys);
    }

    public function test_can_switch_when_production_env_fully_configured(): void
    {
        $this->configureProductionStripe();

        $switch = app(StripeProductionSwitchService::class);

        $this->assertTrue($switch->canSwitchToProduction());
        $this->assertTrue($switch->isProductionActive());
    }

    public function test_reconciliation_report_matched_and_crm_only(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $matched = EcommerceOrdine::create([
            'user_id'                    => $user->id,
            'stato'                      => OrdineEcommerceStato::Confermato,
            'totale'                     => 50.00,
            'righe'                      => [],
            'payment_gateway'            => 'stripe',
            'stripe_checkout_session_id' => 'cs_matched_s117',
        ]);

        EcommerceOrdine::create([
            'user_id'                    => $user->id,
            'stato'                      => OrdineEcommerceStato::Confermato,
            'totale'                     => 20.00,
            'righe'                      => [],
            'payment_gateway'            => 'stripe',
            'stripe_checkout_session_id' => 'cs_crm_only_s117',
        ]);

        StripeWebhookEvent::create([
            'stripe_event_id'     => 'evt_matched_s117',
            'event_type'          => 'checkout.session.completed',
            'ecommerce_ordine_id' => $matched->id,
            'checkout_session_id' => 'cs_matched_s117',
            'reconciliation'      => [
                'amount_eur'  => 50.00,
                'environment' => 'sandbox',
            ],
            'processed_at' => now(),
        ]);

        $summary = app(StripeReconciliationReportService::class)->summary(30);

        $this->assertSame(1, $summary['matched']);
        $this->assertSame(1, $summary['crm_only']);
        $this->assertSame(50.0, $summary['amount_matched_eur']);
    }

    public function test_dispute_stub_handles_webhook_event(): void
    {
        Config::set('services.stripe.dispute_stub', true);

        $result = app(StripeDisputeStubService::class)->handleDisputeEvent([
            'id'   => 'evt_dispute_s117',
            'type' => 'charge.dispute.created',
            'data' => [
                'object' => [
                    'id'     => 'dp_s117',
                    'status' => 'needs_response',
                    'reason' => 'fraudulent',
                    'amount' => 5000,
                ],
            ],
        ]);

        $this->assertTrue($result['handled']);
        $this->assertSame('dp_s117', $result['dispute_id']);
        $this->assertDatabaseHas('stripe_webhook_events', [
            'stripe_event_id' => 'evt_dispute_s117',
            'event_type'      => 'charge.dispute.created',
        ]);
    }

    public function test_stripe_production_switch_check_command_outputs_report(): void
    {
        Config::set('services.ecommerce.payment_stub', false);
        Config::set('services.stripe.live_mode', true);
        Config::set('services.stripe.secret', '');
        Config::set('services.stripe.webhook_secret', '');

        $exitCode = Artisan::call('stripe:production-switch-check', ['--dry-run' => true]);

        $output = Artisan::output();
        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Stripe production switch', $output);
        $this->assertStringContainsString('STRIPE-RECONCILIATION-PRODUZIONE-RUNBOOK.md', $output);
    }

    public function test_ecommerce_hub_shows_stripe_switch_and_reconciliation(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(EcommerceIndex::class)
            ->assertSee('Stripe produzione')
            ->assertSee('stripe:production-switch-check')
            ->assertSee('Riconciliazione pagamenti')
            ->assertSee('Export CSV')
            ->assertSee('Workflow dispute');
    }

    public function test_runbook_documents_reconciliation_and_dispute_stub(): void
    {
        $content = file_get_contents(base_path('docs/STRIPE-RECONCILIATION-PRODUZIONE-RUNBOOK.md'));

        $this->assertStringContainsString('ECOMMERCE_PAYMENT_STUB', $content);
        $this->assertStringContainsString('matched', $content);
        $this->assertStringContainsString('charge.dispute.created', $content);
        $this->assertStringContainsString('stripe:production-switch-check', $content);
    }

    public function test_rollback_steps_include_payment_stub(): void
    {
        $steps = app(StripeProductionSwitchService::class)->rollbackSteps();

        $actions = array_column($steps, 'action');
        $this->assertTrue(
            collect($actions)->contains(fn (string $a): bool => str_contains($a, 'ECOMMERCE_PAYMENT_STUB=true')),
        );
    }

    public function test_reconciliation_csv_export_contains_header(): void
    {
        $csv = app(StripeReconciliationReportService::class)->toCsv(30);

        $this->assertStringContainsString('ordine_id,stripe_event_id', $csv);
        $this->assertStringContainsString('status', $csv);
    }

    public function test_fixture_documents_production_switch_contract(): void
    {
        $fixture = json_decode(
            file_get_contents(base_path('tests/fixtures/stripe/production-switch.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertSame(117, $fixture['sprint']);
        $this->assertSame('sk_live_', $fixture['env_required']['STRIPE_KEY_PREFIX']);
        $this->assertContains('matched', $fixture['reconciliation_statuses']);
    }
}
