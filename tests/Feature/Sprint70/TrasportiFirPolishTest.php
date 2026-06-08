<?php

namespace Tests\Feature\Sprint70;

use App\Domain\Fir\FirBulkExportService;
use App\Domain\Trasporti\TrasportoTrackingPrepService;
use App\Enums\FirStato;
use App\Enums\TrasportoStato;
use App\Http\Livewire\Segreteria\Fir\FirIndex;
use App\Http\Livewire\Segreteria\Trasporti\TrasportoShow;
use App\Models\Anagrafica;
use App\Models\CodiceCer;
use App\Models\Fir;
use App\Models\Trasporto;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TrasportiFirPolishTest extends TestCase
{
    public function test_fir_bulk_export_includes_vidimati_and_respects_date_filter(): void
    {
        $cer = CodiceCer::factory()->create(['codice' => 'S70-EXP']);
        $dest = Anagrafica::factory()->create(['tipo' => 'impianto']);
        $trasporto = Trasporto::create([
            'codice_cer_id'              => $cer->id,
            'anagrafica_destinatario_id' => $dest->id,
            'stato'                      => TrasportoStato::InTransito,
            'quantita_kg'                => 50,
        ]);

        Fir::create([
            'numero_fir'       => 'FIR-S70-OLD',
            'codice_blocco'    => 'BLK-S70',
            'progressivo'      => 1,
            'stato'            => FirStato::Vidimato,
            'vidimato_at'      => now()->subDays(10),
            'trasporto_id'     => $trasporto->id,
            'peso_partenza_kg' => 50,
        ]);

        Fir::create([
            'numero_fir'       => 'FIR-S70-NEW',
            'codice_blocco'    => 'BLK-S70',
            'progressivo'      => 2,
            'stato'            => FirStato::Firmato,
            'vidimato_at'      => now()->subDay(),
            'firmato_at'       => now(),
            'trasporto_id'     => $trasporto->id,
            'peso_partenza_kg' => 50,
        ]);

        Fir::create([
            'numero_fir'       => 'FIR-S70-BOZZA',
            'codice_blocco'    => 'BLK-S70',
            'progressivo'      => 3,
            'stato'            => FirStato::Bozza,
            'peso_partenza_kg' => 10,
        ]);

        $export = app(FirBulkExportService::class);
        $rows = $export->filteredQuery(['data_da' => now()->subDays(3)->toDateString()])->get();

        $this->assertCount(1, $rows);
        $this->assertSame('FIR-S70-NEW', $rows->first()->numero_fir);

        ob_start();
        $export->exportCsv(['data_da' => now()->subDays(3)->toDateString()])->sendContent();
        $csv = ob_get_clean();

        $this->assertStringContainsString('FIR-S70-NEW', $csv);
        $this->assertStringContainsString('S70-EXP', $csv);
        $this->assertStringNotContainsString('FIR-S70-BOZZA', $csv);
        $this->assertStringNotContainsString('FIR-S70-OLD', $csv);
    }

    public function test_fir_index_export_bulk_livewire(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Fir::create([
            'numero_fir'       => 'FIR-S70-LW',
            'codice_blocco'    => 'BLK-LW',
            'progressivo'      => 1,
            'stato'            => FirStato::Vidimato,
            'vidimato_at'      => now(),
            'peso_partenza_kg' => 20,
        ]);

        Livewire::actingAs($user)
            ->test(FirIndex::class)
            ->assertSee('Export CSV bulk')
            ->call('exportBulkCsv')
            ->assertSuccessful();
    }

    public function test_fir_index_shows_tracking_stub_badge_for_in_transito(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $dest = Anagrafica::factory()->create(['tipo' => 'impianto']);
        $trasporto = Trasporto::create([
            'codice_cer_id'              => CodiceCer::factory()->create()->id,
            'anagrafica_destinatario_id' => $dest->id,
            'stato'                      => TrasportoStato::InTransito,
            'quantita_kg'                => 30,
        ]);

        Fir::create([
            'numero_fir'       => 'FIR-S70-GPS',
            'codice_blocco'    => 'BLK-GPS',
            'progressivo'      => 1,
            'stato'            => FirStato::Vidimato,
            'vidimato_at'      => now(),
            'trasporto_id'     => $trasporto->id,
            'peso_partenza_kg' => 30,
        ]);

        Livewire::actingAs($user)
            ->test(FirIndex::class)
            ->assertSee('GPS stub')
            ->assertSee('Tracking stub: 1');
    }

    public function test_trasporto_tracking_prep_timeline_for_in_transito(): void
    {
        $dest = Anagrafica::factory()->create(['tipo' => 'impianto', 'ragione_sociale' => 'Impianto S70']);
        $trasporto = Trasporto::create([
            'codice_cer_id'               => CodiceCer::factory()->create()->id,
            'anagrafica_destinatario_id'  => $dest->id,
            'stato'                       => TrasportoStato::InTransito,
            'quantita_kg'                 => 80,
        ]);

        $prep = app(TrasportoTrackingPrepService::class);
        $timeline = $prep->timeline($trasporto);

        $this->assertCount(4, $timeline);
        $this->assertSame('gps_stub', $timeline[1]['key']);
        $this->assertNotNull($prep->etaStub($trasporto));
    }

    public function test_trasporto_show_renders_tracking_prep_timeline(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $dest = Anagrafica::factory()->create(['tipo' => 'impianto', 'ragione_sociale' => 'Impianto Show S70']);
        $trasporto = Trasporto::create([
            'codice_cer_id'               => CodiceCer::factory()->create()->id,
            'anagrafica_destinatario_id'  => $dest->id,
            'stato'                       => TrasportoStato::InTransito,
            'quantita_kg'                 => 60,
        ]);

        Livewire::actingAs($user)
            ->test(TrasportoShow::class, ['trasporto' => $trasporto])
            ->assertSee('Integrazione GPS/ETA')
            ->assertSee('seg-tracking-timeline', false)
            ->assertSee('GPS stub attivo')
            ->assertSee('ETA stimata');
    }

    public function test_operatore_cannot_export_fir_bulk(): void
    {
        Role::findOrCreate('operatore');
        $user = User::where('email', 'operatore@example.com')->firstOrFail();

        $this->assertFalse(Gate::forUser($user)->allows('exportAny', Fir::class));
    }

    public function test_fir_export_policy_allows_segretaria(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $this->assertTrue(Gate::forUser($user)->allows('exportAny', Fir::class));
    }
}
