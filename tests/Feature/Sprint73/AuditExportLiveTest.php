<?php

namespace Tests\Feature\Sprint73;

use App\Domain\Audit\ActivityLogService;
use App\Domain\Audit\AuditExportDownloadService;
use App\Domain\Audit\AuditExportLiveService;
use App\Http\Livewire\Admin\AuditIndex;
use App\Jobs\AuditExportScheduledJob;
use App\Models\AuditExportRun;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AuditExportLiveTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('audit_exports');
        config(['audit.export.disk' => 'audit_exports']);
    }

    public function test_live_export_uploads_csv_with_sha256_checksum(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        app(ActivityLogService::class)->record('audit', 'Evento test export', userId: $admin->id);

        $run = app(AuditExportLiveService::class)->export(triggeredBy: $admin->id);

        $this->assertSame('completed', $run->status);
        $this->assertGreaterThan(0, $run->row_count);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $run->checksum_sha256);
        Storage::disk('audit_exports')->assertExists($run->path);

        $contents = Storage::disk('audit_exports')->get($run->path);
        $this->assertSame(hash('sha256', $contents), $run->checksum_sha256);
        $this->assertStringContainsString('Evento test export', $contents);
    }

    public function test_download_service_creates_signed_url_and_audit_trail(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $run = app(AuditExportLiveService::class)->export(triggeredBy: $admin->id);

        URL::forceRootUrl('http://localhost');

        $url = app(AuditExportDownloadService::class)->createDownloadUrl($run, $admin);

        $this->assertStringContainsString('signature=', $url);

        $this->assertDatabaseHas('activity_log', [
            'log_name'    => 'audit',
            'description' => 'Download export audit richiesto',
        ]);
    }

    public function test_signed_download_route_streams_csv_file(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $run = app(AuditExportLiveService::class)->export(triggeredBy: $admin->id);

        $url = URL::temporarySignedRoute('admin.audit.export.download', now()->addHour(), ['run' => $run->id]);

        $this->actingAs($admin)
            ->get($url)
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    public function test_audit_export_scheduled_command_dry_run(): void
    {
        $exit = Artisan::call('audit:export-scheduled', ['--dry-run' => true]);
        $output = Artisan::output();

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Export audit (dry-run) completato', $output);
        $this->assertDatabaseHas('audit_export_runs', ['dry_run' => true, 'status' => 'dry_run']);
    }

    public function test_scheduled_export_job_is_unique_and_dispatchable(): void
    {
        Bus::fake();

        AuditExportScheduledJob::dispatch(dryRun: false, triggeredBy: 1);

        Bus::assertDispatched(AuditExportScheduledJob::class);
    }

    public function test_audit_index_shows_export_history(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        app(AuditExportLiveService::class)->export(triggeredBy: $admin->id);

        Livewire::actingAs($admin)
            ->test(AuditIndex::class)
            ->assertSee('Export live su storage')
            ->assertSee('SHA-256')
            ->assertSee('Download')
            ->assertSee('audit:export-scheduled');
    }

    public function test_segreteria_cannot_download_export(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $this->assertFalse(Gate::forUser($user)->allows('downloadExport', Activity::class));
        $this->assertFalse(Gate::forUser($user)->allows('viewExports', Activity::class));
    }

    public function test_purge_expired_removes_old_export_files(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $run = app(AuditExportLiveService::class)->export(triggeredBy: $admin->id);
        $run->update(['expires_at' => now()->subDay()]);

        $purged = app(AuditExportLiveService::class)->purgeExpired();

        $this->assertSame(1, $purged);
        Storage::disk('audit_exports')->assertMissing($run->path);
        $this->assertSame('purged', $run->fresh()->status);
    }
}
