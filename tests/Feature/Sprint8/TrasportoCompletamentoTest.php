<?php

namespace Tests\Feature\Sprint8;

use App\Domain\Magazzino\MagazzinoSvuotamentoService;
use App\Domain\Trasporti\TrasportoService;
use App\Enums\RegistroMovimentoTipo;
use App\Enums\SvuotamentoStato;
use App\Enums\TrasportoStato;
use App\Http\Livewire\Segreteria\Trasporti\TrasportoShow;
use App\Models\Anagrafica;
use App\Models\Authorization;
use App\Models\CodiceCer;
use App\Models\FirBlocco;
use App\Models\MagazzinoRifiuto;
use App\Models\RegistroMovimento;
use App\Models\RentriSetting;
use App\Models\Trasporto;
use App\Models\User;
use App\Services\Rentri\Contracts\RentriFirServiceInterface;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\Support\SeedsRentriCertificate;
use Tests\TestCase;

class TrasportoCompletamentoTest extends TestCase
{
    use SeedsRentriCertificate;
    public function test_completa_scarica_magazzino_and_creates_registro_movimento(): void
    {
        $trasporto = $this->seedTrasportoInTransito(withFir: true);
        $cerId = $trasporto->codice_cer_id;
        $quantita = (float) $trasporto->quantita_kg;

        $giacenzaPrima = (float) MagazzinoRifiuto::where('codice_cer_id', $cerId)->value('quantita_attuale_kg');

        app(TrasportoService::class)->completa($trasporto);

        $this->assertSame(TrasportoStato::Completato, $trasporto->fresh()->stato);
        $this->assertSame(SvuotamentoStato::Completato, $trasporto->svuotamento->fresh()->stato);
        $this->assertSame(
            round($giacenzaPrima - $quantita, 4),
            (float) MagazzinoRifiuto::where('codice_cer_id', $cerId)->value('quantita_attuale_kg'),
        );

        $this->assertDatabaseHas('registro_movimenti', [
            'tipo'          => RegistroMovimentoTipo::Scarico->value,
            'codice_cer_id' => $cerId,
            'source_type'   => Trasporto::class,
            'source_id'     => $trasporto->id,
            'peso_kg'       => $quantita,
        ]);
    }

    public function test_completa_rejects_without_fir_vidimato(): void
    {
        $trasporto = $this->seedTrasportoInTransito(withFir: false);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('FIR');

        app(TrasportoService::class)->completa($trasporto);
    }

    public function test_completa_rejects_insufficient_magazzino(): void
    {
        $trasporto = $this->seedTrasportoInTransito(withFir: true);

        MagazzinoRifiuto::where('codice_cer_id', $trasporto->codice_cer_id)
            ->update(['quantita_attuale_kg' => 1]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Giacenza');

        app(TrasportoService::class)->completa($trasporto->fresh());
    }

    public function test_livewire_completa_after_fir_vidimato(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $trasporto = $this->seedTrasportoInTransito(withFir: true);

        Livewire::actingAs($user)
            ->test(TrasportoShow::class, ['trasporto' => $trasporto])
            ->assertSet('trasporto.stato', TrasportoStato::InTransito)
            ->call('completa')
            ->assertHasNoErrors();

        $this->assertSame(TrasportoStato::Completato, $trasporto->fresh()->stato);
        $this->assertSame(1, RegistroMovimento::where('source_id', $trasporto->id)->count());
    }

    public function test_operatore_cannot_complete_trasporto(): void
    {
        $trasporto = $this->seedTrasportoInTransito(withFir: true);
        $operatore = User::where('email', 'operatore@example.com')->firstOrFail();

        $this->assertFalse(Gate::forUser($operatore)->allows('trasporto.complete', $trasporto));
    }

    private function seedTrasportoInTransito(bool $withFir): Trasporto
    {
        $this->seedRentriCertificate(['num_iscr_sito' => 'SITE-S8']);
        FirBlocco::firstOrCreate(
            ['codice_blocco' => 'BLK-S8', 'num_iscr_sito' => 'SITE-S8'],
            ['progressivo_ultimo' => 0],
        );

        $cer = CodiceCer::factory()->create();
        MagazzinoRifiuto::create(['codice_cer_id' => $cer->id, 'quantita_attuale_kg' => 80]);
        $impianto = Anagrafica::factory()->create(['tipo' => 'impianto', 'email' => 'imp@s8.test']);
        $trasportatore = Anagrafica::factory()->create(['tipo' => 'trasportatore', 'gestisce_trasporti' => true]);
        Authorization::factory()->create(['anagrafica_id' => $trasportatore->id, 'scade_il' => now()->addYear()]);

        app(MagazzinoSvuotamentoService::class)->richiediSvuotamento(
            $cer->id, $impianto->id, $trasportatore->id, false, 30, null,
            User::factory()->create()->id,
        );

        $trasporto = Trasporto::firstOrFail();
        $trasporto = app(TrasportoService::class)->avviaTransito($trasporto);

        if ($withFir) {
            app(RentriFirServiceInterface::class)->vidima($trasporto);
            $trasporto->refresh();
        }

        return $trasporto;
    }
}
