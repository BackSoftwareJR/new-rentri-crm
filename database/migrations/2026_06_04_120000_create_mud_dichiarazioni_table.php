<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mud_dichiarazioni', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('anno_riferimento');
            $table->string('stato', 20)->default('bozza');
            $table->json('righe')->nullable();
            $table->json('export_payload')->nullable();
            $table->timestamp('completata_at')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique('anno_riferimento');
            $table->index('stato');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mud_dichiarazioni');
    }
};
