<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('magazzino_rifiuti', function (Blueprint $table) {
            $table->decimal('soglia_minima_kg', 14, 4)->nullable()->after('quantita_attuale_kg');
        });
    }

    public function down(): void
    {
        Schema::table('magazzino_rifiuti', function (Blueprint $table) {
            $table->dropColumn('soglia_minima_kg');
        });
    }
};
