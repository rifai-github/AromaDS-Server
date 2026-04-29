@extends('layouts.app')

@section('title', 'Technician Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">Technician Dashboard</h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="#">Operational</a></li>
                    <li class="breadcrumb-item active">Technician Dashboard</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fa fa-users fa-2x text-primary"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h4 class="mb-0">{{ $stats['total_technicians'] ?? 0 }}</h4>
                            <p class="text-muted mb-0">Total Technicians</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fa fa-tasks fa-2x text-warning"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h4 class="mb-0">{{ $stats['active_jobs'] ?? 0 }}</h4>
                            <p class="text-muted mb-0">Active Jobs</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fa fa-check-circle fa-2x text-success"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h4 class="mb-0">{{ $stats['completed_today'] ?? 0 }}</h4>
                            <p class="text-muted mb-0">Completed Today</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fa fa-exclamation-triangle fa-2x text-danger"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h4 class="mb-0">{{ $stats['pending_jobs'] ?? 0 }}</h4>
                            <p class="text-muted mb-0">Pending Jobs</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Technicians -->
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Active Technicians</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Technician</th>
                                    <th>Current Job</th>
                                    <th>Location</th>
                                    <th>Status</th>
                                    <th>Last Update</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($activeTechnicians ?? [] as $technician)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm me-3">
                                                <img src="{{ asset('assets/img/avatar.png') }}" alt="Avatar">
                                            </div>
                                            <div>
                                                <h6 class="mb-0">{{ $technician->name }}</h6>
                                                <small class="text-muted">{{ $technician->nik }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($technician->currentJob)
                                            <strong>{{ $technician->currentJob->job_number }}</strong><br>
                                            <small class="text-muted">{{ $technician->currentJob->company_name }}</small>
                                        @else
                                            <span class="text-muted">No active job</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($technician->currentJob && $technician->currentJob->technician_location)
                                            <i class="fa fa-map-marker text-primary"></i>
                                            {{ Str::limit($technician->currentJob->technician_location, 30) }}
                                        @else
                                            <span class="text-muted">Location not available</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($technician->currentJob)
                                            @if($technician->currentJob->work_status === 'in_progress')
                                                <span class="badge bg-warning">In Progress</span>
                                            @elseif($technician->currentJob->work_status === 'completed')
                                                <span class="badge bg-success">Completed</span>
                                            @else
                                                <span class="badge bg-secondary">Pending</span>
                                            @endif
                                        @else
                                            <span class="badge bg-light">Idle</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($technician->currentJob && $technician->currentJob->location_updated_at)
                                            {{ $technician->currentJob->location_updated_at->diffForHumans() }}
                                        @else
                                            <span class="text-muted">Never</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($technician->currentJob)
                                            <a href="{{ route('operational.job-schedules.show', $technician->currentJob->id) }}" 
                                               class="btn btn-sm btn-primary">
                                                <i class="fa fa-eye"></i> View
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center">No active technicians found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Recent Activities</h4>
                </div>
                <div class="card-body">
                    <div class="activity-feed">
                        @forelse($recentActivities ?? [] as $activity)
                        <div class="activity-item">
                            <div class="activity-content">
                                <div class="activity-header">
                                    <strong>{{ $activity->technician->name }}</strong>
                                    <small class="text-muted">{{ $activity->activity_time->diffForHumans() }}</small>
                                </div>
                                <div class="activity-body">
                                    <p class="mb-1">{{ $activity->activity_type_label }}</p>
                                    @if($activity->jobSchedule)
                                        <small class="text-muted">
                                            Job: {{ $activity->jobSchedule->job_number }}
                                        </small>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="text-center text-muted">
                            <p>No recent activities</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Job Assignments -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Today's Job Assignments</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Job Number</th>
                                    <th>Technician</th>
                                    <th>Company</th>
                                    <th>Building</th>
                                    <th>Schedule Time</th>
                                    <th>Status</th>
                                    <th>Progress</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($todayJobs ?? [] as $job)
                                <tr>
                                    <td>
                                        <strong>{{ $job->job_number }}</strong><br>
                                        <small class="text-muted">{{ ucfirst($job->type) }}</small>
                                    </td>
                                    <td>
                                        @if($job->assignedTechnician)
                                            {{ $job->assignedTechnician->name }}
                                        @else
                                            <span class="text-muted">Not assigned</span>
                                        @endif
                                    </td>
                                    <td>{{ $job->company_name }}</td>
                                    <td>{{ $job->building_name }}</td>
                                    <td>
                                        {{ $job->schedule_date->format('d/mmm/Y') }}<br>
                                        <small class="text-muted">{{ $job->expected_date->format('d/mmm/Y') }}</small>
                                    </td>
                                    <td>
                                        @if($job->work_status === 'pending')
                                            <span class="badge bg-secondary">Pending</span>
                                        @elseif($job->work_status === 'in_progress')
                                            <span class="badge bg-warning">In Progress</span>
                                        @elseif($job->work_status === 'completed')
                                            <span class="badge bg-success">Completed</span>
                                        @else
                                            <span class="badge bg-danger">Cancelled</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($job->work_status === 'in_progress')
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-warning" style="width: 50%">50%</div>
                                            </div>
                                        @elseif($job->work_status === 'completed')
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-success" style="width: 100%">100%</div>
                                            </div>
                                        @else
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-secondary" style="width: 0%">0%</div>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('operational.job-schedules.show', $job->id) }}" 
                                           class="btn btn-sm btn-primary">
                                            <i class="fa fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center">No jobs scheduled for today</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.activity-feed {
    max-height: 400px;
    overflow-y: auto;
}

.activity-item {
    padding: 10px 0;
    border-bottom: 1px solid #eee;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 5px;
}

.activity-body p {
    margin-bottom: 5px;
}
</style>
@endsection
