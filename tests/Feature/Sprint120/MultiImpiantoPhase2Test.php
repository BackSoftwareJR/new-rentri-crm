<?php

namespace Tests\Feature\Sprint120;

use App\Models\Sito;
use App\Models\VfuRegistration;
use App\Support\Sito\SitoContext;
use Tests\TestCase;

class MultiImpiantoPhase2Test extends TestCase
{
    public function test_vfu_created_under_sito_a_not_visible_when_sito_b_is_active(): void
    {
        $sitoA = Sito::create([
            'nome' => 'Impianto A',
            'is_active' => true,
            'is_default' => true,
        ]);

        $sitoB = Sito::create([
            'nome' => 'Impianto B',
            'is_active' => true,
            'is_default' => false,
        ]);

        SitoContext::setActiveSitoId($sitoA->id);

        $vfu = VfuRegistration::factory()->create([
            'targa' => 'SITO2AA',
        ]);

        $this->assertSame($sitoA->id, $vfu->fresh()->sito_id);

        SitoContext::setActiveSitoId($sitoB->id);

        $this->assertFalse(
            VfuRegistration::query()
                ->forActiveSito()
                ->whereKey($vfu->id)
                ->exists(),
        );

        SitoContext::setActiveSitoId($sitoA->id);

        $this->assertTrue(
            VfuRegistration::query()
                ->forActiveSito()
                ->whereKey($vfu->id)
                ->exists(),
        );
    }
}
