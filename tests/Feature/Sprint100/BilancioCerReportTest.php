<?php

namespace Tests\Feature\Sprint100;

use App\Http\Livewire\Segreteria\Report\BilancioCerIndex;
use App\Models\CodiceCer;
use App\Models\MagazzinoCaricoManuale;
use App\Models\RegistroMovimento;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class BilancioCerReportTest extends TestCase
{
    public function test_bilancio_cer_page_renders_for_segreteria(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(BilancioCerIndex::class)
            ->assertStatus(200);
    }

    public function test_bilancio_cer_aggregates_carichi_and_scarichi(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $cer  = CodiceCer::factory()->create(['attivo' => true]);

        $carico = RegistroMovimento::factory()->create([
            'codice_cer_id'  => $cer->id,
            'tipo'           => 'carico',
            'peso_kg'        => 100.0,
            'data_movimento' => Carbon::now()->startOfYear()->addDays(5),
        ]);

        $scarico = RegistroMovimento::factory()->create([
            'codice_cer_id'  => $cer->id,
            'tipo'           => 'scarico',
            'peso_kg'        => 40.0,
            'data_movimento' => Carbon::now()->startOfYear()->addDays(10),
        ]);

        $bilancio = Livewire::actingAs($user)
            ->test(BilancioCerIndex::class)
            ->instance()
            ->bilancio();

        $row = collect($bilancio['rows'])->firstWhere('id', $cer->id);
        $this->assertNotNull($row, 'CER row must appear in bilancio');
        $this->assertEqualsWithDelta(100.0, $row['carichi_kg'], 0.01);
        $this->assertEqualsWithDelta(40.0, $row['scarichi_kg'], 0.01);
        $this->assertEqualsWithDelta(60.0, $row['saldo_kg'], 0.01);

        $carico->forceDelete();
        $scarico->forceDelete();
        $cer->delete();
    }

    public function test_bilancio_cer_preset_year_sets_date_range(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $now  = Carbon::now();

        $component = Livewire::actingAs($user)
            ->test(BilancioCerIndex::class);

        $component->call('applyPreset', 'year');

        $this->assertSame($now->copy()->startOfYear()->toDateString(), $component->get('data_da'));
        $this->assertSame($now->copy()->endOfYear()->toDateString(), $component->get('data_a'));
    }

    public function test_bilancio_cer_preset_month(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $now  = Carbon::now();

        $component = Livewire::actingAs($user)
            ->test(BilancioCerIndex::class);

        $component->call('applyPreset', 'month');

        $this->assertSame($now->copy()->startOfMonth()->toDateString(), $component->get('data_da'));
        $this->assertSame($now->copy()->endOfMonth()->toDateString(), $component->get('data_a'));
    }

    public function test_bilancio_cer_totals_match_sum_of_rows(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $bilancio = Livewire::actingAs($user)
            ->test(BilancioCerIndex::class)
            ->instance()
            ->bilancio();

        $sumCarichi  = array_sum(array_column($bilancio['rows'], 'carichi_kg'));
        $sumScarichi = array_sum(array_column($bilancio['rows'], 'scarichi_kg'));
        $sumMov      = array_sum(array_column($bilancio['rows'], 'n_movimenti'));

        $this->assertEqualsWithDelta($bilancio['totals']['carichi_kg'], $sumCarichi, 0.01);
        $this->assertEqualsWithDelta($bilancio['totals']['scarichi_kg'], $sumScarichi, 0.01);
        $this->assertSame($bilancio['totals']['n_movimenti'], $sumMov);
    }

    public function test_bilancio_cer_operatore_cannot_access(): void
    {
        $user = User::where('email', 'operatore@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(BilancioCerIndex::class)
            ->assertForbidden();
    }

    public function test_bilancio_cer_route_exists(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('segreteria.report.bilancio-cer'));
    }
}
