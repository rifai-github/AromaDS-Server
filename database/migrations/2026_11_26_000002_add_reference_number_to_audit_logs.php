<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * QA 25 Nov 2026: audit trail tidak menampilkan nomor dokumen ("job advice action delete" tanpa
 * nomor JA). Kolom ini diisi otomatis oleh AuditLog::booted() dari isi record. Baris lama tetap
 * kosong dan ditampilkan dari isi log-nya (display_reference_number), tanpa backfill.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('audit_logs') || Schema::hasColumn('audit_logs', 'reference_number')) {
            return;
        }

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('reference_number', 100)->nullable()->after('model_id');
            $table->index('reference_number');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('audit_logs') || ! Schema::hasColumn('audit_logs', 'reference_number')) {
            return;
        }

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['reference_number']);
            $table->dropColumn('reference_number');
        });
    }
};
