<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fatture', function (Blueprint $table) {
            $table->string('fattura_pa_xml_path')->nullable()->after('pdf_path');
            $table->enum('sdi_stato', ['da_inviare', 'inviata', 'consegnata', 'scartata', 'accettata'])
                ->nullable()
                ->after('fattura_pa_xml_path');
        });
    }

    public function down(): void
    {
        Schema::table('fatture', function (Blueprint $table) {
            $table->dropColumn(['fattura_pa_xml_path', 'sdi_stato']);
        });
    }
};
