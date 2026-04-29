@extends('layouts.app')

@section('title', 'Marketing Level Details')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">Marketing Level Details</h1>
                    <p class="text-muted">View marketing level information</p>
                </div>
                <div>
                    <a href="{{ route('finance.marketing-levels.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                    <a href="{{ route('finance.marketing-levels.edit', $marketingLevel) }}" class="btn btn-warning">
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
                    <h6 class="m-0 font-weight-bold text-primary">Marketing Level Information</h6>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th width="200">Level Code</th>
                            <td><strong>{{ $marketingLevel->level_code }}</strong></td>
                        </tr>
                        <tr>
                            <th>Level Name</th>
                            <td>{{ $marketingLevel->level_name }}</td>
                        </tr>
                        <tr>
                            <th>Sort Order</th>
                            <td>{{ $marketingLevel->sort_order }}</td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                @if($marketingLevel->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-danger">Inactive</span>
                                @endif
                            </td>
                        </tr>
                        @if($marketingLevel->description)
                        <tr>
                            <th>Description</th>
                            <td>{{ $marketingLevel->description }}</td>
                        </tr>
                        @endif
                        <tr>
                            <th>Created At</th>
                            <td>{{ $marketingLevel->created_at->format('d M Y H:i') }}</td>
                        </tr>
                        <tr>
                            <th>Updated At</th>
                            <td>{{ $marketingLevel->updated_at->format('d M Y H:i') }}</td>
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
                    <p><strong>Users Assigned:</strong> {{ $marketingLevel->users->count() }}</p>
                    <p class="text-muted">This level is assigned to {{ $marketingLevel->users->count() }} user(s).</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

