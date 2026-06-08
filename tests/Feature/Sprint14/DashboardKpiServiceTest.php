<?php

namespace Tests\Feature\Sprint14;

use App\Domain\Dashboard\DashboardKpiService;
use App\Enums\MudStato;
use App\Enums\OrdineEcommerceStato;
use App\Enums\RegistroMovimentoTipo;
use App\Enums\VfuStato;
use App\Models\CodiceCer;
use App\Models\EcommerceOrdine;
use App\Models\EcommerceProdotto;
use App\Models\MagazzinoCaricoManuale;
use App\Models\MudDichiarazione;
use App\Models\RegistroMovimento;
use App\Models\RentriTransazione;
use App\Models\User;
use App\Models\VfuRegistration;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardKpiServiceTest extends TestCase
{
    public function test_aggregate_includes_cross_module_counters(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        VfuRegistration::factory()->create(['stato' => VfuStato::Accettato]);
        VfuRegistration::factory()->create(['stato' => VfuStato::AttesaBonifica]);

        $cer = CodiceCer::factory()->create(['attivo' => true]);
        RegistroMovimento::create([
            'tipo'             => RegistroMovimentoTipo::Carico,
            'codice_cer_id'    => $cer->id,
            'peso_kg'          => 50,
            'data_movimento'   => now(),
            'rentri_trasmesso' => false,
            'source_type'      => MagazzinoCaricoManuale::class,
            'source_id'        => 1,
        ]);

        RentriTransazione::create([
            'transazione_id' => (string) Str::uuid(),
            'tipo_api'       => 'health',
            'stato'          => 'errore',
            'request_json'   => ['method' => 'GET', 'endpoint' => '/health'],
            'response_json'  => ['error' => true],
            'completed_at'   => now(),
        ]);

        MudDichiarazione::create([
            'anno_riferimento' => 2019,
            'stato'            => MudStato::Bozza,
            'righe'            => [],
            'user_id'          => $user->id,
        ]);

        EcommerceProdotto::factory()->create(['giacenza' => 3, 'attivo' => true]);
        EcommerceProdotto::factory()->create(['giacenza' => 0, 'attivo' => true]);

        EcommerceOrdine::create([
            'user_id' => $user->id,
            'stato'   => OrdineEcommerceStato::Bozza,
            'totale'  => 10,
            'righe'   => [],
        ]);

        $kpi = app(DashboardKpiService::class)->aggregate();

        $this->assertGreaterThanOrEqual(2, $kpi['vfu_attivi']);
        $this->assertGreaterThanOrEqual(1, $kpi['bonifiche_pending']);
        $this->assertGreaterThanOrEqual(1, $kpi['rentri_pending']);
        $this->assertGreaterThanOrEqual(1, $kpi['rentri_errori']);
        $this->assertGreaterThanOrEqual(1, $kpi['mud_bozze']);
        $this->assertGreaterThanOrEqual(2, $kpi['ecommerce_prodotti']);
        $this->assertGreaterThanOrEqual(1, $kpi['ecommerce_disponibili']);
        $this->assertGreaterThanOrEqual(1, $kpi['ecommerce_ordini_bozza']);
        $this->assertGreaterThanOrEqual(1, $kpi['movimenti_mese']);
    }
}
