<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_logs', function (Blueprint $table) {
            $table->id();
            $table->string('trace_id', 64)->index();
            $table->string('level', 16)->index();
            $table->string('module', 32)->index();
            $table->string('channel', 32);
            $table->string('action', 128);
            $table->text('message');
            $table->string('entity_type', 64)->nullable();
            $table->string('entity_id', 64)->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->boolean('demo_mode')->default(false)->index();
            $table->string('outcome', 32)->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_logs');
    }
};
