<?php

namespace Database\Seeders;

use App\Enums\VfuStato;
use App\Models\VfuRegistration;
use Illuminate\Database\Seeder;

/**
 * Helper per demo/test: crea VFU accettati pronti per bonifica.
 */
class BonificaTestSeeder extends Seeder
{
  public function run(): void
  {
    if (! app()->environment(['local', 'testing'])) {
      return;
    }

    VfuRegistration::updateOrCreate(
      ['targa' => 'BN001TE'],
      [
        'telaio'            => 'VFUBONIFICA00001',
        'codice_motore'     => 'MOT-BN01',
        'marca'             => 'Fiat',
        'modello'           => 'Panda',
        'stato'             => VfuStato::Accettato,
        'peso_kg'           => 980,
        'data_consegna'     => now()->subDays(4)->toDateString(),
        'data_accettazione' => now()->subDays(3)->toDateString(),
      ]
    );

    VfuRegistration::updateOrCreate(
      ['targa' => 'BN002TE'],
      [
        'telaio'            => 'VFUBONIFICA00002',
        'codice_motore'     => 'MOT-BN02',
        'marca'             => 'Ford',
        'modello'           => 'Fiesta',
        'stato'             => VfuStato::InBonifica,
        'peso_kg'           => 1150,
        'data_consegna'     => now()->subDays(12)->toDateString(),
        'data_accettazione' => now()->subDays(10)->toDateString(),
      ]
    );
  }
}
