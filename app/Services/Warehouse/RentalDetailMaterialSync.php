<?php

namespace App\Services\Warehouse;

use App\Models\MasterProduct;
use App\Models\RentalDetail;
use Illuminate\Support\Collection;

/**
 * Material list (rental_detail_materials) untuk baris BOM rental yang auto_expand: semua produk
 * aktif di kategori (dan tipe) baris itu.
 *
 * MOM 18 Nov 2026 p7: data rental hasil seed/import menyimpan auto_expand=1 + kategori, tetapi
 * material list-nya kosong, sehingga Master Rental menampilkan "belum ada material list yang
 * terhubung" sampai modal Material List dibuka & disimpan satu per satu. Logika yang dulu hanya
 * ada di controller dipindah ke sini supaya halaman detail dan command perbaikan memakai aturan
 * yang sama.
 */
class RentalDetailMaterialSync
{
    public function productsForScope(int $productCategoryId, ?int $productTypeId = null): Collection
    {
        return MasterProduct::with(['packagingSize', 'productCategory', 'productType'])
            ->where('is_active', true)
            ->where('product_category_id', $productCategoryId)
            ->when($productTypeId, fn ($query) => $query->where(function ($sub) use ($productTypeId) {
                $sub->where('product_type_id', $productTypeId)->orWhereNull('product_type_id');
            }))
            ->orderBy('name')
            ->get();
    }

    /** Baris auto_expand berkategori yang material list-nya masih kosong. */
    /**
     * QA 23 Nov (MOM 18 Nov p7): Material Assign membaca material list rental detail secara
     * langsung; baris seed/import yang belum pernah dibuka di Master Rental masih kosong sehingga
     * MA Issue gagal sampai detail itu di-Simpan ulang. Hubungkan dulu semua baris auto_expand
     * kosong milik rental-rental job advice ini.
     *
     * @return int jumlah rental detail yang baru dihubungkan
     */
    public function ensureLinkedForJobAdvice($jobAdviceId): int
    {
        if (! $jobAdviceId) {
            return 0;
        }

        $rentalIds = \App\Models\JobAdviceRoom::query()
            ->where('job_advice_id', $jobAdviceId)
            ->whereNotNull('rental_product_id')
            ->pluck('rental_product_id')
            ->unique()
            ->values()
            ->all();

        return $this->ensureLinkedForRentals($rentalIds);
    }

    public function ensureLinkedForRentals(array $masterRentalIds): int
    {
        if (empty($masterRentalIds)) {
            return 0;
        }

        $details = RentalDetail::query()
            ->whereIn('master_rental_id', $masterRentalIds)
            ->where('auto_expand', true)
            ->whereNotNull('product_category_id')
            ->whereDoesntHave('allowedProducts')
            ->get();

        $linked = 0;
        foreach ($details as $detail) {
            try {
                if ($this->syncAutoExpanded($detail) > 0) {
                    $linked++;
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Auto-link material rental detail #{$detail->id} gagal: ".$e->getMessage());
            }
        }

        return $linked;
    }

    public function needsSync(RentalDetail $detail): bool
    {
        if (! $detail->auto_expand || ! $detail->product_category_id) {
            return false;
        }

        return $detail->relationLoaded('allowedProducts')
            ? $detail->allowedProducts->isEmpty()
            : ! $detail->allowedProducts()->exists();
    }

    /**
     * Tambahkan produk kategori baris ini ke material list (produk lain yang sengaja ditambahkan
     * lewat Material List tetap dipertahankan) dan pastikan produk utama salah satunya.
     *
     * @return int jumlah produk di material list setelah sync
     */
    public function syncAutoExpanded(RentalDetail $detail): int
    {
        if (! $detail->product_category_id) {
            return 0;
        }

        $products = $this->productsForScope(
            (int) $detail->product_category_id,
            $detail->product_type_id ? (int) $detail->product_type_id : null
        );

        // Auto-expand hanya MENAMBAH produk kategori baris ini. Dulu sync() mengganti seluruh
        // daftar, jadi produk kategori lain yang sengaja ditambahkan lewat Material List (mis.
        // HEPA di baris FRAGRANCE) hilang lagi setiap kali detail dibuka (UAT 17 Nov).
        $productIds = $products->pluck('id')->map(fn ($id) => (int) $id)
            ->merge($detail->allowedProducts()->pluck('master_products.id')->map(fn ($id) => (int) $id))
            ->unique()
            ->values();

        $syncData = [];
        foreach ($productIds as $index => $productId) {
            $syncData[$productId] = [
                'is_selected' => true,
                'sort_order' => $index,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $detail->allowedProducts()->sync($syncData);

        // Produk utama harus salah satu produk yang diizinkan; dulu tetap menunjuk produk
        // kategori lama setelah kategori baris diganti (FRAGRANCE -> HEPA).
        if ($productIds->isNotEmpty() && ! $productIds->contains((int) $detail->master_product_id)) {
            $detail->update([
                'master_product_id' => $productIds->first(),
                'item_type' => 'product',
                'item_id' => $productIds->first(),
            ]);
        }

        return $productIds->count();
    }
}
