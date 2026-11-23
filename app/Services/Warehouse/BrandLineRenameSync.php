<?php

namespace App\Services\Warehouse;

use App\Models\MasterOption;
use App\Models\OptionDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * UAT 17 Nov: rename Brand Line di Master Option tidak sampai ke Master Product.
 *
 * master_products menyimpan SALINAN TEKS nama brand line (kolom brand_line), bukan FK ke
 * option_details. Akibatnya setelah "Signature" diganti namanya, index/detail produk masih
 * menampilkan nama lama, dropdown edit (yang mencocokkan by name) tidak lagi preselect, dan
 * pencarian LOWER(TRIM(brand_line)) di AromaProductResolver / QuotationWizard / AromaChange /
 * Material Assign tidak lagi menemukan produknya.
 *
 * Satu-satunya salinan teks yang memang harus ikut nama master adalah master_products.brand_line.
 * Label historis (quotation_rooms.aroma_variant, nama brand variant "White Tea Artisan") sengaja
 * tidak disentuh: itu teks dokumen yang sudah terbit.
 */
class BrandLineRenameSync
{
    public const BRAND_LINE_OPTION_NAME = 'Brand Lines';

    /**
     * Jalankan setelah option_name sebuah option detail berubah. Tidak melakukan apa-apa bila
     * detail itu bukan anggota master option "Brand Lines" atau namanya tidak berubah.
     *
     * @return int jumlah baris master_products yang diperbarui
     */
    public function propagate(OptionDetail $detail, ?string $oldName): int
    {
        $newName = (string) $detail->option_name;
        $normalizedOld = mb_strtolower(trim((string) $oldName));

        if ($normalizedOld === '' || $oldName === $newName) {
            return 0;
        }

        $masterName = MasterOption::withTrashed()
            ->whereKey($detail->master_option_id)
            ->value('name');

        if ($masterName !== self::BRAND_LINE_OPTION_NAME) {
            return 0;
        }

        return DB::transaction(function () use ($detail, $oldName, $newName, $normalizedOld) {
            // Cocokkan case-insensitive + trim: data import (Excel/Catalyst) tidak selalu
            // menyimpan kapitalisasi/spasi yang sama dengan option_name.
            $updated = DB::table('master_products')
                ->whereRaw('LOWER(TRIM(brand_line)) = ?', [$normalizedOld])
                ->update([
                    'brand_line' => $newName,
                    'updated_at' => now(),
                ]);

            // Peta grade Aroma Switching (AromaChange::brandLineGradeMap) dikunci per nama.
            \Illuminate\Support\Once::flush();

            Log::info('Brand Line rename disinkronkan ke master_products', [
                'option_detail_id' => $detail->id,
                'old_name' => $oldName,
                'new_name' => $newName,
                'master_products_updated' => $updated,
            ]);

            return $updated;
        });
    }
}
