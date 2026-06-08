<?php

namespace Tests\Feature\Sprint15;

use App\Domain\Audit\ActivityLogService;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ActivityLogServiceTest extends TestCase
{
    public function test_record_and_list_filters_by_modulo_and_user(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $service = app(ActivityLogService::class);

        $this->actingAs($admin);

        $service->record('rentri', 'Test trasmissione RENTRI', properties: ['stub' => true], userId: $admin->id);
        $service->record('mud', 'Test completamento MUD', properties: ['anno' => 2024], userId: $admin->id);

        $rentri = $service->list(['modulo' => 'rentri']);
        $this->assertSame(1, $rentri->total());
        $this->assertSame('Test trasmissione RENTRI', $rentri->first()->description);

        $byUser = $service->list(['user_id' => $admin->id]);
        $this->assertSame(2, $byUser->total());

        $this->assertSame('RENTRI', $service->moduloLabel('rentri'));
    }

    public function test_policy_allows_only_admin(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $segreteria = User::where('email', 'segreteria@example.com')->firstOrFail();

        $this->assertTrue($admin->can('viewAny', Activity::class));
        $this->assertFalse($segreteria->can('viewAny', Activity::class));
    }
}
