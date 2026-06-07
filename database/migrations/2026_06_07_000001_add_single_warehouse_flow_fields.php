<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_returns', function (Blueprint $table) {
            if (! Schema::hasColumn('material_returns', 'return_reason_category')) {
                $table->string('return_reason_category', 64)->nullable()->after('return_reason')->index();
            }
        });

        Schema::table('lost_unit_reports', function (Blueprint $table) {
            if (! Schema::hasColumn('lost_unit_reports', 'bap_file')) {
                $table->string('bap_file')->nullable()->after('remark');
            }

            if (! Schema::hasColumn('lost_unit_reports', 'charge_customer')) {
                $table->boolean('charge_customer')->default(true)->after('bap_file');
            }

            if (! Schema::hasColumn('lost_unit_reports', 'charge_amount')) {
                $table->decimal('charge_amount', 15, 2)->nullable()->after('charge_customer');
            }
        });

        Schema::table('inventory_transfers', function (Blueprint $table) {
            if (! Schema::hasColumn('inventory_transfers', 'is_direct_branch_transfer')) {
                $table->boolean('is_direct_branch_transfer')->default(false)->after('status');
            }

            if (! Schema::hasColumn('inventory_transfers', 'delivery_order_file')) {
                $table->string('delivery_order_file')->nullable()->after('is_direct_branch_transfer');
            }

            if (! Schema::hasColumn('inventory_transfers', 'central_approved_by')) {
                $table->unsignedBigInteger('central_approved_by')->nullable()->after('delivery_order_file');
            }

            if (! Schema::hasColumn('inventory_transfers', 'central_approved_at')) {
                $table->timestamp('central_approved_at')->nullable()->after('central_approved_by');
            }

            if (! Schema::hasColumn('inventory_transfers', 'central_approval_notes')) {
                $table->text('central_approval_notes')->nullable()->after('central_approved_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventory_transfers', function (Blueprint $table) {
            foreach ([
                'central_approval_notes',
                'central_approved_at',
                'central_approved_by',
                'delivery_order_file',
                'is_direct_branch_transfer',
            ] as $column) {
                if (Schema::hasColumn('inventory_transfers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('lost_unit_reports', function (Blueprint $table) {
            foreach (['charge_amount', 'charge_customer', 'bap_file'] as $column) {
                if (Schema::hasColumn('lost_unit_reports', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('material_returns', function (Blueprint $table) {
            if (Schema::hasColumn('material_returns', 'return_reason_category')) {
                $table->dropIndex(['return_reason_category']);
                $table->dropColumn('return_reason_category');
            }
        });
    }
};
