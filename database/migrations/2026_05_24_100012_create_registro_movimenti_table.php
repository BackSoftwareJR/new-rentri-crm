<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registro_movimenti', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['carico', 'scarico']);
            $table->foreignId('codice_cer_id')->constrained('codici_cer')->restrictOnDelete();
            $table->decimal('peso_kg', 14, 4);
            $table->timestamp('data_movimento');
            $table->string('source_type', 100)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->text('note')->nullable();
            $table->boolean('rentri_trasmesso')->default(false);
            $table->foreignId('rentri_transmission_id')->nullable()->constrained('rentri_transmissioni')->nullOnDelete();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();

            $table->index(['codice_cer_id', 'data_movimento']);
            $table->index(['source_type', 'source_id']);
            $table->index('rentri_trasmesso');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registro_movimenti');
    }
};
