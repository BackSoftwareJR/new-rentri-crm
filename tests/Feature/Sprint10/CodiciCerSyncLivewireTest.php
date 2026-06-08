<?php

namespace Tests\Feature\Sprint10;

use App\Http\Livewire\Segreteria\CodiciCer\CodiciCerIndex;
use App\Models\User;
use App\Services\Rentri\Contracts\RentriCodificheSyncInterface;
use Livewire\Livewire;
use Mockery\MockInterface;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CodiciCerSyncLivewireTest extends TestCase
{
    public function test_sync_da_rentri_shows_success_flash_with_counts(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $this->mock(RentriCodificheSyncInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('sync')->once()->andReturn([
                'created'           => 12,
                'updated'           => 3,
                'deactivated'       => 1,
                'skipped'           => 5,
                'created_codes'     => [],
                'updated_codes'     => [],
                'deactivated_codes' => [],
            ]);
        });

        // In Livewire 4 the component re-renders (including flash partials) before the response
        // event clears flash from the session, so assertSee captures the rendered flash message.
        Livewire::actingAs($user)
            ->test(CodiciCerIndex::class)
            ->call('syncDaRentri')
            ->assertHasNoErrors()
            ->assertSee('Sincronizzati: 12 creati, 3 aggiornati, 1 disattivati.');
    }

    public function test_sync_button_visible_for_authorized_user(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $this->mock(RentriCodificheSyncInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('sync')->andReturn([
                'created'           => 0,
                'updated'           => 0,
                'deactivated'       => 0,
                'skipped'           => 0,
                'created_codes'     => [],
                'updated_codes'     => [],
                'deactivated_codes' => [],
            ]);
        });

        Livewire::actingAs($user)
            ->test(CodiciCerIndex::class)
            ->assertSee('Sincronizza da RENTRI');
    }

    public function test_operatore_cannot_call_sync_da_rentri(): void
    {
        Role::findOrCreate('operatore');
        $user = User::where('email', 'operatore@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(CodiciCerIndex::class)
            ->assertForbidden();
    }

    public function test_sync_da_rentri_shows_error_flash_on_exception(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $this->mock(RentriCodificheSyncInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('sync')
                ->once()
                ->andThrow(new \RuntimeException('API RENTRI non raggiungibile'));
        });

        // Error flash is rendered into the HTML before Livewire 4 clears session flash.
        Livewire::actingAs($user)
            ->test(CodiciCerIndex::class)
            ->call('syncDaRentri')
            ->assertHasNoErrors()
            ->assertSee('Errore durante la sincronizzazione RENTRI: API RENTRI non raggiungibile');
    }

    public function test_unauthorized_user_cannot_see_sync_button(): void
    {
        Role::findOrCreate('operatore');
        $user = User::factory()->create();
        $user->assignRole('operatore');

        // operatore is blocked at mount() via viewAny, so the component returns 403
        Livewire::actingAs($user)
            ->test(CodiciCerIndex::class)
            ->assertForbidden();
    }
}
