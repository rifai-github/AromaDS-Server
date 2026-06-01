@extends('layouts.app')

@section('title', 'Stock Opname Detail')

@section('content')

@php
    $user = auth()->user();
    $canViewSystemStock = $user->hasPermission('warehouse.stock-opnames.view-system-stock')
        || $user->hasRole('Admin')
        || $user->hasRole('super_admin')
        || $user->hasRoleStartingWith('Management');
@endphp

<style>
    /* Helper classes similar to Inventory Request */
    .cursor-pointer { cursor: pointer; }
    .transition-colors { transition: all 0.3s ease; }
    .text-blue-600 { color: #2563eb; }
    .hover\:text-blue-600:hover { color: #2563eb; }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            
            <!-- Header Section (Blue Card) -->
            <div class="card mb-3" style="background-color: #1e3a8a; color: white; border: none; border-radius: 10px;">
                <div class="card-header" style="background-color: #1e3a8a; border: none; padding: 1rem 1.5rem;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <a href="{{ route('warehouse.stock-opnames.index') }}" class="btn btn-light btn-sm">
                                <i class="fas fa-arrow-left"></i> Back to List
                            </a>
                        </div>
                        <div>
                            <h3 class="card-title mb-0" style="color: white; font-size: 1.5rem; font-weight: bold;">
                                {{ $stockOpname->opname_no }} - <span style="font-size: 0.9rem; font-weight: normal;">{{ ucfirst(str_replace('-', ' ', $stockOpname->status)) }}</span>
                            </h3>
                        </div>
                        <div>
                            @if($stockOpname->status === 'draft')
                            <form action="{{ route('warehouse.stock-opnames.start', $stockOpname->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="fas fa-play"></i> Start Opname
                                </button>
                            </form>
                            @endif
                            
                            @if($stockOpname->status === 'in-progress')
                            <a href="{{ route('warehouse.stock-opnames.export-stock', $stockOpname->id) }}" class="btn btn-success btn-sm me-1">
                                <i class="fas fa-file-download"></i> Export Stock
                            </a>
                            <button type="button" class="btn btn-primary btn-sm me-1" onclick="openImportModal()">
                                <i class="fas fa-file-upload"></i> Import Stock
                            </button>
                            <button type="button" class="btn btn-warning btn-sm" onclick="openCompleteModal()">
                                <i class="fas fa-check-circle"></i> Complete Opname
                            </button>
                            @endif

                            @if($stockOpname->status === 'waiting for approval' && auth()->user()->hasPermission('warehouse.stock-opnames.approve'))
                            <button type="button" class="btn btn-success btn-sm" onclick="approveOpname()">
                                <i class="fas fa-check"></i> Approve
                            </button>
                            @endif

                            @if($stockOpname->status === 'approved')
                            <button type="button" class="btn btn-info btn-sm" onclick="createStockAdjustment()">
                                <i class="fas fa-exchange-alt"></i> Create Stock Adjustment
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Basic Info Section -->
            <div class="card mb-3">
                <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #1e3a8a; padding: 0.75rem 1.5rem;">
                    <h5 class="card-title mb-0" style="color: #1e3a8a;">
                        <i class="fas fa-info-circle me-2"></i>Basic Info
                    </h5>
                </div>
                <div class="card-body" style="padding: 1rem 1.5rem;">
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 2rem;">
                        <!-- Row 1 -->
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Opname Number</div>
                            <div style="font-size: 1rem; font-weight: 600; color: #212529;">{{ $stockOpname->opname_no }}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Status</div>
                            <div>
                                <span class="badge" style="background-color: {{ $stockOpname->status === 'completed' ? '#059669' : ($stockOpname->status === 'in-progress' ? '#2563eb' : ($stockOpname->status === 'draft' ? '#6b7280' : '#d97706')) }}; color: white; padding: 0.35rem 0.75rem; font-size: 0.8rem;">
                                    {{ ucfirst(str_replace('-', ' ', $stockOpname->status)) }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Opname Date</div>
                            <div style="font-size: 1rem; color: #212529;">{{ $stockOpname->opname_date ? $stockOpname->opname_date->format('j M Y') : '-' }}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Warehouse</div>
                            <div style="font-size: 1rem; color: #212529;">{{ $stockOpname->warehouse->name ?? '-' }}</div>
                        </div>

                        <!-- Row 2 -->
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Branch</div>
                            <div style="font-size: 1rem; color: #212529;">{{ $stockOpname->branch->name ?? '-' }}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Person In Charge</div>
                            <div style="font-size: 1rem; color: #212529;">{{ $stockOpname->personResponsible->name ?? '-' }}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Started At</div>
                            <div style="font-size: 1rem; color: #212529;">{{ $stockOpname->started_at ? $stockOpname->started_at->format('j M Y H:i') : '-' }}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Notes</div>
                            <div style="font-size: 1rem; color: #212529;">{{ $stockOpname->notes ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Audit Information Section -->
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
                            <div style="font-size: 0.9rem; color: #212529;">{{ $stockOpname->createdBy->name ?? '-' }}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.25rem;">Created At</div>
                            <div style="font-size: 0.9rem; color: #212529;">{{ $stockOpname->created_at ? $stockOpname->created_at->format('j M Y H:i') : '-' }}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.25rem;">Last Updated By</div>
                            <div style="font-size: 0.9rem; color: #212529;">{{ $stockOpname->updatedBy->name ?? '-' }}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.25rem;">Last Updated At</div>
                            <div style="font-size: 0.9rem; color: #212529;">{{ $stockOpname->updated_at ? $stockOpname->updated_at->format('j M Y H:i') : '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Bar (Date & Warehouse) - Inventory Request Style -->
            <div class="card mb-3" style="border: none; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                <div class="card-body" style="padding: 1.25rem 2rem;">
                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 2rem;">
                        <div style="display: flex; align-items: center; gap: 3rem;">
                            <!-- Opname Date -->
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <div style="width: 48px; height: 48px; background: #1e3a8a; border-radius: 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(30, 58, 138, 0.2);">
                                    <i class="fas fa-calendar-alt" style="color: white; font-size: 1.2rem;"></i>
                                </div>
                                <div style="position: relative;">
                                    <div style="font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 2px;">Opname Date</div>
                                    <div style="font-size: 1.1rem; font-weight: 600; color: #1e293b;">
                                        {{ $stockOpname->opname_date ? $stockOpname->opname_date->format('j M Y') : '-' }}
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Warehouse -->
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <div style="width: 48px; height: 48px; background: #059669; border-radius: 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(5, 150, 105, 0.2);">
                                    <i class="fas fa-warehouse" style="color: white; font-size: 1.2rem;"></i>
                                </div>
                                <div style="position: relative;">
                                    <div style="font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 2px;">Warehouse</div>
                                    <div style="font-size: 1.1rem; font-weight: 600; color: #1e293b;">
                                        {{ $stockOpname->warehouse->name ?? '-' }}
                                    </div>
                                    <div style="font-size: 0.8rem; color: #64748b;">
                                        {{ $stockOpname->branch->name ?? '-' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Notes -->
                        <div style="flex: 1; min-width: 250px; padding-left: 2.5rem; border-left: 2px solid #e2e8f0;">
                            <div style="font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Notes</div>
                            <div style="font-size: 1rem; color: #334155; line-height: 1.5;">{{ $stockOpname->notes ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Details Section -->
            <div class="card mb-3">
                <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #1e3a8a;">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0" style="color: #1e3a8a;">
                            <i class="fas fa-list-alt me-2"></i>Opname Details
                        </h5>
                        <span class="badge bg-secondary">{{ $stockOpname->stockOpnameDetails->count() }} Products</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" style="margin-bottom: 0;">
                            <thead>
                                <tr>
                                    <th style="width: 30%; background-color: #f8f9fa; color: #495057;">Product</th>
                                    <th style="width: 15%; background-color: #f8f9fa; color: #495057;">Package</th>
                                    @if($canViewSystemStock)
                                    <th class="text-center" style="width: 15%; background-color: #f8f9fa; color: #495057;">System Stock</th>
                                    @endif
                                    <th class="text-center" style="width: 25%; background-color: #f8f9fa; color: #495057;">Physical Stock</th>
                                    @if($canViewSystemStock)
                                    <th class="text-center" style="width: 15%; background-color: #f8f9fa; color: #495057;">Variance</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($stockOpname->stockOpnameDetails as $detail)
                                <tr>
                                    <td style="vertical-align: middle; padding: 12px;">
                                        <div class="font-weight-bold">{{ $detail->masterProduct->name ?? 'Product Not Found' }}</div>
                                        <div class="small text-muted">
                                            SKU: {{ $detail->masterProduct->sku ?? '-' }}
                                        </div>
                                    </td>
                                    <td style="vertical-align: middle; padding: 12px;">
                                        <div class="badge bg-light text-dark border">
                                            {{ $detail->masterProduct->packaging_size ?? '-' }} {{ $detail->masterProduct->unit ?? '' }}
                                        </div>
                                    </td>
                                    @if($canViewSystemStock)
                                    <td class="text-center font-weight-bold text-muted" style="vertical-align: middle; padding: 12px;">
                                        {{ number_format($detail->system_stock, 0) }}
                                    </td>
                                    @endif
                                    <td class="text-center" style="vertical-align: middle; padding: 12px;">
                                        @if($stockOpname->status === 'in-progress')
                                        <div class="d-flex justify-content-center align-items-center">
                                            <div class="input-group input-group-sm" style="width: 160px;">
                                                <input type="number" 
                                                       class="form-control text-center physical-input font-weight-bold" 
                                                       data-id="{{ $detail->id }}"
                                                       value="{{ $detail->physical_stock }}"
                                                       id="physical-{{ $detail->id }}"
                                                       style="font-size: 1.1rem;">
                                                <div class="input-group-append">
                                                    <button class="btn btn-outline-primary" type="button" onclick="openQRModal({{ $detail->id }}, '{{ $detail->masterProduct->name }}')" title="Scan QR SN">
                                                        <i class="fas fa-qrcode"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <small class="text-muted d-block mt-1 sn-count-label" id="sn-count-{{ $detail->id }}">
                                            @if(is_array($detail->scanned_serial_numbers) && count($detail->scanned_serial_numbers) > 0)
                                                <i class="fas fa-check-circle text-success"></i> {{ count($detail->scanned_serial_numbers) }} SN Scanned
                                            @endif
                                        </small>
                                        <div id="save-indicator-{{ $detail->id }}" class="tiny-feedback" style="display:none; color: green; font-size: 0.8rem;">
                                            Saved <i class="fas fa-check"></i>
                                        </div>
                                        @else
                                            <span class="font-weight-bold" style="font-size: 1.1rem;">
                                                {{ $detail->physical_stock !== null ? number_format($detail->physical_stock, 0) : '-' }}
                                            </span>
                                        @endif
                                    </td>
                                    @if($canViewSystemStock)
                                    <td class="text-center variance-c" id="variance-{{ $detail->id }}" style="vertical-align: middle; padding: 12px;">
                                        @php
                                            $variance = (float)($detail->variance ?? 0);
                                            $bgColor = $variance < 0 ? '#ef4444' : ($variance > 0 ? '#10b981' : '#6b7280');
                                            $prefix = $variance > 0 ? '+' : '';
                                        @endphp
                                        <span class="badge" style="font-size: 1rem; padding: 8px 16px; min-width: 65px; background-color: {{ $bgColor }} !important; color: white;">
                                            {{ $prefix }}{{ number_format($variance, 0) }}
                                        </span>
                                    </td>
                                    @endif
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">No products in this opname</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div> <!-- Close col-12 -->
    </div> <!-- Close row -->
</div> <!-- Close container-fluid -->

<!-- All modals are now created dynamically via JavaScript -->

<!-- Approve Opname Form -->
<form id="approveForm" action="{{ route('warehouse.stock-opnames.approve', $stockOpname->id) }}" method="POST" class="d-none">
    @csrf
</form>

<!-- Include Html5Qrcode Library -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js" type="text/javascript"></script>

<script>
    let html5QrcodeScanner = null;
    let currentDetailId = null;
    let scannedSNs = [];
    
    // Existing Scanned Data (loaded from backend)
    const existingScannedData = {
        @foreach($stockOpname->stockOpnameDetails as $detail)
            {{ $detail->id }}: @json($detail->scanned_serial_numbers ?? []),
        @endforeach
    };

    // ==================== QR SCANNER MODAL ====================
    function openQRModal(detailId, productName) {
        currentDetailId = detailId;
        scannedSNs = [...(existingScannedData[detailId] || [])];
        
        const modal = document.createElement('div');
        modal.id = 'customQRModal';
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
            <div class="modal-dialog" style="margin: auto; width: 100%; max-width: 600px;">
                <div class="modal-content" style="background-color: white; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); border: none;">
                    <div class="modal-header" style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); color: white; border-radius: 12px 12px 0 0; padding: 16px 20px; border-bottom: none;">
                        <h5 class="modal-title" style="font-weight: 600; font-size: 1.15rem;">
                            <i class="fas fa-qrcode me-2"></i>Scan Serial Numbers: ${productName}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" onclick="closeQRModal()"></button>
                    </div>
                    <div class="modal-body" style="background-color: white; padding: 24px;">
                        <!-- Input SN Section -->
                        <div class="mb-4">
                            <label class="form-label" style="font-weight: 600; color: #374151; margin-bottom: 8px; display: block;">
                                <i class="fas fa-barcode me-2"></i>Serial Number
                            </label>
                            <div class="input-group" style="margin-bottom: 10px;">
                                <input type="text" id="manualInput" class="form-control" 
                                    placeholder="Scan QR Code atau ketik manual" 
                                    autocomplete="off"
                                    style="padding: 10px 12px; border: 2px solid #e5e7eb; border-radius: 8px 0 0 8px; font-size: 14px; text-transform: uppercase;"
                                    onkeyup="this.value = this.value.toUpperCase()"
                                    onkeypress="if(event.key === 'Enter') { event.preventDefault(); addManualSN(); }">
                                <button type="button" class="btn btn-success" onclick="addManualSN()" style="border-radius: 0; border: 2px solid #059669; background: #10b981;">
                                    <i class="fas fa-plus me-1"></i>Add
                                </button>
                                <button type="button" class="btn btn-primary" onclick="toggleQRScanner()" id="scanQRBtn" style="border-radius: 0 8px 8px 0; border: 2px solid #1e3a8a; border-left: none; background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);">
                                    <i class="fas fa-camera me-1"></i>Scan
                                </button>
                            </div>
                            <small class="text-muted"><i class="fas fa-info-circle me-1"></i>Ketik SN dan tekan Enter atau klik Add. Klik Scan untuk buka kamera.</small>
                        </div>
                        
                        <!-- QR Camera Container (Hidden by default) -->
                        <div id="qrReaderContainer" style="display: none; margin-bottom: 20px; border: 2px solid #e5e7eb; border-radius: 8px; padding: 15px; background-color: #f9fafb;">
                            <div id="reader" style="width: 100%; max-width: 500px; margin: 0 auto;"></div>
                            <div class="text-center mt-3">
                                <small class="text-muted d-block mb-2">Arahkan kamera ke QR Code</small>
                                <button type="button" class="btn btn-sm btn-secondary" onclick="stopQRScanner()">
                                    <i class="fas fa-times me-1"></i>Stop Camera
                                </button>
                            </div>
                        </div>
                        
                        <!-- Scanned List -->
                        <div class="mb-3">
                            <h6 style="font-weight: 600; color: #374151;">
                                <i class="fas fa-list me-2"></i>Scanned Serial Numbers (<span id="scanCount">0</span>)
                            </h6>
                            <div class="border rounded p-2" style="max-height: 200px; overflow-y: auto; background-color: #f9fafb;">
                                <ul id="scannedList" class="list-group list-group-flush small"></ul>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer" style="background-color: #f9fafb; border-radius: 0 0 12px 12px; padding: 16px 20px; border-top: 1px solid #e5e7eb; gap: 10px;">
                        <button type="button" class="btn btn-secondary" onclick="closeQRModal()">Close</button>
                        <button type="button" id="saveScannedSNsBtn" class="btn btn-success no-double-click-prevention" onclick="saveScannedSNs(this)"><i class="fas fa-save me-1"></i>Save & Update Stock</button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        
        updateScannedListUI();
        
        // Auto focus on input
        setTimeout(() => {
            document.getElementById('manualInput').focus();
        }, 100);
    }
    
    let isScannerActive = false;
    
    function toggleQRScanner() {
        if (isScannerActive) {
            stopQRScanner();
        } else {
            startQRScanner();
        }
    }
    
    function startQRScanner() {
        const container = document.getElementById('qrReaderContainer');
        const scanBtn = document.getElementById('scanQRBtn');
        
        if (!container) return;
        
        container.style.display = 'block';
        scanBtn.innerHTML = '<i class="fas fa-stop me-1"></i>Stop';
        isScannerActive = true;
        
        html5QrcodeScanner = new Html5Qrcode("reader");
        const config = { 
            fps: 15, 
            qrbox: (viewfinderWidth, viewfinderHeight) => {
                let minEdge = Math.min(viewfinderWidth, viewfinderHeight);
                let qrboxSize = Math.floor(minEdge * 0.7);
                return { width: qrboxSize, height: qrboxSize };
            }
        };
        
        html5QrcodeScanner.start({ facingMode: "environment" }, config, onScanSuccess)
            .catch(err => console.error("Camera error:", err));
    }
    
    function stopQRScanner() {
        const container = document.getElementById('qrReaderContainer');
        const scanBtn = document.getElementById('scanQRBtn');
        let stopPromise = Promise.resolve();
        
        if (html5QrcodeScanner && isScannerActive) {
            stopPromise = html5QrcodeScanner.stop().catch(err => console.error(err));
            html5QrcodeScanner = null;
        }
        
        isScannerActive = false;
        
        if (container) container.style.display = 'none';
        if (scanBtn) scanBtn.innerHTML = '<i class="fas fa-camera me-1"></i>Scan';

        return stopPromise;
    }

    function closeQRModal() {
        const stopPromise = stopQRScanner();
        isScannerActive = false;
        const modal = document.getElementById('customQRModal');
        if (modal) modal.remove();
        document.body.classList.remove('modal-open');
        document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());
        stopPromise.finally(() => {});
    }

    function onScanSuccess(decodedText, decodedResult) {
        const sn = String(decodedText || '').trim();
        if (!sn) return;

        const audio = new Audio('https://actions.google.com/sounds/v1/alarms/beep_short.ogg');
        audio.play().catch(e => {});
        scannedSNs.push(sn);
        updateScannedListUI();
    }

    function addManualSN() {
        const input = document.getElementById('manualInput');
        const sn = input.value.trim();
        if (sn) {
            scannedSNs.push(sn);
            updateScannedListUI();
            input.value = '';
        }
    }

    function removeSNAt(index) {
        scannedSNs.splice(index, 1);
        updateScannedListUI();
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function updateScannedListUI() {
        const list = document.getElementById('scannedList');
        const countSpan = document.getElementById('scanCount');
        if (!list || !countSpan) return;
        
        list.innerHTML = '';
        scannedSNs.forEach((sn, index) => {
            const li = document.createElement('li');
            li.className = 'list-group-item d-flex justify-content-between align-items-center py-2 px-3';
            li.innerHTML = `${escapeHtml(sn)} <button class="btn btn-danger btn-sm py-0 px-2" onclick="removeSNAt(${index})">&times;</button>`;
            list.appendChild(li);
        });
        countSpan.textContent = scannedSNs.length;
    }

    function updateDetailRowAfterSN(detailId, detailData, snCount) {
        const physicalInput = document.getElementById(`physical-${detailId}`);
        if (physicalInput) {
            physicalInput.value = snCount;
            physicalInput.dataset.lastValue = String(snCount);
            physicalInput.classList.remove('bg-warning', 'bg-opacity-25', 'is-invalid');
            physicalInput.classList.add('is-valid');
            setTimeout(() => physicalInput.classList.remove('is-valid'), 1500);
        }

        const countLabel = document.getElementById(`sn-count-${detailId}`);
        if (countLabel) {
            countLabel.innerHTML = snCount > 0
                ? `<i class="fas fa-check-circle text-success"></i> ${snCount} SN Scanned`
                : '';
        }

        const varianceBadge = document.querySelector(`#variance-${detailId} span`);
        if (varianceBadge && detailData && detailData.variance !== null && detailData.variance !== undefined) {
            const varianceValue = parseFloat(detailData.variance);
            const prefix = varianceValue > 0 ? '+' : '';
            varianceBadge.textContent = prefix + varianceValue;
            varianceBadge.style.backgroundColor = varianceValue < 0 ? '#ef4444' : (varianceValue > 0 ? '#10b981' : '#6b7280');
            varianceBadge.style.color = 'white';
        }
    }

    function saveScannedSNs(button = null) {
        if (!currentDetailId) return;
        const detailId = currentDetailId;
        const snCount = scannedSNs.length;
        const originalButtonHtml = button ? button.innerHTML : null;

        if (button) {
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';
        }

        existingScannedData[currentDetailId] = [...scannedSNs];
        
        // Save SN list and keep the visible physical stock in sync with the count.
        fetch(`/warehouse/stock-opnames/details/${currentDetailId}/update`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                scanned_serial_numbers: scannedSNs,
                physical_stock: snCount
            })
        })
        .then(async response => {
            const data = await response.json().catch(() => null);
            if (!response.ok || !data) {
                throw new Error(data?.message || 'Response tidak valid.');
            }

            return data;
        })
        .then(data => {
            if(data.status === 'success') {
                updateDetailRowAfterSN(detailId, data.data || {}, snCount);
                closeQRModal();
                showToast('SN dan physical stock tersimpan', 'success');
            } else {
                throw new Error(data.message || 'Daftar SN tidak berhasil disimpan.');
            }
        })
        .catch(err => {
            console.error('Error:', err);
            showErrorDialog(err.message || 'Terjadi kesalahan saat menyimpan.', 'Gagal');
        })
        .finally(() => {
            if (button) {
                button.disabled = false;
                button.innerHTML = originalButtonHtml;
            }
        });
    }

    // ==================== COMPLETE OPNAME MODAL ====================
    function openCompleteModal() {
        const modal = document.createElement('div');
        modal.id = 'customCompleteModal';
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
            <div class="modal-dialog" style="margin: auto; width: 100%; max-width: 500px;">
                <form action="{{ route('warehouse.stock-opnames.complete', $stockOpname->id) }}" method="POST">
                    @csrf
                    <div class="modal-content" style="background-color: white; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); border: none;">
                        <div class="modal-header" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; border-radius: 12px 12px 0 0; padding: 16px 20px; border-bottom: none;">
                            <h5 class="modal-title" style="font-weight: 600; font-size: 1.15rem;">
                                <i class="fas fa-check-circle me-2"></i>Selesaikan Stock Opname
                            </h5>
                            <button type="button" class="btn-close btn-close-white" onclick="closeCompleteModal()"></button>
                        </div>
                        <div class="modal-body" style="background-color: white; padding: 24px;">
                            <div class="form-group mb-3">
                                <label class="form-label" style="font-weight: 600;">Catatan Penyelesaian</label>
                                <textarea name="completion_notes" class="form-control" rows="3" placeholder="Catatan penyelesaian..."></textarea>
                            </div>
                            <p class="text-muted small">Setelah diselesaikan, stock adjustment akan dibuat otomatis untuk setiap selisih stok.</p>
                        </div>
                        <div class="modal-footer" style="background-color: #f9fafb; border-radius: 0 0 12px 12px; padding: 16px 20px; border-top: 1px solid #e5e7eb; gap: 10px;">
                            <button type="button" class="btn btn-secondary" onclick="closeCompleteModal()">Batal</button>
                            <button type="submit" class="btn btn-warning"><i class="fas fa-check me-1"></i>Selesaikan Opname</button>
                        </div>
                    </div>
                </form>
            </div>
        `;
        document.body.appendChild(modal);
    }

    function closeCompleteModal() {
        const modal = document.getElementById('customCompleteModal');
        if (modal) modal.remove();
    }

    // ==================== IMPORT STOCK MODAL ====================
    function openImportModal() {
        const modal = document.createElement('div');
        modal.id = 'customImportModal';
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
            <div class="modal-dialog" style="margin: auto; width: 100%; max-width: 500px;">
                <form action="{{ route('warehouse.stock-opnames.import-stock', $stockOpname->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-content" style="background-color: white; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); border: none;">
                        <div class="modal-header" style="background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); color: white; border-radius: 12px 12px 0 0; padding: 16px 20px; border-bottom: none;">
                            <h5 class="modal-title" style="font-weight: 600; font-size: 1.15rem;">
                                <i class="fas fa-file-upload me-2"></i>Impor Stok Fisik
                            </h5>
                            <button type="button" class="btn-close btn-close-white" onclick="closeImportModal()"></button>
                        </div>
                        <div class="modal-body" style="background-color: white; padding: 24px;">
                            <div class="alert alert-info small">
                                <i class="fas fa-info-circle me-1"></i>
                                <strong>Petunjuk:</strong>
                                <ol class="mb-0 mt-2">
                                    <li>Isi kolom "Stock Fisik" untuk setiap produk</li>
                                    <li>Upload file yang sudah dilengkapi di sini</li>
                                </ol>
                            </div>
                            <div class="form-group">
                                <label for="import_file" class="form-label" style="font-weight: 600;">Pilih File Excel (.xlsx, .xls)</label>
                                <input type="file" name="import_file" id="import_file" class="form-control" accept=".xlsx,.xls" required>
                            </div>
                        </div>
                        <div class="modal-footer" style="background-color: #f9fafb; border-radius: 0 0 12px 12px; padding: 16px 20px; border-top: 1px solid #e5e7eb; gap: 10px;">
                            <button type="button" class="btn btn-secondary" onclick="closeImportModal()">Batal</button>
                            <button type="submit" class="btn btn-info"><i class="fas fa-upload me-1"></i>Impor</button>
                        </div>
                    </div>
                </form>
            </div>
        `;
        document.body.appendChild(modal);
    }

    function closeImportModal() {
        const modal = document.getElementById('customImportModal');
        if (modal) modal.remove();
    }

    // ==================== APPROVE OPNAME ====================
    function approveOpname() {
        showConfirmDialog(
            'Setujui Stock Opname?',
            'Tindakan ini tidak dapat dibatalkan.',
            'Ya, setujui',
            'Batal'
        ).then((confirmed) => {
            if (confirmed) {
                document.getElementById('approveForm').submit();
            }
        });
    }

    function createStockAdjustment() {
        showConfirmDialog(
            'Buat Stock Adjustment?',
            'Aksi ini akan membuat stock adjustment baru, menyesuaikan stok sistem dengan hasil opname, dan mengubah status stock opname menjadi completed.',
            'Ya, buat',
            'Batal'
        ).then((confirmed) => {
            if (!confirmed) {
                return;
            }
            
            const btn = document.querySelector('button[onclick="createStockAdjustment()"]');
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
            btn.disabled = true;
            
            fetch('{{ route("warehouse.stock-opnames.create-adjustment", $stockOpname->id) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if(data.status === 'success') {
                    showSuccessDialog('Berhasil', 'Stock Adjustment berhasil dibuat. ID: ' + (data.data?.adjustment_number || 'N/A'));
                    window.location.reload();
                } else {
                    showErrorDialog('Gagal', 'Stock Adjustment tidak berhasil dibuat: ' + (data.message || 'Unknown error'));
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorDialog('Gagal', 'Terjadi kesalahan saat membuat Stock Adjustment.');
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            });
        });
    }

    // ==================== TOAST NOTIFICATION ====================
    function showToast(message, type = 'success') {
        // Remove existing toast
        const existingToast = document.getElementById('autoSaveToast');
        if (existingToast) existingToast.remove();
        
        const toast = document.createElement('div');
        toast.id = 'autoSaveToast';
        toast.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            z-index: 9999;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            animation: slideIn 0.3s ease;
            background: ${type === 'success' ? 'linear-gradient(135deg, #10b981, #059669)' : 
                         type === 'error' ? 'linear-gradient(135deg, #ef4444, #dc2626)' : 
                         'linear-gradient(135deg, #3b82f6, #2563eb)'};
        `;
        
        const icon = type === 'success' ? 'fa-check-circle' : 
                     type === 'error' ? 'fa-exclamation-circle' : 'fa-spinner fa-spin';
        
        toast.innerHTML = `<i class="fas ${icon}"></i> ${message}`;
        document.body.appendChild(toast);
        
        // Auto remove after 2 seconds for success/error
        if (type !== 'saving') {
            setTimeout(() => {
                toast.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 2000);
        }
    }

    // ==================== SAVING INDICATOR ====================
    function showSavingIndicator() {
        let indicator = document.getElementById('savingIndicator');
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.id = 'savingIndicator';
            indicator.style.cssText = `
                position: fixed;
                top: 70px;
                right: 20px;
                padding: 8px 16px;
                background: linear-gradient(135deg, #f59e0b, #d97706);
                color: white;
                border-radius: 20px;
                font-size: 13px;
                font-weight: 500;
                z-index: 9998;
                display: flex;
                align-items: center;
                gap: 8px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            `;
            indicator.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            document.body.appendChild(indicator);
        }
        indicator.style.display = 'flex';
    }

    function showSavedIndicator() {
        let indicator = document.getElementById('savingIndicator');
        if (indicator) {
            indicator.style.background = 'linear-gradient(135deg, #10b981, #059669)';
            indicator.innerHTML = '<i class="fas fa-check"></i> Saved!';
            setTimeout(() => {
                indicator.style.display = 'none';
            }, 1500);
        }
    }

    function showErrorIndicator() {
        let indicator = document.getElementById('savingIndicator');
        if (indicator) {
            indicator.style.background = 'linear-gradient(135deg, #ef4444, #dc2626)';
            indicator.innerHTML = '<i class="fas fa-times"></i> Error!';
            setTimeout(() => {
                indicator.style.display = 'none';
            }, 2000);
        }
    }

    // ==================== AUTO-SAVE PHYSICAL STOCK WITH DEBOUNCE ====================
    const debounceTimers = {};
    
    function savePhysicalStock(input) {
        const id = input.dataset.id;
        const value = input.value;
        
        showSavingIndicator();
        input.classList.add('bg-warning', 'bg-opacity-25');
        
        let payload = { physical_stock: value };
        if (existingScannedData[id]) {
            payload.scanned_serial_numbers = existingScannedData[id];
        }

        fetch(`/warehouse/stock-opnames/details/${id}/update`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(payload)
        })
        .then(response => response.json())
        .then(data => {
            if(data.status === 'success') {
                const varianceBadge = document.querySelector(`#variance-${id} span`);
                if(varianceBadge) {
                    const varianceValue = parseFloat(data.data.variance);
                    const prefix = varianceValue > 0 ? '+' : '';
                    varianceBadge.textContent = prefix + data.data.variance;
                    
                    const bgColor = varianceValue < 0 ? '#ef4444' : (varianceValue > 0 ? '#10b981' : '#6b7280');
                    varianceBadge.style.backgroundColor = bgColor;
                    varianceBadge.style.color = 'white';
                }
                input.classList.remove('bg-warning', 'bg-opacity-25', 'is-invalid');
                input.classList.add('is-valid');
                setTimeout(() => input.classList.remove('is-valid'), 1500);
                
                showSavedIndicator();
                showToast('Data tersimpan otomatis', 'success');
            } else {
                showErrorIndicator();
                showToast('Gagal menyimpan: ' + (data.message || 'Error'), 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            input.classList.remove('bg-warning', 'bg-opacity-25');
            input.classList.add('is-invalid');
            showErrorIndicator();
            showToast('Gagal menyimpan data', 'error');
        });
    }
    
    document.querySelectorAll('.physical-input').forEach(input => {
        // Debounce on input (while typing)
        input.addEventListener('input', function() {
            const id = this.dataset.id;
            
            // Show typing indicator
            this.classList.add('bg-light');
            
            // Clear previous timer
            if (debounceTimers[id]) {
                clearTimeout(debounceTimers[id]);
            }
            
            // Set new timer - save after 1 second of no typing
            debounceTimers[id] = setTimeout(() => {
                savePhysicalStock(this);
            }, 1000);
        });
        
        // Also save on blur (when leaving the field)
        input.addEventListener('blur', function() {
            const id = this.dataset.id;
            
            // Clear debounce timer and save immediately
            if (debounceTimers[id]) {
                clearTimeout(debounceTimers[id]);
            }
            
            // Only save if value changed
            if (this.value !== this.dataset.lastValue) {
                this.dataset.lastValue = this.value;
                savePhysicalStock(this);
            }
        });
        
        // Store initial value
        input.dataset.lastValue = input.value;
    });

    // Add CSS animation for toast
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
@endsection
