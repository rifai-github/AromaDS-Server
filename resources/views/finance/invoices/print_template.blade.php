<!DOCTYPE html>
<html>
<head>
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 20px 28px 52px 28px; }
        body { font-family: sans-serif; font-size: 10px; color: #333; }
        .page-break { page-break-after: always; }
        
        .header-table { width: 100%; margin-bottom: 12px; border-bottom: 2px solid #214589; padding-bottom: 8px; }
        .header-table td { vertical-align: top; }
        
        .logo { width: 118px; }
        
        .title { font-size: 18px; font-weight: bold; color: #214589; text-align: right; }
        .doc-number { font-size: 12px; font-weight: bold; text-align: right; margin-top: 4px; }
        
        .info-table { width: 100%; margin-bottom: 10px; border-collapse: collapse; }
        .info-table td { padding: 3px 5px; vertical-align: top; }
        .label { font-weight: bold; width: 120px; color: #555; }
        
        h3 { margin: 8px 0 6px; font-size: 13px; }
        .items-table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .items-table th { background-color: #214589; color: white; padding: 6px; text-align: left; }
        .items-table td { border: 1px solid #ddd; padding: 5px 6px; vertical-align: top; }
        .items-table .total-row td { border: none; font-weight: bold; padding-top: 4px; padding-bottom: 2px; }
        .notes { margin-top: 14px; }
        .notes p { margin-top: 4px; margin-bottom: 0; font-style: italic; }
        .payment-info { margin-top: 18px; line-height: 1.35; }
        .payment-info-title { font-weight: bold; margin-bottom: 4px; }
        .payment-info-row { margin-bottom: 2px; }
        .signature-table { width: 100%; margin-top: 18px; text-align: center; border-collapse: collapse; page-break-inside: avoid; }
        .signature-table td { width: 33%; vertical-align: top; }
        .signature-label { margin: 0 0 34px; }
        .signature-line { width: 80%; border-top: 1px solid #ccc; margin: 0 auto 5px; }
        .signature-name { margin: 0 0 2px; }
        .signature-company { margin: 0; }
        
        .center { text-align: center; }
        .right { text-align: right; }
        
        .footer { position: fixed; bottom: 14px; left: 0; right: 0; text-align: center; font-size: 9px; color: #888; border-top: 1px solid #eee; padding-top: 7px; }
        
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
    <div class="notes">
        <strong>Notes:</strong><br>
        <p>{{ $invoice->notes }}</p>
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
    <table class="signature-table">
        <tr>
            <td>
                <p class="signature-label">Receiver</p>
                <div class="signature-line"></div>
            </td>
            <td></td>
            <td>
                <p class="signature-label">Authorized By</p>
                <div class="signature-line"></div>
                @if($authorizedName)
                    <p class="signature-name">{{ $authorizedName }}</p>
                @endif
                @if($authorizedPosition)
                    <p class="signature-name">{{ $authorizedPosition }}</p>
                @endif
                <p class="signature-company">{{ $company->name ?? 'Management' }}</p>
            </td>
        </tr>
    </table>

    <div class="footer">
        Printed on {{ date('d/M/Y H:i') }} | Page 1 of 1
    </div>
</body>
</html>
