<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bonifica_vfu', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vfu_registration_id')->constrained('vfu_registrations')->cascadeOnDelete();
            $table->string('stato', 30)->default('in_corso');
            $table->string('fase', 50)->nullable();
            $table->timestamp('data_inizio');
            $table->timestamp('data_completamento')->nullable();
            $table->timestamps();

            $table->index(['vfu_registration_id', 'stato']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bonifica_vfu');
    }
};
