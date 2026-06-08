<?php

namespace Tests\Feature\Sprint45;

use Tests\TestCase;

class Cycle3ClosureDocumentationTest extends TestCase
{
    public function test_security_checklist_document_exists(): void
    {
        $path = base_path('docs/SECURITY-CHECKLIST-DEMO-PROD.md');
        $this->assertFileExists($path);

        $contents = file_get_contents($path);
        $this->assertStringContainsString('HasDemoScope', $contents);
        $this->assertStringContainsString('DemoIsolationException', $contents);
        $this->assertStringContainsString('api.rentri.gov.it', $contents);
        $this->assertStringContainsString('Sessioni', $contents);
    }

    public function test_go_live_ciclo_3_document_marks_cycle_closed(): void
    {
        $path = base_path('docs/GO-LIVE-CICLO-3.md');
        $this->assertFileExists($path);

        $contents = file_get_contents($path);
        $this->assertStringContainsString('CHIUSO', $contents);
        $this->assertStringContainsString('Sprint 45', $contents);
        $this->assertStringContainsString('SECURITY-CHECKLIST-DEMO-PROD.md', $contents);
        $this->assertStringContainsString('MONITORING-CICLO-3.md', $contents);
    }

    public function test_monitoring_document_covers_health_and_dead_letter(): void
    {
        $path = base_path('docs/MONITORING-CICLO-3.md');
        $this->assertFileExists($path);

        $contents = file_get_contents($path);
        $this->assertStringContainsString('/up', $contents);
        $this->assertStringContainsString('rentri:monitor', $contents);
        $this->assertStringContainsString('dead-letter', $contents);
    }
}
