@extends('layouts.app')

@section('title', 'Packaging Sizes')
@section('breadcrumb', 'Home / Warehouse / Master Data / Packaging Sizes')

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

    .btn-danger {
        background-color: #dc2626;
        color: white;
    }

    .btn-danger:hover {
        background-color: #b91c1c;
    }

    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
    }

    /* Table Container */
    .table-container {
        background: white;
        border-radius: 10px;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        overflow: hidden;
    }

    .table-header {
        padding: 24px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background-color: #f8fafc;
    }

    .table-title {
        font-size: 20px;
        font-weight: 600;
        color: #111827;
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

    .table-wrapper {
        overflow-x: auto;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 1600px;
        table-layout: auto;
    }

    .data-table th {
        background-color: #f8fafc;
        color: #374151;
        font-weight: 600;
        padding: 16px 20px;
        text-align: left;
        border-bottom: 1px solid #e5e7eb;
        font-size: 14px;
        white-space: nowrap;
    }

    .data-table td {
        padding: 16px 20px;
        border-bottom: 1px solid #f3f4f6;
        color: #374151;
        font-size: 14px;
    }

    .data-table tr:hover {
        background-color: #f9fafb;
    }

    .data-table tr:last-child td {
        border-bottom: none;
    }

    /* Badge Styles */
    .badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
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
        cursor: pointer;
        color: #6b7280;
        padding: 4px;
        border-radius: 4px;
        transition: all 0.2s ease;
    }

    .modal-close:hover {
        background-color: #f3f4f6;
        color: #374151;
    }

    .modal-body {
        padding: 24px !important;
        overflow-y: auto !important;
        flex: 1 !important;
        max-height: calc(90vh - 160px) !important;
        position: relative !important;
        z-index: 1 !important;
    }

    .modal-footer {
        padding: 20px 24px !important;
        border-top: 2px solid #e5e7eb !important;
        display: flex !important;
        justify-content: flex-end !important;
        gap: 12px !important;
        flex-shrink: 0 !important;
        background-color: #f8f9fa !important;
        border-radius: 0 0 8px 8px !important;
        min-height: 80px !important;
        position: relative !important;
        z-index: 999 !important;
        margin-top: 0 !important;
        clear: both !important;
    }

    .modal-footer .btn {
        min-width: 100px !important;
        font-weight: 500 !important;
        padding: 10px 20px !important;
        border-radius: 6px !important;
        font-size: 14px !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 8px !important;
    }

    .modal-footer .btn-secondary {
        background-color: #6c757d !important;
        border-color: #6c757d !important;
        color: white !important;
    }

    .modal-footer .btn-secondary:hover {
        background-color: #5a6268 !important;
        border-color: #545b62 !important;
    }

    .modal-footer .btn-primary {
        background-color: #214589 !important;
        border-color: #214589 !important;
        color: white !important;
    }

    .modal-footer .btn-primary:hover {
        background-color: #1e3a8a !important;
        border-color: #1e3a8a !important;
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
        transition: border-color 0.2s ease;
    }

    .form-control:focus {
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
        margin: 0;
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
                        <i class="fas fa-box"></i>
                        Packaging Sizes
                    </h3>
                    <div class="table-actions">
                        <a href="{{ route('warehouse.master-products.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            Back to List
                        </a>
                        <button class="btn btn-primary" onclick="openAddModal()">
                            <i class="fas fa-plus"></i>
                            Add Packaging Size
                        </button>
                    </div>
                </div>

                <div class="table-wrapper">
                    @if($packagingSizes->count() > 0)
                        <table class="data-table" id="packagingSizesTable">
                            <thead>
                                <tr>
                                    <th data-column="name" style="min-width: 200px;">Name</th>
                                    <th data-column="code" style="min-width: 100px;">Code</th>
                                    <th data-column="description" style="min-width: 300px;">Description</th>
                                    <th data-column="sort_order" data-type="numeric" style="width: 100px;">Sort Order</th>
                                    <th data-no-filter style="width: 120px;">Products</th>
                                    <th data-column="is_active" style="width: 100px;">Status</th>
                                    <th data-column="createdBy__name" style="min-width: 150px;">Created By</th>
                                    <th data-column="created_at" data-type="date" style="min-width: 180px;">Created At</th>
                                    <th data-column="updatedBy__name" style="min-width: 150px;">Last Updated By</th>
                                    <th data-column="updated_at" data-type="date" style="min-width: 180px;">Last Updated At</th>
                                    <th data-no-filter style="width: 100px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($packagingSizes as $packagingSize)
                                <tr onclick="showPackagingSize({{ $packagingSize->id }})" style="cursor: pointer;">
                                    <td>
                                        <div class="font-weight-bold">{{ $packagingSize->name }}</div>
                                    </td>
                                    <td>
                                        <span class="badge badge-info">{{ $packagingSize->code }}</span>
                                    </td>
                                    <td>
                                        {{ $packagingSize->description ?? '-' }}
                                    </td>
                                    <td>
                                        {{ $packagingSize->sort_order }}
                                    </td>
                                    <td>
                                        {{ $packagingSize->product_count }} products
                                    </td>
                                    <td>
                                        @if($packagingSize->is_active)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td>{{ $packagingSize->createdBy->name ?? '-' }}</td>
                                    <td>
                                        @if($packagingSize->created_at)
                                            {{ \Carbon\Carbon::parse($packagingSize->created_at)->format('d M Y') }}<br>
                                            at {{ \Carbon\Carbon::parse($packagingSize->created_at)->format('H.i') }} WIB
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $packagingSize->updatedBy->name ?? '-' }}</td>
                                    <td>
                                        @if($packagingSize->updated_at)
                                            {{ \Carbon\Carbon::parse($packagingSize->updated_at)->format('d M Y') }}<br>
                                            at {{ \Carbon\Carbon::parse($packagingSize->updated_at)->format('H.i') }} WIB
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-danger" onclick="deletePackagingSize({{ $packagingSize->id }}, event)" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="empty-state">
                            <i class="fas fa-box"></i>
                            @if(request()->hasAny(['name', 'code', 'description', 'sort_order', 'is_active', 'search']))
                                <h5>No Results Found</h5>
                                <p>We couldn't find any packaging sizes matching your search.</p>
                                <a href="{{ route('warehouse.packaging-sizes.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-undo"></i>
                                    Clear Filters
                                </a>
                            @else
                                <h5>No Packaging Sizes Found</h5>
                                <p>Start by adding your first packaging size.</p>
                                <button class="btn btn-primary" onclick="openAddModal()">
                                    <i class="fas fa-plus"></i>
                                    Add Packaging Size
                                </button>
                            @endif
                        </div>
                    @endif
                </div>

                @if($packagingSizes->count() > 0)
                    <div class="pagination-wrapper">
                        <div class="pagination-info">
                            Showing {{ $packagingSizes->firstItem() }} to {{ $packagingSizes->lastItem() }} of {{ $packagingSizes->total() }} results
                        </div>
                        <div class="pagination-controls">
                            {{ $packagingSizes->links() }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal-overlay" id="packagingSizeModal">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="modalTitle">Add Packaging Size</h5>
            <button type="button" class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form id="packagingSizeForm" method="POST">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="code" class="form-label">Code <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="code" name="code" required>
                </div>
                
                <div class="form-group">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="sort_order" class="form-label">Sort Order <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="sort_order" name="sort_order" min="0" value="0" required>
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
                <button type="button" class="btn btn-primary" onclick="submitPackagingSizeForm()">
                    <i class="fas fa-save me-1"></i>
                    <span id="saveButtonText">Save Packaging Size</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Show Modal -->
<div class="modal-overlay" id="showPackagingSizeModal">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Packaging Size Details</h5>
            <button type="button" class="modal-close" onclick="closeShowModal()">&times;</button>
        </div>
        <div class="modal-body" id="showPackagingSizeContent">
            <!-- Content will be loaded here -->
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeShowModal()">Close</button>
            <button type="button" class="btn btn-primary" onclick="editPackagingSizeFromShow()">
                <i class="fas fa-edit me-1"></i>
                Edit Packaging Size
            </button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
let currentPackagingSizeId = null;

function openAddModal() {
    currentPackagingSizeId = null;
    document.getElementById('modalTitle').textContent = 'Add Packaging Size';
    document.getElementById('saveButtonText').textContent = 'Save Packaging Size';
    document.getElementById('packagingSizeForm').reset();
    document.getElementById('is_active').checked = true;
    document.getElementById('packagingSizeModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function showPackagingSize(id) {
    fetch(`/warehouse/packaging-sizes/${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const packagingSize = data.data;
                const content = `
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Name</label>
                                <p class="form-control-plaintext">${packagingSize.name}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Code</label>
                                <p class="form-control-plaintext">${packagingSize.code}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <p class="form-control-plaintext">${packagingSize.description || 'No description'}</p>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Sort Order</label>
                                <p class="form-control-plaintext">${packagingSize.sort_order}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <p class="form-control-plaintext">
                                    <span class="badge ${packagingSize.is_active ? 'badge-success' : 'badge-danger'}">
                                        ${packagingSize.is_active ? 'Active' : 'Inactive'}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Products Count</label>
                        <p class="form-control-plaintext">${packagingSize.products_count || 0} products</p>
                    </div>
                `;
                
                document.getElementById('showPackagingSizeContent').innerHTML = content;
                document.getElementById('showPackagingSizeModal').classList.add('show');
                document.body.style.overflow = 'hidden';
                currentPackagingSizeId = id;
            } else {
                alert('Error loading packaging size details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Something went wrong');
        });
}

function editPackagingSize(id) {
    fetch(`/warehouse/packaging-sizes/${id}/edit`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const packagingSize = data.data;
                
                // Fill form with packaging size data
                document.getElementById('name').value = packagingSize.name;
                document.getElementById('code').value = packagingSize.code;
                document.getElementById('description').value = packagingSize.description || '';
                document.getElementById('sort_order').value = packagingSize.sort_order;
                document.getElementById('is_active').checked = packagingSize.is_active;
                
                // Update modal title and form action
                document.getElementById('modalTitle').textContent = 'Edit Packaging Size';
                document.getElementById('saveButtonText').textContent = 'Update Packaging Size';
                currentPackagingSizeId = id;
                
                // Show edit modal
                document.getElementById('packagingSizeModal').classList.add('show');
                document.body.style.overflow = 'hidden';
            } else {
                alert('Error loading packaging size details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Something went wrong');
        });
}

function editPackagingSizeFromShow() {
    closeShowModal();
    editPackagingSize(currentPackagingSizeId);
}

function closeShowModal() {
    document.getElementById('showPackagingSizeModal').classList.remove('show');
    document.body.style.overflow = 'auto';
}

function deletePackagingSize(id, event) {
    if (event) {
        event.stopPropagation();
    }
    if (confirm('Are you sure you want to delete this packaging size?')) {
        fetch(`/warehouse/packaging-sizes/${id}`, {
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
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Something went wrong');
        });
    }
}

function closeModal() {
    document.getElementById('packagingSizeModal').classList.remove('show');
    document.body.style.overflow = 'auto';
}

// Form submission
// Form submission
// Form submission
function submitPackagingSizeForm() {
    const form = document.getElementById('packagingSizeForm');
    if (!form) return;

    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    
    // Handle checkbox properly
    data.is_active = document.getElementById('is_active').checked;
    
    const url = currentPackagingSizeId 
        ? `/warehouse/packaging-sizes/${currentPackagingSizeId}`
        : '/warehouse/packaging-sizes';
    const method = currentPackagingSizeId ? 'PUT' : 'POST';
    
    // Show loading state
    const submitBtn = form.querySelector('button[onclick="submitPackagingSizeForm()"]');
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Saving...';
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!csrfToken) {
        alert('Error: CSRF token not found. Please refresh the page.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
        return;
    }

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            ...data,
            _method: method
        })
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(err => {
                throw new Error(err.message || 'Server error');
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.status === 'success') {
            closeModal();
            location.reload();
        } else {
            alert('Error: ' + data.message);
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error: ' + error.message);
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    });
}

// Close modal when clicking outside
document.getElementById('packagingSizeModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>
@endpush
