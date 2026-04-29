@extends('layouts.app')

@section('title', 'Create e-Materai Transaction')
@section('breadcrumb', 'Home / Finance / e-Materai Transactions / Create')

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
    
    .status-badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
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
    
    .invoice-info {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 15px;
        margin-top: 10px;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Create e-Materai Transaction</h1>
                    <p class="text-muted">Apply e-Materai to a tax invoice</p>
                </div>
                <div>
                    <a href="{{ route('finance.e-materai-transactions.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>

            <form action="{{ route('finance.e-materai-transactions.store') }}" method="POST" id="eMateraiForm">
                @csrf
                
                <!-- Transaction Information Section -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-stamp"></i> Transaction Information
                    </h3>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="transaction_id" class="form-label">
                                    Transaction ID <span class="required">*</span>
                                </label>
                                <input type="text" class="form-control" id="transaction_id" name="transaction_id" 
                                       value="{{ old('transaction_id') }}" required>
                                @error('transaction_id')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                                <div class="help-text">Auto-generated if left empty</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="applied" {{ old('status') == 'applied' ? 'selected' : '' }}>Applied</option>
                                    <option value="failed" {{ old('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                                    <option value="cancelled" {{ old('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                </select>
                                @error('status')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="peruri_reference_number" class="form-label">Peruri Reference Number</label>
                                <input type="text" class="form-control" id="peruri_reference_number" name="peruri_reference_number" 
                                       value="{{ old('peruri_reference_number') }}" placeholder="Reference from Peruri API">
                                @error('peruri_reference_number')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                                <div class="help-text">Will be filled automatically when e-Materai is applied</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="applied_at" class="form-label">Applied At</label>
                                <input type="datetime-local" class="form-control" id="applied_at" name="applied_at" 
                                       value="{{ old('applied_at') }}">
                                @error('applied_at')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                                <div class="help-text">Leave empty for current date/time</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Invoice Information Section -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-file-invoice"></i> Invoice Information
                    </h3>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tax_invoice_id" class="form-label">
                                    Tax Invoice <span class="required">*</span>
                                </label>
                                <select class="form-control" id="tax_invoice_id" name="tax_invoice_id" required>
                                    <option value="">Select Tax Invoice</option>
                                    @foreach($taxInvoices as $invoice)
                                        <option value="{{ $invoice->id }}" {{ old('tax_invoice_id') == $invoice->id ? 'selected' : '' }}>
                                            {{ $invoice->invoice_number }} - {{ $invoice->customer->name ?? 'N/A' }} 
                                            ({{ number_format($invoice->total_amount, 0, ',', '.') }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('tax_invoice_id')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="invoice_id" class="form-label">Regular Invoice</label>
                                <select class="form-control" id="invoice_id" name="invoice_id">
                                    <option value="">Select Regular Invoice (Optional)</option>
                                    @foreach($invoices as $invoice)
                                        <option value="{{ $invoice->id }}" {{ old('invoice_id') == $invoice->id ? 'selected' : '' }}>
                                            {{ $invoice->invoice_number }} - {{ $invoice->customer->name ?? 'N/A' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('invoice_id')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="invoice-info" id="invoice-info" style="display: none;">
                        <h6 class="mb-2">Selected Invoice Information</h6>
                        <div id="invoice-info-content">
                            <p class="text-muted mb-0">Select a tax invoice to see details</p>
                        </div>
                    </div>
                </div>

                <!-- Document Information Section -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-file-pdf"></i> Document Information
                    </h3>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="document_path" class="form-label">Document Path</label>
                                <input type="text" class="form-control" id="document_path" name="document_path" 
                                       value="{{ old('document_path') }}" placeholder="Path to the document file">
                                @error('document_path')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                                <div class="help-text">Path to the PDF document that will receive e-Materai</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="file_upload" class="form-label">Upload Document</label>
                                <input type="file" class="form-control" id="file_upload" name="file_upload" 
                                       accept=".pdf" onchange="updateDocumentPath(this)">
                                <div class="help-text">Upload PDF document (optional, will auto-generate path)</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- API Response Section -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-code"></i> API Response Data
                    </h3>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="response_data" class="form-label">Response Data (JSON)</label>
                                <textarea class="form-control" id="response_data" name="response_data" rows="4" 
                                          placeholder='{"status": "success", "reference": "123456", ...}'>{{ old('response_data') }}</textarea>
                                @error('response_data')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                                <div class="help-text">JSON response from Peruri API (will be filled automatically)</div>
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
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3" 
                                          placeholder="Additional notes about this e-Materai transaction...">{{ old('notes') }}</textarea>
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
                        <a href="{{ route('finance.e-materai-transactions.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <div>
                            <button type="button" class="btn btn-info me-2" onclick="applyEMaterai()">
                                <i class="fas fa-stamp"></i> Apply e-Materai
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Create Transaction
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-generate transaction ID if empty
    const transactionIdInput = document.getElementById('transaction_id');
    if (!transactionIdInput.value) {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const timestamp = now.getTime().toString().slice(-6);
        transactionIdInput.value = `EM-${year}${month}${day}-${timestamp}`;
    }
    
    // Update invoice information when tax invoice is selected
    const taxInvoiceSelect = document.getElementById('tax_invoice_id');
    const invoiceInfo = document.getElementById('invoice-info');
    const invoiceInfoContent = document.getElementById('invoice-info-content');
    
    taxInvoiceSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (this.value) {
            const invoiceText = selectedOption.text;
            const parts = invoiceText.split(' - ');
            const invoiceNumber = parts[0];
            const customerName = parts[1].split(' (')[0];
            const amount = parts[1].split(' (')[1].replace(')', '');
            
            invoiceInfoContent.innerHTML = `
                <div class="row">
                    <div class="col-md-4">
                        <strong>Invoice Number:</strong><br>
                        ${invoiceNumber}
                    </div>
                    <div class="col-md-4">
                        <strong>Customer:</strong><br>
                        ${customerName}
                    </div>
                    <div class="col-md-4">
                        <strong>Amount:</strong><br>
                        Rp ${amount}
                    </div>
                </div>
            `;
            invoiceInfo.style.display = 'block';
        } else {
            invoiceInfo.style.display = 'none';
        }
    });
    
    // Set default applied_at to current date/time
    const appliedAtInput = document.getElementById('applied_at');
    if (!appliedAtInput.value) {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        appliedAtInput.value = `${year}-${month}-${day}T${hours}:${minutes}`;
    }
});

function updateDocumentPath(input) {
    if (input.files && input.files[0]) {
        const fileName = input.files[0].name;
        const documentPathInput = document.getElementById('document_path');
        documentPathInput.value = `storage/documents/e-materai/${fileName}`;
    }
}

function applyEMaterai() {
    // This would typically make an AJAX call to apply e-Materai via Peruri API
    alert('e-Materai application feature will be implemented in the controller');
}
</script>
@endsection