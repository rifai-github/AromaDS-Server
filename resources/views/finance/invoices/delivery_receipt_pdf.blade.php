<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Tanda Terima {{ $invoice->invoice_number }}</title>
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
        
        .center { text-align: center; }
        .right { text-align: right; }
        
        .footer { position: fixed; bottom: 30px; left: 0; right: 0; text-align: center; font-size: 10px; color: #888; border-top: 1px solid #eee; padding-top: 10px; }
        
        @media print {
            .footer { position: fixed; bottom: 0; }
        }
    </style>
</head>
<body>
    @php $company = \App\Models\Company::first(); @endphp

    <!-- Header -->
    <table class="header-table">
        <tr>
            <td style="width: 50%;">
                <img src="{{ public_path('images/logo.png') }}" class="logo" alt="Logo" onerror="this.style.display='none'">
                <div style="margin-top: 10px; font-size: 10px;">
                    <strong>{{ $company->name ?? config('app.name') }}</strong><br>
                    {!! nl2br(e($company->address ?? '')) !!}<br>
                    Phone: {{ $company->phone ?? '-' }} | Email: {{ $company->email ?? '-' }}
                </div>
            </td>
            <td style="width: 50%; text-align: right;">
                <div class="title">TANDA TERIMA INVOICE</div>
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
            <td class="label">Total Amount:</td>
            <td>Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</td>
        </tr>
    </table>

    <!-- Document Checklist -->
    <h3>Dokumen yang Diserahkan</h3>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 10%;" class="center">No</th>
                <th style="width: 90%;">Nama Dokumen</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="center">1</td>
                <td>Invoice Asli No: {{ $invoice->invoice_number }}</td>
            </tr>
            @if($invoice->faktur_pajak)
            <tr>
                <td class="center">2</td>
                <td>Faktur Pajak Asli</td>
            </tr>
            @endif
            @if($invoice->contract)
            <tr>
                <td class="center">3</td>
                <td>Lampiran Kontrak / PO No: {{ $invoice->contract->contract_number }}</td>
            </tr>
            @endif
            @if($invoice->jobSchedules && $invoice->jobSchedules->count() > 0)
            <tr>
                <td class="center">4</td>
                <td>Berita Acara Pekerjaan ({{ $invoice->jobSchedules->count() }} dokumen)</td>
            </tr>
            @endif
        </tbody>
    </table>

    @if($invoice->catatan_pengiriman)
    <div style="margin-top: 20px; font-style: italic; border: 1px solid #ddd; padding: 10px; background-color: #f9f9f9;">
        <strong>Catatan Pengiriman:</strong><br>
        {{ $invoice->catatan_pengiriman }}
    </div>
    @endif

    <!-- Signatures -->
    <table style="width: 100%; margin-top: 50px; text-align: center;">
        <tr>
            <td style="width: 33%;">
                <p>Dikirim Oleh</p>
                <br><br><br>
                <hr style="width: 80%; border-top: 1px solid #ccc;">
                <p>{{ $invoice->dikirim_oleh }}</p>
                <p style="font-size: 10px;">{{ $invoice->dikirim_pada ? $invoice->dikirim_pada->format('d/M/Y H:i') : '' }}</p>
            </td>
            <td style="width: 33%;"></td>
            <td style="width: 33%;">
                <p>Diterima Oleh</p>
                <br><br><br>
                <hr style="width: 80%; border-top: 1px solid #ccc;">
                <p>{{ $invoice->diterima_oleh ?? '( ....................... )' }}</p>
                <p style="font-size: 10px;">{{ $invoice->pada ? $invoice->pada->format('d/M/Y H:i') : '' }}</p>
            </td>
        </tr>
    </table>

    <div class="footer">
        Dicetak pada: {{ now()->format('d/M/Y H:i') }} | Dokumen ini digenerate secara otomatis oleh sistem.
    </div>
</body>
</html>
