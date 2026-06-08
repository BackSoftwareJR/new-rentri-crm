<?php

namespace Tests\Feature\Sprint36;

use App\Models\FirBlocco;
use App\Models\RentriSetting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DemoResetCommandTest extends TestCase
{
    public function test_reset_command_fails_without_demo_mode_or_force(): void
    {
        Config::set('demo.enabled', false);

        $this->insertFirBlocco(['codice_blocco' => 'DEMO-BLK', 'is_demo' => true]);

        $exit = Artisan::call('rentri:demo-reset');

        $this->assertSame(1, $exit);
        $this->assertSame(1, FirBlocco::includingAllDemoModes()->where('is_demo', true)->count());
    }

    public function test_reset_command_deletes_only_demo_records(): void
    {
        Config::set('demo.enabled', true);

        $this->insertFirBlocco(['codice_blocco' => 'PROD-BLK', 'is_demo' => false]);
        FirBlocco::create(['codice_blocco' => 'DEMO-BLK', 'num_iscr_sito' => 'SITE-1']);
        RentriSetting::create(['ambiente' => 'sandbox']);

        $exit = Artisan::call('rentri:demo-reset');

        $this->assertSame(0, $exit);
        $this->assertSame(0, FirBlocco::count());
        $this->assertSame(1, FirBlocco::includingAllDemoModes()->where('is_demo', false)->count());
        $this->assertSame(0, RentriSetting::count());
    }

    public function test_reset_command_with_force_deletes_demo_in_production_mode(): void
    {
        Config::set('demo.enabled', false);

        $this->insertFirBlocco(['codice_blocco' => 'DEMO-BLK', 'is_demo' => true]);
        FirBlocco::create(['codice_blocco' => 'PROD-BLK', 'num_iscr_sito' => 'SITE-1']);

        $exit = Artisan::call('rentri:demo-reset', ['--force' => true]);

        $this->assertSame(0, $exit);
        $this->assertSame(1, FirBlocco::count());
        $this->assertSame('PROD-BLK', FirBlocco::first()->codice_blocco);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function insertFirBlocco(array $overrides): void
    {
        DB::table('fir_blocchi')->insert(array_merge([
            'codice_blocco'      => 'BLK',
            'num_iscr_sito'      => 'SITE-1',
            'progressivo_ultimo' => 0,
            'is_demo'            => false,
            'created_at'         => now(),
            'updated_at'         => now(),
        ], $overrides));
    }
}
