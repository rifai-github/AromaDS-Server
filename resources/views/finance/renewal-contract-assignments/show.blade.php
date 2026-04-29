@extends('layouts.app')

@section('title', 'Renewal Contract Assignment Details')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">Renewal Contract Assignment Details</h1>
                    <p class="text-muted">View renewal contract assignment information</p>
                </div>
                <div>
                    <a href="{{ route('finance.renewal-contract-assignments.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                    <a href="{{ route('finance.renewal-contract-assignments.edit', $renewalContractAssignment) }}" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Renewal Contract Assignment Information</h6>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th width="200">Marketing User</th>
                            <td><strong>{{ $renewalContractAssignment->user->name ?? 'N/A' }}</strong></td>
                        </tr>
                        <tr>
                            <th>Achievement Period</th>
                            <td>{{ $renewalContractAssignment->achievementPeriod->period_name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Contract Number From</th>
                            <td>{{ $renewalContractAssignment->contract_number_from ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Contract Number To</th>
                            <td>{{ $renewalContractAssignment->contract_number_to ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Target Amount</th>
                            <td>
                                @if($renewalContractAssignment->target_amount)
                                    <strong>Rp {{ number_format($renewalContractAssignment->target_amount, 0, ',', '.') }}</strong>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                @if($renewalContractAssignment->is_locked)
                                    <span class="badge badge-warning">Locked</span>
                                    @if($renewalContractAssignment->lock_date)
                                        <small class="text-muted">(Locked on {{ $renewalContractAssignment->lock_date->format('d M Y') }})</small>
                                    @endif
                                @else
                                    <span class="badge badge-success">Active</span>
                                @endif
                            </td>
                        </tr>
                        @if($renewalContractAssignment->notes)
                        <tr>
                            <th>Notes</th>
                            <td>{{ $renewalContractAssignment->notes }}</td>
                        </tr>
                        @endif
                        <tr>
                            <th>Created At</th>
                            <td>{{ $renewalContractAssignment->created_at->format('d M Y H:i') }}</td>
                        </tr>
                        <tr>
                            <th>Updated At</th>
                            <td>{{ $renewalContractAssignment->updated_at->format('d M Y H:i') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

