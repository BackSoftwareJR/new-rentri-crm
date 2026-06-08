<?php

namespace Tests\Feature\Magazzino;

use App\Domain\Magazzino\MagazzinoService;
use App\Domain\Magazzino\SerbatoioAlertService;
use App\Enums\NotificationEvent;
use App\Http\Livewire\Segreteria\Magazzino\MagazzinoIndex;
use App\Models\CodiceCer;
use App\Models\MagazzinoRifiuto;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Tests\TestCase;

class MagazzinoControllaGiacenzeTest extends TestCase
{
    public function test_serbatoio_alert_service_detects_sotto_soglia_minima(): void
    {
        $cer = CodiceCer::factory()->create(['codice' => 'MIN-01', 'attivo' => true]);
        MagazzinoRifiuto::create([
            'codice_cer_id'       => $cer->id,
            'quantita_attuale_kg' => 20,
            'soglia_minima_kg'    => 50,
        ]);

        $rows = app(SerbatoioAlertService::class)->giacenzeSottoMinimo();

        $this->assertCount(1, $rows);
        $this->assertSame('MIN-01', $rows->first()['codice']);
        $this->assertTrue($rows->first()['sotto_soglia_minima']);
    }

    public function test_magazzino_service_update_soglia_minima(): void
    {
        $cer = CodiceCer::factory()->create(['attivo' => true]);

        app(MagazzinoService::class)->updateSogliaMinima($cer->id, 100.5);

        $this->assertSame(100.5, (float) MagazzinoRifiuto::where('codice_cer_id', $cer->id)->value('soglia_minima_kg'));
    }

    public function test_controlla_giacenze_command_reports_sotto_soglia(): void
    {
        $cer = CodiceCer::factory()->create(['codice' => 'CMD-MIN', 'attivo' => true]);
        MagazzinoRifiuto::create([
            'codice_cer_id'       => $cer->id,
            'quantita_attuale_kg' => 5,
            'soglia_minima_kg'    => 20,
        ]);

        Artisan::call('magazzino:controlla-giacenze');

        $this->assertStringContainsString('CMD-MIN', Artisan::output());
    }

    public function test_controlla_giacenze_command_notify_dispatches_in_app(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $cer = CodiceCer::factory()->create(['codice' => 'NOTIFY-MIN', 'attivo' => true]);
        MagazzinoRifiuto::create([
            'codice_cer_id'       => $cer->id,
            'quantita_attuale_kg' => 1,
            'soglia_minima_kg'    => 10,
        ]);

        Artisan::call('magazzino:controlla-giacenze', ['--notify' => true]);

        $this->assertGreaterThan(
            0,
            $user->fresh()->notifications()->where('data->event', NotificationEvent::MagazzinoSerbatoioSoglia->value)->count(),
        );
    }

    public function test_magazzino_index_shows_sotto_soglia_banner(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $cer = CodiceCer::factory()->create(['codice' => 'BAN-MIN', 'attivo' => true]);
        MagazzinoRifiuto::create([
            'codice_cer_id'       => $cer->id,
            'quantita_attuale_kg' => 3,
            'soglia_minima_kg'    => 15,
        ]);

        Livewire::actingAs($user)
            ->test(MagazzinoIndex::class)
            ->assertSee('Alert giacenza minima')
            ->assertSee('BAN-MIN');
    }

    public function test_command_is_registered(): void
    {
        $this->assertArrayHasKey('magazzino:controlla-giacenze', Artisan::all());
    }
}
