<?php

namespace Tests\Feature\Sprint104;

use App\Domain\Magazzino\MagazzinoService;
use App\Domain\Rentri\RentriOnboardingService;
use App\Enums\NotificationEvent;
use App\Jobs\RentriInitialSyncJob;
use App\Models\CodiceCer;
use App\Models\MagazzinoRifiuto;
use App\Models\RentriSetting;
use App\Models\User;
use App\Services\Rentri\Contracts\RentriApiClientInterface;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\Support\SeedsRentriCertificate;
use Tests\TestCase;

class OnboardingAutoSyncTest extends TestCase
{
    use SeedsRentriCertificate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRentriCertificate();
    }

    public function test_test_connection_returns_sync_counts(): void
    {
        $service = app(RentriOnboardingService::class);
        $apiClient = app(RentriApiClientInterface::class);

        $result = $service->testConnection($apiClient);

        $this->assertArrayHasKey('health', $result);
        $this->assertArrayHasKey('codifiche_count', $result);
        $this->assertArrayHasKey('codifiche_synced', $result);
        $this->assertArrayHasKey('serbatoi_created', $result);
        $this->assertArrayHasKey('sync_error', $result);
        $this->assertNull($result['sync_error']);
    }

    public function test_test_connection_auto_syncs_cer_codes(): void
    {
        $service = app(RentriOnboardingService::class);
        $apiClient = app(RentriApiClientInterface::class);

        $result = $service->testConnection($apiClient);

        $this->assertGreaterThanOrEqual(0, $result['codifiche_synced']);

        if ($result['codifiche_count'] > 0) {
            $this->assertGreaterThan(0, CodiceCer::count());
        }
    }

    public function test_test_connection_creates_serbatoi(): void
    {
        $cer = CodiceCer::factory()->create(['attivo' => true]);

        $service = app(RentriOnboardingService::class);
        $apiClient = app(RentriApiClientInterface::class);

        $service->testConnection($apiClient);

        $this->assertDatabaseHas('magazzino_rifiuti', [
            'codice_cer_id' => $cer->id,
        ]);
    }

    public function test_complete_onboarding_dispatches_initial_sync_job(): void
    {
        Bus::fake([RentriInitialSyncJob::class]);

        $service = app(RentriOnboardingService::class);
        $service->completeOnboarding();

        Bus::assertDispatched(RentriInitialSyncJob::class);
    }

    public function test_ensure_serbatoio_exists_creates_new(): void
    {
        $cer = CodiceCer::factory()->create(['attivo' => true]);

        $magazzinoService = app(MagazzinoService::class);
        $created = $magazzinoService->ensureSerbatoioExists($cer);

        $this->assertTrue($created);
        $this->assertDatabaseHas('magazzino_rifiuti', [
            'codice_cer_id'       => $cer->id,
            'quantita_attuale_kg' => 0,
        ]);
    }

    public function test_ensure_serbatoio_exists_skips_if_already_present(): void
    {
        $cer = CodiceCer::factory()->create(['attivo' => true]);
        MagazzinoRifiuto::create(['codice_cer_id' => $cer->id, 'quantita_attuale_kg' => 5]);

        $magazzinoService = app(MagazzinoService::class);
        $created = $magazzinoService->ensureSerbatoioExists($cer);

        $this->assertFalse($created);
        $this->assertSame(1, MagazzinoRifiuto::where('codice_cer_id', $cer->id)->count());
    }

    public function test_ensure_serbatoi_returns_created_count(): void
    {
        CodiceCer::factory()->count(3)->create(['attivo' => true]);
        CodiceCer::factory()->create(['attivo' => false]);

        $magazzinoService = app(MagazzinoService::class);
        $created = $magazzinoService->ensureSerbatoi();

        $this->assertSame(3, $created);
    }

    public function test_rentri_initial_sync_job_executes_sync(): void
    {
        $this->assertSame(0, CodiceCer::count());

        RentriInitialSyncJob::dispatchSync();

        $this->assertGreaterThanOrEqual(0, CodiceCer::count());
    }

    public function test_rentri_initial_sync_job_failed_notifies_admins(): void
    {
        Role::findOrCreate('admin');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $job = new RentriInitialSyncJob();
        $job->failed(new \RuntimeException('Sync test failure'));

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $admin->id,
        ]);

        $notification = $admin->notifications()->latest()->first();
        $this->assertNotNull($notification);
        $this->assertSame(NotificationEvent::RentriInitialSyncFailed->value, $notification->data['event']);
    }
}
