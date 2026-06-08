<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fir_blocchi', function (Blueprint $table) {
            $table->id();
            $table->string('codice_blocco', 50);
            $table->string('num_iscr_sito', 50);
            $table->unsignedInteger('progressivo_ultimo')->default(0);
            $table->timestamps();

            $table->unique(['codice_blocco', 'num_iscr_sito']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fir_blocchi');
    }
};
