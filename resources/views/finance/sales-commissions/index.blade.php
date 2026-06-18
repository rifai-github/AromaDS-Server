@extends('layouts.app')

@section('title', 'Sales Commissions')
@section('breadcrumb', 'Home / Finance / Sales Commissions')

@section('content')
<style>
    /* Responsive Table */
    .table-container {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        border-radius: 0 0 10px 10px;
    }
    
    .responsive-table {
        min-width: 1200px;
        width: 100%;
        border-collapse: collapse;
    }
    
    .responsive-table th,
    .responsive-table td {
        padding: 12px 8px;
        text-align: left;
        border-bottom: 1px solid #e5e7eb;
        white-space: nowrap;
        font-size: 14px;
    }
    
    .responsive-table th {
        background-color: #225fd3;
        color: white;
        font-weight: 600;
        font-size: 13px;
        position: sticky;
        top: 0;
        z-index: 10;
    }
    
    .responsive-table tbody tr:hover {
        background-color: #eff6ff;
        transition: background-color 0.2s ease;
    }
    
    .responsive-table tbody tr {
        cursor: pointer;
    }
    
    /* Mobile Responsive */
    @media (max-width: 768px) {
        .responsive-table th,
        .responsive-table td {
            padding: 8px 6px;
            font-size: 12px;
        }
        
        .responsive-table {
            min-width: 1000px;
        }
        
        .controls-row {
            flex-direction: column;
            gap: 10px;
            align-items: stretch;
        }
        
        .controls-left {
            justify-content: space-between;
        }
        
        .pagination-controls {
            justify-content: center;
            flex-wrap: wrap;
            gap: 5px;
        }
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
    
    .btn-outline {
        background-color: white;
        color: #214589;
        border: 2px solid #214589;
        font-weight: 500;
    }
    
    .btn-outline:hover {
        background-color: #214589;
        color: white;
    }
    
    .btn-danger {
        background-color: #ef4444;
        color: white;
    }
    
    .btn-danger:hover {
        background-color: #dc2626;
    }
    
    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
    }
    
    /* Status Badges */
    .badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .badge-pending {
        background-color: #fef3c7;
        color: #92400e;
    }
    
    .badge-valid {
        background-color: #d1fae5;
        color: #065f46;
    }
    
    .badge-void {
        background-color: #fee2e2;
        color: #991b1b;
    }
    
    .badge-paid {
        background-color: #dbeafe;
        color: #1e40af;
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
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        max-width: 90vw;
        max-height: 90vh;
        width: 600px;
        overflow: hidden;
        position: relative;
    }
    
    .modal-header {
        background: #214589;
        color: white;
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: sticky;
        top: 0;
        z-index: 20;
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
        padding: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: background-color 0.2s ease;
    }
    
    .modal-close:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }
    
    .modal-body {
        padding: 20px;
        overflow-y: auto;
        max-height: calc(90vh - 140px);
    }
    
    .modal-footer {
        padding: 20px;
        background: #f9fafb;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: center;
        gap: 20px;
        position: sticky;
        bottom: 0;
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
        transition: border-color 0.2s ease;
        box-sizing: border-box;
    }
    
    .form-input:focus {
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
        transition: border-color 0.2s ease;
        box-sizing: border-box;
        background-color: white;
    }
    
    .form-select:focus {
        outline: none;
        border-color: #214589;
        box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
    }
    
    /* Pagination Styles */
    .pagination-controls {
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }
    
    .page-number {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s ease;
    }
    
    .page-number.active {
        background-color: #214589;
        color: white;
    }
    
    .page-number:not(.active) {
        color: #6b7280;
    }
    
    .page-number:not(.active):hover {
        background-color: #f3f4f6;
        color: #214589;
    }
    
    .page-dropdown-container {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        white-space: nowrap;
    }
    
    .page-dropdown-container span {
        display: inline;
        white-space: nowrap;
    }
    
    /* Detail View Styles */
    .detail-item {
        margin-bottom: 1rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #f3f4f6;
    }
    
    .detail-item:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }
    
    .detail-value {
        color: #6b7280;
        font-size: 14px;
        line-height: 1.5;
        margin-top: 4px;
        word-wrap: break-word;
    }
    
    /* Mobile Modal Adjustments */
    @media (max-width: 768px) {
        .modal-container {
            width: 95vw;
            max-height: 95vh;
        }
        
        .modal-header {
            padding: 15px;
        }
        
        .modal-body {
            padding: 15px;
            max-height: calc(95vh - 120px);
        }
        
        .modal-footer {
            padding: 15px;
            flex-direction: column;
        }
        
        .modal-footer .btn {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<div class="flex flex-col w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">
        
        <!-- Sales Commissions Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Sales Commissions</h1>
            </div>
            
            <button class="btn btn-primary" onclick="openCreateModal()">
                <i class="fas fa-plus"></i>
                <span>Add New Commission</span>
            </button>
        </div>
        
        <!-- Controls Row -->
        <div class="flex flex-row justify-between items-center w-full p-4 bg-white controls-row">
            <div class="flex flex-row justify-start items-center w-full controls-left">
                <div class="flex flex-row justify-start items-center w-auto">
                    <div class="flex flex-row items-center">
                        <input type="checkbox" id="selectAll" class="w-4 h-4 bg-white border border-[#888888] rounded cursor-pointer">
                        <label for="selectAll" class="ml-2 text-sm text-[#3d3d3d] cursor-pointer">Select all</label>
                    </div>
                </div>
                
                <button class="btn btn-secondary ml-4" onclick="deleteSelected()">
                    <i class="fas fa-trash"></i>
                    <span>Delete</span>
                </button>
            </div>
            
        </div>
        
        <!-- Table Container -->
        <div class="w-full bg-white rounded-b-[10px] table-container">
            <table class="responsive-table">
                <!-- Table Header -->
                <thead>
                    <tr>
                        <th class="w-[50px]" data-no-filter>
                            <input type="checkbox" id="headerSelectAll" class="w-4 h-4 bg-white border border-[#888888] rounded cursor-pointer">
                        </th>
                        <th class="w-[150px]" data-column="user__name">User</th>
                        <th class="w-[150px]" data-column="contract__contract_number">Contract</th>
                        <th class="w-[120px]" data-column="commission_type">Type</th>
                        <th class="w-[120px]" data-column="amount" data-type="numeric">Amount</th>
                        <th class="w-[100px]" data-column="status">Status</th>
                        <th class="w-[120px]" data-column="calculated_date" data-type="date">Calculated Date</th>
                        <th class="w-[150px]" data-column="created_at" data-type="date">Created At</th>
                        <th class="w-[150px]" data-column="updated_at" data-type="date">Last Updated At</th>
                         
                    </tr>
                </thead>
                
                <!-- Table Body -->
                <tbody>
                    @forelse($commissions as $commission)
                    <tr onclick="openViewModal({{ $commission->id }})" data-id="{{ $commission->id }}">
                        <td class="text-center">
                            <input type="checkbox" class="row-checkbox w-4 h-4 bg-white border border-[#888888] rounded cursor-pointer" onclick="event.stopPropagation()" value="{{ $commission->id }}">
                        </td>
                        <td>{{ $commission->user->name ?? 'N/A' }}</td>
                        <td>{{ $commission->contract->contract_number ?? 'N/A' }}</td>
                        <td>
                            <span class="badge badge-{{ $commission->commission_type === 'percentage' ? 'primary' : 'secondary' }}">
                                {{ ucfirst($commission->commission_type) }}
                            </span>
                        </td>
                        <td>{{ $commission->formatted_amount }}</td>
                        <td>
                            <span class="badge badge-{{ $commission->status }}">
                                {{ ucfirst($commission->status) }}
                            </span>
                        </td>
                        <td>{{ $commission->calculated_date->format('d/M/Y') }}</td>
                        <td>{!! $commission->created_at ? $commission->created_at->format('d/M/Y') . '<br>at ' . $commission->created_at->format('H.i') . ' WIB' : '-' !!}</td>
                        <td>{!! $commission->updated_at ? $commission->updated_at->format('d/M/Y') . '<br>at ' . $commission->updated_at->format('H.i') . ' WIB' : '-' !!}</td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline" onclick="event.stopPropagation(); openEditModal({{ $commission->id }})">
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="p-8 text-center">
                            <p class="text-lg font-inter text-center text-[#666]">No sales commissions found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        @if($commissions->hasPages())
        <div class="flex flex-row justify-center items-center w-full p-4 bg-white rounded-b-[10px] border-t">
            {{ $commissions->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">Sales Commission Details</h2>
            <button class="modal-close" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="modalBody" class="modal-body">
            <!-- Modal content will be loaded here -->
        </div>
        <div id="modalFooter" class="modal-footer">
            <!-- Modal footer buttons will be loaded here -->
        </div>
    </div>
</div>

<script>
// Select All functionality
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
});

document.getElementById('headerSelectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
    document.getElementById('selectAll').checked = this.checked;
});

// Individual checkbox functionality
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('row-checkbox')) {
        const checkboxes = document.querySelectorAll('.row-checkbox');
        const selectAllCheckbox = document.getElementById('selectAll');
        const headerSelectAllCheckbox = document.getElementById('headerSelectAll');
        
        const allChecked = Array.from(checkboxes).every(checkbox => checkbox.checked);
        const anyChecked = Array.from(checkboxes).some(checkbox => checkbox.checked);
        
        selectAllCheckbox.checked = allChecked;
        headerSelectAllCheckbox.checked = allChecked;
        selectAllCheckbox.indeterminate = anyChecked && !allChecked;
        headerSelectAllCheckbox.indeterminate = anyChecked && !allChecked;
    }
});

// Delete selected function
function deleteSelected() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    if (checkboxes.length === 0) {
        showWarningDialog('Pilih minimal satu komisi yang ingin dihapus.');
        return;
    }
    
    showConfirmDialog(
        'Hapus komisi yang dipilih?',
        'Data komisi yang dipilih akan dihapus.'
    ).then((result) => {
        if (!result.isConfirmed) {
            return;
        }
        const selectedIds = Array.from(checkboxes).map(checkbox => checkbox.value);
        
        fetch('/finance/sales-commissions/bulk-delete', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ ids: selectedIds })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                showErrorDialog('Gagal', 'Gagal menghapus komisi: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorDialog('Gagal', 'Gagal menghapus komisi.');
        });
    });
}

// Modal functions
function openModal() {
    document.getElementById('modalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('modalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
}

function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Add New Sales Commission';
    document.getElementById('modalBody').innerHTML = `
        <p class="text-gray-600 mb-6 text-center">Create a new sales commission record.</p>
        <form id="createForm">
            <div class="form-group">
                <label class="form-label">User *</label>
                <select name="user_id" class="form-select" required>
                    <option value="">Select User</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Contract *</label>
                <select name="contract_id" class="form-select" required>
                    <option value="">Select Contract</option>
                    <!-- Contracts will be loaded via AJAX -->
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Commission Type *</label>
                <select name="commission_type" class="form-select" required>
                    <option value="">Select Type</option>
                    <option value="percentage">Percentage</option>
                    <option value="fixed">Fixed</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Amount *</label>
                <input type="number" name="amount" class="form-input" placeholder="Enter amount" step="0.01" required>
            </div>
            <div class="form-group">
                <label class="form-label">Calculated Date *</label>
                <input type="date" name="calculated_date" class="form-input" required>
            </div>
        </form>
    `;
    document.getElementById('modalFooter').innerHTML = `
        <div class="flex justify-center gap-6">
            <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="submitCreateForm()">Create Commission</button>
        </div>
    `;
    openModal();
}

function openViewModal(id) {
    // Load data via AJAX
    fetch(`/finance/sales-commissions/${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalTitle').textContent = 'Sales Commission Details';
            document.getElementById('modalBody').innerHTML = `
                <div class="space-y-4">
                    <div class="detail-item">
                        <label class="form-label">User</label>
                        <p class="detail-value">${data.user?.name || 'N/A'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Contract</label>
                        <p class="detail-value">${data.contract?.contract_number || 'N/A'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Commission Type</label>
                        <p class="detail-value">${data.commission_type ? data.commission_type.charAt(0).toUpperCase() + data.commission_type.slice(1) : 'N/A'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Amount</label>
                        <p class="detail-value">${data.formatted_amount || 'N/A'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Status</label>
                        <p class="detail-value">${data.status ? data.status.charAt(0).toUpperCase() + data.status.slice(1) : 'N/A'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Calculated Date</label>
                        <p class="detail-value">${data.calculated_date ? new Date(data.calculated_date).toLocaleDateString('id-ID') : 'N/A'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Created At</label>
                        <p class="detail-value">${data.created_at ? new Date(data.created_at).toLocaleString('id-ID') : 'N/A'}</p>
                    </div>
                </div>
            `;
            document.getElementById('modalFooter').innerHTML = `
                <div class="flex justify-center gap-6">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Close</button>
                    <button type="button" class="btn btn-primary" onclick="openEditModal(${id})">Edit</button>
                </div>
            `;
            openModal();
        })
        .catch(error => {
            console.error('Error loading commission data:', error);
            showErrorDialog('Gagal', 'Gagal memuat data komisi.');
        });
}

function openEditModal(id) {
    // Load data via AJAX
    fetch(`/finance/sales-commissions/${id}/edit`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalTitle').textContent = 'Edit Sales Commission';
            document.getElementById('modalBody').innerHTML = `
                <p class="text-gray-600 mb-6 text-center">Update the sales commission details.</p>
                <form id="editForm">
                    <input type="hidden" name="id" value="${data.id}">
                    <div class="form-group">
                        <label class="form-label">User *</label>
                        <select name="user_id" class="form-select" required>
                            <option value="">Select User</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contract *</label>
                        <select name="contract_id" class="form-select" required>
                            <option value="">Select Contract</option>
                            <!-- Contracts will be loaded via AJAX -->
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Commission Type *</label>
                        <select name="commission_type" class="form-select" required>
                            <option value="">Select Type</option>
                            <option value="percentage">Percentage</option>
                            <option value="fixed">Fixed</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Amount *</label>
                        <input type="number" name="amount" class="form-input" placeholder="Enter amount" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status *</label>
                        <select name="status" class="form-select" required>
                            <option value="">Select Status</option>
                            <option value="pending">Pending</option>
                            <option value="valid">Valid</option>
                            <option value="void">Void</option>
                            <option value="paid">Paid</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Calculated Date *</label>
                        <input type="date" name="calculated_date" class="form-input" required>
                    </div>
                </form>
            `;
            document.getElementById('modalFooter').innerHTML = `
                <div class="flex justify-center gap-6">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitEditForm()">Update Commission</button>
                </div>
            `;
            
            // Populate form with existing data
            const form = document.getElementById('editForm');
            form.user_id.value = data.user_id || '';
            form.contract_id.value = data.contract_id || '';
            form.commission_type.value = data.commission_type || '';
            form.amount.value = data.amount || '';
            form.status.value = data.status || '';
            form.calculated_date.value = data.calculated_date || '';
        })
        .catch(error => {
            console.error('Error loading commission data:', error);
            showErrorDialog('Gagal', 'Gagal memuat data komisi.');
        });
}

function submitCreateForm() {
    const form = document.getElementById('createForm');
    const formData = new FormData(form);
    
    fetch('/finance/sales-commissions', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(Object.fromEntries(formData))
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeModal();
            location.reload();
        } else {
            showErrorDialog('Gagal', 'Gagal membuat komisi: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorDialog('Gagal', 'Gagal membuat komisi.');
    });
}

function submitEditForm() {
    const form = document.getElementById('editForm');
    const formData = new FormData(form);
    const id = formData.get('id');
    
    fetch(`/finance/sales-commissions/${id}`, {
        method: 'PUT',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(Object.fromEntries(formData))
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeModal();
            location.reload();
        } else {
            showErrorDialog('Gagal', 'Gagal memperbarui komisi: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorDialog('Gagal', 'Gagal memperbarui komisi.');
    });
}

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});
</script>
@endsection
