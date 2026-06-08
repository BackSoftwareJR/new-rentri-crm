<?php

namespace Tests\Feature\Sprint1;

use App\Http\Livewire\Segreteria\Anagrafiche\AnagraficheIndex;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AnagraficaAuthorizationTest extends TestCase
{
    public function test_operatore_cannot_access_anagrafiche_index(): void
    {
        Role::findOrCreate('operatore');
        $user = User::factory()->create();
        $user->assignRole('operatore');

        Livewire::actingAs($user)
            ->test(AnagraficheIndex::class)
            ->assertForbidden();
    }

    public function test_segreteria_can_access_anagrafiche_index(): void
    {
        $user = User::where('email', 'segreteria@example.com')->first()
            ?? tap(User::factory()->create(), fn ($u) => $u->assignRole('segreteria'));

        Livewire::actingAs($user)
            ->test(AnagraficheIndex::class)
            ->assertSuccessful();
    }
}
