@extends('layouts.app')

@section('title', 'Survey Wizard')
@section('breadcrumb')
<a href="{{ route('marketing.surveys.index') }}" style="color: #214589; text-decoration: none;">
    <i class="fas fa-arrow-left" style="margin-right: 5px;"></i>Back to Survey
</a>
/ Home / Marketing / Survey / Create
@endsection

@section('content')
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
<style>
/* Fix layout for form text */
.form-text {
    display: block !important;
    margin-top: 0.25rem;
    margin-bottom: 0;
}

/* Ensure proper spacing for form groups */
.form-group {
    margin-bottom: 1rem;
}

/* Validation Error Styling */
.form-control.is-invalid {
    border-color: #dc3545 !important;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='none' stroke='%23dc3545' viewBox='0 0 24 24'%3e%3ccircle cx='12' cy='12' r='10'/%3e%3cline x1='15' y1='9' x2='9' y2='15'/%3e%3cline x1='9' y1='9' x2='15' y2='15'/%3e%3c/svg%3e") !important;
    background-repeat: no-repeat !important;
    background-position: right calc(0.375em + 0.1875rem) center !important;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem) !important;
}

.invalid-feedback {
    display: block !important;
    width: 100%;
    margin-top: 0.25rem;
    font-size: 0.875rem;
    color: #dc3545;
    font-weight: 500;
}

.invalid-feedback::before {
    content: "⚠️ ";
    margin-right: 5px;
}

/* Select2 validation styling */
.select2-container--default .select2-selection--single.is-invalid {
    border-color: #dc3545 !important;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
}

/* Fix Select2 container if it exists */
.select2-container {
    width: 100% !important;
}

/* Step Indicator Styles */
.step-indicator-vertical {
    position: relative;
}

.step-indicator-vertical .step {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
    position: relative;
    padding: 10px;
    border-radius: 8px;
    transition: all 0.3s ease;
    cursor: pointer;
}

.step-indicator-vertical .step:not(:last-child)::after {
    content: '';
    position: absolute;
    left: 20px;
    top: 50px;
    width: 2px;
    height: 40px;
    background: #dee2e6;
    z-index: 1;
}

.step-indicator-vertical .step.active:not(:last-child)::after,
.step-indicator-vertical .step.completed:not(:last-child)::after {
    background: #007bff;
}

.step-indicator-vertical .step:hover {
    background: #e9ecef;
}

.step-indicator-vertical .step.active {
    background: #e3f2fd;
    border: 1px solid #007bff;
}

.step-indicator-vertical .step.completed {
    background: #e8f5e8;
    border: 1px solid #28a745;
}

.step-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #dee2e6;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    position: relative;
    z-index: 2;
    transition: all 0.3s ease;
    border: 3px solid #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    flex-shrink: 0;
    margin-right: 15px;
}

.step.active .step-circle {
    background: #007bff;
    color: white;
    transform: scale(1.1);
    box-shadow: 0 4px 8px rgba(0,123,255,0.3);
}

.step.completed .step-circle {
    background: #28a745;
    color: white;
}

.step-content {
    flex: 1;
}

.step-title {
    font-size: 14px;
    font-weight: 600;
    color: #495057;
    margin-bottom: 2px;
    transition: color 0.3s ease;
}

.step-desc {
    font-size: 12px;
    color: #6c757d;
    transition: color 0.3s ease;
}

.step.active .step-title {
    color: #007bff;
}

.step.active .step-desc {
    color: #007bff;
}

.step.completed .step-title {
    color: #28a745;
}

.step.completed .step-desc {
    color: #28a745;
}

/* Navigation Buttons */
.wizard-navigation {
    background: #f8f9fa;
    border-top: 1px solid #dee2e6;
    padding: 20px;
    display: flex;
    justify-content: flex-end;
    align-items: center;
    border-radius: 0 0 8px 8px;
    gap: 10px;
}

/* Form Sections */
.form-section {
    margin-bottom: 20px;
}

/* Modal Styling */
.modal-content {
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
}

.modal-header {
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
    border-radius: 10px 10px 0 0;
    border-bottom: none;
    padding: 20px 25px;
}

.modal-title {
    font-weight: 600;
    font-size: 1.2rem;
}

.modal-body {
    padding: 25px;
    background-color: #f8f9fa;
    max-height: 70vh;
    overflow-y: auto;
}

.modal-footer {
    background-color: #f8f9fa;
    border-top: 1px solid #dee2e6;
    border-radius: 0 0 10px 10px;
    padding: 20px 25px;
}

.modal .form-section {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    border: 1px solid #e9ecef;
}

/* Empty State Styling */
.empty-state {
    padding: 40px 20px;
}

.empty-icon {
    opacity: 0.6;
}

.empty-state h5 {
    font-weight: 500;
}

.empty-state p {
    font-size: 14px;
    line-height: 1.5;
}

.modal .section-header h6 {
    font-size: 1rem;
    font-weight: 600;
    color: #007bff;
}

.modal .section-header hr {
    border-top: 2px solid #007bff;
    margin: 8px 0 15px 0;
}

.modal .form-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 8px;
}

.modal .form-control {
    border-radius: 6px;
    border: 1px solid #ced4da;
    padding: 10px 12px;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.modal .form-control:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.modal .form-control::placeholder {
    color: #6c757d;
    font-style: italic;
}

.modal .btn {
    border-radius: 6px;
    font-weight: 500;
    padding: 10px 20px;
    transition: all 0.3s ease;
}

.modal .btn-primary {
    background: linear-gradient(135deg, #007bff, #0056b3);
    border: none;
}

.modal .btn-primary:hover {
    background: linear-gradient(135deg, #0056b3, #004085);
    transform: translateY(-1px);
}

.modal .btn-secondary {
    background: #6c757d;
    border: none;
}

.modal .btn-secondary:hover {
    background: #545b62;
    transform: translateY(-1px);
}

/* Modal Scroll Fix */
.modal-dialog-scrollable .modal-body {
    max-height: 70vh;
    overflow-y: auto;
}

.modal-dialog-scrollable .modal-content {
    max-height: 90vh;
}

/* Custom scrollbar for modal */
.modal-body::-webkit-scrollbar {
    width: 8px;
}

.modal-body::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.modal-body::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

.modal-body::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
    padding-top: 20px;
}

.section-header {
    border-bottom: 1px solid #e9ecef;
    padding-bottom: 10px;
    margin-bottom: 20px;
}

.section-header h6 {
    font-weight: 600;
    color: #6c757d;
    margin: 0;
}

/* Content positioning to align with navigation */
.card-body {
    padding-top: 30px;
}

/* Step content alignment */
.wizard-step .card {
    min-height: 500px;
    display: flex;
    flex-direction: column;
}

.wizard-step .card-body {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.btn-wizard {
    padding: 10px 25px;
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.3s ease;
    border: none;
}

.btn-wizard:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.btn-wizard:active {
    transform: translateY(0);
}

/* Form Enhancements */
.form-control {
    border-radius: 6px;
    border: 1px solid #ced4da;
    transition: all 0.3s ease;
    padding: 12px 15px;
    font-size: 14px;
}

.form-control:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
}

.form-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 8px;
}

.select2-container--default .select2-selection--single {
    height: 38px;
    border-radius: 6px;
    border: 1px solid #ced4da;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 36px;
    padding-left: 12px;
}

/* Force side-by-side layout for desktop */
@media (min-width: 768px) {
    .row {
        display: flex !important;
        flex-wrap: nowrap !important;
        align-items: stretch !important;
        gap: 20px !important;
    }
    
    .col-lg-4, .col-md-4 {
        flex: 0 0 30% !important;
        max-width: 100% !important;
        display: flex !important;
    }
    
    .col-lg-8, .col-md-8 {
        flex: 0 0 70% !important;
        max-width: 70% !important;
        display: flex !important;
    }
    
    .card {
        width: 100% !important;
        display: flex !important;
        flex-direction: column !important;
    }
    
    .card-body {
        flex: 1 !important;
    }
}

/* Mobile responsive */
@media (max-width: 767px) {
    .row {
        display: block !important;
    }
    
    .col-lg-4, .col-md-4,
    .col-lg-8, .col-md-8 {
        flex: none !important;
        max-width: 100% !important;
        width: 100% !important;
    }
}
</style>
<div class="container-fluid">
    <div class="row">
        <!-- Left Card - Navigation -->
        <div class="col-lg-4 col-md-4">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0">
                                <i class="fas fa-clipboard-list mr-2"></i>
                                Survey Wizard
                            </h4>
                            <small>Step <span id="current-step-number">1</span> of 6</small>
                        </div>
                        <a href="{{ route('marketing.surveys.index') }}" class="btn btn-light btn-sm" title="Back to Survey List">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="step-indicator-vertical">
                        <div class="step active" data-step="1">
                            <div class="step-circle">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="step-content">
                                <div class="step-title">Basic Info</div>
                                <div class="step-desc">Marketing & Date</div>
                            </div>
                        </div>
                        <div class="step" data-step="2">
                            <div class="step-circle">
                                <i class="fas fa-building"></i>
                            </div>
                            <div class="step-content">
                                <div class="step-title">Customer</div>
                                <div class="step-desc">Company Data</div>
                            </div>
                        </div>
                        <div class="step" data-step="3">
                            <div class="step-circle">
                                <i class="fas fa-address-book"></i>
                            </div>
                            <div class="step-content">
                                <div class="step-title">Contact</div>
                                <div class="step-desc">Contact Person</div>
                            </div>
                        </div>
                        <div class="step" data-step="4">
                            <div class="step-circle">
                                <i class="fas fa-home"></i>
                            </div>
                            <div class="step-content">
                                <div class="step-title">Building</div>
                                <div class="step-desc">Location Data</div>
                            </div>
                        </div>
                        <div class="step" data-step="5">
                            <div class="step-circle">
                                <i class="fas fa-door-open"></i>
                            </div>
                            <div class="step-content">
                                <div class="step-title">Rooms</div>
                                <div class="step-desc">Room Details</div>
                            </div>
                        </div>
                        <div class="step" data-step="6">
                            <div class="step-circle">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="step-content">
                                <div class="step-title">Summary</div>
                                <div class="step-desc">Review & Save</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Card - Content -->
        <div class="col-lg-8 col-md-8">
            <!-- Step 1: Basic Information -->
            <div class="wizard-step" id="step-1" style="display: block;">
                <div class="card">
                    <div class="card-header">
                        <h4 class="text-primary">
                            <i class="fas fa-user mr-2"></i>
                            Step 1: Basic Information
                        </h4>
                        <p class="text-muted mb-0">Pilih marketing staff dan tentukan tanggal survey</p>
                    </div>
                    <div class="card-body">
                        <div class="form-section">
                            <div class="section-header">
                                <h6 class="text-muted mb-3">
                                    <i class="fas fa-user-circle mr-2"></i>
                                    Marketing Information
                                </h6>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="marketing_staff_id" class="form-label">Marketing Staff <span class="text-danger">*</span></label>
                                        <select class="form-control select2" id="marketing_staff_id" name="marketing_staff_id" required>
                                            <option value="">Pilih Marketing Staff</option>
                                            @foreach($marketingStaff as $staff)
                                                <option value="{{ $staff->id }}" {{ $staff->id == Auth::id() ? 'selected' : '' }}>{{ ($staff->salutation ? $staff->salutation . ' ' : '') . $staff->name }}</option>
                                            @endforeach
                                        </select>
                                        <small class="form-text text-muted d-block">Bisa diketik untuk mencari nama marketing</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="survey_date" class="form-label">Tanggal Survey <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="date" class="form-control" id="survey_date" name="survey_date" value="{{ date('Y-m-d') }}" required>
                                            <input type="hidden" id="survey_date_hidden" name="survey_date_hidden" value="{{ date('Y-m-d') }}">
                                        </div>
                                            <small class="form-text text-muted">Tanggal survey akan dilakukan</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="wizard-navigation">
                        <button type="button" class="btn btn-wizard btn-outline-secondary" id="prev-btn" style="display: none;">
                            <i class="fas fa-arrow-left mr-2"></i> Previous
                        </button>
                        <button type="button" class="btn btn-wizard btn-primary" id="next-btn">
                            Next <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Step 2: Customer Data -->
            <div class="wizard-step" id="step-2" style="display: none;">
                <div class="card">
                    <div class="card-header">
                        <h4 class="text-primary">
                            <i class="fas fa-building mr-2"></i>
                            Step 2: Customer Data
                        </h4>
                        <p class="text-muted mb-0">Informasi perusahaan customer</p>
                    </div>
                    <div class="card-body">
                        <div class="form-section">
                            <div class="section-header">
                                <h6 class="text-muted mb-3">
                                    <i class="fas fa-building mr-2"></i>
                                    Data Customer
                                </h6>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="customer_id" class="form-label">Customer <span class="text-danger">*</span></label>
                                        <select class="form-control select2" id="customer_id" name="customer_id" required>
                                            <option value="">Pilih atau ketik untuk mencari...</option>
                                            @foreach($customers as $customer)
                                                @if($customer->customerTaxSettings->count() > 0)
                                                    @foreach($customer->customerTaxSettings as $tax)
                                                        @php
                                                            $npwp = $tax->tax_number ?? '-';
                                                            $address = $tax->tax_address ?? '-';
                                                            $shortAddress = strlen($address) > 30 ? substr($address, 0, 30) . '...' : $address;
                                                            $label = "{$customer->name} - {$npwp} - {$shortAddress}";
                                                        @endphp
                                                        <option value="{{ $customer->id }}" data-company-type="{{ $customer->company_type }}">{{ $label }}</option>
                                                    @endforeach
                                                @else
                                                    <option value="{{ $customer->id }}" data-company-type="{{ $customer->company_type }}">{{ $customer->name }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                        <small class="form-text text-muted d-block">Bisa diketik untuk mencari customer</small>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="add_new_customer" name="add_new_customer">
                                        <label class="form-check-label" for="add_new_customer">
                                            Tambahkan Company Baru
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- New Customer Fields (Hidden by default) -->
                            <div id="new_customer_fields" style="display: none;">
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="new_company_type" class="form-label">Jenis Customer <span class="text-danger">*</span></label>
                                            <select class="form-control select2" id="new_company_type" name="new_company_type">
                                                <option value="">Pilih Jenis Customer</option>
                                                @foreach($companyOptions as $jenis)
                                                    <option value="{{ $jenis->id }}" data-label="{{ $jenis->label ?? $jenis->option_name }}" data-code="{{ $jenis->code ?? '' }}">{{ $jenis->label ?? $jenis->option_name }}</option>
                                                @endforeach
                                            </select>
                                            <small class="form-text text-muted d-block">Pilih jenis customer dari master options</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="new_customer_name" class="form-label">Nama Customer <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="new_customer_name" name="new_customer_name" placeholder="Masukkan nama customer" style="pointer-events: auto !important; z-index: 9999 !important;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="wizard-navigation">
                        <button type="button" class="btn btn-wizard btn-outline-secondary" id="prev-btn">
                            <i class="fas fa-arrow-left mr-2"></i> Previous
                        </button>
                        <button type="button" class="btn btn-wizard btn-primary" id="next-btn">
                            Next <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Step 3: Contact Person -->
            <div class="wizard-step" id="step-3" style="display: none;">
                <div class="card">
                    <div class="card-header">
                        <h4 class="text-primary">
                            <i class="fas fa-address-book mr-2"></i>
                            Step 3: Contact Person
                        </h4>
                        <p class="text-muted mb-0">Data kontak person customer</p>
                    </div>
                    <div class="card-body">
                        <div class="form-section">
                            <div class="section-header">
                                <h6 class="text-muted mb-3">
                                    <i class="fas fa-address-book mr-2"></i>
                                    Data Kontak
                                </h6>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="contact_id" class="form-label">Daftar Kontak Company <span class="text-danger">*</span></label>
                                        <select class="form-control select2" id="contact_id" name="contact_id" required>
                                            <option value="">Pilih atau ketik disini..</option>
                                        </select>
                                        <small class="form-text text-muted">Data kontak akan muncul sesuai customer yang dipilih di step 2</small>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="add_new_contact" name="add_new_contact">
                                        <label class="form-check-label" for="add_new_contact">
                                            Tambah Kontak baru
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- New Contact Fields (Hidden by default) -->
                            <div id="new_contact_fields" style="display: none;">
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="new_contact_salutation" class="form-label">Panggilan <span class="text-danger">*</span></label>
                                            <select class="form-control select2" id="new_contact_salutation" name="new_contact_salutation">
                                                <option value="">Pilih Panggilan</option>
                                                @foreach($salutations as $salutation)
                                                    <option value="{{ $salutation->label ?? $salutation->option_name }}">{{ $salutation->label ?? $salutation->option_name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="new_contact_name" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="new_contact_name" name="new_contact_name" placeholder="Masukkan nama lengkap">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="new_contact_email" class="form-label">Email <span class="text-danger">*</span></label>
                                            <input type="email" class="form-control" id="new_contact_email" name="new_contact_email" placeholder="Masukkan email">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="new_contact_phone" class="form-label">Phone <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="new_contact_phone" name="new_contact_phone" placeholder="Masukkan nomor telepon">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="new_contact_position" class="form-label">Jabatan / Posisi <span class="text-danger">*</span></label>
                                            <select class="form-control select2" id="new_contact_position" name="new_contact_position">
                                                <option value="">Pilih atau ketik disini..</option>
                                                @foreach($positions as $position)
                                                    <option value="{{ $position }}">{{ $position }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="wizard-navigation">
                        <button type="button" class="btn btn-wizard btn-outline-secondary" id="prev-btn">
                            <i class="fas fa-arrow-left mr-2"></i> Previous
                        </button>
                        <button type="button" class="btn btn-wizard btn-primary" id="next-btn">
                            Next <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Step 4: Lokasi Survey -->
            <div class="wizard-step" id="step-4" style="display: none;">
                <div class="card">
                    <div class="card-header">
                        <h4 class="text-primary">
                            <i class="fas fa-map-marker-alt mr-2"></i>
                            Step 4: Lokasi Survey
                        </h4>
                        <p class="text-muted mb-0">Pilih atau tambah alamat survey</p>
                    </div>
                    <div class="card-body">
                        <div class="form-section">
                            <div class="section-header">
                                <h6 class="text-muted mb-3">
                                    <i class="fas fa-map-marker-alt mr-2"></i>
                                    Lokasi Survey
                                </h6>
                            </div>
                            
                            <!-- Pilih Alamat Existing -->
                            <div id="existing_address_section">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="building_id" class="form-label">Pilih Alamat <span class="text-danger">*</span></label>
                                            <select class="form-control select2" id="building_id" name="building_id" required>
                                                <option value="">Pilih atau ketik disini..</option>
                                            </select>
                                            <small class="form-text text-muted">Daftar semua gedung aktif. Gunakan fitur cari untuk mempermudah.</small>
                                        </div>
                                        
                                        <!-- Selected Address Display -->
                                        <div id="selected_address_display" style="display: none; margin-top: 20px;">
                                            <div class="address-card" style="border: 1px solid #e0e0e0; border-radius: 8px; padding: 20px; background-color: #f8f9fa;">
                                                <div class="address-header" style="background-color: #007bff; color: white; padding: 10px 15px; margin: -20px -20px 15px -20px; border-radius: 8px 8px 0 0; font-weight: bold; text-align: center;">
                                                    ALAMAT PEMASANGAN
                                                </div>
                                                <div class="address-details">
                                                    <div class="building-name" style="font-weight: bold; font-size: 16px; margin-bottom: 8px; color: #333;">
                                                        <span id="selected_building_name"></span>
                                                    </div>
                                                    <div class="building-address" style="color: #666; margin-bottom: 5px;">
                                                        <span id="selected_building_address"></span>
                                                    </div>
                                                    <div class="building-postal" style="color: #666; margin-bottom: 15px;">
                                                        <span id="selected_building_postal"></span>
                                                    </div>
                                                    <div class="building-phone" style="color: #666; margin-bottom: 15px;">
                                                        <i class="fas fa-phone"></i> <span id="selected_building_phone"></span>
                                                    </div>
                                                    
                                                    <!-- Lokasi Detail dalam Gedung -->
                                                    <div class="location-detail-section" style="border-top: 1px solid #dee2e6; padding-top: 15px;">
                                                        <label for="building_location_detail" class="form-label" style="font-weight: bold; color: #495057;">
                                                            <i class="fas fa-map-marker-alt mr-2"></i>Lokasi Detail dalam Gedung
                                                        </label>
                                                        <input type="text" class="form-control" id="building_location_detail" name="building_location_detail" 
                                                               placeholder="Contoh: Lantai 2, No. 24 atau Unit A-101" 
                                                               style="border: 1px solid #ced4da; border-radius: 4px; padding: 8px 12px;">
                                                        <small class="form-text text-muted">
                                                            <i class="fas fa-info-circle mr-1"></i>
                                                            Masukkan lokasi spesifik dalam gedung (lantai, nomor unit, dll)
                                                        </small>
                                                    </div>
                                                </div>
                                                <div class="text-center mt-3">
                                                    <button type="button" class="btn btn-primary" id="change_address_btn">
                                                        <i class="fas fa-edit"></i> GANTI LOKASI
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            

                            <!-- Checkbox Tambah Alamat Baru -->
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="add_new_building" name="add_new_building">
                                        <label class="form-check-label" for="add_new_building">
                                            Tambahkan Alamat baru
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- New Building Fields (Hidden by default) -->
                            <div id="new_building_fields" style="display: none;">
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="new_building_name" class="form-label">Nama Gedung <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <select class="form-control select2" id="new_building_name" name="new_building_name" required>
                                                    <option value="">Pilih atau ketik disini..</option>
                                                </select>
                                                <div class="input-group-append">
                                                    <button type="button" class="btn btn-success no-double-click-prevention" id="add_master_building_btn" style="background-color: #28a745; border-color: #28a745; color: white; width: 40px; height: 38px; display: flex; align-items: center; justify-content: center;">
                                                        <i class="fas fa-plus" style="font-size: 14px;"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="new_building_type" class="form-label">Jenis Alamat <span class="text-danger">*</span></label>
                                            <select class="form-control select2" id="new_building_type" name="new_building_type" required>
                                                <option value="">Pilih atau ketik untuk mencari...</option>
                                                @foreach($addressTypes ?? [] as $addressType)
                                                    <option value="{{ $addressType->option_name }}">{{ $addressType->option_name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="new_building_location_detail" class="form-label">Lokasi Detail <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="new_building_location_detail" name="new_building_location_detail" placeholder="Contoh: Lantai 2, No. 24 atau Unit A-101" required>
                                            <small class="form-text text-muted">
                                                <i class="fas fa-info-circle mr-1"></i>
                                                Masukkan lokasi spesifik dalam gedung (lantai, nomor unit, dll)
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                
                                
                            </div>
                        </div>
                    </div>
                    <div class="wizard-navigation">
                        <button type="button" class="btn btn-wizard btn-outline-secondary" id="prev-btn">
                            <i class="fas fa-arrow-left mr-2"></i> Previous
                        </button>
                        <button type="button" class="btn btn-wizard btn-primary" id="next-btn">
                            Next <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Step 5: Ruang Survey -->
            <div class="wizard-step" id="step-5" style="display: none;">
                <div class="card">
                    <div class="card-header">
                        <h4 class="text-primary">
                            <i class="fas fa-door-open mr-2"></i>
                            Step 5: Ruang Survey
                        </h4>
                        <p class="text-muted mb-0">Detail ruangan yang akan di-survey</p>
                    </div>
                    <div class="card-body">
                        <div class="form-section">
                            <div class="section-header">
                                <h6 class="text-muted mb-3">
                                    <i class="fas fa-door-open mr-2"></i>
                                    Ruang Survey
                                </h6>
                            </div>
                            
                            <!-- Empty State - Only show when no rooms -->
                            <div id="empty-rooms-state" class="text-center py-5">
                                <div class="empty-state">
                                    <div class="empty-icon mb-4">
                                        <i class="fas fa-door-open fa-3x text-muted"></i>
                                    </div>
                                    <h5 class="text-muted mb-3">Belum ada ruangan survey</h5>
                                    <p class="text-muted mb-4">Klik tombol di bawah untuk menambahkan ruangan survey</p>
                                    <button type="button" class="btn btn-primary btn-lg" id="add-room-btn">
                                        <i class="fas fa-plus mr-2"></i> Tambah Ruangan
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Room Cards Container - Hidden by default -->
                            <div id="rooms-table-container" style="display: none; overflow: hidden; width: 100%;">
                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <button type="button" class="btn btn-primary" id="add-room-btn-table">
                                            <i class="fas fa-plus mr-2"></i> Tambah Ruangan
                                        </button>
                                    </div>
                                </div>
                                
                                <div id="rooms-cards-container">
                                    <!-- Room cards will be populated by JavaScript -->
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="wizard-navigation">
                        <button type="button" class="btn btn-wizard btn-outline-secondary" id="prev-btn">
                            <i class="fas fa-arrow-left mr-2"></i> Previous
                        </button>
                        <button type="button" class="btn btn-wizard btn-primary" id="next-btn">
                            Next <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Step 6: Summary -->
            <div class="wizard-step" id="step-6" style="display: none;">
                <div class="card">
                    <div class="card-header">
                        <h4 class="text-primary">
                            <i class="fas fa-check-circle mr-2"></i>
                            Step 6: Summary
                        </h4>
                        <p class="text-muted mb-0">Review data sebelum menyimpan</p>
                    </div>
                    <div class="card-body">
                        <!-- Data Survey Section -->
                        <div class="row mb-4 summary-cards-container">
                            <div class="col-lg-6 col-md-12 mb-4">
                                <div class="card border-primary h-100 summary-card">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="mb-0">
                                            <i class="fas fa-clipboard-list mr-2"></i>
                                            Data Survey
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="summary-item">
                                                    <label class="summary-label">Nama Marketing:</label>
                                                    <span class="summary-value" id="summary-marketing-name">-</span>
                                                </div>
                                                <div class="summary-item">
                                                    <label class="summary-label">Tanggal Survey:</label>
                                                    <span class="summary-value" id="summary-survey-date">-</span>
                                                </div>
                                                <div class="summary-item">
                                                    <label class="summary-label">Nama Gedung:</label>
                                                    <span class="summary-value" id="summary-building-name">-</span>
                                                </div>
                                                <div class="summary-item">
                                                    <label class="summary-label">Lokasi Detail:</label>
                                                    <span class="summary-value" id="summary-location-detail">-</span>
                                                </div>
                                                <div class="summary-item">
                                                    <label class="summary-label">Alamat 1:</label>
                                                    <span class="summary-value" id="summary-address1">-</span>
                                                </div>
                                                <div class="summary-item">
                                                    <label class="summary-label">Alamat 2:</label>
                                                    <span class="summary-value" id="summary-address2">-</span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="summary-item">
                                                    <label class="summary-label">Provinsi:</label>
                                                    <span class="summary-value" id="summary-province">-</span>
                                                </div>
                                                <div class="summary-item">
                                                    <label class="summary-label">Kota/Kabupaten:</label>
                                                    <span class="summary-value" id="summary-city">-</span>
                                                </div>
                                                <div class="summary-item">
                                                    <label class="summary-label">Kecamatan:</label>
                                                    <span class="summary-value" id="summary-district">-</span>
                                                </div>
                                                <div class="summary-item">
                                                    <label class="summary-label">Kelurahan:</label>
                                                    <span class="summary-value" id="summary-subdistrict">-</span>
                                                </div>
                                                <div class="summary-item">
                                                    <label class="summary-label">Kode Pos:</label>
                                                    <span class="summary-value" id="summary-postal-code">-</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-12 mb-4">
                                <div class="card border-success h-100 summary-card">
                                    <div class="card-header bg-success text-white">
                                        <h5 class="mb-0">
                                            <i class="fas fa-building mr-2"></i>
                                            Data Customer
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="summary-item">
                                                    <label class="summary-label">Nama Customer:</label>
                                                    <span class="summary-value" id="summary-customer-name">-</span>
                                                </div>
                                                <div class="summary-item">
                                                    <label class="summary-label">PIC:</label>
                                                    <span class="summary-value" id="summary-contact-name">-</span>
                                                </div>
                                                <div class="summary-item">
                                                    <label class="summary-label">E-mail:</label>
                                                    <span class="summary-value" id="summary-contact-email">-</span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="summary-item">
                                                    <label class="summary-label">Phone 1:</label>
                                                    <span class="summary-value" id="summary-contact-phone1">-</span>
                                                </div>
                                                <div class="summary-item">
                                                    <label class="summary-label">Phone 2:</label>
                                                    <span class="summary-value" id="summary-contact-phone2">-</span>
                                                </div>
                                                <div class="summary-item">
                                                    <label class="summary-label">Jabatan / Posisi:</label>
                                                    <span class="summary-value" id="summary-contact-position">-</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Ruangan Survey Table -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card border-info">
                                    <div class="card-header bg-info text-white">
                                        <h5 class="mb-0">
                                            <i class="fas fa-door-open mr-2"></i>
                                            Ruangan Survey
                                        </h5>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-hover mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th width="8%" class="text-center">No</th>
                                                        <th width="40%">Nama Ruangan</th>
                                                        <th width="52%">Spesifikasi</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="summary-rooms-tbody">
                                                    <!-- Room data will be populated by JavaScript -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="wizard-navigation">
                        <button type="button" class="btn btn-wizard btn-outline-secondary" id="prev-btn">
                            <i class="fas fa-arrow-left mr-2"></i> Previous
                        </button>
                        <button type="button" class="btn btn-wizard btn-outline-primary" id="save-draft-btn">
                            <i class="fas fa-save mr-2"></i> SAVE DRAFT
                        </button>
                        <button type="button" class="btn btn-wizard btn-success" id="finalize-btn">
                            <i class="fas fa-check mr-2"></i> FINALIZE & EMAIL
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    let currentStep = 1;
    const totalSteps = 6;

    // Initialize Select2 with proper loading check
    function initializeSelect2() {
        if (typeof $.fn.select2 !== 'undefined') {
            console.log('Initializing Select2 on elements:', $('.select2').length);
            $('.select2').each(function() {
                console.log('Initializing Select2 on:', $(this).attr('id'));
            });
            
            // Initialize Select2 for all elements with .select2 class, EXCEPT master_building_status
            $('.select2').not('#master_building_status').select2({
                placeholder: 'Pilih atau ketik untuk mencari...',
                allowClear: true
            });
            initializeCustomerSelect2();
            console.log('Select2 initialized successfully on', $('.select2').length, 'elements');
            return true;
        } else {
            console.log('Select2 not ready, retrying...');
            return false;
        }
    }

    function initializeCustomerSelect2() {
        const customerSelect = $('#customer_id');
        if (!customerSelect.length || typeof $.fn.select2 === 'undefined') {
            return;
        }

        if (customerSelect.hasClass('select2-hidden-accessible')) {
            customerSelect.select2('destroy');
        }

        customerSelect.select2({
            placeholder: 'Pilih atau ketik untuk mencari...',
            allowClear: true,
            minimumInputLength: 0,
            ajax: {
                url: '{{ route("marketing.surveys.wizard.get-customers") }}',
                dataType: 'json',
                delay: 250,
                cache: false,
                data: function(params) {
                    return { q: params.term || '' };
                },
                processResults: function(data) {
                    return {
                        results: $.map(data || [], function(customer) {
                            return {
                                id: customer.id,
                                text: customer.text || customer.name,
                                company_type: customer.company_type || '',
                            };
                        })
                    };
                }
            }
        });
    }
    
    // Wait for Select2 to be available
    function waitForSelect2(callback, maxRetries = 10) {
        let retries = 0;
        const checkSelect2 = () => {
            if (typeof $.fn.select2 !== 'undefined') {
                console.log('Select2 is now available');
                callback();
            } else if (retries < maxRetries) {
                retries++;
                console.log('Waiting for Select2... attempt ' + retries);
                setTimeout(checkSelect2, 100);
            } else {
                console.error('Select2 failed to load after ' + maxRetries + ' attempts');
            }
        };
        checkSelect2();
    }
    
    // Start initialization
    waitForSelect2(() => {
        initializeSelect2();
        // Load all buildings for Step 4 dropdown
        loadAllBuildings();
    });
    
    // Fallback: If Select2 fails to load after 3 seconds, remove select2 class
    setTimeout(() => {
        if (typeof $.fn.select2 === 'undefined') {
            console.log('Select2 failed to load, removing select2 class from elements');
            $('.select2').removeClass('select2').addClass('form-control');
        }
    }, 3000);
    
    // Function to load buildings based on customer
    function loadAllBuildings() {
        console.log('Loading buildings for Step 4...');
        
        // Determine customer_id based on current form state
        let customerId = null;
        const addNewCustomer = $('#add_new_customer').is(':checked');
        
        if (!addNewCustomer) {
            // Existing customer selected
            customerId = $('#customer_id').val();
        } else {
            // New customer - use 'all' to show all buildings
            customerId = 'all';
        }
        
        console.log('🏢 Loading buildings for customer_id:', customerId, 'addNewCustomer:', addNewCustomer);
        
        $.get('{{ route("marketing.surveys.wizard.get-buildings") }}', {customer_id: customerId})
            .done(function(data) {
                console.log('✅ Buildings loaded for customer:', customerId, 'Count:', data.length, 'Data:', data);
                $('#new_building_name').empty().append('<option value="">Pilih atau ketik disini..</option>');
                $.each(data, function(index, building) {
                    // Use nama_gedung if name is null/empty
                    const buildingName = building.name || building.nama_gedung || 'Unnamed Building';
                    const option = $('<option value="' + building.id + '">' + buildingName + '</option>');
                    
                    // Add data attributes for summary display
                    option.attr('data-address1', building.alamat_1 || '');
                    option.attr('data-address2', building.alamat_2 || '');
                    option.attr('data-province', building.province ? building.province.name : '');
                    option.attr('data-city', building.city ? building.city.name : '');
                    option.attr('data-district', building.district ? building.district.name : '');
                    option.attr('data-subdistrict', building.subdistrict ? building.subdistrict.name : '');
                    option.attr('data-postal-code', building.kode_pos || '');
                    option.attr('data-phone1', building.phone_1 || '');
                    option.attr('data-phone2', building.phone_2 || '');
                    
                    $('#new_building_name').append(option);
                });
                
                // Reinitialize Select2 for new building dropdown
                if (typeof $.fn.select2 !== 'undefined') {
                    if ($('#new_building_name').hasClass('select2-hidden-accessible')) {
                        $('#new_building_name').select2('destroy');
                    }
                    $('#new_building_name').select2({
                        placeholder: 'Pilih atau ketik untuk mencari...',
                        allowClear: true
                    });
                    console.log('New building dropdown Select2 reinitialized');
                    
                    // Also reinitialize new_building_type
                    if ($('#new_building_type').length > 0) {
                        if ($('#new_building_type').hasClass('select2-hidden-accessible')) {
                            $('#new_building_type').select2('destroy');
                        }
                        $('#new_building_type').select2({
                            placeholder: 'Pilih atau ketik untuk mencari...',
                            allowClear: true
                        });
                        console.log('✅ new_building_type Select2 reinitialized');
                    }
                }
            })
            .fail(function(xhr, status, error) {
                console.error('Error loading all buildings:', error);
            });
    }

    // Step navigation with validation
    $(document).on('click', '#next-btn', function(e) {
        e.preventDefault();
        console.log('Next clicked, current step:', currentStep);
        
        // Validate current step before proceeding
        if (!validateCurrentStep()) {
            return false;
        }
        
        if (currentStep < totalSteps) {
            // Hide current step
            $('#step-' + currentStep).hide();
            
            // Show next step
            currentStep++;
            $('#step-' + currentStep).show();
            
            // Update sidebar
            updateSidebar();
            
            // Reinitialize Select2 for new step
            setTimeout(function() {
                initializeSelect2();
                
                // Load buildings for Step 4
                if (currentStep === 4) {
                    console.log('Step 4: Loading all buildings...');
                    loadAllBuildings();
                    
                    // Initialize Select2 for new_building_type specifically
                    if ($('#new_building_type').length > 0 && typeof $.fn.select2 !== 'undefined') {
                        if ($('#new_building_type').hasClass('select2-hidden-accessible')) {
                            $('#new_building_type').select2('destroy');
                        }
                        $('#new_building_type').select2({
                            placeholder: 'Pilih atau ketik untuk mencari...',
                            allowClear: true
                        });
                        console.log('✅ Select2 initialized for new_building_type');
                    }
                }
            }, 100);
            
            console.log('Moved to step:', currentStep);
        }
    });

    // Previous button
    $(document).on('click', '#prev-btn', function(e) {
        e.preventDefault();
        console.log('Previous clicked, current step:', currentStep);
        
        if (currentStep > 1) {
            // Hide current step
            $('#step-' + currentStep).hide();
            
            // Show previous step
            currentStep--;
            $('#step-' + currentStep).show();
            
            // Update sidebar
            updateSidebar();
            
            // Reinitialize Select2 for new step
            setTimeout(function() {
                initializeSelect2();
            }, 100);
            
            console.log('Moved to step:', currentStep);
        }
    });

    // Update sidebar active states
    function updateSidebar() {
        console.log('Updating sidebar for step:', currentStep);
        
        $('.step').removeClass('active completed');
        
        // Mark previous steps as completed
        for (let i = 1; i < currentStep; i++) {
            $('.step[data-step="' + i + '"]').addClass('completed');
        }
        
        // Mark current step as active
        $('.step[data-step="' + currentStep + '"]').addClass('active');
        
        // Update step number in header
        $('#current-step-number').text(currentStep);
        
        // Show/hide navigation buttons
        if (currentStep === 1) {
            $('#prev-btn').hide();
        } else {
            $('#prev-btn').show();
        }
        
        if (currentStep === totalSteps) {
            $('#next-btn').hide();
            $('#save-btn').hide();
            $('#save-draft-btn').show();
            $('#finalize-btn').show();
            // Populate summary data
            populateSummary();
        } else {
            $('#next-btn').show();
            $('#save-btn').hide();
            $('#save-draft-btn').hide();
            $('#finalize-btn').hide();
        }
        
        console.log('Sidebar updated');
    }

    // Validate current step
    function validateCurrentStep() {
        let isValid = true;
        
        // Clear previous error states
        $('.form-control').removeClass('is-invalid');
        $('.invalid-feedback').remove();
        
        if (currentStep === 1) {
            // Step 1: Marketing Staff and Survey Date validation
            if (!$('#marketing_staff_id').val()) {
                showFieldError('#marketing_staff_id', 'Marketing Staff harus dipilih');
                isValid = false;
            }
            if (!$('#survey_date').val()) {
                showFieldError('#survey_date', 'Tanggal Survey harus diisi');
                isValid = false;
            }
        }
        
        else if (currentStep === 2) {
            // Step 2: Customer validation
            const addNewCustomer = $('#add_new_customer').is(':checked');
            
            if (!addNewCustomer && !$('#customer_id').val()) {
                showFieldError('#customer_id', 'Customer harus dipilih atau buat customer baru');
                isValid = false;
            }
            
            if (addNewCustomer) {
                if (!$('#new_customer_name').val().trim()) {
                    showFieldError('#new_customer_name', 'Nama Customer harus diisi');
                    isValid = false;
                }
                if (!$('#new_company_type').val()) {
                    showFieldError('#new_company_type', 'Jenis Company harus dipilih');
                    isValid = false;
                }
            }
        }
        
        else if (currentStep === 3) {
            // Step 3: Contact validation
            const addNewContact = $('#add_new_contact').is(':checked');
            
            if (!addNewContact && !$('#contact_id').val()) {
                showFieldError('#contact_id', 'Contact harus dipilih atau buat contact baru');
                isValid = false;
            }
            
            if (addNewContact) {
                if (!$('#new_contact_name').val().trim()) {
                    showFieldError('#new_contact_name', 'Nama Contact harus diisi');
                    isValid = false;
                }
                
                const email = $('#new_contact_email').val().trim();
                if (!email) {
                    showFieldError('#new_contact_email', 'Email harus diisi');
                    isValid = false;
                } else if (!isValidEmail(email)) {
                    showFieldError('#new_contact_email', 'Format email tidak valid (contoh: user@domain.com)');
                    isValid = false;
                }
                
                if (!$('#new_contact_phone').val().trim()) {
                    showFieldError('#new_contact_phone', 'Phone harus diisi');
                    isValid = false;
                }
                
                if (!$('#new_contact_position').val()) {
                    showFieldError('#new_contact_position', 'Jabatan/Posisi harus dipilih');
                    isValid = false;
                }
            }
        }
        
        else if (currentStep === 4) {
            // Step 4: Building validation
            const buildingId = $('#building_id').val();
            const addNewBuilding = $('#add_new_building').is(':checked');
            
            console.log('Step 4 validation - building_id value:', buildingId);
            console.log('Step 4 validation - add_new_building checked:', addNewBuilding);
            console.log('Step 4 validation - building_id element:', $('#building_id')[0]);
            
            // Check if building is selected OR if adding new building
            if (!buildingId && !addNewBuilding) {
                console.log('Step 4 validation failed - no building selected and not adding new');
                showFieldError('#building_id', 'Building harus dipilih atau tambahkan building baru');
                isValid = false;
            } else if (addNewBuilding) {
                // Validate new building fields
                if (!$('#new_building_name').val().trim()) {
                    showFieldError('#new_building_name', 'Nama Gedung harus diisi');
                    isValid = false;
                }
                if (!$('#new_building_type').val()) {
                    showFieldError('#new_building_type', 'Jenis Alamat harus dipilih');
                    isValid = false;
                }
                if (!$('#new_building_location_detail').val().trim()) {
                    showFieldError('#new_building_location_detail', 'Lokasi Detail harus diisi');
                    isValid = false;
                }
            } else {
                console.log('Step 4 validation passed - building selected:', buildingId);
            }
        }
        
        else if (currentStep === 5) {
            // Step 5: Rooms validation
            if ($('.room-card').length === 0) {
                alert('Minimal harus ada 1 ruangan yang ditambahkan');
                isValid = false;
            }
        }
        
        return isValid;
    }
    
    // Show field error
    function showFieldError(fieldSelector, message) {
        const $field = $(fieldSelector);
        $field.addClass('is-invalid');
        
        // Remove existing error message
        $field.siblings('.invalid-feedback').remove();
        
        // Add error message
        $field.after('<div class="invalid-feedback d-block">' + message + '</div>');
        
        // Focus on first invalid field
        if ($('.is-invalid').length === 1) {
            $field.focus();
        }
    }
    
    // Email validation function
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    // Real-time email validation
    $(document).on('blur', '#new_contact_email', function() {
        const email = $(this).val().trim();
        if (email && !isValidEmail(email)) {
            showFieldError('#new_contact_email', 'Format email tidak valid (contoh: user@domain.com)');
        } else {
            $(this).removeClass('is-invalid');
            $(this).siblings('.invalid-feedback').remove();
        }
    });
    
    // Real-time validation for required fields
    $(document).on('blur', 'input[required], select[required]', function() {
        const $field = $(this);
        if (!$field.val().trim()) {
            const label = $field.closest('.form-group').find('label').text().replace('*', '').trim();
            showFieldError('#' + $field.attr('id'), label + ' harus diisi');
        } else {
            $field.removeClass('is-invalid');
            $field.siblings('.invalid-feedback').remove();
        }
    });

    // Populate summary data
    function populateSummary() {
        console.log('Populating summary data...');
        
        // Get form data
        const marketingStaff = $('#marketing_staff_id option:selected').text();
        const surveyDate = $('#survey_date').val() ? formatDateTo3Digit(new Date($('#survey_date').val())) : '-';
        
        // Check if using new customer or existing customer
        const isNewCustomer = $('#add_new_customer').is(':checked');
        let customerName, customerType;
        
        if (isNewCustomer) {
            customerName = $('#new_customer_name').val();
            customerType = $('#new_company_type option:selected').text();
        } else {
            customerName = $('#customer_id option:selected').text();
            customerType = $('#customer_id option:selected').data('company-type') || '-';
        }
        
        // Check if using new contact or existing contact
        const isNewContact = $('#add_new_contact').is(':checked');
        let contactName, contactEmail, contactPhone1, contactPosition;
        
        if (isNewContact) {
            contactName = $('#new_contact_name').val();
            contactEmail = $('#new_contact_email').val();
            contactPhone1 = $('#new_contact_phone').val();
            contactPosition = $('#new_contact_position option:selected').text();
        } else {
            contactName = $('#contact_id option:selected').text();
            contactEmail = $('#contact_id option:selected').data('email') || '';
            contactPhone1 = $('#contact_id option:selected').data('phone1') || '';
            contactPosition = $('#contact_id option:selected').data('position') || '';
        }
        // Check if using new building or existing building
        const isNewBuilding = $('#add_new_building').is(':checked');
        let buildingName, address1, address2, province, city, district, subdistrict, postalCode;
        
        if (isNewBuilding) {
            // Check if building is selected from dropdown (existing building) or new building form
            const selectedBuildingId = $('#new_building_name').val();
            if (selectedBuildingId) {
                // Building selected from dropdown - use data attributes
                buildingName = $('#new_building_name option:selected').text() || '';
                address1 = $('#new_building_name option:selected').data('address1') || '';
                address2 = $('#new_building_name option:selected').data('address2') || '';
                province = $('#new_building_name option:selected').data('province') || '';
                city = $('#new_building_name option:selected').data('city') || '';
                district = $('#new_building_name option:selected').data('district') || '';
                subdistrict = $('#new_building_name option:selected').data('subdistrict') || '';
                postalCode = $('#new_building_name option:selected').data('postal-code') || '';
            } else {
                // New building form - use form fields
                buildingName = $('#new_building_name option:selected').text() || $('#new_building_name').val();
                address1 = $('#new_building_location_detail').val();
                address2 = '';
                province = $('#master_building_province option:selected').text();
                city = $('#master_building_city option:selected').text();
                district = $('#master_building_district option:selected').text();
                subdistrict = $('#master_building_subdistrict option:selected').text();
                postalCode = $('#master_building_postal_code').val();
            }
        } else {
            // Use existing building data
            buildingName = $('#building_id option:selected').text();
            address1 = $('#building_id option:selected').data('address1') || '';
            address2 = $('#building_id option:selected').data('address2') || '';
            province = $('#building_id option:selected').data('province') || '';
            city = $('#building_id option:selected').data('city') || '';
            district = $('#building_id option:selected').data('district') || '';
            subdistrict = $('#building_id option:selected').data('subdistrict') || '';
            postalCode = $('#building_id option:selected').data('postal-code') || '';
        }
        
        // Debug logging
        console.log('Summary data debug:');
        console.log('isNewCustomer:', isNewCustomer);
        console.log('isNewContact:', isNewContact);
        console.log('isNewBuilding:', isNewBuilding);
        console.log('customerName:', customerName);
        console.log('customerType:', customerType);
        console.log('contactName:', contactName);
        console.log('buildingName:', buildingName);
        console.log('address1:', address1);
        console.log('province:', province);
        
        // Populate survey data
        $('#summary-marketing-name').text(marketingStaff || '-');
        $('#summary-survey-date').text(surveyDate || '-');
        $('#summary-building-name').text(buildingName || '-');
        
        // Get location detail from form - check both fields
        const locationDetail = $('#building_location_detail').val() || $('#new_building_location_detail').val() || '-';
        $('#summary-location-detail').text(locationDetail);
        
        $('#summary-address1').text(address1 || '-');
        $('#summary-address2').text(address2 || '-');
        $('#summary-province').text(province || '-');
        $('#summary-city').text(city || '-');
        $('#summary-district').text(district || '-');
        $('#summary-subdistrict').text(subdistrict || '-');
        $('#summary-postal-code').text(postalCode || '-');
        
        // Populate customer data
        $('#summary-customer-name').text(customerName || '-');
        $('#summary-contact-name').text(contactName || '-');
        $('#summary-contact-email').text(contactEmail || '-');
        $('#summary-contact-phone1').text(contactPhone1 || '-');
        $('#summary-contact-position').text(contactPosition || '-');
        
        // Populate rooms table
        populateRoomsSummary();
    }
    
    // Populate rooms summary table
    function populateRoomsSummary() {
        const tbody = $('#summary-rooms-tbody');
        tbody.empty();
        
        if (rooms.length === 0) {
            tbody.append('<tr><td colspan="3" class="text-center text-muted">Tidak ada ruangan yang ditambahkan</td></tr>');
            return;
        }
        
        rooms.forEach((room, index) => {
            const roomName = `
                <div class="room-name-container">
                    <div class="room-name-main">${room.name}</div>
                    <div class="room-name-details">
                        <span class="badge badge-secondary mr-1">${room.type}</span>
                        <span class="badge badge-info mr-1">${room.floor}</span>
                    </div>
                    <div class="room-name-remark">
                        <small class="text-muted">Remark: ${room.remark || '-'}</small>
                    </div>
                </div>
            `;
            const specification = `
                <div class="specification-container">
                    <div class="spec-item">
                        <span class="spec-label">Wangi:</span>
                        <span class="spec-value">${room.intensity}</span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">Installation Type:</span>
                        <span class="spec-value">${room.installation_type}</span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">Dimensi:</span>
                        <span class="spec-value">${room.length} × ${room.width} × ${room.height}</span>
                    </div>
                </div>
            `;
            
            const row = `
                <tr>
                    <td class="text-center">${index + 1}</td>
                    <td>${roomName}</td>
                    <td>${specification}</td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    // Add new customer checkbox handler
    $('#add_new_customer').change(function() {
        console.log('Add new customer checkbox changed:', $(this).is(':checked'));
        if ($(this).is(':checked')) {
            console.log('Showing new customer fields');
            $('#new_customer_fields').show();
            $('#customer_id').closest('.form-group').hide();
            $('#customer_id').prop('required', false);
            $('#new_company_type').prop('required', true);
            $('#new_customer_name').prop('required', true);
            
            // Debug: Check if field is visible and clickable
            setTimeout(function() {
                console.log('New customer name field visible:', $('#new_customer_name').is(':visible'));
                console.log('New customer name field enabled:', $('#new_customer_name').prop('disabled'));
                console.log('New customer name field readonly:', $('#new_customer_name').prop('readonly'));
            }, 100);
        } else {
            console.log('Hiding new customer fields');
            $('#new_customer_fields').hide();
            $('#customer_id').closest('.form-group').show();
            $('#customer_id').prop('required', true);
            $('#new_company_type').prop('required', false);
            $('#new_customer_name').prop('required', false);
        }

        // Fix: Clear contact list when switching between existing customer and new customer
        console.log('Clearing contact list due to customer mode change');
        $('#contact_id').empty().append('<option value="">Pilih atau buat contact baru..</option>');
        if (typeof $.fn.select2 !== 'undefined' && $('#contact_id').hasClass('select2-hidden-accessible')) {
            $('#contact_id').trigger('change');
        }

        // Auto-check 'Add New Contact' if 'Add New Customer' is checked
        if ($(this).is(':checked')) {
            if (!$('#add_new_contact').is(':checked')) {
                $('#add_new_contact').prop('checked', true).trigger('change');
                console.log('Auto-checked add_new_contact because add_new_customer is checked');
            }
        }
    });

    // Debug: Add click handler for new customer name field
    $(document).on('click', '#new_customer_name', function() {
        console.log('New customer name field clicked');
        console.log('Field value:', $(this).val());
        console.log('Field visible:', $(this).is(':visible'));
        console.log('Field enabled:', !$(this).prop('disabled'));
    });

    // Debug: Add focus handler for new customer name field
    $(document).on('focus', '#new_customer_name', function() {
        console.log('New customer name field focused');
        $(this).css('background-color', '#fff3cd');
    });

    // Debug: Add blur handler for new customer name field
    $(document).on('blur', '#new_customer_name', function() {
        console.log('New customer name field blurred');
        $(this).css('background-color', '');
    });

    // Custom date picker with 3-digit month format
    function formatDateTo3Digit(date) {
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(3, '0'); // 3-digit month
        const year = date.getFullYear();
        return `${day}/${month}/${year}`;
    }

    function parseDate3Digit(dateString) {
        // Parse DD/MMM/YYYY format where MMM is 3-digit month
        const parts = dateString.split('/');
        if (parts.length === 3) {
            const day = parseInt(parts[0]);
            const month = parseInt(parts[1]) - 1; // JavaScript months are 0-based
            const year = parseInt(parts[2]);
            return new Date(year, month, day);
        }
        return new Date();
    }

    // Simple date change handler
    $('#survey_date').on('change', function() {
        const selectedDate = $(this).val();
        $('#survey_date_hidden').val(selectedDate);
    });

    // Initialize with current date in ISO format for date input
    $(document).ready(function() {
        const today = new Date();
        const isoDate = today.toISOString().split('T')[0];
        $('#survey_date').val(isoDate);
        $('#survey_date_hidden').val(isoDate);
    });


    // Add new contact checkbox handler
    $('#add_new_contact').change(function() {
        if ($(this).is(':checked')) {
            $('#new_contact_fields').show();
            $('#contact_id').closest('.form-group').hide();
            $('#contact_id').prop('required', false);
            $('#new_contact_salutation').prop('required', true);
            $('#new_contact_name').prop('required', true);
            $('#new_contact_email').prop('required', true);
            $('#new_contact_phone').prop('required', true);
            $('#new_contact_position').prop('required', true);
            
            // Initialize Select2 for salutation if not already initialized
            if ($('#new_contact_salutation').length > 0 && typeof $.fn.select2 !== 'undefined') {
                if (!$('#new_contact_salutation').hasClass('select2-hidden-accessible')) {
                    $('#new_contact_salutation').select2({
                        placeholder: 'Pilih Panggilan',
                        allowClear: true
                    });
                }
            }
        } else {
            $('#new_contact_fields').hide();
            $('#contact_id').closest('.form-group').show();
            $('#contact_id').prop('required', true);
            $('#new_contact_salutation').prop('required', false);
            $('#new_contact_name').prop('required', false);
            $('#new_contact_email').prop('required', false);
            $('#new_contact_phone').prop('required', false);
            $('#new_contact_position').prop('required', false);
        }
    });

    // Add new building checkbox handler
    $('#add_new_building').change(function() {
        if ($(this).is(':checked')) {
            $('#new_building_fields').show();
            $('#existing_address_section').hide();
            $('#building_id').prop('required', false);
            $('#new_building_name').prop('required', true);
            $('#new_building_type').prop('required', true);
            $('#new_building_location_detail').prop('required', true);
            loadAllBuildings();
            
            // Initialize Select2 for new_building_type if not already initialized
            if ($('#new_building_type').length > 0 && typeof $.fn.select2 !== 'undefined') {
                if (!$('#new_building_type').hasClass('select2-hidden-accessible')) {
                    $('#new_building_type').select2({
                        placeholder: 'Pilih atau ketik untuk mencari...',
                        allowClear: true
                    });
                    console.log('✅ Select2 initialized for new_building_type on checkbox check');
                }
            }
        } else {
            $('#new_building_fields').hide();
            $('#existing_address_section').show();
            $('#building_id').prop('required', true);
            $('#new_building_name').prop('required', false);
            $('#new_building_type').prop('required', false);
            $('#new_building_location_detail').prop('required', false);
        }
    });

    // Customer change handler
    $('#customer_id').on('select2:select', function(e) {
        const selected = e.params.data || {};
        if (selected.element && selected.company_type) {
            $(selected.element).attr('data-company-type', selected.company_type);
        }
    });

    $('#customer_id').change(function() {
        const customerId = $(this).val();
        console.log('Customer changed, customerId:', customerId);
        if (customerId) {
            // Load contacts
            console.log('Loading contacts for customer:', customerId);
            $('#contact_id').empty().append('<option value="">Loading contacts...</option>');
            
            console.log('Making request to:', '{{ route("marketing.surveys.wizard.get-contacts") }}');
            $.get('{{ route("marketing.surveys.wizard.get-contacts") }}', {customer_id: customerId})
                .done(function(data) {
                    console.log('Contacts loaded:', data);
                    $('#contact_id').empty().append('<option value="">Pilih Contact Person</option>');
                    
                    if (data && data.length > 0) {
                        $.each(data, function(index, contact) {
                            const option = $('<option value="' + contact.id + '">' + contact.name + '</option>');
                            
                            // Add data attributes for summary
                            option.attr('data-email', contact.email || '');
                            option.attr('data-phone1', contact.phone || '');
                            option.attr('data-position', contact.position || '');
                            
                            $('#contact_id').append(option);
                        });
                        console.log('Contacts populated:', data.length, 'contacts');
                        
                        // Reinitialize Select2 for contact dropdown
                        if (typeof $.fn.select2 !== 'undefined') {
                            if ($('#contact_id').hasClass('select2-hidden-accessible')) {
                                $('#contact_id').select2('destroy');
                            }
                            $('#contact_id').select2({
                                placeholder: 'Pilih atau ketik untuk mencari...',
                                allowClear: true
                            });
                            console.log('Contact dropdown Select2 reinitialized');
                        }
                    } else {
                        $('#contact_id').append('<option value="" disabled>Belum ada contact untuk customer ini</option>');
                        console.log('No contacts found for this customer');
                        
                        // Reinitialize Select2 for contact dropdown
                        if (typeof $.fn.select2 !== 'undefined') {
                            if ($('#contact_id').hasClass('select2-hidden-accessible')) {
                                $('#contact_id').select2('destroy');
                            }
                            $('#contact_id').select2({
                                placeholder: 'Pilih atau ketik untuk mencari...',
                                allowClear: true
                            });
                            console.log('Contact dropdown Select2 reinitialized');
                        }
                    }
                })
                .fail(function(xhr, status, error) {
                    console.error('Error loading contacts:', error);
                    console.error('Response:', xhr.responseText);
                    $('#contact_id').empty().append('<option value="">Error loading contacts</option>');
                });

            // Load buildings
            $.get('{{ route("marketing.surveys.wizard.get-buildings") }}', {customer_id: customerId})
                .done(function(data) {
                    $('#building_id').empty().append('<option value="">Pilih Building</option>');
                    $.each(data, function(index, building) {
                        // Use nama_gedung if name is null/empty
                        const buildingName = building.name || building.nama_gedung || 'Unnamed Building';
                        const option = $('<option value="' + building.id + '">' + buildingName + '</option>');
                        
                        // Add data attributes for summary
                        option.attr('data-address1', building.alamat_1 || '');
                        option.attr('data-address2', building.alamat_2 || '');
                        option.attr('data-province', building.province ? building.province.name : '');
                        option.attr('data-city', building.city ? building.city.name : '');
                        option.attr('data-district', building.district ? building.district.name : '');
                        option.attr('data-subdistrict', building.subdistrict ? building.subdistrict.name : '');
                        option.attr('data-postal-code', building.kode_pos || '');
                        option.attr('data-phone1', building.phone_1 || '');
                        option.attr('data-phone2', building.phone_2 || '');
                        option.attr('data-province-id', building.province_id || '');
                        option.attr('data-city-id', building.city_id || '');
                        option.attr('data-district-id', building.district_id || '');
                        option.attr('data-subdistrict-id', building.subdistrict_id || '');
                        
                        $('#building_id').append(option);
                    });
                    
                    // Reinitialize Select2 for building dropdown
                    if (typeof $.fn.select2 !== 'undefined') {
                        if ($('#building_id').hasClass('select2-hidden-accessible')) {
                            $('#building_id').select2('destroy');
                        }
                        $('#building_id').select2({
                            placeholder: 'Pilih atau ketik untuk mencari...',
                            allowClear: true
                        });
                        console.log('Building dropdown Select2 reinitialized');
                    }
                });
        }
    });

    // Contact change handler
    $('#contact_id').change(function() {
        const contactId = $(this).val();
        if (contactId) {
            // Load contact details
            $.get('{{ route("marketing.surveys.wizard.get-contacts") }}', {contact_id: contactId})
                .done(function(data) {
                    if (data.length > 0) {
                        $('#contact_phone').val(data[0].phone || '');
                    }
                });
        }
    });

    // Building change handler
    $('#building_id').change(function() {
        const buildingId = $(this).val();
        console.log('Building change handler - buildingId:', buildingId);
        
        if (buildingId) {
            // Load building details and display them
            $.get('{{ route("marketing.surveys.wizard.get-buildings") }}', {customer_id: $('#customer_id').val()})
                .done(function(data) {
                    const selectedBuilding = data.find(function(building) {
                        return building.id == buildingId;
                    });
                    
                    if (selectedBuilding) {
                        console.log('Auto-filling from selected building:', selectedBuilding);
                        displaySelectedAddress(selectedBuilding);
                    }
                })
                .fail(function(xhr, status, error) {
                    console.error('Error loading building details:', error);
                });
        } else {
            hideSelectedAddress();
        }
    });
    
    // Also handle Select2 select event
    $('#building_id').on('select2:select', function(e) {
        // Add null check to prevent error
        if (e.params && e.params.data) {
            const buildingId = e.params.data.id;
            console.log('Select2 select event - buildingId:', buildingId);
            console.log('Select2 select event - current val before:', $(this).val());
            $(this).val(buildingId).trigger('change');
            console.log('Select2 select event - current val after:', $(this).val());
        } else {
            console.log('Select2 select event - params or data is undefined');
        }
    });
    
    // Debug Select2 initialization
    $('#building_id').on('select2:open', function() {
        console.log('Select2 opened for building_id');
    });
    
    $('#building_id').on('select2:close', function() {
        console.log('Select2 closed for building_id, current value:', $(this).val());
    });

    // New building dropdown change handler (Step 4)
    $('#new_building_name').change(function() {
        const buildingId = $(this).val();
        if (buildingId) {
            // Get building data from option attributes
            const selectedOption = $(this).find('option:selected');
            const buildingData = {
                id: buildingId,
                name: selectedOption.text(),
                nama_gedung: selectedOption.text(),
                alamat_1: selectedOption.data('address1') || '',
                alamat_2: selectedOption.data('address2') || '',
                phone_1: selectedOption.data('phone1') || '',
                phone_2: selectedOption.data('phone2') || '',
                fax: selectedOption.data('fax') || '',
                province_id: selectedOption.data('province-id') || '',
                city_id: selectedOption.data('city-id') || '',
                district_id: selectedOption.data('district-id') || '',
                subdistrict_id: selectedOption.data('subdistrict-id') || '',
                kode_pos: selectedOption.data('postal-code') || ''
            };
            
            console.log('🏢 Auto-filling from selected building:', buildingData);
            
            // Auto-fill form fields
            $('#new_building_address1').val(buildingData.alamat_1);
            $('#new_building_address2').val(buildingData.alamat_2);
            $('#new_building_phone1').val(buildingData.phone_1);
            $('#new_building_phone2').val(buildingData.phone_2);
            $('#new_building_fax').val(buildingData.fax);
            
            // Auto-fill location data
            if (buildingData.province_id) {
                $('#master_building_province').val(buildingData.province_id).trigger('change');
            }
            if (buildingData.city_id) {
                $('#master_building_city').val(buildingData.city_id).trigger('change');
            }
            if (buildingData.district_id) {
                $('#master_building_district').val(buildingData.district_id).trigger('change');
            }
            if (buildingData.subdistrict_id) {
                $('#master_building_subdistrict').val(buildingData.subdistrict_id).trigger('change');
            }
            if (buildingData.kode_pos) {
                $('#master_building_postal_code').val(buildingData.kode_pos);
            }
        } else {
            // Clear form fields when no building selected
            $('#new_building_location_detail').val('');
            $('#new_building_phone1').val('');
            $('#new_building_phone2').val('');
            $('#new_building_fax').val('');
            $('#building_location_detail').val('');
            $('#master_building_province').val('').trigger('change');
            $('#master_building_city').val('').trigger('change');
            $('#master_building_district').val('').trigger('change');
            $('#master_building_subdistrict').val('').trigger('change');
            $('#master_building_postal_code').val('');
        }
    });
    
    // Handle change address button
    $('#change_address_btn').on('click', function() {
        hideSelectedAddress();
        $('#building_id').val('').trigger('change');
    });
    
    // Function to display selected address details
    function displaySelectedAddress(building) {
        $('#selected_building_name').text(building.name || building.nama_gedung || 'Nama Gedung Tidak Tersedia');
        $('#selected_building_address').text(building.address || building.alamat_1 || 'Alamat Tidak Tersedia');
        $('#selected_building_postal').text(building.postal_code || building.kode_pos || 'Kode Pos Tidak Tersedia');
        $('#selected_building_phone').text(building.phone_1 || 'Telepon Tidak Tersedia');
        
        // AUTO-FILL FORM FIELDS with building data
        $('#new_building_name').val(building.name || building.nama_gedung || '');
        $('#new_building_address1').val(building.alamat_1 || building.address || '');
        $('#new_building_address2').val(building.alamat_2 || '');
        $('#new_building_phone1').val(building.phone_1 || '');
        $('#new_building_phone2').val(building.phone_2 || '');
        $('#new_building_fax').val(building.fax || '');
        
        // Auto-fill location data if available
        if (building.province_id) {
            $('#master_building_province').val(building.province_id).trigger('change');
        }
        if (building.city_id) {
            $('#master_building_city').val(building.city_id).trigger('change');
        }
        if (building.district_id) {
            $('#master_building_district').val(building.district_id).trigger('change');
        }
        if (building.subdistrict_id) {
            $('#master_building_subdistrict').val(building.subdistrict_id).trigger('change');
        }
        if (building.kode_pos) {
            $('#master_building_postal_code').val(building.kode_pos);
        }
        
        $('#selected_address_display').show();
        $('#building_id').closest('.form-group').hide();
        
        console.log('🏢 Building data auto-filled:', {
            name: building.name || building.nama_gedung,
            address1: building.alamat_1,
            address2: building.alamat_2,
            phone1: building.phone_1,
            phone2: building.phone_2,
            fax: building.fax
        });
    }
    
    // Function to hide selected address details
    function hideSelectedAddress() {
        $('#selected_address_display').hide();
        $('#building_id').closest('.form-group').show();
    }

    // Save survey
    $('#save-btn').click(function() {
        // Validate rooms
        if (rooms.length === 0) {
            alert('Mohon tambahkan minimal 1 ruangan!');
            return;
        }

        const formData = {
            marketing_id: $('#marketing_id').val(),
            survey_date: $('#survey_date').val(),
            customer_id: $('#customer_id').val(),
            add_new_customer: $('#add_new_customer').is(':checked'),
            new_company_type: $('#new_company_type').val(),
            new_customer_name: $('#new_customer_name').val(),
            contact_id: $('#contact_id').val(),
            add_new_contact: $('#add_new_contact').is(':checked'),
            new_contact_salutation: $('#new_contact_salutation').val(),
            new_contact_name: $('#new_contact_name').val(),
            new_contact_email: $('#new_contact_email').val(),
            new_contact_phone: $('#new_contact_phone').val(),
            new_contact_position: $('#new_contact_position').val(),
            building_id: $('#building_id').val(),
            add_new_building: $('#add_new_building').is(':checked'),
            new_building_name: $('#new_building_name').val(),
            new_building_type: $('#new_building_type').val(),
            new_building_location_detail: $('#new_building_location_detail').val(),
            new_building_phone1: $('#new_building_phone1').val(),
            new_building_phone2: $('#new_building_phone2').val(),
            new_building_fax: $('#new_building_fax').val(),
            building_location_detail: $('#building_location_detail').val(),
            rooms: rooms,
            action: 'save_draft',
            _token: '{{ csrf_token() }}'
        };

        $.post('{{ route("marketing.surveys.wizard.save") }}', formData)
            .done(function(response) {
                if (response.success || response.status === 'success') {
                    alert('Survey berhasil disimpan!');
                    if (response.redirect_url) {
                        window.location.href = response.redirect_url;
                    } else if (response.survey_id) {
                        window.location.href = '{{ route("marketing.surveys.show", ":id") }}'.replace(':id', response.survey_id);
                    } else {
                        window.location.href = '{{ route("marketing.surveys.index") }}';
                    }
                } else {
                    alert('Error: ' + (response.message || 'Unknown error'));
                }
            })
            .fail(function() {
                alert('Terjadi kesalahan saat menyimpan survey');
            });
    });

// Function to clear master building form (defined early for use in multiple places)
function clearMasterBuildingForm() {
    $('#master_building_name').val('');
    $('#master_building_address1').val('');
    $('#master_building_address2').val('');
    
    // Reset status to "Aktif" (value="1") - ensure options exist first
    const statusSelect = $('#master_building_status');
    if (statusSelect.find('option[value="1"]').length === 0) {
        // If options don't exist, add them
        statusSelect.html('<option value="">Pilih Status</option><option value="1">Aktif</option><option value="0">Tidak Aktif</option>');
    }
    // Set value without trigger to avoid Select2 close error
    statusSelect.val('1');
    // Only trigger change if it's not using Select2
    if (!statusSelect.hasClass('select2-hidden-accessible')) {
        statusSelect.trigger('change');
    }
    
    $('#master_building_province').val('').trigger('change');
    $('#master_building_city').val('').trigger('change');
    $('#master_building_district').val('').trigger('change');
    $('#master_building_subdistrict').val('').trigger('change');
    $('#master_building_postal_code').val('');
    console.log('✅ Master building form cleared');
}

// Master Building Modal
$(document).on('click', '#add_master_building_btn', function(e) {
    e.preventDefault();
    e.stopPropagation();
    console.log('Opening master building modal');
    
    // Ensure Status Aktif dropdown has options and is NOT using Select2
    const statusSelect = $('#master_building_status');
    if (statusSelect.length > 0) {
        // Destroy Select2 if it was initialized
        if (statusSelect.hasClass('select2-hidden-accessible')) {
            console.log('Destroying Select2 from Status Aktif before opening modal...');
            statusSelect.select2('destroy');
        }
        statusSelect.removeClass('select2');
        
        // Ensure options exist
        if (statusSelect.find('option').length < 3) {
            console.log('Restoring Status Aktif options before clearing...');
            statusSelect.html('<option value="">Pilih Status</option><option value="1" selected>Aktif</option><option value="0">Tidak Aktif</option>');
        }
    }
    
    // Clear all form fields before opening modal
    clearMasterBuildingForm();
    
    // Check if modal exists
    if ($('#masterBuildingModal').length === 0) {
        console.error('Modal #masterBuildingModal not found!');
        return;
    }
    
    // Try Bootstrap 5 API first (if available)
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        console.log('Using Bootstrap 5 Modal API');
        const modalElement = document.getElementById('masterBuildingModal');
        if (modalElement) {
            // Get or create modal instance
            let modalInstance = bootstrap.Modal.getInstance(modalElement);
            if (!modalInstance) {
                console.log('Creating new Bootstrap 5 Modal instance');
                modalInstance = new bootstrap.Modal(modalElement, {
                    backdrop: 'static',
                    keyboard: false
                });
            }
            // Show modal
            console.log('Showing modal with Bootstrap 5...');
            modalInstance.show();
            
            // Check if modal is visible after show
            setTimeout(function() {
                if (modalElement.classList.contains('show')) {
                    console.log('✅ Modal is visible (Bootstrap 5)');
                } else {
                    console.error('❌ Modal is NOT visible after show (Bootstrap 5)');
                    // Force show
                    modalElement.classList.add('show');
                    modalElement.style.display = 'block';
                    document.body.classList.add('modal-open');
                    if (!document.querySelector('.modal-backdrop')) {
                        const backdrop = document.createElement('div');
                        backdrop.className = 'modal-backdrop fade show';
                        document.body.appendChild(backdrop);
                    }
                }
            }, 100);
        } else {
            console.error('Modal element not found!');
        }
    } else {
        // Fallback to jQuery modal (Bootstrap 4 style)
        console.log('Using jQuery Modal (Bootstrap 4 style)');
        $('#masterBuildingModal').modal({
            backdrop: 'static',
            keyboard: false,
            show: true
        });
        $('#masterBuildingModal').modal('show');
        
        // Check if modal is visible after show
        setTimeout(function() {
            if ($('#masterBuildingModal').hasClass('show')) {
                console.log('✅ Modal is visible (jQuery)');
            } else {
                console.error('❌ Modal is NOT visible after show (jQuery)');
            }
        }, 100);
    }
    
    // Function to initialize Select2 in modal (defined in outer scope)
    const initializeModalSelect2 = function() {
        console.log('Initializing Select2 in modal...');
        
        // CRITICAL: Ensure Status Aktif dropdown does NOT use Select2
        const statusSelect = $('#master_building_status');
        if (statusSelect.length > 0) {
            // Destroy Select2 if it was initialized (with safe check)
            if (statusSelect.hasClass('select2-hidden-accessible')) {
                console.log('Destroying Select2 from Status Aktif dropdown...');
                try {
                    if (statusSelect.data('select2')) {
                        statusSelect.select2('destroy');
                    }
                } catch (e) {
                    console.warn('Error destroying Select2:', e);
                    // Force remove Select2 classes and elements
                    statusSelect.removeClass('select2-hidden-accessible');
                    statusSelect.next('.select2-container').remove();
                }
            }
            
            // Remove select2 class if exists
            statusSelect.removeClass('select2');
            
            // Ensure options exist
            if (statusSelect.find('option').length < 3) {
                console.log('Restoring Status Aktif options...');
                statusSelect.html('<option value="">Pilih Status</option><option value="1" selected>Aktif</option><option value="0">Tidak Aktif</option>');
            }
            
            // Set default value to "Aktif" if not set
            if (!statusSelect.val() || statusSelect.val() === '') {
                statusSelect.val('1');
            }
            
            console.log('Status Aktif dropdown ready (no Select2). Options:', statusSelect.find('option').length);
        }
        
        // Initialize province dropdown
        if ($('#master_building_province').length > 0) {
            if ($('#master_building_province').hasClass('select2-hidden-accessible')) {
                $('#master_building_province').select2('destroy');
            }
            $('#master_building_province').select2({
                placeholder: 'Pilih atau ketik untuk mencari...',
                allowClear: true,
                dropdownParent: $('#masterBuildingModal')
            });
        }
        
        // Initialize other dropdowns (NOT status - it's a simple dropdown)
        ['#master_building_city', '#master_building_district', '#master_building_subdistrict'].forEach(function(selector) {
            if ($(selector).length > 0) {
                if ($(selector).hasClass('select2-hidden-accessible')) {
                    $(selector).select2('destroy');
                }
                $(selector).select2({
                    placeholder: 'Pilih atau ketik untuk mencari...',
                    allowClear: true,
                    dropdownParent: $('#masterBuildingModal')
                });
            }
        });
        
        console.log('Select2 initialized for all dropdowns');
        console.log('Province options count:', $('#master_building_province option').length);
        console.log('Status Aktif options count:', statusSelect.find('option').length);
        
        // Final check: Ensure Status Aktif is NOT using Select2 and has options
        setTimeout(function() {
            const finalCheck = $('#master_building_status');
            if (finalCheck.hasClass('select2-hidden-accessible')) {
                console.warn('⚠️ Status Aktif still has Select2! Destroying...');
                try {
                    if (finalCheck.data('select2')) {
                        finalCheck.select2('destroy');
                    }
                } catch (e) {
                    console.warn('Error destroying Select2 in final check:', e);
                    // Force remove Select2 classes and elements
                    finalCheck.removeClass('select2-hidden-accessible');
                    finalCheck.next('.select2-container').remove();
                }
            }
            if (finalCheck.find('option').length === 0) {
                console.warn('⚠️ Status Aktif has no options! Restoring...');
                finalCheck.html('<option value="">Pilih Status</option><option value="1" selected>Aktif</option><option value="0">Tidak Aktif</option>');
                finalCheck.val('1');
            }
            console.log('✅ Final check - Status Aktif options:', finalCheck.find('option').length, 'Select2:', finalCheck.hasClass('select2-hidden-accessible') ? 'YES (ERROR!)' : 'NO (OK)');
        }, 500);
        
        // Add Select2 specific event handlers (use off to prevent duplicates)
        $('#master_building_province').off('select2:select').on('select2:select', function(e) {
            if (e.params && e.params.data) {
                console.log('Select2 province selected:', e.params.data);
                const provinceId = e.params.data.id;
                handleProvinceChange(provinceId);
            }
        });
        
        $('#master_building_city').off('select2:select').on('select2:select', function(e) {
            if (e.params && e.params.data) {
                console.log('Select2 city selected:', e.params.data);
                const cityId = e.params.data.id;
                handleCityChange(cityId);
            }
        });
        
        $('#master_building_district').off('select2:select').on('select2:select', function(e) {
            if (e.params && e.params.data) {
                console.log('Select2 district selected:', e.params.data);
                const districtId = e.params.data.id;
                handleDistrictChange(districtId);
            }
        });
    };
    
    // Initialize Select2 when modal is shown (Bootstrap 5 uses same event name)
    const modalElementForEvent = document.getElementById('masterBuildingModal');
    if (modalElementForEvent) {
        // Remove existing listeners to prevent duplicates
        modalElementForEvent.removeEventListener('shown.bs.modal', initializeModalSelect2);
        // Add new listener
        modalElementForEvent.addEventListener('shown.bs.modal', function() {
            console.log('Modal shown event triggered (Bootstrap 5), initializing Select2...');
            initializeModalSelect2();
            
            // CRITICAL: Prevent Select2 from being initialized on Status Aktif after modal is shown
            setTimeout(function() {
                const statusField = $('#master_building_status');
                if (statusField.hasClass('select2-hidden-accessible')) {
                    console.warn('⚠️ Select2 detected on Status Aktif after modal shown! Destroying...');
                    statusField.select2('destroy');
                    // Restore options
                    if (statusField.find('option').length < 3) {
                        statusField.html('<option value="">Pilih Status</option><option value="1" selected>Aktif</option><option value="0">Tidak Aktif</option>');
                    }
                    statusField.val('1');
                }
            }, 100);
        }, { once: true }); // Use once to prevent multiple handlers
        
        // Also use jQuery version as fallback
        $('#masterBuildingModal').off('shown.bs.modal').one('shown.bs.modal', function() {
            console.log('Modal shown event triggered (jQuery), initializing Select2...');
            initializeModalSelect2();
            
            // CRITICAL: Prevent Select2 from being initialized on Status Aktif after modal is shown
            setTimeout(function() {
                const statusField = $('#master_building_status');
                if (statusField.hasClass('select2-hidden-accessible')) {
                    console.warn('⚠️ Select2 detected on Status Aktif after modal shown! Destroying...');
                    statusField.select2('destroy');
                    // Restore options
                    if (statusField.find('option').length < 3) {
                        statusField.html('<option value="">Pilih Status</option><option value="1" selected>Aktif</option><option value="0">Tidak Aktif</option>');
                    }
                    statusField.val('1');
                }
            }, 100);
        });
    }
    
    // Add a MutationObserver to watch for Select2 initialization on Status Aktif
    const statusField = document.getElementById('master_building_status');
    if (statusField) {
        const observer = new MutationObserver(function(mutations) {
            const $statusField = $('#master_building_status');
            if ($statusField.hasClass('select2-hidden-accessible')) {
                console.warn('⚠️ MutationObserver: Select2 detected on Status Aktif! Destroying...');
                $statusField.select2('destroy');
                // Restore options
                if ($statusField.find('option').length < 3) {
                    $statusField.html('<option value="">Pilih Status</option><option value="1" selected>Aktif</option><option value="0">Tidak Aktif</option>');
                }
                $statusField.val('1');
            }
        });
        
        observer.observe(statusField, {
            attributes: true,
            attributeFilter: ['class'],
            childList: false,
            subtree: false
        });
    }
    
    // Fallback: Initialize Select2 after a short delay if modal shown event doesn't fire
    setTimeout(function() {
        if ($('#masterBuildingModal').hasClass('show') || $('#masterBuildingModal').is(':visible')) {
            console.log('Fallback: Modal is shown, initializing Select2...');
            initializeModalSelect2();
        } else {
            console.log('Modal not visible yet, checking again...');
            // Check again after another delay
            setTimeout(function() {
                if ($('#masterBuildingModal').hasClass('show') || $('#masterBuildingModal').is(':visible')) {
                    console.log('Second fallback: Modal is shown, initializing Select2...');
                    initializeModalSelect2();
                }
            }, 300);
        }
    }, 300);
});

// Master Building Modal - Event handlers using document delegation
$(document).on('click', '#masterBuildingModal .close', function(e) {
    e.preventDefault();
    e.stopPropagation();
    console.log('X button clicked - closing master building modal');
    clearMasterBuildingForm();
    $('#masterBuildingModal').modal('hide');
});

$(document).on('click', '#masterBuildingModal .btn-secondary', function(e) {
    e.preventDefault();
    e.stopPropagation();
    console.log('Cancel button clicked - closing master building modal');
    clearMasterBuildingForm();
    $('#masterBuildingModal').modal('hide');
});

// Master Building Modal - Disable backdrop click to close
// Modal hanya bisa ditutup dengan tombol X atau Cancel
// Setting ini sudah dilakukan di HTML dengan data-backdrop="static" data-keyboard="false"

// Master Building Modal - ESC key handler disabled
// Modal hanya bisa ditutup dengan tombol X atau Cancel

// Handler functions for cascade dropdowns
function handleProvinceChange(provinceId) {
    console.log('handleProvinceChange called with:', provinceId);
    const citySelect = $('#master_building_city');
    const districtSelect = $('#master_building_district');
    const subdistrictSelect = $('#master_building_subdistrict');
    
    // Clear dependent dropdowns
    citySelect.empty().append('<option value="">Pilih Kota/Kabupaten</option>');
    districtSelect.empty().append('<option value="">Pilih Kecamatan</option>');
    subdistrictSelect.empty().append('<option value="">Pilih Kelurahan</option>');
    
    if (provinceId) {
        console.log('Fetching cities for province:', provinceId);
        console.log('URL:', '{{ route("marketing.surveys.wizard.get-cities-by-province") }}');
        
        $.ajax({
            url: '{{ route("marketing.surveys.wizard.get-cities-by-province") }}',
            method: 'GET',
            data: { province_id: provinceId },
            dataType: 'json',
            beforeSend: function() {
                console.log('AJAX request started for province:', provinceId);
                citySelect.append('<option value="">Loading cities...</option>');
            },
            success: function(response) {
                console.log('Cities response received:', response);
                
                // Clear loading option
                citySelect.empty().append('<option value="">Pilih Kota/Kabupaten</option>');
                
                if (Array.isArray(response) && response.length > 0) {
                    console.log('Processing', response.length, 'cities');
                    response.forEach(function(city, index) {
                        console.log('Adding city', index + 1, ':', city);
                        citySelect.append(`<option value="${city.id}">${city.name} (${city.type})</option>`);
                    });
                    
                    // Reinitialize Select2
                    if (citySelect.hasClass('select2-hidden-accessible')) {
                        citySelect.select2('destroy');
                    }
                    citySelect.select2({
                        placeholder: 'Pilih atau ketik untuk mencari...',
                        allowClear: true,
                        dropdownParent: $('#masterBuildingModal'),
                        width: 'resolve',
                        dropdownAutoWidth: false,
                        closeOnSelect: true,
                        escapeMarkup: function (markup) {
                            return markup;
                        }
                    });
                    
                    // Re-add event handler
                    citySelect.on('select2:select', function(e) {
                        if (e.params && e.params.data) {
                            console.log('Select2 city selected:', e.params.data);
                            const cityId = e.params.data.id;
                            handleCityChange(cityId);
                        }
                    });
                } else {
                    console.warn('No cities found');
                    citySelect.append('<option value="" disabled>No cities found</option>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                citySelect.empty().append('<option value="">Pilih Kota/Kabupaten</option>');
                citySelect.append('<option value="" disabled>Error loading cities</option>');
            }
        });
    }
}

// Location Cascade Dropdowns - Use document ready to ensure elements exist
$(document).on('change', '#master_building_province', function() {
    console.log('Province change event triggered (fallback)');
    const provinceId = $(this).val();
    handleProvinceChange(provinceId);
});

function handleCityChange(cityId) {
    console.log('handleCityChange called with:', cityId);
    const districtSelect = $('#master_building_district');
    const subdistrictSelect = $('#master_building_subdistrict');
    
    // Clear dependent dropdowns
    districtSelect.empty().append('<option value="">Pilih Kecamatan</option>');
    subdistrictSelect.empty().append('<option value="">Pilih Kelurahan</option>');
    
    if (cityId) {
        console.log('Fetching districts for city:', cityId);
        
        $.ajax({
            url: '{{ route("marketing.surveys.wizard.get-districts-by-city") }}',
            method: 'GET',
            data: { city_id: cityId },
            beforeSend: function() {
                console.log('🚀 AJAX request starting for districts with city_id:', cityId);
                console.log('🔗 URL:', '{{ route("marketing.surveys.wizard.get-districts-by-city") }}');
            },
            success: function(response) {
                console.log('✅ Districts response received:', response);
                console.log('📊 Response type:', typeof response);
                console.log('📊 Is array:', Array.isArray(response));
                console.log('📊 Response length:', response ? response.length : 'null');
                
                districtSelect.empty().append('<option value="">Pilih Kecamatan</option>');
                
                if (Array.isArray(response) && response.length > 0) {
                    console.log('🏘️ Processing', response.length, 'districts');
                    response.forEach(function(district) {
                        console.log('   - Adding district:', district.name, 'ID:', district.id);
                        districtSelect.append(`<option value="${district.id}">${district.name}</option>`);
                    });
                    
                    // Reinitialize Select2
                    if (districtSelect.hasClass('select2-hidden-accessible')) {
                        districtSelect.select2('destroy');
                    }
                    districtSelect.select2({
                        placeholder: 'Pilih atau ketik untuk mencari...',
                        allowClear: true,
                        dropdownParent: $('#masterBuildingModal'),
                        width: 'resolve',
                        dropdownAutoWidth: false,
                        closeOnSelect: true,
                        escapeMarkup: function (markup) {
                            return markup;
                        }
                    });
                    
                    // Re-add event handler
                    districtSelect.on('select2:select', function(e) {
                        if (e.params && e.params.data) {
                            console.log('Select2 district selected:', e.params.data);
                            const districtId = e.params.data.id;
                            handleDistrictChange(districtId);
                        }
                    });
                } else {
                    districtSelect.append('<option value="" disabled>No districts found</option>');
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Error loading districts:', error);
                console.error('📄 XHR status:', xhr.status);
                console.error('📄 XHR response:', xhr.responseText);
                console.error('📄 Status:', status);
                
                districtSelect.empty().append('<option value="">Pilih Kecamatan</option>');
                districtSelect.append('<option value="" disabled>Error loading districts</option>');
            }
        });
    }
}

$(document).on('change', '#master_building_city', function() {
    console.log('City change event triggered (fallback)');
    const cityId = $(this).val();
    handleCityChange(cityId);
});

function handleDistrictChange(districtId) {
    console.log('handleDistrictChange called with:', districtId);
    const subdistrictSelect = $('#master_building_subdistrict');
    
    // Clear dependent dropdown
    subdistrictSelect.empty().append('<option value="">Pilih Kelurahan</option>');
    
    if (districtId) {
        console.log('Fetching subdistricts for district:', districtId);
        
        $.ajax({
            url: '{{ route("marketing.surveys.wizard.get-subdistricts-by-district") }}',
            method: 'GET',
            data: { district_id: districtId },
            success: function(response) {
                console.log('Subdistricts response:', response);
                console.log('First subdistrict data:', response[0]);
                subdistrictSelect.empty().append('<option value="">Pilih Kelurahan</option>');
                
                if (Array.isArray(response) && response.length > 0) {
                    response.forEach(function(subdistrict) {
                        console.log('Processing subdistrict:', subdistrict.name, 'postal_code:', subdistrict.postal_code);
                        subdistrictSelect.append(`<option value="${subdistrict.id}" data-postal-code="${subdistrict.postal_code}">${subdistrict.name}</option>`);
                    });
                    
                    // Reinitialize Select2
                    if (subdistrictSelect.hasClass('select2-hidden-accessible')) {
                        subdistrictSelect.select2('destroy');
                    }
                    subdistrictSelect.select2({
                        placeholder: 'Pilih atau ketik untuk mencari...',
                        allowClear: true,
                        dropdownParent: $('#masterBuildingModal'),
                        width: 'resolve',
                        dropdownAutoWidth: false,
                        closeOnSelect: true,
                        escapeMarkup: function (markup) {
                            return markup;
                        }
                    });
                    
                    // Add Select2 event handler for postal code auto-fill
                    subdistrictSelect.on('select2:select', function(e) {
                        if (e.params && e.params.data) {
                            console.log('🎯 Select2 subdistrict selected (inline handler):', e.params.data);
                            const selectedData = e.params.data;
                            const selectedOption = $(this).find('option[value="' + selectedData.id + '"]');
                            const postalCode = selectedOption.data('postal-code');
                            
                            console.log('🏘️ Selected subdistrict (inline):', selectedData.text);
                            console.log('📮 Postal code from data attribute (inline):', postalCode);
                        
                            if (postalCode) {
                                $('#master_building_postal_code').val(postalCode);
                                console.log('✅ Postal code auto-filled (inline):', postalCode);
                            } else {
                                console.log('❌ No postal code found (inline)');
                            }
                        }
                    });
                } else {
                    subdistrictSelect.append('<option value="" disabled>No subdistricts found</option>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading subdistricts:', error);
                subdistrictSelect.empty().append('<option value="">Pilih Kelurahan</option>');
                subdistrictSelect.append('<option value="" disabled>Error loading subdistricts</option>');
            }
        });
    }
}

$(document).on('change', '#master_building_district', function() {
    console.log('District change event triggered (fallback)');
    const districtId = $(this).val();
    handleDistrictChange(districtId);
});

// Auto-fill postal code when subdistrict is selected
$(document).on('change', '#master_building_subdistrict', function() {
    console.log('Subdistrict change event triggered (fallback)');
    const selectedOption = $(this).find('option:selected');
    const postalCode = selectedOption.data('postal-code');
    
    console.log('Selected subdistrict option:', selectedOption.text());
    console.log('Postal code from data attribute:', postalCode);
    
    if (postalCode) {
        $('#master_building_postal_code').val(postalCode);
        console.log('Postal code auto-filled:', postalCode);
    } else {
        console.log('No postal code found for selected subdistrict');
    }
});

// Select2 event handler for subdistrict (primary handler)
$(document).on('select2:select', '#master_building_subdistrict', function(e) {
    if (e.params && e.params.data) {
        console.log('🎯 Select2 subdistrict selected:', e.params.data);
        const selectedData = e.params.data;
        const selectedOption = $(this).find('option[value="' + selectedData.id + '"]');
        const postalCode = selectedOption.data('postal-code');
    
        console.log('🏘️ Selected subdistrict (Select2):', selectedData.text);
        console.log('📮 Postal code from data attribute (Select2):', postalCode);
        console.log('🔍 Selected option element:', selectedOption[0]);
        console.log('🔍 All data attributes:', selectedOption.data());
        
        if (postalCode) {
            $('#master_building_postal_code').val(postalCode);
            console.log('✅ Postal code auto-filled (Select2):', postalCode);
        } else {
            console.log('❌ No postal code found for selected subdistrict (Select2)');
            console.log('🔍 Available options with postal codes:');
            $('#master_building_subdistrict option').each(function() {
                const opt = $(this);
                console.log(`   - ${opt.text()}: ${opt.data('postal-code')}`);
            });
        }
    }
});

// Save Master Building - Use document delegation
$(document).on('click', '#save_master_building', function() {
    console.log('Save master building button clicked');
    console.log('Form elements check:');
    console.log('Name field:', $('#master_building_name').val());
    console.log('Address1 field:', $('#master_building_address1').val());
    console.log('Province field:', $('#master_building_province').val());
    
    const formData = {
        name: $('#master_building_name').val(),
        address1: $('#master_building_address1').val(),
        address2: $('#master_building_address2').val(),
        postal_code: $('#master_building_postal_code').val(),
        province_id: $('#master_building_province').val(),
        city_id: $('#master_building_city').val(),
        district_id: $('#master_building_district').val(),
        subdistrict_id: $('#master_building_subdistrict').val(),
        customer_id: $('#customer_id').val(), // Add customer_id for relationship
        is_active: $('#master_building_status').val(),
        _token: '{{ csrf_token() }}'
    };
    
    console.log('Form data collected:', formData);

    console.log('Sending AJAX request to:', '{{ route("marketing.surveys.wizard.create-master-building") }}');
    
    $.post('{{ route("marketing.surveys.wizard.create-master-building") }}', formData)
        .done(function(response) {
            console.log('AJAX response received:', response);
            if (response.success) {
                // Add new option to both building selects with data attributes
                // Use nama_gedung if name is null/empty
                const buildingName = response.building.name || response.building.nama_gedung || 'Unnamed Building';
                const newOption = $('<option value="' + response.building.id + '">' + buildingName + '</option>');
                
                // Add data attributes for summary display
                newOption.attr('data-address1', response.building.address1 || '');
                newOption.attr('data-address2', response.building.address2 || '');
                newOption.attr('data-province', response.building.province || '');
                newOption.attr('data-city', response.building.city || '');
                newOption.attr('data-district', response.building.district || '');
                newOption.attr('data-subdistrict', response.building.subdistrict || '');
                newOption.attr('data-postal-code', response.building.postal_code || '');
                newOption.attr('data-phone1', response.building.phone1 || '');
                newOption.attr('data-phone2', response.building.phone2 || '');
                newOption.attr('data-province-id', response.building.province_id || '');
                newOption.attr('data-city-id', response.building.city_id || '');
                newOption.attr('data-district-id', response.building.district_id || '');
                newOption.attr('data-subdistrict-id', response.building.subdistrict_id || '');
                
                // Add to new building dropdown
                $('#new_building_name').append(newOption.clone());
                $('#new_building_name').val(response.building.id).trigger('change');
                
                // Also add to existing building dropdown if it exists
                if ($('#building_id').length > 0) {
                    $('#building_id').append(newOption.clone());
                    console.log('Building added to existing dropdown:', buildingName);
                }
                
                // IMPORTANT: Prevent duplicate building creation
                // Uncheck "add new building" checkbox and set building_id
                $('#add_new_building').prop('checked', false);
                $('#building_id').val(response.building.id).trigger('change');
                
                // Clear ALL master building form fields using function
                clearMasterBuildingForm();
                
                // Clear new building location fields in Step 4
                $('#new_building_location_detail').val('');
                $('#new_building_type').val('').trigger('change');
                $('#new_building_phone1').val('');
                $('#new_building_phone2').val('');
                $('#new_building_fax').val('');
                $('#building_location_detail').val('');
                
                // Close modal
                $('#masterBuildingModal').modal('hide');
                
                console.log('✅ Building created and form reset to prevent duplication');
                
                // Also refresh the main building dropdown in Step 4 if it exists
                if ($('#new_building_name').length > 0) {
                    console.log('Refreshing main building dropdown in Step 4...');
                    $.get('{{ route("marketing.surveys.wizard.get-buildings") }}', {customer_id: 'all'})
                        .done(function(allBuildings) {
                            $('#new_building_name').empty().append('<option value="">Pilih atau ketik disini..</option>');
                            
                            // Sort to put new building first
                            const sortedAllBuildings = allBuildings.sort(function(a, b) {
                                if (a.id == response.building.id) return -1;
                                if (b.id == response.building.id) return 1;
                                const aDate = new Date(a.created_at || 0);
                                const bDate = new Date(b.created_at || 0);
                                return bDate - aDate;
                            });
                            
                            $.each(sortedAllBuildings, function(index, building) {
                                const buildingName = building.name || building.nama_gedung || 'Unnamed Building';
                                const displayName = (building.id == response.building.id) ? '🆕 ' + buildingName : buildingName;
                                const option = $('<option value="' + building.id + '">' + displayName + '</option>');
                                
                                // Add data attributes
                                option.attr('data-address1', building.alamat_1 || '');
                                option.attr('data-address2', building.alamat_2 || '');
                                option.attr('data-province', building.province ? building.province.name : '');
                                option.attr('data-city', building.city ? building.city.name : '');
                                option.attr('data-district', building.district ? building.district.name : '');
                                option.attr('data-subdistrict', building.subdistrict ? building.subdistrict.name : '');
                                option.attr('data-postal-code', building.postal_code || building.kode_pos || '');
                                option.attr('data-phone', building.phone_1 || '');
                                option.attr('data-province-id', building.province_id || '');
                                option.attr('data-city-id', building.city_id || '');
                                option.attr('data-district-id', building.district_id || '');
                                option.attr('data-subdistrict-id', building.subdistrict_id || '');
                                
                                $('#new_building_name').append(option);
                            });
                            
                            console.log('Main building dropdown refreshed with new building first');
                        });
                }
                
                // Always refresh building dropdown to show new building
                console.log('Refreshing building dropdown...');
                const currentCustomerId = $('#customer_id').val() || 'all';
                console.log('Using customer_id for refresh:', currentCustomerId);
                
                $.get('{{ route("marketing.surveys.wizard.get-buildings") }}', {customer_id: currentCustomerId})
                    .done(function(data) {
                        console.log('Buildings refreshed:', data);
                        
                        // Clear dropdown
                        $('#building_id').empty().append('<option value="">Pilih Building</option>');
                        
                        // Sort buildings - put new building FIRST
                        const newBuildingId = response.building.id;
                        console.log('New building ID to prioritize:', newBuildingId);
                        console.log('Raw data from backend:', data.map(b => ({id: b.id, name: b.name || b.nama_gedung, created_at: b.created_at})));
                        
                        const sortedData = data.sort(function(a, b) {
                            // Prioritize new building
                            if (a.id == newBuildingId) return -1; // New building first
                            if (b.id == newBuildingId) return 1;
                            
                            // For others, sort by created_at desc (newest first)
                            const aDate = new Date(a.created_at || 0);
                            const bDate = new Date(b.created_at || 0);
                            return bDate - aDate;
                        });
                        
                        console.log('Sorted buildings - new building should be first:', sortedData.map(b => ({id: b.id, name: b.name || b.nama_gedung})));
                        
                        $.each(sortedData, function(index, building) {
                            // Use nama_gedung if name is null/empty
                            const buildingName = building.name || building.nama_gedung || 'Unnamed Building';
                            
                            // Highlight new building
                            const displayName = (building.id == newBuildingId) ? '🆕 ' + buildingName : buildingName;
                            const option = $('<option value="' + building.id + '">' + displayName + '</option>');
                            
                            // Add data attributes for summary
                            option.attr('data-address1', building.alamat_1 || '');
                            option.attr('data-address2', building.alamat_2 || '');
                            option.attr('data-province', building.province ? building.province.name : '');
                            option.attr('data-city', building.city ? building.city.name : '');
                            option.attr('data-district', building.district ? building.district.name : '');
                            option.attr('data-subdistrict', building.subdistrict ? building.subdistrict.name : '');
                            option.attr('data-postal-code', building.kode_pos || '');
                            option.attr('data-phone1', building.phone_1 || '');
                            option.attr('data-phone2', building.phone_2 || '');
                            option.attr('data-province-id', building.province_id || '');
                            option.attr('data-city-id', building.city_id || '');
                            option.attr('data-district-id', building.district_id || '');
                            option.attr('data-subdistrict-id', building.subdistrict_id || '');
                            
                            // Mark new building
                            if (building.id == newBuildingId) {
                                option.attr('data-new-building', 'true');
                            }
                            
                            $('#building_id').append(option);
                        });
                        
                        // Reinitialize Select2 FIRST, then select
                        if (typeof $.fn.select2 !== 'undefined') {
                            if ($('#building_id').hasClass('select2-hidden-accessible')) {
                                $('#building_id').select2('destroy');
                            }
                            $('#building_id').select2({
                                placeholder: 'Pilih atau ketik untuk mencari...',
                                allowClear: true
                            });
                            console.log('Building dropdown Select2 reinitialized');
                        }
                        
                        // Auto-select the newly created building
                        console.log('Attempting to auto-select building ID:', newBuildingId);
                        console.log('Available options:', $('#building_id option').map(function() { return {id: this.value, text: this.text}; }).get());
                        
                        // Multiple attempts with increasing delays to ensure selection works
                        function attemptSelection(attempt = 1) {
                            const delay = attempt * 100; // 100ms, 200ms, 300ms
                            
                            setTimeout(function() {
                                $('#building_id').val(newBuildingId).trigger('change');
                                
                                setTimeout(function() {
                                    const currentVal = $('#building_id').val();
                                    console.log(`Attempt ${attempt}: Selected value:`, currentVal, 'Expected:', newBuildingId);
                                    
                                    if (currentVal == newBuildingId) {
                                        console.log('✅ Building successfully auto-selected on attempt', attempt);
                                        
                                        // Trigger building selection event to update summary
                                        $('#building_id').trigger('select2:select');
                                        
                                    } else if (attempt < 3) {
                                        console.log(`❌ Attempt ${attempt} failed, trying again...`);
                                        attemptSelection(attempt + 1);
                                    } else {
                                        console.log('❌ All auto-selection attempts failed');
                                    }
                                }, 50);
                            }, delay);
                        }
                        
                        // Start selection attempts
                        attemptSelection(1);
                        
                        // Close modal and show success message AFTER dropdown is refreshed
                        setTimeout(function() {
                            $('#masterBuildingModal').modal('hide');
                            alert('Master building berhasil ditambahkan!');
                        }, 500);
                    })
                    .fail(function(xhr, status, error) {
                        console.error('Error refreshing buildings:', error);
                        $('#masterBuildingModal').modal('hide');
                        alert('Error refreshing building list: ' + error);
                    });
            } else {
                alert('Error: ' + response.message);
            }
        })
        .fail(function(xhr, status, error) {
            console.log('AJAX error:', xhr, status, error);
            console.log('Response text:', xhr.responseText);
            alert('Terjadi kesalahan saat menyimpan master building: ' + error);
        });
});

// Room management
let roomCounter = 0;
let rooms = [];

// Function to reset room form completely
function resetRoomForm() {
    // Reset form
    $('#room-form')[0].reset();
    
    // Clear edit mode if any
    $('#room-form').removeData('edit-id');
    
    // Reset Select2 dropdowns to default state
    $('#room_type').val('').trigger('change');
    $('#room_floor').val('').trigger('change');
    $('#room_intensity').val('').trigger('change');
    $('#room_installation_type').val('').trigger('change');
    
    // Clear all input fields explicitly
    $('#room_name').val('');
    $('#room_qty').val('');
    $('#room_temperature').val('');
    $('#room_length').val('');
    $('#room_width').val('');
    $('#room_height').val('');
    $('#room_remark').val('');
    
    // Reset modal title for add mode
    $('#addRoomModalLabel').text('Tambah Ruangan');
    
    console.log('Room form has been reset');
}

// Add Room Button (Empty State)
$('#add-room-btn').click(function() {
    console.log('Opening add room modal from empty state');
    resetRoomForm(); // Reset form before showing modal
    $('#addRoomModal').modal('show');
});

// Add Room Button (Table State)
$('#add-room-btn-table').click(function() {
    console.log('Opening add room modal from table state');
    resetRoomForm(); // Reset form before showing modal
    $('#addRoomModal').modal('show');
});

// Add Room Modal - Event handlers using document delegation
$(document).on('click', '#addRoomModal .close', function(e) {
    e.preventDefault();
    e.stopPropagation();
    console.log('X button clicked - closing add room modal');
    resetRoomForm(); // Reset form when closing
    $('#addRoomModal').modal('hide');
});

$(document).on('click', '#addRoomModal .btn-secondary', function(e) {
    e.preventDefault();
    e.stopPropagation();
    console.log('Cancel button clicked - closing add room modal');
    resetRoomForm(); // Reset form when canceling
    $('#addRoomModal').modal('hide');
});

$(document).on('click', '#addRoomModal .btn-primary', function(e) {
    e.preventDefault();
    e.stopPropagation();
    console.log('Save button clicked - processing add room form');
    
    // Get form data
    const formData = {
        name: $('#room_name').val(),
        type: $('#room_type').val(),
        floor: $('#room_floor').val(),
        intensity: $('#room_intensity').val(),
        installation_type: $('#room_installation_type').val(),
        qty: $('#room_qty').val(),
        temperature: $('#room_temperature').val(),
        length: $('#room_length').val(),
        width: $('#room_width').val(),
        height: $('#room_height').val(),
        remark: $('#room_remark').val()
    };
    
    // Validate required fields
    if (!formData.name || !formData.type || !formData.floor || !formData.intensity || !formData.installation_type || !formData.qty || !formData.temperature || !formData.length || !formData.width || !formData.height) {
        alert('Mohon lengkapi semua field yang wajib diisi!');
        return;
    }
    
    // Check if editing existing room
    const editId = $('#room-form').data('edit-id');
    if (editId) {
        // Update existing room
        const roomIndex = rooms.findIndex(r => r.id == editId);
        if (roomIndex !== -1) {
            rooms[roomIndex] = {...formData, id: editId};
            updateRoomCard(rooms[roomIndex]);
        }
        $('#room-form').removeData('edit-id');
    } else {
        // Add new room
        roomCounter++;
        formData.id = roomCounter;
        rooms.push(formData);
        addRoomCard(formData);
    }
    
    // Clear form and close modal
    $('#room-form')[0].reset();
    $('#addRoomModal').modal('hide');
    
    console.log('Room saved successfully:', formData);
});

// Add Room Modal - Backdrop click handler
// Backdrop click handler removed to allow static backdrop
/* $(document).on('click', '#addRoomModal', function(e) {
    if (e.target === this) {
        console.log('Add room modal backdrop clicked - closing modal');
        resetRoomForm(); // Reset form when closing via backdrop
        $(this).modal('hide');
    }
}); */

// Reset form when modal is hidden (additional safety)
$('#addRoomModal').on('hidden.bs.modal', function () {
    resetRoomForm();
});


// Add room card
function addRoomCard(room) {
    // Hide empty state and show cards container
    $('#empty-rooms-state').hide();
    $('#rooms-table-container').show();
    
    const card = `
        <div class="col-12 mb-4 room-card-wrapper" data-room-id="${room.id}">
            <div class="card room-card h-100">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fas fa-door-open mr-2"></i>
                        ${room.name}
                    </h6>
                    <span class="badge badge-light text-primary">#${room.id}</span>
                </div>
                <div class="card-body">
                    <div class="room-details-grid">
                        <div class="room-detail-item">
                            <label class="room-detail-label">Jenis Ruangan</label>
                            <span class="room-detail-value">${room.type}</span>
                        </div>
                        <div class="room-detail-item">
                            <label class="room-detail-label">Lantai</label>
                            <span class="room-detail-value">${room.floor}</span>
                        </div>
                        <div class="room-detail-item">
                            <label class="room-detail-label">Qty</label>
                            <span class="room-detail-value">${room.qty}</span>
                        </div>
                        <div class="room-detail-item">
                            <label class="room-detail-label">Temperatur</label>
                            <span class="room-detail-value">${room.temperature}°C</span>
                        </div>
                        <div class="room-detail-item">
                            <label class="room-detail-label">Intensitas Wangi</label>
                            <span class="room-detail-value">${room.intensity}</span>
                        </div>
                        <div class="room-detail-item">
                            <label class="room-detail-label">Installation Type</label>
                            <span class="room-detail-value">${room.installation_type}</span>
                        </div>
                        <div class="room-detail-item">
                            <label class="room-detail-label">Dimensi (P × L × T)</label>
                            <span class="room-detail-value">${room.length} × ${room.width} × ${room.height} m</span>
                        </div>
                        <div class="room-detail-item">
                            <label class="room-detail-label">Remark</label>
                            <span class="room-detail-value">${room.remark || '-'}</span>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-sm btn-warning mr-2 edit-room" data-room-id="${room.id}">
                            <i class="fas fa-edit mr-1"></i> UBAH
                        </button>
                        <button type="button" class="btn btn-sm btn-info mr-2 copy-room" data-room-id="${room.id}">
                            <i class="fas fa-copy mr-1"></i> COPY
                        </button>
                        <button type="button" class="btn btn-sm btn-danger delete-room" data-room-id="${room.id}">
                            <i class="fas fa-trash mr-1"></i> HAPUS
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    $('#rooms-cards-container').append(card);
}

// Update room card
function updateRoomCard(room) {
    const card = $(`div[data-room-id="${room.id}"]`);
    // Update header
    card.find('.card-header h6').html(`<i class="fas fa-door-open mr-2"></i>${room.name}`);
    // Update detail values in new grid order:
    // 0: Jenis Ruangan, 1: Lantai, 2: Qty, 3: Temperatur
    // 4: Intensitas Wangi, 5: Installation Type, 6: Dimensi, 7: Remark
    card.find('.room-detail-value').eq(0).text(room.type);
    card.find('.room-detail-value').eq(1).text(room.floor);
    card.find('.room-detail-value').eq(2).text(room.qty);
    card.find('.room-detail-value').eq(3).text(`${room.temperature}°C`);
    card.find('.room-detail-value').eq(4).text(room.intensity);
    card.find('.room-detail-value').eq(5).text(room.installation_type);
    card.find('.room-detail-value').eq(6).text(`${room.length} × ${room.width} × ${room.height} m`);
    card.find('.room-detail-value').eq(7).text(room.remark || '-');
}

// Edit Room
$(document).on('click', '.edit-room', function() {
    const roomId = $(this).data('room-id');
    const room = rooms.find(r => r.id == roomId);
    
    if (room) {
        // Change modal title to Edit mode
        $('#addRoomModalLabel').text('Edit Ruangan');
        
        // Populate form with room data
        $('#room_name').val(room.name);
        $('#room_type').val(room.type).trigger('change');
        $('#room_floor').val(room.floor).trigger('change');
        $('#room_intensity').val(room.intensity).trigger('change');
        $('#room_installation_type').val(room.installation_type).trigger('change');
        $('#room_qty').val(room.qty);
        $('#room_temperature').val(room.temperature);
        $('#room_length').val(room.length);
        $('#room_width').val(room.width);
        $('#room_height').val(room.height);
        $('#room_remark').val(room.remark);
        
        // Store room ID for update
        $('#room-form').data('edit-id', roomId);
        
        $('#addRoomModal').modal('show');
    }
});

// Copy Room
$(document).on('click', '.copy-room', function() {
    const roomId = $(this).data('room-id');
    const room = rooms.find(r => r.id == roomId);
    
    if (room) {
        // Create copy with new ID
        roomCounter++;
        const newRoom = {...room, id: roomCounter, name: room.name + ' (Copy)'};
        rooms.push(newRoom);
        
        // Add to table
        addRoomCard(newRoom);
    }
});

// Delete Room
$(document).on('click', '.delete-room', function() {
    if (confirm('Apakah Anda yakin ingin menghapus ruangan ini?')) {
        const roomId = $(this).data('room-id');
        
        // Remove from array
        rooms = rooms.filter(r => r.id != roomId);
        
        // Remove from cards
        $(`div[data-room-id="${roomId}"]`).remove();
        
        // If no rooms left, show empty state
        if (rooms.length === 0) {
            $('#rooms-table-container').hide();
            $('#empty-rooms-state').show();
        }
    }
});

    // Save Draft button
    $(document).on('click', '#save-draft-btn', function(e) {
        e.preventDefault();
        console.log('Save Draft clicked');
        
        // Collect all form data
        const formData = collectFormData();
        formData.action = 'save_draft';
        
        // Submit form
        submitSurvey(formData);
    });
    
    // Finalize & Email button
    $(document).on('click', '#finalize-btn', function(e) {
        e.preventDefault();
        console.log('Finalize & Email clicked');
        
        // Basic validation - ensure marketing and date are filled
        if (!validateCurrentStep()) {
            return false;
        }

        // Collect all form data
        const formData = collectFormData();

        // Directly proceed with finalize confirmation
        Swal.fire({
            title: 'Finalize Survey?',
            text: "Survey yang sudah difinalisasi tidak dapat diubah lagi. Pastikan semua data sudah benar dan email akan dikirim ke customer.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Finalize!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                formData.action = 'finalize';
                submitSurvey(formData);
            }
        });
    });
    

    // Collect all form data
    function collectFormData() {
        // Transform rooms data to match validation rules
        const transformedRooms = rooms.map(room => ({
            room_name: room.name,
            room_type: room.type,
            floor: room.floor,
            intensity: room.intensity,
            installation_type: room.installation_type,
            qty: parseInt(room.qty),
            length: parseFloat(room.length),
            width: parseFloat(room.width),
            height: parseFloat(room.height),
            temperature: parseFloat(room.temperature) || null,
            remark: room.remark || ''
        }));
        
        // Get boolean values properly - always send as true/false
        const addNewCustomer = $('#add_new_customer').is(':checked');
        const addNewContact = $('#add_new_contact').is(':checked');
        const addNewBuilding = $('#add_new_building').is(':checked');
        
        const formData = {
            marketing_id: $('#marketing_staff_id').val(),
            survey_date: $('#survey_date_hidden').val() || $('#survey_date').val(), // Use hidden field with ISO format, fallback to display field
            customer_id: $('#customer_id').val(),
            contact_id: $('#contact_id').val(),
            building_id: $('#building_id').val(),
            building_location_detail: $('#building_location_detail').val() || $('#new_building_location_detail').val(),
            rooms: transformedRooms,
            // Always send boolean fields as true/false
            customer_id: $('#customer_id').val(),
            add_new_customer: addNewCustomer,
            add_new_contact: addNewContact,
            add_new_building: addNewBuilding,
            // Include new customer data if adding new customer
            ...(addNewCustomer && {
                new_company_type: $('#new_company_type').val(),
                new_customer_name: $('#new_customer_name').val()
            }),
            // Include new contact data if adding new contact
            ...(addNewContact && {
                new_contact_salutation: $('#new_contact_salutation').val(),
                new_contact_name: $('#new_contact_name').val(),
                new_contact_email: $('#new_contact_email').val(),
                new_contact_phone: $('#new_contact_phone').val(),
                new_contact_position: $('#new_contact_position').val()
            }),
            // Include new building data if adding new building
            ...(addNewBuilding && {
                new_building_name: $('#master_building_name').val() || $('#new_building_name option:selected').text(), // Use actual building name input, fallback to selected option text
                new_building_type: $('#new_building_type').val(),
                new_building_location_detail: $('#new_building_location_detail').val(),
                new_building_phone1: $('#new_building_phone1').val(),
                new_building_phone2: $('#new_building_phone2').val(),
                new_building_fax: $('#new_building_fax').val(),
                // Include location data from modal
                master_building_province: $('#master_building_province').val(),
                master_building_city: $('#master_building_city').val(),
                master_building_district: $('#master_building_district').val(),
                master_building_subdistrict: $('#master_building_subdistrict').val(),
                master_building_postal_code: $('#master_building_postal_code').val()
            })
        };
        
        console.log('Form data collected:', formData);
        console.log('Marketing staff value:', $('#marketing_staff_id').val());
        console.log('Marketing staff element exists:', $('#marketing_staff_id').length);
        
        // Debug building data
        console.log('Building debug:');
        console.log('add_new_building checked:', $('#add_new_building').is(':checked'));
        console.log('new_building_address1 value:', $('#new_building_address1').val());
        
        // Debug date values
        console.log('Date values:', {
            display_date: $('#survey_date').val(),
            hidden_date: $('#survey_date_hidden').val(),
            final_date: formData.survey_date
        });
        
        console.log('Form data collected:', formData);
        
        return formData;
    }
    
    // Submit survey
    let _surveySubmitting = false;

    function submitSurvey(formData) {
        if (_surveySubmitting) return;
        _surveySubmitting = true;

        console.log('Submitting survey:', formData);

        // Disable buttons for the whole lifecycle — re-enabled only on error
        $('#save-draft-btn, #finalize-btn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Processing...');

        // Submit via AJAX
        $.ajax({
            url: '{{ route("marketing.surveys.wizard.save") }}',
            method: 'POST',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                console.log('Survey saved successfully:', response);
                // Keep buttons disabled — page is navigating away
                $('#save-draft-btn, #finalize-btn')
                    .prop('disabled', true)
                    .html('<i class="fas fa-check mr-2"></i> Tersimpan, mengalihkan...');
                const target = response.redirect_url
                    || ('{{ route("marketing.surveys.show", ":id") }}'.replace(':id', response.survey_id))
                    || '{{ route("marketing.surveys.index") }}';
                window.location.href = target;
            },
            error: function(xhr, status, error) {
                console.error('Error saving survey:', xhr.responseText);
                _surveySubmitting = false;
                // Only re-enable on error so user can retry
                $('#save-draft-btn').prop('disabled', false).html('<i class="fas fa-save mr-2"></i> SAVE DRAFT');
                $('#finalize-btn').prop('disabled', false).html('<i class="fas fa-check mr-2"></i> FINALIZE & EMAIL');
                const msg = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'Terjadi kesalahan saat menyimpan survey. Silakan coba lagi.';
                alert(msg);
            }
        });
    }

    // Ensure modals are properly hidden on page load
    $('.modal').modal('hide');
    $('.modal').removeClass('show');
    $('.modal').css('display', 'none');
});
</script>

<style>
/* Ensure modals are properly styled */
.modal {
    display: none !important;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1050;
}

/* Select2 dropdown styling for modal */
.select2-container--default .select2-dropdown {
    z-index: 10060 !important;
}

.select2-container--default .select2-selection--single {
    height: 38px !important;
    border: 1px solid #ced4da !important;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 36px !important;
    padding-left: 12px !important;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 36px !important;
}

/* Fix modal width - prevent Select2 from making modal too wide */
#masterBuildingModal .modal-dialog {
    max-width: 900px !important;
    width: 95% !important;
    margin: 1rem auto !important;
}

#masterBuildingModal .modal-content {
    max-height: 90vh !important;
    display: flex !important;
    flex-direction: column !important;
}

#masterBuildingModal .modal-body {
    overflow-y: auto !important;
    overflow-x: hidden !important;
    max-height: calc(90vh - 120px) !important;
    padding: 1.5rem !important;
    -webkit-overflow-scrolling: touch !important;
    scroll-behavior: smooth !important;
}

/* Fix scroll issue - ensure modal body can scroll properly */
#masterBuildingModal.modal-dialog-scrollable .modal-body {
    overflow-y: auto !important;
    position: relative !important;
}

#masterBuildingModal .modal-header {
    flex-shrink: 0 !important;
    padding: 1rem 1.5rem !important;
}

#masterBuildingModal .modal-footer {
    flex-shrink: 0 !important;
    padding: 1rem 1.5rem !important;
}

#masterBuildingModal .select2-container {
    width: 100% !important;
    max-width: 100% !important;
}

/* Responsive form fields */
#masterBuildingModal .form-group {
    margin-bottom: 1rem !important;
}

#masterBuildingModal .row {
    margin-left: -10px !important;
    margin-right: -10px !important;
}

#masterBuildingModal .row > [class*="col-"] {
    padding-left: 10px !important;
    padding-right: 10px !important;
}

/* Fix form control width */
#masterBuildingModal .form-control,
#masterBuildingModal .select2-container {
    width: 100% !important;
    max-width: 100% !important;
    box-sizing: border-box !important;
}

/* Responsive columns */
@media (max-width: 768px) {
    #masterBuildingModal .modal-dialog {
        width: 98% !important;
        max-width: 98% !important;
        margin: 0.5rem auto !important;
    }
    
    #masterBuildingModal .modal-body {
        max-height: calc(90vh - 100px) !important;
        padding: 1rem !important;
    }
    
    #masterBuildingModal .col-md-6,
    #masterBuildingModal .col-md-8,
    #masterBuildingModal .col-md-4,
    #masterBuildingModal .col-md-12 {
        flex: 0 0 100% !important;
        max-width: 100% !important;
        margin-bottom: 0.5rem !important;
    }
}

/* Prevent dropdown from closing on hover - more specific selectors */
.select2-container--open .select2-dropdown {
    pointer-events: auto !important;
}

.select2-container--open .select2-results {
    pointer-events: auto !important;
}

.select2-container--open .select2-results__option {
    pointer-events: auto !important;
    cursor: pointer !important;
}

.select2-container--open .select2-results__option:hover {
    background-color: #0d6efd !important;
    color: white !important;
}

.modal.show {
    display: block !important;
}

.modal-dialog {
    position: relative;
    width: auto;
    margin: 1.75rem auto;
    max-width: 800px;
}

.modal-content {
    position: relative;
    display: flex;
    flex-direction: column;
    width: 100%;
    pointer-events: auto;
    background-color: #fff;
    background-clip: padding-box;
    border: 1px solid rgba(0, 0, 0, 0.2);
    border-radius: 0.3rem;
    outline: 0;
}

.modal-lg {
    max-width: 800px;
}

/* Button plus styling */
#add_master_building_btn {
    background-color: #28a745 !important;
    border-color: #28a745 !important;
    color: white !important;
    width: 40px;
    height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0 0.25rem 0.25rem 0;
}

#add_master_building_btn:hover {
    background-color: #218838 !important;
    border-color: #1e7e34 !important;
}

#add_master_building_btn i {
    font-size: 14px;
}

/* Room Modal - Remark field styling */
#room_remark {
    width: 100% !important;
    min-width: 100% !important;
    max-width: 100% !important;
}

/* Fix aria-hidden focus issue */
.modal.show ~ * [aria-hidden="true"] {
    pointer-events: none;
}

/* Ensure modal focus is properly managed */
.modal.show {
    z-index: 1055;
}

.modal-backdrop.show {
    z-index: 1050;
}

/* Room Modal - Better layout for remark section */
#addRoomModal .form-section:last-child .row {
    margin: 0;
}

#addRoomModal .form-section:last-child .col-md-12 {
    padding: 0;
}

/* Room Table Container */
#rooms-table-container {
    width: 100%;
    max-width: 100%;
    overflow: hidden;
}

/* Room Cards Container */
#rooms-cards-container {
    display: flex !important;
    flex-wrap: wrap !important;
    margin: 0 -8px;
    width: calc(100% + 16px);
    max-width: calc(100% + 16px);
}

/* Room Card Wrapper - Responsive sizing with max 2 per row */
.room-card-wrapper {
    transition: all 0.3s ease;
    padding: 0 8px;
    box-sizing: border-box !important;
    margin-bottom: 16px;
    flex-shrink: 0;
}

/* Mobile: Always 1 card per row (full width) */
.room-card-wrapper {
    flex: 0 0 100% !important;
    max-width: 100% !important;
    width: 100%;
}

/* Tablet and up: Max 2 cards per row */
@media (min-width: 768px) {
    /* Single card - full width */
    .room-card-wrapper:only-child {
        flex: 0 0 100% !important;
        max-width: 100% !important;
        width: 100%;
    }
    
    /* Multiple cards - 2 per row, wrap to next line */
    .room-card-wrapper:not(:only-child) {
        flex: 0 0 50% !important;
        max-width: 50% !important;
        width: 50%;
    }
}

/* Room Cards Styling */
.room-card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: box-shadow 0.3s ease, transform 0.2s ease;
    overflow: hidden;
}

.room-card:hover {
    box-shadow: 0 6px 16px rgba(0,0,0,0.15);
    transform: translateY(-2px);
}

.room-card .card-header {
    background: linear-gradient(135deg, #007bff, #0056b3) !important;
    border-bottom: none;
    padding: 15px 20px;
}

.room-card .card-header h6 {
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0;
}

.room-card .card-body {
    padding: 15px;
    background: #fff;
    overflow: hidden;
}

/* Room Details Grid Layout */
.room-details-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
}

/* Tablet: 2 columns for all */
@media (min-width: 768px) {
    .room-details-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }
    
    /* Single card (full width) - 4 columns */
    .room-card-wrapper:only-child .room-details-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

/* Large desktop: optimize grid */
@media (min-width: 1200px) {
    /* Single card - 4 columns */
    .room-card-wrapper:only-child .room-details-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
    }
    
    /* Multiple cards - still 2 columns but with better spacing */
    .room-card-wrapper:not(:only-child) .room-details-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }
}

.room-detail-item {
    display: flex;
    flex-direction: column;
    padding: 8px 10px;
    background: #f8f9fa;
    border-radius: 6px;
    border-left: 3px solid #007bff;
    min-width: 0; /* Prevent overflow */
}

.room-detail-label {
    font-size: 10px;
    font-weight: 600;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    margin-bottom: 3px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.room-detail-value {
    font-size: 13px;
    font-weight: 500;
    color: #212529;
    word-break: break-word;
}

.room-card .card-footer {
    background-color: #f8f9fa;
    border-top: 1px solid #e9ecef;
    padding: 10px 15px;
}

.room-card .card-footer .btn {
    margin-right: 5px;
    padding: 4px 10px;
    font-size: 12px;
}

.room-card .card-footer .btn:last-child {
    margin-right: 0;
}

.room-card .card-footer .d-flex {
    flex-wrap: wrap;
    gap: 5px;
}

.room-detail-item {
    margin-bottom: 8px;
    padding-bottom: 4px;
    border-bottom: 1px solid #f0f0f0;
}

.room-detail-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.room-detail-label {
    font-weight: 600;
    color: #495057;
    font-size: 0.875rem;
    margin-bottom: 2px;
    display: block;
}

.room-detail-value {
    color: #212529;
    font-size: 0.9rem;
    font-weight: 500;
}

/* Summary Styling */
.summary-item {
    margin-bottom: 8px;
    padding-bottom: 6px;
    border-bottom: 1px solid #f0f0f0;
}

.summary-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.summary-label {
    font-weight: 600;
    color: #495057;
    font-size: 0.875rem;
    margin-bottom: 2px;
    display: block;
}

.summary-value {
    color: #212529;
    font-size: 0.9rem;
    font-weight: 500;
}

/* Summary Cards */
.card.border-primary .card-header {
    background: linear-gradient(135deg, #007bff, #0056b3) !important;
}

.card.border-success .card-header {
    background: linear-gradient(135deg, #28a745, #1e7e34) !important;
}

.card.border-info .card-header {
    background: linear-gradient(135deg, #17a2b8, #138496) !important;
}

/* Responsive Summary Layout */
@media (min-width: 1200px) {
    .summary-cards-container {
        max-width: 100%;
    }
    
    .summary-card {
        min-height: 400px;
    }
}

@media (min-width: 992px) {
    .summary-cards-container {
        max-width: 100%;
    }
    
    .summary-card {
        min-height: 350px;
    }
}

@media (max-width: 991px) {
    .summary-cards-container .col-lg-6 {
        margin-bottom: 1.5rem;
    }
    
    .summary-card {
        min-height: auto;
    }
}

/* Summary Cards Responsive */
.summary-card {
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.summary-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.15);
    transform: translateY(-2px);
}

/* Summary Items Responsive */
.summary-item {
    margin-bottom: 12px;
    padding-bottom: 8px;
    border-bottom: 1px solid #f0f0f0;
    transition: all 0.2s ease;
}

.summary-item:hover {
    background-color: #f8f9fa;
    padding: 8px;
    border-radius: 4px;
    margin: 4px -8px 8px -8px;
}

.summary-label {
    font-weight: 600;
    color: #495057;
    font-size: 0.9rem;
    margin-bottom: 4px;
    display: block;
}

.summary-value {
    color: #212529;
    font-size: 0.95rem;
    font-weight: 500;
    word-break: break-word;
}

/* Table Responsive */
.table-responsive {
    border-radius: 0 0 8px 8px;
    overflow-x: auto;
}

.table th {
    background-color: #f8f9fa;
    border-top: none;
    font-weight: 600;
    color: #495057;
    padding: 12px 16px;
}

.table td {
    padding: 12px 16px;
    vertical-align: top;
    border-top: 1px solid #dee2e6;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
}

/* Navigation Buttons Responsive */
.wizard-navigation {
    padding: 20px 0;
    border-top: 1px solid #dee2e6;
    background-color: #f8f9fa;
    margin-top: 20px;
}

.wizard-navigation .btn {
    min-width: 140px;
    padding: 10px 20px;
    font-weight: 500;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.wizard-navigation .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .wizard-navigation {
        text-align: center;
    }
    
    .wizard-navigation .btn {
        width: 100%;
        margin-bottom: 10px;
        min-width: auto;
    }
    
    .wizard-navigation .btn:last-child {
        margin-bottom: 0;
    }
    
    .summary-item {
        margin-bottom: 8px;
        padding-bottom: 6px;
    }
    
    .summary-label {
        font-size: 0.85rem;
    }
    
    .summary-value {
        font-size: 0.9rem;
    }
}

/* Large Screen Optimization */
@media (min-width: 1400px) {
    .summary-cards-container {
        max-width: 100%;
        margin: 0;
    }
    
    .summary-card {
        min-height: 450px;
    }
    
    .summary-item {
        margin-bottom: 16px;
        padding-bottom: 10px;
    }
    
    .summary-label {
        font-size: 1rem;
    }
    
    .summary-value {
        font-size: 1.1rem;
    }
}

/* Room Name Container Styling */
.room-name-container {
    padding: 8px 0;
}

.room-name-main {
    font-weight: 600;
    color: #212529;
    font-size: 1rem;
    margin-bottom: 6px;
}

.room-name-details {
    margin-bottom: 4px;
}

.room-name-details .badge {
    font-size: 0.75rem;
    padding: 4px 8px;
}

.room-name-remark {
    font-style: italic;
}

/* Specification Container Styling */
.specification-container {
    padding: 8px 0;
}

.spec-item {
    margin-bottom: 6px;
    display: flex;
    align-items: center;
}

.spec-item:last-child {
    margin-bottom: 0;
}

.spec-label {
    font-weight: 600;
    color: #495057;
    font-size: 0.85rem;
    min-width: 120px;
    margin-right: 8px;
}

.spec-value {
    color: #212529;
    font-size: 0.9rem;
    font-weight: 500;
    flex: 1;
}

/* Table Row Hover Effects */
.table tbody tr:hover .room-name-main {
    color: #007bff;
}

.table tbody tr:hover .spec-value {
    color: #007bff;
}

/* Badge Styling */
.badge {
    border-radius: 12px;
    font-weight: 500;
}

.badge-secondary {
    background-color: #6c757d;
    color: white;
}

.badge-info {
    background-color: #17a2b8;
    color: white;
}

/* Responsive Table Improvements */
@media (max-width: 768px) {
    .spec-item {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .spec-label {
        min-width: auto;
        margin-right: 0;
        margin-bottom: 2px;
    }
    
    .room-name-details .badge {
        display: block;
        margin-bottom: 4px;
        width: fit-content;
    }
}

/* Large Screen Table Optimization */
@media (min-width: 1200px) {
    .table td {
        padding: 16px 20px;
    }
    
    .room-name-main {
        font-size: 1.1rem;
    }
    
    .spec-label {
        font-size: 0.9rem;
    }
    
    .spec-value {
        font-size: 1rem;
    }
}
</style>

<!-- Master Building Modal -->
<div class="modal fade" id="masterBuildingModal" tabindex="-1" role="dialog" aria-labelledby="masterBuildingModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="masterBuildingModalLabel">Tambah Master Building</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Basic Information Section -->
                <div class="form-section mb-4">
                    <div class="section-header mb-3">
                        <h6 class="text-primary mb-0">
                            <i class="fas fa-building mr-2"></i>
                            Informasi Dasar
                        </h6>
                        <hr class="mt-2 mb-3">
                    </div>
                    <div class="row">
                        <div class="col-md-9">
                            <div class="form-group">
                                <label for="master_building_name" class="form-label font-weight-bold">Nama Gedung <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="master_building_name" name="master_building_name" placeholder="Masukkan nama gedung" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="master_building_status" class="form-label font-weight-bold">Status Aktif <span class="text-danger">*</span></label>
                                <select class="form-control no-select2" id="master_building_status" name="master_building_status" required>
                                    <option value="">Pilih Status</option>
                                    <option value="1" selected>Aktif</option>
                                    <option value="0">Tidak Aktif</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Address Information Section -->
                <div class="form-section mb-4">
                    <div class="section-header mb-3">
                        <h6 class="text-primary mb-0">
                            <i class="fas fa-map-marker-alt mr-2"></i>
                            Informasi Alamat
                        </h6>
                        <hr class="mt-2 mb-3">
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="master_building_address1" class="form-label font-weight-bold">Alamat 1 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="master_building_address1" name="master_building_address1" placeholder="Masukkan alamat utama" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="master_building_address2" class="form-label font-weight-bold">Alamat 2</label>
                                <input type="text" class="form-control" id="master_building_address2" name="master_building_address2" placeholder="Masukkan alamat tambahan (opsional)">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="master_building_province" class="form-label font-weight-bold">Provinsi <span class="text-danger">*</span></label>
                                <select class="form-control select2" id="master_building_province" name="master_building_province" required>
                                    <option value="">Pilih Provinsi</option>
                                    @foreach($provinces as $province)
                                        <option value="{{ $province->id }}">{{ $province->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Location Details Section -->
                <div class="form-section mb-4">
                    <div class="section-header mb-3">
                        <h6 class="text-primary mb-0">
                            <i class="fas fa-map mr-2"></i>
                            Detail Lokasi
                        </h6>
                        <hr class="mt-2 mb-3">
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="master_building_city" class="form-label font-weight-bold">Kota/Kabupaten</label>
                                <select class="form-control select2" id="master_building_city" name="master_building_city">
                                    <option value="">Pilih Kota/Kabupaten</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="master_building_district" class="form-label font-weight-bold">Kecamatan</label>
                                <select class="form-control select2" id="master_building_district" name="master_building_district">
                                    <option value="">Pilih Kecamatan</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="master_building_subdistrict" class="form-label font-weight-bold">Kelurahan</label>
                                <select class="form-control select2" id="master_building_subdistrict" name="master_building_subdistrict">
                                    <option value="">Pilih Kelurahan</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="master_building_postal_code" class="form-label font-weight-bold">Kode Pos</label>
                                <input type="text" class="form-control" id="master_building_postal_code" name="master_building_postal_code" placeholder="Auto-fill dari kelurahan" readonly>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="save_master_building">Simpan</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Ruangan -->
<div class="modal fade" id="addRoomModal" tabindex="-1" role="dialog" aria-labelledby="addRoomModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addRoomModalLabel">Tambah Ruangan</h5>
                <button type="button" class="close" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="room-form">
                    <!-- Basic Information Section -->
                    <div class="form-section mb-4">
                        <div class="section-header mb-3">
                            <h6 class="text-primary mb-0">
                                <i class="fas fa-door-open mr-2"></i>
                                Informasi Dasar
                            </h6>
                            <hr class="mt-2 mb-3">
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="room_name" class="form-label font-weight-bold">Nama Ruangan <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="room_name" name="room_name" placeholder="Masukkan nama ruangan" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="room_type" class="form-label font-weight-bold">Jenis Ruangan <span class="text-danger">*</span></label>
                                    <select class="form-control select2" id="room_type" name="room_type" required>
                                        <option value="">Pilih Jenis Ruangan</option>
                                        @foreach($roomTypes as $type)
                                            <option value="{{ $type->option_name }}">{{ $type->option_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="room_floor" class="form-label font-weight-bold">Lantai <span class="text-danger">*</span></label>
                                    <select class="form-control select2" id="room_floor" name="room_floor" required>
                                        <option value="">Pilih Lantai</option>
                                        @foreach($floors as $floor)
                                            <option value="{{ $floor->option_name }}">{{ $floor->option_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="room_qty" class="form-label font-weight-bold">Qty <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="room_qty" name="room_qty" min="1" placeholder="Masukkan jumlah" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="room_temperature" class="form-label font-weight-bold">Temperatur (°C) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="room_temperature" name="room_temperature" step="0.1" placeholder="Masukkan temperatur" required>
                                    <small class="form-text text-muted">Temperatur ruangan dalam derajat Celsius</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <!-- Empty column for spacing -->
                            </div>
                        </div>
                    </div>

                    <!-- Scent & Installation Section -->
                    <div class="form-section mb-4">
                        <div class="section-header mb-3">
                            <h6 class="text-primary mb-0">
                                <i class="fas fa-wind mr-2"></i>
                                Konfigurasi Wangi & Instalasi
                            </h6>
                            <hr class="mt-2 mb-3">
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="room_intensity" class="form-label font-weight-bold">Intensitas Wangi <span class="text-danger">*</span></label>
                                    <select class="form-control select2" id="room_intensity" name="room_intensity" required>
                                        <option value="">Pilih Intensitas Wangi</option>
                                        @foreach($intensities as $intensity)
                                            <option value="{{ $intensity->option_name }}">{{ $intensity->option_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="room_installation_type" class="form-label font-weight-bold">Installation Type <span class="text-danger">*</span></label>
                                    <select class="form-control select2" id="room_installation_type" name="room_installation_type" required>
                                        <option value="">Pilih Installation Type</option>
                                        @foreach($installationTypes as $type)
                                            <option value="{{ $type->option_name }}">{{ $type->option_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Dimensions Section -->
                    <div class="form-section mb-4">
                        <div class="section-header mb-3">
                            <h6 class="text-primary mb-0">
                                <i class="fas fa-ruler-combined mr-2"></i>
                                Dimensi Ruangan
                            </h6>
                            <hr class="mt-2 mb-3">
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="room_length" class="form-label font-weight-bold">Panjang (M) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="room_length" name="room_length" step="0.01" min="0" placeholder="0.00" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="room_width" class="form-label font-weight-bold">Lebar (M) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="room_width" name="room_width" step="0.01" min="0" placeholder="0.00" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="room_height" class="form-label font-weight-bold">Tinggi (M) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="room_height" name="room_height" step="0.01" min="0" placeholder="0.00" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Information Section -->
                    <div class="form-section mb-4">
                        <div class="section-header mb-3">
                            <h6 class="text-primary mb-0">
                                <i class="fas fa-sticky-note mr-2"></i>
                                Informasi Tambahan
                            </h6>
                            <hr class="mt-2 mb-3">
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="room_remark" class="form-label font-weight-bold">Remark</label>
                                    <textarea class="form-control" id="room_remark" name="room_remark" rows="3" placeholder="Masukkan catatan tambahan (opsional)"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary">Cancel</button>
                <button type="button" class="btn btn-primary" id="save-room-btn">Simpan</button>
            </div>
        </div>
    </div>
</div>
@endsection
