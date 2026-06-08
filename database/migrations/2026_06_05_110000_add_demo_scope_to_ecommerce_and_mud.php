<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecommerce_prodotti', function (Blueprint $table) {
            $table->boolean('is_demo')->default(false)->after('attivo');
            $table->index('is_demo');
        });

        Schema::table('ecommerce_ordini', function (Blueprint $table) {
            $table->boolean('is_demo')->default(false)->after('righe');
            $table->index('is_demo');
        });

        Schema::table('mud_dichiarazioni', function (Blueprint $table) {
            $table->boolean('is_demo')->default(false)->after('user_id');
            $table->index('is_demo');
        });
    }

    public function down(): void
    {
        Schema::table('mud_dichiarazioni', function (Blueprint $table) {
            $table->dropIndex(['is_demo']);
            $table->dropColumn('is_demo');
        });

        Schema::table('ecommerce_ordini', function (Blueprint $table) {
            $table->dropIndex(['is_demo']);
            $table->dropColumn('is_demo');
        });

        Schema::table('ecommerce_prodotti', function (Blueprint $table) {
            $table->dropIndex(['is_demo']);
            $table->dropColumn('is_demo');
        });
    }
};
