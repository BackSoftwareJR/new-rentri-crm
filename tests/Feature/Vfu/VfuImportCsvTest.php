<?php

namespace Tests\Feature\Vfu;

use App\Domain\Vfu\VfuAccettazioneService;
use App\Http\Livewire\Segreteria\Vfu\VfuImportCsv;
use App\Http\Livewire\TimelineWidget;
use App\Models\Anagrafica;
use App\Models\Fattura;
use App\Models\User;
use App\Models\VfuRegistration;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class VfuImportCsvTest extends TestCase
{
    public function test_accetta_batch_imports_valid_rows(): void
    {
        $result = app(VfuAccettazioneService::class)->accettaBatch([
            [
                'targa'              => 'AA111BB',
                'telaio'             => 'ZFA11100001111111',
                'marca'              => 'FIAT',
                'modello'            => '500',
                'anno'               => '2018',
                'colore'             => 'Rosso',
                'data_accettazione'  => '08/06/2026',
                'nome_proprietario'  => 'Luigi Verdi',
                'cf_proprietario'    => 'VRDLGU80A01H501X',
                'email_proprietario' => 'luigi@example.com',
            ],
        ]);

        $this->assertSame(1, $result['imported']);
        $this->assertSame([], $result['errors']);

        $vfu = VfuRegistration::where('targa', 'AA111BB')->first();
        $this->assertNotNull($vfu);
        $this->assertSame('ZFA11100001111111', $vfu->telaio);
        $this->assertSame('Luigi Verdi', $vfu->proprietario);
        $this->assertSame('2018-01-01', $vfu->data_consegna?->toDateString());
        $this->assertStringContainsString('Colore: Rosso', (string) $vfu->note);
    }

    public function test_accetta_batch_collects_row_errors(): void
    {
        $result = app(VfuAccettazioneService::class)->accettaBatch([
            [
                'targa'              => '',
                'telaio'             => '',
                'marca'              => '',
                'modello'            => '',
                'anno'               => '',
                'colore'             => '',
                'data_accettazione'  => '',
                'nome_proprietario'  => '',
                'cf_proprietario'    => '',
                'email_proprietario' => 'invalid-email',
            ],
        ]);

        $this->assertSame(0, $result['imported']);
        $this->assertCount(1, $result['errors']);
    }

    public function test_vfu_import_csv_livewire_import(): void
    {
        Storage::fake('local');
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $headers = implode(';', VfuAccettazioneService::csvImportHeaders());
        $row = implode(';', [
            'BB222CC',
            'ZFA22200002222222',
            'OPEL',
            'Corsa',
            '2019',
            'Blu',
            '08/06/2026',
            'Anna Bianchi',
            'BNCNNA85D41H501Y',
            'anna@example.com',
        ]);
        $csv = $headers."\n".$row."\n";

        $file = UploadedFile::fake()->createWithContent('vfu.csv', $csv);

        Livewire::actingAs($user)
            ->test(VfuImportCsv::class)
            ->call('openModal')
            ->set('csvFile', $file)
            ->call('import')
            ->assertSet('importResult.imported', 1)
            ->assertSet('importResult.errors', []);

        $this->assertDatabaseHas('vfu_registrations', ['targa' => 'BB222CC']);
    }

    public function test_timeline_widget_renders_activity_for_fattura(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $anagrafica = Anagrafica::factory()->create();
        $fattura = Fattura::create([
            'numero_fattura'  => 'FT-TL-001',
            'tipo'            => 'fattura',
            'anagrafica_id'   => $anagrafica->id,
            'data_emissione'  => now()->toDateString(),
            'stato'           => 'bozza',
            'imponibile'      => 0,
            'iva_percentuale' => 22,
            'iva_importo'     => 0,
            'totale'          => 0,
        ]);

        app(\App\Domain\Audit\ActivityLogService::class)->record(
            'fatturazione',
            'Fattura creata',
            $fattura,
            userId: $user->id,
        );

        Livewire::test(TimelineWidget::class, ['subject' => $fattura])
            ->assertSee('Fattura creata')
            ->assertSee($user->name);
    }
}
