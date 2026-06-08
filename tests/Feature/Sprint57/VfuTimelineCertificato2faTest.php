<?php

namespace Tests\Feature\Sprint57;

use App\Domain\Vfu\VfuTimelineService;
use App\Enums\VfuStato;
use App\Http\Livewire\Operatore\Ricambi;
use App\Http\Livewire\Segreteria\Vfu\VfuShow;
use App\Models\EcommerceProdotto;
use App\Models\User;
use App\Models\VfuRegistration;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class VfuTimelineCertificato2faTest extends TestCase
{
    public function test_vfu_timeline_marks_bonificato_as_current_step(): void
    {
        $vfu = VfuRegistration::factory()->bonificato()->create();
        $steps = app(VfuTimelineService::class)->steps($vfu);

        $current = collect($steps)->firstWhere('status', 'current');
        $this->assertNotNull($current);
        $this->assertSame('bonificato', $current['key']);

        $completed = collect($steps)->where('status', 'completed')->pluck('key')->all();
        $this->assertContains('registrazione', $completed);
        $this->assertContains('accettazione', $completed);
        $this->assertContains('bonifica', $completed);
    }

    public function test_vfu_show_renders_timeline_component(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $vfu = VfuRegistration::factory()->attesaBonifica()->create(['stato' => VfuStato::InBonifica]);

        Livewire::actingAs($user)
            ->test(VfuShow::class, ['vfuRegistration' => $vfu])
            ->assertSee('Avanzamento pratica')
            ->assertSee('seg-vfu-timeline', false)
            ->assertSee('Bonifica');
    }

    public function test_vfu_show_certificato_preview_and_print_controls(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $vfu = VfuRegistration::factory()->bonificato()->create(['targa' => 'TL57ABC']);

        Livewire::actingAs($user)
            ->test(VfuShow::class, ['vfuRegistration' => $vfu])
            ->assertSee('Anteprima certificato')
            ->call('toggleCertificatoPreview')
            ->assertSee('seg-certificato-preview', false)
            ->assertSee('Stampa')
            ->assertSee('TL57ABC')
            ->assertSee('cert-header', false);
    }

    public function test_operatore_can_upload_foto_bulk_stub(): void
    {
        Storage::fake('public');

        $user = User::where('email', 'operatore@example.com')->firstOrFail();

        $this->assertTrue(Gate::forUser($user)->allows('uploadPhotos', EcommerceProdotto::class));

        Livewire::actingAs($user)
            ->test(Ricambi::class)
            ->set('prodottoSelezionato', EcommerceProdotto::factory()->create()->id)
            ->set('fotoBulk', [
                UploadedFile::fake()->image('ricambio-1.jpg'),
                UploadedFile::fake()->image('ricambio-2.jpg'),
            ])
            ->call('uploadFotoBulk')
            ->assertHasNoErrors()
            ->assertSee('2 foto collegate');
    }

    public function test_segreteria_cannot_upload_foto_bulk(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $this->assertFalse(Gate::forUser($user)->allows('uploadPhotos', EcommerceProdotto::class));
    }

    public function test_two_factor_prep_runbook_exists(): void
    {
        $path = base_path('docs/2FA-PREP-RUNBOOK.md');

        $this->assertFileExists($path);
        $this->assertStringContainsString('enforcement configurabile', file_get_contents($path));
        $this->assertStringContainsString('TWO_FACTOR_ENFORCE_ADMIN_SEGRETERIA', file_get_contents($path));
    }

    public function test_timeline_shows_cancelled_for_annullato_vfu(): void
    {
        $vfu = VfuRegistration::factory()->create(['stato' => VfuStato::Annullato]);
        $steps = app(VfuTimelineService::class)->steps($vfu);

        $this->assertCount(1, $steps);
        $this->assertSame('cancelled', $steps[0]['status']);
        $this->assertSame('annullato', $steps[0]['key']);
    }
}
