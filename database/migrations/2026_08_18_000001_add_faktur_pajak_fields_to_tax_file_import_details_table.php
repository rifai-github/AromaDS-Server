<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The PDF Faktur Pajak import path now creates one tax_file_imports row per
 * PDF, with exactly one detail row underneath — so the detail row is the
 * natural home for the fields extracted from the document, not just the
 * columns the old CSV/XLSX batch result needed (invoice_number, tax_number,
 * tax_date, tax_amount, status, remarks).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tax_file_import_details', function (Blueprint $table) {
            $table->string('buyer_npwp', 30)->nullable()->after('tax_number');
            $table->string('buyer_name')->nullable()->after('buyer_npwp');
            $table->decimal('dpp', 15, 2)->nullable()->after('tax_amount');
        });
    }

    public function down(): void
    {
        Schema::table('tax_file_import_details', function (Blueprint $table) {
            $table->dropColumn(['buyer_npwp', 'buyer_name', 'dpp']);
        });
    }
};
