<?php

namespace Tests\Feature\Sprint53;

use App\Enums\RegistroMovimentoTipo;
use App\Enums\VfuStato;
use App\Enums\FirStato;
use App\Enums\TrasportoStato;
use App\Http\Livewire\Segreteria\Magazzino\RegistroMovimentiIndex;
use App\Http\Livewire\Segreteria\Vfu\VfuAccettazioneWizard;
use App\Http\Livewire\Settings\RentriSettings;
use App\Models\CodiceCer;
use App\Models\Fir;
use App\Models\MagazzinoCaricoManuale;
use App\Models\RegistroMovimento;
use App\Models\Trasporto;
use App\Models\User;
use App\Models\VfuRegistration;
use Livewire\Livewire;
use Tests\TestCase;

class FormSecurityRegistroTest extends TestCase
{
    public function test_vfu_wizard_renders_form_field_with_hint(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(VfuAccettazioneWizard::class)
            ->assertSee('seg-form-group')
            ->assertSee('seg-field-hint')
            ->assertSee('17 caratteri, senza spazi');
    }

    public function test_rentri_settings_renders_form_field_components(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(RentriSettings::class)
            ->assertSee('seg-form-group')
            ->assertSee('Codice fiscale dell\'incaricato interoperabilità MASE');
    }

    public function test_models_block_mass_assignment_of_guarded_fields(): void
    {
        $vfu = VfuRegistration::factory()->create(['stato' => VfuStato::Bozza]);
        $vfu->fill([
            'is_demo'                           => true,
            'bonifica_pericolosi_completata_at' => now(),
            'data_invio_agenzia'                => now(),
            'data_accettazione'                 => now()->toDateString(),
        ]);
        $this->assertFalse((bool) $vfu->is_demo);
        $this->assertNull($vfu->bonifica_pericolosi_completata_at);
        $this->assertNull($vfu->data_invio_agenzia);
        $this->assertNull($vfu->data_accettazione);

        $trasporto = Trasporto::create([
            'codice_cer_id'              => CodiceCer::factory()->create()->id,
            'anagrafica_destinatario_id' => \App\Models\Anagrafica::factory()->create(['tipo' => 'impianto'])->id,
            'quantita_kg'                => 10,
            'stato'                      => TrasportoStato::InPreparazione,
        ]);
        $trasporto->fill(['fir_id' => 999, 'is_demo' => true]);
        $this->assertNull($trasporto->fir_id);
        $this->assertFalse((bool) $trasporto->is_demo);

        $fir = Fir::create([
            'numero_fir'       => 'FIR-GUARD-TEST',
            'codice_blocco'    => 'BLK-GUARD',
            'progressivo'      => 1,
            'stato'            => FirStato::Vidimato,
            'vidimato_at'      => now(),
            'peso_partenza_kg' => 10,
        ]);
        $fir->fill([
            'xfir_protocollo'     => 'HACK-123',
            'xfir_transazione_id' => 'evil-uuid',
            'is_demo'             => true,
        ]);
        $this->assertNull($fir->xfir_protocollo);
        $this->assertNull($fir->xfir_transazione_id);
        $this->assertFalse((bool) $fir->is_demo);
    }

    public function test_post_forms_include_csrf_token(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('name="_token"', false);

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('segreteria.dashboard'))
            ->assertOk()
            ->assertSee('name="_token"', false);
    }

    public function test_registro_movimenti_paginates_server_side(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $cer = CodiceCer::factory()->create();

        for ($i = 0; $i < 30; $i++) {
            RegistroMovimento::create([
                'tipo'           => RegistroMovimentoTipo::Carico,
                'codice_cer_id'  => $cer->id,
                'peso_kg'        => 10 + $i,
                'data_movimento' => now()->subDays($i),
                'source_type'    => MagazzinoCaricoManuale::class,
                'source_id'      => $i + 1,
            ]);
        }

        Livewire::actingAs($user)
            ->test(RegistroMovimentiIndex::class)
            ->assertSee('seg-pagination-wrap')
            ->assertViewHas('movimenti', fn ($paginator) => $paginator->total() >= 30
                && $paginator->perPage() === 25
                && $paginator->hasPages());
    }

    public function test_operatore_bonifica_shows_empty_state_component(): void
    {
        $user = User::where('email', 'operatore@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('operatore.bonifica', ['q' => 'NO-MATCH-S53']))
            ->assertOk()
            ->assertSee('seg-empty-state')
            ->assertSee('Nessun veicolo trovato');
    }
}
