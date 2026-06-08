<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fatture', function (Blueprint $table) {
            $table->id();
            $table->string('numero_fattura', 20)->unique();
            $table->enum('tipo', ['fattura', 'nota_credito', 'preventivo'])->default('fattura');
            $table->foreignId('anagrafica_id')->constrained('anagrafiche')->restrictOnDelete();
            $table->date('data_emissione');
            $table->date('data_scadenza')->nullable();
            $table->enum('stato', ['bozza', 'emessa', 'pagata', 'scaduta', 'annullata'])->default('bozza');
            $table->decimal('imponibile', 12, 2)->default(0);
            $table->unsignedTinyInteger('iva_percentuale')->default(22);
            $table->decimal('iva_importo', 12, 2)->default(0);
            $table->decimal('totale', 12, 2)->default(0);
            $table->text('note')->nullable();
            $table->string('metodo_pagamento', 50)->nullable();
            $table->foreignId('riferimento_vfu_id')->nullable()->constrained('vfu_registrations')->nullOnDelete();
            $table->string('pdf_path')->nullable();
            $table->string('motivo_annullamento')->nullable();
            $table->date('data_pagamento')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['stato', 'data_emissione']);
            $table->index(['anagrafica_id', 'stato']);
            $table->index('tipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fatture');
    }
};
