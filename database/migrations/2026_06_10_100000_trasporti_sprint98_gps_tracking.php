<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trasporti', function (Blueprint $table) {
            $table->json('gps_last_position')->nullable()->after('note');
            $table->timestamp('gps_tracked_at')->nullable()->after('gps_last_position');

            $table->index('gps_tracked_at');
        });
    }

    public function down(): void
    {
        Schema::table('trasporti', function (Blueprint $table) {
            $table->dropIndex(['gps_tracked_at']);
            $table->dropColumn(['gps_last_position', 'gps_tracked_at']);
        });
    }
};
