<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MasterOption;
use App\Models\OptionDetail;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BrandLinesAndVariantsSeeder extends Seeder
{
    public function run(): void
    {
        // Disable model events temporarily to prevent audit trail issues during seeding
        MasterOption::unsetEventDispatcher();
        OptionDetail::unsetEventDispatcher();

        $user = User::first(); // Get the first user for created_by/updated_by

        if (!$user) {
            $this->command->error('No user found. Please create a user first.');
            return;
        }

        try {
            DB::beginTransaction();

            // 1. Create Brand Lines Master Option
            $brandLines = MasterOption::firstOrCreate(
                ['name' => 'Brand Lines'],
                [
                    'description' => 'Kategori aroma untuk produk berdasarkan test-case2.md',
                    'is_active' => true,
                    'system_reserved' => true,
                    'created_by' => $user->id,
                    'updated_by' => $user->id
                ]
            );

            // Add Brand Lines Options
            $brandLineOptions = [
                [
                    'option_name' => 'Signature',
                    'option_description' => 'Premium aroma category - Signature line',
                    'is_active' => true
                ],
                [
                    'option_name' => 'Artisan',
                    'option_description' => 'Craft aroma category - Artisan line',
                    'is_active' => true
                ],
                [
                    'option_name' => 'Luxo',
                    'option_description' => 'Luxury aroma category - Luxo line',
                    'is_active' => true
                ]
            ];

            foreach ($brandLineOptions as $option) {
                OptionDetail::firstOrCreate(
                    [
                        'master_option_id' => $brandLines->id,
                        'option_name' => $option['option_name']
                    ],
                    array_merge($option, [
                        'master_option_id' => $brandLines->id
                    ])
                );
            }

            // 2. Create Product Variants Master Option
            $productVariants = MasterOption::firstOrCreate(
                ['name' => 'Product Variants'],
                [
                    'description' => 'Nama aroma spesifik berdasarkan test-case2.md',
                    'is_active' => true,
                    'system_reserved' => true,
                    'created_by' => $user->id,
                    'updated_by' => $user->id
                ]
            );

            // Add Product Variants Options
            $variantOptions = [
                // Signature Variants
                [
                    'option_name' => 'Lemon Squash',
                    'option_description' => 'Signature aroma - Lemon Squash',
                    'is_active' => true
                ],
                [
                    'option_name' => 'Citrus Burst',
                    'option_description' => 'Signature aroma - Citrus Burst',
                    'is_active' => true
                ],
                [
                    'option_name' => 'Ocean Breeze',
                    'option_description' => 'Signature aroma - Ocean Breeze',
                    'is_active' => true
                ],
                
                // Artisan Variants
                [
                    'option_name' => 'White Rose',
                    'option_description' => 'Artisan aroma - White Rose',
                    'is_active' => true
                ],
                [
                    'option_name' => 'Lavender Fields',
                    'option_description' => 'Artisan aroma - Lavender Fields',
                    'is_active' => true
                ],
                [
                    'option_name' => 'Vanilla Dreams',
                    'option_description' => 'Artisan aroma - Vanilla Dreams',
                    'is_active' => true
                ],
                
                // Luxo Variants
                [
                    'option_name' => 'Ginger Blossom',
                    'option_description' => 'Luxo aroma - Ginger Blossom',
                    'is_active' => true
                ],
                [
                    'option_name' => 'Royal Jasmine',
                    'option_description' => 'Luxo aroma - Royal Jasmine',
                    'is_active' => true
                ],
                [
                    'option_name' => 'Sandalwood Essence',
                    'option_description' => 'Luxo aroma - Sandalwood Essence',
                    'is_active' => true
                ]
            ];

            foreach ($variantOptions as $option) {
                OptionDetail::firstOrCreate(
                    [
                        'master_option_id' => $productVariants->id,
                        'option_name' => $option['option_name']
                    ],
                    array_merge($option, [
                        'master_option_id' => $productVariants->id
                    ])
                );
            }

            DB::commit();

            $this->command->info('Brand Lines and Product Variants seeded successfully!');
            $this->command->info('Brand Lines: ' . $brandLines->id);
            $this->command->info('Product Variants: ' . $productVariants->id);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Error seeding Brand Lines and Product Variants: ' . $e->getMessage());
        } finally {
            // Re-enable model events
            MasterOption::setEventDispatcher(new \Illuminate\Events\Dispatcher(app()));
            OptionDetail::setEventDispatcher(new \Illuminate\Events\Dispatcher(app()));
        }
    }
}
