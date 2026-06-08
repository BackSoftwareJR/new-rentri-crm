<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smontaggio_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vfu_registration_id')->constrained('vfu_registrations')->cascadeOnDelete();
            $table->foreignId('operatore_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('stato', 30)->default('avviato'); // avviato | in_corso | completato
            $table->text('note')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();

            $table->index('vfu_registration_id');
            $table->index('stato');
        });

        Schema::create('smontaggio_ricambi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('smontaggio_session_id')->constrained('smontaggio_sessions')->cascadeOnDelete();
            $table->string('numero_parte', 100)->nullable();
            $table->string('descrizione', 500);
            $table->string('condizione', 50)->default('buono'); // buono | accettabile | per_ricambi
            $table->decimal('valore_stimato', 10, 2)->nullable();
            $table->string('foto_path', 500)->nullable();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();

            $table->index('smontaggio_session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smontaggio_ricambi');
        Schema::dropIfExists('smontaggio_sessions');
    }
};
