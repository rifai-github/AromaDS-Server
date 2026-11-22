<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MOM 19 Nov 2026 p4: keterangan Basis SQ (rental / ruangan) tampil di Contract Information dan
 * boleh diubah walau kontrak sudah aktif. Diisi awal dari quotation sumbernya.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('contracts') || Schema::hasColumn('contracts', 'price_basis')) {
            return;
        }

        Schema::table('contracts', function (Blueprint $table) {
            $table->string('price_basis', 20)->nullable()->after('po_number');
        });

        if (Schema::hasTable('quotations') && Schema::hasColumn('quotations', 'price_basis')) {
            DB::table('contracts')
                ->whereNull('price_basis')
                ->whereNotNull('quotation_id')
                ->update([
                    'price_basis' => DB::raw("(SELECT q.price_basis FROM quotations q WHERE q.id = contracts.quotation_id AND q.price_basis IN ('room', 'rental'))"),
                ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('contracts') && Schema::hasColumn('contracts', 'price_basis')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->dropColumn('price_basis');
            });
        }
    }
};
