<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Isi `invoices.is_tax_exported` untuk invoice yang sudah pernah masuk Tax File Export.
 *
 * Sampai 6 Okt 2026 tidak ada kode yang mengisi kolom ini, jadi "Tax Exported?" selalu NO
 * walau invoicenya sudah diekspor ke CoreTax (QA: JKT-INV/26-10/0001 sudah ada di
 * TFE-20261005-0001). CoreTaxExportService sekarang menandainya saat file dibuat; migrasi
 * ini menyusul data lama:
 *  - invoice yang dipilih eksplisit (filter_parameters.invoice_ids) di export yang selesai;
 *  - invoice yang sudah punya nomor faktur CoreTax (pasti pernah diekspor).
 * Export berbasis rentang tanggal sengaja tidak ditebak: status invoice saat itu tidak
 * tercatat, jadi menandai seluruh rentang bisa salah.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('invoices', 'is_tax_exported') || ! Schema::hasTable('tax_file_exports')) {
            return;
        }

        $invoiceIds = DB::table('tax_file_exports')
            ->where('status', 'completed')
            ->whereNull('deleted_at')
            ->pluck('filter_parameters')
            ->flatMap(function ($parameters) {
                $decoded = is_array($parameters) ? $parameters : json_decode((string) $parameters, true);

                return (array) ($decoded['invoice_ids'] ?? []);
            })
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($invoiceIds->isNotEmpty()) {
            DB::table('invoices')->whereIn('id', $invoiceIds->all())->update(['is_tax_exported' => true]);
        }

        if (Schema::hasColumn('invoices', 'coretax_faktur_number')) {
            DB::table('invoices')
                ->whereNotNull('coretax_faktur_number')
                ->where('coretax_faktur_number', '!=', '')
                ->update(['is_tax_exported' => true]);
        }
    }

    public function down(): void
    {
        // Tidak dibalik: flag ini mencerminkan fakta (invoice memang sudah diekspor).
    }
};
