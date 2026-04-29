@extends('layouts.app')

@section('title', 'Create New Team')

@section('breadcrumb', 'Home / Operational / Teams / Create')

@section('content')
<div class="w-full">
    <!-- Header dengan judul dan button -->
    <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] pt-[7px] pr-[7px] pb-[7px] pl-[7px] md:pt-[10px] md:pr-[10px] md:pb-[10px] md:pl-[10px] lg:pt-[14px] lg:pr-[14px] lg:pb-[14px] lg:pl-[14px]">
        <div class="flex flex-row justify-start items-center w-full">
            <p class="text-[8px] md:text-[12px] lg:text-[16px] font-inter font-medium leading-[10px] md:leading-[15px] lg:leading-[20px] text-left text-[#214589] w-auto">Create New Team</p>
        </div>
        
        <div class="flex flex-row gap-2">
            <a href="{{ route('operational.teams.index') }}" class="btn-secondary">
                <i class="fas fa-arrow-left text-white text-[7px] md:text-[10px] lg:text-[14px] mr-2"></i>
                <span class="text-[7px] md:text-[10px] lg:text-[14px] font-inter font-medium leading-[8px] md:leading-[12px] lg:leading-[17px] text-center text-white">Back to List</span>
            </a>
        </div>
    </div>

    <!-- Content Container -->
    <div class="content-container w-full bg-white rounded-b-[10px] p-[7px] md:p-[10px] lg:p-[14px]">
        <form id="teamForm" method="POST" action="{{ route('operational.teams.store') }}" enctype="multipart/form-data">
            @csrf
            
            <!-- Basic Information Section -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                            Nama Tim <span class="text-red-500">*</span>
                        </label>
                        <input type="text" class="form-input @error('name') border-red-500 @enderror" 
                               id="name" name="name" value="{{ old('name') }}" required>
                        @error('name')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="code" class="block text-sm font-medium text-gray-700 mb-2">Kode Tim</label>
                        <input type="text" class="form-input @error('code') border-red-500 @enderror" 
                               id="code" name="code" value="{{ old('code') }}">
                        @error('code')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Organization Section -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Organization</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label for="branch_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Kantor Cabang <span class="text-red-500">*</span>
                        </label>
                        <select class="form-select @error('branch_id') border-red-500 @enderror" 
                                id="branch_id" name="branch_id" required>
                            <option value="">Pilih Kantor Cabang</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('branch_id')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="department_id" class="block text-sm font-medium text-gray-700 mb-2">Departemen</label>
                        <select class="form-select @error('department_id') border-red-500 @enderror" 
                                id="department_id" name="department_id">
                            <option value="">Pilih Departemen</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}" {{ old('department_id') == $department->id ? 'selected' : '' }}>
                                    {{ $department->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('department_id')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Team Leadership Section -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Team Leadership</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label for="leader_id" class="block text-sm font-medium text-gray-700 mb-2">Tim Head</label>
                        <select class="form-select @error('leader_id') border-red-500 @enderror" 
                                id="leader_id" name="leader_id">
                            <option value="">Pilih Tim Head</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('leader_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }} ({{ $user->email }})
                                </option>
                            @endforeach
                        </select>
                        @error('leader_id')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status Aktif</label>
                        <select class="form-select @error('status') border-red-500 @enderror" 
                                id="status" name="status">
                            <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
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
                    <div class="form-group">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Deskripsi</label>
                        <textarea class="form-textarea @error('description') border-red-500 @enderror" 
                                  id="description" name="description" rows="3">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Catatan Tambahan</label>
                        <textarea class="form-textarea @error('notes') border-red-500 @enderror" 
                                  id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Team Members Section -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Team Members</h3>
                <div class="grid grid-cols-1 gap-6">
                    <div class="form-group">
                        <label for="members" class="block text-sm font-medium text-gray-700 mb-2">Pilih Anggota Tim</label>
                        <select class="form-select @error('members') border-red-500 @enderror" 
                                id="members" name="members[]" multiple>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ in_array($user->id, old('members', [])) ? 'selected' : '' }}>
                                    {{ $user->name }} ({{ $user->email }}) - {{ $user->department->name ?? 'N/A' }}
                                </option>
                            @endforeach
                        </select>
                        @error('members')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                        <small class="text-gray-500 text-sm mt-1">Pilih satu atau lebih anggota tim</small>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-row justify-end items-center w-full gap-4 pt-6 border-t border-gray-200">
                <a href="{{ route('operational.teams.index') }}" class="btn-secondary">
                    <i class="fas fa-times mr-2"></i>
                    Cancel
                </a>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save mr-2"></i>
                    Save Team
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
$(document).ready(function() {
    // Initialize Select2 for multiple selection
    $('#members').select2({
        placeholder: 'Pilih anggota tim...',
        allowClear: true,
        width: '100%'
    });

    // Form validation
    $('#teamForm').submit(function(e) {
        var isValid = true;
        
        // Clear previous errors
        $('.border-red-500').removeClass('border-red-500');
        $('.text-red-500').remove();
        
        // Validate required fields
        if (!$('#name').val()) {
            $('#name').addClass('border-red-500');
            $('#name').after('<p class="text-red-500 text-sm mt-1">Nama tim wajib diisi</p>');
            isValid = false;
        }
        
        if (!$('#branch_id').val()) {
            $('#branch_id').addClass('border-red-500');
            $('#branch_id').after('<p class="text-red-500 text-sm mt-1">Kantor cabang wajib dipilih</p>');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            alert('Silakan isi semua field wajib.');
        }
    });

    // Auto-generate team code based on name
    $('#name').on('keyup', function() {
        var name = $(this).val();
        if (name && !$('#code').val()) {
            var code = name.replace(/[^a-zA-Z0-9]/g, '').toUpperCase().substring(0, 5);
            $('#code').val(code);
        }
    });
});
</script>
@endsection
