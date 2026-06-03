@extends('layouts.app')

@section('title', 'Detail Stock - ' . ($product->name ?? 'Product'))
@section('breadcrumb', 'Home / Warehouse / Warehouses / ' . $warehouse->name . ' / ' . ($product->name ?? 'Product'))

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
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        margin-top: 20px;
        overflow-x: auto;
        overflow-y: auto;
        max-height: 500px;
        -webkit-overflow-scrolling: touch;
    }

    /* Custom scrollbar for table container */
    .table-container::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    .table-container::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 4px;
    }

    .table-container::-webkit-scrollbar-thumb {
        background: #94a3b8;
        border-radius: 4px;
    }

    .table-container::-webkit-scrollbar-thumb:hover {
        background: #64748b;
    }

    .table-container::-webkit-scrollbar-corner {
        background: #f1f5f9;
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

    .badge-secondary {
        background-color: #f3f4f6;
        color: #4b5563;
    }

    .badge-primary {
        background-color: #dbeafe;
        color: #1e40af;
    }

    /* Header */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 2px solid #e5e7eb;
    }

    .page-title {
        font-size: 24px;
        font-weight: 600;
        color: #1f2937;
        margin: 0;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #6b7280;
    }

    .empty-state i {
        font-size: 48px;
        margin-bottom: 16px;
        opacity: 0.5;
    }

    .empty-state p {
        margin: 0;
        font-size: 14px;
    }

    /* Flex utilities */
    .flex {
        display: flex;
    }

    .justify-between {
        justify-content: space-between;
    }

    .items-center {
        align-items: center;
    }

    .mb-3 {
        margin-bottom: 12px;
    }

    .mb-4 {
        margin-bottom: 16px;
    }

    .mt-4 {
        margin-top: 16px;
    }

    .text-success {
        color: #059669;
    }

    .text-danger {
        color: #dc2626;
    }

    .text-primary {
        color: #214589;
    }

    .text-muted {
        color: #6b7280;
    }

    .font-mono {
        font-family: 'Courier New', monospace;
    }
</style>

<div class="container-fluid" style="padding: 20px;">
    <!-- Header -->
    <div class="page-header">
        <div>
            <h1 class="page-title">Detail Stock - {{ $product->name ?? 'Product' }}</h1>
            <p class="text-muted" style="margin: 4px 0 0 0; font-size: 14px;">
                Warehouse: <strong>{{ $warehouse->name }}</strong>
            </p>
        </div>
        <a href="{{ route('warehouse.warehouses.show', $warehouse->id) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Warehouse
        </a>
    </div>

    <!-- Product Info Card -->
    <div class="info-card">
        <h3 style="margin: 0 0 20px 0; font-size: 18px; font-weight: 600; color: #1f2937;">Product Information</h3>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Product Name</div>
                <div class="info-value">{{ $product->name ?? '-' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Product Code</div>
                <div class="info-value">
                    <span class="badge badge-info">{{ $product->product_code ?? '-' }}</span>
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">Category</div>
                <div class="info-value">{{ $product->productCategory->name ?? '-' }}</div>
            </div>
            @if($product->packagingSize)
            <div class="info-item">
                <div class="info-label">Package Size</div>
                <div class="info-value">{{ $product->packagingSize->name ?? '-' }}</div>
            </div>
            @endif
            <div class="info-item">
                <div class="info-label">Status Stock</div>
                <div class="info-value">
                    @php
                        $stockStatus = $warehouseProduct->stock_status ?? 'normal';
                        $stockStatusText = $warehouseProduct->stock_status_text ?? 'Normal';
                        $statusClass = 'badge-secondary';
                        if ($stockStatus === 'out_of_stock') {
                            $statusClass = 'badge-danger';
                        } elseif ($stockStatus === 'low_stock') {
                            $statusClass = 'badge-warning';
                        } elseif ($stockStatus === 'over_stock') {
                            $statusClass = 'badge-info';
                        } elseif ($stockStatus === 'normal') {
                            $statusClass = 'badge-success';
                        }
                    @endphp
                    <span class="badge {{ $statusClass }}">{{ $stockStatusText }}</span>
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">Current Stock</div>
                <div class="info-value" style="color: #214589; font-size: 20px; font-weight: 600;">
                    {{ number_format($warehouseProduct->quantity ?? 0, 0, ',', '.') }}
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">Minimum Stock</div>
                <div class="info-value">{{ number_format($warehouseProduct->minimum_stock ?? 0, 0, ',', '.') }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Maximum Stock</div>
                <div class="info-value">{{ number_format($warehouseProduct->maximum_stock ?? 0, 0, ',', '.') }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Last Updated</div>
                <div class="info-value">
                    {{ $warehouseProduct->updated_at ? $warehouseProduct->updated_at->format('d/M/Y H:i') : '-' }}
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">Warehouse</div>
                <div class="info-value">{{ $warehouse->name }}</div>
            </div>
        </div>
    </div>

    <!-- Tabs Section -->
    <div class="info-card" style="width: 100%;">
        <div class="tabs-container">
            <ul class="tabs">
                <li class="tab-item active" onclick="switchTab(event, 'basicInfo')">
                    <i class="fas fa-info-circle"></i> Basic Info
                </li>
                <li class="tab-item" onclick="switchTab(event, 'serialNumbers')">
                    <i class="fas fa-barcode"></i> Serial Numbers
                </li>
            </ul>
        </div>

        <!-- Basic Info Tab Content -->
        <div id="basicInfo" class="tab-content active">
            <div class="flex justify-between items-center mb-3" style="margin-top: 20px;">
                <h3 class="text-lg font-semibold" style="font-size: 18px; font-weight: 600; color: #1f2937; margin: 0;">Stock Movement History</h3>
            </div>

            <div class="table-container">
                <table class="responsive-table" id="movementsTable">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Adjustment</th>
                            <th>Description</th>
                            <th>Updated By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($formattedMovements as $movement)
                        <tr>
                            <td>{{ $movement['date']->format('d/M/Y H:i') }}</td>
                            <td>
                                <strong class="{{ $movement['adjustment'] >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $movement['adjustment'] >= 0 ? '+' : '' }}{{ number_format($movement['adjustment'], 0, ',', '.') }}
                                </strong>
                            </td>
                            <td>{{ $movement['description'] }}</td>
                            <td>
                                <span class="badge badge-secondary">{{ $movement['updated_by'] }}</span>
                                <br>
                                <small class="text-muted" style="font-size: 11px;">{{ $movement['updated_at']->format('d/M/Y H:i') }}</small>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4">
                                <div class="empty-state">
                                    <i class="fas fa-exchange-alt"></i>
                                    <p>No stock movements found</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Serial Numbers Tab Content -->
        <div id="serialNumbers" class="tab-content">
            <div class="flex justify-between items-center mb-3" style="margin-top: 20px;">
                <h3 class="text-lg font-semibold" style="font-size: 18px; font-weight: 600; color: #1f2937; margin: 0;">
                    Serial Numbers for {{ $product->name ?? 'Product' }}
                </h3>
            </div>

            <div class="table-container">
                <table class="responsive-table" id="serialNumbersTable">
                    <thead>
                        <tr>
                            <th>Serial Number</th>
                            <th>Unit Status</th>
                            <th>Transfer Status</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($formattedSerialNumbers as $sn)
                        <tr>
                            <td>
                                <span class="badge badge-info font-mono">{{ $sn['serial_number'] }}</span>
                            </td>
                            <td>
                                @php
                                    $statusClass = 'badge-secondary';
                                    if (in_array($sn['unit_status'], ['ready', 'available'])) {
                                        $statusClass = 'badge-success';
                                    } elseif ($sn['unit_status'] === 'broken') {
                                        $statusClass = 'badge-danger';
                                    } elseif (in_array($sn['unit_status'], ['on service', 'on_service'])) {
                                        $statusClass = 'badge-warning';
                                    } elseif ($sn['unit_status'] === 'pending') {
                                        $statusClass = 'badge-warning';
                                    } elseif (in_array($sn['unit_status'], ['in use', 'in_use'])) {
                                        $statusClass = 'badge-primary'; // Blue badge for in_use status
                                    } elseif ($sn['unit_status'] === 'on hand') {
                                        $statusClass = 'badge-info'; // Light blue for on hand
                                    }
                                @endphp
                                <span class="badge {{ $statusClass }}">
                                    {{ ucfirst(str_replace('_', ' ', $sn['unit_status'])) }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $transferClass = 'badge-info';
                                    if ($sn['transfer_status'] === 'unit on wall') {
                                        $transferClass = 'badge-primary';
                                    } elseif ($sn['transfer_status'] === 'with technician') {
                                        $transferClass = 'badge-warning';
                                    }
                                @endphp
                                <span class="badge {{ $transferClass }}">
                                    {{ ucfirst($sn['transfer_status']) }}
                                </span>
                            </td>
                            <td>{{ $sn['created_at']->format('d/M/Y H:i') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4">
                                <div class="empty-state">
                                    <i class="fas fa-barcode"></i>
                                    <p>No serial numbers found</p>
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

<script>
function switchTab(event, tabId) {
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
    document.getElementById(tabId).classList.add('active');
}
</script>
@endsection
