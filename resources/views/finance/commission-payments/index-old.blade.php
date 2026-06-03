@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Commission Payments</h3>
                    <div class="card-tools">
                        <a href="{{ route('finance.commission-payments.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create Payment
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Amount</th>
                                    <th>Payment Method</th>
                                    <th>Payment Date</th>
                                    <th>Status</th>
                                    <th>Reference</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($payments as $payment)
                                <tr>
                                    <td>{{ $payment->user->name ?? 'N/A' }}</td>
                                    <td>{{ $payment->formatted_amount }}</td>
                                    <td>{{ $payment->payment_method_label }}</td>
                                    <td>{{ $payment->payment_date->format('d/M/Y') }}</td>
                                    <td>
                                        <span class="badge {{ $payment->status_badge }}">
                                            {{ $payment->status_label }}
                                        </span>
                                    </td>
                                    <td>{{ $payment->payment_reference }}</td>
                                    <td>
                                        <a href="{{ route('finance.commission-payments.show', $payment) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('finance.commission-payments.edit', $payment) }}" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @if($payment->status === 'pending')
                                        <form action="{{ route('finance.commission-payments.mark-processing', $payment) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-primary">
                                                <i class="fas fa-play"></i> Process
                                            </button>
                                        </form>
                                        @endif
                                        @if($payment->status === 'processing')
                                        <form action="{{ route('finance.commission-payments.mark-completed', $payment) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">
                                                <i class="fas fa-check"></i> Complete
                                            </button>
                                        </form>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center">No commission payments found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
