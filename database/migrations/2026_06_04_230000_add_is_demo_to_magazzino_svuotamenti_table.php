<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('magazzino_svuotamenti', function (Blueprint $table): void {
            $table->boolean('is_demo')->default(false)->after('id');
            $table->index('is_demo');
        });
    }

    public function down(): void
    {
        Schema::table('magazzino_svuotamenti', function (Blueprint $table): void {
            $table->dropIndex(['is_demo']);
            $table->dropColumn('is_demo');
        });
    }
};
