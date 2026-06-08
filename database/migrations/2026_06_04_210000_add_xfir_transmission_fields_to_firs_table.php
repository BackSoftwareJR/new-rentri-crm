<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('firs', function (Blueprint $table): void {
            $table->timestamp('xfir_trasmesso_at')->nullable()->after('firmato_at');
            $table->string('xfir_protocollo', 100)->nullable()->after('xfir_trasmesso_at');
            $table->string('xfir_transazione_id', 100)->nullable()->after('xfir_protocollo');
            $table->index('xfir_trasmesso_at');
        });
    }

    public function down(): void
    {
        Schema::table('firs', function (Blueprint $table): void {
            $table->dropIndex(['xfir_trasmesso_at']);
            $table->dropColumn(['xfir_trasmesso_at', 'xfir_protocollo', 'xfir_transazione_id']);
        });
    }
};
