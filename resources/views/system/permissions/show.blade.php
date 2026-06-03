@extends('layouts.app')

@section('title', 'Permission Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fas fa-key"></i> Permission Details: {{ $permission->name }}
                    </h4>
                    <div class="card-tools">
                        <a href="{{ route('system.permissions.edit', $permission->id) }}" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="{{ route('system.permissions.index') }}" class="btn btn-secondary">
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
                                                    <td width="40%"><strong>Permission ID:</strong></td>
                                                    <td>{{ $permission->id }}</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Name:</strong></td>
                                                    <td><code>{{ $permission->name }}</code></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Guard Name:</strong></td>
                                                    <td>
                                                        @if($permission->guard_name == 'web')
                                                            <span class="badge badge-primary">Web</span>
                                                        @else
                                                            <span class="badge badge-info">API</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Module:</strong></td>
                                                    <td>
                                                        @if($permission->module)
                                                            <span class="badge badge-secondary">{{ ucfirst($permission->module) }}</span>
                                                        @else
                                                            <span class="text-muted">N/A</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Action:</strong></td>
                                                    <td>
                                                        @if($permission->action)
                                                            <span class="badge badge-warning">{{ ucfirst($permission->action) }}</span>
                                                        @else
                                                            <span class="text-muted">N/A</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                        <div class="col-md-6">
                                            <table class="table table-borderless">
                                                <tr>
                                                    <td width="40%"><strong>Status:</strong></td>
                                                    <td>
                                                        @if($permission->is_active)
                                                            <span class="badge badge-success">Active</span>
                                                        @else
                                                            <span class="badge badge-secondary">Inactive</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><strong>System Permission:</strong></td>
                                                    <td>
                                                        @if($permission->is_system)
                                                            <span class="badge badge-danger">Yes</span>
                                                        @else
                                                            <span class="badge badge-light">No</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Created:</strong></td>
                                                    <td>{{ $permission->created_at->format('d/M/Y H:i') }}</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Updated:</strong></td>
                                                    <td>{{ $permission->updated_at->format('d/M/Y H:i') }}</td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h5 class="card-title">
                                        <i class="fas fa-sticky-note"></i> Description
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <p>{{ $permission->description ?? 'No description available.' }}</p>
                                </div>
                            </div>

                            <!-- Roles with this Permission -->
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h5 class="card-title">
                                        <i class="fas fa-users"></i> Roles with this Permission ({{ $permission->roles->count() }})
                                    </h5>
                                </div>
                                <div class="card-body">
                                    @if($permission->roles->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Role Name</th>
                                                        <th>Guard Name</th>
                                                        <th>Users Count</th>
                                                        <th>Created</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($permission->roles as $role)
                                                    <tr>
                                                        <td>
                                                            <strong>{{ $role->name }}</strong>
                                                            @if($role->description)
                                                                <br><small class="text-muted">{{ $role->description }}</small>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if($role->guard_name == 'web')
                                                                <span class="badge badge-primary">Web</span>
                                                            @else
                                                                <span class="badge badge-info">API</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <span class="badge badge-info">{{ $role->users_count ?? 0 }} users</span>
                                                        </td>
                                                        <td>
                                                            <small class="text-muted">{{ $role->created_at->format('d/M/Y') }}</small>
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle"></i> No roles have been assigned this permission yet.
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Users with this Permission -->
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h5 class="card-title">
                                        <i class="fas fa-user"></i> Users with this Permission ({{ $permission->users->count() }})
                                    </h5>
                                </div>
                                <div class="card-body">
                                    @if($permission->users->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>Email</th>
                                                        <th>Department</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($permission->users as $user)
                                                    <tr>
                                                        <td>
                                                            <strong>{{ $user->name }}</strong>
                                                            @if($user->position)
                                                                <br><small class="text-muted">{{ $user->position }}</small>
                                                            @endif
                                                        </td>
                                                        <td>{{ $user->email }}</td>
                                                        <td>{{ $user->department->name ?? 'N/A' }}</td>
                                                        <td>
                                                            @if($user->status == 'active')
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
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle"></i> No users have been assigned this permission directly.
                                        </div>
                                    @endif
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
                                        <a href="{{ route('system.permissions.edit', $permission->id) }}" class="btn btn-primary">
                                            <i class="fas fa-edit"></i> Edit Permission
                                        </a>
                                        <a href="#" class="btn btn-info" onclick="assignToRoles({{ $permission->id }})">
                                            <i class="fas fa-users"></i> Assign to Roles
                                        </a>
                                        <a href="#" class="btn btn-success" onclick="assignToUsers({{ $permission->id }})">
                                            <i class="fas fa-user-plus"></i> Assign to Users
                                        </a>
                                        <a href="#" class="btn btn-warning" onclick="printPermission({{ $permission->id }})">
                                            <i class="fas fa-print"></i> Print Details
                                        </a>
                                        <a href="#" class="btn btn-secondary" onclick="exportPermission({{ $permission->id }}, 'excel')">
                                            <i class="fas fa-file-excel"></i> Export Excel
                                        </a>
                                        <a href="#" class="btn btn-danger" onclick="exportPermission({{ $permission->id }}, 'pdf')">
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
                                                    <span class="info-box-text">Roles</span>
                                                    <span class="info-box-number">{{ $permission->roles->count() }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="info-box">
                                                <span class="info-box-icon bg-success">
                                                    <i class="fas fa-user"></i>
                                                </span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">Users</span>
                                                    <span class="info-box-number">{{ $permission->users->count() }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Permission Usage -->
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h5 class="card-title">
                                        <i class="fas fa-chart-line"></i> Usage Statistics
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Total Usage
                                            <span class="badge badge-primary badge-pill">{{ $permission->usage_count ?? 0 }}</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            This Month
                                            <span class="badge badge-success badge-pill">{{ $permission->monthly_usage_count ?? 0 }}</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            This Week
                                            <span class="badge badge-info badge-pill">{{ $permission->weekly_usage_count ?? 0 }}</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Today
                                            <span class="badge badge-warning badge-pill">{{ $permission->daily_usage_count ?? 0 }}</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Related Permissions -->
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h5 class="card-title">
                                        <i class="fas fa-link"></i> Related Permissions
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <ul class="list-group list-group-flush">
                                        @if($permission->module)
                                            <li class="list-group-item">
                                                <strong>Module:</strong> {{ ucfirst($permission->module) }}
                                            </li>
                                        @endif
                                        @if($permission->action)
                                            <li class="list-group-item">
                                                <strong>Action:</strong> {{ ucfirst($permission->action) }}
                                            </li>
                                        @endif
                                        <li class="list-group-item">
                                            <strong>Guard:</strong> {{ ucfirst($permission->guard_name) }}
                                        </li>
                                    </ul>
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
function assignToRoles(id) {
    window.location.href = '{{ url("system/permissions") }}/' + id + '/assign-roles';
}

function assignToUsers(id) {
    window.location.href = '{{ url("system/permissions") }}/' + id + '/assign-users';
}

function printPermission(id) {
    window.open('{{ url("system/permissions") }}/' + id + '/print', '_blank');
}

function exportPermission(id, format) {
    window.location.href = '{{ url("system/permissions") }}/' + id + '/export/' + format;
}
</script>
@endpush
