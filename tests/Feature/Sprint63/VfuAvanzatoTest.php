<?php

namespace Tests\Feature\Sprint63;

use App\Domain\Vfu\VfuDocumentoService;
use App\Domain\Vfu\VfuStoricoExportService;
use App\Enums\VfuAllegatoTipo;
use App\Http\Livewire\Segreteria\Vfu\VfuIndex;
use App\Http\Livewire\Segreteria\Vfu\VfuShow;
use App\Models\User;
use App\Models\VfuDocumento;
use App\Models\VfuRegistration;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class VfuAvanzatoTest extends TestCase
{
    public function test_vfu_documento_service_uploads_pdf_allegato(): void
    {
        Storage::fake('public');

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $vfu = VfuRegistration::factory()->create();
        $file = UploadedFile::fake()->create('contratto.pdf', 100, 'application/pdf');

        $documento = app(VfuDocumentoService::class)->upload(
            $vfu,
            $file,
            VfuAllegatoTipo::Contratto,
            $user,
        );

        $this->assertSame(VfuAllegatoTipo::Contratto, $documento->tipo);
        $this->assertSame($user->id, $documento->uploaded_by);
        Storage::disk('public')->assertExists($documento->path);
    }

    public function test_vfu_documento_policy_allows_segretaria_on_demo_scope(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $vfu = VfuRegistration::factory()->create();

        $this->assertTrue(Gate::forUser($user)->allows('create', [VfuDocumento::class, $vfu]));
        $this->assertTrue(Gate::forUser($user)->allows('exportStorico', $vfu));
    }

    public function test_vfu_show_uploads_and_lists_allegato(): void
    {
        Storage::fake('public');

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $vfu = VfuRegistration::factory()->create(['targa' => 'S63TEST']);

        Livewire::actingAs($user)
            ->test(VfuShow::class, ['vfuRegistration' => $vfu])
            ->assertSee('Allegati pratica')
            ->set('allegatoTipo', 'foto')
            ->set('allegatoUpload', UploadedFile::fake()->image('veicolo.jpg'))
            ->call('uploadAllegato')
            ->assertHasNoErrors()
            ->assertSee('veicolo.jpg');

        $this->assertDatabaseHas('vfu_documenti', [
            'vfu_registration_id' => $vfu->id,
            'tipo' => 'foto',
            'original_name' => 'veicolo.jpg',
        ]);
    }

    public function test_vfu_storico_export_csv_contains_timeline_rows(): void
    {
        $vfu = VfuRegistration::factory()->bonificato()->create(['targa' => 'EX63CSV']);
        $export = app(VfuStoricoExportService::class);
        $rows = $export->rowsFor($vfu);

        $this->assertNotEmpty($rows);
        $this->assertSame('EX63CSV', $rows[0]['targa']);
        $this->assertTrue(collect($rows)->contains(fn ($r) => $r['step_key'] === 'bonificato'));

        ob_start();
        $export->exportCsvFor($vfu)->sendContent();
        $csv = ob_get_clean();

        $this->assertStringContainsString('EX63CSV', $csv);
        $this->assertStringContainsString('bonificato', $csv);
        $this->assertStringContainsString('step_key', $csv);
    }

    public function test_vfu_index_export_storico_respects_stato_filter(): void
    {
        VfuRegistration::factory()->create(['targa' => 'FILTRATA']);
        VfuRegistration::factory()->bonificato()->create(['targa' => 'BONIF63']);

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(VfuIndex::class)
            ->set('stato', 'bonificato')
            ->call('exportStoricoCsv')
            ->assertSuccessful();

        $export = app(VfuStoricoExportService::class);
        $rows = $export->filteredQuery(['stato' => 'bonificato'])->get();

        $this->assertTrue($rows->every(fn ($v) => $v->stato->value === 'bonificato'));
        $this->assertTrue($rows->contains(fn ($v) => $v->targa === 'BONIF63'));
    }

    public function test_vfu_show_deletes_allegato_with_policy(): void
    {
        Storage::fake('public');

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $vfu = VfuRegistration::factory()->create();
        $documento = app(VfuDocumentoService::class)->upload(
            $vfu,
            UploadedFile::fake()->create('temp.pdf', 50, 'application/pdf'),
            VfuAllegatoTipo::Altro,
            $user,
        );

        Livewire::actingAs($user)
            ->test(VfuShow::class, ['vfuRegistration' => $vfu])
            ->call('deleteAllegato', $documento->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('vfu_documenti', ['id' => $documento->id]);
        Storage::disk('public')->assertMissing($documento->path);
    }
}
