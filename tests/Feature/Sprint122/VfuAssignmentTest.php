<?php

namespace Tests\Feature\Sprint122;

use App\Domain\Bonifica\BonificaService;
use App\Domain\Vfu\VfuAccettazioneService;
use App\Enums\NotificationEvent;
use App\Enums\VfuStato;
use App\Http\Livewire\Operatore\Bonifica;
use App\Http\Livewire\Segreteria\Vfu\VfuShow;
use App\Models\User;
use App\Models\VfuRegistration;
use Livewire\Livewire;
use Tests\TestCase;

class VfuAssignmentTest extends TestCase
{
    public function test_assegna_operatore_sets_fk_and_notifies_operatore(): void
    {
        $segreteria = User::where('email', 'segreteria@example.com')->firstOrFail();
        $operatore = User::where('email', 'operatore@example.com')->firstOrFail();
        $altroOperatore = User::factory()->create();
        $altroOperatore->assignRole('operatore');

        $vfu = VfuRegistration::factory()->accettatoPerBonifica()->create([
            'targa' => 'ASSIGN01',
        ]);

        $this->actingAs($segreteria);

        Livewire::test(VfuShow::class, ['vfuRegistration' => $vfu])
            ->set('operatoreAssegnatoId', $operatore->id)
            ->call('assegnaOperatore')
            ->assertHasNoErrors();

        $vfu->refresh();
        $this->assertSame($operatore->id, $vfu->operatore_assegnato_id);

        $notification = $operatore->fresh()->notifications()->first();
        $this->assertNotNull($notification);
        $this->assertSame(NotificationEvent::VfuOperatoreAssegnato->value, $notification->data['event']);
        $this->assertStringContainsString('ASSIGN01', $notification->data['title']);
    }

    public function test_bonifica_list_filters_assigned_vfus_by_default(): void
    {
        $operatore = User::where('email', 'operatore@example.com')->firstOrFail();
        $altroOperatore = User::factory()->create();
        $altroOperatore->assignRole('operatore');

        VfuRegistration::factory()->accettatoPerBonifica()->create([
            'targa' => 'MIO001',
            'operatore_assegnato_id' => $operatore->id,
        ]);

        VfuRegistration::factory()->accettatoPerBonifica()->create([
            'targa' => 'ALTRO99',
            'operatore_assegnato_id' => $altroOperatore->id,
        ]);

        VfuRegistration::factory()->accettatoPerBonifica()->create([
            'targa' => 'LIBERO1',
            'operatore_assegnato_id' => null,
        ]);

        $this->actingAs($operatore);

        Livewire::test(Bonifica::class)
            ->assertSet('soloAssegnati', true)
            ->assertSee('MIO001')
            ->assertDontSee('ALTRO99')
            ->assertDontSee('LIBERO1');

        Livewire::test(Bonifica::class)
            ->set('soloAssegnati', false)
            ->assertSee('MIO001')
            ->assertSee('ALTRO99')
            ->assertSee('LIBERO1');
    }

    public function test_bonifica_service_applies_assignment_filter(): void
    {
        $operatore = User::where('email', 'operatore@example.com')->firstOrFail();

        VfuRegistration::factory()->accettatoPerBonifica()->create([
            'operatore_assegnato_id' => $operatore->id,
        ]);

        VfuRegistration::factory()->accettatoPerBonifica()->create([
            'operatore_assegnato_id' => null,
        ]);

        $this->actingAs($operatore);

        $service = app(BonificaService::class);

        $assigned = $service->queryVeicoliDaBonificare([
            'solo_assegnati' => true,
            'operatore_id' => $operatore->id,
        ])->count();

        $all = $service->queryVeicoliDaBonificare([
            'solo_assegnati' => false,
        ])->count();

        $this->assertSame(1, $assigned);
        $this->assertSame(2, $all);
    }
}
