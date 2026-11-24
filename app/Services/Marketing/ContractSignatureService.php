<?php

namespace App\Services\Marketing;

use App\Models\AuditLog;
use App\Models\Contract;
use App\Models\ContractSignatureRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Digital Sign kontrak (MOM 19 Nov 2026 p4 no 8, jawaban klien 23 Nov 2026).
 *
 * Alur: kontrak di-post → admin membuat link → penandatangan Pink (internal signing di kontrak)
 * dan customer (customer signing 1) membuka link publik tanpa login, menggoreskan tanda tangan
 * → setelah keduanya tanda tangan, PDF final berisi tanda tangan + lembar jejak audit siap
 * diunduh Finance untuk e-Materai (manual, di luar sistem).
 *
 * Aturan klien:
 * - Tanda tangan digoreskan di layar, TIDAK tersertifikasi (sama dengan Catalyst).
 * - Penandatangan mengikuti pilihan di kontrak, bukan selalu Pak Furton.
 * - Sudah ditandatangani = final. Karena itu yang ditandatangani adalah SNAPSHOT dokumen
 *   (HTML + PDF dibekukan saat link dibuat, dengan hash SHA-256): perubahan data kontrak
 *   sesudahnya tidak mengubah dokumen yang ditandatangani, link tidak bisa dibuat ulang, dan
 *   kontrak tidak bisa di-unpost.
 *
 * File disimpan di disk `local` (storage/app/private), BUKAN disk `public` (public/uploads
 * bisa dibuka siapa saja yang tahu path-nya).
 */
class ContractSignatureService
{
    public const POSTED_STATUSES = ['active', 'running'];

    private const DISK = 'local';

    private const MAX_SIGNATURE_BYTES = 1048576;

    public const METHOD_DRAW = 'draw';

    public const METHOD_UPLOAD = 'upload';

    /** @return Collection<int, ContractSignatureRequest> rows of the latest non-revoked batch */
    public function activeBatch(Contract $contract): Collection
    {
        $batch = ContractSignatureRequest::query()
            ->where('contract_id', $contract->id)
            ->active()
            ->latest('id')
            ->value('batch');

        if (! $batch) {
            return collect();
        }

        return ContractSignatureRequest::query()
            ->where('batch', $batch)
            ->active()
            ->orderByRaw("CASE WHEN signer_role = 'internal' THEN 0 ELSE 1 END")
            ->get();
    }

    public function hasAnySignature(Contract $contract): bool
    {
        return ContractSignatureRequest::query()
            ->where('contract_id', $contract->id)
            ->active()
            ->whereNotNull('signed_at')
            ->exists();
    }

    public function isPosted(Contract $contract): bool
    {
        return in_array((string) $contract->contract_status, self::POSTED_STATUSES, true);
    }

    /**
     * Penandatangan sesuai yang tercetak di PDF kontrak (pdf.blade.php "Signatories").
     *
     * @return array<string, array{name: string, position: ?string, user_id: ?int, contact_id: ?int}>
     */
    public function signers(Contract $contract): array
    {
        $contract->loadMissing(['internalSigning', 'customerSigning1']);

        $internalName = trim((string) ($contract->signatory_name ?: ($contract->internalSigning->name ?? '')));
        $internalPosition = trim((string) ($contract->signatory_position ?: ($contract->internalSigning->position_name ?? '')));
        $customerName = trim((string) ($contract->customerSigning1->name ?? ''));
        $customerPosition = trim((string) ($contract->customerSigning1->position ?? ''));

        $missing = [];
        if ($internalName === '') {
            $missing[] = 'penandatangan Pihak Pink (Internal Signing)';
        }
        if ($customerName === '') {
            $missing[] = 'penandatangan Pihak Customer (Customer Signing 1)';
        }
        if ($missing !== []) {
            throw new RuntimeException('Kontrak belum punya '.implode(' dan ', $missing).'. Lengkapi dulu di data kontrak.');
        }

        return [
            ContractSignatureRequest::ROLE_INTERNAL => [
                'name' => $internalName,
                'position' => $internalPosition !== '' ? $internalPosition : null,
                'user_id' => $contract->internal_signing_id ? (int) $contract->internal_signing_id : null,
                'contact_id' => null,
            ],
            ContractSignatureRequest::ROLE_CUSTOMER => [
                'name' => $customerName,
                'position' => $customerPosition !== '' ? $customerPosition : null,
                'user_id' => null,
                'contact_id' => $contract->customer_signing_1_id ? (int) $contract->customer_signing_1_id : null,
            ],
        ];
    }

    /**
     * Buat link baru (dan bekukan dokumennya). Batch lama yang belum ditandatangani siapa pun
     * dicabut; batch yang sudah ada tanda tangannya tidak bisa diganti.
     *
     * @return Collection<int, ContractSignatureRequest>
     */
    public function createBatch(Contract $contract, ?int $userId = null): Collection
    {
        if (! $this->isPosted($contract)) {
            throw new RuntimeException('Link tanda tangan hanya bisa dibuat untuk kontrak yang sudah di-post (Active).');
        }

        if ($this->hasAnySignature($contract)) {
            throw new RuntimeException('Kontrak sudah ditandatangani, link tidak bisa dibuat ulang.');
        }

        $signers = $this->signers($contract);
        $html = $this->renderContractHtml($contract);
        $pdf = $this->renderPdf($html);

        $batch = (string) Str::uuid();
        $directory = 'contract-signatures/'.$contract->id.'/'.$batch;
        $htmlPath = $directory.'/document.html';
        $documentPath = $directory.'/document.pdf';

        $disk = $this->disk();
        $disk->put($htmlPath, $html);
        $disk->put($documentPath, $pdf);

        $hash = hash('sha256', $pdf);
        $expiresAt = now()->addDays(max(1, (int) config('contract.signature_link_days', 14)));

        $rows = DB::transaction(function () use ($contract, $signers, $batch, $htmlPath, $documentPath, $hash, $expiresAt, $userId) {
            $revoked = ContractSignatureRequest::query()
                ->where('contract_id', $contract->id)
                ->active()
                ->update(['revoked_at' => now(), 'updated_at' => now()]);

            $rows = collect();
            foreach ($signers as $role => $signer) {
                $rows->push(ContractSignatureRequest::create([
                    'contract_id' => $contract->id,
                    'batch' => $batch,
                    'signer_role' => $role,
                    'signer_name' => $signer['name'],
                    'signer_position' => $signer['position'],
                    'signer_user_id' => $signer['user_id'],
                    'signer_contact_id' => $signer['contact_id'],
                    'token' => Str::random(48),
                    'html_path' => $htmlPath,
                    'document_path' => $documentPath,
                    'document_hash' => $hash,
                    'expires_at' => $expiresAt,
                    'created_by' => $userId,
                ]));
            }

            $this->audit('contract_signature_links_created', $contract, [
                'contract_number' => $contract->contract_number,
                'batch' => $batch,
                'document_hash' => $hash,
                'signers' => collect($signers)->map(fn ($s) => $s['name'])->all(),
                'revoked_previous_links' => $revoked,
                'expires_at' => $expiresAt->toDateTimeString(),
            ], $userId);

            return $rows;
        });

        return $rows;
    }

    public function findByToken(string $token): ?ContractSignatureRequest
    {
        if ($token === '' || strlen($token) > 64) {
            return null;
        }

        return ContractSignatureRequest::query()->where('token', $token)->first();
    }

    /** Alasan link tidak bisa dipakai untuk tanda tangan, atau null bila bisa. */
    public function unavailableReason(ContractSignatureRequest $request): ?string
    {
        if ($request->isRevoked()) {
            return 'Link ini sudah tidak berlaku karena link baru telah dibuat. Mohon minta link terbaru ke PT Pink Services Indonesia.';
        }
        if ($request->isSigned()) {
            return null;
        }
        if ($request->isExpired()) {
            return 'Link ini sudah kedaluwarsa. Mohon minta link baru ke PT Pink Services Indonesia.';
        }

        return null;
    }

    /**
     * Simpan tanda tangan satu penandatangan. Bila semua penandatangan batch sudah tanda tangan,
     * PDF final langsung dibuat.
     */
    public function sign(ContractSignatureRequest $request, string $signatureDataUrl, string $signedName, ?string $ip, ?string $userAgent, string $method = self::METHOD_DRAW): ContractSignatureRequest
    {
        if (! in_array($method, [self::METHOD_DRAW, self::METHOD_UPLOAD], true)) {
            throw new RuntimeException('Cara tanda tangan tidak dikenal.');
        }

        $reason = $this->unavailableReason($request);
        if ($reason !== null) {
            throw new RuntimeException($reason);
        }
        if ($request->isSigned()) {
            throw new RuntimeException('Dokumen ini sudah Anda tandatangani pada '.$request->signed_at->timezone('Asia/Jakarta')->format('d/m/Y H:i').' WIB.');
        }

        $png = $this->decodeSignaturePng($signatureDataUrl);
        $signaturePath = dirname($request->document_path).'/signature-'.$request->signer_role.'.png';

        DB::transaction(function () use ($request, $png, $signaturePath, $signedName, $ip, $userAgent, $method) {
            // Kunci baris supaya dua submit bersamaan tidak sama-sama lolos.
            $fresh = ContractSignatureRequest::query()->whereKey($request->id)->lockForUpdate()->first();
            if (! $fresh || $fresh->isSigned() || $fresh->isRevoked()) {
                throw new RuntimeException('Link ini sudah dipakai atau tidak berlaku lagi.');
            }

            $this->disk()->put($signaturePath, $png);

            $fresh->update([
                'signed_at' => now(),
                'signed_name' => Str::limit(trim($signedName), 250, ''),
                'signature_path' => $signaturePath,
                'signature_method' => $method,
                'signer_ip' => $ip,
                'signer_user_agent' => $userAgent !== null ? Str::limit($userAgent, 1000, '') : null,
            ]);

            $request->setRawAttributes($fresh->getAttributes(), true);
        });

        $this->audit('contract_signed_digitally', $request->contract, [
            'contract_number' => $request->contract?->contract_number,
            'batch' => $request->batch,
            'signer_role' => $request->signer_role,
            'signer_name' => $request->signer_name,
            'signed_name' => $request->signed_name,
            'signature_method' => $method,
            'ip' => $ip,
        ], null);

        $this->finalizeIfComplete($request->batch);

        return $request->refresh();
    }

    /** Buat PDF final bila semua baris batch sudah tanda tangan. Mengembalikan path-nya. */
    public function finalizeIfComplete(string $batch): ?string
    {
        $rows = ContractSignatureRequest::query()->where('batch', $batch)->active()->get();

        if ($rows->isEmpty() || $rows->contains(fn ($row) => ! $row->isSigned())) {
            return null;
        }

        $existing = $rows->pluck('final_document_path')->filter()->first();
        if ($existing) {
            return $existing;
        }

        $first = $rows->first();
        $disk = $this->disk();
        $html = (string) $disk->get($first->html_path);

        foreach ($rows as $row) {
            $png = (string) $disk->get($row->signature_path);
            $image = '<img src="data:image/png;base64,'.base64_encode($png).'" alt="Tanda tangan" '
                .'style="height: 50px; max-width: 70%; display: block;">'
                .'<div class="signature-line" style="margin-top: 6px;"></div>';
            $html = preg_replace(
                '/<!--SIG:'.preg_quote($row->signer_role, '/').'-->.*?<!--\/SIG:'.preg_quote($row->signer_role, '/').'-->/s',
                str_replace(['\\', '$'], ['\\\\', '\\$'], $image),
                $html,
                1
            );
        }

        $html = $this->appendAuditPage($html, $rows);
        $finalPath = dirname($first->document_path).'/signed.pdf';
        $disk->put($finalPath, $this->renderPdf($html));

        ContractSignatureRequest::query()->where('batch', $batch)->update([
            'final_document_path' => $finalPath,
            'updated_at' => now(),
        ]);

        $this->audit('contract_signature_completed', $first->contract, [
            'contract_number' => $first->contract?->contract_number,
            'batch' => $batch,
            'document_hash' => $first->document_hash,
        ], null);

        return $finalPath;
    }

    public function disk(): Filesystem
    {
        return Storage::disk(self::DISK);
    }

    /** HTML PDF kontrak, sama persis dengan tombol Download kontrak. */
    protected function renderContractHtml(Contract $contract): string
    {
        $contract->load([
            'customer',
            'quotation.prospect',
            'quotation.survey',
            'marketing',
            'creator',
            'contractRooms.room.building',
            'contractRentals.masterRental',
            'customerSigning1',
            'internalSigning',
        ]);

        return view('marketing.contracts.pdf', compact('contract'))->render();
    }

    protected function renderPdf(string $html): string
    {
        return Pdf::loadHTML($html)
            ->setPaper('A4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'Arial',
            ])
            ->output();
    }

    private function decodeSignaturePng(string $dataUrl): string
    {
        if (! preg_match('/^data:image\/png;base64,([A-Za-z0-9+\/=\s]+)$/', trim($dataUrl), $matches)) {
            throw new RuntimeException('Format tanda tangan tidak valid. Silakan ulangi tanda tangan.');
        }

        $png = base64_decode(preg_replace('/\s+/', '', $matches[1]), true);
        if ($png === false || ! str_starts_with($png, "\x89PNG\r\n\x1a\n")) {
            throw new RuntimeException('Format tanda tangan tidak valid. Silakan ulangi tanda tangan.');
        }
        if (strlen($png) > self::MAX_SIGNATURE_BYTES) {
            throw new RuntimeException('Gambar tanda tangan terlalu besar.');
        }

        return $png;
    }

    private function appendAuditPage(string $html, Collection $rows): string
    {
        $first = $rows->first();
        $contractNumber = e($first->contract?->contract_number ?? '-');
        $frozenAt = $first->created_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i:s');

        $rowsHtml = '';
        foreach ($rows as $row) {
            $rowsHtml .= '<tr>'
                .'<td>'.e($row->roleLabel()).'</td>'
                .'<td>'.e($row->signer_name).($row->signer_position ? '<br><span style="color:#555;">'.e($row->signer_position).'</span>' : '').'</td>'
                .'<td>'.e($row->signed_name ?: '-').'</td>'
                .'<td>'.e($row->signed_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i:s')).' WIB</td>'
                .'<td>'.($row->signature_method === self::METHOD_UPLOAD ? 'Unggah gambar' : 'Digores di layar').'</td>'
                .'<td>'.e($row->signer_ip ?: '-').'</td>'
                .'</tr>';
        }

        $page = '<div style="page-break-before: always; font-family: Arial, sans-serif; font-size: 10px; color: #222;">'
            .'<div style="font-size: 13px; font-weight: bold; margin-bottom: 10px;">Lembar Pengesahan Tanda Tangan Elektronik</div>'
            .'<div style="margin-bottom: 4px;">No. Kontrak: <b>'.$contractNumber.'</b></div>'
            .'<div style="margin-bottom: 4px;">Dokumen dibekukan: '.e($frozenAt).' WIB</div>'
            .'<div style="margin-bottom: 12px;">SHA-256 dokumen yang ditandatangani: <span style="font-family: monospace;">'.e($first->document_hash).'</span></div>'
            .'<table style="width: 100%; border-collapse: collapse;" border="1" cellpadding="5">'
            .'<tr style="background: #eef2f7; font-weight: bold;"><td>Pihak</td><td>Penandatangan</td><td>Nama yang diketik</td><td>Waktu tanda tangan</td><td>Cara</td><td>Alamat IP</td></tr>'
            .$rowsHtml
            .'</table>'
            .'<div style="margin-top: 12px; line-height: 1.5;">Tanda tangan di atas digoreskan atau diunggah sendiri oleh masing-masing pihak '
            .'melalui tautan tanda tangan kontrak yang dikirim oleh PT Pink Services Indonesia. Isi kontrak dibekukan saat tautan dibuat '
            .'dan tidak berubah sesudahnya.</div>'
            .'</div>';

        $position = strripos($html, '</body>');

        return $position === false
            ? $html.$page
            : substr($html, 0, $position).$page.substr($html, $position);
    }

    private function audit(string $action, ?Contract $contract, array $values, ?int $userId): void
    {
        if (! $contract) {
            return;
        }

        try {
            AuditLog::log($action, $contract, null, $values, $userId);
        } catch (\Throwable $e) {
            Log::warning('Audit log tanda tangan kontrak gagal ditulis: '.$e->getMessage());
        }
    }
}
