@extends('layouts.app')

@section('title', 'Team Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fas fa-users"></i> Team Details: {{ $team->name }}
                    </h4>
                    <div class="card-tools">
                        <a href="{{ route('operational.teams.edit', $team->id) }}" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="{{ route('operational.teams.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <!-- Basic Information -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">
                                        <i class="fas fa-info-circle"></i> Basic Information
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <table class="table table-borderless">
                                                <tr>
                                                    <td width="40%"><strong>Team ID:</strong></td>
                                                    <td>{{ $team->team_id ?? 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Name:</strong></td>
                                                    <td>{{ $team->name }}</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Code:</strong></td>
                                                    <td>{{ $team->code ?? 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Status:</strong></td>
                                                    <td>
                                                        @if($team->status == 'active')
                                                            <span class="badge badge-success">Active</span>
                                                        @else
                                                            <span class="badge badge-secondary">Inactive</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Branch:</strong></td>
                                                    <td>{{ $team->branch->name ?? 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Department:</strong></td>
                                                    <td>{{ $team->department->name ?? 'N/A' }}</td>
                                                </tr>
                                            </table>
                                        </div>
                                        <div class="col-md-6">
                                            <table class="table table-borderless">
                                                <tr>
                                                    <td width="40%"><strong>Created:</strong></td>
                                                    <td>{{ $team->created_at->format('d/mmm/Y H:i') }}</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Updated:</strong></td>
                                                    <td>{{ $team->updated_at->format('d/mmm/Y H:i') }}</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Created By:</strong></td>
                                                    <td>{{ $team->created_by ?? 'System' }}</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Updated By:</strong></td>
                                                    <td>{{ $team->updated_by ?? 'System' }}</td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Team Leader Information -->
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h5 class="card-title">
                                        <i class="fas fa-user-tie"></i> Team Leader
                                    </h5>
                                </div>
                                <div class="card-body">
                                    @if($team->leader)
                                        <div class="row">
                                            <div class="col-md-6">
                                                <table class="table table-borderless">
                                                    <tr>
                                                        <td width="40%"><strong>Name:</strong></td>
                                                        <td>{{ $team->leader->name }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Email:</strong></td>
                                                        <td>{{ $team->leader->email }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Phone:</strong></td>
                                                        <td>{{ $team->leader->phone ?? 'N/A' }}</td>
                                                    </tr>
                                                </table>
                                            </div>
                                            <div class="col-md-6">
                                                <table class="table table-borderless">
                                                    <tr>
                                                        <td width="40%"><strong>Department:</strong></td>
                                                        <td>{{ $team->leader->department->name ?? 'N/A' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Position:</strong></td>
                                                        <td>{{ $team->leader->position ?? 'N/A' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Status:</strong></td>
                                                        <td>
                                                            @if($team->leader->status == 'active')
                                                                <span class="badge badge-success">Active</span>
                                                            @else
                                                                <span class="badge badge-secondary">Inactive</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                    @else
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle"></i> No team leader assigned yet.
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Team Members -->
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h5 class="card-title">
                                        <i class="fas fa-users"></i> Team Members ({{ $team->members->count() }})
                                    </h5>
                                </div>
                                <div class="card-body">
                                    @if($team->members->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped">
                                                <thead>
                                                    <tr>
                                                        <th data-column="user.name">Name</th>
                                                        <th data-column="user.email">Email</th>
                                                        <th data-column="user.phone">Phone</th>
                                                        <th data-column="user.department.department_name">Department</th>
                                                        <th data-column="user.position.option_name">Position</th>
                                                        <th data-column="status">Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($team->members as $member)
                                                    <tr>
                                                        <td>
                                                            <strong>{{ $member->name }}</strong>
                                                            @if($member->id == $team->leader_id)
                                                                <span class="badge badge-primary">Leader</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ $member->email }}</td>
                                                        <td>{{ $member->phone ?? 'N/A' }}</td>
                                                        <td>{{ $member->department->name ?? 'N/A' }}</td>
                                                        <td>{{ $member->position ?? 'N/A' }}</td>
                                                        <td>
                                                            @if($member->status == 'active')
                                                                <span class="badge badge-success">Active</span>
                                                            @else
                                                                <span class="badge badge-secondary">Inactive</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle"></i> No team members assigned yet.
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Description & Notes -->
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h5 class="card-title">
                                        <i class="fas fa-sticky-note"></i> Description & Notes
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>Description:</strong>
                                            <p class="mt-2">{{ $team->description ?? 'No description available.' }}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Notes:</strong>
                                            <p class="mt-2">{{ $team->notes ?? 'No notes available.' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <!-- Quick Actions -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">
                                        <i class="fas fa-cogs"></i> Quick Actions
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <a href="{{ route('operational.teams.edit', $team->id) }}" class="btn btn-primary">
                                            <i class="fas fa-edit"></i> Edit Team
                                        </a>
                                        <a href="#" class="btn btn-info" onclick="viewMembers({{ $team->id }})">
                                            <i class="fas fa-users"></i> Manage Members
                                        </a>
                                        <a href="#" class="btn btn-success" onclick="assignMembers({{ $team->id }})">
                                            <i class="fas fa-user-plus"></i> Assign Members
                                        </a>
                                        <a href="#" class="btn btn-warning" onclick="printTeam({{ $team->id }})">
                                            <i class="fas fa-print"></i> Print Details
                                        </a>
                                        <a href="#" class="btn btn-secondary" onclick="exportTeam({{ $team->id }}, 'excel')">
                                            <i class="fas fa-file-excel"></i> Export Excel
                                        </a>
                                        <a href="#" class="btn btn-danger" onclick="exportTeam({{ $team->id }}, 'pdf')">
                                            <i class="fas fa-file-pdf"></i> Export PDF
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Statistics -->
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h5 class="card-title">
                                        <i class="fas fa-chart-bar"></i> Statistics
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <div class="info-box">
                                                <span class="info-box-icon bg-info">
                                                    <i class="fas fa-users"></i>
                                                </span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">Total Members</span>
                                                    <span class="info-box-number">{{ $team->members->count() }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="info-box">
                                                <span class="info-box-icon bg-success">
                                                    <i class="fas fa-user-check"></i>
                                                </span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">Active Members</span>
                                                    <span class="info-box-number">{{ $team->members->where('status', 'active')->count() }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Related Information -->
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h5 class="card-title">
                                        <i class="fas fa-link"></i> Related Information
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Job Schedules
                                            <span class="badge badge-primary badge-pill">{{ $team->job_schedules_count ?? 0 }}</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Assigned Tasks
                                            <span class="badge badge-success badge-pill">{{ $team->assigned_tasks_count ?? 0 }}</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Completed Tasks
                                            <span class="badge badge-info badge-pill">{{ $team->completed_tasks_count ?? 0 }}</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Team Performance -->
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h5 class="card-title">
                                        <i class="fas fa-tachometer-alt"></i> Team Performance
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <div class="info-box">
                                                <span class="info-box-icon bg-warning">
                                                    <i class="fas fa-clock"></i>
                                                </span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">Avg. Response</span>
                                                    <span class="info-box-number">2.5h</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="info-box">
                                                <span class="info-box-icon bg-success">
                                                    <i class="fas fa-check-circle"></i>
                                                </span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">Success Rate</span>
                                                    <span class="info-box-number">95%</span>
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
<script>
function viewMembers(id) {
    window.location.href = '{{ url("operational/teams") }}/' + id + '/members';
}

function assignMembers(id) {
    window.location.href = '{{ url("operational/teams") }}/' + id + '/assign-members';
}

function printTeam(id) {
    window.open('{{ url("operational/teams") }}/' + id + '/print', '_blank');
}

function exportTeam(id, format) {
    window.location.href = '{{ url("operational/teams") }}/' + id + '/export/' + format;
}
</script>
@endpush
