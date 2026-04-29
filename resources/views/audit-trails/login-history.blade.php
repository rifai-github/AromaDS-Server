@extends('layouts.app')

@section('title', 'Login History - System')
@section('breadcrumb', 'Home / System / Audit Trails / Login History')

@section('content')
<style>
    /* Force enable scroll and override any scroll locks */
    html {
        overflow-y: auto !important;
        height: auto !important;
    }
    
    body {
        overflow-y: auto !important;
        height: auto !important;
        position: static !important;
    }

    /* Force un-hide table content and pagination */
    .table-container {
        height: auto !important;
        max-height: none !important;
        overflow: visible !important;
        display: block !important;
    }

    .table-wrapper {
        overflow-x: auto !important;
        overflow-y: visible !important;
        max-height: none !important;
        height: auto !important;
    }

    .pagination-wrapper {
        display: flex !important;
        visibility: visible !important;
        opacity: 1 !important;
    }

    /* Standardizes font */
    body, * {
        font-family: 'Inter', sans-serif;
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
        -webkit-overflow-scrolling: touch;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
        min-width: 900px;
    }

    .data-table th {
        background-color: #225fd3;
        color: white;
        font-weight: 600;
        font-size: 13px;
        padding: 12px 16px;
        text-align: left;
        white-space: nowrap;
        position: sticky;
        top: 0;
        z-index: 10;
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

    .badge-danger {
        background-color: #fee2e2;
        color: #dc2626;
        border: 1px solid #fecaca;
    }

    .badge-secondary {
        background-color: #f1f5f9;
        color: #64748b;
        border: 1px solid #e2e8f0;
    }

    /* Status indicators */
    .status-indicator {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
    }

    .status-dot.success {
        background-color: #16a34a;
    }

    .status-dot.danger {
        background-color: #dc2626;
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
    <!-- Navigation Breadcrumb -->
    <div style="margin-bottom: 20px;">
        <a href="{{ route('audit-trails.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Audit Trails
        </a>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <div class="filter-title">
            <i class="fas fa-filter"></i>
            Filter Login History
        </div>
        <form action="{{ route('audit-trails.login-history') }}" method="GET">
            <div class="filter-grid">
                <div class="form-group">
                    <label class="form-label" for="user_id">User</label>
                    <select name="user_id" id="user_id" class="form-control" onchange="this.form.submit()">
                        <option value="">All Users</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="ip_address">IP Address</label>
                    <input type="text" name="ip_address" id="ip_address" class="form-control" 
                           placeholder="Filter by IP address..." value="{{ request('ip_address') }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="status">Status</label>
                    <select name="status" id="status" class="form-control" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="success" {{ request('status') == 'success' ? 'selected' : '' }}>Success</option>
                        <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
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
                <a href="{{ route('audit-trails.login-history') }}" class="btn btn-secondary">
                    <i class="fas fa-sync-alt"></i> Reset Filter
                </a>
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <div class="table-container">
        <div class="table-header">
            <h3 class="table-title">
                <i class="fas fa-sign-in-alt"></i>
                Login History Records
            </h3>
            <div class="table-actions">
                <span class="text-sm text-gray-600">
                    Showing {{ $loginHistories->firstItem() ?? 0 }} to {{ $loginHistories->lastItem() ?? 0 }} 
                    of {{ $loginHistories->total() }} entries
                </span>
            </div>
        </div>

        <div class="table-wrapper">
            @if($loginHistories->count() > 0)
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User / Input</th>
                            <th>Status</th>
                            <th>IP Address</th>
                            <th>Location</th>
                            <th>User Agent</th>
                            <th>Login Time</th>
                            <th>Logout Time</th>
                            <th>Failure Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($loginHistories as $history)
                        <tr>
                            <td>{{ $history->id }}</td>
                            <td>
                                @if($history->user)
                                    <div>
                                        <strong>{{ $history->user->name }}</strong>
                                        <br>
                                        <small class="text-gray-600">{{ $history->user->username }}</small>
                                    </div>
                                @elseif($history->attempted_identifier)
                                    <div class="text-danger">
                                        <i class="fas fa-user-slash"></i>
                                        <strong>{{ $history->attempted_identifier }}</strong>
                                        <br>
                                        <small>(Input User)</small>
                                    </div>
                                @else
                                    <span class="text-gray-500">N/A</span>
                                @endif
                            </td>
                            <td>
                                <div class="status-indicator">
                                    @if($history->is_successful)
                                        <span class="status-dot success"></span>
                                        <span class="badge badge-success">
                                            <i class="fas fa-check"></i> Success
                                        </span>
                                    @else
                                        <span class="status-dot danger"></span>
                                        <span class="badge badge-danger">
                                            <i class="fas fa-times"></i> Failed
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <code>{{ $history->ip_address }}</code>
                            </td>
                            <td>
                                @php
                                    $location = $history->location;
                                    // If location is empty/null, try to parse from user_agent
                                    if (empty($location) || $location === 'Unknown Location') {
                                        $ua = $history->user_agent ?? '';
                                        $browser = 'Unknown';
                                        $os = 'Unknown';
                                        
                                        // Parse browser
                                        if (preg_match('/MSIE|Trident/i', $ua)) {
                                            $browser = 'IE';
                                        } elseif (preg_match('/Firefox/i', $ua)) {
                                            $browser = 'Firefox';
                                        } elseif (preg_match('/Chrome/i', $ua) && !preg_match('/Edge|Edg/i', $ua)) {
                                            $browser = 'Chrome';
                                        } elseif (preg_match('/Safari/i', $ua) && !preg_match('/Chrome/i', $ua)) {
                                            $browser = 'Safari';
                                        } elseif (preg_match('/Edge|Edg/i', $ua)) {
                                            $browser = 'Edge';
                                        } elseif (preg_match('/Opera|OPR/i', $ua)) {
                                            $browser = 'Opera';
                                        }
                                        
                                        // Parse OS
                                        if (preg_match('/Windows/i', $ua)) {
                                            $os = 'Windows';
                                        } elseif (preg_match('/Mac OS X/i', $ua)) {
                                            $os = 'macOS';
                                        } elseif (preg_match('/Android/i', $ua)) {
                                            $os = 'Android';
                                        } elseif (preg_match('/Linux/i', $ua)) {
                                            $os = 'Linux';
                                        } elseif (preg_match('/iPhone|iPad|iPod/i', $ua)) {
                                            $os = 'iOS';
                                        }
                                        
                                        $location = "{$browser} on {$os}";
                                    }
                                @endphp
                                <span class="badge badge-secondary">
                                    <i class="fas fa-desktop"></i>
                                    {{ $location }}
                                </span>
                            </td>
                            <td>
                                <span title="{{ $history->user_agent }}" style="cursor: help;">
                                    {{ Str::limit($history->user_agent, 30) }}
                                </span>
                            </td>
                            <td>
                                @if($history->login_at)
                                    <div>
                                        <strong>{{ formatIndonesianDate($history->login_at) }}</strong>
                                        <br>
                                        <small class="text-gray-600">{{ formatIndonesianTimeOnly($history->login_at) }} WIB</small>
                                    </div>
                                @else
                                    <span class="text-gray-500">N/A</span>
                                @endif
                            </td>
                            <td>
                                @if($history->logout_at)
                                    <div>
                                        <strong>{{ formatIndonesianDate($history->logout_at) }}</strong>
                                        <br>
                                        <small class="text-gray-600">{{ formatIndonesianTimeOnly($history->logout_at) }} WIB</small>
                                    </div>
                                @else
                                    @if($history->is_successful)
                                        <span class="badge badge-secondary">
                                            <i class="fas fa-circle"></i> Active
                                        </span>
                                    @else
                                        <span class="text-gray-500">N/A</span>
                                    @endif
                                @endif
                            </td>
                            <td>
                                @if($history->failure_reason)
                                    <span class="badge badge-danger">
                                        <i class="fas fa-exclamation-circle"></i>
                                        {{ $history->failure_reason }}
                                    </span>
                                @else
                                    @if($history->is_successful)
                                        <span class="text-gray-400">-</span>
                                    @else
                                        <span class="badge badge-secondary">Tidak diketahui</span>
                                    @endif
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty-state">
                    <i class="fas fa-sign-in-alt"></i>
                    <h3>No Login History Found</h3>
                    <p>No login history records match your current filter criteria.</p>
                </div>
            @endif
        </div>

        @if($loginHistories->hasPages())
            <div class="pagination-wrapper">
                <div class="pagination-info">
                    <span class="info-text">
                        Menampilkan {{ $loginHistories->firstItem() ?? 0 }} sampai {{ $loginHistories->lastItem() ?? 0 }} 
                        dari {{ $loginHistories->total() }} entri
                    </span>
                </div>
                <div class="pagination-controls">
                    {{ $loginHistories->appends(request()->query())->links('pagination.custom') }}
                </div>
            </div>
        @endif
    </div>
</div>

@endsection
