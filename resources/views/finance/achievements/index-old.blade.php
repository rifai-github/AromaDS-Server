@extends('layouts.app')

@section('title', 'Achievement Management')

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">Achievement Management</h1>
                    <p class="text-muted">Manage achievements and performance tracking</p>
                </div>
                <div>
                    <a href="{{ route('finance.achievements.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> New Achievement
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Achievements</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalAchievements">-</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-trophy fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Achieved</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="achievedCount">-</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Exceeded</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="exceededCount">-</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-star fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Amount</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalAmount">-</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filters</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('finance.achievements.index') }}">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="user_id">User</label>
                            <select name="user_id" id="user_id" class="form-control">
                                <option value="">All Users</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="period_id">Period</label>
                            <select name="period_id" id="period_id" class="form-control">
                                <option value="">All Periods</option>
                                @foreach($periods as $period)
                                    <option value="{{ $period->id }}" {{ request('period_id') == $period->id ? 'selected' : '' }}>
                                        {{ $period->period_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="achievement_type">Type</label>
                            <select name="achievement_type" id="achievement_type" class="form-control">
                                <option value="">All Types</option>
                                <option value="sales" {{ request('achievement_type') == 'sales' ? 'selected' : '' }}>Sales</option>
                                <option value="service" {{ request('achievement_type') == 'service' ? 'selected' : '' }}>Service</option>
                                <option value="installation" {{ request('achievement_type') == 'installation' ? 'selected' : '' }}>Installation</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select name="status" id="status" class="form-control">
                                <option value="">All Status</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="achieved" {{ request('status') == 'achieved' ? 'selected' : '' }}>Achieved</option>
                                <option value="exceeded" {{ request('status') == 'exceeded' ? 'selected' : '' }}>Exceeded</option>
                                <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="start_date">Start Date</label>
                            <input type="date" name="start_date" id="start_date" class="form-control" value="{{ request('start_date') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="end_date">End Date</label>
                            <input type="date" name="end_date" id="end_date" class="form-control" value="{{ request('end_date') }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                                <a href="{{ route('finance.achievements.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Achievement List -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Achievements</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="achievementsTable">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Period</th>
                            <th>Type</th>
                            <th>Target</th>
                            <th>Achieved</th>
                            <th>Percentage</th>
                            <th>Commission</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($achievements as $achievement)
                            <tr>
                                <td>{{ $achievement->user->name }}</td>
                                <td>{{ $achievement->achievementPeriod->period_name }}</td>
                                <td>
                                    <span class="badge badge-info">{{ ucfirst($achievement->achievement_type) }}</span>
                                </td>
                                <td>{{ $achievement->formatted_target_amount }}</td>
                                <td>{{ $achievement->formatted_achieved_amount }}</td>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar {{ $achievement->achievement_percentage >= 100 ? 'bg-success' : ($achievement->achievement_percentage >= 80 ? 'bg-warning' : 'bg-danger') }}" 
                                             role="progressbar" 
                                             style="width: {{ min(100, $achievement->achievement_percentage) }}%"
                                             aria-valuenow="{{ $achievement->achievement_percentage }}" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                            {{ number_format($achievement->achievement_percentage, 1) }}%
                                        </div>
                                    </div>
                                </td>
                                <td class="font-weight-bold">{{ $achievement->formatted_commission_amount }}</td>
                                <td>
                                    <span class="badge {{ $achievement->status_badge }}">
                                        {{ $achievement->status_label }}
                                    </span>
                                </td>
                                <td>{{ $achievement->achievement_date->format('d/M/Y') }}</td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('finance.achievements.show', $achievement) }}" class="btn btn-sm btn-info" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('finance.achievements.edit', $achievement) }}" class="btn btn-sm btn-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" action="{{ route('finance.achievements.destroy', $achievement) }}" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-trophy fa-3x mb-3"></i>
                                        <p>No achievements found</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center">
                {{ $achievements->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Load statistics
    loadStatistics();
    
    // Auto-refresh statistics every 30 seconds
    setInterval(loadStatistics, 30000);
});

function loadStatistics() {
    $.get('{{ route("finance.achievements.statistics") }}', function(data) {
        $('#totalAchievements').text(data.total_achievements);
        $('#achievedCount').text(data.achieved_count);
        $('#exceededCount').text(data.exceeded_count);
        $('#totalAmount').text('Rp ' + data.total_amount.toLocaleString());
    });
}
</script>
@endpush
