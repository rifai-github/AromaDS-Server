<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\MasterProduct;
use App\Models\OptionDetail;
use App\Models\BrandVariant;

class BrandVariantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Migrate unique brand_line + variant_name combinations from master_products
     * to the new product_brand_variants table.
     */
    public function run(): void
    {
        $this->command->info('Starting Brand Variant seeding...');

        // Get unique brand_line + variant_name combinations from master_products
        $products = MasterProduct::select('brand_line', 'variant_name')
            ->whereIn('brand_line', ['Signature', 'Luxo', 'Artisan'])
            ->whereNotNull('variant_name')
            ->where('variant_name', '!=', '')
            ->distinct()
            ->get();

        $this->command->info("Found {$products->count()} unique brand line + variant combinations");

        $created = 0;
        $skipped = 0;

        foreach ($products as $product) {
            // Find the brand_line_id from option_details
            // Brand Lines is Master Option ID 42
            $brandLineDetail = OptionDetail::where('master_option_id', 42)
                ->where('option_name', $product->brand_line)
                ->first();

            if (!$brandLineDetail) {
                $this->command->warn("Brand line not found: {$product->brand_line}");
                $skipped++;
                continue;
            }

            // Check if this combination already exists
            $exists = BrandVariant::where('brand_line_id', $brandLineDetail->id)
                ->where('name', $product->variant_name)
                ->exists();

            if ($exists) {
                $this->command->line("Skipping duplicate: {$product->brand_line} - {$product->variant_name}");
                $skipped++;
                continue;
            }

            // Create the brand variant
            BrandVariant::create([
                'brand_line_id' => $brandLineDetail->id,
                'name' => $product->variant_name,
                'description' => 'Imported from Master Products',
                'is_active' => true,
            ]);

            $this->command->info("Created: {$product->brand_line} - {$product->variant_name}");
            $created++;
        }

        $this->command->info("Seeding complete! Created: {$created}, Skipped: {$skipped}");
    }
}
