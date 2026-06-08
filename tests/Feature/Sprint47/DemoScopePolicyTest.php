<?php

namespace Tests\Feature\Sprint47;

use App\Models\FirBlocco;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DemoScopePolicyTest extends TestCase
{
    public function test_user_cannot_view_production_fir_blocco_in_session_demo(): void
    {
        Config::set('demo.enabled', false);
        session([config('demo.session.key') => true]);

        $id = $this->insertBlocco(['codice_blocco' => 'PROD-BLK', 'is_demo' => false]);
        $blocco = FirBlocco::includingAllDemoModes()->findOrFail($id);

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $this->assertFalse($user->can('view', $blocco));
    }

    public function test_user_can_view_demo_fir_blocco_in_session_demo(): void
    {
        Config::set('demo.enabled', false);
        session([config('demo.session.key') => true]);

        $blocco = FirBlocco::create([
            'codice_blocco'      => 'DEMO-BLK-POL',
            'num_iscr_sito'      => 'SITE-1',
            'progressivo_ultimo' => 0,
        ]);

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $this->assertTrue($user->can('view', $blocco));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function insertBlocco(array $overrides): int
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
