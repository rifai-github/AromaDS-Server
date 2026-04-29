@extends('layouts.app')

@section('title', 'Commission Transfer Details')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">Commission Transfer Details</h1>
                    <p class="text-muted">View commission transfer information</p>
                </div>
                <div>
                    <a href="{{ route('finance.commission-transfers.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                    @if($commissionTransfer->status == 'pending')
                    <form action="{{ route('finance.commission-transfers.approve', $commissionTransfer) }}" method="POST" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check"></i> Approve
                        </button>
                    </form>
                    <form action="{{ route('finance.commission-transfers.reject', $commissionTransfer) }}" method="POST" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-times"></i> Reject
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Commission Transfer Information</h6>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th width="200">Contract</th>
                            <td><strong>{{ $commissionTransfer->contract->contract_number ?? 'N/A' }}</strong></td>
                        </tr>
                        <tr>
                            <th>From User</th>
                            <td>{{ $commissionTransfer->fromUser->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>To User</th>
                            <td><strong>{{ $commissionTransfer->toUser->name ?? 'N/A' }}</strong></td>
                        </tr>
                        <tr>
                            <th>Commission Amount</th>
                            <td><strong>Rp {{ number_format($commissionTransfer->commission_amount, 0, ',', '.') }}</strong></td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                @if($commissionTransfer->status == 'approved')
                                    <span class="badge badge-success">Approved</span>
                                @elseif($commissionTransfer->status == 'rejected')
                                    <span class="badge badge-danger">Rejected</span>
                                @else
                                    <span class="badge badge-warning">Pending</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Reason</th>
                            <td>{{ $commissionTransfer->reason }}</td>
                        </tr>
                        @if($commissionTransfer->approved_by)
                        <tr>
                            <th>Approved By</th>
                            <td>{{ $commissionTransfer->approvedBy->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Approved At</th>
                            <td>{{ $commissionTransfer->approved_at ? $commissionTransfer->approved_at->format('d M Y H:i') : '-' }}</td>
                        </tr>
                        @endif
                        <tr>
                            <th>Created At</th>
                            <td>{{ $commissionTransfer->created_at->format('d M Y H:i') }}</td>
                        </tr>
                        <tr>
                            <th>Updated At</th>
                            <td>{{ $commissionTransfer->updated_at->format('d M Y H:i') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

