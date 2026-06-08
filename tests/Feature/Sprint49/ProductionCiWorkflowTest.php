<?php

namespace Tests\Feature\Sprint49;

use Tests\TestCase;

class ProductionCiWorkflowTest extends TestCase
{
    public function test_production_workflow_exists_with_test_and_preflight(): void
    {
        $path = base_path('.github/workflows/production.yml');
        $this->assertFileExists($path);

        $contents = file_get_contents($path);
        $this->assertStringContainsString('rentri:preflight', $contents);
        $this->assertStringContainsString('php artisan test', $contents);
        $this->assertStringContainsString('playwright-palestra-smoke', $contents);
        $this->assertDoesNotMatchRegularExpression('/secrets\./', $contents);
    }

    public function test_playwright_palestra_spec_exists(): void
    {
        $this->assertFileExists(base_path('tests/e2e/palestra-smoke.spec.ts'));
        $this->assertFileExists(base_path('playwright.config.ts'));
    }
}
