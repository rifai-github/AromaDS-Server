@extends('layouts.app')

@section('title', 'Achievement Management')
@section('breadcrumb', 'Home / Finance / Achievement Management')

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

    .data-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }

    .data-table th {
        background: #f1f5f9;
        color: #475569;
        font-weight: 600;
        padding: 16px 12px;
        text-align: left;
        border-bottom: 2px solid #e2e8f0;
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
        background: #3b82f6;
        color: white;
        border-color: #3b82f6;
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
    
    /* Form Styles in Modal */
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
        
        .pagination-wrapper {
            flex-direction: column;
            text-align: center;
        }
    }
</style>

<div class="page-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title">Achievement Management</h1>
                <p class="page-subtitle">Manage achievements and performance tracking</p>
                <div class="mt-2 flex gap-2">
                    <a href="{{ route('finance.tax-invoices.index') }}" class="btn btn-outline btn-sm">
                        <i class="fas fa-receipt"></i>
                        <span>Tax Invoices</span>
                    </a>
                    <a href="{{ route('finance.tax-reports.index') }}" class="btn btn-outline btn-sm">
                        <i class="fas fa-chart-line"></i>
                        <span>Tax Reports</span>
                    </a>
                    <a href="{{ route('finance.e-materai-transactions.index') }}" class="btn btn-outline btn-sm">
                        <i class="fas fa-stamp"></i>
                        <span>e-Materai</span>
                    </a>
                </div>
            </div>
            <div>
                <button onclick="openCreateModal()" class="btn btn-primary">
                    <i class="fas fa-plus"></i> New Achievement
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
                    <p class="stat-value" id="totalAchievements">-</p>
                    <p class="stat-label">Total Achievements</p>
                </div>
                <div class="stat-icon primary">
                    <i class="fas fa-trophy"></i>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div>
                    <p class="stat-value" id="achievedCount">-</p>
                    <p class="stat-label">Achieved</p>
                </div>
                <div class="stat-icon success">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div>
                    <p class="stat-value" id="exceededCount">-</p>
                    <p class="stat-label">Exceeded</p>
                </div>
                <div class="stat-icon warning">
                    <i class="fas fa-star"></i>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div>
                    <p class="stat-value" id="totalAmount">-</p>
                    <p class="stat-label">Total Amount</p>
                </div>
                <div class="stat-icon info">
                    <i class="fas fa-dollar-sign"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <div class="filter-header">
            <h3 class="filter-title">
                <i class="fas fa-filter"></i>
                Filter Achievements
            </h3>
        </div>
        <form method="GET" action="{{ route('finance.achievements.index') }}">
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
                    <label for="achievement_type" class="form-label">Type</label>
                    <select name="achievement_type" id="achievement_type" class="form-control">
                        <option value="">All Types</option>
                        <option value="sales" {{ request('achievement_type') == 'sales' ? 'selected' : '' }}>Sales</option>
                        <option value="service" {{ request('achievement_type') == 'service' ? 'selected' : '' }}>Service</option>
                        <option value="installation" {{ request('achievement_type') == 'installation' ? 'selected' : '' }}>Installation</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-control">
                        <option value="">All Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="achieved" {{ request('status') == 'achieved' ? 'selected' : '' }}>Achieved</option>
                        <option value="exceeded" {{ request('status') == 'exceeded' ? 'selected' : '' }}>Exceeded</option>
                        <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" 
                           value="{{ request('start_date') }}">
                </div>
                <div class="form-group">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" 
                           value="{{ request('end_date') }}">
                </div>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Apply Filter
                </button>
                <a href="{{ route('finance.achievements.index') }}" class="btn btn-secondary">
                    <i class="fas fa-sync-alt"></i> Reset Filter
                </a>
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <div class="table-container">
        <div class="table-header">
            <h3 class="table-title">
                <i class="fas fa-trophy"></i>
                Achievement Records
            </h3>
            <div class="table-actions">
                <span class="text-sm text-gray-600">
                    Showing {{ $achievements->firstItem() ?? 0 }} to {{ $achievements->lastItem() ?? 0 }} 
                    of {{ $achievements->total() }} entries
                </span>
            </div>
        </div>

        <div class="table-wrapper">
            @if($achievements->count() > 0)
                <table class="data-table">
                    <thead>
                        <tr>
                            <th data-column="id">ID</th>
                            <th data-column="user.name">User</th>
                            <th data-column="achievement_type">Type</th>
                            <th data-column="target_amount" data-type="numeric">Target Amount</th>
                            <th data-column="achieved_amount" data-type="numeric">Achieved Amount</th>
                            <th data-column="status">Status</th>
                            <th data-column="created_by">Created By</th>
                            <th data-column="created_at" data-type="date">Created At</th>
                            <th data-column="updated_by">Last Updated By</th>
                            <th data-column="updated_at" data-type="date">Last Updated At</th>
                            <th data-column="achievement_date" data-type="date">Achievement Date</th>
                            <th data-no-filter>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($achievements as $achievement)
                        <tr onclick="openViewModal({{ $achievement->id }})" style="cursor: pointer;">
                            <td>{{ $achievement->id }}</td>
                            <td>{{ $achievement->user->name }}</td>
                            <td>
                                <span class="badge badge-info">
                                    {{ ucfirst($achievement->achievement_type) }}
                                </span>
                            </td>
                            <td>Rp {{ number_format($achievement->target_amount, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format($achievement->achieved_amount, 0, ',', '.') }}</td>
                            <td>
                                @if($achievement->status == 'achieved')
                                    <span class="badge badge-success">Achieved</span>
                                @elseif($achievement->status == 'exceeded')
                                    <span class="badge badge-warning">Exceeded</span>
                                @elseif($achievement->status == 'failed')
                                    <span class="badge badge-danger">Failed</span>
                                @else
                                    <span class="badge badge-secondary">Pending</span>
                                @endif
                            </td>
                            <td class="text-sm text-gray-500">{{ $achievement->createdBy->name ?? '-' }}</td>
                            <td class="text-sm text-gray-500">{!! $achievement->created_at ? $achievement->created_at->format('d/M/Y<br>at H.i') . ' WIB' : '-' !!}</td>
                            <td class="text-sm text-gray-500">{{ $achievement->updatedBy->name ?? '-' }}</td>
                            <td class="text-sm text-gray-500">{!! $achievement->updated_at ? $achievement->updated_at->format('d/M/Y<br>at H.i') . ' WIB' : '-' !!}</td>
                            <td>{{ $achievement->achievement_date ? $achievement->achievement_date->format('d/M/Y') : '-' }}</td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button onclick="openViewModal({{ $achievement->id }})" class="btn btn-sm btn-info" title="View">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button onclick="openEditModal({{ $achievement->id }})" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" action="{{ route('finance.achievements.destroy', $achievement) }}" class="d-inline" onsubmit="return confirmDeleteAchievement(event);">
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
            @else
                <div class="text-center py-5">
                    <i class="fas fa-trophy fa-3x text-gray-300 mb-3"></i>
                    <h4 class="text-gray-500">No achievements found</h4>
                    <p class="text-gray-400">Try adjusting your filters or create a new achievement.</p>
                </div>
            @endif
        </div>

        @if($achievements->hasPages())
        <div class="pagination-wrapper">
            <div class="pagination-info">
                <span class="info-text">
                    Showing {{ $achievements->firstItem() ?? 0 }} to {{ $achievements->lastItem() ?? 0 }} 
                    of {{ $achievements->total() }} entries
                </span>
            </div>
            {{ $achievements->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">View Achievement</h2>
            <button class="modal-close" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="modalBody" class="modal-body">
            <!-- Modal content will be loaded here -->
        </div>
        <div id="modalFooter" class="modal-footer">
            <!-- Modal footer content will be loaded here -->
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
        <h3 class="delete-modal-title">Hide Achievement</h3>
        <p class="delete-modal-description" id="deleteMessage">Are you sure you want to hide this achievement? This action can be undone later.</p>
        <div class="delete-modal-buttons">
            <button class="btn btn-cancel" onclick="closeDeleteModal()">Cancel</button>
            <button class="btn btn-hide" onclick="confirmDelete()">Yes, Hide</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Load statistics immediately
    loadStatistics();
    
    // Auto-refresh statistics every 30 seconds
    setInterval(loadStatistics, 30000);
});

function loadStatistics() {
    console.log('Loading statistics...');
    console.log('Route URL:', '{{ route("finance.achievements.statistics") }}');
    
    $.ajax({
        url: '{{ route("finance.achievements.statistics") }}',
        method: 'GET',
        dataType: 'json',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function(data) {
            console.log('Statistics data received:', data);
            console.log('Setting totalAchievements to:', data.total_achievements);
            console.log('Setting achievedCount to:', data.achieved_count);
            console.log('Setting exceededCount to:', data.exceeded_count);
            console.log('Setting totalAmount to:', data.total_amount);
            
            $('#totalAchievements').text(data.total_achievements || 0);
            $('#achievedCount').text(data.achieved_count || 0);
            $('#exceededCount').text(data.exceeded_count || 0);
            $('#totalAmount').text('Rp ' + (data.total_amount || 0).toLocaleString());
            
            console.log('Statistics updated successfully');
        },
        error: function(xhr, status, error) {
            console.error('Statistics error:', xhr.responseText);
            console.error('Status:', status);
            console.error('Error:', error);
            console.error('Response:', xhr.responseJSON);
            
            // Set default values on error
            $('#totalAchievements').text('0');
            $('#achievedCount').text('0');
            $('#exceededCount').text('0');
            $('#totalAmount').text('Rp 0');
        }
    });
}

function openViewModal(achievementId) {
    fetch(`/finance/achievements/${achievementId}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        const achievement = data.achievement;
        document.getElementById('modalTitle').textContent = 'View Achievement';
        document.getElementById('modalBody').innerHTML = `
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-600">User</label>
                        <p class="text-gray-900">${achievement.user ? achievement.user.name : 'N/A'}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Type</label>
                        <span class="badge badge-info">${achievement.achievement_type || 'N/A'}</span>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-600">Target Amount</label>
                        <p class="text-gray-900">Rp ${achievement.target_amount ? achievement.target_amount.toLocaleString() : '0'}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Achieved Amount</label>
                        <p class="text-gray-900">Rp ${achievement.achieved_amount ? achievement.achieved_amount.toLocaleString() : '0'}</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-600">Status</label>
                        <span class="badge badge-${achievement.status === 'achieved' ? 'success' : achievement.status === 'exceeded' ? 'warning' : achievement.status === 'failed' ? 'danger' : 'secondary'}">${achievement.status || 'N/A'}</span>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Achievement Date</label>
                        <p class="text-gray-900">${achievement.achievement_date ? achievement.achievement_date.split('T')[0] : 'N/A'}</p>
                    </div>
                </div>
                ${achievement.notes ? `
                    <div>
                        <label class="text-sm font-medium text-gray-600">Notes</label>
                        <p class="text-gray-900">${achievement.notes}</p>
                    </div>
                ` : ''}
            </div>
        `;
        document.getElementById('modalFooter').innerHTML = `
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Tutup</button>
            <button type="button" class="btn btn-primary" onclick="openEditModal(${achievementId})">Edit</button>
        `;
        document.getElementById('modalOverlay').classList.add('show');
    })
    .catch(error => {
        console.error('Error loading achievement details:', error);
        showErrorDialog('Gagal', 'Gagal memuat detail achievement.');
    });
}

function openEditModal(achievementId) {
    fetch(`/finance/achievements/${achievementId}/edit`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        const achievement = data.achievement;
        const users = data.users || [];
        const periods = data.periods || [];
        
        document.getElementById('modalTitle').textContent = 'Edit Achievement';
        document.getElementById('modalBody').innerHTML = `
            <form id="editForm" method="POST" action="/finance/achievements/${achievementId}">
                @csrf
                @method('PUT')
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-600">User *</label>
                            <select name="user_id" class="form-control" required>
                                <option value="">Select User</option>
                                ${users.map(user => `<option value="${user.id}" ${user.id == achievement.user_id ? 'selected' : ''}>${user.name}</option>`).join('')}
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Achievement Period *</label>
                            <select name="achievement_period_id" class="form-control" required>
                                <option value="">Select Period</option>
                                ${periods.map(period => `<option value="${period.id}" ${period.id == achievement.achievement_period_id ? 'selected' : ''}>${period.period_name}</option>`).join('')}
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Achievement Type *</label>
                        <select name="achievement_type" class="form-control" required>
                            <option value="sales" ${achievement.achievement_type === 'sales' ? 'selected' : ''}>Sales</option>
                            <option value="service" ${achievement.achievement_type === 'service' ? 'selected' : ''}>Service</option>
                            <option value="installation" ${achievement.achievement_type === 'installation' ? 'selected' : ''}>Installation</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-600">Target Amount *</label>
                            <input type="number" step="0.01" name="target_amount" class="form-control" value="${achievement.target_amount || ''}" required>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Achieved Amount</label>
                            <input type="number" step="0.01" name="achieved_amount" class="form-control" value="${achievement.achieved_amount || ''}">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-600">Commission Rate (%)</label>
                            <input type="number" step="0.01" name="commission_rate" class="form-control" value="${achievement.commission_rate || ''}">
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Status *</label>
                            <select name="status" class="form-control" required>
                                <option value="pending" ${achievement.status === 'pending' ? 'selected' : ''}>Pending</option>
                                <option value="achieved" ${achievement.status === 'achieved' ? 'selected' : ''}>Achieved</option>
                                <option value="exceeded" ${achievement.status === 'exceeded' ? 'selected' : ''}>Exceeded</option>
                                <option value="failed" ${achievement.status === 'failed' ? 'selected' : ''}>Failed</option>
                            </select>
                        </div>
                    </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Achievement Date</label>
                            <input type="date" name="achievement_date" class="form-control" value="${achievement.achievement_date ? achievement.achievement_date.split('T')[0] : ''}">
                        </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Notes</label>
                        <textarea name="notes" class="form-control" rows="3">${achievement.notes || ''}</textarea>
                    </div>
                </div>
            </form>
        `;
        document.getElementById('modalFooter').innerHTML = `
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
            <button type="submit" form="editForm" class="btn btn-primary">Perbarui Achievement</button>
        `;
        document.getElementById('modalOverlay').classList.add('show');
    })
    .catch(error => {
        console.error('Error loading edit form:', error);
        showErrorDialog('Gagal', 'Gagal memuat form edit.');
    });
}

function openCreateModal() {
    fetch('/finance/achievements/create', {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        const users = data.users || [];
        const periods = data.periods || [];
        
        document.getElementById('modalTitle').textContent = 'Create Achievement';
        document.getElementById('modalBody').innerHTML = `
            <form id="createForm" method="POST" action="/finance/achievements">
                @csrf
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-600">User *</label>
                            <select name="user_id" class="form-control" required>
                                <option value="">Select User</option>
                                ${users.map(user => `<option value="${user.id}">${user.name}</option>`).join('')}
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Achievement Period *</label>
                            <select name="achievement_period_id" class="form-control" required>
                                <option value="">Select Period</option>
                                ${periods.map(period => `<option value="${period.id}">${period.period_name}</option>`).join('')}
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Achievement Type *</label>
                        <select name="achievement_type" class="form-control" required>
                            <option value="">Select Type</option>
                            <option value="sales">Sales</option>
                            <option value="service">Service</option>
                            <option value="installation">Installation</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-600">Target Amount *</label>
                            <input type="number" step="0.01" name="target_amount" class="form-control" required>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Achieved Amount</label>
                            <input type="number" step="0.01" name="achieved_amount" class="form-control">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-600">Commission Rate (%)</label>
                            <input type="number" step="0.01" name="commission_rate" class="form-control">
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Status *</label>
                            <select name="status" class="form-control" required>
                                <option value="pending">Pending</option>
                                <option value="achieved">Achieved</option>
                                <option value="exceeded">Exceeded</option>
                                <option value="failed">Failed</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Achievement Date</label>
                        <input type="date" name="achievement_date" class="form-control">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Notes</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
            </form>
        `;
        document.getElementById('modalFooter').innerHTML = `
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
            <button type="submit" form="createForm" class="btn btn-primary">Simpan Achievement</button>
        `;
        document.getElementById('modalOverlay').classList.add('show');
    })
    .catch(error => {
        console.error('Error loading create form:', error);
        showErrorDialog('Gagal', 'Gagal memuat form tambah.');
    });
}

function closeModal() {
    document.getElementById('modalOverlay').classList.remove('show');
}

function openDeleteModal(achievementId, achievementType) {
    document.getElementById('deleteMessage').textContent = `Apakah Anda yakin ingin menyembunyikan achievement ${achievementType} ini? Tindakan ini masih bisa dibatalkan nanti.`;
    document.getElementById('deleteModalOverlay').classList.add('show');
    window.currentDeleteId = achievementId;
}

function closeDeleteModal() {
    document.getElementById('deleteModalOverlay').classList.remove('show');
}

function confirmDeleteAchievement(event) {
    const form = event.target;

    if (form.dataset.confirmedDelete === 'true') {
        delete form.dataset.confirmedDelete;
        return true;
    }

    event.preventDefault();

    showConfirmDialog(
        'Hapus achievement ini?',
        'Data achievement ini akan dihapus.'
    ).then((result) => {
        if (!result.isConfirmed) {
            return;
        }

        form.dataset.confirmedDelete = 'true';
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            form.submit();
        }
    });

    return false;
}

function confirmDelete() {
    if (window.currentDeleteId) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/finance/achievements/${window.currentDeleteId}`;
        
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

// Click outside to close modal
document.getElementById('modalOverlay').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});

// Update the create button to use modal
document.addEventListener('DOMContentLoaded', function() {
    const createButton = document.querySelector('a[href*="achievements/create"]');
    if (createButton) {
        createButton.onclick = function(e) {
            e.preventDefault();
            openCreateModal();
        };
    }
});
</script>
@endpush
