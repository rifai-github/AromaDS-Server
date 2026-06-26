<?php

namespace App\Console\Commands;

use App\Models\BrandVariant;
use App\Models\MasterProduct;
use Illuminate\Console\Command;

class MapBrandVariantsToProducts extends Command
{
    protected $signature = 'brand-variants:map-products {--apply : Actually write brand_variant_id for confirmed exact matches}';

    protected $description = 'Report (and optionally apply) exact brand_line+variant_name matches between master_products and product_brand_variants';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $variantsByKey = BrandVariant::with('brandLine')
            ->get()
            ->keyBy(function (BrandVariant $variant) {
                return $this->normalize($variant->brandLine?->option_name).'|'.$this->normalize($variant->name);
            });

        $products = MasterProduct::whereNull('brand_variant_id')
            ->whereNotNull('brand_line')
            ->where('brand_line', '!=', '')
            ->whereNotNull('variant_name')
            ->where('variant_name', '!=', '')
            ->get(['id', 'sku', 'name', 'brand_line', 'variant_name']);

        $matched = [];
        $unmatched = [];

        foreach ($products as $product) {
            $key = $this->normalize($product->brand_line).'|'.$this->normalize($product->variant_name);
            $variant = $variantsByKey->get($key);

            if ($variant) {
                $matched[] = [$product, $variant];
            } else {
                $unmatched[] = $product;
            }
        }

        $this->info("Matched (would link " . count($matched) . " master_products rows):");
        $this->table(
            ['product_id', 'sku', 'brand_line', 'variant_name', 'brand_variant_id'],
            array_map(fn ($pair) => [$pair[0]->id, $pair[0]->sku, $pair[0]->brand_line, $pair[0]->variant_name, $pair[1]->id], $matched)
        );

        $this->warn("Unmatched (" . count($unmatched) . ' rows — need manual mapping):');
        $this->table(
            ['product_id', 'sku', 'brand_line', 'variant_name'],
            array_map(fn ($p) => [$p->id, $p->sku, $p->brand_line, $p->variant_name], $unmatched)
        );

        if (! $apply) {
            $this->line('Dry run only — pass --apply to write brand_variant_id for the matched rows above.');

            return self::SUCCESS;
        }

        foreach ($matched as [$product, $variant]) {
            $product->update(['brand_variant_id' => $variant->id]);
        }

        $this->info('Applied: ' . count($matched) . ' master_products rows updated.');

        return self::SUCCESS;
    }

    private function normalize(?string $value): string
    {
        return strtolower(trim((string) $value));
    }
}
