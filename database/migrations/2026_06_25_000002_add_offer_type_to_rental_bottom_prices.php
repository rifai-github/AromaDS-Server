<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('rental_bottom_prices', 'offer_type')) {
            Schema::table('rental_bottom_prices', function (Blueprint $table) {
                // Matches Quotation::rental_unit values ('hari' / 'bulan') for consistent comparisons.
                $table->enum('offer_type', ['hari', 'bulan'])->default('bulan')->after('branch_id');
            });

            // Existing rows predate the offer-type split; widen the unique
            // constraint so harian/bulanan bottom prices can coexist per branch+rental.
            Schema::table('rental_bottom_prices', function (Blueprint $table) {
                $table->dropUnique('rental_bottom_prices_unique');
                $table->unique(['master_rental_id', 'branch_id', 'offer_type', 'deleted_at'], 'rental_bottom_prices_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('rental_bottom_prices', 'offer_type')) {
            Schema::table('rental_bottom_prices', function (Blueprint $table) {
                $table->dropUnique('rental_bottom_prices_unique');
                $table->unique(['master_rental_id', 'branch_id', 'deleted_at'], 'rental_bottom_prices_unique');
            });

            Schema::table('rental_bottom_prices', function (Blueprint $table) {
                $table->dropColumn('offer_type');
            });
        }
    }
};
