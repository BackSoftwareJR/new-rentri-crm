<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anagrafiche', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 50);
            $table->string('ragione_sociale', 200);
            $table->string('piva', 20)->nullable();
            $table->string('codice_fiscale', 16)->nullable();
            $table->string('codice_sdi', 7)->nullable();
            $table->string('pec', 255)->nullable();
            $table->string('indirizzo', 255)->nullable();
            $table->string('citta', 100)->nullable();
            $table->string('cap', 10)->nullable();
            $table->string('provincia', 2)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('email', 255)->nullable();
            $table->boolean('gestisce_trasporti')->default(false);
            $table->string('rentri_soggetto_id', 64)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('tipo');
            $table->index('piva');
            $table->index('codice_fiscale');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anagrafiche');
    }
};
