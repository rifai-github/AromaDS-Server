@extends('layouts.app')

@section('title', 'Edit Master Corporate')

@section('content')
<style>
    /* Reuse consistent styles */
    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        font-weight: 600;
        color: #334155;
        margin-bottom: 8px;
        display: block;
    }

    .form-control {
        border-radius: 6px;
        border: 1px solid #d1d5db;
        padding: 10px 12px;
        font-size: 14px;
        transition: all 0.2s ease;
    }

    .form-control:focus {
        border-color: #214589;
        box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
    }

    .card {
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .card-header {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 16px 20px;
    }

    .card-title {
        font-size: 18px;
        font-weight: 600;
        color: #1e293b;
        margin: 0;
    }

    .btn {
        padding: 8px 16px;
        border-radius: 6px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
    }

    .btn-success { background: #16a34a; border: none; color: white; }
    .btn-success:hover { background: #15803d; }
    
    .btn-danger { background: #dc2626; border: none; color: white; }
    .btn-danger:hover { background: #b91c1c; }

    .btn-secondary { background: #f3f4f6; color: #4b5563; border: 1px solid #d1d5db; }
    .btn-secondary:hover { background: #e5e7eb; }

    .select2-container .select2-selection--single {
        height: 42px !important;
        border: 1px solid #d1d5db !important;
        border-radius: 6px !important;
        display: flex !important;
        align-items: center !important;
    }

    .select2-container--bootstrap .select2-selection--single .select2-selection__rendered {
        padding-left: 12px !important;
        color: #334155 !important;
        font-size: 14px !important;
    }
</style>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title">
                        <i class="fas fa-edit text-info me-2"></i>
                        Edit Corporate Price
                    </h4>
                    <span class="badge bg-light text-dark border">{{ $masterCorporate->code }}</span>
                </div>
                
                <form action="{{ route('marketing.master-corporates.update', $masterCorporate->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="card-body p-4">
                        <div class="form-group">
                            <label class="form-label">Customer <span class="text-danger">*</span></label>
                            <select class="form-control select2" id="customer_id" name="customer_id" required>
                                <option value="">Select Customer</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}" {{ $masterCorporate->customer_id == $customer->id ? 'selected' : '' }}>
                                        {{ $customer->name }} - {{ $customer->customer_code }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Rental Product <span class="text-danger">*</span></label>
                            <select class="form-control select2" id="master_rental_id" name="master_rental_id" required>
                                <option value="">Select Rental</option>
                                @foreach($rentals as $rental)
                                    <option value="{{ $rental->id }}" {{ $masterCorporate->master_rental_id == $rental->id ? 'selected' : '' }}>
                                        {{ $rental->rental_name }} ({{ $rental->rental_code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Corporate Price <span class="text-danger">*</span></label>
                            <div class="input-group flex-nowrap">
                                <span class="input-group-text bg-light border-end-0" style="border-color: #d1d5db;">Rp</span>
                                <input type="number" class="form-control border-start-0 ps-0" id="price" name="price" value="{{ round($masterCorporate->price) }}" required min="0">
                            </div>
                            <small class="text-muted mt-1 d-block">Set the special fixed price for this customer and product.</small>
                        </div>
                    </div>

                    <div class="card-footer bg-light p-4 text-end">
                        <button type="submit" class="btn btn-success me-2">
                            <i class="fas fa-save"></i> Update Price
                        </button>
                        <a href="{{ route('marketing.master-corporates.index') }}" class="btn btn-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('.select2').select2({
            theme: "bootstrap",
            width: '100%'
        });
    });
</script>
@endpush
