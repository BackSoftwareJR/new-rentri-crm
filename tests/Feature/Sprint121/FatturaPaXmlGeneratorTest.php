<?php

namespace Tests\Feature\Sprint121;

use App\Domain\Azienda\AziendaSettingService;
use App\Domain\Fatturazione\FatturaPaXmlGeneratorService;
use App\Domain\Fatturazione\FatturazioneService;
use App\Models\Anagrafica;
use App\Models\CompanySetting;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FatturaPaXmlGeneratorTest extends TestCase
{
    private function segreteria(): User
    {
        return User::where('email', 'segreteria@example.com')->firstOrFail();
    }

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

    public function test_generates_valid_fatturapa_xml_structure(): void
    {
        Storage::fake('local');
        $this->configureAzienda();

        $anagrafica = Anagrafica::factory()->create([
            'ragione_sociale' => 'Cliente Test SPA',
            'piva'            => '98765432109',
            'codice_sdi'      => 'ABCDEF',
            'indirizzo'       => 'Via Verdi 2',
            'citta'           => 'Torino',
            'cap'             => '10100',
            'provincia'       => 'TO',
        ]);

        $fatturazione = app(FatturazioneService::class);
        $fattura = $fatturazione->creaFattura([
            'tipo'          => 'fattura',
            'anagrafica_id' => $anagrafica->id,
            'data_emissione' => '2026-06-08',
            'iva_percentuale' => 22,
        ]);

        $fatturazione->aggiungiRiga($fattura, [
            'descrizione'     => 'Servizio rottamazione veicolo',
            'quantita'        => 1,
            'prezzo_unitario' => 150.00,
        ]);

        $fatturazione->emettiFattura($fattura->fresh());

        $xml = app(FatturaPaXmlGeneratorService::class)->generate($fattura->fresh(['anagrafica', 'righe']));

        $this->assertStringContainsString('FatturaElettronica', $xml);
        $this->assertStringContainsString('DatiTrasmissione', $xml);
        $this->assertStringContainsString('CedentePrestatore', $xml);
        $this->assertStringContainsString('CessionarioCommittente', $xml);
        $this->assertStringContainsString('DettaglioLinee', $xml);
        $this->assertStringContainsString('DatiRiepilogo', $xml);
        $this->assertStringContainsString('DatiPagamento', $xml);

        $dom = new DOMDocument();
        $this->assertTrue($dom->loadXML($xml));

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('p', 'http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2');

        $this->assertGreaterThan(0, $xpath->query('//p:FatturaElettronica')->length);
        $this->assertGreaterThan(0, $xpath->query('//DatiTrasmissione/ProgressivoInvio')->length);
        $this->assertGreaterThan(0, $xpath->query('//DatiBeniServizi/DettaglioLinee')->length);

        $fattura->refresh();
        $this->assertNotNull($fattura->fattura_pa_xml_path);
        Storage::disk('local')->assertExists($fattura->fattura_pa_xml_path);
    }

    public function test_xml_generation_requires_emitted_invoice(): void
    {
        $this->configureAzienda();

        $anagrafica = Anagrafica::factory()->create(['piva' => '98765432109']);
        $fattura = app(FatturazioneService::class)->creaFattura([
            'tipo'          => 'fattura',
            'anagrafica_id' => $anagrafica->id,
        ]);

        $this->expectException(\LogicException::class);
        app(FatturaPaXmlGeneratorService::class)->generate($fattura);
    }

    public function test_xml_generation_requires_company_piva(): void
    {
        Storage::fake('local');
        CompanySetting::query()->where('key', 'company_piva')->delete();

        $anagrafica = Anagrafica::factory()->create(['piva' => '98765432109']);
        $fatturazione = app(FatturazioneService::class);

        $fattura = $fatturazione->creaFattura([
            'tipo'          => 'fattura',
            'anagrafica_id' => $anagrafica->id,
        ]);
        $fatturazione->aggiungiRiga($fattura, [
            'descrizione'     => 'Test',
            'quantita'        => 1,
            'prezzo_unitario' => 10,
        ]);
        $fatturazione->emettiFattura($fattura->fresh());

        $this->expectException(\LogicException::class);
        app(FatturaPaXmlGeneratorService::class)->generate($fattura->fresh(['anagrafica', 'righe']));
    }
}
