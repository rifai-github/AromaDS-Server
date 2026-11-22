<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jawaban klien #7 (UAT 18 Nov 2026): satu baris refill/non-unit qty 4 boleh diambil dari
 * beberapa batch SN berbeda (mis. 4 batch x 1 botol). Tiap tautan SN batch kini membawa
 * jumlah botolnya sendiri.
 *
 * NULL = "sisa qty baris" (perilaku lama: satu kode batch menutup seluruh qty), jadi data
 * yang sudah ada tidak berubah arti.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('inventory_issuing_item_serials')
            || Schema::hasColumn('inventory_issuing_item_serials', 'quantity')) {
            return;
        }

        Schema::table('inventory_issuing_item_serials', function (Blueprint $table) {
            $table->unsignedInteger('quantity')->nullable()->after('unit_index');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('inventory_issuing_item_serials')
            && Schema::hasColumn('inventory_issuing_item_serials', 'quantity')) {
            Schema::table('inventory_issuing_item_serials', function (Blueprint $table) {
                $table->dropColumn('quantity');
            });
        }
    }
};
