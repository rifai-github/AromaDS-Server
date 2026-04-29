@extends('layouts.app')

@section('title', 'Reports Dashboard')
@section('breadcrumb', 'Home / Reports')

@section('content')
<style>
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
    
    .report-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .report-card {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 20px;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .report-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        border-color: #214589;
    }
    
    .report-card h4 {
        font-size: 16px;
        font-weight: 600;
        color: #214589;
        margin-bottom: 10px;
    }
    
    .report-card p {
        font-size: 14px;
        color: #64748b;
        margin-bottom: 15px;
    }
    
    .report-card .btn {
        background-color: #214589;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: background-color 0.2s ease;
        text-decoration: none;
        display: inline-block;
    }
    
    .report-card .btn:hover {
        background-color: #1e3a8a;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
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
</style>

<div class="flex flex-col w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">
        
        <!-- Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] pt-[7px] pr-[7px] pb-[7px] pl-[7px] md:pt-[10px] md:pr-[10px] md:pb-[10px] md:pl-[10px] lg:pt-[14px] lg:pr-[14px] lg:pb-[14px] lg:pl-[14px]">
            <div class="flex flex-row justify-start items-center w-full">
                <p class="text-[8px] md:text-[12px] lg:text-[16px] font-inter font-medium leading-[10px] md:leading-[15px] lg:leading-[20px] text-left text-[#214589] w-auto">Reports Dashboard</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="report-container w-full">
            <div class="report-section">
                <h3>Quick Statistics</h3>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number">0</div>
                        <div class="stat-label">Total Reports</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">0</div>
                        <div class="stat-label">Active Reports</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">0</div>
                        <div class="stat-label">Scheduled Reports</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">0</div>
                        <div class="stat-label">Recent Exports</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Categories -->
        <div class="report-container w-full">
            <div class="report-section">
                <h3>Report Categories</h3>
                <div class="report-grid">
                    <div class="report-card" onclick="window.location.href='{{ route('reports.operational.index') }}'">
                        <h4>Operational Reports</h4>
                        <p>Job schedules, assignments, team performance, and operational metrics.</p>
                        <a href="{{ route('reports.operational.index') }}" class="btn">View Reports</a>
                    </div>
                    
                    <div class="report-card" onclick="window.location.href='{{ route('reports.financial.index') }}'">
                        <h4>Financial Reports</h4>
                        <p>Quotations, contracts, invoices, payments, and revenue analysis.</p>
                        <a href="{{ route('reports.financial.index') }}" class="btn">View Reports</a>
                    </div>
                    
                    <div class="report-card" onclick="window.location.href='{{ route('reports.inventory.index') }}'">
                        <h4>Inventory Reports</h4>
                        <p>Stock levels, movements, low stock alerts, and inventory analysis.</p>
                        <a href="{{ route('reports.inventory.index') }}" class="btn">View Reports</a>
                    </div>
                    
                    <div class="report-card" onclick="window.location.href='{{ route('reports.customer.index') }}'">
                        <h4>Customer Reports</h4>
                        <p>Customer lists, activities, financial summaries, and customer insights.</p>
                        <a href="{{ route('reports.customer.index') }}" class="btn">View Reports</a>
                    </div>
                    
                    <div class="report-card" onclick="window.location.href='{{ route('reports.hr.index') }}'">
                        <h4>HR Reports</h4>
                        <p>Team performance, workload analysis, job assignments, and efficiency metrics.</p>
                        <a href="{{ route('reports.hr.index') }}" class="btn">View Reports</a>
                    </div>
                    
                    <div class="report-card" onclick="window.location.href='{{ route('reports.marketing.index') }}'">
                        <h4>Marketing Reports</h4>
                        <p>Prospects, surveys, quotations, contracts, and sales activities.</p>
                        <a href="{{ route('reports.marketing.index') }}" class="btn">View Reports</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="report-container w-full">
            <div class="report-section">
                <h3>Quick Actions</h3>
                <div class="flex flex-wrap gap-4">
                    <a href="{{ route('reports.dashboard.index') }}" class="btn">
                        <i class="fas fa-tachometer-alt mr-2"></i>
                        Dashboard Reports
                    </a>
                    <a href="{{ route('reports.template.index') }}" class="btn">
                        <i class="fas fa-file-alt mr-2"></i>
                        Report Templates
                    </a>
                    <a href="{{ route('reports.export.index') }}" class="btn">
                        <i class="fas fa-download mr-2"></i>
                        Data Export
                    </a>
                    <a href="{{ route('reports.kpi.index') }}" class="btn">
                        <i class="fas fa-chart-line mr-2"></i>
                        KPI Management
                    </a>
                    <a href="{{ route('reports.analytics.index') }}" class="btn">
                        <i class="fas fa-chart-bar mr-2"></i>
                        Analytics
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add click handlers for report cards
    const reportCards = document.querySelectorAll('.report-card');
    reportCards.forEach(card => {
        card.addEventListener('click', function() {
            const link = card.querySelector('a');
            if (link) {
                window.location.href = link.href;
            }
        });
    });
});
</script>
@endsection