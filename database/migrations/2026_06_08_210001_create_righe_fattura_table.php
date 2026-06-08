<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('righe_fattura', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fattura_id')->constrained('fatture')->cascadeOnDelete();
            $table->text('descrizione');
            $table->decimal('quantita', 10, 3)->default(1);
            $table->decimal('prezzo_unitario', 12, 2)->default(0);
            $table->unsignedTinyInteger('iva_percentuale')->default(22);
            $table->decimal('totale_riga', 12, 2)->default(0);
            $table->unsignedSmallInteger('ordine')->default(0);
            $table->timestamps();

            $table->index(['fattura_id', 'ordine']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('righe_fattura');
    }
};
