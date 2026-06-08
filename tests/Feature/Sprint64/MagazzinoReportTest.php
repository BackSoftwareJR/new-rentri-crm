<?php

namespace Tests\Feature\Sprint64;

use App\Domain\Magazzino\MagazzinoService;
use App\Domain\Magazzino\SerbatoioAlertNotificationService;
use App\Domain\Registro\RegistroMovimentiExportService;
use App\Domain\Magazzino\SerbatoioAlertService;
use App\Enums\RegistroMovimentoTipo;
use App\Http\Livewire\Segreteria\Dashboard;
use App\Http\Livewire\Segreteria\Magazzino\RegistroMovimentiIndex;
use App\Http\Livewire\Segreteria\Magazzino\SerbatoioShow;
use App\Models\CodiceCer;
use App\Models\MagazzinoCaricoManuale;
use App\Models\MagazzinoRifiuto;
use App\Models\RegistroMovimento;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Tests\TestCase;

class MagazzinoReportTest extends TestCase
{
    public function test_registro_export_csv_respects_filters(): void
    {
        $cerA = CodiceCer::factory()->create(['codice' => '16.01.04']);
        $cerB = CodiceCer::factory()->create(['codice' => '16.01.06']);

        RegistroMovimento::create([
            'tipo'           => RegistroMovimentoTipo::Carico,
            'codice_cer_id'  => $cerA->id,
            'peso_kg'        => 100,
            'data_movimento' => now()->subDays(2),
            'source_type'    => MagazzinoCaricoManuale::class,
            'source_id'      => 1,
        ]);
        RegistroMovimento::create([
            'tipo'           => RegistroMovimentoTipo::Scarico,
            'codice_cer_id'  => $cerB->id,
            'peso_kg'        => 50,
            'data_movimento' => now()->subDay(),
            'source_type'    => MagazzinoCaricoManuale::class,
            'source_id'      => 2,
        ]);

        $export = app(RegistroMovimentiExportService::class);

        ob_start();
        $export->exportCsv(['codice_cer_id' => $cerA->id, 'tipo' => 'carico'])->sendContent();
        $csv = ob_get_clean();

        $this->assertStringContainsString('16.01.04', $csv);
        $this->assertStringContainsString('carico', $csv);
        $this->assertStringNotContainsString('16.01.06', $csv);
    }

    public function test_serbatoio_alert_service_counts_threshold_states(): void
    {
        $attenzione = CodiceCer::factory()->create(['codice' => 'ALRT-70', 'limite_kg' => 1000, 'attivo' => true]);
        $superata = CodiceCer::factory()->create(['codice' => 'ALRT-110', 'limite_kg' => 1000, 'attivo' => true]);
        $regolare = CodiceCer::factory()->create(['codice' => 'ALRT-OK', 'limite_kg' => 1000, 'attivo' => true]);

        MagazzinoRifiuto::create(['codice_cer_id' => $attenzione->id, 'quantita_attuale_kg' => 750]);
        MagazzinoRifiuto::create(['codice_cer_id' => $superata->id, 'quantita_attuale_kg' => 1100]);
        MagazzinoRifiuto::create(['codice_cer_id' => $regolare->id, 'quantita_attuale_kg' => 200]);

        $summary = app(SerbatoioAlertService::class)->summary();

        $this->assertSame(1, $summary['in_attenzione']);
        $this->assertSame(1, $summary['soglia_superata']);
        $this->assertSame(2, $summary['totale_alert']);
    }

    public function test_serbatoio_alert_notification_logs_stub_on_threshold(): void
    {
        Log::shouldReceive('channel')
            ->with('notifications')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                return $message === 'notification.dispatched'
                    && ($context['event'] ?? null) === 'magazzino.serbatoio_soglia'
                    && ($context['stato'] ?? null) === 'attenzione';
            });

        app(SerbatoioAlertNotificationService::class)->notifyThreshold([
            'codice'              => 'LOG-64',
            'stato'               => 'attenzione',
            'percentuale'         => 75.0,
            'quantita_attuale_kg' => 750,
            'limite_kg'           => 1000,
        ]);
    }

    public function test_carico_manuale_triggers_threshold_notification_path(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $cer = CodiceCer::factory()->create(['limite_kg' => 1000, 'attivo' => true]);
        MagazzinoRifiuto::create(['codice_cer_id' => $cer->id, 'quantita_attuale_kg' => 650]);

        Log::shouldReceive('channel')
            ->with('notifications')
            ->andReturnSelf();
        Log::shouldReceive('info')->once();

        app(MagazzinoService::class)->caricoManuale($cer->id, 100, 'Carico test soglia', $user->id);

        $this->assertSame(750.0, (float) $cer->fresh()->magazzino->quantita_attuale_kg);
    }

    public function test_registro_index_export_csv_livewire(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $cer = CodiceCer::factory()->create(['codice' => 'EXP-64']);

        RegistroMovimento::create([
            'tipo'           => RegistroMovimentoTipo::Carico,
            'codice_cer_id'  => $cer->id,
            'peso_kg'        => 25,
            'data_movimento' => now(),
            'source_type'    => MagazzinoCaricoManuale::class,
            'source_id'      => 99,
        ]);

        Livewire::actingAs($user)
            ->test(RegistroMovimentiIndex::class)
            ->set('codice_cer_id', $cer->id)
            ->call('exportCsv')
            ->assertSuccessful();
    }

    public function test_serbatoio_show_displays_alert_banner(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $cer = CodiceCer::factory()->create(['codice' => 'BAN-64', 'limite_kg' => 500, 'attivo' => true]);
        MagazzinoRifiuto::create(['codice_cer_id' => $cer->id, 'quantita_attuale_kg' => 450]);

        Livewire::actingAs($user)
            ->test(SerbatoioShow::class, ['codiceCer' => $cer])
            ->assertSee('Alert serbatoio')
            ->assertSee('Attenzione');
    }

    public function test_dashboard_shows_serbatoio_alert_list(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $cer = CodiceCer::factory()->create(['codice' => 'DSH-64', 'limite_kg' => 100, 'attivo' => true]);
        MagazzinoRifiuto::create(['codice_cer_id' => $cer->id, 'quantita_attuale_kg' => 120]);

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertSee('DSH-64')
            ->assertSee('Soglia superata');
    }

    public function test_registro_export_policy_allows_segretaria(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $this->assertTrue(Gate::forUser($user)->allows('exportAny', RegistroMovimento::class));
    }
}
