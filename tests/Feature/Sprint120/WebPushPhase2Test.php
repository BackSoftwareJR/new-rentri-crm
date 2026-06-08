<?php

namespace Tests\Feature\Sprint120;

use App\Domain\Bonifica\BonificaService;
use App\Domain\Trasporti\TrasportoService;
use App\Domain\Vfu\SmontaggioService;
use App\Domain\Vfu\VfuAccettazioneService;
use App\Enums\TrasportoStato;
use App\Enums\VfuStato;
use App\Models\Anagrafica;
use App\Models\BonificaVfu;
use App\Models\CodiceCer;
use App\Models\Fattura;
use App\Models\SmontaggioSession;
use App\Models\Trasporto;
use App\Models\User;
use App\Models\VfuDocument;
use App\Models\VfuRegistration;
use App\Services\Push\WebPushService;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class WebPushPhase2Test extends TestCase
{
    public function test_send_to_roles_does_not_throw_without_vapid_keys(): void
    {
        config([
            'webpush.vapid.public_key' => null,
            'webpush.vapid.private_key' => null,
        ]);

        app(WebPushService::class)->sendToRoles(
            'operatore',
            'Test title',
            'Test body',
            '/test',
        );

        $this->assertTrue(true);
    }

    public function test_vfu_accettazione_triggers_operatore_push(): void
    {
        $push = $this->mock(WebPushService::class);
        $push->shouldReceive('sendToRoles')
            ->once()
            ->withArgs(function ($roles, $title) {
                return $roles === 'operatore' && str_contains($title, 'Nuovo VFU:');
            });

        CodiceCer::create([
            'codice' => '16.01.04*',
            'descrizione' => 'VFU accettazione test',
            'categoria' => 'altro',
            'um' => 'kg',
            'attivo' => true,
        ]);

        $vfu = VfuRegistration::factory()->create([
            'stato' => VfuStato::InAccettazione,
            'peso_kg' => 1200,
            'targa' => 'AB123CD',
        ]);

        $this->seedRequiredDocuments($vfu);

        app(VfuAccettazioneService::class)->completeAccettazione($vfu->fresh());
    }

    public function test_bonifica_completata_triggers_segreteria_push(): void
    {
        $push = $this->mock(WebPushService::class);
        $push->shouldReceive('sendToRoles')
            ->once()
            ->withArgs(function ($roles, $title) {
                return $roles === 'segreteria' && str_contains($title, 'bonifica completata');
            });

        $vfu = VfuRegistration::factory()->create([
            'stato' => VfuStato::InBonifica,
            'bonifica_pericolosi_completata_at' => now(),
            'targa' => 'XY999ZZ',
        ]);

        $bonifica = BonificaVfu::create([
            'vfu_registration_id' => $vfu->id,
            'stato' => 'in_corso',
            'fase' => 'altri',
            'data_inizio' => now(),
        ]);

        app(BonificaService::class)->completeBonifica($bonifica);
    }

    public function test_trasporto_avviato_triggers_segreteria_push(): void
    {
        $push = $this->mock(WebPushService::class);
        $push->shouldReceive('sendToRoles')
            ->once()
            ->withArgs(function ($roles, $title, $body, $url) {
                return $roles === 'segreteria'
                    && str_contains($title, 'Trasporto #')
                    && str_contains($title, 'in corso');
            });

        $trasporto = Trasporto::create([
            'codice_cer_id' => CodiceCer::factory()->create()->id,
            'anagrafica_destinatario_id' => Anagrafica::factory()->create()->id,
            'stato' => TrasportoStato::InPreparazione,
            'quantita_kg' => 100,
        ]);

        app(TrasportoService::class)->avviaTransito($trasporto);
    }

    public function test_smontaggio_completato_triggers_admin_and_segreteria_push(): void
    {
        $push = $this->mock(WebPushService::class);
        $push->shouldReceive('sendToRoles')
            ->once()
            ->withArgs(function ($roles, $title) {
                return $roles === ['segreteria', 'admin']
                    && str_contains($title, 'Smontaggio')
                    && str_contains($title, 'completato');
            });

        $vfu = VfuRegistration::factory()->create([
            'stato' => VfuStato::InSmontaggio,
            'targa' => 'SM001NT',
        ]);

        $session = SmontaggioSession::create([
            'vfu_registration_id' => $vfu->id,
            'stato' => 'in_corso',
            'operatore_id' => User::where('email', 'operatore@example.com')->firstOrFail()->id,
            'started_at' => now(),
        ]);

        app(SmontaggioService::class)->completa($session);
    }

    public function test_fattura_scaduta_command_notifies_segreteria_for_yesterday_due_date(): void
    {
        $push = $this->mock(WebPushService::class);
        $push->shouldReceive('sendToRoles')
            ->once()
            ->withArgs(function ($roles, $title) {
                return $roles === 'segreteria'
                    && str_contains($title, 'Fattura')
                    && str_contains($title, 'scaduta ieri');
            });

        Carbon::setTestNow('2026-06-10');

        Fattura::create([
            'numero_fattura' => '2026/042',
            'tipo' => 'fattura',
            'anagrafica_id' => Anagrafica::factory()->create()->id,
            'data_emissione' => '2026-06-01',
            'stato' => 'emessa',
            'data_scadenza' => '2026-06-09',
        ]);

        $this->artisan('fatture:segna-scadute')->assertSuccessful();

        Carbon::setTestNow();
    }

    private function seedRequiredDocuments(VfuRegistration $vfu): void
    {
        VfuDocument::create([
            'vfu_registration_id' => $vfu->id,
            'tipo' => \App\Enums\VfuTipoDocumento::CartaCircolazione,
            'path' => 'test/carta.pdf',
            'original_name' => 'carta.pdf',
        ]);

        VfuDocument::create([
            'vfu_registration_id' => $vfu->id,
            'tipo' => \App\Enums\VfuTipoDocumento::DocumentoIdentita,
            'path' => 'test/ci.pdf',
            'original_name' => 'ci.pdf',
        ]);
    }
}
