@extends('layouts.app')

@section('title', 'Maintenance Dashboard')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-bar mr-2"></i>
                        Maintenance Dashboard
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('maintenance.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Statistics Cards -->
                    <div class="row">
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3>{{ $stats['total_schedules'] }}</h3>
                                    <p>Total Schedules</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-tools"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3>{{ $stats['scheduled'] }}</h3>
                                    <p>Scheduled</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3>{{ $stats['completed'] }}</h3>
                                    <p>Completed</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-danger">
                                <div class="inner">
                                    <h3>{{ $stats['overdue'] }}</h3>
                                    <p>Overdue</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Maintenance by Type</h3>
                                </div>
                                <div class="card-body">
                                    <canvas id="typeChart" width="400" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Maintenance by Priority</h3>
                                </div>
                                <div class="card-body">
                                    <canvas id="priorityChart" width="400" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activities -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Recent Schedules</h3>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Title</th>
                                                    <th>Type</th>
                                                    <th>Status</th>
                                                    <th>Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($recent_schedules as $schedule)
                                                    <tr>
                                                        <td>
                                                            <a href="{{ route('maintenance.show', $schedule) }}">
                                                                {{ Str::limit($schedule->title, 30) }}
                                                            </a>
                                                        </td>
                                                        <td>
                                                            <span class="badge badge-info">
                                                                {{ ucfirst($schedule->maintenance_type) }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            @php
                                                                $statusColors = [
                                                                    'scheduled' => 'primary',
                                                                    'in_progress' => 'warning',
                                                                    'completed' => 'success',
                                                                    'cancelled' => 'secondary',
                                                                    'overdue' => 'danger'
                                                                ];
                                                            @endphp
                                                            <span class="badge badge-{{ $statusColors[$schedule->status] }}">
                                                                {{ ucfirst(str_replace('_', ' ', $schedule->status)) }}
                                                            </span>
                                                        </td>
                                                        <td>{{ $schedule->scheduled_date->format('M d') }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="4" class="text-center">No recent schedules</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Upcoming Schedules</h3>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Title</th>
                                                    <th>Priority</th>
                                                    <th>Assigned To</th>
                                                    <th>Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($upcoming_schedules as $schedule)
                                                    <tr>
                                                        <td>
                                                            <a href="{{ route('maintenance.show', $schedule) }}">
                                                                {{ Str::limit($schedule->title, 30) }}
                                                            </a>
                                                        </td>
                                                        <td>
                                                            @php
                                                                $priorityColors = [
                                                                    'low' => 'success',
                                                                    'medium' => 'warning',
                                                                    'high' => 'danger',
                                                                    'critical' => 'dark'
                                                                ];
                                                            @endphp
                                                            <span class="badge badge-{{ $priorityColors[$schedule->priority] }}">
                                                                {{ ucfirst($schedule->priority) }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            @if($schedule->assignedTo)
                                                                {{ $schedule->assignedTo->name }}
                                                            @else
                                                                <span class="text-muted">Unassigned</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ $schedule->scheduled_date->format('M d') }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="4" class="text-center">No upcoming schedules</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Performance Metrics -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Performance Metrics</h3>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="info-box">
                                                <span class="info-box-icon bg-success">
                                                    <i class="fas fa-percentage"></i>
                                                </span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">Completion Rate</span>
                                                    <span class="info-box-number">{{ $stats['completion_rate'] }}%</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="info-box">
                                                <span class="info-box-icon bg-info">
                                                    <i class="fas fa-clock"></i>
                                                </span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">Avg. Duration</span>
                                                    <span class="info-box-number">{{ $stats['avg_duration'] }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="info-box">
                                                <span class="info-box-icon bg-warning">
                                                    <i class="fas fa-exclamation-triangle"></i>
                                                </span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">Overdue Rate</span>
                                                    <span class="info-box-number">{{ $stats['overdue_rate'] }}%</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="info-box">
                                                <span class="info-box-icon bg-primary">
                                                    <i class="fas fa-dollar-sign"></i>
                                                </span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">Total Cost</span>
                                                    <span class="info-box-number">${{ number_format($stats['total_cost'], 2) }}</span>
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
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    // Type Chart
    var typeCtx = document.getElementById('typeChart').getContext('2d');
    var typeChart = new Chart(typeCtx, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($chart_data['type_labels']) !!},
            datasets: [{
                data: {!! json_encode($chart_data['type_data']) !!},
                backgroundColor: [
                    '#007bff',
                    '#28a745',
                    '#ffc107',
                    '#dc3545'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Priority Chart
    var priorityCtx = document.getElementById('priorityChart').getContext('2d');
    var priorityChart = new Chart(priorityCtx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($chart_data['priority_labels']) !!},
            datasets: [{
                label: 'Count',
                data: {!! json_encode($chart_data['priority_data']) !!},
                backgroundColor: [
                    '#28a745',
                    '#ffc107',
                    '#fd7e14',
                    '#dc3545'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});
</script>
@endpush
