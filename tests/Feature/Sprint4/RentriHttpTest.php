<?php

namespace Tests\Feature\Sprint4;

use App\Enums\RegistroMovimentoTipo;
use App\Http\Livewire\Segreteria\Rentri;
use App\Models\CodiceCer;
use App\Models\MagazzinoCaricoManuale;
use App\Models\RegistroMovimento;
use App\Models\RentriTransmissione;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\Support\SeedsRentriCertificate;
use Tests\TestCase;

class RentriHttpTest extends TestCase
{
    use SeedsRentriCertificate;

    public function test_segreteria_can_access_rentri_page(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('segreteria.rentri'))
            ->assertOk();

        Livewire::actingAs($user)
            ->test(Rentri::class)
            ->assertSuccessful()
            ->assertSee('Trasmissione registro');
    }

    public function test_operatore_cannot_access_rentri_page(): void
    {
        Role::findOrCreate('operatore');
        $user = User::where('email', 'operatore@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(Rentri::class)
            ->assertForbidden();
    }

    public function test_segreteria_can_transmit_via_livewire(): void
    {
        $this->seedRentriCertificate();
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $cer = CodiceCer::factory()->create();

        RegistroMovimento::create([
            'tipo'           => RegistroMovimentoTipo::Carico,
            'codice_cer_id'  => $cer->id,
            'peso_kg'        => 15,
            'data_movimento' => now()->subDay(),
            'source_type'    => MagazzinoCaricoManuale::class,
            'source_id'      => 1,
        ]);

        Livewire::actingAs($user)
            ->test(Rentri::class)
            ->set('periodo_da', now()->startOfMonth()->toDateString())
            ->set('periodo_a', now()->toDateString())
            ->call('trasmetti')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('rentri_transmissioni', 1);
        $this->assertSame(1, RegistroMovimento::where('rentri_trasmesso', true)->count());
    }

    public function test_transmit_shows_error_when_no_movimenti(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(Rentri::class)
            ->set('periodo_da', now()->startOfMonth()->toDateString())
            ->set('periodo_a', now()->toDateString())
            ->call('trasmetti')
            ->assertHasErrors(['periodo_da']);

        $this->assertDatabaseCount('rentri_transmissioni', 0);
    }
}
