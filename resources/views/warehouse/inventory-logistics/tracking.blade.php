@extends('layouts.app')

@section('title', 'Logistics Tracking - Warehouse')
@section('breadcrumb', 'Home / Warehouse / Inventory Logistics / Tracking')

@section('content')
<style>
    /* Global overflow control */
    html, body {
        overflow-x: hidden;
        max-width: 100vw;
    }

    *, *::before, *::after {
        box-sizing: border-box;
    }

    /* Button Styles */
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
    }

    .btn-secondary {
        background-color: #f3f4f6;
        color: #6b7280;
        border: 1px solid #d1d5db;
    }

    .btn-secondary:hover {
        background-color: #e5e7eb;
        color: #4b5563;
    }

    .btn-success {
        background-color: #16a34a;
        color: white;
    }

    .btn-success:hover {
        background-color: #15803d;
    }

    .btn-info {
        background-color: #0ea5e9;
        color: white;
    }

    .btn-info:hover {
        background-color: #0284c7;
    }

    .btn-warning {
        background-color: #f59e0b;
        color: white;
    }

    .btn-warning:hover {
        background-color: #d97706;
    }

    .btn-danger {
        background-color: #dc2626;
        color: white;
    }

    .btn-danger:hover {
        background-color: #b91c1c;
    }

    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
    }

    /* Filter Section */
    .filter-section {
        background: #f8fafc;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        border: 1px solid #e2e8f0;
    }

    .filter-title {
        font-size: 16px;
        font-weight: 600;
        color: #334155;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .filter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 16px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
    }

    .form-label {
        font-size: 14px;
        font-weight: 500;
        color: #374151;
        margin-bottom: 6px;
    }

    .form-control {
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.2s ease;
        background-color: white;
    }

    .form-control:focus {
        outline: none;
        border-color: #214589;
        box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
    }

    .filter-actions {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    /* Table Container */
    .table-container {
        background: white;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        overflow: hidden;
    }

    .table-header {
        background: #f8fafc;
        padding: 20px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
    }

    .table-title {
        font-size: 18px;
        font-weight: 600;
        color: #1e293b;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .table-actions {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
    }

    .table-wrapper {
        overflow-x: auto;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }

    .data-table th {
        background: #f1f5f9;
        color: #475569;
        font-weight: 600;
        padding: 12px 16px;
        text-align: left;
        border-bottom: 1px solid #e2e8f0;
        white-space: nowrap;
    }

    .data-table td {
        padding: 12px 16px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }

    .data-table tbody tr:hover {
        background-color: #f8fafc;
        transition: background-color 0.2s ease;
    }

    .data-table tbody tr:nth-child(even) {
        background-color: #f9fafb;
    }

    .data-table tbody tr:nth-child(even):hover {
        background-color: #f1f5f9;
    }

    /* Clickable Row Styles */
    .clickable-row:hover {
        background-color: #f8fafc !important;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        transition: all 0.2s ease;
    }

    /* Modal Styles */
    .modal-lg {
        max-width: 800px;
    }

    /* Tracking Details Styles */
    .tracking-details {
        padding: 0;
    }

    .detail-section {
        margin-bottom: 24px;
        padding: 16px;
        background: #f8fafc;
        border-radius: 8px;
        border-left: 4px solid #214589;
    }

    .section-title {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0 0 16px 0;
        font-size: 16px;
        font-weight: 600;
        color: #214589;
    }

    .detail-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 16px;
    }

    .detail-item {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .detail-item.full-width {
        grid-column: 1 / -1;
    }

    .detail-label {
        font-size: 12px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .detail-value {
        font-size: 14px;
        color: #1e293b;
        font-weight: 500;
    }

    .detail-value.tracking-number {
        font-size: 16px;
        font-weight: 700;
        color: #214589;
    }

    /* Loading and Error States */
    .loading-state, .error-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 40px;
        text-align: center;
    }

    .loading-state i, .error-state i {
        font-size: 24px;
        margin-bottom: 12px;
    }

    .loading-state i {
        color: #214589;
    }

    .error-state i {
        color: #dc2626;
    }

    .loading-state p, .error-state p {
        margin: 0;
        color: #64748b;
    }

    /* Badge Styles */
    .badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
        line-height: 1;
        white-space: nowrap;
    }

    .badge-success {
        background-color: #dcfce7;
        color: #166534;
        border: 1px solid #bbf7d0;
    }

    .badge-warning {
        background-color: #fef3c7;
        color: #d97706;
        border: 1px solid #fde68a;
    }

    .badge-danger {
        background-color: #fee2e2;
        color: #dc2626;
        border: 1px solid #fecaca;
    }

    .badge-info {
        background-color: #dbeafe;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
    }

    .badge-secondary {
        background-color: #f1f5f9;
        color: #64748b;
        border: 1px solid #e2e8f0;
    }

    .badge-primary {
        background-color: #dbeafe;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
    }

    /* Pagination */
    .pagination-wrapper {
        padding: 20px;
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
    }

    .pagination-info {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #64748b;
        font-size: 14px;
    }

    .pagination-info .info-text {
        font-weight: 500;
    }

    .pagination-controls {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .pagination-controls .btn {
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        background: white;
        color: #374151;
        text-decoration: none;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .pagination-controls .btn:hover {
        background: #f3f4f6;
        border-color: #9ca3af;
    }

    .pagination-controls .btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .pagination-controls .btn.active {
        background: #214589;
        color: white;
        border-color: #214589;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #64748b;
    }

    .empty-state i {
        font-size: 48px;
        color: #cbd5e1;
        margin-bottom: 16px;
    }

    .empty-state h3 {
        font-size: 18px;
        font-weight: 600;
        color: #475569;
        margin-bottom: 8px;
    }

    .empty-state p {
        font-size: 14px;
        color: #64748b;
        margin-bottom: 20px;
    }

    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }

    .modal.show {
        display: flex !important;
    }

    .modal-dialog {
        background: white;
        border-radius: 8px;
        width: 90%;
        max-width: 800px;
        max-height: 80vh;
        overflow: hidden;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    }

    .modal-header {
        background: #f1f5f9;
        padding: 16px 20px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-title {
        font-size: 18px;
        font-weight: 600;
        color: #1e293b;
        margin: 0;
    }

    .modal-close {
        background: none;
        border: none;
        font-size: 20px;
        color: #64748b;
        cursor: pointer;
        padding: 4px;
        border-radius: 4px;
        transition: color 0.2s ease;
    }

    .modal-close:hover {
        color: #334155;
    }

    .modal-body {
        padding: 20px;
        max-height: 60vh;
        overflow-y: auto;
    }

    .modal-footer {
        background: #f8fafc;
        padding: 16px 20px;
        border-top: 1px solid #e2e8f0;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    /* Progress Bar */
    .progress-bar {
        width: 100%;
        height: 8px;
        background-color: #e2e8f0;
        border-radius: 4px;
        overflow: hidden;
    }

    .progress-fill {
        height: 100%;
        background-color: #214589;
        transition: width 0.3s ease;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .filter-grid {
            grid-template-columns: 1fr;
        }

        .filter-actions {
            flex-direction: column;
        }

        .filter-actions .btn {
            justify-content: center;
        }

        .table-header {
            flex-direction: column;
            align-items: stretch;
        }

        .table-actions {
            justify-content: stretch;
        }

        .table-actions .btn {
            justify-content: center;
        }

        .data-table {
            font-size: 12px;
        }

        .data-table th,
        .data-table td {
            padding: 8px 12px;
        }
    }
</style>

<div class="container-fluid">
    <!-- Filter Section -->
    <div class="filter-section">
        <div class="filter-title">
            <i class="fas fa-filter"></i>
            Filter Logistics Tracking
        </div>
        <form action="{{ route('warehouse.inventory-logistics.tracking') }}" method="GET">
            <div class="filter-grid">
                <div class="form-group">
                    <label class="form-label" for="tracking_number">Tracking Number</label>
                    <input type="text" name="tracking_number" id="tracking_number" class="form-control" 
                           placeholder="Filter by tracking number..." value="{{ request('tracking_number') }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="status">Status</label>
                    <select name="status" id="status" class="form-control">
                        <option value="">All Status</option>
                        <option value="requested" {{ request('status') == 'requested' ? 'selected' : '' }}>Requested</option>
                        <option value="preparing" {{ request('status') == 'preparing' ? 'selected' : '' }}>Preparing</option>
                        <option value="shipped" {{ request('status') == 'shipped' ? 'selected' : '' }}>Shipped</option>
                        <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Delivered</option>
                        <option value="returned" {{ request('status') == 'returned' ? 'selected' : '' }}>Returned</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="from_warehouse_id">From Warehouse</label>
                    <select name="from_warehouse_id" id="from_warehouse_id" class="form-control">
                        <option value="">All Warehouses</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" {{ request('from_warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                {{ $warehouse->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="to_branch_id">To Branch</label>
                    <select name="to_branch_id" id="to_branch_id" class="form-control">
                        <option value="">All Branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ request('to_branch_id') == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="start_date">Start Date</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" 
                           value="{{ request('start_date') }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="end_date">End Date</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" 
                           value="{{ request('end_date') }}">
                </div>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Apply Filter
                </button>
                <a href="{{ route('warehouse.inventory-logistics.tracking') }}" class="btn btn-secondary">
                    <i class="fas fa-sync-alt"></i> Reset Filter
                </a>
                <button type="button" class="btn btn-success" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i> Create Tracking
                </button>
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <div class="table-container">
        <div class="table-header">
            <h3 class="table-title">
                <i class="fas fa-shipping-fast"></i>
                Logistics Tracking Records
            </h3>
            <div class="table-actions">
                <span class="text-sm text-gray-600">
                    Showing {{ $trackings->firstItem() ?? 0 }} to {{ $trackings->lastItem() ?? 0 }} 
                    of {{ $trackings->total() }} entries
                </span>
            </div>
        </div>

        <div class="table-wrapper">
            @if($trackings->count() > 0)
                <table class="data-table">
                    <thead>
                        <tr>
                            <th data-column="tracking_number">Tracking Number</th>
                            <th data-column="fromWarehouse__name">From Warehouse</th>
                            <th data-column="toBranch__name">To Branch</th>
                            <th data-column="status">Status</th>
                            <th data-no-filter>Progress</th>
                            <th data-column="resi_number">Resi Number</th>
                            <th data-column="courier_name">Courier</th>
                            <th data-column="requested_at" data-type="date">Requested At</th>
                            <th data-no-filter>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($trackings as $tracking)
                        <tr class="clickable-row" onclick="viewTracking({{ $tracking->id }})" style="cursor: pointer;">
                            <td>
                                <strong>{{ $tracking->tracking_number }}</strong>
                            </td>
                            <td>{{ $tracking->fromWarehouse->name ?? 'N/A' }}</td>
                            <td>{{ $tracking->toBranch->name ?? 'N/A' }}</td>
                            <td>
                                <span class="badge badge-{{ $tracking->status_badge_class }}">
                                    {{ $tracking->status_text }}
                                </span>
                            </td>
                            <td>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: {{ $tracking->progress_percentage }}%"></div>
                                </div>
                                <small class="text-muted">{{ $tracking->progress_percentage }}%</small>
                            </td>
                            <td>{{ $tracking->resi_number ?? 'N/A' }}</td>
                            <td>{{ $tracking->courier_name ?? 'N/A' }}</td>
                            <td>{{ $tracking->requested_at ? $tracking->requested_at->format('d/m/Y H:i') : 'N/A' }}</td>
                            <td>
                                <div style="display: flex; gap: 5px;">
                                    <span class="text-muted text-sm">Click row to view details</span>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty-state">
                    <i class="fas fa-shipping-fast"></i>
                    <h3>No Tracking Records Found</h3>
                    <p>No logistics tracking records match your current filter criteria.</p>
                    <button class="btn btn-primary" onclick="openCreateModal()">
                        <i class="fas fa-plus"></i> Create First Tracking
                    </button>
                </div>
            @endif
        </div>

        @if($trackings->count() > 0)
            <div class="pagination-wrapper">
                <div class="pagination-info">
                    <span class="info-text">
                        Showing {{ $trackings->firstItem() }} to {{ $trackings->lastItem() }} 
                        of {{ $trackings->total() }} entries
                    </span>
                </div>
                <div class="pagination-controls">
                    {{ $trackings->links() }}
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Create/Edit Modal -->
<div id="trackingModal" class="modal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title" id="modalTitle">Create Tracking</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form id="trackingForm" method="POST">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="inventory_request_id">Inventory Request</label>
                    <select name="inventory_request_id" id="inventory_request_id" class="form-control" required>
                        <option value="">Select Inventory Request</option>
                        @foreach($inventoryRequests as $request)
                            <option value="{{ $request->id }}">{{ $request->request_number }} - {{ $request->description }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="from_warehouse_id">From Warehouse</label>
                    <select name="from_warehouse_id" id="from_warehouse_id" class="form-control" required>
                        <option value="">Select Warehouse</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="to_branch_id">To Branch</label>
                    <select name="to_branch_id" id="to_branch_id" class="form-control" required>
                        <option value="">Select Branch</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="resi_number">Resi Number</label>
                    <input type="text" name="resi_number" id="resi_number" class="form-control" placeholder="Enter resi number">
                </div>
                <div class="form-group">
                    <label class="form-label" for="courier_name">Courier Name</label>
                    <input type="text" name="courier_name" id="courier_name" class="form-control" placeholder="Enter courier name">
                </div>
                <div class="form-group">
                    <label class="form-label" for="notes">Notes</label>
                    <textarea name="notes" id="notes" class="form-control" rows="3" placeholder="Enter notes"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Tracking</button>
            </div>
        </form>
    </div>
</div>

<!-- View Modal -->
<div id="viewModal" class="modal">
    <div class="modal-dialog modal-lg">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-shipping-fast"></i>
                Detail Tracking
            </h3>
            <button class="modal-close" onclick="closeViewModal()">&times;</button>
        </div>
        <div class="modal-body" id="viewModalBody">
            <div class="loading-state">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Memuat detail tracking...</p>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeViewModal()">
                <i class="fas fa-times"></i> Tutup
            </button>
        </div>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Tambah Tracking';
    document.getElementById('trackingForm').action = '{{ route("warehouse.inventory-logistics.tracking.store") }}';
    document.getElementById('trackingForm').method = 'POST';
    document.getElementById('trackingForm').reset();
    document.getElementById('trackingModal').classList.add('show');
}

function closeModal() {
    document.getElementById('trackingModal').classList.remove('show');
}

function closeViewModal() {
    document.getElementById('viewModal').classList.remove('show');
}

function viewTracking(id) {
    // Show loading state
    document.getElementById('viewModalBody').innerHTML = `
        <div class="loading-state">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Memuat detail tracking...</p>
        </div>
    `;
    document.getElementById('viewModal').classList.add('show');
    
    // Load tracking details via AJAX
    fetch(`/warehouse/inventory-logistics/tracking/${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('viewModalBody').innerHTML = `
                <div class="tracking-details">
                    <!-- Header Section -->
                    <div class="detail-section">
                        <h4 class="section-title">
                            <i class="fas fa-info-circle"></i>
                            Informasi Dasar
                        </h4>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <label class="detail-label">Tracking Number</label>
                                <div class="detail-value tracking-number">${data.tracking_number}</div>
                            </div>
                            <div class="detail-item">
                                <label class="detail-label">Status</label>
                                <div class="detail-value">
                                    <span class="badge badge-${getStatusBadgeClass(data.status)}">${data.status}</span>
                                </div>
                            </div>
                            <div class="detail-item">
                                <label class="detail-label">Resi Number</label>
                                <div class="detail-value">${data.resi_number || 'N/A'}</div>
                            </div>
                            <div class="detail-item">
                                <label class="detail-label">Courier</label>
                                <div class="detail-value">${data.courier_name || 'N/A'}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Location Section -->
                    <div class="detail-section">
                        <h4 class="section-title">
                            <i class="fas fa-map-marker-alt"></i>
                            Informasi Lokasi
                        </h4>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <label class="detail-label">From Warehouse</label>
                                <div class="detail-value">${data.from_warehouse}</div>
                            </div>
                            <div class="detail-item">
                                <label class="detail-label">To Branch</label>
                                <div class="detail-value">${data.to_branch}</div>
                            </div>
                            <div class="detail-item">
                                <label class="detail-label">Inventory Request</label>
                                <div class="detail-value">${data.inventory_request}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Timeline Section -->
                    <div class="detail-section">
                        <h4 class="section-title">
                            <i class="fas fa-clock"></i>
                            Timeline
                        </h4>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <label class="detail-label">Requested At</label>
                                <div class="detail-value">${data.requested_at || 'N/A'}</div>
                            </div>
                            <div class="detail-item">
                                <label class="detail-label">Preparing At</label>
                                <div class="detail-value">${data.preparing_at || 'N/A'}</div>
                            </div>
                            <div class="detail-item">
                                <label class="detail-label">Shipped At</label>
                                <div class="detail-value">${data.shipped_at || 'N/A'}</div>
                            </div>
                            <div class="detail-item">
                                <label class="detail-label">Delivered At</label>
                                <div class="detail-value">${data.delivered_at || 'N/A'}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Info Section -->
                    <div class="detail-section">
                        <h4 class="section-title">
                            <i class="fas fa-sticky-note"></i>
                            Informasi Tambahan
                        </h4>
                        <div class="detail-grid">
                            <div class="detail-item full-width">
                                <label class="detail-label">Notes</label>
                                <div class="detail-value">${data.notes || 'Tidak ada catatan'}</div>
                            </div>
                            <div class="detail-item">
                                <label class="detail-label">Created At</label>
                                <div class="detail-value">${data.created_at}</div>
                            </div>
                            <div class="detail-item">
                                <label class="detail-label">Created By</label>
                                <div class="detail-value">${data.created_by}</div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('viewModalBody').innerHTML = `
                <div class="error-state">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Gagal memuat detail tracking. Silakan coba lagi.</p>
                </div>
            `;
        });
}

function getStatusBadgeClass(status) {
    const statusMap = {
        'requested': 'warning',
        'preparing': 'info',
        'shipped': 'primary',
        'delivered': 'success',
        'returned': 'secondary',
        'cancelled': 'danger'
    };
    return statusMap[status] || 'secondary';
}


// Close modals when clicking outside
window.onclick = function(event) {
    const trackingModal = document.getElementById('trackingModal');
    const viewModal = document.getElementById('viewModal');
    
    if (event.target === trackingModal) {
        closeModal();
    }
    if (event.target === viewModal) {
        closeViewModal();
    }
}
</script>
@endsection
