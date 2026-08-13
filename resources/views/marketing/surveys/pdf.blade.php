<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Survey {{ $survey->survey_number }}</title>
    <style>
        @page {
            margin: 30px 36px 140px 36px;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 13px;
            color: #111;
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
            margin: 24px 0 0 40px;
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

        /* ===== Section ===== */
        .section {
            margin-bottom: 18px;
        }

        .section-compact {
            page-break-inside: avoid;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #1aa3c9;
            border-bottom: 1px solid #ccc;
            padding-bottom: 4px;
            margin-bottom: 8px;
        }

        /* ===== Detail table (label/value pairs) ===== */
        .detail-table {
            width: 100%;
            border-collapse: collapse;
        }

        .detail-table td {
            padding: 4px 8px;
            font-size: 10px;
            border: 1px solid #dee2e6;
            vertical-align: top;
        }

        .detail-label {
            font-weight: bold;
            background-color: #f8f9fa;
            width: 25%;
        }

        /* ===== Room blocks ===== */
        .room-block {
            margin-bottom: 14px;
            page-break-inside: avoid;
        }

        .room-title {
            font-size: 11px;
            font-weight: bold;
            color: #1aa3c9;
            border-bottom: 1px solid #ccc;
            padding-bottom: 3px;
            margin-bottom: 6px;
        }

        /* ===== Status badge ===== */
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-draft { background-color: #ffc107; color: #000; }
        .status-submitted { background-color: #17a2b8; color: #fff; }
        .status-approved { background-color: #28a745; color: #fff; }
        .status-rejected { background-color: #dc3545; color: #fff; }
        .status-in_progress { background-color: #6f42c1; color: #fff; }
        .status-completed { background-color: #20c997; color: #fff; }
        .status-cancelled { background-color: #6c757d; color: #fff; }

        /* ===== Result / recommendation text ===== */
        .result-block {
            font-size: 10px;
            line-height: 1.5;
            margin-bottom: 10px;
            page-break-inside: avoid;
        }

        .result-block .r-label {
            font-weight: bold;
            margin-bottom: 3px;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        /* ===== Footer ===== */
        .page-footer {
            position: fixed;
            bottom: -125px;
            left: 0;
            right: 0;
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
        $companyWebsite = trim((string) ($company->website ?? 'www.adsscent.com'));
        $headOfficeLine = trim(
            collect([
                $companyAddress ? 'Head Office :  ' . $companyAddress : null,
                $companyPhone ? 'Whatsapp: ' . $companyPhone : null,
            ])
                ->filter()
                ->implode('  '),
        );

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

        $qrHeaderSrc = $qrPng($survey->survey_number ?: 'SURVEY', 78);
        $qrFooterSrc = $qrPng($companyWebsite !== '' ? $companyWebsite : 'https://www.adsscent.com', 56);

        /* ===== Survey Untuk block ===== */
        $surveyForName = trim((string) ($survey->company_name ?? $survey->customer->name ?? '-'));
        $address1 = $survey->survey_location ?? $survey->building->building_address ?? '';
        $address2 = $survey->building->alamat_2 ?? '';
        $surveyForAddress = trim($address1);
        if (!empty($address2)) {
            $surveyForAddress .= '. ' . $address2;
        }

    @endphp

    <!-- ===== Letterhead ===== -->
    <table class="letterhead">
        <tr>
            <td style="width: 26%;">
                <img src="{{ $logoSrc }}" class="logo" alt="Logo" onerror="this.style.display='none'">
            </td>
            <td style="width: 42%;">
                <div class="doc-title">LAPORAN SURVEY</div>
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
                <div class="lbl">Survey Untuk :</div>
                <div class="party-name">{{ strtoupper($surveyForName) }}</div>
                <div class="party-addr">{{ $surveyForAddress ?: '-' }}</div>
                @if ($survey->contact_person || $survey->phone_1)
                    <div class="party-addr">{{ trim(($survey->contact_person ?? '') . ' ' . ($survey->phone_1 ? '- ' . $survey->phone_1 : '')) }}</div>
                @endif
            </td>
            <td class="info-right">
                <table class="meta-table">
                    <tr>
                        <td class="meta-label">Nomor</td>
                        <td class="meta-sep">:</td>
                        <td>{{ $survey->survey_number }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Tanggal</td>
                        <td class="meta-sep">:</td>
                        <td>{{ $survey->survey_date ? \Carbon\Carbon::parse($survey->survey_date)->format('d F Y') : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Status</td>
                        <td class="meta-sep">:</td>
                        <td><span class="status-badge status-{{ $survey->status }}">{{ ucfirst($survey->status) }}</span></td>
                    </tr>
                    <tr>
                        <td class="meta-label">SC</td>
                        <td class="meta-sep">:</td>
                        <td>{{ $survey->marketing->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Surveyor</td>
                        <td class="meta-sep">:</td>
                        <td>{{ $survey->surveyor->name ?? '-' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- ===== Location details ===== -->
    @if ($survey->building_location_detail || $survey->temperature || ($survey->latitude && $survey->longitude))
    <div class="section section-compact">
        <div class="section-title">Informasi Lokasi</div>
        <table class="detail-table">
            @if ($survey->building_location_detail)
            <tr>
                <td class="detail-label">Detail Lokasi</td>
                <td colspan="3">{{ $survey->building_location_detail }}</td>
            </tr>
            @endif
            <tr>
                <td class="detail-label">Suhu</td>
                <td style="width: 25%;">{{ $survey->temperature_text }}</td>
                <td class="detail-label">Koordinat GPS</td>
                <td style="width: 25%;">{{ $survey->latitude && $survey->longitude ? $survey->latitude . ', ' . $survey->longitude : '-' }}</td>
            </tr>
        </table>
    </div>
    @endif

    <!-- ===== Company Information ===== -->
    <div class="section section-compact">
        <div class="section-title">Informasi Perusahaan</div>
        <table class="detail-table">
            <tr>
                <td class="detail-label">Tipe Pelanggan</td>
                <td style="width: 25%;">{{ $survey->customer_type ?? $survey->customer->company_type ?? '-' }}</td>
                <td class="detail-label">Kontak Person</td>
                <td style="width: 25%;">{{ $survey->contact_person ?? '-' }}</td>
            </tr>
            <tr>
                <td class="detail-label">Email</td>
                <td>{{ $survey->email ?? '-' }}</td>
                <td class="detail-label">Jabatan</td>
                <td>{{ $survey->position ?? '-' }}</td>
            </tr>
            <tr>
                <td class="detail-label">Telepon 1</td>
                <td>{{ $survey->phone_1 ?? '-' }}</td>
                <td class="detail-label">Telepon 2</td>
                <td>{{ $survey->phone_2 ?? '-' }}</td>
            </tr>
        </table>
    </div>

    <!-- ===== Building Information ===== -->
    <div class="section section-compact">
        <div class="section-title">Informasi Gedung</div>
        <table class="detail-table">
            <tr>
                <td class="detail-label">Nama Gedung</td>
                <td style="width: 25%;">{{ $survey->building->building_name ?? $survey->building_name ?? '-' }}</td>
                <td class="detail-label">Tipe Gedung</td>
                <td style="width: 25%;">{{ $survey->building_type ?? $survey->building->building_type ?? '-' }}</td>
            </tr>
            <tr>
                <td class="detail-label">Alamat 1</td>
                <td>{{ $survey->building->building_address ?? $survey->address_1 ?? '-' }}</td>
                <td class="detail-label">Alamat 2</td>
                <td>{{ $survey->building->alamat_2 ?? $survey->address_2 ?? '-' }}</td>
            </tr>
            <tr>
                <td class="detail-label">Telepon</td>
                <td>{{ $survey->building->phone_1 ?? '-' }}</td>
                <td class="detail-label">Fax</td>
                <td>{{ $survey->building->fax ?? '-' }}</td>
            </tr>
            <tr>
                <td class="detail-label">Provinsi</td>
                <td>{{ $survey->province ?? $survey->building->province->name ?? '-' }}</td>
                <td class="detail-label">Kota</td>
                <td>{{ $survey->city ?? $survey->building->city->name ?? '-' }}</td>
            </tr>
            <tr>
                <td class="detail-label">Kecamatan</td>
                <td>{{ $survey->district ?? $survey->building->district->name ?? '-' }}</td>
                <td class="detail-label">Kelurahan</td>
                <td>{{ $survey->village ?? $survey->building->subdistrict->name ?? '-' }}</td>
            </tr>
            <tr>
                <td class="detail-label">Kode Pos</td>
                <td colspan="3">{{ $survey->postal_code ?? $survey->building->building_postal_code ?? '-' }}</td>
            </tr>
        </table>
    </div>

    <!-- ===== Room Details ===== -->
    @if ($survey->surveyDetails && $survey->surveyDetails->count() > 0)
    <div class="section">
        <div class="section-title">Detail Ruangan</div>
        @foreach ($survey->surveyDetails as $index => $detail)
            @php
                $specs = json_decode($detail->specifications ?? '{}', true);
            @endphp
            <div class="room-block">
                <div class="room-title">Ruang {{ $index + 1 }}: {{ $detail->room_name ?? 'Tanpa Nama' }}</div>
                <table class="detail-table">
                    <tr>
                        <td class="detail-label">Tipe Ruangan</td>
                        <td style="width: 25%;">{{ $detail->room_type ?? '-' }}</td>
                        <td class="detail-label">Lantai</td>
                        <td style="width: 25%;">{{ $specs['floor'] ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="detail-label">Intensitas Aroma</td>
                        <td>{{ $specs['intensity'] ?? '-' }}</td>
                        <td class="detail-label">Tipe Instalasi</td>
                        <td>{{ $specs['installation_type'] ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="detail-label">Jumlah</td>
                        <td>{{ $specs['qty'] ?? $detail->quantity_needed ?? '-' }}</td>
                        <td class="detail-label">Suhu</td>
                        <td>{{ $specs['temperature'] ?? '-' }}{{ isset($specs['temperature']) ? '°C' : '' }}</td>
                    </tr>
                    <tr>
                        <td class="detail-label">Panjang (m)</td>
                        <td>{{ $specs['length'] ?? '-' }}</td>
                        <td class="detail-label">Lebar (m)</td>
                        <td>{{ $specs['width'] ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="detail-label">Tinggi (m)</td>
                        <td>{{ $specs['height'] ?? '-' }}</td>
                        <td class="detail-label">Luas (m²)</td>
                        <td>{{ $specs['area'] ?? $detail->room_area ?? '-' }}</td>
                    </tr>
                    @if (!empty($specs['remark']))
                    <tr>
                        <td class="detail-label">Catatan</td>
                        <td colspan="3">{{ $specs['remark'] }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        @endforeach
    </div>
    @endif

    <!-- ===== Survey Result & Recommendations ===== -->
    @if ($survey->survey_result || $survey->recommendations)
    <div class="section section-compact">
        <div class="section-title">Hasil Survey & Rekomendasi</div>
        @if ($survey->survey_result)
        <div class="result-block">
            <div class="r-label">Hasil Survey</div>
            <div>{{ $survey->survey_result }}</div>
        </div>
        @endif
        @if ($survey->recommendations)
        <div class="result-block">
            <div class="r-label">Rekomendasi</div>
            <div>{{ $survey->recommendations }}</div>
        </div>
        @endif
    </div>
    @endif

    <!-- ===== Fixed footer (repeats on every page via position:fixed) ===== -->
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
