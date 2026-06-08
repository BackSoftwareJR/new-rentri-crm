<?php

namespace Tests\Feature\Sprint34;

use App\Domain\Magazzino\MagazzinoSvuotamentoService;
use App\Enums\FirStato;
use App\Http\Livewire\Segreteria\Trasporti\TrasportoShow;
use App\Http\Livewire\Settings\RentriSettings;
use App\Models\Anagrafica;
use App\Models\Authorization;
use App\Models\CodiceCer;
use App\Models\FirBlocco;
use App\Models\MagazzinoRifiuto;
use App\Models\Trasporto;
use App\Models\User;
use App\Services\Rentri\Contracts\RentriFirServiceInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Support\SeedsRentriCertificate;
use Tests\TestCase;

class RentriXfirHttpTest extends TestCase
{
    use SeedsRentriCertificate;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.rentri.firma_stub', true);
        Storage::fake('local');
        $this->seedRentriCertificate([
            'num_iscr_sito'             => 'SITE-TEST',
            'onboarding_step_completed' => 3,
        ]);
    }

    public function test_settings_upload_firma_certificate(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $file = UploadedFile::fake()->create('firma-rentri.p12', 100, 'application/x-pkcs12');

        Livewire::actingAs($user)
            ->test(RentriSettings::class)
            ->set('firma_certificato', $file)
            ->set('firma_cert_password', 'firma-secret')
            ->call('uploadFirmaCertificato')
            ->assertHasNoErrors();

        $settings = \App\Models\RentriSetting::instance()->fresh();
        $this->assertNotNull($settings->firma_cert_path_encrypted);
        $this->assertNotNull($settings->firma_cert_scadenza);
    }

    public function test_trasporto_livewire_firma_and_download_xfir(): void
    {
        FirBlocco::create([
            'codice_blocco' => 'BLK-01',
            'num_iscr_sito' => 'SITE-TEST',
            'progressivo_ultimo' => 0,
        ]);

        $trasporto = $this->seedTrasporto();
        app(RentriFirServiceInterface::class)->vidima($trasporto);
        $trasporto->refresh();

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(TrasportoShow::class, ['trasporto' => $trasporto])
            ->assertSee('Da firmare')
            ->call('firmaXfir')
            ->assertHasNoErrors()
            ->assertSee('Firmato');

        $trasporto->refresh();
        $this->assertSame(FirStato::Firmato, $trasporto->firCollegato->stato);
        $this->assertNotNull($trasporto->firCollegato->xfir_signed_payload);

        Livewire::actingAs($user)
            ->test(TrasportoShow::class, ['trasporto' => $trasporto->fresh()])
            ->call('downloadXfirFirmato')
            ->assertFileDownloaded('xfir-SITE-TEST-BLK-01-0001.json');
    }

    private function seedTrasporto(): Trasporto
    {
        $cer = CodiceCer::factory()->create();
        MagazzinoRifiuto::create(['codice_cer_id' => $cer->id, 'quantita_attuale_kg' => 50]);
        $impianto = Anagrafica::factory()->create(['tipo' => 'impianto', 'email' => 'imp@test.local']);
        $trasportatore = Anagrafica::factory()->create(['tipo' => 'trasportatore', 'gestisce_trasporti' => true]);
        Authorization::factory()->create(['anagrafica_id' => $trasportatore->id, 'scade_il' => now()->addYear()]);

        app(MagazzinoSvuotamentoService::class)->richiediSvuotamento(
            $cer->id, $impianto->id, $trasportatore->id, false, 20, null,
            User::factory()->create()->id,
        );

        return Trasporto::firstOrFail();
    }
}
