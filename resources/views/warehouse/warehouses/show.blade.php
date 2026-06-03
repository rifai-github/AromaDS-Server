@extends('layouts.app')

@section('title', 'Warehouse Details')
@section('breadcrumb', 'Home / Warehouse / Master Warehouse / ' . $warehouse->name)

@section('content')
<style>
    /* Global styles */
    .btn {
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
    }

    .btn-primary {
        background-color: #214589;
        color: white;
    }

    .btn-primary:hover {
        background-color: #1e3a8a;
        color: white;
    }

    .btn-secondary {
        background-color: #f3f4f6;
        color: #6b7280;
        border: 1px solid #d1d5db;
    }

    .btn-secondary:hover {
        background-color: #214589;
        color: white;
        border-color: #214589;
    }

    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
    }

    .btn-danger {
        background-color: #ef4444;
        color: white;
    }

    .btn-danger:hover {
        background-color: #dc2626;
    }

    /* Tabs */
    .tabs-container {
        margin-bottom: 20px;
        border-bottom: 2px solid #e5e7eb;
    }

    .tabs {
        display: flex;
        gap: 10px;
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .tab-item {
        padding: 12px 24px;
        cursor: pointer;
        border-bottom: 3px solid transparent;
        font-weight: 500;
        color: #6b7280;
        transition: all 0.2s ease;
        margin-bottom: -2px;
    }

    .tab-item:hover {
        color: #214589;
        background-color: #f9fafb;
    }

    .tab-item.active {
        color: #214589;
        border-bottom-color: #214589;
        background-color: #eff6ff;
    }

    .tab-content {
        display: none;
        animation: fadeIn 0.3s ease-in;
    }

    .tab-content.active {
        display: block;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    /* Table */
    .table-container {
        background: white;
        border-radius: 8px;
        overflow-x: auto; /* Fix: Allow horizontal scroll */
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        margin-top: 20px;
    }

    .responsive-table {
        width: 100%;
        border-collapse: collapse;
    }

    .responsive-table th,
    .responsive-table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #e5e7eb;
    }

    .responsive-table th {
        background-color: #214589;
        color: white;
        font-weight: 600;
        font-size: 13px;
    }

    .responsive-table tbody tr:hover {
        background-color: #eff6ff;
    }

    /* Card */
    .info-card {
        background: white;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }

    .info-item {
        padding: 12px 0;
        border-bottom: 1px solid #f3f4f6;
    }

    .info-item:last-child {
        border-bottom: none;
    }

    .info-label {
        font-size: 12px;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }

    .info-value {
        font-size: 16px;
        color: #1f2937;
        font-weight: 500;
    }

    /* Badge */
    .badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
    }

    .badge-success {
        background-color: #d1fae5;
        color: #065f46;
    }

    .badge-danger {
        background-color: #fee2e2;
        color: #991b1b;
    }

    .badge-info {
        background-color: #dbeafe;
        color: #1e40af;
    }

    .badge-warning {
        background-color: #fef3c7;
        color: #92400e;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #6b7280;
    }

    .empty-state i {
        font-size: 48px;
        margin-bottom: 16px;
        opacity: 0.5;
    }

    .empty-state p {
        font-size: 16px;
        margin-bottom: 20px;
    }

    /* Stats Cards */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 20px;
    }

    .stat-card {
        background: white;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .stat-icon {
        font-size: 40px;
        opacity: 0.7;
        flex-shrink: 0;
    }

    .stat-content {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .stat-label {
        font-size: 12px;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .stat-value {
        font-size: 24px;
        font-size: 28px;
        font-weight: 700;
        color: #1f2937;
        line-height: 1;
    }

    .text-blue-500 { color: #3b82f6; }
    .text-green-500 { color: #10b981; }
    .text-yellow-500 { color: #f59e0b; }
    .text-red-500 { color: #ef4444; }

    @media (max-width: 1024px) {
        .stats-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 640px) {
        .stats-grid { grid-template-columns: 1fr; }
    }
    /* DataTables Customization */
    .dataTables_wrapper {
        padding: 20px 0;
    }

    .dataTables_wrapper .dataTables_length {
        margin-bottom: 16px;
        margin-left: 20px;
        float: left;
    }
    
    .dataTables_wrapper .dataTables_filter {
        margin-bottom: 16px;
        margin-right: 20px;
        float: right;
    }

    .dataTables_wrapper .dataTables_info {
        margin-left: 20px;
        padding-top: 10px;
        color: #6b7280;
        font-size: 13px;
        float: left;
    }

    .dataTables_wrapper .dataTables_paginate {
        margin-right: 20px;
        padding-top: 10px;
        float: right;
        display: flex;
        align-items: center;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button {
        padding: 6px 12px;
        margin-left: 4px;
        border-radius: 6px;
        border: 1px solid #d1d5db;
        background: white;
        color: #374151 !important;
        font-size: 13px;
        cursor: pointer;
        display: inline-block;
        min-width: 32px;
        text-align: center;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background: #f3f4f6 !important;
        color: #214589 !important;
        border: 1px solid #d1d5db !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: #214589 !important;
        color: white !important;
        border: 1px solid #214589 !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    /* Hide the ugly default search input */
    .dataTables_filter input {
        border: 1px solid #d1d5db;
        border-radius: 6px;
        padding: 6px 12px;
        margin-left: 8px;
        outline: none;
    }
    
    .dataTables_filter input:focus {
        border-color: #214589;
        box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
    }

    /* Filter Row Styling - White Background */
    #productsTable thead tr:nth-child(2) th,
    #movementsTable thead tr:nth-child(2) th {
        background-color: #f8f9fa !important;
        padding: 8px 16px;
        border-bottom: 2px solid #e5e7eb;
    }

    /* Column Search Inputs */
    #productsTable thead input,
    #movementsTable thead input {
        width: 100%;
        padding: 6px 10px;
        box-sizing: border-box;
        border: 1px solid #d1d5db;
        border-radius: 4px;
        font-size: 13px;
        color: #374151;
        background-color: white;
    }

    #productsTable thead input::placeholder,
    #movementsTable thead input::placeholder {
        color: #9ca3af;
    }

    #productsTable thead input:focus,
    #movementsTable thead input:focus {
        outline: none;
        border-color: #214589;
        box-shadow: 0 0 0 2px rgba(33, 69, 137, 0.1);
    }
</style>

<div class="flex flex-col w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">
        
        <!-- Header Section -->
        <div class="info-card" style="width: 100%;">
            <div class="flex justify-between items-center mb-4">
                <div class="flex items-center gap-3">
                    <a href="{{ route('warehouse.warehouses.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                    <h1 class="text-2xl font-bold text-[#214589]">
                        {{ $warehouse->warehouse_code }} - {{ $warehouse->name }}
                    </h1>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('warehouse.warehouses.export-stock', $warehouse->id) }}" class="btn btn-success" style="background-color: #10b981; color: white;">
                        <i class="fas fa-file-excel"></i> Export Stock
                    </a>
                    <a href="{{ route('warehouse.warehouses.edit', $warehouse->id) }}" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                </div>
            </div>

            <!-- Basic Info Grid -->
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Warehouse Code</div>
                    <div class="info-value">
                        <span class="badge badge-info">{{ $warehouse->warehouse_code ?? '-' }}</span>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Branch</div>
                    <div class="info-value">
                        @if($warehouse->branch)
                            <span class="badge badge-info">{{ $warehouse->branch->branch_name ?? $warehouse->branch->name ?? '-' }}</span>
                        @else
                            -
                        @endif
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Warehouse Type</div>
                    <div class="info-value">
                        @if($warehouse->warehouseType)
                            {{ $warehouse->warehouseType->name }}
                        @else
                            -
                        @endif
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Type</div>
                    <div class="info-value">
                        <span class="badge {{ $warehouse->is_center ? 'badge-warning' : 'badge-info' }}">
                            {{ $warehouse->is_center ? 'Center Warehouse' : 'Branch Warehouse' }}
                        </span>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Status</div>
                    <div class="info-value">
                        <span class="badge {{ $warehouse->is_active ? 'badge-success' : 'badge-danger' }}">
                            {{ $warehouse->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Manager</div>
                    <div class="info-value">
                        {{ $warehouse->managerUser->name ?? '-' }}
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Admins</div>
                    <div class="info-value">
                        @forelse($warehouse->admins as $admin)
                            <span class="badge badge-info">{{ $admin->name }}</span>
                        @empty
                            <span class="text-gray-400">No admins assigned</span>
                        @endforelse
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Phone</div>
                    <div class="info-value">
                        {{ $warehouse->phone ?? '-' }}
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Address</div>
                    <div class="info-value">
                        {{ $warehouse->address ?? '-' }}
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Last Updated</div>
                    <div class="info-value">
                        {{ $warehouse->updated_at ? $warehouse->updated_at->format('d/M/Y H:i') : '-' }}
                        <small class="text-gray-500">by {{ $warehouse->updatedBy->name ?? $warehouse->createdBy->name ?? '-' }}</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon text-blue-500">
                    <i class="fas fa-boxes"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Total Products</div>
                    <div class="stat-value">{{ number_format($totalProducts ?? 0) }}</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon text-green-500">
                    <i class="fas fa-warehouse"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Total Stock</div>
                    <div class="stat-value">{{ number_format($totalStock ?? 0) }}</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon text-yellow-500">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Low Stock Products</div>
                    <div class="stat-value">{{ number_format($lowStockProducts ?? 0) }}</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon text-red-500">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Out of Stock</div>
                    <div class="stat-value">{{ number_format($outOfStockProducts ?? 0) }}</div>
                </div>
            </div>
        </div>

        <!-- Tabs Section -->
        <div class="info-card" style="width: 100%;">
            <div class="tabs-container">
                <ul class="tabs">
                    <li class="tab-item active" onclick="switchTab(event, 'products')">
                        <i class="fas fa-box"></i> Products
                    </li>
                    <li class="tab-item" onclick="switchTab(event, 'inventoryMovements')">
                        <i class="fas fa-exchange-alt"></i> Inventory Movements
                    </li>
                </ul>
            </div>

            <!-- Products Tab Content -->
            <div id="products" class="tab-content active">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="text-lg font-semibold">Warehouse Products</h3>
                </div>

                <div class="table-container">
                    <table class="responsive-table" id="productsTable">
                        <thead>
                            <tr>
                                <th>Product Code</th>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Packaging Size</th>
                                <th>Quantity</th>
                                <th>Min Stock</th>
                                <th>Max Stock</th>
                                <th>Stock Status</th>
                                <th>Last Updated</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($warehouse->warehouseProducts as $warehouseProduct)
                            <tr style="cursor: pointer;" onclick="window.location.href='{{ route('warehouse.warehouses.detail-stock', ['warehouse' => $warehouse->id, 'product' => $warehouseProduct->master_product_id]) }}'">
                                <td>
                                    @if($warehouseProduct->masterProduct)
                                        <span class="badge badge-info">{{ $warehouseProduct->masterProduct->sku ?? $warehouseProduct->masterProduct->sku_code ?? '-' }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($warehouseProduct->masterProduct)
                                        <strong class="text-blue-600 hover:text-blue-800">{{ $warehouseProduct->masterProduct->name ?? '-' }}</strong>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($warehouseProduct->masterProduct && $warehouseProduct->masterProduct->productCategory)
                                        {{ $warehouseProduct->masterProduct->productCategory->name }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($warehouseProduct->masterProduct && $warehouseProduct->masterProduct->packagingSize)
                                        {{ $warehouseProduct->masterProduct->packagingSize->name }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ number_format($warehouseProduct->quantity, 0) }}</strong>
                                </td>
                                <td>{{ number_format($warehouseProduct->minimum_stock ?? 0, 0) }}</td>
                                <td>{{ number_format($warehouseProduct->maximum_stock ?? 0, 0) }}</td>
                                <td>
                                    @php
                                        $status = 'normal';
                                        $statusClass = 'badge-info';
                                        $statusText = 'Normal';
                                        $minimumStock = (int) ($warehouseProduct->minimum_stock ?? 0);
                                        $maximumStock = (int) ($warehouseProduct->maximum_stock ?? 0);
                                        
                                        if ($warehouseProduct->quantity <= 0) {
                                            $status = 'out_of_stock';
                                            $statusClass = 'badge-danger';
                                            $statusText = 'Out of Stock';
                                        } elseif ($minimumStock > 0 && $warehouseProduct->quantity <= $minimumStock) {
                                            $status = 'low_stock';
                                            $statusClass = 'badge-warning';
                                            $statusText = 'Low Stock';
                                        } elseif ($maximumStock > 0 && $warehouseProduct->quantity > $maximumStock) {
                                            $status = 'over_stock';
                                            $statusClass = 'badge-warning';
                                            $statusText = 'Over Stock';
                                        }
                                    @endphp
                                    <span class="badge {{ $statusClass }}">{{ $statusText }}</span>
                                </td>
                                <td>
                                    {{ $warehouseProduct->updated_at ? $warehouseProduct->updated_at->format('d/M/Y H:i') : '-' }}
                                    <br>
                                    <small class="text-gray-500">{{ $warehouseProduct->updater->name ?? $warehouseProduct->creator->name ?? '-' }}</small>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9">
                                    <div class="empty-state">
                                        <i class="fas fa-box-open"></i>
                                        <p>No products found in this warehouse</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Inventory Movements Tab Content -->
            <div id="inventoryMovements" class="tab-content">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="text-lg font-semibold">Inventory Movements</h3>
                </div>

                <div class="table-container">
                    <table class="responsive-table" id="movementsTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Reference</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($warehouse->inventoryMovements ?? [] as $movement)
                            <tr>
                                <td>{{ $movement->created_at ? $movement->created_at->format('d/M/Y H:i') : '-' }}</td>
                                <td>
                                    @php
                                        $typeLabels = [
                                            'in' => 'In',
                                            'out' => 'Out',
                                            'purchase' => 'Purchase',
                                            'sale' => 'Sale',
                                            'transfer_in' => 'Transfer In',
                                            'transfer_out' => 'Transfer Out',
                                            'adjustment_in' => 'Adjustment In',
                                            'adjustment_out' => 'Adjustment Out',
                                            'issue' => 'Issue',
                                            'return' => 'Return'
                                        ];
                                        $typeLabel = $typeLabels[$movement->movement_type ?? ''] ?? ($movement->movement_type ?? 'Unknown');
                                        $typeClass = in_array($movement->movement_type ?? '', ['in', 'purchase', 'transfer_in', 'adjustment_in', 'return']) ? 'badge-success' : 'badge-danger';
                                    @endphp
                                    <span class="badge {{ $typeClass }}">{{ $typeLabel }}</span>
                                </td>
                                <td>
                                    @if($movement->masterProduct && $movement->masterProduct->name)
                                        <strong>{{ $movement->masterProduct->name }}</strong>
                                        @if($movement->masterProduct->product_code)
                                            <br><small class="text-gray-500">{{ $movement->masterProduct->product_code }}</small>
                                        @endif
                                    @else
                                        <span class="text-gray-400">Product ID: {{ $movement->master_product_id ?? '-' }}</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ ($movement->quantity ?? 0) >= 0 ? 'badge-success' : 'badge-danger' }}" style="font-size: 0.9em;">
                                        {{ ($movement->quantity ?? 0) >= 0 ? '+' : '' }}{{ number_format($movement->quantity ?? 0, 0, ',', '.') }}
                                    </span>
                                </td>
                                <td>
                                    @if($movement->reference_no)
                                        <span class="badge badge-info">{{ $movement->reference_no }}</span>
                                        @if($movement->reference_type)
                                            <br><small class="text-gray-500">{{ str_replace('_', ' ', ucfirst($movement->reference_type)) }}</small>
                                        @endif
                                    @elseif($movement->movement_no)
                                        <span class="badge badge-info">{{ $movement->movement_no }}</span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td style="white-space: normal; word-break: break-word; min-width: 250px;">
                                    @if($movement->notes && trim($movement->notes) !== '')
                                        {{ $movement->notes }}
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <i class="fas fa-exchange-alt"></i>
                                        <p>No inventory movements found</p>
                                    </div>
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

@push('scripts')
<!-- DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/responsive.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
function switchTab(event, tabName) {
    event.preventDefault();
    
    // Remove active class from all tabs and contents
    document.querySelectorAll('.tab-item').forEach(item => {
        item.classList.remove('active');
    });
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Add active class to clicked tab and corresponding content
    event.currentTarget.classList.add('active');
    document.getElementById(tabName).classList.add('active');
}

$(document).ready(function() {
    // --- Products Table Setup ---
    // 1. Clone the header row for Search Filters
    $('#productsTable thead tr').clone(true).addClass('filters').appendTo('#productsTable thead');

    // 2. Initialize Products DataTable
    var productsTable = $('#productsTable').DataTable({
        "responsive": false, 
        "lengthChange": true, 
        "autoWidth": false,
        "pageLength": 10,
        "order": [[ 1, "asc" ]], // Order by Product Name
        "orderCellsTop": true,   // IMPORTANT: Tells DT that top row is for sorting
        "fixedHeader": false,    // Disable fixed header to prevent duplication issues in some layouts
        "language": {
            "search": "Search all:",
            "zeroRecords": "No products found",
            "info": "Showing page _PAGE_ of _PAGES_",
            "infoEmpty": "No products available",
            "infoFiltered": "(filtered from _MAX_ total records)"
        },
        "initComplete": function () {
            var api = this.api();
 
            // For each column in the second row (the cloned filters)
            api.columns().eq(0).each(function (colIdx) {
                var cell = $('.filters th').eq(
                    $(api.column(colIdx).header()).index()
                );
                var title = $(cell).text().trim();
                
                // create input
                $(cell).html('<input type="text" placeholder="Search" />');
 
                // Add event listener
                $('input', $('.filters th').eq($(api.column(colIdx).header()).index()))
                    .off('keyup change')
                    .on('keyup change', function (e) {
                        e.stopPropagation();
                        // Get the search value
                        $(this).attr('title', $(this).val());
                        var regexr = '({search})'; 
                        
                        var cursorPosition = this.selectionStart;
                        // Search the column for that value
                        api
                            .column(colIdx)
                            .search(
                                this.value != ''
                                    ? this.value // simple search
                                    : '',
                                this.value != '',
                                this.value == ''
                            )
                            .draw();
                    });
            });
        }
    });

    // --- Movements Table Setup (Similar Logic) ---
    $('#movementsTable thead tr').clone(true).addClass('filters').appendTo('#movementsTable thead');

    var movementsTable = $('#movementsTable').DataTable({
        "responsive": false, 
        "lengthChange": true, 
        "autoWidth": false,
        "pageLength": 10,
        "order": [[ 0, "desc" ]], 
        "orderCellsTop": true,
        "fixedHeader": false,
        "language": {
            "search": "Search all:",
            "zeroRecords": "No movements found",
            "info": "Showing page _PAGE_ of _PAGES_",
            "infoEmpty": "No movements available",
            "infoFiltered": "(filtered from _MAX_ total records)"
        },
        "initComplete": function () {
            var api = this.api();
            api.columns().eq(0).each(function (colIdx) {
                var cell = $('#movementsTable .filters th').eq(
                    $(api.column(colIdx).header()).index()
                );
                var title = $(cell).text().trim();

                if (colIdx === 0) {
                    $(cell).html('<input type="text" class="movement-date-filter" placeholder="Select date..." readonly />');
                    var dateInput = $('input', cell)[0];

                    if (typeof flatpickr !== 'undefined') {
                        flatpickr(dateInput, {
                            dateFormat: 'd/M/Y',
                            allowInput: false,
                            onChange: function(selectedDates, dateStr) {
                                api.column(colIdx).search(dateStr).draw();
                            },
                            onReady: function(selectedDates, dateStr, instance) {
                                instance._input.addEventListener('keydown', function(event) {
                                    if (event.key === 'Backspace' || event.key === 'Delete') {
                                        instance.clear();
                                        api.column(colIdx).search('').draw();
                                    }
                                });
                            }
                        });
                    } else {
                        $(dateInput)
                            .off('keyup change')
                            .on('keyup change', function (e) {
                                e.stopPropagation();
                                api.column(colIdx).search(this.value).draw();
                            });
                    }

                    return;
                }

                $(cell).html('<input type="text" placeholder="Search" />');
                $('input', cell)
                    .off('keyup change')
                    .on('keyup change', function (e) {
                        e.stopPropagation();
                        api.column(colIdx).search(this.value).draw();
                    });
            });
        }
    });
});
</script>
@endpush
@endsection

