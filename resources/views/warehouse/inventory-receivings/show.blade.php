@extends('layouts.app')

@section('title', 'Inventory Receiving Detail')

@section('content')

@php
    $isInventoryTransferReceiving = \Illuminate\Support\Str::startsWith((string) $receiving->reference_no, 'TR-');
@endphp

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
    }
    /* Flatpickr Customization */
    .flatpickr-input {
        background-color: white !important;
    }
</style>

<!-- Flatpickr CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/airbnb.css">

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header - LAYOUT BARU -->
            <div class="card mb-3" style="background-color: #1e3a8a; color: white; border: none; border-radius: 10px;">
                <div class="card-header" style="background-color: #1e3a8a; border: none; padding: 1rem 1.5rem;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <a href="{{ route('warehouse.inventory-receivings.index') }}" class="btn btn-light btn-sm">
                                <i class="fas fa-arrow-left"></i> Back to List
                            </a>
                        </div>
                        <div>
                            <h3 class="card-title mb-0" style="color: white; font-size: 1.5rem; font-weight: bold;">
                                {{ $receiving->receiving_number }} - <span style="font-size: 0.9rem; font-weight: normal;">{{ $receiving->status === 'pending' ? 'Draft' : ucfirst($receiving->status) }}</span>
                            </h3>
                        </div>
                        <div>
                            @if($receiving->status === 'pending')
                            <form method="POST" action="{{ route('warehouse.inventory-receivings.finalize', $receiving->id) }}" style="display: inline;" onsubmit="return handleFinalizeReceiving(event, this)">
                                @csrf
                                <input type="hidden" name="confirm_partial" value="0">
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="fas fa-check-circle"></i> Finalize
                                </button>
                            </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Tabs - HORIZONTAL LAYOUT -->
            <div class="card mb-3">
                <div class="card-body p-0">
                    <ul class="nav nav-tabs" id="receivingTabs" role="tablist">
                        <li class="nav-item" role="presentation" style="flex: 1;">
                            <button class="nav-link active" id="receiving-info-tab" data-bs-toggle="tab" data-bs-target="#receiving-info" type="button" role="tab" aria-controls="receiving-info" aria-selected="true">
                                <i class="fas fa-info-circle me-2"></i>RECEIVING INFO
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
            <div class="tab-content" id="receivingTabsContent">
                <!-- Receiving Info Tab -->
                <div class="tab-pane fade show active" id="receiving-info" role="tabpanel" aria-labelledby="receiving-info-tab">
                    <!-- Info Section Grid - LAYOUT BARU -->
                    <div class="card mb-3">
                        <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #1e3a8a;">
                            <h5 class="card-title mb-0" style="color: #1e3a8a;">
                                <i class="fas fa-info-circle me-2"></i>Receiving Information
                            </h5>
                        </div>
                        <div class="card-body">
                            @if($isInventoryTransferReceiving)
                            <div style="margin-bottom: 1rem; padding: 0.85rem 1rem; border: 1px solid #bfdbfe; background: #eff6ff; color: #1e40af; border-radius: 8px;">
                                <i class="fas fa-info-circle me-2"></i>
                                Receiving ini hanya untuk verifikasi item ber-Serial Number dari Inventory Transfer
                                <strong>{{ $receiving->reference_no }}</strong>. Item tanpa Serial Number langsung masuk ke warehouse tujuan saat transfer ditandai <strong>Received</strong>.
                            </div>
                            @endif
                            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 2rem;">
                                <!-- Row 1 -->
                                <div>
                                    <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Cabang</div>
                                    <div style="font-size: 1rem; font-weight: 600; color: #212529;">{{ $receiving->branch_name }}</div>
                                </div>
                                <div>
                                    <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Status</div>
                                    <div>
                                        <span class="badge" style="background-color: {{ $receiving->status === 'received' ? '#059669' : ($receiving->status === 'pending' ? '#d97706' : '#2563eb') }}; color: white; padding: 0.35rem 0.75rem; font-size: 0.8rem;">
                                            {{ $receiving->status === 'pending' ? 'Draft' : ucfirst($receiving->status) }}
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Nomor Referensi</div>
                                    <div style="font-size: 1rem; color: #212529;">{{ $receiving->reference_no ?? '-' }}</div>
                                </div>
                                <div>
                                    <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Diterima Oleh</div>
                                    <div style="font-size: 1rem; color: #212529;">{{ $receiving->receivedFrom?->name ?? '-' }}</div>
                                </div>
                                
                                <!-- Row 2 -->
                                <div>
                                    <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Tanggal Transaksi</div>
                                    <div style="font-size: 1rem; color: #212529;">{{ $receiving->created_at?->format('d/M/Y') ?? '-' }}</div>
                                </div>
                                <div>
                                    <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Tanggal Penerimaan</div>
                                    <div style="font-size: 1rem; color: #212529;">
                                        @if($receiving->status === 'received')
                                            {{ ($receiving->receive_date ?: $receiving->updated_at)?->format('d/M/Y') ?? '-' }}
                                        @else
                                            -
                                        @endif
                                    </div>
                                </div>
                                <div style="grid-column: span 2;">
                                    <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Catatan</div>
                                    <div style="font-size: 1rem; color: #212529;">{{ $receiving->notes ?? '-' }}</div>
                                </div>
                            </div>
                            
                            @if($receiving->status === 'pending')
                            <div class="mt-3 text-end">
                                <button type="button" onclick="openEditReceivingModal()" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-edit me-1"></i>Edit Informasi
                                </button>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Audit Information Section - Compact Design -->
                    <div class="card mb-3">
                        <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #1e3a8a; padding: 0.75rem 1.5rem;">
                            <h5 class="card-title mb-0" style="color: #1e3a8a; font-size: 0.95rem;">
                                <i class="fas fa-history me-2"></i>Audit Information
                            </h5>
                        </div>
                        <div class="card-body" style="padding: 1rem 1.5rem;">
                            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem;">
                                <div>
                                    <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.25rem;">Created By</div>
                                    <div style="font-size: 0.9rem; color: #212529;">{{ $receiving->createdBy->name ?? '-' }}</div>
                                </div>
                                <div>
                                    <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.25rem;">Created At</div>
                                    <div style="font-size: 0.9rem; color: #212529;">{{ $receiving->created_at ? $receiving->created_at->format('d/M/Y H:i') : '-' }}</div>
                                </div>
                                <div>
                                    <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.25rem;">Last Updated By</div>
                                    <div style="font-size: 0.9rem; color: #212529;">{{ $receiving->updatedBy->name ?? '-' }}</div>
                                </div>
                                <div>
                                    <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.25rem;">Last Updated At</div>
                                    <div style="font-size: 0.9rem; color: #212529;">{{ $receiving->updated_at ? $receiving->updated_at->format('d/M/Y H:i') : '-' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Products Tab -->
                <div class="tab-pane fade" id="products" role="tabpanel" aria-labelledby="products-tab">
                    @php $isPending = $receiving->status === 'pending'; @endphp
                    <div class="card" style="width: 100%; min-height: 500px;">
                        <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #1e3a8a;">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0" style="color: #1e3a8a;">
                                    <i class="fas fa-box me-2"></i>Products
                                </h5>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            @if($isInventoryTransferReceiving)
                            <div style="margin: 1rem 1.5rem; padding: 0.85rem 1rem; border: 1px solid #bfdbfe; background: #eff6ff; color: #1e40af; border-radius: 8px;">
                                <i class="fas fa-barcode me-2"></i>
                                Daftar produk di sini adalah item yang perlu verifikasi Serial Number dari transfer
                                <strong>{{ $receiving->reference_no }}</strong>.
                            </div>
                            @endif
                            <div class="table-responsive" style="overflow-x: auto; max-width: 100%;">
                                <table class="table table-bordered table-striped" style="min-width: 1000px; white-space: nowrap;">
                                    <thead>
                                        <tr>
                                            <th>Product Name</th>
                                            <th>Product Category</th>
                                            <th>SN Terdaftar</th>
                                            <th style="text-align: center;">Quantity</th>
                                            <th style="text-align: center;">Qty Received</th>
                                            <th>Last Update</th>
                                            @if($isPending)
                                            <th style="text-align: center;">Actions</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse(($receiving->items ?? []) as $item)
                                        @php
                                            $snCount = isset($serialNumbers[$item->master_product_id]) ? $serialNumbers[$item->master_product_id]->count() : 0;
                                            $hasSerial = $item->product?->requiresSerialNumber() ?? false;
                                        @endphp
                                        <tr data-product-row="{{ $item->master_product_id }}" data-item-id="{{ $item->id }}" data-total-qty="{{ (int)$item->quantity }}">
                                            <td>
                                                <strong>{{ $item->product->name ?? '-' }}</strong>
                                                @if($item->product?->sku)
                                                <br><small class="text-muted">SKU: {{ $item->product->sku }}</small>
                                                @endif
                                            </td>
                                            <td>{{ $item->product?->productCategory?->name ?? '-' }}</td>
                                            <td class="sn-registered-container">
                                                @php
                                                    // ALWAYS use total requested quantity as the denominator for display
                                                    $displayDenominator = (int)$item->quantity;
                                                @endphp

                                                @if($snCount > 0)
                                                    @if($hasSerial && $snCount < $displayDenominator)
                                                        <span class="badge bg-danger sn-registered-badge" title="Missing Serial Numbers">{{ $snCount }} / {{ $displayDenominator }}</span>
                                                    @else
                                                        <span class="badge bg-success sn-registered-badge">{{ $snCount }} / {{ $displayDenominator }}</span>
                                                    @endif
                                                @else
                                                    @if($hasSerial)
                                                        <span class="badge bg-danger sn-registered-badge" title="Serial Number Required">0 / {{ $displayDenominator }}</span>
                                                    @else
                                                        <span class="text-muted sn-registered-badge">-</span>
                                                    @endif
                                                @endif
                                            </td>
                                            <td style="text-align: center;">{{ number_format($item->quantity, 0) }}</td>
                                            <td style="text-align: center;">
                                                <span class="badge {{ ($item->quantity_received ?? 0) >= $item->quantity ? 'bg-success' : 'bg-primary' }} qty-received-badge" style="padding: 0.5rem 0.75rem; font-size: 0.9rem;">
                                                    {{ number_format($item->quantity_received ?? 0, 0) }}
                                                </span>
                                            </td>
                                            <td>
                                                {{ $item->updated_at?->format('d/M/Y H:i') ?? '-' }}
                                                @if($item->updatedBy || $receiving->updatedBy)
                                                <br><small class="text-muted">by {{ $item->updatedBy?->name ?? $receiving->updatedBy?->name }}</small>
                                                @endif
                                            </td>
                                            @if($isPending)
                                            <td style="text-align: center;">
                                                @if(!$hasSerial)
                                                <button type="button" class="btn btn-sm btn-success me-1"
                                                    onclick='openUpdateQtyModal({{ $item->id }}, {{ $item->master_product_id }}, @json($item->product->name ?? "-"), {{ (int)$item->quantity }}, {{ (int)($item->quantity_received ?? 0) }})'
                                                    title="Update Qty Received">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                @endif
                                                <button type="button" class="btn btn-sm btn-primary"
                                                    onclick="openScanSNModal('{{ $item->master_product_id }}')"
                                                    title="{{ $hasSerial ? 'Scan QR / Input SN' : 'Input Batch SN (Optional)' }}">
                                                    <i class="fas fa-qrcode"></i>
                                                </button>
                                            </td>
                                            @endif
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="{{ $isPending ? '7' : '6' }}" class="text-center text-muted">
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
                            </div>
                        </div>
                        <div class="card-body">
                            @if(isset($serialNumbers) && count($serialNumbers) > 0)
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover" style="width: 100%;">
                                    <thead class="table-light" style="background-color: #f8f9fa;">
                                        <tr>
                                            <th style="width: 25%; padding: 12px; font-weight: 600; color: #495057;">Product Name</th>
                                            <th style="width: 18%; padding: 12px; font-weight: 600; color: #495057;">Serial Number</th>
                                            <th style="width: 12%; padding: 12px; font-weight: 600; color: #495057;">Status</th>
                                            <th style="width: 18%; padding: 12px; font-weight: 600; color: #495057;">Warehouse</th>
                                            <th style="width: 17%; padding: 12px; font-weight: 600; color: #495057;">Notes</th>
                                            @if($receiving->status === 'pending')
                                            <th style="width: 10%; padding: 12px; font-weight: 600; color: #495057; text-align: center;">Action</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $renderedProductSerials = []; @endphp
                                        @foreach($receiving->items as $item)
                                            @if(in_array($item->master_product_id, $renderedProductSerials, true))
                                                @continue
                                            @endif
                                            @php $renderedProductSerials[] = $item->master_product_id; @endphp
                                            @if(isset($serialNumbers[$item->master_product_id]) && $serialNumbers[$item->master_product_id]->count() > 0)
                                                @foreach($serialNumbers[$item->master_product_id] as $sn)
                                                <tr data-sn-id="{{ $sn->id }}" data-product-id="{{ $item->master_product_id }}">
                                                    <td style="padding: 12px; vertical-align: middle;">
                                                        <strong>{{ $item->product->name ?? 'Unknown Product' }}</strong>
                                                        @if($item->product?->productCategory)
                                                        <br><small class="text-muted">{{ $item->product->productCategory->name }}</small>
                                                        @endif
                                                    </td>
                                                    <td style="padding: 12px; vertical-align: middle;">
                                                        <strong style="font-family: monospace; font-size: 1rem; color: #10b981;">{{ $sn->serial_number }}</strong>
                                                    </td>
                                                    <td style="padding: 12px; vertical-align: middle;">
                                                        @php
                                                            $statusClass = 'secondary';
                                                            $statusText = ucfirst(str_replace('_', ' ', $sn->status));
                                                            if (in_array($sn->status, ['ready', 'available'])) {
                                                                $statusClass = 'success';
                                                                $statusText = 'Ready';
                                                            } elseif ($sn->status === 'pending') {
                                                                $statusClass = 'warning';
                                                                $statusText = 'Pending';
                                                            } elseif (in_array($sn->status, ['broken', 'damaged'])) {
                                                                $statusClass = 'danger';
                                                                $statusText = 'Broken';
                                                            } elseif (in_array($sn->status, ['on_service', 'maintenance'])) {
                                                                $statusClass = 'warning';
                                                                $statusText = 'On Service';
                                                            } elseif ($sn->status === 'in_use') {
                                                                $statusClass = 'info';
                                                                $statusText = 'In Use';
                                                            }
                                                        @endphp
                                                        <span class="badge bg-{{ $statusClass }}" style="font-size: 0.85rem; padding: 6px 10px;">
                                                            {{ $statusText }}
                                                        </span>
                                                    </td>
                                                    <td style="padding: 12px; vertical-align: middle;">
                                                        @if($sn->warehouse)
                                                        <small>{{ $sn->warehouse->name }}</small>
                                                        @else
                                                        <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                    <td style="padding: 12px; vertical-align: middle;">
                                                        <small class="text-muted">{{ $sn->notes ?? '-' }}</small>
                                                    </td>
                                                    @if($receiving->status === 'pending')
                                                    <td style="padding: 12px; vertical-align: middle; text-align: center;">
                                                        <button type="button" class="btn btn-danger btn-sm delete-sn-btn" 
                                                            data-sn-id="{{ $sn->id }}"
                                                            data-sn-code="{{ $sn->serial_number }}"
                                                            onclick="deleteSerialNumber({{ $sn->id }}, '{{ $sn->serial_number }}')">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </td>
                                                    @endif
                                                </tr>
                                                @endforeach
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @else
                                <div class="text-center text-muted" style="padding: 3rem;">
                                    <i class="fas fa-info-circle fa-3x mb-3"></i>
                                    <p class="mb-0">Tidak ada serial number untuk produk dalam receiving ini.</p>
                                    @if($receiving->status === 'pending')
                                    <p class="text-sm mt-2">Klik tombol "Scan QR / Input SN" untuk menambahkan serial number.</p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Old Bootstrap modal removed - using custom modal instead -->

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
$(document).ready(function() {
    // Tab switching functionality using Bootstrap 5
    $('#receivingTabs button[data-bs-toggle="tab"]').on('click', function(e) {
        e.preventDefault();
        var target = $(this).attr('data-bs-target');
        
        // Remove active class from all tabs and content
        $('#receivingTabs button').removeClass('active');
        $('.tab-pane').removeClass('show active');
        
        // Add active class to clicked tab
        $(this).addClass('active');
        $(target).addClass('show active');
        
        // Update URL hash
        window.location.hash = target.replace('#', '');
    });

    // Handle initial tab based on URL hash
    var hash = window.location.hash;
    if (hash) {
        var tabTriggerEl = document.querySelector('#receivingTabs button[data-bs-target="' + hash + '"]');
        if (tabTriggerEl) {
            tabTriggerEl.click();
        }
    }
});

function openEditReceivingModal() {
    const modal = document.createElement('div');
    modal.id = 'customEditReceivingModal';
    modal.style.cssText = `
        display: flex !important;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
        z-index: 9999;
        align-items: center;
        justify-content: center;
        overflow-y: auto;
    `;
    modal.innerHTML = `
        <div class="modal-dialog" style="margin: 20px auto; max-width: 500px;">
            <div class="modal-content" style="background-color: white; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); border: none;">
                <div class="modal-header" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border-radius: 12px 12px 0 0; padding: 20px 24px; border-bottom: none;">
                    <h5 class="modal-title" style="font-weight: 600; font-size: 1.25rem;">
                        <i class="fas fa-edit me-2"></i>Edit Informasi Penerimaan
                    </h5>
                    <button type="button" class="btn-close btn-close-white" onclick="closeEditReceivingModal()"></button>
                </div>
                <form id="customEditReceivingForm">
                    @csrf
                    <div class="modal-body" style="background-color: white; padding: 24px;">
                        @if($receiving->status === 'pending')
                        <div class="mb-4">
                            <label class="form-label" style="font-weight: 600; color: #374151; margin-bottom: 8px; display: block;">Diterima Oleh</label>
                            <select name="received_from" class="form-control" style="padding: 10px 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px;">
                                <option value="">Pilih User</option>
                                @foreach(\App\Models\User::orderBy('name')->get() as $user)
                                <option value="{{ $user->id }}" {{ $receiving->received_from == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @else
                        <div class="mb-4">
                            <label class="form-label" style="font-weight: 600; color: #374151; margin-bottom: 8px; display: block;">Diterima Oleh</label>
                            <input type="text" class="form-control" value="{{ $receiving->receivedFrom?->name ?? '-' }}" readonly disabled style="padding: 10px 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px; background-color: #f3f4f6;">
                            <small class="text-muted"><i class="fas fa-info-circle me-1"></i>Tidak dapat diubah karena status bukan pending</small>
                        </div>
                        @endif
                        <div class="mb-4">
                            <label class="form-label" style="font-weight: 600; color: #374151; margin-bottom: 8px; display: block;">Catatan Tambahan</label>
                            <textarea name="notes" rows="4" class="form-control" style="padding: 10px 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px; resize: vertical;">{{ $receiving->notes ?? '' }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer" style="background-color: #f9fafb; border-radius: 0 0 12px 12px; padding: 16px 24px; border-top: 1px solid #e5e7eb; gap: 10px;">
                        <button type="button" class="btn btn-secondary" onclick="closeEditReceivingModal()" style="padding: 10px 20px; border-radius: 8px; font-weight: 500;">Batal</button>
                        <button type="button" class="btn btn-primary" onclick="saveReceivingInfo()" style="padding: 10px 20px; border-radius: 8px; font-weight: 500; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none;">
                            <i class="fas fa-save me-2"></i>Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
    
}

function closeEditReceivingModal() {
    const modal = document.getElementById('customEditReceivingModal');
    if (modal) {
        modal.remove();
    }
}

function openUpdateQtyModal(itemId, productId, productName, totalQty, currentQty) {
    const modal = document.createElement('div');
    const safeProductName = escapeHtml(productName);
    modal.id = 'updateQtyModal';
    modal.style.cssText = `
        display: flex !important;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
        z-index: 9999;
        align-items: center;
        justify-content: center;
        overflow-y: auto;
    `;

    modal.innerHTML = `
        <div class="modal-dialog" style="margin: auto; width: 100%; max-width: 460px;">
            <div class="modal-content" style="background-color: white; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); border: none;">
                <div class="modal-header" style="background: linear-gradient(135deg, #059669 0%, #10b981 100%); color: white; border-radius: 12px 12px 0 0; padding: 16px 20px; border-bottom: none;">
                    <h5 class="modal-title" style="font-weight: 600; font-size: 1.15rem;">
                        <i class="fas fa-edit me-2"></i>Update Qty Received
                    </h5>
                    <button type="button" class="btn-close btn-close-white" onclick="closeUpdateQtyModal()"></button>
                </div>
                <form id="updateQtyForm" onsubmit="return false;">
                    @csrf
                    <div class="modal-body" style="background-color: white; padding: 24px;">
                        <div class="mb-4 p-3" style="background-color: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px;">
                            <div style="font-size: 0.75rem; color: #047857; font-weight: 600; text-transform: uppercase;">Produk</div>
                            <div style="font-size: 1.05rem; color: #064e3b; font-weight: 700;">${safeProductName}</div>
                            <div class="mt-1 text-muted">Quantity: ${totalQty}</div>
                        </div>
                        <input type="hidden" name="item_id" value="${itemId}">
                        <input type="hidden" id="updateQtyProductId" value="${productId}">
                        <div class="mb-3">
                            <label class="form-label" style="font-weight: 600; color: #374151; margin-bottom: 8px; display: block;">
                                Qty Received
                            </label>
                            <input type="number" name="quantity_received" id="updateQtyReceivedInput" class="form-control" min="0" max="${totalQty}" step="1" value="${currentQty}" required
                                style="padding: 10px 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px;">
                            <small class="text-muted mt-1 d-block">Qty yang tidak diterima tidak akan masuk stok saat finalize.</small>
                        </div>
                        <div id="updateQtyError" class="alert alert-danger d-none" role="alert"></div>
                    </div>
                    <div class="modal-footer" style="background-color: #f9fafb; border-radius: 0 0 12px 12px; padding: 16px 20px; border-top: 1px solid #e5e7eb; gap: 10px;">
                        <button type="button" class="btn btn-secondary" onclick="closeUpdateQtyModal()" style="padding: 8px 16px; border-radius: 8px; font-weight: 500;">Cancel</button>
                        <button type="button" class="btn btn-success no-double-click-prevention" onclick="submitUpdateQty()" id="updateQtySubmitBtn" style="padding: 8px 16px; border-radius: 8px; font-weight: 500;">
                            <i class="fas fa-save me-2"></i>Simpan Qty
                        </button>
                    </div>
                </form>
            </div>
        </div>
    `;

    document.body.appendChild(modal);
    setTimeout(() => document.getElementById('updateQtyReceivedInput')?.focus(), 100);
}

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
}

function closeUpdateQtyModal() {
    const modal = document.getElementById('updateQtyModal');
    if (modal) {
        modal.remove();
    }
}

function submitUpdateQty() {
    const form = document.getElementById('updateQtyForm');
    const submitBtn = document.getElementById('updateQtySubmitBtn');
    const errorDiv = document.getElementById('updateQtyError');

    if (!form) {
        showErrorDialog('Gagal', 'Form tidak ditemukan.');
        return;
    }

    errorDiv.classList.add('d-none');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Menyimpan...';

    fetch('{{ route("warehouse.inventory-receivings.update-item-quantity", $receiving->id) }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: new FormData(form)
    })
    .then(response => response.json())
    .then(result => {
        if (result.status === 'success') {
            const productId = document.getElementById('updateQtyProductId')?.value;
            const qtyReceived = parseInt(result.data?.quantity_received ?? 0);
            const row = productId ? document.querySelector(`tr[data-product-row="${productId}"]`) : null;

            if (row) {
                const totalQty = parseInt(row.getAttribute('data-total-qty') || 0);
                const qtyBadge = row.querySelector('.qty-received-badge');
                if (qtyBadge) {
                    qtyBadge.textContent = qtyReceived;
                    qtyBadge.classList.toggle('bg-success', qtyReceived >= totalQty);
                    qtyBadge.classList.toggle('bg-primary', qtyReceived < totalQty);
                }
                globalRemainingQty[productId] = Math.max(0, totalQty - qtyReceived);
            }

            closeUpdateQtyModal();
            showNotification('success', result.message || 'Quantity Received berhasil diupdate!');
        } else {
            errorDiv.textContent = result.message || 'Gagal mengupdate Quantity Received';
            errorDiv.classList.remove('d-none');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Simpan Qty';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        errorDiv.textContent = 'Terjadi kesalahan saat mengupdate Quantity Received';
        errorDiv.classList.remove('d-none');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Simpan Qty';
    });
}

function handleFinalizeReceiving(event, form) {
    event.preventDefault();

    const items = globalReceivingItems || [];
    const shortfallProducts = [];
    items.forEach((item) => {
        const productId = item.master_product_id;
        if (!productId || shortfallProducts.some(p => p.id === productId)) {
            return;
        }
        const remaining = globalRemainingQty[productId] ?? 0;
        if (remaining > 0) {
            shortfallProducts.push({ id: productId, name: item.product?.name || `Product #${productId}`, remaining });
        }
    });

    const hasShortfall = shortfallProducts.length > 0;
    const confirmPartialInput = form ? form.querySelector('input[name="confirm_partial"]') : null;

    const message = hasShortfall
        ? 'Jumlah barang yang diterima kurang dari yang diminta untuk: ' +
          shortfallProducts.map(p => `${p.name} (kurang ${p.remaining})`).join(', ') +
          '. Kekurangan ini akan dianggap tidak diterima dan request akan tetap diselesaikan (completed). Lanjutkan finalize?'
        : 'Apakah Anda yakin ingin finalize receiving ini? Stok akan otomatis bertambah dan request akan di-complete.';

    showConfirmDialog(
        'Finalize Receiving',
        message,
        'Ya, Finalize',
        'Batal'
    ).then((confirmed) => {
        if (confirmed && form) {
            if (confirmPartialInput) {
                confirmPartialInput.value = hasShortfall ? '1' : '0';
            }
            form.submit();
        }
    });

    return false;
}

function saveReceivingInfo() {
    const form = document.getElementById('customEditReceivingForm');
    if (!form) {
        showErrorDialog('Gagal', 'Form tidak ditemukan.');
        return;
    }
    
    const formData = new FormData(form);
    formData.append('_method', 'PUT');
    
    fetch('{{ route("warehouse.inventory-receivings.update", $receiving->id) }}', {
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
            // Close modal
            closeEditReceivingModal();
            
            // Show success message
            showSuccessDialog('Berhasil', 'Informasi receiving berhasil diperbarui.');
            
            // Reload page to show updated data
            location.reload();
        } else {
            showErrorDialog('Gagal', result.message || 'Informasi receiving tidak berhasil diperbarui.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorDialog('Gagal', 'Terjadi kesalahan jaringan.');
    });
}
// Global variables for scanner and scanning state
let qrCodeScannerReceiving = null;
let isScanningReceiving = false;
let lastActionWasQR = false;

// Global state for items and remaining quantities to ensure synchronization without reload
const globalReceivingItems = @json($receiving->items);
let globalRemainingQty = @json(collect($remainingQuantities ?? [])->map(fn($val) => (int)$val)->toArray());

function openScanSNModal(preSelectedProductId = null) {
    const modal = document.createElement('div');
    modal.id = 'scanSNModal';
    modal.style.cssText = `
        display: flex !important;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
        z-index: 9999;
        align-items: center;
        justify-content: center;
        overflow-y: auto;
    `;
    
    const items = @json($receiving->items);
    const remainingQuantities = @json($remainingQuantities ?? []);
    
    let selectedProductText = '';
    let selectedProductRemaining = 0;
    
    if (preSelectedProductId) {
        const item = globalReceivingItems.find(i => i.master_product_id == preSelectedProductId);
        if (item) {
            selectedProductText = item.product?.name || 'Unknown';
            selectedProductRemaining = globalRemainingQty[preSelectedProductId] ?? item.quantity;
        }
    }

    // Filter items to only show products with remaining quantity > 0
    const seenProductIds = new Set();
    const itemsOptions = globalReceivingItems
        .filter(item => {
            if (seenProductIds.has(item.master_product_id)) {
                return false;
            }
            seenProductIds.add(item.master_product_id);
            const remaining = globalRemainingQty[item.master_product_id] ?? item.quantity;
            return remaining > 0;
        })
        .map(item => {
            const remaining = remainingQuantities[item.master_product_id] ?? item.quantity;
            return `<option value="${item.master_product_id}" ${preSelectedProductId == item.master_product_id ? 'selected' : ''}>${item.product?.name || 'Unknown'} (Sisa: ${remaining})</option>`;
        })
        .join('');
    
    modal.innerHTML = `
        <div class="modal-dialog" style="margin: auto; width: 100%; max-width: 600px;">
            <div class="modal-content" style="background-color: white; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); border: none;">
                <div class="modal-header" style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); color: white; border-radius: 12px 12px 0 0; padding: 16px 20px; border-bottom: none;">
                    <h5 class="modal-title" style="font-weight: 600; font-size: 1.15rem;">
                        <i class="fas fa-qrcode me-2"></i>Scan QR / Input Serial Number
                    </h5>
                    <button type="button" class="btn-close btn-close-white" onclick="closeScanSNModal()"></button>
                </div>
                <form id="scanSNForm" onsubmit="return false;">
                    @csrf
                    <div class="modal-body" style="background-color: white; padding: 24px;">
                        <div class="mb-4" id="productSelectionSection" style="${preSelectedProductId ? 'display: none;' : ''}">
                            <label class="form-label" style="font-weight: 600; color: #374151; margin-bottom: 8px; display: block;">
                                <i class="fas fa-box me-2"></i>Produk <span class="text-danger">*</span>
                            </label>
                            <select name="master_product_id" id="scanSNProduct" class="form-control" required style="padding: 10px 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px;">
                                <option value="">Pilih Produk</option>
                                ${itemsOptions}
                            </select>
                        </div>
                        
                        ${preSelectedProductId ? `
                        <div class="mb-4 p-3" style="background-color: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div style="font-size: 0.75rem; color: #1e40af; font-weight: 600; text-transform: uppercase;">Produk Terpilih</div>
                                    <div style="font-size: 1.1rem; color: #1e3a8a; font-weight: 700;">${selectedProductText}</div>
                                </div>
                                <div class="text-end">
                                    <div style="font-size: 0.75rem; color: #1e40af; font-weight: 600; text-transform: uppercase;">Sisa</div>
                                    <div id="modalRemainingQty" style="font-size: 1.5rem; color: #1d4ed8; font-weight: 800;">${selectedProductRemaining}</div>
                                </div>
                            </div>
                            <input type="hidden" name="master_product_id" value="${preSelectedProductId}">
                        </div>
                        ` : ''}
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
                                <button type="button" class="btn btn-primary" onclick="startQRScannerReceiving()" id="scanQRBtnReceiving" style="border-radius: 0 8px 8px 0; border: 2px solid #1e3a8a; border-left: none; background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);">
                                    <i class="fas fa-camera me-2"></i>Scan
                                </button>
                            </div>
                            <div id="qrReaderContainerReceiving" style="display: none; margin-top: 15px; border: 2px solid #e5e7eb; border-radius: 8px; padding: 15px; background-color: #f9fafb;">
                                <div id="qrReaderReceiving" style="width: 100%; max-width: 500px; margin: 0 auto;"></div>
                                <div class="text-center mt-3">
                                    <button type="button" class="btn btn-sm btn-secondary" onclick="stopQRScannerReceiving()">
                                        <i class="fas fa-times me-2"></i>Stop Camera
                                    </button>
                                </div>
                            </div>
                            <small class="text-muted mt-1 d-block">
                                <i class="fas fa-info-circle me-1"></i>
                                @if($receiving->issuing_id)
                                    Untuk receiving dari issuing/return, SN harus sudah terdaftar dan berstatus On Hand Teknisi.
                                @else
                                    System akan mengecek agar SN tidak duplikat. Jika SN belum ada, SN baru akan dibuat.
                                @endif
                            </small>
                        </div>
                        <div class="mb-4">
                            <label class="form-label" style="font-weight: 600; color: #374151; margin-bottom: 8px; display: block;">
                                <i class="fas fa-sticky-note me-2"></i>Catatan (Optional)
                            </label>
                            <textarea name="notes" rows="3" class="form-control" 
                                placeholder="Catatan tambahan untuk serial number ini"
                                style="padding: 10px 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px; resize: vertical;"></textarea>
                        </div>
                        <div id="scanSNError" class="alert alert-danger d-none" role="alert"></div>
                        <div id="scanSNSuccess" class="alert alert-success d-none" role="alert"></div>
                    </div>
                    <div class="modal-footer" style="background-color: #f9fafb; border-radius: 0 0 12px 12px; padding: 16px 20px; border-top: 1px solid #e5e7eb; gap: 10px;">
                        <button type="button" class="btn btn-secondary" onclick="closeScanSNModal()" style="padding: 8px 16px; border-radius: 8px; font-weight: 500;">Cancel</button>
                        <button type="button" class="btn btn-primary no-double-click-prevention" onclick="submitScanSN()" id="scanSNSubmitBtn" style="padding: 8px 16px; border-radius: 8px; font-weight: 500; background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); border: none;">
                            <i class="fas fa-save me-2"></i>Simpan SN
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

async function startQRScannerReceiving() {
    if (isScanningReceiving) {
        stopQRScannerReceiving();
        return;
    }
    
    const container = document.getElementById('qrReaderContainerReceiving');
    const readerDiv = document.getElementById('qrReaderReceiving');
    const scanBtn = document.getElementById('scanQRBtnReceiving');
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
        isScanningReceiving = true;
        
        // Clear previous scanner
        if (qrCodeScannerReceiving) {
            try {
                await qrCodeScannerReceiving.clear();
            } catch(e) {
                console.log('Clear previous scanner:', e);
            }
        }
        
        // Initialize HTML5 QR Code Scanner
        qrCodeScannerReceiving = new Html5Qrcode("qrReaderReceiving");
        
        await qrCodeScannerReceiving.start(
            { facingMode: "environment" }, // Use back camera
            {
                fps: 20, // Increase FPS for smoother scanning
                qrbox: (viewfinderWidth, viewfinderHeight) => {
                    // Responsive qrbox: 70% of the shortest side
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
                
                // Manually trigger events so any listeners know the value changed
                serialInput.dispatchEvent(new Event('input', { bubbles: true }));
                serialInput.dispatchEvent(new Event('change', { bubbles: true }));

                lastActionWasQR = true;
                stopQRScannerReceiving();
                
                // Show success message
                const successDiv = document.getElementById('scanSNSuccess');
                if (successDiv) {
                    successDiv.textContent = 'QR Code berhasil di-scan: ' + cleanedSN;
                    successDiv.classList.remove('d-none');
                }
                
                // Auto submit after QR scan success
                setTimeout(() => {
                    submitScanSN();
                }, 100);
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
        stopQRScannerReceiving();
    }
}

async function stopQRScannerReceiving() {
    if (qrCodeScannerReceiving && isScanningReceiving) {
        try {
            await qrCodeScannerReceiving.stop();
            await qrCodeScannerReceiving.clear();
        } catch (err) {
            console.log('Error stopping scanner:', err);
        }
        qrCodeScannerReceiving = null;
        isScanningReceiving = false;
        // Don't reset lastActionWasQR here because we need it in submitScanSN
    }
    
    const container = document.getElementById('qrReaderContainerReceiving');
    const scanBtn = document.getElementById('scanQRBtnReceiving');
    
    if (container) {
        container.style.display = 'none';
    }
    
    if (scanBtn) {
        scanBtn.innerHTML = '<i class="fas fa-camera me-2"></i>Scan QR';
    }
}

async function closeScanSNModal() {
    // Stop scanner if running
    await stopQRScannerReceiving();
    
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
    const productInput = form.querySelector('input[name="master_product_id"]') || document.getElementById('scanSNProduct');
    const productId = productInput ? productInput.value : '';
    const serialNumber = document.getElementById('scanSNSerial').value.trim().toUpperCase();
    
    if (!productId || !serialNumber) {
        errorDiv.textContent = 'Produk dan Serial Number harus diisi!';
        errorDiv.classList.remove('d-none');
        return;
    }
    
    // Disable submit button
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Menyimpan...';
    
    const formData = new FormData(form);
    formData.append('serial_number', serialNumber);
    
    fetch('{{ route("warehouse.inventory-receivings.scan-serial-number", $receiving->id) }}', {
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
            const newRemaining = result.remaining_quantity;
            
            // Update global state
            const currentProductInput = document.querySelector('#scanSNForm input[name="master_product_id"]') || document.getElementById('scanSNProduct');
            const currentProductId = currentProductInput ? currentProductInput.value : '';
            globalRemainingQty[currentProductId] = newRemaining;
            
            // Display success message
            successDiv.textContent = result.message || 'Serial Number berhasil disimpan!';
            successDiv.classList.remove('d-none');
            
            // Update UI elements in modal
            const productSelect = document.getElementById('scanSNProduct');
            const remainingQtyDisplay = document.getElementById('modalRemainingQty');

            if (productSelect && productSelect.selectedIndex >= 0) {
                const selectedOption = productSelect.options[productSelect.selectedIndex];
                if (selectedOption && selectedOption.value) {
                    const productName = selectedOption.text.replace(/\s*\(Sisa: \d+\)$/, '');
                    if (newRemaining <= 0) {
                        productSelect.remove(productSelect.selectedIndex);
                    } else {
                        selectedOption.text = `${productName} (Sisa: ${newRemaining})`;
                    }
                }
            }
            
            if (remainingQtyDisplay) {
                remainingQtyDisplay.textContent = newRemaining;
            }
            
            // Sync background table in Products tab
            const bgRow = document.querySelector(`tr[data-product-row="${currentProductId}"]`);
            if (bgRow) {
                const totalQty = parseInt(bgRow.getAttribute('data-total-qty') || 0);
                
                // Update Qty Received Badge
                const qtyBadge = bgRow.querySelector('.qty-received-badge');
                if (qtyBadge) {
                    const currentReceivedLimit = totalQty - newRemaining;
                    qtyBadge.textContent = currentReceivedLimit;
                    if (currentReceivedLimit >= totalQty) {
                        qtyBadge.classList.replace('bg-primary', 'bg-success');
                    }
                }
                
                // Update SN Registered Badge
                const snBadge = bgRow.querySelector('.sn-registered-badge');
                if (snBadge) {
                    const registeredCount = totalQty - newRemaining;
                    if (registeredCount > 0) {
                        snBadge.textContent = `${registeredCount} / ${totalQty}`;
                        snBadge.className = 'badge bg-success sn-registered-badge';
                    } else {
                        snBadge.textContent = '-';
                        snBadge.className = 'text-muted sn-registered-badge';
                    }
                }
            }
            
            if (newRemaining <= 0) {
                // Check if ALL products in receiving are now finished
                const stillPending = Object.values(globalRemainingQty).some(qty => qty > 0);
                
                if (!stillPending) {
                    successDiv.innerHTML = '<i class="fas fa-check-double me-2"></i>Semua produk telah dipenuhi! Halaman akan reload.';
                    setTimeout(() => {
                        window.location.hash = 'products';
                        location.reload();
                    }, 1000);
                } else {
                    successDiv.textContent = 'Kuantitas produk ini terpenuhi! Menutup modal...';
                    setTimeout(() => {
                        closeScanSNModal();
                    }, 1500);
                }
                return;
            }
            
            // Clear serial number input and refocus for next scan
            document.getElementById('scanSNSerial').value = '';
            document.getElementById('scanSNSerial').focus();
            
            // Re-enable submit button
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Simpan SN';
            
            // Hide success message after 2 seconds
            setTimeout(() => {
                successDiv.classList.add('d-none');
                
                // If last action was QR and still in scanning context, restart scanner
                if (lastActionWasQR && document.getElementById('scanSNModal')) {
                    startQRScannerReceiving();
                }
                lastActionWasQR = false; // Reset after attempt
            }, 1000);
        } else {
            // Use innerHTML to support HTML formatting in error message
            errorDiv.innerHTML = result.message || 'Gagal menyimpan Serial Number';
            errorDiv.classList.remove('d-none');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Simpan SN';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        errorDiv.textContent = 'Terjadi kesalahan saat menyimpan Serial Number';
        errorDiv.classList.remove('d-none');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Simpan SN';
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

// Delete Serial Number function
function deleteSerialNumber(snId, snCode) {
    showConfirmDialog(
        'Hapus Serial Number',
        `Yakin ingin menghapus Serial Number "${snCode}"? Serial Number akan dihapus dan tidak bisa digunakan di Check Serial Number.`,
        'Ya, Hapus',
        'Batal'
    ).then((confirmed) => {
        if (!confirmed) {
            return;
        }
        
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        fetch('{{ route("warehouse.inventory-receivings.delete-serial-number", $receiving->id) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                serial_number_id: snId
            })
        })
        .then(response => response.json())
        .then(result => {
            if (result.status === 'success') {
                // Remove the row from table
                const row = document.querySelector(`tr[data-sn-id="${snId}"]`);
                if (row) {
                    row.remove();
                }
                
                // Show success notification
                showNotification('success', result.message || 'Serial Number berhasil dihapus!');
                
                // Update UI in Products tab dynamically if we have the product_id
                if (result.data && result.data.product_id) {
                    const productId = result.data.product_id;
                    const newRemaining = result.data.remaining_quantity;
                    
                    // Update global state
                    globalRemainingQty[productId] = newRemaining;

                    const row = document.querySelector(`tr[data-product-row="${productId}"]`);
                    
                    if (row) {
                        const totalQty = parseInt(row.getAttribute('data-total-qty') || 0);
                        
                        // Update Qty Received Badge
                        const qtyBadge = row.querySelector('.qty-received-badge');
                        if (qtyBadge) {
                            const currentQty = parseInt(qtyBadge.textContent.trim()) || 0;
                            const newQty = Math.max(0, currentQty - 1);
                            qtyBadge.textContent = newQty;
                            
                            // Update color if needed
                            if (newQty < totalQty) {
                                qtyBadge.classList.replace('bg-success', 'bg-primary');
                            }
                        }
                        
                        // Update SN Registered Badge
                        const snContainer = row.querySelector('.sn-registered-container');
                        const snBadge = row.querySelector('.sn-registered-badge');
                        if (snBadge) {
                            const match = snBadge.textContent.match(/^(\d+) \//);
                            if (match) {
                                const currentCount = parseInt(match[1]) || 0;
                                const newCount = Math.max(0, currentCount - 1);
                                
                                if (newCount > 0) {
                                    snBadge.textContent = `${newCount} / ${totalQty}`;
                                    snBadge.className = 'badge bg-success sn-registered-badge';
                                } else {
                                    snBadge.textContent = '-';
                                    snBadge.className = 'text-muted sn-registered-badge';
                                }
                            }
                        }
                    }
                }
            } else {
                showNotification('error', result.message || 'Gagal menghapus Serial Number');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('error', 'Terjadi kesalahan saat menghapus Serial Number');
        });
    });
}


// Simple notification helper
function showNotification(type, message) {
    // Remove existing notification
    const existing = document.getElementById('toast-notification');
    if (existing) existing.remove();
    
    const bgColor = type === 'success' ? '#10b981' : (type === 'warning' ? '#f59e0b' : '#ef4444');
    
    const toast = document.createElement('div');
    toast.id = 'toast-notification';
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 12px 24px;
        background: ${bgColor};
        color: white;
        border-radius: 8px;
        z-index: 9999;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        animation: slideIn 0.3s ease;
    `;
    toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle me-2"></i>${message}`;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Add animation keyframes
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);
</script>
@endpush
