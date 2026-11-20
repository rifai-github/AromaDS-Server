<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Meeting UAT Komisi 17 Nov 2026.
 *
 * - Status baru `over_period`: penerimaan kas dari customer melewati masa CR. Nominal komisi
 *   jadi 0, nominal aslinya disimpan di `original_amount` sebagai batas atas bila admin
 *   mengembalikannya secara manual (kebijakan management).
 * - `payout_requested_at` / `payout_requested_by`: marketing menekan "Request Pencairan" di
 *   dashboard komisinya; admin melihat tandanya di halaman Commission Payment.
 */
return new class extends Migration
{
    private const STATUSES_WITH = "'calculated','approved','paid','cancelled','over_period'";

    private const STATUSES_WITHOUT = "'calculated','approved','paid','cancelled'";

    public function up(): void
    {
        if (! Schema::hasTable('commission_calculations')) {
            return;
        }

        Schema::table('commission_calculations', function (Blueprint $table) {
            if (! Schema::hasColumn('commission_calculations', 'original_amount')) {
                $table->decimal('original_amount', 15, 2)->nullable()->after('final_amount');
            }
            if (! Schema::hasColumn('commission_calculations', 'payout_requested_at')) {
                $table->timestamp('payout_requested_at')->nullable()->after('payment_date');
            }
            if (! Schema::hasColumn('commission_calculations', 'payout_requested_by')) {
                $table->unsignedBigInteger('payout_requested_by')->nullable()->after('payout_requested_at');
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `commission_calculations` MODIFY `status` ENUM('.self::STATUSES_WITH.") NOT NULL DEFAULT 'calculated'");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('commission_calculations')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::table('commission_calculations')->where('status', 'over_period')->update(['status' => 'cancelled']);
            DB::statement('ALTER TABLE `commission_calculations` MODIFY `status` ENUM('.self::STATUSES_WITHOUT.") NOT NULL DEFAULT 'calculated'");
        }

        Schema::table('commission_calculations', function (Blueprint $table) {
            foreach (['original_amount', 'payout_requested_at', 'payout_requested_by'] as $column) {
                if (Schema::hasColumn('commission_calculations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
