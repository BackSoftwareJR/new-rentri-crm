<?php

namespace Tests\Feature\Sprint1;

use App\Http\Livewire\Segreteria\CodiciCer\CodiciCerIndex;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CodiciCerAuthorizationTest extends TestCase
{
    public function test_operatore_cannot_access_codici_cer_index(): void
    {
        Role::findOrCreate('operatore');
        $user = User::factory()->create();
        $user->assignRole('operatore');

        Livewire::actingAs($user)
            ->test(CodiciCerIndex::class)
            ->assertForbidden();
    }
}
