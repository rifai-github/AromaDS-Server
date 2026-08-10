<?php

namespace App\Console\Commands;

use App\Models\MasterProduct;
use Illuminate\Console\Command;

/**
 * product_type_id itu nullable di form Master Product, jadi produk yang dibuat
 * tanpa memilih Product Type sengaja tetap muncul di dropdown gudang (lihat
 * MasterProduct::scopeStockableGoods()) - kalau tidak, dia hilang begitu saja
 * tanpa peringatan.
 *
 * Tapi sebagian produk begini ternyata cuma data lama yang dobel dengan
 * produk hasil import Catalyst yang SKU-nya beda tapi namanya identik dan
 * sudah terklasifikasi dengan benar (mis. HSG1000 kosong vs REFHSG1000/HSG1k
 * yang sudah punya Product Type). Command ini menonaktifkan yang lama supaya
 * user tidak salah pilih dari dua entri yang kelihatan sama di dropdown.
 *
 * Sengaja tidak menghapus datanya - riwayat transaksi lama mungkin masih
 * mereferensikan SKU tersebut.
 */
class DeactivateUnclassifiedDuplicateProducts extends Command
{
    protected $signature = 'master-products:deactivate-unclassified-duplicates
                            {--id=* : Batasi ke master_product ID tertentu}
                            {--apply : Terapkan perubahan (default dry-run)}';

    protected $description = 'Nonaktifkan produk aktif tanpa Product Type yang ternyata duplikat nama dari produk lain yang sudah terklasifikasi';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        if (! $apply) {
            $this->info('DRY RUN. Tidak ada perubahan yang ditulis. Tambahkan --apply untuk menyimpan.');
        }

        $ids = array_map('intval', (array) $this->option('id'));

        $candidates = MasterProduct::query()
            ->where('is_active', true)
            ->whereNull('product_type_id')
            ->when($ids, fn ($q) => $q->whereIn('id', $ids))
            ->orderBy('id')
            ->get(['id', 'sku', 'name']);

        if ($candidates->isEmpty()) {
            $this->info('Tidak ada produk aktif tanpa Product Type yang cocok dengan filter.');

            return self::SUCCESS;
        }

        $rows = [];
        $toDeactivate = [];

        foreach ($candidates as $candidate) {
            // Kembaran cuma valid kalau dia sendiri lolos stockableGoods() - kalau
            // "kembaran"-nya justru paket sewa (mis. HSG1k, RNNQR), menonaktifkan
            // si kandidat berarti tidak ada lagi versi barang gudang yang tersisa.
            $twin = MasterProduct::query()
                ->where('is_active', true)
                ->where('id', '!=', $candidate->id)
                ->whereNotNull('product_type_id')
                ->where('name', $candidate->name)
                ->stockableGoods()
                ->first(['id', 'sku']);

            if ($twin) {
                $toDeactivate[] = $candidate->id;
                $rows[] = [$apply ? 'DEACTIVATE' : 'PLAN', $candidate->id, $candidate->sku, $candidate->name, "-> {$twin->sku} (ID {$twin->id})"];
            } else {
                $rows[] = ['SKIP (tanpa kembaran)', $candidate->id, $candidate->sku, $candidate->name, '-'];
            }
        }

        $this->table(['Aksi', 'ID', 'SKU', 'Nama', 'Kembaran terklasifikasi'], $rows);

        if ($apply && $toDeactivate) {
            MasterProduct::whereIn('id', $toDeactivate)->update(['is_active' => false]);
        }

        $this->newLine();
        $this->info(sprintf(
            '%s %d produk. %d dilewati (tidak punya kembaran terklasifikasi, kemungkinan barang asli - butuh diklasifikasi manual, bukan dinonaktifkan).',
            $apply ? 'Dinonaktifkan' : 'Akan dinonaktifkan',
            count($toDeactivate),
            $candidates->count() - count($toDeactivate)
        ));

        if (! $apply) {
            $this->warn('Dry-run selesai. Jalankan ulang dengan --apply untuk menerapkan.');
        }

        return self::SUCCESS;
    }
}
