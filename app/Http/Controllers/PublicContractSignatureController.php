<?php

namespace App\Http\Controllers;

use App\Services\Marketing\ContractSignatureService;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Halaman publik (tanpa login) untuk tanda tangan digital kontrak. Akses hanya lewat token
 * acak di link yang dibuat admin (ContractSignatureController::store).
 */
class PublicContractSignatureController extends Controller
{
    public function __construct(private ContractSignatureService $signatures)
    {
    }

    public function show(string $token)
    {
        $signatureRequest = $this->signatures->findByToken($token);
        if (! $signatureRequest) {
            abort(404);
        }

        $signatureRequest->load('contract.customer');

        return response()
            ->view('public.contract-signature', [
                'signatureRequest' => $signatureRequest,
                'contract' => $signatureRequest->contract,
                'unavailableReason' => $this->signatures->unavailableReason($signatureRequest),
            ])
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Referrer-Policy', 'no-referrer');
    }

    public function document(string $token)
    {
        $signatureRequest = $this->signatures->findByToken($token);
        if (! $signatureRequest || $signatureRequest->isRevoked()) {
            abort(404);
        }

        $disk = $this->signatures->disk();
        if (! $disk->exists($signatureRequest->document_path)) {
            abort(404);
        }

        return response($disk->get($signatureRequest->document_path), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Kontrak.pdf"',
            'X-Robots-Tag' => 'noindex, nofollow',
            'Referrer-Policy' => 'no-referrer',
        ]);
    }

    public function sign(Request $request, string $token)
    {
        $signatureRequest = $this->signatures->findByToken($token);
        if (! $signatureRequest) {
            abort(404);
        }

        $validated = $request->validate([
            'signature' => 'required|string|max:1500000',
            'signed_name' => 'required|string|max:255',
            'agree' => 'accepted',
        ], [
            'signature.required' => 'Silakan tanda tangan di kotak yang tersedia.',
            'signed_name.required' => 'Nama lengkap wajib diisi.',
            'agree.accepted' => 'Centang pernyataan persetujuan terlebih dahulu.',
        ]);

        try {
            $this->signatures->sign(
                $signatureRequest,
                $validated['signature'],
                $validated['signed_name'],
                $request->ip(),
                $request->userAgent()
            );
        } catch (RuntimeException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Terima kasih, tanda tangan Anda sudah tersimpan.',
        ]);
    }
}
