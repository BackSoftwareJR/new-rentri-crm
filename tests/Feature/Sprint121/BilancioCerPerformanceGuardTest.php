<?php

namespace Tests\Feature\Sprint121;

use App\Http\Livewire\Segreteria\Report\BilancioCerIndex;
use App\Models\RegistroMovimento;
use App\Models\Sito;
use App\Models\User;
use App\Support\Sito\SitoContext;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class BilancioCerPerformanceGuardTest extends TestCase
{
    private function segreteria(): User
    {
        return User::where('email', 'segreteria@example.com')->firstOrFail();
    }

    public function test_caps_custom_date_range_to_365_days(): void
    {
        $this->actingAs($this->segreteria());

        Livewire::test(BilancioCerIndex::class)
            ->set('preset', 'custom')
            ->set('data_da', '2024-01-01')
            ->set('data_a', '2026-06-08')
            ->assertSet('data_a', '2024-12-31');
    }

    public function test_bilancio_aggregates_only_active_sito_movements(): void
    {
        $sitoA = Sito::create(['nome' => 'Nord', 'is_active' => true, 'is_default' => true]);
        $sitoB = Sito::create(['nome' => 'Sud', 'is_active' => true, 'is_default' => false]);

        $cer = \App\Models\CodiceCer::factory()->create();

        SitoContext::setActiveSitoId($sitoA->id);
        RegistroMovimento::factory()->create([
            'codice_cer_id' => $cer->id,
            'tipo' => 'carico',
            'peso_kg' => 100,
            'data_movimento' => Carbon::parse('2026-06-01'),
        ]);

        SitoContext::setActiveSitoId($sitoB->id);
        RegistroMovimento::factory()->create([
            'codice_cer_id' => $cer->id,
            'tipo' => 'carico',
            'peso_kg' => 500,
            'data_movimento' => Carbon::parse('2026-06-01'),
        ]);

        $this->actingAs($this->segreteria());
        SitoContext::setActiveSitoId($sitoA->id);

        $component = Livewire::test(BilancioCerIndex::class)
            ->set('preset', 'custom')
            ->set('data_da', '2026-06-01')
            ->set('data_a', '2026-06-30');

        $bilancio = $component->instance()->bilancio();
        $this->assertEqualsWithDelta(100.0, $bilancio['totals']['carichi_kg'], 0.01);
    }
}
