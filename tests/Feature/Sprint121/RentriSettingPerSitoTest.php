<?php

namespace Tests\Feature\Sprint121;

use App\Models\RentriSetting;
use App\Models\Sito;
use App\Support\Sito\SitoContext;
use Tests\TestCase;

class RentriSettingPerSitoTest extends TestCase
{
    public function test_instance_returns_per_sito_setting_when_configured(): void
    {
        RentriSetting::flushInstanceCache();

        $sitoA = Sito::create(['nome' => 'Nord', 'is_active' => true, 'is_default' => true]);
        $sitoB = Sito::create(['nome' => 'Sud', 'is_active' => true, 'is_default' => false]);

        RentriSetting::query()->create([
            'sito_id' => $sitoA->id,
            'is_demo' => false,
            'ambiente' => 'sandbox',
            'num_iscr_sito' => 'SITE-A-001',
        ]);

        RentriSetting::query()->create([
            'sito_id' => $sitoB->id,
            'is_demo' => false,
            'ambiente' => 'production',
            'num_iscr_sito' => 'SITE-B-001',
        ]);

        SitoContext::setActiveSitoId($sitoA->id);
        $this->assertSame('SITE-A-001', RentriSetting::instance()->num_iscr_sito);

        SitoContext::setActiveSitoId($sitoB->id);
        $this->assertSame('SITE-B-001', RentriSetting::instance()->num_iscr_sito);
    }

    public function test_instance_falls_back_to_global_setting_without_sito_row(): void
    {
        RentriSetting::flushInstanceCache();

        $sito = Sito::create(['nome' => 'Unico', 'is_active' => true, 'is_default' => true]);

        RentriSetting::query()->create([
            'sito_id' => null,
            'is_demo' => false,
            'ambiente' => 'sandbox',
            'num_iscr_sito' => 'GLOBAL-001',
        ]);

        SitoContext::setActiveSitoId($sito->id);
        $this->assertSame('GLOBAL-001', RentriSetting::instance()->num_iscr_sito);
    }
}
