<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Return Cabang -> Pusat flow.
 *
 * Links the branch-side MaterialReturn to a center-bound InventoryTransfer so a
 * technician's returned material can be forwarded to the central warehouse
 * instead of only landing back in the branch warehouse.
 *
 * - material_returns.disposition        : decision captured at approve time.
 * - material_returns.inventory_transfer_id : the auto-created transfer (if forwarded).
 * - inventory_transfers.source_type/id  : back-reference so the center admin can
 *                                         trace where an auto-created transfer came from.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_returns', function (Blueprint $table) {
            if (! Schema::hasColumn('material_returns', 'disposition')) {
                $table->enum('disposition', ['keep_branch', 'forward_to_center'])
                    ->default('keep_branch')
                    ->after('status')
                    ->comment('keep_branch = stok tetap di cabang; forward_to_center = teruskan ke gudang pusat');
            }

            if (! Schema::hasColumn('material_returns', 'inventory_transfer_id')) {
                $table->unsignedBigInteger('inventory_transfer_id')
                    ->nullable()
                    ->after('returned_at');
                $table->index('inventory_transfer_id', 'material_returns_inventory_transfer_id_index');
            }
        });

        Schema::table('inventory_transfers', function (Blueprint $table) {
            if (! Schema::hasColumn('inventory_transfers', 'source_type')) {
                $table->string('source_type', 64)
                    ->nullable()
                    ->after('notes')
                    ->comment('e.g. material_return when auto-created from a branch return');
            }

            if (! Schema::hasColumn('inventory_transfers', 'source_id')) {
                $table->unsignedBigInteger('source_id')
                    ->nullable()
                    ->after('source_type');
                $table->index(['source_type', 'source_id'], 'inventory_transfers_source_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('material_returns', function (Blueprint $table) {
            if (Schema::hasColumn('material_returns', 'inventory_transfer_id')) {
                $table->dropIndex('material_returns_inventory_transfer_id_index');
                $table->dropColumn('inventory_transfer_id');
            }
            if (Schema::hasColumn('material_returns', 'disposition')) {
                $table->dropColumn('disposition');
            }
        });

        Schema::table('inventory_transfers', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_transfers', 'source_id')) {
                $table->dropIndex('inventory_transfers_source_index');
                $table->dropColumn('source_id');
            }
            if (Schema::hasColumn('inventory_transfers', 'source_type')) {
                $table->dropColumn('source_type');
            }
        });
    }
};
