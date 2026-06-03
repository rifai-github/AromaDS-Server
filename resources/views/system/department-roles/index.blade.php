@extends('layouts.app')

@section('title', 'Department Roles')
@section('breadcrumb', 'Home / System / Department Roles')

@section('content')
<style>
    /* Global overflow control */
    html, body {
        overflow-x: hidden;
        max-width: 100vw;
    }

    *, *::before, *::after {
        box-sizing: border-box;
    }

    /* Button Styles */
    .btn {
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
    }

    .btn-primary {
        background-color: #214589;
        color: white;
    }

    .btn-primary:hover {
        background-color: #1e3a8a;
    }

    .btn-secondary {
        background-color: #f3f4f6;
        color: #6b7280;
        border: 1px solid #d1d5db;
    }

    .btn-secondary:hover {
        background-color: #e5e7eb;
        color: #4b5563;
    }

    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
    }

    /* Table Container */
    .table-container {
        background: white;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        border-radius: 0 0 10px 10px;
        position: relative;
        width: 100%;
        max-width: 100%;
        margin: 0;
        padding: 0;
        overflow-x: auto;
        overflow-y: visible;
        -webkit-overflow-scrolling: touch;
    }

    /* Custom scrollbar */
    .table-container::-webkit-scrollbar {
        height: 8px;
    }

    .table-container::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }

    .table-container::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 4px;
    }

    .table-container::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }

    /* Table Header */
    .table-header {
        background: linear-gradient(135deg, #214589 0%, #1e3a8a 100%);
        color: white;
        padding: 20px 24px;
        border-radius: 10px 10px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .table-title {
        font-size: 20px;
        font-weight: 600;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .table-actions {
        display: flex;
        gap: 12px;
        align-items: center;
    }

    /* Table Wrapper */
    .table-wrapper {
        overflow-x: auto;
        overflow-y: visible;
        -webkit-overflow-scrolling: touch;
    }

    /* Data Table */
    .data-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
        min-width: 800px;
    }

    .data-table th {
        background-color: #f8fafc;
        color: #374151;
        font-weight: 600;
        text-align: left;
        padding: 16px 20px;
        border-bottom: 2px solid #e5e7eb;
        white-space: nowrap;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .data-table td {
        padding: 16px 20px;
        border-bottom: 1px solid #f3f4f6;
        vertical-align: middle;
    }

    .data-table tbody tr {
        transition: background-color 0.2s ease;
        cursor: pointer;
    }

    .data-table tbody tr:hover {
        background-color: #f8fafc;
    }

    /* Badge Styles */
    .badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.025em;
    }

    .badge-success {
        background-color: #d1fae5;
        color: #065f46;
    }

    .badge-danger {
        background-color: #fee2e2;
        color: #991b1b;
    }

    .badge-info {
        background-color: #dbeafe;
        color: #1e40af;
    }

    .badge-secondary {
        background-color: #f3f4f6;
        color: #6b7280;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #6b7280;
    }

    .empty-state i {
        font-size: 48px;
        color: #d1d5db;
        margin-bottom: 16px;
    }

    .empty-state h5 {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 8px;
        color: #374151;
    }

    .empty-state p {
        font-size: 14px;
        margin-bottom: 24px;
    }

    /* Pagination */
    .pagination-wrapper {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 24px;
        background-color: #f8fafc;
        border-top: 1px solid #e5e7eb;
        border-radius: 0 0 10px 10px;
    }

    .pagination-info {
        color: #6b7280;
        font-size: 14px;
    }

    .pagination-controls {
        display: flex;
        gap: 8px;
    }

    /* Modal Styles */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }

    .modal-overlay.show {
        opacity: 1;
        visibility: visible;
    }

    .modal-content {
        background: white;
        border-radius: 8px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        max-width: 500px;
        width: 90%;
        max-height: 90vh;
        display: flex !important;
        flex-direction: column !important;
        transform: scale(0.9);
        transition: transform 0.3s ease;
        position: relative !important;
        overflow: hidden !important;
    }

    .modal-overlay.show .modal-content {
        transform: scale(1);
    }

    .modal-header {
        padding: 20px 24px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-shrink: 0;
    }

    .modal-title {
        font-size: 18px;
        font-weight: 600;
        margin: 0;
        color: #111827;
    }

    .modal-close {
        background: none;
        border: none;
        font-size: 24px;
        color: #6b7280;
        cursor: pointer;
        padding: 0;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        transition: all 0.2s ease;
    }

    .modal-close:hover {
        background-color: #f3f4f6;
        color: #374151;
    }

    .modal-body {
        padding: 24px;
        flex: 1;
        overflow-y: auto;
        max-height: calc(90vh - 140px);
        position: relative;
        z-index: 1;
    }

    .modal-footer {
        padding: 20px 24px;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        background-color: #f8f9fa;
        flex-shrink: 0;
        position: relative;
        z-index: 9999;
    }

    /* Form Styles */
    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        display: block;
        font-weight: 500;
        color: #374151;
        margin-bottom: 6px;
        font-size: 14px;
    }

    .form-control {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .form-control:focus {
        outline: none;
        border-color: #214589;
        box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
    }

    .form-select {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        background-color: white;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
        background-position: right 8px center;
        background-repeat: no-repeat;
        background-size: 16px;
        padding-right: 32px;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .form-select:focus {
        outline: none;
        border-color: #214589;
        box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
    }

    .form-check {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .form-check-input {
        width: 16px;
        height: 16px;
        border: 1px solid #d1d5db;
        border-radius: 3px;
        cursor: pointer;
    }

    .form-check-input:checked {
        background-color: #214589;
        border-color: #214589;
    }

    .form-check-label {
        font-size: 14px;
        color: #374151;
        cursor: pointer;
    }

    /* Department Icon */
    .department-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 6px;
        font-size: 16px;
        margin-right: 8px;
        background-color: #dbeafe;
        color: #1e40af;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .table-header {
            flex-direction: column;
            align-items: stretch;
        }

        .table-actions {
            justify-content: center;
        }

        .pagination-wrapper {
            flex-direction: column;
            gap: 16px;
            text-align: center;
        }
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Table Container -->
            <div class="table-container">
                <div class="table-header">
                    <h3 class="table-title">
                        <i class="fas fa-building"></i>
                        Department Roles
                    </h3>
                    <div class="table-actions">
                        <button class="btn btn-primary" onclick="openAssignModal()">
                            <i class="fas fa-plus"></i>
                            Assign Role
                        </button>
                    </div>
                </div>

                <div class="table-wrapper">
                    @if($departments->count() > 0)
                        <table class="data-table" id="departmentRolesTable">
                            <thead>
                                <tr>
                                    <th data-column="name">Department</th>
                                    <th data-column="departmentRoles__role__name">Assigned Role</th>
                                    <th data-no-filter>Users Count</th>
                                    <th data-column="is_active">Status</th>
                                    <th data-column="createdBy__name">Created By</th>
                                    <th data-column="created_at" data-type="date">Created At</th>
                                    <th data-column="updatedBy__name">Updated By</th>
                                    <th data-column="updated_at" data-type="date">Updated At</th>
                                    <th data-no-filter>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($departments as $department)
                                <tr onclick="showDepartmentRole({{ $department->id }})" style="cursor: pointer;">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="department-icon">
                                                <i class="fas fa-building"></i>
                                            </div>
                                            <div>
                                                <div class="font-weight-bold">{{ $department->name }}</div>
                                                @if($department->description)
                                                    <small class="text-muted">{{ Str::limit($department->description, 50) }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($department->departmentRoles->count() > 0)
                                            @foreach($department->departmentRoles as $deptRole)
                                                @if($deptRole->role)
                                                    <span class="badge badge-info">{{ $deptRole->role->name }}</span>
                                                @else
                                                    <span class="badge badge-danger">Role Deleted</span>
                                                @endif
                                            @endforeach
                                        @else
                                            <span class="text-muted">No role assigned</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-info">{{ $department->users->count() }}</span>
                                    </td>
                                    <td>
                                        @if($department->is_active)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td>{{ $department->createdBy->name ?? 'System' }}</td>
                                    <td>
                                        @if($department->created_at)
                                            {{ \Carbon\Carbon::parse($department->created_at)->format('d/M/Y') }}<br>
                                            <small>{{ \Carbon\Carbon::parse($department->created_at)->format('H:i') }}</small>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $department->updatedBy->name ?? 'System' }}</td>
                                    <td>
                                        @if($department->updated_at)
                                            {{ \Carbon\Carbon::parse($department->updated_at)->format('d/M/Y') }}<br>
                                            <small>{{ \Carbon\Carbon::parse($department->updated_at)->format('H:i') }}</small>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-danger" onclick="deleteDepartmentRole({{ $department->id }})" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="empty-state">
                            <i class="fas fa-building"></i>
                            <h5>No Department Roles Found</h5>
                            <p>Start by assigning roles to departments.</p>
                            <button class="btn btn-primary" onclick="openAssignModal()">
                                <i class="fas fa-plus"></i>
                                Assign First Role
                            </button>
                        </div>
                    @endif
                </div>

                @if($departments->count() > 0)
                <!-- Pagination -->
                <div class="pagination-wrapper">
                    <div class="pagination-info">
                        <span class="info-text">
                            Showing {{ $departments->count() }} entries
                        </span>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Assign Role Modal -->
<div class="modal-overlay" id="assignModal">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="modalTitle">Assign Role to Department</h5>
            <button type="button" class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form id="assignForm">
            <div class="modal-body">
                <div class="form-group">
                    <label for="department_id" class="form-label">Department <span class="text-danger">*</span></label>
                    <select class="form-select" id="department_id" name="department_id" required>
                        <option value="">Select Department</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="role_id" class="form-label">Role <span class="text-danger">*</span></label>
                    <select class="form-select" id="role_id" name="role_id" required>
                        <option value="">Select Role</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                    <label class="form-check-label" for="is_active">
                        Active
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">
                    <i class="fas fa-times me-1"></i>
                    Cancel
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>
                    <span id="saveButtonText">Assign Role</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Show Department Role Modal -->
<div class="modal-overlay" id="showDepartmentRoleModal">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Department Role Details</h5>
            <button type="button" class="modal-close" onclick="closeShowModal()">&times;</button>
        </div>
        <div class="modal-body" id="showDepartmentRoleContent">
            <!-- Content will be loaded here -->
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeShowModal()">Close</button>
            <button type="button" class="btn btn-primary" onclick="editDepartmentRoleFromShow()">
                <i class="fas fa-edit me-1"></i>
                Edit Role
            </button>
        </div>
    </div>
</div>

<script>
let currentDepartmentId = null;

function openAssignModal() {
    document.getElementById('assignModal').classList.add('show');
    document.getElementById('assignForm').reset();
    document.getElementById('modalTitle').textContent = 'Assign Role to Department';
    document.getElementById('saveButtonText').textContent = 'Assign Role';
}

function closeModal() {
    document.getElementById('assignModal').classList.remove('show');
    document.getElementById('assignForm').reset();
}

function closeShowModal() {
    document.getElementById('showDepartmentRoleModal').classList.remove('show');
}

function showDepartmentRole(departmentId) {
    currentDepartmentId = departmentId;
    
    console.log('Fetching department ID:', departmentId);
    
    fetch(`/system/department-roles/${departmentId}`)
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            
            if (data.status === 'success') {
                const department = data.department;
                console.log('Department data:', department);
                
                const content = `
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Department</label>
                                <div class="form-control-plaintext">${department.name || 'N/A'}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <div class="form-control-plaintext">
                                    <span class="badge ${department.is_active ? 'badge-success' : 'badge-danger'}">
                                        ${department.is_active ? 'Active' : 'Inactive'}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <div class="form-control-plaintext">${department.description || 'No description'}</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Assigned Roles</label>
                        <div class="form-control-plaintext">
                            ${department.assigned_roles && department.assigned_roles.length > 0 
                                ? department.assigned_roles.map(role => 
                                    `<span class="badge badge-info">${role}</span>`
                                  ).join(' ')
                                : '<span class="text-muted">No roles assigned</span>'
                            }
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Users Count</label>
                        <div class="form-control-plaintext">
                            <span class="badge badge-info">${department.users_count || 0}</span>
                        </div>
                    </div>
                `;
                
                document.getElementById('showDepartmentRoleContent').innerHTML = content;
                document.getElementById('showDepartmentRoleModal').classList.add('show');
            } else {
                console.error('API returned error status:', data);
                alert('Gagal memuat detail department role');
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            alert('Gagal memuat detail department role');
        });
}

function editDepartmentRoleFromShow() {
    closeShowModal();
    // For now, just open the assign modal
    // In a real implementation, you might want to pre-populate the form
    openAssignModal();
}

function deleteDepartmentRole(departmentId) {
    if (confirm('Apakah kamu yakin ingin menghapus assignment department role ini?')) {
        fetch(`/system/department-roles/${departmentId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                location.reload();
            } else {
                alert('Gagal menghapus department role: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Gagal menghapus department role');
        });
    }
}

// Form submission
document.getElementById('assignForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('/system/department-roles', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            closeModal();
            location.reload();
        } else {
            alert('Gagal assign role: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Gagal assign role');
    });
});

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        closeModal();
        closeShowModal();
    }
});
</script>
@endsection
