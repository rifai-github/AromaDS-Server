<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_adjustment_items', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_adjustment_items', 'serial_numbers')) {
                $table->json('serial_numbers')->nullable()->after('notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_adjustment_items', function (Blueprint $table) {
            if (Schema::hasColumn('stock_adjustment_items', 'serial_numbers')) {
                $table->dropColumn('serial_numbers');
            }
        });
    }
};
