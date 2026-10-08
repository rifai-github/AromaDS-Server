<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Baris Achievement otomatis (dibuat CommissionCalculationService, tipe new/renewal) selalu
 * disimpan "pending" walau pencapaiannya sudah melewati target (QA 8 Okt 2026). Service
 * sekarang menghitung statusnya; migrasi ini menyusul baris lama. Baris manual (tipe
 * sales/service/installation) dan status lain tidak disentuh.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('achievements')) {
            return;
        }

        $automatic = DB::table('achievements')
            ->whereIn('achievement_type', ['new', 'renewal'])
            ->where('status', 'pending')
            ->where('target_amount', '>', 0);

        (clone $automatic)->whereColumn('achieved_amount', '>', 'target_amount')->update(['status' => 'exceeded']);
        (clone $automatic)->whereColumn('achieved_amount', '=', 'target_amount')->update(['status' => 'achieved']);
    }

    public function down(): void
    {
        // Tidak dibalik: status baru mencerminkan angka yang sudah tersimpan.
    }
};
