@extends('layouts.app')

@section('title', 'Customer Category Details')
@section('breadcrumb', 'Home / Marketing / Master Customer Category / Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="card-title">
                            <i class="fas fa-info-circle mr-2"></i>
                            Category Details: {{ $customerType->name }}
                        </h3>
                        <div class="card-tools">
                            <a href="{{ route('system.customer-types.edit', $customerType) }}" class="btn btn-warning mr-2">
                                <i class="fas fa-edit mr-1"></i>
                                Edit
                            </a>
                            <a href="{{ route('system.customer-types.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-1"></i>
                                Back to List
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-box">
                                <div class="info-box-content">
                                    <span class="info-box-text">ID</span>
                                    <span class="info-box-number">{{ $customerType->id }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-box">
                                <div class="info-box-content">
                                    <span class="info-box-text">Status</span>
                                    <span class="info-box-number">
                                        @if($customerType->is_active)
                                            <span class="badge badge-success">Aktif</span>
                                        @else
                                            <span class="badge badge-secondary">Tidak Aktif</span>
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Category Information</h5>
                                </div>
                                <div class="card-body">
                                    <dl class="row">
                                        <dt class="col-sm-3">Name:</dt>
                                        <dd class="col-sm-9">
                                            <strong>{{ $customerType->name }}</strong>
                                        </dd>
                                        
                                        <dt class="col-sm-3">Description:</dt>
                                        <dd class="col-sm-9">
                                            {{ $customerType->description ?? 'No description provided' }}
                                        </dd>
                                        
                                        <dt class="col-sm-3">Created At:</dt>
                                        <dd class="col-sm-9">
                                            {{ $customerType->created_at->format('d/M/Y H:i:s') }}
                                        </dd>
                                        
                                        <dt class="col-sm-3">Updated At:</dt>
                                        <dd class="col-sm-9">
                                            {{ $customerType->updated_at->format('d/M/Y H:i:s') }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between">
                        <form action="{{ route('system.customer-types.destroy', $customerType) }}" 
                              method="POST" 
                              onsubmit="return confirm('Apakah kamu yakin ingin menghapus tipe customer ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash mr-1"></i>
                                Hapus Kategori
                            </button>
                        </form>
                        <div>
                            <a href="{{ route('system.customer-types.edit', $customerType) }}" class="btn btn-warning">
                                <i class="fas fa-edit mr-1"></i>
                                Edit
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
