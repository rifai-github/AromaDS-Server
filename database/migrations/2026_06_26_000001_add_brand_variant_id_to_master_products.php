<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('master_products', 'brand_variant_id')) {
            Schema::table('master_products', function (Blueprint $table) {
                $table->unsignedBigInteger('brand_variant_id')->nullable()->after('brand_line');
                $table->foreign('brand_variant_id')
                    ->references('id')->on('product_brand_variants')
                    ->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('master_products', 'brand_variant_id')) {
            Schema::table('master_products', function (Blueprint $table) {
                $table->dropForeign(['brand_variant_id']);
                $table->dropColumn('brand_variant_id');
            });
        }
    }
};
