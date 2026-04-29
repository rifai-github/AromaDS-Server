@extends('layouts.app')

@section('title', 'Edit Permission')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fas fa-key"></i> Edit Permission: {{ $permission->name }}
                    </h4>
                    <div class="card-tools">
                        <a href="{{ route('system.permissions.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form id="permissionForm" method="POST" action="{{ route('system.permissions.update', $permission->id) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Permission Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name', $permission->name) }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="guard_name">Guard Name</label>
                                    <select class="form-control @error('guard_name') is-invalid @enderror" 
                                            id="guard_name" name="guard_name">
                                        <option value="web" {{ old('guard_name', $permission->guard_name) == 'web' ? 'selected' : '' }}>Web</option>
                                        <option value="api" {{ old('guard_name', $permission->guard_name) == 'api' ? 'selected' : '' }}>API</option>
                                    </select>
                                    @error('guard_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="module">Module</label>
                                    <select class="form-control @error('module') is-invalid @enderror" 
                                            id="module" name="module">
                                        <option value="">Select Module</option>
                                        <option value="system" {{ old('module', $permission->module) == 'system' ? 'selected' : '' }}>System</option>
                                        <option value="company" {{ old('module', $permission->module) == 'company' ? 'selected' : '' }}>Company</option>
                                        <option value="finance" {{ old('module', $permission->module) == 'finance' ? 'selected' : '' }}>Finance</option>
                                        <option value="warehouse" {{ old('module', $permission->module) == 'warehouse' ? 'selected' : '' }}>Warehouse</option>
                                        <option value="marketing" {{ old('module', $permission->module) == 'marketing' ? 'selected' : '' }}>Marketing</option>
                                        <option value="operational" {{ old('module', $permission->module) == 'operational' ? 'selected' : '' }}>Operational</option>
                                        <option value="reports" {{ old('module', $permission->module) == 'reports' ? 'selected' : '' }}>Reports</option>
                                        <option value="settings" {{ old('module', $permission->module) == 'settings' ? 'selected' : '' }}>Settings</option>
                                        <option value="other" {{ old('module', $permission->module) == 'other' ? 'selected' : '' }}>Other</option>
                                    </select>
                                    @error('module')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="action">Action</label>
                                    <select class="form-control @error('action') is-invalid @enderror" 
                                            id="action" name="action">
                                        <option value="">Select Action</option>
                                        <option value="view" {{ old('action', $permission->action) == 'view' ? 'selected' : '' }}>View</option>
                                        <option value="create" {{ old('action', $permission->action) == 'create' ? 'selected' : '' }}>Create</option>
                                        <option value="edit" {{ old('action', $permission->action) == 'edit' ? 'selected' : '' }}>Edit</option>
                                        <option value="delete" {{ old('action', $permission->action) == 'delete' ? 'selected' : '' }}>Delete</option>
                                        <option value="approve" {{ old('action', $permission->action) == 'approve' ? 'selected' : '' }}>Approve</option>
                                        <option value="export" {{ old('action', $permission->action) == 'export' ? 'selected' : '' }}>Export</option>
                                        <option value="print" {{ old('action', $permission->action) == 'print' ? 'selected' : '' }}>Print</option>
                                    </select>
                                    @error('action')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="description">Description</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" 
                                              id="description" name="description" rows="3">{{ old('description', $permission->description) }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="is_active">Status</label>
                                    <select class="form-control @error('is_active') is-invalid @enderror" 
                                            id="is_active" name="is_active">
                                        <option value="1" {{ old('is_active', $permission->is_active) == 1 ? 'selected' : '' }}>Active</option>
                                        <option value="0" {{ old('is_active', $permission->is_active) == 0 ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                    @error('is_active')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="is_system">System Permission</label>
                                    <select class="form-control @error('is_system') is-invalid @enderror" 
                                            id="is_system" name="is_system">
                                        <option value="0" {{ old('is_system', $permission->is_system) == 0 ? 'selected' : '' }}>No</option>
                                        <option value="1" {{ old('is_system', $permission->is_system) == 1 ? 'selected' : '' }}>Yes</option>
                                    </select>
                                    @error('is_system')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update Permission
                                    </button>
                                    <a href="{{ route('system.permissions.index') }}" class="btn btn-secondary">
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
    // Form validation
    $('#permissionForm').submit(function(e) {
        var isValid = true;
        
        // Clear previous errors
        $('.is-invalid').removeClass('is-invalid');
        
        // Validate required fields
        if (!$('#name').val()) {
            $('#name').addClass('is-invalid');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fill in all required fields.');
        }
    });

    // Auto-generate permission name based on module and action
    $('#module, #action').change(function() {
        var module = $('#module').val();
        var action = $('#action').val();
        
        if (module && action) {
            var permissionName = module + '.' + action;
            $('#name').val(permissionName);
        }
    });
});
</script>
@endpush
