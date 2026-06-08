<?php

namespace Tests\Feature\Sprint31;

use App\Services\Rentri\Dto\RentriFirVidimaRequest;
use App\Services\Rentri\RentriEndpoints;
use Tests\TestCase;

class RentriFirVidimaRequestTest extends TestCase
{
    public function test_vidima_request_maps_to_rentri_v1_endpoint(): void
    {
        $request = new RentriFirVidimaRequest(
            codiceBlocco: 'BLK-001',
            numIscrSito: 'OP12345678901-PD00001',
            payload: ['note' => 'test'],
        );

        $this->assertSame('/fir/vidima', $request->logicalEndpoint());
        $this->assertSame('POST', $request->httpMethod());
        $this->assertSame('/vidimazione-formulari/v1.0/BLK-001', $request->livePath());
        $this->assertSame(RentriEndpoints::FIR_VIDIMazione.'/BLK-001', RentriEndpoints::firVidimaPath('BLK-001'));
    }
}
