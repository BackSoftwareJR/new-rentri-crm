<?php

namespace Tests\Feature\Sprint44;

use Tests\TestCase;

class DemoStagingWorkflowTest extends TestCase
{
    public function test_demo_env_example_enables_demo_mode(): void
    {
        $path = base_path('.env.demo.example');
        $this->assertFileExists($path);

        $contents = file_get_contents($path);
        $this->assertStringContainsString('APP_DEMO_MODE=true', $contents);
        $this->assertStringContainsString('RENTRI_DEMO_FORCE_SANDBOX=true', $contents);
        $this->assertStringContainsString('APP_ENV=demo', $contents);
    }

    public function test_github_workflow_demo_staging_exists(): void
    {
        $path = base_path('.github/workflows/demo-staging.yml');
        $this->assertFileExists($path);

        $contents = file_get_contents($path);
        $this->assertStringContainsString('name: Demo Staging', $contents);
        $this->assertStringContainsString('.env.demo.example', $contents);
        $this->assertStringContainsString('rentri:demo-seed', $contents);
        $this->assertStringContainsString('rentri:preflight --demo', $contents);
        $this->assertStringContainsString('php artisan test', $contents);
    }
}
