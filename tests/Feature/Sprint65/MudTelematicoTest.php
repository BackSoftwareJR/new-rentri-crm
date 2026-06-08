<?php

namespace Tests\Feature\Sprint65;

use App\Domain\Mud\MudInvioTelematicoService;
use App\Domain\Mud\MudService;
use App\Domain\Mud\MudXmlValidationService;
use App\Enums\MudStato;
use App\Http\Livewire\Segreteria\Mud\MudIndex;
use App\Http\Livewire\Segreteria\Mud\MudShow;
use App\Models\MudDichiarazione;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class MudTelematicoTest extends TestCase
{
    public function test_mud_xml_validation_accepts_valid_export(): void
    {
        $mud = $this->completataSample();

        $result = app(MudXmlValidationService::class)->validate($mud);

        $this->assertTrue($result['valid']);
        $this->assertStringContainsString('<DichiarazioneMUD', $result['xml'] ?? '');
        $this->assertStringContainsString('<AnnoRiferimento>2025</AnnoRiferimento>', $result['xml'] ?? '');
    }

    public function test_mud_xml_validation_rejects_malformed_xml(): void
    {
        $validator = app(MudXmlValidationService::class);
        $errors = (new \ReflectionClass($validator))
            ->getMethod('validateXmlString')
            ->invoke($validator, '<DichiarazioneMUD><AnnoRiferimento>2025</AnnoRiferimento>', 2025);

        $this->assertNotEmpty($errors);
    }

    public function test_invio_stub_sets_protocol_and_activity_log(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $mud = $this->completataSample();

        $result = app(MudInvioTelematicoService::class)->inviaStub($mud, $user->id);

        $this->assertSame(MudStato::Inviata, $result->stato);
        $this->assertNotNull($result->inviata_at);
        $this->assertStringStartsWith('MUD-STUB-2025-', $result->invio_protocollo);

        $this->assertDatabaseHas('activity_log', [
            'log_name'    => 'mud',
            'description' => 'Invio telematico MUD stub completato',
            'causer_id'   => $user->id,
        ]);

        $activity = Activity::query()->where('log_name', 'mud')->latest('id')->first();
        $this->assertSame('accettato', $activity?->properties->get('esito'));
    }

    public function test_invio_stub_rejects_bozza(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $mud = MudDichiarazione::create([
            'anno_riferimento' => 2022,
            'stato'            => MudStato::Bozza,
            'righe'            => [['codice' => '16.01.04', 'carichi_kg' => 1, 'scarichi_kg' => 0, 'saldo_kg' => 1]],
            'user_id'          => $user->id,
        ]);

        $this->expectException(\InvalidArgumentException::class);

        app(MudInvioTelematicoService::class)->inviaStub($mud, $user->id);
    }

    public function test_mud_show_renders_checklist_and_invio_stub(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $mud = $this->completataSample();

        Livewire::actingAs($user)
            ->test(MudShow::class, ['dichiarazione' => $mud])
            ->assertSee('Checklist pre-invio telematico')
            ->assertSee('Invia telematico (stub)')
            ->call('inviaStub')
            ->assertHasNoErrors()
            ->assertSee('MUD-STUB-2025-');
    }

    public function test_mud_index_filters_by_anno_and_stato_inviata(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        MudDichiarazione::create([
            'anno_riferimento' => 2019,
            'stato'            => MudStato::Inviata,
            'righe'            => [],
            'invio_protocollo' => 'MUD-STUB-2019-TEST0001',
            'inviata_at'       => now(),
            'user_id'          => $user->id,
        ]);

        MudDichiarazione::create([
            'anno_riferimento' => 2025,
            'stato'            => MudStato::Bozza,
            'righe'            => [],
            'user_id'          => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(MudIndex::class)
            ->set('filtro_anno', '2019')
            ->set('stato', 'inviata')
            ->assertSee('MUD-STUB-2019-TEST0001')
            ->assertViewHas('dichiarazioni', fn ($paginator) => $paginator->total() === 1
                && $paginator->first()->anno_riferimento === 2019);
    }

    public function test_policy_allows_invio_telematico_for_completata(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $mud = $this->completataSample();

        $this->assertTrue(Gate::forUser($user)->allows('invioTelematico', $mud));
    }

    private function completataSample(): MudDichiarazione
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $mud = MudDichiarazione::create([
            'anno_riferimento' => 2025,
            'stato'            => MudStato::Bozza,
            'righe'            => [
                [
                    'codice_cer_id' => 1,
                    'codice'        => '16.01.04',
                    'descrizione'   => 'Rifiuti ferrosi',
                    'carichi_kg'    => 100,
                    'scarichi_kg'   => 20,
                    'saldo_kg'      => 80,
                ],
            ],
            'user_id'          => $user->id,
        ]);

        return app(MudService::class)->completa($mud);
    }
}
