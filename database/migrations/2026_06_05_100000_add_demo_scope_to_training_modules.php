<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('anagrafiche', function (Blueprint $table) {
            $table->boolean('is_demo')->default(false)->after('note');
            $table->index('is_demo');
        });

        Schema::table('vfu_registrations', function (Blueprint $table) {
            $table->boolean('is_demo')->default(false)->after('note');
            $table->index('is_demo');
        });

        Schema::table('rentri_settings', function (Blueprint $table) {
            $table->text('note_operatore')->nullable()->after('last_health_status');
        });
    }

    public function down(): void
    {
        Schema::table('rentri_settings', function (Blueprint $table) {
            $table->dropColumn('note_operatore');
        });

        Schema::table('vfu_registrations', function (Blueprint $table) {
            $table->dropIndex(['is_demo']);
            $table->dropColumn('is_demo');
        });

        Schema::table('anagrafiche', function (Blueprint $table) {
            $table->dropIndex(['is_demo']);
            $table->dropColumn('is_demo');
        });
    }
};
