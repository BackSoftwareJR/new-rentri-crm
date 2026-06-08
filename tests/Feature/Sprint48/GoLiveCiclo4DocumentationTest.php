<?php

namespace Tests\Feature\Sprint48;

use Tests\TestCase;

class GoLiveCiclo4DocumentationTest extends TestCase
{
    public function test_go_live_ciclo_4_checklist_exists(): void
    {
        $this->assertFileExists(base_path('docs/GO-LIVE-CICLO-4.md'));

        $content = file_get_contents(base_path('docs/GO-LIVE-CICLO-4.md'));
        $this->assertStringContainsString('Isolamento dati', $content);
        $this->assertStringContainsString('Preset multi-operatore', $content);
        $this->assertMatchesRegularExpression('/walkthrough/i', $content);
    }

    public function test_ciclo_4_piano_marks_sprint_48_complete(): void
    {
        $piano = file_get_contents(base_path('docs/CICLO-4-PIANO.md'));
        $this->assertStringContainsString('Sprint 48', $piano);
        $this->assertStringContainsString('Sprint 49', $piano);
    }
}
