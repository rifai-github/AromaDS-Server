@extends('layouts.app')

@section('title', 'Invoice Forms - Finance')
@section('breadcrumb', 'Home / Finance / Invoice Forms / List')

@section('content')
<div class="w-full">
    <!-- Header Section -->
    <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center w-full bg-white rounded-t-[10px] pt-[7px] pr-[7px] pb-[7px] pl-[7px] md:pt-[10px] md:pr-[10px] md:pb-[10px] md:pl-[10px] lg:pt-[14px] lg:pr-[14px] lg:pb-[14px] lg:pl-[14px] gap-4">
        <div class="flex flex-row justify-start items-center w-full">
            <p class="text-[8px] md:text-[12px] lg:text-[16px] font-inter font-medium leading-[10px] md:leading-[15px] lg:leading-[20px] text-left text-[#214589] w-auto">Invoice Forms Management</p>
        </div>
        
        <div class="flex flex-col sm:flex-row gap-2 w-full lg:w-auto">
            <button type="button" onclick="openCreateModal()" class="btn-primary">
                <i class="fas fa-plus text-white text-[7px] md:text-[10px] lg:text-[14px] mr-2"></i>
                <span class="text-[7px] md:text-[10px] lg:text-[14px] font-inter font-medium leading-[8px] md:leading-[12px] lg:leading-[17px] text-center text-white">Create New Form</span>
            </button>
            <button type="button" onclick="openImportModal()" class="btn-secondary">
                <i class="fas fa-upload text-white text-[7px] md:text-[10px] lg:text-[14px] mr-2"></i>
                <span class="text-[7px] md:text-[10px] lg:text-[14px] font-inter font-medium leading-[8px] md:leading-[12px] lg:leading-[17px] text-center text-white">Import Forms</span>
            </button>
            <button type="button" onclick="exportData()" class="btn-secondary">
                <i class="fas fa-download text-white text-[7px] md:text-[10px] lg:text-[14px] mr-2"></i>
                <span class="text-[7px] md:text-[10px] lg:text-[14px] font-inter font-medium leading-[8px] md:leading-[12px] lg:leading-[17px] text-center text-white">Export Data</span>
            </button>
        </div>
    </div>

    <!-- Controls Row -->
    <div class="controls-row bg-white border-t border-gray-200 px-[7px] py-[10px] md:px-[10px] md:py-[12px] lg:px-[14px] lg:py-[14px]">
        <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center gap-4">
            <!-- Search and Filters -->
            <div class="flex flex-col sm:flex-row gap-4 flex-1">
                <div class="search-container">
                    <div class="relative">
                        <input type="text" 
                               id="search-input" 
                               placeholder="Search forms..." 
                               class="search-input"
                               value="{{ request('search') }}">
                        <i class="fas fa-search search-icon"></i>
                    </div>
                </div>
                
                <div class="filter-container">
                    <select id="status-filter" class="filter-select">
                        <option value="">All Status</option>
                        <option value="draft">Draft</option>
                        <option value="submitted">Submitted</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                
                <div class="filter-container">
                    <select id="type-filter" class="filter-select">
                        <option value="">All Types</option>
                        <option value="invoice">Invoice</option>
                        <option value="credit_note">Credit Note</option>
                        <option value="debit_note">Debit Note</option>
                    </select>
                </div>
            </div>
            
            <!-- Bulk Actions -->
            <div class="bulk-actions">
                <span id="selected-count" class="selected-count">0 selected</span>
                <button type="button" id="bulk-approve-btn" class="bulk-btn" disabled>
                    <i class="fas fa-check"></i>
                    Approve
                </button>
                <button type="button" id="bulk-delete-btn" class="bulk-btn bulk-delete" disabled>
                    <i class="fas fa-trash"></i>
                    Delete
                </button>
            </div>
        </div>
    </div>

    <!-- Content Container -->
    <div class="content-container w-full bg-white rounded-b-[10px] p-[7px] md:p-[10px] lg:p-[14px]">
        
        <!-- Statistics Cards -->
        <div class="stats-grid mb-6">
            <div class="stat-card">
                <div class="stat-icon bg-blue-100">
                    <i class="fas fa-file-invoice text-blue-600"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number">{{ $invoiceForms->total() ?? 0 }}</div>
                    <div class="stat-label">Total Forms</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-green-100">
                    <i class="fas fa-check-circle text-green-600"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number">{{ $invoiceFormStats['approved'] ?? 0 }}</div>
                    <div class="stat-label">Approved</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-yellow-100">
                    <i class="fas fa-clock text-yellow-600"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number">{{ $invoiceFormStats['draft'] ?? 0 }}</div>
                    <div class="stat-label">Draft</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-red-100">
                    <i class="fas fa-times-circle text-red-600"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number">{{ $invoiceFormStats['rejected'] ?? 0 }}</div>
                    <div class="stat-label">Rejected</div>
                </div>
            </div>
        </div>

        <!-- Table Section -->
        <div class="table-container">
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="checkbox-column">
                                <input type="checkbox" id="select-all" class="checkbox-input">
                            </th>
                            <th class="sortable" data-column="form_number">
                                Form Number <i class="fas fa-sort sort-icon"></i>
                            </th>
                            <th class="sortable" data-column="customer">
                                Customer <i class="fas fa-sort sort-icon"></i>
                            </th>
                            <th class="sortable" data-column="form_type">
                                Form Type <i class="fas fa-sort sort-icon"></i>
                            </th>
                            <th class="sortable" data-column="total_amount">
                                Amount <i class="fas fa-sort sort-icon"></i>
                            </th>
                            <th class="sortable" data-column="status">
                                Status <i class="fas fa-sort sort-icon"></i>
                            </th>
                            <th class="sortable" data-column="created_by">
                                Created By <i class="fas fa-sort sort-icon"></i>
                            </th>
                            <th class="sortable" data-column="created_at">
                                Created Date <i class="fas fa-sort sort-icon"></i>
                            </th>
                            <th class="sortable" data-column="updated_by">
                                Last Updated By <i class="fas fa-sort sort-icon"></i>
                            </th>
                            <th class="sortable" data-column="updated_at">
                                Last Updated At <i class="fas fa-sort sort-icon"></i>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoiceForms ?? [] as $form)
                            <tr class="data-row" data-id="{{ $form->id }}">
                                <td class="checkbox-column">
                                    <input type="checkbox" class="row-checkbox checkbox-input" value="{{ $form->id }}">
                                </td>
                                <td class="font-medium text-gray-900">
                                    {{ $form->form_number ?? 'N/A' }}
                                </td>
                                <td class="text-gray-700">
                                    {{ $form->company_name ?? 'N/A' }}
                                </td>
                                <td>
                                    <span class="status-badge type-badge 
                                        @if($form->form_type == 'invoice') bg-blue-100 text-blue-800
                                        @elseif($form->form_type == 'credit_note') bg-green-100 text-green-800
                                        @elseif($form->form_type == 'debit_note') bg-yellow-100 text-yellow-800
                                        @else bg-gray-100 text-gray-800
                                        @endif">
                                        {{ ucfirst(str_replace('_', ' ', $form->form_type ?? 'Unknown')) }}
                                    </span>
                                </td>
                                <td class="font-medium text-gray-900">
                                    IDR {{ number_format($form->grand_total ?? 0) }}
                                </td>
                                <td>
                                    <span class="status-badge 
                                        @if($form->status == 'draft') bg-gray-100 text-gray-800
                                        @elseif($form->status == 'submitted') bg-blue-100 text-blue-800
                                        @elseif($form->status == 'approved') bg-green-100 text-green-800
                                        @else bg-red-100 text-red-800
                                        @endif">
                                        {{ ucfirst($form->status ?? 'Unknown') }}
                                    </span>
                                </td>
                                <td class="text-gray-500 text-sm">{{ $form->creator->name ?? '-' }}</td>
                                <td class="text-gray-500 text-sm">{!! $form->created_at ? $form->created_at->format('d/M/Y<br>at H.i') . ' WIB' : '-' !!}</td>
                                <td class="text-gray-500 text-sm">{{ $form->updater->name ?? '-' }}</td>
                                <td class="text-gray-500 text-sm">{!! $form->updated_at ? $form->updated_at->format('d/M/Y<br>at H.i') . ' WIB' : '-' !!}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="empty-state">
                                    <div class="empty-content">
                                        <i class="fas fa-file-invoice empty-icon"></i>
                                        <h3 class="empty-title">No Invoice Forms Found</h3>
                                        <p class="empty-description">Create your first invoice form to get started.</p>
                                        <button type="button" onclick="openCreateModal()" class="btn-primary">
                                            <i class="fas fa-plus mr-2"></i>
                                            Create First Form
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        @if(isset($invoiceForms) && $invoiceForms->hasPages())
            <div class="pagination-container">
                {{ $invoiceForms->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Main Modal -->
<div id="main-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modal-title" class="modal-title">Modal Title</h3>
            <button type="button" class="modal-close" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="modal-form">
                @csrf
                <div class="form-grid">
                    <!-- Basic Information -->
                    <div class="form-group">
                        <label for="invoice_number" class="form-label">Invoice Number</label>
                        <input type="text" id="invoice_number" name="invoice_number" class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label for="po_number" class="form-label">PO Number</label>
                        <input type="text" id="po_number" name="po_number" class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label for="contract_number" class="form-label">Contract Number</label>
                        <select id="contract_number" name="contract_number" class="form-select">
                            <option value="">Select Contract</option>
                            @foreach($contracts ?? [] as $contract)
                                <option value="{{ $contract->contract_number }}">{{ $contract->contract_number }} - {{ $contract->customer->company_name ?? 'N/A' }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="company_name" class="form-label">Company Name <span class="required">*</span></label>
                        <input type="text" id="company_name" name="company_name" class="form-input" required>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="billing_address" class="form-label">Billing Address</label>
                        <textarea id="billing_address" name="billing_address" class="form-textarea" rows="2"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="period_invoice" class="form-label">Period Invoice</label>
                        <input type="text" id="period_invoice" name="period_invoice" class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label for="invoice_status" class="form-label">Invoice Status <span class="required">*</span></label>
                        <select id="invoice_status" name="invoice_status" class="form-select" required>
                            <option value="draft">Draft</option>
                            <option value="sent">Sent</option>
                            <option value="paid">Paid</option>
                            <option value="overdue">Overdue</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="invoice_date" class="form-label">Invoice Date <span class="required">*</span></label>
                        <input type="date" id="invoice_date" name="invoice_date" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="due_date" class="form-label">Due Date <span class="required">*</span></label>
                        <input type="date" id="due_date" name="due_date" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="form_type" class="form-label">Form Type <span class="required">*</span></label>
                        <select id="form_type" name="form_type" class="form-select" required>
                            <option value="invoice">Invoice</option>
                            <option value="credit_note">Credit Note</option>
                            <option value="debit_note">Debit Note</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="status" class="form-label">Status <span class="required">*</span></label>
                        <select id="status" name="status" class="form-select" required>
                            <option value="draft">Draft</option>
                            <option value="submitted">Submitted</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    
                    <!-- Tax Information -->
                    <div class="form-group">
                        <label for="tax_obligation" class="form-label">Tax Obligation</label>
                        <select id="tax_obligation" name="tax_obligation" class="form-select">
                            <option value="0">No</option>
                            <option value="1">Yes</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="tax_code" class="form-label">Tax Code</label>
                        <input type="text" id="tax_code" name="tax_code" class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label for="tax_number" class="form-label">Tax Number</label>
                        <input type="text" id="tax_number" name="tax_number" class="form-input">
                    </div>
                    
                    <!-- Financial Information -->
                    <div class="form-group">
                        <label for="subtotal" class="form-label">Subtotal <span class="required">*</span></label>
                        <input type="number" id="subtotal" name="subtotal" class="form-input" step="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="discount_amount" class="form-label">Discount Amount</label>
                        <input type="number" id="discount_amount" name="discount_amount" class="form-input" step="0.01" value="0">
                    </div>
                    
                    <div class="form-group">
                        <label for="subtotal_after_discount" class="form-label">Subtotal After Discount</label>
                        <input type="number" id="subtotal_after_discount" name="subtotal_after_discount" class="form-input" step="0.01">
                    </div>
                    
                    <div class="form-group">
                        <label for="tax_amount" class="form-label">Tax Amount</label>
                        <input type="number" id="tax_amount" name="tax_amount" class="form-input" step="0.01" value="0">
                    </div>
                    
                    <div class="form-group">
                        <label for="grand_total" class="form-label">Grand Total <span class="required">*</span></label>
                        <input type="number" id="grand_total" name="grand_total" class="form-input" step="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="total_paid" class="form-label">Total Paid</label>
                        <input type="number" id="total_paid" name="total_paid" class="form-input" step="0.01" value="0">
                    </div>
                    
                    <div class="form-group">
                        <label for="outstanding" class="form-label">Outstanding</label>
                        <input type="number" id="outstanding" name="outstanding" class="form-input" step="0.01">
                    </div>
                    
                    <!-- Tax Address Information -->
                    <div class="form-group">
                        <label for="npwp_number" class="form-label">NPWP Number</label>
                        <input type="text" id="npwp_number" name="npwp_number" class="form-input">
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="tax_address" class="form-label">Tax Address</label>
                        <textarea id="tax_address" name="tax_address" class="form-textarea" rows="2"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="province_name" class="form-label">Province</label>
                        <input type="text" id="province_name" name="province_name" class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label for="city_name" class="form-label">City</label>
                        <input type="text" id="city_name" name="city_name" class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label for="district_name" class="form-label">District</label>
                        <input type="text" id="district_name" name="district_name" class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label for="village_name" class="form-label">Village</label>
                        <input type="text" id="village_name" name="village_name" class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label for="postal_code" class="form-label">Postal Code</label>
                        <input type="text" id="postal_code" name="postal_code" class="form-input">
                    </div>
                    
                    <!-- Notes -->
                    <div class="form-group full-width">
                        <label for="internal_notes" class="form-label">Internal Notes</label>
                        <textarea id="internal_notes" name="internal_notes" class="form-textarea" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="additional_notes" class="form-label">Additional Notes</label>
                        <textarea id="additional_notes" name="additional_notes" class="form-textarea" rows="3"></textarea>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
            <button type="button" id="modal-save-btn" class="btn-primary" onclick="saveForm()">Save</button>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="delete-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Confirm Delete</h3>
            <button type="button" class="modal-close" onclick="closeDeleteModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="delete-content">
                <i class="fas fa-exclamation-triangle delete-icon"></i>
                <p class="delete-message">Are you sure you want to delete this invoice form?</p>
                <p class="delete-warning">This action cannot be undone.</p>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-secondary" onclick="closeDeleteModal()">Cancel</button>
            <button type="button" id="confirm-delete-btn" class="btn-danger" onclick="confirmDelete()">Delete</button>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div id="success-modal" class="modal">
    <div class="modal-content">
        <div class="modal-body">
            <div class="success-content">
                <i class="fas fa-check-circle success-icon"></i>
                <h3 class="success-title">Success!</h3>
                <p id="success-message" class="success-message">Operation completed successfully.</p>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-primary" onclick="closeSuccessModal()">OK</button>
        </div>
    </div>
</div>

<!-- Error Modal -->
<div id="error-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Error</h3>
            <button type="button" class="modal-close" onclick="closeErrorModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="error-content">
                <i class="fas fa-exclamation-circle error-icon"></i>
                <p id="error-message" class="error-message">An error occurred. Please try again.</p>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-primary" onclick="closeErrorModal()">OK</button>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div id="import-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Import Invoice Forms</h3>
            <button type="button" class="modal-close" onclick="closeImportModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="import-form" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label for="import_file" class="form-label">Select File <span class="required">*</span></label>
                    <input type="file" id="import_file" name="import_file" class="form-input" accept=".csv,.xlsx,.xls" required>
                    <p class="form-help">Supported formats: CSV, Excel (.xlsx, .xls)</p>
                </div>
                <div class="form-group">
                    <label class="form-label">Import Options</label>
                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="skip_duplicates" checked>
                            <span class="checkbox-text">Skip duplicate records</span>
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="validate_data" checked>
                            <span class="checkbox-text">Validate data before import</span>
                        </label>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-secondary" onclick="closeImportModal()">Cancel</button>
            <button type="button" class="btn-primary" onclick="processImport()">Import</button>
        </div>
    </div>
</div>

@endsection

@section('styles')
<style>
/* Base Styles */
.content-container {
    background-color: white;
    border-radius: 10px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

/* Button Styles */
.btn-primary {
    background-color: #214589;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
}

.btn-primary:hover {
    background-color: #1a365d;
    transform: translateY(-1px);
}

.btn-secondary {
    background-color: #6b7280;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
}

.btn-secondary:hover {
    background-color: #4b5563;
    transform: translateY(-1px);
}

.btn-danger {
    background-color: #dc2626;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-danger:hover {
    background-color: #b91c1c;
    transform: translateY(-1px);
}

/* Controls Row */
.controls-row {
    background-color: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}

.search-container {
    flex: 1;
    min-width: 200px;
}

.search-input {
    width: 100%;
    padding: 8px 12px 8px 40px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    transition: all 0.2s ease;
}

.search-input:focus {
    outline: none;
    border-color: #214589;
    box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
}

.search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #6b7280;
    font-size: 14px;
}

.filter-container {
    min-width: 150px;
}

.filter-select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    background-color: white;
    transition: all 0.2s ease;
}

.filter-select:focus {
    outline: none;
    border-color: #214589;
    box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
}

.bulk-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.selected-count {
    font-size: 14px;
    color: #6b7280;
    margin-right: 8px;
}

.bulk-btn {
    padding: 6px 12px;
    border: none;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.bulk-btn:disabled {
    background-color: #d1d5db;
    color: #9ca3af;
    cursor: not-allowed;
}

.bulk-btn:not(:disabled) {
    background-color: #214589;
    color: white;
}

.bulk-btn:not(:disabled):hover {
    background-color: #1a365d;
}

.bulk-delete:not(:disabled) {
    background-color: #dc2626;
}

.bulk-delete:not(:disabled):hover {
    background-color: #b91c1c;
}

/* Statistics Cards */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.stat-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    transition: all 0.2s ease;
}

.stat-card:hover {
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

.stat-content {
    flex: 1;
}

.stat-number {
    font-size: 24px;
    font-weight: 700;
    color: #1f2937;
    line-height: 1;
}

.stat-label {
    font-size: 14px;
    color: #6b7280;
    margin-top: 4px;
}

/* Table Styles */
.table-container {
    overflow-x: auto;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}

.table-wrapper {
    min-width: 100%;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
}

.data-table th {
    background-color: #f8fafc;
    padding: 12px 16px;
    text-align: left;
    font-weight: 600;
    font-size: 12px;
    color: #374151;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 1px solid #e2e8f0;
}

.data-table td {
    padding: 12px 16px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 14px;
}

.data-row:hover {
    background-color: #f8fafc;
}

.checkbox-column {
    width: 40px;
    text-align: center;
}

.checkbox-input {
    width: 16px;
    height: 16px;
    border-radius: 4px;
    border: 1px solid #d1d5db;
    cursor: pointer;
}

.checkbox-input:checked {
    background-color: #214589;
    border-color: #214589;
}

.sortable {
    cursor: pointer;
    user-select: none;
    position: relative;
}

.sortable:hover {
    background-color: #f1f5f9;
}

.sort-icon {
    margin-left: 4px;
    opacity: 0.5;
    font-size: 10px;
}

.actions-column {
    width: 120px;
    text-align: center;
}

.action-buttons {
    display: flex;
    gap: 4px;
    justify-content: center;
}

.action-btn {
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    transition: all 0.2s ease;
}

.view-btn {
    background-color: #3b82f6;
    color: white;
}

.view-btn:hover {
    background-color: #2563eb;
}

.edit-btn {
    background-color: #8b5cf6;
    color: white;
}

.edit-btn:hover {
    background-color: #7c3aed;
}

.delete-btn {
    background-color: #ef4444;
    color: white;
}

.delete-btn:hover {
    background-color: #dc2626;
}

/* Status Badges */
.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
    text-transform: capitalize;
}

.type-badge {
    font-size: 11px;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 48px 16px;
}

.empty-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
}

.empty-icon {
    font-size: 48px;
    color: #d1d5db;
}

.empty-title {
    font-size: 18px;
    font-weight: 600;
    color: #374151;
}

.empty-description {
    font-size: 14px;
    color: #6b7280;
}

/* Pagination */
.pagination-container {
    margin-top: 24px;
    display: flex;
    justify-content: center;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
}

.modal-content {
    background-color: white;
    margin: 5% auto;
    border-radius: 12px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    animation: modalSlideIn 0.3s ease-out;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-50px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.modal-header {
    padding: 20px 24px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-title {
    font-size: 18px;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    font-size: 20px;
    color: #6b7280;
    cursor: pointer;
    padding: 4px;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.modal-close:hover {
    background-color: #f3f4f6;
    color: #374151;
}

.modal-body {
    padding: 24px;
}

.modal-footer {
    padding: 20px 24px;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

/* Form Styles */
.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 16px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-label {
    font-size: 14px;
    font-weight: 500;
    color: #374151;
    margin-bottom: 6px;
}

.required {
    color: #dc2626;
}

.form-input, .form-select, .form-textarea {
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    transition: all 0.2s ease;
    background-color: white;
}

.form-input:focus, .form-select:focus, .form-textarea:focus {
    outline: none;
    border-color: #214589;
    box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
}

.form-textarea {
    resize: vertical;
    min-height: 80px;
}

.form-help {
    font-size: 12px;
    color: #6b7280;
    margin-top: 4px;
}

.checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.checkbox-text {
    font-size: 14px;
    color: #374151;
}

/* Delete Modal */
.delete-content {
    text-align: center;
    padding: 20px;
}

.delete-icon {
    font-size: 48px;
    color: #f59e0b;
    margin-bottom: 16px;
}

.delete-message {
    font-size: 16px;
    font-weight: 500;
    color: #1f2937;
    margin-bottom: 8px;
}

.delete-warning {
    font-size: 14px;
    color: #6b7280;
}

/* Success Modal */
.success-content {
    text-align: center;
    padding: 20px;
}

.success-icon {
    font-size: 48px;
    color: #10b981;
    margin-bottom: 16px;
}

.success-title {
    font-size: 18px;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 8px;
}

.success-message {
    font-size: 14px;
    color: #6b7280;
}

/* Error Modal */
.error-content {
    text-align: center;
    padding: 20px;
}

.error-icon {
    font-size: 48px;
    color: #ef4444;
    margin-bottom: 16px;
}

.error-message {
    font-size: 14px;
    color: #6b7280;
}

/* Responsive Design */
@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .bulk-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .modal-content {
        margin: 10% auto;
        width: 95%;
    }
    
    .data-table th,
    .data-table td {
        padding: 8px 12px;
        font-size: 12px;
    }
}

@media (max-width: 480px) {
    .btn-primary,
    .btn-secondary {
        padding: 6px 12px;
        font-size: 11px;
    }
    
    .stat-card {
        padding: 12px;
    }
    
    .stat-number {
        font-size: 20px;
    }
    
    .modal-header,
    .modal-body,
    .modal-footer {
        padding: 16px;
    }
}
</style>
@endsection

@section('scripts')
<script>
let currentFormId = null;
let deleteFormId = null;
let selectedForms = new Set();

document.addEventListener('DOMContentLoaded', function() {
    initializeEventListeners();
    initializeTable();
});

function initializeEventListeners() {
    // Select all functionality
    const selectAllCheckbox = document.getElementById('select-all');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    
    selectAllCheckbox.addEventListener('change', function() {
        rowCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
            if (this.checked) {
                selectedForms.add(checkbox.value);
            } else {
                selectedForms.delete(checkbox.value);
            }
        });
        updateBulkActions();
    });
    
    rowCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (this.checked) {
                selectedForms.add(this.value);
            } else {
                selectedForms.delete(this.value);
            }
            updateSelectAllState();
            updateBulkActions();
        });
    });
    
    // Search functionality
    const searchInput = document.getElementById('search-input');
    searchInput.addEventListener('input', debounce(function() {
        filterTable();
    }, 300));
    
    // Filter functionality
    const statusFilter = document.getElementById('status-filter');
    const typeFilter = document.getElementById('type-filter');
    
    statusFilter.addEventListener('change', filterTable);
    typeFilter.addEventListener('change', filterTable);
    
    // Bulk actions
    document.getElementById('bulk-approve-btn').addEventListener('click', bulkApprove);
    document.getElementById('bulk-delete-btn').addEventListener('click', bulkDelete);
    
    // Modal close on outside click
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            closeAllModals();
        }
    });
}

function initializeTable() {
    // Add sort functionality
    document.querySelectorAll('.sortable').forEach(header => {
        header.addEventListener('click', function() {
            const column = this.dataset.column;
            sortTable(column);
        });
    });
}

function updateSelectAllState() {
    const selectAllCheckbox = document.getElementById('select-all');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    const checkedCount = Array.from(rowCheckboxes).filter(cb => cb.checked).length;
    
    if (checkedCount === 0) {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = false;
    } else if (checkedCount === rowCheckboxes.length) {
        selectAllCheckbox.checked = true;
        selectAllCheckbox.indeterminate = false;
    } else {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = true;
    }
}

function updateBulkActions() {
    const selectedCount = document.getElementById('selected-count');
    const bulkApproveBtn = document.getElementById('bulk-approve-btn');
    const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
    
    const count = selectedForms.size;
    selectedCount.textContent = `${count} selected`;
    
    bulkApproveBtn.disabled = count === 0;
    bulkDeleteBtn.disabled = count === 0;
}

function filterTable() {
    const searchTerm = document.getElementById('search-input').value.toLowerCase();
    const statusFilter = document.getElementById('status-filter').value;
    const typeFilter = document.getElementById('type-filter').value;
    
    const rows = document.querySelectorAll('.data-row');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const status = row.querySelector('.status-badge').textContent.toLowerCase();
        const type = row.querySelector('.type-badge').textContent.toLowerCase();
        
        const matchesSearch = text.includes(searchTerm);
        const matchesStatus = !statusFilter || status.includes(statusFilter);
        const matchesType = !typeFilter || type.includes(typeFilter.replace('_', ' '));
        
        if (matchesSearch && matchesStatus && matchesType) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function sortTable(column) {
    // Implement table sorting logic here
    console.log('Sorting by:', column);
}

// Modal Functions
function openCreateModal() {
    currentFormId = null;
    document.getElementById('modal-title').textContent = 'Create New Invoice Form';
    document.getElementById('modal-form').reset();
    document.getElementById('modal-save-btn').textContent = 'Create';
    
    // Auto-generate form number
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const timestamp = now.getTime().toString().slice(-4);
    const formNumber = `IF${year}${month}${timestamp}`;
    document.getElementById('form_number').value = formNumber;
    
    // Set default values
    document.getElementById('invoice_status').value = 'draft';
    document.getElementById('form_type').value = 'invoice';
    document.getElementById('status').value = 'draft';
    document.getElementById('tax_obligation').value = '0';
    document.getElementById('discount_amount').value = '0';
    document.getElementById('tax_amount').value = '0';
    document.getElementById('total_paid').value = '0';
    
    // Set current date as default
    const today = now.toISOString().split('T')[0];
    document.getElementById('invoice_date').value = today;
    
    // Set due date to 30 days from now
    const dueDate = new Date(now.getTime() + (30 * 24 * 60 * 60 * 1000));
    document.getElementById('due_date').value = dueDate.toISOString().split('T')[0];
    
    document.getElementById('main-modal').style.display = 'block';
}

// Auto-calculation functions
function calculateTotals() {
    const subtotal = parseFloat(document.getElementById('subtotal').value) || 0;
    const discountAmount = parseFloat(document.getElementById('discount_amount').value) || 0;
    const taxAmount = parseFloat(document.getElementById('tax_amount').value) || 0;
    
    const subtotalAfterDiscount = subtotal - discountAmount;
    const grandTotal = subtotalAfterDiscount + taxAmount;
    const totalPaid = parseFloat(document.getElementById('total_paid').value) || 0;
    const outstanding = grandTotal - totalPaid;
    
    document.getElementById('subtotal_after_discount').value = subtotalAfterDiscount.toFixed(2);
    document.getElementById('grand_total').value = grandTotal.toFixed(2);
    document.getElementById('outstanding').value = outstanding.toFixed(2);
}

// Add event listeners for auto-calculation
document.addEventListener('DOMContentLoaded', function() {
    const subtotalInput = document.getElementById('subtotal');
    const discountInput = document.getElementById('discount_amount');
    const taxInput = document.getElementById('tax_amount');
    const paidInput = document.getElementById('total_paid');
    
    if (subtotalInput) subtotalInput.addEventListener('input', calculateTotals);
    if (discountInput) discountInput.addEventListener('input', calculateTotals);
    if (taxInput) taxInput.addEventListener('input', calculateTotals);
    if (paidInput) paidInput.addEventListener('input', calculateTotals);
});

function editForm(id) {
    currentFormId = id;
    document.getElementById('modal-title').textContent = 'Edit Invoice Form';
    document.getElementById('modal-save-btn').textContent = 'Update';
    
    // Fetch form data and populate modal
    fetch(`/finance/invoice-forms/${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('form_number').value = data.form_number || '';
            document.getElementById('customer_id').value = data.customer_id || '';
            document.getElementById('form_type').value = data.form_type || '';
            document.getElementById('total_amount').value = data.total_amount || '';
            document.getElementById('status').value = data.status || '';
            document.getElementById('notes').value = data.notes || '';
        })
        .catch(error => {
            showError('Failed to load form data');
        });
    
    document.getElementById('main-modal').style.display = 'block';
}

function viewForm(id) {
    // Redirect to view page
    window.location.href = `/finance/invoice-forms/${id}`;
}

function deleteForm(id) {
    deleteFormId = id;
    document.getElementById('delete-modal').style.display = 'block';
}

function confirmDelete() {
    if (deleteFormId) {
        fetch(`/finance/invoice-forms/${deleteFormId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeDeleteModal();
                showSuccess('Invoice form deleted successfully');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showError(data.message || 'Failed to delete invoice form');
            }
        })
        .catch(error => {
            showError('An error occurred while deleting the form');
        });
    }
}

function saveForm() {
    const form = document.getElementById('modal-form');
    const formData = new FormData(form);
    
    // Ensure outstanding is calculated
    calculateTotals();
    
    const url = currentFormId ? `/finance/invoice-forms/${currentFormId}` : '/finance/invoice-forms';
    const method = currentFormId ? 'PUT' : 'POST';
    
    if (currentFormId) {
        formData.append('_method', 'PUT');
    }
    
    fetch(url, {
        method: method,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeModal();
            showSuccess(currentFormId ? 'Invoice form updated successfully' : 'Invoice form created successfully');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showError(data.message || 'Failed to save invoice form');
        }
    })
    .catch(error => {
        showError('An error occurred while saving the form');
    });
}

function bulkApprove() {
    if (selectedForms.size === 0) return;
    
    const formData = new FormData();
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
    formData.append('ids', Array.from(selectedForms));
    
    fetch('/finance/invoice-forms/bulk-approve', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess(`${data.count} forms approved successfully`);
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showError(data.message || 'Failed to approve forms');
        }
    })
    .catch(error => {
        showError('An error occurred while approving forms');
    });
}

function bulkDelete() {
    if (selectedForms.size === 0) return;
    
    if (confirm(`Are you sure you want to delete ${selectedForms.size} selected forms?`)) {
        const formData = new FormData();
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
        formData.append('ids', Array.from(selectedForms));
        
        fetch('/finance/invoice-forms/bulk-delete', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSuccess(`${data.count} forms deleted successfully`);
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showError(data.message || 'Failed to delete forms');
            }
        })
        .catch(error => {
            showError('An error occurred while deleting forms');
        });
    }
}

function openImportModal() {
    document.getElementById('import-modal').style.display = 'block';
}

function processImport() {
    const form = document.getElementById('import-form');
    const formData = new FormData(form);
    
    fetch('/finance/invoice-forms/import', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeImportModal();
            showSuccess(`Successfully imported ${data.count} forms`);
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showError(data.message || 'Failed to import forms');
        }
    })
    .catch(error => {
        showError('An error occurred while importing forms');
    });
}

function exportData() {
    const statusFilter = document.getElementById('status-filter').value;
    const typeFilter = document.getElementById('type-filter').value;
    
    let url = '/finance/invoice-forms/export?';
    if (statusFilter) url += `status=${statusFilter}&`;
    if (typeFilter) url += `type=${typeFilter}&`;
    
    window.open(url, '_blank');
}

// Modal Close Functions
function closeModal() {
    document.getElementById('main-modal').style.display = 'none';
}

function closeDeleteModal() {
    document.getElementById('delete-modal').style.display = 'none';
    deleteFormId = null;
}

function closeSuccessModal() {
    document.getElementById('success-modal').style.display = 'none';
}

function closeErrorModal() {
    document.getElementById('error-modal').style.display = 'none';
}

function closeImportModal() {
    document.getElementById('import-modal').style.display = 'none';
}

function closeAllModals() {
    closeModal();
    closeDeleteModal();
    closeSuccessModal();
    closeErrorModal();
    closeImportModal();
}

// Utility Functions
function showSuccess(message) {
    document.getElementById('success-message').textContent = message;
    document.getElementById('success-modal').style.display = 'block';
}

function showError(message) {
    document.getElementById('error-message').textContent = message;
    document.getElementById('error-modal').style.display = 'block';
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
</script>
@endsection
