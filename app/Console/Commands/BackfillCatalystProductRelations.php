<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class BackfillCatalystProductRelations extends Command
{
    protected $signature = 'catalyst:backfill-product-relations';

    protected $description = 'Backfill imported Catalyst product categories plus high-confidence brand line and variant metadata';

    private array $categoryLookup = [];
    private array $brandLineLookup = [];
    private array $skuBrandVariantLookup = [];
    private array $nameBrandVariantLookup = [];
    private array $variantBrandVariantLookup = [];

    public function handle(): int
    {
        $this->loadCategoryLookup();
        $this->loadBrandLookups();

        $importedProductIds = DB::table('source_import_maps')
            ->where('source_system', 'catalyst')
            ->where('source_table', 'MsProduct')
            ->where('target_table', 'master_products')
            ->pluck('target_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $typeStats = ['updated' => 0];
        $productStats = ['updated' => 0];
        $brandStats = ['updated' => 0, 'skipped' => 0];
        $dictionaryStats = ['inserted' => 0, 'updated' => 0];

        $types = DB::table('product_types')->get(['id', 'sku_prefix', 'name', 'product_category_id']);
        foreach ($types as $type) {
            $targetCategoryId = $type->product_category_id ?: $this->resolveCategoryId($type->sku_prefix, $type->name, null);
            if ($targetCategoryId && (int) ($type->product_category_id ?? 0) !== $targetCategoryId) {
                DB::table('product_types')->where('id', $type->id)->update([
                    'product_category_id' => $targetCategoryId,
                    'updated_at' => now(),
                ]);
                $typeStats['updated']++;
            }
        }

        $typeCategoryMap = DB::table('product_types')
            ->pluck('product_category_id', 'id')
            ->map(fn ($value) => $value ? (int) $value : null)
            ->all();

        $products = DB::table('master_products')->get([
            'id',
            'product_type_id',
            'product_category_id',
            'name',
            'sku',
            'brand_line',
            'variant_name',
        ]);

        foreach ($products as $product) {
            $targetCategoryId = $product->product_category_id
                ?: ($typeCategoryMap[$product->product_type_id] ?? null)
                ?: $this->resolveCategoryIdFromProductName($product->name, $product->sku);

            if ($targetCategoryId && (int) ($product->product_category_id ?? 0) !== $targetCategoryId) {
                DB::table('master_products')->where('id', $product->id)->update([
                    'product_category_id' => $targetCategoryId,
                    'updated_at' => now(),
                ]);
                $productStats['updated']++;
            }
        }

        if ($importedProductIds !== []) {
            $importedProducts = DB::table('master_products')
                ->whereIn('id', $importedProductIds)
                ->get(['id', 'sku', 'name', 'brand_line', 'variant_name']);

            foreach ($importedProducts as $product) {
                $payload = $this->resolveBrandVariantPayload($product);
                if (!$payload) {
                    $brandStats['skipped']++;
                    continue;
                }

                $needsUpdate = false;
                foreach ($payload as $column => $value) {
                    if (($product->{$column} ?? null) !== $value) {
                        $needsUpdate = true;
                        break;
                    }
                }

                if (!$needsUpdate) {
                    $brandStats['skipped']++;
                    continue;
                }

                DB::table('master_products')->where('id', $product->id)->update(array_merge($payload, [
                    'updated_at' => now(),
                ]));

                $brandStats['updated']++;
            }

            $dictionaryStats = $this->syncBrandVariantDictionary($importedProductIds);
        }

        $this->table(
            ['Metric', 'Value'],
            [
                ['Product Types Updated', $typeStats['updated']],
                ['Master Products Updated', $productStats['updated']],
                ['Imported Products Brand/Variant Updated', $brandStats['updated']],
                ['Imported Products Brand/Variant Skipped', $brandStats['skipped']],
                ['Brand Variant Dictionary Inserted', $dictionaryStats['inserted']],
                ['Brand Variant Dictionary Updated', $dictionaryStats['updated']],
                ['Remaining Products Without Category', DB::table('master_products')->whereNull('product_category_id')->count()],
                ['Imported Products Without Brand Line', $importedProductIds === [] ? 0 : DB::table('master_products')->whereIn('id', $importedProductIds)->where(function ($query) {
                    $query->whereNull('brand_line')->orWhere('brand_line', '');
                })->count()],
                ['Imported Products Without Variant Name', $importedProductIds === [] ? 0 : DB::table('master_products')->whereIn('id', $importedProductIds)->where(function ($query) {
                    $query->whereNull('variant_name')->orWhere('variant_name', '');
                })->count()],
            ]
        );

        $this->warn('Catatan: brand variant di schema saat ini tetap berbasis string brand_line + variant_name pada master_products, bukan FK langsung.');

        return self::SUCCESS;
    }

    private function loadCategoryLookup(): void
    {
        foreach (DB::table('product_categories')->get(['id', 'name', 'code', 'sku_prefix']) as $row) {
            foreach (array_filter([
                $this->normalizeLookupKey($row->name ?? null),
                $this->normalizeLookupKey($row->code ?? null),
                $this->normalizeLookupKey($row->sku_prefix ?? null),
            ]) as $key) {
                $this->categoryLookup[$key] ??= (int) $row->id;
            }
        }
    }

    private function loadBrandLookups(): void
    {
        $brandLineOptionId = DB::table('master_options')->where('name', 'Brand Lines')->value('id');
        if ($brandLineOptionId) {
            foreach (DB::table('option_details')->where('master_option_id', $brandLineOptionId)->get(['id', 'option_name']) as $row) {
                $this->brandLineLookup[$this->normalizeLookupKey($row->option_name ?? null)] = [
                    'id' => (int) $row->id,
                    'name' => trim((string) $row->option_name),
                ];
            }
        }

        $rows = DB::table('master_products')
            ->whereNotNull('brand_line')
            ->where('brand_line', '!=', '')
            ->whereNotNull('variant_name')
            ->where('variant_name', '!=', '')
            ->get(['sku', 'name', 'brand_line', 'variant_name']);

        foreach ($rows as $row) {
            $pair = [
                'brand_line' => $this->canonicalizeBrandLine($row->brand_line),
                'variant_name' => $this->cleanVariantName($row->variant_name),
            ];

            if (!$pair['brand_line'] || !$pair['variant_name']) {
                continue;
            }

            $this->rememberUniquePair($this->skuBrandVariantLookup, $this->normalizeLookupKey($row->sku ?? null), $pair);
            $this->rememberUniquePair($this->nameBrandVariantLookup, $this->normalizeLookupKey($row->name ?? null), $pair);
            $this->rememberUniquePair($this->variantBrandVariantLookup, $this->normalizeLookupKey($row->variant_name ?? null), $pair);
        }
    }

    private function rememberUniquePair(array &$lookup, ?string $key, array $pair): void
    {
        if (!$key) {
            return;
        }

        if (!array_key_exists($key, $lookup)) {
            $lookup[$key] = $pair;
            return;
        }

        if ($lookup[$key] !== false && $lookup[$key] !== $pair) {
            $lookup[$key] = false;
        }
    }

    private function resolveBrandVariantPayload(object $product): ?array
    {
        $currentBrandLine = $this->canonicalizeBrandLine($product->brand_line ?? null);
        $currentVariantName = $this->cleanVariantName($product->variant_name ?? null);

        if ($currentBrandLine && $currentVariantName) {
            return [
                'brand_line' => $currentBrandLine,
                'variant_name' => $currentVariantName,
            ];
        }

        $candidate = $this->findBrandVariantPairByLookup($product->sku ?? null, $this->skuBrandVariantLookup)
            ?? $this->findBrandVariantPairByLookup($product->name ?? null, $this->nameBrandVariantLookup);

        if (!$candidate) {
            $variantCandidate = $this->extractVariantCandidate($product->name ?? null);

            if ($variantCandidate) {
                $candidate = $this->findBrandVariantPairByLookup($variantCandidate, $this->variantBrandVariantLookup);

                if (!$candidate && $currentBrandLine) {
                    $candidate = [
                        'brand_line' => $currentBrandLine,
                        'variant_name' => $variantCandidate,
                    ];
                }

                if (!$candidate && $this->looksLikeHandSanitizer($product->name ?? null)) {
                    $candidate = [
                        'brand_line' => 'Dispenser',
                        'variant_name' => $variantCandidate,
                    ];
                }
            }
        }

        if (!$candidate) {
            return null;
        }

        $brandLine = $currentBrandLine ?: $candidate['brand_line'];
        $variantName = $currentVariantName ?: $candidate['variant_name'];

        $brandLine = $this->canonicalizeBrandLine($brandLine);
        $variantName = $this->cleanVariantName($variantName);

        if (!$brandLine || !$variantName) {
            return null;
        }

        return [
            'brand_line' => $brandLine,
            'variant_name' => $variantName,
        ];
    }

    private function findBrandVariantPairByLookup(?string $value, array $lookup): ?array
    {
        $key = $this->normalizeLookupKey($value);
        $result = $key ? ($lookup[$key] ?? null) : null;

        return $result === false ? null : $result;
    }

    private function extractVariantCandidate(?string $name): ?string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return null;
        }

        if (preg_match('/\bADS\s*5000S\b/i', $name)) {
            return 'ADS 5000S';
        }

        if (preg_match('/\bADS\s*ATOM\b/i', $name)) {
            return 'ADS Atom';
        }

        if (preg_match('/\bADS\s*F\b/i', $name)) {
            return 'ADS F';
        }

        if (preg_match('/\bADS\s*(\d{3,4})\b/i', $name, $matches)) {
            return 'ADS ' . $matches[1];
        }

        if (preg_match('/HAND\s+SANITIZER\s*(\d{4})/i', $name, $matches) || preg_match('/DISPENSER\s+HAND\s+SANITIZER\s*(\d{4})/i', $name, $matches)) {
            return $matches[1];
        }

        if (Str::startsWith(Str::lower($name), 'fragrance ')) {
            $variant = preg_replace('/^fragrance\s+/i', '', $name);
            $variant = preg_replace('/\s*[\-\(]?\s*\d+(?:\.\d+)?\s*(?:ml|l)\b.*$/i', '', (string) $variant);
            $variant = preg_replace('/\s+svc.*$/i', '', (string) $variant);
            return $this->cleanVariantName($variant);
        }

        return null;
    }

    private function looksLikeHandSanitizer(?string $name): bool
    {
        return Str::contains(Str::upper((string) $name), 'HAND SANITIZER');
    }

    private function cleanVariantName(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $value = preg_replace('/\s+/', ' ', $value);
        return trim((string) $value);
    }

    private function canonicalizeBrandLine(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $normalized = $this->normalizeLookupKey($value);
        if ($normalized && isset($this->brandLineLookup[$normalized])) {
            return $this->brandLineLookup[$normalized]['name'];
        }

        return match (Str::upper($value)) {
            'AHU' => 'AHU',
            'CEILING' => 'Ceiling',
            'DESK' => 'Desk',
            'DISPENSER' => 'Dispenser',
            'FLOOR' => 'Floor',
            'PUMP' => 'Pump',
            'REFILL' => 'Refill',
            'SPRAYER' => 'Sprayer',
            'WALL' => 'Wall',
            default => $value,
        };
    }

    private function syncBrandVariantDictionary(array $importedProductIds): array
    {
        if (!Schema::hasTable('product_brand_variants') || $importedProductIds === []) {
            return ['inserted' => 0, 'updated' => 0];
        }

        $stats = ['inserted' => 0, 'updated' => 0];

        $products = DB::table('master_products')
            ->whereIn('id', $importedProductIds)
            ->whereNotNull('brand_line')
            ->where('brand_line', '!=', '')
            ->whereNotNull('variant_name')
            ->where('variant_name', '!=', '')
            ->select('brand_line', 'variant_name')
            ->distinct()
            ->get();

        foreach ($products as $product) {
            $brandLine = $this->canonicalizeBrandLine($product->brand_line ?? null);
            $variantName = $this->cleanVariantName($product->variant_name ?? null);
            $brandLineDetail = $brandLine ? ($this->brandLineLookup[$this->normalizeLookupKey($brandLine)] ?? null) : null;

            if (!$brandLineDetail || !$variantName) {
                continue;
            }

            $existing = DB::table('product_brand_variants')
                ->where('brand_line_id', $brandLineDetail['id'])
                ->where('name', $variantName)
                ->first();

            if (!$existing) {
                DB::table('product_brand_variants')->insert([
                    'brand_line_id' => $brandLineDetail['id'],
                    'name' => $variantName,
                    'description' => 'Backfilled from Catalyst import.',
                    'is_active' => true,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $stats['inserted']++;
                continue;
            }

            $update = [];
            if (property_exists($existing, 'deleted_at') && $existing->deleted_at !== null) {
                $update['deleted_at'] = null;
            }
            if (property_exists($existing, 'is_active') && !$existing->is_active) {
                $update['is_active'] = true;
            }

            if ($update !== []) {
                $update['updated_by'] = auth()->id();
                $update['updated_at'] = now();
                DB::table('product_brand_variants')->where('id', $existing->id)->update($update);
                $stats['updated']++;
            }
        }

        return $stats;
    }

    private function resolveCategoryId(?string $sourceCode, ?string $typeName, ?string $sourceCategory): ?int
    {
        $sourceCode = Str::upper(trim((string) $sourceCode));
        $typeName = Str::upper(trim((string) $typeName));
        $sourceCategory = Str::upper(trim((string) $sourceCategory));

        $candidates = match (true) {
            in_array($sourceCode, ['REF', 'REFD'], true) || Str::contains($typeName, 'REFILL') => ['aroma refill', 'refill'],
            in_array($sourceCode, ['HSR'], true) || Str::contains($typeName, 'HAND SANITIZER REFILL') => ['hs refill', 'refill'],
            in_array($sourceCode, ['DIS'], true) || Str::contains($typeName, 'DIFFUSER') => ['diffuser', 'ads diffuser', 'ads'],
            in_array($sourceCode, ['DSP'], true) || Str::contains($typeName, 'DISPENSER') => ['dispenser'],
            in_array($sourceCode, ['PART', 'SP'], true) || Str::contains($typeName, 'PART') => ['spare part', 'pump'],
            in_array($sourceCode, ['BTR'], true) || Str::contains($typeName, 'BATTERY') => ['battery set'],
            in_array($sourceCode, ['AF', 'JAF'], true) || Str::contains($typeName, 'FILTER') => ['aroma filter', 'air filter'],
            in_array($sourceCode, ['TRM', 'THM'], true) || Str::contains($typeName, 'THERMAL') => ['ads thermal', 'thermal251219'],
            in_array($sourceCode, ['RNT', 'RNNQR'], true) || $sourceCategory === 'RENTAL' => ['aroma delivery sys svc'],
            in_array($sourceCode, ['FA'], true) || Str::contains($typeName, 'FIXED ASSET') => ['equipment'],
            in_array($sourceCode, ['AK', 'BATK', 'BSS', 'CON', 'PK', 'PNLTY', 'PPP', 'PRL', 'PRM'], true) || $sourceCategory === 'OTHER' => ['uncategorized'],
            default => [],
        };

        foreach ($candidates as $candidate) {
            $key = $this->normalizeLookupKey($candidate);
            if ($key && isset($this->categoryLookup[$key])) {
                return $this->categoryLookup[$key];
            }
        }

        return null;
    }

    private function resolveCategoryIdFromProductName(?string $name, ?string $sku): ?int
    {
        $haystack = Str::upper(trim((string) $name . ' ' . $sku));

        return match (true) {
            Str::contains($haystack, 'FRAGRANCE') => $this->resolveByName('aroma refill'),
            Str::contains($haystack, ['DISPENSER', 'DIFFUSER']) => $this->resolveByName('diffuser'),
            Str::contains($haystack, 'BATTERY') => $this->resolveByName('battery set'),
            Str::contains($haystack, ['PART', 'PUMP', 'SPRAYER', 'FILTER']) => $this->resolveByName('spare part') ?? $this->resolveByName('aroma filter'),
            default => null,
        };
    }

    private function resolveByName(string $name): ?int
    {
        return $this->categoryLookup[$this->normalizeLookupKey($name)] ?? null;
    }

    private function normalizeLookupKey($value): ?string
    {
        $value = Str::ascii(Str::lower(trim((string) $value)));
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value);
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
