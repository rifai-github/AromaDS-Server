<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * QA "1 Rental banyak Qty": a single inventory_issuing_items row can now represent
 * quantity > 1 units (e.g. Diffuser qty 2). Unit products need one distinct serial
 * number per unit within that same row. This pivot table links N serial numbers to
 * one inventory_issuing_item, while inventory_issuing_items.serial_number_id is kept
 * as the first/primary serial number for backward compatibility with existing code
 * that reads $item->serialNumber directly (single-SN products, qty 1 rows, aroma/
 * refill rows which only ever need 1 SN regardless of qty).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inventory_issuing_item_serials')) {
            return;
        }

        Schema::create('inventory_issuing_item_serials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_issuing_item_id')
                ->constrained('inventory_issuing_items')
                ->cascadeOnDelete();
            $table->foreignId('serial_number_id')
                ->constrained('serial_numbers')
                ->cascadeOnDelete();
            $table->unsignedInteger('unit_index')->default(1);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['inventory_issuing_item_id', 'serial_number_id'], 'iiis_item_serial_unique');
            $table->index('serial_number_id');
        });

        // Backfill: every existing single-SN row becomes unit_index 1 in the pivot too,
        // so lookups via the new table are consistent for rows created before this migration.
        DB::table('inventory_issuing_items')
            ->whereNotNull('serial_number_id')
            ->orderBy('id')
            ->chunkById(500, function ($items) {
                $rows = $items->map(fn ($item) => [
                    'inventory_issuing_item_id' => $item->id,
                    'serial_number_id' => $item->serial_number_id,
                    'unit_index' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all();

                if (!empty($rows)) {
                    DB::table('inventory_issuing_item_serials')->insertOrIgnore($rows);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_issuing_item_serials');
    }
};
