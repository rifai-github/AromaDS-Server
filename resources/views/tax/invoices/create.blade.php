@extends('layouts.app')

@section('title', 'Create Tax Invoice')
@section('breadcrumb', 'Home / Finance / Tax Invoices / Create')

@section('content')
<style>
    .form-section {
        background: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .section-title {
        color: #214589;
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid #e5e7eb;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-label {
        font-weight: 500;
        color: #374151;
        margin-bottom: 5px;
    }
    
    .form-control {
        border: 1px solid #d1d5db;
        border-radius: 6px;
        padding: 10px 12px;
        font-size: 14px;
    }
    
    .form-control:focus {
        border-color: #214589;
        box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
    }
    
    .btn-primary {
        background-color: #214589;
        border-color: #214589;
        padding: 10px 20px;
        font-weight: 500;
    }
    
    .btn-primary:hover {
        background-color: #1e3a8a;
        border-color: #1e3a8a;
    }
    
    .btn-secondary {
        background-color: #6b7280;
        border-color: #6b7280;
        padding: 10px 20px;
        font-weight: 500;
    }
    
    .btn-secondary:hover {
        background-color: #4b5563;
        border-color: #4b5563;
    }
    
    .required {
        color: #ef4444;
    }
    
    .help-text {
        font-size: 12px;
        color: #6b7280;
        margin-top: 5px;
    }
    
    .row {
        margin-left: -10px;
        margin-right: -10px;
    }
    
    .col-md-6 {
        padding-left: 10px;
        padding-right: 10px;
    }
    
    .col-md-4 {
        padding-left: 10px;
        padding-right: 10px;
    }
    
    .col-md-3 {
        padding-left: 10px;
        padding-right: 10px;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Create Tax Invoice</h1>
                    <p class="text-muted">Create a new tax invoice for billing</p>
                </div>
                <div>
                    <a href="{{ route('finance.tax-invoices.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>

            <form action="{{ route('finance.tax-invoices.store') }}" method="POST" id="taxInvoiceForm">
                @csrf
                
                <!-- Basic Information Section -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-info-circle"></i> Basic Information
                    </h3>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="invoice_number" class="form-label">
                                    Invoice Number <span class="required">*</span>
                                </label>
                                <input type="text" class="form-control" id="invoice_number" name="invoice_number" 
                                       value="{{ old('invoice_number') }}" required>
                                @error('invoice_number')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="invoice_date" class="form-label">
                                    Invoice Date <span class="required">*</span>
                                </label>
                                <input type="date" class="form-control" id="invoice_date" name="invoice_date" 
                                       value="{{ old('invoice_date', date('Y-m-d')) }}" required>
                                @error('invoice_date')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="due_date" class="form-label">
                                    Due Date <span class="required">*</span>
                                </label>
                                <input type="date" class="form-control" id="due_date" name="due_date" 
                                       value="{{ old('due_date') }}" required>
                                @error('due_date')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                                    <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="approved" {{ old('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                                    <option value="paid" {{ old('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                                </select>
                                @error('status')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Customer & Contract Information Section -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-user"></i> Customer & Contract Information
                    </h3>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="customer_id" class="form-label">
                                    Customer <span class="required">*</span>
                                </label>
                                <select class="form-control" id="customer_id" name="customer_id" required>
                                    <option value="">Select Customer</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                            {{ $customer->name }} ({{ $customer->code }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('customer_id')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="contract_id" class="form-label">Contract</label>
                                <select class="form-control" id="contract_id" name="contract_id">
                                    <option value="">Select Contract</option>
                                    @foreach($contracts as $contract)
                                        <option value="{{ $contract->id }}" {{ old('contract_id') == $contract->id ? 'selected' : '' }}>
                                            {{ $contract->contract_number }} - {{ $contract->customer->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('contract_id')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="billing_group_id" class="form-label">Billing Group</label>
                                <select class="form-control" id="billing_group_id" name="billing_group_id">
                                    <option value="">Select Billing Group</option>
                                    @foreach($billingGroups as $billingGroup)
                                        <option value="{{ $billingGroup->id }}" {{ old('billing_group_id') == $billingGroup->id ? 'selected' : '' }}>
                                            {{ $billingGroup->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('billing_group_id')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tax_code" class="form-label">Tax Code</label>
                                <input type="text" class="form-control" id="tax_code" name="tax_code" 
                                       value="{{ old('tax_code') }}" placeholder="e.g., 01.234.567.8-901.000">
                                @error('tax_code')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Financial Information Section -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-calculator"></i> Financial Information
                    </h3>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="subtotal" class="form-label">
                                    Subtotal <span class="required">*</span>
                                </label>
                                <input type="number" class="form-control" id="subtotal" name="subtotal" 
                                       value="{{ old('subtotal') }}" step="0.01" min="0" required>
                                @error('subtotal')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="tax_amount" class="form-label">
                                    Tax Amount <span class="required">*</span>
                                </label>
                                <input type="number" class="form-control" id="tax_amount" name="tax_amount" 
                                       value="{{ old('tax_amount') }}" step="0.01" min="0" required>
                                @error('tax_amount')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="total_amount" class="form-label">
                                    Total Amount <span class="required">*</span>
                                </label>
                                <input type="number" class="form-control" id="total_amount" name="total_amount" 
                                       value="{{ old('total_amount') }}" step="0.01" min="0" required>
                                @error('total_amount')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tax_status" class="form-label">Tax Status</label>
                                <select class="form-control" id="tax_status" name="tax_status">
                                    <option value="pending" {{ old('tax_status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="applied" {{ old('tax_status') == 'applied' ? 'selected' : '' }}>Applied</option>
                                    <option value="exempt" {{ old('tax_status') == 'exempt' ? 'selected' : '' }}>Exempt</option>
                                </select>
                                @error('tax_status')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="is_e_materai_applied" class="form-label">e-Materai Applied</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_e_materai_applied" 
                                           name="is_e_materai_applied" value="1" {{ old('is_e_materai_applied') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_e_materai_applied">
                                        Apply e-Materai to this invoice
                                    </label>
                                </div>
                                @error('is_e_materai_applied')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Information Section -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-sticky-note"></i> Additional Information
                    </h3>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tax_notes" class="form-label">Tax Notes</label>
                                <textarea class="form-control" id="tax_notes" name="tax_notes" rows="3" 
                                          placeholder="Additional notes about tax calculation...">{{ old('tax_notes') }}</textarea>
                                @error('tax_notes')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="notes" class="form-label">General Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3" 
                                          placeholder="Additional notes about this invoice...">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-section">
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('finance.tax-invoices.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Create Tax Invoice
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-calculate total amount
    const subtotalInput = document.getElementById('subtotal');
    const taxAmountInput = document.getElementById('tax_amount');
    const totalAmountInput = document.getElementById('total_amount');
    
    function calculateTotal() {
        const subtotal = parseFloat(subtotalInput.value) || 0;
        const taxAmount = parseFloat(taxAmountInput.value) || 0;
        const total = subtotal + taxAmount;
        totalAmountInput.value = total.toFixed(2);
    }
    
    subtotalInput.addEventListener('input', calculateTotal);
    taxAmountInput.addEventListener('input', calculateTotal);
    
    // Auto-generate invoice number if empty
    const invoiceNumberInput = document.getElementById('invoice_number');
    if (!invoiceNumberInput.value) {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const timestamp = now.getTime().toString().slice(-6);
        invoiceNumberInput.value = `TI-${year}${month}${day}-${timestamp}`;
    }
    
    // Set default due date (30 days from invoice date)
    const invoiceDateInput = document.getElementById('invoice_date');
    const dueDateInput = document.getElementById('due_date');
    
    invoiceDateInput.addEventListener('change', function() {
        if (this.value && !dueDateInput.value) {
            const invoiceDate = new Date(this.value);
            const dueDate = new Date(invoiceDate);
            dueDate.setDate(dueDate.getDate() + 30);
            dueDateInput.value = dueDate.toISOString().split('T')[0];
        }
    });
});
</script>
@endsection