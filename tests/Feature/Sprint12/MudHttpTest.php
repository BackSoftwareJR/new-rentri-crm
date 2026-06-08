<?php

namespace Tests\Feature\Sprint12;

use App\Domain\Mud\MudService;
use App\Enums\MudStato;
use App\Enums\RegistroMovimentoTipo;
use App\Http\Livewire\Segreteria\Mud\MudIndex;
use App\Http\Livewire\Segreteria\Mud\MudShow;
use App\Models\CodiceCer;
use App\Models\MagazzinoCaricoManuale;
use App\Models\MudDichiarazione;
use App\Models\RegistroMovimento;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MudHttpTest extends TestCase
{
    public function test_segreteria_can_access_mud_list_and_create_bozza(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $anno = (int) now()->format('Y');
        $this->seedMovimenti($anno);

        $this->actingAs($user)
            ->get(route('segreteria.mud'))
            ->assertOk()
            ->assertSee('MUD — Dichiarazioni ambientali');

        Livewire::actingAs($user)
            ->test(MudIndex::class)
            ->set('anno_riferimento', (string) $anno)
            ->call('creaBozza')
            ->assertHasNoErrors()
            ->assertRedirect(route('segreteria.mud.show', MudDichiarazione::first()));

        $mud = MudDichiarazione::firstOrFail();
        $this->assertSame(MudStato::Bozza, $mud->stato);
        $this->assertNotEmpty($mud->righe);
    }

    public function test_segreteria_can_complete_and_export_mud(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $anno = (int) now()->format('Y');
        $this->seedMovimenti($anno);

        $mud = app(MudService::class)->createBozza($anno, $user->id);

        Livewire::actingAs($user)
            ->test(MudShow::class, ['dichiarazione' => $mud])
            ->call('completa')
            ->assertHasNoErrors();

        $mud->refresh();
        $this->assertSame(MudStato::Completata, $mud->stato);
        $this->assertNotNull($mud->export_payload);
        $this->assertSame('mud-json-stub-v1', $mud->export_payload['formato'] ?? null);

        Livewire::actingAs($user)
            ->test(MudShow::class, ['dichiarazione' => $mud])
            ->call('exportJson')
            ->assertFileDownloaded('mud-'.$anno.'-stub.json');
    }

    public function test_operatore_cannot_access_mud(): void
    {
        Role::findOrCreate('operatore');
        $user = User::where('email', 'operatore@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('segreteria.mud'))
            ->assertForbidden();

        Livewire::actingAs($user)
            ->test(MudIndex::class)
            ->assertForbidden();
    }

    public function test_policy_allows_segreteria_complete_and_export(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $mud = MudDichiarazione::create([
            'anno_riferimento' => 2020,
            'stato'            => MudStato::Bozza,
            'righe'            => [],
            'user_id'          => $user->id,
        ]);

        $this->assertTrue(Gate::forUser($user)->allows('complete', $mud));
        $this->assertTrue(Gate::forUser($user)->allows('export', $mud));
    }

    private function seedMovimenti(int $anno): void
    {
        $cer = CodiceCer::factory()->create();

        RegistroMovimento::create([
            'tipo'           => RegistroMovimentoTipo::Carico,
            'codice_cer_id'  => $cer->id,
            'peso_kg'        => 100,
            'data_movimento' => now()->setYear($anno)->startOfYear()->addMonth(),
            'source_type'    => MagazzinoCaricoManuale::class,
            'source_id'      => 1,
        ]);

        RegistroMovimento::create([
            'tipo'           => RegistroMovimentoTipo::Scarico,
            'codice_cer_id'  => $cer->id,
            'peso_kg'        => 30,
            'data_movimento' => now()->setYear($anno)->startOfYear()->addMonths(2),
            'source_type'    => MagazzinoCaricoManuale::class,
            'source_id'      => 2,
        ]);
    }
}
