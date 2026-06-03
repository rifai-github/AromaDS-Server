@extends('layouts.app')

@section('title', 'Operational Report')
@section('breadcrumb', 'Home / Report / Operational Report')

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

    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
    }

    /* Statistics Cards */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .stat-number {
        font-size: 24px;
        font-weight: 700;
        color: #214589;
        margin-bottom: 5px;
    }

    .stat-label {
        font-size: 14px;
        color: #64748b;
        font-weight: 500;
    }

    /* Report Container */
    .report-container {
        background-color: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
    }

    .report-section {
        margin-bottom: 30px;
    }

    .report-section h3 {
        font-size: 18px;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #e5e7eb;
    }

    /* Filter Section */
    .filter-section {
        background-color: #f9fafb;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        border: 1px solid #e5e7eb;
    }

    .filter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        align-items: end;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
    }

    .filter-label {
        font-weight: 500;
        color: #374151;
        font-size: 14px;
        margin-bottom: 5px;
    }

    .filter-input {
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.2s ease;
        background-color: white;
    }

    .filter-input:focus {
        outline: none;
        border-color: #214589;
        box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
    }

    .filter-select {
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        background-color: white;
        transition: border-color 0.2s ease;
    }

    .filter-select:focus {
        outline: none;
        border-color: #214589;
        box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
    }

    /* Table Container */
    .table-container {
        background: white;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        border-radius: 0 0 10px 10px;
        position: relative;
        width: 100%;
        max-width: 100%;
        margin: 0;
        padding: 0;
        overflow-x: auto;
        overflow-y: visible;
        -webkit-overflow-scrolling: touch;
    }

    /* Custom scrollbar */
    .table-container::-webkit-scrollbar {
        height: 8px;
    }

    .table-container::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }

    .table-container::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 4px;
    }

    .table-container::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }

    /* Scroll indicator */
    .table-container::after {
        content: '← Scroll horizontally to see more →';
        position: absolute;
        bottom: -25px;
        left: 50%;
        transform: translateX(-50%);
        font-size: 12px;
        color: #666;
        opacity: 0.7;
    }

    /* Responsive Table */
    .responsive-table {
        min-width: 1200px;
        table-layout: auto;
        width: 100%;
        border-collapse: collapse;
        margin: 0;
        padding: 0;
        height: auto;
    }
    
    .responsive-table th,
    .responsive-table td {
        padding: 12px 8px;
        text-align: left;
        border-bottom: 1px solid #e5e7eb;
        white-space: nowrap;
        font-size: 14px;
        line-height: 1.4;
    }
    
    .responsive-table th {
        background-color: #214589;
        color: white;
        font-weight: 600;
        font-size: 13px;
        position: sticky;
        top: 0;
        z-index: 10;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        overflow: visible;
        text-overflow: unset;
    }

    .responsive-table td {
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .responsive-table tbody tr:hover {
        background-color: #eff6ff;
        transition: background-color 0.2s ease;
    }
    
    .responsive-table tbody tr {
        cursor: pointer;
    }
    
    .responsive-table tbody {
        height: auto;
    }

    /* Column widths for better layout */
    .responsive-table th:nth-child(1), .responsive-table td:nth-child(1) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(2), .responsive-table td:nth-child(2) { width: 100px; min-width: 100px; }
    .responsive-table th:nth-child(3), .responsive-table td:nth-child(3) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(4), .responsive-table td:nth-child(4) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(5), .responsive-table td:nth-child(5) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(6), .responsive-table td:nth-child(6) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(7), .responsive-table td:nth-child(7) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(8), .responsive-table td:nth-child(8) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(9), .responsive-table td:nth-child(9) { width: 120px; min-width: 120px; }

    /* Pagination Specific Styles */
    .pagination-controls {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        flex-wrap: nowrap;
        white-space: nowrap;
    }

    .page-number {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s ease;
    }
    
    .page-number.active {
        background-color: #214589;
        color: white;
    }
    
    .page-number:not(.active) {
        color: #6b7280;
    }
    
    .page-number:not(.active):hover {
        background-color: #f3f4f6;
        color: #214589;
    }

    .page-dropdown-container {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .pagination-btn {
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s ease;
        border: 1px solid #d1d5db;
        background-color: #f9fafb;
        color: #374151;
    }

    .pagination-btn:hover:not(:disabled) {
        background-color: #f3f4f6;
        border-color: #9ca3af;
    }

    .pagination-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* Status Badge Styles */
    .status-badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
        text-transform: uppercase;
    }
    
    .status-completed {
        background-color: #d1fae5;
        color: #065f46;
    }
    
    .status-pending {
        background-color: #fef3c7;
        color: #92400e;
    }
    
    .status-in-progress {
        background-color: #dbeafe;
        color: #1e40af;
    }
    
    .status-cancelled {
        background-color: #fee2e2;
        color: #991b1b;
    }

    /* Performance Indicators */
    .performance-good {
        color: #059669;
        font-weight: 600;
    }
    
    .performance-average {
        color: #d97706;
        font-weight: 600;
    }
    
    .performance-poor {
        color: #dc2626;
        font-weight: 600;
    }

    /* Action Buttons */
    .action-buttons {
        display: flex;
        gap: 8px;
        justify-content: center;
    }

    /* Export Section */
    .export-section {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 12px;
        padding: 20px 0;
        border-top: 1px solid #e5e7eb;
        margin-top: 20px;
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .responsive-table th,
        .responsive-table td {
            padding: 8px 6px;
            font-size: 12px;
        }
        
        .responsive-table {
            min-width: 1200px;
        }
        
        .filter-grid {
            grid-template-columns: 1fr;
            gap: 12px;
        }
        
        .stats-grid {
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .export-section {
            flex-direction: column;
            align-items: stretch;
            gap: 8px;
        }
        
        .export-section .btn {
            width: 100%;
            justify-content: center;
        }
        
        /* Header responsive */
        .flex.flex-row.justify-between {
            flex-direction: column;
            gap: 1rem;
            align-items: stretch;
        }
        
        .flex.flex-row.justify-between > div:first-child {
            width: 100%;
        }
        
        .flex.flex-row.justify-between > div:last-child {
            width: 100%;
            justify-content: flex-start;
        }
    }

    /* Tablet and small screen responsive */
    @media (max-width: 1024px) and (min-width: 769px) {
        .filter-grid {
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        }
        
        .stats-grid {
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }
    }
</style>

<div class="flex flex-col w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">
        
        <!-- Operational Report Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Operational Report</h1>
            </div>
            
            <div class="flex flex-row justify-end items-center">
                <a href="{{ route('reports.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    <span class="hidden md:inline">Back to Reports</span>
                    <span class="md:hidden">Back</span>
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="report-container w-full">
            <div class="report-section">
                <h3>Operational Statistics</h3>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number">{{ $statistics['total_jobs'] ?? 0 }}</div>
                        <div class="stat-label">Total Jobs</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">{{ $statistics['completed_jobs'] ?? 0 }}</div>
                        <div class="stat-label">Completed Jobs</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">{{ $statistics['pending_jobs'] ?? 0 }}</div>
                        <div class="stat-label">Pending Jobs</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">{{ $statistics['in_progress_jobs'] ?? 0 }}</div>
                        <div class="stat-label">In Progress Jobs</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">{{ $statistics['total_assignments'] ?? 0 }}</div>
                        <div class="stat-label">Total Assignments</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">{{ $statistics['assigned_jobs'] ?? 0 }}</div>
                        <div class="stat-label">Assigned Jobs</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Job Schedule Report -->
        <div class="report-container w-full">
            <div class="report-section">
                <h3>Job Schedule Report</h3>
                
                <!-- Filter Section -->
                <div class="filter-section">
                    <form action="{{ route('reports.operational.job-schedule') }}" method="GET" id="jobScheduleForm">
                        <div class="filter-grid">
                            <div class="filter-group">
                                <label class="filter-label">Team</label>
                                <select name="team_id" class="filter-select">
                                    <option value="">All Teams</option>
                                    @foreach($teams ?? [] as $team)
                                        <option value="{{ $team->id }}" {{ request('team_id') == $team->id ? 'selected' : '' }}>
                                            {{ $team->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label class="filter-label">Status</label>
                                <select name="status" class="filter-select">
                                    <option value="">All Status</option>
                                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label class="filter-label">Start Date</label>
                                <input type="date" name="start_date" class="filter-input" value="{{ request('start_date') }}">
                            </div>
                            
                            <div class="filter-group">
                                <label class="filter-label">End Date</label>
                                <input type="date" name="end_date" class="filter-input" value="{{ request('end_date') }}">
                            </div>
                            
                            <div class="filter-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                    <span class="hidden md:inline">Filter</span>
                                    <span class="md:hidden">Filter</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Report Table -->
                @if(isset($jobSchedules) && $jobSchedules->count() > 0)
                <div class="table-container">
                    <table class="responsive-table">
                        <thead>
                            <tr>
                                <th>Job No</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Building</th>
                                <th>Company</th>
                                <th>Team</th>
                                <th>Schedule Date</th>
                                <th>Expected Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($jobSchedules as $job)
                            <tr onclick="window.location.href='{{ route('operational.job-schedules.show', $job->id) }}'">
                                <td class="font-medium">{{ $job->job_number ?? '-' }}</td>
                                <td>{{ $job->type ? ucfirst($job->type) : '-' }}</td>
                                <td>
                                    <span class="status-badge status-{{ str_replace('_', '-', $job->status ?? 'pending') }}">
                                        {{ ucfirst(str_replace('_', ' ', $job->status ?? 'pending')) }}
                                    </span>
                                </td>
                                <td>{{ $job->building->name ?? '-' }}</td>
                                <td>{{ $job->customer->name ?? '-' }}</td>
                                <td>{{ $job->team->name ?? '-' }}</td>
                                <td>{{ $job->schedule_date ? \Carbon\Carbon::parse($job->schedule_date)->format('d/M/Y') : '-' }}</td>
                                <td>{{ $job->expected_date ? \Carbon\Carbon::parse($job->expected_date)->format('d/M/Y') : '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination for Job Schedules -->
                @if(isset($jobSchedules) && $jobSchedules->hasPages())
                <div class="pagination-controls" style="display: flex; justify-content: center; align-items: center; margin-top: 24px; gap: 8px;">
                    {{ $jobSchedules->links() }}
                </div>
                @endif
                
                <!-- Export Buttons -->
                <div class="export-section">
                    <a href="{{ route('reports.operational.export.job-schedule') }}?{{ http_build_query(request()->all()) }}" class="btn btn-success">
                        <i class="fas fa-download"></i>
                        <span class="hidden md:inline">Export Excel</span>
                        <span class="md:hidden">Excel</span>
                    </a>
                    <a href="{{ route('reports.operational.export.job-schedule.pdf') }}?{{ http_build_query(request()->all()) }}" class="btn btn-secondary">
                        <i class="fas fa-file-pdf"></i>
                        <span class="hidden md:inline">Export PDF</span>
                        <span class="md:hidden">PDF</span>
                    </a>
                </div>
                @else
                <div class="text-center py-8">
                    <div class="text-gray-600">
                        <i class="fas fa-calendar-alt text-4xl mb-3"></i>
                        <p class="text-lg">No job schedule data found for the selected filters.</p>
                        <p class="text-sm mt-2">Try adjusting your filter criteria or date range.</p>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Team Performance Report -->
        <div class="report-container w-full">
            <div class="report-section">
                <h3>Team Performance Report</h3>
                
                <!-- Filter Section -->
                <div class="filter-section">
                    <form action="{{ route('reports.operational.team-performance') }}" method="GET" id="teamPerformanceForm">
                        <div class="filter-grid">
                            <div class="filter-group">
                                <label class="filter-label">Team</label>
                                <select name="team_id" class="filter-select">
                                    <option value="">All Teams</option>
                                    @foreach($teams ?? [] as $team)
                                        <option value="{{ $team->id }}" {{ request('team_id') == $team->id ? 'selected' : '' }}>
                                            {{ $team->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label class="filter-label">Start Date</label>
                                <input type="date" name="start_date" class="filter-input" value="{{ request('start_date') }}">
                            </div>
                            
                            <div class="filter-group">
                                <label class="filter-label">End Date</label>
                                <input type="date" name="end_date" class="filter-input" value="{{ request('end_date') }}">
                            </div>
                            
                            <div class="filter-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                    <span class="hidden md:inline">Filter</span>
                                    <span class="md:hidden">Filter</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Report Table -->
                @if(isset($teamPerformance) && $teamPerformance->count() > 0)
                <div class="table-container">
                    <table class="responsive-table">
                        <thead>
                            <tr>
                                <th>Team</th>
                                <th>Total Jobs</th>
                                <th>Completed Jobs</th>
                                <th>Pending Jobs</th>
                                <th>In Progress Jobs</th>
                                <th>Completion Rate</th>
                                <th>Average Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($teamPerformance as $performance)
                            <tr onclick="window.location.href='{{ route('operational.teams.show', $performance->team_id) }}'">
                                <td class="font-medium">{{ $performance->team_name ?? '-' }}</td>
                                <td class="text-center">{{ $performance->total_jobs ?? 0 }}</td>
                                <td class="text-center">{{ $performance->completed_jobs ?? 0 }}</td>
                                <td class="text-center">{{ $performance->pending_jobs ?? 0 }}</td>
                                <td class="text-center">{{ $performance->in_progress_jobs ?? 0 }}</td>
                                <td class="text-center {{ ($performance->completion_rate ?? 0) >= 80 ? 'performance-good' : (($performance->completion_rate ?? 0) >= 60 ? 'performance-average' : 'performance-poor') }}">
                                    {{ number_format($performance->completion_rate ?? 0, 1) }}%
                                </td>
                                <td class="text-center">{{ $performance->average_time ?? '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination for Team Performance -->
                @if(isset($teamPerformance) && $teamPerformance->hasPages())
                <div class="pagination-controls" style="display: flex; justify-content: center; align-items: center; margin-top: 24px; gap: 8px;">
                    {{ $teamPerformance->links() }}
                </div>
                @endif
                
                <!-- Export Buttons -->
                <div class="export-section">
                    <a href="{{ route('reports.operational.export.team-performance') }}?{{ http_build_query(request()->all()) }}" class="btn btn-success">
                        <i class="fas fa-download"></i>
                        <span class="hidden md:inline">Export Excel</span>
                        <span class="md:hidden">Excel</span>
                    </a>
                    <a href="{{ route('reports.operational.export.team-performance.pdf') }}?{{ http_build_query(request()->all()) }}" class="btn btn-secondary">
                        <i class="fas fa-file-pdf"></i>
                        <span class="hidden md:inline">Export PDF</span>
                        <span class="md:hidden">PDF</span>
                    </a>
                </div>
                @else
                <div class="text-center py-8">
                    <div class="text-gray-600">
                        <i class="fas fa-users text-4xl mb-3"></i>
                        <p class="text-lg">No team performance data found for the selected filters.</p>
                        <p class="text-sm mt-2">Try adjusting your filter criteria or date range.</p>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation for date ranges
    const forms = ['jobScheduleForm', 'teamPerformanceForm'];
    
    forms.forEach(formId => {
        const form = document.getElementById(formId);
        if (form) {
            form.addEventListener('submit', function(e) {
                const startDate = form.querySelector('input[name="start_date"]');
                const endDate = form.querySelector('input[name="end_date"]');
                
                if (startDate && endDate && startDate.value && endDate.value) {
                    if (new Date(startDate.value) > new Date(endDate.value)) {
                        e.preventDefault();
                        alert('Start date must be before or equal to end date.');
                        return;
                    }
                }
            });
        }
    });

    // Add loading states for export buttons
    const exportButtons = document.querySelectorAll('.export-section .btn');
    exportButtons.forEach(button => {
        button.addEventListener('click', function() {
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Preparing...';
            this.disabled = true;
            
            // Re-enable after 3 seconds (in case of slow response)
            setTimeout(() => {
                this.innerHTML = originalText;
                this.disabled = false;
            }, 3000);
        });
    });

    // Add hover effects for stat cards
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
            this.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.1)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 1px 3px rgba(0, 0, 0, 0.1)';
        });
    });

    // Add performance indicator tooltips
    const performanceCells = document.querySelectorAll('.performance-good, .performance-average, .performance-poor');
    performanceCells.forEach(cell => {
        const rate = parseFloat(cell.textContent);
        let tooltip = '';
        
        if (rate >= 80) {
            tooltip = 'Excellent performance!';
        } else if (rate >= 60) {
            tooltip = 'Good performance';
        } else {
            tooltip = 'Needs improvement';
        }
        
        cell.title = tooltip;
    });
});
</script>
@endsection