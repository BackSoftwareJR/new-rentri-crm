<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fatture', function (Blueprint $table) {
            $table->foreignId('ecommerce_ordine_id')
                ->nullable()
                ->after('riferimento_vfu_id')
                ->constrained('ecommerce_ordini')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('fatture', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ecommerce_ordine_id');
        });
    }
};
