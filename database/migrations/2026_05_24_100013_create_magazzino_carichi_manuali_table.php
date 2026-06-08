<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('magazzino_carichi_manuali', function (Blueprint $table) {
            $table->id();
            $table->foreignId('codice_cer_id')->constrained('codici_cer')->restrictOnDelete();
            $table->decimal('peso_kg', 14, 4);
            $table->date('data');
            $table->text('note')->nullable();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['codice_cer_id', 'data']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('magazzino_carichi_manuali');
    }
};
