<?php

namespace App\Domain\Demo;

use App\Models\EcommerceOrdine;
use App\Models\EcommerceProdotto;
use App\Models\Fir;
use App\Models\FirBlocco;
use App\Models\MagazzinoSvuotamento;
use App\Models\MudDichiarazione;
use App\Models\RegistroMovimento;
use App\Models\RentriSetting;
use App\Models\RentriTransazione;
use App\Models\RentriTransmissione;
use App\Models\Trasporto;
use Illuminate\Support\Facades\DB;

class DemoResetService
{
    /**
     * @return array<string, int>
     */
    public function resetDemoData(): array
    {
        return DB::transaction(function () {
            $counts = [];

            Trasporto::includingAllDemoModes()->where('is_demo', true)->update(['fir_id' => null]);

            $counts['firs'] = Fir::includingAllDemoModes()->where('is_demo', true)->delete();
            $counts['trasporti'] = Trasporto::includingAllDemoModes()->where('is_demo', true)->delete();
            $counts['magazzino_svuotamenti'] = MagazzinoSvuotamento::includingAllDemoModes()->where('is_demo', true)->delete();
            $counts['registro_movimenti'] = RegistroMovimento::includingAllDemoModes()->where('is_demo', true)->delete();
            $counts['rentri_transmissioni'] = RentriTransmissione::includingAllDemoModes()->where('is_demo', true)->delete();
            $counts['rentri_transazioni'] = RentriTransazione::includingAllDemoModes()->where('is_demo', true)->delete();
            $counts['fir_blocchi'] = FirBlocco::includingAllDemoModes()->where('is_demo', true)->delete();
            $counts['rentri_settings'] = RentriSetting::includingAllDemoModes()->where('is_demo', true)->delete();
            RentriSetting::flushInstanceCache();
            $counts['ecommerce_ordini'] = EcommerceOrdine::includingAllDemoModes()->where('is_demo', true)->delete();
            $counts['ecommerce_prodotti'] = EcommerceProdotto::includingAllDemoModes()->where('is_demo', true)->delete();
            $counts['mud_dichiarazioni'] = MudDichiarazione::includingAllDemoModes()->where('is_demo', true)->delete();

            return $counts;
        });
    }
}
