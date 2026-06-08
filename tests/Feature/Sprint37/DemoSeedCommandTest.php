<?php

namespace Tests\Feature\Sprint37;

use App\Domain\Demo\DemoSeedService;
use App\Models\FirBlocco;
use App\Models\MagazzinoSvuotamento;
use App\Models\RegistroMovimento;
use App\Models\RentriSetting;
use App\Models\Trasporto;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class DemoSeedCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('demo.enabled', true);
    }

    public function test_seed_command_populates_demo_fixtures(): void
    {
        $exit = Artisan::call('rentri:demo-seed');

        $this->assertSame(0, $exit);
        $this->assertTrue(app(DemoSeedService::class)->isSeeded());
        $this->assertDatabaseHas('fir_blocchi', [
            'codice_blocco' => DemoSeedService::BLOCCO_CODICE,
            'is_demo'       => true,
        ]);
        $this->assertDatabaseHas('trasporti', [
            'note'    => DemoSeedService::TRASPORTO_NOTE,
            'is_demo' => true,
        ]);
        $this->assertDatabaseHas('magazzino_svuotamenti', [
            'note_interne' => DemoSeedService::SVUOTAMENTO_NOTE,
            'is_demo'      => true,
        ]);
        $this->assertDatabaseHas('registro_movimenti', [
            'note'             => DemoSeedService::MOVIMENTO_NOTE,
            'rentri_trasmesso' => false,
            'is_demo'          => true,
        ]);

        $settings = RentriSetting::instance();
        $this->assertSame('sandbox', $settings->ambiente);
        $this->assertSame(DemoSeedService::NUM_ISCR_SITO, $settings->num_iscr_sito);
    }

    public function test_seed_command_is_idempotent(): void
    {
        Artisan::call('rentri:demo-seed');
        $blocchi = FirBlocco::count();
        $svuotamenti = MagazzinoSvuotamento::count();
        $trasporti = Trasporto::count();
        $movimenti = RegistroMovimento::count();

        Artisan::call('rentri:demo-seed');

        $this->assertSame($blocchi, FirBlocco::count());
        $this->assertSame($svuotamenti, MagazzinoSvuotamento::count());
        $this->assertSame($trasporti, Trasporto::count());
        $this->assertSame($movimenti, RegistroMovimento::count());
    }

    public function test_fresh_option_resets_and_reseeds(): void
    {
        Artisan::call('rentri:demo-seed');
        $firstTrasportoId = Trasporto::first()->id;

        Artisan::call('rentri:demo-seed', ['--fresh' => true]);

        $this->assertTrue(app(DemoSeedService::class)->isSeeded());
        $this->assertNotSame($firstTrasportoId, Trasporto::first()->id);
    }

    public function test_seed_command_fails_without_demo_mode_or_force(): void
    {
        Config::set('demo.enabled', false);

        $exit = Artisan::call('rentri:demo-seed');

        $this->assertSame(1, $exit);
        $this->assertFalse(app(DemoSeedService::class)->isSeeded());
    }
}
