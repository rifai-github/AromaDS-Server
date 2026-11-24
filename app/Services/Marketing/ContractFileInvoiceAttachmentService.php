<?php

namespace App\Services\Marketing;

use App\Models\ContractFile;
use App\Models\InvoiceFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Spec Report 24 Nov 2026 poin 1: file kontrak verified hanya menjadi pelengkap invoice bila
 * user memilih "Ya" (contract_files.is_invoice_attachment). Kelas ini menyalin / mencabut
 * salinan file itu di lampiran invoice kontrak yang sudah ada. Invoice baru mengambilnya lewat
 * InvoiceGenerationService::attachVerifiedContractFiles.
 */
class ContractFileInvoiceAttachmentService
{
    /** Penanda deskripsi salinan otomatis, dipakai juga untuk mencabutnya kembali. */
    private const DESCRIPTION_PREFIX = 'File Kontrak #';

    public function setAttachment(ContractFile $file, bool $attach): void
    {
        DB::transaction(function () use ($file, $attach) {
            $file->update(['is_invoice_attachment' => $attach]);

            if ($attach && $file->verification_status === 'verified') {
                $this->attachToExistingInvoices($file);
            } else {
                $this->detachFromInvoices($file);
            }
        });
    }

    public function attachToExistingInvoices(ContractFile $file): int
    {
        $contract = $file->contract;
        if (! $contract || $file->verification_status !== 'verified' || ! $file->is_invoice_attachment) {
            return 0;
        }

        $path = $this->invoicePath($file);
        $attached = 0;

        foreach ($this->invoiceIds($file) as $invoiceId) {
            $exists = InvoiceFile::where('invoice_id', $invoiceId)->where('file_path', $path)->exists();
            if ($exists) {
                continue;
            }

            // Hanya kolom yang ada di tabel invoice_files (file_size/mime_type/uploaded_* tidak ada).
            DB::table('invoice_files')->insert([
                'invoice_id' => $invoiceId,
                'file_type' => 'attachment',
                'file_name' => 'Contract File - '.$file->file_name,
                'file_path' => $path,
                'description' => self::DESCRIPTION_PREFIX."{$contract->contract_number}: {$file->file_type}",
                'created_by' => auth()->id() ?? $file->uploaded_by,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $attached++;
        }

        if ($attached > 0) {
            Log::info("Contract File #{$file->id} attached to {$attached} invoice(s)");
        }

        return $attached;
    }

    public function detachFromInvoices(ContractFile $file): int
    {
        $invoiceIds = $this->invoiceIds($file);
        if ($invoiceIds === []) {
            return 0;
        }

        return DB::table('invoice_files')
            ->whereIn('invoice_id', $invoiceIds)
            ->where('file_path', $this->invoicePath($file))
            ->where('description', 'like', self::DESCRIPTION_PREFIX.'%')
            ->delete();
    }

    /** Lampiran invoice menyimpan path tanpa awalan "uploads/" (lihat attachVerifiedContractFiles). */
    private function invoicePath(ContractFile $file): string
    {
        return str_replace('uploads/', '', (string) $file->file_path);
    }

    /** @return array<int, int> */
    private function invoiceIds(ContractFile $file): array
    {
        $contract = $file->contract;
        if (! $contract) {
            return [];
        }

        return DB::table('invoices')
            ->whereNull('deleted_at')
            ->where(function ($query) use ($contract) {
                $query->where('contract_id', $contract->id)
                    ->orWhere('contract_number', $contract->contract_number);
            })
            ->where(function ($query) {
                $query->whereNull('invoice_status')->orWhere('invoice_status', '!=', 'cancelled');
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
