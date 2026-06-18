@extends('layouts.app')

@section('title', 'Tax Invoices')
@section('breadcrumb', 'Home / Finance / Tax Invoices')

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
    
    .status-draft {
        background-color: #f3f4f6;
        color: #374151;
    }
    
    .status-pending {
        background-color: #fef3c7;
        color: #92400e;
    }
    
    .status-approved {
        background-color: #d1fae5;
        color: #065f46;
    }
    
    .status-paid {
        background-color: #dbeafe;
        color: #1e40af;
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .responsive-table th,
        .responsive-table td {
            padding: 8px 6px;
            font-size: 12px;
        }
        
        .responsive-table {
            min-width: 1600px;
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
        
        <!-- Tax Invoices Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Tax Invoices</h1>
            </div>
            
            <div class="flex flex-row justify-end items-center">
                <button class="btn btn-primary" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i>
                    <span class="hidden md:inline">Add New Tax Invoice</span>
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
                        <th>Invoice Number</th>
                        <th>Customer</th>
                        <th>Invoice Date</th>
                        <th>Due Date</th>
                        <th>Subtotal</th>
                        <th>Tax Amount</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Tax Status</th>
                        <th>e-Materai</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                
                <!-- Table Body -->
                <tbody>
                    @forelse($taxInvoices ?? [] as $invoice)
                    <tr data-id="{{ $invoice->id }}" onclick="openViewModal({{ $invoice->id }})">
                        <td class="text-center">
                            <input type="checkbox" class="row-checkbox w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer" value="{{ $invoice->id }}" onclick="event.stopPropagation()">
                        </td>
                        <td>{{ $invoice->invoice_number ?? '-' }}</td>
                        <td>{{ $invoice->customer->name ?? '-' }}</td>
                        <td>{{ $invoice->invoice_date ? \Carbon\Carbon::parse($invoice->invoice_date)->format('d/M/Y') : '-' }}</td>
                        <td>{{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d/M/Y') : '-' }}</td>
                        <td class="text-right">{{ $invoice->subtotal ? number_format($invoice->subtotal, 0, ',', '.') : '-' }}</td>
                        <td class="text-right">{{ $invoice->tax_amount ? number_format($invoice->tax_amount, 0, ',', '.') : '-' }}</td>
                        <td class="text-right">{{ $invoice->total_amount ? number_format($invoice->total_amount, 0, ',', '.') : '-' }}</td>
                        <td>
                            <span class="status-badge status-{{ $invoice->status }}">
                                {{ ucfirst($invoice->status) }}
                            </span>
                        </td>
                        <td>
                            <span class="status-badge status-{{ $invoice->tax_status }}">
                                {{ ucfirst($invoice->tax_status) }}
                            </span>
                        </td>
                        <td>
                            @if($invoice->is_e_materai_applied)
                                <span class="status-badge status-approved">
                                    <i class="fas fa-check"></i> Applied
                                </span>
                            @else
                                <span class="status-badge status-draft">
                                    <i class="fas fa-times"></i> Not Applied
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($invoice->created_at)
                                {{ \Carbon\Carbon::parse($invoice->created_at)->format('d/M/Y') }}<br>
                                at {{ \Carbon\Carbon::parse($invoice->created_at)->format('H.i') }} WIB
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="12" class="p-8 text-center">
                            <p class="text-lg text-gray-600">No tax invoices found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        @if($taxInvoices->total() > 0)
        <div class="flex flex-row justify-center items-center w-full p-4 bg-white rounded-b-[10px] border-t">
            {{ $taxInvoices->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">View Tax Invoice</h2>
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
        showWarningDialog('Pilih minimal satu faktur pajak yang ingin dihapus.');
        return;
    }
    
    selectedIdsForRetry = Array.from(checkboxes).map(cb => cb.value);
    showConfirmDialog(
        'Hapus Faktur Pajak Terpilih?',
        'Apakah Anda yakin ingin menghapus faktur pajak yang dipilih?',
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
    openModal('Create New Tax Invoice');
    
    // Get dynamic data for dropdowns
    const customers = @json($customers ?? []);
    const contracts = @json($contracts ?? []);
    const billingGroups = @json($billingGroups ?? []);
    
    let customersOptions = '<option value="">Select Customer</option>';
    customers.forEach(customer => {
        customersOptions += `<option value="${customer.id}">${customer.name || 'N/A'} (${customer.code || 'N/A'})</option>`;
    });
    
    let contractsOptions = '<option value="">Select Contract</option>';
    contracts.forEach(contract => {
        contractsOptions += `<option value="${contract.id}">${contract.contract_number} - ${contract.customer?.name || 'N/A'}</option>`;
    });
    
    let billingGroupsOptions = '<option value="">Select Billing Group</option>';
    billingGroups.forEach(billingGroup => {
        billingGroupsOptions += `<option value="${billingGroup.id}">${billingGroup.name}</option>`;
    });
    
    document.getElementById('modalBody').innerHTML = `
        <form id="createForm">
            <div class="form-section">
                <div class="section-title">Basic Information</div>
                <div class="form-group">
                    <label class="form-label">Invoice Number <span class="required">*</span></label>
                    <input type="text" name="invoice_number" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Invoice Date <span class="required">*</span></label>
                    <input type="date" name="invoice_date" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Due Date <span class="required">*</span></label>
                    <input type="date" name="due_date" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status">
                        <option value="draft">Draft</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="paid">Paid</option>
                    </select>
                </div>
            </div>
            
            <div class="form-section">
                <div class="section-title">Customer & Contract Information</div>
                <div class="form-group">
                    <label class="form-label">Customer <span class="required">*</span></label>
                    <select name="customer_id" required>
                        ${customersOptions}
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Contract</label>
                    <select name="contract_id">
                        ${contractsOptions}
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Billing Group</label>
                    <select name="billing_group_id">
                        ${billingGroupsOptions}
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Tax Code</label>
                    <input type="text" name="tax_code" placeholder="e.g., 01.234.567.8-901.000">
                </div>
            </div>
            
            <div class="form-section">
                <div class="section-title">Financial Information</div>
                <div class="form-group">
                    <label class="form-label">Subtotal <span class="required">*</span></label>
                    <input type="number" name="subtotal" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Tax Amount <span class="required">*</span></label>
                    <input type="number" name="tax_amount" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Total Amount <span class="required">*</span></label>
                    <input type="number" name="total_amount" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Tax Status</label>
                    <select name="tax_status">
                        <option value="pending">Pending</option>
                        <option value="applied">Applied</option>
                        <option value="exempt">Exempt</option>
                    </select>
                </div>
            </div>
            
            <div class="form-section">
                <div class="section-title">Additional Information</div>
                <div class="form-group">
                    <label class="form-label">Tax Notes</label>
                    <textarea name="tax_notes" rows="3" placeholder="Additional notes about tax calculation..."></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">General Notes</label>
                    <textarea name="notes" rows="3" placeholder="Additional notes about this invoice..."></textarea>
                </div>
            </div>
        </form>
    `;
    
    document.getElementById('modalFooter').innerHTML = `
        <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
        <button class="btn btn-primary" onclick="submitCreateForm()">Create Tax Invoice</button>
    `;
    
    // Auto-generate invoice number
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const timestamp = now.getTime().toString().slice(-6);
    document.querySelector('input[name="invoice_number"]').value = `TI-${year}${month}${day}-${timestamp}`;
    
    // Set default dates
    document.querySelector('input[name="invoice_date"]').value = now.toISOString().split('T')[0];
    const dueDate = new Date(now);
    dueDate.setDate(dueDate.getDate() + 30);
    document.querySelector('input[name="due_date"]').value = dueDate.toISOString().split('T')[0];
    
    // Auto-calculate total amount
    const subtotalInput = document.querySelector('input[name="subtotal"]');
    const taxAmountInput = document.querySelector('input[name="tax_amount"]');
    const totalAmountInput = document.querySelector('input[name="total_amount"]');
    
    function calculateTotal() {
        const subtotal = parseFloat(subtotalInput.value) || 0;
        const taxAmount = parseFloat(taxAmountInput.value) || 0;
        const total = subtotal + taxAmount;
        totalAmountInput.value = total.toFixed(2);
    }
    
    subtotalInput.addEventListener('input', calculateTotal);
    taxAmountInput.addEventListener('input', calculateTotal);
}

function openViewModal(id) {
    openModal('View Tax Invoice');
    
    // Get tax invoice data (this would typically come from an API call)
    const taxInvoice = {
        id: id,
        invoice_number: 'TI-20240929-123456',
        customer: { name: 'PT Example Company' },
        invoice_date: '2024-09-29',
        due_date: '2024-10-29',
        subtotal: 1000000,
        tax_amount: 110000,
        total_amount: 1110000,
        status: 'approved',
        tax_status: 'applied',
        is_e_materai_applied: true,
        tax_notes: 'Standard VAT calculation',
        notes: 'Monthly service invoice'
    };
    
    document.getElementById('modalBody').innerHTML = `
        <div class="form-section">
            <div class="section-title">Basic Information</div>
            <div class="form-group">
                <label class="form-label">Invoice Number</label>
                <div style="padding: 8px 12px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; color: #374151;">${taxInvoice.invoice_number}</div>
            </div>
            <div class="form-group">
                <label class="form-label">Customer</label>
                <div style="padding: 8px 12px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; color: #374151;">${taxInvoice.customer.name}</div>
            </div>
            <div class="form-group">
                <label class="form-label">Invoice Date</label>
                <div style="padding: 8px 12px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; color: #374151;">${taxInvoice.invoice_date}</div>
            </div>
            <div class="form-group">
                <label class="form-label">Due Date</label>
                <div style="padding: 8px 12px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; color: #374151;">${taxInvoice.due_date}</div>
            </div>
        </div>
        
        <div class="form-section">
            <div class="section-title">Financial Information</div>
            <div class="form-group">
                <label class="form-label">Subtotal</label>
                <div style="padding: 8px 12px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; color: #374151;">Rp ${taxInvoice.subtotal.toLocaleString('id-ID')}</div>
            </div>
            <div class="form-group">
                <label class="form-label">Tax Amount</label>
                <div style="padding: 8px 12px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; color: #374151;">Rp ${taxInvoice.tax_amount.toLocaleString('id-ID')}</div>
            </div>
            <div class="form-group">
                <label class="form-label">Total Amount</label>
                <div style="padding: 8px 12px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; color: #374151;">Rp ${taxInvoice.total_amount.toLocaleString('id-ID')}</div>
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <div style="padding: 8px 12px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; color: #374151;">${taxInvoice.status}</div>
            </div>
        </div>
    `;
    
    document.getElementById('modalFooter').innerHTML = `
        <button class="btn btn-secondary" onclick="closeModal()">Close</button>
        <button class="btn btn-primary" onclick="openEditModal(${id})">Edit</button>
    `;
}

function openEditModal(id) {
    openModal('Edit Tax Invoice');
    
    // Similar to create modal but with pre-filled data
    document.getElementById('modalBody').innerHTML = `
        <form id="editForm">
            <div class="form-section">
                <div class="section-title">Basic Information</div>
                <div class="form-group">
                    <label class="form-label">Invoice Number <span class="required">*</span></label>
                    <input type="text" name="invoice_number" value="TI-20240929-123456" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Invoice Date <span class="required">*</span></label>
                    <input type="date" name="invoice_date" value="2024-09-29" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Due Date <span class="required">*</span></label>
                    <input type="date" name="due_date" value="2024-10-29" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status">
                        <option value="draft">Draft</option>
                        <option value="pending">Pending</option>
                        <option value="approved" selected>Approved</option>
                        <option value="paid">Paid</option>
                    </select>
                </div>
            </div>
        </form>
    `;
    
    document.getElementById('modalFooter').innerHTML = `
        <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
        <button class="btn btn-primary" onclick="submitEditForm()">Update Tax Invoice</button>
    `;
}

function submitCreateForm() {
    const form = document.getElementById('createForm');
    const formData = new FormData(form);
    
    // Here you would typically send the data to the server
    console.log('Creating tax invoice:', Object.fromEntries(formData));
    
    // Close modal and refresh page
    closeModal();
    location.reload();
}

function submitEditForm() {
    const form = document.getElementById('editForm');
    const formData = new FormData(form);
    
    // Here you would typically send the data to the server
    console.log('Updating tax invoice:', Object.fromEntries(formData));
    
    // Close modal and refresh page
    closeModal();
    location.reload();
}
</script>
@endsection
