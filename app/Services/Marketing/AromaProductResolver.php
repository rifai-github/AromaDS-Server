<?php

namespace App\Services\Marketing;

use App\Models\BrandVariant;
use App\Models\MasterProduct;

/**
 * Single source of truth for turning an aroma *choice* into a concrete MasterProduct.
 *
 * The quotation wizard lets marketing pick a brand variant ("Lavender Luxo"), which is
 * stored on quotation_rooms as BOTH a FK (aroma_product_id) and a label (aroma_variant).
 * In practice the FK is almost always NULL — no master_products row carries
 * brand_variant_id/variant_name, so the FK lookups find nothing and only the label
 * survives. Every consumer that reads the FK alone (material issue generation) then
 * silently falls back to the rental BOM's default aroma, shipping the wrong scent.
 *
 * This resolver adds the missing link: match the brand variant to a product by NAME,
 * after stripping the brand-line word (Signature/Artisan/Luxo/...) from the variant
 * label — "Lavender Luxo" -> "Lavender" -> "Fragrance Lavender 100 ml".
 */
class AromaProductResolver
{
    /** Per-request memo so a job with many rooms doesn't re-run the same lookup. */
    private array $memo = [];

    /** @var array<int, string>|null Brand-line words, loaded once per process. */
    private static ?array $brandLineWords = null;

    /**
     * Resolve the aroma label stored on quotation_rooms.aroma_variant to a product.
     */
    public function resolveFromVariantText(?string $text): ?MasterProduct
    {
        $text = trim((string) $text);

        if ($text === '') {
            return null;
        }

        $key = 'text:'.mb_strtolower($text);

        if (array_key_exists($key, $this->memo)) {
            return $this->memo[$key];
        }

        // The label is normally a brand variant name, so try that first — it carries the
        // brand line, which tells us which word to strip before matching product names.
        $brandVariant = BrandVariant::active()
            ->with('brandLine')
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($text)])
            ->first();

        if ($brandVariant) {
            return $this->memo[$key] = $this->resolveFromBrandVariant($brandVariant);
        }

        // Free-text label (legacy rows, renewal copies): match on the label itself.
        return $this->memo[$key] = $this->matchByName($this->stripBrandLineWords($text));
    }

    /**
     * Resolve a brand variant to a product: FK first, then the legacy
     * variant_name/brand_line columns, then a name match on the scent.
     */
    public function resolveFromBrandVariant(BrandVariant|int|null $variant): ?MasterProduct
    {
        if (is_int($variant)) {
            $variant = BrandVariant::active()->with('brandLine')->find($variant);
        }

        if (! $variant) {
            return null;
        }

        $key = 'variant:'.$variant->id;

        if (array_key_exists($key, $this->memo)) {
            return $this->memo[$key];
        }

        $byFk = $this->pickBest(
            MasterProduct::query()
                ->with(['productCategory', 'productType', 'packagingSize'])
                ->where('is_active', true)
                ->where('brand_variant_id', $variant->id)
                ->get()
        );

        if ($byFk) {
            return $this->memo[$key] = $byFk;
        }

        $brandLineName = trim((string) $variant->brandLine?->option_name);
        $variantName = trim((string) $variant->name);

        if ($variantName === '') {
            return $this->memo[$key] = null;
        }

        $byLegacyColumns = $this->pickBest(
            MasterProduct::query()
                ->with(['productCategory', 'productType', 'packagingSize'])
                ->where('is_active', true)
                ->whereRaw('LOWER(TRIM(variant_name)) = ?', [mb_strtolower($variantName)])
                ->when($brandLineName !== '', fn ($q) => $q->whereRaw('LOWER(TRIM(brand_line)) = ?', [mb_strtolower($brandLineName)]))
                ->get()
        );

        if ($byLegacyColumns) {
            return $this->memo[$key] = $byLegacyColumns;
        }

        return $this->memo[$key] = $this->matchByName($this->stripBrandLineWords($variantName, $brandLineName));
    }

    /**
     * Whether a product may be offered/substituted as an aroma at all: a non-unit,
     * non-serialised, non-test consumable that reads as a fragrance/refill.
     */
    public function isSelectableAromaProduct(?MasterProduct $product): bool
    {
        if (! $product) {
            return false;
        }

        $name = strtolower(trim((string) $product->name));
        $sku = strtolower(trim((string) $product->sku));
        $variant = strtolower(trim((string) $product->variant_name));
        $categoryName = strtolower($product->productCategory?->name ?? '');
        $typeName = strtolower($product->productType?->name ?? '');

        $isUnit = (bool) ($product->productCategory?->is_unit ?? $product->productType?->is_unit ?? false);
        $hasSerialNumber = (bool) ($product->productCategory?->has_serial_number ?? $product->productType?->has_serial_number ?? false);
        $isTestProduct = str_contains($name, 'test')
            || str_contains($sku, 'test')
            || str_contains($variant, 'test')
            || preg_match('/^ta\d*/i', (string) $product->sku);

        $looksLikeAroma = str_contains($name, 'fragrance')
            || str_contains($name, 'aroma')
            || str_contains($name, 'refill')
            || str_contains($name, 'scent')
            || str_contains($categoryName, 'refill')
            || str_contains($categoryName, 'aroma')
            || str_contains($categoryName, 'fragrance')
            || str_contains($categoryName, 'scent')
            || str_contains($typeName, 'aroma')
            || str_contains($typeName, 'fragrance')
            || str_contains($typeName, 'scent')
            || str_contains($typeName, 'variant')
            || str_contains($typeName, 'refill');

        return ! $isUnit && ! $hasSerialNumber && ! $isTestProduct && $looksLikeAroma;
    }

    /**
     * Brand-line words that qualify a scent rather than name it. Read from the brand
     * lines actually in use (option_details behind product_brand_variants) so a new
     * line added by master data works without a code change.
     */
    private function brandLineWords(): array
    {
        // Deliberately a plain in-process memo rather than the cache store: this runs
        // inside material issue generation, and a cache backend that cannot be written
        // must never be able to fail the job.
        if (self::$brandLineWords !== null) {
            return self::$brandLineWords;
        }

        return self::$brandLineWords = BrandVariant::query()
            ->with('brandLine')
            ->get()
            ->map(fn ($variant) => mb_strtolower(trim((string) $variant->brandLine?->option_name)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * "Lavender Luxo" -> "Lavender", "Signature Lemon Grass" -> "Lemon Grass".
     * Only a leading or trailing brand-line word is removed, so a scent that happens to
     * contain one in the middle is left alone.
     */
    private function stripBrandLineWords(string $variantName, ?string $brandLineName = null): string
    {
        $words = preg_split('/\s+/', trim($variantName)) ?: [];
        $strip = $this->brandLineWords();

        if ($brandLineName !== null && trim($brandLineName) !== '') {
            $strip[] = mb_strtolower(trim($brandLineName));
        }

        $isBrandWord = fn ($word) => in_array(mb_strtolower(trim((string) $word)), $strip, true);

        while (count($words) > 1 && $isBrandWord($words[0])) {
            array_shift($words);
        }

        while (count($words) > 1 && $isBrandWord($words[count($words) - 1])) {
            array_pop($words);
        }

        return trim(implode(' ', $words));
    }

    /**
     * Match a scent phrase against product names. The full phrase wins; a single
     * significant word is only tried as a last resort so "White Tea" cannot silently
     * degrade into every product containing "tea".
     */
    private function matchByName(string $scent): ?MasterProduct
    {
        $scent = trim($scent);

        if (mb_strlen($scent) < 3) {
            return null;
        }

        $match = $this->pickBest($this->candidatesByNameLike($scent));

        if ($match) {
            return $match;
        }

        $words = preg_split('/\s+/', $scent) ?: [];

        if (count($words) < 2) {
            return null;
        }

        foreach ($words as $word) {
            if (mb_strlen($word) < 4) {
                continue;
            }

            $match = $this->pickBest($this->candidatesByNameLike($word));

            if ($match) {
                return $match;
            }
        }

        return null;
    }

    private function candidatesByNameLike(string $needle)
    {
        $escaped = addcslashes($needle, '%_\\');

        return MasterProduct::query()
            ->with(['productCategory', 'productType', 'packagingSize'])
            ->where('is_active', true)
            ->where('name', 'like', '%'.$escaped.'%')
            ->limit(200)
            ->get();
    }

    /**
     * Prefer a refill-category product in the 100 ml size (the standard issue size), then
     * the closest name, then the lowest id so the choice is stable across runs.
     */
    private function pickBest($candidates): ?MasterProduct
    {
        return collect($candidates)
            ->filter(fn ($candidate) => $this->isSelectableAromaProduct($candidate))
            ->sortBy(function ($candidate) {
                $categoryName = strtolower($candidate->productCategory?->name ?? '');
                $packageName = strtolower(str_replace(' ', '', (string) ($candidate->packagingSize?->name ?? '')));
                $name = strtolower((string) $candidate->name);
                $isHundredMl = $packageName === '100ml' || preg_match('/\b100\s*ml\b/i', $name);

                return [
                    str_contains($categoryName, 'refill') ? 0 : 1,
                    $isHundredMl ? 0 : 1,
                    mb_strlen($name),
                    $candidate->id,
                ];
            })
            ->first();
    }
}
