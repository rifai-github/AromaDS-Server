<?php

namespace Tests\Feature;

use App\Models\MasterProduct;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RentalDetailAutoExpandMaterialTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('rental_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_category_id')->nullable();
            $table->boolean('auto_expand')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_category_id')->nullable();
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('rental_detail_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_detail_id');
            $table->foreignId('master_product_id');
            $table->boolean('is_selected')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('rental_detail_materials');
        Schema::dropIfExists('master_products');
        Schema::dropIfExists('rental_details');

        parent::tearDown();
    }

    public function test_new_active_product_is_attached_to_auto_expanded_rental_detail_in_same_category(): void
    {
        $rentalDetailId = DB::table('rental_details')->insertGetId([
            'product_category_id' => 151,
            'auto_expand' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $product = MasterProduct::create([
            'name' => 'Fragrance AW Sport Mix 100 ml',
            'product_category_id' => 151,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('rental_detail_materials', [
            'rental_detail_id' => $rentalDetailId,
            'master_product_id' => $product->id,
            'is_selected' => true,
        ]);
    }

    public function test_new_product_is_not_attached_to_manual_rental_detail(): void
    {
        DB::table('rental_details')->insert([
            'product_category_id' => 151,
            'auto_expand' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $product = MasterProduct::create([
            'name' => 'Fragrance Manual Only',
            'product_category_id' => 151,
            'is_active' => true,
        ]);

        $this->assertDatabaseMissing('rental_detail_materials', [
            'master_product_id' => $product->id,
        ]);
    }
}
