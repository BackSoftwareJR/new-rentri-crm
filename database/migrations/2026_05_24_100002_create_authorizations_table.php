<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authorizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anagrafica_id')->constrained('anagrafiche')->cascadeOnDelete();
            $table->string('numero', 100);
            $table->date('rilasciata_il');
            $table->date('scade_il')->nullable();
            $table->string('tipo', 50);
            $table->string('documento_path', 500)->nullable();
            $table->timestamps();

            $table->index(['anagrafica_id', 'scade_il']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('authorizations');
    }
};
