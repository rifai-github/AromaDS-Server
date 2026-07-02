@extends('layouts.app')

@section('title', 'Purchasing Requests - Warehouse')
@section('breadcrumb', 'Home / Warehouse / Inventory Logistics / Purchasing Requests')

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
            Filter Purchasing Requests
        </div>
        <form action="{{ route('warehouse.inventory-logistics.purchasing-requests') }}" method="GET">
            <div class="filter-grid">
                <div class="form-group">
                    <label class="form-label" for="request_number">Request Number</label>
                    <input type="text" name="request_number" id="request_number" class="form-control" 
                           placeholder="Filter by request number..." value="{{ request('request_number') }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="status">Status</label>
                    <select name="status" id="status" class="form-control">
                        <option value="">All Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
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
                <a href="{{ route('warehouse.inventory-logistics.purchasing-requests') }}" class="btn btn-secondary">
                    <i class="fas fa-sync-alt"></i> Reset Filter
                </a>
                <button type="button" class="btn btn-success" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i> Create Purchasing Request
                </button>
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <div class="table-container">
        <div class="table-header">
            <h3 class="table-title">
                <i class="fas fa-shopping-cart"></i>
                Purchasing Request Records
            </h3>
            <div class="table-actions">
                <span class="text-sm text-gray-600">
                    Showing {{ $purchasingRequests->firstItem() ?? 0 }} to {{ $purchasingRequests->lastItem() ?? 0 }} 
                    of {{ $purchasingRequests->total() }} entries
                </span>
            </div>
        </div>

        <div class="table-wrapper">
            @if($purchasingRequests->count() > 0)
                <table class="data-table">
                    <thead>
                        <tr>
                            <th data-column="request_number">Request Number</th>
                            <th data-column="reason">Reason</th>
                            <th data-column="estimated_cost" data-type="numeric">Total Amount</th>
                            <th data-column="status">Status</th>
                            <th data-column="requestedBy__name">Requested By</th>
                            <th data-column="created_at" data-type="date">Created At</th>
                            <th data-no-filter>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($purchasingRequests as $request)
                        <tr>
                            <td>
                                <strong>{{ $request->request_number }}</strong>
                            </td>
                            <td>{{ $request->reason ?? 'N/A' }}</td>
                            <td>Rp {{ number_format($request->total_amount, 0, ',', '.') }}</td>
                            <td>
                                <span class="badge badge-{{ $request->status == 'approved' ? 'success' : ($request->status == 'rejected' ? 'danger' : ($request->status == 'completed' ? 'info' : 'warning')) }}">
                                    {{ ucfirst($request->status) }}
                                </span>
                            </td>
                            <td>{{ $request->requestedBy->name ?? 'N/A' }}</td>
                            <td>{{ $request->created_at->format('d/M/Y H:i') }}</td>
                            <td>
                                <div style="display: flex; gap: 5px;">
                                    <button class="btn btn-sm btn-info" onclick="viewPurchasingRequest({{ $request->id }})">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    @if($request->status == 'pending')
                                        <button class="btn btn-sm btn-success" onclick="approvePurchasingRequest({{ $request->id }})">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="rejectPurchasingRequest({{ $request->id }})">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty-state">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>No Purchasing Request Records Found</h3>
                    <p>No purchasing request records match your current filter criteria.</p>
                    <button class="btn btn-primary" onclick="openCreateModal()">
                        <i class="fas fa-plus"></i> Create First Purchasing Request
                    </button>
                </div>
            @endif
        </div>

        @if($purchasingRequests->count() > 0)
            <div class="pagination-wrapper">
                <div class="pagination-info">
                    <span class="info-text">
                        Showing {{ $purchasingRequests->firstItem() }} to {{ $purchasingRequests->lastItem() }} 
                        of {{ $purchasingRequests->total() }} entries
                    </span>
                </div>
                <div class="pagination-controls">
                    {{ $purchasingRequests->links() }}
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Create/Edit Modal -->
<div id="purchasingRequestModal" class="modal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title" id="modalTitle">Create Purchasing Request</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form id="purchasingRequestForm" method="POST">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="logistics_tracking_id">Logistics Tracking</label>
                    <select name="logistics_tracking_id" id="logistics_tracking_id" class="form-control" required>
                        <option value="">Select Logistics Tracking</option>
                        @foreach($logisticsTrackings as $tracking)
                            <option value="{{ $tracking->id }}">{{ $tracking->tracking_number }} - {{ $tracking->fromWarehouse->name ?? 'N/A' }} to {{ $tracking->toBranch->name ?? 'N/A' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="reason">Reason</label>
                    <input type="text" name="reason" id="reason" class="form-control" required 
                           placeholder="Enter reason for purchasing request">
                </div>
                <div class="form-group">
                    <label class="form-label" for="total_amount">Total Amount</label>
                    <input type="number" name="total_amount" id="total_amount" class="form-control" 
                           placeholder="Enter total amount" required step="0.01" min="0">
                </div>
                <div class="form-group">
                    <label class="form-label" for="notes">Notes</label>
                    <textarea name="notes" id="notes" class="form-control" rows="3" placeholder="Enter notes"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Purchasing Request</button>
            </div>
        </form>
    </div>
</div>

<!-- View Modal -->
<div id="viewModal" class="modal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title">Purchasing Request Details</h3>
            <button class="modal-close" onclick="closeViewModal()">&times;</button>
        </div>
        <div class="modal-body" id="viewModalBody">
            <!-- Content will be loaded here -->
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeViewModal()">Close</button>
        </div>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Create Purchasing Request';
    document.getElementById('purchasingRequestForm').action = '{{ route("warehouse.inventory-logistics.purchasing-requests.store") }}';
    document.getElementById('purchasingRequestForm').method = 'POST';
    document.getElementById('purchasingRequestForm').reset();
    document.getElementById('purchasingRequestModal').classList.add('show');
}

function closeModal() {
    document.getElementById('purchasingRequestModal').classList.remove('show');
}

function closeViewModal() {
    document.getElementById('viewModal').classList.remove('show');
}

function viewPurchasingRequest(id) {
    // Load purchasing request details via AJAX
    fetch(`/warehouse/inventory-logistics/purchasing-requests/${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('viewModalBody').innerHTML = `
                <div class="detail-group">
                    <div class="detail-label">Request Number</div>
                    <div class="detail-value">${data.request_number}</div>
                </div>
                <div class="detail-group">
                    <div class="detail-label">Status</div>
                    <div class="detail-value">
                        <span class="badge badge-${data.status == 'approved' ? 'success' : (data.status == 'rejected' ? 'danger' : (data.status == 'completed' ? 'info' : 'warning'))}">${data.status.charAt(0).toUpperCase() + data.status.slice(1)}</span>
                    </div>
                </div>
                <div class="detail-group">
                    <div class="detail-label">Reason</div>
                    <div class="detail-value">${data.reason || 'N/A'}</div>
                </div>
                <div class="detail-group">
                    <div class="detail-label">Total Amount</div>
                    <div class="detail-value">Rp ${data.total_amount ? data.total_amount.toLocaleString('id-ID') : 'N/A'}</div>
                </div>
                <div class="detail-group">
                    <div class="detail-label">Notes</div>
                    <div class="detail-value">${data.notes || 'N/A'}</div>
                </div>
                <div class="detail-group">
                    <div class="detail-label">Requested By</div>
                    <div class="detail-value">${data.requested_by?.name || 'N/A'}</div>
                </div>
                <div class="detail-group">
                    <div class="detail-label">Created At</div>
                    <div class="detail-value">${data.created_at || 'N/A'}</div>
                </div>
            `;
            document.getElementById('viewModal').classList.add('show');
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Gagal memuat detail purchasing request');
        });
}

function approvePurchasingRequest(id) {
    showConfirmDialog('Setujui purchasing request?', 'Purchasing request ini akan disetujui.')
    .then((confirmed) => {
        if (!confirmed) {
            return;
        }
        fetch(`/warehouse/inventory-logistics/purchasing-requests/${id}/approve`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Gagal menyetujui purchasing request: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Gagal menyetujui purchasing request');
        });
    });
}

function rejectPurchasingRequest(id) {
    showConfirmDialog('Tolak purchasing request?', 'Purchasing request ini akan ditolak.')
    .then((confirmed) => {
        if (!confirmed) {
            return;
        }
        fetch(`/warehouse/inventory-logistics/purchasing-requests/${id}/reject`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Gagal menolak purchasing request: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Gagal menolak purchasing request');
        });
    });
}

// Close modals when clicking outside
window.onclick = function(event) {
    const purchasingRequestModal = document.getElementById('purchasingRequestModal');
    const viewModal = document.getElementById('viewModal');
    
    if (event.target === purchasingRequestModal) {
        closeModal();
    }
    if (event.target === viewModal) {
        closeViewModal();
    }
}
</script>
@endsection
