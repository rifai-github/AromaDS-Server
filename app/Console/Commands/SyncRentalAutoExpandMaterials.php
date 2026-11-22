<?php

namespace App\Console\Commands;

use App\Models\RentalDetail;
use App\Services\Warehouse\RentalDetailMaterialSync;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * MOM 18 Nov 2026 p7: banyak rental hasil seed/import menampilkan "belum ada material list yang
 * terhubung" padahal datanya ada; baru terhubung setelah Material List dibuka & disimpan satu per
 * satu. Command ini menghubungkan SEMUA baris BOM auto_expand berkategori yang material list-nya
 * masih kosong ke produk aktif kategorinya (aturan sama dengan modal Material List).
 *
 * Hanya MENAMBAH material list yang kosong; baris yang sudah punya material tidak disentuh.
 * Default dry-run; tulis ke database hanya dengan --apply.
 */
class SyncRentalAutoExpandMaterials extends Command
{
    protected $signature = 'master-rentals:sync-auto-expand-materials
        {--apply : Simpan perubahan ke database}
        {--rental= : Batasi ke satu master_rental_id}
        {--limit=20 : Jumlah contoh yang ditampilkan}';

    protected $description = 'Hubungkan material list baris BOM rental auto_expand yang masih kosong ke produk kategorinya';

    public function handle(RentalDetailMaterialSync $sync): int
    {
        $apply = (bool) $this->option('apply');

        $details = RentalDetail::query()
            ->with(['masterRental:id,rental_code,rental_name', 'productCategory:id,name', 'productType:id,name'])
            ->where('auto_expand', true)
            ->whereNotNull('product_category_id')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('rental_detail_materials')
                    ->whereColumn('rental_detail_materials.rental_detail_id', 'rental_details.id');
            })
            ->when($this->option('rental'), fn ($query, $rentalId) => $query->where('master_rental_id', $rentalId))
            ->orderBy('master_rental_id')
            ->orderBy('id')
            ->get();

        $this->info('Material list rental auto_expand yang masih kosong - mode '.($apply ? 'apply' : 'dry-run').'.');

        if ($details->isEmpty()) {
            $this->info('Tidak ada baris yang perlu dihubungkan.');

            return self::SUCCESS;
        }

        $rows = [];
        $linked = 0;
        $withoutProducts = 0;
        $productLinks = 0;
        $limit = max(0, (int) $this->option('limit'));

        foreach ($details as $detail) {
            $products = $sync->productsForScope(
                (int) $detail->product_category_id,
                $detail->product_type_id ? (int) $detail->product_type_id : null
            );

            if ($products->isEmpty()) {
                $withoutProducts++;

                continue;
            }

            if ($apply) {
                DB::transaction(fn () => $sync->syncAutoExpanded($detail));
            }

            $linked++;
            $productLinks += $products->count();

            if (count($rows) < $limit) {
                $rows[] = [
                    $detail->masterRental?->rental_code ?? '#'.$detail->master_rental_id,
                    $detail->id,
                    $detail->productCategory?->name ?? '-',
                    $detail->productType?->name ?? '-',
                    $products->count(),
                ];
            }
        }

        if ($rows) {
            $this->table(['Rental', 'Detail ID', 'Kategori', 'Tipe', 'Jumlah produk'], $rows);
        }

        $this->line("Baris kosong: {$details->count()}; bisa dihubungkan: {$linked} ({$productLinks} tautan produk); tanpa produk aktif di kategorinya: {$withoutProducts}.");

        if ($apply) {
            $this->info("Selesai: {$linked} baris dihubungkan.");
        } else {
            $this->warn('Dry-run: belum ada yang disimpan. Jalankan ulang dengan --apply untuk menyimpan.');
        }

        return self::SUCCESS;
    }
}
