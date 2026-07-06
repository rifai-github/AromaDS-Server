<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Return Cabang -> Pusat: document trail for the physical goods movement.
 *
 * - submission_letter_file (surat pengajuan): branch's request document, uploaded
 *   for the transfer to justify/accompany the goods leaving the branch.
 * - delivery_note_file (surat jalan): center's dispatch/acknowledgement document
 *   for the branch, uploaded by center staff (typically when marking the transfer
 *   transferred/received).
 *
 * Mirrors the existing delivery_order_file pattern already used for direct
 * branch-to-branch transfers.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_transfers', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_transfers', 'submission_letter_file')) {
                $table->string('submission_letter_file')->nullable()->after('delivery_order_file');
                $table->unsignedBigInteger('submission_letter_uploaded_by')->nullable()->after('submission_letter_file');
                $table->timestamp('submission_letter_uploaded_at')->nullable()->after('submission_letter_uploaded_by');
            }

            if (!Schema::hasColumn('inventory_transfers', 'delivery_note_file')) {
                $table->string('delivery_note_file')->nullable()->after('submission_letter_uploaded_at');
                $table->unsignedBigInteger('delivery_note_uploaded_by')->nullable()->after('delivery_note_file');
                $table->timestamp('delivery_note_uploaded_at')->nullable()->after('delivery_note_uploaded_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventory_transfers', function (Blueprint $table) {
            $columns = ['submission_letter_file', 'submission_letter_uploaded_by', 'submission_letter_uploaded_at', 'delivery_note_file', 'delivery_note_uploaded_by', 'delivery_note_uploaded_at'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('inventory_transfers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
