@extends('layouts.app')

@section('title', 'Inventory Issuing Detail')

@section('content')

<style>
    .container-fluid {
        padding: 0 !important;
        margin: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
    }
    
    .row {
        margin: 0 !important;
        display: flex !important;
        flex-wrap: wrap !important;
        width: 100% !important;
    }
    
    .col-12 {
        padding: 15px !important;
        width: 100% !important;
    }
    
    .card {
        width: 100% !important;
        margin-bottom: 1rem !important;
        border-radius: 8px !important;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
        border: 1px solid rgba(0, 0, 0, 0.125) !important;
    }
    
    .card-header {
        padding: 1rem 1.5rem !important;
        border-bottom: 1px solid rgba(0, 0, 0, 0.125) !important;
    }
    
    .card-body {
        padding: 1.5rem !important;
    }
    
    .nav-tabs {
        border-bottom: 2px solid #1e3a8a !important;
        margin: 0 !important;
        display: flex !important;
        flex-direction: row !important;
    }
    
    .nav-tabs .nav-item {
        flex: 1 !important;
    }
    
    .nav-tabs .nav-link {
        border: none !important;
        border-radius: 0 !important;
        transition: all 0.3s ease !important;
        padding: 12px 20px !important;
        width: 100% !important;
        text-align: center !important;
    }
    
    .nav-tabs .nav-link:hover {
        border-color: transparent !important;
        background-color: #f8f9fa !important;
    }
    
    .nav-tabs .nav-link.active {
        border-color: transparent !important;
        background-color: white !important;
        border-bottom: 3px solid #1e3a8a !important;
        color: #1e3a8a !important;
        font-weight: bold !important;
    }
    
    .tab-content {
        width: 100% !important;
        min-height: 500px !important;
    }
    
    .tab-pane {
        width: 100% !important;
        min-height: 500px !important;
        display: none !important;
    }
    
    .tab-pane.show.active {
        display: block !important;
    }
    
    .info-card {
        border: 1px solid #dee2e6 !important;
        border-radius: 8px !important;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
        margin-bottom: 1rem !important;
        height: 100% !important;
        display: flex !important;
        flex-direction: column !important;
    }
    
    .info-card .card-body {
        flex: 1 !important;
        display: flex !important;
        flex-direction: column !important;
    }
    
    .col-lg-6 {
        display: flex !important;
        flex-direction: column !important;
    }
    
    .col-lg-6 .info-card {
        flex: 1 !important;
    }
    
    .info-field {
        margin-bottom: 1rem !important;
        display: flex !important;
        align-items: center !important;
    }
    
    .info-field-label {
        flex: 0 0 40% !important;
        font-weight: bold !important;
        color: #495057 !important;
    }
    
    .info-field-value {
        flex: 0 0 60% !important;
        color: #6c757d !important;
    }
    
    .table th {
        background-color: #f8f9fa !important;
        border-top: none !important;
        font-weight: 600 !important;
        color: #495057 !important;
        padding: 12px !important;
    }
    
    .table td {
        padding: 12px !important;
        vertical-align: middle !important;
    }
    
    @media (max-width: 768px) {
        .d-flex.justify-content-between {
            flex-direction: column !important;
            gap: 1rem !important;
        }
        
        .nav-tabs {
            flex-direction: row !important;
        }
        
        .nav-tabs .nav-link {
            text-align: center !important;
            font-size: 0.9rem !important;
            padding: 10px 15px !important;
        }
        
        .col-lg-6 {
            flex: 0 0 100% !important;
            max-width: 100% !important;
        }
    }
    
    @media (min-width: 769px) and (max-width: 991px) {
        .col-lg-6 {
            flex: 0 0 50% !important;
            max-width: 50% !important;
        }
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header - LAYOUT BARU -->
            <div class="card mb-3" style="background-color: #1e3a8a; color: white; border: none; border-radius: 10px;">
                <div class="card-header" style="background-color: #1e3a8a; border: none; padding: 1rem 1.5rem;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <a href="{{ route('warehouse.inventory-issuings.index') }}" class="btn btn-light btn-sm">
                                <i class="fas fa-arrow-left"></i> Back to List
                            </a>
                        </div>
                        <div>
                            <h3 class="card-title mb-0" style="color: white; font-size: 1.5rem; font-weight: bold;">
                                {{ $issuing->issuing_number }} - <span style="font-size: 0.9rem; font-weight: normal;">
                                    @if($issuing->status === 'pending')
                                        Material Assign
                                    @elseif($issuing->status === 'processed')
                                        Material Issue
                                    @elseif($issuing->status === 'sent')
                                        Material Taken
                                    @else
                                        {{ ucfirst($issuing->status) }}
                                    @endif
                                </span>
                            </h3>
                        </div>
                        <div>
                            @if($issuing->isPending())
                                @php
                                    $missingSN = false;
                                    $missingItems = [];
                                    foreach($issuing->items as $item) {
                                        if (($item->product?->requiresSerialNumber() ?? false) && !$item->serial_number_id) {
                                            $missingSN = true;
                                            $missingItems[] = $item->product->name;
                                        }
                                    }
                                @endphp
                                
                                @if($missingSN)
                                    <span class="d-inline-block" tabindex="0" data-bs-toggle="tooltip" title="Harap isi Serial Number untuk: {{ implode(', ', array_unique($missingItems)) }}">
                                        <button type="button" class="btn btn-success btn-sm" disabled style="opacity: 0.6; cursor: not-allowed;">
                                            <i class="fas fa-check-circle"></i> Ready to Issue
                                        </button>
                                    </span>
                                @else
                                    <form method="POST" action="{{ route('warehouse.inventory-issuings.process', $issuing->id) }}" style="display: inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm">
                                            <i class="fas fa-check-circle"></i> Ready to Issue
                                        </button>
                                    </form>
                                @endif
                            @elseif($issuing->isProcessed())
                            <form method="POST" action="{{ route('warehouse.inventory-issuings.draft', $issuing->id) }}" style="display: inline;" onsubmit="return handleDraftIssuing(event, this)">
                                @csrf
                                <button type="submit" class="btn btn-warning btn-sm me-2">
                                    <i class="fas fa-undo"></i> Draft (Kembali ke Unprepare)
                                </button>
                            </form>
                            <form method="POST" action="{{ route('warehouse.inventory-issuings.finalize', $issuing->id) }}" style="display: inline;" onsubmit="return handleFinalizeIssuing(event, this)">
                                @csrf
                                @php
                                    $canFinalize = $issuing->team_id && $issuing->received_by;
                                @endphp
                                @if($canFinalize)
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fas fa-check-double"></i> Verifikasi Material
                                </button>
                                @else
                                <span class="d-inline-block" tabindex="0" data-bs-toggle="tooltip" title="Harap pilih Team dan Diberikan Kepada terlebih dahulu">
                                    <button type="button" class="btn btn-primary btn-sm" disabled style="opacity: 0.6; cursor: not-allowed;">
                                        <i class="fas fa-check-double"></i> Verifikasi Material
                                    </button>
                                </span>
                                @endif
                            </form>
                            @endif

                            @if($issuing->status === 'sent' && ($canUnpost ?? false))
                            <form method="POST" action="{{ route('warehouse.inventory-issuings.unpost', $issuing->id) }}" style="display: inline;" onsubmit="return handleUnpostIssuing(event, this)">
                                @csrf
                                <button type="submit" class="btn btn-danger btn-sm me-2">
                                    <i class="fas fa-undo"></i> Unpost to Processed
                                </button>
                            </form>
                            @endif
                            
                            <!-- Print Receipt Button - Always visible -->
                            <a href="{{ route('warehouse.inventory-issuings.print-receipt', $issuing->id) }}" 
                               class="btn btn-info btn-sm" 
                               target="_blank"
                               style="margin-left: 10px;">
                                <i class="fas fa-print"></i> Print Receipt
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Tabs - HORIZONTAL LAYOUT -->
            <div class="card mb-3">
                <div class="card-body p-0">
                    <ul class="nav nav-tabs" id="issuingTabs" role="tablist">
                        <li class="nav-item" role="presentation" style="flex: 1;">
                            <button class="nav-link active" id="issuing-info-tab" data-bs-toggle="tab" data-bs-target="#issuing-info" type="button" role="tab" aria-controls="issuing-info" aria-selected="true">
                                <i class="fas fa-info-circle me-2"></i>ISSUING INFO
                            </button>
                        </li>
                        <li class="nav-item" role="presentation" style="flex: 1;">
                            <button class="nav-link" id="products-tab" data-bs-toggle="tab" data-bs-target="#products" type="button" role="tab" aria-controls="products" aria-selected="false">
                                <i class="fas fa-box me-2"></i>PRODUCTS
                            </button>
                        </li>
                        <li class="nav-item" role="presentation" style="flex: 1;">
                            <button class="nav-link" id="serials-tab" data-bs-toggle="tab" data-bs-target="#serials" type="button" role="tab" aria-controls="serials" aria-selected="false">
                                <i class="fas fa-barcode me-2"></i>SERIAL NUMBER
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Tab Content -->
            <div class="tab-content" id="issuingTabsContent">
                <!-- Issuing Info Tab -->
                <div class="tab-pane fade show active" id="issuing-info" role="tabpanel" aria-labelledby="issuing-info-tab">
                    <div class="row" style="display: flex; flex-wrap: wrap; margin: 0 -15px;">
                        <div class="col-lg-6 mb-4" style="padding: 0 15px; flex: 0 0 50%; max-width: 50%;">
                            <div class="card info-card" style="height: 100%;">
                                <div class="card-header" style="background-color: #6c757d; color: white; border-radius: 8px 8px 0 0;">
                                    <h5 class="card-title mb-0">Issuing Information</h5>
                                </div>
                                <div class="card-body" style="flex: 1;">
                                    <div class="info-field">
                                        <div class="info-field-label">Cabang</div>
                                        <div class="info-field-value">{{ $issuing->branch_name }}</div>
                                    </div>
                                    <div class="info-field">
                                        <div class="info-field-label">Reference No</div>
                                        <div class="info-field-value">{{ $issuing->reference_no ?? '-' }}</div>
                                    </div>
                                    {{-- Show Team Field --}}
                                    <div class="info-field">
                                        <div class="info-field-label">Nama Team</div>
                                        <div class="info-field-value">
                                            <span id="teamDisplay">{{ $issuing->team?->team_name ?? '-' }}</span>
                                            @if(strtolower(trim($issuing->status ?? '')) !== 'sent')
                                            <button type="button" onclick="openEditTeamModal()" class="btn btn-link btn-sm p-0 ml-2">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="info-field">
                                        <div class="info-field-label">Diberikan Kepada</div>
                                        <div class="info-field-value">
                                            <span id="receivedByDisplay">{{ $issuing->receivedBy?->name ?? '-' }}</span>
                                            @if(strtolower(trim($issuing->status ?? '')) !== 'sent')
                                            <button type="button" onclick="openEditReceivedByModal()" class="btn btn-link btn-sm p-0 ml-2">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div class="info-field">
                                        <div class="info-field-label">Request By</div>
                                        <div class="info-field-value">{{ $issuing->requestedBy?->name ?? '-' }}</div>
                                    </div>
                                    <div class="info-field">
                                        <div class="info-field-label">Issue Date</div>
                                        <div class="info-field-value">{{ $issuing->issue_date?->format('d/M/Y') ?? '-' }}</div>
                                    </div>
                                    <div class="info-field">
                                        <div class="info-field-label">Status</div>
                                        <div class="info-field-value">
                                            <span class="badge badge-{{ $issuing->status === 'processed' ? 'success' : ($issuing->status === 'pending' ? 'warning' : 'info') }}">
                                                {{ ucfirst($issuing->status) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 mb-4" style="padding: 0 15px; flex: 0 0 50%; max-width: 50%;">
                            <div class="card info-card" style="height: 100%;">
                                <div class="card-header" style="background-color: #6c757d; color: white; border-radius: 8px 8px 0 0;">
                                    <h5 class="card-title mb-0">Additional Information</h5>
                                </div>
                                <div class="card-body" style="flex: 1;">
                                    <div class="info-field">
                                        <div class="info-field-label">Catatan Tambahan</div>
                                        <div class="info-field-value">
                                            <span id="remarksDisplay">{{ $issuing->remarks ?? '-' }}</span>
                                            <button type="button" onclick="openEditRemarksModal()" class="btn btn-link btn-sm p-0 ml-2">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Products Tab -->
                <div class="tab-pane fade" id="products" role="tabpanel" aria-labelledby="products-tab">
                    <div class="card" style="width: 100%; min-height: 500px;">
                        <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #1e3a8a;">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0" style="color: #1e3a8a;">
                                    <i class="fas fa-box me-2"></i>Products
                                </h5>
                                @if(($issuing->status === 'pending' || $issuing->status === 'draft') && (!$issuing->reference_no || \Illuminate\Support\Str::startsWith($issuing->reference_no, 'MI-')))
                                <button class="btn btn-primary btn-sm" onclick="openAddItemModal()">
                                    <i class="fas fa-plus"></i> Add Item
                                </button>
                                @endif
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="overflow-x: auto; max-width: 100%;">
                                <table class="table table-bordered table-striped" style="min-width: 1200px; white-space: nowrap;">
                                    <thead>
                                        <tr>
                                            <th>Issuing No</th>
                                            <th>Room</th>
                                            <th>Product Name</th>
                                            <th>Product Category</th>
                                            <th>Has Serial?</th>
                                            <th>Quantity</th>
                                            <th>Last Update</th>
                                            <th>Oleh</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                        <tbody>
                                        @forelse($issuing->items as $item)
                                        @php
                                            $noteRoomName = null;
                                            if (!empty($item->notes) && preg_match('/Room:\s*([^,]+)/i', $item->notes, $matches)) {
                                                $noteRoomName = trim($matches[1]);
                                            }
                                            $displayRoomName = $noteRoomName ?: $item->room_name;
                                        @endphp
                                        <tr>
                                            <td>{{ $issuing->issuing_number }}</td>
                                            <td>
                                                @if($displayRoomName)
                                                    <span class="badge" style="background-color: #e0f2fe; color: #0f172a; border: 1px solid #7dd3fc;">{{ $displayRoomName }}</span>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>{{ $item->product->name ?? '-' }}</td>
                                            <td>{{ $item->product?->productCategory?->name ?? '-' }}</td>
                                            <td>{{ ($item->product?->requiresSerialNumber() ?? false) ? 'Yes' : 'No' }}</td>
                                            <td>{{ $item->quantity_requested }}</td>
                                            <td>{{ $item->updated_at?->format('d/M/Y H:i') ?? '-' }}</td>
                                            <td>{{ $item->updatedBy->name ?? $item->createdBy->name ?? '-' }}</td>
                                            <td class="text-center">
                                                @if($issuing->status === 'pending' || $issuing->status === 'draft')
                                                <div class="btn-group">
                                                    @if(optional($item->product->productCategory)->is_unit != true && auth()->user()->hasPermission('warehouse.inventory-issuings.change-aroma-direct'))
                                                    <button class="btn btn-warning btn-sm" onclick="openChangeAromaModal({{ $item->id }}, '{{ $item->product->name }}', {{ $item->quantity_requested }}, {{ $item->product->packaging_size_id }}, '{{ $item->product->brand_line }}')" title="Change Aroma">
                                                        <i class="fas fa-exchange-alt"></i>
                                                    </button>
                                                    @endif
                                                </div>
                                                @else
                                                -
                                                @endif
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="9" class="text-center text-muted">
                                                <i class="fas fa-info-circle me-2"></i>No items found.
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Serial Number Tab -->
                <div class="tab-pane fade" id="serials" role="tabpanel" aria-labelledby="serials-tab">
                    <div class="card" style="width: 100%; min-height: 500px;">
                        <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #1e3a8a;">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0" style="color: #1e3a8a;">
                                    <i class="fas fa-barcode me-2"></i>Serial Numbers
                                </h5>
                                @if($issuing->status === 'pending')
                                <button class="btn btn-primary btn-sm" onclick="openScanSNModal()">
                                    <i class="fas fa-qrcode me-2"></i>Scan QR / Input SN
                                </button>
                                @endif
                            </div>
                        </div>
                        <div class="card-body">
                            @if($issuing->items->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover" style="width: 100%;">
                                    <thead class="table-light" style="background-color: #f8f9fa;">
                                        <tr>
                                            <th style="width: 22%; padding: 12px; font-weight: 600; color: #495057;">Product Name</th>
                                            <th style="width: 10%; padding: 12px; font-weight: 600; color: #495057;">Quantity</th>
                                            <th style="width: 15%; padding: 12px; font-weight: 600; color: #495057; background-color: #e8f4f8;"><i class="fas fa-door-open me-1"></i>Room</th>
                                            <th style="width: 18%; padding: 12px; font-weight: 600; color: #495057;">Serial Number</th>
                                            <th style="width: 12%; padding: 12px; font-weight: 600; color: #495057;">Status</th>
                                            <th style="width: 13%; padding: 12px; font-weight: 600; color: #495057;">Warehouse</th>
                                            <th style="width: 10%; padding: 12px; font-weight: 600; color: #495057;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($issuing->items as $item)
                                        @php
                                            $requiresSerialNumber = $item->product?->requiresSerialNumber() ?? false;
                                            $canScanSerialNumber = $requiresSerialNumber || in_array((int) $item->product_id, $scanSerialProductIds ?? [], true);
                                            $isOptionalSerialNumber = !$requiresSerialNumber && $canScanSerialNumber;
                                        @endphp
                                        <tr>
                                            <td style="padding: 12px; vertical-align: middle;">
                                                <strong>{{ $item->product->name ?? 'Unknown Product' }}</strong>
                                                @if($item->product?->productCategory)
                                                <br><small class="text-muted">{{ $item->product->productCategory->name }}</small>
                                                @endif
                                            </td>
                                            <td style="padding: 12px; vertical-align: middle; text-align: center;">
                                                <span class="badge bg-info" style="font-size: 0.9rem; padding: 6px 12px;">{{ $item->quantity_requested }}</span>
                                            </td>
                                            <td style="padding: 12px; vertical-align: middle; background-color: #f0f9ff;">
                                                @if($item->room_name)
                                                <span class="badge" style="background-color: #0ea5e9; color: white; font-size: 0.8rem; padding: 5px 10px;">
                                                    <i class="fas fa-door-open me-1"></i>{{ $item->room_name }}
                                                </span>
                                                @else
                                                <span class="text-muted" style="font-style: italic; font-size: 0.85rem;">-</span>
                                                @endif
                                            </td>
                                            <td style="padding: 12px; vertical-align: middle;">
                                                @if(!$canScanSerialNumber)
                                                <span class="text-muted" style="font-style: italic;">Tidak wajib SN</span>
                                                @elseif($item->serialNumber)
                                                <strong style="font-family: monospace; font-size: 1rem; color: #1e3a8a;">{{ $item->serialNumber->serial_number }}</strong>
                                                @elseif($isOptionalSerialNumber)
                                                <span class="text-muted" style="font-style: italic;">Opsional - belum ada SN</span>
                                                @else
                                                <span class="text-muted" style="font-style: italic;">Belum ada SN</span>
                                                @endif
                                            </td>
                                            <td style="padding: 12px; vertical-align: middle;">
                                                @if(!$canScanSerialNumber)
                                                <span class="badge bg-secondary" style="font-size: 0.85rem; padding: 6px 10px;">Tidak Wajib</span>
                                                @elseif($item->serialNumber)
                                                @php
                                                    $statusClass = 'secondary';
                                                    $statusText = ucfirst(str_replace('_', ' ', $item->serialNumber->status));
                                                    if (in_array($item->serialNumber->status, ['ready', 'available'])) {
                                                        $statusClass = 'success';
                                                        $statusText = 'Ready';
                                                    } elseif (in_array($item->serialNumber->status, ['broken', 'damaged'])) {
                                                        $statusClass = 'danger';
                                                        $statusText = 'Broken';
                                                    } elseif (in_array($item->serialNumber->status, ['on_service', 'maintenance'])) {
                                                        $statusClass = 'warning';
                                                        $statusText = 'On Service';
                                                    } elseif ($item->serialNumber->status === 'in_use') {
                                                        $statusClass = 'info';
                                                        $statusText = 'In Use';
                                                    }
                                                @endphp
                                                <span class="badge bg-{{ $statusClass }}" style="font-size: 0.85rem; padding: 6px 10px;">
                                                    {{ $statusText }}
                                                </span>
                                                @elseif($isOptionalSerialNumber)
                                                <span class="badge bg-info" style="font-size: 0.85rem; padding: 6px 10px;">Opsional</span>
                                                @else
                                                <span class="badge bg-warning" style="font-size: 0.85rem; padding: 6px 10px;">Pending</span>
                                                @endif
                                            </td>
                                            <td style="padding: 12px; vertical-align: middle;">
                                                @if($item->serialNumber && $item->serialNumber->warehouse)
                                                <small>{{ $item->serialNumber->warehouse->name }}</small>
                                                @else
                                                <small>{{ $issuing->warehouse->name ?? '-' }}</small>
                                                @endif
                                            </td>
                                            <td style="padding: 12px; vertical-align: middle; text-align: center;">
                                                @if($issuing->status === 'pending')
                                                @if(!$canScanSerialNumber)
                                                <span class="text-muted">-</span>
                                                @elseif(!$item->serialNumber)
                                                <button class="btn btn-sm btn-primary" onclick="openScanSNModalForItem({{ $item->id }})" title="Scan QR / Input SN">
                                                    <i class="fas fa-qrcode"></i>
                                                </button>
                                                @else
                                                <button class="btn btn-sm btn-warning" onclick="openScanSNModalForItem({{ $item->id }})" title="Ubah SN">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                @endif
                                                @else
                                                <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @else
                                <div class="text-center text-muted" style="padding: 3rem;">
                                    <i class="fas fa-info-circle fa-3x mb-3"></i>
                                    <p class="mb-0">Tidak ada item dalam issuing ini.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div> {{-- End of container-fluid --}}

<!-- Modals Section - Moved outside container-fluid to avoid flex/overflow issues -->

<!-- Edit Diberikan Kepada Modal -->
<div class="modal fade" id="editReceivedByModal" tabindex="-1" aria-labelledby="editReceivedByModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editReceivedByModalLabel">Edit Diberikan Kepada</h5>
                <button type="button" class="btn-close" onclick="closeEditReceivedByModal()" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editReceivedByForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Diberikan Kepada</label>
                        <select name="received_by" id="receivedBySelect" class="form-control">
                            <option value="">Pilih User</option>
                            @foreach(\App\Models\User::orderBy('name')->get() as $user)
                            <option value="{{ $user->id }}" {{ $issuing->received_by == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditReceivedByModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveReceivedBy(this)">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Team Modal (Manual Issuing Only) -->
<div class="modal fade" id="editTeamModal" tabindex="-1" aria-labelledby="editTeamModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: white !important; color: black !important; border-bottom: 1px solid #dee2e6 !important;">
                <h5 class="modal-title" id="editTeamModalLabel" style="color: black !important;">Edit Nama Team</h5>
                <button type="button" class="btn-close" onclick="closeEditTeamModal()" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editTeamForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Nama Team</label>
                        <select name="team_id" id="teamSelectModal" class="form-control">
                            <option value="">Pilih Team</option>
                        </select>
                        <small class="text-muted">Team akan menentukan user yang akan menerima barang</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditTeamModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveTeam(this)">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Catatan Modal -->
<div class="modal fade" id="editRemarksModal" tabindex="-1" aria-labelledby="editRemarksModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editRemarksModalLabel">Edit Catatan Tambahan</h5>
                <button type="button" class="btn-close" onclick="closeEditRemarksModal()" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editRemarksForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Catatan Tambahan</label>
                        <textarea name="remarks" id="remarksTextarea" rows="4" class="form-control">{{ $issuing->remarks ?? '' }}</textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditRemarksModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveRemarks(this)">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addItemModalLabel">Add Item to Issuing</h5>
                <button type="button" class="btn-close" onclick="closeAddItemModal()" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addItemForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Product <span class="text-danger">*</span></label>
                        <select name="product_id" id="addProductSelect" class="form-control" required style="width: 100%;">
                            <option value="">Search Product...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quantity <span class="text-danger">*</span></label>
                        <input type="number" name="quantity" class="form-control" step="0.01" min="0.01" required value="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Catatan (Optional)</label>
                        <textarea name="remarks" class="form-control" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeAddItemModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveItem(this)">Add Item</button>
            </div>
        </div>
    </div>
</div>

<style>
    /* CUSTOM MODAL SYSTEM (Because Bootstrap CSS is missing from layout) */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 1060 !important;
        overflow-x: hidden;
        overflow-y: auto;
        outline: 0;
        align-items: center;
        justify-content: center;
    }

    .modal.show {
        display: flex !important;
    }

    .modal-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background-color: rgba(0, 0, 0, 0.7) !important;
        backdrop-filter: blur(8px) !important;
        -webkit-backdrop-filter: blur(8px) !important;
        z-index: 1050 !important;
    }

    .modal-dialog {
        position: relative;
        width: auto;
        margin: 0.5rem;
        pointer-events: none;
        z-index: 1061 !important;
        width: 100%;
        max-width: 500px;
        display: flex;
        align-items: center;
        min-height: calc(100% - 1rem);
    }

    .modal-dialog-centered {
        display: flex;
        align-items: center;
        min-height: calc(100% - 1rem);
    }

    .modal-content {
        position: relative;
        display: flex;
        flex-direction: column;
        width: 100%;
        pointer-events: auto;
        background-color: #fff;
        background-clip: padding-box;
        border: none !important;
        border-radius: 12px !important;
        outline: 0;
        box-shadow: 0 15px 35px rgba(0,0,0,0.3) !important;
    }

    .modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1.25rem;
        border-bottom: 1px solid #dee2e6;
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
    }

    .modal-title {
        margin-bottom: 0;
        line-height: 1.5;
        font-weight: 600;
        font-size: 1.25rem;
        color: #333;
    }

    .btn-close {
        box-sizing: content-box;
        width: 1em;
        height: 1em;
        padding: 0.25em 0.25em;
        color: #000;
        background: transparent url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23000'%3e%3cpath d='M.293.293a1 1 0 011.414 0L8 6.586 14.293.293a1 1 0 111.414 1.414L9.414 8l6.293 6.293a1 1 0 01-1.414 1.414L8 9.414l-6.293 6.293a1 1 0 01-1.414-1.414L6.586 8 .293 1.707a1 1 0 010-1.414z'/%3e%3c/svg%3e") center/1em auto no-repeat;
        border: 0;
        border-radius: 0.25rem;
        opacity: 0.5;
        cursor: pointer;
    }

    .modal-body {
        position: relative;
        flex: 1 1 auto;
        padding: 1.25rem;
    }

    .modal-footer {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: flex-end;
        padding: 1rem;
        border-top: 1px solid #dee2e6;
        border-bottom-right-radius: 12px;
        border-bottom-left-radius: 12px;
        gap: 0.5rem;
    }

    /* Fix for when body has no overflow */
    body.modal-open {
        overflow: hidden;
    }
</style>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
$(document).ready(function() {
    // Tab switching functionality using Bootstrap 5
    $('#issuingTabs button[data-bs-toggle="tab"]').on('click', function(e) {
        e.preventDefault();
        var target = $(this).attr('data-bs-target');
        
        // Remove active class from all tabs and content
        $('#issuingTabs button').removeClass('active');
        $('.tab-pane').removeClass('show active');
        
        // Add active class to clicked tab
        $(this).addClass('active');
        $(target).addClass('show active');
    });

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
});

async function openAddItemModal() {
    const modalEl = document.getElementById('addItemModal');
    modalEl.classList.add('show');
    modalEl.style.display = 'flex';
    modalEl.setAttribute('aria-hidden', 'false');
    document.body.classList.add('modal-open');
    
    // Create backdrop
    if (!document.querySelector('.modal-backdrop')) {
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show';
        document.body.appendChild(backdrop);
    }

    // Load products if not loaded
    const productSelect = document.getElementById('addProductSelect');
    if (productSelect.options.length <= 1) {
        try {
            const response = await fetch('{{ route("warehouse.inventory-issuings.all-products") }}');
            const result = await response.json();
            if (result.status === 'success') {
                result.data.forEach(product => {
                    const option = document.createElement('option');
                    option.value = product.id;
                    option.textContent = `${product.name} (${product.code})`;
                    productSelect.appendChild(option);
                });
            }
        } catch (e) {
            console.error('Error loading products:', e);
        }
    }
}

function closeAddItemModal() {
    const modalEl = document.getElementById('addItemModal');
    modalEl.classList.remove('show');
    modalEl.style.display = 'none';
    modalEl.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('modal-open');
    const backdrop = document.querySelector('.modal-backdrop');
    if (backdrop) backdrop.remove();
}

function handleDraftIssuing(event, form) {
    event.preventDefault();
    showConfirmDialog(
        'Kembalikan ke Unprepare',
        'Apakah Anda yakin ingin mengembalikan status ke Unprepare?',
        'Ya, Lanjutkan',
        'Batal'
    ).then((confirmed) => {
        if (confirmed && form) {
            form.submit();
        }
    });
    return false;
}

function handleFinalizeIssuing(event, form) {
    event.preventDefault();
    showConfirmDialog(
        'Verifikasi Material',
        'Apakah Anda yakin ingin memverifikasi material ini?',
        'Ya, Verifikasi',
        'Batal'
    ).then((confirmed) => {
        if (confirmed && form) {
            form.submit();
        }
    });
    return false;
}

function handleUnpostIssuing(event, form) {
    event.preventDefault();
    showConfirmDialog(
        'Unpost Inventory',
        'Apakah Anda yakin ingin unpost inventory ini? Status akan kembali menjadi Ready (Processed) dan mutasi stok akan dihapus.',
        'Ya, Unpost',
        'Batal'
    ).then((confirmed) => {
        if (confirmed && form) {
            form.submit();
        }
    });
    return false;
}

async function saveItem(btn) {
    const form = document.getElementById('addItemForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());

    if (!data.product_id || !data.quantity) {
        showWarningDialog('Perhatian', 'Mohon isi field wajib: Produk dan Quantity.');
        return;
    }

    try {
        const response = await fetch('{{ route("warehouse.inventory-issuings.add-item", $issuing->id) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();
        if (result.status === 'success') {
            showSuccessDialog('Berhasil', 'Item berhasil ditambahkan.');
            location.reload();
        } else {
            showErrorDialog('Gagal', result.message || 'Item tidak berhasil ditambahkan.');
        }
    } catch (e) {
        console.error('Error saving item:', e);
        showErrorDialog('Gagal', 'Terjadi kesalahan saat menyimpan item.');
    }
}

async function deleteProduct(itemId) {
    const confirmed = await showConfirmDialog(
        'Hapus Produk',
        'Apakah Anda yakin ingin menghapus produk ini dari daftar?',
        'Ya, Hapus',
        'Batal'
    );

    if (!confirmed) {
        return;
    }

    try {
        const response = await fetch(`{{ url('warehouse/inventory-issuings') }}/{{ $issuing->id }}/delete-item/${itemId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        });

        const result = await response.json();
        if (result.status === 'success') {
            showSuccessDialog('Berhasil', 'Produk berhasil dihapus.');
            location.reload();
        } else {
            showErrorDialog('Gagal', result.message || 'Produk tidak berhasil dihapus.');
        }
    } catch (e) {
        console.error('Error deleting product:', e);
        showErrorDialog('Gagal', 'Terjadi kesalahan saat menghapus produk.');
    }
}

function openEditReceivedByModal() {
    const modalEl = document.getElementById('editReceivedByModal');
    modalEl.classList.add('show');
    modalEl.style.display = 'flex';
    modalEl.setAttribute('aria-hidden', 'false');
    document.body.classList.add('modal-open');
    
    // Create backdrop if not exists
    if (!document.querySelector('.modal-backdrop')) {
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show';
        document.body.appendChild(backdrop);
    }
}

function closeEditReceivedByModal() {
    const modalEl = document.getElementById('editReceivedByModal');
    modalEl.classList.remove('show');
    modalEl.style.display = 'none';
    modalEl.setAttribute('aria-hidden', 'true');
    
    // Cleanup
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    const backdrop = document.querySelector('.modal-backdrop');
    if (backdrop) backdrop.remove();
    
    // Also try BS5 instance if exists
    try {
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
    } catch(e) {}
}

function saveReceivedBy(btn) {
    const saveBtn = btn || (event ? event.target : null);
    const formData = new FormData(document.getElementById('editReceivedByForm'));
    formData.append('_method', 'PUT');
    formData.append('team_id', ''); // Reset team when recipient changes
    
    fetch('{{ route("warehouse.inventory-issuings.update", $issuing->id) }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-HTTP-Method-Override': 'PUT'
        },
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.status === 'success') {
            document.getElementById('receivedByDisplay').textContent = result.data.received_by_name || '-';
            // Reset team display as well
            if (document.getElementById('teamDisplay')) {
                document.getElementById('teamDisplay').textContent = '-';
            }
            closeEditReceivedByModal();
            showSuccessDialog('Berhasil', 'Data berhasil diperbarui. Nama Team telah di-reset.');
        } else {
            showErrorDialog('Gagal', result.message || 'Data tidak berhasil diperbarui.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorDialog('Gagal', 'Data tidak berhasil diperbarui.');
    })
    .finally(() => {
        // Unlock button (Global Double-click prevention)
        if (window.enableSubmitButton) {
            window.enableSubmitButton(saveBtn);
        }
    });
}

function openEditRemarksModal() {
    const modalEl = document.getElementById('editRemarksModal');
    modalEl.classList.add('show');
    modalEl.style.display = 'flex';
    modalEl.setAttribute('aria-hidden', 'false');
    document.body.classList.add('modal-open');
    
    // Create backdrop if not exists
    if (!document.querySelector('.modal-backdrop')) {
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show';
        document.body.appendChild(backdrop);
    }
}

function closeEditRemarksModal() {
    const modalEl = document.getElementById('editRemarksModal');
    modalEl.classList.remove('show');
    modalEl.style.display = 'none';
    modalEl.setAttribute('aria-hidden', 'true');
    
    // Cleanup
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    const backdrop = document.querySelector('.modal-backdrop');
    if (backdrop) backdrop.remove();
}

function saveRemarks(btn) {
    const saveBtn = btn || event.target;
    const formData = new FormData(document.getElementById('editRemarksForm'));
    formData.append('_method', 'PUT');
    
    fetch('{{ route("warehouse.inventory-issuings.update", $issuing->id) }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-HTTP-Method-Override': 'PUT'
        },
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.status === 'success') {
            document.getElementById('remarksDisplay').textContent = result.data.remarks || '-';
            closeEditRemarksModal();
            showSuccessDialog('Berhasil', 'Catatan berhasil diperbarui.');
        } else {
            showErrorDialog('Gagal', result.message || 'Data tidak berhasil diperbarui.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorDialog('Gagal', 'Data tidak berhasil diperbarui.');
    })
    .finally(() => {
        // Unlock button
        if (window.enableSubmitButton) {
            window.enableSubmitButton(saveBtn);
        }
    });
}
// Scan SN Modal for Inventory Issuing
let qrCodeScanner = null;
let isScanning = false;

function openScanSNModal(preSelectedItemId = null) {
    const modal = document.createElement('div');
    modal.id = 'scanSNModal';
    modal.style.cssText = `
        display: flex !important;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.7);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        z-index: 9999;
        align-items: center;
        justify-content: center;
        overflow-y: auto;
    `;
    
    const items = @json($issuing->items);
    const scanSerialProductIds = new Set((@json($scanSerialProductIds ?? [])).map(productId => Number(productId)));
    const itemRequiresSerialNumber = (item) => {
        const product = item.product || {};
        return Boolean(
            product.requires_serial_number ||
            product.product_category?.has_serial_number ||
            product.product_type?.has_serial_number
        );
    };
    const itemCanScanSerialNumber = (item) => {
        return itemRequiresSerialNumber(item) || scanSerialProductIds.has(Number(item.product_id));
    };
    const serialItems = items.filter(itemCanScanSerialNumber);
    const itemsOptions = serialItems.map(item => 
        `<option value="${item.id}" data-product-id="${item.product_id}" ${preSelectedItemId == item.id ? 'selected' : ''}>${item.product?.name || 'Unknown'} (Qty: ${item.quantity_requested})</option>`
    ).join('');
    
    modal.innerHTML = `
        <div class="modal-dialog modal-dialog-centered" style="margin: 20px auto; max-width: 700px; width: 90%;">
            <div class="modal-content" style="background-color: white; border-radius: 12px; box-shadow: 0 15px 40px rgba(0,0,0,0.3); border: none;">
                <div class="modal-header" style="background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%); color: white; border-radius: 12px 12px 0 0; padding: 20px 24px; border-bottom: none;">
                    <h5 class="modal-title" style="font-weight: 600; font-size: 1.25rem;">
                        <i class="fas fa-qrcode me-2"></i>Scan QR / Validasi Serial Number
                    </h5>
                    <button type="button" class="btn-close btn-close-white" onclick="closeScanSNModal()"></button>
                </div>
                <form id="scanSNForm" onsubmit="return false;">
                    @csrf
                    <div class="modal-body" style="background-color: white; padding: 24px;">
                        <div class="mb-4">
                            <label class="form-label" style="font-weight: 600; color: #374151; margin-bottom: 8px; display: block;">
                                <i class="fas fa-box me-2"></i>Item <span class="text-danger">*</span>
                            </label>
                            <select name="issuing_item_id" id="scanSNItem" class="form-control" required style="padding: 10px 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px;">
                                <option value="">Pilih Item</option>
                                ${itemsOptions}
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label" style="font-weight: 600; color: #374151; margin-bottom: 8px; display: block;">
                                <i class="fas fa-barcode me-2"></i>Serial Number <span class="text-danger">*</span>
                            </label>
                            <div class="input-group" style="margin-bottom: 10px;">
                                <input type="text" name="serial_number" id="scanSNSerial" class="form-control" required 
                                    placeholder="Scan QR Code atau input manual" 
                                    autocomplete="off"
                                    style="padding: 10px 12px; border: 2px solid #e5e7eb; border-radius: 8px 0 0 8px; font-size: 14px; text-transform: uppercase;"
                                    onkeyup="this.value = this.value.toUpperCase()">
                                <button type="button" class="btn btn-primary" onclick="startQRScanner()" id="scanQRBtn" style="border-radius: 0 8px 8px 0; border: 2px solid #1e3a8a; border-left: none;">
                                    <i class="fas fa-camera me-2"></i>Scan QR
                                </button>
                            </div>
                            <div id="qrReaderContainer" style="display: none; margin-top: 15px; border: 2px solid #e5e7eb; border-radius: 8px; padding: 15px; background-color: #f9fafb;">
                                <div id="qrReader" style="width: 100%; max-width: 500px; margin: 0 auto;"></div>
                                <div class="text-center mt-3">
                                    <button type="button" class="btn btn-sm btn-secondary" onclick="stopQRScanner()">
                                        <i class="fas fa-times me-2"></i>Stop Camera
                                    </button>
                                </div>
                            </div>
                            <small class="text-muted mt-1 d-block">
                                <i class="fas fa-info-circle me-1"></i>System akan mengecek:
                                <ul class="mt-2 mb-0" style="padding-left: 20px;">
                                    <li>SN harus sudah ada di database (sudah diinput di Receiving)</li>
                                    <li>SN harus sesuai dengan produk yang di-issue</li>
                                    <li>SN tidak boleh sudah terpakai di Unit On Wall</li>
                                    <li>SN status harus ready</li>
                                </ul>
                            </small>
                        </div>
                        <div id="scanSNError" class="alert alert-danger d-none" role="alert"></div>
                        <div id="scanSNSuccess" class="alert alert-success d-none" role="alert"></div>
                    </div>
                    <div class="modal-footer" style="background-color: #f9fafb; border-radius: 0 0 12px 12px; padding: 16px 24px; border-top: 1px solid #e5e7eb; gap: 10px;">
                        <button type="button" class="btn btn-secondary" onclick="closeScanSNModal()" style="padding: 10px 20px; border-radius: 8px; font-weight: 500;">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="submitScanSN()" id="scanSNSubmitBtn" style="padding: 10px 20px; border-radius: 8px; font-weight: 500; background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%); border: none;">
                            <i class="fas fa-check me-2"></i>Validasi & Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
    
    // Auto focus on serial number input
    setTimeout(() => {
        document.getElementById('scanSNSerial').focus();
    }, 100);
}

function openScanSNModalForItem(itemId) {
    openScanSNModal(itemId);
}

async function startQRScanner() {
    if (isScanning) {
        stopQRScanner();
        return;
    }
    
    const container = document.getElementById('qrReaderContainer');
    const readerDiv = document.getElementById('qrReader');
    const scanBtn = document.getElementById('scanQRBtn');
    const serialInput = document.getElementById('scanSNSerial');
    
    if (!container || !readerDiv) {
        showErrorDialog('Gagal', 'Container QR Scanner tidak ditemukan.');
        return;
    }
    
    // Check if we're in a secure context (required for camera access)
    const isSecureContext = window.isSecureContext || window.location.protocol === 'https:' || window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
    
    if (!isSecureContext) {
        // Show informative message about HTTPS requirement
        const errorDiv = document.getElementById('scanSNError');
        if (errorDiv) {
            errorDiv.innerHTML = `
                <strong><i class="fas fa-exclamation-triangle me-2"></i>Kamera tidak dapat diakses</strong><br>
                <small>
                    Akses kamera membutuhkan koneksi HTTPS atau localhost.<br><br>
                    <strong>Solusi:</strong><br>
                    • Ketik di browser <code>chrome://flags/#unsafely-treat-insecure-origin-as-secure</code><br>
                    • Cari opsi "Insecure origins treated as secure". <br>
                    • Masukan alamat urlnya di kotak, yaitu: http://72.60.77.23:8082 <br>
                    • Opsi disable ganti menjadi enable, lalu refresh<br>
                    • Hubungi admin untuk mengaktifkan HTTPS di server<br>
                    • Atau input Serial Number secara manual di kolom di atas
                </small>
            `;
            errorDiv.classList.remove('d-none');
        }
        return;
    }
    
    try {
        // Show container
        container.style.display = 'block';
        scanBtn.innerHTML = '<i class="fas fa-stop me-2"></i>Stop Camera';
        isScanning = true;
        
        // Clear previous scanner
        if (qrCodeScanner) {
            try {
                await qrCodeScanner.clear();
            } catch(e) {
                console.log('Clear previous scanner:', e);
            }
        }
        
        // Initialize HTML5 QR Code Scanner
        qrCodeScanner = new Html5Qrcode("qrReader");
        
        await qrCodeScanner.start(
            { facingMode: "environment" }, // Use back camera
            {
                fps: 20, // Faster scan rate
                qrbox: (viewfinderWidth, viewfinderHeight) => {
                    let minEdge = Math.min(viewfinderWidth, viewfinderHeight);
                    let qrboxSize = Math.floor(minEdge * 0.7);
                    return { width: qrboxSize, height: qrboxSize };
                }
            },
            (decodedText, decodedResult) => {
                // Success callback
                console.log('QR Code scanned:', decodedText);
                const cleanedSN = decodedText.trim().toUpperCase();
                serialInput.value = cleanedSN;
                
                // Manually trigger events
                serialInput.dispatchEvent(new Event('input', { bubbles: true }));
                serialInput.dispatchEvent(new Event('change', { bubbles: true }));

                stopQRScanner();
                
                // Show success message
                const successDiv = document.getElementById('scanSNSuccess');
                if (successDiv) {
                    successDiv.textContent = 'QR Code berhasil di-scan: ' + cleanedSN;
                    successDiv.classList.remove('d-none');
                    setTimeout(() => {
                        successDiv.classList.add('d-none');
                    }, 3000);
                }
                
                // Auto focus on submit button
                const submitBtn = document.getElementById('scanSNSubmitBtn');
                if (submitBtn) {
                    submitBtn.focus();
                }
            },
            (errorMessage) => {
                // Error callback - ignore, scanner will keep trying
                // console.log('QR Scan error:', errorMessage);
            }
        );
    } catch (err) {
        console.error('Error starting QR scanner:', err);
        
        // Show detailed error in the modal instead of alert
        const errorDiv = document.getElementById('scanSNError');
        if (errorDiv) {
            let errorMsg = 'Gagal membuka kamera.';
            if (err.message && err.message.includes('Permission denied')) {
                errorMsg = '<strong><i class="fas fa-ban me-2"></i>Izin kamera ditolak</strong><br><small>Klik ikon kamera di address bar browser untuk mengizinkan akses kamera.</small>';
            } else if (err.message && err.message.includes('NotAllowedError')) {
                errorMsg = '<strong><i class="fas fa-ban me-2"></i>Akses kamera diblokir</strong><br><small>Browser memblokir akses kamera. Pastikan izin sudah diberikan.</small>';
            } else {
                errorMsg = `<strong><i class="fas fa-exclamation-triangle me-2"></i>Kamera tidak tersedia</strong><br><small>${err.message || 'Pastikan browser mendukung akses kamera.'}<br>Anda tetap bisa input Serial Number secara manual.</small>`;
            }
            errorDiv.innerHTML = errorMsg;
            errorDiv.classList.remove('d-none');
        }
        stopQRScanner();
    }
}

async function stopQRScanner() {
    if (qrCodeScanner && isScanning) {
        try {
            await qrCodeScanner.stop();
            await qrCodeScanner.clear();
        } catch (err) {
            console.log('Error stopping scanner:', err);
        }
        qrCodeScanner = null;
        isScanning = false;
    }
    
    const container = document.getElementById('qrReaderContainer');
    const scanBtn = document.getElementById('scanQRBtn');
    
    if (container) {
        container.style.display = 'none';
    }
    
    if (scanBtn) {
        scanBtn.innerHTML = '<i class="fas fa-camera me-2"></i>Scan QR';
    }
}

async function closeScanSNModal() {
    // Stop scanner if running
    await stopQRScanner();
    
    const modal = document.getElementById('scanSNModal');
    if (modal) {
        modal.remove();
    }
}

function submitScanSN() {
    const form = document.getElementById('scanSNForm');
    const submitBtn = document.getElementById('scanSNSubmitBtn');
    const errorDiv = document.getElementById('scanSNError');
    const successDiv = document.getElementById('scanSNSuccess');
    
    if (!form) {
        showErrorDialog('Gagal', 'Form tidak ditemukan.');
        return;
    }
    
    // Hide previous messages
    errorDiv.classList.add('d-none');
    successDiv.classList.add('d-none');
    
    // Validate form
    const itemId = document.getElementById('scanSNItem').value;
    const serialNumber = document.getElementById('scanSNSerial').value.trim().toUpperCase();
    
    if (!itemId || !serialNumber) {
        errorDiv.textContent = 'Item dan Serial Number harus diisi!';
        errorDiv.classList.remove('d-none');
        return;
    }
    
    // Disable submit button
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memvalidasi...';
    
    const formData = new FormData(form);
    formData.append('serial_number', serialNumber);
    
    fetch('{{ route("warehouse.inventory-issuings.scan-serial-number", $issuing->id) }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.status === 'success') {
            successDiv.textContent = result.message || 'Serial Number berhasil divalidasi dan disimpan!';
            successDiv.classList.remove('d-none');
            
            // Clear form
            form.reset();
            
            // Reload page after 1 second to show updated SN
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            errorDiv.textContent = result.message || 'Gagal memvalidasi Serial Number';
            errorDiv.classList.remove('d-none');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-check me-2"></i>Validasi & Simpan';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        errorDiv.textContent = 'Terjadi kesalahan saat memvalidasi Serial Number';
        errorDiv.classList.remove('d-none');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-check me-2"></i>Validasi & Simpan';
    });
}

// Allow Enter key to submit
document.addEventListener('keypress', function(e) {
    if (e.key === 'Enter' && document.getElementById('scanSNModal')) {
        const serialInput = document.getElementById('scanSNSerial');
        if (document.activeElement === serialInput) {
            e.preventDefault();
            submitScanSN();
        }
    }
});

// State
let currentTeamId = "{{ $issuing->team_id ?? '' }}";

// Edit Received By Modal Functions
function openEditReceivedByModal() {
    const modalEl = document.getElementById('editReceivedByModal');
    const receivedBySelect = document.getElementById('receivedBySelect');
    
    // Reset options
    receivedBySelect.innerHTML = '<option value="">Loading...</option>';

    if (!currentTeamId) {
        receivedBySelect.innerHTML = '<option value="">Silakan pilih Team terlebih dahulu</option>';
        const bsModal = new bootstrap.Modal(modalEl);
        bsModal.show();
        return;
    }

    // Fetch members of the selected team
    fetch(`/warehouse/inventory-issuings/team-data/${currentTeamId}/members`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                receivedBySelect.innerHTML = '<option value="">Pilih User</option>';
                const members = data.data;
                
                if (members.length === 0) {
                    const option = document.createElement('option');
                    option.text = "Tidak ada anggota di team ini";
                    receivedBySelect.add(option);
                } else {
                    members.forEach(member => {
                        const option = document.createElement('option');
                        option.value = member.id;
                        option.text = member.name;
                        if (member.id == "{{ $issuing->received_by }}") {
                            option.selected = true;
                        }
                        receivedBySelect.add(option);
                    });
                }
            } else {
                receivedBySelect.innerHTML = '<option value="">Gagal memuat network</option>';
            }
        })
        .catch(error => {
            console.error('Error fetching team members:', error);
            receivedBySelect.innerHTML = '<option value="">Error memuat data</option>';
        });

    const bsModal = new bootstrap.Modal(modalEl);
    bsModal.show();
}

function closeEditReceivedByModal() {
    const modalEl = document.getElementById('editReceivedByModal');
    const bsModal = bootstrap.Modal.getInstance(modalEl);
    if (bsModal) {
        bsModal.hide();
    } else {
        // Fallback manual close
        modalEl.classList.remove('show');
        modalEl.style.display = 'none';
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) backdrop.remove();
    }
}

function saveReceivedBy(btn) {
    const receivedById = document.getElementById('receivedBySelect').value;
    
    if (!receivedById) {
        showWarningDialog('Perhatian', 'Silakan pilih user.');
        return;
    }

    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    btn.disabled = true;

    fetch('{{ route("warehouse.inventory-issuings.update", $issuing->id) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            _method: 'PUT',
            received_by: receivedById
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const userName = document.getElementById('receivedBySelect').selectedOptions[0].text;
            document.getElementById('receivedByDisplay').textContent = userName;
            closeEditReceivedByModal();
            showSuccessDialog('Berhasil', 'Received By berhasil diperbarui.');
        } else {
            showErrorDialog('Gagal', data.message || 'Received By tidak berhasil diperbarui.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorDialog('Gagal', 'Terjadi kesalahan saat memperbarui data.');
    })
    .finally(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

// Edit Team Modal Functions 
function openEditTeamModal() {
    const modalEl = document.getElementById('editTeamModal');
    const teamSelect = document.getElementById('teamSelectModal');
    
    // Load teams if empty
    if (teamSelect.options.length <= 1) {
        fetch('/api/warehouse/inventory-issuings/modal-data')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' && data.data.teams) {
                    // Clear existing options except first
                    teamSelect.innerHTML = '<option value="">Pilih Team</option>';
                    
                    data.data.teams.forEach(team => {
                        const option = document.createElement('option');
                        option.value = team.id;
                        option.textContent = team.team_name;
                        if (team.id == (currentTeamId || "{{ $issuing->team_id ?? '' }}")) {
                            option.selected = true;
                        }
                        teamSelect.appendChild(option);
                    });
                }
            })
            .catch(err => {
                console.error('Failed to load teams', err);
                showErrorDialog('Gagal', 'Gagal memuat data team.');
            });
    } else {
        // Update selection based on current state
        if (currentTeamId) {
             teamSelect.value = currentTeamId;
        }
    }

    const bsModal = new bootstrap.Modal(modalEl);
    bsModal.show();
}

function closeEditTeamModal() {
    const modalEl = document.getElementById('editTeamModal');
    const bsModal = bootstrap.Modal.getInstance(modalEl);
    if (bsModal) {
        bsModal.hide();
    } else {
        // Fallback manual close
        modalEl.classList.remove('show');
        modalEl.style.display = 'none';
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) backdrop.remove();
    }
}

async function saveTeam(btn) {
    const saveBtn = btn || event.target;
    const teamId = document.getElementById('teamSelectModal').value;
    
    if (!teamId) {
        showWarningDialog('Perhatian', 'Silakan pilih team.');
        return;
    }

    const originalText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    saveBtn.disabled = true;

    try {
        const response = await fetch('{{ route("warehouse.inventory-issuings.update", $issuing->id) }}', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                team_id: teamId
            })
        });

        const data = await response.json();

        if (data.status === 'success') {
            // Update display
            const teamName = document.getElementById('teamSelectModal').selectedOptions[0].text;
            document.getElementById('teamDisplay').textContent = teamName;
            
            // Update local state
            currentTeamId = teamId;
            
            // Clear Received By as it might not be valid anymore for new team
            document.getElementById('receivedByDisplay').textContent = '-';
            
            closeEditTeamModal();
            showSuccessDialog('Berhasil', 'Team berhasil diperbarui. Silakan pilih "Diberikan Kepada" yang sesuai.');
        } else {
            showErrorDialog('Gagal', data.message || 'Team tidak berhasil diperbarui.');
        }
    } catch (error) {
        console.error('Error:', error);
        showErrorDialog('Gagal', 'Terjadi kesalahan saat memperbarui team.');
    } finally {
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
    }
}
</script>
<!-- Change Aroma Modal -->
<div class="modal fade" id="changeAromaModal" tabindex="-1" aria-labelledby="changeAromaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changeAromaModalLabel">Change Aroma</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="changeAromaForm">
                    @csrf
                    <input type="hidden" id="changeAromaItemId" name="item_id">
                    <input type="hidden" id="currentPackageSizeId" name="current_package_size_id">
                    <input type="hidden" id="currentBrandLine" name="current_brand_line">
                    
                    <div class="mb-3">
                        <label class="form-label">Current Product</label>
                        <input type="text" class="form-control" id="currentProductName" readonly disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">New Product (Same Brand Line & Size)</label>
                        <select class="form-control select2" id="newProductSelect" name="new_product_id" style="width: 100%;">
                            <option value="">Select New Aroma...</option>
                            <!-- Populated via AJAX -->
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Quantity to Change</label>
                        <input type="number" class="form-control" id="changeQuantity" name="quantity" min="1" step="0.01">
                        <small class="text-muted">Max: <span id="maxQuantityDisplay">0</span></small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Reason</label>
                        <textarea class="form-control" name="change_reason" rows="2" placeholder="Why is this aroma being changed?"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitChangeAroma(this)">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<script>
    function openChangeAromaModal(itemId, productName, maxQty, packageSizeId, brandLine) {
        document.getElementById('changeAromaItemId').value = itemId;
        document.getElementById('currentProductName').value = productName;
        document.getElementById('changeQuantity').value = 1; // Default to 1
        document.getElementById('changeQuantity').max = maxQty;
        document.getElementById('maxQuantityDisplay').textContent = maxQty;
        document.getElementById('currentPackageSizeId').value = packageSizeId;
        document.getElementById('currentBrandLine').value = brandLine;
        
        // Reset Select2
        $('#newProductSelect').empty().append('<option value="">Select New Aroma...</option>');
        
        // Load candidates via AJAX
        $.ajax({
            url: '{{ route("warehouse.inventory-issuings.get-replacement-candidates") }}',
            data: {
                packaging_size_id: packageSizeId,
                brand_line: brandLine,
                warehouse_id: '{{ $issuing->warehouse_id }}'
            },
            success: function(response) {
                if(response.status === 'success' && response.data) {
                    response.data.forEach(function(product) {
                        var statusLabel = '';
                        if (!product.is_selectable) {
                            statusLabel = ' (' + product.reason + ')';
                        }
                        
                        var optionText = product.name + ' [Stock: ' + product.stock + ']' + statusLabel;
                        var option = new Option(optionText, product.id, false, false);
                        
                        if (!product.is_selectable) {
                            $(option).prop('disabled', true);
                            $(option).css('color', '#999');
                        }
                        
                        $('#newProductSelect').append(option);
                    });
                    $('#newProductSelect').trigger('change');
                } else {
                    console.error('Aroma candidates response issue:', response);
                    if (response.status === 'error') {
                        showErrorDialog('Gagal', response.message || 'Gagal memuat produk pengganti.');
                    }
                }
            },
            error: function() {
                showErrorDialog('Gagal', 'Gagal memuat produk pengganti.');
            }
        });

        var modalEl = document.getElementById('changeAromaModal');
        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }

    function closeChangeAromaModal() {
        var modalEl = document.getElementById('changeAromaModal');
        var modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) {
            modal.hide();
        }
    }

    function submitChangeAroma(btn) {
        var itemId = document.getElementById('changeAromaItemId').value;
        var newProductId = document.getElementById('newProductSelect').value;
        var quantity = document.getElementById('changeQuantity').value;
        
        if (!newProductId) {
            showWarningDialog('Perhatian', 'Silakan pilih produk baru.');
            return;
        }

        var saveBtn = $(btn);
        saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');

        var formData = new FormData(document.getElementById('changeAromaForm'));

        $.ajax({
            url: '/warehouse/inventory-issuings/items/' + itemId + '/change-aroma',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.status === 'success') {
                    showSuccessDialog('Berhasil', 'Aroma berhasil diubah. Update kontrak telah dimulai.');
                    window.location.reload();
                } else {
                    showErrorDialog('Gagal', response.message || 'Aroma tidak berhasil diubah.');
                    saveBtn.prop('disabled', false).text('Save Changes');
                }
            },
            error: function(xhr) {
                var msg = 'An error occurred';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                showErrorDialog('Gagal', msg);
                saveBtn.prop('disabled', false).text('Save Changes');
            }
        });
    }
</script>

@endpush
