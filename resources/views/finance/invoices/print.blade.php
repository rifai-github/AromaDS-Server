<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        .header { width: 100%; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 20px; }
        .logo { font-size: 24px; font-weight: bold; color: #1e3a8a; }
        .invoice-title { float: right; font-size: 24px; font-weight: bold; }
        .info-table { width: 100%; margin-bottom: 20px; }
        .info-table td { vertical-align: top; padding: 5px; }
        .details-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .details-table th { background-color: #f8f9fa; border-bottom: 2px solid #ddd; padding: 10px; text-align: left; }
        .details-table td { border-bottom: 1px solid #eee; padding: 10px; }
        .totals-table { width: 400px; margin-left: auto; border-collapse: collapse; }
        .totals-table td { padding: 5px 10px; }
        .totals-table .label { font-weight: bold; text-align: right; }
        .totals-table .value { text-align: right; }
        .total-row { font-size: 14px; border-top: 2px solid #333; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">AROMA</div>
        <div class="invoice-title">INVOICE</div>
        <div style="clear: both;"></div>
    </div>

    <table class="info-table">
        <tr>
            <td width="50%">
                <strong>Bill To:</strong><br>
                {{ $invoice->customer->name }}<br>
                {{ $invoice->customer->address }}<br>
                {{ $invoice->customer->city }}
            </td>
            <td width="50%" align="right">
                <strong>Invoice Number:</strong> {{ $invoice->invoice_number }}<br>
                <strong>Date:</strong> {{ $invoice->invoice_date->format('d M Y') }}<br>
                <strong>Due Date:</strong> {{ $invoice->due_date->format('d M Y') }}<br>
                <strong>Contract:</strong> {{ $invoice->contract_number }}
            </td>
        </tr>
    </table>

    <table class="details-table">
        <thead>
            <tr>
                <th>Description</th>
                <th width="15%" style="text-align: right;">Price</th>
                <th width="10%" style="text-align: center;">Qty</th>
                <th width="15%" style="text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->invoiceDetails as $detail)
            <tr>
                <td>{{ $detail->description }}</td>
                <td align="right">Rp {{ number_format($detail->unit_price, 0, ',', '.') }}</td>
                <td align="center">{{ $detail->quantity }}</td>
                <td align="right">Rp {{ number_format($detail->total_price, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td class="label">Subtotal</td>
            <td class="value">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="label">PPN (Tax)</td>
            <td class="value">Rp {{ number_format($invoice->tax_amount, 0, ',', '.') }}</td>
        </tr>
        @if($invoice->discount_amount > 0)
        <tr>
            <td class="label">Discount</td>
            <td class="value text-danger">- Rp {{ number_format($invoice->discount_amount, 0, ',', '.') }}</td>
        </tr>
        @endif
        <tr class="total-row">
            <td class="label">Total Amount</td>
            <td class="value"><strong>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</strong></td>
        </tr>
    </table>
    
    @if($invoice->internal_notes)
    <div style="margin-top: 30px; border-top: 1px solid #ddd; padding-top: 10px;">
        <strong>Notes:</strong><br>
        {{ $invoice->internal_notes }}
    </div>
    @endif
</body>
</html>
