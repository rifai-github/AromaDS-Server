@extends('layouts.app')

@section('title', 'Bank Receipts')
@section('breadcrumb', 'Home / Finance / Bank Receipts')

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
        line-height: 1.4;
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
    
    .badge-verified {
        background-color: #d1fae5;
        color: #065f46;
    }
    
    .badge-rejected {
        background-color: #fee2e2;
        color: #991b1b;
    }
    
    .badge-processed {
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
        width: 700px;
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
        
        <!-- Bank Receipts Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Bank Receipts</h1>
            </div>
            
            <button class="btn btn-primary" onclick="openCreateModal()">
                <i class="fas fa-plus"></i>
                <span>Create Receipt</span>
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
                        <th class="w-[150px]" data-column="receipt_number">Receipt Number</th>
                        <th class="w-[150px]" data-column="customer__name">Customer</th>
                        <th class="w-[120px]" data-column="bank__name">Bank</th>
                        <th class="w-[120px]" data-column="amount" data-type="numeric">Amount</th>
                        <th class="w-[120px]" data-column="payment_date" data-type="date">Payment Date</th>
                        <th class="w-[100px]" data-column="status">Status</th>
                        <th class="w-[150px]" data-column="created_by">Created By</th>
                        <th class="w-[150px]" data-column="created_at" data-type="date">Created At</th>
                        <th class="w-[150px]" data-column="updated_by">Last Updated By</th>
                        <th class="w-[150px]" data-column="updated_at" data-type="date">Last Updated At</th>
                         
                    </tr>
                </thead>
                
                <!-- Table Body -->
                <tbody>
                    @forelse($bankReceipts as $receipt)
                    <tr onclick="openViewModal({{ $receipt->id }})" data-id="{{ $receipt->id }}">
                        <td class="text-center">
                            <input type="checkbox" class="row-checkbox w-4 h-4 bg-white border border-[#888888] rounded cursor-pointer" onclick="event.stopPropagation()" value="{{ $receipt->id }}">
                        </td>
                        <td>{{ $receipt->receipt_number }}</td>
                        <td>{{ $receipt->customer->name ?? 'N/A' }}</td>
                        <td>{{ $receipt->bank->bank_name ?? 'N/A' }}</td>
                        <td class="font-semibold">IDR {{ number_format($receipt->amount) }}</td>
                        <td>{{ $receipt->payment_date ? \Carbon\Carbon::parse($receipt->payment_date)->format('d/M/Y') : 'N/A' }}</td>
                        <td>
                            <span class="badge badge-{{ $receipt->status }}">
                                {{ ucfirst($receipt->status) }}
                            </span>
                        </td>
                        <td class="text-sm text-gray-500">{{ $receipt->creator->name ?? '-' }}</td>
                        <td class="text-sm text-gray-500">{!! $receipt->created_at ? $receipt->created_at->format('d/M/Y<br>at H.i') . ' WIB' : '-' !!}</td>
                        <td class="text-sm text-gray-500">{{ $receipt->updater->name ?? '-' }}</td>
                        <td class="text-sm text-gray-500">{!! $receipt->updated_at ? $receipt->updated_at->format('d/M/Y<br>at H.i') . ' WIB' : '-' !!}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="p-8 text-center">
                            <p class="text-lg font-inter text-center text-[#666]">No bank receipts found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        @if($bankReceipts->total() > 0)
        <div class="flex flex-row justify-center items-center w-full p-4 bg-white rounded-b-[10px] border-t">
            {{ $bankReceipts->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">Bank Receipt Details</h2>
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
        showWarningDialog('Pilih minimal satu bank receipt yang ingin dihapus.');
        return;
    }
    
    showConfirmDialog(
        'Hapus bank receipt yang dipilih?',
        'Data bank receipt yang dipilih akan dihapus.'
    ).then((confirmed) => {
        if (!confirmed) {
            return;
        }
        const selectedIds = Array.from(checkboxes).map(checkbox => checkbox.value);
        
        fetch('/finance/bank-receipts/bulk-delete', {
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
                showErrorDialog('Gagal', 'Gagal menghapus bank receipt: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorDialog('Gagal', 'Gagal menghapus bank receipt.');
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
    document.getElementById('modalTitle').textContent = 'Create New Bank Receipt';
    document.getElementById('modalBody').innerHTML = `
        <p class="text-gray-600 mb-6 text-center">Create a new bank receipt record.</p>
        <form id="createForm">
            <div class="form-group">
                <label class="form-label">Receipt Date *</label>
                <input type="date" name="receipt_date" class="form-input" value="{{ date('Y-m-d') }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Customer *</label>
                <select name="customer_id" class="form-select" required>
                    <option value="">Select Customer</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Invoice Reference</label>
                <input type="text" name="invoice_reference" class="form-input" placeholder="Invoice reference (optional)">
            </div>
            <div class="form-group">
                <label class="form-label">Bank *</label>
                <select name="bank_id" class="form-select" required>
                    <option value="">Select Bank</option>
                    @foreach($banks as $bank)
                        <option value="{{ $bank->id }}">{{ $bank->bank_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Account Number *</label>
                <input type="text" name="account_number" class="form-input" placeholder="Bank account number" required>
            </div>
            <div class="form-group">
                <label class="form-label">Account Holder Name *</label>
                <input type="text" name="account_holder_name" class="form-input" placeholder="Account holder name" required>
            </div>
            <div class="form-group">
                <label class="form-label">Amount *</label>
                <input type="number" name="amount" class="form-input" placeholder="Enter amount" step="0.01" min="0" required>
            </div>
            <div class="form-group">
                <label class="form-label">Payment Date *</label>
                <input type="date" name="payment_date" class="form-input" value="{{ date('Y-m-d') }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Payment Method *</label>
                <select name="payment_method" class="form-select" required>
                    <option value="">Select Payment Method</option>
                    <option value="transfer">Transfer</option>
                    <option value="cash">Cash</option>
                    <option value="check">Check</option>
                    <option value="giro">Giro</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="credit_card">Credit Card</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Status *</label>
                <select name="status" class="form-select" required>
                    <option value="">Select Status</option>
                    <option value="pending">Pending</option>
                    <option value="verified">Verified</option>
                    <option value="rejected">Rejected</option>
                    <option value="processed">Processed</option>
                    <option value="failed">Failed</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Receipt Image</label>
                <input type="file" name="receipt_image" class="form-input" accept="image/*,.pdf">
                <small class="text-gray-500">Upload receipt image or PDF (max 5MB)</small>
            </div>
            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-input" rows="3" placeholder="Additional notes (optional)"></textarea>
            </div>
        </form>
    `;
    document.getElementById('modalFooter').innerHTML = `
        <div class="flex justify-center gap-6">
            <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="submitCreateForm()">Create Receipt</button>
        </div>
    `;
    openModal();
}

function openViewModal(id) {
    // Load data via AJAX
    fetch(`/finance/bank-receipts/${id}`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Invalid JSON response:', text);
                throw new Error('Server returned invalid JSON response');
            }
        });
    })
    .then(response => {
        if (response.success) {
            const data = response.data;
            document.getElementById('modalTitle').textContent = 'Bank Receipt Details';
            document.getElementById('modalBody').innerHTML = `
                <div class="space-y-4">
                    <div class="detail-item">
                        <label class="form-label">Receipt Number</label>
                        <p class="detail-value">${data.receipt_number || 'N/A'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Customer</label>
                        <p class="detail-value">${data.customer?.name || 'N/A'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Bank</label>
                        <p class="detail-value">${data.bank?.bank_name || 'N/A'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Account Number</label>
                        <p class="detail-value">${data.account_number || 'N/A'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Account Holder</label>
                        <p class="detail-value">${data.account_holder_name || 'N/A'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Amount</label>
                        <p class="detail-value font-semibold">IDR ${data.amount ? new Intl.NumberFormat('id-ID').format(data.amount) : 'N/A'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Payment Date</label>
                        <p class="detail-value">${data.payment_date ? new Date(data.payment_date).toLocaleDateString('id-ID') : 'N/A'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Payment Method</label>
                        <p class="detail-value">${data.payment_method ? data.payment_method.charAt(0).toUpperCase() + data.payment_method.slice(1) : 'N/A'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Status</label>
                        <p class="detail-value">${data.status ? data.status.charAt(0).toUpperCase() + data.status.slice(1) : 'N/A'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Receipt Date</label>
                        <p class="detail-value">${data.receipt_date ? new Date(data.receipt_date).toLocaleDateString('id-ID') : 'N/A'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Invoice Reference</label>
                        <p class="detail-value">${data.invoice_reference || 'N/A'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Notes</label>
                        <p class="detail-value">${data.notes || 'N/A'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Created By</label>
                        <p class="detail-value">${data.creator?.name || 'N/A'}</p>
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
        } else {
            console.error('Invalid response format:', response);
            showErrorDialog('Gagal', 'Format response tidak valid.');
        }
    })
    .catch(error => {
        console.error('Error loading bank receipt data:', error);
        showErrorDialog('Gagal', 'Gagal memuat data bank receipt: ' + error.message);
    });
}

function openEditModal(id) {
    // Load data via AJAX
    fetch(`/finance/bank-receipts/${id}/edit`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Invalid JSON response:', text);
                throw new Error('Server returned invalid JSON response');
            }
        });
    })
    .then(response => {
        if (response.success) {
            const data = response.data;
            const customers = response.customers;
            const banks = response.banks;
            
            // Helper function to convert ISO datetime to yyyy-MM-dd format
            const formatDateForInput = (dateString) => {
                if (!dateString) return '';
                const date = new Date(dateString);
                return date.toISOString().split('T')[0];
            };
            
            document.getElementById('modalTitle').textContent = 'Edit Bank Receipt';
            document.getElementById('modalBody').innerHTML = `
                <p class="text-gray-600 mb-6 text-center">Update the bank receipt details.</p>
                <form id="editForm">
                    <input type="hidden" name="id" value="${data.id}">
                    <div class="form-group">
                        <label class="form-label">Customer *</label>
                        <select name="customer_id" class="form-select" required>
                            <option value="">Select Customer</option>
                            ${customers.map(customer => 
                                `<option value="${customer.id}" ${data.customer_id == customer.id ? 'selected' : ''}>${customer.name}</option>`
                            ).join('')}
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Bank *</label>
                        <select name="bank_id" class="form-select" required>
                            <option value="">Select Bank</option>
                            ${banks.map(bank => 
                                `<option value="${bank.id}" ${data.bank_id == bank.id ? 'selected' : ''}>${bank.bank_name}</option>`
                            ).join('')}
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Receipt Number *</label>
                        <input type="text" name="receipt_number" class="form-input" value="${data.receipt_number || ''}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Receipt Date *</label>
                        <input type="date" name="receipt_date" class="form-input" value="${formatDateForInput(data.receipt_date)}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Amount *</label>
                        <input type="number" name="amount" class="form-input" value="${data.amount || ''}" placeholder="Enter amount" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Payment Date *</label>
                        <input type="date" name="payment_date" class="form-input" value="${formatDateForInput(data.payment_date)}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Invoice Reference</label>
                        <input type="text" name="invoice_reference" class="form-input" value="${data.invoice_reference || ''}" placeholder="Invoice reference">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Account Number *</label>
                        <input type="text" name="account_number" class="form-input" value="${data.account_number || ''}" placeholder="Bank account number" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Account Holder Name *</label>
                        <input type="text" name="account_holder_name" class="form-input" value="${data.account_holder_name || ''}" placeholder="Account holder name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Payment Method *</label>
                        <select name="payment_method" class="form-select" required>
                            <option value="">Select Payment Method</option>
                            <option value="transfer" ${data.payment_method == 'transfer' ? 'selected' : ''}>Transfer</option>
                            <option value="cash" ${data.payment_method == 'cash' ? 'selected' : ''}>Cash</option>
                            <option value="check" ${data.payment_method == 'check' ? 'selected' : ''}>Check</option>
                            <option value="giro" ${data.payment_method == 'giro' ? 'selected' : ''}>Giro</option>
                            <option value="bank_transfer" ${data.payment_method == 'bank_transfer' ? 'selected' : ''}>Bank Transfer</option>
                            <option value="credit_card" ${data.payment_method == 'credit_card' ? 'selected' : ''}>Credit Card</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Receipt Image</label>
                        <input type="file" name="receipt_image" class="form-input" accept="image/*,.pdf">
                        <small class="text-gray-500">Upload receipt image or PDF (max 5MB)</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-input" rows="3" placeholder="Additional notes (optional)">${data.notes || ''}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status *</label>
                        <select name="status" class="form-select" required>
                            <option value="">Select Status</option>
                            <option value="pending" ${data.status == 'pending' ? 'selected' : ''}>Pending</option>
                            <option value="verified" ${data.status == 'verified' ? 'selected' : ''}>Verified</option>
                            <option value="rejected" ${data.status == 'rejected' ? 'selected' : ''}>Rejected</option>
                            <option value="processed" ${data.status == 'processed' ? 'selected' : ''}>Processed</option>
                            <option value="failed" ${data.status == 'failed' ? 'selected' : ''}>Failed</option>
                        </select>
                    </div>
                </form>
            `;
            document.getElementById('modalFooter').innerHTML = `
                <div class="flex justify-center gap-6">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitEditForm()">Update Receipt</button>
                </div>
            `;
            openModal();
        } else {
            console.error('Invalid response format:', response);
            showErrorDialog('Gagal', 'Format response tidak valid.');
        }
    })
    .catch(error => {
        console.error('Error loading bank receipt data:', error);
        showErrorDialog('Gagal', 'Gagal memuat data bank receipt: ' + error.message);
    });
}

function submitCreateForm() {
    const form = document.getElementById('createForm');
    const formData = new FormData(form);
    
    // Add CSRF token
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
    
    fetch('/finance/bank-receipts', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Invalid JSON response:', text);
                throw new Error('Server returned invalid JSON response');
            }
        });
    })
    .then(data => {
        if (data.success) {
            closeModal();
            location.reload();
        } else {
            // Handle validation errors
            if (data.errors) {
                let errorMessage = 'Validation errors:\n';
                for (const [field, messages] of Object.entries(data.errors)) {
                    errorMessage += `${field}: ${messages.join(', ')}\n`;
                }
                showErrorDialog('Validasi Gagal', errorMessage);
            } else {
                showErrorDialog('Gagal', 'Gagal membuat bank receipt: ' + (data.message || 'Terjadi kesalahan.'));
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorDialog('Gagal', 'Gagal membuat bank receipt: ' + error.message);
    });
}

function submitEditForm() {
    const form = document.getElementById('editForm');
    const formData = new FormData(form);
    const id = formData.get('id');
    
    // Add CSRF token and method override
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
    formData.append('_method', 'PUT');
    
    fetch(`/finance/bank-receipts/${id}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Invalid JSON response:', text);
                throw new Error('Server returned invalid JSON response');
            }
        });
    })
    .then(data => {
        if (data.success) {
            closeModal();
            location.reload();
        } else {
            // Handle validation errors
            if (data.errors) {
                let errorMessage = 'Validation errors:\n';
                for (const [field, messages] of Object.entries(data.errors)) {
                    errorMessage += `${field}: ${messages.join(', ')}\n`;
                }
                showErrorDialog('Validasi Gagal', errorMessage);
            } else {
                showErrorDialog('Gagal', 'Gagal memperbarui bank receipt: ' + (data.message || 'Terjadi kesalahan.'));
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorDialog('Gagal', 'Gagal memperbarui bank receipt: ' + error.message);
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
