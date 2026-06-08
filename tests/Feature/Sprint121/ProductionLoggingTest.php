<?php

namespace Tests\Feature\Sprint121;

use App\Domain\Logging\ApplicationLogQueryService;
use App\Http\Livewire\Admin\LogsIndex;
use App\Models\ApplicationLog;
use App\Models\User;
use App\Support\Logging\RequestContext;
use App\Support\Logging\StructuredLogService;
use Livewire\Livewire;
use Tests\TestCase;

class ProductionLoggingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        RequestContext::reset();
    }

    public function test_middleware_sets_x_request_id_header(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $response = $this->actingAs($admin)->get(route('admin.logs'));

        $response->assertOk();
        $this->assertTrue($response->headers->has('X-Request-Id'));
        $this->assertNotSame('', $response->headers->get('X-Request-Id'));
    }

    public function test_structured_log_service_persists_application_log(): void
    {
        RequestContext::setTraceId('trace-test-001');

        app(StructuredLogService::class)->info(
            'rentri',
            'test_action',
            'Messaggio di test RENTRI',
            [
                'entity_type' => 'rentri_transazione',
                'entity_id'   => 42,
                'outcome'     => 'success',
                'duration_ms' => 120,
                'context'     => ['endpoint' => '/health'],
            ],
        );

        $this->assertDatabaseHas('application_logs', [
            'trace_id' => 'trace-test-001',
            'module'   => 'rentri',
            'action'   => 'test_action',
            'level'    => 'info',
            'outcome'  => 'success',
        ]);
    }

    public function test_structured_log_masks_sensitive_values(): void
    {
        app(StructuredLogService::class)->info(
            'security',
            'mask_test',
            'Test mascheramento',
            [
                'context' => [
                    'api_key'       => 'sk_live_abcdefghijklmnop',
                    'authorization' => 'Bearer secret-token-value',
                ],
            ],
        );

        $log = ApplicationLog::query()->firstOrFail();
        $context = $log->context ?? [];

        $this->assertStringNotContainsString('sk_live_abcdefghijklmnop', json_encode($context));
        $this->assertStringNotContainsString('secret-token-value', json_encode($context));
    }

    public function test_admin_can_access_logs_index_with_filters(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        app(StructuredLogService::class)->info(
            'stripe',
            'webhook_test',
            'Webhook Stripe test',
            ['outcome' => 'success'],
        );

        $this->actingAs($admin)
            ->get(route('admin.logs'))
            ->assertOk()
            ->assertSee('Log applicativi')
            ->assertSee('Webhook Stripe test');

        Livewire::actingAs($admin)
            ->test(LogsIndex::class)
            ->set('module', 'stripe')
            ->assertSee('Stripe')
            ->assertSee('webhook_test');
    }

    public function test_segreteria_cannot_access_logs(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('admin.logs'))
            ->assertForbidden();

        Livewire::actingAs($user)
            ->test(LogsIndex::class)
            ->assertForbidden();
    }

    public function test_logs_filter_by_trace_id(): void
    {
        RequestContext::setTraceId('trace-filter-xyz');

        app(StructuredLogService::class)->warning(
            'gps',
            'probe',
            'Probe GPS filtro trace',
            ['outcome' => 'failure'],
        );

        $results = app(ApplicationLogQueryService::class)->list([
            'trace_id' => 'trace-filter-xyz',
        ]);

        $this->assertSame(1, $results->total());
        $this->assertSame('trace-filter-xyz', $results->items()[0]->trace_id);
    }

    public function test_logs_purge_command_deletes_old_records(): void
    {
        ApplicationLog::query()->create([
            'trace_id'   => 'old-trace',
            'level'      => 'info',
            'module'     => 'rentri',
            'channel'    => 'rentri',
            'action'     => 'legacy',
            'message'    => 'Vecchio log',
            'demo_mode'  => false,
            'created_at' => now()->subDays(120),
        ]);

        ApplicationLog::query()->create([
            'trace_id'   => 'new-trace',
            'level'      => 'info',
            'module'     => 'rentri',
            'channel'    => 'rentri',
            'action'     => 'recent',
            'message'    => 'Log recente',
            'demo_mode'  => false,
            'created_at' => now()->subDays(5),
        ]);

        $this->artisan('logs:purge', ['--days' => 90])
            ->assertSuccessful();

        $this->assertDatabaseMissing('application_logs', ['trace_id' => 'old-trace']);
        $this->assertDatabaseHas('application_logs', ['trace_id' => 'new-trace']);
    }

    public function test_logs_health_command_reports_ok(): void
    {
        $this->artisan('logs:health')
            ->assertSuccessful()
            ->expectsOutputToContain('Health check logging');
    }

    public function test_admin_can_export_logs_csv(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        app(StructuredLogService::class)->info(
            'business',
            'export_test',
            'Riga export CSV',
            ['outcome' => 'success'],
        );

        $response = $this->actingAs($admin)->get(route('admin.logs.export'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('export_test', $response->streamedContent());
    }

    public function test_logs_index_detail_shows_context(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        app(StructuredLogService::class)->error(
            'integration',
            'detail_test',
            'Errore integrazione',
            [
                'outcome' => 'failure',
                'context' => ['provider' => 'gps'],
            ],
        );

        $log = ApplicationLog::query()->firstOrFail();

        Livewire::actingAs($admin)
            ->test(LogsIndex::class)
            ->call('showDetail', $log->id)
            ->assertSee('Dettaglio #'.$log->id)
            ->assertSee('detail_test')
            ->assertSee('gps');
    }
}
