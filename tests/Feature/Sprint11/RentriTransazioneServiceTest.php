<?php

namespace Tests\Feature\Sprint11;

use App\Domain\Rentri\RentriTransazioneService;
use App\Models\RentriTransazione;
use Illuminate\Support\Str;
use Tests\TestCase;

class RentriTransazioneServiceTest extends TestCase
{
    public function test_list_filters_by_date_range(): void
    {
        $old = RentriTransazione::create([
            'transazione_id' => (string) Str::uuid(),
            'tipo_api'       => 'registro',
            'stato'          => 'completata',
            'request_json'   => ['method' => 'POST', 'endpoint' => '/registro/trasmetti'],
            'response_json'  => ['esito' => 'accettato'],
            'completed_at'   => now()->subDays(10),
        ]);
        $old->created_at = now()->subDays(10);
        $old->save();

        RentriTransazione::create([
            'transazione_id' => (string) Str::uuid(),
            'tipo_api'       => 'codifiche',
            'stato'          => 'completata',
            'request_json'   => ['method' => 'GET', 'endpoint' => '/codifiche/cer'],
            'response_json'  => ['items' => []],
            'completed_at'   => now(),
        ]);

        $service = app(RentriTransazioneService::class);

        $recent = $service->list([
            'data_da' => now()->subDay()->toDateString(),
            'data_a'  => now()->toDateString(),
        ]);

        $this->assertSame(1, $recent->total());
        $this->assertSame('codifiche', $recent->first()->tipo_api);
    }

    public function test_format_json_pretty_prints_array(): void
    {
        $service = app(RentriTransazioneService::class);
        $json = $service->formatJson(['status' => 'ok', 'nested' => ['a' => 1]]);

        $this->assertStringContainsString('"status": "ok"', $json);
        $this->assertStringContainsString("\n", $json);
    }
}
