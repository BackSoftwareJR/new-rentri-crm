<?php

namespace Tests\Feature\Sprint36;

use App\Models\FirBlocco;
use App\Support\Demo\DemoIsolationException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DemoScopeIsolationTest extends TestCase
{
    public function test_production_mode_only_sees_non_demo_records(): void
    {
        Config::set('demo.enabled', false);

        FirBlocco::create(['codice_blocco' => 'PROD-BLK', 'num_iscr_sito' => 'SITE-1']);
        $this->insertFirBlocco(['codice_blocco' => 'DEMO-BLK', 'is_demo' => true]);

        $this->assertSame(1, FirBlocco::count());
        $this->assertSame('PROD-BLK', FirBlocco::first()->codice_blocco);
    }

    public function test_demo_mode_only_sees_demo_records(): void
    {
        Config::set('demo.enabled', true);

        $this->insertFirBlocco(['codice_blocco' => 'PROD-BLK', 'is_demo' => false]);
        FirBlocco::create(['codice_blocco' => 'DEMO-BLK', 'num_iscr_sito' => 'SITE-1']);

        $this->assertSame(1, FirBlocco::count());
        $this->assertSame('DEMO-BLK', FirBlocco::first()->codice_blocco);
    }

    public function test_production_mode_cannot_update_demo_record(): void
    {
        Config::set('demo.enabled', false);

        $id = $this->insertFirBlocco(['codice_blocco' => 'DEMO-BLK', 'is_demo' => true]);
        $blocco = FirBlocco::includingAllDemoModes()->findOrFail($id);

        $this->expectException(DemoIsolationException::class);

        $blocco->update(['progressivo_ultimo' => 5]);
    }

    public function test_demo_mode_cannot_update_production_record(): void
    {
        Config::set('demo.enabled', true);

        $id = $this->insertFirBlocco(['codice_blocco' => 'PROD-BLK', 'is_demo' => false]);
        $blocco = FirBlocco::includingAllDemoModes()->findOrFail($id);

        $this->expectException(DemoIsolationException::class);

        $blocco->update(['progressivo_ultimo' => 5]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function insertFirBlocco(array $overrides): int
    {
        return (int) DB::table('fir_blocchi')->insertGetId(array_merge([
            'codice_blocco'      => 'BLK',
            'num_iscr_sito'      => 'SITE-1',
            'progressivo_ultimo' => 0,
            'is_demo'            => false,
            'created_at'         => now(),
            'updated_at'         => now(),
        ], $overrides));
    }
}
