<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('codici_cer', function (Blueprint $table) {
            $table->id();
            $table->string('codice', 20)->unique();
            $table->string('descrizione', 500);
            $table->enum('categoria', ['pericoloso', 'altro'])->default('altro');
            $table->string('um', 10)->default('kg');
            $table->decimal('limite_kg', 12, 2)->nullable();
            $table->string('rentri_codice_ref', 64)->nullable();
            $table->boolean('attivo')->default(true);
            $table->timestamps();

            $table->index('categoria');
            $table->index('attivo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('codici_cer');
    }
};
