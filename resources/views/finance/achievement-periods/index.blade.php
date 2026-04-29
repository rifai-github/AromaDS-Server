@extends('layouts.app')

@section('title', 'Achievement Period Management')
@section('breadcrumb', 'Home / Finance / Achievement Period Management')

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
        background-color: #10b981;
        color: white;
    }

    .btn-success:hover {
        background-color: #059669;
    }

    .btn-warning {
        background-color: #f59e0b;
        color: white;
    }

    .btn-warning:hover {
        background-color: #d97706;
    }

    .btn-danger {
        background-color: #ef4444;
        color: white;
    }

    .btn-danger:hover {
        background-color: #dc2626;
    }

    .btn-info {
        background-color: #3b82f6;
        color: white;
    }

    .btn-info:hover {
        background-color: #2563eb;
    }

    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
    }

    /* Page Header */
    .page-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px 0;
        margin-bottom: 30px;
    }

    .page-title {
        font-size: 28px;
        font-weight: 700;
        margin: 0;
    }

    .page-subtitle {
        font-size: 16px;
        opacity: 0.9;
        margin: 8px 0 0 0;
    }

    /* Statistics Cards */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }

    .stat-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }

    .stat-icon.primary {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .stat-icon.success {
        background: #dcfce7;
        color: #16a34a;
    }

    .stat-icon.warning {
        background: #fef3c7;
        color: #d97706;
    }

    .stat-icon.info {
        background: #dbeafe;
        color: #0ea5e9;
    }

    .stat-value {
        font-size: 32px;
        font-weight: 700;
        color: #1f2937;
        margin: 0;
    }

    .stat-label {
        font-size: 14px;
        color: #6b7280;
        margin: 4px 0 0 0;
    }

    /* Filter Section */
    .filter-section {
        background: white;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 30px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb;
    }

    .filter-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
    }

    .filter-title {
        font-size: 18px;
        font-weight: 600;
        color: #1f2937;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .filter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
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
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .form-control:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .filter-actions {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    /* Table Styles */
    .table-container {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb;
    }

    .table-header {
        background: #f8fafc;
        padding: 20px 24px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .table-title {
        font-size: 18px;
        font-weight: 600;
        color: #1f2937;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .table-actions {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .table-wrapper {
        overflow-x: auto;
    }

    .table thead th, .data-table thead th {
        background-color: white !important;
        color: #214589 !important;
        font-weight: 600;
        padding: 12px 8px;
        vertical-align: middle;
        border-bottom: 2px solid #e5e7eb !important;
        white-space: nowrap;
    }

    .table thead th input.form-control-sm, .data-table thead th input.form-control-sm {
        height: 28px;
        font-size: 11px;
        padding: 4px 8px;
        margin-top: 5px;
        background-color: rgba(255, 255, 255, 0.9);
        border: 1px solid #d1d5db;
        color: #333;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }

    .data-table th {
        background: #214589;
        color: white;
        font-weight: 600;
        padding: 16px 12px;
        text-align: left;
        border-bottom: 2px solid #1e3a8a;
        white-space: nowrap;
    }

    .data-table td {
        padding: 16px 12px;
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

    /* Modal Styles */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        backdrop-filter: blur(2px);
    }
    
    .modal-overlay.show {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    
    .modal-container {
        background: white;
        border-radius: 12px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        max-width: 90vw;
        max-height: 90vh;
        width: 800px;
        overflow: hidden;
        position: relative;
        display: flex;
        flex-direction: column;
        animation: modalSlideIn 0.3s ease-out;
    }
    
    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: scale(0.95) translateY(-20px);
        }
        to {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }
    
    .modal-header {
        padding: 20px 24px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        flex-shrink: 0;
    }
    
    .modal-title {
        font-size: 18px;
        font-weight: 600;
        color: white;
        margin: 0;
    }
    
    .modal-close {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        font-size: 18px;
        color: white;
        cursor: pointer;
        padding: 8px;
        border-radius: 6px;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
    }
    
    .modal-close:hover {
        background: rgba(255, 255, 255, 0.3);
    }
    
    .modal-body {
        padding: 24px;
        flex: 1;
        overflow-y: auto;
        background: #f8fafc;
    }
    
    .modal-footer {
        padding: 16px 24px;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        background: white;
        flex-shrink: 0;
    }
</style>

<div class="page-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title">Achievement Period Management</h1>
                <p class="page-subtitle">Manage achievement periods for tracking</p>
            </div>
            <div>
                <button onclick="openCreateModal()" class="btn btn-primary">
                    <i class="fas fa-plus"></i> New Period
                </button>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-header">
                <div>
                    <p class="stat-value">{{ $achievementPeriods->total() }}</p>
                    <p class="stat-label">Total Periods</p>
                </div>
                <div class="stat-icon primary">
                    <i class="fas fa-calendar-alt"></i>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div>
                    <p class="stat-value">{{ $achievementPeriods->where('status', 'active')->count() }}</p>
                    <p class="stat-label">Active Periods</p>
                </div>
                <div class="stat-icon success">
                    <i class="fas fa-play-circle"></i>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div>
                    <p class="stat-value">{{ $achievementPeriods->where('status', 'completed')->count() }}</p>
                    <p class="stat-label">Completed</p>
                </div>
                <div class="stat-icon warning">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div>
                    <p class="stat-value">{{ $achievementPeriods->where('status', 'inactive')->count() }}</p>
                    <p class="stat-label">Inactive</p>
                </div>
                <div class="stat-icon info">
                    <i class="fas fa-pause-circle"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Section (Dropdowns) -->
    <div class="filter-section">
        <div class="filter-header">
            <h3 class="filter-title">
                <i class="fas fa-filter"></i>
                Filter Periods
            </h3>
        </div>
        <form method="GET" action="{{ route('finance.achievement-periods.index') }}">
            <div class="filter-grid">
                <div class="form-group">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-control">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" value="{{ request('start_date') }}">
                </div>
                <div class="form-group">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" value="{{ request('end_date') }}">
                </div>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Apply Filter
                </button>
                <a href="{{ route('finance.achievement-periods.index') }}" class="btn btn-secondary">
                    <i class="fas fa-sync-alt"></i> Reset Filter
                </a>
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <div class="table-container">
        <div class="table-header">
            <h3 class="table-title">
                <i class="fas fa-calendar-alt"></i>
                Achievement Period Records
            </h3>
            <div class="table-actions">
                <span class="text-sm text-gray-600">
                    Showing {{ $achievementPeriods->firstItem() ?? 0 }} to {{ $achievementPeriods->lastItem() ?? 0 }} 
                    of {{ $achievementPeriods->total() }} entries
                </span>
            </div>
        </div>

        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th data-column="id">ID</th>
                        <th data-column="period_name">Period Name</th>
                        <th data-column="start_date" data-type="date">Start Date</th>
                        <th data-column="end_date" data-type="date">End Date</th>
                        <th data-column="duration">Duration</th>
                        <th data-column="status">Status</th>
                        <th data-column="creator.name">Created By</th>
                        <th data-column="created_at" data-type="date">Created At</th>
                        <th data-column="updater.name">Last Updated By</th>
                        <th data-column="updated_at" data-type="date">Last Updated At</th>
                        <th data-no-filter>Actions</th>
                    </tr>
                    <tr class="filter-row" style="background-color: #f8fafc;">
                        <th class="p-2 text-center">
                            <button onclick="resetFilters()" class="btn btn-sm btn-secondary w-full" style="padding: 2px 5px; font-size: 10px;">
                                <i class="fas fa-undo"></i>
                            </button>
                        </th>
                        <th class="p-2"><input type="text" class="form-control form-control-sm table-filter" name="filter[period_name]" placeholder="Search..." value="{{ request('filter.period_name') }}"></th>
                        <th class="p-2"><input type="date" class="form-control form-control-sm table-filter" name="filter[start_date]" value="{{ request('filter.start_date') }}"></th>
                        <th class="p-2"><input type="date" class="form-control form-control-sm table-filter" name="filter[end_date]" value="{{ request('filter.end_date') }}"></th>
                        <th></th>
                        <th class="p-2">
                            <select class="form-control form-control-sm table-filter" name="filter[status]">
                                <option value="">Semua</option>
                                <option value="active" {{ request('filter.status') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ request('filter.status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                <option value="completed" {{ request('filter.status') == 'completed' ? 'selected' : '' }}>Completed</option>
                            </select>
                        </th>
                        <th class="p-2"><input type="text" class="form-control form-control-sm table-filter" name="filter[createdBy__name]" placeholder="Search..." value="{{ request('filter.createdBy__name') }}"></th>
                        <th class="p-2"><input type="date" class="form-control form-control-sm table-filter" name="filter[created_at]" value="{{ request('filter.created_at') }}"></th>
                        <th class="p-2"><input type="text" class="form-control form-control-sm table-filter" name="filter[updatedBy__name]" placeholder="Search..." value="{{ request('filter.updatedBy__name') }}"></th>
                        <th class="p-2"><input type="date" class="form-control form-control-sm table-filter" name="filter[updated_at]" value="{{ request('filter.updated_at') }}"></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($achievementPeriods as $period)
                    <tr onclick="openViewModal({{ $period->id }})" style="cursor: pointer;">
                        <td>{{ $period->id }}</td>
                        <td>
                            <strong>{{ $period->period_name }}</strong>
                            @if($period->description)
                                <br><small class="text-muted">{{ Str::limit($period->description, 50) }}</small>
                            @endif
                        </td>
                        <td>{{ $period->start_date->format('d M Y') }}</td>
                        <td>{{ $period->end_date->format('d M Y') }}</td>
                        <td>{{ $period->duration }} days</td>
                        <td>
                            <span class="badge {{ $period->status_badge }}">
                                {{ $period->status }}
                            </span>
                        </td>
                        <td>{{ $period->createdBy->name ?? 'Unknown' }}</td>
                        <td>
                            @if($period->created_at)
                                {{ $period->created_at->format('d M Y') }}<br>
                                <small class="text-muted">{{ $period->created_at->format('H.i') }} WIB</small>
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $period->updatedBy->name ?? 'Unknown' }}</td>
                        <td>
                            @if($period->updated_at)
                                {{ $period->updated_at->format('d M Y') }}<br>
                                <small class="text-muted">{{ $period->updated_at->format('H.i') }} WIB</small>
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            <div class="btn-group" role="group" onclick="event.stopPropagation();">
                                <button onclick="openViewModal({{ $period->id }})" class="btn btn-sm btn-info" title="View">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button onclick="openEditModal({{ $period->id }})" class="btn btn-sm btn-warning" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" action="{{ route('finance.achievement-periods.destroy', $period) }}" class="d-inline" onsubmit="return confirmDeletePeriod(event);">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="pagination-wrapper" style="padding: 20px; display: flex; justify-content: center;">
            {{ $achievementPeriods->links() }}
        </div>
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">View Achievement Period</h2>
            <button class="modal-close" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="modalBody" class="modal-body"></div>
        <div id="modalFooter" class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function applyFilters() {
    const inputs = document.querySelectorAll('.table-filter');
    const url = new URL(window.location.href);
    
    // Clear old filters
    const keys = Array.from(url.searchParams.keys());
    keys.forEach(key => {
        if (key.startsWith('filter[')) url.searchParams.delete(key);
    });

    inputs.forEach(input => {
        if (input.value) url.searchParams.set(input.name, input.value);
    });
    
    url.searchParams.set('page', 1);
    window.location.href = url.toString();
}

function resetFilters() {
    const url = new URL(window.location.href);
    const keys = Array.from(url.searchParams.keys());
    keys.forEach(key => {
        if (key.startsWith('filter[')) url.searchParams.delete(key);
    });
    url.searchParams.set('page', 1);
    window.location.href = url.pathname + url.search;
}

document.addEventListener('DOMContentLoaded', function() {
    const filterInputs = document.querySelectorAll('.table-filter');
    filterInputs.forEach(input => {
        input.addEventListener('keypress', e => { if (e.key === 'Enter') applyFilters(); });
        if (input.tagName === 'SELECT') input.addEventListener('change', applyFilters);
    });

    const urlParams = new URL(window.location.search);
    filterInputs.forEach(input => { 
        if (urlParams.has(input.name)) {
            input.value = urlParams.get(input.name);
        }
    });
});

function openViewModal(periodId) {
    fetch(`/finance/achievement-periods/${periodId}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        const period = data.period;
        document.getElementById('modalTitle').textContent = 'View Achievement Period';
        document.getElementById('modalBody').innerHTML = `
            <div class="space-y-4" style="display: flex; flex-direction: column; gap: 15px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <label style="font-weight: 500; font-size: 13px; color: #666;">Period Name</label>
                        <p style="margin: 5px 0 0 0; font-size: 14px;">${period.period_name || 'N/A'}</p>
                    </div>
                    <div>
                        <label style="font-weight: 500; font-size: 13px; color: #666;">Status</label>
                        <p style="margin: 5px 0 0 0;"><span class="badge badge-info">${period.status || 'N/A'}</span></p>
                    </div>
                </div>
                <div>
                    <label style="font-weight: 500; font-size: 13px; color: #666;">Description</label>
                    <p style="margin: 5px 0 0 0; font-size: 14px;">${period.description || '-'}</p>
                </div>
            </div>
        `;
        document.getElementById('modalOverlay').classList.add('show');
    });
}

function closeModal() {
    document.getElementById('modalOverlay').classList.remove('show');
}

function confirmDeletePeriod(event) {
    if (!confirm('Are you sure you want to delete this period?')) {
        event.preventDefault();
        return false;
    }
    return true;
}
</script>
@endpush
