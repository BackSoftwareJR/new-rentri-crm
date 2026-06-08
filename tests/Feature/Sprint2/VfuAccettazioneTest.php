<?php

namespace Tests\Feature\Sprint2;

use App\Domain\Vfu\VfuAccettazioneService;
use App\Domain\Vfu\VfuDocumentService;
use App\Enums\RegistroMovimentoTipo;
use App\Enums\VfuStato;
use App\Enums\VfuTipoDocumento;
use App\Models\MagazzinoRifiuto;
use App\Models\RegistroMovimento;
use App\Models\User;
use App\Models\VfuRegistration;
use Database\Seeders\CodiceCerSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VfuAccettazioneTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CodiceCerSeeder::class);
        Storage::fake('public');
    }

    public function test_creates_vfu_draft(): void
    {
        $service = app(VfuAccettazioneService::class);

        $vfu = $service->saveDraft(null, [
            'targa' => 'AB123CD',
            'telaio' => 'WF0XXXGCDG1234567',
            'codice_motore' => 'MOT-001',
            'marca' => 'FIAT',
            'modello' => 'PANDA',
            'peso_kg' => 950,
        ]);

        $this->assertDatabaseHas('vfu_registrations', [
            'id' => $vfu->id,
            'targa' => 'AB123CD',
            'stato' => VfuStato::Bozza->value,
        ]);
    }

    public function test_uploads_document(): void
    {
        $vfu = VfuRegistration::factory()->create();
        $file = UploadedFile::fake()->create('carta.pdf', 100, 'application/pdf');

        $doc = app(VfuDocumentService::class)->store(
            $vfu,
            $file,
            VfuTipoDocumento::CartaCircolazione,
        );

        $this->assertDatabaseHas('vfu_documents', [
            'id' => $doc->id,
            'vfu_registration_id' => $vfu->id,
            'tipo' => VfuTipoDocumento::CartaCircolazione->value,
        ]);
        Storage::disk('public')->assertExists($doc->path);
    }

    public function test_complete_accettazione_creates_registro_movimento_carico(): void
    {
        $vfu = VfuRegistration::factory()->inAccettazione()->create([
            'peso_kg' => 1200,
        ]);

        $documents = app(VfuDocumentService::class);
        $documents->store($vfu, UploadedFile::fake()->create('id.pdf'), VfuTipoDocumento::DocumentoIdentita);
        $documents->store($vfu, UploadedFile::fake()->create('cc.pdf'), VfuTipoDocumento::CartaCircolazione);

        $completed = app(VfuAccettazioneService::class)->completeAccettazione($vfu->fresh('documents'));

        $this->assertSame(VfuStato::Accettato, $completed->stato);
        $this->assertNotNull($completed->data_accettazione);

        $this->assertDatabaseHas('registro_movimenti', [
            'source_type' => RegistroMovimento::SOURCE_VFU_REGISTRATION,
            'source_id' => $vfu->id,
            'tipo' => RegistroMovimentoTipo::Carico->value,
        ]);

        $movimento = RegistroMovimento::where('source_id', $vfu->id)->first();
        $this->assertSame('16.01.04*', $movimento->codiceCer->codice);
        $this->assertEquals(1200.0, (float) $movimento->peso_kg);

        $this->assertSame(
            1200.0,
            (float) MagazzinoRifiuto::where('codice_cer_id', $movimento->codice_cer_id)->value('quantita_attuale_kg')
        );
    }

    public function test_vfu_index_requires_auth(): void
    {
        $this->get(route('segreteria.vfu.index'))->assertRedirect(route('login'));
    }

    public function test_segreteria_can_access_vfu_index(): void
    {
        $user = User::where('email', 'segreteria@example.com')->first();

        $this->actingAs($user)
            ->get(route('segreteria.vfu.index'))
            ->assertOk();
    }
}
