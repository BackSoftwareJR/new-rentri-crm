<?php

namespace Tests\Feature\Sprint104;

use App\Domain\Security\OwaspExternalPrepService;
use App\Http\Livewire\Admin\PenTestPrepPage;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OwaspExternalPrepTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin');
        Role::findOrCreate('segreteria');
    }

    public function test_owasp_external_prep_lists_scope_assets(): void
    {
        $prep = app(OwaspExternalPrepService::class);
        $assets = $prep->scopeAssets();

        $this->assertGreaterThanOrEqual(8, count($assets));

        $keys = array_column($assets, 'key');
        $this->assertContains('login', $keys);
        $this->assertContains('stripe_webhook', $keys);
        $this->assertContains('mud_telematico', $keys);
        $this->assertContains('gps_provider', $keys);
    }

    public function test_owasp_external_prep_test_accounts_template_has_four_roles(): void
    {
        $accounts = app(OwaspExternalPrepService::class)->testAccountsTemplate();

        $this->assertCount(4, $accounts);
        $roles = array_column($accounts, 'role');
        $this->assertContains('admin', $roles);
        $this->assertContains('segreteria', $roles);
        $this->assertContains('operatore', $roles);
    }

    public function test_owasp_external_prep_out_of_scope_excludes_mase_and_waf(): void
    {
        $items = app(OwaspExternalPrepService::class)->outOfScopeItems();
        $keys = array_column($items, 'key');

        $this->assertContains('mase_production', $keys);
        $this->assertContains('infra_waf', $keys);
        $this->assertContains('stripe_live_charges', $keys);
    }

    public function test_owasp_internal_checklist_documents_2fa_stripe_mud_gps(): void
    {
        $content = file_get_contents(base_path('docs/OWASP-INTERNAL-CHECKLIST.md'));

        $this->assertStringContainsString('EnsureTwoFactorEnabled', $content);
        $this->assertStringContainsString('stripe_event_id', $content);
        $this->assertStringContainsString('MudTelematicoEndpoints', $content);
        $this->assertStringContainsString('TRASPORTO_GPS_PROVIDER_URL', $content);
        $this->assertStringContainsString('PEN-TEST-EXTERNAL-SCOPE.md', $content);
    }

    public function test_pen_test_external_scope_document_exists(): void
    {
        $path = base_path('docs/PEN-TEST-EXTERNAL-SCOPE.md');
        $content = file_get_contents($path);

        $this->assertFileExists($path);
        $this->assertStringContainsString('/webhooks/stripe/ecommerce', $content);
        $this->assertStringContainsString('two-factor-challenge', $content);
        $this->assertStringContainsString('REMEDIATION-FINDINGS-TEMPLATE.md', $content);
    }

    public function test_remediation_findings_template_has_p0_p1_p2(): void
    {
        $content = file_get_contents(base_path('docs/REMEDIATION-FINDINGS-TEMPLATE.md'));

        $this->assertStringContainsString('P0 — Critical', $content);
        $this->assertStringContainsString('P1 — High', $content);
        $this->assertStringContainsString('P2 — Medium', $content);
    }

    public function test_admin_pen_test_prep_page_renders_for_admin(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(PenTestPrepPage::class)
            ->assertSee('Pen-test OWASP esterno')
            ->assertSee('Checklist engagement')
            ->assertSee('/webhooks/stripe/ecommerce')
            ->assertSee('Fuori scope');
    }

    public function test_admin_pen_test_prep_denied_for_segreteria(): void
    {
        $user = User::factory()->create();
        $user->assignRole('segreteria');

        $this->actingAs($user)
            ->get(route('admin.pen-test-prep'))
            ->assertForbidden();
    }

    public function test_go_live_operativo_links_pen_test_scope(): void
    {
        $content = file_get_contents(base_path('docs/GO-LIVE-OPERATIVO.md'));

        $this->assertStringContainsString('PEN-TEST-EXTERNAL-SCOPE.md', $content);
        $this->assertStringContainsString('admin/pen-test-prep', $content);
    }

    public function test_fixture_documents_pen_test_external_scope_contract(): void
    {
        $fixture = json_decode(
            file_get_contents(base_path('tests/fixtures/security/pen-test-external-scope.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertSame(104, $fixture['sprint']);
        $this->assertContains('/webhooks/stripe/ecommerce', $fixture['in_scope_paths']);
        $this->assertContains('admin', $fixture['test_accounts_roles']);
    }

    public function test_sprint_104_audit_notes_document_owasp_prep(): void
    {
        $content = file_get_contents(base_path('docs/SPRINT-104-AUDIT-NOTES.md'));

        $this->assertStringContainsString('OwaspExternalPrepService', $content);
        $this->assertStringContainsString('PenTestPrepPage', $content);
    }

    public function test_engagement_checklist_ready_when_docs_and_config_present(): void
    {
        Config::set('two-factor.enforce_admin_segreteria', true);
        Config::set('services.ecommerce.payment_stub', true);
        Config::set('app.url', 'https://staging.example.test');

        $prep = app(OwaspExternalPrepService::class);

        $this->assertTrue($prep->isReadyForEngagement());
        $this->assertGreaterThanOrEqual(9, $prep->summary()['assets_count']);
    }
}
