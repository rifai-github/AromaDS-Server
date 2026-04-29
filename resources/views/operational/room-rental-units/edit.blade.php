@extends('layouts.app')

@section('title', 'Edit Room Rental Unit - Operational')
@section('breadcrumb', 'Home / Operational / Room Rental Unit / Edit')

@section('content')
<div class="w-full">
    <!-- Header dengan judul dan button -->
    <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] pt-[7px] pr-[7px] pb-[7px] pl-[7px] md:pt-[10px] md:pr-[10px] md:pb-[10px] md:pl-[10px] lg:pt-[14px] lg:pr-[14px] lg:pb-[14px] lg:pl-[14px]">
        <div class="flex flex-row justify-start items-center w-full">
            <p class="text-[8px] md:text-[12px] lg:text-[16px] font-inter font-medium leading-[10px] md:leading-[15px] lg:leading-[20px] text-left text-[#214589] w-auto">Edit Room Rental Unit</p>
        </div>
        
        <div class="flex flex-row gap-2">
            <a href="{{ route('operational.room-rental-units.index') }}" class="btn-secondary">
                <i class="fas fa-arrow-left text-white text-[7px] md:text-[10px] lg:text-[14px] mr-2"></i>
                <span class="text-[7px] md:text-[10px] lg:text-[14px] font-inter font-medium leading-[8px] md:leading-[12px] lg:leading-[17px] text-center text-white">Back to List</span>
            </a>
        </div>
    </div>

    <!-- Content Container -->
    <div class="content-container w-full bg-white rounded-b-[10px] p-[7px] md:p-[10px] lg:p-[14px]">
        <form id="rentalUnitForm" method="POST" action="{{ route('operational.room-rental-units.update', $rentalUnit->id) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            <!-- Basic Information Section -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                                <option value="{{ $building->id }}" {{ old('building_id', $rentalUnit->room->building_id ?? '') == $building->id ? 'selected' : '' }}>
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
                                <option value="{{ $room->id }}" {{ old('room_id', $rentalUnit->room_id) == $room->id ? 'selected' : '' }}>
                                    {{ $room->room_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('room_id')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Unit Name -->
                    <div class="form-group">
                        <label for="unit_name" class="block text-sm font-medium text-gray-700 mb-2">
                            Unit Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               class="form-input @error('unit_name') border-red-500 @enderror" 
                               id="unit_name" 
                               name="unit_name" 
                               value="{{ old('unit_name', $rentalUnit->unit_name) }}" 
                               required>
                        @error('unit_name')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Unit Type -->
                    <div class="form-group">
                        <label for="unit_type" class="block text-sm font-medium text-gray-700 mb-2">
                            Unit Type <span class="text-red-500">*</span>
                        </label>
                        <select class="form-select @error('unit_type') border-red-500 @enderror" 
                                id="unit_type" 
                                name="unit_type" 
                                required>
                            <option value="">Select Unit Type</option>
                            <option value="diffuser" {{ old('unit_type', $rentalUnit->unit_type) == 'diffuser' ? 'selected' : '' }}>Diffuser</option>
                            <option value="atomizer" {{ old('unit_type', $rentalUnit->unit_type) == 'atomizer' ? 'selected' : '' }}>Atomizer</option>
                            <option value="sprayer" {{ old('unit_type', $rentalUnit->unit_type) == 'sprayer' ? 'selected' : '' }}>Sprayer</option>
                            <option value="ventilation" {{ old('unit_type', $rentalUnit->unit_type) == 'ventilation' ? 'selected' : '' }}>Ventilation</option>
                            <option value="other" {{ old('unit_type', $rentalUnit->unit_type) == 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('unit_type')
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
                            <option value="active" {{ old('status', $rentalUnit->status) == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $rentalUnit->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            <option value="maintenance" {{ old('status', $rentalUnit->status) == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                        </select>
                        @error('status')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Rental Information Section -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Rental Information</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Rental Price -->
                    <div class="form-group">
                        <label for="rental_price" class="block text-sm font-medium text-gray-700 mb-2">
                            Rental Price (IDR)
                        </label>
                        <input type="number" 
                               class="form-input @error('rental_price') border-red-500 @enderror" 
                               id="rental_price" 
                               name="rental_price" 
                               value="{{ old('rental_price', $rentalUnit->rental_price) }}" 
                               min="0" 
                               step="1000">
                        @error('rental_price')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Rental Period -->
                    <div class="form-group">
                        <label for="rental_period" class="block text-sm font-medium text-gray-700 mb-2">
                            Rental Period
                        </label>
                        <select class="form-select @error('rental_period') border-red-500 @enderror" 
                                id="rental_period" 
                                name="rental_period">
                            <option value="">Select Rental Period</option>
                            <option value="daily" {{ old('rental_period', $rentalUnit->rental_period) == 'daily' ? 'selected' : '' }}>Daily</option>
                            <option value="weekly" {{ old('rental_period', $rentalUnit->rental_period) == 'weekly' ? 'selected' : '' }}>Weekly</option>
                            <option value="monthly" {{ old('rental_period', $rentalUnit->rental_period) == 'monthly' ? 'selected' : '' }}>Monthly</option>
                            <option value="yearly" {{ old('rental_period', $rentalUnit->rental_period) == 'yearly' ? 'selected' : '' }}>Yearly</option>
                        </select>
                        @error('rental_period')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Installation Date -->
                    <div class="form-group">
                        <label for="installation_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Installation Date
                        </label>
                        <input type="date" 
                               class="form-input @error('installation_date') border-red-500 @enderror" 
                               id="installation_date" 
                               name="installation_date" 
                               value="{{ old('installation_date', $rentalUnit->installation_date) }}">
                        @error('installation_date')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Maintenance Date -->
                    <div class="form-group">
                        <label for="maintenance_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Next Maintenance Date
                        </label>
                        <input type="date" 
                               class="form-input @error('maintenance_date') border-red-500 @enderror" 
                               id="maintenance_date" 
                               name="maintenance_date" 
                               value="{{ old('maintenance_date', $rentalUnit->maintenance_date) }}">
                        @error('maintenance_date')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Technical Specifications Section -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Technical Specifications</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Model -->
                    <div class="form-group">
                        <label for="model" class="block text-sm font-medium text-gray-700 mb-2">
                            Model
                        </label>
                        <input type="text" 
                               class="form-input @error('model') border-red-500 @enderror" 
                               id="model" 
                               name="model" 
                               value="{{ old('model', $rentalUnit->model) }}">
                        @error('model')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Serial Number -->
                    <div class="form-group">
                        <label for="serial_number" class="block text-sm font-medium text-gray-700 mb-2">
                            Serial Number
                        </label>
                        <input type="text" 
                               class="form-input @error('serial_number') border-red-500 @enderror" 
                               id="serial_number" 
                               name="serial_number" 
                               value="{{ old('serial_number', $rentalUnit->serial_number) }}">
                        @error('serial_number')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Capacity -->
                    <div class="form-group">
                        <label for="capacity" class="block text-sm font-medium text-gray-700 mb-2">
                            Capacity (ml)
                        </label>
                        <input type="number" 
                               class="form-input @error('capacity') border-red-500 @enderror" 
                               id="capacity" 
                               name="capacity" 
                               value="{{ old('capacity', $rentalUnit->capacity) }}" 
                               min="0" 
                               step="0.1">
                        @error('capacity')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Power Consumption -->
                    <div class="form-group">
                        <label for="power_consumption" class="block text-sm font-medium text-gray-700 mb-2">
                            Power Consumption (W)
                        </label>
                        <input type="number" 
                               class="form-input @error('power_consumption') border-red-500 @enderror" 
                               id="power_consumption" 
                               name="power_consumption" 
                               value="{{ old('power_consumption', $rentalUnit->power_consumption) }}" 
                               min="0" 
                               step="0.1">
                        @error('power_consumption')
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
                                  rows="4">{{ old('description', $rentalUnit->description) }}</textarea>
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
                                  rows="4">{{ old('notes', $rentalUnit->notes) }}</textarea>
                        @error('notes')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-row justify-end items-center w-full gap-4 pt-6 border-t border-gray-200">
                <a href="{{ route('operational.room-rental-units.index') }}" class="btn-secondary">
                    <i class="fas fa-times mr-2"></i>
                    Cancel
                </a>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save mr-2"></i>
                    Update Rental Unit
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
    const form = document.getElementById('rentalUnitForm');
    
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
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fill in all required fields.');
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
});
</script>
@endsection
