<?php

namespace Tests\Feature\Sprint5;

use App\Domain\Magazzino\MagazzinoSvuotamentoService;
use App\Domain\Trasporti\TrasportoService;
use App\Enums\SvuotamentoStato;
use App\Enums\TrasportoStato;
use App\Models\Anagrafica;
use App\Models\Authorization;
use App\Models\CodiceCer;
use App\Models\FirBlocco;
use App\Models\MagazzinoRifiuto;
use App\Models\RentriSetting;
use App\Models\Trasporto;
use App\Models\User;
use App\Services\Rentri\Contracts\RentriFirServiceInterface;
use Tests\Support\SeedsRentriCertificate;
use Tests\TestCase;

class TrasportoServiceTest extends TestCase
{
    use SeedsRentriCertificate;
    private TrasportoService $trasporti;

    private MagazzinoSvuotamentoService $svuotamenti;

    protected function setUp(): void
    {
        parent::setUp();
        $this->trasporti = app(TrasportoService::class);
        $this->svuotamenti = app(MagazzinoSvuotamentoService::class);
    }

    public function test_richiedi_svuotamento_creates_linked_trasporto(): void
    {
        $user = User::factory()->create();
        $cer = CodiceCer::factory()->create();
        MagazzinoRifiuto::create(['codice_cer_id' => $cer->id, 'quantita_attuale_kg' => 100]);
        [$impianto, $trasportatore] = $this->seedImpiantoAndTrasportatore();

        $svuotamento = $this->svuotamenti->richiediSvuotamento(
            $cer->id, $impianto->id, $trasportatore->id, false, 40, null, $user->id,
        );

        $this->assertDatabaseHas('trasporti', [
            'magazzino_svuotamento_id' => $svuotamento->id,
            'codice_cer_id' => $cer->id,
            'stato' => TrasportoStato::InPreparazione->value,
            'quantita_kg' => 40,
        ]);
    }

    public function test_completa_marks_trasporto_and_svuotamento_completati(): void
    {
        $trasporto = $this->createTrasportoInTransito(withFir: true);

        $result = $this->trasporti->completa($trasporto);

        $this->assertSame(TrasportoStato::Completato, $result->stato);
        $this->assertSame(SvuotamentoStato::Completato, $result->svuotamento->fresh()->stato);
    }

    public function test_annulla_releases_svuotamento_impegno(): void
    {
        $user = User::factory()->create();
        $cer = CodiceCer::factory()->create();
        MagazzinoRifiuto::create(['codice_cer_id' => $cer->id, 'quantita_attuale_kg' => 100]);
        [$impianto, $trasportatore] = $this->seedImpiantoAndTrasportatore();

        $this->svuotamenti->richiediSvuotamento($cer->id, $impianto->id, $trasportatore->id, false, 30, null, $user->id);
        $trasporto = Trasporto::firstOrFail();

        $this->trasporti->annulla($trasporto);

        $this->assertSame(TrasportoStato::Annullato, $trasporto->fresh()->stato);
        $this->assertSame(SvuotamentoStato::Annullato, $trasporto->svuotamento->fresh()->stato);
        $this->assertSame(100.0, $this->svuotamenti->quantitaDisponibile($cer->id));
    }

    private function createTrasportoInTransito(bool $withFir = false): Trasporto
    {
        $this->seedRentriCertificate(['num_iscr_sito' => 'SITE-T5']);
        FirBlocco::firstOrCreate(
            ['codice_blocco' => 'BLK-T5', 'num_iscr_sito' => 'SITE-T5'],
            ['progressivo_ultimo' => 0],
        );

        $user = User::factory()->create();
        $cer = CodiceCer::factory()->create();
        MagazzinoRifiuto::create(['codice_cer_id' => $cer->id, 'quantita_attuale_kg' => 50]);
        [$impianto, $trasportatore] = $this->seedImpiantoAndTrasportatore();

        $this->svuotamenti->richiediSvuotamento($cer->id, $impianto->id, $trasportatore->id, false, 20, null, $user->id);
        $trasporto = Trasporto::firstOrFail();
        $trasporto = $this->trasporti->avviaTransito($trasporto);

        if ($withFir) {
            app(RentriFirServiceInterface::class)->vidima($trasporto);
            $trasporto->refresh();
        }

        return $trasporto;
    }

    /**
     * @return array{0: Anagrafica, 1: Anagrafica}
     */
    private function seedImpiantoAndTrasportatore(): array
    {
        $impianto = Anagrafica::factory()->create(['tipo' => 'impianto', 'email' => 'imp@test.local']);
        $trasportatore = Anagrafica::factory()->create(['tipo' => 'trasportatore', 'gestisce_trasporti' => true]);
        Authorization::factory()->create(['anagrafica_id' => $trasportatore->id, 'scade_il' => now()->addYear()]);

        return [$impianto, $trasportatore];
    }
}
