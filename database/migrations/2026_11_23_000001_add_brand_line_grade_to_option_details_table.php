<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Grade Aroma Switching (Signature 1 < Artisan 2 < Luxo 3) dulu dicocokkan lewat NAMA brand
 * line. Sejak rename Brand Line didukung (BrandLineRenameSync), mengganti nama "Luxo" membuat
 * grade-nya hilang dan switching ke/dari brand line itu selalu minta approval.
 *
 * Grade kini menempel di baris option_details master option "Brand Lines" (identitas baris
 * tetap walau namanya diganti). Kolom ini tidak ada di form Master Option, jadi tidak ikut
 * tertimpa saat option disimpan.
 */
return new class extends Migration
{
    private const GRADES = [
        'signature' => 1,
        'artisan' => 2,
        'luxo' => 3,
    ];

    public function up(): void
    {
        if (! Schema::hasTable('option_details')) {
            return;
        }

        if (! Schema::hasColumn('option_details', 'brand_line_grade')) {
            Schema::table('option_details', function (Blueprint $table) {
                $table->unsignedTinyInteger('brand_line_grade')->nullable()->after('code');
            });
        }

        if (! Schema::hasTable('master_options')) {
            return;
        }

        $brandLineOptionIds = DB::table('master_options')
            ->where('name', 'Brand Lines')
            ->pluck('id');

        if ($brandLineOptionIds->isEmpty()) {
            return;
        }

        foreach (self::GRADES as $name => $grade) {
            DB::table('option_details')
                ->whereIn('master_option_id', $brandLineOptionIds)
                ->whereNull('brand_line_grade')
                ->whereRaw('LOWER(TRIM(option_name)) = ?', [$name])
                ->update(['brand_line_grade' => $grade]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('option_details') && Schema::hasColumn('option_details', 'brand_line_grade')) {
            Schema::table('option_details', function (Blueprint $table) {
                $table->dropColumn('brand_line_grade');
            });
        }
    }
};
