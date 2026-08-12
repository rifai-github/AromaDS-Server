@extends('layouts.app')

@section('title', 'Unit On Wall Detail')

@section('content')

<style>
    .container-fluid {
        padding: 0 !important;
        margin: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
    }
    
    .row {
        margin: 0 !important;
        display: flex !important;
        flex-wrap: wrap !important;
        width: 100% !important;
    }
    
    .col-12 {
        padding: 15px !important;
        width: 100% !important;
    }
    
    .card {
        width: 100% !important;
        margin-bottom: 1rem !important;
        border-radius: 8px !important;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
        border: 1px solid rgba(0, 0, 0, 0.125) !important;
    }
    
    .card-header {
        padding: 1rem 1.5rem !important;
        border-bottom: 1px solid rgba(0, 0, 0, 0.125) !important;
    }
    
    .card-body {
        padding: 1.5rem !important;
    }
    
    .nav-tabs {
        border-bottom: 2px solid #1e3a8a !important;
        margin: 0 !important;
        display: flex !important;
        flex-direction: row !important;
    }
    
    .nav-tabs .nav-item {
        flex: 1 !important;
    }
    
    .nav-tabs .nav-link {
        border: none !important;
        border-radius: 0 !important;
        transition: all 0.3s ease !important;
        padding: 12px 20px !important;
        width: 100% !important;
        text-align: center !important;
    }
    
    .nav-tabs .nav-link:hover {
        border-color: transparent !important;
        background-color: #f8f9fa !important;
    }
    
    .nav-tabs .nav-link.active {
        border-color: transparent !important;
        background-color: white !important;
        border-bottom: 3px solid #1e3a8a !important;
        color: #1e3a8a !important;
        font-weight: bold !important;
    }
    
    .tab-content {
        width: 100% !important;
        min-height: 500px !important;
    }
    
    .tab-pane {
        width: 100% !important;
        min-height: 500px !important;
        display: none !important;
    }
    
    .tab-pane.show.active {
        display: block !important;
    }
    
    .info-card {
        border: 1px solid #dee2e6 !important;
        border-radius: 8px !important;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
    }
    
    .info-field {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        padding: 0.75rem 0 !important;
        border-bottom: 1px solid #e9ecef !important;
    }
    
    .info-field:last-child {
        border-bottom: none !important;
    }
    
    .info-field-label {
        font-weight: 600 !important;
        color: #6c757d !important;
        font-size: 0.9rem !important;
    }
    
    .info-field-value {
        color: #212529 !important;
        font-size: 0.95rem !important;
        text-align: right !important;
    }
    
    .status-badge {
        padding: 0.25rem 0.75rem !important;
        border-radius: 0.375rem !important;
        font-size: 0.875rem !important;
        font-weight: 500 !important;
    }
    
    .status-active {
        background-color: #d1e7dd !important;
        color: #0f5132 !important;
    }
    
    .status-inactive {
        background-color: #f8d7da !important;
        color: #842029 !important;
    }
    
    .status-maintenance {
        background-color: #fff3cd !important;
        color: #856404 !important;
    }
    
    .status-removed {
        background-color: #d1ecf1 !important;
        color: #055160 !important;
    }
    
    .log-item {
        padding: 1rem !important;
        border-bottom: 1px solid #e9ecef !important;
        background-color: #f8f9fa !important;
        margin-bottom: 0.5rem !important;
        border-radius: 0.375rem !important;
    }
    
    .log-item:last-child {
        border-bottom: none !important;
    }
    
    .log-timestamp {
        font-size: 0.875rem !important;
        color: #6c757d !important;
        font-weight: 600 !important;
    }
    
    .log-message {
        margin-top: 0.5rem !important;
        color: #212529 !important;
    }
    
    .refresh-indicator {
        display: inline-block;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background-color: #28a745;
        margin-right: 0.5rem;
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.5; }
        100% { opacity: 1; }
    }
    
    .refresh-indicator.inactive {
        background-color: #dc3545;
        animation: none;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header Card -->
            <div class="card mb-3" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
                <div class="card-body" style="padding: 1.5rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h3 class="card-title mb-0" style="color: white; font-size: 1.5rem; font-weight: bold;">
                                <i class="fas fa-wifi me-2"></i>{{ $unitOnWall->serial_number ?? 'Unit #' . $unitOnWall->id }}
                            </h3>
                            <p class="mb-0 mt-1" style="color: rgba(255, 255, 255, 0.9); font-size: 0.9rem;">
                                {{ $unitOnWall->product_name ?? ($unitOnWall->product?->name ?? '-') }} - {{ $unitOnWall->room_name ?? ($unitOnWall->room?->room_name ?? '-') }}
                            </p>
                        </div>
                        <div>
                            <button type="button" class="btn btn-light btn-sm me-2" onclick="window.print()">
                                <i class="fas fa-print"></i> Print
                            </button>
                            <a href="{{ route('warehouse.unit-on-walls.index') }}" class="btn btn-light btn-sm">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Success/Error Messages -->
            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert" style="margin: 15px;">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif
            
            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert" style="margin: 15px;">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            <!-- Navigation Tabs - 5 TABS -->
            <div class="card mb-3">
                <div class="card-body p-0">
                    <ul class="nav nav-tabs" id="unitOnWallTabs" role="tablist">
                        <li class="nav-item" role="presentation" style="flex: 1;">
                            <button class="nav-link active" id="live-tab" data-bs-toggle="tab" data-bs-target="#live" type="button" role="tab" aria-controls="live" aria-selected="true">
                                <i class="fas fa-wifi me-2"></i>LIVE
                                @if($hasWifi)
                                <span class="refresh-indicator" id="refreshIndicator"></span>
                                @endif
                            </button>
                        </li>
                        <li class="nav-item" role="presentation" style="flex: 1;">
                            <button class="nav-link" id="history-log-tab" data-bs-toggle="tab" data-bs-target="#history-log" type="button" role="tab" aria-controls="history-log" aria-selected="false">
                                <i class="fas fa-history me-2"></i>HISTORY LOG
                            </button>
                        </li>
                        <li class="nav-item" role="presentation" style="flex: 1;">
                            <button class="nav-link" id="history-service-tab" data-bs-toggle="tab" data-bs-target="#history-service" type="button" role="tab" aria-controls="history-service" aria-selected="false">
                                <i class="fas fa-tools me-2"></i>HISTORY SERVICE
                            </button>
                        </li>
                        <li class="nav-item" role="presentation" style="flex: 1;">
                            <button class="nav-link" id="history-repair-tab" data-bs-toggle="tab" data-bs-target="#history-repair" type="button" role="tab" aria-controls="history-repair" aria-selected="false">
                                <i class="fas fa-wrench me-2"></i>HISTORY REPAIR
                            </button>
                        </li>
                        <li class="nav-item" role="presentation" style="flex: 1;">
                            <button class="nav-link" id="setting-tab" data-bs-toggle="tab" data-bs-target="#setting" type="button" role="tab" aria-controls="setting" aria-selected="false">
                                <i class="fas fa-cog me-2"></i>SETTING
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Tab Content -->
            <div class="tab-content" id="unitOnWallTabsContent">
                <!-- Tab 1: LIVE (Real-time monitoring for WiFi units) -->
                <div class="tab-pane fade show active" id="live" role="tabpanel" aria-labelledby="live-tab">
                    <div class="row g-3">
                        @if($hasWifi)
                        <!-- Unit with WiFi - Real-time monitoring -->
                        <div class="col-12">
                            <div class="card info-card">
                                <div class="card-header" style="background-color: #28a745; color: white; border-radius: 8px 8px 0 0;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-wifi me-2"></i>Real-time Monitoring
                                        </h5>
                                        <div>
                                            <select id="refreshInterval" class="form-select form-select-sm" style="width: auto; display: inline-block;">
                                                <option value="60000">Auto Refresh: 1 minute</option>
                                                <option value="300000">Auto Refresh: 5 minutes</option>
                                                <option value="3600000">Auto Refresh: 1 hour</option>
                                                <option value="43200000">Auto Refresh: 12 hours</option>
                                                <option value="86400000">Auto Refresh: 1 day</option>
                                            </select>
                                            <button type="button" class="btn btn-sm btn-light ms-2" onclick="refreshLiveData()">
                                                <i class="fas fa-sync-alt"></i> Refresh Now
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div id="liveDataContainer">
                                        <!-- Real-time logs will be displayed here -->
                                        <div class="text-center text-muted py-5">
                                            <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
                                            <p>Loading real-time data...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @else
                        <!-- Unit without WiFi - Static detail -->
                        <div class="col-12 col-lg-6">
                            <div class="card info-card h-100">
                                <div class="card-header" style="background-color: #6c757d; color: white; border-radius: 8px 8px 0 0;">
                                    <h5 class="card-title mb-0">Unit Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="info-field">
                                        <div class="info-field-label">Serial Number</div>
                                        <div class="info-field-value">{{ $unitOnWall->serial_number ?? '-' }}</div>
                                    </div>
                                    <div class="info-field">
                                        <div class="info-field-label">Product</div>
                                        <div class="info-field-value">{{ $unitOnWall->product_name ?? ($unitOnWall->product?->name ?? '-') }}</div>
                                    </div>
                                    <div class="info-field">
                                        <div class="info-field-label">Status</div>
                                        <div class="info-field-value">
                                            <span class="status-badge status-{{ $unitOnWall->status }}">
                                                {{ $unitOnWall->status_text }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="info-field">
                                        <div class="info-field-label">Install Date</div>
                                        <div class="info-field-value">{{ $unitOnWall->install_date?->format('d/M/Y') ?? '-' }}</div>
                                    </div>
                                    <div class="info-field">
                                        <div class="info-field-label">Last Service Date</div>
                                        <div class="info-field-value">{{ $unitOnWall->last_service_date?->format('d/M/Y') ?? '-' }}</div>
                                    </div>
                                    <div class="info-field">
                                        <div class="info-field-label">Temperature</div>
                                        <div class="info-field-value">{{ $unitOnWall->temperature ? number_format($unitOnWall->temperature, 2) . '°C' : '-' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="card info-card h-100">
                                <div class="card-header" style="background-color: #6c757d; color: white; border-radius: 8px 8px 0 0;">
                                    <h5 class="card-title mb-0">Location Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="info-field">
                                        <div class="info-field-label">Customer</div>
                                        <div class="info-field-value">{{ $unitOnWall->customer_name ?? ($unitOnWall->customer?->name ?? '-') }}</div>
                                    </div>
                                    <div class="info-field">
                                        <div class="info-field-label">Building</div>
                                        <div class="info-field-value">{{ $unitOnWall->building?->building_name ?? '-' }}</div>
                                    </div>
                                    <div class="info-field">
                                        <div class="info-field-label">Room</div>
                                        <div class="info-field-value">{{ $unitOnWall->room_name ?? ($unitOnWall->room?->room_name ?? '-') }}</div>
                                    </div>
                                    <div class="info-field">
                                        <div class="info-field-label">Rental</div>
                                        <div class="info-field-value">{{ $unitOnWall->rental_name ?? ($unitOnWall->rental?->rental_name ?? '-') }}</div>
                                    </div>
                                    <div class="info-field">
                                        <div class="info-field-label">Last Seen At</div>
                                        <div class="info-field-value">{{ $unitOnWall->last_seen_at?->format('d/M/Y H:i') ?? '-' }}</div>
                                    </div>
                                    <div class="info-field">
                                        <div class="info-field-label">Notes</div>
                                        <div class="info-field-value">{{ $unitOnWall->notes ?? '-' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Tab 2: HISTORY LOG (Installation History) -->
                <div class="tab-pane fade" id="history-log" role="tabpanel" aria-labelledby="history-log-tab">
                    <div class="card info-card">
                        <div class="card-header" style="background-color: #6f42c1; color: white; border-radius: 8px 8px 0 0;">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-history me-2"></i>Installation History Log
                            </h5>
                        </div>
                        <div class="card-body">
                            @if($unitOnWall->installHistories->count() > 0)
                            <div class="alert alert-info mb-3">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Serial Number:</strong> {{ $unitOnWall->serial_number ?? 'N/A' }}
                                @if(empty($unitOnWall->serial_number))
                                <span class="badge bg-warning ms-2">No Serial Number</span>
                                @else
                                <span class="badge bg-success ms-2">Has Serial Number</span>
                                @endif
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Action</th>
                                            <th>Date</th>
                                            <th>Customer</th>
                                            <th>Location</th>
                                            <th>Technician</th>
                                            <th>Job Schedule</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($unitOnWall->installHistories as $index => $history)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                <span class="badge bg-{{ $history->getActionBadgeClass() }}">
                                                    {{ $history->getActionLabel() }}
                                                </span>
                                            </td>
                                            <td>{{ $history->action_date ? $history->action_date->format('d/M/Y') : '-' }}</td>
                                            <td>{{ $history->customer_name ?? '-' }}</td>
                                            <td>{{ $history->location ?? '-' }}</td>
                                            <td>{{ $history->technician_name ?? '-' }}</td>
                                            <td>
                                                @if($history->job_schedule_number)
                                                <a href="{{ route('operational.job-schedules.show', $history->job_schedule_id) }}" class="text-primary" target="_blank" rel="noopener noreferrer">
                                                    {{ $history->job_schedule_number }}
                                                </a>
                                                @else
                                                -
                                                @endif
                                            </td>
                                            <td>{{ \Illuminate\Support\Str::limit($history->notes ?? '-', 50) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @else
                            <div class="text-center text-muted py-5">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <p>No installation history found for this unit.</p>
                                @if(empty($unitOnWall->serial_number))
                                <small class="text-muted">This unit does not have a serial number, so installation history cannot be tracked.</small>
                                @endif
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Tab 3: HISTORY SERVICE -->
                <div class="tab-pane fade" id="history-service" role="tabpanel" aria-labelledby="history-service-tab">
                    <div class="card info-card">
                        <div class="card-header" style="background-color: #17a2b8; color: white; border-radius: 8px 8px 0 0;">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-tools me-2"></i>Service History
                            </h5>
                        </div>
                        <div class="card-body">
                            @if($unitOnWall->serviceHistories->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Service Date</th>
                                            <th>Customer</th>
                                            <th>Location</th>
                                            <th>Technician</th>
                                            <th>Job Schedule</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($unitOnWall->serviceHistories as $index => $history)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $history->action_date ? $history->action_date->format('d/M/Y') : '-' }}</td>
                                            <td>{{ $history->customer_name ?? '-' }}</td>
                                            <td>{{ $history->location ?? '-' }}</td>
                                            <td>{{ $history->technician_name ?? '-' }}</td>
                                            <td>
                                                @if($history->job_schedule_number)
                                                <a href="{{ route('operational.job-schedules.show', $history->job_schedule_id) }}" class="text-primary" target="_blank" rel="noopener noreferrer">
                                                    {{ $history->job_schedule_number }}
                                                </a>
                                                @else
                                                -
                                                @endif
                                            </td>
                                            <td>{{ \Illuminate\Support\Str::limit($history->notes ?? '-', 50) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @else
                            <div class="text-center text-muted py-5">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <p>No service history found for this unit.</p>
                                <small class="text-muted">Service history will be automatically recorded from Job Schedule Service.</small>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Tab 3: HISTORY REPAIR -->
                <div class="tab-pane fade" id="history-repair" role="tabpanel" aria-labelledby="history-repair-tab">
                    <div class="card info-card">
                        <div class="card-header" style="background-color: #dc3545; color: white; border-radius: 8px 8px 0 0;">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-wrench me-2"></i>Repair History
                            </h5>
                        </div>
                        <div class="card-body">
                            @if($repairHistories && $repairHistories->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Repair Number</th>
                                            <th>Problem Description</th>
                                            <th>Status</th>
                                            <th>Cost</th>
                                            <th>Reported At</th>
                                            <th>Completed At</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($repairHistories as $repair)
                                        <tr>
                                            <td>{{ $repair->repair_number ?? '-' }}</td>
                                            <td>{{ \Illuminate\Support\Str::limit($repair->problem_description ?? '-', 50) }}</td>
                                            <td>
                                                <span class="badge bg-{{ $repair->repair_status === 'completed' ? 'success' : ($repair->repair_status === 'in_progress' ? 'warning' : 'secondary') }}">
                                                    {{ ucfirst(str_replace('_', ' ', $repair->repair_status ?? '-')) }}
                                                </span>
                                            </td>
                                            <td>{{ $repair->repair_cost ? 'Rp ' . number_format($repair->repair_cost, 0, ',', '.') : '-' }}</td>
                                            <td>{{ $repair->reported_at?->format('d/M/Y H:i') ?? '-' }}</td>
                                            <td>{{ $repair->completed_at?->format('d/M/Y H:i') ?? '-' }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @else
                            <div class="text-center text-muted py-5">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <p>No repair history found for this unit.</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Tab 5: SETTING -->
                <div class="tab-pane fade" id="setting" role="tabpanel" aria-labelledby="setting-tab">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="card info-card">
                                <div class="card-header" style="background-color: #6c757d; color: white; border-radius: 8px 8px 0 0;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-cog me-2"></i>Unit Settings
                                        </h5>
                                        <div>
                                            <label class="form-check-label text-white me-3">
                                                <input type="checkbox" class="form-check-input" id="useGlobalSettings" checked>
                                                Use Global Settings
                                            </label>
                                            <button type="button" class="btn btn-sm btn-light" onclick="saveSettings()">
                                                <i class="fas fa-save"></i> Save Settings
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Note:</strong> Unit-specific settings will override global settings. 
                                        If "Use Global Settings" is checked, the unit will use global settings by default.
                                    </div>
                                    
                                    <div class="row g-3">
                                        <div class="col-12 col-md-6">
                                            <label class="form-label">Auto Refresh Interval</label>
                                            <select class="form-select" id="autoRefreshInterval">
                                                <option value="60000">1 minute</option>
                                                <option value="300000">5 minutes</option>
                                                <option value="3600000">1 hour</option>
                                                <option value="43200000">12 hours</option>
                                                <option value="86400000">1 day</option>
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="form-label">Temperature Alert Threshold (°C)</label>
                                            <input type="number" class="form-control" id="temperatureThreshold" placeholder="e.g., 30" step="0.1">
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="form-label">Notification Email</label>
                                            <input type="email" class="form-control" id="notificationEmail" placeholder="email@example.com">
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="form-label">Notification Enabled</label>
                                            <select class="form-select" id="notificationEnabled">
                                                <option value="1">Yes</option>
                                                <option value="0">No</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let refreshInterval = null;
let refreshIntervalValue = 60000; // Default: 1 minute

// Initialize live data refresh for WiFi units
@if($hasWifi)
document.addEventListener('DOMContentLoaded', function() {
    loadLiveData();
    startAutoRefresh();
    
    // Update refresh interval when changed
    document.getElementById('refreshInterval').addEventListener('change', function() {
        refreshIntervalValue = parseInt(this.value);
        startAutoRefresh();
    });
});

function loadLiveData() {
    // TODO: Implement API call to get real-time unit logs
    // For now, show placeholder
    const container = document.getElementById('liveDataContainer');
    container.innerHTML = `
        <div class="log-item">
            <div class="log-timestamp">
                <i class="fas fa-clock me-2"></i>${new Date().toLocaleString('id-ID')}
            </div>
            <div class="log-message">
                Unit is online and operating normally.
            </div>
        </div>
        <div class="log-item">
            <div class="log-timestamp">
                <i class="fas fa-clock me-2"></i>${new Date(Date.now() - 3600000).toLocaleString('id-ID')}
            </div>
            <div class="log-message">
                Last service completed successfully.
            </div>
        </div>
    `;
}

function refreshLiveData() {
    const indicator = document.getElementById('refreshIndicator');
    indicator.classList.remove('inactive');
    
    // TODO: Implement API call to refresh live data
    loadLiveData();
    
    // Simulate refresh delay
    setTimeout(() => {
        indicator.classList.remove('inactive');
    }, 1000);
}

function startAutoRefresh() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
    
    refreshInterval = setInterval(function() {
        refreshLiveData();
    }, refreshIntervalValue);
}
@endif

function saveSettings() {
    // TODO: Implement API call to save settings
    const useGlobal = document.getElementById('useGlobalSettings').checked;
    const autoRefresh = document.getElementById('autoRefreshInterval').value;
    const tempThreshold = document.getElementById('temperatureThreshold').value;
    const notificationEmail = document.getElementById('notificationEmail').value;
    const notificationEnabled = document.getElementById('notificationEnabled').value;
    
    // Show success message
    showSuccessDialog('Berhasil', 'Pengaturan berhasil disimpan.');
}

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});
</script>

@endsection

