@extends('layouts.app')

@section('title', 'Commission Management')
@section('breadcrumb', 'Home / Finance / Commission Management')

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

    .table thead th {
        background-color: white !important;
        color: #214589 !important;
        font-weight: 600;
        padding: 12px 8px;
        vertical-align: middle;
        border-bottom: 2px solid #e5e7eb !important;
        white-space: nowrap;
    }

    .table thead th input.form-control-sm {
        height: 28px;
        font-size: 11px;
        padding: 4px 8px;
        margin-top: 5px;
        background-color: rgba(255, 255, 255, 0.9);
        border: 1px solid #d1d5db;
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
    
    .modal-body .form-control {
        border: 1px solid #d1d5db;
        border-radius: 6px;
        padding: 8px 12px;
        font-size: 14px;
        transition: border-color 0.2s ease;
        width: 100%;
    }
    
    .modal-body .form-control:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        outline: none;
    }
    
    .modal-body .grid {
        display: grid;
        gap: 16px;
    }
    
    .modal-body .grid-cols-2 {
        grid-template-columns: 1fr 1fr;
    }
    
    .modal-body .space-y-4 > * + * {
        margin-top: 16px;
    }
    
    .modal-body label {
        display: block;
        margin-bottom: 4px;
        font-weight: 500;
        color: #374151;
    }
    
    .modal-body .text-sm {
        font-size: 14px;
    }
    
    .modal-body .font-medium {
        font-weight: 500;
    }
    
    .modal-body .text-gray-600 {
        color: #6b7280;
    }
    
    .modal-body .text-gray-900 {
        color: #111827;
    }

    /* Delete Confirmation Modal */
    .delete-modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        z-index: 2000;
        align-items: center;
        justify-content: center;
    }

    .delete-modal-overlay.show {
        display: flex;
    }

    .delete-modal-container {
        background: #f0f9ff;
        border-radius: 16px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        max-width: 90vw;
        width: 500px;
        overflow: hidden;
        position: relative;
        padding: 40px 30px 30px;
        text-align: center;
    }

    .delete-icon-container {
        margin-bottom: 20px;
    }

    .delete-icon {
        width: 48px;
        height: 48px;
        color: #dc2626;
        margin: 0 auto;
    }

    .delete-modal-title {
        font-size: 20px;
        font-weight: 600;
        color: #1f2937;
        margin: 0 0 12px 0;
    }

    .delete-modal-description {
        color: #6b7280;
        margin: 0 0 24px 0;
        line-height: 1.5;
    }

    .delete-modal-buttons {
        display: flex;
        gap: 12px;
        justify-content: center;
    }

    .btn-cancel {
        background: #f3f4f6;
        color: #374151;
        border: 1px solid #d1d5db;
        padding: 8px 16px;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-cancel:hover {
        background: #e5e7eb;
    }

    .btn-hide {
        background: #dc2626;
        color: white;
        border: 1px solid #dc2626;
        padding: 8px 16px;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-hide:hover {
        background: #b91c1c;
        border-color: #b91c1c;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .filter-grid {
            grid-template-columns: 1fr;
        }
        
        .filter-actions {
            flex-direction: column;
        }
        
        .table-header {
            flex-direction: column;
            gap: 15px;
            align-items: flex-start;
        }
    }
</style>

<div class="page-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title">Commission Management</h1>
                <p class="page-subtitle">Manage commission calculations and payments</p>
                <div class="mt-2 flex gap-2">
                    <a href="{{ route('finance.tax-invoices.index') }}" class="btn btn-outline btn-sm" style="border: 1px solid white; color: white;">
                        <i class="fas fa-receipt"></i>
                        <span>Tax Invoices</span>
                    </a>
                    <a href="{{ route('finance.tax-reports.index') }}" class="btn btn-outline btn-sm" style="border: 1px solid white; color: white;">
                        <i class="fas fa-chart-line"></i>
                        <span>Tax Reports</span>
                    </a>
                    <a href="{{ route('finance.e-materai-transactions.index') }}" class="btn btn-outline btn-sm" style="border: 1px solid white; color: white;">
                        <i class="fas fa-stamp"></i>
                        <span>e-Materai</span>
                    </a>
                </div>
            </div>
            <div>
                <button onclick="openCreateModal()" class="btn btn-primary">
                    <i class="fas fa-plus"></i> New Commission
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
                    <p class="stat-value" id="totalCommissions">-</p>
                    <p class="stat-label">Total Commissions</p>
                </div>
                <div class="stat-icon primary">
                    <i class="fas fa-calculator"></i>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div>
                    <p class="stat-value" id="totalAmount">-</p>
                    <p class="stat-label">Total Amount</p>
                </div>
                <div class="stat-icon success">
                    <i class="fas fa-dollar-sign"></i>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div>
                    <p class="stat-value" id="pendingApproval">-</p>
                    <p class="stat-label">Pending Approval</p>
                </div>
                <div class="stat-icon warning">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div>
                    <p class="stat-value" id="paid">-</p>
                    <p class="stat-label">Paid</p>
                </div>
                <div class="stat-icon info">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Section (Dropdowns) -->
    <div class="filter-section">
        <div class="filter-header">
            <h3 class="filter-title">
                <i class="fas fa-filter"></i>
                Filter Commissions
            </h3>
        </div>
        <form method="GET" action="{{ route('finance.commissions.index') }}">
            <div class="filter-grid">
                <div class="form-group">
                    <label for="user_id" class="form-label">User</label>
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
                    <label for="period_id" class="form-label">Period</label>
                    <select name="period_id" id="period_id" class="form-control">
                        <option value="">All Periods</option>
                        @foreach($periods as $period)
                            <option value="{{ $period->id }}" {{ request('period_id') == $period->id ? 'selected' : '' }}>
                                {{ $period->period_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-control">
                        <option value="">All Status</option>
                        <option value="calculated" {{ request('status') == 'calculated' ? 'selected' : '' }}>Calculated</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
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
                <a href="{{ route('finance.commissions.index') }}" class="btn btn-secondary">
                    <i class="fas fa-sync-alt"></i> Reset Filter
                </a>
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <div class="table-container">
        <div class="table-header">
            <h3 class="table-title">
                <i class="fas fa-calculator"></i>
                Commission Calculations
            </h3>
            <div class="table-actions">
                <span class="text-sm text-gray-600">
                    Showing {{ $commissions->firstItem() ?? 0 }} to {{ $commissions->lastItem() ?? 0 }} 
                    of {{ $commissions->total() }} entries
                </span>
            </div>
        </div>

        <div class="table-wrapper">
            <table class="table table-bordered" id="commissionsTable">
                <thead>
                    <tr class="filter-row" style="background-color: #f8fafc;">
                        <th class="p-2 text-center">
                            <button onclick="resetFilters()" class="btn btn-sm btn-secondary w-full" style="padding: 2px 5px; font-size: 10px;">
                                <i class="fas fa-undo"></i>
                            </button>
                        </th>
                        <th class="p-2"><input type="text" class="form-control form-control-sm table-filter" name="filter[user__name]" placeholder="Search..." value="{{ request('filter.user__name') }}"></th>
                        <th class="p-2"><input type="text" class="form-control form-control-sm table-filter" name="filter[achievementPeriod__period_name]" placeholder="Search..." value="{{ request('filter.achievementPeriod__period_name') }}"></th>
                        <th class="p-2"><input type="text" class="form-control form-control-sm table-filter" name="filter[calculation_type]" placeholder="Search..." value="{{ request('filter.calculation_type') }}"></th>
                        <th class="p-2"><input type="text" class="form-control form-control-sm table-filter" name="filter[base_amount]" placeholder="Filter..." value="{{ request('filter.base_amount') }}"></th>
                        <th class="p-2"><input type="text" class="form-control form-control-sm table-filter" name="filter[commission_rate]" placeholder="Filter..." value="{{ request('filter.commission_rate') }}"></th>
                        <th class="p-2"><input type="text" class="form-control form-control-sm table-filter" name="filter[final_amount]" placeholder="Filter..." value="{{ request('filter.final_amount') }}"></th>
                        <th class="p-2">
                             <select class="form-control form-control-sm table-filter" name="filter[status]">
                                <option value="">Semua</option>
                                <option value="calculated" {{ request('filter.status') == 'calculated' ? 'selected' : '' }}>Calculated</option>
                                <option value="approved" {{ request('filter.status') == 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="paid" {{ request('filter.status') == 'paid' ? 'selected' : '' }}>Paid</option>
                                <option value="cancelled" {{ request('filter.status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </th>
                        <th class="p-2"><input type="date" class="form-control form-control-sm table-filter" name="filter[calculation_date]" value="{{ request('filter.calculation_date') }}"></th>
                        <th class="p-2"><input type="text" class="form-control form-control-sm table-filter" name="filter[createdBy__name]" placeholder="Search..." value="{{ request('filter.createdBy__name') }}"></th>
                        <th class="p-2"><input type="date" class="form-control form-control-sm table-filter" name="filter[created_at]" value="{{ request('filter.created_at') }}"></th>
                        <th class="p-2"><input type="text" class="form-control form-control-sm table-filter" name="filter[updatedBy__name]" placeholder="Search..." value="{{ request('filter.updatedBy__name') }}"></th>
                        <th class="p-2"><input type="date" class="form-control form-control-sm table-filter" name="filter[updated_at]" value="{{ request('filter.updated_at') }}"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($commissions as $commission)
                        <tr onclick="openViewModal({{ $commission->id }})" style="cursor: pointer;">
                            <td>{{ $commission->user->name ?? 'N/A' }}</td>
                            <td>{{ $commission->achievementPeriod->period_name ?? 'N/A' }}</td>
                            <td>
                                <span class="badge badge-info">{{ ucfirst($commission->calculation_type) }}</span>
                            </td>
                            <td>Rp {{ number_format($commission->base_amount, 0, ',', '.') }}</td>
                            <td>{{ $commission->commission_rate }}%</td>
                            <td class="font-weight-bold">Rp {{ number_format($commission->final_amount, 0, ',', '.') }}</td>
                            <td>
                                <span class="badge {{ $commission->status_badge }}">
                                    {{ $commission->status_label }}
                                </span>
                            </td>
                            <td>{{ $commission->calculation_date ? $commission->calculation_date->format('d/M/Y') : '-' }}</td>
                            <td>{{ $commission->createdBy->name ?? '-' }}</td>
                            <td>
                                @if($commission->created_at)
                                    {{ $commission->created_at->format('d/M/Y') }}<br>
                                    <small class="text-muted">{{ $commission->created_at->format('H.i') }} WIB</small>
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $commission->updatedBy->name ?? '-' }}</td>
                            <td>
                                @if($commission->updated_at)
                                    {{ $commission->updated_at->format('d/M/Y') }}<br>
                                    <small class="text-muted">{{ $commission->updated_at->format('H.i') }} WIB</small>
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                <div class="btn-group" role="group" onclick="event.stopPropagation();">
                                    @if($commission->status === 'calculated')
                                        <form method="POST" action="{{ route('finance.commissions.approve', $commission) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success" title="Approve">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                    @endif
                                    
                                    @if($commission->status === 'approved')
                                        <form method="POST" action="{{ route('finance.commissions.mark-paid', $commission) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-primary" title="Mark as Paid">
                                                <i class="fas fa-money-bill"></i>
                                            </button>
                                        </form>
                                    @endif
                                    
                                    <button type="button" class="btn btn-sm btn-danger" onclick="openDeleteModal({{ $commission->id }}, '{{ $commission->user->name ?? 'N/A' }}')" title="Hide">
                                        <i class="fas fa-eye-slash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="text-center py-5">
                                <i class="fas fa-calculator fa-3x text-gray-300 mb-3"></i>
                                <h4 class="text-gray-500">No commission calculations found</h4>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination-wrapper" style="padding: 20px; display: flex; justify-content: center;">
            {{ $commissions->links() }}
        </div>
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">View Commission</h2>
            <button class="modal-close" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="modalBody" class="modal-body">
            <!-- Modal content will be loaded here -->
        </div>
        <div id="modalFooter" class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModalOverlay" class="delete-modal-overlay" onclick="closeDeleteModal()">
    <div class="delete-modal-container" onclick="event.stopPropagation()">
        <div class="delete-icon-container">
            <svg class="delete-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 19.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
        </div>
        <h3 class="delete-modal-title">Hide Commission</h3>
        <p class="delete-modal-description" id="deleteMessage">Are you sure you want to hide this commission? This action can be undone later.</p>
        <div class="delete-modal-buttons">
            <button class="btn btn-cancel" onclick="closeDeleteModal()">Cancel</button>
            <button class="btn btn-hide" onclick="confirmDelete()">Yes, Hide</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function applyFilters() {
    const inputs = document.querySelectorAll('.table-filter, #user_id, #period_id, #status, #start_date, #end_date');
    const url = new URL(window.location.href);
    
    // Clear old filters
    const keys = Array.from(url.searchParams.keys());
    keys.forEach(key => {
        if (key.startsWith('filter[')) url.searchParams.delete(key);
    });

    inputs.forEach(input => {
        if (input.value) {
            let name = input.name;
            if (!name && input.dataset.column) {
                name = 'filter[' + input.dataset.column.replace(/\./g, '__') + ']';
            }
            if (name) url.searchParams.set(name, input.value);
        }
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
    url.searchParams.delete('user_id');
    url.searchParams.delete('period_id');
    url.searchParams.delete('status');
    url.searchParams.delete('start_date');
    url.searchParams.delete('end_date');
    url.searchParams.set('page', 1);
    window.location.href = url.pathname + url.search;
}

$(document).ready(function() {
    loadStatistics();
    setInterval(loadStatistics, 30000);

    const filterInputs = document.querySelectorAll('.table-filter, #user_id, #period_id, #status, #start_date, #end_date');
    filterInputs.forEach(input => {
        input.addEventListener('keypress', e => { if (e.key === 'Enter') applyFilters(); });
        if (input.tagName === 'SELECT') input.addEventListener('change', applyFilters);
    });

    const urlParams = new URL(window.location.search);
    filterInputs.forEach(input => { 
        let name = input.name;
        if (!name && input.dataset.column) {
            name = 'filter[' + input.dataset.column.replace(/\./g, '__') + ']';
        }
        if (name && urlParams.has(name)) {
            input.value = urlParams.get(name);
        }
    });
});

function loadStatistics() {
    $.ajax({
        url: '{{ route("finance.commissions.statistics") }}',
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            $('#totalCommissions').text(data.total_commissions || 0);
            $('#totalAmount').text('Rp ' + (data.total_amount || 0).toLocaleString('id-ID'));
            $('#pendingApproval').text(data.pending_approval || 0);
            $('#paid').text(data.paid || 0);
        }
    });
}

function openViewModal(commissionId) {
    fetch(`/finance/commissions/${commissionId}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        const commission = data.commission;
        document.getElementById('modalTitle').textContent = 'View Commission Details';
        document.getElementById('modalBody').innerHTML = `
            <div class="space-y-4" style="display: flex; flex-direction: column; gap: 15px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <label style="font-weight: 500; font-size: 13px; color: #666;">User</label>
                        <p style="margin: 5px 0 0 0; font-size: 14px;">${commission.user ? commission.user.name : 'N/A'}</p>
                    </div>
                    <div>
                        <label style="font-weight: 500; font-size: 13px; color: #666;">Status</label>
                        <p style="margin: 5px 0 0 0;"><span class="badge badge-info">${commission.status || 'N/A'}</span></p>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <label style="font-weight: 500; font-size: 13px; color: #666;">Final Amount</label>
                        <p style="margin: 5px 0 0 0; font-weight: bold; font-size: 16px;">Rp ${commission.final_amount ? commission.final_amount.toLocaleString('id-ID') : '0'}</p>
                    </div>
                    <div>
                        <label style="font-weight: 500; font-size: 13px; color: #666;">Date</label>
                        <p style="margin: 5px 0 0 0; font-size: 14px;">${commission.calculation_date ? commission.calculation_date.split('T')[0] : 'N/A'}</p>
                    </div>
                </div>
            </div>
        `;
        document.getElementById('modalOverlay').classList.add('show');
    });
}

function closeModal() {
    document.getElementById('modalOverlay').classList.remove('show');
}

function openDeleteModal(commissionId, userName) {
    document.getElementById('deleteMessage').textContent = `Are you sure you want to hide commission for "${userName}"?`;
    document.getElementById('deleteModalOverlay').classList.add('show');
    window.currentDeleteId = commissionId;
}

function closeDeleteModal() {
    document.getElementById('deleteModalOverlay').classList.remove('show');
}

function confirmDelete() {
    if (window.currentDeleteId) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/finance/commissions/${window.currentDeleteId}`;
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const methodField = document.createElement('input');
        methodField.type = 'hidden';
        methodField.name = '_method';
        methodField.value = 'DELETE';
        form.appendChild(csrfToken);
        form.appendChild(methodField);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endpush
