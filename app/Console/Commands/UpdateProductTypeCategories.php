<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProductType;
use App\Models\ProductCategory;
use App\Models\MasterProduct;
use Illuminate\Support\Facades\DB;

class UpdateProductTypeCategories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product-types:update-categories 
                            {--dry-run : Show what would be updated without making changes}
                            {--force : Force update even if category already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update existing ProductTypes to assign product_category_id based on MasterProduct data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('🔍 Analyzing ProductTypes without category...');
        
        // Get ProductTypes without category
        $productTypesWithoutCategory = ProductType::whereNull('product_category_id')->get();
        
        if ($productTypesWithoutCategory->isEmpty()) {
            $this->info('✅ All ProductTypes already have categories assigned!');
            return 0;
        }

        $this->info("Found {$productTypesWithoutCategory->count()} ProductTypes without category.");
        $this->newLine();

        $stats = [
            'updated' => 0,
            'skipped' => 0,
            'created_default' => 0,
            'errors' => []
        ];

        DB::beginTransaction();

        try {
            foreach ($productTypesWithoutCategory as $productType) {
                $this->line("Processing: {$productType->name} (SKU: {$productType->sku_prefix})");

                // Strategy 1: Find category from MasterProduct that uses this ProductType
                $masterProduct = MasterProduct::where('product_type_id', $productType->id)
                    ->whereNotNull('product_category_id')
                    ->first();

                if ($masterProduct && $masterProduct->product_category_id) {
                    $categoryId = $masterProduct->product_category_id;
                    $category = ProductCategory::find($categoryId);
                    
                    if ($category) {
                        if ($dryRun) {
                            $this->info("  → Would assign: {$category->name} (ID: {$categoryId})");
                        } else {
                            $productType->product_category_id = $categoryId;
                            $productType->save();
                            $this->info("  ✅ Assigned: {$category->name}");
                        }
                        $stats['updated']++;
                        continue;
                    }
                }

                // Strategy 2: Try to find category by name matching
                $categoryName = $this->guessCategoryName($productType);
                $category = ProductCategory::where('name', 'like', "%{$categoryName}%")
                    ->where('is_active', true)
                    ->first();

                if ($category) {
                    if ($dryRun) {
                        $this->info("  → Would assign (by name match): {$category->name} (ID: {$category->id})");
                    } else {
                        $productType->product_category_id = $category->id;
                        $productType->save();
                        $this->info("  ✅ Assigned (by name match): {$category->name}");
                    }
                    $stats['updated']++;
                    continue;
                }

                // Strategy 3: Create default "Uncategorized" category or use first available
                $defaultCategory = ProductCategory::where('name', 'Uncategorized')
                    ->where('is_active', true)
                    ->first();

                if (!$defaultCategory) {
                    // Create default category
                    if ($dryRun) {
                        $this->warn("  → Would create default 'Uncategorized' category");
                    } else {
                        $defaultCategory = ProductCategory::create([
                            'code' => 'UNC',
                            'name' => 'Uncategorized',
                            'description' => 'Default category for ProductTypes without category',
                            'sort_order' => 9999,
                            'is_active' => true,
                            'created_by' => 1,
                            'updated_by' => 1,
                        ]);
                        $this->info("  ✅ Created default category: Uncategorized");
                    }
                    $stats['created_default']++;
                }

                if ($defaultCategory && !$dryRun) {
                    $productType->product_category_id = $defaultCategory->id;
                    $productType->save();
                    $this->info("  ✅ Assigned to default category: {$defaultCategory->name}");
                    $stats['updated']++;
                } else {
                    $this->warn("  ⚠️  No category found. Skipping.");
                    $stats['skipped']++;
                }
            }

            if ($dryRun) {
                DB::rollBack();
                $this->newLine();
                $this->info('🔍 DRY RUN - No changes were made.');
                $this->info("Would update: {$stats['updated']} ProductTypes");
                $this->info("Would create: {$stats['created_default']} default category");
                $this->info("Would skip: {$stats['skipped']} ProductTypes");
            } else {
                DB::commit();
                $this->newLine();
                $this->info('✅ Update completed!');
                $this->info("Updated: {$stats['updated']} ProductTypes");
                $this->info("Created: {$stats['created_default']} default category");
                $this->info("Skipped: {$stats['skipped']} ProductTypes");
            }

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Guess category name from ProductType
     */
    private function guessCategoryName(ProductType $productType)
    {
        // Try to extract category from name or description
        $name = strtolower($productType->name);
        
        // Common patterns
        $patterns = [
            'rental' => 'Rental',
            'material' => 'Material',
            'refill' => 'Refill',
            'cleaner' => 'Cleaner',
            'dispenser' => 'Rental',
            'unit' => 'Rental',
        ];

        foreach ($patterns as $pattern => $category) {
            if (strpos($name, $pattern) !== false) {
                return $category;
            }
        }

        return null;
    }
}
