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
                <h1 class="page-title">Achievement Period Management</h1>
                <p class="page-subtitle">Manage achievement periods for tracking</p>
            </div>
            <div>
                <a href="{{ route('finance.achievement-periods.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> New Period
                </a>
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

    <!-- Filter Section -->
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
            @if($achievementPeriods->count() > 0)
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Period Name</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Duration</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($achievementPeriods as $period)
                        <tr>
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
                                @if($period->status == 'active')
                                    <span class="badge badge-success">Active</span>
                                @elseif($period->status == 'completed')
                                    <span class="badge badge-warning">Completed</span>
                                @else
                                    <span class="badge badge-secondary">Inactive</span>
                                @endif
                            </td>
                            <td>{{ $period->createdBy->name ?? 'Unknown' }}</td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('finance.achievement-periods.show', $period) }}" class="btn btn-sm btn-info" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('finance.achievement-periods.edit', $period) }}" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="{{ route('finance.achievement-periods.destroy', $period) }}" class="d-inline" onsubmit="return confirm('Are you sure?')">
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
                    <i class="fas fa-calendar-alt fa-3x text-gray-300 mb-3"></i>
                    <h4 class="text-gray-500">No achievement periods found</h4>
                    <p class="text-gray-400">Try adjusting your filters or create a new period.</p>
                </div>
            @endif
        </div>

        @if($achievementPeriods->hasPages())
        <div class="pagination-wrapper">
            <div class="pagination-info">
                <span class="info-text">
                    Showing {{ $achievementPeriods->firstItem() ?? 0 }} to {{ $achievementPeriods->lastItem() ?? 0 }} 
                    of {{ $achievementPeriods->total() }} entries
                </span>
            </div>
            <div class="pagination-controls">
                @if($achievementPeriods->onFirstPage())
                    <span class="btn" disabled>Previous</span>
                @else
                    <a href="{{ $achievementPeriods->previousPageUrl() }}" class="btn">Previous</a>
                @endif

                @foreach($achievementPeriods->getUrlRange(1, $achievementPeriods->lastPage()) as $page => $url)
                    @if($page == $achievementPeriods->currentPage())
                        <span class="btn active">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="btn">{{ $page }}</a>
                    @endif
                @endforeach

                @if($achievementPeriods->hasMorePages())
                    <a href="{{ $achievementPeriods->nextPageUrl() }}" class="btn">Next</a>
                @else
                    <span class="btn" disabled>Next</span>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
