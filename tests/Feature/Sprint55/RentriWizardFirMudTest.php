<?php

namespace Tests\Feature\Sprint55;

use App\Domain\Mud\MudPdfExportService;
use App\Domain\Mud\MudService;
use App\Domain\Rentri\RentriCertPreviewService;
use App\Http\Livewire\Segreteria\Mud\MudShow;
use App\Http\Livewire\Segreteria\Trasporti\TrasportoShow;
use App\Http\Livewire\Settings\RentriSettings;
use App\Enums\TrasportoStato;
use App\Models\CodiceCer;
use App\Models\RentriSetting;
use App\Models\Trasporto;
use App\Models\User;
use App\Support\Rentri\FirActionRateLimiter;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class RentriWizardFirMudTest extends TestCase
{
    public function test_rentri_settings_wizard_shows_cert_expiry_preview(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        RentriSetting::instance()->update([
            'cert_path_encrypted'     => encrypt('rentri/certificates/test.p12'),
            'cert_scadenza'           => now()->addMonths(6)->toDateString(),
            'onboarding_step_completed' => 2,
        ]);

        Livewire::actingAs($user)
            ->test(RentriSettings::class)
            ->assertSee('seg-cert-preview', false)
            ->assertSee('Certificato interoperabilità (mTLS)')
            ->assertSee('Valido fino al');
    }

    public function test_rentri_settings_cert_modal_opens_and_closes(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(RentriSettings::class)
            ->call('openCertModal', 'mtls')
            ->assertSet('certModalOpen', true)
            ->assertSet('certModalKind', 'mtls')
            ->assertSee('data-seg-modal', false)
            ->call('closeCertModal')
            ->assertSet('certModalOpen', false);
    }

    public function test_cert_preview_service_flags_expiring_certificate(): void
    {
        $settings = RentriSetting::instance();
        $settings->update([
            'cert_path_encrypted' => encrypt('rentri/certificates/test.p12'),
            'cert_scadenza'       => now()->addDays(10)->toDateString(),
        ]);

        $preview = app(RentriCertPreviewService::class)->previews($settings->fresh());

        $this->assertSame('expiring', $preview['mtls']['state']);
        $this->assertSame('warning', $preview['mtls']['badge']);
    }

    public function test_fir_action_rate_limiter_blocks_after_five_attempts(): void
    {
        $limiter = app(FirActionRateLimiter::class);
        $userId = User::where('email', 'segreteria@example.com')->firstOrFail()->id;

        RateLimiter::clear('fir-vidima:'.$userId);

        for ($i = 0; $i < 5; $i++) {
            $limiter->record('vidima', $userId);
        }

        $this->assertTrue($limiter->tooMany('vidima', $userId));
    }

    public function test_vidima_fir_is_rate_limited_on_trasporto_show(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        RateLimiter::clear('fir-vidima:'.$user->id);

        for ($i = 0; $i < 5; $i++) {
            RateLimiter::hit('fir-vidima:'.$user->id, 60);
        }

        $trasporto = Trasporto::create([
            'codice_cer_id'              => CodiceCer::factory()->create()->id,
            'anagrafica_destinatario_id' => \App\Models\Anagrafica::factory()->create(['tipo' => 'impianto'])->id,
            'quantita_kg'                => 10,
            'stato'                      => TrasportoStato::InPreparazione,
        ]);

        Livewire::actingAs($user)
            ->test(TrasportoShow::class, ['trasporto' => $trasporto])
            ->call('vidimaFir')
            ->assertSee('Troppe vidimazioni FIR', false);
    }

    public function test_mud_pdf_export_generates_valid_pdf_stub(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $anno = 2019;
        $mud = app(MudService::class)->createBozza($anno, $user->id);
        $mud = app(MudService::class)->completa($mud);

        $pdf = app(MudPdfExportService::class)->generatePdf($mud, app(MudService::class));

        $this->assertStringStartsWith('%PDF-1.4', $pdf);
        $this->assertStringContainsString('Dichiarazione MUD', $pdf);
    }

    public function test_segreteria_can_export_mud_pdf_from_show(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $anno = (int) now()->format('Y') - 1;
        $mud = app(MudService::class)->createBozza($anno, $user->id);
        $mud = app(MudService::class)->completa($mud);

        Livewire::actingAs($user)
            ->test(MudShow::class, ['dichiarazione' => $mud])
            ->call('exportPdf')
            ->assertFileDownloaded('mud-'.$anno.'-stub.pdf');
    }

    public function test_activity_log_has_query_indexes(): void
    {
        $this->assertTrue(Schema::hasIndex('activity_log', 'activity_log_created_at_index'));
        $this->assertTrue(Schema::hasIndex('activity_log', 'activity_log_log_name_created_at_index'));
    }
}
