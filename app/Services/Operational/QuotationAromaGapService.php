<?php

namespace App\Services\Operational;

use App\Models\JobAssignMaterialIssue;
use App\Models\MasterProduct;
use App\Models\Quotation;
use App\Models\QuotationRoom;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Klien 24 Nov 2026 (implementasi, "SQ aroma kosong"): SQ hasil import Catalyst tidak punya
 * aroma per ruangan, sehingga dropdown aroma di Material Assign terkunci ke brand line produk
 * default BOM. Pilihan klien (opsi 4):
 *
 *  - aroma SQ ruangan itu KOSONG -> dropdown aroma Material Assign dilepas: semua aroma di
 *    kategori produk yang sama (lintas brand line), dan validasi brand line / daftar material
 *    rental detail tidak berlaku untuk baris itu;
 *  - setelah dipilih dan inventory issuing terbit, aroma SQ yang kosong otomatis diisi dari
 *    aroma yang dipilih (fillFromMaterialIssue), jadi berikutnya aturan normal berlaku;
 *  - aroma SQ SUDAH ada -> aturan brand line yang sekarang tetap
 *    ([[material-assign-brand-line-dropdown-scope]]).
 *
 * Bisa dimatikan lewat config material_assign.relax_empty_sq_aroma
 * (env MATERIAL_ASSIGN_RELAX_EMPTY_SQ_AROMA) bila Pak Hengky minta guard penuh kembali.
 */
class QuotationAromaGapService
{
    public function enabled(): bool
    {
        return (bool) config('material_assign.relax_empty_sq_aroma', true);
    }

    /**
     * ID item material issue (di halaman ini) yang ruangan SQ-nya belum punya aroma.
     *
     * @param  iterable<JobAssignMaterialIssue>  $jobAssignMaterialIssues
     * @return array<int, true>
     */
    public function emptyAromaItemIds(iterable $jobAssignMaterialIssues): array
    {
        if (! $this->enabled()) {
            return [];
        }

        $ids = [];
        foreach ($jobAssignMaterialIssues as $jami) {
            $quotation = $this->quotationFor($jami);
            foreach ($jami->materialIssue?->items ?? [] as $item) {
                if ($this->roomAromaIsEmpty($quotation, $item->room_name)) {
                    $ids[(int) $item->id] = true;
                }
            }
        }

        return $ids;
    }

    public function itemHasEmptyQuotationAroma($item): bool
    {
        if (! $this->enabled() || ! $item) {
            return false;
        }

        $jami = JobAssignMaterialIssue::where('material_issue_id', $item->material_issue_id)->first();

        return $jami && $this->roomAromaIsEmpty($this->quotationFor($jami), $item->room_name);
    }

    /** Aroma bebas pilih untuk baris SQ kosong: masih harus kategori produk yang sama & bukan unit. */
    public function isAllowedFreeAromaSwap(?MasterProduct $current, ?MasterProduct $new): bool
    {
        if (! $current || ! $new || $this->isUnit($new)) {
            return false;
        }

        return ! $current->product_category_id || (int) $current->product_category_id === (int) $new->product_category_id;
    }

    /**
     * Isi aroma ruangan SQ yang masih kosong dari aroma yang dipilih di Material Assign.
     * Dipanggil saat inventory issuing dibuat dari material issue.
     */
    public function fillFromMaterialIssue(JobAssignMaterialIssue $jami): int
    {
        $quotation = $this->quotationFor($jami);
        if (! $quotation) {
            return 0;
        }

        $filled = 0;
        foreach ($jami->materialIssue?->items ?? [] as $item) {
            $product = $item->product;
            if (! $this->isAromaProduct($product)) {
                continue;
            }

            foreach ($this->matchingRooms($quotation, $item->room_name) as $room) {
                if (! $this->aromaIsEmpty($room)) {
                    continue;
                }

                $room->update([
                    'aroma_product_id' => $product->id,
                    'aroma_variant' => $this->aromaLabel($product),
                ]);
                $filled++;

                Log::info('SQ aroma kosong diisi dari Material Assign', [
                    'quotation_id' => $quotation->id,
                    'quotation_room_id' => $room->id,
                    'product_id' => $product->id,
                    'material_issue_id' => $item->material_issue_id,
                ]);
            }
        }

        return $filled;
    }

    private function quotationFor(JobAssignMaterialIssue $jami): ?Quotation
    {
        $jobAdvice = $jami->jobAssignSchedule?->jobSchedule?->jobAdvice;
        $quotation = $jobAdvice?->quotation ?? $jobAdvice?->contract?->quotation;
        $quotation?->loadMissing('quotationRooms');

        return $quotation;
    }

    /**
     * Ruangan dianggap tanpa aroma bila: tak ada SQ sama sekali; ruangan SQ yang cocok
     * aromanya kosong; atau ruangan tak ketemu dan SQ itu tidak punya aroma di ruangan mana pun.
     */
    private function roomAromaIsEmpty(?Quotation $quotation, ?string $roomName): bool
    {
        if (! $quotation) {
            return true;
        }

        $rooms = $this->matchingRooms($quotation, $roomName);
        if ($rooms->isNotEmpty()) {
            return $rooms->every(fn (QuotationRoom $room) => $this->aromaIsEmpty($room));
        }

        return $quotation->quotationRooms->every(fn (QuotationRoom $room) => $this->aromaIsEmpty($room));
    }

    /** @return Collection<int, QuotationRoom> */
    private function matchingRooms(Quotation $quotation, ?string $roomName): Collection
    {
        $needle = strtolower(trim((string) $roomName));
        if ($needle === '') {
            return collect();
        }

        return $quotation->quotationRooms
            ->filter(fn (QuotationRoom $room) => strtolower(trim((string) $room->room_name)) === $needle)
            ->values();
    }

    private function aromaIsEmpty(QuotationRoom $room): bool
    {
        return ! $room->aroma_product_id && trim((string) $room->aroma_variant) === '';
    }

    private function isUnit(?MasterProduct $product): bool
    {
        return (bool) ($product?->productCategory?->is_unit ?? false) || (bool) ($product?->productType?->is_unit ?? false);
    }

    private function isAromaProduct(?MasterProduct $product): bool
    {
        if (! $product || $this->isUnit($product)) {
            return false;
        }

        $haystack = strtolower(implode(' ', array_filter([
            $product->name, $product->variant_name, $product->brand_line,
            $product->productCategory?->name, $product->productType?->name,
        ])));

        if (str_contains($haystack, 'sanitizer')) {
            return false;
        }

        return (bool) $product->brand_line
            || str_contains($haystack, 'aroma')
            || str_contains($haystack, 'fragrance');
    }

    /** "Fragrance Lavender 100 ml" + Luxo -> "Lavender Luxo" (format label SQ). */
    private function aromaLabel(MasterProduct $product): string
    {
        $base = trim(preg_replace(['/\b\d+(?:[\.,]\d+)?\s*ml\b/i', '/^\s*(fragrance|aroma)\s+/i', '/\s+/'], ['', '', ' '], (string) $product->name));
        $brand = trim((string) $product->brand_line);

        return trim($base.($brand !== '' && stripos($base, $brand) === false ? ' '.$brand : ''));
    }
}
