@extends('layouts.app')

@section('title', 'Audit Trails - System')
@section('breadcrumb', 'Home / System / Audit Trails')

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
        font-size: 13px;
        font-weight: 500;
        color: #475569;
        margin-bottom: 4px;
    }

    .form-control {
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.2s ease;
    }

    .form-control:focus {
        outline: none;
        border-color: #214589;
        box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
    }

    .filter-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    /* Table Styles */
    .table-container {
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .table-header {
        background: #f1f5f9;
        padding: 16px 20px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }

    .table-title {
        font-size: 18px;
        font-weight: 600;
        color: #1e293b;
        margin: 0;
    }

    .table-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .table-wrapper {
        overflow-x: auto;
        overflow-y: auto;
        max-height: 600px; /* Fixed height for internal scroll */
        -webkit-overflow-scrolling: touch;
        border-bottom: 1px solid #e2e8f0;
        position: relative; /* Context for sticky */
    }

    .data-table {
        width: 100%;
        border-collapse: separate; /* Required for some browsers with sticky */
        border-spacing: 0;
        font-size: 14px;
        min-width: 1200px;
    }

    .data-table th {
        background-color: #225fd3 !important;
        color: white !important;
        font-weight: 600;
        font-size: 13px;
        padding: 12px 16px;
        text-align: left;
        white-space: nowrap;
        position: sticky !important;
        z-index: 50 !important;
    }

    /* Fixed Row specific positions */
    .data-table thead tr:first-child th {
        top: 0 !important;
        z-index: 52 !important;
    }

    /* Target specifically the filter row added by script */
    .data-table thead tr.filter-row th {
        background-color: #214589 !important; /* Darker blue for distinction */
        z-index: 51 !important;
        /* style.top is still injected by JS, but we want to ensure it has z-index */
    }

    .data-table td {
        padding: 12px 16px;
        border-bottom: 1px solid #e5e7eb;
        vertical-align: middle;
        white-space: nowrap;
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
        color: #111827;
    }

    .pagination-controls .btn.active {
        background: #214589;
        border-color: #214589;
        color: white;
    }

    .pagination-controls .btn:disabled {
        background: #f9fafb;
        border-color: #e5e7eb;
        color: #9ca3af;
        cursor: not-allowed;
    }

    .pagination-controls .btn:disabled:hover {
        background: #f9fafb;
        border-color: #e5e7eb;
        color: #9ca3af;
    }

    .pagination-controls .page-numbers {
        display: flex;
        gap: 4px;
        align-items: center;
    }

    .pagination-controls .page-numbers .btn {
        min-width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
    }

    .pagination-controls .ellipsis {
        padding: 8px 4px;
        color: #9ca3af;
        font-weight: 500;
    }

    /* Responsive pagination */
    @media (max-width: 768px) {
        .pagination-wrapper {
            flex-direction: column;
            align-items: stretch;
            text-align: center;
        }
        
        .pagination-info {
            justify-content: center;
        }
        
        .pagination-controls {
            justify-content: center;
        }
        
        .pagination-controls .page-numbers {
            flex-wrap: wrap;
            justify-content: center;
        }
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 40px 20px;
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
        margin-bottom: 8px;
        color: #475569;
    }

    .empty-state p {
        font-size: 14px;
        margin: 0;
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
        justify-content: between;
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

    /* Detail Styles */
    .detail-group {
        margin-bottom: 20px;
    }

    .detail-label {
        font-size: 13px;
        font-weight: 600;
        color: #475569;
        margin-bottom: 4px;
    }

    .detail-value {
        font-size: 14px;
        color: #1e293b;
        background: #f8fafc;
        padding: 8px 12px;
        border-radius: 4px;
        border: 1px solid #e2e8f0;
        word-break: break-word;
    }

    .detail-value pre {
        margin: 0;
        font-family: 'Courier New', monospace;
        font-size: 12px;
        line-height: 1.4;
        white-space: pre-wrap;
    }

    /* Loading Spinner Styles */
    .loading-spinner {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
    }

    .spinner {
        width: 50px;
        height: 50px;
        border: 4px solid #e3f2fd;
        border-top: 4px solid #214589;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin-bottom: 15px;
    }

    .spinner-text {
        color: #214589;
        font-size: 16px;
        font-weight: 500;
        margin-top: 10px;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Pulse animation for loading dots */
    .loading-dots {
        display: flex;
        gap: 5px;
        margin-top: 10px;
    }

    .loading-dot {
        width: 8px;
        height: 8px;
        background-color: #214589;
        border-radius: 50%;
        animation: pulse 1.4s ease-in-out infinite both;
    }

    .loading-dot:nth-child(1) { animation-delay: -0.32s; }
    .loading-dot:nth-child(2) { animation-delay: -0.16s; }
    .loading-dot:nth-child(3) { animation-delay: 0s; }

    @keyframes pulse {
        0%, 80%, 100% {
            transform: scale(0.8);
            opacity: 0.5;
        }
        40% {
            transform: scale(1);
            opacity: 1;
        }
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
            Filter Audit Trails
        </div>
        <form action="{{ route('audit-trails.index') }}" method="GET">
            <div class="filter-grid">
                <div class="form-group">
                    <label class="form-label" for="table_name">Table Name</label>
                    <input type="text" name="table_name" id="table_name" class="form-control" 
                           placeholder="Filter by table name..." value="{{ request('table_name') }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="action">Action</label>
                    <select name="action" id="action" class="form-control">
                        <option value="">All Actions</option>
                        <option value="created" {{ request('action') == 'created' ? 'selected' : '' }}>Created</option>
                        <option value="updated" {{ request('action') == 'updated' ? 'selected' : '' }}>Updated</option>
                        <option value="deleted" {{ request('action') == 'deleted' ? 'selected' : '' }}>Deleted</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="module_name">Module</label>
                    <input type="text" name="module_name" id="module_name" class="form-control" 
                           placeholder="Filter by module..." value="{{ request('module_name') }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="search">Search</label>
                    <input type="text" name="search" id="search" class="form-control" 
                           placeholder="Search by anything..." value="{{ request('search') }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="user_id">User</label>
                    <select name="user_id" id="user_id" class="form-control">
                        <option value="">All Users</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
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
                <a href="{{ route('audit-trails.index') }}" class="btn btn-secondary">
                    <i class="fas fa-sync-alt"></i> Reset Filter
                </a>
                <a href="{{ route('audit-trails.export') }}" class="btn btn-success">
                    <i class="fas fa-download"></i> Export CSV
                </a>
                <a href="{{ route('audit-trails.login-history') }}" class="btn btn-info">
                    <i class="fas fa-history"></i> Login History
                </a>
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <div class="table-container">
        <div class="table-header">
            <h3 class="table-title">
                <i class="fas fa-clipboard-list"></i>
                Audit Trail Records
            </h3>
            <div class="table-actions">
                <span class="text-sm text-gray-600">
                    Showing {{ $auditTrails->firstItem() ?? 0 }} to {{ $auditTrails->lastItem() ?? 0 }} 
                    of {{ $auditTrails->total() }} entries
                </span>
            </div>
        </div>

        <div class="table-wrapper">
            @if($auditTrails->count() > 0)
                <table class="data-table">
                    <thead>
                        <tr>
                            <th data-column="id">ID</th>
                            <th data-column="model_type">Table</th>
                            <th data-column="model_id">Record ID</th>
                            <th data-column="action">Action</th>
                            <th data-column="user.name">User</th>
                            <th data-column="page_name|module_name">Menu</th>
                            <th data-column="ip_address">IP Address</th>
                            <th data-column="user_agent">User Agent</th>
                            <th data-column="created_at" data-type="date">Timestamp</th>
                            <th data-no-filter>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($auditTrails as $audit)
                        <tr>
                            <td>{{ $audit->id }}</td>
                            <td>
                                <span class="badge badge-secondary">
                                    {{ class_basename($audit->model_type) }}
                                </span>
                            </td>
                            <td>{{ $audit->model_id }}</td>
                            <td>
                                @if($audit->action == 'created')
                                    <span class="badge badge-success">
                                        <i class="fas fa-plus"></i> Created
                                    </span>
                                @elseif($audit->action == 'updated')
                                    <span class="badge badge-warning">
                                        <i class="fas fa-edit"></i> Updated
                                    </span>
                                @elseif($audit->action == 'deleted')
                                    <span class="badge badge-danger">
                                        <i class="fas fa-trash"></i> Deleted
                                    </span>
                                @else
                                    <span class="badge badge-info">
                                        <i class="fas fa-info"></i> {{ ucfirst($audit->action) }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($audit->user)
                                    <div>
                                        <strong>{{ $audit->user->name }}</strong>
                                        <br>
                                        <small class="text-gray-600">{{ $audit->user->username }}</small>
                                    </div>
                                @else
                                    <span class="text-gray-500">N/A</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-info text-wrap" style="max-width: 200px; white-space: normal;">
                                    <i class="fas fa-th-list"></i>
                                    {{ $audit->menu_label }}
                                </span>
                            </td>
                            <td>
                                <code>{{ $audit->ip_address }}</code>
                            </td>
                            <td>
                                <span title="{{ $audit->user_agent }}">
                                    {{ Str::limit($audit->user_agent, 30) }}
                                </span>
                            </td>
                            <td>
                                <div>
                                    <strong>{{ formatIndonesianDate($audit->created_at) }}</strong>
                                    <br>
                                    <small class="text-gray-600">{{ formatIndonesianTimeOnly($audit->created_at) }} WIB</small>
                                </div>
                            </td>
                            <td>
                                <button class="btn btn-info btn-sm view-details" 
                                        data-id="{{ $audit->id }}" 
                                        data-table="{{ class_basename($audit->model_type) }}" 
                                        data-record-id="{{ $audit->model_id }}">
                                    <i class="fas fa-eye"></i> Details
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty-state">
                    <i class="fas fa-clipboard-list"></i>
                    <h3>No Audit Records Found</h3>
                    <p>No audit trail records match your current filter criteria.</p>
                </div>
            @endif
        </div>

        @if($auditTrails->hasPages())
            <div class="pagination-wrapper">
                <div class="pagination-info">
                    <span class="info-text">
                        Menampilkan {{ $auditTrails->firstItem() ?? 0 }} sampai {{ $auditTrails->lastItem() ?? 0 }} 
                        dari {{ $auditTrails->total() }} entri
                    </span>
                </div>
                <div class="pagination-controls">
                    {{ $auditTrails->appends(request()->query())->links('pagination.custom') }}
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Audit Trail Details Modal -->
<div class="modal" id="auditDetailsModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h5 class="modal-title">
                <i class="fas fa-info-circle"></i>
                Audit Trail Details
            </h5>
            <button type="button" class="modal-close" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div id="auditDetailsContent">
                <!-- Details will be loaded here via AJAX -->
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal()">
                <i class="fas fa-times"></i> Tutup
            </button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // View details functionality
    $(document).on('click', '.view-details', function() {
        var auditId = $(this).data('id');
        var tableName = $(this).data('table');
        var recordId = $(this).data('record-id');

        console.log('Clicked details button:', {auditId, tableName, recordId});

                // Show loading
                $('#auditDetailsContent').html(`
                    <div class="loading-spinner">
                        <div class="spinner"></div>
                        <div class="spinner-text">Memuat detail audit...</div>
                        <div class="loading-dots">
                            <div class="loading-dot"></div>
                            <div class="loading-dot"></div>
                            <div class="loading-dot"></div>
                        </div>
                    </div>
                `);
        $('#auditDetailsModal').addClass('show');
        console.log('Modal should be visible now');

        // Fetch details
        $.ajax({
            url: `/audit-trails/${tableName}/${recordId}?audit_id=${auditId}`,
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                console.log('AJAX Success:', response);
                var content = '';
                if (response.length > 0) {
                    response.forEach(function(audit, index) {
                        content += `
                            <div class="detail-group">
                                <div class="detail-label">Record #${index + 1}</div>
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px;">
                                    <div>
                                        <div class="detail-label">Audit ID</div>
                                        <div class="detail-value">${audit.id}</div>
                                    </div>
                                    <div>
                                        <div class="detail-label">Table</div>
                                        <div class="detail-value">${audit.model_type.split('\\').pop()}</div>
                                    </div>
                                    <div>
                                        <div class="detail-label">Record ID</div>
                                        <div class="detail-value">${audit.model_id}</div>
                                    </div>
                                    <div>
                                        <div class="detail-label">Action</div>
                                        <div class="detail-value">
                                            <span class="badge badge-${audit.action === 'created' ? 'success' : audit.action === 'updated' ? 'warning' : 'danger'}">
                                                ${audit.action}
                                            </span>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="detail-label">User</div>
                                        <div class="detail-value">${audit.user ? audit.user.name : 'N/A'}</div>
                                    </div>
                                    <div>
                                        <div class="detail-label">Module</div>
                                        <div class="detail-value">${audit.module_name || 'N/A'}</div>
                                    </div>
                                    <div>
                                        <div class="detail-label">Page</div>
                                        <div class="detail-value">${audit.page_name || 'N/A'}</div>
                                    </div>
                                    <div>
                                        <div class="detail-label">IP Address</div>
                                        <div class="detail-value"><code>${audit.ip_address}</code></div>
                                    </div>
                                    <div style="grid-column: 1 / -1;">
                                        <div class="detail-label">User Agent</div>
                                        <div class="detail-value">${audit.user_agent}</div>
                                    </div>
                                    <div>
                                        <div class="detail-label">Timestamp</div>
                                        <div class="detail-value">${new Date(audit.created_at).toLocaleString('id-ID', { 
                                            timeZone: 'Asia/Jakarta',
                                            year: 'numeric',
                                            month: '2-digit',
                                            day: '2-digit',
                                            hour: '2-digit',
                                            minute: '2-digit',
                                            second: '2-digit',
                                            hour12: false
                                        })} WIB</div>
                                    </div>
                                </div>
                                
                                ${audit.old_values ? `
                                    <div style="margin-top: 16px;">
                                        <div class="detail-label">Nilai Lama</div>
                                        <div class="detail-value">
                                            <pre>${JSON.stringify(audit.old_values, null, 2)}</pre>
                                        </div>
                                    </div>
                                ` : ''}
                                
                                ${audit.new_values ? `
                                    <div style="margin-top: 16px;">
                                        <div class="detail-label">Nilai Baru</div>
                                        <div class="detail-value">
                                            <pre>${JSON.stringify(audit.new_values, null, 2)}</pre>
                                        </div>
                                    </div>
                                ` : ''}
                                
                                ${audit.changed_fields && audit.changed_fields.length > 0 ? `
                                    <div style="margin-top: 16px;">
                                        <div class="detail-label">Field yang Berubah</div>
                                        <div class="detail-value">
                                            ${audit.changed_fields.map(field => `<span class="badge badge-info">${field}</span>`).join(' ')}
                                        </div>
                                    </div>
                                ` : ''}
                            </div>
                            ${index < response.length - 1 ? '<hr style="margin: 20px 0; border: 1px solid #e2e8f0;">' : ''}
                        `;
                    });
                } else {
                    content = `
                        <div class="empty-state">
                            <i class="fas fa-info-circle"></i>
                            <h3>Tidak Ada Detail Record</h3>
                            <p>Tidak ada detail audit log untuk record ini.</p>
                        </div>
                    `;
                }
                $('#auditDetailsContent').html(content);
            },
            error: function(xhr, status, error) {
                console.error("Error fetching audit details:", error);
                console.error("XHR Response:", xhr.responseText);
                console.error("Status:", status);
                $('#auditDetailsContent').html(`
                    <div class="empty-state">
                        <i class="fas fa-exclamation-triangle" style="color: #dc2626;"></i>
                        <h3>Gagal Memuat Detail</h3>
                        <p>Gagal memuat detail audit. Silakan coba lagi.</p>
                        <p><small>Kesalahan: ${error}</small></p>
                    </div>
                `);
            }
        });
    });

    // Close modal function
    window.closeModal = function() {
        $('#auditDetailsModal').removeClass('show');
    };

});
</script>
@endpush
