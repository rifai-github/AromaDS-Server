<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Moves the invoice discount from the invoice header down to each rental line.
 *
 * The single invoices.discount_amount field could only express "a discount on
 * this whole invoice", which is wrong once one invoice carries several rooms or
 * rentals negotiated separately. Finance now enters the discount per rental
 * line here.
 *
 * invoices.discount_amount is kept and becomes the SUM of these lines, so the
 * print template, tax calculation and every report that reads the header total
 * keep working untouched. Invoices created before this migration keep whatever
 * header discount they already had - nothing is rewritten.
 *
 * total_price stays gross (quantity x unit_price); the discount is held apart
 * so the invoice subtotal keeps matching the sum of the line totals.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('invoice_rental_details', 'discount_amount')) {
            return;
        }

        Schema::table('invoice_rental_details', function (Blueprint $table) {
            $table->decimal('discount_amount', 15, 2)->default(0)->after('total_price');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('invoice_rental_details', 'discount_amount')) {
            return;
        }

        Schema::table('invoice_rental_details', function (Blueprint $table) {
            $table->dropColumn('discount_amount');
        });
    }
};
