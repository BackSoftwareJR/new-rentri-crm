<?php

namespace Tests\Feature\Sprint122;

use App\Domain\Azienda\AziendaSettingService;
use App\Domain\Fatturazione\FatturaPaXmlGeneratorService;
use App\Domain\Fatturazione\FatturazioneService;
use App\Domain\Fatturazione\SdiTransmissionService;
use App\Http\Livewire\Segreteria\Fatturazione\FatturaShow;
use App\Enums\SdiStato;
use App\Jobs\TransmitFatturaSdiJob;
use App\Models\Anagrafica;
use App\Models\Fattura;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SdiTransmissionJobTest extends TestCase
{
    private function configureAzienda(): void
    {
        $service = app(AziendaSettingService::class);
        $service->set('ragione_sociale', 'Autodemolizioni Test SRL');
        $service->set('piva', '12345678901');
        $service->set('codice_fiscale', '12345678901');
        $service->set('indirizzo', 'Via Roma 1');
        $service->set('comune', 'Milano');
        $service->set('cap', '20100');
        $service->set('provincia', 'MI');
    }

    private function emittedFattura(): Fattura
    {
        $this->configureAzienda();

        $anagrafica = Anagrafica::factory()->create([
            'ragione_sociale' => 'Cliente Test SPA',
            'piva'            => '98765432109',
            'codice_sdi'      => 'ABCDEF',
        ]);

        $fatturazione = app(FatturazioneService::class);
        $fattura = $fatturazione->creaFattura([
            'tipo'           => 'fattura',
            'anagrafica_id'  => $anagrafica->id,
            'data_emissione' => '2026-06-08',
        ]);

        $fatturazione->aggiungiRiga($fattura, [
            'descrizione'     => 'Servizio rottamazione',
            'quantita'        => 1,
            'prezzo_unitario' => 100.00,
        ]);

        $fatturazione->emettiFattura($fattura->fresh());

        return $fattura->fresh(['anagrafica', 'righe']);
    }

    public function test_stub_transmission_updates_sdi_stato(): void
    {
        Storage::fake('local');
        config(['services.sdi.stub' => true]);

        $fattura = $this->emittedFattura();

        $result = app(SdiTransmissionService::class)->transmit($fattura);

        $this->assertTrue($result['stub']);
        $this->assertStringStartsWith('SDI-STUB-', $result['protocollo']);

        $fattura->refresh();
        $this->assertSame(SdiStato::Inviata->value, $fattura->sdi_stato);
        $this->assertNotNull($fattura->fattura_pa_xml_path);
    }

    public function test_job_dispatched_from_fattura_show(): void
    {
        Storage::fake('local');
        Queue::fake();

        $fattura = $this->emittedFattura();
        app(FatturaPaXmlGeneratorService::class)->generate($fattura);
        $fattura->refresh();

        $segreteria = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($segreteria)
            ->test(FatturaShow::class, ['fattura' => $fattura])
            ->call('inviaSdi')
            ->assertHasNoErrors();

        Queue::assertPushed(TransmitFatturaSdiJob::class, function (TransmitFatturaSdiJob $job) use ($fattura) {
            return $job->fatturaId === $fattura->id;
        });
    }

    public function test_job_handle_transmits_in_stub_mode(): void
    {
        Storage::fake('local');
        config(['services.sdi.stub' => true]);

        $fattura = $this->emittedFattura();

        (new TransmitFatturaSdiJob($fattura->id))->handle(app(SdiTransmissionService::class));

        $fattura->refresh();
        $this->assertSame(SdiStato::Inviata->value, $fattura->sdi_stato);
    }
}
