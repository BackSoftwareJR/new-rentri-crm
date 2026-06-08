<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rentri_settings', function (Blueprint $table) {
            $table->id();
            $table->string('ambiente', 20)->default('sandbox');
            $table->string('cf', 16)->nullable();
            $table->string('piva', 20)->nullable();
            $table->string('ragione_sociale', 200)->nullable();
            $table->string('num_iscr_sito', 50)->nullable();
            $table->timestamp('registro_vidimato_at')->nullable();
            $table->text('cert_path_encrypted')->nullable();
            $table->text('cert_password_encrypted')->nullable();
            $table->date('cert_scadenza')->nullable();
            $table->unsignedTinyInteger('onboarding_step_completed')->default(0);
            $table->timestamp('last_health_check_at')->nullable();
            $table->json('last_health_status')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rentri_settings');
    }
};
