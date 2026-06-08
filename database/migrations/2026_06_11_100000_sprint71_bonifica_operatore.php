<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bonifica_vfu', function (Blueprint $table) {
            $table->json('checklist_pericolosi')->nullable()->after('fase');
        });

        Schema::create('ecommerce_prodotto_foto_operatore', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ecommerce_prodotto_id')->constrained('ecommerce_prodotti')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('path', 500);
            $table->boolean('is_demo')->default(false);
            $table->timestamps();

            $table->index('ecommerce_prodotto_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_prodotto_foto_operatore');

        Schema::table('bonifica_vfu', function (Blueprint $table) {
            $table->dropColumn('checklist_pericolosi');
        });
    }
};
