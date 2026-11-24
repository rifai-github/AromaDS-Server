<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Spec Halaman Report 24 Nov 2026 poin 2: tipe file "Contract Balik" di Add File kontrak.
 * Kontrak yang sudah ditandatangani semua pihak diunduh, di-e-Materai di situs e-Materai,
 * lalu diunggah kembali di Contract Detail dengan tipe ini. Laporan List Kontrak dan
 * Agreement Sign membaca tipe ini untuk kolom Upload / Confirm / Contract Balik.
 */
return new class extends Migration
{
    private const CODE = 'contract_balik';

    public function up(): void
    {
        $masterOptionId = DB::table('master_options')
            ->where('name', 'Contract File Type')
            ->whereNull('deleted_at')
            ->value('id');

        if (! $masterOptionId) {
            return;
        }

        $exists = DB::table('option_details')
            ->where('master_option_id', $masterOptionId)
            ->where('code', self::CODE)
            ->whereNull('deleted_at')
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('option_details')->insert([
            'master_option_id' => $masterOptionId,
            'option_name' => 'Contract Balik',
            'label' => 'Contract Balik',
            'code' => self::CODE,
            'option_description' => 'Kontrak yang sudah ditandatangani semua pihak dan sudah ber-e-Materai',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        $masterOptionId = DB::table('master_options')->where('name', 'Contract File Type')->value('id');

        if ($masterOptionId) {
            DB::table('option_details')
                ->where('master_option_id', $masterOptionId)
                ->where('code', self::CODE)
                ->delete();
        }
    }
};
