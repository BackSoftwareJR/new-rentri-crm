<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rentri_settings', function (Blueprint $table) {
            $table->text('firma_cert_path_encrypted')->nullable()->after('cert_scadenza');
            $table->text('firma_cert_password_encrypted')->nullable()->after('firma_cert_path_encrypted');
            $table->date('firma_cert_scadenza')->nullable()->after('firma_cert_password_encrypted');
        });

        Schema::table('firs', function (Blueprint $table) {
            $table->text('xfir_payload')->nullable()->after('qr_payload');
            $table->longText('xfir_signed_payload')->nullable()->after('xfir_payload');
            $table->timestamp('firmato_at')->nullable()->after('vidimato_at');
        });
    }

    public function down(): void
    {
        Schema::table('firs', function (Blueprint $table) {
            $table->dropColumn(['xfir_payload', 'xfir_signed_payload', 'firmato_at']);
        });

        Schema::table('rentri_settings', function (Blueprint $table) {
            $table->dropColumn(['firma_cert_path_encrypted', 'firma_cert_password_encrypted', 'firma_cert_scadenza']);
        });
    }
};
