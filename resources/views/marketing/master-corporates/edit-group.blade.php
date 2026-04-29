@extends('layouts.app')

@section('title', 'Edit Master Corporate: ' . $code)

@section('content')
<style>
    .form-group { margin-bottom: 20px; }
    .form-label { font-weight: 600; color: #334155; margin-bottom: 8px; display: block; }
    .form-control { border-radius: 6px; border: 1px solid #d1d5db; padding: 10px 12px; font-size: 14px; transition: all 0.2s ease; }
    .form-control:focus { border-color: #214589; box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1); }
    .card { border-radius: 8px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    .card-header { background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 16px 20px; }
    .card-title { font-size: 18px; font-weight: 600; color: #1e293b; margin: 0; }
    .btn { padding: 8px 16px; border-radius: 6px; font-weight: 500; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; }
    .btn-success { background: #16a34a; border: none; color: white; }
    .btn-success:hover { background: #15803d; }
    .btn-danger { background: #dc2626; border: none; color: white; }
    .btn-danger:hover { background: #b91c1c; }
    .btn-secondary { background: #f3f4f6; color: #4b5563; border: 1px solid #d1d5db; }
    .btn-secondary:hover { background: #e5e7eb; }
    .table-custom { width: 100%; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0; }
    .table-custom thead th { background: #f1f5f9; padding: 12px 16px; font-size: 13px; font-weight: 600; color: #475569; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
    .table-custom tbody td { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; background: white; }
    .select2-container .select2-selection--single { height: 42px !important; border: 1px solid #d1d5db !important; border-radius: 6px !important; display: flex !important; align-items: center !important; }
    .select2-container--bootstrap .select2-selection--single .select2-selection__rendered { line-height: 42px !important; padding-left: 12px !important; color: #334155 !important; }
    .select2-container--bootstrap .select2-selection--single .select2-selection__arrow { height: 40px !important; position: absolute !important; top: 1px !important; right: 1px !important; }
</style>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title">Edit Master Corporate</h5>
                    <div class="text-muted small">Code: {{ $code }}</div>
                </div>
                
                <form action="{{ route('marketing.master-corporates.update-group', $code) }}" method="POST" id="corporateForm">
                    @csrf
                    @method('PUT')
                    
                    <div class="card-body p-4">
                        <!-- Customer Selection (Read Only) -->
                        <div class="form-group">
                            <label class="form-label">Customer</label>
                            <input type="text" class="form-control" value="{{ $customer->name }} ({{ $customer->code }})" readonly style="background-color: #f8fafc;">
                        </div>

                        <!-- Rental Items Table -->
                        <div class="form-group mb-0">
                            <label class="form-label mb-3">Rental Items</label>
                            <div class="table-responsive">
                                <table class="table-custom" id="rental-table">
                                    <thead>
                                        <tr>
                                            <th width="50%">Rental Unit</th>
                                            <th width="40%">Special Price</th>
                                            <th width="10%">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($items as $index => $item)
                                        <tr class="rental-row">
                                            <td>
                                                <input type="hidden" name="rentals[{{ $index }}][id]" value="{{ $item->id }}">
                                                <select class="form-control select2-rental" name="rentals[{{ $index }}][master_rental_id]" required>
                                                    <option value="">Select Rental...</option>
                                                    @foreach($rentals as $rental)
                                                        <option value="{{ $rental->id }}" data-price="{{ $rental->monthly_price }}" {{ $item->master_rental_id == $rental->id ? 'selected' : '' }}>
                                                            {{ $rental->rental_name }} ({{ $rental->rental_code }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <div class="input-group flex-nowrap" style="display: flex !important; align-items: stretch !important;">
                                                    <span class="input-group-text bg-light border-end-0 d-flex align-items-center" style="white-space: nowrap;">Rp</span>
                                                    <input type="number" class="form-control border-start-0 ps-0" name="rentals[{{ $index }}][price]" 
                                                           value="{{ $item->price }}" placeholder="0" min="0" required style="flex: 1 !important; width: auto !important;">
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-danger btn-sm remove-row">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="mt-3">
                                <button type="button" class="btn btn-primary btn-sm" id="add-row">
                                    <i class="fas fa-plus"></i> Add Another Rental
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer bg-light p-4 text-end">
                        <button type="submit" class="btn btn-success me-2">
                            <i class="fas fa-save"></i> Update Changes
                        </button>
                        <a href="{{ route('marketing.master-corporates.show', $code) }}" class="btn btn-secondary">
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
        // Initialize existing rows
        $('.select2-rental').each(function() {
            initRowSelect2($(this));
        });

        let rowIdx = {{ count($items) }};

        // Add Row Function
        $('#add-row').on('click', function () {
            rowIdx++;
            const markup = `
                <tr class="rental-row">
                    <td>
                        <select class="form-control select2-rental" name="rentals[${rowIdx}][master_rental_id]" required>
                            <option value="">Select Rental...</option>
                            @foreach($rentals as $rental)
                                <option value="{{ $rental->id }}" data-price="{{ $rental->monthly_price }}">{{ $rental->rental_name }} ({{ $rental->rental_code }})</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <div class="input-group flex-nowrap" style="display: flex !important; align-items: stretch !important;">
                            <span class="input-group-text bg-light border-end-0 d-flex align-items-center" style="white-space: nowrap;">Rp</span>
                            <input type="number" class="form-control border-start-0 ps-0" name="rentals[${rowIdx}][price]" placeholder="0" min="0" required style="flex: 1 !important; width: auto !important;">
                        </div>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-danger btn-sm remove-row">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            
            $('#rental-table tbody').append(markup);
            initRowSelect2($('#rental-table tbody tr:last .select2-rental'));
        });

        // Remove Row Function
        $('#rental-table').on('click', '.remove-row', function () {
            if ($('.rental-row').length > 1) {
                $(this).closest('tr').remove();
            } else {
                Swal.fire('Warning', 'You must have at least one rental item.', 'warning');
            }
        });

        // Helper: Initialize Select2 for a row
        function initRowSelect2(element) {
            element.select2({
                theme: "bootstrap",
                width: '100%',
                placeholder: "Select Rental",
                dropdownParent: element.parent()
            }).on('select2:select', function (e) {
                const price = $(this).find(':selected').data('price');
                const row = $(this).closest('tr');
                if(price) {
                     // Optionally auto-fill price? User might not want this if editing.
                     // In create mode we auto-filled. In edit mode, if they CHANGE the rental, maybe auto-fill default price?
                     // Yes, if rental changes, update price.
                     row.find('input[type="number"]').val(Math.floor(price));
                }
            });
        }
    });
</script>
@endpush
