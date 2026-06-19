@extends('layouts.app')

@section('title', 'Dashboard')
@section('breadcrumb', 'Home / Dashboard')

@section('content')
<style>
    /* ============================================
       DASHBOARD STYLES - MODERN & PROFESSIONAL
       ============================================ */
    
    .dashboard-wrapper {
        background: linear-gradient(135deg, #f5f7fa 0%, #e4e8ec 100%);
        min-height: 100vh;
        padding: 1rem;
    }
    
    .dashboard-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding: 1rem 1.5rem;
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }
    
    .dashboard-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #214589;
        margin: 0;
    }
    
    .dashboard-date {
        color: #64748b;
        font-size: 0.875rem;
    }
    
    /* KPI Cards */
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    
    @media (max-width: 1400px) {
        .kpi-grid { grid-template-columns: repeat(3, 1fr); }
    }
    
    @media (max-width: 768px) {
        .kpi-grid { grid-template-columns: repeat(2, 1fr); }
    }
    
    @media (max-width: 480px) {
        .kpi-grid { grid-template-columns: 1fr; }
    }
    
    .kpi-card {
        background: white;
        border-radius: 12px;
        padding: 1.25rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .kpi-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.12);
    }
    
    .kpi-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
    }
    
    .kpi-card.blue::before { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
    .kpi-card.green::before { background: linear-gradient(90deg, #10b981, #34d399); }
    .kpi-card.amber::before { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
    .kpi-card.purple::before { background: linear-gradient(90deg, #8b5cf6, #a78bfa); }
    .kpi-card.red::before { background: linear-gradient(90deg, #ef4444, #f87171); }
    .kpi-card.indigo::before { background: linear-gradient(90deg, #6366f1, #818cf8); }
    
    .kpi-icon {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        margin-bottom: 0.75rem;
    }
    
    .kpi-card.blue .kpi-icon { background: #dbeafe; color: #2563eb; }
    .kpi-card.green .kpi-icon { background: #d1fae5; color: #059669; }
    .kpi-card.amber .kpi-icon { background: #fef3c7; color: #d97706; }
    .kpi-card.purple .kpi-icon { background: #ede9fe; color: #7c3aed; }
    .kpi-card.red .kpi-icon { background: #fee2e2; color: #dc2626; }
    .kpi-card.indigo .kpi-icon { background: #e0e7ff; color: #4f46e5; }
    
    .kpi-value {
        font-size: 1.75rem;
        font-weight: 700;
        color: #1e293b;
        line-height: 1;
        margin-bottom: 0.25rem;
    }
    
    .kpi-label {
        font-size: 0.8rem;
        color: #64748b;
        font-weight: 500;
    }
    
    /* Content Grid */
    .content-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }
    
    @media (max-width: 1200px) {
        .content-grid { grid-template-columns: 1fr; }
    }
    
    .content-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        overflow: hidden;
    }
    
    .content-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #e2e8f0;
        background: #f8fafc;
    }
    
    .content-card-title {
        font-size: 0.95rem;
        font-weight: 600;
        color: #214589;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .content-card-body {
        padding: 1rem 1.25rem;
        max-height: 400px;
        overflow-y: auto;
    }
    
    /* Job Tables */
    .job-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .job-table th {
        text-align: left;
        font-size: 0.75rem;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        padding: 0.5rem;
        border-bottom: 1px solid #e2e8f0;
        position: sticky;
        top: -1rem; /* Adjust for .content-card-body padding */
        background: #f8fafc; /* Match header background or white */
        z-index: 10;
        box-shadow: inset 0 -1px 0 #e2e8f0;
    }
    
    .job-table td {
        padding: 0.75rem 0.5rem;
        font-size: 0.85rem;
        color: #334155;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }
    
    .job-table tr:hover {
        background: #f8fafc;
    }
    
    .status-badge {
        display: inline-block;
        padding: 0.25rem 0.6rem;
        border-radius: 9999px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .status-badge.in_progress, .status-badge.assigned { background: #dbeafe; color: #1d4ed8; }
    .status-badge.scheduled, .status-badge.pending { background: #fef3c7; color: #b45309; }
    .status-badge.done, .status-badge.completed { background: #d1fae5; color: #047857; }
    .status-badge.suspend { background: #fee2e2; color: #b91c1c; }
    .status-badge.force_majeure { background: #f3f4f6; color: #4b5563; }
    .status-badge.active { background: #d1fae5; color: #047857; }
    .status-badge.approved { background: #d1fae5; color: #047857; }
    .status-badge.hot { background: #fee2e2; color: #b91c1c; }
    .status-badge.warm { background: #fef3c7; color: #b45309; }
    .status-badge.cold { background: #e0e7ff; color: #4338ca; }
    
    /* List Items */
    .list-item {
        display: flex;
        align-items: flex-start;
        padding: 0.75rem 0;
        border-bottom: 1px solid #f1f5f9;
        gap: 0.75rem;
    }
    
    .list-item:last-child {
        border-bottom: none;
    }
    
    .list-icon {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.875rem;
        flex-shrink: 0;
    }
    
    .list-icon.blue { background: #dbeafe; color: #2563eb; }
    .list-icon.green { background: #d1fae5; color: #059669; }
    .list-icon.purple { background: #ede9fe; color: #7c3aed; }
    .list-icon.amber { background: #fef3c7; color: #d97706; }
    
    .list-content {
        flex: 1;
        min-width: 0;
    }
    
    .list-title {
        font-size: 0.85rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 0.125rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .list-subtitle {
        font-size: 0.75rem;
        color: #64748b;
    }
    
    /* Charts Section */
    .charts-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }
    
    @media (max-width: 992px) {
        .charts-grid { grid-template-columns: 1fr; }
    }
    
    .chart-container {
        height: 280px;
    }
    
    /* Activity Timeline */
    .activity-item {
        display: flex;
        gap: 1rem;
        padding: 0.75rem 0;
        border-bottom: 1px solid #f1f5f9;
    }
    
    .activity-item:last-child {
        border-bottom: none;
    }
    
    .activity-icon {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        flex-shrink: 0;
    }
    
    .activity-icon.blue { background: #dbeafe; color: #2563eb; }
    .activity-icon.green { background: #d1fae5; color: #059669; }
    .activity-icon.purple { background: #ede9fe; color: #7c3aed; }
    
    .activity-text {
        font-size: 0.85rem;
        color: #334155;
    }
    
    .activity-time {
        font-size: 0.75rem;
        color: #94a3b8;
    }
    
    /* Quick Actions */
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 0.75rem;
    }
    
    .quick-action-btn {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1rem;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        color: #334155;
        font-size: 0.8rem;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s ease;
    }
    
    .quick-action-btn:hover {
        background: #214589;
        border-color: #214589;
        color: white;
        transform: translateY(-2px);
    }
    
    .quick-action-btn i {
        font-size: 1rem;
    }
    
    /* Refresh button */
    .refresh-btn {
        background: #214589;
        color: white;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 500;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.2s ease;
    }
    
    .refresh-btn:hover {
        background: #1e3a8a;
    }
    
    /* Empty state */
    .empty-state {
        text-align: center;
        padding: 2rem;
        color: #94a3b8;
    }
    
    .empty-state i {
        font-size: 2rem;
        margin-bottom: 0.5rem;
    }

    /* Scrollbar styling */
    .content-card-body::-webkit-scrollbar {
        width: 6px;
    }
    
    .content-card-body::-webkit-scrollbar-track {
        background: #f1f5f9;
    }
    
    .content-card-body::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }
</style>

<div class="dashboard-wrapper">
    <!-- Header -->
    <div class="dashboard-header">
        <div>
            <h1 class="dashboard-title">
                <i class="fas fa-tachometer-alt me-2"></i>Dashboard Overview
            </h1>
            <p class="dashboard-date mb-0">
                {{ now()->locale('id')->translatedFormat('l, d/M/Y') }}
            </p>
        </div>
        <button class="refresh-btn" onclick="location.reload()">
            <i class="fas fa-sync-alt"></i> Refresh
        </button>
    </div>

    <!-- KPI Cards -->
    <div class="kpi-grid">
        <div class="kpi-card blue">
            <div class="kpi-icon"><i class="fas fa-play-circle"></i></div>
            <div class="kpi-value">{{ $jobStats['in_progress'] }}</div>
            <div class="kpi-label">Jobs In Progress</div>
        </div>
        <div class="kpi-card green">
            <div class="kpi-icon"><i class="fas fa-calendar-day"></i></div>
            <div class="kpi-value">{{ $jobStats['today'] }}</div>
            <div class="kpi-label">Jobs Today</div>
        </div>
        <div class="kpi-card amber">
            <div class="kpi-icon"><i class="fas fa-clock"></i></div>
            <div class="kpi-value">{{ $jobStats['this_week'] }}</div>
            <div class="kpi-label">Jobs This Week</div>
        </div>
        <div class="kpi-card purple">
            <div class="kpi-icon"><i class="fas fa-check-circle"></i></div>
            <div class="kpi-value">{{ $jobStats['completed_today'] }}</div>
            <div class="kpi-label">Completed Today</div>
        </div>
        <div class="kpi-card indigo">
            <div class="kpi-icon"><i class="fas fa-file-contract"></i></div>
            <div class="kpi-value">{{ $contractStats['active'] }}</div>
            <div class="kpi-label">Active Contracts</div>
        </div>
        <div class="kpi-card red">
            <div class="kpi-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="kpi-value">{{ $lowStockCount }}</div>
            <div class="kpi-label">Low Stock Alerts</div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="content-grid">
        <!-- Left Column: Jobs -->
        <div>
            <!-- Jobs In Progress -->
            <div class="content-card mb-4">
                <div class="content-card-header">
                    <h3 class="content-card-title">
                        <i class="fas fa-play text-primary"></i> Jobs In Progress
                    </h3>
                    <span class="badge bg-primary">{{ $jobsInProgress->count() }}</span>
                </div>
                <div class="content-card-body">
                    @if($jobsInProgress->count() > 0)
                    <table class="job-table">
                        <thead>
                            <tr>
                                <th>Job Number</th>
                                <th>Building</th>
                                <th>Schedule</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($jobsInProgress as $job)
                            <tr>
                                <td><strong>{{ $job->job_number ?? '-' }}</strong></td>
                                <td>{{ $job->building->nama_gedung ?? $job->building_name ?? '-' }}</td>
                                <td>{{ $job->schedule_date ? \Carbon\Carbon::parse($job->schedule_date)->format('d/M/Y') : '-' }}</td>
                                <td><span class="status-badge {{ $job->status }}">{{ str_replace('_', ' ', $job->status) }}</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <div class="empty-state">
                        <i class="fas fa-check-circle"></i>
                        <p>No jobs currently in progress</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Today's Jobs -->
            <div class="content-card mb-4">
                <div class="content-card-header">
                    <h3 class="content-card-title">
                        <i class="fas fa-calendar-day text-success"></i> Today's Jobs
                    </h3>
                    <span class="badge bg-success">{{ $todaysJobs->count() }}</span>
                </div>
                <div class="content-card-body">
                    @if($todaysJobs->count() > 0)
                    <table class="job-table">
                        <thead>
                            <tr>
                                <th>Job Number</th>
                                <th>Building</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($todaysJobs as $job)
                            <tr>
                                <td><strong>{{ $job->job_number ?? '-' }}</strong></td>
                                <td>{{ $job->building->nama_gedung ?? $job->building_name ?? '-' }}</td>
                                <td><span class="status-badge {{ $job->status }}">{{ str_replace('_', ' ', $job->status) }}</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <div class="empty-state">
                        <i class="fas fa-calendar-check"></i>
                        <p>No jobs scheduled for today</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Upcoming Jobs -->
            <div class="content-card">
                <div class="content-card-header">
                    <h3 class="content-card-title">
                        <i class="fas fa-forward text-warning"></i> Upcoming Jobs (Next 7 Days)
                    </h3>
                    <span class="badge bg-warning text-dark">{{ $upcomingJobs->count() }}</span>
                </div>
                <div class="content-card-body">
                    @if($upcomingJobs->count() > 0)
                    <table class="job-table">
                        <thead>
                            <tr>
                                <th>Job Number</th>
                                <th>Building</th>
                                <th>Schedule Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($upcomingJobs as $job)
                            <tr>
                                <td><strong>{{ $job->job_number ?? '-' }}</strong></td>
                                <td>{{ $job->building->nama_gedung ?? $job->building_name ?? '-' }}</td>
                                <td>{{ $job->schedule_date ? \Carbon\Carbon::parse($job->schedule_date)->format('d/M/Y (D)') : '-' }}</td>
                                <td><span class="status-badge {{ $job->status }}">{{ str_replace('_', ' ', $job->status) }}</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <div class="empty-state">
                        <i class="fas fa-calendar"></i>
                        <p>No upcoming jobs in the next 7 days</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right Column: Surveys, Pipeline, etc -->
        <div>
            <!-- Upcoming Surveys -->
            <div class="content-card mb-4">
                <div class="content-card-header">
                    <h3 class="content-card-title">
                        <i class="fas fa-clipboard-check text-success"></i> Upcoming Surveys
                    </h3>
                    <span class="badge bg-success">{{ $upcomingSurveys->count() }}</span>
                </div>
                <div class="content-card-body">
                    @forelse($upcomingSurveys as $survey)
                    <div class="list-item">
                        <div class="list-icon green"><i class="fas fa-clipboard-list"></i></div>
                        <div class="list-content">
                            <div class="list-title">{{ $survey->customer->name ?? 'N/A' }}</div>
                            <div class="list-subtitle">
                                {{ $survey->survey_date ? \Carbon\Carbon::parse($survey->survey_date)->format('d/M/Y') : 'Not scheduled' }}
                                · <span class="status-badge {{ $survey->status }}">{{ $survey->status }}</span>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="empty-state">
                        <i class="fas fa-clipboard"></i>
                        <p>No upcoming surveys</p>
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Marketing Pipeline: Recent Prospects -->
            <div class="content-card mb-4">
                <div class="content-card-header">
                    <h3 class="content-card-title">
                        <i class="fas fa-bullhorn text-info"></i> Recent Prospects
                    </h3>
                    <span class="badge bg-info">{{ $prospectStats['total'] }}</span>
                </div>
                <div class="content-card-body">
                    @forelse($recentProspects as $prospect)
                    <div class="list-item">
                        <div class="list-icon blue"><i class="fas fa-user-tie"></i></div>
                        <div class="list-content">
                            <div class="list-title">{{ $prospect->company_name ?? 'N/A' }}</div>
                            <div class="list-subtitle">
                                {{ $prospect->created_at->diffForHumans() }}
                                @if($prospect->status)
                                · <span class="status-badge {{ $prospect->status }}">{{ $prospect->status }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="empty-state">
                        <i class="fas fa-bullhorn"></i>
                        <p>No prospects yet</p>
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Recent Quotations -->
            <div class="content-card mb-4">
                <div class="content-card-header">
                    <h3 class="content-card-title">
                        <i class="fas fa-file-invoice text-purple"></i> Recent Quotations
                    </h3>
                    <span class="badge" style="background: #8b5cf6;">{{ $quotationStats['pending'] }} pending</span>
                </div>
                <div class="content-card-body">
                    @forelse($recentQuotations as $quotation)
                    <div class="list-item">
                        <div class="list-icon purple"><i class="fas fa-file-alt"></i></div>
                        <div class="list-content">
                            <div class="list-title">{{ $quotation->quotation_number ?? 'N/A' }}</div>
                            <div class="list-subtitle">
                                {{ $quotation->customer->name ?? 'N/A' }}
                                · <span class="status-badge {{ $quotation->status }}">{{ $quotation->status }}</span>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="empty-state">
                        <i class="fas fa-file-invoice"></i>
                        <p>No quotations yet</p>
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Marketing Pipeline (Work Plans) -->
            <div class="content-card mb-4" style="border-left: 4px solid #f59e0b;">
                <div class="content-card-header" style="background: linear-gradient(90deg, #fff7ed, #ffffff);">
                    <h3 class="content-card-title">
                        <i class="fas fa-road text-warning"></i> Marketing Pipeline
                    </h3>
                    @if($pipelineStats['needs_followup'] > 0)
                    <span class="badge bg-danger">{{ $pipelineStats['needs_followup'] }} perlu follow-up</span>
                    @endif
                </div>
                <div class="content-card-body">
                    <!-- Pipeline Stats Summary -->
                    <div class="d-flex justify-content-between mb-3 pb-2" style="border-bottom: 1px solid #f1f5f9;">
                        <div class="text-center">
                            <div class="fw-bold text-warning">{{ $pipelineStats['prospect'] }}</div>
                            <small class="text-muted">Prospect</small>
                        </div>
                        <div class="text-center">
                            <div class="fw-bold text-info">{{ $pipelineStats['qualified'] }}</div>
                            <small class="text-muted">Qualified</small>
                        </div>
                        <div class="text-center">
                            <div class="fw-bold text-success">{{ $pipelineStats['converted'] }}</div>
                            <small class="text-muted">Converted</small>
                        </div>
                    </div>

                    <!-- Upcoming Follow-ups -->
                    @if($upcomingFollowups->count() > 0)
                    <div class="mb-2">
                        <small class="text-muted fw-bold">📅 Upcoming Follow-ups:</small>
                    </div>
                    @foreach($upcomingFollowups as $pipeline)
                    <div class="list-item">
                        <div class="list-icon amber"><i class="fas fa-calendar-check"></i></div>
                        <div class="list-content">
                            <div class="list-title">{{ $pipeline->company_name ?? 'N/A' }}</div>
                            <div class="list-subtitle">
                                <strong>{{ $pipeline->follow_up_date ? $pipeline->follow_up_date->format('d/M/Y') : '-' }}</strong>
                                · <span class="status-badge {{ $pipeline->status }}">{{ $pipeline->status }}</span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                    @else
                    <div class="empty-state py-2">
                        <i class="fas fa-check-circle text-success"></i>
                        <p class="mb-0">Tidak ada follow-up dalam 14 hari ke depan</p>
                    </div>
                    @endif

                    <!-- Recent Pipelines -->
                    @if($recentPipelines->count() > 0 && $upcomingFollowups->count() == 0)
                    @foreach($recentPipelines->take(5) as $pipeline)
                    <div class="list-item">
                        <div class="list-icon amber"><i class="fas fa-road"></i></div>
                        <div class="list-content">
                            <div class="list-title">{{ $pipeline->company_name ?? 'N/A' }}</div>
                            <div class="list-subtitle">
                                {{ $pipeline->visit_date ? $pipeline->visit_date->format('d/M/Y') : '-' }}
                                · <span class="status-badge {{ $pipeline->status }}">{{ $pipeline->status }}</span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                    @endif
                </div>
            </div>

            <!-- Recent Contracts -->
            <div class="content-card mb-4">
                <div class="content-card-header">
                    <h3 class="content-card-title">
                        <i class="fas fa-file-contract text-primary"></i> Recent Contracts
                    </h3>
                    @if($contractStats['expiring_soon'] > 0)
                    <span class="badge bg-warning text-dark">{{ $contractStats['expiring_soon'] }} expiring</span>
                    @endif
                </div>
                <div class="content-card-body">
                    @forelse($recentContracts as $contract)
                    <div class="list-item">
                        <div class="list-icon blue"><i class="fas fa-file-signature"></i></div>
                        <div class="list-content">
                            <div class="list-title">{{ $contract->contract_number ?? 'N/A' }}</div>
                            <div class="list-subtitle">
                                {{ $contract->customer->name ?? 'N/A' }}
                                · <span class="status-badge {{ $contract->status }}">{{ $contract->status }}</span>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="empty-state">
                        <i class="fas fa-file-contract"></i>
                        <p>No contracts yet</p>
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Recent Invoices (No Amount) -->
            <div class="content-card">
                <div class="content-card-header">
                    <h3 class="content-card-title">
                        <i class="fas fa-receipt text-amber"></i> Recent Invoices
                    </h3>
                </div>
                <div class="content-card-body">
                    @forelse($recentInvoices as $invoice)
                    <div class="list-item">
                        <div class="list-icon amber"><i class="fas fa-file-invoice-dollar"></i></div>
                        <div class="list-content">
                            <div class="list-title">{{ $invoice->invoice_number ?? 'N/A' }}</div>
                            <div class="list-subtitle">
                                {{ $invoice->customer->name ?? 'N/A' }}
                                · {{ $invoice->created_at->format('d/M/Y') }}
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="empty-state">
                        <i class="fas fa-receipt"></i>
                        <p>No invoices yet</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="charts-grid">
        <div class="content-card">
            <div class="content-card-header">
                <h3 class="content-card-title">
                    <i class="fas fa-chart-pie"></i> Job Status Distribution
                </h3>
            </div>
            <div class="content-card-body">
                <div class="chart-container">
                    <canvas id="jobStatusChart"></canvas>
                </div>
            </div>
        </div>
        <div class="content-card">
            <div class="content-card-header">
                <h3 class="content-card-title">
                    <i class="fas fa-chart-bar"></i> Jobs This Week
                </h3>
            </div>
            <div class="content-card-body">
                <div class="chart-container">
                    <canvas id="jobsWeekChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Activity & Quick Actions -->
    <div class="content-grid">
        <!-- Recent Activity -->
        <div class="content-card">
            <div class="content-card-header">
                <h3 class="content-card-title">
                    <i class="fas fa-history"></i> Recent Activity
                </h3>
            </div>
            <div class="content-card-body">
                @forelse($recentActivities as $activity)
                <div class="activity-item">
                    <div class="activity-icon {{ $activity['color'] }}">
                        <i class="{{ $activity['icon'] }}"></i>
                    </div>
                    <div class="flex-1">
                        <div class="activity-text">{{ $activity['description'] }}</div>
                        <div class="activity-time">{{ $activity['time_ago'] }}</div>
                    </div>
                    <span class="status-badge {{ $activity['status'] }}">{{ str_replace('_', ' ', $activity['status']) }}</span>
                </div>
                @empty
                <div class="empty-state">
                    <i class="fas fa-history"></i>
                    <p>No recent activity</p>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Quick Actions (Admin/Management Only) -->
        @if(auth()->user() && auth()->user()->hasPermission('admin.view'))
        <div class="content-card">
            <div class="content-card-header">
                <h3 class="content-card-title">
                    <i class="fas fa-bolt"></i> Quick Actions
                </h3>
            </div>
            <div class="content-card-body">
                <div class="quick-actions">
                    <a href="{{ route('operational.job-schedules.index') }}" class="quick-action-btn">
                        <i class="fas fa-calendar-alt"></i> Job Schedules
                    </a>
                    <a href="{{ route('marketing.surveys.index') }}" class="quick-action-btn">
                        <i class="fas fa-clipboard-check"></i> Surveys
                    </a>
                    <a href="{{ route('marketing.prospects.index') }}" class="quick-action-btn">
                        <i class="fas fa-bullhorn"></i> Prospects
                    </a>
                    <a href="{{ route('marketing.quotations.index') }}" class="quick-action-btn">
                        <i class="fas fa-file-invoice"></i> Quotations
                    </a>
                    <a href="{{ route('marketing.contracts.index') }}" class="quick-action-btn">
                        <i class="fas fa-file-contract"></i> Contracts
                    </a>
                    <a href="{{ route('finance.invoices.index') }}" class="quick-action-btn">
                        <i class="fas fa-receipt"></i> Invoices
                    </a>
                    <a href="{{ route('warehouse.master-products.index') }}" class="quick-action-btn">
                        <i class="fas fa-boxes"></i> Inventory
                    </a>
                    <a href="{{ route('system.users.index') }}" class="quick-action-btn">
                        <i class="fas fa-users"></i> Users
                    </a>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Job Status Distribution Chart
    const statusCtx = document.getElementById('jobStatusChart');
    if (statusCtx) {
        new Chart(statusCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($jobStatusDistribution['labels']) !!},
                datasets: [{
                    data: {!! json_encode($jobStatusDistribution['data']) !!},
                    backgroundColor: {!! json_encode($jobStatusDistribution['colors']) !!},
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            font: { size: 11 }
                        }
                    }
                },
                cutout: '60%'
            }
        });
    }

    // Jobs This Week Chart
    const weekCtx = document.getElementById('jobsWeekChart');
    if (weekCtx) {
        const jobsByDay = {!! json_encode($jobsByDay) !!};
        new Chart(weekCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: jobsByDay.map(d => d.day + '\n' + d.date),
                datasets: [{
                    label: 'Scheduled',
                    data: jobsByDay.map(d => d.count),
                    backgroundColor: 'rgba(59, 130, 246, 0.7)',
                    borderRadius: 6,
                    barPercentage: 0.6
                }, {
                    label: 'Completed',
                    data: jobsByDay.map(d => d.completed),
                    backgroundColor: 'rgba(16, 185, 129, 0.7)',
                    borderRadius: 6,
                    barPercentage: 0.6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            font: { size: 11 }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }
});
</script>
@endsection
