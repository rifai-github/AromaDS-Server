@extends('layouts.app')

@section('title', 'Create Maintenance Record')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-plus mr-2"></i>
                        Create Maintenance Record for: {{ $schedule->title }}
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('maintenance.records', $schedule) }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Records
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('maintenance.records.store', $schedule) }}" method="POST" id="recordForm" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="unit_id">Unit <span class="text-danger">*</span></label>
                                    <select class="form-control @error('unit_id') is-invalid @enderror" 
                                            id="unit_id" name="unit_id" required>
                                        <option value="">Select Unit</option>
                                        @foreach($units as $unit)
                                            <option value="{{ $unit->id }}" {{ old('unit_id') == $unit->id ? 'selected' : '' }}>
                                                {{ $unit->serial_number }} 
                                                @if($unit->product)
                                                    - {{ $unit->product->name }}
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('unit_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="room_id">Room</label>
                                    <select class="form-control @error('room_id') is-invalid @enderror" 
                                            id="room_id" name="room_id">
                                        <option value="">Select Room</option>
                                        @foreach($rooms as $room)
                                            <option value="{{ $room->id }}" {{ old('room_id') == $room->id ? 'selected' : '' }}>
                                                {{ $room->name }} ({{ $room->building->name }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('room_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="building_id">Building</label>
                                    <select class="form-control @error('building_id') is-invalid @enderror" 
                                            id="building_id" name="building_id">
                                        <option value="">Select Building</option>
                                        @foreach($buildings as $building)
                                            <option value="{{ $building->id }}" {{ old('building_id') == $building->id ? 'selected' : '' }}>
                                                {{ $building->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('building_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="performed_by">Performed By <span class="text-danger">*</span></label>
                                    <select class="form-control @error('performed_by') is-invalid @enderror" 
                                            id="performed_by" name="performed_by" required>
                                        <option value="">Select Technician</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" {{ old('performed_by') == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }} ({{ $user->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('performed_by')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3" required>{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="work_performed">Work Performed</label>
                            <textarea class="form-control @error('work_performed') is-invalid @enderror" 
                                      id="work_performed" name="work_performed" rows="4">{{ old('work_performed') }}</textarea>
                            @error('work_performed')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="findings">Findings</label>
                                    <textarea class="form-control @error('findings') is-invalid @enderror" 
                                              id="findings" name="findings" rows="3">{{ old('findings') }}</textarea>
                                    @error('findings')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="recommendations">Recommendations</label>
                                    <textarea class="form-control @error('recommendations') is-invalid @enderror" 
                                              id="recommendations" name="recommendations" rows="3">{{ old('recommendations') }}</textarea>
                                    @error('recommendations')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="cost">Cost</label>
                                    <input type="number" class="form-control @error('cost') is-invalid @enderror" 
                                           id="cost" name="cost" value="{{ old('cost') }}" step="0.01" min="0">
                                    @error('cost')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="duration_minutes">Duration (minutes)</label>
                                    <input type="number" class="form-control @error('duration_minutes') is-invalid @enderror" 
                                           id="duration_minutes" name="duration_minutes" value="{{ old('duration_minutes') }}" min="1">
                                    @error('duration_minutes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="checklist_results">Checklist Results (one per line)</label>
                            <textarea class="form-control @error('checklist_results') is-invalid @enderror" 
                                      id="checklist_results" name="checklist_results" rows="5" 
                                      placeholder="Enter checklist results, one per line">{{ old('checklist_results') }}</textarea>
                            <small class="form-text text-muted">Enter each checklist result on a new line.</small>
                            @error('checklist_results')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="photos">Photos</label>
                            <input type="file" class="form-control @error('photos') is-invalid @enderror" 
                                   id="photos" name="photos[]" multiple accept="image/*">
                            <small class="form-text text-muted">You can select multiple image files.</small>
                            @error('photos')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="attachments">Attachments</label>
                            <input type="file" class="form-control @error('attachments') is-invalid @enderror" 
                                   id="attachments" name="attachments[]" multiple accept=".pdf,.doc,.docx,.txt">
                            <small class="form-text text-muted">You can select multiple files. Supported formats: PDF, DOC, DOCX, TXT.</small>
                            @error('attachments')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Create Record
                            </button>
                            <a href="{{ route('maintenance.records', $schedule) }}" class="btn btn-secondary">
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
    // Form validation
    $('#recordForm').on('submit', function(e) {
        var unitId = $('#unit_id').val();
        var performedBy = $('#performed_by').val();
        var description = $('#description').val().trim();

        if (!unitId || !performedBy || !description) {
            e.preventDefault();
            alert('Please fill in all required fields.');
            return false;
        }
    });

    // Auto-populate room when unit is selected
    $('#unit_id').on('change', function() {
        var unitId = $(this).val();
        if (unitId) {
            // You can add AJAX call here to get unit's room and building
            // For now, we'll just enable the room and building fields
            $('#room_id, #building_id').prop('disabled', false);
        } else {
            $('#room_id, #building_id').prop('disabled', true);
        }
    });
});
</script>
@endpush
