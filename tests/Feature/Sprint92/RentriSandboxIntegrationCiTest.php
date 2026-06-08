<?php

namespace Tests\Feature\Sprint92;

use Tests\TestCase;

class RentriSandboxIntegrationCiTest extends TestCase
{
    private function workflowContents(): string
    {
        $path = base_path('.github/workflows/rentri-sandbox-integration.yml');

        $this->assertFileExists($path);

        return file_get_contents($path);
    }

    public function test_rentri_sandbox_integration_workflow_exists(): void
    {
        $this->assertFileExists(base_path('.github/workflows/rentri-sandbox-integration.yml'));
    }

    public function test_workflow_triggers_manual_dispatch_and_label_only(): void
    {
        $contents = $this->workflowContents();

        $this->assertStringContainsString('workflow_dispatch:', $contents);
        $this->assertStringContainsString('integration-sandbox', $contents);
        $this->assertDoesNotMatchRegularExpression('/^\s*push:\s*$/m', $contents);
        $this->assertDoesNotMatchRegularExpression('/branches:\s*\n\s*-\s*main/m', $contents);
    }

    public function test_workflow_runs_rentri_integration_test_with_gated_secrets(): void
    {
        $contents = $this->workflowContents();

        $this->assertStringContainsString('RentriIntegrationTest', $contents);
        $this->assertStringContainsString('RENTRI_SANDBOX_CERT_BASE64', $contents);
        $this->assertStringContainsString('RENTRI_SANDBOX_CERT_PASSWORD', $contents);
        $this->assertStringContainsString('RENTRI_INTEGRATION_TEST', $contents);
        $this->assertStringContainsString('configured=false', $contents);
        $this->assertStringContainsString('Skipping RentriIntegrationTest', $contents);
    }

    public function test_workflow_decodes_cert_to_temp_and_cleans_up(): void
    {
        $contents = $this->workflowContents();

        $this->assertStringContainsString('base64 -d', $contents);
        $this->assertStringContainsString('RUNNER_TEMP', $contents);
        $this->assertStringContainsString('chmod 600', $contents);
        $this->assertStringContainsString('Cleanup sandbox certificate', $contents);
        $this->assertStringContainsString('rm -f', $contents);
    }

    public function test_workflow_does_not_echo_secrets_in_run_steps(): void
    {
        $contents = $this->workflowContents();

        $this->assertDoesNotMatchRegularExpression('/echo\s+\$\{\{\s*secrets\./', $contents);
        $this->assertDoesNotMatchRegularExpression('/echo\s+"?\$CERT_PASS/', $contents);
        $this->assertDoesNotMatchRegularExpression('/echo\s+"?\$CERT_B64/', $contents);
    }

    public function test_validazione_sandbox_mase_documents_ci_section(): void
    {
        $doc = file_get_contents(base_path('docs/VALIDAZIONE-SANDBOX-MASE.md'));

        $this->assertStringContainsString('## 8. CI gated (GitHub Actions)', $doc);
        $this->assertStringContainsString('rentri-sandbox-integration.yml', $doc);
        $this->assertStringContainsString('RENTRI_SANDBOX_CERT_BASE64', $doc);
        $this->assertStringContainsString('integration-sandbox', $doc);
        $this->assertStringContainsString('workflow_dispatch', $doc);
        $this->assertStringContainsString('skip', strtolower($doc));
    }

    public function test_production_workflow_does_not_run_sandbox_integration(): void
    {
        $production = file_get_contents(base_path('.github/workflows/production.yml'));

        $this->assertStringNotContainsString('RentriIntegrationTest', $production);
        $this->assertStringNotContainsString('RENTRI_SANDBOX_CERT_BASE64', $production);
    }
}
