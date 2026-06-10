@extends('layouts.app')

@section('title', 'Inventory Request Detail')

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
        color: #334155 !important;
    }

    .table .badge {
        display: inline-block;
        line-height: 1.2;
    }

    .table .badge-secondary {
        background-color: #f1f5f9 !important;
        color: #475569 !important;
        border: 1px solid #e2e8f0 !important;
    }
    
    .qty-input {
        width: 80px !important;
        text-align: center !important;
    }
    
    @media (max-width: 768px) {
        .d-flex.justify-content-between {
            flex-direction: column !important;
            gap: 1rem !important;
        }
    }
    .d-none {
        display: none !important;
    }
    
    .row-locked {
        background-color: #f8fafc !important;
        opacity: 0.7;
        color: #94a3b8 !important;
    }
    
    .row-locked input {
        background-color: #f1f5f9 !important;
        border-color: #e2e8f0 !important;
        cursor: not-allowed !important;
    }
    
    .cursor-not-allowed {
        cursor: not-allowed !important;
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
                            <a href="{{ route('warehouse.inventory-requests.index') }}" class="btn btn-light btn-sm">
                                <i class="fas fa-arrow-left"></i> Back to List
                            </a>
                        </div>
                        <div>
                            <h3 class="card-title mb-0" style="color: white; font-size: 1.5rem; font-weight: bold;">
                                {{ $requestData->request_number }} - <span style="font-size: 0.9rem; font-weight: normal;">{{ ucfirst($requestData->status) ?: 'Draft' }}</span>
                            </h3>
                            
                            @if($requestData->status === 'approved' && $requestData->warehouse_id)
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm" 
                                    style="background-color: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; font-weight: 600; border-radius: 6px; padding: 4px 12px; transition: all 0.2s;"
                                    onclick="confirmActionWithAutoFill('{{ route('warehouse.inventory-requests.complete-issue', $requestData->id) }}', 'Complete Issue', 'Apakah Anda ingin mengisi otomatis semua Qty Issued yang kosong sesuai Approved Qty?')">
                                    <i class="fas fa-check-double me-1"></i> Complete Issue
                                </button>
                            </div>
                            @endif
                        </div>
                        <div>
                            @if($requestData->status === 'draft')
                            <form method="POST" action="{{ route('warehouse.inventory-requests.approve', $requestData->id) }}" style="display: inline;">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="fas fa-paper-plane"></i> Submit for Approval
                                </button>
                            </form>
                            @endif
                            
                            @if($requestData->status === 'pending' && ($canApproveInventoryRequest ?? false))
                            <div style="display: inline-flex; gap: 10px;">
                                <button type="button" class="btn btn-success btn-sm"
                                    onclick="confirmActionWithAutoFill('{{ route('warehouse.inventory-requests.approve', $requestData->id) }}', 'Approve Request', 'Apakah Anda ingin mengisi otomatis semua Approved Qty yang kosong sesuai Qty Request?')">
                                    <i class="fas fa-check-circle"></i> Approve Request
                                </button>
                                <button type="button" class="btn btn-warning btn-sm" onclick="openRejectModal()">
                                    <i class="fas fa-undo-alt"></i> Reject to Draft
                                </button>
                            </div>
                            @endif
                            
                            @if($requestData->status === 'approved')
                            <div style="display: inline-flex; gap: 10px;">
                                <form method="POST" action="{{ route('warehouse.inventory-requests.back-to-pending', $requestData->id) }}" style="display: inline;" onsubmit="return submitBackToPending(event, this)">
                                    @csrf
                                    <button type="submit" class="btn btn-warning btn-sm">
                                        <i class="fas fa-history"></i> Back to Pending
                                    </button>
                                </form>
                                @if(!$requestData->warehouse_id)
                                <button type="button" class="btn btn-warning btn-sm" onclick="openAssignWarehouseModal()">
                                    <i class="fas fa-warehouse"></i> Assign Warehouse
                                </button>
                                @endif
                            </div>
                            @endif
                            
                            @if($requestData->status === 'issued')
                            <div style="display: inline-flex; gap: 10px;">
                                <button type="button" class="btn btn-primary btn-sm" onclick="openShippingModal()">
                                    <i class="fas fa-shipping-fast"></i> Complete Shipping
                                </button>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Basic Info Section -->
            <div class="card mb-3">
                <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #1e3a8a;">
                    <h5 class="card-title mb-0" style="color: #1e3a8a;">
                        <i class="fas fa-info-circle me-2"></i>Basic Info
                    </h5>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 2rem;">
                        <!-- Row 1 -->
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Request No</div>
                            <div style="font-size: 1rem; font-weight: 600; color: #212529;">{{ $requestData->request_number }}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Status</div>
                            <div>
                                <span class="badge" style="background-color: {{ $requestData->status === 'approved' ? '#059669' : ($requestData->status === 'pending' ? '#d97706' : ($requestData->status === 'draft' ? '#6b7280' : '#2563eb')) }}; color: white; padding: 0.35rem 0.75rem; font-size: 0.8rem;">
                                    {{ ucfirst($requestData->status) }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem; display: flex; justify-content: space-between; align-items: center;">
                                Tanggal Keperluan
                                @if($requestData->status === 'draft')
                                <button class="btn btn-link btn-sm p-0 text-primary" onclick="editRequiredDate()" title="Edit Tanggal Keperluan">
                                    <i class="fas fa-edit"></i>
                                </button>
                                @endif
                            </div>
                            <div style="font-size: 1rem; color: #212529;">{{ $requestData->required_date ? $requestData->required_date->format('d/M/Y') : '-' }}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Cabang</div>
                            <div style="font-size: 1rem; color: #212529;">{{ $requestData->branch_name }}</div>
                        </div>
                        
                        <!-- Row 2 -->
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Warehouse</div>
                            <div style="font-size: 1rem; color: #212529;">{{ $requestData->warehouse?->name ?? 'Belum ditentukan' }}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Diajukan Oleh</div>
                            <div style="font-size: 1rem; color: #212529;">{{ $requestData->requestedBy?->name ?? '-' }}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Tanggal Diajukan</div>
                            <div style="font-size: 1rem; color: #212529;">{{ $requestData->request_date ? $requestData->request_date->format('d/M/Y') : '-' }}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem; display: flex; justify-content: space-between; align-items: center;">
                                Catatan
                                @if($requestData->status === 'draft')
                                <button class="btn btn-link btn-sm p-0 text-primary" onclick="editReason()" title="Edit Catatan">
                                    <i class="fas fa-edit"></i>
                                </button>
                                @endif
                            </div>
                            <div style="font-size: 1rem; color: #212529;">{{ $requestData->reason ?? '-' }}</div>
                        </div>
                        
                        @if($requestData->status === 'draft' && $requestData->rejection_reason)
                        <div style="grid-column: span 4; background-color: #fef2f2; border: 1px solid #fee2e2; border-radius: 8px; padding: 1rem; margin-top: 1rem;">
                            <div style="font-size: 0.75rem; font-weight: 700; color: #991b1b; text-transform: uppercase; margin-bottom: 0.5rem;">Alasan Penolakan (Reject)</div>
                            <div style="font-size: 1rem; color: #b91c1c; font-weight: 500;">{{ $requestData->rejection_reason }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            @if($requestData->status === 'shipped' || $requestData->status === 'completed')
            <!-- Shipping Information Section -->
            <div class="card mb-3">
                <div class="card-header" style="background-color: #1e3a8a; color: white;">
                    <h5 class="card-title mb-0"><i class="fas fa-shipping-fast me-2"></i>Shipping Information</h5>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem;">
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Nomor Resi</div>
                            <div style="font-size: 1rem; font-weight: 600; color: #212529;">{{ $requestData->shipping_tracking_number ?? '-' }}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Tanggal Pengiriman</div>
                            <div style="font-size: 1rem; color: #212529;">{{ $requestData->shipping_date ? $requestData->shipping_date->format('d/M/Y') : '-' }}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Dikirim Pada</div>
                            <div style="font-size: 1rem; color: #212529;">{{ $requestData->shipped_at ? $requestData->shipped_at->format('d/M/Y H:i') : '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
            @endif


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
                            <div style="font-size: 0.9rem; color: #212529;">{{ $requestData->createdBy->name ?? '-' }}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.25rem;">Created At</div>
                            <div style="font-size: 0.9rem; color: #212529;">{{ $requestData->created_at ? $requestData->created_at->format('d/M/Y H:i') : '-' }}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.25rem;">Last Updated By</div>
                            <div style="font-size: 0.9rem; color: #212529;">{{ $requestData->updatedBy->name ?? '-' }}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.25rem;">Last Updated At</div>
                            <div style="font-size: 0.9rem; color: #212529;">{{ $requestData->updated_at ? $requestData->updated_at->format('d/M/Y H:i') : '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Request Summary Bar - Date & Branch Above Item List -->
            <div class="card mb-3" style="border: none; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                <div class="card-body" style="padding: 1.25rem 2rem;">
                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 2rem;">
                        <div style="display: flex; align-items: center; gap: 3rem;">
                            <!-- Required Date -->
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <div style="width: 48px; height: 48px; background: #1e3a8a; border-radius: 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(30, 58, 138, 0.2);">
                                    <i class="fas fa-calendar-alt" style="color: white; font-size: 1.2rem;"></i>
                                </div>
                                <div style="position: relative;">
                                    <div style="font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 2px;">Required Date</div>
                                    <div style="font-size: 1.1rem; font-weight: 600; color: #1e293b;">
                                        <span class="{{ $requestData->status === 'draft' ? 'cursor-pointer hover:text-blue-600' : '' }} transition-colors" 
                                            @if($requestData->status === 'draft') onclick="editRequiredDate()" @endif>
                                            {{ $requestData->required_date ? $requestData->required_date->format('d/M/Y') : '-' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Branch -->
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <div style="width: 48px; height: 48px; background: #059669; border-radius: 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(5, 150, 105, 0.2);">
                                    <i class="fas fa-building" style="color: white; font-size: 1.2rem;"></i>
                                </div>
                                <div style="position: relative;">
                                    <div style="font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 2px;">Branch</div>
                                    <div style="font-size: 1.1rem; font-weight: 600; color: #1e293b; display: flex; align-items: center; min-width: 200px;">
                                        <div class="{{ $requestData->status === 'draft' ? 'cursor-pointer hover:text-blue-600' : 'text-muted' }} transition-colors" 
                                            @if($requestData->status === 'draft') onclick="editBranch()" @endif 
                                            style="display: flex; align-items: center; gap: 0.5rem; width: 100%;">
                                            <span>{{ $requestData->branch_name }}</span>
                                            @if($requestData->status === 'draft')
                                            <i class="fas fa-edit" style="font-size: 0.8rem; color: #94a3b8;"></i>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Reason/Notes -->
                        <div style="flex: 1; min-width: 250px; padding-left: 2.5rem; border-left: 2px solid #e2e8f0;">
                            <div style="font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Reason for Request</div>
                            <div style="font-size: 1rem; color: #334155; line-height: 1.5;">{{ $requestData->reason ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Item List Section -->
            <div class="card">
                <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #1e3a8a;">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0" style="color: #1e3a8a;">
                            <i class="fas fa-list-alt me-2"></i>Item List
                        </h5>
                        <div id="bulk-actions-container" class="ms-auto d-none" style="display: flex; gap: 8px; align-items: center;">
                            <span class="text-muted small me-2"><span id="selected-count">0</span> terpilih</span>
                            @if($requestData->status === 'pending')
                            <button class="btn btn-success btn-sm" onclick="bulkApprove()">
                                <i class="fas fa-check-double"></i> Approve All
                            </button>
                            @endif
                            @if($requestData->status === 'approved')
                            <button class="btn btn-info btn-sm" onclick="bulkIssue()">
                                <i class="fas fa-dolly"></i> Issue All
                            </button>
                            @endif
                        </div>
                        @if($requestData->status === 'draft')
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
                                    <th style="width: 40px; text-align: center;">
                                        <input type="checkbox" id="select-all-items" onchange="toggleSelectAll(this)">
                                    </th>
                                    <th>Product</th>
                                    <th>Qty Request</th>
                                    <th>Approved</th>
                                    <th>Issued</th>
                                    <th>Received</th>
                                    <th>Returned</th>
                                    <th>Terakhir Update</th>
                                    <th>Oleh</th>
                                    @if($requestData->status === 'draft')
                                    <th style="width: 50px; text-align: center;">Action</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    // Table Lock Logic (Ref: inventory_request_testing_guide.md)
                                    $status = $requestData->status;
                                    $canEditQtyRequest = ($status === 'draft');
                                    $canEditApproved = ($status === 'pending');
                                    $canEditIssued = ($status === 'approved');
                                    $canEditReceived = false; // Always read-only, auto-filled from receiving
                                    $canEditReturned = ($status === 'completed');
                                @endphp
                                @forelse($requestData->items as $item)
                                @php
                                    $isRowLocked = !($canEditQtyRequest || $canEditApproved || $canEditIssued || $canEditReceived || $canEditReturned);
                                @endphp
                                <tr class="{{ $isRowLocked ? 'row-locked' : '' }}">
                                    <td style="text-align: center;">
                                        @if(!$isRowLocked)
                                        <input type="checkbox" class="item-checkbox" value="{{ $item->id }}" onchange="updateBulkActionUI()">
                                        @else
                                        <i class="fas fa-lock text-muted small"></i>
                                        @endif
                                    </td>
                                    <td>{{ $item->product->name ?? '-' }}</td>
                                    <td>
                                        @if($canEditQtyRequest)
                                        <input type="number" 
                                            class="qty-input form-control" 
                                            data-item-id="{{ $item->id }}" 
                                            data-field="quantity" 
                                            value="{{ (int)$item->quantity }}" 
                                            min="1"
                                            onchange="updateItemQty({{ $item->id }}, 'quantity', this.value)">
                                        @else
                                        <span class="badge badge-secondary" data-field="quantity-badge" style="background-color: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;">{{ number_format($item->quantity, 0) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($canEditApproved)
                                        <input type="number" 
                                            class="qty-input form-control" 
                                            data-item-id="{{ $item->id }}" 
                                            data-field="approved_qty" 
                                            value="{{ (float)($item->approved_qty ?? 0) > 0 ? (int)$item->approved_qty : (int)$item->quantity }}" 
                                            min="0"
                                            max="{{ (int)$item->quantity }}"
                                            onchange="updateItemQty({{ $item->id }}, 'approved_qty', this.value)">
                                        @else
                                        <span class="badge badge-secondary" data-field="approved_qty-badge">{{ isset($item->approved_qty) ? number_format($item->approved_qty, 0) : '-' }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($canEditIssued)
                                        <input type="number" 
                                            class="qty-input form-control" 
                                            data-item-id="{{ $item->id }}" 
                                            data-field="issued_qty" 
                                            data-previous-value="{{ (float)($item->issued_qty ?? 0) > 0 ? (int)$item->issued_qty : (int)(((float)($item->approved_qty ?? 0) > 0 ? $item->approved_qty : $item->quantity)) }}"
                                            value="{{ (float)($item->issued_qty ?? 0) > 0 ? (int)$item->issued_qty : (int)(((float)($item->approved_qty ?? 0) > 0 ? $item->approved_qty : $item->quantity)) }}" 
                                            min="0"
                                            max="{{ (int)(((float)($item->approved_qty ?? 0) > 0 ? $item->approved_qty : $item->quantity)) }}"
                                            onchange="updateItemQty({{ $item->id }}, 'issued_qty', this.value)"
                                            onfocus="this.setAttribute('data-previous-value', this.value)">
                                        @else
                                        <span class="badge badge-secondary">{{ isset($item->issued_qty) ? number_format($item->issued_qty, 0) : '-' }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-secondary">{{ number_format($item->received_qty ?? 0, 0) }}</span>
                                    </td>
                                    <td>
                                        @php
                                            $isReturnedFromReceiving = isset($item->received_qty) && $item->received_qty > 0;
                                            $canEditReturnedNow = $canEditReturned && !$isReturnedFromReceiving;
                                        @endphp

                                        @if($canEditReturnedNow)
                                        <input type="number" 
                                            class="qty-input form-control" 
                                            data-item-id="{{ $item->id }}" 
                                            data-field="returned_qty" 
                                            data-previous-value="{{ isset($item->returned_qty) ? (int)$item->returned_qty : '' }}"
                                            value="{{ isset($item->returned_qty) ? (int)$item->returned_qty : 0 }}" 
                                            min="0"
                                            max="{{ isset($item->received_qty) ? (int)$item->received_qty : '' }}"
                                            onchange="updateItemQty({{ $item->id }}, 'returned_qty', this.value)"
                                            onfocus="this.setAttribute('data-previous-value', this.value)">
                                        @else
                                        <div class="d-flex flex-column align-items-center gap-1">
                                            <span class="badge badge-secondary" style="font-size: 0.9rem;">{{ isset($item->returned_qty) ? number_format($item->returned_qty, 0) : '-' }}</span>
                                            @if(isset($item->returned_qty))
                                                @if($item->returned_qty > 0)
                                                <span class="badge badge-warning" style="font-size: 0.75rem; background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a;">Partial</span>
                                                @else
                                                <span class="badge badge-success" style="font-size: 0.75rem; background-color: #dcfce7; color: #166534; border: 1px solid #bbf7d0;">Full</span>
                                                @endif
                                            @endif
                                        </div>
                                        @endif
                                    </td>
                                    <td>{{ $item->updated_at ? $item->updated_at->format('d/M/Y H:i') : '-' }}</td>
                                    <td>
                                        {{ $item->updatedBy->name ?? $item->createdBy->name ?? $requestData->requestedBy->name ?? '-' }}
                                    </td>
                                    @if($requestData->status === 'draft')
                                    <td style="text-align: center;">
                                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeItem({{ $item->id }}, '{{ $item->product->name ?? '' }}')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                    @endif
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">
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
    </div>
</div>


@endsection

@push('scripts')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function editRequiredDate() {
    const display = document.getElementById('requiredDateDisplay');
    const input = document.getElementById('requiredDateInput');
    display.classList.add('d-none');
    input.classList.remove('d-none');
    
    // Initialize flatpickr if not already initialized
    if (!input._flatpickr) {
        flatpickr(input, {
            altInput: true,
            altFormat: "d/M/Y",
            dateFormat: "Y-m-d",
            defaultDate: input.value,
            allowInput: true,
            onClose: function(selectedDates, dateStr, instance) {
                // dateStr will be Y-m-d because of dateFormat
                saveRequiredDate(dateStr);
            }
        }).open();
    } else {
        input._flatpickr.open();
    }
}

let isUpdatingDate = false;
function saveRequiredDate(newDate) {
    if (isUpdatingDate) return;
    
    const display = document.getElementById('requiredDateDisplay');
    const input = document.getElementById('requiredDateInput');
    const date = newDate || input.value;
    
    // Check if value actually changed
    if (date == "{{ $requestData->required_date?->format('Y-m-d') }}") {
        display.classList.remove('d-none');
        input.classList.add('d-none');
        return;
    }

    if (date) {
        isUpdatingDate = true;
        fetch('{{ route("warehouse.inventory-requests.update", $requestData->id) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-HTTP-Method-Override': 'PUT'
            },
            body: JSON.stringify({ required_date: date })
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => { throw err; });
            }
            return response.json();
        })
        .then(result => {
            if (result.status === 'success') {
                showToast('✓ Date updated successfully. Reloading...', 'success');
                setTimeout(() => {
                    location.reload(); 
                }, 2000);
            } else {
                showErrorDialog('Gagal', 'Tanggal tidak berhasil diperbarui: ' + (result.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorDialog('Gagal', 'Tanggal tidak berhasil diperbarui: ' + (error.message || 'Failed to fetch'));
        })
        .finally(() => {
            isUpdatingDate = false;
        });
    }
    
    display.classList.remove('d-none');
    input.classList.add('d-none');
}

function editBranch() {
    const display = document.getElementById('branchDisplayContainer');
    const select = document.getElementById('branchSelect');
    display.classList.add('d-none');
    select.classList.remove('d-none');
    select.focus();
}

let isUpdatingBranch = false;
function saveBranch(trigger) {
    if (isUpdatingBranch) return;
    
    const display = document.getElementById('branchDisplayContainer');
    const displaySpan = document.getElementById('branchDisplay');
    const select = document.getElementById('branchSelect');
    const branchId = select.value;
    const branchName = select.options[select.selectedIndex].text;
    
    // Check if value actually changed
    if (branchId == "{{ $requestData->branch_id }}") {
        display.classList.remove('d-none');
        select.classList.add('d-none');
        return;
    }

    if (branchId) {
        isUpdatingBranch = true;
        
        fetch('{{ route("warehouse.inventory-requests.update", $requestData->id) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-HTTP-Method-Override': 'PUT'
            },
            body: JSON.stringify({ branch_id: branchId })
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => { throw err; });
            }
            return response.json();
        })
        .then(result => {
            if (result.status === 'success') {
                displaySpan.textContent = branchName;
                showToast('✓ Branch updated successfully. Reloading...', 'success');
                setTimeout(() => {
                    location.reload(); 
                }, 2000);
            } else {
                showErrorDialog('Gagal', 'Cabang tidak berhasil diperbarui: ' + (result.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorDialog('Gagal', 'Cabang tidak berhasil diperbarui: ' + (error.message || 'Failed to fetch'));
        })
        .finally(() => {
            isUpdatingBranch = false;
        });
    }
    
    display.classList.remove('d-none');
    select.classList.add('d-none');
}

// Bulk Action Logic
function toggleSelectAll(selectAllCheckbox) {
    document.querySelectorAll('.item-checkbox').forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
    updateBulkActionUI();
}

function updateBulkActionUI() {
    const checkedBoxes = document.querySelectorAll('.item-checkbox:checked');
    const container = document.getElementById('bulk-actions-container');
    const countSpan = document.getElementById('selected-count');
    
    if (checkedBoxes.length > 0) {
        container.classList.remove('d-none');
        container.style.display = 'flex';
        countSpan.textContent = checkedBoxes.length;
    } else {
        container.classList.add('d-none');
        container.style.display = 'none';
    }
}

async function confirmActionWithAutoFill(url, actionName, autoFillMessage) {
    const checkedBoxes = document.querySelectorAll('.item-checkbox:checked');
    const allQtyInputs = Array.from(document.querySelectorAll('.qty-input'));
    const emptyFields = allQtyInputs.filter(input => !input.value || input.value == '0');
    
    let useAutoFill = false;
    let selectedIds = [];

    if (checkedBoxes.length > 0) {
        // Option 1: User has selected specific items
        selectedIds = Array.from(checkedBoxes).map(cb => cb.value);
        const autoFillSelected = await showConfirmDialog(
            'Isi Otomatis Item Terpilih?',
            `Isi otomatis ${selectedIds.length} item terpilih sesuai permintaan?`,
            'Ya, isi otomatis',
            'Tidak'
        );
        if (autoFillSelected) {
            useAutoFill = true;
        } else {
            const continueWithoutFill = await showConfirmDialog(
                `Lanjutkan ${actionName}?`,
                `Lanjutkan ${actionName} tanpa mengisi item terpilih secara otomatis?`,
                'Ya, lanjutkan',
                'Batal'
            );
            if (!continueWithoutFill) return;
        }
    } else if (emptyFields.length > 0) {
        // Option 2: No selection, but there are empty fields
        const autoFillEmpty = await showConfirmDialog(
            'Isi Otomatis Item Kosong?',
            autoFillMessage,
            'Ya, isi otomatis',
            'Tidak'
        );
        if (autoFillEmpty) {
            useAutoFill = true;
        } else {
            const continueWithoutEmpty = await showConfirmDialog(
                `Lanjutkan ${actionName}?`,
                `Lanjutkan ${actionName} tanpa mengisi item yang kosong secara otomatis?`,
                'Ya, lanjutkan',
                'Batal'
            );
            if (!continueWithoutEmpty) return;
        }
    } else {
        // Option 3: Everything filled, just confirm action
        const confirmedAction = await showConfirmDialog(
            `${actionName}?`,
            `Apakah Anda yakin ingin melakukan ${actionName}?`,
            'Ya, lanjutkan',
            'Batal'
        );
        if (!confirmedAction) return;
    }

    // Create a hidden form and submit it
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = url;

    const csrfToken = document.createElement('input');
    csrfToken.type = 'hidden';
    csrfToken.name = '_token';
    csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    form.appendChild(csrfToken);

    if (useAutoFill) {
        const autoFillInput = document.createElement('input');
        autoFillInput.type = 'hidden';
        autoFillInput.name = 'auto_fill';
        autoFillInput.value = '1';
        form.appendChild(autoFillInput);

        selectedIds.forEach(id => {
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'item_ids[]';
            idInput.value = id;
            form.appendChild(idInput);
        });
    }

    document.body.appendChild(form);
    form.submit();
}

function updateItemQty(itemId, field, value) {
    if (!value || value < 0) return;
    
    // Get input element for visual feedback
    const input = document.querySelector(`input[data-item-id="${itemId}"][data-field="${field}"]`);
    if (input) {
        input.style.borderColor = '#fbbf24'; // Yellow border while saving
        input.disabled = true;
    }
    
    fetch(`{{ url('/warehouse/inventory-requests/items') }}/${itemId}/update-qty`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ field: field, value: value })
    })
    .then(response => {
        return response.json().then(data => {
            if (!response.ok) {
                throw { status: response.status, data: data };
            }
            return data;
        });
    })
    .then(result => {
        if (result.status === 'success') {
            // Success feedback
            if (input) {
                input.style.borderColor = '#10b981'; // Green border
                input.setAttribute('data-previous-value', input.value); // Update previous value
                setTimeout(() => {
                    input.style.borderColor = '';
                    input.disabled = false;
                }, 1000);
            }
            
            // Show toast notification
            showToast('✓ Quantity updated successfully', 'success');
        } else {
            if (input) {
                input.style.borderColor = '#ef4444'; // Red border
                input.disabled = false;
                // Revert to previous value on error
                const previousValue = input.getAttribute('data-previous-value') || '';
                input.value = previousValue;
            }
            showToast('Error: ' + (result.message || 'Unknown error'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (input) {
            input.style.borderColor = '#ef4444'; // Red border
            input.disabled = false;
            // Revert to previous value on error
            const previousValue = input.getAttribute('data-previous-value') || '';
            input.value = previousValue;
        }
        const errorMessage = error.data?.message || error.message || 'Failed to update quantity';
        showToast('Error: ' + errorMessage, 'error');
    });
}

// Toast notification function
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        background-color: ${type === 'success' ? '#10b981' : '#ef4444'};
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        z-index: 9999;
        font-size: 14px;
        font-weight: 500;
        animation: slideIn 0.3s ease;
    `;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 2000);
}

// Shipping Modal Functions
function openShippingModal() {
    const modal = document.createElement('div');
    modal.id = 'shippingModal';
    modal.className = 'modal fade show';
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
                <div class="modal-header" style="background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); color: white; border-radius: 12px 12px 0 0; padding: 20px 24px; border-bottom: none;">
                    <h5 class="modal-title" style="font-weight: 600; font-size: 1.25rem;">
                        <i class="fas fa-shipping-fast me-2"></i>Complete Shipping
                    </h5>
                    <button type="button" class="btn-close btn-close-white" onclick="closeShippingModal()"></button>
                </div>
                <form method="POST" action="{{ route('warehouse.inventory-requests.complete-shipping', $requestData->id) }}">
                    @csrf
                    <div class="modal-body" style="background-color: white; padding: 24px;">
                        <div class="mb-4">
                            <label class="form-label" style="font-weight: 600; color: #374151; margin-bottom: 8px; display: block;">
                                Nomor Resi <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="shipping_tracking_number" class="form-control" required placeholder="Masukkan nomor resi pengiriman" style="padding: 10px 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px;">
                        </div>
                        <div class="mb-4">
                            <label class="form-label" style="font-weight: 600; color: #374151; margin-bottom: 8px; display: block;">
                                Tanggal Pengiriman <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="shipping_date" id="shipping_date_picker" class="form-control" required style="padding: 10px 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px;">
                        </div>
                        <div class="alert alert-info" style="background-color: #dbeafe; border: 1px solid #93c5fd; border-radius: 8px; padding: 12px 16px; margin-bottom: 0;">
                            <i class="fas fa-info-circle me-2" style="color: #2563eb;"></i>
                            <span style="color: #1e40af;">Setelah complete shipping, Inventory Receiving akan otomatis dibuat untuk cabang.</span>
                        </div>
                    </div>
                    <div class="modal-footer" style="background-color: #f9fafb; border-radius: 0 0 12px 12px; padding: 16px 24px; border-top: 1px solid #e5e7eb; gap: 10px;">
                        <button type="button" class="btn btn-secondary" onclick="closeShippingModal()" style="padding: 10px 20px; border-radius: 8px; font-weight: 500;">Cancel</button>
                        <button type="submit" class="btn btn-primary" style="padding: 10px 20px; border-radius: 8px; font-weight: 500; background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); border: none;">
                            <i class="fas fa-check me-2"></i>Complete Shipping
                        </button>
                    </div>
                </form>
            </div>
        </div>
    `;
    document.body.appendChild(modal);

    // Initialize Flatpickr on the shipping date field
    flatpickr("#shipping_date_picker", {
        altInput: true,
        altFormat: "d/M/Y",
        dateFormat: "Y-m-d",
        defaultDate: "today"
    });
}

function closeShippingModal() {
    const modal = document.getElementById('shippingModal');
    if (modal) {
        modal.remove();
    }
}

// Assign Warehouse Modal
function openAssignWarehouseModal() {
    const modal = document.createElement('div');
    modal.id = 'assignWarehouseModal';
    modal.className = 'modal fade show';
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
                <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 12px 12px 0 0; padding: 20px 24px; border-bottom: none;">
                    <h5 class="modal-title" style="font-weight: 600; font-size: 1.25rem;">
                        <i class="fas fa-warehouse me-2"></i>Assign Warehouse
                    </h5>
                    <button type="button" class="btn-close btn-close-white" onclick="closeAssignWarehouseModal()"></button>
                </div>
                <form method="POST" action="{{ route('warehouse.inventory-requests.assign-warehouse', $requestData->id) }}">
                    @csrf
                    <div class="modal-body" style="background-color: white; padding: 24px;">
                        <div class="mb-4">
                            <label class="form-label" style="font-weight: 600; color: #374151; margin-bottom: 8px; display: block;">
                                Warehouse <span class="text-danger">*</span>
                            </label>
                            <select name="warehouse_id" class="form-control" required style="padding: 10px 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px;">
                                <option value="">Select Warehouse</option>
                                @foreach(\App\Models\Warehouse::where('is_active', true)->where('branch_id', $requestData->branch_id)->orderBy('name')->get() as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="alert alert-info" style="background-color: #dbeafe; border: 1px solid #93c5fd; border-radius: 8px; padding: 12px 16px; margin-bottom: 0;">
                            <i class="fas fa-info-circle me-2" style="color: #2563eb;"></i>
                            <span style="color: #1e40af;">Pilih warehouse yang akan memproses request ini.</span>
                        </div>
                    </div>
                    <div class="modal-footer" style="background-color: #f9fafb; border-radius: 0 0 12px 12px; padding: 16px 24px; border-top: 1px solid #e5e7eb; gap: 10px;">
                        <button type="button" class="btn btn-secondary" onclick="closeAssignWarehouseModal()" style="padding: 10px 20px; border-radius: 8px; font-weight: 500;">Cancel</button>
                        <button type="submit" class="btn btn-primary" style="padding: 10px 20px; border-radius: 8px; font-weight: 500; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
                            <i class="fas fa-check me-2"></i>Assign Warehouse
                        </button>
                    </div>
                </form>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

function closeAssignWarehouseModal() {
    const modal = document.getElementById('assignWarehouseModal');
    if (modal) {
        modal.remove();
    }
}

// Reject Modal
function openRejectModal() {
    const modal = document.createElement('div');
    modal.id = 'rejectModal';
    modal.className = 'modal fade show';
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
        <div class="modal-dialog" style="margin: auto; max-width: 500px;">
            <div class="modal-content" style="background-color: white; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <div class="modal-header bg-danger text-white" style="border-radius: 8px 8px 0 0;">
                    <h5 class="modal-title">
                        <i class="fas fa-undo-alt me-2"></i>Reject to Draft
                    </h5>
                    <button type="button" class="btn-close btn-close-white" onclick="closeRejectModal()"></button>
                </div>
                <form method="POST" action="{{ route('warehouse.inventory-requests.reject', $requestData->id) }}">
                    @csrf
                    <div class="modal-body" style="background-color: white;">
                        <div class="mb-3">
                            <label class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                            <textarea name="rejection_reason" class="form-control" rows="4" required placeholder="Enter reason for rejecting this request"></textarea>
                        </div>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Request akan dikembalikan ke status <b>Draft</b> agar user bisa mengedit kembali detail barangnya.
                        </div>
                    </div>
                    <div class="modal-footer" style="background-color: #f8f9fa; border-radius: 0 0 8px 8px;">
                        <button type="button" class="btn btn-secondary" onclick="closeRejectModal()">Cancel</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-undo-alt"></i> Reject to Draft
                        </button>
                    </div>
                </form>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

function closeRejectModal() {
    const modal = document.getElementById('rejectModal');
    if (modal) {
        modal.remove();
    }
}

// Bulk selection functions
function toggleSelectAll(checkbox) {
    const isChecked = checkbox.checked;
    document.querySelectorAll('.item-checkbox').forEach(item => {
        item.checked = isChecked;
    });
    updateBulkActionUI();
}

function updateBulkActionUI() {
    const selectedCount = document.querySelectorAll('.item-checkbox:checked').length;
    const container = document.getElementById('bulk-actions-container');
    const countSpan = document.getElementById('selected-count');
    
    if (countSpan) countSpan.textContent = selectedCount;
    
    if (selectedCount > 0) {
        container.classList.remove('d-none');
    } else {
        container.classList.add('d-none');
    }
}

function bulkApprove() {
    const selectedCheckboxes = document.querySelectorAll('.item-checkbox:checked');
    if (selectedCheckboxes.length === 0) return;
    
    if (confirm(`Approve ${selectedCheckboxes.length} item yang dipilih?`)) {
        selectedCheckboxes.forEach(cb => {
            const id = cb.value;
            const row = cb.closest('tr');
            const qtyReqSpan = row.querySelector('[data-field="quantity-badge"]');
            const qtyReq = qtyReqSpan ? qtyReqSpan.textContent.trim().replace(/,/g, '') : 0;
            
            const input = row.querySelector('[data-field="approved_qty"]');
            if (input) {
                input.value = qtyReq;
                updateItemQty(id, 'approved_qty', qtyReq);
            }
        });
        showToast('✓ Selected items approved', 'success');
    }
}

function bulkIssue() {
    const selectedCheckboxes = document.querySelectorAll('.item-checkbox:checked');
    if (selectedCheckboxes.length === 0) return;
    
    if (confirm(`Issue ${selectedCheckboxes.length} item yang dipilih?`)) {
        selectedCheckboxes.forEach(cb => {
            const id = cb.value;
            const row = cb.closest('tr');
            const qtyAppBadge = row.querySelector('[data-field="approved_qty-badge"]');
            const qtyApp = qtyAppBadge ? qtyAppBadge.textContent.trim().replace(/,/g, '') : 0;
            
            const input = row.querySelector('[data-field="issued_qty"]');
            if (input) {
                input.value = qtyApp;
                updateItemQty(id, 'issued_qty', qtyApp);
            }
        });
        showToast('✓ Selected items issued', 'success');
    }
}

// Item Management
function openAddItemModal() {
    fetch("{{ route('warehouse.inventory-requests.available-products', $requestData->id) }}")
        .then(response => response.json())
        .then(result => {
            if (result.status === 'success') {
                showAddItemModal(result.data);
            } else {
                showToast('Gagal memuat produk: ' + result.message, 'error');
            }
        });
}

function showAddItemModal(products) {
    const modal = document.createElement('div');
    modal.id = 'addItemModal';
    modal.className = 'modal fade show';
    modal.style.cssText = 'display: flex !important; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center;';
    
    let options = products.map(p => `<option value="${p.id}">${p.name} (${p.sku || '-'})</option>`).join('');
    
    modal.innerHTML = `
        <div class="modal-dialog" style="width: 100%; max-width: 400px;">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Tambah Produk</h5>
                    <button type="button" class="btn-close btn-close-white" onclick="document.getElementById('addItemModal').remove()"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Produk</label>
                        <select id="new-item-product" class="form-select">${options}</select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quantity</label>
                        <input type="number" id="new-item-qty" class="form-control" value="1" min="1">
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="document.getElementById('addItemModal').remove()">Batal</button>
                    <button class="btn btn-primary" onclick="submitAddItem()">Tambah</button>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

function submitAddItem() {
    const productId = document.getElementById('new-item-product').value;
    const qty = document.getElementById('new-item-qty').value;
    
    fetch("{{ route('warehouse.inventory-requests.add-item', $requestData->id) }}", {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ master_product_id: productId, quantity: qty })
    })
    .then(r => r.json())
    .then(res => {
        if(res.status === 'success') location.reload();
        else showToast(res.message, 'error');
    });
}

function removeItem(itemId, productName) {
    if (confirm(`Hapus ${productName} dari permintaan?`)) {
        fetch(`${window.location.origin}/warehouse/inventory-requests/{{ $requestData->id }}/remove-item/${itemId}`, {
            method: 'DELETE',
            headers: { 
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(r => r.json())
        .then(res => {
            if(res.status === 'success') location.reload();
            else showToast(res.message, 'error');
        });
    }
}

// Header Edits
function editReason() {
    const current = "{{ $requestData->reason }}";
    const val = prompt("Edit Catatan/Alasan:", current);
    if (val !== null && val !== current) saveHeader({ reason: val });
}

function editBranch() {
    // We can also use prompt here or just keep it simple with prompt
    const branches = [
        @foreach(\App\Models\Branch::where('is_active', true)->orderBy('name')->get() as $branch)
        { id: "{{ $branch->id }}", name: "{{ $branch->name }}" },
        @endforeach
    ];
    let msg = "Pilih Cabang (masukkan nomor):\n";
    branches.forEach((b, i) => msg += `${i+1}. ${b.name}\n`);
    const choice = prompt(msg);
    if (choice) {
        const index = parseInt(choice) - 1;
        if (branches[index]) {
            saveHeader({ branch_id: branches[index].id });
        } else {
            alert("Pilihan tidak valid.");
        }
    }
}

function editRequiredDate() {
    const current = "{{ $requestData->required_date ? $requestData->required_date->format('Y-m-d') : '' }}";
    const val = prompt("Edit Tanggal Keperluan (YYYY-MM-DD):", current);
    if (val && val !== current) {
        if (new Date(val) < new Date().setHours(0,0,0,0)) return alert("Tanggal tidak boleh kurang dari hari ini.");
        saveHeader({ required_date: val });
    }
}

function saveHeader(data) {
    fetch("{{ route('warehouse.inventory-requests.update', $requestData->id) }}", {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(res => res.success ? location.reload() : showErrorDialog('Gagal', res.message));
}

function submitBackToPending(event, form) {
    event.preventDefault();

    showConfirmDialog(
        'Kembalikan ke Pending?',
        'Apakah Anda yakin ingin mengembalikan request ini ke status Pending?',
        'Ya, kembalikan',
        'Batal'
    ).then((confirmed) => {
        if (confirmed) {
            form.submit();
        }
    });

    return false;
}

bulkApprove = function() {
    const selectedCheckboxes = document.querySelectorAll('.item-checkbox:checked');
    if (selectedCheckboxes.length === 0) return;

    showConfirmDialog(
        'Approve Item Terpilih?',
        `Approve ${selectedCheckboxes.length} item yang dipilih?`,
        'Ya, approve',
        'Batal'
    ).then((confirmed) => {
        if (!confirmed) return;

        selectedCheckboxes.forEach(cb => {
            const id = cb.value;
            const row = cb.closest('tr');
            const qtyReqSpan = row.querySelector('[data-field="quantity-badge"]');
            const qtyReq = qtyReqSpan ? qtyReqSpan.textContent.trim().replace(/,/g, '') : 0;

            const input = row.querySelector('[data-field="approved_qty"]');
            if (input) {
                input.value = qtyReq;
                updateItemQty(id, 'approved_qty', qtyReq);
            }
        });

        showToast('Item terpilih berhasil di-approve', 'success');
    });
};

bulkIssue = function() {
    const selectedCheckboxes = document.querySelectorAll('.item-checkbox:checked');
    if (selectedCheckboxes.length === 0) return;

    showConfirmDialog(
        'Issue Item Terpilih?',
        `Issue ${selectedCheckboxes.length} item yang dipilih?`,
        'Ya, issue',
        'Batal'
    ).then((confirmed) => {
        if (!confirmed) return;

        selectedCheckboxes.forEach(cb => {
            const id = cb.value;
            const row = cb.closest('tr');
            const qtyAppBadge = row.querySelector('[data-field="approved_qty-badge"]');
            const qtyApp = qtyAppBadge ? qtyAppBadge.textContent.trim().replace(/,/g, '') : 0;

            const input = row.querySelector('[data-field="issued_qty"]');
            if (input) {
                input.value = qtyApp;
                updateItemQty(id, 'issued_qty', qtyApp);
            }
        });

        showToast('Item terpilih berhasil di-issue', 'success');
    });
};

removeItem = function(itemId, productName) {
    showConfirmDialog(
        'Hapus Item?',
        `Hapus ${productName} dari permintaan?`,
        'Ya, hapus',
        'Batal'
    ).then((confirmed) => {
        if (!confirmed) return;

        fetch(`${window.location.origin}/warehouse/inventory-requests/{{ $requestData->id }}/remove-item/${itemId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') location.reload();
            else showToast(res.message, 'error');
        });
    });
};

editReason = function() {
    const current = "{{ $requestData->reason }}";
    Swal.fire({
        title: 'Edit Catatan/Alasan',
        input: 'text',
        inputValue: current,
        inputPlaceholder: 'Masukkan catatan/alasan',
        showCancelButton: true,
        confirmButtonText: 'Simpan',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed && result.value !== current) {
            saveHeader({ reason: result.value });
        }
    });
};

editBranch = function() {
    const branches = [
        @foreach(\App\Models\Branch::where('is_active', true)->orderBy('name')->get() as $branch)
        { id: "{{ $branch->id }}", name: "{{ $branch->name }}" },
        @endforeach
    ];

    let msg = "Pilih Cabang (masukkan nomor):\n";
    branches.forEach((b, i) => msg += `${i + 1}. ${b.name}\n`);

    Swal.fire({
        title: 'Pilih Cabang',
        input: 'text',
        inputLabel: msg,
        inputPlaceholder: 'Masukkan nomor cabang',
        showCancelButton: true,
        confirmButtonText: 'Pilih',
        cancelButtonText: 'Batal'
    }).then((result) => {
        const choice = result.value;
        if (!result.isConfirmed || !choice) return;

        const index = parseInt(choice) - 1;
        if (branches[index]) {
            saveHeader({ branch_id: branches[index].id });
        } else {
            showErrorDialog('Gagal', 'Pilihan tidak valid.');
        }
    });
};

editRequiredDate = function() {
    const current = "{{ $requestData->required_date ? $requestData->required_date->format('Y-m-d') : '' }}";
    Swal.fire({
        title: 'Edit Tanggal Keperluan',
        input: 'date',
        inputValue: current,
        showCancelButton: true,
        confirmButtonText: 'Simpan',
        cancelButtonText: 'Batal'
    }).then((result) => {
        const val = result.value;
        if (!result.isConfirmed || !val || val === current) return;

        if (new Date(val) < new Date().setHours(0,0,0,0)) {
            showWarningDialog('Tanggal tidak boleh kurang dari hari ini.');
            return;
        }

        saveHeader({ required_date: val });
    });
};
</script>
@endpush
