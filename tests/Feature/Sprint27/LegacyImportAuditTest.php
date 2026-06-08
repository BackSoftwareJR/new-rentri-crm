<?php

namespace Tests\Feature\Sprint27;

use App\Domain\Legacy\LegacyImportService;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class LegacyImportAuditTest extends TestCase
{
    public function test_import_records_activity_with_counts(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin);

        $service = app(LegacyImportService::class);
        $result = $service->import('anagrafiche', $service->defaultFixturePath('anagrafiche'));

        $this->assertSame(3, $result['imported']);

        $this->assertDatabaseHas('activity_log', [
            'log_name'    => 'legacy',
            'description' => 'Import legacy anagrafiche completato',
            'causer_type' => User::class,
            'causer_id'   => $admin->id,
        ]);

        $activity = Activity::query()->where('log_name', 'legacy')->latest('id')->first();
        $this->assertNotNull($activity);

        $props = $activity->properties->toArray();
        $this->assertSame('anagrafiche', $props['entity']);
        $this->assertFalse($props['dry_run']);
        $this->assertSame(3, $props['imported']);
        $this->assertSame(3, $props['processed']);
        $this->assertSame(0, $props['skipped']);
        $this->assertSame(0, $props['errors_count']);
    }

    public function test_dry_run_import_records_activity_with_dry_run_flag(): void
    {
        $service = app(LegacyImportService::class);
        $service->import('anagrafiche', $service->defaultFixturePath('anagrafiche'), dryRun: true);

        $activity = Activity::query()->where('log_name', 'legacy')->latest('id')->first();
        $this->assertNotNull($activity);
        $this->assertSame('Import legacy anagrafiche (dry-run)', $activity->description);
        $this->assertTrue($activity->properties->get('dry_run'));
        $this->assertSame(3, $activity->properties->get('imported'));
    }
}
