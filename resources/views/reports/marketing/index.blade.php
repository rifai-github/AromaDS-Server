@extends('layouts.app')

@section('title', 'Marketing Report')
@section('breadcrumb', 'Home / Report / Marketing Report')

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
        min-width: 1000px;
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
    .responsive-table th:nth-child(1), .responsive-table td:nth-child(1) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(2), .responsive-table td:nth-child(2) { width: 200px; min-width: 200px; }
    .responsive-table th:nth-child(3), .responsive-table td:nth-child(3) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(4), .responsive-table td:nth-child(4) { width: 200px; min-width: 200px; }
    .responsive-table th:nth-child(5), .responsive-table td:nth-child(5) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(6), .responsive-table td:nth-child(6) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(7), .responsive-table td:nth-child(7) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(8), .responsive-table td:nth-child(8) { width: 120px; min-width: 120px; }

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
    
    .status-new {
        background-color: #dbeafe;
        color: #1e40af;
    }
    
    .status-contacted {
        background-color: #fef3c7;
        color: #92400e;
    }
    
    .status-qualified {
        background-color: #d1fae5;
        color: #065f46;
    }
    
    .status-proposal {
        background-color: #f3e8ff;
        color: #7c3aed;
    }
    
    .status-negotiation {
        background-color: #fef3c7;
        color: #92400e;
    }
    
    .status-closed-won {
        background-color: #d1fae5;
        color: #065f46;
    }
    
    .status-closed-lost {
        background-color: #fee2e2;
        color: #991b1b;
    }
    
    .status-pending {
        background-color: #fef3c7;
        color: #92400e;
    }
    
    .status-in-progress {
        background-color: #dbeafe;
        color: #1e40af;
    }
    
    .status-completed {
        background-color: #d1fae5;
        color: #065f46;
    }
    
    .status-cancelled {
        background-color: #fee2e2;
        color: #991b1b;
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
            min-width: 1000px;
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
        
        <!-- Marketing Report Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Marketing Report</h1>
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
                <h3>Marketing Statistics</h3>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number">{{ $statistics['total_prospects'] ?? 0 }}</div>
                        <div class="stat-label">Total Prospects</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">{{ $statistics['total_surveys'] ?? 0 }}</div>
                        <div class="stat-label">Total Surveys</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">{{ $statistics['total_quotations'] ?? 0 }}</div>
                        <div class="stat-label">Total Quotations</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">{{ $statistics['total_contracts'] ?? 0 }}</div>
                        <div class="stat-label">Total Contracts</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">{{ $statistics['total_job_advices'] ?? 0 }}</div>
                        <div class="stat-label">Total Job Advices</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">{{ $statistics['total_sales_activities'] ?? 0 }}</div>
                        <div class="stat-label">Total Sales Activities</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Prospect Report -->
        <div class="report-container w-full">
            <div class="report-section">
                <h3>Prospect Report</h3>
                
                <!-- Filter Section -->
                <div class="filter-section">
                    <form action="{{ route('reports.marketing.prospect') }}" method="GET" id="prospectForm">
                        <div class="filter-grid">
                            <div class="filter-group">
                                <label class="filter-label">Status</label>
                                <select name="status" class="filter-select">
                                    <option value="">All Status</option>
                                    <option value="new" {{ request('status') == 'new' ? 'selected' : '' }}>New</option>
                                    <option value="contacted" {{ request('status') == 'contacted' ? 'selected' : '' }}>Contacted</option>
                                    <option value="qualified" {{ request('status') == 'qualified' ? 'selected' : '' }}>Qualified</option>
                                    <option value="proposal" {{ request('status') == 'proposal' ? 'selected' : '' }}>Proposal</option>
                                    <option value="negotiation" {{ request('status') == 'negotiation' ? 'selected' : '' }}>Negotiation</option>
                                    <option value="closed_won" {{ request('status') == 'closed_won' ? 'selected' : '' }}>Closed Won</option>
                                    <option value="closed_lost" {{ request('status') == 'closed_lost' ? 'selected' : '' }}>Closed Lost</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label class="filter-label">Staff</label>
                                <select name="staff_id" class="filter-select">
                                    <option value="">All Staff</option>
                                    @foreach($staff ?? [] as $member)
                                        <option value="{{ $member->id }}" {{ request('staff_id') == $member->id ? 'selected' : '' }}>
                                            {{ $member->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label class="filter-label">Company Name</label>
                                <input type="text" name="company_name" class="filter-input" value="{{ request('company_name') }}" placeholder="Search company...">
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
                @if(isset($prospects) && $prospects->count() > 0)
                <div class="table-container">
                    <table class="responsive-table">
                        <thead>
                            <tr>
                                <th>Staff</th>
                                <th>Company Name</th>
                                <th>Contact Name</th>
                                <th>Contact Email</th>
                                <th>Status</th>
                                <th>Follow Up Date</th>
                                <th>Latest Update</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($prospects as $prospect)
                            <tr onclick="window.location.href='{{ route('marketing.prospects.show', $prospect->id) }}'">
                                <td class="font-medium">{{ $prospect->assignedTo->name ?? '-' }}</td>
                                <td>{{ $prospect->company_name ?? '-' }}</td>
                                <td>{{ $prospect->contact_name ?? '-' }}</td>
                                <td>{{ $prospect->contact_email ?? '-' }}</td>
                                <td>
                                    <span class="status-badge status-{{ str_replace('_', '-', $prospect->status ?? 'new') }}">
                                        {{ ucfirst(str_replace('_', ' ', $prospect->status ?? 'new')) }}
                                    </span>
                                </td>
                                <td>{{ $prospect->follow_up_date ? \Carbon\Carbon::parse($prospect->follow_up_date)->format('d/m/Y') : '-' }}</td>
                                <td>{{ $prospect->updated_at ? \Carbon\Carbon::parse($prospect->updated_at)->format('d/m/Y H:i') : '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination for Prospects -->
                @if(isset($prospects) && $prospects->hasPages())
                <div class="pagination-controls" style="display: flex; justify-content: center; align-items: center; margin-top: 24px; gap: 8px;">
                    {{ $prospects->links() }}
                </div>
                @endif
                
                <!-- Export Buttons -->
                <div class="export-section">
                    <a href="{{ route('reports.marketing.export.prospect') }}?{{ http_build_query(request()->all()) }}" class="btn btn-success">
                        <i class="fas fa-download"></i>
                        <span class="hidden md:inline">Export Excel</span>
                        <span class="md:hidden">Excel</span>
                    </a>
                    <a href="{{ route('reports.marketing.export.prospect.pdf') }}?{{ http_build_query(request()->all()) }}" class="btn btn-secondary">
                        <i class="fas fa-file-pdf"></i>
                        <span class="hidden md:inline">Export PDF</span>
                        <span class="md:hidden">PDF</span>
                    </a>
                </div>
                @else
                <div class="text-center py-8">
                    <div class="text-gray-600">
                        <i class="fas fa-users text-4xl mb-3"></i>
                        <p class="text-lg">No prospect data found for the selected filters.</p>
                        <p class="text-sm mt-2">Try adjusting your filter criteria or date range.</p>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Survey Report -->
        <div class="report-container w-full">
            <div class="report-section">
                <h3>Survey Report</h3>
                
                <!-- Filter Section -->
                <div class="filter-section">
                    <form action="{{ route('reports.marketing.survey') }}" method="GET" id="surveyForm">
                        <div class="filter-grid">
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
                                <label class="filter-label">Marketing Staff</label>
                                <select name="marketing_staff_id" class="filter-select">
                                    <option value="">All Staff</option>
                                    @foreach($staff ?? [] as $member)
                                        <option value="{{ $member->id }}" {{ request('marketing_staff_id') == $member->id ? 'selected' : '' }}>
                                            {{ $member->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label class="filter-label">Company Name</label>
                                <input type="text" name="company_name" class="filter-input" value="{{ request('company_name') }}" placeholder="Search company...">
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
                @if(isset($surveys) && $surveys->count() > 0)
                <div class="table-container">
                    <table class="responsive-table">
                        <thead>
                            <tr>
                                <th>Survey Number</th>
                                <th>Company Name</th>
                                <th>Marketing Staff</th>
                                <th>Status</th>
                                <th>Survey Date</th>
                                <th>Building Name</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($surveys as $survey)
                            <tr onclick="window.location.href='{{ route('marketing.surveys.show', $survey->id) }}'">
                                <td class="font-medium">{{ $survey->survey_number ?? '-' }}</td>
                                <td>{{ $survey->company_name ?? '-' }}</td>
                                <td>{{ $survey->marketingStaff->name ?? '-' }}</td>
                                <td>
                                    <span class="status-badge status-{{ str_replace('_', '-', $survey->status ?? 'pending') }}">
                                        {{ ucfirst(str_replace('_', ' ', $survey->status ?? 'pending')) }}
                                    </span>
                                </td>
                                <td>{{ $survey->survey_date ? \Carbon\Carbon::parse($survey->survey_date)->format('d/m/Y') : '-' }}</td>
                                <td>{{ $survey->building_name ?? '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination for Surveys -->
                @if(isset($surveys) && $surveys->hasPages())
                <div class="pagination-controls" style="display: flex; justify-content: center; align-items: center; margin-top: 24px; gap: 8px;">
                    {{ $surveys->links() }}
                </div>
                @endif
                
                <!-- Export Buttons -->
                <div class="export-section">
                    <a href="{{ route('reports.marketing.export.survey') }}?{{ http_build_query(request()->all()) }}" class="btn btn-success">
                        <i class="fas fa-download"></i>
                        <span class="hidden md:inline">Export Excel</span>
                        <span class="md:hidden">Excel</span>
                    </a>
                    <a href="{{ route('reports.marketing.export.survey.pdf') }}?{{ http_build_query(request()->all()) }}" class="btn btn-secondary">
                        <i class="fas fa-file-pdf"></i>
                        <span class="hidden md:inline">Export PDF</span>
                        <span class="md:hidden">PDF</span>
                    </a>
                </div>
                @else
                <div class="text-center py-8">
                    <div class="text-gray-600">
                        <i class="fas fa-clipboard-list text-4xl mb-3"></i>
                        <p class="text-lg">No survey data found for the selected filters.</p>
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
    const forms = ['prospectForm', 'surveyForm'];
    
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
});
</script>
@endsection