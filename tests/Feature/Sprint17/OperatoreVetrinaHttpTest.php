<?php

namespace Tests\Feature\Sprint17;

use App\Domain\Ecommerce\EcommerceService;
use App\Http\Livewire\Operatore\VetrinaIndex;
use App\Models\EcommerceProdotto;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class OperatoreVetrinaHttpTest extends TestCase
{
    public function test_operatore_can_access_vetrina_with_highlighted_products(): void
    {
        $user = User::where('email', 'operatore@example.com')->firstOrFail();

        $recente = EcommerceProdotto::factory()->create([
            'codice'     => 'VET-NEW',
            'nome'       => 'Ricambio in evidenza',
            'giacenza'   => 2,
            'attivo'     => true,
            'created_at' => Carbon::now(),
        ]);
        EcommerceProdotto::factory()->create([
            'codice'     => 'VET-OLD',
            'nome'       => 'Ricambio vecchio',
            'giacenza'   => 1,
            'attivo'     => true,
            'created_at' => Carbon::now()->subDays(5),
        ]);
        EcommerceProdotto::factory()->create([
            'codice'   => 'VET-OUT',
            'nome'     => 'Esaurito',
            'giacenza' => 0,
            'attivo'   => true,
        ]);

        $this->actingAs($user)
            ->get(route('operatore.vetrina'))
            ->assertOk()
            ->assertSee('Vetrina')
            ->assertSee('Ricambio in evidenza')
            ->assertSee('VET-NEW')
            ->assertDontSee('VET-OUT');

        Livewire::actingAs($user)
            ->test(VetrinaIndex::class)
            ->assertSee('Sfoglia tutti i ricambi');
    }

    public function test_list_prodotti_in_evidenza_returns_latest_available(): void
    {
        EcommerceProdotto::factory()->create([
            'nome'       => 'Primo',
            'giacenza'   => 1,
            'created_at' => Carbon::now()->subHour(),
        ]);
        $ultimo = EcommerceProdotto::factory()->create([
            'nome'       => 'Ultimo',
            'giacenza'   => 1,
            'created_at' => Carbon::now(),
        ]);

        $evidenza = app(EcommerceService::class)->listProdottiInEvidenza(1);

        $this->assertCount(1, $evidenza);
        $this->assertSame($ultimo->id, $evidenza->first()->id);
    }

    public function test_segreteria_cannot_access_vetrina_route(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('operatore.vetrina'))
            ->assertForbidden();
    }
}
