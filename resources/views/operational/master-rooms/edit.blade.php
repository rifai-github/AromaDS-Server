@extends('layouts.app')

@section('title', 'Edit Master Room - Operational')
@section('breadcrumb', 'Home / Operational / Master Room / Edit')

@section('content')
<div class="w-full">
    <!-- Header dengan judul dan button -->
    <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] pt-[7px] pr-[7px] pb-[7px] pl-[7px] md:pt-[10px] md:pr-[10px] md:pb-[10px] md:pl-[10px] lg:pt-[14px] lg:pr-[14px] lg:pb-[14px] lg:pl-[14px]">
        <div class="flex flex-row justify-start items-center w-full">
            <p class="text-[8px] md:text-[12px] lg:text-[16px] font-inter font-medium leading-[10px] md:leading-[15px] lg:leading-[20px] text-left text-[#214589] w-auto">Edit Master Room</p>
        </div>
        
        <div class="flex flex-row gap-2">
            <a href="{{ route('operational.master-rooms.index') }}" class="btn-secondary">
                <i class="fas fa-arrow-left text-white text-[7px] md:text-[10px] lg:text-[14px] mr-2"></i>
                <span class="text-[7px] md:text-[10px] lg:text-[14px] font-inter font-medium leading-[8px] md:leading-[12px] lg:leading-[17px] text-center text-white">Back to List</span>
            </a>
        </div>
    </div>

    <!-- Content Container -->
    <div class="content-container w-full bg-white rounded-b-[10px] p-[7px] md:p-[10px] lg:p-[14px]">
        <form id="roomForm" method="POST" action="{{ route('operational.master-rooms.update', $room->id) }}" enctype="multipart/form-data">
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
                                <option value="{{ $building->id }}" {{ old('building_id', $room->building_id) == $building->id ? 'selected' : '' }}>
                                    {{ $building->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('building_id')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Customer -->
                    <div class="form-group">
                        <label for="customer_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Customer <span class="text-red-500">*</span>
                        </label>
                        <select class="form-select @error('customer_id') border-red-500 @enderror" 
                                id="customer_id" 
                                name="customer_id" 
                                required>
                            <option value="">Select Customer</option>
                            @foreach($customers ?? [] as $customer)
                                <option value="{{ $customer->id }}" {{ old('customer_id', $room->customer_id) == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->company_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('customer_id')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Room Name -->
                    <div class="form-group">
                        <label for="room_name" class="block text-sm font-medium text-gray-700 mb-2">
                            Room Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               class="form-input @error('room_name') border-red-500 @enderror" 
                               id="room_name" 
                               name="room_name" 
                               value="{{ old('room_name', $room->room_name) }}" 
                               required>
                        @error('room_name')
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
                            <option value="active" {{ old('status', $room->status) == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $room->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('status')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Room Details Section -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Room Details</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Room Type -->
                    <div class="form-group">
                        <label for="room_type" class="block text-sm font-medium text-gray-700 mb-2">
                            Room Type
                        </label>
                        <select class="form-select @error('room_type') border-red-500 @enderror" 
                                id="room_type" 
                                name="room_type">
                            <option value="">Select Room Type</option>
                            <option value="office" {{ old('room_type', $room->room_type) == 'office' ? 'selected' : '' }}>Office</option>
                            <option value="meeting_room" {{ old('room_type', $room->room_type) == 'meeting_room' ? 'selected' : '' }}>Meeting Room</option>
                            <option value="lobby" {{ old('room_type', $room->room_type) == 'lobby' ? 'selected' : '' }}>Lobby</option>
                            <option value="corridor" {{ old('room_type', $room->room_type) == 'corridor' ? 'selected' : '' }}>Corridor</option>
                            <option value="restroom" {{ old('room_type', $room->room_type) == 'restroom' ? 'selected' : '' }}>Restroom</option>
                            <option value="other" {{ old('room_type', $room->room_type) == 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('room_type')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Floor -->
                    <div class="form-group">
                        <label for="floor" class="block text-sm font-medium text-gray-700 mb-2">
                            Floor
                        </label>
                        <input type="text" 
                               class="form-input @error('floor') border-red-500 @enderror" 
                               id="floor" 
                               name="floor" 
                               value="{{ old('floor', $room->floor) }}">
                        @error('floor')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Scent Intensity -->
                    <div class="form-group">
                        <label for="scent_intensity" class="block text-sm font-medium text-gray-700 mb-2">
                            Scent Intensity
                        </label>
                        <select class="form-select @error('scent_intensity') border-red-500 @enderror" 
                                id="scent_intensity" 
                                name="scent_intensity">
                            <option value="">Select Scent Intensity</option>
                            <option value="low" {{ old('scent_intensity', $room->scent_intensity) == 'low' ? 'selected' : '' }}>Low</option>
                            <option value="medium" {{ old('scent_intensity', $room->scent_intensity) == 'medium' ? 'selected' : '' }}>Medium</option>
                            <option value="high" {{ old('scent_intensity', $room->scent_intensity) == 'high' ? 'selected' : '' }}>High</option>
                        </select>
                        @error('scent_intensity')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Install Type -->
                    <div class="form-group">
                        <label for="install_type" class="block text-sm font-medium text-gray-700 mb-2">
                            Install Type
                        </label>
                        <select class="form-select @error('install_type') border-red-500 @enderror" 
                                id="install_type" 
                                name="install_type">
                            <option value="">Select Install Type</option>
                            <option value="wall_mounted" {{ old('install_type', $room->install_type) == 'wall_mounted' ? 'selected' : '' }}>Wall Mounted</option>
                            <option value="ceiling_mounted" {{ old('install_type', $room->install_type) == 'ceiling_mounted' ? 'selected' : '' }}>Ceiling Mounted</option>
                            <option value="floor_standing" {{ old('install_type', $room->install_type) == 'floor_standing' ? 'selected' : '' }}>Floor Standing</option>
                            <option value="duct_connected" {{ old('install_type', $room->install_type) == 'duct_connected' ? 'selected' : '' }}>Duct Connected</option>
                        </select>
                        @error('install_type')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- AHU Quantity -->
                    <div class="form-group">
                        <label for="ahu_quantity" class="block text-sm font-medium text-gray-700 mb-2">
                            AHU Quantity
                        </label>
                        <input type="number" 
                               class="form-input @error('ahu_quantity') border-red-500 @enderror" 
                               id="ahu_quantity" 
                               name="ahu_quantity" 
                               value="{{ old('ahu_quantity', $room->ahu_quantity ?? 0) }}" 
                               min="0">
                        @error('ahu_quantity')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Dimensions Section -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Room Dimensions</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Height -->
                    <div class="form-group">
                        <label for="height" class="block text-sm font-medium text-gray-700 mb-2">
                            Height (m)
                        </label>
                        <input type="number" 
                               class="form-input @error('height') border-red-500 @enderror" 
                               id="height" 
                               name="height" 
                               value="{{ old('height', $room->height) }}" 
                               step="0.01" 
                               min="0">
                        @error('height')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Width -->
                    <div class="form-group">
                        <label for="width" class="block text-sm font-medium text-gray-700 mb-2">
                            Width (m)
                        </label>
                        <input type="number" 
                               class="form-input @error('width') border-red-500 @enderror" 
                               id="width" 
                               name="width" 
                               value="{{ old('width', $room->width) }}" 
                               step="0.01" 
                               min="0">
                        @error('width')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Length -->
                    <div class="form-group">
                        <label for="length" class="block text-sm font-medium text-gray-700 mb-2">
                            Length (m)
                        </label>
                        <input type="number" 
                               class="form-input @error('length') border-red-500 @enderror" 
                               id="length" 
                               name="length" 
                               value="{{ old('length', $room->length) }}" 
                               step="0.01" 
                               min="0">
                        @error('length')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Additional Information Section -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Additional Information</h3>
                
                <div class="grid grid-cols-1 gap-6">
                    <!-- Notes -->
                    <div class="form-group">
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                            Notes
                        </label>
                        <textarea class="form-textarea @error('notes') border-red-500 @enderror" 
                                  id="notes" 
                                  name="notes" 
                                  rows="4">{{ old('notes', $room->notes) }}</textarea>
                        @error('notes')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-row justify-end items-center w-full gap-4 pt-6 border-t border-gray-200">
                <a href="{{ route('operational.master-rooms.index') }}" class="btn-secondary">
                    <i class="fas fa-times mr-2"></i>
                    Cancel
                </a>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save mr-2"></i>
                    Update Room
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
    const form = document.getElementById('roomForm');
    
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
});
</script>
@endsection
