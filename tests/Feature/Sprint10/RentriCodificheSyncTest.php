<?php

namespace Tests\Feature\Sprint10;

use App\Models\CodiceCer;
use App\Models\RentriTransazione;
use App\Models\User;
use App\Services\Rentri\Contracts\RentriCodificheSyncInterface;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\Support\SeedsRentriCertificate;
use Tests\TestCase;

class RentriCodificheSyncTest extends TestCase
{
    use SeedsRentriCertificate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRentriCertificate();
    }

    public function test_sync_creates_new_cer_from_fixture(): void
    {
        $result = app(RentriCodificheSyncInterface::class)->sync();

        $this->assertGreaterThanOrEqual(1, $result['created']);
        $this->assertContains('17.04.05', $result['created_codes']);
        $this->assertDatabaseHas('codici_cer', [
            'codice'            => '17.04.05',
            'rentri_codice_ref' => 'RENTRI-CER-170405',
            'attivo'            => true,
        ]);
        $this->assertSame('codifiche', RentriTransazione::latest('id')->value('tipo_api'));
    }

    public function test_sync_updates_existing_cer_with_rentri_ref(): void
    {
        CodiceCer::factory()->create([
            'codice'            => '16.01.04*',
            'descrizione'       => 'Descrizione locale obsoleta',
            'categoria'         => 'altro',
            'rentri_codice_ref' => null,
            'attivo'            => true,
        ]);

        $result = app(RentriCodificheSyncInterface::class)->sync();

        $this->assertContains('16.01.04*', $result['updated_codes']);
        $this->assertDatabaseHas('codici_cer', [
            'codice'            => '16.01.04*',
            'categoria'         => 'pericoloso',
            'rentri_codice_ref' => 'RENTRI-CER-160104H',
        ]);
    }

    public function test_sync_deactivates_rentri_codes_missing_from_catalog(): void
    {
        CodiceCer::factory()->create([
            'codice'            => '99.99.99',
            'descrizione'       => 'Codice rimosso da RENTRI',
            'rentri_codice_ref' => 'RENTRI-CER-OLD',
            'attivo'            => true,
        ]);

        $result = app(RentriCodificheSyncInterface::class)->sync();

        $this->assertContains('99.99.99', $result['deactivated_codes']);
        $this->assertFalse(CodiceCer::where('codice', '99.99.99')->value('attivo'));
    }

    public function test_sync_does_not_deactivate_manual_codes_without_rentri_ref(): void
    {
        CodiceCer::factory()->create([
            'codice'            => '88.88.88',
            'rentri_codice_ref' => null,
            'attivo'            => true,
        ]);

        app(RentriCodificheSyncInterface::class)->sync();

        $this->assertTrue(CodiceCer::where('codice', '88.88.88')->value('attivo'));
    }

    public function test_artisan_command_runs_sync_successfully(): void
    {
        $this->artisan('rentri:sync-codifiche')
            ->assertSuccessful()
            ->expectsOutputToContain('Sync completata');
    }

    public function test_live_mode_fetches_codifiche_via_http(): void
    {
        Config::set('services.rentri.api_stub', false);

        Http::fake([
            'demoapi.rentri.gov.it/codifiche/v1.0/cer' => Http::response([
                'items' => [
                    [
                        'codice'      => '20.01.01',
                        'descrizione' => 'Carta e cartone',
                        'pericoloso'  => false,
                        'um'          => 'kg',
                        'rentri_ref'  => 'RENTRI-CER-200101',
                        'attivo'      => true,
                    ],
                ],
            ], 200),
        ]);

        $result = app(RentriCodificheSyncInterface::class)->sync();

        $this->assertSame(1, $result['created']);
        $this->assertContains('20.01.01', $result['created_codes']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://demoapi.rentri.gov.it/codifiche/v1.0/cer'
                && $request->method() === 'GET';
        });
    }

    public function test_segreteria_can_sync_rentri_via_policy(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $this->assertTrue(Gate::forUser($user)->allows('codice-cer.sync-rentri'));
    }

    public function test_operatore_cannot_sync_rentri_via_policy(): void
    {
        Role::findOrCreate('operatore');
        $user = User::where('email', 'operatore@example.com')->firstOrFail();

        $this->assertFalse(Gate::forUser($user)->allows('codice-cer.sync-rentri'));
    }
}
