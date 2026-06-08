<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('magazzino_rifiuti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('codice_cer_id')->unique()->constrained('codici_cer')->cascadeOnDelete();
            $table->decimal('quantita_attuale_kg', 14, 4)->default(0);
            $table->date('oldest_load_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('magazzino_rifiuti');
    }
};
