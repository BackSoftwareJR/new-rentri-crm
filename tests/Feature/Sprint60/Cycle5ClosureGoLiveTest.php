<?php

namespace Tests\Feature\Sprint60;

use App\Models\User;
use Tests\TestCase;

class Cycle5ClosureGoLiveTest extends TestCase
{
    public function test_uat_ux_360_checklist_exists_with_core_paths(): void
    {
        $path = base_path('docs/UAT-UX-360-CHECKLIST.md');
        $content = file_get_contents($path);

        $this->assertFileExists($path);
        $this->assertStringContainsString('Palestra operativa', $content);
        $this->assertStringContainsString('/segreteria/rentri', $content);
        $this->assertStringContainsString('/operatore', $content);
    }

    public function test_go_live_360_consolidates_security_sign_off(): void
    {
        $path = base_path('docs/GO-LIVE-360.md');
        $content = file_get_contents($path);

        $this->assertFileExists($path);
        $this->assertStringContainsString('OWASP-INTERNAL-CHECKLIST.md', $content);
        $this->assertStringContainsString('WAF-RULES-PREP.md', $content);
        $this->assertStringContainsString('2FA-PREP-RUNBOOK.md', $content);
        $this->assertStringContainsString('Gap residui post-ciclo 5', $content);
    }

    public function test_a11y_audit_runbook_and_pages_config_exist(): void
    {
        $this->assertFileExists(base_path('docs/A11Y-AUDIT-RUNBOOK.md'));
        $this->assertFileExists(base_path('scripts/a11y-pages.json'));
        $this->assertFileExists(base_path('scripts/axe-smoke.js'));

        $pages = json_decode(file_get_contents(base_path('scripts/a11y-pages.json')), true);
        $this->assertIsArray($pages);
        $this->assertGreaterThanOrEqual(8, count($pages));
    }

    public function test_lighthouse_budget_doc_and_config_exist(): void
    {
        $this->assertFileExists(base_path('docs/LIGHTHOUSE-BUDGET.md'));
        $this->assertFileExists(base_path('scripts/lighthouse-budget.json'));

        $budget = json_decode(file_get_contents(base_path('scripts/lighthouse-budget.json')), true);
        $this->assertNotEmpty($budget[0]['timings'] ?? null);
    }

    public function test_topbar_search_exposes_sr_hint_for_disabled_state(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('segreteria.dashboard'))
            ->assertOk()
            ->assertSee('id="seg-global-search-hint"', false)
            ->assertSee('aria-describedby="seg-global-search-hint"', false)
            ->assertSee('non ancora disponibile', false);
    }

    public function test_demo_banner_has_aria_live_polite(): void
    {
        $this->assertStringContainsString(
            'aria-live="polite"',
            file_get_contents(resource_path('views/components/demo-banner.blade.php'))
        );
    }

    public function test_ciclo_5_piano_marks_sprint_60_complete(): void
    {
        $content = file_get_contents(base_path('docs/CICLO-5-PIANO-360.md'));

        $this->assertStringContainsString('Sprint 60 — ✅ completato', $content);
        $this->assertStringContainsString('GO-LIVE-360.md', $content);
    }
}
