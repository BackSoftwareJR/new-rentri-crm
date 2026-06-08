<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rentri_registri', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('anno');
            $table->boolean('vidimato')->default(false);
            $table->string('codice_registro_rentri', 64)->nullable();
            $table->timestamps();

            $table->unique('anno');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rentri_registri');
    }
};
