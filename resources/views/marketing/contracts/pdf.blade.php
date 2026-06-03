<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contract - {{ $contract->contract_number }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
            background: #fff;
        }
        
        .header {
            text-align: center;
            border-bottom: 3px solid #214589;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #214589;
            font-size: 28px;
            margin: 0;
            font-weight: bold;
        }
        
        .header h2 {
            color: #666;
            font-size: 18px;
            margin: 10px 0 0 0;
            font-weight: normal;
        }
        
        .contract-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .contract-info h3 {
            color: #214589;
            margin-top: 0;
            font-size: 18px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-weight: bold;
            color: #214589;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 14px;
            color: #333;
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .section {
            margin-bottom: 30px;
        }
        
        .section h3 {
            color: #214589;
            font-size: 18px;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 2px solid #214589;
        }
        
        .parties {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .party {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #214589;
        }
        
        .party h4 {
            color: #214589;
            margin-top: 0;
            margin-bottom: 15px;
        }
        
        .party-details {
            font-size: 14px;
            line-height: 1.5;
        }
        
        .terms {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
        }
        
        .terms h4 {
            color: #214589;
            margin-top: 0;
            margin-bottom: 15px;
        }
        
        .terms-content {
            font-size: 14px;
            line-height: 1.6;
            white-space: pre-line;
        }
        
        .financial-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .financial-summary h3 {
            color: #214589;
            margin-top: 0;
            margin-bottom: 15px;
        }
        
        .financial-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .financial-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .financial-label {
            font-weight: bold;
            color: #333;
        }
        
        .financial-value {
            color: #214589;
            font-weight: bold;
        }
        
        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            margin-top: 50px;
            padding-top: 30px;
            border-top: 2px solid #e0e0e0;
        }
        
        .signature-block {
            text-align: center;
        }
        
        .signature-line {
            border-bottom: 1px solid #333;
            height: 40px;
            margin-bottom: 10px;
        }
        
        .signature-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .signature-name {
            font-weight: bold;
            color: #214589;
        }
        
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        
        .qr-code {
            text-align: center;
            margin: 20px 0;
        }
        
        .qr-code img {
            max-width: 150px;
            height: auto;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-expired {
            background: #f8d7da;
            color: #721c24;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 15px;
            }
            
            .header {
                page-break-after: avoid;
            }
            
            .section {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>CONTRACT AGREEMENT</h1>
        <h2>{{ $contract->contract_number }}</h2>
    </div>

    <!-- Contract Information -->
    <div class="contract-info">
        <h3>Contract Details</h3>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Contract Number</div>
                <div class="info-value">{{ $contract->contract_number }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Contract Date</div>
                <div class="info-value">{{ $contract->contract_date ? $contract->contract_date->format('d/M/Y') : 'N/A' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Start Date</div>
                <div class="info-value">{{ $contract->actual_start_date ? $contract->actual_start_date->format('d/M/Y') : 'N/A' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">End Date</div>
                <div class="info-value">{{ $contract->actual_end_date ? $contract->actual_end_date->format('d/M/Y') : 'N/A' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Contract Type</div>
                <div class="info-value">{{ ucfirst($contract->contract_type ?? 'New') }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Status</div>
                <div class="info-value">
                    <span class="status-badge status-{{ strtolower($contract->contract_status ?? 'pending') }}">
                        {{ ucfirst($contract->contract_status ?? 'Draft') }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Parties -->
    <div class="section">
        <h3>Contracting Parties</h3>
        <div class="parties">
            <div class="party">
                <h4>Company (AROMA)</h4>
                <div class="party-details">
                    <strong>PT. AROMA INDONESIA</strong><br>
                    Address: Jakarta, Indonesia<br>
                    Phone: +62-21-12345678<br>
                    Email: info@aroma.com<br>
                    NPWP: 01.234.567.8-901.000
                </div>
            </div>
            <div class="party">
                <h4>Customer</h4>
                <div class="party-details">
                    <strong>{{ $contract->customer->name ?? 'N/A' }}</strong><br>
                    Address: {{ $contract->customer->address ?? 'N/A' }}<br>
                    Phone: {{ $contract->customer->phone ?? 'N/A' }}<br>
                    Email: {{ $contract->customer->email ?? 'N/A' }}<br>
                    NPWP: {{ $contract->billingGroup->tax_number ?? $contract->billingGroup->npwp_number ?? $contract->customer->npwp ?? 'N/A' }}
                </div>
            </div>
        </div>
    </div>

    <!-- Financial Summary -->
    <div class="financial-summary">
        <h3>Financial Summary</h3>
        <div class="financial-grid">
            <div class="financial-item">
                <span class="financial-label">Contract Value:</span>
                <span class="financial-value">Rp {{ number_format($contract->contract_value ?? 0, 0, ',', '.') }}</span>
            </div>
            <div class="financial-item">
                <span class="financial-label">Payment Terms:</span>
                <span class="financial-value">{{ \App\Models\Quotation::formatTermsOfPaymentLabel($contract->term_of_payment ?? ($contract->quotation->terms_of_payment ?? $contract->payment_terms) ?? null) }}</span>
            </div>
            <div class="financial-item">
                <span class="financial-label">Contract Period:</span>
                <span class="financial-value">
                    @if($contract->actual_start_date && $contract->actual_end_date)
                        {{ $contract->actual_start_date->format('d/M/Y') }} - {{ $contract->actual_end_date->format('d/M/Y') }}
                    @else
                        N/A
                    @endif
                </span>
            </div>
            <div class="financial-item">
                <span class="financial-label">Marketing Staff:</span>
                <span class="financial-value">{{ $contract->marketing->name ?? 'N/A' }}</span>
            </div>
        </div>
    </div>

    <!-- Contract Terms -->
    <div class="section">
        <h3>Terms and Conditions</h3>
        <div class="terms">
            <h4>Contract Terms</h4>
            <div class="terms-content">{{ $contract->contract_terms ?? 'No specific terms and conditions provided.' }}</div>
        </div>
    </div>

    <!-- Signing Information -->
    @if($contract->signatory_name || $contract->marketing_name)
    <div class="section">
        <h3>Signing Information</h3>
        <div class="info-grid">
            @if($contract->signatory_name)
            <div class="info-item">
                <div class="info-label">Customer Signatory</div>
                <div class="info-value">{{ $contract->signatory_name }}</div>
            </div>
            @endif
            @if($contract->signatory_position)
            <div class="info-item">
                <div class="info-label">Position</div>
                <div class="info-value">{{ $contract->signatory_position }}</div>
            </div>
            @endif
            @if($contract->signatory_npwp)
            <div class="info-item">
                <div class="info-label">NPWP</div>
                <div class="info-value">{{ $contract->signatory_npwp }}</div>
            </div>
            @endif
            @if($contract->marketing_name)
            <div class="info-item">
                <div class="info-label">Marketing Representative</div>
                <div class="info-value">{{ $contract->marketing_name }}</div>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- QR Code -->
    @if($contract->qr_code)
    <div class="qr-code">
        <h4>Contract QR Code</h4>
        <div>QR Code: {{ $contract->qr_code }}</div>
    </div>
    @endif

    <!-- Signatures -->
    <div class="signatures">
        <div class="signature-block">
            <div class="signature-label">Customer Signature</div>
            <div class="signature-line"></div>
            <div class="signature-name">{{ $contract->signatory_name ?? '_________________' }}</div>
            <div style="font-size: 12px; color: #666; margin-top: 5px;">
                {{ $contract->signatory_position ?? 'Position' }}<br>
                Date: {{ $contract->customer_signed_at ? $contract->customer_signed_at->format('d/M/Y') : '_____________' }}
            </div>
        </div>
        <div class="signature-block">
            <div class="signature-label">Company Signature</div>
            <div class="signature-line"></div>
            <div class="signature-name">{{ $contract->marketing_name ?? 'AROMA Representative' }}</div>
            <div style="font-size: 12px; color: #666; margin-top: 5px;">
                Marketing Staff<br>
                Date: {{ $contract->staff_signed_at ? $contract->staff_signed_at->format('d/M/Y') : '_____________' }}
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>This contract is generated electronically and is legally binding.</p>
        <p>Generated on: {{ now()->format('d/M/Y H:i:s') }}</p>
        <p>Contract ID: {{ $contract->id }} | Version: 1.0</p>
    </div>
</body>
</html>
