<?php

namespace Tests\Feature\Sprint12;

use App\Domain\Mud\MudService;
use App\Enums\MudStato;
use App\Enums\RegistroMovimentoTipo;
use App\Models\CodiceCer;
use App\Models\MagazzinoCaricoManuale;
use App\Models\MudDichiarazione;
use App\Models\RegistroMovimento;
use App\Models\User;
use Tests\TestCase;

class MudServiceTest extends TestCase
{
    public function test_aggregate_righe_per_anno_from_registro(): void
    {
        $cer = CodiceCer::factory()->create(['codice' => '16.01.04']);
        $anno = 2025;

        RegistroMovimento::create([
            'tipo'           => RegistroMovimentoTipo::Carico,
            'codice_cer_id'  => $cer->id,
            'peso_kg'        => 50,
            'data_movimento' => '2025-06-15',
            'source_type'    => MagazzinoCaricoManuale::class,
            'source_id'      => 1,
        ]);

        $righe = app(MudService::class)->aggregateRighePerAnno($anno);

        $this->assertCount(1, $righe);
        $this->assertSame('16.01.04', $righe[0]['codice']);
        $this->assertSame(50.0, $righe[0]['carichi_kg']);
        $this->assertSame(0.0, $righe[0]['scarichi_kg']);
    }

    public function test_create_bozza_rejects_duplicate_year(): void
    {
        MudDichiarazione::create([
            'anno_riferimento' => 2024,
            'stato'            => MudStato::Bozza,
            'righe'            => [],
            'user_id'          => User::factory()->create()->id,
        ]);

        $this->expectException(\InvalidArgumentException::class);

        app(MudService::class)->createBozza(2024, 1);
    }

    public function test_completa_builds_export_payload(): void
    {
        $mud = MudDichiarazione::create([
            'anno_riferimento' => 2023,
            'stato'            => MudStato::Bozza,
            'righe'            => [
                ['codice' => '16.01.04', 'carichi_kg' => 10, 'scarichi_kg' => 2, 'saldo_kg' => 8],
            ],
            'user_id'          => User::factory()->create()->id,
        ]);

        $result = app(MudService::class)->completa($mud);

        $this->assertSame(MudStato::Completata, $result->stato);
        $this->assertSame(10.0, (float) ($result->export_payload['totali']['carichi_kg'] ?? 0));
    }
}
