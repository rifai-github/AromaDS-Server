<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ContractSignatureRequest;
use App\Services\Marketing\ContractSignatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Sisi admin Digital Sign kontrak: buat link tanda tangan & unduh dokumen.
 * Halaman publik penandatangan ada di PublicContractSignatureController.
 */
class ContractSignatureController extends Controller
{
    public function __construct(private ContractSignatureService $signatures)
    {
    }

    public function store(Contract $contract)
    {
        try {
            $rows = $this->signatures->createBatch($contract, Auth::id());
        } catch (RuntimeException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('Gagal membuat link tanda tangan kontrak '.$contract->id.': '.$e->getMessage());

            return response()->json(['status' => 'error', 'message' => 'Gagal membuat link tanda tangan: '.$e->getMessage()], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Link tanda tangan dibuat. Salin dan kirim ke masing-masing penandatangan.',
            'data' => $rows->map(fn (ContractSignatureRequest $row) => [
                'role' => $row->signer_role,
                'role_label' => $row->roleLabel(),
                'signer_name' => $row->signer_name,
                'url' => $row->publicUrl(),
                'expires_at' => $row->expires_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i'),
            ])->values(),
        ]);
    }

    /** ?type=signed (default) = PDF bertanda tangan; ?type=original = snapshot yang dikirim. */
    public function download(Request $request, Contract $contract)
    {
        $rows = $this->signatures->activeBatch($contract);
        if ($rows->isEmpty()) {
            abort(404, 'Belum ada link tanda tangan untuk kontrak ini.');
        }

        $type = $request->query('type', 'signed');
        $path = $type === 'original'
            ? $rows->first()->document_path
            : $rows->pluck('final_document_path')->filter()->first();

        $disk = $this->signatures->disk();
        if (! $path || ! $disk->exists($path)) {
            abort(404, $type === 'original'
                ? 'Dokumen tidak ditemukan.'
                : 'Kontrak belum ditandatangani semua pihak.');
        }

        $number = preg_replace('/[^A-Za-z0-9_-]+/', '_', (string) ($contract->contract_number ?: 'Contract')) ?: 'Contract';
        $filename = 'Contract_'.trim($number, '_').($type === 'original' ? '_unsigned' : '_signed').'.pdf';

        return response($disk->get($path), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
