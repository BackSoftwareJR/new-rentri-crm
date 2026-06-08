<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\User;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \App\Models\RentriSetting::flushInstanceCache();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $segreteria = User::where('email', 'segreteria@example.com')->first();
        if ($segreteria !== null) {
            RateLimiter::clear('fir-vidima:'.$segreteria->id);
            RateLimiter::clear('fir-firma:'.$segreteria->id);
            RateLimiter::clear('rentri-transmit:'.$segreteria->id);
        }
    }
}
