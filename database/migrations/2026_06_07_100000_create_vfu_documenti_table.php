<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vfu_documenti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vfu_registration_id')->constrained('vfu_registrations')->cascadeOnDelete();
            $table->string('tipo', 50);
            $table->string('path', 500);
            $table->string('original_name', 255);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['vfu_registration_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vfu_documenti');
    }
};
