<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MOM 17 Nov 2026 p5: Lock Commission oleh admin per PERIODE. Sebelum di-LOCK komisi masih
 * boleh berubah dan kontrak masih boleh dipindah ke marketing/periode lain; sesudahnya tidak.
 * Lock per Marketing Target yang sudah ada tetap berlaku (dicek bersama).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('achievement_periods')) {
            return;
        }

        Schema::table('achievement_periods', function (Blueprint $table) {
            if (! Schema::hasColumn('achievement_periods', 'is_commission_locked')) {
                $table->boolean('is_commission_locked')->default(false)->after('status');
            }
            if (! Schema::hasColumn('achievement_periods', 'commission_locked_at')) {
                $table->timestamp('commission_locked_at')->nullable()->after('is_commission_locked');
            }
            if (! Schema::hasColumn('achievement_periods', 'commission_locked_by')) {
                $table->unsignedBigInteger('commission_locked_by')->nullable()->after('commission_locked_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('achievement_periods')) {
            return;
        }

        Schema::table('achievement_periods', function (Blueprint $table) {
            foreach (['commission_locked_by', 'commission_locked_at', 'is_commission_locked'] as $column) {
                if (Schema::hasColumn('achievement_periods', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
