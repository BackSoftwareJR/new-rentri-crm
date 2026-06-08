<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trasporti', function (Blueprint $table) {
            $table->foreignId('magazzino_svuotamento_id')
                ->nullable()
                ->after('id')
                ->constrained('magazzino_svuotamenti')
                ->nullOnDelete();

            $table->unique('magazzino_svuotamento_id');
        });
    }

    public function down(): void
    {
        Schema::table('trasporti', function (Blueprint $table) {
            $table->dropConstrainedForeignId('magazzino_svuotamento_id');
        });
    }
};
