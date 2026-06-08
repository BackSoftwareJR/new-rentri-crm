<?php

namespace Database\Seeders;

use App\Models\RentriSetting;
use Illuminate\Database\Seeder;

class RentriSettingSeeder extends Seeder
{
    public function run(): void
    {
        RentriSetting::firstOrCreate(
            [],
            [
                'ambiente'                  => 'sandbox',
                'cf'                        => 'RSSMRA80A01H501Z',
                'piva'                      => '12345678901',
                'ragione_sociale'           => 'Autodemolizione Demo Srl',
                'num_iscr_sito'             => 'SANDBOX-DEMO-001',
                'onboarding_step_completed' => 0,
            ]
        );
    }
}
