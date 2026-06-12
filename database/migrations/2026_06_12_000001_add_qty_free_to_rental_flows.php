<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'quotation_details',
            'quotation_rentals',
            'contract_rentals',
            'job_advice_rooms',
            'invoice_rental_details',
        ] as $tableName) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'qty_free')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->decimal('qty_free', 10, 2)->default(0)->after('quantity');
            });
        }
    }

    public function down(): void
    {
        foreach ([
            'invoice_rental_details',
            'job_advice_rooms',
            'contract_rentals',
            'quotation_rentals',
            'quotation_details',
        ] as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'qty_free')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('qty_free');
            });
        }
    }
};
