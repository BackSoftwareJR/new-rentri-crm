<?php

namespace Tests\Feature\Sprint41;

use App\Domain\Registro\Exceptions\RegistroMovimentoLockedException;
use App\Enums\RegistroMovimentoTipo;
use App\Models\CodiceCer;
use App\Models\MagazzinoCaricoManuale;
use App\Models\RegistroMovimento;
use App\Models\RentriTransmissione;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class RegistroMovimentoLockTest extends TestCase
{
    public function test_locked_movimento_cannot_be_updated_or_deleted(): void
    {
        $cer = CodiceCer::factory()->create();
        $transmissione = RentriTransmissione::create([
            'periodo_da'   => now()->startOfMonth(),
            'periodo_a'    => now(),
            'payload_hash' => hash('sha256', 'test'),
            'esito'        => 'accettato',
            'trasmesso_at' => now(),
            'response_json'=> ['protocollo' => 'LOCK-TEST'],
        ]);

        $movimento = RegistroMovimento::create([
            'tipo'                   => RegistroMovimentoTipo::Carico,
            'codice_cer_id'          => $cer->id,
            'peso_kg'                => 10,
            'data_movimento'         => now()->subDay(),
            'source_type'            => MagazzinoCaricoManuale::class,
            'source_id'              => 1,
            'rentri_trasmesso'       => true,
            'rentri_transmission_id' => $transmissione->id,
            'locked_at'              => now(),
        ]);

        $this->expectException(RegistroMovimentoLockedException::class);
        $movimento->update(['note' => 'tentativo modifica']);
    }

    public function test_policy_denies_delete_on_locked_movimento(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $cer = CodiceCer::factory()->create();

        $movimento = RegistroMovimento::create([
            'tipo'             => RegistroMovimentoTipo::Scarico,
            'codice_cer_id'    => $cer->id,
            'peso_kg'          => 8,
            'data_movimento'   => now()->subDay(),
            'source_type'      => MagazzinoCaricoManuale::class,
            'source_id'        => 1,
            'rentri_trasmesso' => true,
            'locked_at'        => now(),
        ]);

        $this->assertFalse($user->can('delete', $movimento));
        $this->assertFalse($user->can('update', $movimento));
    }

    public function test_demo_scoped_locked_movimento_visible_in_demo_mode(): void
    {
        Config::set('demo.enabled', true);

        $cer = CodiceCer::factory()->create();
        $movimento = RegistroMovimento::create([
            'tipo'             => RegistroMovimentoTipo::Carico,
            'codice_cer_id'    => $cer->id,
            'peso_kg'          => 3,
            'data_movimento'   => now()->subDay(),
            'source_type'      => MagazzinoCaricoManuale::class,
            'source_id'        => 1,
            'rentri_trasmesso' => true,
            'locked_at'        => now(),
            'is_demo'          => true,
        ]);

        $this->assertTrue(RegistroMovimento::query()->whereKey($movimento->id)->exists());
        $this->assertTrue($movimento->fresh()->isLocked());
    }
}
