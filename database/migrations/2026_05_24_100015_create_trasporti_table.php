<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trasporti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('codice_cer_id')->constrained('codici_cer')->restrictOnDelete();
            $table->foreignId('anagrafica_destinatario_id')->constrained('anagrafiche')->restrictOnDelete();
            $table->decimal('quantita_kg', 14, 4);
            $table->decimal('peso_destinazione_kg', 14, 4)->nullable();
            $table->string('stato', 30)->default('bozza');
            $table->unsignedBigInteger('fir_id')->nullable();
            $table->string('fir_partenza_path', 500)->nullable();
            $table->string('fir_arrivo_path', 500)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('stato');
            $table->index('fir_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trasporti');
    }
};
