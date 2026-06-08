<?php

namespace Tests\Feature\Sprint32;

use App\Http\Livewire\Segreteria\Fir\FirBlocchiIndex;
use App\Models\FirBlocco;
use App\Models\User;
use App\Services\Rentri\Contracts\RentriFirBlocchiSyncInterface;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\Support\SeedsRentriCertificate;
use Tests\TestCase;

class RentriFirBlocchiSyncTest extends TestCase
{
    use SeedsRentriCertificate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRentriCertificate(['num_iscr_sito' => 'OP12345678901-PD00001']);
    }

    public function test_sync_creates_blocchi_from_stub_api(): void
    {
        Config::set('services.rentri.api_stub', true);

        $result = app(RentriFirBlocchiSyncInterface::class)->sync();

        $this->assertSame(1, $result['created']);
        $this->assertSame(0, $result['skipped']);
        $this->assertDatabaseHas('fir_blocchi', [
            'codice_blocco' => 'BLK-STUB-01',
            'num_iscr_sito' => 'OP12345678901-PD00001',
        ]);
    }

    public function test_sync_from_live_api_http_fake(): void
    {
        Config::set('services.rentri.api_stub', false);

        Http::fake([
            'demoapi.rentri.gov.it/vidimazione-formulari/v1.0*' => Http::response([
                'items' => [
                    ['codice_blocco' => 'BLK-API-01', 'num_iscr_sito' => 'OP12345678901-PD00001'],
                    ['codice_blocco' => 'BLK-API-02', 'num_iscr_sito' => 'OP12345678901-PD00001'],
                ],
            ], 200),
        ]);

        $result = app(RentriFirBlocchiSyncInterface::class)->sync();

        $this->assertSame(2, $result['created']);
        $this->assertDatabaseCount('fir_blocchi', 2);

        $resultAgain = app(RentriFirBlocchiSyncInterface::class)->sync();
        $this->assertSame(0, $resultAgain['created']);
        $this->assertSame(2, $resultAgain['skipped']);
    }

    public function test_livewire_sync_da_rentri_action(): void
    {
        Config::set('services.rentri.api_stub', true);
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(FirBlocchiIndex::class)
            ->call('syncDaRentri')
            ->assertHasNoErrors();

        $this->assertSame(1, FirBlocco::count());
    }
}
