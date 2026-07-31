<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kategori legacy Catalyst/PinkAds (MsProductType.ProductCategory) hanya ikut
 * terbawa sebagai teks bebas di product_types.description, contoh:
 *
 *     SourceCode: REF
 *     ProductCategory: Material
 *
 * Nilai itu dipakai untuk membedakan Material / Rental / Fixed Asset / Other,
 * dan tabel product_categories bukan padanannya (isinya hierarki lain: REFILL,
 * Aroma, Diffuser, ...). Kolom ini mengangkatnya jadi field beneran supaya bisa
 * dipakai di query tanpa parsing description.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_types', function (Blueprint $table) {
            $table->string('source_category', 50)->nullable()->after('product_category_id');
            $table->index('source_category');
        });

        $this->backfillFromDescription();
    }

    public function down(): void
    {
        Schema::table('product_types', function (Blueprint $table) {
            $table->dropIndex(['source_category']);
            $table->dropColumn('source_category');
        });
    }

    private function backfillFromDescription(): void
    {
        $rows = DB::table('product_types')
            ->whereNotNull('description')
            ->where('description', 'like', '%ProductCategory:%')
            ->get(['id', 'description']);

        foreach ($rows as $row) {
            if (! preg_match('/ProductCategory:\s*(.+)/', (string) $row->description, $matches)) {
                continue;
            }

            $category = trim($matches[1]);

            if ($category === '') {
                continue;
            }

            DB::table('product_types')
                ->where('id', $row->id)
                ->update(['source_category' => $category]);
        }
    }
};
