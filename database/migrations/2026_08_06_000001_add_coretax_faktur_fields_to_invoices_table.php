<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Holds the faktur pajak CoreTax issues back to us after the XLSX upload.
 *
 * Deliberately separate from the existing `faktur_pajak` / `faktur_pajak_status`
 * pair: those track the faktur pajak FILE attached on the invoice's Files tab,
 * and `tax_number` already carries a copy of the customer NPWP.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('coretax_faktur_number', 50)->nullable()->after('faktur_pajak_status');
            $table->date('coretax_faktur_date')->nullable()->after('coretax_faktur_number');
            $table->string('coretax_status', 30)->nullable()->after('coretax_faktur_date');

            $table->index('coretax_faktur_number', 'invoices_coretax_faktur_number_index');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('invoices_coretax_faktur_number_index');
            $table->dropColumn(['coretax_faktur_number', 'coretax_faktur_date', 'coretax_status']);
        });
    }
};
