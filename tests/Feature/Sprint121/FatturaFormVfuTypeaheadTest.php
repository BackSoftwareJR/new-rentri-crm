<?php

namespace Tests\Feature\Sprint121;

use App\Http\Livewire\Segreteria\Fatturazione\FatturaForm;
use App\Models\Sito;
use App\Models\User;
use App\Models\VfuRegistration;
use App\Support\Sito\SitoContext;
use Livewire\Livewire;
use Tests\TestCase;

class FatturaFormVfuTypeaheadTest extends TestCase
{
    private function segreteria(): User
    {
        return User::where('email', 'segreteria@example.com')->firstOrFail();
    }

    public function test_vfu_search_filters_results_by_active_sito(): void
    {
        $sitoA = Sito::create(['nome' => 'Nord', 'is_active' => true, 'is_default' => true]);
        $sitoB = Sito::create(['nome' => 'Sud', 'is_active' => true, 'is_default' => false]);

        SitoContext::setActiveSitoId($sitoA->id);
        VfuRegistration::factory()->create(['targa' => 'UNIQUEA1', 'marca' => 'Fiat']);

        SitoContext::setActiveSitoId($sitoB->id);
        VfuRegistration::factory()->create(['targa' => 'UNIQUEB1', 'marca' => 'Fiat']);

        $this->actingAs($this->segreteria());
        SitoContext::setActiveSitoId($sitoA->id);

        Livewire::test(FatturaForm::class)
            ->set('vfuSearch', 'UNIQUEA')
            ->assertSet('vfuSearch', 'UNIQUEA')
            ->tap(function ($component) {
                $list = $component->instance()->vfuList();
                $this->assertCount(1, $list);
                $this->assertSame('UNIQUEA1', $list->first()->targa);
            });
    }
}
