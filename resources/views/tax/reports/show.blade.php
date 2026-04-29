@extends('layouts.app')

@section('title', 'Tax Report Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Tax Report Details</h3>
                    <div class="card-tools">
                        <a href="{{ route('tax-reports.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                        <a href="{{ route('tax-reports.edit', $taxReport) }}" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Report Information</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Report Number:</strong></td>
                                    <td>{{ $taxReport->report_number }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Report Name:</strong></td>
                                    <td>{{ $taxReport->report_name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Report Type:</strong></td>
                                    <td>
                                        <span class="badge badge-info">{{ ucfirst($taxReport->report_type) }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Period Start:</strong></td>
                                    <td>{{ $taxReport->period_start->format('d/m/Y') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Period End:</strong></td>
                                    <td>{{ $taxReport->period_end->format('d/m/Y') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td>
                                        <span class="badge badge-{{ $taxReport->status == 'generated' ? 'success' : ($taxReport->status == 'submitted' ? 'info' : 'warning') }}">
                                            {{ ucfirst($taxReport->status) }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>Tax Summary</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Total PPN:</strong></td>
                                    <td class="text-right">Rp {{ number_format($taxReport->total_ppn, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Total PPH:</strong></td>
                                    <td class="text-right">Rp {{ number_format($taxReport->total_pph, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Total Tax:</strong></td>
                                    <td class="text-right"><strong>Rp {{ number_format($taxReport->total_tax, 0, ',', '.') }}</strong></td>
                                </tr>
                                <tr>
                                    <td><strong>Total Invoices:</strong></td>
                                    <td class="text-right">{{ number_format($taxReport->total_invoices) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>e-SPT Status:</strong></td>
                                    <td>
                                        @if($taxReport->e_spt_file_path)
                                            <span class="badge badge-success">
                                                <i class="fas fa-check"></i> Generated
                                            </span>
                                            @if($taxReport->e_spt_reference)
                                                <br><small>Reference: {{ $taxReport->e_spt_reference }}</small>
                                            @endif
                                        @else
                                            <span class="badge badge-secondary">
                                                <i class="fas fa-times"></i> Not Generated
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if($taxReport->notes)
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5>Notes</h5>
                            <p>{{ $taxReport->notes }}</p>
                        </div>
                    </div>
                    @endif

                    @if($taxReport->report_data)
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5>Report Data</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Invoice Number</th>
                                            <th>Customer</th>
                                            <th>Date</th>
                                            <th>Subtotal</th>
                                            <th>Tax Amount</th>
                                            <th>Total</th>
                                            <th>Tax Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if(isset($taxReport->report_data['invoices']))
                                            @foreach($taxReport->report_data['invoices'] as $invoice)
                                                <tr>
                                                    <td>{{ $invoice['invoice_number'] }}</td>
                                                    <td>{{ $invoice['customer_name'] }}</td>
                                                    <td>{{ $invoice['invoice_date'] }}</td>
                                                    <td class="text-right">{{ number_format($invoice['subtotal'], 0, ',', '.') }}</td>
                                                    <td class="text-right">{{ number_format($invoice['tax_amount'], 0, ',', '.') }}</td>
                                                    <td class="text-right">{{ number_format($invoice['total_amount'], 0, ',', '.') }}</td>
                                                    <td>
                                                        <span class="badge badge-{{ $invoice['tax_status'] == 'applied' ? 'success' : 'info' }}">
                                                            {{ ucfirst($invoice['tax_status']) }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="7" class="text-center">No invoice data available</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="row mt-4">
                        <div class="col-12">
                            <h5>Audit Information</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Created By:</strong></td>
                                    <td>{{ $taxReport->createdBy->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Created At:</strong></td>
                                    <td>{{ $taxReport->created_at->format('d/m/Y H:i:s') }}</td>
                                </tr>
                                @if($taxReport->updatedBy)
                                <tr>
                                    <td><strong>Updated By:</strong></td>
                                    <td>{{ $taxReport->updatedBy->name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Updated At:</strong></td>
                                    <td>{{ $taxReport->updated_at->format('d/m/Y H:i:s') }}</td>
                                </tr>
                                @endif
                                @if($taxReport->e_spt_submitted_at)
                                <tr>
                                    <td><strong>e-SPT Submitted At:</strong></td>
                                    <td>{{ $taxReport->e_spt_submitted_at->format('d/m/Y H:i:s') }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>

                    @if($taxReport->status == 'draft')
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Generate Report</h5>
                                </div>
                                <div class="card-body">
                                    <p>Click the button below to generate the tax report data.</p>
                                    <form action="{{ route('tax-reports.generate', $taxReport) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-success" onclick="return submitGenerateReport(event, this.form)">
                                            <i class="fas fa-cogs"></i> Generate Report
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($taxReport->status == 'generated' && !$taxReport->e_spt_file_path)
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Export to e-SPT</h5>
                                </div>
                                <div class="card-body">
                                    <p>Export this report to e-SPT format for government submission.</p>
                                    <button class="btn btn-primary" onclick="exportESPT()">
                                        <i class="fas fa-download"></i> Export to e-SPT
                                    </button>
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

@push('scripts')
<script>
function exportESPT() {
    showConfirmDialog(
        'Export ke e-SPT?',
        'Apakah Anda yakin ingin export laporan ini ke format e-SPT?',
        'Ya, export',
        'Batal'
    ).then((confirmed) => {
        if (confirmed) {
            window.location.href = `/tax-reports/{{ $taxReport->id }}/export-espt`;
        }
    });
}

function submitGenerateReport(event, form) {
    event.preventDefault();
    showConfirmDialog(
        'Generate Report?',
        'Apakah Anda yakin ingin generate laporan ini?',
        'Ya, generate',
        'Batal'
    ).then((confirmed) => {
        if (confirmed) {
            form.submit();
        }
    });
    return false;
}
</script>
@endpush
