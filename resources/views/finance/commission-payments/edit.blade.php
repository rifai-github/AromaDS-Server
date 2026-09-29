@extends('layouts.app')

@section('title', 'Edit Commission Payment')

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">Edit Commission Payment</h1>
                    <p class="text-muted">Payment #{{ $commissionPayment->id }}</p>
                </div>
                <div>
                    <a href="{{ route('finance.commission-payments.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Section -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Commission Payment Information</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('finance.commission-payments.update', $commissionPayment) }}">
                        @csrf
                        @method('PUT')

                        @include('finance.commission-payments._form')

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Payment
                            </button>
                            <a href="{{ route('finance.commission-payments.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
