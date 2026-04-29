@extends('layouts.app')

@section('title', 'Edit e-Materai Transaction')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Edit e-Materai Transaction</h3>
                    <div class="card-tools">
                        <a href="{{ route('e-materai-transactions.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                        <a href="{{ route('e-materai-transactions.show', $eMateraiTransaction) }}" class="btn btn-info">
                            <i class="fas fa-eye"></i> View
                        </a>
                    </div>
                </div>
                <form action="{{ route('e-materai-transactions.update', $eMateraiTransaction) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tax_invoice_id">Tax Invoice <span class="text-danger">*</span></label>
                                    <select class="form-control @error('tax_invoice_id') is-invalid @enderror" 
                                            id="tax_invoice_id" name="tax_invoice_id" required>
                                        <option value="">Select Tax Invoice</option>
                                        @foreach($taxInvoices as $invoice)
                                            <option value="{{ $invoice->id }}" {{ old('tax_invoice_id', $eMateraiTransaction->tax_invoice_id) == $invoice->id ? 'selected' : '' }}>
                                                {{ $invoice->invoice_number }} - {{ $invoice->customer->name ?? 'N/A' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('tax_invoice_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="invoice_id">Invoice</label>
                                    <select class="form-control @error('invoice_id') is-invalid @enderror" 
                                            id="invoice_id" name="invoice_id">
                                        <option value="">Select Invoice</option>
                                        <!-- This would be populated via AJAX based on tax_invoice_id -->
                                    </select>
                                    @error('invoice_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="document_path">Document Path <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('document_path') is-invalid @enderror" 
                                   id="document_path" name="document_path" value="{{ old('document_path', $eMateraiTransaction->document_path) }}" required>
                            <small class="form-text text-muted">Enter the path to the document that will receive the e-Materai stamp.</small>
                            @error('document_path')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="notes">Notes</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                      id="notes" name="notes" rows="3">{{ old('notes', $eMateraiTransaction->notes) }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update e-Materai Transaction
                        </button>
                        <a href="{{ route('e-materai-transactions.show', $eMateraiTransaction) }}" class="btn btn-secondary">
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
// Auto-populate invoice_id based on tax_invoice_id
document.getElementById('tax_invoice_id').addEventListener('change', function() {
    const taxInvoiceId = this.value;
    const invoiceSelect = document.getElementById('invoice_id');
    
    if (taxInvoiceId) {
        // This would typically be an AJAX call to get related invoices
        // For now, we'll just enable the field
        invoiceSelect.disabled = false;
    } else {
        invoiceSelect.disabled = true;
        invoiceSelect.value = '';
    }
});
</script>
@endpush
