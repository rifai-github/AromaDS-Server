@extends('layouts.app')

@section('title', 'Maintenance Record Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-list mr-2"></i>
                        Maintenance Record Details
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('maintenance.records', $record->maintenanceSchedule) }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Records
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

                    <div class="row">
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-info">
                                            <i class="fas fa-tools"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Maintenance Type</span>
                                            <span class="info-box-number">{{ ucfirst($record->maintenance_type) }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-warning">
                                            <i class="fas fa-exclamation-triangle"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Status</span>
                                            <span class="info-box-number">
                                                @php
                                                    $statusColors = [
                                                        'in_progress' => 'warning',
                                                        'completed' => 'success',
                                                        'failed' => 'danger',
                                                        'cancelled' => 'secondary'
                                                    ];
                                                @endphp
                                                <span class="badge badge-{{ $statusColors[$record->status] }} badge-lg">
                                                    {{ ucfirst(str_replace('_', ' ', $record->status)) }}
                                                </span>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-primary">
                                            <i class="fas fa-user"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Performed By</span>
                                            <span class="info-box-number">
                                                @if($record->performedBy)
                                                    {{ $record->performedBy->name }}
                                                @else
                                                    <span class="text-muted">Unknown</span>
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-success">
                                            <i class="fas fa-clock"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Duration</span>
                                            <span class="info-box-number">
                                                @if($record->duration_formatted)
                                                    {{ $record->duration_formatted }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Record Information</h3>
                                </div>
                                <div class="card-body">
                                    <dl class="row">
                                        <dt class="col-sm-3">Description:</dt>
                                        <dd class="col-sm-9">{{ $record->description }}</dd>

                                        @if($record->work_performed)
                                            <dt class="col-sm-3">Work Performed:</dt>
                                            <dd class="col-sm-9">{{ $record->work_performed }}</dd>
                                        @endif

                                        @if($record->findings)
                                            <dt class="col-sm-3">Findings:</dt>
                                            <dd class="col-sm-9">{{ $record->findings }}</dd>
                                        @endif

                                        @if($record->recommendations)
                                            <dt class="col-sm-3">Recommendations:</dt>
                                            <dd class="col-sm-9">{{ $record->recommendations }}</dd>
                                        @endif

                                        @if($record->cost)
                                            <dt class="col-sm-3">Cost:</dt>
                                            <dd class="col-sm-9">${{ number_format($record->cost, 2) }}</dd>
                                        @endif

                                        <dt class="col-sm-3">Created:</dt>
                                        <dd class="col-sm-9">{{ $record->created_at->format('M d, Y H:i') }}</dd>

                                        @if($record->started_at)
                                            <dt class="col-sm-3">Started:</dt>
                                            <dd class="col-sm-9">{{ $record->started_at->format('M d, Y H:i') }}</dd>
                                        @endif

                                        @if($record->completed_at)
                                            <dt class="col-sm-3">Completed:</dt>
                                            <dd class="col-sm-9">{{ $record->completed_at->format('M d, Y H:i') }}</dd>
                                        @endif
                                    </dl>
                                </div>
                            </div>

                            @if($record->unit)
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">Unit Information</h3>
                                    </div>
                                    <div class="card-body">
                                        <dl class="row">
                                            <dt class="col-sm-3">Serial Number:</dt>
                                            <dd class="col-sm-9">{{ $record->unit->serial_number }}</dd>

                                            @if($record->unit->product)
                                                <dt class="col-sm-3">Product:</dt>
                                                <dd class="col-sm-9">{{ $record->unit->product->name }}</dd>
                                            @endif

                                            @if($record->room)
                                                <dt class="col-sm-3">Room:</dt>
                                                <dd class="col-sm-9">{{ $record->room->name }}</dd>
                                            @endif

                                            @if($record->building)
                                                <dt class="col-sm-3">Building:</dt>
                                                <dd class="col-sm-9">{{ $record->building->name }}</dd>
                                            @endif
                                        </dl>
                                    </div>
                                </div>
                            @endif

                            @if($record->checklist_results && count($record->checklist_results) > 0)
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">Checklist Results</h3>
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-group">
                                            @foreach($record->checklist_results as $result)
                                                <li class="list-group-item">
                                                    <i class="fas fa-check-circle text-success mr-2"></i>
                                                    {{ $result }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            @endif

                            @if($record->photos && count($record->photos) > 0)
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">Photos</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            @foreach($record->photos as $photo)
                                                <div class="col-md-3 mb-3">
                                                    <div class="card">
                                                        <img src="{{ $photo['url'] }}" class="card-img-top" alt="{{ $photo['name'] }}" style="height: 200px; object-fit: cover;">
                                                        <div class="card-body text-center">
                                                            <p class="card-text small">{{ $photo['name'] }}</p>
                                                            <a href="{{ $photo['url'] }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-primary">
                                                                <i class="fas fa-download"></i> Download
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if($record->attachments && count($record->attachments) > 0)
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">Attachments</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            @foreach($record->attachments as $attachment)
                                                <div class="col-md-3 mb-3">
                                                    <div class="card">
                                                        <div class="card-body text-center">
                                                            <i class="fas fa-file-alt fa-2x mb-2"></i>
                                                            <p class="card-text">{{ $attachment['name'] }}</p>
                                                            <a href="{{ $attachment['url'] }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-primary">
                                                                <i class="fas fa-download"></i> Download
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Actions</h3>
                                </div>
                                <div class="card-body">
                                    @if($record->status === 'in_progress')
                                        <form action="{{ route('maintenance.records.complete', $record) }}" method="POST" class="mb-2">
                                            @csrf
                                            <button type="submit" class="btn btn-success btn-block">
                                                <i class="fas fa-check"></i> Complete Record
                                            </button>
                                        </form>
                                    @endif

                                    @if($record->status === 'in_progress')
                                        <form action="{{ route('maintenance.records.cancel', $record) }}" method="POST" class="mb-2">
                                            @csrf
                                            <button type="submit" class="btn btn-danger btn-block" onclick="return confirm('Are you sure you want to cancel this maintenance record?')">
                                                <i class="fas fa-times"></i> Cancel Record
                                            </button>
                                        </form>
                                    @endif

                                    <a href="{{ route('maintenance.records', $record->maintenanceSchedule) }}" class="btn btn-info btn-block">
                                        <i class="fas fa-list"></i> Back to Records
                                    </a>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Timeline</h3>
                                </div>
                                <div class="card-body">
                                    <div class="timeline">
                                        <div class="time-label">
                                            <span class="bg-primary">{{ $record->created_at->format('M d, Y') }}</span>
                                        </div>
                                        <div>
                                            <i class="fas fa-plus bg-blue"></i>
                                            <div class="timeline-item">
                                                <span class="time">{{ $record->created_at->format('H:i') }}</span>
                                                <h3 class="timeline-header">Record Created</h3>
                                                <div class="timeline-body">
                                                    Created by {{ $record->createdBy->name }}
                                                </div>
                                            </div>
                                        </div>

                                        @if($record->started_at)
                                            <div>
                                                <i class="fas fa-play bg-yellow"></i>
                                                <div class="timeline-item">
                                                    <span class="time">{{ $record->started_at->format('H:i') }}</span>
                                                    <h3 class="timeline-header">Maintenance Started</h3>
                                                    <div class="timeline-body">
                                                        Started by {{ $record->performedBy->name ?? 'Unknown' }}
                                                    </div>
                                                </div>
                                            </div>
                                        @endif

                                        @if($record->completed_at)
                                            <div>
                                                <i class="fas fa-check bg-green"></i>
                                                <div class="timeline-item">
                                                    <span class="time">{{ $record->completed_at->format('H:i') }}</span>
                                                    <h3 class="timeline-header">Maintenance Completed</h3>
                                                    <div class="timeline-body">
                                                        Completed by {{ $record->performedBy->name ?? 'Unknown' }}
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
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
