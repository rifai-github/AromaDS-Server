<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * QA 26 Nov 2026: Billing Group perlu nomor transaksi format ADS-BIL-MM/XXXX. Kode Catalyst
 * (MsBillingGroup.BillingCode) selama ini hanya tersimpan di billing_group_name. Kolom baru
 * (tidak unik: satu kode Catalyst dipakai ulang di banyak kontrak/renewal) diisi dari nama yang
 * sudah berformat itu; billing_group_name tidak diubah karena dipakai Contract Switching.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('billing_groups') || Schema::hasColumn('billing_groups', 'transaction_number')) {
            return;
        }

        Schema::table('billing_groups', function (Blueprint $table) {
            $table->string('transaction_number', 30)->nullable()->after('billing_group_name');
            $table->index('transaction_number');
        });

        DB::table('billing_groups')
            ->where('billing_group_name', 'like', 'ADS-BIL-%')
            ->orderBy('id')
            ->select('id', 'billing_group_name')
            ->chunkById(1000, function ($rows) {
                foreach ($rows as $row) {
                    $name = trim((string) $row->billing_group_name);
                    if (preg_match('/^ADS-BIL-\d{2}\/\d+$/', $name)) {
                        DB::table('billing_groups')->where('id', $row->id)->update(['transaction_number' => $name]);
                    }
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasTable('billing_groups') && Schema::hasColumn('billing_groups', 'transaction_number')) {
            Schema::table('billing_groups', function (Blueprint $table) {
                $table->dropIndex(['transaction_number']);
                $table->dropColumn('transaction_number');
            });
        }
    }
};
