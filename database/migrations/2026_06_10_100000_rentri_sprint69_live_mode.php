<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rentri_settings', function (Blueprint $table) {
            $table->timestamp('live_mode_enabled_at')->nullable()->after('last_health_status');
            $table->timestamp('firma_live_enabled_at')->nullable()->after('live_mode_enabled_at');
        });
    }

    public function down(): void
    {
        Schema::table('rentri_settings', function (Blueprint $table) {
            $table->dropColumn(['live_mode_enabled_at', 'firma_live_enabled_at']);
        });
    }
};
