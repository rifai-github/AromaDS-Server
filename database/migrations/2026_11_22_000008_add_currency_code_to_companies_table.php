<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jawaban klien #12 (UAT 17-19 Nov 2026): mata uang diatur di Master Company, default Rupiah,
 * dan berlaku untuk semua customer.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('companies') || Schema::hasColumn('companies', 'currency_code')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            $table->string('currency_code', 3)->default('IDR');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('companies') && Schema::hasColumn('companies', 'currency_code')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropColumn('currency_code');
            });
        }
    }
};
