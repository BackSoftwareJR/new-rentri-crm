<?php

namespace Tests\Feature\Sprint24;

use App\Domain\Bonifica\BonificaService;
use App\Http\Livewire\Operatore\BonificaWizard;
use App\Mail\BonificaPericolosiCompletataMail;
use App\Models\CodiceCer;
use App\Models\MagazzinoRifiuto;
use App\Models\User;
use App\Models\VfuRegistration;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class BonificaPericolosiNotificationTest extends TestCase
{
    private BonificaService $bonifica;

    protected function setUp(): void
    {
        parent::setUp();
        config(['notifications.live' => true]);
        Mail::fake();
        $this->bonifica = app(BonificaService::class);
    }

    public function test_complete_pericolosi_sends_notification_email(): void
    {
        [$bonifica] = $this->seedBonificaSession('NT123AB');

        $this->completeChecklist($bonifica);
        $this->bonifica->completePericolosi($bonifica->fresh());

        Mail::assertSent(BonificaPericolosiCompletataMail::class, function (BonificaPericolosiCompletataMail $mail) {
            return $mail->hasTo('segreteria@example.com')
                && $mail->vfu->targa === 'NT123AB';
        });
    }

    public function test_notification_email_contains_vfu_and_deadline(): void
    {
        [$bonifica, $vfu] = $this->seedBonificaSession('EM456CD', now()->subDays(10)->toDateString());

        $this->completeChecklist($bonifica);
        $this->bonifica->completePericolosi($bonifica->fresh());

        $sent = Mail::sent(BonificaPericolosiCompletataMail::class);
        $this->assertCount(1, $sent);

        $html = $sent[0]->render();
        $this->assertStringContainsString('EM456CD', $html);
        $this->assertStringContainsString('Bonifica pericolosi completata', $html);
        $this->assertStringContainsString('Scadenza pericolosi:', $html);
        $this->assertStringContainsString('Completata entro scadenza:', $html);
        $this->assertTrue($sent[0]->withinDeadline);
    }

    public function test_operatore_wizard_confirm_pericolosi_sends_email(): void
    {
        $user = User::where('email', 'operatore@example.com')->firstOrFail();
        [$bonifica, $vfu, $cerPer] = $this->seedBonificaSession('WZ789EF');

        Livewire::actingAs($user)
            ->test(BonificaWizard::class, ['vfu' => $vfu])
            ->set('quantita.'.$cerPer->id, 6)
            ->set('checklist.dpi', true)
            ->set('checklist.contenitori', true)
            ->set('checklist.area_ventilata', true)
            ->call('confirmPericolosi')
            ->assertHasNoErrors()
            ->assertSet('step', 2)
            ->assertSet('pericolosiCompletata', true);

        Mail::assertSent(BonificaPericolosiCompletataMail::class, function (BonificaPericolosiCompletataMail $mail) use ($vfu) {
            return $mail->vfu->id === $vfu->id;
        });
    }

    /**
     * @return array{0: \App\Models\BonificaVfu, 1: VfuRegistration, 2?: CodiceCer}
     */
    private function seedBonificaSession(string $targa, ?string $dataAccettazione = null): array
    {
        $cerPer = CodiceCer::factory()->pericoloso()->create(['codice' => '16.01.96*']);
        $cerAlt = CodiceCer::factory()->create(['codice' => '16.01.96', 'categoria' => 'altro']);
        MagazzinoRifiuto::create(['codice_cer_id' => $cerPer->id, 'quantita_attuale_kg' => 0]);
        MagazzinoRifiuto::create(['codice_cer_id' => $cerAlt->id, 'quantita_attuale_kg' => 0]);

        $vfu = VfuRegistration::factory()->accettatoPerBonifica()->create([
            'targa'             => $targa,
            'data_accettazione' => $dataAccettazione ?? now()->subDays(5)->toDateString(),
        ]);

        $bonifica = $this->bonifica->startBonifica($vfu);
        $this->bonifica->saveMovimenti($bonifica, [
            ['codice_cer_id' => $cerPer->id, 'quantita' => 5, 'um' => 'kg', 'peso_kg' => 5],
            ['codice_cer_id' => $cerAlt->id, 'quantita' => 0, 'um' => 'kg', 'peso_kg' => 0],
        ]);

        return [$bonifica, $vfu, $cerPer];
    }

    private function completeChecklist(\App\Models\BonificaVfu $bonifica): void
    {
        $this->bonifica->saveChecklistPericolosi($bonifica, [
            'dpi'            => true,
            'contenitori'    => true,
            'area_ventilata' => true,
        ]);
    }
}
