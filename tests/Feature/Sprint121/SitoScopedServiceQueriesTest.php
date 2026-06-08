<?php

namespace Tests\Feature\Sprint121;

use App\Domain\Trasporti\TrasportoService;
use App\Domain\Vfu\VfuAccettazioneService;
use App\Models\Anagrafica;
use App\Models\CodiceCer;
use App\Models\Fattura;
use App\Models\Sito;
use App\Models\Trasporto;
use App\Models\VfuRegistration;
use App\Support\Sito\SitoContext;
use Tests\TestCase;

class SitoScopedServiceQueriesTest extends TestCase
{
    public function test_vfu_service_paginate_respects_active_sito(): void
    {
        $sitoA = Sito::create(['nome' => 'Nord', 'is_active' => true, 'is_default' => true]);
        $sitoB = Sito::create(['nome' => 'Sud', 'is_active' => true, 'is_default' => false]);

        SitoContext::setActiveSitoId($sitoA->id);
        VfuRegistration::factory()->create(['targa' => 'NORD001']);

        SitoContext::setActiveSitoId($sitoB->id);
        VfuRegistration::factory()->create(['targa' => 'SUD001']);

        $service = app(VfuAccettazioneService::class);

        SitoContext::setActiveSitoId($sitoA->id);
        $this->assertSame(1, $service->paginate()->total());
        $this->assertSame('NORD001', $service->paginate()->first()->targa);

        SitoContext::setActiveSitoId($sitoB->id);
        $this->assertSame(1, $service->paginate()->total());
        $this->assertSame('SUD001', $service->paginate()->first()->targa);
    }

    public function test_trasporto_service_list_respects_active_sito(): void
    {
        $sitoA = Sito::create(['nome' => 'Nord', 'is_active' => true, 'is_default' => true]);
        $sitoB = Sito::create(['nome' => 'Sud', 'is_active' => true, 'is_default' => false]);

        $cer = CodiceCer::factory()->create();
        $dest = Anagrafica::factory()->create();

        SitoContext::setActiveSitoId($sitoA->id);
        Trasporto::create([
            'codice_cer_id' => $cer->id,
            'anagrafica_destinatario_id' => $dest->id,
            'quantita_kg' => 100,
            'stato' => 'in_preparazione',
        ]);

        SitoContext::setActiveSitoId($sitoB->id);
        Trasporto::create([
            'codice_cer_id' => $cer->id,
            'anagrafica_destinatario_id' => $dest->id,
            'quantita_kg' => 200,
            'stato' => 'in_preparazione',
        ]);

        $service = app(TrasportoService::class);

        SitoContext::setActiveSitoId($sitoA->id);
        $this->assertSame(1, $service->list()->total());

        SitoContext::setActiveSitoId($sitoB->id);
        $this->assertSame(1, $service->list()->total());
    }

    public function test_fatture_index_query_respects_active_sito(): void
    {
        $sitoA = Sito::create(['nome' => 'Nord', 'is_active' => true, 'is_default' => true]);
        $sitoB = Sito::create(['nome' => 'Sud', 'is_active' => true, 'is_default' => false]);
        $ana = Anagrafica::factory()->create();

        SitoContext::setActiveSitoId($sitoA->id);
        Fattura::create([
            'numero_fattura' => 'FT-A-001',
            'tipo' => 'fattura',
            'anagrafica_id' => $ana->id,
            'data_emissione' => now(),
            'stato' => 'bozza',
            'iva_percentuale' => 22,
            'imponibile' => 0,
            'iva_importo' => 0,
            'totale' => 0,
        ]);

        SitoContext::setActiveSitoId($sitoB->id);
        Fattura::create([
            'numero_fattura' => 'FT-B-001',
            'tipo' => 'fattura',
            'anagrafica_id' => $ana->id,
            'data_emissione' => now(),
            'stato' => 'bozza',
            'iva_percentuale' => 22,
            'imponibile' => 0,
            'iva_importo' => 0,
            'totale' => 0,
        ]);

        SitoContext::setActiveSitoId($sitoA->id);
        $this->assertSame(1, Fattura::query()->forActiveSito()->count());

        SitoContext::setActiveSitoId($sitoB->id);
        $this->assertSame(1, Fattura::query()->forActiveSito()->count());
    }
}
