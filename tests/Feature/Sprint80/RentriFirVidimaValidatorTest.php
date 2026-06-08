<?php

namespace Tests\Feature\Sprint80;

use App\Domain\Magazzino\MagazzinoSvuotamentoService;
use App\Domain\Rentri\RentriFirVidimaValidator;
use App\Http\Livewire\Segreteria\Trasporti\TrasportoShow;
use App\Models\Anagrafica;
use App\Models\Authorization;
use App\Models\CodiceCer;
use App\Models\FirBlocco;
use App\Models\MagazzinoRifiuto;
use App\Models\RentriSetting;
use App\Models\Trasporto;
use App\Models\User;
use App\Services\Rentri\Contracts\RentriFirServiceInterface;
use App\Services\Rentri\Exceptions\RentriFirVidimaException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\Support\SeedsRentriCertificate;
use Tests\TestCase;

class RentriFirVidimaValidatorTest extends TestCase
{
    use SeedsRentriCertificate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRentriCertificate(['num_iscr_sito' => 'SITE-S80']);
    }

    public function test_checklist_passes_with_valid_settings(): void
    {
        $checklist = app(RentriFirVidimaValidator::class)->checklist(RentriSetting::instance());

        $this->assertTrue(collect($checklist)->every(fn (array $item) => $item['ok']));
        app(RentriFirVidimaValidator::class)->assertReady(RentriSetting::instance());
    }

    public function test_assert_ready_fails_without_cf_operatore(): void
    {
        RentriSetting::instance()->update(['cf_operatore' => null, 'cf' => null]);

        $this->expectException(RentriFirVidimaException::class);
        app(RentriFirVidimaValidator::class)->assertReady(RentriSetting::instance());
    }

    public function test_assert_ready_fails_without_num_iscr_sito(): void
    {
        RentriSetting::instance()->update(['num_iscr_sito' => null]);

        $this->expectException(RentriFirVidimaException::class);
        app(RentriFirVidimaValidator::class)->assertReady(RentriSetting::instance());
    }

    public function test_assert_ready_fails_when_onboarding_below_step_3(): void
    {
        RentriSetting::instance()->update(['onboarding_step_completed' => 2]);

        $blockers = app(RentriFirVidimaValidator::class)->blockers(RentriSetting::instance());

        $this->assertContains('Completa almeno il test connessione nel wizard RENTRI (step 3).', $blockers);
        $this->expectException(RentriFirVidimaException::class);
        app(RentriFirVidimaValidator::class)->assertReady(RentriSetting::instance());
    }

    public function test_assert_ready_fails_when_cert_expired_in_live_mode(): void
    {
        Config::set('services.rentri.api_stub', false);
        RentriSetting::instance()->update([
            'cert_scadenza' => now()->subDay()->toDateString(),
        ]);

        $checklist = app(RentriFirVidimaValidator::class)->checklist(RentriSetting::instance());
        $certItem = collect($checklist)->firstWhere('codice', 'certificato_mtls');

        $this->assertFalse($certItem['ok']);
        $this->assertStringContainsString('scaduto', (string) $certItem['message']);

        $this->expectException(RentriFirVidimaException::class);
        app(RentriFirVidimaValidator::class)->assertReady(RentriSetting::instance());
    }

    public function test_vidima_service_blocks_before_api_when_settings_invalid(): void
    {
        Http::fake();
        RentriSetting::instance()->update(['num_iscr_sito' => null]);

        FirBlocco::create([
            'codice_blocco'      => 'BLK-S80',
            'num_iscr_sito'      => 'SITE-S80',
            'progressivo_ultimo' => 0,
        ]);

        try {
            app(RentriFirServiceInterface::class)->vidima($this->seedTrasporto());
            $this->fail('Expected RentriFirVidimaException');
        } catch (RentriFirVidimaException $e) {
            $this->assertNotEmpty($e->errors);
        }

        Http::assertNothingSent();
    }

    public function test_trasporto_ui_shows_vidima_checklist_with_ko_message(): void
    {
        RentriSetting::instance()->update(['onboarding_step_completed' => 1]);

        FirBlocco::create([
            'codice_blocco'      => 'BLK-UI-S80',
            'num_iscr_sito'      => 'SITE-S80',
            'progressivo_ultimo' => 0,
        ]);

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $trasporto = $this->seedTrasporto();

        Livewire::actingAs($user)
            ->test(TrasportoShow::class, ['trasporto' => $trasporto])
            ->assertSee('Checklist pre-vidima RENTRI')
            ->assertSee('Onboarding RENTRI completato')
            ->assertSee('Correggi gli elementi KO');
    }

    private function seedTrasporto(): Trasporto
    {
        $cer = CodiceCer::factory()->create();
        MagazzinoRifiuto::create(['codice_cer_id' => $cer->id, 'quantita_attuale_kg' => 50]);
        $impianto = Anagrafica::factory()->create(['tipo' => 'impianto']);
        $trasportatore = Anagrafica::factory()->create(['tipo' => 'trasportatore', 'gestisce_trasporti' => true]);
        Authorization::factory()->create(['anagrafica_id' => $trasportatore->id, 'scade_il' => now()->addYear()]);

        app(MagazzinoSvuotamentoService::class)->richiediSvuotamento(
            $cer->id, $impianto->id, $trasportatore->id, false, 20, null,
            User::factory()->create()->id,
        );

        return Trasporto::firstOrFail();
    }
}
