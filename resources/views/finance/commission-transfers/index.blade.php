@extends('layouts.app')

@section('title', 'Commission Transfers')
@section('breadcrumb', 'Home / Finance / Commission Transfers')

@section('content')
<style>
    html, body { overflow-x: hidden; max-width: 100vw; }
    .btn { padding: 8px 16px; border-radius: 6px; font-size: 14px; font-weight: 500; cursor: pointer; transition: all 0.2s ease; border: none; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
    .btn-primary { background-color: #214589; color: white; }
    .btn-secondary { background-color: #f3f4f6; color: #6b7280; border: 1px solid #d1d5db; }
    .btn-success { background-color: #10b981; color: white; }
    .btn-warning { background-color: #f59e0b; color: white; }
    .btn-danger { background-color: #ef4444; color: white; }
    .btn-info { background-color: #3b82f6; color: white; }
    .btn-sm { padding: 6px 12px; font-size: 12px; }
    .page-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px 0; margin-bottom: 30px; }
    .page-title { font-size: 28px; font-weight: 700; margin: 0; }
    .page-subtitle { font-size: 16px; opacity: 0.9; margin: 8px 0 0 0; }
    .table-container { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 1px solid #e5e7eb; }
    .table-header { background: #f8fafc; padding: 20px 24px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; }
    .table-title { font-size: 18px; font-weight: 600; color: #1f2937; margin: 0; }
    .table-wrapper { overflow-x: auto; }
    .data-table { width: 100%; border-collapse: collapse; font-size: 14px; }
    .data-table th { background: #f1f5f9; color: #475569; font-weight: 600; padding: 12px 16px; text-align: left; border-bottom: 2px solid #e2e8f0; }
    .data-table td { padding: 12px 16px; border-bottom: 1px solid #e5e7eb; color: #1f2937; }
    .data-table tbody tr:hover { background-color: #f8fafc; }
    .badge { padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 500; }
    .badge-success { background-color: #dcfce7; color: #16a34a; }
    .badge-danger { background-color: #fee2e2; color: #dc2626; }
    .badge-warning { background-color: #fef3c7; color: #d97706; }
    .badge-info { background-color: #dbeafe; color: #2563eb; }
    .action-buttons { display: flex; gap: 8px; }
</style>

<div class="container-fluid">
    <div class="page-header">
        <div class="container-fluid">
            <h1 class="page-title"><i class="fas fa-exchange-alt"></i> Commission Transfers</h1>
            <p class="page-subtitle">Manage commission transfer requests</p>
        </div>
    </div>

    <div class="container-fluid">
        <div class="mb-4">
            <a href="{{ route('finance.commission-transfers.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Request Transfer
            </a>
        </div>

        <div class="table-container">
            <div class="table-header">
                <h3 class="table-title">Commission Transfers List</h3>
            </div>
            <div class="table-wrapper">
                <table class="data-table" id="transfersTable">
                    <thead>
                        <tr>
                            <th>Contract</th>
                            <th>From User</th>
                            <th>To User</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Created At</th>
                            <th>Last Updated By</th>
                            <th>Last Updated At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transfers as $transfer)
                        <tr>
                            <td>{{ $transfer->contract->contract_number ?? 'N/A' }}</td>
                            <td>{{ $transfer->fromUser->name ?? 'N/A' }}</td>
                            <td><strong>{{ $transfer->toUser->name ?? 'N/A' }}</strong></td>
                            <td><strong>Rp {{ number_format($transfer->commission_amount, 0, ',', '.') }}</strong></td>
                            <td>
                                @if($transfer->status == 'approved')
                                    <span class="badge badge-success">Approved</span>
                                @elseif($transfer->status == 'rejected')
                                    <span class="badge badge-danger">Rejected</span>
                                @else
                                    <span class="badge badge-warning">Pending</span>
                                @endif
                            </td>
                            <td>{{ $transfer->createdBy->name ?? '-' }}</td>
                            <td>{!! $transfer->created_at ? $transfer->created_at->format('d/M/Y') . '<br>at ' . $transfer->created_at->format('H.i') . ' WIB' : '-' !!}</td>
                            <td>{{ $transfer->updatedBy->name ?? '-' }}</td>
                            <td>{!! $transfer->updated_at ? $transfer->updated_at->format('d/M/Y') . '<br>at ' . $transfer->updated_at->format('H.i') . ' WIB' : '-' !!}</td>
                            <td>
                                <div class="action-buttons">
                                    <a href="{{ route('finance.commission-transfers.show', $transfer) }}" class="btn btn-info btn-sm">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if($transfer->status == 'pending')
                                    <form action="{{ route('finance.commission-transfers.approve', $transfer) }}" method="POST" style="display: inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm" title="Approve">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('finance.commission-transfers.reject', $transfer) }}" method="POST" style="display: inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-danger btn-sm" title="Reject">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="11" style="text-align: center; padding: 40px;">
                                <p>No commission transfers found. <a href="{{ route('finance.commission-transfers.create') }}">Create one</a></p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($transfers->hasPages())
        <div class="mt-4">
            {{ $transfers->links() }}
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    $('#transfersTable').DataTable({
        order: [[5, 'desc']],
        pageLength: 25,
        responsive: true
    });
});
</script>
@endpush
@endsection

