@extends('layouts.app')

@section('title', 'Master Corporate')

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

    .btn-info {
        background-color: #0ea5e9;
        color: white;
    }

    .btn-warning {
        background-color: #f59e0b;
        color: white;
    }

    .btn-danger {
        background-color: #dc2626;
        color: white;
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

    /* Stats Cards */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 16px;
        margin-bottom: 20px;
    }

    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }

    .stat-icon.secondary { background: #f1f5f9; color: #64748b; }
    .stat-icon.warning { background: #fef3c7; color: #f59e0b; }
    .stat-icon.success { background: #dcfce7; color: #16a34a; }
    .stat-icon.info { background: #dbeafe; color: #0ea5e9; }
    .stat-icon.primary { background: #e0e7ff; color: #4f46e5; }

    .stat-content {
        flex: 1;
    }

    .stat-label {
        font-size: 13px;
        color: #64748b;
        font-weight: 500;
    }

    .stat-value {
        font-size: 28px;
        font-weight: 700;
        color: #1e293b;
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
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
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

    .table-wrapper {
        overflow-x: auto;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
    }

    .data-table thead {
        background: #f8fafc;
    }

    .data-table th {
        padding: 12px 16px;
        text-align: left;
        font-size: 13px;
        font-weight: 600;
        color: #475569;
        border-bottom: 2px solid #e2e8f0;
        white-space: nowrap;
    }

    .data-table td {
        padding: 12px 16px;
        font-size: 14px;
        color: #334155;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }

    .data-table tbody tr {
        transition: background-color 0.2s ease;
    }

    .data-table tbody tr:hover {
        background-color: #f8fafc;
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

    .badge-info {
        background-color: #dbeafe;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #64748b;
    }

    .empty-state i {
        font-size: 64px;
        margin-bottom: 20px;
        color: #cbd5e1;
    }

    .empty-state h3 {
        font-size: 20px;
        font-weight: 600;
        color: #334155;
        margin-bottom: 8px;
    }

    .empty-state p {
        font-size: 14px;
        color: #64748b;
    }

    .text-muted {
        color: #6b7280;
        font-size: 13px;
    }
</style>

<div class="container-fluid">
    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon info">
                <i class="fas fa-building"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Total Corporate Prices</div>
                <div class="stat-value">{{ number_format($stats['total_items'] ?? 0) }}</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Customers with Corporate Pricing</div>
                <div class="stat-value">{{ number_format($stats['total_customers'] ?? 0) }}</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon success">
                <i class="fas fa-box-open"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Rentals with Special Pricing</div>
                <div class="stat-value">{{ number_format($stats['total_rentals'] ?? 0) }}</div>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <div class="filter-title">
            <i class="fas fa-filter"></i>
            Filter Master Corporate
        </div>
        <form action="{{ route('marketing.master-corporates.index') }}" method="GET">
            <div class="filter-grid">
                <div class="form-group">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Search code, customer name..." value="{{ request('search') }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Customer</label>
                    <select name="customer_id" class="form-control">
                        <option value="">All Customers</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                                {{ $customer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="waiting_approval" {{ request('status') == 'waiting_approval' ? 'selected' : '' }}>Waiting Approval</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Apply Filter
                </button>
                <a href="{{ route('marketing.master-corporates.index') }}" class="btn btn-secondary">
                    <i class="fas fa-sync-alt"></i> Reset Filter
                </a>
                <a href="{{ route('marketing.master-corporates.create') }}" class="btn btn-success">
                    <i class="fas fa-plus"></i> Add New Data
                </a>
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <div class="table-container">
        <div class="table-header">
            <h3 class="table-title">
                <i class="fas fa-list"></i>
                List Master Corporate
            </h3>
            <div class="table-actions d-flex align-items-center gap-3">
                 <form id="bulkDeleteForm" action="{{ route('marketing.master-corporates.destroy-group', 'bulk') }}" method="POST" style="display: none;">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="codes" id="bulkDeleteCodes">
                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete selected submissions?')">
                        <i class="fas fa-trash me-2"></i>Delete Selected
                    </button>
                </form>

                <span class="text-sm text-gray-600">
                    Showing {{ $masterCorporates->firstItem() ?? 0 }} to {{ $masterCorporates->lastItem() ?? 0 }} 
                    of {{ $masterCorporates->total() }} entries
                </span>
            </div>
        </div>

        <div class="table-wrapper">
            @if($masterCorporates->count() > 0)
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="40" class="text-center">
                                <input type="checkbox" id="selectAll" class="form-check-input" style="cursor: pointer;">
                            </th>
                            <th>Code</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Status Summary</th>
                            <th>Created</th>
                            <th>Created By</th>
                            <th>Updated</th>
                            <th>Updated By</th>
                            <th>Approved</th>
                            <th>Approved By</th>
                        </tr>
                    </thead>
                    <tbody>
                            @foreach($masterCorporates as $group)
                                @php
                                    // Logic to get latest info from the GROUP
                                    // Since we are grouping by Code, we need to inspect the items to find latest update/approve info
                                    $latestItem = $group->items->sortByDesc('updated_at')->first();
                                    $approvedItem = $group->items->whereNotNull('approved_at')->sortByDesc('approved_at')->first();
                                    $creator = $group->createdBy; // Grouped by created_by so this is consistent
                                @endphp
                            <tr style="cursor: pointer;" onclick="window.location='{{ route('marketing.master-corporates.show', $group->id) }}'">
                                <td class="text-center" onclick="event.stopPropagation()">
                                    @if($group->approved_count == 0 && $group->total_items > 0)
                                        <input type="checkbox" value="{{ $group->code }}" class="form-check-input group-checkbox" style="cursor: pointer;">
                                    @else
                                        <input type="checkbox" disabled class="form-check-input" style="opacity: 0.5;">
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $group->code }}</strong>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div style="width: 30px; height: 30px; background: #e0e7ff; color: #4f46e5; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 10px; font-weight: bold;">
                                            {{ substr($group->customer->name ?? '?', 0, 1) }}
                                        </div>
                                        <div>
                                            {{ $group->customer->name ?? '-' }}
                                            <div class="small text-muted">{{ $group->customer->code ?? '' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-info">{{ $group->total_items }} Items</span>
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        @if($group->approved_count > 0)
                                            <span class="badge badge-success">{{ $group->approved_count }} Approved</span>
                                        @endif
                                        @if($group->rejected_count > 0)
                                            <span class="badge badge-danger">{{ $group->rejected_count }} Rejected</span>
                                        @endif
                                        @if($group->waiting_count > 0)
                                            <span class="badge badge-info">{{ $group->waiting_count }} Waiting</span>
                                        @endif
                                        @if($group->draft_count > 0)
                                            <span class="badge badge-secondary">{{ $group->draft_count }} Draft</span>
                                        @endif
                                    </div>
                                </td>
                                
                                {{-- Created At --}}
                                <td>
                                    <div class="small">
                                        {{ \Carbon\Carbon::parse($group->created_at)->format('d/m/y H:i') }}
                                    </div>
                                </td>
                                
                                {{-- Created By --}}
                                <td>
                                    <div class="small">
                                        {{ $creator->name ?? '-' }}
                                    </div>
                                </td>
                                
                                {{-- Updated At --}}
                                <td>
                                    <div class="small">
                                        {{ $latestItem ? \Carbon\Carbon::parse($latestItem->updated_at)->format('d/m/y H:i') : '-' }}
                                    </div>
                                </td>

                                {{-- Updated By --}}
                                <td>
                                    <div class="small">
                                        {{ $latestItem && $latestItem->updatedBy ? $latestItem->updatedBy->name : '-' }}
                                    </div>
                                </td>

                                {{-- Approved At --}}
                                <td>
                                    <div class="small">
                                        {{ $approvedItem ? \Carbon\Carbon::parse($approvedItem->approved_at)->format('d/m/y H:i') : '-' }}
                                    </div>
                                </td>

                                {{-- Approved By --}}
                                <td>
                                    <div class="small">
                                        {{ $approvedItem && $approvedItem->approvedBy ? $approvedItem->approvedBy->name : '-' }}
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <h3>No Master Corporate Data Found</h3>
                    <p>No corporate pricing data matches your search criteria.</p>
                    <p><a href="{{ route('marketing.master-corporates.create') }}">Create the first corporate price</a></p>
                </div>
            @endif
        </div>

        @if($masterCorporates->hasPages())
            <div class="pagination-wrapper" style="padding: 16px;">
                {{ $masterCorporates->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.group-checkbox');
        const bulkDeleteForm = document.getElementById('bulkDeleteForm');
        const bulkDeleteCodes = document.getElementById('bulkDeleteCodes');

        function updateBulkDeleteUI() {
            const checked = document.querySelectorAll('.group-checkbox:checked');
            if (checked.length > 0) {
                bulkDeleteForm.style.display = 'block';
                const codes = Array.from(checked).map(cb => cb.value).join(',');
                bulkDeleteCodes.value = codes;
            } else {
                bulkDeleteForm.style.display = 'none';
            }
        }

        if(selectAll) {
            selectAll.addEventListener('change', function() {
                checkboxes.forEach(cb => cb.checked = selectAll.checked);
                updateBulkDeleteUI();
            });
        }

        checkboxes.forEach(cb => {
            cb.addEventListener('change', updateBulkDeleteUI);
        });
    });
</script>
@endsection
