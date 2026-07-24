<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_transfers', function (Blueprint $table) {
            if (! Schema::hasColumn('inventory_transfers', 'delivery_order_uploaded_by')) {
                $table->unsignedBigInteger('delivery_order_uploaded_by')->nullable()->after('delivery_order_file');
            }

            if (! Schema::hasColumn('inventory_transfers', 'delivery_order_uploaded_at')) {
                $table->timestamp('delivery_order_uploaded_at')->nullable()->after('delivery_order_uploaded_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventory_transfers', function (Blueprint $table) {
            foreach (['delivery_order_uploaded_at', 'delivery_order_uploaded_by'] as $column) {
                if (Schema::hasColumn('inventory_transfers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
