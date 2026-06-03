@extends('layouts.app')

@section('title', 'CR Variable Details')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">CR Variable Details</h1>
                    <p class="text-muted">View CR variable information</p>
                </div>
                <div>
                    <a href="{{ route('finance.cr-variables.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                    <a href="{{ route('finance.cr-variables.edit', $crVariable) }}" class="btn btn-warning">
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
                    <h6 class="m-0 font-weight-bold text-primary">CR Variable Information</h6>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th width="200">Variable Name</th>
                            <td><strong>{{ $crVariable->name }}</strong></td>
                        </tr>
                        <tr>
                            <th>CR Days</th>
                            <td>{{ $crVariable->cr_days }} days</td>
                        </tr>
                        <tr>
                            <th>Is Default</th>
                            <td>
                                @if($crVariable->is_default)
                                    <span class="badge badge-warning">Default</span>
                                @else
                                    <span class="badge badge-secondary">No</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                @if($crVariable->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-danger">Inactive</span>
                                @endif
                            </td>
                        </tr>
                        @if($crVariable->description)
                        <tr>
                            <th>Description</th>
                            <td>{{ $crVariable->description }}</td>
                        </tr>
                        @endif
                        <tr>
                            <th>Created At</th>
                            <td>{{ $crVariable->created_at->format('d/M/Y H:i') }}</td>
                        </tr>
                        <tr>
                            <th>Updated At</th>
                            <td>{{ $crVariable->updated_at->format('d/M/Y H:i') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

