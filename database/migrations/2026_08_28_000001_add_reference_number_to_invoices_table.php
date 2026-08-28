<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records what an ad-hoc invoice was raised FOR.
 *
 * Period invoices are identified by contract + period_invoice, but a one-off charge has no
 * period. LostUnitReportController::generateLostUnitInvoice() already passes
 * 'reference_number' => $report->report_number, and has been doing so all along - the column
 * never existed and Invoice is not fillable for it, so the value was silently dropped and a
 * lost-unit invoice ended up with nothing tying it back to its report.
 *
 * ExtraJobInvoiceService needs the same link, and needs it to be reliable: it is the key that
 * stops a second invoice being raised when an Extra job is completed, un-done and completed
 * again. Indexed because that idempotency check runs on every Extra completion.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('invoices', 'reference_number')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            $table->string('reference_number')->nullable()->after('contract_number');
            $table->index('reference_number');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('invoices', 'reference_number')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['reference_number']);
            $table->dropColumn('reference_number');
        });
    }
};
