@extends('layouts.app')

@section('title', 'Edit Maintenance Schedule')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-edit mr-2"></i>
                        Edit Maintenance Schedule
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('maintenance.show', $schedule) }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Details
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('maintenance.update', $schedule) }}" method="POST" id="maintenanceForm">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="title">Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('title') is-invalid @enderror" 
                                           id="title" name="title" value="{{ old('title', $schedule->title) }}" required>
                                    @error('title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="maintenance_type">Maintenance Type <span class="text-danger">*</span></label>
                                    <select class="form-control @error('maintenance_type') is-invalid @enderror" 
                                            id="maintenance_type" name="maintenance_type" required>
                                        <option value="">Select Type</option>
                                        <option value="preventive" {{ old('maintenance_type', $schedule->maintenance_type) == 'preventive' ? 'selected' : '' }}>Preventive</option>
                                        <option value="corrective" {{ old('maintenance_type', $schedule->maintenance_type) == 'corrective' ? 'selected' : '' }}>Corrective</option>
                                        <option value="predictive" {{ old('maintenance_type', $schedule->maintenance_type) == 'predictive' ? 'selected' : '' }}>Predictive</option>
                                        <option value="emergency" {{ old('maintenance_type', $schedule->maintenance_type) == 'emergency' ? 'selected' : '' }}>Emergency</option>
                                    </select>
                                    @error('maintenance_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="priority">Priority <span class="text-danger">*</span></label>
                                    <select class="form-control @error('priority') is-invalid @enderror" 
                                            id="priority" name="priority" required>
                                        <option value="">Select Priority</option>
                                        <option value="low" {{ old('priority', $schedule->priority) == 'low' ? 'selected' : '' }}>Low</option>
                                        <option value="medium" {{ old('priority', $schedule->priority) == 'medium' ? 'selected' : '' }}>Medium</option>
                                        <option value="high" {{ old('priority', $schedule->priority) == 'high' ? 'selected' : '' }}>High</option>
                                        <option value="critical" {{ old('priority', $schedule->priority) == 'critical' ? 'selected' : '' }}>Critical</option>
                                    </select>
                                    @error('priority')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="assigned_to">Assigned To</label>
                                    <select class="form-control @error('assigned_to') is-invalid @enderror" 
                                            id="assigned_to" name="assigned_to">
                                        <option value="">Select Technician</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" {{ old('assigned_to', $schedule->assigned_to) == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }} ({{ $user->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('assigned_to')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="scheduled_date">Scheduled Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('scheduled_date') is-invalid @enderror" 
                                           id="scheduled_date" name="scheduled_date" value="{{ old('scheduled_date', $schedule->scheduled_date->format('Y-m-d')) }}" required>
                                    @error('scheduled_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="scheduled_time">Scheduled Time</label>
                                    <input type="time" class="form-control @error('scheduled_time') is-invalid @enderror" 
                                           id="scheduled_time" name="scheduled_time" value="{{ old('scheduled_time', $schedule->scheduled_time) }}">
                                    @error('scheduled_time')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="due_date">Due Date</label>
                                    <input type="date" class="form-control @error('due_date') is-invalid @enderror" 
                                           id="due_date" name="due_date" value="{{ old('due_date', $schedule->due_date ? $schedule->due_date->format('Y-m-d') : '') }}">
                                    @error('due_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="estimated_duration">Estimated Duration (minutes)</label>
                                    <input type="number" class="form-control @error('estimated_duration') is-invalid @enderror" 
                                           id="estimated_duration" name="estimated_duration" value="{{ old('estimated_duration', $schedule->estimated_duration) }}" min="1">
                                    @error('estimated_duration')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3">{{ old('description', $schedule->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="notes">Notes</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                      id="notes" name="notes" rows="3">{{ old('notes', $schedule->notes) }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="checklist">Checklist Items (one per line)</label>
                            <textarea class="form-control @error('checklist') is-invalid @enderror" 
                                      id="checklist" name="checklist" rows="5" 
                                      placeholder="Enter checklist items, one per line">{{ old('checklist', $schedule->checklist ? implode("\n", $schedule->checklist) : '') }}</textarea>
                            <small class="form-text text-muted">Enter each checklist item on a new line.</small>
                            @error('checklist')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="attachments">Attachments</label>
                            <input type="file" class="form-control @error('attachments') is-invalid @enderror" 
                                   id="attachments" name="attachments[]" multiple accept="image/*,.pdf,.doc,.docx">
                            <small class="form-text text-muted">You can select multiple files. Supported formats: images, PDF, DOC, DOCX.</small>
                            @error('attachments')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @if($schedule->attachments && count($schedule->attachments) > 0)
                            <div class="form-group">
                                <label>Current Attachments</label>
                                <div class="row">
                                    @foreach($schedule->attachments as $attachment)
                                        <div class="col-md-3 mb-2">
                                            <div class="card">
                                                <div class="card-body text-center">
                                                    <i class="fas fa-file-alt fa-2x mb-2"></i>
                                                    <p class="card-text small">{{ $attachment['name'] }}</p>
                                                    <a href="{{ $attachment['url'] }}" target="_blank" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Schedule
                            </button>
                            <a href="{{ route('maintenance.show', $schedule) }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Auto-set due date to scheduled date if not set
    $('#scheduled_date').on('change', function() {
        if (!$('#due_date').val()) {
            $('#due_date').val($(this).val());
        }
    });

    // Form validation
    $('#maintenanceForm').on('submit', function(e) {
        var title = $('#title').val().trim();
        var type = $('#maintenance_type').val();
        var priority = $('#priority').val();
        var scheduledDate = $('#scheduled_date').val();

        if (!title || !type || !priority || !scheduledDate) {
            e.preventDefault();
            alert('Please fill in all required fields.');
            return false;
        }

        // Validate scheduled date is not in the past (unless it's already started)
        var today = new Date().toISOString().split('T')[0];
        if (scheduledDate < today && '{{ $schedule->status }}' === 'scheduled') {
            e.preventDefault();
            alert('Scheduled date cannot be in the past.');
            return false;
        }
    });
});
</script>
@endpush
