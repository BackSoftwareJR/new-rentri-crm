<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rentri_transmissioni', function (Blueprint $table) {
            $table->id();
            $table->date('periodo_da');
            $table->date('periodo_a');
            $table->string('payload_hash', 64);
            $table->string('esito', 30)->default('in_attesa');
            $table->timestamp('trasmesso_at')->nullable();
            $table->text('note')->nullable();
            $table->json('response_json')->nullable();
            $table->timestamps();

            $table->index(['periodo_da', 'periodo_a']);
            $table->index('esito');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rentri_transmissioni');
    }
};
