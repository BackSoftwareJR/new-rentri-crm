<?php

namespace Tests\Feature\Sprint47;

use App\Domain\Demo\DemoModeSessionService;
use App\Domain\Demo\DemoSeedService;
use App\Models\Anagrafica;
use App\Models\User;
use App\Models\VfuRegistration;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DemoTrainingModuleIsolationTest extends TestCase
{
    public function test_session_demo_hides_production_anagrafiche(): void
    {
        Config::set('demo.enabled', false);
        session([config('demo.session.key') => true]);

        $this->insertAnagrafica(['ragione_sociale' => 'Prod Anag', 'is_demo' => false]);
        Anagrafica::create([
            'tipo'            => 'impianto',
            'ragione_sociale' => 'Demo Anag',
            'email'           => 'demo@rentri-demo.local',
            'telefono'        => '000',
        ]);

        $this->assertSame(1, Anagrafica::count());
        $this->assertSame('Demo Anag', Anagrafica::first()->ragione_sociale);
    }

    public function test_session_demo_hides_production_vfu(): void
    {
        Config::set('demo.enabled', false);
        session([config('demo.session.key') => true]);

        $this->insertVfu(['targa' => 'PROD01', 'is_demo' => false, 'marca' => 'FIAT', 'modello' => 'Panda']);
        VfuRegistration::factory()->create(['targa' => 'DEMO01']);

        $this->assertSame(1, VfuRegistration::count());
        $this->assertSame('DEMO01', VfuRegistration::first()->targa);
    }

    public function test_magazzino_lists_only_demo_linked_cer_in_palestra(): void
    {
        Config::set('demo.enabled', false);
        session([config('demo.session.key') => true]);

        Config::set('demo.allow_session_toggle', true);
        app(DemoModeSessionService::class)->activate(
            User::where('email', 'segreteria@example.com')->firstOrFail(),
        );

        \Illuminate\Support\Facades\Artisan::call('rentri:demo-seed');

        $rows = app(\App\Domain\Magazzino\MagazzinoService::class)->listSerbatoi();

        $this->assertGreaterThanOrEqual(1, $rows->count());
        $this->assertSame(
            app(DemoSeedService::class)->demoTrasporto()?->codiceCer?->codice,
            $rows->first()['codice'] ?? null,
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function insertAnagrafica(array $overrides): int
    {
        return (int) DB::table('anagrafiche')->insertGetId(array_merge([
            'tipo'            => 'impianto',
            'ragione_sociale' => 'Test',
            'email'           => 'prod@test.local',
            'telefono'        => '000',
            'is_demo'         => false,
            'created_at'      => now(),
            'updated_at'      => now(),
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function insertVfu(array $overrides): int
    {
        return (int) DB::table('vfu_registrations')->insertGetId(array_merge([
            'tipo_veicolo' => 'auto',
            'nazione'      => 'IT',
            'targa'        => 'XX000XX',
            'telaio'       => 'TEL001',
            'marca'        => 'FIAT',
            'modello'      => 'Panda',
            'stato'        => 'accettato',
            'is_demo'      => false,
            'created_at'   => now(),
            'updated_at'   => now(),
        ], $overrides));
    }
}
