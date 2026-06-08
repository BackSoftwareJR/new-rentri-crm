<?php

namespace Database\Seeders;

use Database\Seeders\BonificaTestSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            CodiceCerSeeder::class,
            RentriSettingSeeder::class,
            SitoSeeder::class,
            DemoDataSeeder::class,
            BonificaTestSeeder::class,
        ]);
    }
}
