<?php

namespace Tests\Feature\Sprint22;

use App\Domain\Vfu\CertificatoRottamazioneGeneratorService;
use App\Enums\VfuStato;
use App\Http\Livewire\Segreteria\Vfu\VfuShow;
use App\Models\User;
use App\Models\VfuRegistration;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class CertificatoRottamazionePdfTest extends TestCase
{
    public function test_generates_valid_pdf_for_rottamato_vfu(): void
    {
        $vfu = VfuRegistration::factory()->rottamato()->create([
            'targa' => 'AB123CD',
        ]);

        $pdf = app(CertificatoRottamazioneGeneratorService::class)->generatePdf($vfu);

        $this->assertStringStartsWith('%PDF-1.4', $pdf);
        $this->assertStringContainsString('AB123CD', $pdf);
        $this->assertStringContainsString('Certificato di rottamazione', $pdf);
    }

    public function test_generates_html_preview_for_bonificato_vfu(): void
    {
        $vfu = VfuRegistration::factory()->bonificato()->create([
            'targa' => 'XY789ZZ',
            'marca' => 'FIAT',
            'modello' => 'Panda',
        ]);

        $html = app(CertificatoRottamazioneGeneratorService::class)->renderHtml($vfu);

        $this->assertStringContainsString('Certificato di rottamazione', $html);
        $this->assertStringContainsString('XY789ZZ', $html);
        $this->assertStringContainsString('FIAT Panda', $html);
    }

    public function test_rejects_vfu_with_invalid_stato(): void
    {
        $vfu = VfuRegistration::factory()->create([
            'stato' => VfuStato::Bozza,
        ]);

        $this->expectException(ValidationException::class);

        app(CertificatoRottamazioneGeneratorService::class)->generatePdf($vfu);
    }

    public function test_segreteria_can_download_certificato_via_livewire(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $vfu = VfuRegistration::factory()->rottamato()->create([
            'targa' => 'CD456EF',
        ]);

        Livewire::actingAs($user)
            ->test(VfuShow::class, ['vfuRegistration' => $vfu])
            ->assertSee('Scarica PDF')
            ->call('downloadCertificato')
            ->assertFileDownloaded('certificato-rottamazione-CD456EF.pdf');
    }

    public function test_operatore_policy_denies_download_certificato(): void
    {
        $user = User::where('email', 'operatore@example.com')->firstOrFail();
        $vfu = VfuRegistration::factory()->rottamato()->create();

        $this->assertFalse($user->can('downloadCertificato', $vfu));
    }

    public function test_segreteria_policy_allows_download_certificato(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $vfu = VfuRegistration::factory()->rottamato()->create();

        $this->assertTrue($user->can('downloadCertificato', $vfu));
    }

    public function test_livewire_rejects_invalid_stato_on_download(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $vfu = VfuRegistration::factory()->create([
            'stato' => VfuStato::Accettato,
        ]);

        Livewire::actingAs($user)
            ->test(VfuShow::class, ['vfuRegistration' => $vfu])
            ->assertDontSee('Scarica PDF')
            ->call('downloadCertificato')
            ->assertHasErrors(['certificato']);
    }
}
