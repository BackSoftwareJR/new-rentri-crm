<?php

namespace Tests\Feature\Sprint11;

use App\Http\Livewire\Segreteria\Rentri\RentriTransazioneShow;
use App\Http\Livewire\Segreteria\Rentri\RentriTransazioniIndex;
use App\Models\RentriTransazione;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RentriTransazioniHttpTest extends TestCase
{
    public function test_segreteria_can_access_transazioni_list_and_detail(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $tx = $this->seedTransazione('health', 'completata');

        $this->actingAs($user)
            ->get(route('segreteria.rentri.transazioni'))
            ->assertOk()
            ->assertSee('Storico transazioni API');

        $this->actingAs($user)
            ->get(route('segreteria.rentri.transazioni.show', $tx))
            ->assertOk()
            ->assertSee($tx->transazione_id);

        Livewire::actingAs($user)
            ->test(RentriTransazioniIndex::class)
            ->assertSuccessful()
            ->assertSee('/health');

        Livewire::actingAs($user)
            ->test(RentriTransazioneShow::class, ['transazione' => $tx])
            ->assertSuccessful()
            ->assertSee('Request')
            ->assertSee('Response')
            ->assertSee('X-RENTRI-Signature');
    }

    public function test_operatore_cannot_access_transazioni(): void
    {
        Role::findOrCreate('operatore');
        $user = User::where('email', 'operatore@example.com')->firstOrFail();
        $tx = $this->seedTransazione('fir', 'completata');

        $this->actingAs($user)
            ->get(route('segreteria.rentri.transazioni'))
            ->assertForbidden();

        Livewire::actingAs($user)
            ->test(RentriTransazioniIndex::class)
            ->assertForbidden();

        Livewire::actingAs($user)
            ->test(RentriTransazioneShow::class, ['transazione' => $tx])
            ->assertForbidden();
    }

    public function test_filters_by_tipo_api_and_stato(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $this->seedTransazione('health', 'completata');
        $this->seedTransazione('fir', 'errore', [
            'request_json' => [
                'method'   => 'POST',
                'endpoint' => '/fir/vidima',
                'payload'  => ['trasporto_id' => 1],
                'headers'  => ['X-RENTRI-Signature' => 'stub:xyz…'],
            ],
            'response_json' => ['error' => 'fail'],
        ]);

        Livewire::actingAs($user)
            ->test(RentriTransazioniIndex::class)
            ->set('tipo_api', 'fir')
            ->assertSee('/fir/vidima')
            ->assertSee('FIR');

        Livewire::actingAs($user)
            ->test(RentriTransazioniIndex::class)
            ->set('stato', 'errore')
            ->assertSee('Errore');
    }

    public function test_rentri_page_links_to_storico_api(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('segreteria.rentri'))
            ->assertOk()
            ->assertSee('Storico API');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function seedTransazione(string $tipoApi, string $stato, array $overrides = []): RentriTransazione
    {
        return RentriTransazione::create(array_merge([
            'transazione_id' => (string) Str::uuid(),
            'tipo_api'       => $tipoApi,
            'stato'          => $stato,
            'request_json'   => [
                'method'   => 'GET',
                'endpoint' => '/health',
                'payload'  => [],
                'headers'  => ['X-RENTRI-Signature' => 'stub:abc123…'],
            ],
            'response_json'  => ['status' => 'ok', 'stub' => true],
            'completed_at'   => now(),
        ], $overrides));
    }
}
