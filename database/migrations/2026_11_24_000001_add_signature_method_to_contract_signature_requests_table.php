<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 24 Nov 2026: penandatangan boleh mengunggah scan tanda tangan selain menggores di layar.
 * Caranya dicatat (draw | upload) supaya Lembar Pengesahan di PDF final menyebut cara yang benar.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('contract_signature_requests') || Schema::hasColumn('contract_signature_requests', 'signature_method')) {
            return;
        }

        Schema::table('contract_signature_requests', function (Blueprint $table) {
            $table->string('signature_method', 10)->nullable()->after('signature_path');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('contract_signature_requests', 'signature_method')) {
            Schema::table('contract_signature_requests', function (Blueprint $table) {
                $table->dropColumn('signature_method');
            });
        }
    }
};
