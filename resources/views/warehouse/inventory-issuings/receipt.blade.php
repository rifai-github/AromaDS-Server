@php
    $pageTitle = 'Print Receipt - ' . $issuing->issuing_number;
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $pageTitle }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #000;
            background: #fff;
        }
        
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            border: 2px solid #000;
            background: #fff;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #000;
        }
        
        .logo {
            width: 120px;
            height: auto;
        }
        
        .title-section {
            text-align: center;
            flex: 1;
            padding: 0 20px;
        }
        
        .title-section h1 {
            font-size: 16pt;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .title-section .doc-number {
            font-size: 14pt;
            font-weight: bold;
            color: #333;
        }
        
        .qr-code {
            width: 100px;
            height: 100px;
        }
        
        .info-section {
            margin-bottom: 15px;
            padding: 10px 0;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 8px;
        }
        
        .info-label {
            width: 150px;
            font-weight: bold;
        }
        
        .info-value {
            flex: 1;
        }
        
        .items-section {
            margin: 20px 0;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .items-table th {
            background-color: #f0f0f0;
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            font-size: 10pt;
        }
        
        .items-table td {
            border: 1px solid #000;
            padding: 8px;
            font-size: 10pt;
        }
        
        .items-table .item-no {
            width: 40px;
            text-align: center;
        }
        
        .items-table .item-name {
            width: auto;
        }
        
        .items-table .item-ref {
            width: 120px;
            font-size: 9pt;
            color: #666;
        }
        
        .items-table .item-qty {
            width: 80px;
            text-align: center;
        }
        
        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ccc;
        }
        
        .signature-box {
            width: 45%;
            text-align: center;
        }
        
        .signature-label {
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .signature-line {
            border-bottom: 1px solid #000;
            height: 60px;
            margin-bottom: 10px;
        }
        
        .signature-name {
            font-weight: bold;
            font-size: 12pt;
        }
        
        .signature-role {
            font-size: 9pt;
            color: #666;
            margin-top: 3px;
        }
        
        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .no-print {
                display: none !important;
            }
            
            .receipt-container {
                border: none;
                padding: 0;
            }
        }
        
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            z-index: 1000;
        }
        
        .print-button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <button class="print-button no-print" onclick="window.print()">🖨️ Print Receipt</button>
    
    <div class="receipt-container">
        <!-- Header -->
        <div class="header">
            <div>
                <img src="{{ asset('images/logo.png') }}" alt="Logo" class="logo">
            </div>
            <div class="title-section">
                <h1>Inventory Issuing</h1>
                <div class="doc-number">{{ $issuing->issuing_number }}</div>
            </div>
            <div>
                {!! QrCode::size(100)->generate(route('warehouse.inventory-issuings.show', $issuing->id)) !!}
            </div>
        </div>
        
        <!-- Info Section -->
        <div class="info-section">
            <div class="info-row">
                <div class="info-label">Branch:</div>
                <div class="info-value">{{ $issuing->branch->name ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Warehouse:</div>
                <div class="info-value">{{ $issuing->warehouse->name ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Issue Date:</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($issuing->issue_date)->format('d M Y') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Reference No:</div>
                <div class="info-value">{{ $issuing->reference_no ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Status:</div>
                <div class="info-value">{{ ucfirst($issuing->status) }}</div>
            </div>
        </div>
        
        <!-- Items Section -->
        <div class="items-section">
            <h3 style="margin-bottom: 10px;">Produk</h3>
            <table class="items-table">
                <thead>
                    <tr>
                        <th class="item-no">No</th>
                        <th class="item-name">Product</th>
                        <th class="item-qty">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($issuing->items as $index => $item)
                    <tr>
                        <td class="item-no">{{ $index + 1 }}</td>
                        <td class="item-name">
                            <strong>{{ $item->product->name ?? 'Unknown Product' }}</strong>
                            @if($item->product->sku ?? false)
                            <br><span class="item-ref">Ref: {{ $item->product->sku }}</span>
                            @endif
                            @if($item->notes)
                            <br><span style="font-size: 9pt; color: #666;">{{ $item->notes }}</span>
                            @endif
                        </td>
                        <td class="item-qty">{{ number_format($item->quantity_requested, 0) }} pcs</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" style="text-align: center; padding: 20px; color: #999;">No items</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Signature Section -->
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-label">Diterima Oleh:</div>
                <div class="signature-line"></div>
                <div class="signature-name">{{ $issuing->receivedBy->name ?? '_______________' }}</div>
                @if($issuing->receivedBy)
                <div class="signature-role">({{ $issuing->receivedBy->department->name ?? 'Receiver' }})</div>
                @endif
            </div>
            <div class="signature-box">
                <div class="signature-label">Diserahkan oleh:</div>
                <div class="signature-line"></div>
                <div class="signature-name">{{ $issuing->issuedBy->name ?? $issuing->requestedBy->name ?? '_______________' }}</div>
                @if($issuing->issuedBy)
                <div class="signature-role">({{ $issuing->issuedBy->department->name ?? 'Issuer' }})</div>
                @endif
            </div>
        </div>
        
        @if($issuing->remarks)
        <div style="margin-top: 20px; padding: 10px; background-color: #f9f9f9; border-left: 3px solid #007bff;">
            <strong>Remarks:</strong><br>
            {{ $issuing->remarks }}
        </div>
        @endif
    </div>
    
    <script>
        // Auto-print on load (optional)
        // window.onload = function() { window.print(); };
    </script>
</body>
</html>
