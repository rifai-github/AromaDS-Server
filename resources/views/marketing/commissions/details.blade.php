@extends('layouts.app')

@section('title', 'Commission Details')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">Commission Calculation Details</h1>
                    <p class="text-muted">View detailed commission calculation information</p>
                </div>
                <div>
                    <a href="{{ route('marketing.commissions.dashboard') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Commission Calculation Information</h6>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th width="200">Contract</th>
                            <td><strong>{{ $calculation->contract->contract_number ?? 'N/A' }}</strong></td>
                        </tr>
                        <tr>
                            <th>Customer</th>
                            <td>{{ $calculation->contract->customer->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Calculation Type</th>
                            <td>{{ ucfirst($calculation->calculation_type ?? 'N/A') }}</td>
                        </tr>
                        <tr>
                            <th>Base Amount</th>
                            <td><strong>Rp {{ number_format($calculation->base_amount ?? 0, 0, ',', '.') }}</strong></td>
                        </tr>
                        <tr>
                            <th>Commission Rate</th>
                            <td>{{ number_format($calculation->commission_rate ?? 0, 2) }}%</td>
                        </tr>
                        <tr>
                            <th>Commission Amount</th>
                            <td><strong>Rp {{ number_format($calculation->commission_amount ?? 0, 0, ',', '.') }}</strong></td>
                        </tr>
                        <tr>
                            <th>Final Amount</th>
                            <td><strong>Rp {{ number_format($calculation->final_amount ?? 0, 0, ',', '.') }}</strong></td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                @if($calculation->status == 'approved')
                                    <span class="badge badge-success">Approved</span>
                                @elseif($calculation->status == 'pending')
                                    <span class="badge badge-warning">Pending</span>
                                @else
                                    <span class="badge badge-danger">Void</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Calculation Date</th>
                            <td>{{ $calculation->calculation_date ? $calculation->calculation_date->format('d M Y') : '-' }}</td>
                        </tr>
                        @if($calculation->commissionLevel)
                        <tr>
                            <th>Commission Level</th>
                            <td>{{ $calculation->commissionLevel->name ?? 'N/A' }}</td>
                        </tr>
                        @endif
                        @if($calculation->marketingLevel)
                        <tr>
                            <th>Marketing Level</th>
                            <td>{{ $calculation->marketingLevel->level_name ?? 'N/A' }}</td>
                        </tr>
                        @endif
                        <tr>
                            <th>Created At</th>
                            <td>{{ $calculation->created_at->format('d M Y H:i') }}</td>
                        </tr>
                        <tr>
                            <th>Updated At</th>
                            <td>{{ $calculation->updated_at->format('d M Y H:i') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

