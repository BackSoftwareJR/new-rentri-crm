<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mud_dichiarazioni', function (Blueprint $table) {
            $table->timestamp('inviata_at')->nullable()->after('completata_at');
            $table->string('invio_protocollo', 64)->nullable()->after('inviata_at');
            $table->json('invio_risposta')->nullable()->after('invio_protocollo');

            $table->index('inviata_at');
        });
    }

    public function down(): void
    {
        Schema::table('mud_dichiarazioni', function (Blueprint $table) {
            $table->dropIndex(['inviata_at']);
            $table->dropColumn(['inviata_at', 'invio_protocollo', 'invio_risposta']);
        });
    }
};
