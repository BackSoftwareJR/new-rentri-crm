<?php

namespace Tests\Feature\Sprint29;

use App\Domain\Legacy\LegacyImportService;
use App\Http\Livewire\Segreteria\Dashboard;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class LegacyMigrationDashboardKpiTest extends TestCase
{
    /** @var list<string> */
    private const IMPORT_ORDER = ['codici_cer', 'anagrafiche', 'vfu', 'movimenti', 'ricambi'];

    public function test_segreteria_dashboard_shows_legacy_kpi_after_full_import(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $service = app(LegacyImportService::class);

        foreach (self::IMPORT_ORDER as $entity) {
            $service->import($entity, $service->defaultFixturePath($entity));
        }

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertSee('Migrazione legacy')
            ->assertSee('Record legacy tracciati')
            ->assertSee('14')
            ->assertSee('Codici CER')
            ->assertSee('Movimenti registro')
            ->assertSee('Checklist go-live')
            ->assertDontSee('Audit import legacy');
    }

    public function test_admin_dashboard_shows_legacy_kpi_and_audit_link(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $service = app(LegacyImportService::class);
        $service->import('anagrafiche', $service->defaultFixturePath('anagrafiche'));

        Livewire::actingAs($admin)
            ->test(Dashboard::class)
            ->assertSee('Migrazione legacy')
            ->assertSee('Record legacy tracciati')
            ->assertSee('3')
            ->assertSee('Anagrafiche')
            ->assertSee('Audit import legacy')
            ->assertSee(route('admin.audit').'?modulo=legacy');
    }
}
