<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('firs', function (Blueprint $table) {
            $table->id();
            $table->string('numero_fir', 50)->nullable();
            $table->string('codice_blocco', 50);
            $table->unsignedInteger('progressivo');
            $table->string('stato', 30)->default('bozza');
            $table->timestamp('vidimato_at')->nullable();
            $table->text('qr_payload')->nullable();
            $table->foreignId('trasporto_id')->nullable()->constrained('trasporti')->nullOnDelete();
            $table->decimal('peso_partenza_kg', 14, 4);
            $table->decimal('peso_arrivo_kg', 14, 4)->nullable();
            $table->timestamps();

            $table->unique(['codice_blocco', 'progressivo']);
            $table->index('stato');
        });

        Schema::table('trasporti', function (Blueprint $table) {
            $table->foreign('fir_id')->references('id')->on('firs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('trasporti', function (Blueprint $table) {
            $table->dropForeign(['fir_id']);
        });

        Schema::dropIfExists('firs');
    }
};
