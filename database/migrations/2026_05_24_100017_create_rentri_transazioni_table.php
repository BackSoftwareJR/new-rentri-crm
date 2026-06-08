<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rentri_transazioni', function (Blueprint $table) {
            $table->id();
            $table->string('transazione_id', 64)->unique();
            $table->string('tipo_api', 100);
            $table->string('stato', 30)->default('in_corso');
            $table->json('request_json')->nullable();
            $table->json('response_json')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('stato');
            $table->index('tipo_api');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rentri_transazioni');
    }
};
