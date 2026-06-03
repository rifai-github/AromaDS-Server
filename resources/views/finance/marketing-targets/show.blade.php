@extends('layouts.app')

@section('title', 'Marketing Target Details')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">Marketing Target Details</h1>
                    <p class="text-muted">View marketing target information</p>
                </div>
                <div>
                    <a href="{{ route('finance.marketing-targets.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                    <a href="{{ route('finance.marketing-targets.edit', $marketingTarget) }}" class="btn btn-warning">
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
                    <h6 class="m-0 font-weight-bold text-primary">Marketing Target Information</h6>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th width="200">Marketing User</th>
                            <td><strong>{{ $marketingTarget->user->name ?? 'N/A' }}</strong></td>
                        </tr>
                        <tr>
                            <th>Achievement Period</th>
                            <td>{{ $marketingTarget->achievementPeriod->period_name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Target Type</th>
                            <td>
                                <span class="badge badge-info">
                                    {{ ucfirst($marketingTarget->target_type) }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Target Amount</th>
                            <td><strong>Rp {{ number_format($marketingTarget->target_amount, 0, ',', '.') }}</strong></td>
                        </tr>
                        <tr>
                            <th>Achieved Amount</th>
                            <td>Rp {{ number_format($marketingTarget->achieved_amount, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <th>Progress</th>
                            <td>
                                @php
                                    $progress = $marketingTarget->target_amount > 0 ? ($marketingTarget->achieved_amount / $marketingTarget->target_amount) * 100 : 0;
                                @endphp
                                <div class="progress" style="height: 25px;">
                                    <div class="progress-bar" role="progressbar" style="width: {{ min($progress, 100) }}%">
                                        {{ number_format($progress, 1) }}%
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                @if($marketingTarget->is_locked)
                                    <span class="badge badge-warning">Locked</span>
                                    @if($marketingTarget->lock_date)
                                        <small class="text-muted">(Locked on {{ $marketingTarget->lock_date->format('d/M/Y') }})</small>
                                    @endif
                                @else
                                    <span class="badge badge-success">Active</span>
                                @endif
                            </td>
                        </tr>
                        @if($marketingTarget->notes)
                        <tr>
                            <th>Notes</th>
                            <td>{{ $marketingTarget->notes }}</td>
                        </tr>
                        @endif
                        <tr>
                            <th>Created At</th>
                            <td>{{ $marketingTarget->created_at->format('d/M/Y H:i') }}</td>
                        </tr>
                        <tr>
                            <th>Updated At</th>
                            <td>{{ $marketingTarget->updated_at->format('d/M/Y H:i') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

