<?php

namespace Tests\Feature\Sprint15;

use App\Domain\Audit\ActivityLogService;
use App\Domain\Ecommerce\EcommerceService;
use App\Domain\Mud\MudService;
use App\Enums\MudStato;
use App\Enums\RegistroMovimentoTipo;
use App\Http\Livewire\Admin\AuditIndex;
use App\Models\CodiceCer;
use App\Models\EcommerceProdotto;
use App\Models\MagazzinoCaricoManuale;
use App\Models\MudDichiarazione;
use App\Models\RegistroMovimento;
use App\Models\User;
use App\Services\Rentri\Contracts\RentriRegistryServiceInterface;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\Support\SeedsRentriCertificate;
use Tests\TestCase;

class AuditHttpTest extends TestCase
{
    use SeedsRentriCertificate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRentriCertificate();
    }
    public function test_admin_can_access_audit_index_with_filters(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        app(ActivityLogService::class)->record(
            'ecommerce',
            'Ordine e-commerce bozza creato',
            properties: ['ordine_id' => 1],
            userId: $admin->id,
        );

        $this->actingAs($admin)
            ->get(route('admin.audit'))
            ->assertOk()
            ->assertSee('Audit & activity log')
            ->assertSee('Ordine e-commerce bozza creato');

        Livewire::actingAs($admin)
            ->test(AuditIndex::class)
            ->set('modulo', 'ecommerce')
            ->assertSee('E-commerce')
            ->assertSee('Ordine e-commerce bozza creato');
    }

    public function test_segreteria_cannot_access_audit(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('admin.audit'))
            ->assertForbidden();

        Livewire::actingAs($user)
            ->test(AuditIndex::class)
            ->assertForbidden();
    }

    public function test_hooks_record_activity_on_key_actions(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $this->actingAs($user);

        $prodotto = EcommerceProdotto::factory()->create(['giacenza' => 2, 'prezzo' => 10]);
        app(EcommerceService::class)->addToCart($prodotto->id, 1);
        app(EcommerceService::class)->createOrdineBozza($user->id);

        $mud = MudDichiarazione::create([
            'anno_riferimento' => 2017,
            'stato'            => MudStato::Bozza,
            'righe'            => [['codice' => '16.01.04', 'carichi_kg' => 1, 'scarichi_kg' => 0, 'saldo_kg' => 1]],
            'user_id'          => $user->id,
        ]);
        app(MudService::class)->completa($mud);

        $cer = CodiceCer::factory()->create();
        RegistroMovimento::create([
            'tipo'             => RegistroMovimentoTipo::Carico,
            'codice_cer_id'    => $cer->id,
            'peso_kg'          => 25,
            'data_movimento'   => now(),
            'rentri_trasmesso' => false,
            'source_type'      => MagazzinoCaricoManuale::class,
            'source_id'        => 1,
        ]);

        $registry = app(RentriRegistryServiceInterface::class);
        $payload = $registry->buildTransmissionPayload(now()->startOfMonth(), now());
        $registry->transmit($payload);

        $this->assertDatabaseHas('activity_log', [
            'log_name'    => 'ecommerce',
            'description' => 'Ordine e-commerce bozza creato',
        ]);
        $this->assertDatabaseHas('activity_log', [
            'log_name'    => 'mud',
            'description' => 'Dichiarazione MUD completata',
        ]);
        $this->assertDatabaseHas('activity_log', [
            'log_name'    => 'rentri',
            'description' => 'Trasmissione registro RENTRI completata',
        ]);

        $this->assertSame(3, Activity::query()->count());
    }
}
