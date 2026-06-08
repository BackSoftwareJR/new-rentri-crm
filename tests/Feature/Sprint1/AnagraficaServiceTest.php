<?php

namespace Tests\Feature\Sprint1;

use App\Domain\Anagrafiche\AnagraficaService;
use App\Domain\Anagrafiche\AuthorizationComplianceService;
use App\Models\Anagrafica;
use App\Models\Authorization;
use Tests\TestCase;

class AnagraficaServiceTest extends TestCase
{
    public function test_creates_anagrafica_with_authorization(): void
    {
        $service = app(AnagraficaService::class);

        $anagrafica = $service->create([
            'tipo' => 'trasportatore',
            'ragione_sociale' => 'Trasporti Test S.r.l.',
            'piva' => '11111111111',
        ], [
            [
                'numero' => 'AUT-001',
                'rilasciata_il' => '2024-01-01',
                'scade_il' => now()->addYear()->format('Y-m-d'),
                'tipo' => 'trasporto_rifiuti',
            ],
        ]);

        $this->assertDatabaseHas('anagrafiche', [
            'id' => $anagrafica->id,
            'ragione_sociale' => 'Trasporti Test S.r.l.',
        ]);
        $this->assertDatabaseHas('authorizations', [
            'anagrafica_id' => $anagrafica->id,
            'numero' => 'AUT-001',
        ]);
    }

    public function test_compliance_passes_with_valid_authorization(): void
    {
        $anagrafica = Anagrafica::factory()->trasportatore()->create();
        Authorization::factory()->valid()->create(['anagrafica_id' => $anagrafica->id]);
        $anagrafica->load('authorizations');

        $compliance = app(AuthorizationComplianceService::class);

        $this->assertTrue($anagrafica->hasValidAuthorization());
        $this->assertTrue($compliance->hasValidAuthorization($anagrafica));
    }

    public function test_compliance_fails_when_authorization_expired(): void
    {
        $anagrafica = Anagrafica::factory()->trasportatore()->create();
        Authorization::factory()->expired()->create(['anagrafica_id' => $anagrafica->id]);
        $anagrafica->load('authorizations');

        $compliance = app(AuthorizationComplianceService::class);

        $this->assertFalse($anagrafica->hasValidAuthorization());
        $this->assertSame('non_conforme', $compliance->anagraficaComplianceStatus($anagrafica));
    }

    public function test_expiring_authorization_triggers_warning_status(): void
    {
        $anagrafica = Anagrafica::factory()->trasportatore()->create();
        Authorization::factory()->expiringSoon()->create(['anagrafica_id' => $anagrafica->id]);
        $anagrafica->load('authorizations');

        $compliance = app(AuthorizationComplianceService::class);

        $this->assertTrue($anagrafica->hasValidAuthorization());
        $this->assertSame('in_scadenza', $compliance->anagraficaComplianceStatus($anagrafica));
    }

    public function test_privato_does_not_require_authorization(): void
    {
        $anagrafica = Anagrafica::factory()->create(['tipo' => 'privato']);
        $anagrafica->load('authorizations');

        $this->assertTrue($anagrafica->hasValidAuthorization());
        $this->assertSame('non_richiesta', app(AuthorizationComplianceService::class)->anagraficaComplianceStatus($anagrafica));
    }
}
