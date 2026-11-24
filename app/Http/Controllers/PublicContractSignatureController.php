<?php

namespace App\Http\Controllers;

use App\Models\ContractSignatureRequest;
use App\Services\Marketing\ContractSignatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

/**
 * Halaman tanda tangan digital kontrak. Akses lewat token acak di link yang dibuat admin
 * (ContractSignatureController::store).
 *
 * 24 Nov 2026: link Pihak Customer tetap publik (tanpa login). Link Pihak Pink/PSI wajib login,
 * dan hanya user yang dipilih sebagai TTD Internal di kontrak (signer_user_id) yang boleh memakainya.
 */
class PublicContractSignatureController extends Controller
{
    /** Session key: link tanda tangan yang dibuka sebelum login, dipakai AuthController sesudah login. */
    public const INTENDED_SESSION_KEY = 'contract_signature.intended';

    public function __construct(private ContractSignatureService $signatures)
    {
    }

    public function show(Request $request, string $token)
    {
        $signatureRequest = $this->signatures->findByToken($token);
        if (! $signatureRequest) {
            abort(404);
        }

        if ($this->needsLogin($signatureRequest)) {
            $request->session()->put(self::INTENDED_SESSION_KEY, $request->fullUrl());

            return redirect()->route('login')
                ->with('info', 'Link tanda tangan Pihak Pink wajib login. Silakan login untuk melanjutkan.');
        }

        $signatureRequest->load('contract.customer');
        $accessDenied = $this->wrongUserMessage($signatureRequest);

        return response()
            ->view('public.contract-signature', [
                'signatureRequest' => $signatureRequest,
                'contract' => $signatureRequest->contract,
                'unavailableReason' => $accessDenied ?? $this->signatures->unavailableReason($signatureRequest),
                'accessDenied' => $accessDenied !== null,
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
        if ($this->needsLogin($signatureRequest) || $this->wrongUserMessage($signatureRequest) !== null) {
            abort(403);
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
        if ($this->needsLogin($signatureRequest)) {
            return response()->json(['status' => 'error', 'message' => 'Sesi login berakhir. Silakan login ulang lalu buka kembali link ini.'], 401);
        }
        if (($message = $this->wrongUserMessage($signatureRequest)) !== null) {
            return response()->json(['status' => 'error', 'message' => $message], 403);
        }

        $validated = $request->validate([
            'signature' => 'required|string|max:1500000',
            'signed_name' => 'required|string|max:255',
            'signature_method' => 'nullable|in:draw,upload',
            'agree' => 'accepted',
        ], [
            'signature.required' => 'Silakan tanda tangan di kotak yang tersedia atau unggah gambar tanda tangan.',
            'signed_name.required' => 'Nama lengkap wajib diisi.',
            'agree.accepted' => 'Centang pernyataan persetujuan terlebih dahulu.',
        ]);

        try {
            $this->signatures->sign(
                $signatureRequest,
                $validated['signature'],
                $validated['signed_name'],
                $request->ip(),
                $request->userAgent(),
                $validated['signature_method'] ?? ContractSignatureService::METHOD_DRAW
            );
        } catch (RuntimeException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Terima kasih, tanda tangan Anda sudah tersimpan.',
        ]);
    }

    private function isInternal(ContractSignatureRequest $signatureRequest): bool
    {
        return $signatureRequest->signer_role === ContractSignatureRequest::ROLE_INTERNAL;
    }

    private function needsLogin(ContractSignatureRequest $signatureRequest): bool
    {
        return $this->isInternal($signatureRequest) && ! Auth::check();
    }

    /** Pesan bila user yang login bukan penandatangan internal yang dipilih di kontrak. */
    private function wrongUserMessage(ContractSignatureRequest $signatureRequest): ?string
    {
        if (! $this->isInternal($signatureRequest) || ! Auth::check() || ! $signatureRequest->signer_user_id) {
            return null;
        }
        if ((int) Auth::id() === (int) $signatureRequest->signer_user_id) {
            return null;
        }

        return 'Link ini khusus untuk '.$signatureRequest->signer_name.'. Anda login sebagai '
            .(Auth::user()->name ?? 'user lain').'. Silakan login dengan akun penandatangan tersebut.';
    }
}
