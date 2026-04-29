@extends('layouts.app')

@section('title', 'Create Job Assign Schedule - Operational')
@section('breadcrumb', 'Home / Operational / Job Assign Schedule / Create')

@section('content')
<div class="w-full">
    <!-- Header dengan judul dan button -->
    <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] pt-[7px] pr-[7px] pb-[7px] pl-[7px] md:pt-[10px] md:pr-[10px] md:pb-[10px] md:pl-[10px] lg:pt-[14px] lg:pr-[14px] lg:pb-[14px] lg:pl-[14px]">
        <div class="flex flex-row justify-start items-center w-full">
            <p class="text-[8px] md:text-[12px] lg:text-[16px] font-inter font-medium leading-[10px] md:leading-[15px] lg:leading-[20px] text-left text-[#214589] w-auto">Create Job Assign Schedule</p>
        </div>
        
        <div class="flex flex-row gap-2">
            <a href="{{ route('operational.job-assign-schedules.index') }}" class="btn-secondary">
                <i class="fas fa-arrow-left text-white text-[7px] md:text-[10px] lg:text-[14px] mr-2"></i>
                <span class="text-[7px] md:text-[10px] lg:text-[14px] font-inter font-medium leading-[8px] md:leading-[12px] lg:leading-[17px] text-center text-white">Back to List</span>
            </a>
        </div>
    </div>

    <!-- Content Container -->
    <div class="content-container w-full bg-white rounded-b-[10px] p-[7px] md:p-[10px] lg:p-[14px]">
        <form id="jobAssignmentForm" method="POST" action="{{ route('operational.job-assign-schedules.store') }}" enctype="multipart/form-data">
            @csrf
            
            <!-- Basic Information Section -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Team -->
                    <div class="form-group">
                        <label for="team_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Team <span class="text-red-500">*</span>
                        </label>
                        <select class="form-select @error('team_id') border-red-500 @enderror" 
                                id="team_id" 
                                name="team_id" 
                                required>
                            <option value="">Select Team</option>
                            @foreach($teams ?? [] as $team)
                                <option value="{{ $team->id }}" {{ old('team_id') == $team->id ? 'selected' : '' }}>
                                    {{ $team->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('team_id')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Building -->
                    <div class="form-group">
                        <label for="building_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Building <span class="text-red-500">*</span>
                        </label>
                        <select class="form-select @error('building_id') border-red-500 @enderror" 
                                id="building_id" 
                                name="building_id" 
                                required>
                            <option value="">Select Building</option>
                            @foreach($buildings ?? [] as $building)
                                <option value="{{ $building->id }}" {{ old('building_id') == $building->id ? 'selected' : '' }}>
                                    {{ $building->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('building_id')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Room -->
                    <div class="form-group">
                        <label for="room_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Room <span class="text-red-500">*</span>
                        </label>
                        <select class="form-select @error('room_id') border-red-500 @enderror" 
                                id="room_id" 
                                name="room_id" 
                                required>
                            <option value="">Select Room</option>
                            @foreach($rooms ?? [] as $room)
                                <option value="{{ $room->id }}" {{ old('room_id') == $room->id ? 'selected' : '' }}>
                                    {{ $room->room_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('room_id')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Job Type -->
                    <div class="form-group">
                        <label for="job_type" class="block text-sm font-medium text-gray-700 mb-2">
                            Job Type <span class="text-red-500">*</span>
                        </label>
                        <select class="form-select @error('job_type') border-red-500 @enderror" 
                                id="job_type" 
                                name="job_type" 
                                required>
                            <option value="">Select Job Type</option>
                            <option value="installation" {{ old('job_type') == 'installation' ? 'selected' : '' }}>Installation</option>
                            <option value="maintenance" {{ old('job_type') == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                            <option value="repair" {{ old('job_type') == 'repair' ? 'selected' : '' }}>Repair</option>
                            <option value="inspection" {{ old('job_type') == 'inspection' ? 'selected' : '' }}>Inspection</option>
                            <option value="cleaning" {{ old('job_type') == 'cleaning' ? 'selected' : '' }}>Cleaning</option>
                            <option value="other" {{ old('job_type') == 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('job_type')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Schedule Information Section -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Schedule Information</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Schedule Date -->
                    <div class="form-group">
                        <label for="schedule_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Schedule Date <span class="text-red-500">*</span>
                        </label>
                        <input type="date" 
                               class="form-input @error('schedule_date') border-red-500 @enderror" 
                               id="schedule_date" 
                               name="schedule_date" 
                               value="{{ old('schedule_date') }}" 
                               required>
                        @error('schedule_date')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Priority -->
                    <div class="form-group">
                        <label for="priority" class="block text-sm font-medium text-gray-700 mb-2">
                            Priority
                        </label>
                        <select class="form-select @error('priority') border-red-500 @enderror" 
                                id="priority" 
                                name="priority">
                            <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>Low</option>
                            <option value="medium" {{ old('priority') == 'medium' ? 'selected' : '' }}>Medium</option>
                            <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>High</option>
                        </select>
                        @error('priority')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Start Time -->
                    <div class="form-group">
                        <label for="start_time" class="block text-sm font-medium text-gray-700 mb-2">
                            Start Time <span class="text-red-500">*</span>
                        </label>
                        <input type="time" 
                               class="form-input @error('start_time') border-red-500 @enderror" 
                               id="start_time" 
                               name="start_time" 
                               value="{{ old('start_time') }}" 
                               required>
                        @error('start_time')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- End Time -->
                    <div class="form-group">
                        <label for="end_time" class="block text-sm font-medium text-gray-700 mb-2">
                            End Time <span class="text-red-500">*</span>
                        </label>
                        <input type="time" 
                               class="form-input @error('end_time') border-red-500 @enderror" 
                               id="end_time" 
                               name="end_time" 
                               value="{{ old('end_time') }}" 
                               required>
                        @error('end_time')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Job Details Section -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Job Details</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Estimated Duration -->
                    <div class="form-group">
                        <label for="estimated_duration" class="block text-sm font-medium text-gray-700 mb-2">
                            Estimated Duration (hours)
                        </label>
                        <input type="number" 
                               class="form-input @error('estimated_duration') border-red-500 @enderror" 
                               id="estimated_duration" 
                               name="estimated_duration" 
                               value="{{ old('estimated_duration') }}" 
                               min="0.5" 
                               step="0.5">
                        @error('estimated_duration')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Required Materials -->
                    <div class="form-group">
                        <label for="required_materials" class="block text-sm font-medium text-gray-700 mb-2">
                            Required Materials
                        </label>
                        <input type="text" 
                               class="form-input @error('required_materials') border-red-500 @enderror" 
                               id="required_materials" 
                               name="required_materials" 
                               value="{{ old('required_materials') }}" 
                               placeholder="e.g., Diffuser, Scent oil, Tools">
                        @error('required_materials')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Special Instructions -->
                    <div class="form-group">
                        <label for="special_instructions" class="block text-sm font-medium text-gray-700 mb-2">
                            Special Instructions
                        </label>
                        <textarea class="form-textarea @error('special_instructions') border-red-500 @enderror" 
                                  id="special_instructions" 
                                  name="special_instructions" 
                                  rows="3">{{ old('special_instructions') }}</textarea>
                        @error('special_instructions')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Status -->
                    <div class="form-group">
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                            Status
                        </label>
                        <select class="form-select @error('status') border-red-500 @enderror" 
                                id="status" 
                                name="status">
                            <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="in_progress" {{ old('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="cancelled" {{ old('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                        @error('status')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Additional Information Section -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Additional Information</h3>
                
                <div class="grid grid-cols-1 gap-6">
                    <!-- Description -->
                    <div class="form-group">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                            Description
                        </label>
                        <textarea class="form-textarea @error('description') border-red-500 @enderror" 
                                  id="description" 
                                  name="description" 
                                  rows="4">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Notes -->
                    <div class="form-group">
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                            Notes
                        </label>
                        <textarea class="form-textarea @error('notes') border-red-500 @enderror" 
                                  id="notes" 
                                  name="notes" 
                                  rows="4">{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-row justify-end items-center w-full gap-4 pt-6 border-t border-gray-200">
                <a href="{{ route('operational.job-assign-schedules.index') }}" class="btn-secondary">
                    <i class="fas fa-times mr-2"></i>
                    Cancel
                </a>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save mr-2"></i>
                    Create Job Assignment
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.content-container {
    background-color: white;
    border-radius: 10px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.btn-primary {
    background-color: #214589;
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.2s ease;
    text-decoration: none;
    display: inline-block;
}

.btn-primary:hover {
    background-color: #1a365d;
}

.btn-secondary {
    background-color: #6b7280;
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.2s ease;
    text-decoration: none;
    display: inline-block;
}

.btn-secondary:hover {
    background-color: #4b5563;
}

.form-group {
    margin-bottom: 1rem;
}

.form-input, .form-select, .form-textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    line-height: 1.25rem;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-input:focus, .form-select:focus, .form-textarea:focus {
    outline: none;
    border-color: #214589;
    box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
}

.form-input.border-red-500, .form-select.border-red-500, .form-textarea.border-red-500 {
    border-color: #ef4444;
}

.form-input.border-red-500:focus, .form-select.border-red-500:focus, .form-textarea.border-red-500:focus {
    border-color: #ef4444;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const form = document.getElementById('jobAssignmentForm');
    
    form.addEventListener('submit', function(e) {
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('border-red-500');
                isValid = false;
            } else {
                field.classList.remove('border-red-500');
            }
        });
        
        // Validate time range
        const startTime = document.getElementById('start_time').value;
        const endTime = document.getElementById('end_time').value;
        
        if (startTime && endTime && startTime >= endTime) {
            alert('End time must be after start time.');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fill in all required fields correctly.');
        }
    });
    
    // Real-time validation
    const inputs = form.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.hasAttribute('required') && !this.value.trim()) {
                this.classList.add('border-red-500');
            } else {
                this.classList.remove('border-red-500');
            }
        });
        
        input.addEventListener('input', function() {
            if (this.value.trim()) {
                this.classList.remove('border-red-500');
            }
        });
    });

    // Dynamic room loading based on building selection
    const buildingSelect = document.getElementById('building_id');
    const roomSelect = document.getElementById('room_id');
    
    buildingSelect.addEventListener('change', function() {
        const buildingId = this.value;
        
        // Clear room options
        roomSelect.innerHTML = '<option value="">Select Room</option>';
        
        if (buildingId) {
            // Fetch rooms for selected building
            fetch(`/api/buildings/${buildingId}/rooms`)
                .then(response => response.json())
                .then(data => {
                    data.forEach(room => {
                        const option = document.createElement('option');
                        option.value = room.id;
                        option.textContent = room.room_name;
                        roomSelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error fetching rooms:', error);
                });
        }
    });

    // Auto-calculate duration when start and end times change
    const startTimeInput = document.getElementById('start_time');
    const endTimeInput = document.getElementById('end_time');
    const durationInput = document.getElementById('estimated_duration');
    
    function calculateDuration() {
        const start = startTimeInput.value;
        const end = endTimeInput.value;
        
        if (start && end) {
            const startDate = new Date(`2000-01-01T${start}`);
            const endDate = new Date(`2000-01-01T${end}`);
            
            if (endDate > startDate) {
                const diffMs = endDate - startDate;
                const diffHours = diffMs / (1000 * 60 * 60);
                durationInput.value = diffHours.toFixed(1);
            }
        }
    }
    
    startTimeInput.addEventListener('change', calculateDuration);
    endTimeInput.addEventListener('change', calculateDuration);
});
</script>
@endsection
