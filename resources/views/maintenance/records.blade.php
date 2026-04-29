@extends('layouts.app')

@section('title', 'Maintenance Records')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-list mr-2"></i>
                        Maintenance Records for: {{ $schedule->title }}
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('maintenance.show', $schedule) }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Schedule
                        </a>
                        <a href="{{ route('maintenance.records.create', $schedule) }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Add Record
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

                    <!-- Schedule Info -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <h5><i class="fas fa-info-circle mr-2"></i>Schedule Information</h5>
                                <div class="row">
                                    <div class="col-md-3">
                                        <strong>Type:</strong> {{ ucfirst($schedule->maintenance_type) }}
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Priority:</strong> 
                                        <span class="badge badge-{{ $schedule->priority === 'low' ? 'success' : ($schedule->priority === 'medium' ? 'warning' : ($schedule->priority === 'high' ? 'danger' : 'dark')) }}">
                                            {{ ucfirst($schedule->priority) }}
                                        </span>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Status:</strong> 
                                        <span class="badge badge-{{ $schedule->status === 'scheduled' ? 'primary' : ($schedule->status === 'in_progress' ? 'warning' : ($schedule->status === 'completed' ? 'success' : ($schedule->status === 'cancelled' ? 'secondary' : 'danger'))) }}">
                                            {{ ucfirst(str_replace('_', ' ', $schedule->status)) }}
                                        </span>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Scheduled:</strong> {{ $schedule->scheduled_date->format('M d, Y') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Records Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="recordsTable">
                            <thead>
                                <tr>
                                    <th>Record ID</th>
                                    <th>Unit</th>
                                    <th>Work Performed</th>
                                    <th>Status</th>
                                    <th>Performed By</th>
                                    <th>Duration</th>
                                    <th>Cost</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($records as $record)
                                    <tr>
                                        <td>
                                            <strong>#{{ $record->id }}</strong>
                                            @if($record->started_at)
                                                <br><small class="text-muted">Started: {{ $record->started_at->format('M d, H:i') }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($record->unit)
                                                <strong>{{ $record->unit->serial_number }}</strong>
                                                @if($record->unit->product)
                                                    <br><small class="text-muted">{{ $record->unit->product->name }}</small>
                                                @endif
                                            @else
                                                <span class="text-muted">No unit</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($record->work_performed)
                                                {{ Str::limit($record->work_performed, 50) }}
                                            @else
                                                <span class="text-muted">No work performed</span>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $statusColors = [
                                                    'in_progress' => 'warning',
                                                    'completed' => 'success',
                                                    'failed' => 'danger',
                                                    'cancelled' => 'secondary'
                                                ];
                                            @endphp
                                            <span class="badge badge-{{ $statusColors[$record->status] }}">
                                                {{ ucfirst(str_replace('_', ' ', $record->status)) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($record->performedBy)
                                                {{ $record->performedBy->name }}
                                            @else
                                                <span class="text-muted">Unknown</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($record->duration_formatted)
                                                {{ $record->duration_formatted }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($record->cost)
                                                ${{ number_format($record->cost, 2) }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('maintenance.records.show', $record) }}" class="btn btn-info btn-sm" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                
                                                @if($record->status === 'in_progress')
                                                    <form action="{{ route('maintenance.records.complete', $record) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-success btn-sm" title="Complete">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                                
                                                @if(in_array($record->status, ['in_progress']))
                                                    <form action="{{ route('maintenance.records.cancel', $record) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-danger btn-sm" title="Cancel" onclick="return confirm('Are you sure?')">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">No maintenance records found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center">
                        {{ $records->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css">
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap4.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize DataTable if needed
    $('#recordsTable').DataTable({
        "paging": false,
        "searching": false,
        "ordering": true,
        "info": false,
        "autoWidth": false,
        "responsive": true
    });
});
</script>
@endpush
