<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Tanda Terima {{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 34px 44px 28px 44px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 14px; color: #111; }

        .receipt { width: 100%; }
        .receipt + .receipt { margin-top: 110px; }

        .rec-head { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .rec-head td { vertical-align: top; }
        .logo { width: 84px; }
        .rec-company { font-size: 16px; font-weight: bold; padding-top: 14px; }
        .rec-title { font-size: 17px; font-weight: bold; text-align: center; }
        .rec-number { font-size: 16px; font-weight: bold; text-align: center; margin-top: 4px; }

        .rec-body { width: 100%; border-collapse: collapse; }
        .rec-body td { vertical-align: top; font-size: 14px; line-height: 1.5; }
        .rb-label { width: 140px; white-space: nowrap; }
        .rb-sep { width: 10px; }
        .rb-right { text-align: right; white-space: nowrap; }

        .row-gap td { height: 30px; }
        .row-gap-sm td { height: 16px; }

        .sign-row td { vertical-align: top; font-size: 14px; }
        .sign-col { width: 46%; }
        .sign-spacer { width: 8%; }
        .sign-line { border-top: 1px solid #222; margin-top: 60px; }
        .sign-name { padding-top: 4px; }
        .sign-caption { padding-top: 4px; }

        .rec-footer { margin-top: 24px; font-size: 12px; line-height: 1.55; }
        .rec-footer .addr-line { }
        .rec-footer .branch-line { }
    </style>
</head>
<body>
    @php
        $company = \App\Models\Company::first();

        $companyName = strtoupper(trim((string) ($company->name ?? 'PT. PINK SERVICES INDONESIA')));
        $customerName = strtoupper(trim((string) ($invoice->customer->name ?? '-')));

        // Derive the Tanda Terima number from the invoice number: swap the INV type code for TT.
        // Legacy keeps a separate TT counter we don't have, so we derive deterministically.
        $ttNumber = preg_replace('#/INV/#', '/TT/', '/' . $invoice->invoice_number . '/');
        $ttNumber = trim($ttNumber, '/');
        if ($ttNumber === $invoice->invoice_number) {
            // Fallback when the pattern differs: replace a bare "-INV/" or "INV" token.
            $ttNumber = preg_replace('/-INV\b/i', '-TT', $invoice->invoice_number);
            $ttNumber = preg_replace('/\bINV\b/i', 'TT', $ttNumber);
        }

        // Short job reference (e.g. "25-11/0247") shown on the right of "Ditujukan kepada".
        $jobRefFull = $invoice->invoiceRentalDetails->pluck('job_no')->filter()->first()
            ?: ($invoice->contract_number ?: '');
        $jobRefShort = '';
        if ($jobRefFull && preg_match('#([0-9]{2}-[0-9]{2}/[0-9]+)#', $jobRefFull, $m)) {
            $jobRefShort = $m[1];
        }

        // Indonesian long-form invoice date: "02 Desember 2025".
        $idMonths = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $invDateId = '-';
        if ($invoice->invoice_date) {
            $d = $invoice->invoice_date;
            $invDateId = sprintf('%02d %s %d', (int) $d->format('d'), $idMonths[(int) $d->format('n')], (int) $d->format('Y'));
        }

        $amountIdr = 'IDR ' . number_format($invoice->grand_total, 0, ',', ',');
        $dikirimOleh = trim((string) ($invoice->dikirim_oleh ?? ''));

        // Footer: prefer the company address, then append the legacy Tel/Fax + branch-phone lines
        // (static branding text not stored per-record — defaults match the official template).
        $footerAddrLine = trim((string) ($company->address ?? ''));
        if ($footerAddrLine === '') {
            $footerAddrLine = 'Komplek Kedoya Center Blok C 8- 9 Jl. Raya Pejuangan No. 1 Kebon Jeruk, Jakarta Barat 11530. INDONESIA Tel.62 21 533 3405, 549 |49|, Fax 62 21 533 3406 - 07';
        } else {
            $footerAddrLine .= ' Tel.62 21 533 3405, 549 |49|, Fax 62 21 533 3406 - 07';
        }
        $footerBranchLine = 'Bali : 62 361 722 745  Batam : 62 778 461 601  Bandung : 62 22 730 4176  Surabaya : 62 31 849 6409-10  Semarang : 62 24 7617 314  Medan : 62 61 4146 494';

        $logoPath = public_path('images/logo.png');
        $logoSrc = $logoPath;
        if (file_exists($logoPath)) {
            $logoType = pathinfo($logoPath, PATHINFO_EXTENSION);
            $logoSrc = 'data:image/' . $logoType . ';base64,' . base64_encode(file_get_contents($logoPath));
        }
    @endphp

    @for($copy = 0; $copy < 2; $copy++)
    <div class="receipt">
        <table class="rec-head">
            <tr>
                <td style="width: 12%;">
                    <img src="{{ $logoSrc }}" class="logo" alt="Logo">
                </td>
                <td style="width: 30%;">
                    <div class="rec-company">{{ $companyName }}</div>
                </td>
                <td style="width: 58%;">
                    <div class="rec-title">TANDA TERIMA</div>
                    <div class="rec-number">No. {{ $ttNumber }}</div>
                </td>
            </tr>
        </table>

        <table class="rec-body">
            <tr>
                <td class="rb-label">Ditujukan kepada</td>
                <td class="rb-sep">:</td>
                <td>{{ $customerName }}</td>
                <td class="rb-right">{{ $jobRefShort !== '' ? $jobRefShort . ';' : '' }}</td>
            </tr>
            <tr class="row-gap"><td colspan="4"></td></tr>
            <tr>
                <td class="rb-label">Telah diterima</td>
                <td class="rb-sep">:</td>
                <td colspan="2">
                    Invoice bertanggal {{ $invDateId }} dengan No {{ $invoice->invoice_number }}<br>
                    sebesar {{ $amountIdr }}
                </td>
            </tr>
            <tr class="row-gap-sm"><td colspan="4"></td></tr>
            <tr>
                <td class="rb-label">Catatan</td>
                <td class="rb-sep">:</td>
                <td>&nbsp;</td>
                <td class="rb-right" style="font-weight: normal;">Tanggal,</td>
            </tr>
        </table>

        <table class="rec-body" style="margin-top: 4px;">
            <tr class="sign-row">
                <td class="sign-col">Yang menyerahkan,</td>
                <td class="sign-spacer">&nbsp;</td>
                <td class="sign-col" style="text-align: right;">Yang menerima,</td>
            </tr>
            <tr class="sign-row">
                <td class="sign-col">
                    <div class="sign-name" style="text-align: center;">{{ $dikirimOleh }}</div>
                    <div class="sign-line"></div>
                </td>
                <td class="sign-spacer">&nbsp;</td>
                <td class="sign-col">
                    <div class="sign-line"></div>
                    <div class="sign-caption" style="text-align: center;">Tanda tangan, nama jelas, cap</div>
                </td>
            </tr>
        </table>

        <div class="rec-footer">
            <div class="addr-line">{{ $footerAddrLine }}</div>
            <div class="branch-line">{{ $footerBranchLine }}</div>
        </div>
    </div>
    @endfor
</body>
</html>
