<?php

namespace Tests\Feature\Sprint1;

use App\Domain\Magazzino\CodiceCerService;
use App\Http\Requests\CodiceCer\StoreCodiceCerRequest;
use App\Models\CodiceCer;
use App\Models\MagazzinoCaricoManuale;
use App\Models\MagazzinoRifiuto;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CodiceCerServiceTest extends TestCase
{
    public function test_creates_codice_cer_with_zero_stock(): void
    {
        $service = app(CodiceCerService::class);

        $codice = $service->create([
            'codice' => '16 01 04',
            'descrizione' => 'Rifiuto test',
            'categoria' => 'pericoloso',
            'um' => 'kg',
            'attivo' => true,
        ]);

        $this->assertDatabaseHas('codici_cer', ['codice' => '16 01 04']);
        $this->assertDatabaseHas('magazzino_rifiuti', [
            'codice_cer_id' => $codice->id,
            'quantita_attuale_kg' => 0,
        ]);
    }

    public function test_codice_must_be_unique(): void
    {
        CodiceCer::factory()->create(['codice' => '16 01 99']);

        $validator = Validator::make(
            ['codice' => '16 01 99', 'descrizione' => 'Duplicato', 'categoria' => 'altro', 'um' => 'kg'],
            StoreCodiceCerRequest::baseRules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('codice', $validator->errors()->toArray());
    }

    public function test_deactivates_codice_with_movements(): void
    {
        $codice = CodiceCer::factory()->create(['attivo' => true]);
        MagazzinoRifiuto::create([
            'codice_cer_id' => $codice->id,
            'quantita_attuale_kg' => 10,
        ]);
        MagazzinoCaricoManuale::create([
            'codice_cer_id' => $codice->id,
            'peso_kg' => 1,
            'data' => now(),
            'user_id' => User::first()->id,
        ]);

        $action = app(CodiceCerService::class)->delete($codice->fresh());

        $this->assertSame('deactivated', $action);
        $this->assertFalse($codice->fresh()->attivo);
    }
}
