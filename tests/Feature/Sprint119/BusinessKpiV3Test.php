<?php

namespace Tests\Feature\Sprint119;

use App\Domain\Dashboard\BusinessKpiAlertService;
use App\Domain\Dashboard\BusinessKpiExportService;
use App\Enums\NotificationEvent;
use App\Enums\OrdineEcommerceStato;
use App\Http\Livewire\Admin\AuditIndex;
use App\Http\Livewire\Segreteria\Dashboard;
use App\Mail\BusinessKpiBreachMail;
use App\Models\EcommerceOrdine;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BusinessKpiV3Test extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget(BusinessKpiAlertService::CACHE_KEY_LAST_CHECK);
        Config::set('dashboard.business_kpi.thresholds.ordini_confermati', ['warn' => 5, 'alert' => 1]);
    }

    public function test_export_csv_includes_metrics_and_thresholds(): void
    {
        $csv = app(BusinessKpiExportService::class)->toCsv('last_7_days');

        $this->assertStringContainsString('ordini_confermati', $csv);
        $this->assertStringContainsString('threshold_status', $csv);
        $this->assertStringContainsString('vfu_accettate', $csv);
        $this->assertStringContainsString('revenue_eur', $csv);
    }

    public function test_alert_service_detects_breach_when_orders_below_alert(): void
    {
        Config::set('dashboard.business_kpi.thresholds', [
            'ordini_confermati' => ['warn' => 5, 'alert' => 1],
            'vfu_accettate'     => ['warn' => 8, 'alert' => 2],
            'magazzino_kg'      => ['warn' => 500, 'alert' => 100],
            'revenue_eur'       => ['warn' => 500, 'alert' => 100],
        ]);

        $result = app(BusinessKpiAlertService::class)->check('last_7_days', notify: false);

        $this->assertSame('fail', $result['overall']);
        $this->assertTrue(collect($result['breaches'])->contains(
            fn (array $b) => $b['key'] === 'ordini_confermati' && $b['status'] === 'alert',
        ));
        $this->assertNotNull(app(BusinessKpiAlertService::class)->lastCheck());
    }

    public function test_alert_sends_mail_on_notify_when_breach(): void
    {
        Mail::fake();
        Config::set('notifications.live', true);
        Config::set('mail.default', 'smtp');
        Config::set('dashboard.business_kpi.thresholds', [
            'ordini_confermati' => ['warn' => 5, 'alert' => 1],
            'vfu_accettate'     => ['warn' => 8, 'alert' => 2],
            'magazzino_kg'      => ['warn' => 500, 'alert' => 100],
            'revenue_eur'       => ['warn' => 500, 'alert' => 100],
        ]);

        $result = app(BusinessKpiAlertService::class)->check('last_7_days', notify: true);

        $this->assertTrue($result['notified']);
        Mail::assertSent(BusinessKpiBreachMail::class);
    }

    public function test_alert_ok_when_metrics_above_thresholds(): void
    {
        Config::set('dashboard.business_kpi.thresholds', [
            'ordini_confermati' => ['warn' => -1, 'alert' => -1],
            'vfu_accettate'     => ['warn' => -1, 'alert' => -1],
            'magazzino_kg'      => ['warn' => -1, 'alert' => -1],
            'revenue_eur'       => ['warn' => -1, 'alert' => -1],
        ]);

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        EcommerceOrdine::create([
            'user_id'       => $user->id,
            'stato'         => OrdineEcommerceStato::Confermato,
            'totale'        => 200,
            'righe'         => [],
            'confermato_at' => now(),
        ]);

        $result = app(BusinessKpiAlertService::class)->check('last_7_days', notify: false);

        $this->assertSame('ok', $result['overall']);
        $this->assertSame([], $result['breaches']);
    }

    public function test_kpi_business_check_command_outputs_json(): void
    {
        Artisan::call('kpi:business-check', ['--json' => true]);

        $output = Artisan::output();
        $this->assertStringContainsString('"overall"', $output);
        $this->assertStringContainsString('"period_key"', $output);
    }

    public function test_dashboard_renders_kpi_v3_export_and_alert_section(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertSee('KPI business v3')
            ->assertSee('Export CSV')
            ->assertSee('kpi:business-check');
    }

    public function test_admin_audit_shows_kpi_alert_when_cached(): void
    {
        Role::findOrCreate('admin');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Cache::put(BusinessKpiAlertService::CACHE_KEY_LAST_CHECK, [
            'checked_at'   => now()->toIso8601String(),
            'overall'      => 'warn',
            'period_label' => 'Ultimi 7 giorni',
        ], now()->addHour());

        Livewire::actingAs($admin)
            ->test(AuditIndex::class)
            ->assertSee('KPI business v3')
            ->assertSee('kpi:business-check');
    }

    public function test_kpi_v3_doc_documents_export_and_alert(): void
    {
        $content = file_get_contents(base_path('docs/KPI-BUSINESS-DASHBOARD-V3.md'));

        $this->assertStringContainsString('BusinessKpiExportService', $content);
        $this->assertStringContainsString('kpi:business-check', $content);
        $this->assertStringContainsString('KPI_BUSINESS_ORDINI_ALERT', $content);
    }

    public function test_notification_event_registered_for_business_kpi(): void
    {
        $this->assertSame('KPI business sotto soglia', NotificationEvent::BusinessKpiBreach->label());
        $events = config('notifications.events', []);
        $this->assertTrue($events[NotificationEvent::BusinessKpiBreach->value]['enabled'] ?? false);
    }

    public function test_schedule_includes_kpi_business_check(): void
    {
        $content = file_get_contents(base_path('routes/console.php'));

        $this->assertStringContainsString("Schedule::command('kpi:business-check --notify')", $content);
    }
}
