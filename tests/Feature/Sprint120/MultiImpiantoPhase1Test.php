<?php

namespace Tests\Feature\Sprint120;

use App\Http\Livewire\Admin\SitiIndex;
use App\Http\Livewire\SitoSwitcher;
use App\Models\RentriSetting;
use App\Models\Sito;
use App\Models\User;
use App\Support\Sito\SitoContext;
use Database\Seeders\RentriSettingSeeder;
use Database\Seeders\SitoSeeder;
use Livewire\Livewire;
use Tests\TestCase;

class MultiImpiantoPhase1Test extends TestCase
{
    public function test_sito_seeder_creates_default_from_rentri_setting(): void
    {
        $this->seed(RentriSettingSeeder::class);
        $this->seed(SitoSeeder::class);

        $sito = Sito::query()->first();
        $setting = RentriSetting::query()->where('is_demo', false)->first();

        $this->assertNotNull($sito);
        $this->assertTrue($sito->is_default);
        $this->assertSame($setting?->num_iscr_sito, $sito->num_iscr_sito);
        $this->assertSame($sito->id, $setting?->sito_id);
    }

    public function test_sito_switcher_stores_active_sito_in_session(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $default = Sito::create([
            'nome'       => 'Impianto A',
            'is_active'  => true,
            'is_default' => true,
        ]);

        $second = Sito::create([
            'nome'       => 'Impianto B',
            'is_active'  => true,
            'is_default' => false,
        ]);

        Livewire::actingAs($admin)
            ->test(SitoSwitcher::class)
            ->call('switchSito', $second->id)
            ->assertSet('open', false);

        $this->assertSame($second->id, SitoContext::activeSitoId());
        $this->assertNotSame($default->id, session(SitoContext::SESSION_KEY));
    }

    public function test_admin_can_manage_siti(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.siti'))
            ->assertOk()
            ->assertSee('Gestione impianti');

        Livewire::actingAs($admin)
            ->test(SitiIndex::class)
            ->call('openCreateModal')
            ->set('formNome', 'Sede Nord')
            ->set('formNumIscrSito', 'RENTRI-NORD-01')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('siti', [
            'nome'          => 'Sede Nord',
            'num_iscr_sito' => 'RENTRI-NORD-01',
        ]);
    }
}
