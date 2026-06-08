<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rentri_settings', function (Blueprint $table) {
            $table->string('cf_operatore', 16)->nullable()->after('cf');
        });
    }

    public function down(): void
    {
        Schema::table('rentri_settings', function (Blueprint $table) {
            $table->dropColumn('cf_operatore');
        });
    }
};
