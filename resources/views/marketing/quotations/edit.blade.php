@extends('layouts.app')

@section('title', 'Edit Quotation')

@section('content')

<!-- Force CSS untuk layout -->
<style>
    .quotation-layout-fix {
        display: flex !important;
        flex-wrap: wrap !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .quotation-layout-fix .col-lg-6 {
        flex: 0 0 50% !important;
        max-width: 50% !important;
        padding: 15px !important;
        display: block !important;
    }
    .quotation-card {
        height: 100% !important;
        border: 1px solid #dee2e6 !important;
        border-radius: 8px !important;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
    }
    .quotation-card-header {
        background-color: #6c757d !important;
        color: white !important;
        padding: 1rem 1.5rem !important;
        border-bottom: 1px solid rgba(0, 0, 0, 0.125) !important;
        border-radius: 8px 8px 0 0 !important;
    }
    .quotation-card-body {
        padding: 1.5rem !important;
    }
    .quotation-field {
        margin-bottom: 1rem !important;
        display: flex !important;
        align-items: center !important;
    }
    .quotation-field-label {
        flex: 0 0 40% !important;
        font-weight: bold !important;
        color: #495057 !important;
    }
    .quotation-field-value {
        flex: 0 0 60% !important;
        color: #6c757d !important;
    }
    
    /* Tab Content Fix */
    .tab-content {
        width: 100% !important;
        min-height: 500px !important;
    }
    
    .tab-pane {
        width: 100% !important;
        min-height: 500px !important;
        display: none !important;
    }
    
    .tab-pane.show.active {
        display: block !important;
    }
    
    #quotation-detail {
        width: 100% !important;
        min-height: 500px !important;
        display: none !important;
    }
    
    #quotation-detail.show.active {
        display: block !important;
    }
    @media (max-width: 991.98px) {
        .quotation-layout-fix .col-lg-6 {
            flex: 0 0 100% !important;
            max-width: 100% !important;
        }
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header - LAYOUT BARU -->
            <div class="card mb-3" style="background-color: #1e3a8a; color: white; border: none; border-radius: 10px;">
                <div class="card-header" style="background-color: #1e3a8a; border: none; padding: 1rem 1.5rem;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <a href="{{ route('marketing.quotations.index') }}" class="btn btn-light btn-sm">
                                <i class="fas fa-arrow-left"></i> Back to List
                            </a>
                        </div>
                        <div>
                            <h3 class="card-title mb-0" style="color: white; font-size: 1.5rem; font-weight: bold;">
                                 Edit Quotation: {{ $quotation->quotation_number }}
                            </h3>
                        </div>
                        <div>
                            <a href="{{ route('marketing.quotations.show', $quotation) }}" class="btn btn-warning btn-sm me-2">
                                <i class="fas fa-eye"></i> VIEW
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            @if($quotation->status !== 'draft')
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Warning:</strong> This quotation is in "{{ ucfirst($quotation->status) }}" status. 
                    Only draft quotations can be fully edited. You can only update basic information.
                </div>
            @endif

            <!-- Navigation Tabs - HORIZONTAL LAYOUT -->
            <div class="card mb-3">
                <div class="card-body p-0">
                    <ul class="nav nav-tabs" id="quotationTabs" role="tablist" style="border-bottom: 2px solid #1e3a8a; margin: 0; display: flex; flex-direction: row;">
                        <li class="nav-item" role="presentation" style="flex: 1;">
                            <button class="nav-link active" id="basic-info-tab" data-bs-toggle="tab" data-bs-target="#basic-info" type="button" role="tab" aria-controls="basic-info" aria-selected="true" style="border-bottom: 3px solid #1e3a8a; color: #1e3a8a; font-weight: bold; padding: 12px 20px; width: 100%; text-align: center;">
                                <i class="fas fa-info-circle me-2"></i>BASIC INFO
                            </button>
                        </li>
                        <li class="nav-item" role="presentation" style="flex: 1;">
                            <button class="nav-link" id="surveys-tab" data-bs-toggle="tab" data-bs-target="#surveys" type="button" role="tab" aria-controls="surveys" aria-selected="false" style="color: #6c757d; padding: 12px 20px; width: 100%; text-align: center;">
                                <i class="fas fa-clipboard-list me-2"></i>SURVEYS
                            </button>
                        </li>
                        <li class="nav-item" role="presentation" style="flex: 1;">
                            <button class="nav-link" id="rooms-tab" data-bs-toggle="tab" data-bs-target="#rooms" type="button" role="tab" aria-controls="rooms" aria-selected="false" style="color: #6c757d; padding: 12px 20px; width: 100%; text-align: center;">
                                <i class="fas fa-door-open me-2"></i>ROOMS
                            </button>
                        </li>
                        <li class="nav-item" role="presentation" style="flex: 1;">
                            <button class="nav-link" id="rentals-tab" data-bs-toggle="tab" data-bs-target="#rentals" type="button" role="tab" aria-controls="rentals" aria-selected="false" style="color: #6c757d; padding: 12px 20px; width: 100%; text-align: center;">
                                <i class="fas fa-cogs me-2"></i>RENTAL ITEMS
                            </button>
                        </li>
                        <li class="nav-item" role="presentation" style="flex: 1;">
                            <button class="nav-link" id="financial-tab" data-bs-toggle="tab" data-bs-target="#financial" type="button" role="tab" aria-controls="financial" aria-selected="false" style="color: #6c757d; padding: 12px 20px; width: 100%; text-align: center;">
                                <i class="fas fa-calculator me-2"></i>FINANCIAL
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Tab Content -->
            <div class="tab-content" id="quotationTabsContent">
                <!-- Basic Info Tab -->
                <div class="tab-pane fade show active" id="basic-info" role="tabpanel" aria-labelledby="basic-info-tab">
                    <div class="row quotation-layout-fix">
                        <!-- Quotation Information Section 1 -->
                        <div class="col-lg-6 mb-4">
                            <div class="card quotation-card">
                                <div class="quotation-card-header">
                                    <h5 class="card-title mb-0">
                                        Edit Quotation Information
                                    </h5>
                                </div>
                                <div class="quotation-card-body">
                                    <form action="{{ route('marketing.quotations.update', $quotation) }}" method="POST" id="quotationForm">
                                        @csrf
                                        @method('PUT')
                                        
                                        <!-- Hidden fields untuk required validation -->
                                        <input type="hidden" name="prospect_id" value="{{ $quotation->prospect_id }}">
                                        <input type="hidden" name="survey_id" value="{{ $quotation->survey_id }}">
                                        <input type="hidden" name="marketing_id" value="{{ $quotation->marketing_id }}">
                                        
                                        <div class="quotation-field">
                                            <div class="quotation-field-label">Quotation Number</div>
                                            <div class="quotation-field-value">
                                                <input type="text" class="form-control" id="quotation_number" name="quotation_number" 
                                                       value="{{ old('quotation_number', $quotation->quotation_number) }}" readonly>
                                            </div>
                                        </div>
                                        
                                        <div class="quotation-field">
                                            <div class="quotation-field-label">Status</div>
                                            <div class="quotation-field-value">
                                                <select class="form-control" id="status" name="status" required>
                                                    <option value="draft" {{ $quotation->status == 'draft' ? 'selected' : '' }}>Draft</option>
                                                    <option value="sent" {{ $quotation->status == 'sent' ? 'selected' : '' }}>Sent</option>
                                                    <option value="approved" {{ $quotation->status == 'approved' ? 'selected' : '' }}>Approved</option>
                                                    <option value="accepted" {{ $quotation->status == 'accepted' ? 'selected' : '' }}>Accepted</option>
                                                    <option value="rejected" {{ $quotation->status == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                                    <option value="expired" {{ $quotation->status == 'expired' ? 'selected' : '' }}>Expired</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="quotation-field">
                                            <div class="quotation-field-label">Company Name</div>
                                            <div class="quotation-field-value">
                                                <input type="text" class="form-control" id="company_name" name="company_name" 
                                                       value="{{ old('company_name', $quotation->company_name) }}" required>
                                            </div>
                                        </div>
                                        
                                        <div class="quotation-field">
                                            <div class="quotation-field-label">PIC Name</div>
                                            <div class="quotation-field-value">
                                                <input type="text" class="form-control" id="pic_name" name="pic_name" 
                                                       value="{{ old('pic_name', $quotation->pic_name) }}" required>
                                            </div>
                                        </div>
                                        
                                        <div class="quotation-field">
                                            <div class="quotation-field-label">Quotation Date</div>
                                            <div class="quotation-field-value">
                                                <input type="date" class="form-control" id="quotation_date" name="quotation_date" 
                                                       value="{{ old('quotation_date', $quotation->quotation_date ? $quotation->quotation_date->format('Y-m-d') : '') }}" required>
                                            </div>
                                        </div>
                                        
                                        <div class="quotation-field">
                                            <div class="quotation-field-label">Valid Until</div>
                                            <div class="quotation-field-value">
                                                <input type="date" class="form-control" id="valid_until" name="valid_until" 
                                                       value="{{ old('valid_until', $quotation->valid_until ? $quotation->valid_until->format('Y-m-d') : '') }}" required>
                                            </div>
                                        </div>
                                        
                                        <div class="quotation-field">
                                            <div class="quotation-field-label">Billing Methods</div>
                                            <div class="quotation-field-value">
                                                <select class="form-control" id="billing_methods" name="billing_methods" required>
                                                    <option value="Before Service" {{ $quotation->billing_methods == 'Before Service' ? 'selected' : '' }}>Before Service</option>
                                                    <option value="After Service" {{ $quotation->billing_methods == 'After Service' ? 'selected' : '' }}>After Service</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="quotation-field">
                                            <div class="quotation-field-label">Rental Period</div>
                                            <div class="quotation-field-value">
                                                <input type="text" class="form-control" id="rental_period" name="rental_period" 
                                                       value="{{ old('rental_period', $quotation->rental_period) }}">
                                            </div>
                                        </div>
                                        
                                        <div class="quotation-field">
                                            <div class="quotation-field-label">Terms of Payment</div>
                                            <div class="quotation-field-value">
                                                <input type="text" class="form-control" id="terms_of_payment" name="terms_of_payment" 
                                                       value="{{ old('terms_of_payment', $quotation->terms_of_payment) }}">
                                            </div>
                                        </div>
                                        
                                        <div class="quotation-field">
                                            <div class="quotation-field-label">Quotation Type</div>
                                            <div class="quotation-field-value">
                                                <select class="form-control" id="quotation_type" name="quotation_type" required>
                                                    <option value="new" {{ $quotation->quotation_type == 'new' ? 'selected' : '' }}>New</option>
                                                    <option value="renewal" {{ $quotation->quotation_type == 'renewal' ? 'selected' : '' }}>Renewal</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between mt-4">
                                            <a href="{{ route('marketing.quotations.show', $quotation) }}" class="btn btn-secondary">
                                                <i class="fas fa-times me-1"></i>Cancel
                                            </a>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save me-1"></i>Update Basic Info
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Notes Section 2 -->
                        <div class="col-lg-6 mb-4">
                            <div class="card quotation-card">
                                <div class="quotation-card-header">
                                    <h5 class="card-title mb-0">
                                        Notes & Additional Information
                                    </h5>
                                </div>
                                <div class="quotation-card-body">
                                    <div class="quotation-field">
                                        <div class="quotation-field-label">Internal Notes</div>
                                        <div class="quotation-field-value">
                                            <textarea class="form-control" id="internal_notes" name="internal_notes" rows="4">{{ old('internal_notes', $quotation->internal_notes) }}</textarea>
                                        </div>
                                    </div>
                                    
                                    <div class="quotation-field">
                                        <div class="quotation-field-label">Additional Notes</div>
                                        <div class="quotation-field-value">
                                            <textarea class="form-control" id="additional_notes" name="additional_notes" rows="4">{{ old('additional_notes', $quotation->additional_notes) }}</textarea>
                                        </div>
                                    </div>
                                    
                                    <div class="quotation-field">
                                        <div class="quotation-field-label">Terms & Conditions</div>
                                        <div class="quotation-field-value">
                                            <textarea class="form-control" id="terms_conditions" name="terms_conditions" rows="6">{{ old('terms_conditions', $quotation->terms_conditions) }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                        <!-- Surveys Tab -->
                        <div class="tab-pane fade" id="surveys" role="tabpanel">
                            <div class="mt-4">
                                <h5>Attached Surveys</h5>
                                @if($quotation->quotationSurveys->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Survey Number</th>
                                                    <th>Customer</th>
                                                    <th>Building</th>
                                                    <th>Survey Date</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($quotation->quotationSurveys as $quotationSurvey)
                                                    <tr>
                                                        <td>{{ $quotationSurvey->survey->survey_number }}</td>
                                                        <td>{{ $quotationSurvey->survey->company_name }}</td>
                                                        <td>{{ $quotationSurvey->survey->building_name }}</td>
                                                        <td>{{ $quotationSurvey->survey->survey_date ? $quotationSurvey->survey->survey_date->format('d/M/Y') : '-' }}</td>
                                                        <td>
                                                            <span class="badge bg-{{ $quotationSurvey->survey->status == 'approved' ? 'success' : 'warning' }}">
                                                                {{ ucfirst($quotationSurvey->survey->status) }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <a href="{{ route('marketing.surveys.show', $quotationSurvey->survey) }}" class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-eye"></i> View
                                                            </a>
                                                            @if($quotation->status == 'draft')
                                                                <button class="btn btn-sm btn-outline-danger" onclick="removeSurvey({{ $quotationSurvey->survey_id }})">
                                                                    <i class="fas fa-trash"></i> Remove
                                                                </button>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="text-center text-muted py-4">
                                        <i class="fas fa-clipboard-list fa-3x mb-3"></i>
                                        <p>No surveys attached to this quotation.</p>
                                    </div>
                                @endif

                                @if($quotation->status == 'draft')
                                    <div class="mt-3">
                                        <button class="btn btn-primary" onclick="addSurvey()">
                                            <i class="fas fa-plus me-1"></i>Add Survey
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Rooms Tab -->
                        <div class="tab-pane fade" id="rooms" role="tabpanel">
                            <div class="mt-4">
                                <h5>Selected Rooms</h5>
                                @if($quotation->quotationDetails->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Room Name</th>
                                                    <th>Specifications</th>
                                                    <th>Quantity</th>
                                                    <th>Unit Price</th>
                                                    <th>Total Price</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($quotation->quotationDetails as $detail)
                                                    <tr>
                                                        <td>{{ $detail->room_name }}</td>
                                                        <td>
                                                            @if($detail->specifications)
                                                                @php
                                                                    $specs = json_decode($detail->specifications, true);
                                                                @endphp
                                                                @if($specs)
                                                                    <small>
                                                                        @foreach($specs as $key => $value)
                                                                            <strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong> {{ $value }}<br>
                                                                        @endforeach
                                                                    </small>
                                                                @endif
                                                            @endif
                                                        </td>
                                                        <td>{{ $detail->quantity }}</td>
                                                        <td>Rp {{ number_format($detail->unit_price, 0, ',', '.') }}</td>
                                                        <td>Rp {{ number_format($detail->total_price, 0, ',', '.') }}</td>
                                                        <td>
                                                            @if($quotation->status == 'draft')
                                                                <button class="btn btn-sm btn-outline-danger" onclick="removeRoom({{ $detail->id }})">
                                                                    <i class="fas fa-trash"></i> Remove
                                                                </button>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="text-center text-muted py-4">
                                        <i class="fas fa-door-open fa-3x mb-3"></i>
                                        <p>No rooms selected for this quotation.</p>
                                    </div>
                                @endif

                                @if($quotation->status == 'draft')
                                    <div class="mt-3">
                                        <button class="btn btn-primary" onclick="addRoom()">
                                            <i class="fas fa-plus me-1"></i>Add Room
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Rental Items Tab -->
                        <div class="tab-pane fade" id="rentals" role="tabpanel">
                            <div class="mt-4">
                                <h5>Rental Items</h5>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Rental items are managed through the quotation details. 
                                    Each room can have multiple rental configurations.
                                </div>
                                
                                @if($quotation->quotationDetails->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Room</th>
                                                    <th>Product</th>
                                                    <th>Quantity</th>
                                                    <th>Unit Price</th>
                                                    <th>Total</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($quotation->quotationDetails as $detail)
                                                    <tr>
                                                        <td>{{ $detail->room_name }}</td>
                                                        <td>
                                                            @if($detail->masterRental)
                                                                {{ $detail->masterRental->rental_name }}
                                                            @else
                                                                <span class="text-muted">No product assigned</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ $detail->quantity }}</td>
                                                        <td>Rp {{ number_format($detail->unit_price, 0, ',', '.') }}</td>
                                                        <td>Rp {{ number_format($detail->total_price, 0, ',', '.') }}</td>
                                                        <td>
                                                            @if($quotation->status == 'draft')
                                                                <button class="btn btn-sm btn-outline-primary" onclick="editRental({{ $detail->id }})">
                                                                    <i class="fas fa-edit"></i> Edit
                                                                </button>
                                                                <button class="btn btn-sm btn-outline-danger" onclick="removeRental({{ $detail->id }})">
                                                                    <i class="fas fa-trash"></i> Remove
                                                                </button>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="text-center text-muted py-4">
                                        <i class="fas fa-cogs fa-3x mb-3"></i>
                                        <p>No rental items configured for this quotation.</p>
                                    </div>
                                @endif

                                @if($quotation->status == 'draft')
                                    <div class="mt-3">
                                        <button class="btn btn-primary" onclick="addRental()">
                                            <i class="fas fa-plus me-1"></i>Add Rental Item
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Financial Tab -->
                        <div class="tab-pane fade" id="financial" role="tabpanel">
                            <div class="mt-4">
                                <h5>Financial Summary</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="total_amount" class="form-label">Total Amount</label>
                                            <input type="number" class="form-control" id="total_amount" name="total_amount" 
                                                   value="{{ old('total_amount', $quotation->total_amount) }}" step="0.01" 
                                                   {{ $quotation->status != 'draft' ? 'readonly' : '' }}>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="tax_amount" class="form-label">Tax Amount</label>
                                            <input type="number" class="form-control" id="tax_amount" name="tax_amount" 
                                                   value="{{ old('tax_amount', $quotation->tax_amount) }}" step="0.01"
                                                   {{ $quotation->status != 'draft' ? 'readonly' : '' }}>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="discount_amount" class="form-label">Discount Amount</label>
                                            <input type="number" class="form-control" id="discount_amount" name="discount_amount" 
                                                   value="{{ old('discount_amount', $quotation->discount_amount) }}" step="0.01"
                                                   {{ $quotation->status != 'draft' ? 'readonly' : '' }}>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="grand_total" class="form-label">Grand Total</label>
                                            <input type="number" class="form-control" id="grand_total" name="grand_total" 
                                                   value="{{ old('grand_total', $quotation->grand_total) }}" step="0.01" readonly>
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Note:</strong> Financial amounts are automatically calculated based on rental items. 
                                    You can override the values if needed.
                                </div>

                                @if($quotation->status == 'draft')
                                    <div class="mt-3">
                                        <button class="btn btn-success" onclick="recalculateTotals()">
                                            <i class="fas fa-calculator me-1"></i>Recalculate Totals
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css">
<style>
    /* Force layout fixes */
    .container-fluid {
        padding: 0 !important;
        margin: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
    }
    
    .row {
        margin: 0 !important;
        display: flex !important;
        flex-wrap: wrap !important;
        width: 100% !important;
    }
    
    .col-12 {
        padding: 0 !important;
        width: 100% !important;
    }
    
    .col-lg-6 {
        flex: 0 0 50% !important;
        max-width: 50% !important;
        padding: 15px !important;
        display: block !important;
    }
    
    /* Card Full Width */
    .card {
        width: 100% !important;
        margin: 0 !important;
    }
    
    /* Tab Content Full Width */
    .tab-content {
        width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    
    @media (max-width: 991.98px) {
        .col-lg-6 {
            flex: 0 0 100% !important;
            max-width: 100% !important;
        }
    }
    
    .card {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
        border: 1px solid rgba(0, 0, 0, 0.125) !important;
        margin-bottom: 1rem !important;
        border-radius: 8px !important;
    }
    
    .card-header {
        padding: 1rem 1.5rem !important;
        border-bottom: 1px solid rgba(0, 0, 0, 0.125) !important;
    }
    
    .card-body {
        padding: 1.5rem !important;
    }
    
    .nav-tabs {
        border-bottom: 2px solid #1e3a8a !important;
        margin: 0 !important;
        display: flex !important;
        flex-direction: row !important;
    }
    
    .nav-tabs .nav-item {
        flex: 1 !important;
    }
    
    .nav-tabs .nav-link {
        border: none !important;
        border-radius: 0 !important;
        transition: all 0.3s ease !important;
        padding: 12px 20px !important;
        width: 100% !important;
        text-align: center !important;
    }
    
    .nav-tabs .nav-link:hover {
        border-color: transparent !important;
        background-color: #f8f9fa !important;
    }
    
    .nav-tabs .nav-link.active {
        border-color: transparent !important;
        background-color: white !important;
        border-bottom: 3px solid #1e3a8a !important;
        color: #1e3a8a !important;
        font-weight: bold !important;
    }
    
    .table th {
        background-color: #f8f9fa !important;
        border-top: none !important;
        font-weight: 600 !important;
        color: #495057 !important;
        padding: 12px !important;
    }
    
    .table td {
        padding: 12px !important;
        vertical-align: middle !important;
    }
    
    .spec-details div {
        margin-bottom: 2px;
        font-size: 0.9rem;
    }
    
    .btn-sm {
        padding: 0.375rem 0.75rem !important;
        font-size: 0.875rem !important;
    }
    
    .form-control {
        border: 1px solid #ced4da !important;
        border-radius: 0.375rem !important;
        padding: 0.375rem 0.75rem !important;
    }
    
    .input-group {
        display: flex !important;
        width: 100% !important;
    }
    
    .input-group .form-control {
        border-top-right-radius: 0 !important;
        border-bottom-right-radius: 0 !important;
    }
    
    .input-group-append {
        display: flex !important;
    }
    
    .input-group-append .btn {
        border-top-left-radius: 0 !important;
        border-bottom-left-radius: 0 !important;
        border-left: 0 !important;
    }
    
    @media (max-width: 768px) {
        .d-flex.justify-content-between {
            flex-direction: column !important;
            gap: 1rem !important;
        }
        
        .nav-tabs {
            flex-direction: row !important;
        }
        
        .nav-tabs .nav-link {
            text-align: center !important;
            font-size: 0.9rem !important;
            padding: 10px 15px !important;
        }
    }
</style>
@endpush

@push('scripts')
<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap4.min.js"></script>
<script>
$(document).ready(function() {
    console.log('Quotation edit page loaded');
    
    // Initialize tab state - ensure only Basic Info is active
    $('.tab-pane').removeClass('show active').css('display', 'none');
    $('#basic-info').addClass('show active').css('display', 'block');
    
    // Tab switching functionality using Bootstrap 5
    $('#quotationTabs button[data-bs-toggle="tab"]').on('click', function(e) {
        e.preventDefault();
        var target = $(this).attr('data-bs-target');
        var tabId = $(this).attr('id');
        
        console.log('Tab clicked:', tabId, 'Target:', target);
        
        // Remove active class from all tabs and content
        $('#quotationTabs button').removeClass('active').css({
            'border-bottom': 'none',
            'color': '#6c757d',
            'font-weight': 'normal'
        });
        
        // Hide all tab panes
        $('.tab-pane').removeClass('show active').css('display', 'none');
        
        // Add active class to clicked tab
        $(this).addClass('active').css({
            'border-bottom': '3px solid #1e3a8a',
            'color': '#1e3a8a',
            'font-weight': 'bold'
        });
        
        // Show target content with proper Bootstrap classes
        $(target).addClass('show active').css('display', 'block');
        
        console.log('Tab switched to:', target, 'Active classes applied');
        console.log('Target element visible:', $(target).is(':visible'));
        console.log('Target element display:', $(target).css('display'));
    });
});

// JavaScript functions for dynamic editing
function addSurvey() {
    // Implementation for adding survey
    alert('Add Survey functionality will be implemented');
}

function removeSurvey(surveyId) {
    if (confirm('Are you sure you want to remove this survey?')) {
        // Implementation for removing survey
        alert('Remove Survey functionality will be implemented');
    }
}

function addRoom() {
    // Implementation for adding room
    alert('Add Room functionality will be implemented');
}

function removeRoom(roomId) {
    if (confirm('Are you sure you want to remove this room?')) {
        // Implementation for removing room
        alert('Remove Room functionality will be implemented');
    }
}

function addRental() {
    // Implementation for adding rental
    alert('Add Rental functionality will be implemented');
}

function editRental(rentalId) {
    // Implementation for editing rental
    alert('Edit Rental functionality will be implemented');
}

function removeRental(rentalId) {
    if (confirm('Are you sure you want to remove this rental item?')) {
        // Implementation for removing rental
        alert('Remove Rental functionality will be implemented');
    }
}

function recalculateTotals() {
    // Implementation for recalculating totals
    alert('Recalculate Totals functionality will be implemented');
}
</script>
@endpush
