<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Survey Report - {{ $survey->survey_number }}</title>
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
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 20px;
        }
        
        .header h1 {
            color: #1e3a8a;
            margin: 0;
            font-size: 24px;
        }
        
        .header h2 {
            color: #666;
            margin: 5px 0 0 0;
            font-size: 16px;
            font-weight: normal;
        }
        
        .section {
            margin-bottom: 15px;
        }
        
        .section-title {
            background-color: #1e3a8a;
            color: white;
            padding: 6px 12px;
            margin: 0 0 10px 0;
            font-size: 13px;
            font-weight: bold;
        }
        
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 0;
        }
        
        .info-row {
            display: table-row;
        }
        
        .info-label {
            display: table-cell;
            width: 30%;
            padding: 5px 10px;
            font-weight: bold;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
        }
        
        .info-value {
            display: table-cell;
            padding: 5px 10px;
            border: 1px solid #dee2e6;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        .table th,
        .table td {
            border: 1px solid #dee2e6;
            padding: 8px;
            text-align: left;
        }
        
        .table th {
            background-color: #1e3a8a;
            color: white;
            font-weight: bold;
        }
        
        .table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #dee2e6;
            padding-top: 10px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
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
    </style>
</head>
<body>
    <div class="header">
        <h1>SURVEY REPORT</h1>
        <h2>{{ $survey->survey_number }}</h2>
    </div>

    <!-- Survey Information -->
    <div class="section">
        <div class="section-title">Survey Information</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Survey Number</div>
                <div class="info-value">{{ $survey->survey_number }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Status</div>
                <div class="info-value">
                    <span class="status-badge status-{{ $survey->status }}">{{ ucfirst($survey->status) }}</span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">Survey Date</div>
                <div class="info-value">{{ $survey->survey_date ? \Carbon\Carbon::parse($survey->survey_date)->format('d F Y') : '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Marketing Staff</div>
                <div class="info-value">{{ $survey->marketing->name ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Survey Location</div>
                <div class="info-value" style="word-wrap: break-word; white-space: normal;">
                    @php
                        $address1 = $survey->survey_location ?? $survey->building->alamat_1 ?? $survey->building->address ?? '';
                        $address2 = $survey->building->alamat_2 ?? '';
                        $fullLocation = $address1;
                        if (!empty($address2)) {
                            $fullLocation .= '. ' . $address2;
                        }
                    @endphp
                    {{ $fullLocation ?: '-' }}
                </div>
            </div>
        </div>
    </div>

    <!-- Company Information -->
    <div class="section">
        <div class="section-title">Company Information</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Company Name</div>
                <div class="info-value">{{ $survey->company_name ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Customer Type</div>
                <div class="info-value">{{ $survey->customer_type ?? $survey->customer->company_type ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Contact Person</div>
                <div class="info-value">{{ $survey->contact_person ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Email</div>
                <div class="info-value">{{ $survey->email ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Phone 1</div>
                <div class="info-value">{{ $survey->phone_1 ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Position</div>
                <div class="info-value">{{ $survey->position ?? '-' }}</div>
            </div>
        </div>
    </div>

    <!-- Building Information -->
    <div class="section">
        <div class="section-title">Building Information</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Building Name</div>
                <div class="info-value">{{ $survey->building->name ?? $survey->building->nama_gedung ?? $survey->building_name ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Building Type</div>
                <div class="info-value">{{ $survey->building_type ?? $survey->building->building_type ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Address 1</div>
                <div class="info-value">{{ $survey->building->alamat_1 ?? $survey->building->address ?? $survey->address_1 ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Address 2</div>
                <div class="info-value">{{ $survey->building->alamat_2 ?? $survey->address_2 ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Phone 1</div>
                <div class="info-value">{{ $survey->building->phone_1 ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Fax</div>
                <div class="info-value">{{ $survey->building->fax ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Province</div>
                <div class="info-value">{{ $survey->province ?? $survey->building->province->name ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">City</div>
                <div class="info-value">{{ $survey->city ?? $survey->building->city->name ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">District</div>
                <div class="info-value">{{ $survey->district ?? $survey->building->district->name ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Subdistrict</div>
                <div class="info-value">{{ $survey->village ?? $survey->building->subdistrict->name ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Postal Code</div>
                <div class="info-value">{{ $survey->postal_code ?? $survey->building->postal_code ?? $survey->building->kode_pos ?? '-' }}</div>
            </div>
        </div>
    </div>

    <!-- Survey Details -->
    @if($survey->surveyDetails && $survey->surveyDetails->count() > 0)
    <div class="section">
        <div class="section-title">Survey Details</div>
        @foreach($survey->surveyDetails as $index => $detail)
        @php
            $specs = json_decode($detail->specifications ?? '{}', true);
        @endphp
        <div style="margin-bottom: 20px; page-break-inside: avoid; border: 1px solid #dee2e6; padding: 15px;">
            <h4 style="color: #1e3a8a; margin: 0 0 15px 0; font-size: 14px; border-bottom: 1px solid #1e3a8a; padding-bottom: 5px;">
                Room {{ $index + 1 }}: {{ $detail->room_name ?? 'Unnamed Room' }}
            </h4>
            
            <div style="display: table; width: 100%; margin-bottom: 10px;">
                <div style="display: table-row;">
                    <div style="display: table-cell; width: 25%; padding: 5px; font-weight: bold; background-color: #f8f9fa; border: 1px solid #dee2e6;">Room Type</div>
                    <div style="display: table-cell; width: 25%; padding: 5px; border: 1px solid #dee2e6;">{{ $detail->room_type ?? '-' }}</div>
                    <div style="display: table-cell; width: 25%; padding: 5px; font-weight: bold; background-color: #f8f9fa; border: 1px solid #dee2e6;">Floor</div>
                    <div style="display: table-cell; width: 25%; padding: 5px; border: 1px solid #dee2e6;">{{ $specs['floor'] ?? '-' }}</div>
                </div>
                <div style="display: table-row;">
                    <div style="display: table-cell; padding: 5px; font-weight: bold; background-color: #f8f9fa; border: 1px solid #dee2e6;">Scent Intensity</div>
                    <div style="display: table-cell; padding: 5px; border: 1px solid #dee2e6;">{{ $specs['intensity'] ?? '-' }}</div>
                    <div style="display: table-cell; padding: 5px; font-weight: bold; background-color: #f8f9fa; border: 1px solid #dee2e6;">Installation Type</div>
                    <div style="display: table-cell; padding: 5px; border: 1px solid #dee2e6;">{{ $specs['installation_type'] ?? '-' }}</div>
                </div>
                <div style="display: table-row;">
                    <div style="display: table-cell; padding: 5px; font-weight: bold; background-color: #f8f9fa; border: 1px solid #dee2e6;">Quantity</div>
                    <div style="display: table-cell; padding: 5px; border: 1px solid #dee2e6;">{{ $specs['qty'] ?? $detail->quantity_needed ?? '-' }}</div>
                    <div style="display: table-cell; padding: 5px; font-weight: bold; background-color: #f8f9fa; border: 1px solid #dee2e6;">Temperature</div>
                    <div style="display: table-cell; padding: 5px; border: 1px solid #dee2e6;">{{ $specs['temperature'] ?? '-' }}{{ isset($specs['temperature']) ? '°C' : '' }}</div>
                </div>
                <div style="display: table-row;">
                    <div style="display: table-cell; padding: 5px; font-weight: bold; background-color: #f8f9fa; border: 1px solid #dee2e6;">Length (m)</div>
                    <div style="display: table-cell; padding: 5px; border: 1px solid #dee2e6;">{{ $specs['length'] ?? '-' }}</div>
                    <div style="display: table-cell; padding: 5px; font-weight: bold; background-color: #f8f9fa; border: 1px solid #dee2e6;">Width (m)</div>
                    <div style="display: table-cell; padding: 5px; border: 1px solid #dee2e6;">{{ $specs['width'] ?? '-' }}</div>
                </div>
                <div style="display: table-row;">
                    <div style="display: table-cell; padding: 5px; font-weight: bold; background-color: #f8f9fa; border: 1px solid #dee2e6;">Height (m)</div>
                    <div style="display: table-cell; padding: 5px; border: 1px solid #dee2e6;">{{ $specs['height'] ?? '-' }}</div>
                    <div style="display: table-cell; padding: 5px; font-weight: bold; background-color: #f8f9fa; border: 1px solid #dee2e6;">Area (m²)</div>
                    <div style="display: table-cell; padding: 5px; border: 1px solid #dee2e6;">{{ $specs['area'] ?? $detail->room_area ?? '-' }}</div>
                </div>
                @if(!empty($specs['remark']))
                <div style="display: table-row;">
                    <div style="display: table-cell; padding: 5px; font-weight: bold; background-color: #f8f9fa; border: 1px solid #dee2e6;">Remark</div>
                    <div style="display: table-cell; padding: 5px; border: 1px solid #dee2e6;" colspan="3">{{ $specs['remark'] }}</div>
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <!-- Additional Information -->
    @if($survey->additional_info)
    <div class="section">
        <div class="section-title">Additional Information</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Notes</div>
                <div class="info-value">{{ $survey->additional_info }}</div>
            </div>
        </div>
    </div>
    @endif

    <div class="footer">
        <p>Generated on {{ now()->format('d F Y H:i:s') }}</p>
        <p>Survey Report - {{ $survey->survey_number }}</p>
    </div>
</body>
</html>
