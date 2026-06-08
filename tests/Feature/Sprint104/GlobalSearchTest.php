<?php

namespace Tests\Feature\Sprint104;

use App\Domain\Dashboard\DashboardKpiService;
use App\Enums\FirStato;
use App\Enums\TrasportoStato;
use App\Enums\VfuStato;
use App\Http\Livewire\GlobalSearch;
use App\Http\Livewire\Segreteria\Dashboard;
use App\Models\Anagrafica;
use App\Models\CodiceCer;
use App\Models\Fattura;
use App\Models\Fir;
use App\Models\Trasporto;
use App\Models\User;
use App\Models\VfuRegistration;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    public function test_global_search_finds_vfu_by_targa(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $vfu = VfuRegistration::factory()->create([
            'targa' => 'GS999ZZ',
            'telaio' => 'TEL999GS',
            'marca' => 'Fiat',
            'modello' => 'Panda',
        ]);

        Livewire::actingAs($user)
            ->test(GlobalSearch::class)
            ->set('open', true)
            ->set('query', 'GS999')
            ->assertSet('results.0.type', 'vfu')
            ->assertSee('GS999ZZ')
            ->call('selectResult', 0)
            ->assertRedirect(route('segreteria.vfu.show', $vfu));
    }

    public function test_global_search_finds_anagrafica_fattura_and_fir(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $anagrafica = Anagrafica::factory()->create([
            'ragione_sociale' => 'Ricerca Globale SRL',
            'piva' => '99887766554',
        ]);

        Fattura::create([
            'numero_fattura' => 'FT-GLOB-001',
            'tipo' => 'fattura',
            'anagrafica_id' => $anagrafica->id,
            'data_emissione' => now()->toDateString(),
            'stato' => 'emessa',
            'imponibile' => 100,
            'iva_percentuale' => 22,
            'iva_importo' => 22,
            'totale' => 122,
        ]);

        Fir::create([
            'numero_fir'       => 'FIR-GLOB-001',
            'codice_blocco'    => 'BLK01',
            'progressivo'      => 1,
            'stato'            => FirStato::Bozza,
            'peso_partenza_kg' => 100,
        ]);

        Livewire::actingAs($user)
            ->test(GlobalSearch::class)
            ->set('open', true)
            ->set('query', 'Glob')
            ->assertSee('Ricerca Globale SRL')
            ->assertSee('FT-GLOB-001')
            ->assertSee('FIR-GLOB-001');
    }

    public function test_global_search_respects_max_five_results_per_type(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        for ($i = 1; $i <= 7; $i++) {
            VfuRegistration::factory()->create(['targa' => 'SRCH'.$i.'AA']);
        }

        $component = Livewire::actingAs($user)
            ->test(GlobalSearch::class)
            ->set('query', 'SRCH');

        $groups = $component->get('results');
        $vfuGroup = collect($groups)->firstWhere('type', 'vfu');

        $this->assertNotNull($vfuGroup);
        $this->assertCount(5, $vfuGroup['items']);
    }

    public function test_dashboard_kpi_includes_operational_counters(): void
    {
        Cache::flush();

        VfuRegistration::factory()->create([
            'stato' => VfuStato::InBonifica,
            'data_accettazione' => now()->toDateString(),
        ]);
        VfuRegistration::factory()->create(['stato' => VfuStato::InSmontaggio]);

        $cer = CodiceCer::factory()->create();
        $dest = Anagrafica::factory()->create();
        Trasporto::create([
            'codice_cer_id'              => $cer->id,
            'anagrafica_destinatario_id' => $dest->id,
            'quantita_kg'                => 50,
            'stato'                      => TrasportoStato::InTransito,
        ]);

        $kpi = app(DashboardKpiService::class)->aggregate();

        $this->assertGreaterThanOrEqual(1, $kpi['vfu_oggi']);
        $this->assertGreaterThanOrEqual(1, $kpi['vfu_in_bonifica']);
        $this->assertGreaterThanOrEqual(1, $kpi['vfu_in_smontaggio']);
        $this->assertGreaterThanOrEqual(1, $kpi['trasporti_in_transito']);
        $this->assertArrayHasKey('rentri_status', $kpi);
        $this->assertArrayHasKey('revenue_mese_corrente', $kpi);
    }

    public function test_dashboard_renders_operational_overview_section(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertSee('Panoramica operativa')
            ->assertSee('VFU oggi')
            ->assertSee('VFU in bonifica')
            ->assertSee('Revenue mese corrente')
            ->assertSee('Revenue fatture pagate');
    }

    public function test_topbar_exposes_global_search_trigger(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('segreteria.dashboard'))
            ->assertOk()
            ->assertSee('seg-global-search-trigger', false)
            ->assertSee('Apri ricerca globale', false)
            ->assertDontSee('non ancora disponibile', false);
    }
}
