@extends('layouts.app')

@section('title', 'Maintenance Schedule Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-tools mr-2"></i>
                        Maintenance Schedule Details
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('maintenance.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                        <a href="{{ route('maintenance.edit', $schedule) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i> Edit
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
                                            <span class="info-box-number">{{ ucfirst($schedule->maintenance_type) }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-warning">
                                            <i class="fas fa-exclamation-triangle"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Priority</span>
                                            <span class="info-box-number">{{ ucfirst($schedule->priority) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-primary">
                                            <i class="fas fa-calendar"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Scheduled Date</span>
                                            <span class="info-box-number">{{ $schedule->scheduled_date->format('M d, Y') }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-success">
                                            <i class="fas fa-user"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Assigned To</span>
                                            <span class="info-box-number">
                                                @if($schedule->assignedTo)
                                                    {{ $schedule->assignedTo->name }}
                                                @else
                                                    <span class="text-muted">Unassigned</span>
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Schedule Information</h3>
                                </div>
                                <div class="card-body">
                                    <dl class="row">
                                        <dt class="col-sm-3">Title:</dt>
                                        <dd class="col-sm-9">{{ $schedule->title }}</dd>

                                        @if($schedule->description)
                                            <dt class="col-sm-3">Description:</dt>
                                            <dd class="col-sm-9">{{ $schedule->description }}</dd>
                                        @endif

                                        <dt class="col-sm-3">Status:</dt>
                                        <dd class="col-sm-9">
                                            @php
                                                $statusColors = [
                                                    'scheduled' => 'primary',
                                                    'in_progress' => 'warning',
                                                    'completed' => 'success',
                                                    'cancelled' => 'secondary',
                                                    'overdue' => 'danger'
                                                ];
                                            @endphp
                                            <span class="badge badge-{{ $statusColors[$schedule->status] }} badge-lg">
                                                {{ ucfirst(str_replace('_', ' ', $schedule->status)) }}
                                            </span>
                                        </dd>

                                        @if($schedule->scheduled_time)
                                            <dt class="col-sm-3">Scheduled Time:</dt>
                                            <dd class="col-sm-9">{{ $schedule->scheduled_time }}</dd>
                                        @endif

                                        @if($schedule->due_date)
                                            <dt class="col-sm-3">Due Date:</dt>
                                            <dd class="col-sm-9">{{ $schedule->due_date->format('M d, Y') }}</dd>
                                        @endif

                                        @if($schedule->estimated_duration)
                                            <dt class="col-sm-3">Estimated Duration:</dt>
                                            <dd class="col-sm-9">{{ $schedule->duration_formatted }}</dd>
                                        @endif

                                        @if($schedule->notes)
                                            <dt class="col-sm-3">Notes:</dt>
                                            <dd class="col-sm-9">{{ $schedule->notes }}</dd>
                                        @endif

                                        <dt class="col-sm-3">Created:</dt>
                                        <dd class="col-sm-9">{{ $schedule->created_at->format('M d, Y H:i') }}</dd>

                                        @if($schedule->started_at)
                                            <dt class="col-sm-3">Started:</dt>
                                            <dd class="col-sm-9">{{ $schedule->started_at->format('M d, Y H:i') }}</dd>
                                        @endif

                                        @if($schedule->completed_at)
                                            <dt class="col-sm-3">Completed:</dt>
                                            <dd class="col-sm-9">{{ $schedule->completed_at->format('M d, Y H:i') }}</dd>
                                        @endif
                                    </dl>
                                </div>
                            </div>

                            @if($schedule->checklist && count($schedule->checklist) > 0)
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">Checklist</h3>
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-group">
                                            @foreach($schedule->checklist as $item)
                                                <li class="list-group-item">
                                                    <i class="fas fa-check-circle text-success mr-2"></i>
                                                    {{ $item }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            @endif

                            @if($schedule->attachments && count($schedule->attachments) > 0)
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">Attachments</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            @foreach($schedule->attachments as $attachment)
                                                <div class="col-md-3 mb-3">
                                                    <div class="card">
                                                        <div class="card-body text-center">
                                                            <i class="fas fa-file-alt fa-2x mb-2"></i>
                                                            <p class="card-text">{{ $attachment['name'] }}</p>
                                                            <a href="{{ $attachment['url'] }}" target="_blank" class="btn btn-sm btn-primary">
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
                                    @if($schedule->status === 'scheduled')
                                        <form action="{{ route('maintenance.start', $schedule) }}" method="POST" class="mb-2">
                                            @csrf
                                            <button type="submit" class="btn btn-success btn-block">
                                                <i class="fas fa-play"></i> Start Maintenance
                                            </button>
                                        </form>
                                    @endif

                                    @if($schedule->status === 'in_progress')
                                        <form action="{{ route('maintenance.complete', $schedule) }}" method="POST" class="mb-2">
                                            @csrf
                                            <button type="submit" class="btn btn-success btn-block">
                                                <i class="fas fa-check"></i> Complete Maintenance
                                            </button>
                                        </form>
                                    @endif

                                    @if(in_array($schedule->status, ['scheduled', 'in_progress']))
                                        <form action="{{ route('maintenance.cancel', $schedule) }}" method="POST" class="mb-2">
                                            @csrf
                                            <button type="submit" class="btn btn-danger btn-block" onclick="return confirm('Are you sure you want to cancel this maintenance schedule?')">
                                                <i class="fas fa-times"></i> Cancel Schedule
                                            </button>
                                        </form>
                                    @endif

                                    <a href="{{ route('maintenance.records', $schedule) }}" class="btn btn-info btn-block mb-2">
                                        <i class="fas fa-list"></i> View Records
                                    </a>

                                    <a href="{{ route('maintenance.records.create', $schedule) }}" class="btn btn-primary btn-block">
                                        <i class="fas fa-plus"></i> Add Record
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
                                            <span class="bg-primary">{{ $schedule->created_at->format('M d, Y') }}</span>
                                        </div>
                                        <div>
                                            <i class="fas fa-plus bg-blue"></i>
                                            <div class="timeline-item">
                                                <span class="time">{{ $schedule->created_at->format('H:i') }}</span>
                                                <h3 class="timeline-header">Schedule Created</h3>
                                                <div class="timeline-body">
                                                    Created by {{ $schedule->createdBy->name }}
                                                </div>
                                            </div>
                                        </div>

                                        @if($schedule->started_at)
                                            <div>
                                                <i class="fas fa-play bg-yellow"></i>
                                                <div class="timeline-item">
                                                    <span class="time">{{ $schedule->started_at->format('H:i') }}</span>
                                                    <h3 class="timeline-header">Maintenance Started</h3>
                                                    <div class="timeline-body">
                                                        Started by {{ $schedule->assignedTo->name ?? 'Unknown' }}
                                                    </div>
                                                </div>
                                            </div>
                                        @endif

                                        @if($schedule->completed_at)
                                            <div>
                                                <i class="fas fa-check bg-green"></i>
                                                <div class="timeline-item">
                                                    <span class="time">{{ $schedule->completed_at->format('H:i') }}</span>
                                                    <h3 class="timeline-header">Maintenance Completed</h3>
                                                    <div class="timeline-body">
                                                        Completed by {{ $schedule->assignedTo->name ?? 'Unknown' }}
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
