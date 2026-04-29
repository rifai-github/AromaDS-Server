@extends('layouts.app')

@section('title', 'Maintenance Schedules')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-tools mr-2"></i>
                        Maintenance Schedules
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('maintenance.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Add New Schedule
                        </a>
                        <a href="{{ route('maintenance.dashboard') }}" class="btn btn-info btn-sm">
                            <i class="fas fa-chart-bar"></i> Dashboard
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    <!-- Filters -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <select class="form-control" id="statusFilter">
                                <option value="">All Status</option>
                                <option value="scheduled">Scheduled</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="overdue">Overdue</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-control" id="typeFilter">
                                <option value="">All Types</option>
                                <option value="preventive">Preventive</option>
                                <option value="corrective">Corrective</option>
                                <option value="predictive">Predictive</option>
                                <option value="emergency">Emergency</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-control" id="priorityFilter">
                                <option value="">All Priorities</option>
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" class="form-control" id="searchInput" placeholder="Search...">
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="maintenanceTable">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Scheduled Date</th>
                                    <th>Assigned To</th>
                                    <th>Duration</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($schedules as $schedule)
                                    <tr>
                                        <td>
                                            <strong>{{ $schedule->title }}</strong>
                                            @if($schedule->description)
                                                <br><small class="text-muted">{{ Str::limit($schedule->description, 50) }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-info">
                                                {{ ucfirst($schedule->maintenance_type) }}
                                            </span>
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
                                        <td>
                                            {{ $schedule->scheduled_date->format('M d, Y') }}
                                            @if($schedule->scheduled_time)
                                                <br><small class="text-muted">{{ $schedule->scheduled_time }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($schedule->assignedTo)
                                                {{ $schedule->assignedTo->name }}
                                            @else
                                                <span class="text-muted">Unassigned</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($schedule->duration_formatted)
                                                {{ $schedule->duration_formatted }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('maintenance.show', $schedule) }}" class="btn btn-info btn-sm" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('maintenance.edit', $schedule) }}" class="btn btn-warning btn-sm" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                
                                                @if($schedule->status === 'scheduled')
                                                    <form action="{{ route('maintenance.start', $schedule) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-success btn-sm" title="Start">
                                                            <i class="fas fa-play"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                                
                                                @if($schedule->status === 'in_progress')
                                                    <form action="{{ route('maintenance.complete', $schedule) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-success btn-sm" title="Complete">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                                
                                                @if(in_array($schedule->status, ['scheduled', 'in_progress']))
                                                    <form action="{{ route('maintenance.cancel', $schedule) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-danger btn-sm" title="Cancel" onclick="return confirm('Are you sure?')">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                                
                                                <a href="{{ route('maintenance.records', $schedule) }}" class="btn btn-secondary btn-sm" title="Records">
                                                    <i class="fas fa-list"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">No maintenance schedules found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center">
                        {{ $schedules->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Filter functionality
    $('#statusFilter, #typeFilter, #priorityFilter').on('change', function() {
        filterTable();
    });

    $('#searchInput').on('keyup', function() {
        filterTable();
    });

    function filterTable() {
        var status = $('#statusFilter').val();
        var type = $('#typeFilter').val();
        var priority = $('#priorityFilter').val();
        var search = $('#searchInput').val().toLowerCase();

        $('#maintenanceTable tbody tr').each(function() {
            var row = $(this);
            var statusText = row.find('td:nth-child(4) .badge').text().toLowerCase();
            var typeText = row.find('td:nth-child(2) .badge').text().toLowerCase();
            var priorityText = row.find('td:nth-child(3) .badge').text().toLowerCase();
            var searchText = row.text().toLowerCase();

            var statusMatch = !status || statusText.includes(status);
            var typeMatch = !type || typeText.includes(type);
            var priorityMatch = !priority || priorityText.includes(priority);
            var searchMatch = !search || searchText.includes(search);

            if (statusMatch && typeMatch && priorityMatch && searchMatch) {
                row.show();
            } else {
                row.hide();
            }
        });
    }
});
</script>
@endpush
