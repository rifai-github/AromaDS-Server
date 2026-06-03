@extends('layouts.app')

@section('title', 'Brand Variants')
@section('breadcrumb', 'Home / Warehouse / Brand Variants')

@section('content')
<style>
    /* Global overflow control */
    html, body {
        overflow-x: hidden;
        max-width: 100vw;
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
        background-color: #ef4444;
        color: white;
    }
    
    .btn-danger:hover {
        background-color: #dc2626;
    }

    .btn-sm {
        padding: 4px 8px;
        font-size: 12px;
    }

    /* Table Container */
    .table-container {
        background: white;
        border-radius: 0 0 10px 10px;
        position: relative;
        width: 100%;
        overflow-x: auto;
    }
    
    /* Responsive Table */
    .responsive-table {
        min-width: 1200px;
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }
    
    .responsive-table th,
    .responsive-table td {
        padding: 12px 16px;
        text-align: left;
        border-bottom: 1px solid #e5e7eb;
        white-space: nowrap;
    }
    
    .responsive-table th {
        background-color: #214589;
        color: white;
        font-weight: 600;
        position: sticky;
        top: 0;
        z-index: 10;
    }
    
    .responsive-table tbody tr:hover {
        background-color: #eff6ff;
    }

    /* Modal Styles */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        backdrop-filter: blur(2px);
    }
    
    .modal-overlay.show {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .modal-container {
        background: white;
        border-radius: 12px;
        max-width: 90vw;
        max-height: 90vh;
        width: 600px;
        display: flex;
        flex-direction: column;
    }
    
    .modal-header {
        background: #214589;
        color: white;
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .modal-title {
        font-size: 18px;
        font-weight: 600;
        margin: 0;
    }
    
    .modal-close {
        background: none;
        border: none;
        color: white;
        font-size: 24px;
        cursor: pointer;
    }
    
    .modal-body {
        padding: 20px;
        overflow-y: auto;
    }
    
    .modal-footer {
        padding: 20px;
        background: #f9fafb;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: flex-end;
        gap: 12px;
    }

    /* Form Styles */
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #1f2937;
        font-size: 14px;
    }
    
    .form-input {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        box-sizing: border-box;
    }
    
    .form-input:focus {
        outline: none;
        border-color: #214589;
    }
    
    /* Status Badge */
    .status-badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .status-active {
        background-color: #d1fae5;
        color: #065f46;
    }
    
    .status-inactive {
        background-color: #fee2e2;
        color: #991b1b;
    }

    .badge {
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
    }
    .badge-success { background-color: #d1fae5; color: #065f46; }
    .badge-danger { background-color: #fee2e2; color: #991b1b; }

    /* Detail Modal Styles */
    .detail-row {
        display: flex;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px solid #f3f4f6;
    }
    .detail-label { font-weight: 600; color: #4b5563; }
    .detail-value { text-align: right; }
</style>

<div class="flex flex-col w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px]">
        
        <!-- Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <h1 class="text-xl font-semibold text-[#214589]">Brand Variants</h1>
            <div class="flex gap-2">
                <button id="bulkDeleteBtn" class="btn btn-danger btn-sm" style="display: none;" onclick="bulkDelete()">
                    <i class="fas fa-trash"></i> Bulk Delete (<span id="selectedCount">0</span>)
                </button>
                <button id="btnAddVariant" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Variant
                </button>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="flex flex-row justify-between items-center w-full p-4 bg-white border-t border-gray-100">
            <div class="relative w-full md:w-64">
                <input type="text" id="searchInput" class="form-input" placeholder="Search variants...">
            </div>
        </div>

        <!-- Table Container -->
        <div class="table-container">
            <table class="responsive-table" id="brand-variants-table">
                <thead>
                    <tr>
                        <th data-no-filter data-no-sort style="width: 40px;">
                            <input type="checkbox" id="selectAll" class="w-4 h-4 rounded border-gray-300">
                        </th>
                        <th data-column="id">ID</th>
                        <th data-column="brandLine.option_name">Brand Line</th>
                        <th data-column="name">Variant Name</th>
                        <th data-column="is_active">Status</th>
                        <th data-column="createdBy.name">Created By</th>
                        <th data-column="created_at" data-type="date">Created At</th>
                        <th data-column="updatedBy.name">Updated By</th>
                        <th data-column="updated_at" data-type="date">Updated At</th>
                        <th data-no-filter data-no-sort>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($brandVariants ?? [] as $variant)
                    <tr data-id="{{ $variant->id }}">
                        <td onclick="event.stopPropagation()">
                            <input type="checkbox" class="row-checkbox w-4 h-4 rounded border-gray-300" value="{{ $variant->id }}">
                        </td>
                        <td onclick="openViewModal({{ $variant->id }})">{{ $variant->id }}</td>
                        <td onclick="openViewModal({{ $variant->id }})">
                            <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                                {{ $variant->brandLine->option_name ?? '-' }}
                            </span>
                        </td>
                        <td onclick="openViewModal({{ $variant->id }})">{{ $variant->name }}</td>
                        <td onclick="openViewModal({{ $variant->id }})">
                            <span class="status-badge {{ $variant->is_active ? 'status-active' : 'status-inactive' }}">
                                {{ $variant->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td onclick="openViewModal({{ $variant->id }})">{{ $variant->createdBy->name ?? '-' }}</td>
                        <td onclick="openViewModal({{ $variant->id }})">
                            @if($variant->created_at)
                                {{ $variant->created_at->format('d/M/Y') }}<br>
                                <small>at {{ $variant->created_at->format('H.i') }} WIB</small>
                            @else - @endif
                        </td>
                        <td onclick="openViewModal({{ $variant->id }})">{{ $variant->updatedBy->name ?? '-' }}</td>
                        <td onclick="openViewModal({{ $variant->id }})">
                            @if($variant->updated_at)
                                {{ $variant->updated_at->format('d/M/Y') }}<br>
                                <small>at {{ $variant->updated_at->format('H.i') }} WIB</small>
                            @else - @endif
                        </td>
                        <td onclick="event.stopPropagation()">
                            <div class="flex gap-2">
                                <button class="btn btn-secondary btn-sm" onclick="openEditModal({{ $variant->id }})">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="deleteVariant({{ $variant->id }})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center py-8 text-gray-400">No brand variants found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header">
            <h5 class="modal-title" id="modalTitle">Modal</h5>
            <button type="button" class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body" id="modalBody"></div>
        <div class="modal-footer" id="modalFooter"></div>
    </div>
</div>

<script>
    var brandLines = {!! json_encode($brandLines) !!};

    function openModal(title) {
        document.getElementById('modalTitle').textContent = title;
        document.getElementById('modalOverlay').classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        document.getElementById('modalOverlay').classList.remove('show');
        document.body.style.overflow = 'auto';
    }

    function openCreateModal() {
        openModal('Add New Variant');
        let options = '<option value="">Select Brand Line</option>';
        brandLines.forEach(bl => options += `<option value="${bl.id}">${bl.option_name}</option>`);

        document.getElementById('modalBody').innerHTML = `
            <form id="variantForm">
                <div class="form-group">
                    <label class="form-label">Brand Line *</label>
                    <select name="brand_line_id" class="form-input" required>${options}</select>
                </div>
                <div class="form-group">
                    <label class="form-label">Variant Name *</label>
                    <input type="text" name="name" class="form-input" required placeholder="Ex: Signature Artisan">
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-input" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="is_active" class="form-input">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
            </form>
        `;
        document.getElementById('modalFooter').innerHTML = `
            <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
            <button class="btn btn-primary" onclick="submitForm()">Save</button>
        `;
    }

    function openViewModal(id) {
        openModal('View Details');
        fetch(`/warehouse/brand-variants/${id}`, { headers: {'X-Requested-With':'XMLHttpRequest'} })
        .then(r => r.json())
        .then(res => {
            let v = res.data;
            document.getElementById('modalBody').innerHTML = `
                <div class="detail-row"><span class="detail-label">ID</span><span class="detail-value">${v.id}</span></div>
                <div class="detail-row"><span class="detail-label">Brand Line</span><span class="detail-value">${v.brand_line.option_name}</span></div>
                <div class="detail-row"><span class="detail-label">Name</span><span class="detail-value">${v.name}</span></div>
                <div class="detail-row"><span class="detail-label">Description</span><span class="detail-value">${v.description || '-'}</span></div>
                <div class="detail-row"><span class="detail-label">Status</span><span class="detail-value"><span class="badge badge-${v.is_active?'success':'danger'}">${v.is_active?'Active':'Inactive'}</span></span></div>
                <div class="detail-row"><span class="detail-label">Created By</span><span class="detail-value">${v.created_by?.name || '-'}</span></div>
                <div class="detail-row"><span class="detail-label">Last Updated By</span><span class="detail-value">${v.updated_by?.name || '-'}</span></div>
            `;
            document.getElementById('modalFooter').innerHTML = `
                <button class="btn btn-secondary" onclick="closeModal()">Close</button>
                <button class="btn btn-primary" onclick="openEditModal(${id})">Edit</button>
            `;
        });
    }

    function openEditModal(id) {
        openModal('Edit Variant');
        fetch(`/warehouse/brand-variants/${id}/edit`, { headers: {'X-Requested-With':'XMLHttpRequest'} })
        .then(r => r.json())
        .then(res => {
            let v = res.data;
            let options = '<option value="">Select Brand Line</option>';
            brandLines.forEach(bl => options += `<option value="${bl.id}" ${bl.id == v.brand_line_id ? 'selected':''}>${bl.option_name}</option>`);

            document.getElementById('modalBody').innerHTML = `
                <form id="variantForm">
                    <input type="hidden" name="id" value="${id}">
                    <div class="form-group">
                        <label class="form-label">Brand Line *</label>
                        <select name="brand_line_id" class="form-input" required>${options}</select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Variant Name *</label>
                        <input type="text" name="name" class="form-input" value="${v.name}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-input" rows="3">${v.description || ''}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="is_active" class="form-input">
                            <option value="1" ${v.is_active?'selected':''}>Active</option>
                            <option value="0" ${!v.is_active?'selected':''}>Inactive</option>
                        </select>
                    </div>
                </form>
            `;
            document.getElementById('modalFooter').innerHTML = `
                <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button class="btn btn-primary" onclick="submitForm()">Update</button>
            `;
        });
    }

    function submitForm() {
        let form = document.getElementById('variantForm');
        let formData = new FormData(form);
        let id = formData.get('id');
        let data = Object.fromEntries(formData.entries());
        data._token = '{{ csrf_token() }}';
        
        let url = id ? `/warehouse/brand-variants/${id}` : '/warehouse/brand-variants';
        let method = id ? 'PUT' : 'POST';

        fetch(url, {
            method: id ? 'POST' : 'POST', // Resource controllers sometimes want id in URL + method field
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({...data, _method: method})
        })
        .then(r => r.json())
        .then(res => {
            if(res.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: res.message || 'Data saved successfully',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: res.message || 'Error saving data'
                });
            }
        });
    }

    function deleteVariant(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#214589',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/warehouse/brand-variants/${id}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({_method: 'DELETE'})
                }).then(r => {
                    if (r.ok) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: 'Brand variant has been deleted.',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => location.reload());
                    } else {
                        return r.json().then(res => {
                            Swal.fire({
                                icon: 'error',
                                title: 'Cannot Delete',
                                text: res.message || 'Error deleting variant'
                            });
                        });
                    }
                }).catch(err => {
                    console.error('Error:', err);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An unexpected error occurred.'
                    });
                });
            }
        });
    }

    function bulkDelete() {
        let selected = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
        if(selected.length === 0) return;

        Swal.fire({
            title: 'Delete Multiple?',
            text: `Are you sure you want to delete ${selected.length} selected variants?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#214589',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete them!'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('/warehouse/brand-variants/bulk-delete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ids: selected})
                }).then(r => r.json())
                .then(res => {
                    Swal.fire({
                        icon: res.success ? 'success' : 'info',
                        title: 'Bulk Action Results',
                        text: res.message
                    }).then(() => location.reload());
                })
                .catch(err => {
                    console.error('Error:', err);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error during bulk delete'
                    });
                });
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('btnAddVariant').addEventListener('click', openCreateModal);

        // Selection Logic
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.getElementsByClassName('row-checkbox');
        const bulkBtn = document.getElementById('bulkDeleteBtn');
        const countSpan = document.getElementById('selectedCount');

        function updateBulkUI() {
            let checked = document.querySelectorAll('.row-checkbox:checked').length;
            bulkBtn.style.display = checked > 0 ? 'inline-flex' : 'none';
            countSpan.textContent = checked;
        }

        selectAll.addEventListener('change', function() {
            Array.from(checkboxes).forEach(cb => cb.checked = this.checked);
            updateBulkUI();
        });

        document.addEventListener('change', function(e) {
            if(e.target.classList.contains('row-checkbox')) {
                updateBulkUI();
            }
        });
        
        // Search
        document.getElementById('searchInput').addEventListener('keyup', function() {
            let q = this.value.toLowerCase();
            document.querySelectorAll('tbody tr').forEach(tr => {
                tr.style.display = tr.innerText.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    });
</script>
@endsection
