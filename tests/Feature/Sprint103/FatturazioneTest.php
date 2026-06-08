<?php

namespace Tests\Feature\Sprint103;

use App\Domain\Fatturazione\FatturazioneService;
use App\Http\Livewire\Segreteria\Fatturazione\FattureIndex;
use App\Models\Anagrafica;
use App\Models\Fattura;
use App\Models\RigaFattura;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class FatturazioneTest extends TestCase
{
    private function segreteria(): User
    {
        return User::where('email', 'segreteria@example.com')->firstOrFail();
    }

    private function operatore(): User
    {
        return User::where('email', 'operatore@example.com')->firstOrFail();
    }

    private function anagrafica(): Anagrafica
    {
        return Anagrafica::factory()->create([
            'ragione_sociale' => 'Test Cliente SRL',
            'piva'            => '12345678901',
        ]);
    }

    // ─── 1. Create fattura ────────────────────────────────────────────────────

    public function test_crea_fattura_crea_record_con_dati_corretti(): void
    {
        $ana = $this->anagrafica();

        /** @var FatturazioneService $service */
        $service = app(FatturazioneService::class);

        $fattura = $service->creaFattura([
            'tipo'           => 'fattura',
            'anagrafica_id'  => $ana->id,
            'data_emissione' => '2026-06-08',
            'iva_percentuale' => 22,
        ]);

        $this->assertDatabaseHas('fatture', [
            'id'             => $fattura->id,
            'tipo'           => 'fattura',
            'anagrafica_id'  => $ana->id,
            'stato'          => 'bozza',
            'iva_percentuale' => 22,
        ]);

        $this->assertStringStartsWith('FT-', $fattura->numero_fattura);
    }

    // ─── 2. Add righe + calcola totali ───────────────────────────────────────

    public function test_aggiungi_righe_e_calcola_totali_correttamente(): void
    {
        $ana     = $this->anagrafica();
        $service = app(FatturazioneService::class);

        $fattura = $service->creaFattura([
            'tipo'           => 'fattura',
            'anagrafica_id'  => $ana->id,
            'iva_percentuale' => 22,
        ]);

        $service->aggiungiRiga($fattura, [
            'descrizione'     => 'Servizio demolizione',
            'quantita'        => 2,
            'prezzo_unitario' => 100.00,
        ]);

        $service->aggiungiRiga($fattura, [
            'descrizione'     => 'Trasporto',
            'quantita'        => 1,
            'prezzo_unitario' => 50.00,
        ]);

        $fattura->refresh();

        // imponibile = 200 + 50 = 250
        $this->assertEquals('250.00', $fattura->imponibile);
        // iva 22% = 55
        $this->assertEquals('55.00', $fattura->iva_importo);
        // totale = 305
        $this->assertEquals('305.00', $fattura->totale);
        $this->assertCount(2, $fattura->righe);
    }

    // ─── 3. Emetti fattura changes stato ─────────────────────────────────────

    public function test_emetti_fattura_cambia_stato_in_emessa(): void
    {
        Storage::fake('local');

        $ana     = $this->anagrafica();
        $service = app(FatturazioneService::class);

        $fattura = $service->creaFattura([
            'tipo'          => 'fattura',
            'anagrafica_id' => $ana->id,
        ]);

        $service->aggiungiRiga($fattura, [
            'descrizione'     => 'Test riga',
            'quantita'        => 1,
            'prezzo_unitario' => 100.00,
        ]);

        $service->emettiFattura($fattura);

        $fattura->refresh();
        $this->assertSame('emessa', $fattura->stato);
        $this->assertNotNull($fattura->pdf_path);
    }

    // ─── 4. Emetti bozza->emessa only ────────────────────────────────────────

    public function test_emetti_fattura_gia_emessa_lancia_eccezione(): void
    {
        Storage::fake('local');

        $ana     = $this->anagrafica();
        $service = app(FatturazioneService::class);

        $fattura = $service->creaFattura([
            'tipo'          => 'fattura',
            'anagrafica_id' => $ana->id,
        ]);

        $service->aggiungiRiga($fattura, ['descrizione' => 'X', 'quantita' => 1, 'prezzo_unitario' => 10]);
        $service->emettiFattura($fattura);

        $this->expectException(\LogicException::class);
        $service->emettiFattura($fattura);
    }

    // ─── 5. Registra pagamento ────────────────────────────────────────────────

    public function test_registra_pagamento_cambia_stato_in_pagata(): void
    {
        Storage::fake('local');

        $ana     = $this->anagrafica();
        $service = app(FatturazioneService::class);

        $fattura = $service->creaFattura([
            'tipo'          => 'fattura',
            'anagrafica_id' => $ana->id,
        ]);
        $service->aggiungiRiga($fattura, ['descrizione' => 'X', 'quantita' => 1, 'prezzo_unitario' => 50]);
        $service->emettiFattura($fattura);

        $dataPagamento = Carbon::parse('2026-06-10');
        $service->registraPagamento($fattura, $dataPagamento);

        $fattura->refresh();
        $this->assertSame('pagata', $fattura->stato);
        $this->assertEquals('2026-06-10', $fattura->data_pagamento->toDateString());
    }

    // ─── 6. PDF generation ───────────────────────────────────────────────────

    public function test_genera_pdf_crea_file_e_restituisce_path(): void
    {
        Storage::fake('local');

        $ana     = $this->anagrafica();
        $service = app(FatturazioneService::class);

        $fattura = $service->creaFattura([
            'tipo'          => 'fattura',
            'anagrafica_id' => $ana->id,
        ]);
        $service->aggiungiRiga($fattura, ['descrizione' => 'Servizio', 'quantita' => 1, 'prezzo_unitario' => 100]);

        $path = $service->generaPdf($fattura);

        $this->assertNotEmpty($path);
        Storage::disk('local')->assertExists($path);
    }

    // ─── 7. RBAC: operatore cannot access fatture index ──────────────────────

    public function test_operatore_non_puo_accedere_a_fatture_index(): void
    {
        $operatore = $this->operatore();

        Livewire::actingAs($operatore)
            ->test(FattureIndex::class)
            ->assertForbidden();
    }

    // ─── 8. Index renders with data ──────────────────────────────────────────

    public function test_fatture_index_renderizza_con_dati(): void
    {
        Storage::fake('local');

        $ana      = $this->anagrafica();
        $service  = app(FatturazioneService::class);
        $user     = $this->segreteria();

        $fattura = $service->creaFattura([
            'tipo'          => 'fattura',
            'anagrafica_id' => $ana->id,
        ]);
        $service->aggiungiRiga($fattura, ['descrizione' => 'Demo', 'quantita' => 1, 'prezzo_unitario' => 200]);

        Livewire::actingAs($user)
            ->test(FattureIndex::class)
            ->assertOk()
            ->assertSee($fattura->numero_fattura)
            ->assertSee('Test Cliente SRL');
    }

    // ─── 9. Annulla fattura ───────────────────────────────────────────────────

    public function test_annulla_fattura_cambia_stato_e_registra_motivo(): void
    {
        Storage::fake('local');

        $ana     = $this->anagrafica();
        $service = app(FatturazioneService::class);

        $fattura = $service->creaFattura([
            'tipo'          => 'fattura',
            'anagrafica_id' => $ana->id,
        ]);

        $service->annulla($fattura, 'Errore di fatturazione');

        $fattura->refresh();
        $this->assertSame('annullata', $fattura->stato);
        $this->assertSame('Errore di fatturazione', $fattura->motivo_annullamento);
    }

    // ─── 10. Numerazione progressiva ─────────────────────────────────────────

    public function test_numerazione_progressiva_incrementa_correttamente(): void
    {
        $ana     = $this->anagrafica();
        $service = app(FatturazioneService::class);

        $f1 = $service->creaFattura(['tipo' => 'fattura', 'anagrafica_id' => $ana->id]);
        $f2 = $service->creaFattura(['tipo' => 'fattura', 'anagrafica_id' => $ana->id]);

        $this->assertNotEquals($f1->numero_fattura, $f2->numero_fattura);

        $year = now()->year;
        $this->assertStringStartsWith("FT-{$year}-", $f1->numero_fattura);
        $this->assertStringStartsWith("FT-{$year}-", $f2->numero_fattura);
    }
}
