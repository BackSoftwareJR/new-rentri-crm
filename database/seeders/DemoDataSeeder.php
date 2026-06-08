<?php

namespace Database\Seeders;

use App\Models\Anagrafica;
use App\Models\Authorization;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $trasportatore = Anagrafica::updateOrCreate(
            ['piva' => '12345678901'],
            [
                'tipo' => 'trasportatore',
                'ragione_sociale' => 'Demo Trasporti S.r.l.',
                'codice_fiscale' => '12345678901',
                'email' => 'trasporti@demo.local',
                'telefono' => '+39 02 0000000',
                'indirizzo' => 'Via Demo 1',
                'citta' => 'Milano',
                'cap' => '20100',
                'provincia' => 'MI',
                'gestisce_trasporti' => true,
            ]
        );

        Authorization::updateOrCreate(
            ['anagrafica_id' => $trasportatore->id, 'numero' => 'AUT-DEMO-001'],
            [
                'rilasciata_il' => now()->subYear(),
                'scade_il' => now()->addMonths(8),
                'tipo' => 'trasporto_rifiuti',
            ]
        );

        Anagrafica::updateOrCreate(
            ['piva' => '98765432109'],
            [
                'tipo' => 'impianto',
                'ragione_sociale' => 'Demo Impianto Recupero S.p.A.',
                'codice_fiscale' => '98765432109',
                'email' => 'impianto@demo.local',
                'indirizzo' => 'Zona Industriale Demo',
                'citta' => 'Torino',
                'cap' => '10100',
                'provincia' => 'TO',
                'gestisce_trasporti' => false,
            ]
        );
    }
}
