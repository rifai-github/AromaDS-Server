@extends('layouts.app')

@section('title', 'Edit Tax Invoice')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Edit Tax Invoice</h3>
                    <div class="card-tools">
                        <a href="{{ route('tax-invoices.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                        <a href="{{ route('tax-invoices.show', $taxInvoice) }}" class="btn btn-info">
                            <i class="fas fa-eye"></i> View
                        </a>
                    </div>
                </div>
                <form action="{{ route('tax-invoices.update', $taxInvoice) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="invoice_number">Invoice Number <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('invoice_number') is-invalid @enderror" 
                                           id="invoice_number" name="invoice_number" value="{{ old('invoice_number', $taxInvoice->invoice_number) }}" required>
                                    @error('invoice_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="customer_id">Customer <span class="text-danger">*</span></label>
                                    <select class="form-control @error('customer_id') is-invalid @enderror" 
                                            id="customer_id" name="customer_id" required>
                                        <option value="">Select Customer</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}" {{ old('customer_id', $taxInvoice->customer_id) == $customer->id ? 'selected' : '' }}>
                                                {{ $customer->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('customer_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="contract_id">Contract</label>
                                    <select class="form-control @error('contract_id') is-invalid @enderror" 
                                            id="contract_id" name="contract_id">
                                        <option value="">Select Contract</option>
                                        @foreach($contracts as $contract)
                                            <option value="{{ $contract->id }}" {{ old('contract_id', $taxInvoice->contract_id) == $contract->id ? 'selected' : '' }}>
                                                {{ $contract->contract_number }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('contract_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="billing_group_id">Billing Group</label>
                                    <select class="form-control @error('billing_group_id') is-invalid @enderror" 
                                            id="billing_group_id" name="billing_group_id">
                                        <option value="">Select Billing Group</option>
                                        @foreach($billingGroups as $billingGroup)
                                            <option value="{{ $billingGroup->id }}" {{ old('billing_group_id', $taxInvoice->billing_group_id) == $billingGroup->id ? 'selected' : '' }}>
                                                {{ $billingGroup->billing_group_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('billing_group_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="invoice_date">Invoice Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('invoice_date') is-invalid @enderror" 
                                           id="invoice_date" name="invoice_date" value="{{ old('invoice_date', $taxInvoice->invoice_date->format('Y-m-d')) }}" required>
                                    @error('invoice_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="due_date">Due Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('due_date') is-invalid @enderror" 
                                           id="due_date" name="due_date" value="{{ old('due_date', $taxInvoice->due_date->format('Y-m-d')) }}" required>
                                    @error('due_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="subtotal">Subtotal <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control @error('subtotal') is-invalid @enderror" 
                                           id="subtotal" name="subtotal" value="{{ old('subtotal', $taxInvoice->subtotal) }}" required onchange="calculateTax()">
                                    @error('subtotal')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="tax_amount">Tax Amount <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control @error('tax_amount') is-invalid @enderror" 
                                           id="tax_amount" name="tax_amount" value="{{ old('tax_amount', $taxInvoice->tax_amount) }}" required>
                                    @error('tax_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="total_amount">Total Amount <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control @error('total_amount') is-invalid @enderror" 
                                           id="total_amount" name="total_amount" value="{{ old('total_amount', $taxInvoice->total_amount) }}" required readonly>
                                    @error('total_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="status">Status <span class="text-danger">*</span></label>
                                    <select class="form-control @error('status') is-invalid @enderror" 
                                            id="status" name="status" required>
                                        <option value="draft" {{ old('status', $taxInvoice->status) == 'draft' ? 'selected' : '' }}>Draft</option>
                                        <option value="pending" {{ old('status', $taxInvoice->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="approved" {{ old('status', $taxInvoice->status) == 'approved' ? 'selected' : '' }}>Approved</option>
                                        <option value="rejected" {{ old('status', $taxInvoice->status) == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                        <option value="cancelled" {{ old('status', $taxInvoice->status) == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tax_status">Tax Status <span class="text-danger">*</span></label>
                                    <select class="form-control @error('tax_status') is-invalid @enderror" 
                                            id="tax_status" name="tax_status" required>
                                        <option value="exempt" {{ old('tax_status', $taxInvoice->tax_status) == 'exempt' ? 'selected' : '' }}>Exempt</option>
                                        <option value="applied" {{ old('tax_status', $taxInvoice->tax_status) == 'applied' ? 'selected' : '' }}>Applied</option>
                                        <option value="pending" {{ old('tax_status', $taxInvoice->tax_status) == 'pending' ? 'selected' : '' }}>Pending</option>
                                    </select>
                                    @error('tax_status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tax_code">Tax Code</label>
                                    <input type="text" class="form-control @error('tax_code') is-invalid @enderror" 
                                           id="tax_code" name="tax_code" value="{{ old('tax_code', $taxInvoice->tax_code) }}">
                                    @error('tax_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tax_notes">Tax Notes</label>
                                    <input type="text" class="form-control @error('tax_notes') is-invalid @enderror" 
                                           id="tax_notes" name="tax_notes" value="{{ old('tax_notes', $taxInvoice->tax_notes) }}">
                                    @error('tax_notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="notes">Notes</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                      id="notes" name="notes" rows="3">{{ old('notes', $taxInvoice->notes) }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Tax Invoice
                        </button>
                        <a href="{{ route('tax-invoices.show', $taxInvoice) }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function calculateTax() {
    const subtotal = parseFloat(document.getElementById('subtotal').value) || 0;
    const taxRate = 0.11; // 11% PPN
    const taxAmount = subtotal * taxRate;
    const totalAmount = subtotal + taxAmount;
    
    document.getElementById('tax_amount').value = taxAmount.toFixed(2);
    document.getElementById('total_amount').value = totalAmount.toFixed(2);
}

// Auto-calculate when subtotal changes
document.getElementById('subtotal').addEventListener('input', calculateTax);
</script>
@endpush
