<?php

namespace Database\Seeders;

use App\Models\CodiceCer;
use App\Models\MagazzinoRifiuto;
use Illuminate\Database\Seeder;

class CodiceCerSeeder extends Seeder
{
    public function run(): void
    {
        $rows = require __DIR__.'/data/codici_cer_autodemolizione.php';

        foreach ($rows as $row) {
            $cer = CodiceCer::updateOrCreate(
                ['codice' => $row['codice']],
                $row
            );

            MagazzinoRifiuto::firstOrCreate(
                ['codice_cer_id' => $cer->id],
                ['quantita_attuale_kg' => 0]
            );
        }
    }
}
