<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 24px 30px 96px 30px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #111; }
        .page-break { page-break-after: always; }

        /* ===== Letterhead ===== */
        .letterhead { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        .letterhead td { vertical-align: top; }
        .logo { width: 96px; }
        .company-name { font-size: 15px; font-weight: bold; text-align: right; }
        .doc-title { font-size: 22px; font-weight: bold; text-align: center; margin: 14px 0 26px; }
        .header-qr { text-align: right; }
        .header-qr img, .header-qr svg { width: 78px; height: 78px; }

        /* ===== Info block ===== */
        .info-wrap { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .info-wrap > tbody > tr > td { vertical-align: top; }
        .info-left { width: 52%; padding-right: 22px; }
        .info-right { width: 48%; }

        .info-left .lbl { font-size: 11px; }
        .info-left .party-name { font-size: 12px; font-weight: bold; margin: 2px 0 4px; }
        .info-left .party-addr { line-height: 1.3; margin-bottom: 16px; }

        .meta-table { width: 100%; border-collapse: collapse; }
        .meta-table td { padding: 2px 0; vertical-align: top; font-size: 11px; }
        .meta-label { width: 120px; }
        .meta-sep { width: 10px; font-weight: bold; }

        /* ===== Items table ===== */
        .items-table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        .items-table thead th {
            border-top: 1px solid #222;
            border-bottom: 1px solid #222;
            padding: 6px 6px;
            font-size: 12px;
            font-weight: bold;
            text-align: left;
        }
        .items-table td { padding: 4px 6px; vertical-align: top; }
        .items-table tr.building-row td { padding-top: 8px; font-weight: bold; text-transform: uppercase; }

        /* ===== Totals ===== */
        .totals-wrap { width: 100%; border-collapse: collapse; margin-top: 4px; }
        .totals-wrap td { vertical-align: top; }
        .totals-table { width: 100%; border-collapse: collapse; }
        .totals-table td { padding: 3px 6px; font-size: 12px; }
        .totals-table .t-label { text-align: right; font-weight: bold; }
        .totals-table .t-sep { width: 12px; text-align: center; font-weight: bold; }
        .totals-table .t-value { text-align: right; width: 110px; }
        .rule-top td { border-top: 2px solid #777; padding: 0; height: 0; }
        .rule-bottom td { border-bottom: 2px solid #777; padding: 0; height: 0; }
        .terbilang { font-size: 11px; padding-top: 6px; }
        .terbilang .t-key { font-weight: bold; }

        /* ===== Payment ===== */
        .payment-info { margin-top: 26px; line-height: 1.45; }
        .payment-info-title { font-weight: bold; margin-bottom: 4px; }
        .payment-info-row { margin-bottom: 1px; }
        .payment-email a { color: #1a56db; text-decoration: none; }

        .generated-note {
            margin-top: 20px;
            border-top: 1px solid #aaa;
            padding-top: 8px;
            text-align: center;
            color: #888;
            font-size: 10px;
            font-weight: bold;
        }

        .center { text-align: center; }
        .right { text-align: right; }

        /* ===== Footer ===== */
        .page-footer { position: fixed; left: 30px; right: 30px; bottom: 14px; }
        .footer-grid { width: 100%; border-collapse: collapse; }
        .footer-grid td { vertical-align: bottom; }
        .find-us { font-size: 9px; font-weight: bold; line-height: 1.2; }
        .find-us img, .find-us svg { width: 56px; height: 56px; margin-top: 2px; }
        .footer-center { text-align: center; font-size: 9px; color: #777; line-height: 1.35; }
        .footer-center .iso-line { font-weight: bold; color: #333; }
        .footer-center .branch-line { color: #9a9a9a; }
        .seal-cell { width: 90px; text-align: right; }
        .seal-img { width: 88px; height: auto; }
        .footer-meta { width: 100%; border-collapse: collapse; font-size: 9px; color: #555; font-weight: bold; margin-top: 6px; }
        .footer-meta td { padding-top: 4px; }
    </style>
</head>
<body>
    @php
        $company = \App\Models\Company::first();
        $taxCodeRule = $invoice->tax_code
            ? \App\Models\FinanceTaxCode::where('code', $invoice->tax_code)->first()
            : null;
        $taxRate = (float) ($invoice->taxSetting->tax_rate ?? 0);
        $showTaxRow = $invoice->tax_amount > 0 || ($taxCodeRule && $taxCodeRule->hasZeroTaxPrint());

        $invoiceBranch = $invoice->resolvedInvoiceBranch();

        $billingGroup = $invoice->billingGroup ?: $invoice->contract?->billingGroup;
        $contractVirtualAccount = trim((string) ($invoice->contract?->virtual_account ?: $invoice->contractById?->virtual_account ?: ''));
        $rawPaymentAccount = trim((string) ($contractVirtualAccount ?: $invoice->virtual_account_number ?: $billingGroup?->virtual_account_number ?: ''));
        $paymentMethod = trim((string) ($invoice->payment_method ?: $billingGroup?->payment_method ?: ''));
        $paymentBankName = trim((string) ($billingGroup?->bank_name ?: ''));
        $paymentAccountName = '';
        $paymentAccountNumber = '';

        if ($rawPaymentAccount !== '') {
            if (preg_match('/^\s*(?:(.*?)\s*-\s*)?(.*?)\s*\((.*?)\)\s*$/', $rawPaymentAccount, $matches)) {
                $parsedBankName = trim((string) ($matches[1] ?? ''));
                $paymentAccountName = trim((string) ($matches[2] ?? ''));
                $paymentAccountNumber = trim((string) ($matches[3] ?? ''));

                if ($parsedBankName !== '') {
                    $paymentBankName = $parsedBankName;
                }
            } else {
                $paymentAccountNumber = $rawPaymentAccount;
            }
        }

        $bankPayment = null;
        if ($paymentAccountNumber !== '' && \Illuminate\Support\Facades\Schema::hasTable('bank_payments')) {
            $bankPayment = \App\Models\BankPayment::with('bank')
                ->where('account_number', $paymentAccountNumber)
                ->first();
        }

        if (!$bankPayment && $paymentAccountNumber === '' && $invoice->customer?->defaultBankPayment) {
            $bankPayment = $invoice->customer->defaultBankPayment;
        }

        $companyVirtualAccount = null;
        if (\Illuminate\Support\Facades\Schema::hasTable('company_virtual_accounts') && $invoice->customer) {
            $companyVirtualAccountQuery = function () use ($invoice) {
                $customerName = trim((string) ($invoice->customer?->name ?? ''));

                return \App\Models\CompanyVirtualAccount::with('bankPayment.bank')
                    ->where('is_active', true)
                    ->where(function ($query) use ($invoice, $customerName) {
                        $query->where('customer_id', $invoice->customer_id);

                        if ($customerName !== '') {
                            $query->orWhere('account_name', $customerName)
                                ->orWhere('account_name', 'like', "%{$customerName}%")
                                ->orWhere('description', 'like', "%{$customerName}%");
                        }
                    })
                    ->orderByDesc('updated_at')
                    ->orderByDesc('created_at');
            };

            if ($contractVirtualAccount !== '') {
                $companyVirtualAccount = \App\Models\CompanyVirtualAccount::with('bankPayment.bank')
                    ->where('account_number', $contractVirtualAccount)
                    ->where('is_active', true)
                    ->first();
            }

            if ($bankPayment) {
                $companyVirtualAccount = $companyVirtualAccount ?: $companyVirtualAccountQuery()
                    ->where('bank_payment_id', $bankPayment->id)
                    ->first();
            }

            $companyVirtualAccount = $companyVirtualAccount ?: $companyVirtualAccountQuery()->first();
        }

        if ($companyVirtualAccount) {
            $bankPayment = $companyVirtualAccount->bankPayment ?: $bankPayment;
            $paymentAccountName = trim((string) ($companyVirtualAccount->account_name ?: $paymentAccountName));
            $paymentAccountNumber = $contractVirtualAccount !== ''
                ? $contractVirtualAccount
                : trim((string) $companyVirtualAccount->account_number);
        }

        if ($bankPayment) {
            $bankNameFromModel = trim((string) ($bankPayment->bank?->bank_name ?: $bankPayment->bank?->name ?: ''));
            $paymentBankName = $paymentBankName ?: $bankNameFromModel;
            $paymentAccountName = $paymentAccountName ?: trim((string) $bankPayment->account_name);
            $paymentAccountNumber = $paymentAccountNumber ?: trim((string) $bankPayment->account_number);
        }

        $paymentAccountName = $paymentAccountName ?: trim((string) ($company->name ?? ''));
        $paymentBranchName = trim((string) ($bankPayment?->branch_name ?? ''));
        $paymentAddress = trim((string) ($bankPayment?->address ?? ''));
        $displayBankName = trim(preg_replace('/^bank\s+/i', '', $paymentBankName));
        $isVirtualAccount = str_starts_with(strtolower($paymentMethod), 'va_')
            || str_contains(strtolower($paymentMethod), 'virtual')
            || (bool) ($bankPayment?->is_default_va ?? false);
        $paymentNumberLabel = $isVirtualAccount ? 'Virtual Account Bank' : 'Nomor Rekening Bank';
        $paymentNumberLabel .= $displayBankName !== '' ? ' ' . $displayBankName : '';
        $bankBranchLine = trim(($displayBankName !== '' ? 'Bank ' . $displayBankName : 'Bank') . ($paymentBranchName !== '' ? ' ' . $paymentBranchName : ''));
        $hasPaymentInfo = $paymentAccountNumber !== '' || $paymentBankName !== '' || $paymentBranchName !== '' || $paymentAddress !== '';

        /* ===== Company identity for header/footer ===== */
        $companyName = strtoupper(trim((string) ($company->name ?? 'PT. PINK SERVICES INDONESIA')));
        $companyAddress = trim((string) ($company->address ?? 'Komplek Kedoya Center Blok C 8- 9 Jl. Raya Pejuangan No. 1 Kebon Jeruk. Jakarta 11530, Indonesia.'));
        $companyPhone = trim((string) ($company->phone ?? '+62 811-9350-083'));
        $companyEmail = trim((string) ($company->email ?? 'invoice@adsscent.com'));
        $companyWebsite = trim((string) ($company->website ?? 'www.adsscent.com'));
        $headOfficeLine = trim(collect([
            $companyAddress ? 'Head Office :  ' . $companyAddress : null,
            $companyPhone ? 'Whatsapp: ' . $companyPhone : null,
        ])->filter()->implode('  '));

        /* ===== NPWP / NIK (prefer invoice tax fields, fall back to customer) ===== */
        $npwpNumber = trim((string) ($invoice->npwp_number ?: $invoice->customer?->npwp ?: ''));
        $nikNumber = trim((string) ($invoice->customer?->nik ?? ''));

        /* ===== Bill To / Shipment addresses ===== */
        $billToName = trim((string) ($invoice->customer->name ?? '-'));
        $billToAddress = trim((string) ($invoice->billing_address ?: $invoice->customer?->address ?: '-'));

        // Compose the site/shipment address from the first job's building (same approach as CSR).
        $composeBuildingAddress = function ($building): ?string {
            if (!$building) {
                return null;
            }
            $parts = collect([
                $building->alamat_1 ?? $building->address ?? null,
                data_get($building, 'alamat_2'),
                data_get($building, 'subdistrict.name'),
                data_get($building, 'district.name'),
                data_get($building, 'city.name'),
                data_get($building, 'province.name'),
                data_get($building, 'kode_pos') ?? data_get($building, 'postal_code'),
            ])->filter(fn ($p) => filled($p))->unique()->values();

            return $parts->isNotEmpty() ? $parts->implode(', ') : null;
        };

        $shipmentAddress = null;
        $firstRentalWithJob = $invoice->invoiceRentalDetails->first(fn ($d) => filled($d->job_no));
        if ($firstRentalWithJob && $firstRentalWithJob->jobSchedule) {
            $shipmentAddress = $composeBuildingAddress($firstRentalWithJob->jobSchedule->building);
        }
        $shipmentAddress = trim((string) (
            $shipmentAddress
            ?: $invoice->invoiceRentalDetails->pluck('building_name')->filter()->first()
            ?: $billToAddress
        ));

        /* ===== Periode: "N of Total ( start - end )" derived from contract rental periods ===== */
        $periodeText = trim((string) ($invoice->period_invoice ?? ''));
        try {
            $periodContract = $invoice->contract ?: $invoice->contractById;
            if ($periodContract && $invoice->period_invoice) {
                $allPeriods = collect(app(\App\Services\Finance\InvoiceGenerationService::class)
                    ->getRentalPeriodsForContract($periodContract->id));
                $totalPeriods = $allPeriods->count();
                $matchedIndex = $allPeriods->search(fn ($p) => ($p['rental_period'] ?? null) === $invoice->period_invoice);
                $matched = $matchedIndex !== false ? $allPeriods[$matchedIndex] : null;

                if ($matched) {
                    $seq = $matchedIndex + 1;
                    $startTxt = !empty($matched['period_start']) ? \Carbon\Carbon::parse($matched['period_start'])->format('d M Y') : null;
                    $endTxt = !empty($matched['period_end']) ? \Carbon\Carbon::parse($matched['period_end'])->format('d M Y') : null;
                    $rangeTxt = ($startTxt && $endTxt) ? " ( {$startTxt} - {$endTxt} )" : '';
                    $periodeText = "{$seq} of {$totalPeriods}{$rangeTxt}";
                }
            }
        } catch (\Throwable $e) {
            // Keep raw period_invoice string on any failure.
        }
        $periodeText = $periodeText !== '' ? $periodeText : '-';

        /* ===== Terbilang (spell amount in Indonesian) ===== */
        $terbilang = function ($number) use (&$terbilang) {
            $number = (int) abs($number);
            $words = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];

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
        $terbilangText = strtoupper(trim(preg_replace('/\s+/', ' ', $terbilang($invoice->grand_total)))) . '  RUPIAH';

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
                    \BaconQrCode\Common\ErrorCorrectionLevel::valueOf('M')
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
                            imagefilledrectangle($im, $x * $scale, $y * $scale, ($x + 1) * $scale - 1, ($y + 1) * $scale - 1, $black);
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

        $qrHeaderSrc = $qrPng($invoice->invoice_number ?: 'INVOICE', 78);
        $qrFooterSrc = $qrPng($companyWebsite !== '' ? $companyWebsite : 'https://www.adsscent.com', 56);

        /* ===== Group rental rows by building ===== */
        $rentalGroups = $invoice->invoiceRentalDetails
            ->groupBy(fn ($d) => trim((string) ($d->building_name ?? '')));
        $displayedRentalNames = $invoice->invoiceRentalDetails->pluck('rental_name')->filter()->toArray();
    @endphp

    <!-- ===== Letterhead ===== -->
    <table class="letterhead">
        <tr>
            <td style="width: 30%;">
                <img src="{{ $logoSrc }}" class="logo" alt="Logo" onerror="this.style.display='none'">
            </td>
            <td style="width: 45%;">
                <div class="doc-title">CUSTOMER INVOICE</div>
            </td>
            <td style="width: 25%;">
                <div class="company-name">{{ $companyName }}</div>
                <div class="header-qr">
                    @if($qrHeaderSrc)
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
                <div class="lbl">Bill To&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</div>
                <div class="party-name">{{ strtoupper($billToName) }}</div>
                <div class="party-addr">{{ $billToAddress }}</div>

                <div class="lbl">Invoice Shipment to :</div>
                <div class="party-addr">{{ $shipmentAddress }}</div>
            </td>
            <td class="info-right">
                <table class="meta-table">
                    <tr>
                        <td class="meta-label">Invoice Number</td>
                        <td class="meta-sep">:</td>
                        <td>{{ $invoice->invoice_number }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Date</td>
                        <td class="meta-sep">:</td>
                        <td>{{ $invoice->invoice_date ? $invoice->invoice_date->format('d M Y') : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Contract Number</td>
                        <td class="meta-sep">:</td>
                        <td>{{ $invoice->contract_number ?: ($invoice->contract?->contract_number ?? '-') }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">NPWP</td>
                        <td class="meta-sep">:</td>
                        <td>{{ $npwpNumber !== '' ? $npwpNumber : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">NIK</td>
                        <td class="meta-sep">:</td>
                        <td>{{ $nikNumber !== '' ? $nikNumber : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Periode</td>
                        <td class="meta-sep">:</td>
                        <td>{{ $periodeText }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- ===== Items table ===== -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 18%;">Reference</th>
                <th style="width: 22%;">Item</th>
                <th style="width: 30%;">Room</th>
                <th style="width: 7%;" class="center">Qty</th>
                <th style="width: 11%;" class="right">Price</th>
                <th style="width: 12%;" class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rentalGroups as $buildingName => $rentals)
                @if(trim((string) $buildingName) !== '')
                <tr class="building-row">
                    <td colspan="6">{{ $buildingName }}</td>
                </tr>
                @endif
                @foreach($rentals as $rental)
                <tr>
                    <td>{{ $rental->job_no ?: '-' }}</td>
                    <td>{{ $rental->rental_name ?? $rental->masterRental->rental_name ?? '-' }}</td>
                    <td>{{ $rental->room_name ?: ($rental->jobSchedule->room->room_name ?? '-') }}</td>
                    <td class="center">{{ number_format($rental->quantity, 0) }}</td>
                    <td class="right">{{ number_format($rental->unit_price, 0) }}</td>
                    <td class="right">{{ number_format($rental->total_price, 0) }}</td>
                </tr>
                @endforeach
            @empty
            @endforelse

            @foreach($invoice->invoiceDetails as $item)
            @php
                // Skip rows already represented by a rental detail (legacy duplicate records).
                $isDuplicate = in_array($item->description, $displayedRentalNames);
            @endphp
            @if(!$isDuplicate && $item->description)
            <tr>
                <td>-</td>
                <td>{{ $item->description }}</td>
                <td>-</td>
                <td class="center">{{ number_format($item->quantity, 0) }}</td>
                <td class="right">{{ number_format($item->unit_price, 0) }}</td>
                <td class="right">{{ number_format($item->total_price, 0) }}</td>
            </tr>
            @endif
            @endforeach
        </tbody>
    </table>

    <!-- ===== Totals ===== -->
    <table class="totals-wrap">
        <tr class="rule-top"><td></td></tr>
        <tr>
            <td style="width: 50%; vertical-align: top;">&nbsp;</td>
            <td style="width: 50%;">
                <table class="totals-table">
                    <tr>
                        <td class="t-label">TOTAL</td>
                        <td class="t-sep">:</td>
                        <td class="t-value">{{ number_format($invoice->subtotal, 0) }}</td>
                    </tr>
                    @if($invoice->discount_amount > 0)
                    <tr>
                        <td class="t-label">DISCOUNT</td>
                        <td class="t-sep">:</td>
                        <td class="t-value">-{{ number_format($invoice->discount_amount, 0) }}</td>
                    </tr>
                    @endif
                    @if($showTaxRow)
                    <tr>
                        <td class="t-label">PPN</td>
                        <td class="t-sep">:</td>
                        <td class="t-value">{{ number_format($invoice->tax_amount, 0) }}</td>
                    </tr>
                    @endif
                </table>
            </td>
        </tr>
        <tr class="rule-bottom"><td></td></tr>
        <tr>
            <td class="terbilang">
                <span class="t-key">Terbilang</span>&nbsp;&nbsp;:&nbsp;&nbsp;{{ $terbilangText }}
            </td>
            <td>
                <table class="totals-table">
                    <tr>
                        <td class="t-label">GRAND TOTAL</td>
                        <td class="t-sep">:</td>
                        <td class="t-value">{{ number_format($invoice->grand_total, 0) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- ===== Payment info ===== -->
    @if($hasPaymentInfo)
    <div class="payment-info">
        @if($paymentAccountName)
            <div class="payment-info-title">Payment to :  {{ $paymentAccountName }}</div>
        @endif
        @if($paymentAccountNumber)
            <div class="payment-info-row">{{ $paymentNumberLabel }} :  {{ $paymentAccountNumber }}</div>
        @endif
        @if($paymentBranchName || $paymentBankName)
            <div class="payment-info-row">{{ $bankBranchLine }}</div>
        @endif
        @if($paymentAddress)
            <div class="payment-info-row">{{ $paymentAddress }}</div>
        @endif
        @if($companyEmail)
            <div class="payment-info-row payment-email" style="margin-top: 16px;">Email:&nbsp; <a href="mailto:{{ $companyEmail }}">{{ $companyEmail }}</a></div>
        @endif
    </div>
    @endif

    <div class="generated-note">
        This Invoice Report was generated on {{ now()->format('d M Y H:i:s') }} and is valid without the signature and seal.
    </div>

    <!-- ===== Fixed footer ===== -->
    <div class="page-footer">
        <table class="footer-grid">
            <tr>
                <td style="width: 16%;">
                    <div class="find-us">Find Us More Info<br>Scan Below</div>
                    @if($qrFooterSrc)
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
                    @if($sealSrc)
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

    {{-- DomPDF page numbering: rendered bottom-right inside the fixed footer band --}}
    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->get_font("DejaVu Sans", "bold");
            $size = 9;
            $w = $pdf->get_width();
            $h = $pdf->get_height();
            $text = "Page {PAGE_NUM} Of {PAGE_COUNT}";
            $textWidth = $fontMetrics->get_text_width($text, $font, $size);
            $pdf->page_text($w - 30 - $textWidth, $h - 20, $text, $font, $size, array(0.33, 0.33, 0.33));
        }
    </script>
</body>
</html>
