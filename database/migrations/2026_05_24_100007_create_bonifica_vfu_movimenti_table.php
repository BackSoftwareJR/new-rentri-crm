<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bonifica_vfu_movimenti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bonifica_vfu_id')->constrained('bonifica_vfu')->cascadeOnDelete();
            $table->foreignId('codice_cer_id')->constrained('codici_cer')->restrictOnDelete();
            $table->decimal('quantita', 12, 4);
            $table->decimal('peso_kg', 12, 2);
            $table->string('um', 10)->default('kg');
            $table->timestamps();

            $table->index('bonifica_vfu_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bonifica_vfu_movimenti');
    }
};
