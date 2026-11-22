<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MOM 18 Nov 2026 p4: wewenang potongan harga (Master Price Slab / level approval quotation)
 * ditentukan per USER yang ditunjuk, bukan role/jabatan, karena sesama manager belum tentu
 * berwenang. Tanpa user ditunjuk -> toleransi 0% (tidak ada yang boleh approve di bawah bottom).
 *
 * Sengaja dibuat kosong: penunjukan role lama TIDAK dikonversi ke user, karena justru itu
 * yang diminta dihentikan. Admin menunjuk user lewat Marketing > Master Price Slab.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('quotation_approval_level_users')) {
            return;
        }

        Schema::create('quotation_approval_level_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quotation_approval_level_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['quotation_approval_level_id', 'user_id'], 'qal_users_level_user_unique');
            $table->index('user_id', 'qal_users_user_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_approval_level_users');
    }
};
