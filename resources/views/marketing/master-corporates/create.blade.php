@extends('layouts.app')

@section('title', 'Create Master Corporate')

@section('content')
<style>
    /* Reuse styles from Index for consistency */
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

    /* Custom Table for Form */
    .table-custom {
        width: 100%;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #e2e8f0;
    }
    
    .table-custom thead th {
        background: #f1f5f9;
        padding: 12px 16px;
        font-size: 13px;
        font-weight: 600;
        color: #475569;
        text-transform: uppercase;
        border-bottom: 1px solid #e2e8f0;
    }

    .table-custom tbody td {
        padding: 12px 16px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
        background: white;
    }

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
        <div class="col-md-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title">
                        <i class="fas fa-plus-circle text-primary me-2"></i>
                        New Corporate Price
                    </h4>
                    <a href="{{ route('marketing.master-corporates.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
                
                <form id="createForm" action="{{ route('marketing.master-corporates.store') }}" method="POST">
                    @csrf
                    <div class="card-body p-4">
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label">Customer <span class="text-danger">*</span></label>
                                    <select class="form-control select2" id="customer_id" name="customer_id" required>
                                        <option value="">Search Customer...</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}">{{ $customer->name }} - {{ $customer->customer_code }}</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted mt-1 d-block">Select the corporate customer to apply special pricing to.</small>
                                </div>
                            </div>
                        </div>

                        <h5 class="mb-3" style="color: #475569; font-weight: 600;">Pricing Configuration</h5>
                        <div class="table-responsive" style="border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                            <table class="table-custom" id="rental-table">
                                <thead>
                                    <tr>
                                        <th style="width: 50%;">Rental Product <span class="text-danger">*</span></th>
                                        <th style="width: 40%;">Corporate Price (Rp) <span class="text-danger">*</span></th>
                                        <th style="width: 10%; text-align: center;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="rental-row">
                                        <td>
                                            <select class="form-control select2-rental" name="rentals[0][master_rental_id]" required>
                                                <option value="">Select Rental...</option>
                                                @foreach($rentals as $rental)
                                                    <option value="{{ $rental->id }}" data-price="{{ $rental->monthly_price }}">{{ $rental->rental_name }} ({{ $rental->rental_code }})</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <div class="input-group flex-nowrap" style="display: flex !important; align-items: stretch !important;">
                                                <span class="input-group-text bg-light border-end-0 d-flex align-items-center" style="white-space: nowrap;">Rp</span>
                                                <input type="number" class="form-control border-start-0 ps-0" name="rentals[0][price]" placeholder="0" min="0" required style="flex: 1 !important; width: auto !important;">
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-danger btn-sm remove-row" disabled style="opacity: 0.5; cursor: not-allowed;">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-3">
                            <button type="button" class="btn btn-primary btn-sm" id="add-row">
                                <i class="fas fa-plus"></i> Add Another Rental
                            </button>
                        </div>
                    </div>

                    <div class="card-footer bg-light p-4 text-end">
                        <button type="submit" class="btn btn-success me-2">
                            <i class="fas fa-save"></i> Save Corporate Price
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
        // Initialize main Select2
        $('.select2').select2({
            theme: "bootstrap",
            width: '100%',
            placeholder: "Select Customer"
        });

        // Initialize first row Select2
        initRowSelect2($('.select2-rental').first());

        let rowIdx = 0;

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
                Swal.fire({
                    icon: 'warning',
                    title: 'Warning',
                    text: 'You must have at least one rental item.'
                });
            }
        });

        // Auto-fill Price on Rental Selection
        $('#rental-table').on('select2:select', '.select2-rental', function (e) {
            const price = $(this).find(':selected').data('price');
            const row = $(this).closest('tr');
            if (price) {
                const formattedPrice = Math.floor(parseFloat(price));
                row.find('input[type="number"]').val(formattedPrice).removeClass('is-invalid');
            }
        });

        // Validation on Submit
        $('#createForm').on('submit', function(e) {
            e.preventDefault(); // Always prevent default first to control the flow
            
            const btn = $(this).find('button[type="submit"]');
            const originalBtnContent = btn.html();
            
            // Show loading state
            btn.prop('disabled', true);
            btn.html('<i class="fas fa-spinner fa-spin"></i> Processing...');

            let isValid = true;
            let firstError = null;

            // Reset validation states
            $('.form-control').removeClass('is-invalid');
            $('.select2-selection').css('border-color', '#d1d5db');

            // Validate Customer
            const customerSelect = $('#customer_id');
            if (!customerSelect.val()) {
                isValid = false;
                customerSelect.next('.select2-container').find('.select2-selection').css('border-color', '#dc2626');
                if (!firstError) firstError = customerSelect;
            }

            // Validate Rows
            $('.rental-row').each(function() {
                const row = $(this);
                const rentalSelect = row.find('.select2-rental');
                const priceInput = row.find('input[name*="[price]"]');

                if (!rentalSelect.val()) {
                    isValid = false;
                    rentalSelect.next('.select2-container').find('.select2-selection').css('border-color', '#dc2626');
                }

                if (!priceInput.val()) {
                    isValid = false;
                    priceInput.addClass('is-invalid');
                }
            });

            if (!isValid) {
                // Reset button if invalid
                btn.prop('disabled', false);
                btn.html(originalBtnContent);
                
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'Please fill in all required fields (marked with *) completely.'
                });
                
                // Scroll to error
                $('html, body').animate({
                    scrollTop: $(".card").offset().top
                }, 500);
            } else {
                // If valid, submit the form programmatically
                e.currentTarget.submit();
            }
        });

        // Reset validation on change
        $(document).on('change', '.form-control', function() {
            if ($(this).val()) {
                $(this).removeClass('is-invalid');
            }
        });

        $(document).on('change', 'select', function() {
            if ($(this).val()) {
                $(this).next('.select2-container').find('.select2-selection').css('border-color', '#d1d5db');
            }
        });

        function initRowSelect2(element) {
            element.select2({
                theme: "bootstrap",
                width: '100%',
                placeholder: "Select Rental"
            });
        }
    });
</script>
@endpush
