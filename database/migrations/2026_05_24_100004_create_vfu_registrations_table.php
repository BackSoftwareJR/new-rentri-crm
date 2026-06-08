<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vfu_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('targa', 20);
            $table->string('telaio', 50);
            $table->string('codice_motore', 50)->nullable();
            $table->string('marca', 100);
            $table->string('modello', 120);
            $table->string('stato', 30)->default('bozza');
            $table->decimal('peso_kg', 10, 2)->default(0);
            $table->date('data_consegna')->nullable();
            $table->date('data_accettazione')->nullable();
            $table->timestamp('bonifica_pericolosi_completata_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('targa');
            $table->index('telaio');
            $table->index('stato');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vfu_registrations');
    }
};
