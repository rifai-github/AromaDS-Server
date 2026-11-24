<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Spec Report 24 Nov 2026 poin 1: tidak semua file kontrak menjadi pelengkap invoice. Saat file
 * diverifikasi, user memilih Ya/Tidak. Hanya file verified dengan is_invoice_attachment = 1 yang
 * ikut ke lampiran invoice.
 *
 * File yang sudah verified sebelum kolom ini ada diisi 1 supaya perilaku lama (semua file
 * verified ikut invoice) tidak berubah untuk data yang sudah ada.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('contract_files') || Schema::hasColumn('contract_files', 'is_invoice_attachment')) {
            return;
        }

        Schema::table('contract_files', function (Blueprint $table) {
            $table->boolean('is_invoice_attachment')->nullable()->after('verification_notes')
                ->comment('Pelengkap invoice: 1 ya, 0 tidak, NULL belum diputuskan');
        });

        DB::table('contract_files')
            ->where('verification_status', 'verified')
            ->update(['is_invoice_attachment' => 1]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('contract_files', 'is_invoice_attachment')) {
            Schema::table('contract_files', function (Blueprint $table) {
                $table->dropColumn('is_invoice_attachment');
            });
        }
    }
};
