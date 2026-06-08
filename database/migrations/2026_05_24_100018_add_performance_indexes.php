<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bonifica_vfu_movimenti', function (Blueprint $table) {
            $table->index('codice_cer_id');
        });

    }

    public function down(): void
    {
        Schema::table('bonifica_vfu_movimenti', function (Blueprint $table) {
            $table->dropIndex(['codice_cer_id']);
        });

    }
};
