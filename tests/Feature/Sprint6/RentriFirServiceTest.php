<?php

namespace Tests\Feature\Sprint6;

use App\Domain\Fir\FirBloccoService;
use App\Domain\Magazzino\MagazzinoSvuotamentoService;
use App\Enums\FirStato;
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

class RentriFirServiceTest extends TestCase
{
    use SeedsRentriCertificate;

    public function test_vidima_creates_fir_and_links_trasporto(): void
    {
        $this->seedRentriCertificate(['num_iscr_sito' => 'SITE-001']);

        FirBlocco::create([
            'codice_blocco' => 'BLK-A',
            'num_iscr_sito' => 'SITE-001',
            'progressivo_ultimo' => 0,
        ]);

        $trasporto = $this->seedTrasporto();

        $fir = app(RentriFirServiceInterface::class)->vidima($trasporto);

        $this->assertSame(FirStato::Vidimato, $fir->stato);
        $this->assertSame('SITE-001-BLK-A-0001', $fir->numero_fir);
        $this->assertSame($trasporto->id, $fir->trasporto_id);
        $this->assertSame($fir->id, $trasporto->fresh()->fir_id);
        $this->assertSame(1, FirBlocco::first()->progressivo_ultimo);
    }

    public function test_vidima_rejects_trasporto_with_existing_fir(): void
    {
        $this->seedRentriCertificate(['num_iscr_sito' => 'SITE-001']);
        FirBlocco::create(['codice_blocco' => 'BLK-A', 'num_iscr_sito' => 'SITE-001']);
        $trasporto = $this->seedTrasporto();
        $service = app(RentriFirServiceInterface::class);
        $service->vidima($trasporto);

        $this->expectException(\RuntimeException::class);
        $service->vidima($trasporto->fresh());
    }

    public function test_fir_blocco_service_creates_blocco(): void
    {
        RentriSetting::instance()->update(['num_iscr_sito' => 'SITE-002']);

        $blocco = app(FirBloccoService::class)->create('BLK-B', 'SITE-002');

        $this->assertSame('BLK-B', $blocco->codice_blocco);
        $this->assertSame(0, $blocco->progressivo_ultimo);
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
