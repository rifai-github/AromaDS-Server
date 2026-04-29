@extends('layouts.app')

@section('title', 'Edit Team')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fas fa-users"></i> Edit Team: {{ $team->name }}
                    </h4>
                    <div class="card-tools">
                        <a href="{{ route('operational.teams.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form id="teamForm" method="POST" action="{{ route('operational.teams.update', $team->id) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Nama Tim <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name', $team->name) }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="code">Kode Tim</label>
                                    <input type="text" class="form-control @error('code') is-invalid @enderror" 
                                           id="code" name="code" value="{{ old('code', $team->code) }}">
                                    @error('code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="branch_id">Kantor Cabang <span class="text-danger">*</span></label>
                                    <select class="form-control @error('branch_id') is-invalid @enderror" 
                                            id="branch_id" name="branch_id" required>
                                        <option value="">Pilih Kantor Cabang</option>
                                        @foreach($branches as $branch)
                                            <option value="{{ $branch->id }}" 
                                                {{ old('branch_id', $team->branch_id) == $branch->id ? 'selected' : '' }}>
                                                {{ $branch->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('branch_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="department_id">Departemen</label>
                                    <select class="form-control @error('department_id') is-invalid @enderror" 
                                            id="department_id" name="department_id">
                                        <option value="">Pilih Departemen</option>
                                        @foreach($departments as $department)
                                            <option value="{{ $department->id }}" 
                                                {{ old('department_id', $team->department_id) == $department->id ? 'selected' : '' }}>
                                                {{ $department->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('department_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="leader_id">Tim Head</label>
                                    <select class="form-control @error('leader_id') is-invalid @enderror" 
                                            id="leader_id" name="leader_id">
                                        <option value="">Pilih Tim Head</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" 
                                                {{ old('leader_id', $team->leader_id) == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }} ({{ $user->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('leader_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="status">Status Aktif</label>
                                    <select class="form-control @error('status') is-invalid @enderror" 
                                            id="status" name="status">
                                        <option value="active" {{ old('status', $team->status) == 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="inactive" {{ old('status', $team->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="description">Deskripsi</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" 
                                              id="description" name="description" rows="3">{{ old('description', $team->description) }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="notes">Catatan Tambahan</label>
                                    <textarea class="form-control @error('notes') is-invalid @enderror" 
                                              id="notes" name="notes" rows="3">{{ old('notes', $team->notes) }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Team Members Section -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">
                                            <i class="fas fa-user-plus"></i> Team Members
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="members">Pilih Anggota Tim</label>
                                                    <select class="form-control select2 @error('members') is-invalid @enderror" 
                                                            id="members" name="members[]" multiple>
                                                        @foreach($users as $user)
                                                            <option value="{{ $user->id }}" 
                                                                {{ in_array($user->id, old('members', $team->members->pluck('id')->toArray())) ? 'selected' : '' }}>
                                                                {{ $user->name }} ({{ $user->email }}) - {{ $user->department->name ?? 'N/A' }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    @error('members')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                    <small class="form-text text-muted">Pilih satu atau lebih anggota tim</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update Team
                                    </button>
                                    <a href="{{ route('operational.teams.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                </div>
                            </div>
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
        $('.is-invalid').removeClass('is-invalid');
        
        // Validate required fields
        if (!$('#name').val()) {
            $('#name').addClass('is-invalid');
            isValid = false;
        }
        
        if (!$('#branch_id').val()) {
            $('#branch_id').addClass('is-invalid');
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
@endpush
