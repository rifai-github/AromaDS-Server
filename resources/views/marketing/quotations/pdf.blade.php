<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Quotation {{ $quotation->quotation_number }}</title>
    <style>
        @page {
            margin: 30px 36px 30px 36px;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 13px;
            color: #111;
        }

        .page-break {
            page-break-after: always;
        }

        /* ===== Letterhead ===== */
        .letterhead {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 28px;
        }

        .letterhead td {
            vertical-align: top;
        }

        .logo {
            width: 70px;
        }

        .company-name {
            font-size: 12px;
            font-weight: bold;
            text-align: right;
            line-height: 1.25;
        }

        .doc-title {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin: 24px 0 0 40x;
        }

        .header-qr {
            text-align: right;
            margin-top: 6px;
        }

        .header-qr img,
        .header-qr svg {
            width: 50px;
            height: 50px;
        }

        /* ===== Info block ===== */
        .info-wrap {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }

        .info-wrap>tbody>tr>td {
            vertical-align: top;
        }

        .info-left {
            width: 54%;
            padding-right: 22px;
        }

        .info-right {
            width: 46%;
        }

        .info-left .lbl {
            font-size: 10px;
        }

        .info-left .party-name {
            font-size: 10px;
            font-weight: bold;
            margin: 4px 0 6px;
        }

        .info-left .party-addr {
            line-height: 1.1;
            font-size: 10px;
            margin-bottom: 12px;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-table td {
            padding: 2px 0;
            vertical-align: top;
            font-size: 10px;
        }

        .meta-label {
            width: 110px;
        }

        .meta-sep {
            width: 12px;
            font-weight: bold;
        }

        /* ===== Items table ===== */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        .items-table thead th {
            border-top: 2px solid #222;
            border-bottom: 2px solid #222;
            padding: 8px 6px;
            font-size: 10px;
            font-weight: bold;
            text-align: left;
        }

        .items-table td {
            padding: 3px 3px;
            vertical-align: top;
            font-size: 10px;
        }

        .items-table tr.building-row td {
            padding-top: 10px;
            font-weight: bold;
            font-size: 10px;
        }

        /* ===== Totals ===== */
        .totals-wrap {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }

        .totals-wrap td {
            vertical-align: top;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 3px 3px;
            font-size: 10px;
        }

        .totals-table .t-label {
            text-align: right;
            font-weight: bold;
        }

        .totals-table .t-sep {
            width: 12px;
            text-align: center;
            font-weight: bold;
        }

        .totals-table .t-value {
            text-align: right;
            width: 120px;
        }

        .rule-top td {
            border-top: 2px solid #777;
            padding: 0;
            height: 0;
        }

        .rule-bottom td {
            border-bottom: 2px solid #777;
            padding: 0;
            height: 0;
        }

        .terbilang {
            font-size: 10px;
            padding-top: 3px;
        }

        .terbilang .t-key {
            font-weight: bold;
        }

        /* ===== Terms / notes ===== */
        .terms-info {
            margin-top: 32px;
            line-height: 1.4;
            font-size: 10px;
        }

        .terms-info-row {
            margin-bottom: 4px;
        }

        .generated-note {
            margin-top: 26px;
            border-top: 1px solid #aaa;
            padding-top: 10px;
            text-align: center;
            color: #888;
            font-size: 10px;
            font-weight: bold;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        /* ===== Bottom-pin wrapper =====
         * Forces the totals/terms/footer block to the bottom of the page on short
         * quotations (empty spacer row eats the remaining height), while staying purely
         * in normal flow so it never overlaps table rows on multi-page quotations.
         */
        .bottom-spacer {
            line-height: 0;
            font-size: 0;
        }

        /* ===== Footer ===== */
        .page-footer {
            margin-top: 14px;
        }

        .footer-grid {
            width: 100%;
            border-collapse: collapse;
        }

        .footer-grid td {
            vertical-align: bottom;
        }

        .find-us {
            font-size: 8px;
            font-weight: bold;
            line-height: 1;
        }

        .find-us img,
        .find-us svg {
            width: 70px;
            height: 70px;
            margin-top: 3px;
        }

        .footer-center {
            text-align: center;
            font-size: 8px;
            color: #777;
            line-height: 1;
        }

        .footer-center .iso-line {
            font-weight: bold;
            color: #333;
            font-size: 8px;
        }

        .footer-center .branch-line {
            color: #9a9a9a;
        }

        .seal-cell {
            width: 96px;
            text-align: right;
        }

        .seal-img {
            width: 92px;
            height: auto;
        }

        .footer-meta {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
            color: #555;
            font-weight: bold;
            margin-top: 8px;
        }

        .footer-meta td {
            padding-top: 4px;
        }
    </style>
</head>

<body>
    @php
        $company = \App\Models\Company::first();

        /* ===== Company identity for header/footer ===== */
        $companyName = strtoupper(trim((string) ($company->name ?? 'PT. PINK SERVICES INDONESIA')));
        $companyAddress = trim(
            (string) ($company->address ??
                'Komplek Kedoya Center Blok C 8- 9 Jl. Raya Pejuangan No. 1 Kebon Jeruk. Jakarta 11530, Indonesia.'),
        );
        $companyPhone = trim((string) ($company->phone ?? '+62 811-9350-083'));
        $companyEmail = trim((string) ($company->email ?? 'invoice@adsscent.com'));
        $companyWebsite = trim((string) ($company->website ?? 'www.adsscent.com'));
        $headOfficeLine = trim(
            collect([
                $companyAddress ? 'Head Office :  ' . $companyAddress : null,
                $companyPhone ? 'Whatsapp: ' . $companyPhone : null,
            ])
                ->filter()
                ->implode('  '),
        );

        /* ===== Bill To / customer block (prefer prospect, fall back to customer) ===== */
        $billToName = trim(
            (string) ($quotation->prospect->company_name ??
                ($quotation->company_name ?? ($quotation->customer->name ?? '-'))),
        );
        $billToAddress = trim(
            (string) ($quotation->prospect->company_address ?? ($quotation->customer->address ?? '-')),
        );
        $picName = trim((string) ($quotation->prospect->contact_person ?? ($quotation->pic_name ?? '')));
        $picPhone = trim((string) ($quotation->prospect->contact_phone ?? ''));

        /* ===== Periode / Syarat Pembayaran / SC ===== */
        $periodeText = trim((string) ($quotation->rental_period ?? ''));
        if ($periodeText !== '') {
            $periodeText .= ' ' . (ucfirst((string) ($quotation->rental_unit ?? 'Bulan')) ?: 'Bulan');
        } else {
            $periodeText = '-';
        }
        $syaratPembayaranText = $quotation->terms_of_payment_label ?: '-';
        $scName = trim((string) ($quotation->marketing->name ?? '-'));

        /* ===== Terbilang (spell amount in Indonesian) ===== */
        $terbilang = function ($number) use (&$terbilang) {
            $number = (int) abs($number);
            $words = [
                '',
                'satu',
                'dua',
                'tiga',
                'empat',
                'lima',
                'enam',
                'tujuh',
                'delapan',
                'sembilan',
                'sepuluh',
                'sebelas',
            ];

            if ($number < 12) {
                return $words[$number];
            } elseif ($number < 20) {
                return trim($terbilang($number - 10) . ' belas');
            } elseif ($number < 100) {
                return trim($terbilang(intdiv($number, 10)) . ' puluh ' . $terbilang($number % 10));
            } elseif ($number < 200) {
                return trim('seratus ' . $terbilang($number - 100));
            } elseif ($number < 1000) {
                return trim($terbilang(intdiv($number, 100)) . ' ratus ' . $terbilang($number % 100));
            } elseif ($number < 2000) {
                return trim('seribu ' . $terbilang($number - 1000));
            } elseif ($number < 1000000) {
                return trim($terbilang(intdiv($number, 1000)) . ' ribu ' . $terbilang($number % 1000));
            } elseif ($number < 1000000000) {
                return trim($terbilang(intdiv($number, 1000000)) . ' juta ' . $terbilang($number % 1000000));
            } elseif ($number < 1000000000000) {
                return trim($terbilang(intdiv($number, 1000000000)) . ' milyar ' . $terbilang($number % 1000000000));
            }
            return trim($terbilang(intdiv($number, 1000000000000)) . ' triliun ' . $terbilang($number % 1000000000000));
        };
        $terbilangText =
            strtoupper(
                trim(preg_replace('/\s+/', ' ', $terbilang($quotation->grand_total ?? $quotation->total_amount))),
            ) . '  RUPIAH';

        /* ===== Logo (base64) ===== */
        $logoPath = public_path('images/logo.png');
        $logoSrc = asset('images/logo.png');
        if (file_exists($logoPath)) {
            $logoType = pathinfo($logoPath, PATHINFO_EXTENSION);
            $logoSrc = 'data:image/' . $logoType . ';base64,' . base64_encode(file_get_contents($logoPath));
        }

        /* ===== ISO / Otabu / IAS certification seal (base64) ===== */
        $sealPath = public_path('certified-company.png');
        $sealSrc = null;
        if (file_exists($sealPath)) {
            $sealType = pathinfo($sealPath, PATHINFO_EXTENSION);
            $sealSrc = 'data:image/' . $sealType . ';base64,' . base64_encode(file_get_contents($sealPath));
        }

        /* ===== QR codes =====
         * Rendered as PNG data-URIs via bacon-qr-code matrix + GD. DomPDF v3 does NOT
         * render bacon's inline SVG (nested fractional transforms), and PNG output needs
 * imagick which is unavailable here — so we rasterize the matrix with GD directly.
 */
$qrPng = function (string $data, int $size): ?string {
    try {
        $matrix = \BaconQrCode\Encoder\Encoder::encode(
            $data,
            \BaconQrCode\Common\ErrorCorrectionLevel::valueOf('M'),
        )->getMatrix();

        $count = $matrix->getWidth();
        $scale = max(1, (int) floor($size / $count));
        $img = max($count * $scale, $count);

        $im = imagecreatetruecolor($img, $img);
        $white = imagecolorallocate($im, 255, 255, 255);
        $black = imagecolorallocate($im, 0, 0, 0);
        imagefilledrectangle($im, 0, 0, $img, $img, $white);

        for ($y = 0; $y < $count; $y++) {
            for ($x = 0; $x < $count; $x++) {
                if ($matrix->get($x, $y)) {
                    imagefilledrectangle(
                        $im,
                        $x * $scale,
                        $y * $scale,
                        ($x + 1) * $scale - 1,
                        ($y + 1) * $scale - 1,
                        $black,
                    );
                }
            }
        }

        ob_start();
        imagepng($im);
        $png = ob_get_clean();
        imagedestroy($im);

        return 'data:image/png;base64,' . base64_encode($png);
    } catch (\Throwable $e) {
        return null;
    }
};

$qrHeaderSrc = $qrPng($quotation->quotation_number ?: 'QUOTATION', 78);
$qrFooterSrc = $qrPng($companyWebsite !== '' ? $companyWebsite : 'https://www.adsscent.com', 56);

/* ===== Group quotation detail rows by building/gedung =====
 * QuotationDetail -> survey -> building (Survey::display_building_name prefers the
 * survey's own snapshot building_name, then falls back to the Building model).
         * Quotations without a linked survey/building render with no group header — same
         * fallback behavior as the invoice template when building_name is blank.
         */
        $quotationDetails = $quotation->quotationDetails ?? collect();
        $detailGroups = $quotationDetails->groupBy(fn($d) => trim((string) ($d->survey->display_building_name ?? '')));

        /* ===== Bottom-pin spacer height =====
         * Pushes totals/terms/footer to the bottom of the page on short quotations.
         * Estimated from row counts since DomPDF v3.1.2 ignores `height` on <table>/<div>
         * when content is shorter — only an explicit pixel value on a plain block works.
         * Page content area ≈ 1063px (A4 @96dpi minus 30px top/bottom margins).
         */
        $buildingGroupCount = $detailGroups
            ->filter(fn($g, $name) => trim((string) $name) !== '' && trim((string) $name) !== '-')
            ->count();
        $itemRowCount = $quotationDetails->count();
        $estimatedContentHeight =
            215 + // letterhead + bill-to/meta block
            40 + // items table header
            $buildingGroupCount * 28 +
            $itemRowCount * 38 +
            150; // totals + terbilang + terms-info + generated-note + footer block itself
        $bottomSpacerHeight = max(0, 900 - $estimatedContentHeight);
    @endphp

    <!-- ===== Letterhead ===== -->
    <table class="letterhead">
        <tr>
            <td style="width: 26%;">
                <img src="{{ $logoSrc }}" class="logo" alt="Logo" onerror="this.style.display='none'">
            </td>
            <td style="width: 42%;">
                <div class="doc-title">PENAWARAN HARGA</div>
            </td>
            <td style="width: 32%;">
                <div class="company-name">{{ $companyName }}</div>
                <div class="header-qr">
                    @if ($qrHeaderSrc)
                        <img src="{{ $qrHeaderSrc }}" alt="QR">
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <!-- ===== Info block ===== -->
    <table class="info-wrap">
        <tr>
            <td class="info-left">
                <div class="lbl">Penawaran Untuk :</div>
                <div class="party-name">{{ strtoupper($billToName) }}</div>
                <div class="party-addr">{{ $billToAddress }}</div>
                @if ($picPhone)
                    <div class="party-addr">{{ $picPhone }}</div>
                @endif
                @if ($picName)
                    <div class="party-addr">{{ $picName }}</div>
                @endif
            </td>
            <td class="info-right">
                <table class="meta-table">
                    <tr>
                        <td class="meta-label">Nomor</td>
                        <td class="meta-sep">:</td>
                        <td>{{ $quotation->quotation_number }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Tanggal</td>
                        <td class="meta-sep">:</td>
                        <td>{{ $quotation->quotation_date ? \Carbon\Carbon::parse($quotation->quotation_date)->format('d F Y') : '-' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="meta-label">Periode</td>
                        <td class="meta-sep">:</td>
                        <td>{{ $periodeText }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Syarat Pembayaran</td>
                        <td class="meta-sep">:</td>
                        <td>{{ $syaratPembayaranText }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">SC</td>
                        <td class="meta-sep">:</td>
                        <td>{{ $scName }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- ===== Items table ===== -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 30%;">Item</th>
                <th style="width: 50%;">Room</th>
                <th style="width: 20%;" class="right">Total / TOP</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($detailGroups as $buildingName => $details)
                @if (trim((string) $buildingName) !== '' && trim((string) $buildingName) !== '-')
                    <tr class="building-row">
                        <td colspan="3">Gedung&nbsp;:&nbsp;&nbsp;{{ $buildingName }}</td>
                    </tr>
                @endif
                @foreach ($details as $detail)
                    <tr>
                        <td>{{ $detail->rental_alias ?: $detail->masterRental->rental_name ?? '-' }}</td>
                        <td>{{ $detail->room_name ?: '-' }}</td>
                        <td class="right">{{ number_format($detail->total_price ?? 0, 0) }}</td>
                    </tr>
                @endforeach
            @empty
            @endforelse
        </tbody>
    </table>

    <!-- ===== Bottom-pin spacer: pushes totals/terms/footer to page bottom on short quotations ===== -->
    @if ($bottomSpacerHeight > 0)
        <div class="bottom-spacer" style="height: {{ $bottomSpacerHeight }}px;">&nbsp;</div>
    @endif

    <!-- ===== Totals ===== -->
    <table class="totals-wrap">
        <tr class="rule-top">
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td style="width: 50%;">
                <div class="terbilang">
                    <span class="t-key">Terbilang</span>&nbsp;&nbsp;:&nbsp;&nbsp;{{ $terbilangText }}
                </div>
            </td>
            <td style="width: 50%;">
                <table class="totals-table">
                    <tr>
                        <td class="t-label">TOTAL / TOP</td>
                        <td class="t-sep">:</td>
                        <td class="t-value">
                            {{ number_format($quotation->grand_total ?? ($quotation->total_amount ?? 0), 0) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr class="rule-bottom">
            <td></td>
            <td></td>
        </tr>
    </table>

    <!-- ===== Terms / notes ===== -->
    <div class="terms-info">
        <div class="terms-info-row">Harga tersebut adalah harga nett belum termasuk PPN sesuai Peraturan Pajak yang
            berlaku</div>
        <div class="terms-info-row">Penawaran berlaku selama 30 (tiga puluh) hari, terhitung dari tanggal penawaran ini.
        </div>
        @if ($quotation->terms_conditions)
            <div class="terms-info-row">{{ $quotation->terms_conditions }}</div>
        @endif
    </div>

    <div class="generated-note">
        This Quotation was generated on {{ now()->format('d M Y H:i:s') }} and is valid without the signature and
        seal.
    </div>

    <div class="generated-note"></div>
    <!-- ===== Fixed footer ===== -->
    <div class="page-footer">
        <table class="footer-grid">
            <tr>
                <td style="width: 16%;">
                    <div class="find-us">Find Us More Info<br>Scan Below</div>
                    @if ($qrFooterSrc)
                        <div><img src="{{ $qrFooterSrc }}" alt="QR"></div>
                    @endif
                </td>
                <td class="footer-center" style="width: 68%;">
                    <div class="iso-line">An ISO 14001:2015 Certified Company | IAS Accredited</div>
                    <div>{{ $headOfficeLine }}</div>
                    <div style="text-align:center;">{{ $companyWebsite }}</div>
                    <div class="branch-line" style="margin-top: 4px;">
                        Bali . Bandung . Batam . Balikpapan . Banjarmasin . Jakarta . Lampung . Makassar . Manado .
                        Medan . Palembang . Pekanbaru . Samarinda . Semarang . Surabaya
                    </div>
                </td>
                <td class="seal-cell" style="width: 16%;">
                    @if ($sealSrc)
                        <img src="{{ $sealSrc }}" class="seal-img" alt="An ISO 14001:2015 Certified Company">
                    @endif
                </td>
            </tr>
        </table>
        <table class="footer-meta">
            <tr>
                <td style="text-align: left;">{{ now()->format('d M Y H:i:s') }}</td>
                <td style="text-align: right;">&nbsp;</td>
            </tr>
        </table>
    </div>
</body>

</html>
