@extends('layouts.app')

@section('title', 'Commission Payment Management')
@section('breadcrumb', 'Home / Finance / Commission Payment Management')

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
        font-size: 24px;
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

    .table-wrapper {
        overflow-x: auto;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }

    .data-table thead th {
        background-color: #214589 !important;
        color: white !important;
        font-weight: 600;
        padding: 12px 8px;
        vertical-align: middle;
        border-bottom: 2px solid #1e3a8a;
        white-space: nowrap;
    }

    .data-table thead th input.form-control-sm {
        height: 28px;
        font-size: 11px;
        padding: 4px 8px;
        margin-top: 5px;
        background-color: rgba(255, 255, 255, 0.9);
        border: 1px solid #d1d5db;
        color: #333;
    }

    .data-table td {
        padding: 16px 12px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
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

    .badge-success { background-color: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .badge-warning { background-color: #fef3c7; color: #d97706; border: 1px solid #fde68a; }
    .badge-danger { background-color: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
    .badge-info { background-color: #dbeafe; color: #1d4ed8; border: 1px solid #bfdbfe; }
    .badge-secondary { background-color: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }

    /* Modal Styles */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        backdrop-filter: blur(2px);
    }
    .modal-overlay.show { display: flex; align-items: center; justify-content: center; }
    .modal-container {
        background: white; border-radius: 12px; width: 800px; max-width: 90vw; max-height: 90vh;
        overflow: hidden; display: flex; flex-direction: column;
    }
    .modal-header { padding: 20px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; display: flex; justify-content: space-between; align-items: center; }
    .modal-body { padding: 24px; flex: 1; overflow-y: auto; background: #f8fafc; }
    .modal-footer { padding: 16px 24px; border-top: 1px solid #e5e7eb; display: flex; justify-content: flex-end; gap: 12px; }
</style>

<div class="page-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title">Commission Payment Management</h1>
                <p class="page-subtitle">Manage commission payments and processing</p>
            </div>
            <div>
                <button onclick="openCreateModal()" class="btn btn-primary">
                    <i class="fas fa-plus"></i> New Payment
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
                <div><p class="stat-value">{{ $payments->total() }}</p><p class="stat-label">Total Payments</p></div>
                <div class="stat-icon primary"><i class="fas fa-credit-card"></i></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <div><p class="stat-value">{{ $payments->where('status', 'pending')->count() }}</p><p class="stat-label">Pending</p></div>
                <div class="stat-icon warning"><i class="fas fa-clock"></i></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <div><p class="stat-value">{{ $payments->where('status', 'completed')->count() }}</p><p class="stat-label">Completed</p></div>
                <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <div><p class="stat-value">Rp {{ number_format($payments->sum('amount'), 0, ',', '.') }}</p><p class="stat-label">Total Amount</p></div>
                <div class="stat-icon info"><i class="fas fa-dollar-sign"></i></div>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <form method="GET" action="{{ route('finance.commission-payments.index') }}">
            <div class="filter-grid">
                <div class="form-group">
                    <label class="form-label">User</label>
                    <select name="user_id" class="form-control">
                        <option value="">All Users</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="">All Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Processing</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Payment Method</label>
                    <select name="payment_method" class="form-control">
                        <option value="">All Methods</option>
                        <option value="bank_transfer" {{ request('payment_method') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                        <option value="cash" {{ request('payment_method') == 'cash' ? 'selected' : '' }}>Cash</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                </div>
                <div class="form-group">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                </div>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Apply Filter</button>
                <a href="{{ route('finance.commission-payments.index') }}" class="btn btn-secondary">Reset Filter</a>
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <div class="table-container">
        <div class="table-header">
            <h3 class="table-title"><i class="fas fa-credit-card"></i> Commission Payment Records</h3>
        </div>

        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th data-no-filter>ID</th>
                        <th data-column="user__name">User</th>
                        <th data-column="amount">Amount</th>
                        <th data-column="payment_method">Method</th>
                        <th data-column="payment_date" data-type="date">Date</th>
                        <th data-column="status">Status</th>
                        <th data-column="payment_reference">Reference</th>
                        <th data-column="createdBy__name">Created By</th>
                        <th data-column="created_at" data-type="date">Created At</th>
                        <th data-column="updatedBy__name">Updated By</th>
                        <th data-column="updated_at" data-type="date">Updated At</th>
                        <th data-no-filter>Actions</th>
                    </tr>
                    <tr class="filter-row">
                        <th><input type="text" class="form-control form-control-sm table-filter" data-column="id" placeholder="ID" value="{{ request('filter.id') }}"></th>
                        <th><input type="text" class="form-control form-control-sm table-filter" data-column="user__name" placeholder="Search..." value="{{ request('filter.user__name') }}"></th>
                        <th><input type="text" class="form-control form-control-sm table-filter" data-column="amount" placeholder="Filter..." value="{{ request('filter.amount') }}"></th>
                        <th><input type="text" class="form-control form-control-sm table-filter" data-column="payment_method" placeholder="Filter..." value="{{ request('filter.payment_method') }}"></th>
                        <th><input type="date" class="form-control form-control-sm table-filter" data-column="payment_date" value="{{ request('filter.payment_date') }}"></th>
                        <th><input type="text" class="form-control form-control-sm table-filter" data-column="status" placeholder="Filter..." value="{{ request('filter.status') }}"></th>
                        <th><input type="text" class="form-control form-control-sm table-filter" data-column="payment_reference" placeholder="Filter..." value="{{ request('filter.payment_reference') }}"></th>
                        <th><input type="text" class="form-control form-control-sm table-filter" data-column="createdBy__name" placeholder="Search..." value="{{ request('filter.createdBy__name') }}"></th>
                        <th><input type="date" class="form-control form-control-sm table-filter" data-column="created_at" value="{{ request('filter.created_at') }}"></th>
                        <th><input type="text" class="form-control form-control-sm table-filter" data-column="updatedBy__name" placeholder="Search..." value="{{ request('filter.updatedBy__name') }}"></th>
                        <th><input type="date" class="form-control form-control-sm table-filter" data-column="updated_at" value="{{ request('filter.updated_at') }}"></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payments as $payment)
                    <tr onclick="openViewModal({{ $payment->id }})" style="cursor: pointer;">
                        <td>{{ $payment->id }}</td>
                        <td>{{ $payment->user->name ?? 'N/A' }}</td>
                        <td>{{ $payment->formatted_amount }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                        <td>{{ $payment->payment_date->format('d/M/Y') }}</td>
                        <td>
                            <span class="badge {{ $payment->status_badge }}">
                                {{ $payment->status_label }}
                            </span>
                        </td>
                        <td>{{ $payment->payment_reference ?? '-' }}</td>
                        <td>{{ $payment->createdBy->name ?? '-' }}</td>
                        <td>{{ $payment->created_at->format('d/M/Y') }}</td>
                        <td>{{ $payment->updatedBy->name ?? '-' }}</td>
                        <td>{{ $payment->updated_at->format('d/M/Y') }}</td>
                        <td>
                            <div class="btn-group" onclick="event.stopPropagation();">
                                <button onclick="openViewModal({{ $payment->id }})" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></button>
                                <button onclick="openEditModal({{ $payment->id }})" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination-wrapper" style="padding: 20px; display: flex; justify-content: center;">
            {{ $payments->links() }}
        </div>
    </div>
</div>

<!-- Modals -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">Payment Detail</h2>
            <button onclick="closeModal()" style="background: none; border: none; color: white; cursor: pointer;"><i class="fas fa-times"></i></button>
        </div>
        <div id="modalBody" class="modal-body"></div>
        <div class="modal-footer"><button class="btn btn-secondary" onclick="closeModal()">Close</button></div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.table-filter').on('keypress', function(e) {
        if (e.which == 13) {
            applyFilters();
        }
    });

    function applyFilters() {
        let url = new URL(window.location.href);
        let params = new URLSearchParams(url.search);
        for (let key of Array.from(params.keys())) { if (key.startsWith('filter[')) params.delete(key); }
        $('.table-filter').each(function() {
            let column = $(this).data('column');
            let value = $(this).val();
            if (value) params.set('filter[' + column + ']', value);
        });
        params.set('page', 1);
        window.location.href = url.origin + url.pathname + '?' + params.toString();
    }
});

function openViewModal(id) {
    fetch(`/finance/commission-payments/${id}`, { headers: { 'Accept': 'application/json' } })
    .then(r => r.json()).then(data => {
        const p = data.payment;
        document.getElementById('modalBody').innerHTML = `
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div><label>User</label><p>${p.user ? p.user.name : '-'}</p></div>
                <div><label>Amount</label><p>Rp ${p.amount.toLocaleString()}</p></div>
                <div><label>Method</label><p>${p.payment_method}</p></div>
                <div><label>Status</label><p>${p.status}</p></div>
            </div>
        `;
        document.getElementById('modalOverlay').classList.add('show');
    });
}

function closeModal() { document.getElementById('modalOverlay').classList.remove('show'); }
</script>
@endpush
