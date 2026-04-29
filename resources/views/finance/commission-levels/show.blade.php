@extends('layouts.app')

@section('title', 'Commission Level Details')

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">Commission Level Details</h1>
                    <p class="text-muted">View commission level information</p>
                </div>
                <div>
                    <a href="{{ route('finance.commission-levels.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                    <a href="{{ route('finance.commission-levels.edit', $commissionLevel) }}" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Details Section -->
    <div class="row">
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Commission Level Information</h6>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th width="200">Level Name</th>
                            <td><strong>{{ $commissionLevel->name }}</strong></td>
                        </tr>
                        <tr>
                            <th>Target Type</th>
                            <td>
                                <span class="badge badge-info">
                                    {{ ucfirst($commissionLevel->target_type) }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Min Achievement %</th>
                            <td>{{ number_format($commissionLevel->min_percentage, 2) }}%</td>
                        </tr>
                        <tr>
                            <th>Max Achievement %</th>
                            <td>{{ $commissionLevel->max_percentage ? number_format($commissionLevel->max_percentage, 2) . '%' : '-' }}</td>
                        </tr>
                        <tr>
                            <th>Commission Rate</th>
                            <td><strong>{{ number_format($commissionLevel->commission_rate, 2) }}%</strong></td>
                        </tr>
                        <tr>
                            <th>Sort Order</th>
                            <td>{{ $commissionLevel->sort_order }}</td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                @if($commissionLevel->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-danger">Inactive</span>
                                @endif
                            </td>
                        </tr>
                        @if($commissionLevel->description)
                        <tr>
                            <th>Description</th>
                            <td>{{ $commissionLevel->description }}</td>
                        </tr>
                        @endif
                        <tr>
                            <th>Created At</th>
                            <td>{{ $commissionLevel->created_at->format('d M Y H:i') }}</td>
                        </tr>
                        <tr>
                            <th>Updated At</th>
                            <td>{{ $commissionLevel->updated_at->format('d M Y H:i') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Statistics</h6>
                </div>
                <div class="card-body">
                    <p><strong>Commission Calculations:</strong> {{ $commissionLevel->commissionCalculations->count() }}</p>
                    <p class="text-muted">This level is used in {{ $commissionLevel->commissionCalculations->count() }} commission calculation(s).</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

