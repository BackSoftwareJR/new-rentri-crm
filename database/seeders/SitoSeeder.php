<?php

namespace Database\Seeders;

use App\Models\RentriSetting;
use App\Models\Sito;
use Illuminate\Database\Seeder;

class SitoSeeder extends Seeder
{
    public function run(): void
    {
        if (Sito::query()->exists()) {
            return;
        }

        $setting = RentriSetting::query()
            ->where('is_demo', false)
            ->orderBy('id')
            ->first()
            ?? RentriSetting::query()->orderBy('id')->first();

        $sito = Sito::create([
            'nome'          => $setting?->ragione_sociale ?? 'Impianto principale',
            'indirizzo'     => null,
            'num_iscr_sito' => $setting?->num_iscr_sito,
            'cf_operatore'  => $setting?->cf_operatore ?? $setting?->cf,
            'is_active'     => true,
            'is_default'    => true,
        ]);

        if ($setting !== null && $setting->sito_id === null) {
            $setting->update(['sito_id' => $sito->id]);
        }
    }
}
