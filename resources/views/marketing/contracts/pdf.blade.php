<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Contract {{ $contract->contract_number }}</title>
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
            margin-bottom: 12px;
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

        .header-qr {
            text-align: right;
            margin-top: 6px;
        }

        .header-qr img,
        .header-qr svg {
            width: 50px;
            height: 50px;
        }

        .doc-title {
            font-size: 18px;
            font-weight: bold;
            color: #1aa3c9;
            border-bottom: 1px solid #ccc;
            padding-bottom: 10px;
            margin-bottom: 18px;
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
            padding-left: 14px;
            border-left: 4px solid #1aa3c9;
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
            width: 130px;
        }

        .meta-sep {
            width: 12px;
            font-weight: bold;
        }

        /* ===== Intro paragraph ===== */
        .intro-text {
            font-size: 10px;
            line-height: 1.5;
            margin-bottom: 14px;
            text-align: justify;
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
            font-size: 10px;
        }

        .rule-top td {
            border-top: 2px solid #777;
            padding: 0;
            height: 0;
        }

        .terbilang .t-key {
            font-weight: bold;
        }

        .top-label {
            text-align: right;
            font-weight: bold;
        }

        .top-value {
            text-align: right;
            width: 110px;
            font-weight: bold;
        }

        /* ===== Billing-to / Catatan ===== */
        .billing-block {
            margin-top: 18px;
            font-size: 10px;
            line-height: 1.5;
        }

        .billing-label {
            display: inline-block;
            width: 30px;
        }

        .catatan-block {
            margin-top: 18px;
            font-size: 10px;
        }

        /* ===== Page 2: payment/signature ===== */
        .intro-text-p2 {
            font-size: 10px;
            line-height: 1.5;
            margin-bottom: 16px;
            text-align: justify;
        }

        .payment-info {
            font-size: 10px;
            line-height: 1.5;
        }

        .payment-info-title {
            font-weight: bold;
            margin-bottom: 4px;
        }

        .signature-date {
            margin-top: 32px;
            font-size: 10px;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        .signature-table td {
            width: 50%;
            vertical-align: top;
            font-size: 10px;
            padding-top: 60px;
        }

        .signature-table .party-title {
            font-weight: bold;
        }

        .signature-line {
            border-top: 1px solid #222;
            width: 70%;
            margin-top: 56px;
        }

        .signature-name {
            font-weight: bold;
            margin-top: 4px;
        }

        .signature-position {
            font-size: 10px;
        }

        /* ===== Persyaratan (terms) ===== */
        .terms-title {
            font-size: 13px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 14px;
        }

        .terms-list {
            font-size: 10px;
            line-height: 1.5;
            text-align: justify;
        }

        .terms-list ol {
            padding-left: 16px;
            margin: 0;
        }

        .terms-list li {
            margin-bottom: 10px;
        }

        /* ===== Room/detail table (last page) ===== */
        .room-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        .room-table thead th {
            border-top: 2px solid #222;
            border-bottom: 2px solid #222;
            padding: 8px 6px;
            font-size: 10px;
            font-weight: bold;
            text-align: left;
        }

        .room-table td {
            padding: 3px 3px;
            vertical-align: top;
            font-size: 10px;
        }

        .room-table tr.building-row td {
            padding-top: 10px;
            font-weight: bold;
            font-size: 10px;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        /* ===== Bottom-pin spacer =====
         * Pushes the Paraf box + footer to the bottom of the page on short sections,
         * while staying purely in normal flow so it never overlaps table/list content
         * on long sections (see finance/invoices/print_template.blade.php for the same
         * pattern / DomPDF height-on-table caveat: only an explicit pixel value on a
         * plain block is honored when content is shorter than the target).
         */
        .bottom-spacer {
            line-height: 0;
            font-size: 0;
        }

        /* ===== Paraf box ===== */
        .paraf-wrap {
            margin-top: 18px;
            text-align: right;
            font-size: 10px;
        }

        .paraf-label {
            margin-bottom: 4px;
        }

        .paraf-table {
            display: inline-table;
            border-collapse: collapse;
        }

        .paraf-table td {
            border: 1px solid #222;
            width: 36px;
            height: 26px;
            text-align: center;
            vertical-align: middle;
            font-size: 10px;
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
        $companyWebsite = trim((string) ($company->website ?? 'www.adsscent.com'));
        $headOfficeLine = trim(
            collect([
                $companyAddress ? 'Head Office:  ' . $companyAddress : null,
                $companyPhone ? 'Whatsapp: ' . $companyPhone : null,
            ])
                ->filter()
                ->implode('  '),
        );

        /* ===== Bill To / customer block ===== */
        $billToName = trim((string) ($contract->customer->name ?? '-'));
        $billToAddress = trim((string) ($contract->customer->address ?? '-'));

        /* ===== Periode / Syarat Pembayaran / SC ===== */
        $periodeText = trim((string) ($contract->quotation->rental_period ?? ''));
        if ($periodeText !== '') {
            $periodeText .= ' ' . (ucfirst((string) ($contract->quotation->rental_unit ?? 'Bulan')) ?: 'Bulan');
        } else {
            $periodeText = '-';
        }
        $syaratPembayaranText = $contract->term_of_payment
            ? \App\Models\Quotation::formatTermsOfPaymentLabel($contract->term_of_payment)
            : '-';
        $scName = trim((string) ($contract->marketing->name ?? '-'));

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
        $contractTotal = (float) ($contract->contract_value ?? $contract->net_value ?? 0);
        $terbilangText = strtoupper(trim(preg_replace('/\s+/', ' ', $terbilang($contractTotal)))) . '  RUPIAH';

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

        $qrHeaderSrc = $qrPng($contract->contract_number ?: 'CONTRACT', 78);
        $qrFooterSrc = $qrPng($companyWebsite !== '' ? $companyWebsite : 'https://www.adsscent.com', 56);

        /* ===== Group rooms by building, price from contractRentals matched by room_id ===== */
        $rentalsByRoom = $contract->contractRentals->groupBy('room_id');
        $roomGroups = $contract->contractRooms->groupBy(
            fn($r) => trim((string) ($r->building->nama_gedung ?? $r->building->name ?? '')),
        );

        /* ===== Signatories ===== */
        $companySignatoryName = trim((string) ($contract->signatory_name ?: $contract->internalSigning->name ?? ''));
        $companySignatoryPosition = trim(
            (string) ($contract->signatory_position ?: $contract->internalSigning->position_name ?? 'General Manager'),
        );
        $customerSignatoryName = trim((string) ($contract->customerSigning1->name ?? ''));
        $customerSignatoryPosition = trim((string) ($contract->customerSigning1->position ?? ''));

        /* ===== Footer "Paraf" box markup (reused on every page) ===== */
        $parafBox = '
            <div class="paraf-wrap">
                <div class="paraf-label">Paraf</div>
                <table class="paraf-table">
                    <tr><td>1</td><td>2</td></tr>
                </table>
            </div>';

        /* ===== Bottom-pin spacer heights (one per page section) =====
         * Pushes the Paraf box + footer to the bottom of the page on short sections.
         * Estimated from row counts since DomPDF v3.1.2 ignores `height` on <table>/<div>
         * when content is shorter — only an explicit pixel value on a plain block works.
         * Page content area ≈ 1063px (A4 @96dpi minus 30px top/bottom margins).
         */
        $totalRoomCount = $contract->contractRooms->count();
        $buildingGroupCount = $roomGroups->filter(fn ($g, $name) => trim((string) $name) !== '')->count();

        // Page 1: letterhead + doc-title + info block + intro + items table + totals + billing/catatan.
        // Base (740px) measured with 2 buildings / 4 rooms — subtract that baseline so the
        // per-row additions below aren't double counted for other row counts.
        $page1ContentHeight = 740
            - (2 * 22)
            - (4 * 22)
            + ($buildingGroupCount * 22)
            + ($totalRoomCount * 22);
        $page1SpacerHeight = max(0, 850 - $page1ContentHeight);

        // Page 2: letterhead + intro + payment-info + signature date + signature table.
        // Fixed-height content, no row-count dependency.
        $page2ContentHeight = 497;
        $page2SpacerHeight = max(0, 850 - $page2ContentHeight);

        // Page 5: letterhead + doc-title + info block + room table.
        $page5ContentHeight = 461
            - (2 * 22)
            - (4 * 22)
            + ($buildingGroupCount * 22)
            + ($totalRoomCount * 22);
        $page5SpacerHeight = max(0, 850 - $page5ContentHeight);

        // Page 4: letterhead + terms list items 16-23 (8 points, shorter than page 3's 1-15).
        $page4ContentHeight = 60 + (8 * 80);
        $page4SpacerHeight = max(0, 850 - $page4ContentHeight);
    @endphp

    {{-- ===================== PAGE 1: Perjanjian Kerjasama ===================== --}}
    <table class="letterhead">
        <tr>
            <td style="width: 26%;">
                <img src="{{ $logoSrc }}" class="logo" alt="Logo" onerror="this.style.display='none'">
            </td>
            <td style="width: 42%;"></td>
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

    <div class="doc-title">PERJANJIAN KERJASAMA</div>

    <table class="info-wrap">
        <tr>
            <td class="info-left">
                <div class="lbl">Perjanjian Untuk :</div>
                <div class="party-name">{{ strtoupper($billToName) }}</div>
                <div class="party-addr">{{ $billToAddress }}</div>
            </td>
            <td class="info-right">
                <table class="meta-table">
                    <tr>
                        <td class="meta-label">No</td>
                        <td class="meta-sep">:</td>
                        <td>{{ $contract->contract_number }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Tanggal</td>
                        <td class="meta-sep">:</td>
                        <td>{{ $contract->contract_date ? \Carbon\Carbon::parse($contract->contract_date)->locale('id')->translatedFormat('d F Y') : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">SQ</td>
                        <td class="meta-sep">:</td>
                        <td>{{ $contract->quotation->quotation_number ?? '-' }}</td>
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

    <div class="intro-text">
        Dengan ini kami selaku Pelanggan tersebut di atas setuju melakukan Kontrak Jasa Pelayanan sesuai dengan survei
        yang dilakukan oleh Sales Consultant PT PINK SERVICES INDONESIA, dengan jumlah unit dan harga yang tertera di
        bawah ini dan dengan segala kondisi dan syarat-syarat yang termaktub di halaman ini dan halaman selanjutnya.
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 80%;">GEDUNG</th>
                <th style="width: 20%;" class="right">Total (IDR) / TOP</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($roomGroups as $buildingName => $rooms)
                @if (trim((string) $buildingName) !== '')
                    <tr class="building-row">
                        <td colspan="2">{{ strtoupper($buildingName) }}</td>
                    </tr>
                @endif
                @foreach ($rooms as $contractRoom)
                    @php
                        $matchedRental = ($rentalsByRoom->get($contractRoom->room_id) ?? collect())->first();
                        $roomAddress = trim((string) ($contractRoom->room->address ?? $contractRoom->building->alamat_1 ?? ''));
                    @endphp
                    <tr>
                        <td>{{ $roomAddress !== '' ? $roomAddress : ($contractRoom->room->room_name ?? '-') }}</td>
                        <td class="right">{{ number_format($matchedRental->total_price ?? 0, 0) }}</td>
                    </tr>
                @endforeach
            @empty
            @endforelse
        </tbody>
    </table>

    <table class="totals-wrap">
        <tr class="rule-top">
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td style="width: 60%;">
                <div class="terbilang">
                    <span class="t-key">Terbilang:</span>&nbsp;&nbsp;{{ $terbilangText }}
                </div>
            </td>
            <td style="width: 40%;">
                <span class="top-label">TOTAL / TOP :Rp</span>&nbsp;&nbsp;<span class="top-value">{{ number_format($contractTotal, 0) }}</span>
            </td>
        </tr>
    </table>

    <div class="billing-block">
        <div><span class="billing-label">Billing To</span> :</div>
        <div>&nbsp;</div>
        <div>NPWP : {{ $contract->npwp_number ?: ($contract->customer->npwp ?? '-') }}</div>
        <div>{{ $billToAddress }}</div>
    </div>

    <div class="catatan-block">
        Catatan&nbsp;&nbsp;:
    </div>

    @if ($page1SpacerHeight > 0)
        <div class="bottom-spacer" style="height: {{ $page1SpacerHeight }}px;">&nbsp;</div>
    @endif

    {!! $parafBox !!}

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
    </div>

    <div class="page-break"></div>

    {{-- ===================== PAGE 2: Payment / Signature ===================== --}}
    <table class="letterhead">
        <tr>
            <td style="width: 26%;">
                <img src="{{ $logoSrc }}" class="logo" alt="Logo" onerror="this.style.display='none'">
            </td>
            <td style="width: 42%;"></td>
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

    <div class="intro-text-p2">
        Dengan ini kami sebagai Pelanggan mengerti dan setuju bahwa penagihan PT PINK SERVICES INDONESIA akan
        dikirimkan dalam bentuk e-mail sebagai bukti penagihan yang Sah.
    </div>

    <div class="payment-info">
        <div class="payment-info-title">Payment to : {{ $companyName }}</div>
        <div>Virtual Account Bank : {{ $contract->virtual_account ?: '-' }}</div>
    </div>

    <div class="signature-date">
        Jakarta, {{ $contract->contract_date ? \Carbon\Carbon::parse($contract->contract_date)->locale('id')->translatedFormat('d F Y') : '-' }}
    </div>

    <table class="signature-table">
        <tr>
            <td>
                <div class="party-title">{{ $companyName }}</div>
            </td>
            <td>
                <div class="party-title">{{ strtoupper($billToName) }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="signature-line"></div>
                <div class="signature-name">{{ $companySignatoryName ?: '-' }}</div>
                <div class="signature-position">( {{ $companySignatoryPosition ?: '-' }} )</div>
            </td>
            <td>
                <div class="signature-line"></div>
                <div class="signature-name">{{ $customerSignatoryName ?: '-' }}</div>
                <div class="signature-position">( {{ $customerSignatoryPosition ?: '-' }} )</div>
            </td>
        </tr>
    </table>

    @if ($page2SpacerHeight > 0)
        <div class="bottom-spacer" style="height: {{ $page2SpacerHeight }}px;">&nbsp;</div>
    @endif

    {!! $parafBox !!}

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
    </div>

    <div class="page-break"></div>

    {{-- ===================== PAGE 3: Persyaratan Perjanjian Kontrak (1-16) ===================== --}}
    <table class="letterhead">
        <tr>
            <td style="width: 26%;">
                <img src="{{ $logoSrc }}" class="logo" alt="Logo" onerror="this.style.display='none'">
            </td>
            <td style="width: 42%;"></td>
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

    <div class="terms-title">PERSYARATAN PERJANJIAN KONTRAK</div>

    <div class="terms-list">
        <ol>
            <li>PT. Pink Services Indonesia adalah perusahaan yang bergerak dalam sarana pelayanan scenting Aroma
                Delivery System (ADS), Healthcare Product (PURE), Face Recognition Terminal (HIK VISION) dan Air
                Purification System (VIRUS GUARD).</li>
            <li>Pihak Pelanggan sepakat untuk melakukan kontrak kerjasama pelayanan paket produk yang ada di PT. Pink
                Services Indonesia. Dalam pelayanan paket tersebut, PT. Pink Services Indonesia akan meminjamkan unit
                ADS atau dan PURE, atau dan HIK VISION dan VIRUS GUARD yang disebut EQUIPMENT sesuai dengan kesepakatan
                yang sudah dipilih selama periode kontrak berjalan.</li>
            <li>Pihak Pelanggan mengijinkan hanya PT. Pink Services Indonesia untuk melakukan pemasangan EQUIPMENT
                dengan melakukan pengelolaan atau pengeboran di lokasi Pelanggan, serta melakukan service berjangka
                selama masa kontrak.</li>
            <li>Semua EQUIPMENT yang dipasang oleh PT. Pink Services Indonesia selama masa kontrak berjalan merupakan
                hak milik PT. Pink Services Indonesia dan hanya dipinjamkan kepada Pihak Pelanggan selama kontrak
                berlangsung.</li>
            <li>Pihak Pelanggan mengijinkan PT. Pink Services Indonesia untuk masuk ke lokasi pemasangan EQUIPMENT
                untuk melakukan pelepasan dan penarikan EQUIPMENT milik PT. Pink Services Indonesia jika kontrak
                berakhir atau kontrak tidak diperpanjang dikarenakan hal-hal tertentu.</li>
            <li>Kontrak baru mulai efektif apabila item EQUIPMENT yang tercantum di belakang halaman ini telah
                terpasang dan diservice oleh PT. Pink Services Indonesia dan kedua belah pihak telah menandatangani
                Berita Acara Pemasangan (BAP) dan Berita Acara Service Pertama (BASP) untuk periode kontrak yang
                tercantum di belakang halaman ini.</li>
            <li>Pembayaran bertahap atau partial dapat disepakati selama setiap pemasangan dapat dilakukan penagihan
                secara langsung dan terpisah sesuai dengan Berita Acara Service Pertama (BASP).</li>
            <li>Sisa pemasangan dari sisa yang tercantum di kontrak, harus diselesaikan dalam kurun waktu 30 (tiga
                puluh) hari.</li>
            <li>Karyawan PT. Pink Services Indonesia yang telah terlatih akan melakukan kunjungan untuk melakukan
                pengecekan atau perbaikan terhadap EQUIPMENT yang sudah terpasang, pengisian refill sesuai jadwal dan
                frekuensi service yang telah disepakati kedua belah pihak untuk memastikan EQUIPMENT yang ada di
                lokasi Pelanggan dapat berfungsi dengan baik selama masa periode kontrak.</li>
            <li>PT. Pink Services Indonesia akan mengirimkan invoice dan faktur pajak sebagai bukti penagihan.</li>
            <li>Semua penagihan akan ditagihkan pada saat pekerjaan selesai dan telah di tanda tangani bukti
                service.</li>
            <li>Pembayaran dilakukan melalui transfer ke bank dan nomor rekening yang telah dicantumkan dalam kontrak
                dan mencantumkan keterangan no invoice.</li>
            <li>Pembayaran secara CASH / TUNAI tidak diijinkan.</li>
            <li>Pembayaran dapat diakui oleh PT. Pink Services Indonesia setelah pembayaran diterima pada rekening PT.
                Pink Services Indonesia yang dicantumkan di dalam kontrak ini.</li>
            <li>Pelanggan wajib menjaga semua EQUIPMENT milik PT. Pink Services Indonesia yang terpasang agar
                terhindar dari pengrusakan atau kehilangan. Apabila terjadi kehilangan EQUIPMENT, maka akan dikenakan
                biaya denda kehilangan EQUIPMENT sesuai harga yang berlaku di PT. Pink Services Indonesia.</li>
        </ol>
    </div>

    {!! $parafBox !!}

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
    </div>

    <div class="page-break"></div>

    {{-- ===================== PAGE 4: Persyaratan Perjanjian Kontrak (16-23) ===================== --}}
    <table class="letterhead">
        <tr>
            <td style="width: 26%;">
                <img src="{{ $logoSrc }}" class="logo" alt="Logo" onerror="this.style.display='none'">
            </td>
            <td style="width: 42%;"></td>
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

    <div class="terms-list">
        <ol start="16">
            <li>Apabila terjadi penambahan chemical atau material pendukung lainnya untuk jenis EQUIPMENT yang
                tercantum di belakang halaman ini dan telah melebihi limit yang tercantum yang telah disetujui, maka
                pihak Pelanggan wajib mengeluarkan PO dan akan dikenakan biaya tambahan sesuai yang berlaku di PT.
                Pink Services Indonesia. Penagihan biaya tambahan akan terpisah dan tidak digabungkan dengan invoice
                lainnya.</li>
            <li>Periode kontrak diberlakukan minimum selama 12 bulan atau lebih dengan kelipatan 12 bulan, yang
                selanjutnya dapat diperpanjang per 12 bulan atau lebih dengan kelipatan 12 bulan berdasarkan
                kesepakatan harga yang telah disepakati kedua belah pihak pada saat perpanjangan kontrak. Apabila
                pihak Pelanggan tidak ingin melanjutkan kontrak kerjasama, maka pihak Pelanggan wajib
                memberitahukan secara tertulis kepada PT. Pink Services Indonesia minimum 60 hari sebelum jatuh tempo
                kontrak berakhir. Apabila tidak ada pemberitahuan secara tertulis dari Pelanggan, maka kontrak ini
                akan secara otomatis diperpanjang dengan syarat dan kondisi yang sama dan telah disetujui pihak PT.
                Pink Services Indonesia.</li>
            <li>Kontrak yang telah disepakati tidak dapat dirubah. Apabila ada pemutusan kontrak sepihak dari pihak
                Pelanggan maka akan dikenakan sanksi denda 25 % dari total nilai kontrak selama 12 bulan atau sisa
                nilai periode kontrak yang belum tertagih (salah satu mana yang lebih besar).</li>
            <li>Hanya pihak PT. Pink Services Indonesia yang berwenang dan berhak melakukan pemasangan, pemindahan,
                perbaikan, dan pelepasan EQUIPMENT. Apabila terjadi pemindahan atau pelepasan EQUIPMENT oleh pihak
                lain di lokasi Pelanggan, maka PT. Pink Services Indonesia akan mengenakan sanksi biaya kepada
                Pelanggan.</li>
            <li>PPn adalah kebijakan yang di tentukan oleh Pemerintah sesuai undang undang yang besarannya dapat
                berubah sesuai peraturan yang berlaku dan kedua belah pihak sepakat mengikuti peraturan tersebut.</li>
            <li>Segala peristiwa force majeure (banjir, gempa bumi, kebakaran, huru-hara, perubahan peraturan
                pemerintahan, devaluasi), yaitu peristiwa yang tidak dikehendaki dan terjadi di luar kemampuan kedua
                belah pihak untuk mencegahnya. Apabila peristiwa force majeure tidak dapat diatasi, sehingga salah
                satu pihak tidak dapat melakukan kewajibannya berkelanjutan, maka kontrak ini dapat diberhentikan
                berdasarkan keputusan bersama.</li>
            <li>Tidak ada perlakuan khusus yang dapat diterima selain ada kesepakatan secara tertulis oleh pejabat
                atau pemimpin yang berwenang dari PT. Pink Services Indonesia.</li>
            <li>Perjanjian ini dibuat dalam 2 (dua) salinan asli diatas meterai yang cukup, dan memiliki kekuatan
                hukum yang sama setelah ditandatangani oleh Para Pihak. Perjanjian ini tidak dapat diubah kecuali
                secara tertulis dan dengan ditanda tangani oleh Para Pihak.</li>
        </ol>
    </div>

    @if ($page4SpacerHeight > 0)
        <div class="bottom-spacer" style="height: {{ $page4SpacerHeight }}px;">&nbsp;</div>
    @endif

    {!! $parafBox !!}

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
    </div>

    <div class="page-break"></div>

    {{-- ===================== PAGE 5: Room / Harga detail ===================== --}}
    <table class="letterhead">
        <tr>
            <td style="width: 26%;">
                <img src="{{ $logoSrc }}" class="logo" alt="Logo" onerror="this.style.display='none'">
            </td>
            <td style="width: 42%;"></td>
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

    <div class="doc-title">PERJANJIAN KERJASAMA</div>

    <table class="info-wrap">
        <tr>
            <td class="info-left">
                <div class="lbl">Perjanjian Untuk :</div>
                <div class="party-name">{{ strtoupper($billToName) }}</div>
                <div class="party-addr">{{ $billToAddress }}</div>
            </td>
            <td class="info-right">
                <table class="meta-table">
                    <tr>
                        <td class="meta-label">No</td>
                        <td class="meta-sep">:</td>
                        <td>{{ $contract->contract_number }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Tanggal</td>
                        <td class="meta-sep">:</td>
                        <td>{{ $contract->contract_date ? \Carbon\Carbon::parse($contract->contract_date)->locale('id')->translatedFormat('d F Y') : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">SQ</td>
                        <td class="meta-sep">:</td>
                        <td>{{ $contract->quotation->quotation_number ?? '-' }}</td>
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

    <table class="room-table">
        <thead>
            <tr>
                <th style="width: 80%;">ROOM</th>
                <th style="width: 20%;" class="right">HARGA / TOP</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($roomGroups as $buildingName => $rooms)
                @if (trim((string) $buildingName) !== '')
                    <tr class="building-row">
                        <td colspan="2">{{ strtoupper($buildingName) }}</td>
                    </tr>
                @endif
                @foreach ($rooms as $contractRoom)
                    @php
                        $matchedRental = ($rentalsByRoom->get($contractRoom->room_id) ?? collect())->first();
                        $rentalName = trim((string) ($matchedRental->rental_alias ?: ($matchedRental->masterRental->rental_name ?? '-')));
                        $roomName = $contractRoom->room->room_name ?? '-';
                    @endphp
                    <tr>
                        <td>{{ $rentalName }}&nbsp;&nbsp;&nbsp;&nbsp;{{ $roomName }}</td>
                        <td class="right">{{ number_format($matchedRental->total_price ?? 0, 0) }}</td>
                    </tr>
                @endforeach
            @empty
            @endforelse
        </tbody>
    </table>

    @if ($page5SpacerHeight > 0)
        <div class="bottom-spacer" style="height: {{ $page5SpacerHeight }}px;">&nbsp;</div>
    @endif

    {!! $parafBox !!}

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
    </div>
</body>

</html>
