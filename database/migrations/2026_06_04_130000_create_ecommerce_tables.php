<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_prodotti', function (Blueprint $table) {
            $table->id();
            $table->string('codice', 50)->unique();
            $table->string('nome', 200);
            $table->string('descrizione', 500)->nullable();
            $table->string('categoria', 100)->default('generico');
            $table->decimal('prezzo', 10, 2)->default(0);
            $table->unsignedInteger('giacenza')->default(0);
            $table->foreignId('vfu_registration_id')->nullable()->constrained('vfu_registrations')->nullOnDelete();
            $table->boolean('attivo')->default(true);
            $table->timestamps();

            $table->index('categoria');
            $table->index('attivo');
        });

        Schema::create('ecommerce_ordini', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('stato', 20)->default('bozza');
            $table->decimal('totale', 12, 2)->default(0);
            $table->json('righe');
            $table->timestamps();

            $table->index('stato');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_ordini');
        Schema::dropIfExists('ecommerce_prodotti');
    }
};
