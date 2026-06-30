@extends('layouts.app')

@section('title', 'Branch PICs - ' . $branch->name)
@section('breadcrumb', 'Home / Company / Branches / ' . $branch->name . ' / PICs')

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

    .btn-success {
        background-color: #16a34a;
        color: white;
    }

    .btn-success:hover {
        background-color: #15803d;
    }

    .btn-info {
        background-color: #0ea5e9;
        color: white;
    }

    .btn-info:hover {
        background-color: #0284c7;
    }

    .btn-warning {
        background-color: #f59e0b;
        color: white;
    }

    .btn-warning:hover {
        background-color: #d97706;
    }

    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
    }

    /* Table Styles */
    .table-container {
        background: white;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        margin-bottom: 20px;
    }

    .table-header {
        background: #f8fafc;
        padding: 20px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
    }

    .table-title {
        font-size: 18px;
        font-weight: 600;
        color: #1e293b;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .table-actions {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .table-wrapper {
        overflow-x: auto;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }

    .data-table th {
        background: #f1f5f9;
        color: #334155;
        font-weight: 600;
        padding: 12px 16px;
        text-align: left;
        border-bottom: 1px solid #e2e8f0;
        white-space: nowrap;
    }

    .data-table td {
        padding: 12px 16px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: top;
    }

    .data-table tbody tr:hover {
        background-color: #f8fafc;
        transition: background-color 0.2s ease;
    }

    .data-table tbody tr:nth-child(even) {
        background-color: #f9fafb;
    }

    .data-table tbody tr:nth-child(even):hover {
        background-color: #f1f5f9;
    }

    /* Badge Styles */
    .badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
        line-height: 1;
        white-space: nowrap;
    }

    .badge-success {
        background-color: #dcfce7;
        color: #166534;
        border: 1px solid #bbf7d0;
    }

    .badge-warning {
        background-color: #fef3c7;
        color: #d97706;
        border: 1px solid #fde68a;
    }

    .badge-danger {
        background-color: #fee2e2;
        color: #dc2626;
        border: 1px solid #fecaca;
    }

    .badge-info {
        background-color: #dbeafe;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
    }

    .badge-secondary {
        background-color: #f1f5f9;
        color: #64748b;
        border: 1px solid #e2e8f0;
    }

    /* Pagination */
    .pagination-wrapper {
        padding: 20px;
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
    }

    .pagination-info {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #64748b;
        font-size: 14px;
    }

    .pagination-info .info-text {
        font-weight: 500;
    }

    .pagination-controls {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .pagination-controls .btn {
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        background: white;
        color: #374151;
        text-decoration: none;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .pagination-controls .btn:hover {
        background: #f3f4f6;
        border-color: #9ca3af;
    }

    .pagination-controls .btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1055;
        align-items: center;
        justify-content: center;
    }
    
    /* Fix accessibility issues */
    .modal[aria-hidden="true"] {
        display: none !important;
        visibility: hidden !important;
    }
    
    .modal[aria-hidden="true"] * {
        visibility: hidden !important;
        pointer-events: none !important;
    }
    
    /* Ensure modal content is not focusable when hidden */
    .modal[aria-hidden="true"] input,
    .modal[aria-hidden="true"] button,
    .modal[aria-hidden="true"] select,
    .modal[aria-hidden="true"] textarea,
    .modal[aria-hidden="true"] a {
        tabindex: -1 !important;
    }

    .modal.show {
        display: flex !important;
    }

    .modal-dialog {
        background: white;
        border-radius: 8px;
        width: 90%;
        max-width: 800px;
        max-height: 80vh;
        overflow: hidden;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    }

    .modal-header {
        background: #f1f5f9;
        padding: 16px 20px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-title {
        font-size: 18px;
        font-weight: 600;
        color: #1e293b;
        margin: 0;
    }

    .modal-close {
        background: none;
        border: none;
        font-size: 20px;
        color: #64748b;
        cursor: pointer;
        padding: 4px;
        border-radius: 4px;
        transition: color 0.2s ease;
    }

    .modal-close:hover {
        color: #334155;
    }

    .modal-body {
        padding: 20px;
        max-height: 60vh;
        overflow-y: auto;
        flex: 1;
    }

    .modal-footer {
        background: #f8fafc;
        padding: 16px 20px;
        border-top: 1px solid #e2e8f0;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        position: sticky;
        bottom: 0;
        z-index: 10;
    }

    /* Form Styles */
    .form-group {
        margin-bottom: 16px;
    }

    .form-label {
        display: block;
        font-size: 14px;
        font-weight: 500;
        color: #374151;
        margin-bottom: 6px;
    }

    .form-control {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.2s ease;
    }

    .form-control:focus {
        outline: none;
        border-color: #214589;
        box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
    }

    .form-select {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        background: white;
        transition: border-color 0.2s ease;
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
        margin: 0;
    }

    .form-check-label {
        margin: 0;
        font-size: 14px;
        color: #374151;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #64748b;
    }

    .empty-state i {
        font-size: 48px;
        margin-bottom: 16px;
        color: #cbd5e1;
    }

    .empty-state h5 {
        font-size: 18px;
        font-weight: 600;
        color: #475569;
        margin-bottom: 8px;
    }

    .empty-state p {
        font-size: 14px;
        color: #64748b;
        margin-bottom: 24px;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .table-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .table-actions {
            width: 100%;
            justify-content: space-between;
        }

        .pagination-wrapper {
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .modal-dialog {
            width: 95%;
            margin: 20px;
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
                        <i class="fas fa-users"></i>
                        Branch PICs - {{ $branch->name }}
                    </h3>
                    <div class="table-actions">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPicModal">
                            <i class="fas fa-plus"></i>
                            Assign PIC
                        </button>
                    </div>
                </div>

                <div class="table-wrapper">
                    @if($pics->count() > 0)
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pics as $pic)
                                <tr class="{{ $pic->is_primary ? 'table-primary' : '' }}">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; font-size: 14px;">
                                                {{ substr($pic->user->name, 0, 1) }}
                                            </div>
                                            <div>
                                                <div class="font-weight-bold">{{ $pic->user->name }}</div>
                                                @if($pic->is_primary)
                                                    <span class="badge badge-warning">Primary</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-sm">
                                            <div>From: {{ \Carbon\Carbon::parse($pic->assigned_date)->format('d/M/Y') }}</div>
                                            @if($pic->end_date)
                                                <div>To: {{ \Carbon\Carbon::parse($pic->end_date)->format('d/M/Y') }}</div>
                                            @else
                                                <div class="text-muted">Ongoing</div>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @if($pic->is_active)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            @if(!$pic->is_primary)
                                                <button class="btn btn-sm btn-warning" 
                                                        onclick="setPrimaryPic({{ $pic->id }})"
                                                        title="Set as Primary">
                                                    <i class="fas fa-star"></i>
                                                </button>
                                            @endif
                                            <button class="btn btn-sm btn-info" 
                                                    onclick="showPic({{ $pic->id }})"
                                                    title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" 
                                                    onclick="deletePic({{ $pic->id }})"
                                                    title="Remove">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="empty-state">
                            <i class="fas fa-users"></i>
                            <h5>No PICs Assigned</h5>
                            <p>Assign people in charge for this branch to manage operations.</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPicModal">
                                <i class="fas fa-plus"></i>
                                Assign First PIC
                            </button>
                        </div>
                    @endif
                </div>

                @if($pics->count() > 0)
                <!-- Pagination -->
                <div class="pagination-wrapper">
                    <div class="pagination-info">
                        <span class="info-text">
                            Showing {{ $pics->firstItem() ?? 0 }} to {{ $pics->lastItem() ?? 0 }} 
                            of {{ $pics->total() }} entries
                        </span>
                    </div>
                    <div class="pagination-controls">
                        {{ $pics->links() }}
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit PIC Modal -->
<div class="modal fade" id="addPicModal" tabindex="-1" aria-hidden="true" aria-labelledby="addPicModalLabel">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPicModalLabel">Assign PIC</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="picForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="user_id" class="form-label">User <span class="text-danger">*</span></label>
                                <select class="form-select" id="user_id" name="user_id" required>
                                    <option value="">Select User</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    

                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="assigned_date" class="form-label">Assigned Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="assigned_date" name="assigned_date" value="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_primary" name="is_primary">
                                <label class="form-check-label" for="is_primary">
                                    Set as Primary PIC
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                                <label class="form-check-label" for="is_active">
                                    Active
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>
                        Assign PIC
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Show PIC Modal -->
<div class="modal fade" id="showPicModal" tabindex="-1" aria-hidden="true" aria-labelledby="showPicModalLabel">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="showPicModalLabel">PIC Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="showPicModalBody">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="editPicBtn">Edit PIC</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit PIC Modal -->
<div class="modal fade" id="editPicModal" tabindex="-1" aria-hidden="true" aria-labelledby="editPicModalLabel">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editPicModalLabel">Edit PIC</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editPicForm">
                <div class="modal-body" id="editPicModalBody">
                    <!-- Content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>
                        Update PIC
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fix modal accessibility issues
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('show.bs.modal', function() {
            // Remove aria-hidden when modal is shown
            this.removeAttribute('aria-hidden');
            this.setAttribute('aria-modal', 'true');
        });
        
        modal.addEventListener('hide.bs.modal', function() {
            // Remove focus from any focused element inside modal
            const focusedElement = this.querySelector(':focus');
            if (focusedElement) {
                focusedElement.blur();
            }
            
            // Add aria-hidden when modal is hidden
            this.setAttribute('aria-hidden', 'true');
            this.removeAttribute('aria-modal');
        });
        
        modal.addEventListener('hidden.bs.modal', function() {
            // Ensure no focus remains after modal is completely hidden
            const focusedElement = this.querySelector(':focus');
            if (focusedElement) {
                focusedElement.blur();
            }
            
            // Return focus to trigger element if available
            const triggerElement = document.querySelector('[data-bs-target="#' + this.id + '"]');
            if (triggerElement) {
                triggerElement.focus();
            }
        });
    });
    
    // Form submission
    const form = document.getElementById('picForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            fetch('{{ route("company.branches.pics.store", $branch->id) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    location.reload();
                } else {
                    alert('Gagal: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan');
            });
        });
    }
    
    // Edit PIC form submission
    const editForm = document.getElementById('editPicForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            const picId = this.dataset.picId;
            
            fetch(`/company/branches/{{ $branch->id }}/pics/${picId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Close modal properly before reload
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editPicModal'));
                    if (modal) {
                        modal.hide();
                    }
                    
                    // Reload after modal is closed
                    setTimeout(() => {
                        location.reload();
                    }, 300);
                } else {
                    alert('Gagal: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan');
            });
        });
    }
});

function setPrimaryPic(id) {
    if (confirm('Apakah kamu yakin ingin menjadikan PIC ini sebagai primary?')) {
        fetch('{{ route("company.branches.pics.set-primary", [$branch->id, ":id"]) }}'.replace(':id', id), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                location.reload();
            } else {
                alert('Gagal: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan');
        });
    }
}

function showPic(id) {
    fetch(`/company/branches/{{ $branch->id }}/pics/${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const pic = data.data;
                document.getElementById('showPicModalBody').innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">User</label>
                                <div class="p-2 bg-light rounded">${pic.user ? pic.user.name : '-'}</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Status</label>
                                <div class="p-2 bg-light rounded">
                                    <span class="badge ${pic.is_active ? 'bg-success' : 'bg-danger'}">
                                        ${pic.is_active ? 'Active' : 'Inactive'}
                                    </span>
                                    ${pic.is_primary ? '<span class="badge bg-primary ms-2">Primary</span>' : ''}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Assigned Date</label>
                                <div class="p-2 bg-light rounded">${pic.assigned_date ? pic.assigned_date.split('T')[0].split('-').reverse().join('/') : '-'}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">End Date</label>
                                <div class="p-2 bg-light rounded">${pic.end_date ? pic.end_date.split('T')[0].split('-').reverse().join('/') : '-'}</div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Notes</label>
                                <div class="p-2 bg-light rounded" style="min-height: 80px;">${pic.notes || '-'}</div>
                            </div>
                        </div>
                    </div>
                `;
                
                // Set edit button to open edit modal
                document.getElementById('editPicBtn').onclick = () => editPic(id);
                
                // Show modal
                new bootstrap.Modal(document.getElementById('showPicModal')).show();
            } else {
                alert('Gagal: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan');
        });
}

function editPic(id) {
    fetch(`/company/branches/{{ $branch->id }}/pics/${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const pic = data.data;
                document.getElementById('editPicModalBody').innerHTML = `
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="edit_user_id" class="form-label">User <span class="text-danger">*</span></label>
                                <select class="form-select" id="edit_user_id" name="user_id" required>
                                    <option value="">Select User</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" ${pic.user_id == {{ $user->id }} ? 'selected' : ''}>{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_assigned_date" class="form-label">Assigned Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="edit_assigned_date" name="assigned_date" value="${pic.assigned_date ? pic.assigned_date.split('T')[0] : ''}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="edit_end_date" name="end_date" value="${pic.end_date ? pic.end_date.split('T')[0] : ''}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="edit_is_primary" name="is_primary" value="1" ${pic.is_primary ? 'checked' : ''}>
                                    <label class="form-check-label" for="edit_is_primary">
                                        Set as Primary PIC
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active" value="1" ${pic.is_active ? 'checked' : ''}>
                                    <label class="form-check-label" for="edit_is_active">
                                        Is Active
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="edit_notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="edit_notes" name="notes" rows="3">${pic.notes || ''}</textarea>
                            </div>
                        </div>
                    </div>
                `;
                
                // Set form data attribute
                document.getElementById('editPicForm').dataset.picId = id;
                
                // Show modal with proper focus management
                const modal = new bootstrap.Modal(document.getElementById('editPicModal'));
                modal.show();
                
                // Ensure proper focus after modal is shown
                setTimeout(() => {
                    const firstInput = document.querySelector('#editPicModal input:not([type="hidden"]):not([disabled])');
                    if (firstInput) {
                        firstInput.focus();
                    }
                }, 150);
            } else {
                alert('Gagal: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan');
        });
}

function deletePic(id) {
    if (confirm('Apakah kamu yakin ingin menghapus PIC ini?')) {
        fetch('{{ route("company.branches.pics.destroy", [$branch->id, ":id"]) }}'.replace(':id', id), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                location.reload();
            } else {
                alert('Gagal: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan');
        });
    }
}
</script>
@endpush
