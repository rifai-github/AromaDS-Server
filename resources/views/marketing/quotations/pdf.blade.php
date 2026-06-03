<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation {{ $quotation->quotation_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
        }
        
        .company-logo {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 10px;
        }
        
        .quotation-title {
            font-size: 20px;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .quotation-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            gap: 20px;
        }
        
        .info-section {
            width: 48%;
            flex: 1;
        }
        
        .info-section h3 {
            background-color: #f8f9fa;
            padding: 8px;
            margin: 0 0 10px 0;
            font-size: 14px;
            border-left: 4px solid #007bff;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 5px;
        }
        
        .info-label {
            font-weight: bold;
            width: 120px;
        }
        
        .info-value {
            flex: 1;
        }
        
        .quotation-details {
            margin-bottom: 30px;
        }
        
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .details-table th,
        .details-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        
        .details-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        
        .financial-summary {
            margin-top: 30px;
            text-align: right;
            max-width: 300px;
            margin-left: auto;
        }
        
        .financial-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }
        
        .financial-label {
            font-weight: bold;
            text-align: left;
        }
        
        .financial-value {
            min-width: 120px;
            text-align: right;
            font-weight: bold;
        }
        
        .total-row {
            border-top: 2px solid #333;
            font-weight: bold;
            font-size: 14px;
            padding-top: 10px;
            margin-top: 10px;
        }
        
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        
        .notes-section {
            margin-top: 30px;
        }
        
        .notes-section h3 {
            background-color: #f8f9fa;
            padding: 8px;
            margin: 0 0 10px 0;
            font-size: 14px;
            border-left: 4px solid #28a745;
        }
        
        .notes-section p {
            margin: 0 0 15px 0;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 4px;
            border-left: 3px solid #28a745;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-logo">AROMA</div>
        <div class="quotation-title">QUOTATION</div>
        <div>No: {{ $quotation->quotation_number }}</div>
    </div>

    <div class="quotation-info">
        <div class="info-section">
            <h3>Company Information</h3>
            <div class="info-row">
                <div class="info-label">Company:</div>
                <div class="info-value">{{ $quotation->company_name ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">PIC Name:</div>
                <div class="info-value">{{ $quotation->pic_name ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Marketing:</div>
                <div class="info-value">{{ $quotation->marketing->name ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Quotation Date:</div>
                <div class="info-value">{{ $quotation->quotation_date ? \Carbon\Carbon::parse($quotation->quotation_date)->format('d/M/Y') : '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Valid Until:</div>
                <div class="info-value">{{ $quotation->valid_until ? \Carbon\Carbon::parse($quotation->valid_until)->format('d/M/Y') : '-' }}</div>
            </div>
        </div>

        <div class="info-section">
            <h3>Quotation Details</h3>
            <div class="info-row">
                <div class="info-label">Type:</div>
                <div class="info-value">{{ ucfirst($quotation->quotation_type ?? 'New') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Rental Period:</div>
                <div class="info-value">{{ $quotation->rental_period ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Payment Method:</div>
                <div class="info-value">{{ $quotation->billing_methods ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Term of Payment:</div>
                <div class="info-value">{{ $quotation->terms_of_payment_label }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Status:</div>
                <div class="info-value">{{ ucfirst(str_replace('_', ' ', $quotation->status ?? 'Draft')) }}</div>
            </div>
        </div>
    </div>

    @if($quotation->quotationDetails && $quotation->quotationDetails->count() > 0)
    <div class="quotation-details">
        <h3>Quotation Items</h3>
        <table class="details-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Room Name</th>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Total Price</th>
                </tr>
            </thead>
            <tbody>
                @foreach($quotation->quotationDetails as $index => $detail)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $detail->room_name ?? '-' }}</td>
                    <td>{{ $detail->rental_alias ?? ($detail->masterRental->rental_name ?? '-') }}</td>
                    <td>{{ $detail->quantity ?? 0 }}</td>
                    <td>Rp {{ number_format($detail->unit_price ?? 0, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($detail->total_price ?? 0, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="financial-summary">
        <div class="financial-row">
            <div class="financial-label">Sub Total:</div>
            <div class="financial-value">Rp {{ number_format($quotation->total_amount ?? 0, 0, ',', '.') }}</div>
        </div>
        <div class="financial-row">
            <div class="financial-label">PPN ({{ $quotation->tax_amount > 0 ? '11%' : '0%' }}):</div>
            <div class="financial-value">Rp {{ number_format($quotation->tax_amount ?? 0, 0, ',', '.') }}</div>
        </div>
        <div class="financial-row total-row">
            <div class="financial-label">TOTAL PENAWARAN:</div>
            <div class="financial-value">Rp {{ number_format($quotation->grand_total ?? 0, 0, ',', '.') }}</div>
        </div>
    </div>

    @if($quotation->internal_notes || $quotation->additional_notes)
    <div class="notes-section">
        @if($quotation->internal_notes)
        <h3>Internal Notes</h3>
        <p>{{ $quotation->internal_notes }}</p>
        @endif
        
        @if($quotation->additional_notes)
        <h3>Additional Notes</h3>
        <p>{{ $quotation->additional_notes }}</p>
        @endif
    </div>
    @endif

    <div class="footer">
        <p>This quotation is generated automatically on {{ now()->format('d/M/Y H:i') }}</p>
        <p>For any questions, please contact our marketing team.</p>
    </div>
</body>
</html>
