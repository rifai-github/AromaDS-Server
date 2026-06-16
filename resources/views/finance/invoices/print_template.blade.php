<!DOCTYPE html>
<html>
<head>
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #333; }
        .page-break { page-break-after: always; }
        
        .header-table { width: 100%; margin-bottom: 20px; border-bottom: 2px solid #214589; padding-bottom: 10px; }
        .header-table td { vertical-align: top; }
        
        .logo { width: 150px; }
        
        .title { font-size: 20px; font-weight: bold; color: #214589; text-align: right; }
        .doc-number { font-size: 14px; font-weight: bold; text-align: right; margin-top: 5px; }
        
        .info-table { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        .info-table td { padding: 5px; vertical-align: top; }
        .label { font-weight: bold; width: 120px; color: #555; }
        
        .items-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .items-table th { background-color: #214589; color: white; padding: 8px; text-align: left; }
        .items-table td { border: 1px solid #ddd; padding: 8px; vertical-align: top; }
        .items-table .total-row td { border: none; font-weight: bold; padding-top: 5px; }
        .payment-info { margin-top: 32px; line-height: 1.45; }
        .payment-info-title { font-weight: bold; margin-bottom: 6px; }
        .payment-info-row { margin-bottom: 2px; }
        
        .center { text-align: center; }
        .right { text-align: right; }
        
        .footer { position: fixed; bottom: 30px; left: 0; right: 0; text-align: center; font-size: 10px; color: #888; border-top: 1px solid #eee; padding-top: 10px; }
        
        @media print {
            .footer { position: fixed; bottom: 0; }
        }
    </style>
</head>
<body onload="window.print()">
    @php
        $company = \App\Models\Company::first();
        $taxCodeRule = $invoice->tax_code
            ? \App\Models\FinanceTaxCode::where('code', $invoice->tax_code)->first()
            : null;
        $taxRate = (float) ($invoice->taxSetting->tax_rate ?? 0);
        $showTaxRow = $invoice->tax_amount > 0 || ($taxCodeRule && $taxCodeRule->hasZeroTaxPrint());
        $taxRowLabel = $showTaxRow
            ? ($taxCodeRule && $taxCodeRule->hasZeroTaxPrint()
                ? 'PPN (0%)'
                : 'PPN (' . number_format($taxRate, 2) . '%)')
            : null;

        $invoiceSignatoryValue = function (string $key, ?string $default = null): ?string {
            $keyColumn = \Illuminate\Support\Facades\Schema::hasColumn('system_settings', 'setting_key')
                ? 'setting_key'
                : (\Illuminate\Support\Facades\Schema::hasColumn('system_settings', 'key') ? 'key' : null);
            $valueColumn = \Illuminate\Support\Facades\Schema::hasColumn('system_settings', 'setting_value')
                ? 'setting_value'
                : (\Illuminate\Support\Facades\Schema::hasColumn('system_settings', 'value') ? 'value' : null);

            if (!$keyColumn || !$valueColumn) {
                return $default;
            }

            $setting = \App\Models\SystemSetting::active()->where($keyColumn, $key)->first();
            $value = $setting?->getRawOriginal($valueColumn);

            return filled($value) ? trim((string) $value) : $default;
        };

        $invoiceBranch = $invoice->resolvedInvoiceBranch();
        $branchAuthorizedUser = $invoiceBranch?->invoiceAuthorizedByUser;
        $authorizedName = filled($branchAuthorizedUser?->name)
            ? trim((string) $branchAuthorizedUser->name)
            : $invoiceSignatoryValue('invoice_authorized_by_name', 'Manager Finance');
        $authorizedPosition = filled($branchAuthorizedUser?->position_name)
            ? trim((string) $branchAuthorizedUser->position_name)
            : $invoiceSignatoryValue('invoice_authorized_by_position');

        $billingGroup = $invoice->billingGroup ?: $invoice->contract?->billingGroup;
        $rawPaymentAccount = trim((string) ($invoice->virtual_account_number ?: $billingGroup?->virtual_account_number ?: ''));
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
    @endphp

    <!-- Header -->
    <table class="header-table">
        <tr>
            <td style="width: 50%;">
                @php
                    $logoPath = public_path('images/logo.png');
                    $logoSrc = asset('images/logo.png'); // Default fallback
                    if (file_exists($logoPath)) {
                        $type = pathinfo($logoPath, PATHINFO_EXTENSION);
                        $data = file_get_contents($logoPath);
                        $logoSrc = 'data:image/' . $type . ';base64,' . base64_encode($data);
                    }
                @endphp
                <img src="{{ $logoSrc }}" class="logo" alt="Logo" onerror="this.style.display='none'">
                <div style="margin-top: 10px; font-size: 10px;">
                    <strong>{{ $company->name ?? config('app.name') }}</strong><br>
                    {!! nl2br(e($company->address ?? '')) !!}<br>
                    Phone: {{ $company->phone ?? '-' }} | Email: {{ $company->email ?? '-' }}
                </div>
            </td>
            <td style="width: 50%; text-align: right;">
                <div class="title">INVOICE</div>
                <div class="doc-number">{{ $invoice->invoice_number }}</div>
            </td>
        </tr>
    </table>

    <!-- Info -->
    <table class="info-table">
        <tr>
            <td class="label">Customer:</td>
            <td>{{ $invoice->customer->name }}</td>
            <td class="label">Invoice Date:</td>
            <td>{{ $invoice->invoice_date ? $invoice->invoice_date->format('d/M/Y') : '-' }}</td>
        </tr>
        <tr>
            <td class="label">Address:</td>
            <td>{{ $invoice->billing_address ?? $invoice->customer->billing_address ?? '-' }}</td>
            <td class="label">Due Date:</td>
            <td>{{ $invoice->due_date ? $invoice->due_date->format('d/M/Y') : '-' }}</td>
        </tr>
        <tr>
            <td class="label">Attn:</td>
            <td>{{ $invoice->pic_name ?? '-' }}</td>
            <td class="label">Status:</td>
            <td>{{ ucfirst($invoice->invoice_status) }}</td>
        </tr>
        <tr>
            <td class="label">Tax Code:</td>
            <td>{{ $invoice->tax_code ?? '-' }}</td>
            <td class="label">Tax Rule:</td>
            <td>{{ $taxCodeRule?->customer_status ?? '-' }}</td>
        </tr>
    </table>

    <!-- Items List -->
    <h3>Invoice Details</h3>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;" class="center">No</th>
                <th style="width: 40%;">Description</th>
                <th style="width: 15%;" class="right">Price</th>
                <th style="width: 10%;" class="center">Qty</th>
                <th style="width: 10%;" class="center">Qty Free</th>
                <th style="width: 20%;" class="right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $rowNumber = 1;
                $displayedRentalNames = $invoice->invoiceRentalDetails->pluck('rental_name')->toArray();
            @endphp

            @foreach($invoice->invoiceRentalDetails as $rental)
            <tr>
                <td class="center">{{ $rowNumber++ }}</td>
                <td>
                    {{ $rental->rental_name ?? $rental->masterRental->rental_name ?? '-' }}
                    <br><small style="color: #666;">
                        {{ $rental->room_name ?: ($rental->jobSchedule->room->room_name ?? '-') }} 
                        @if($rental->job_no) (Job: {{ $rental->job_no }}) @endif
                    </small>
                </td>
                <td class="right">{{ number_format($rental->unit_price, 0) }}</td>
                <td class="center">{{ number_format($rental->quantity, 0) }}</td>
                <td class="center">{{ number_format($rental->qty_free ?? 0, 0) }}</td>
                <td class="right">{{ number_format($rental->total_price, 0) }}</td>
            </tr>
            @endforeach

            @foreach($invoice->invoiceDetails as $item)
            @php
                // NEW: Duplicate Check
                // Many old invoices (like #24) had identical records in both tables.
                // We skip invoiceDetails if the description matches a rental already shown above.
                $isDuplicate = in_array($item->description, $displayedRentalNames);
            @endphp
            @if(!$isDuplicate && $item->description)
            <tr>
                <td class="center">{{ $rowNumber++ }}</td>
                <td>
                    {{ $item->description }}
                </td>
                <td class="right">{{ number_format($item->unit_price, 0) }}</td>
                <td class="center">{{ number_format($item->quantity, 0) }}</td>
                <td class="center">0</td>
                <td class="right">{{ number_format($item->total_price, 0) }}</td>
            </tr>
            @endif
            @endforeach
            
            <tr class="total-row">
                <td colspan="4"></td>
                <td class="right">Subtotal:</td>
                <td class="right">{{ number_format($invoice->subtotal, 0) }}</td>
            </tr>
            @if($invoice->discount_amount > 0)
            <tr class="total-row">
                <td colspan="4"></td>
                <td class="right">Discount:</td>
                <td class="right">-{{ number_format($invoice->discount_amount, 0) }}</td>
            </tr>
            @endif
            @if($showTaxRow)
            <tr class="total-row">
                <td colspan="4"></td>
                <td class="right">{{ $taxRowLabel }}:</td>
                <td class="right">{{ number_format($invoice->tax_amount, 0) }}</td>
            </tr>
            @endif
            <tr class="total-row" style="font-size: 14px; color: #214589;">
                <td colspan="4"></td>
                <td class="right">Total:</td>
                <td class="right">Rp {{ number_format($invoice->grand_total, 0) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Payment Info / Notes -->
    @if($invoice->notes)
    <div style="margin-top: 30px;">
        <strong>Notes:</strong><br>
        <p style="margin-top: 5px; font-style: italic;">{{ $invoice->notes }}</p>
    </div>
    @endif

    @if($hasPaymentInfo)
    <div class="payment-info">
        @if($paymentAccountName)
            <div class="payment-info-title">Payment to : {{ $paymentAccountName }}</div>
        @endif
        @if($paymentAccountNumber)
            <div class="payment-info-row">{{ $paymentNumberLabel }} : {{ $paymentAccountNumber }}</div>
        @endif
        @if($paymentBranchName || $paymentBankName)
            <div class="payment-info-row">{{ $bankBranchLine }}</div>
        @endif
        @if($paymentAddress)
            <div class="payment-info-row">{{ $paymentAddress }}</div>
        @endif
        @if($company?->email)
            <div class="payment-info-row" style="margin-top: 14px;">Email: {{ $company->email }}</div>
        @endif
    </div>
    @endif

    <!-- Signatures -->
    <table style="width: 100%; margin-top: 50px; text-align: center;">
        <tr>
            <td style="width: 33%;">
                <p>Receiver</p>
                <br><br><br>
                <hr style="width: 80%; border-top: 1px solid #ccc;">
            </td>
            <td style="width: 33%;"></td>
            <td style="width: 33%;">
                <p>Authorized By</p>
                <br><br><br>
                <hr style="width: 80%; border-top: 1px solid #ccc;">
                @if($authorizedName)
                    <p style="margin-bottom: 2px;">{{ $authorizedName }}</p>
                @endif
                @if($authorizedPosition)
                    <p style="margin-top: 0; margin-bottom: 2px;">{{ $authorizedPosition }}</p>
                @endif
                <p>{{ $company->name ?? 'Management' }}</p>
            </td>
        </tr>
    </table>

    <div class="footer">
        Printed on {{ date('d/M/Y H:i') }} | Page 1 of 1
    </div>
</body>
</html>
