<?php

namespace Tests\Feature\Sprint48;

use App\Domain\Audit\ActivityLogService;
use App\Domain\Ecommerce\EcommerceService;
use App\Enums\MudStato;
use App\Enums\OrdineEcommerceStato;
use App\Models\EcommerceOrdine;
use App\Models\EcommerceProdotto;
use App\Models\MudDichiarazione;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DemoEcommerceMudIsolationTest extends TestCase
{
    public function test_session_demo_hides_production_ecommerce_products(): void
    {
        Config::set('demo.enabled', false);
        session([config('demo.session.key') => true]);

        $this->insertProdotto(['codice' => 'PROD-001', 'nome' => 'Prod Ricambio', 'is_demo' => false]);
        EcommerceProdotto::factory()->create(['codice' => 'DEMO-001', 'nome' => 'Demo Ricambio']);

        $this->assertSame(1, EcommerceProdotto::count());
        $this->assertSame('Demo Ricambio', EcommerceProdotto::first()->nome);
    }

    public function test_session_demo_hides_production_mud_dichiarazioni(): void
    {
        Config::set('demo.enabled', false);
        session([config('demo.session.key') => true]);

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $this->insertMud(['anno_riferimento' => 2023, 'is_demo' => false], $user->id);
        MudDichiarazione::create([
            'anno_riferimento' => 2024,
            'stato'            => MudStato::Bozza,
            'righe'            => [],
            'user_id'          => $user->id,
        ]);

        $this->assertSame(1, MudDichiarazione::count());
        $this->assertSame(2024, MudDichiarazione::first()->anno_riferimento);
    }

    public function test_ecommerce_service_cannot_decrement_production_stock_in_demo(): void
    {
        Config::set('demo.enabled', false);
        session([config('demo.session.key') => true]);

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $prodId = $this->insertProdotto(['codice' => 'PROD-STOCK', 'nome' => 'Prod Stock', 'giacenza' => 5, 'is_demo' => false]);

        $service = app(EcommerceService::class);
        $this->assertSame(0, $service->contatoriCatalogo()['totale']);

        $this->assertSame(5, (int) DB::table('ecommerce_prodotti')->where('id', $prodId)->value('giacenza'));
    }

    public function test_activity_log_hides_production_events_in_demo_session(): void
    {
        Config::set('demo.enabled', false);
        session([config('demo.session.key') => true]);

        app(ActivityLogService::class)->record('rentri', 'Evento produzione', properties: ['demo_mode' => false]);
        app(ActivityLogService::class)->record('rentri', 'Evento demo palestra');

        $this->assertSame(1, app(ActivityLogService::class)->contatori()['totale']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function insertProdotto(array $overrides): int
    {
        return (int) DB::table('ecommerce_prodotti')->insertGetId(array_merge([
            'codice'      => 'X-'.uniqid(),
            'nome'        => 'Test',
            'descrizione' => null,
            'categoria'   => 'generico',
            'prezzo'      => 10,
            'giacenza'    => 1,
            'attivo'      => true,
            'is_demo'     => true,
            'created_at'  => now(),
            'updated_at'  => now(),
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function insertMud(array $overrides, int $userId): int
    {
        return (int) DB::table('mud_dichiarazioni')->insertGetId(array_merge([
            'anno_riferimento' => 2020,
            'stato'            => MudStato::Bozza->value,
            'righe'            => '[]',
            'user_id'          => $userId,
            'is_demo'          => true,
            'created_at'       => now(),
            'updated_at'       => now(),
        ], $overrides));
    }
}
