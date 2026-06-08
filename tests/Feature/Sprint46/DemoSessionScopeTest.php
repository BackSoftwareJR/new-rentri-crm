<?php

namespace Tests\Feature\Sprint46;

use App\Models\FirBlocco;
use App\Support\Demo\DemoIsolationException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DemoSessionScopeTest extends TestCase
{
    public function test_session_demo_only_sees_demo_records(): void
    {
        Config::set('demo.enabled', false);
        session([config('demo.session.key') => true]);

        $this->insertFirBlocco(['codice_blocco' => 'PROD-BLK', 'is_demo' => false]);
        FirBlocco::create(['codice_blocco' => 'DEMO-BLK', 'num_iscr_sito' => 'SITE-1']);

        $this->assertSame(1, FirBlocco::count());
        $this->assertSame('DEMO-BLK', FirBlocco::first()->codice_blocco);
    }

    public function test_deactivating_session_restores_production_scope(): void
    {
        Config::set('demo.enabled', false);

        $this->insertFirBlocco(['codice_blocco' => 'PROD-BLK', 'is_demo' => false]);
        $this->insertFirBlocco(['codice_blocco' => 'DEMO-BLK', 'is_demo' => true]);

        session([config('demo.session.key') => true]);
        $this->assertSame('DEMO-BLK', FirBlocco::first()->codice_blocco);

        session()->forget(config('demo.session.key'));
        $this->assertSame('PROD-BLK', FirBlocco::first()->codice_blocco);
    }

    public function test_session_demo_cannot_write_production_record(): void
    {
        Config::set('demo.enabled', false);
        session([config('demo.session.key') => true]);

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
