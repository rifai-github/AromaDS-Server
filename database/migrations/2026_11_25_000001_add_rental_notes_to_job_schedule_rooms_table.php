<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * UAT 24 Nov 2026: APK punya dua catatan saat menyelesaikan room - catatan pemasangan
 * (teknisi) dan catatan penyewaan. Hanya yang pertama tersimpan; catatan penyewaan dibuang.
 * Catatan penyewaan kini disimpan per room per kunjungan di sini.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('job_schedule_rooms') && ! Schema::hasColumn('job_schedule_rooms', 'rental_notes')) {
            Schema::table('job_schedule_rooms', function (Blueprint $table) {
                $table->text('rental_notes')->nullable()->after('completion_notes');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('job_schedule_rooms') && Schema::hasColumn('job_schedule_rooms', 'rental_notes')) {
            Schema::table('job_schedule_rooms', function (Blueprint $table) {
                $table->dropColumn('rental_notes');
            });
        }
    }
};
