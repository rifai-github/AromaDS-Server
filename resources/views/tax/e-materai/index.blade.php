@extends('layouts.app')

@section('title', 'e-Materai Transactions')
@section('breadcrumb', 'Home / Finance / e-Materai Transactions')

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

    /* Responsive Table */
    .responsive-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 1400px;
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
        background-color: #214589;
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

    /* Pagination Styles */
    .pagination-controls {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        justify-content: center;
    }

    .page-number {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s ease;
        border: 1px solid #d1d5db;
        background-color: #f9fafb;
        color: #374151;
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

    .pagination-btn {
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s ease;
        border: 1px solid #d1d5db;
        background-color: #f9fafb;
        color: #374151;
    }

    .pagination-btn:hover:not(:disabled) {
        background-color: #f3f4f6;
        border-color: #9ca3af;
    }

    .pagination-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
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
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
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
        border-radius: 4px;
        transition: background-color 0.2s ease;
    }
    
    .modal-close:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }
    
    .modal-body {
        padding: 20px;
        max-height: 60vh;
        overflow-y: auto;
    }
    
    .modal-footer {
        padding: 20px;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        background: #f9fafb;
    }

    /* Form Input Styling */
    input[type="date"], input[type="text"], input[type="number"], select, textarea {
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        width: 100%;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    input[type="date"]:focus, input[type="text"]:focus, input[type="number"]:focus, select:focus, textarea:focus {
        outline: none;
        border-color: #214589;
        box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
    }

    /* Form Sections */
    .form-section {
        margin-bottom: 20px;
    }

    .form-section:last-child {
        margin-bottom: 0;
    }

    .section-title {
        font-size: 16px;
        font-weight: 600;
        color: #214589;
        margin-bottom: 15px;
        padding-bottom: 8px;
        border-bottom: 2px solid #e5e7eb;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group:last-child {
        margin-bottom: 0;
    }

    .form-label {
        display: block;
        font-weight: 500;
        color: #374151;
        margin-bottom: 5px;
    }

    .required {
        color: #ef4444;
    }

    /* Status Badge Styles */
    .status-badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
        text-transform: uppercase;
    }
    
    .status-pending {
        background-color: #fef3c7;
        color: #92400e;
    }
    
    .status-applied {
        background-color: #d1fae5;
        color: #065f46;
    }
    
    .status-failed {
        background-color: #fee2e2;
        color: #991b1b;
    }
    
    .status-cancelled {
        background-color: #f3f4f6;
        color: #374151;
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .responsive-table th,
        .responsive-table td {
            padding: 8px 6px;
            font-size: 12px;
        }
        
        .responsive-table {
            min-width: 1400px;
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
            max-width: 100%;
            overflow-x: hidden;
        }
        
        .page-dropdown-container {
            max-width: 100%;
            overflow-x: hidden;
        }
        
        /* Header responsive */
        .flex.flex-row.justify-between {
            flex-direction: column;
            gap: 1rem;
            align-items: stretch;
        }
        
        .flex.flex-row.justify-between > div:first-child {
            width: 100%;
        }
        
        .flex.flex-row.justify-between > div:last-child {
            width: 100%;
            justify-content: flex-start;
        }
    }
</style>

<div class="flex flex-col w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">
        
        <!-- e-Materai Transactions Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">e-Materai Transactions</h1>
            </div>
            
            <div class="flex flex-row justify-end items-center">
                <button class="btn btn-primary" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i>
                    <span class="hidden md:inline">Add New e-Materai Transaction</span>
                    <span class="md:hidden">Add New</span>
                </button>
            </div>
        </div>
        
        <!-- Controls Row -->
        <div class="flex flex-row justify-between items-center w-full p-4 bg-white">
            <div class="flex flex-row justify-start items-center w-full">
                <div class="flex flex-row justify-start items-center w-auto">
                    <div class="flex flex-row items-center w-auto">
                        <input type="checkbox" id="selectAll" class="w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer">
                        <div class="flex flex-row justify-start items-center w-full px-2">
                            <p class="text-sm font-normal text-gray-700 w-auto ml-2 cursor-pointer" onclick="document.getElementById('selectAll').click()">Select all</p>
                        </div>
                    </div>
                </div>
                
                <!-- Delete Button -->
                <button class="btn btn-secondary btn-sm ml-4" onclick="deleteSelected()">
                    <i class="fas fa-trash"></i>
                    <span>Delete</span>
                </button>
            </div>
            
        </div>
        
        <!-- Table Container with Horizontal Scroll -->
        <div class="table-container">
            <table class="responsive-table">
                <!-- Table Header -->
                <thead>
                    <tr>
                        <th>
                            <input type="checkbox" id="headerSelectAll" class="w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer">
                        </th>
                        <th>Transaction ID</th>
                        <th>Tax Invoice</th>
                        <th>Peruri Reference</th>
                        <th>Status</th>
                        <th>Applied At</th>
                        <th>Document Path</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                
                <!-- Table Body -->
                <tbody>
                    @forelse($eMateraiTransactions ?? [] as $transaction)
                    <tr data-id="{{ $transaction->id }}" onclick="openViewModal({{ $transaction->id }})">
                        <td class="text-center">
                            <input type="checkbox" class="row-checkbox w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer" value="{{ $transaction->id }}" onclick="event.stopPropagation()">
                        </td>
                        <td>{{ $transaction->transaction_id ?? '-' }}</td>
                        <td>{{ $transaction->taxInvoice->invoice_number ?? '-' }}</td>
                        <td>{{ $transaction->peruri_reference_number ?? '-' }}</td>
                        <td>
                            <span class="status-badge status-{{ $transaction->status }}">
                                {{ ucfirst($transaction->status) }}
                            </span>
                        </td>
                        <td>{{ $transaction->applied_at ? \Carbon\Carbon::parse($transaction->applied_at)->format('d/M/Y H:i') : '-' }}</td>
                        <td>{{ $transaction->document_path ? basename($transaction->document_path) : '-' }}</td>
                        <td>
                            @if($transaction->created_at)
                                {{ \Carbon\Carbon::parse($transaction->created_at)->format('d/M/Y') }}<br>
                                at {{ \Carbon\Carbon::parse($transaction->created_at)->format('H.i') }} WIB
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="p-8 text-center">
                            <p class="text-lg text-gray-600">No e-Materai transactions found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        @if($eMateraiTransactions->total() > 0)
        <div class="flex flex-row justify-center items-center w-full p-4 bg-white rounded-b-[10px] border-t">
            {{ $eMateraiTransactions->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">View e-Materai Transaction</h2>
            <button class="modal-close" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="modalBody" class="modal-body">
            <!-- Modal content will be loaded here -->
        </div>
        <div id="modalFooter" class="modal-footer">
            <!-- Modal footer content will be loaded here -->
        </div>
    </div>
</div>

<script>
// Global variables
let selectedIdsForRetry = [];

// Select All functionality
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
    document.getElementById('headerSelectAll').checked = this.checked;
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
        showWarningDialog('Pilih minimal satu transaksi e-Materai yang ingin dihapus.');
        return;
    }
    
    selectedIdsForRetry = Array.from(checkboxes).map(cb => cb.value);
    showConfirmDialog(
        'Hapus Transaksi e-Materai Terpilih?',
        'Apakah Anda yakin ingin menghapus transaksi e-Materai yang dipilih?',
        'Ya, hapus',
        'Batal'
    ).then((confirmed) => {
        if (!confirmed) return;
        console.log('Deleting:', selectedIdsForRetry);
    });
}

// Modal functions
function openModal(title) {
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('modalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('modalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
    document.getElementById('modalBody').innerHTML = '';
    document.getElementById('modalFooter').innerHTML = '';
}

// CRUD Modal functions
function openCreateModal() {
    openModal('Create New e-Materai Transaction');
    
    // Get dynamic data for dropdowns
    const taxInvoices = @json($taxInvoices ?? []);
    const invoices = @json($invoices ?? []);
    
    let taxInvoicesOptions = '<option value="">Select Tax Invoice</option>';
    taxInvoices.forEach(invoice => {
        taxInvoicesOptions += `<option value="${invoice.id}">${invoice.invoice_number} - ${invoice.customer.name} (${invoice.total_amount})</option>`;
    });
    
    let invoicesOptions = '<option value="">Select Regular Invoice (Optional)</option>';
    invoices.forEach(invoice => {
        invoicesOptions += `<option value="${invoice.id}">${invoice.invoice_number} - ${invoice.customer.name}</option>`;
    });
    
    document.getElementById('modalBody').innerHTML = `
        <form id="createForm">
            <div class="form-section">
                <div class="section-title">Transaction Information</div>
                <div class="form-group">
                    <label class="form-label">Transaction ID <span class="required">*</span></label>
                    <input type="text" name="transaction_id" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status">
                        <option value="pending">Pending</option>
                        <option value="applied">Applied</option>
                        <option value="failed">Failed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Peruri Reference Number</label>
                    <input type="text" name="peruri_reference_number" placeholder="Reference from Peruri API">
                </div>
                <div class="form-group">
                    <label class="form-label">Applied At</label>
                    <input type="datetime-local" name="applied_at">
                </div>
            </div>
            
            <div class="form-section">
                <div class="section-title">Invoice Information</div>
                <div class="form-group">
                    <label class="form-label">Tax Invoice <span class="required">*</span></label>
                    <select name="tax_invoice_id" required>
                        ${taxInvoicesOptions}
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Regular Invoice</label>
                    <select name="invoice_id">
                        ${invoicesOptions}
                    </select>
                </div>
            </div>
            
            <div class="form-section">
                <div class="section-title">Document Information</div>
                <div class="form-group">
                    <label class="form-label">Document Path</label>
                    <input type="text" name="document_path" placeholder="Path to the document file">
                </div>
                <div class="form-group">
                    <label class="form-label">Upload Document</label>
                    <input type="file" name="file_upload" accept=".pdf">
                </div>
            </div>
            
            <div class="form-section">
                <div class="section-title">API Response Data</div>
                <div class="form-group">
                    <label class="form-label">Response Data (JSON)</label>
                    <textarea name="response_data" rows="4" placeholder='{"status": "success", "reference": "123456", ...}'></textarea>
                </div>
            </div>
            
            <div class="form-section">
                <div class="section-title">Additional Information</div>
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" rows="3" placeholder="Additional notes about this e-Materai transaction..."></textarea>
                </div>
            </div>
        </form>
    `;
    
    document.getElementById('modalFooter').innerHTML = `
        <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
        <button class="btn btn-primary" onclick="submitCreateForm()">Create e-Materai Transaction</button>
    `;
    
    // Auto-generate transaction ID
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const timestamp = now.getTime().toString().slice(-6);
    document.querySelector('input[name="transaction_id"]').value = `EM-${year}${month}${day}-${timestamp}`;
    
    // Set default applied_at to current date/time
    const appliedAtInput = document.querySelector('input[name="applied_at"]');
    const nowDateTime = new Date();
    const yearDateTime = nowDateTime.getFullYear();
    const monthDateTime = String(nowDateTime.getMonth() + 1).padStart(2, '0');
    const dayDateTime = String(nowDateTime.getDate()).padStart(2, '0');
    const hours = String(nowDateTime.getHours()).padStart(2, '0');
    const minutes = String(nowDateTime.getMinutes()).padStart(2, '0');
    appliedAtInput.value = `${yearDateTime}-${monthDateTime}-${dayDateTime}T${hours}:${minutes}`;
}

function openViewModal(id) {
    openModal('View e-Materai Transaction');
    
    // Get e-Materai transaction data (this would typically come from an API call)
    const eMateraiTransaction = {
        id: id,
        transaction_id: 'EM-20240929-123456',
        tax_invoice: { invoice_number: 'TI-20240929-123456' },
        peruri_reference_number: 'PER-123456789',
        status: 'applied',
        applied_at: '2024-09-29T10:30:00',
        document_path: 'storage/documents/e-materai/invoice-123456.pdf',
        response_data: '{"status": "success", "reference": "123456"}',
        notes: 'e-Materai applied successfully'
    };
    
    document.getElementById('modalBody').innerHTML = `
        <div class="form-section">
            <div class="section-title">Transaction Information</div>
            <div class="form-group">
                <label class="form-label">Transaction ID</label>
                <div style="padding: 8px 12px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; color: #374151;">${eMateraiTransaction.transaction_id}</div>
            </div>
            <div class="form-group">
                <label class="form-label">Tax Invoice</label>
                <div style="padding: 8px 12px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; color: #374151;">${eMateraiTransaction.tax_invoice.invoice_number}</div>
            </div>
            <div class="form-group">
                <label class="form-label">Peruri Reference</label>
                <div style="padding: 8px 12px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; color: #374151;">${eMateraiTransaction.peruri_reference_number}</div>
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <div style="padding: 8px 12px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; color: #374151;">${eMateraiTransaction.status}</div>
            </div>
        </div>
        
        <div class="form-section">
            <div class="section-title">Document Information</div>
            <div class="form-group">
                <label class="form-label">Document Path</label>
                <div style="padding: 8px 12px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; color: #374151;">${eMateraiTransaction.document_path}</div>
            </div>
            <div class="form-group">
                <label class="form-label">Applied At</label>
                <div style="padding: 8px 12px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; color: #374151;">${eMateraiTransaction.applied_at}</div>
            </div>
        </div>
    `;
    
    document.getElementById('modalFooter').innerHTML = `
        <button class="btn btn-secondary" onclick="closeModal()">Close</button>
        <button class="btn btn-primary" onclick="openEditModal(${id})">Edit</button>
    `;
}

function openEditModal(id) {
    openModal('Edit e-Materai Transaction');
    
    // Similar to create modal but with pre-filled data
    document.getElementById('modalBody').innerHTML = `
        <form id="editForm">
            <div class="form-section">
                <div class="section-title">Transaction Information</div>
                <div class="form-group">
                    <label class="form-label">Transaction ID <span class="required">*</span></label>
                    <input type="text" name="transaction_id" value="EM-20240929-123456" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status">
                        <option value="pending">Pending</option>
                        <option value="applied" selected>Applied</option>
                        <option value="failed">Failed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>
        </form>
    `;
    
    document.getElementById('modalFooter').innerHTML = `
        <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
        <button class="btn btn-primary" onclick="submitEditForm()">Update e-Materai Transaction</button>
    `;
}

function submitCreateForm() {
    const form = document.getElementById('createForm');
    const formData = new FormData(form);
    
    // Here you would typically send the data to the server
    console.log('Creating e-Materai transaction:', Object.fromEntries(formData));
    
    // Close modal and refresh page
    closeModal();
    location.reload();
}

function submitEditForm() {
    const form = document.getElementById('editForm');
    const formData = new FormData(form);
    
    // Here you would typically send the data to the server
    console.log('Updating e-Materai transaction:', Object.fromEntries(formData));
    
    // Close modal and refresh page
    closeModal();
    location.reload();
}
</script>
@endsection
