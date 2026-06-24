@extends('layouts.app')

@section('title', 'e-Materai Transaction Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">e-Materai Transaction Details</h3>
                    <div class="card-tools">
                        <a href="{{ route('e-materai-transactions.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                        <a href="{{ route('e-materai-transactions.edit', $eMateraiTransaction) }}" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Transaction Information</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Transaction ID:</strong></td>
                                    <td>{{ $eMateraiTransaction->transaction_id }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Tax Invoice:</strong></td>
                                    <td>{{ $eMateraiTransaction->taxInvoice->invoice_number ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Invoice:</strong></td>
                                    <td>{{ $eMateraiTransaction->invoice->invoice_number ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Peruri Reference:</strong></td>
                                    <td>{{ $eMateraiTransaction->peruri_reference_number ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td>
                                        <span class="badge badge-{{ $eMateraiTransaction->status == 'applied' ? 'success' : ($eMateraiTransaction->status == 'failed' ? 'danger' : ($eMateraiTransaction->status == 'pending' ? 'warning' : 'secondary')) }}">
                                            {{ ucfirst($eMateraiTransaction->status) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Applied At:</strong></td>
                                    <td>{{ $eMateraiTransaction->applied_at ? $eMateraiTransaction->applied_at->format('d/M/Y H:i:s') : 'N/A' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>Document Information</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Document Path:</strong></td>
                                    <td>
                                        @if($eMateraiTransaction->document_path)
                                            <a href="{{ $eMateraiTransaction->document_path }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-info">
                                                <i class="fas fa-file"></i> View Document
                                            </a>
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Response Data:</strong></td>
                                    <td>
                                        @if($eMateraiTransaction->response_data)
                                            <button class="btn btn-sm btn-secondary" onclick="showResponseData()">
                                                <i class="fas fa-eye"></i> View Response
                                            </button>
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if($eMateraiTransaction->notes)
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5>Notes</h5>
                            <p>{{ $eMateraiTransaction->notes }}</p>
                        </div>
                    </div>
                    @endif

                    <div class="row mt-4">
                        <div class="col-12">
                            <h5>Audit Information</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Created By:</strong></td>
                                    <td>{{ $eMateraiTransaction->createdBy->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Created At:</strong></td>
                                    <td>{{ $eMateraiTransaction->created_at->format('d/M/Y H:i:s') }}</td>
                                </tr>
                                @if($eMateraiTransaction->updatedBy)
                                <tr>
                                    <td><strong>Updated By:</strong></td>
                                    <td>{{ $eMateraiTransaction->updatedBy->name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Updated At:</strong></td>
                                    <td>{{ $eMateraiTransaction->updated_at->format('d/M/Y H:i:s') }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>

                    @if($eMateraiTransaction->status == 'failed')
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Retry Transaction</h5>
                                </div>
                                <div class="card-body">
                                    <p>This transaction failed. You can retry it by clicking the button below.</p>
                                    <form action="{{ route('e-materai-transactions.retry', $eMateraiTransaction) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-success" onclick="return submitRetryTransaction(event, this.form)">
                                            <i class="fas fa-redo"></i> Retry Transaction
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

<!-- Response Data Modal -->
<div class="modal fade" id="responseDataModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Data Response</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <pre id="responseDataContent">{{ json_encode($eMateraiTransaction->response_data, JSON_PRETTY_PRINT) }}</pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function showResponseData() {
    $('#responseDataModal').modal('show');
}

function submitRetryTransaction(event, form) {
    event.preventDefault();
    showConfirmDialog(
        'Ulangi Transaksi?',
        'Apakah Anda yakin ingin mengulangi transaksi ini?',
        'Ya, ulangi',
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
