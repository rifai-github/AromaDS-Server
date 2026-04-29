@extends('layouts.app')

@section('title', 'Tax Invoice Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Tax Invoice Details</h3>
                    <div class="card-tools">
                        <a href="{{ route('tax-invoices.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                        <a href="{{ route('tax-invoices.edit', $taxInvoice) }}" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Invoice Information</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Invoice Number:</strong></td>
                                    <td>{{ $taxInvoice->invoice_number }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Customer:</strong></td>
                                    <td>{{ $taxInvoice->customer->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Contract:</strong></td>
                                    <td>{{ $taxInvoice->contract->contract_number ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Billing Group:</strong></td>
                                    <td>{{ $taxInvoice->billingGroup->billing_group_name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Invoice Date:</strong></td>
                                    <td>{{ $taxInvoice->invoice_date->format('d/m/Y') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Due Date:</strong></td>
                                    <td>{{ $taxInvoice->due_date->format('d/m/Y') }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>Financial Information</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Subtotal:</strong></td>
                                    <td class="text-right">Rp {{ number_format($taxInvoice->subtotal, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Tax Amount:</strong></td>
                                    <td class="text-right">Rp {{ number_format($taxInvoice->tax_amount, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Total Amount:</strong></td>
                                    <td class="text-right"><strong>Rp {{ number_format($taxInvoice->total_amount, 0, ',', '.') }}</strong></td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td>
                                        <span class="badge badge-{{ $taxInvoice->status == 'approved' ? 'success' : ($taxInvoice->status == 'pending' ? 'warning' : 'secondary') }}">
                                            {{ ucfirst($taxInvoice->status) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Tax Status:</strong></td>
                                    <td>
                                        <span class="badge badge-{{ $taxInvoice->tax_status == 'applied' ? 'success' : ($taxInvoice->tax_status == 'pending' ? 'warning' : 'info') }}">
                                            {{ ucfirst($taxInvoice->tax_status) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>e-Materai:</strong></td>
                                    <td>
                                        @if($taxInvoice->is_e_materai_applied)
                                            <span class="badge badge-success">
                                                <i class="fas fa-check"></i> Applied
                                            </span>
                                            @if($taxInvoice->e_materai_reference)
                                                <br><small>Reference: {{ $taxInvoice->e_materai_reference }}</small>
                                            @endif
                                        @else
                                            <span class="badge badge-secondary">
                                                <i class="fas fa-times"></i> Not Applied
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if($taxInvoice->tax_code || $taxInvoice->tax_notes)
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5>Tax Information</h5>
                            <table class="table table-borderless">
                                @if($taxInvoice->tax_code)
                                <tr>
                                    <td><strong>Tax Code:</strong></td>
                                    <td>{{ $taxInvoice->tax_code }}</td>
                                </tr>
                                @endif
                                @if($taxInvoice->tax_notes)
                                <tr>
                                    <td><strong>Tax Notes:</strong></td>
                                    <td>{{ $taxInvoice->tax_notes }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>
                    @endif

                    @if($taxInvoice->notes)
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5>Notes</h5>
                            <p>{{ $taxInvoice->notes }}</p>
                        </div>
                    </div>
                    @endif

                    <div class="row mt-4">
                        <div class="col-12">
                            <h5>Audit Information</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Created By:</strong></td>
                                    <td>{{ $taxInvoice->createdBy->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Created At:</strong></td>
                                    <td>{{ $taxInvoice->created_at->format('d/m/Y H:i:s') }}</td>
                                </tr>
                                @if($taxInvoice->updatedBy)
                                <tr>
                                    <td><strong>Updated By:</strong></td>
                                    <td>{{ $taxInvoice->updatedBy->name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Updated At:</strong></td>
                                    <td>{{ $taxInvoice->updated_at->format('d/m/Y H:i:s') }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>

                    @if(!$taxInvoice->is_e_materai_applied)
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Apply e-Materai</h5>
                                </div>
                                <div class="card-body">
                                    <form action="{{ route('tax-invoices.apply-e-materai', $taxInvoice) }}" method="POST">
                                        @csrf
                                        <div class="form-group">
                                            <label for="document_path">Document Path</label>
                                            <input type="text" class="form-control" id="document_path" name="document_path" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="notes">Notes</label>
                                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-stamp"></i> Apply e-Materai
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
