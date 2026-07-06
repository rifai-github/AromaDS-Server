<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Standalone warehouse-to-warehouse return (no job schedule involved): a branch can
 * already create a direct Branch -> Center InventoryTransfer (canTransferTo() already
 * allows it), but had no structured way to record WHY the goods are being returned -
 * only the generic free-text 'notes' field. Mirrors MaterialReturn's
 * return_reason/return_reason_category so warehouse-initiated returns get the same
 * audit/reporting categorization as job-schedule-triggered ones.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_transfers', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_transfers', 'return_reason')) {
                $table->text('return_reason')->nullable()->after('notes');
            }

            if (!Schema::hasColumn('inventory_transfers', 'return_reason_category')) {
                $table->string('return_reason_category', 64)->nullable()->after('return_reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventory_transfers', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_transfers', 'return_reason_category')) {
                $table->dropColumn('return_reason_category');
            }
            if (Schema::hasColumn('inventory_transfers', 'return_reason')) {
                $table->dropColumn('return_reason');
            }
        });
    }
};
