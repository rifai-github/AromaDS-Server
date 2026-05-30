@extends('layouts.app')

@section('title', 'Quotation Wizard')
@section('breadcrumb')
<a href="{{ route('marketing.quotations.index') }}" style="color: #214589; text-decoration: none;">
    <i class="fas fa-arrow-left" style="margin-right: 5px;"></i>Back to Quotation
</a>
@section('content')
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
<!-- Bootstrap 5 CSS (for Modal) -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" />

<style>
/* ===== BASE STYLES ===== */
.form-text {
    display: block !important;
    margin-top: 0.25rem;
    margin-bottom: 0;
}

.form-group {
    margin-bottom: 1rem;
}

/* ===== VALIDATION STYLES ===== */
.form-control.is-invalid {
    border-color: #dc3545 !important;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
}

.invalid-feedback {
    display: block !important;
    width: 100%;
    margin-top: 0.25rem;
    font-size: 0.875rem;
    color: #dc3545;
    font-weight: 500;
}

.required-indicator {
    display: inline-block;
    background-color: #dc3545;
    color: white;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 2px 6px;
    border-radius: 3px;
    margin-left: 5px;
    vertical-align: top;
}

.required-indicator::before {
    content: "REQUIRED";
}

/* ===== CONTRACT FIELD ===== */
#contract-field {
    display: none !important;
}

#contract-field.show {
    display: block !important;
}

/* ===== STEP NAVIGATION ===== */
.step.disabled {
    opacity: 0.5;
    cursor: not-allowed;
    pointer-events: none;
}

.step.disabled .step-circle {
    background-color: #6c757d !important;
    color: #adb5bd !important;
}

.step.disabled .step-title,
.step.disabled .step-desc {
    color: #6c757d !important;
}

/* ===== SELECT2 STYLES ===== */
.select2-container--default .select2-selection--single.is-invalid {
    border-color: #dc3545 !important;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
}

.select2-container {
    width: 100% !important;
}

/* ===== STEP INDICATOR ===== */
.step-indicator-vertical {
    position: relative;
}

.step-indicator-vertical .step {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
    position: relative;
    padding: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.step-indicator-vertical .step:not(:last-child)::after {
    content: '';
    position: absolute;
    left: 20px;
    top: 50px;
    width: 2px;
    height: 30px;
    background-color: #e9ecef;
    z-index: 1;
}

.step-indicator-vertical .step.active::after,
.step-indicator-vertical .step.completed::after {
    background-color: #007bff;
}

.step-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #e9ecef;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-right: 15px;
    transition: all 0.3s ease;
    z-index: 2;
    position: relative;
}

.step.active .step-circle {
    background-color: #007bff;
    color: white;
}

.step.completed .step-circle {
    background-color: #28a745;
    color: white;
}

.step-content {
    flex: 1;
}

.step-title {
    font-weight: 600;
    margin-bottom: 5px;
    color: #495057;
}

.step-desc {
    font-size: 0.875rem;
    color: #6c757d;
    margin: 0;
}

/* ===== WIZARD STEPS ===== */
.wizard-step {
    display: none;
}

.wizard-step.active {
    display: block !important;
}

/* ===== BUTTON STYLES ===== */
.btn-wizard {
    padding: 10px 20px;
    border-radius: 5px;
    font-weight: 500;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

.btn-wizard-primary {
    background-color: #007bff;
    color: white;
}

.btn-wizard-primary:hover {
    background-color: #0056b3;
    color: white;
}

.btn-wizard-secondary {
    background-color: #6c757d;
    color: white;
}

.btn-wizard-secondary:hover {
    background-color: #545b62;
    color: white;
}

.btn-wizard-success {
    background-color: #28a745;
    color: white;
}

.btn-wizard-success:hover {
    background-color: #1e7e34;
    color: white;
}

/* ===== STEP NAVIGATION ===== */
.step-navigation {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    background-color: #f8f9fa;
    border-top: 1px solid #dee2e6;
    margin-top: 20px;
    min-height: 80px;
}

.nav-buttons {
    display: flex;
    gap: 15px;
    align-items: center;
}

.nav-buttons button,
.final-buttons button {
    min-width: 120px;
    height: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* ===== ROOM SELECTION ===== */
.room-specs {
    font-size: 0.875rem;
}

.room-specs div {
    margin-bottom: 5px;
}

.spec-item {
    display: flex;
    align-items: center;
    padding: 2px 0;
}

.spec-item i {
    width: 16px;
    text-align: center;
}

.survey-room-section .card {
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.survey-room-section .card-header {
    border-bottom: 2px solid #e9ecef;
}

.survey-room-section .table th {
    background-color: #f8f9fa;
    border-top: none;
    font-weight: 600;
}

.survey-room-section .table td {
    vertical-align: middle;
}

/* Responsive table for specifications */
.survey-room-section .table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.survey-room-section .table {
    min-width: 100%;
    table-layout: fixed;
}

.survey-room-section .table th:nth-child(1),
.survey-room-section .table td:nth-child(1) {
    width: 5%;
}

.survey-room-section .table th:nth-child(2),
.survey-room-section .table td:nth-child(2) {
    width: 30%;
}

.survey-room-section .table th:nth-child(3),
.survey-room-section .table td:nth-child(3) {
    width: 30%;
}

/* Responsive specifications */
.room-specs .row {
    margin: 0;
}

.room-specs .col-md-6 {
    padding: 0 5px;
}

.room-specs .spec-item {
    font-size: 0.85rem;
    padding: 4px 8px;
    margin-bottom: 4px;
}

.room-specs .spec-item i {
    width: 16px;
    font-size: 0.8rem;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .room-specs .row {
        flex-direction: column;
    }
    
    .room-specs .col-md-6 {
        width: 100%;
        padding: 0;
    }
    
    .room-specs .spec-item {
        font-size: 0.8rem;
        padding: 3px 6px;
    }
}

/* ===== INFO SECTIONS ===== */

.info-item {
    margin-bottom: 8px;
    font-size: 0.9rem;
}

.info-item:last-child {
    margin-bottom: 0;
}

/* Ensure equal height for side-by-side info sections */
.row .col-md-6 .info-section {
    flex: 1;
    width: 100%;
}

/* Force horizontal layout for info sections */
.info-section {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border-left: 4px solid #007bff;
    height: 100%;
    margin-bottom: 0;
}

/* Ensure proper Bootstrap grid behavior */
.row .col-md-6 {
    display: block;
    float: left;
    width: 50%;
    padding-left: 15px;
    padding-right: 15px;
}

/* Clear floats */
.row::after {
    content: "";
    display: table;
    clear: both;
}

/* ===== SPECIFICATIONS STYLING ===== */
.spec-item {
    display: flex;
    align-items: center;
    padding: 8px 12px;
    background: #f8f9fa;
    border-radius: 6px;
    margin-bottom: 8px;
    border-left: 3px solid #007bff;
    transition: all 0.2s ease;
}

.spec-item:hover {
    background: #e9ecef;
    transform: translateX(2px);
}

.spec-item i {
    width: 20px;
    text-align: center;
    font-size: 1.1rem;
}

.spec-value {
    color: #495057;
    font-weight: 500;
}

/* Icon colors for different types */
.spec-item i.fa-layer-group { color: #6f42c1 !important; }
.spec-item i.fa-tint { color: #17a2b8 !important; }
.spec-item i.fa-tools { color: #fd7e14 !important; }
.spec-item i.fa-ruler-horizontal { color: #28a745 !important; }
.spec-item i.fa-ruler-vertical { color: #28a745 !important; }
.spec-item i.fa-arrows-alt-v { color: #dc3545 !important; }
.spec-item i.fa-expand-arrows-alt { color: #20c997 !important; }
.spec-item i.fa-thermometer-half { color: #e83e8c !important; }
.spec-item i.fa-hashtag { color: #6c757d !important; }
.spec-item i.fa-sticky-note { color: #ffc107 !important; }

/* Additional color classes */
.text-purple { color: #6f42c1 !important; }
.text-cyan { color: #17a2b8 !important; }
.text-orange { color: #fd7e14 !important; }
.text-green { color: #28a745 !important; }
.text-red { color: #dc3545 !important; }
.text-yellow { color: #ffc107 !important; }
.text-pink { color: #e83e8c !important; }

/* Additional spacing for 2-column layout */
.spec-item {
    padding: 8px 12px;
    background: #f8f9fa;
    border-radius: 6px;
    margin-bottom: 8px;
    border-left: 3px solid #007bff;
    transition: all 0.2s ease;
}

.spec-item:hover {
    background: #e9ecef;
    transform: translateX(2px);
}

.spec-item i {
    width: 20px;
    text-align: center;
    font-size: 1.1rem;
}

.spec-item strong {
    margin-right: 5px;
    color: #343a40;
}

.spec-value {
    color: #495057;
    flex-grow: 1;
}

/* ===== LOADING SPINNER ===== */
.loading-spinner {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 40px;
}

.spinner-border {
    width: 3rem;
    height: 3rem;
}

/* ===== WIZARD CONTAINER ===== */
.wizard-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.wizard-header {
    background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
    color: white;
    padding: 30px;
    border-radius: 15px;
    margin-bottom: 30px;
    text-align: center;
}

.wizard-steps {
    display: flex;
    justify-content: center;
    margin-bottom: 30px;
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.wizard-steps .step {
    flex: 1;
    text-align: center;
    padding: 15px 20px; /* More horizontal padding */
    position: relative;
    background: white; /* Ensure background is white */
}

.wizard-steps .step:not(:last-child)::after {
    content: '';
    position: absolute;
    top: 20px; /* Position line at the circle level, not at 50% */
    right: -50%;
    width: 100%;
    height: 2px;
    background: #e5e7eb;
    z-index: 0;
}

.wizard-steps .step.active::after {
    background: #3b82f6;
}

/* Ensure step content is above the line */
.wizard-steps .step {
    position: relative;
    z-index: 2; /* Higher z-index to be above the line */
}

.wizard-steps .step-number {
    position: relative;
    z-index: 3; /* Even higher for the circle */
}

.wizard-steps .step-title,
.wizard-steps .step-description {
    position: relative;
    z-index: 2; /* Above the line */
}

.wizard-steps .step-number {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e5e7eb;
    color: #6b7280;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 10px;
    font-weight: bold;
    transition: all 0.3s ease;
}

.wizard-steps .step.active .step-number {
    background: #3b82f6;
    color: white;
}

.wizard-steps .step.completed .step-number {
    background: #10b981;
    color: white;
}

.wizard-steps .step-title {
    font-weight: 600;
    color: #374151;
    margin-bottom: 5px;
    margin-top: 10px; /* Add top margin to create space from line */
    position: relative;
    z-index: 2;
}

.wizard-steps .step.active .step-title {
    color: #3b82f6;
}

.wizard-steps .step-description {
    font-size: 12px;
    color: #6b7280;
    position: relative;
    z-index: 2;
}

.wizard-content {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    min-height: 500px;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
    .wizard-steps {
        flex-direction: column;
    }
    
    .wizard-steps .step:not(:last-child)::after {
        display: none;
    }
    
    .step-navigation {
        flex-direction: column;
        gap: 15px;
    }
    
    .nav-buttons {
        width: 100%;
        justify-content: center;
    }
}
</style>

<div class="wizard-container">
    <!-- Wizard Header -->
    <div class="wizard-header">
        <h2 class="mb-2">
            <i class="fas fa-file-invoice me-3"></i>
            {{ isset($quotation) ? 'Edit Quotation' : 'Create New Quotation' }}
        </h2>
        <p class="mb-0">{{ isset($quotation) ? 'Update the details below to modify the quotation' : 'Follow the steps below to create a new quotation' }}</p>
    </div>

    <!-- Wizard Steps Navigation -->
    <div class="wizard-steps">
        <div class="step active" data-step="1">
            <div class="step-number">1</div>
            <div class="step-title">Marketing Info</div>
            <div class="step-description">Select marketing & type</div>
        </div>
        <div class="step disabled" data-step="2">
            <div class="step-number">2</div>
            <div class="step-title">Survey Selection</div>
            <div class="step-description">Choose surveys</div>
        </div>
        <div class="step disabled" data-step="3">
            <div class="step-number">3</div>
            <div class="step-title">Room Selection</div>
            <div class="step-description">Select rooms</div>
        </div>
        <div class="step disabled" data-step="4">
            <div class="step-number">4</div>
            <div class="step-title">Unit Configuration</div>
            <div class="step-description">Configure units</div>
        </div>
        <div class="step disabled" data-step="5">
            <div class="step-number">5</div>
            <div class="step-title">Remark & Discount</div>
            <div class="step-description">Add remarks</div>
        </div>
        <div class="step disabled" data-step="6">
            <div class="step-number">6</div>
            <div class="step-title">PIC Selection</div>
            <div class="step-description">Choose PIC</div>
        </div>
        <div class="step disabled" data-step="7">
            <div class="step-number">7</div>
            <div class="step-title">Summary</div>
            <div class="step-description">Review & submit</div>
        </div>
    </div>

    <!-- Wizard Content -->
    <div class="wizard-content">
                    <form id="quotationWizardForm" method="POST" action="{{ isset($quotation) ? route('marketing.quotations.wizard.update', $quotation->id) : route('marketing.quotations.wizard.store') }}" novalidate>
                        @csrf
                        @if(isset($quotation))
                            @method('PUT')
                        @endif
                        <!-- Hidden input for action (draft/finalize) -->
                        <input type="hidden" name="action" id="formAction" value="draft">
                        
                        <!-- Step 1: Data Quotation -->
                        <div class="wizard-step active" id="step-1">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-file-invoice me-2"></i>
                                        Data Quotation
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="marketing_id" class="form-label">
                                                    Marketing <span class="required-indicator"></span>
                                                </label>
                                                <select class="form-control" id="marketing_id" name="marketing_id" required>
                                                    <option value="">Pilih marketing...</option>
                                                    @foreach($marketingUsers as $user)
                                                        <option value="{{ $user->id }}" {{ (isset($quotation) && $quotation->marketing_id == $user->id) || auth()->id() == $user->id ? 'selected' : '' }}>
                                                            {{ ($user->salutation ? $user->salutation . ' ' : '') . $user->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <div class="invalid-feedback"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="quotation_type" class="form-label">
                                                    Jenis Penawaran <span class="required-indicator"></span>
                                                </label>
                                                <select class="form-control" id="quotation_type" name="quotation_type" required>
                                                    <option value="">Pilih jenis penawaran...</option>
                                                    <option value="new">New</option>
                                                    <option value="renewal">Renewal</option>
                                                </select>
                                                <div class="invalid-feedback"></div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Branch Selection Field -->
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group" id="branch-field-container">
                                                <label for="branch_id" class="form-label">
                                                    Cabang <span class="required-indicator"></span>
                                                </label>
                                                <select class="form-control" id="branch_id" name="branch_id" required>
                                                    <option value="">Pilih marketing terlebih dahulu...</option>
                                                </select>
                                                <div class="branch-readonly-display" style="display: none; padding: 10px; background: #f8f9fa; border-radius: 6px; border: 1px solid #dee2e6;">
                                                    <i class="fas fa-building me-2"></i>
                                                    <span class="branch-name"></span>
                                                    <input type="hidden" name="branch_id" id="branch_id_hidden" value="">
                                                </div>
                                                <div class="invalid-feedback"></div>
                                                <small class="text-muted">Cabang menentukan kode nomor quotation</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="quotation_date" class="form-label">
                                                    Tanggal Quotation <span class="required-indicator"></span>
                                                </label>
                                                <input
                                                    type="date"
                                                    class="form-control"
                                                    id="quotation_date"
                                                    name="quotation_date"
                                                    value="{{ old('quotation_date', isset($quotation) && $quotation->quotation_date ? $quotation->quotation_date->format('Y-m-d') : now()->toDateString()) }}"
                                                    required
                                                >
                                                <div class="invalid-feedback"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Contract field (hidden by default) -->
                                    <div class="row" id="contract-field">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="existing_contract_id" class="form-label">
                                                    Existing Contract <span class="required-indicator"></span>
                                                </label>
                                                <select class="form-control" id="existing_contract_id" name="existing_contract_id">
                                                    <option value="">Pilih contract...</option>
                                                    <!-- Will be populated via AJAX -->
                                                </select>
                                                <div class="invalid-feedback"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="rental_period" class="form-label">
                                                    Periode Sewa <span class="required-indicator"></span>
                                                </label>
                                                <input type="number" class="form-control" id="rental_period" name="rental_period" min="1" required>
                                                <div class="invalid-feedback"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="rental_unit" class="form-label">
                                                    Satuan <span class="required-indicator"></span>
                                                </label>
                                                <select class="form-control" id="rental_unit" name="rental_unit" required>
                                                    <option value="">Pilih satuan...</option>
                                                    <option value="hari">Hari</option>
                                                    <option value="bulan">Bulan</option>
                                                </select>
                                                <div class="invalid-feedback"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="payment_method" class="form-label">
                                                    Payment Method <span class="required-indicator"></span>
                                                </label>
                                                <select class="form-control" id="payment_method" name="payment_method" required>
                                                    <option value="">Pilih payment method...</option>
                                                    <option value="Before Service">Before Service</option>
                                                    <option value="After Service">After Service</option>
                                                </select>
                                                <div class="invalid-feedback"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="term_of_payment" class="form-label">
                                                    Term of Payment <span class="required-indicator"></span>
                                                </label>
                                                <select class="form-control" id="term_of_payment" name="term_of_payment" required>
                                                    <option value="">Pilih term of payment...</option>
                                                    <option value="1 bulan 1x">1 bulan 1x</option>
                                                    <option value="2 bulan 1x">2 bulan 1x</option>
                                                    <option value="3 bulan 1x">3 bulan 1x</option>
                                                    <option value="4 bulan 1x">4 bulan 1x</option>
                                                    <option value="5 bulan 1x">5 bulan 1x</option>
                                                    <option value="6 bulan 1x">6 bulan 1x</option>
                                                    <option value="Tahunan">Tahunan</option>
                                                    <option value="7 bulan 1x">7 bulan 1x</option>
                                                    <option value="8 bulan 1x">8 bulan 1x</option>
                                                    <option value="9 bulan 1x">9 bulan 1x</option>
                                                    <option value="10 bulan 1x">10 bulan 1x</option>
                                                    <option value="11 bulan 1x">11 bulan 1x</option>
                                                    <option value="13 bulan 1x">13 bulan 1x</option>
                                                    <option value="14 bulan 1x">14 bulan 1x</option>
                                                    <option value="15 bulan 1x">15 bulan 1x</option>
                                                    <option value="16 bulan 1x">16 bulan 1x</option>
                                                    <option value="17 bulan 1x">17 bulan 1x</option>
                                                    <option value="18 bulan 1x">18 bulan 1x</option>
                                                    <option value="19 bulan 1x">19 bulan 1x</option>
                                                    <option value="20 bulan 1x">20 bulan 1x</option>
                                                    <option value="21 bulan 1x">21 bulan 1x</option>
                                                    <option value="22 bulan 1x">22 bulan 1x</option>
                                                    <option value="23 bulan 1x">23 bulan 1x</option>
                                                    <option value="2 tahunan">2 tahunan</option>
                                                    <option value="3 tahunan">3 tahunan</option>
                                                </select>
                                                <div class="invalid-feedback"></div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                            
                            <!-- Step 1 Navigation -->
                            <div class="step-navigation" id="step-1-nav">
                                <div class="nav-buttons">
                                    <button type="button" class="btn btn-wizard btn-wizard-primary" id="nextBtn">
                                        Next<i class="fas fa-arrow-right ms-2"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Survey Selection -->
                        <div class="wizard-step" id="step-2">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-clipboard-list me-2"></i>
                                        Survey Selection
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label for="survey_tags" class="form-label">
                                            Pilih Survey <span class="required-indicator"></span>
                                        </label>
                                        <select class="form-control" id="survey_tags" name="survey_tags[]" multiple required>
                                            <option value="">Pilih survey...</option>
                                        </select>
                                        <div class="invalid-feedback"></div>
                                        <div class="form-text">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Pilih survey yang akan digunakan untuk quotation ini. 
                                            Setelah memilih survey pertama, hanya survey dari customer yang sama yang akan ditampilkan.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Step 2 Navigation -->
                            <div class="step-navigation" id="step-2-nav">
                                <div class="nav-buttons">
                                    <button type="button" class="btn btn-wizard btn-wizard-secondary" id="prevBtn">
                                        <i class="fas fa-arrow-left me-2"></i>Previous
                                    </button>
                                    <button type="button" class="btn btn-wizard btn-wizard-primary" id="nextBtn">
                                        Next<i class="fas fa-arrow-right ms-2"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Room Selection -->
                        <div class="wizard-step" id="step-3">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-door-open me-2"></i>
                                        Pilih Ruangan
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div id="room-selection-container">
                                        <!-- Room selection tables will be loaded here -->
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Add Room Button -->
                           <!-- <div class="text-center mt-4 mb-3">
                                <button type="button" class="btn btn-success" id="add-new-room-btn" onclick="openAddRoomModal()">
                                    <i class="fas fa-plus me-2"></i>Tambah Ruangan Baru
                                </button>
                                <small class="d-block text-muted mt-2">Tambahkan ruangan custom untuk quotation ini</small>
                            </div> -->
                            
                            <!-- Step 3 Navigation -->
                            <div class="step-navigation" id="step-3-nav">
                                <div class="nav-buttons">
                                    <button type="button" class="btn btn-wizard btn-wizard-secondary" id="prevBtn">
                                        <i class="fas fa-arrow-left me-2"></i>Previous
                                    </button>
                                    <button type="button" class="btn btn-wizard btn-wizard-primary" id="nextBtn">
                                        Next<i class="fas fa-arrow-right ms-2"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Step 4: Konfigurasi Unit -->
                        <div class="wizard-step" id="step-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-cogs me-2"></i>
                                        Konfigurasi Unit
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div id="unit-configuration-container">
                                        <!-- Unit configuration tables will be loaded here -->
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i>
                                            Konfigurasi unit akan ditampilkan berdasarkan ruangan yang dipilih di step sebelumnya.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Step 4 Navigation -->
                            <div class="step-navigation" id="step-4-nav">
                                <div class="nav-buttons">
                                    <button type="button" class="btn btn-wizard btn-wizard-secondary" id="prevBtn">
                                        <i class="fas fa-arrow-left me-2"></i>Previous
                                    </button>
                                    <button type="button" class="btn btn-wizard btn-wizard-primary" id="nextBtn">
                                        Next<i class="fas fa-arrow-right ms-2"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Step 5: Remark & Discount -->
                        <div class="wizard-step" id="step-5">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-comment me-2"></i>
                                        Remark & Discount
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="remark_internal" class="form-label">Remark Internal</label>
                                                <textarea class="form-control" id="remark_internal" name="remark_internal" rows="4" placeholder="Catatan internal untuk tim..."></textarea>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="remark_external" class="form-label">Remark External</label>
                                                <textarea class="form-control" id="remark_external" name="remark_external" rows="4" placeholder="Catatan yang akan dikirim ke customer..."></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <hr class="my-4">
                                    
                                    <!-- Summary Section -->
                                    <div class="row">
                                        <div class="col-md-12">
                                            <h6 class="text-primary mb-3">
                                                <i class="fas fa-calculator me-2"></i>Summary
                                            </h6>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">Sub Total</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">Rp</span>
                                                    <input type="text" class="form-control" id="sub_total" name="sub_total" readonly placeholder="0">
                                                </div>
                                            </div>
                                        </div>
                                        <!-- PPN removed per report-mom5.md: Quotation & Contract should show total price without PPN -->
                                        <!-- PPN will be added later in Invoice, taken from tax_settings based on effective date -->
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="price_basis" class="form-label">Harga Basis <span class="required-indicator"></span></label>
                                                <select class="form-control" id="price_basis" name="price_basis" required>
                                                    <option value="">Pilih Basis Harga...</option>
                                                    <option value="room">Basis Ruangan</option>
                                                    <option value="rental">Basis Rental</option>
                                                </select>
                                                <small class="text-muted">
                                                    <strong>Basis Ruangan:</strong> Summary menampilkan nama ruangan & total (tanpa qty)<br>
                                                    <strong>Basis Rental:</strong> Summary menampilkan detail rental (tanpa nama ruangan)
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label class="form-label">Total Penawaran</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">Rp</span>
                                                    <input type="text" class="form-control" id="total_penawaran" name="total_penawaran" readonly placeholder="0">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Step 5 Navigation -->
                            <div class="step-navigation" id="step-5-nav">
                                <div class="nav-buttons">
                                    <button type="button" class="btn btn-wizard btn-wizard-secondary" id="prevBtn">
                                        <i class="fas fa-arrow-left me-2"></i>Previous
                                    </button>
                                    <button type="button" class="btn btn-wizard btn-wizard-primary" id="nextBtn">
                                        Next<i class="fas fa-arrow-right ms-2"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Step 6: PIC Selection -->
                        <div class="wizard-step" id="step-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-user-check me-2"></i>
                                        PIC Selection
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label for="pic_quotation" class="form-label">
                                            PIC Customer / Penerima Kuotasi <span class="required-indicator"></span>
                                        </label>
                                        <select class="form-control" id="pic_quotation" name="pic_quotation" required>
                                            <option value="">Pilih PIC Customer...</option>
                                        </select>
                                        <div class="invalid-feedback"></div>
                                        <small class="text-muted">Pilih PIC dari pihak customer yang akan menerima quotation ini</small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Step 6 Navigation -->
                            <div class="step-navigation" id="step-6-nav">
                                <div class="nav-buttons">
                                    <button type="button" class="btn btn-wizard btn-wizard-secondary" id="prevBtn">
                                        <i class="fas fa-arrow-left me-2"></i>Previous
                                    </button>
                                    <button type="button" class="btn btn-wizard btn-wizard-primary" id="nextBtn">
                                        Next<i class="fas fa-arrow-right ms-2"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Step 7: Summary -->
                        <div class="wizard-step" id="step-7">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-check-circle me-2"></i>
                                        Summary
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div id="summary-content">
                                        <!-- Summary content will be loaded here -->
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Step 7 Navigation -->
                            <div class="step-navigation" id="step-7-nav">
                                <div class="nav-buttons">
                                    <button type="button" class="btn btn-wizard btn-wizard-secondary" id="prevBtn">
                                        <i class="fas fa-arrow-left me-2"></i>Previous
                                    </button>
                                </div>
                                <div class="final-buttons">
                                    <button type="submit" class="btn btn-wizard btn-wizard-success" id="submitBtn">
                                        <i class="fas fa-save me-2"></i>{{ isset($quotation) ? 'Update Draft' : 'Save Draft' }}
                                    </button>
                                    <button type="button" class="btn btn-wizard btn-wizard-success" id="finalizeBtn">
                                        <i class="{{ isset($quotation) ? 'fas fa-check-double' : 'fas fa-check' }} me-2"></i>{{ isset($quotation) ? 'Update & Finalize' : 'Get Approval / Finalize' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
    </div>
</div>

<!-- Add Room Modal -->
<div class="modal fade" id="addRoomModal" tabindex="-1" aria-labelledby="addRoomModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="addRoomModalLabel">
                    <i class="fas fa-plus-circle me-2"></i>Tambah Ruangan Baru
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addRoomForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="custom_room_name" class="form-label">Nama Ruangan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="custom_room_name" required placeholder="Contoh: Lobby Utama">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="custom_room_type" class="form-label">Tipe Ruangan <span class="text-danger">*</span></label>
                                <select class="form-control" id="custom_room_type" required>
                                    <option value="">Pilih tipe ruangan...</option>
                                    @foreach($roomTypes as $type)
                                        <option value="{{ $type->option_name }}">{{ $type->option_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="custom_room_floor" class="form-label">Lantai</label>
                                <select class="form-control" id="custom_room_floor">
                                    <option value="">Pilih lantai...</option>
                                    @foreach($floors as $floor)
                                        <option value="{{ $floor->option_name }}">{{ $floor->option_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="custom_room_area" class="form-label">Luas Area (m²)</label>
                                <input type="number" class="form-control" id="custom_room_area" step="0.01" placeholder="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="custom_room_qty" class="form-label">Quantity</label>
                                <input type="number" class="form-control" id="custom_room_qty" min="1" value="1">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="custom_room_length" class="form-label">Panjang (m)</label>
                                <input type="number" class="form-control" id="custom_room_length" step="0.01" placeholder="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="custom_room_width" class="form-label">Lebar (m)</label>
                                <input type="number" class="form-control" id="custom_room_width" step="0.01" placeholder="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="custom_room_height" class="form-label">Tinggi (m)</label>
                                <input type="number" class="form-control" id="custom_room_height" step="0.01" placeholder="0">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="custom_room_intensity" class="form-label">Intensitas</label>
                                <select class="form-control" id="custom_room_intensity">
                                    <option value="">Pilih intensitas...</option>
                                    @foreach($intensities as $intensity)
                                        <option value="{{ $intensity->option_name }}">{{ $intensity->option_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="custom_room_installation" class="form-label">Tipe Instalasi</label>
                                <select class="form-control" id="custom_room_installation">
                                    <option value="">Pilih tipe instalasi...</option>
                                    @foreach($installationTypes as $type)
                                        <option value="{{ $type->option_name }}">{{ $type->option_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="custom_room_temperature" class="form-label">Temperatur</label>
                                <input type="text" class="form-control" id="custom_room_temperature" placeholder="Contoh: 20-25°C">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="custom_room_remark" class="form-label">Catatan</label>
                                <textarea class="form-control" id="custom_room_remark" rows="2" placeholder="Catatan tambahan untuk ruangan ini..."></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Batal
                </button>
                <button type="button" class="btn btn-success" onclick="saveCustomRoom()">
                    <i class="fas fa-plus me-2"></i>Tambah Ruangan
                </button>
            </div>
        </div>
    </div>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
<!-- Bootstrap Bundle JS (for Modal) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    console.log("Create Blade Version: FIX_SYNTAX_V9_" + Date.now());
    
    // ===== GLOBAL VARIABLES (Moved out of $(document).ready for scope accessibility) =====
    window.rentalAliasOptions = @json($rentalAliases);
    let currentStep = 1;
    const totalSteps = 7;
    let globalSurveySelections = [];
    let globalRoomSelections = []; 
    let selectedSurveys = [];
    let selectedRooms = [];
    let rentalConfigurations = [];
    let aromaProducts = []; 
    let customRooms = []; 
    let customRoomIdCounter = 1; 

    // ===== GLOBAL HELPERS MOVED TO TOP TO PREVENT RACE CONDITIONS =====
    window.isSelectableAromaProductOption = function(product) {
        if (!product) return false;

        const name = String(product.name || product.display_name || '').toLowerCase();
        const variant = String(product.variant || product.variant_name || '').toLowerCase();
        const sku = String(product.sku || '').toLowerCase();

        return !name.includes('test')
            && !variant.includes('test')
            && !sku.includes('test')
            && !/^ta\d*/i.test(String(product.sku || ''));
    };

    window.normalizeAromaProductsList = function(products) {
        if (!Array.isArray(products)) return [];

        return products.filter(window.isSelectableAromaProductOption);
    };

    window.hasAromaProductOption = function(productId) {
        if (!productId || !window.aromaProductsList) return false;

        return window.aromaProductsList.some(function(product) {
            return String(product.id) === String(productId);
        });
    };
    
    window.renderRoomRows = function(rooms, surveyId) {
        if (!rooms || rooms.length === 0) {
            return '<tr><td colspan="4" class="text-center text-muted"><i class="fas fa-info-circle me-2"></i>Tidak ada data ruangan untuk survey ini</td></tr>';
        }

        var html = '';
        for (var i = 0; i < rooms.length; i++) {
            var room = rooms[i];
            var roomIndex = i;
            var specs = {};
            try {
                specs = room.specifications ? JSON.parse(room.specifications) : {};
            } catch (e) {
                console.error('Error parsing room specs:', e);
            }
            
            html += '<tr>' +
                '<td><input type="checkbox" class="form-check-input room-checkbox" name="selected_rooms[' + surveyId + '][]" value="' + room.id + '" data-survey="' + surveyId + '" data-room="' + room.id + '" data-master-room-id="' + (room.room_id || '') + '"></td>' +
                '<td>' + (roomIndex + 1) + '</td>' +
                '<td><strong>' + (room.room_name || '-') + '</strong><br><small class="text-muted">' + (room.room_type || '-') + '</small></td>' +
                '<td><div class="room-specs"><div class="row">' +
                    '<div class="col-md-6">' +
                        '<div class="spec-item mb-2"><i class="fas fa-door-open me-2 text-primary"></i><strong>Room Type:</strong> ' + (room.room_type || '-') + '</div>' +
                        '<div class="spec-item mb-2"><i class="fas fa-expand-arrows-alt me-2 text-success"></i><strong>Room Area:</strong> ' + (room.room_area || 0) + ' m²</div>' +
                        '<div class="spec-item mb-2"><i class="fas fa-hashtag me-2 text-info"></i><strong>Quantity:</strong> ' + (room.quantity_needed || 1) + '</div>' +
                    '</div>' +
                    '<div class="col-md-6">' +
                        '<div class="spec-item mb-2"><i class="fas fa-layer-group me-2 text-purple"></i><strong>Floor:</strong> ' + (specs.floor || '-') + '</div>' +
                        '<div class="spec-item mb-2"><i class="fas fa-tint me-2 text-cyan"></i><strong>Intensity:</strong> ' + (specs.intensity || '-') + '</div>' +
                        '<div class="spec-item mb-2"><i class="fas fa-tools me-2 text-orange"></i><strong>Installation:</strong> ' + (specs.installation_type || '-') + '</div>' +
                        '<div class="spec-item mb-2"><i class="fas fa-ruler-horizontal me-2 text-green"></i><strong>Length:</strong> ' + (specs.length || '-') + '</div>' +
                        '<div class="spec-item mb-2"><i class="fas fa-ruler-vertical me-2 text-green"></i><strong>Width:</strong> ' + (specs.width || '-') + '</div>' +
                        '<div class="spec-item mb-2"><i class="fas fa-arrows-alt-v me-2 text-red"></i><strong>Height:</strong> ' + (specs.height || '-') + '</div>' +
                        '<div class="spec-item mb-2"><i class="fas fa-thermometer-half me-2 text-pink"></i><strong>Temperature:</strong> ' + (specs.temperature || '-') + '</div>' +
                        '<div class="spec-item mb-2"><i class="fas fa-sticky-note me-2 text-yellow"></i><strong>Remark:</strong> ' + (specs.remark || '-') + '</div>' +
                    '</div>' +
                '</div></div></td></tr>';
        }
        return html;
    };

    window.restoreAromasHelperV2 = function() {
        console.log('Executing restoreAromasHelperV2 (Global)...');
        if (!window.persistentAromaMap) return;
        
        const mapEntries = Object.entries(window.persistentAromaMap);
        for (let i = 0; i < mapEntries.length; i++) {
            const entry = mapEntries[i];
            const aromaData = entry[1];
            const resolvedRoomId = aromaData.resolvedRoomId;
            const aromaProductId = aromaData.aroma_product_id;
            
            if (aromaProductId && window.hasAromaProductOption(aromaProductId)) {
                const aromaSelect = $(`.aroma-select[data-room="${resolvedRoomId}"]`);
                if (aromaSelect.length > 0) {
                    aromaSelect.val(aromaProductId).trigger('change');
                    console.log('FINAL Restored aroma for room ' + resolvedRoomId + ': ' + aromaProductId);
                }
            } else if (aromaProductId) {
                console.warn('Skipped restoring invalid aroma product id:', aromaProductId);
            }
        }
        if (typeof window.updateNextButtonState === 'function') {
            window.updateNextButtonState();
        }
    };

    window.processAromaProductsResponseV2 = function(response) {
        console.log('Aroma products loaded (V2 Global):', response.length, 'products');
        window.aromaProductsList = window.normalizeAromaProductsList(response);
        
        // Update the local aromaProducts variable used in rebuildAromaDropdowns
        if (typeof aromaProducts !== 'undefined') {
            aromaProducts = window.aromaProductsList;
        }
        
        console.log('Aroma products loaded. Updating aroma dropdowns globally...');
        
        const surveysToRebuild = new Set();
        $('.room-checkbox:checked').each(function() {
            const sId = $(this).data('survey');
            if (sId) surveysToRebuild.add(sId);
        });
        
        surveysToRebuild.forEach(function(surveyId) {
            if (typeof window.rebuildAromaDropdowns === 'function') {
                window.rebuildAromaDropdowns(surveyId);
            }
        });

        if (window.persistentAromaMap && Object.keys(window.persistentAromaMap).length > 0) {
            console.log('Restoring values from persistentAromaMap:', window.persistentAromaMap);
            setTimeout(window.restoreAromasHelperV2, 100);
        }
    };

    window.handleRoomCheckboxChange = function() {
        const checkbox = $(this);
        const surveyId = checkbox.data('survey');
        console.log('Room checkbox changed. Survey:', surveyId);
        
        // Fix: Save current selections BEFORE rebuilding UI
        if (typeof updateRoomSelections === 'function') {
            updateRoomSelections();
        }
        
        if (typeof window.rebuildAromaDropdowns === 'function') {
            window.rebuildAromaDropdowns(surveyId);
        }
    };

    window.handleSelectAllCheckboxChange = function() {
        const selectAllCheckbox = $(this);
        const surveyId = selectAllCheckbox.data('survey-id');
        const isChecked = selectAllCheckbox.is(':checked');
        console.log('Select All checkbox changed for survey:', surveyId, 'checked:', isChecked);
        const roomCheckboxes = $(`.room-checkbox[data-survey="${surveyId}"]`);
        roomCheckboxes.each(function() {
            $(this).prop('checked', isChecked).trigger('change');
        });
        
        // Fix: Save current selections BEFORE rebuilding UI
        if (typeof updateRoomSelections === 'function') {
            updateRoomSelections();
        }
        
        if (typeof window.rebuildAromaDropdowns === 'function') {
            window.rebuildAromaDropdowns(surveyId);
        }
        
        if (typeof window.updateNextButtonState === 'function') {
            window.updateNextButtonState();
        }
    };

    window.handleAromaSelectChange = function() {
        console.log('Aroma select changed');
        if (typeof updateRoomSelections === 'function') {
            updateRoomSelections();
        }
    };

    window.loadAromaProducts = function() {
        console.log('Loading aroma products (Global)...');
        $.ajax({
            url: '/marketing/quotations/wizard/get-aroma-products',
            method: 'GET',
            success: window.processAromaProductsResponseV2,
            error: function(xhr, status, error) {
                console.error('Error loading aroma products:', error);
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Error', 'Failed to load aroma products', 'error');
                }
            }
        });
    };

    window.populateAromaDropdowns = function() {
        if (!window.aromaProductsList) return;
        console.log('Populating aroma dropdowns (Global)...');
        $('.aroma-select').each(function() {
            const select = $(this);
            const currentValue = select.data('selected-value');
            select.find('option:not(:first)').remove();
            window.aromaProductsList.forEach(function(product) {
                const option = $('<option></option>')
                    .val(product.id)
                    .text(product.display_name)
                    .data('variant', product.variant)
                    .data('packaging-size', product.packaging_size || '');
                if (currentValue && product.id == currentValue) {
                    option.prop('selected', true);
                }
                select.append(option);
            });
        });
    };

    window.setupRoomCheckboxHandlers = function() {
        $(document).off('change', '.room-checkbox').on('change', '.room-checkbox', window.handleRoomCheckboxChange);
        $(document).off('change', '.select-all-checkbox').on('change', '.select-all-checkbox', window.handleSelectAllCheckboxChange);
        $(document).off('change', '.aroma-select').on('change', '.aroma-select', window.handleAromaSelectChange);
    };

    window.rebuildAromaDropdowns = function(surveyId) {
        console.log('Rebuilding aroma dropdowns (Global) for survey:', surveyId);
        const checkedRooms = $(`.room-checkbox[data-survey="${surveyId}"]:checked`);
        const aromaSection = $(`#aroma-section-${surveyId}`);
        const aromaContainer = $(`#aroma-selection-container-${surveyId}`);
        aromaContainer.empty();
        if (checkedRooms.length === 0) {
            aromaSection.hide();
            return;
        }
        aromaSection.show();
        checkedRooms.each(function() {
            const checkbox = $(this);
            const roomId = checkbox.data('room');
            const masterRoomId = checkbox.data('master-room-id') || '';
            const roomRow = checkbox.closest('tr');
            const roomName = roomRow.find('td:eq(2) strong').text();
            const roomType = roomRow.find('td:eq(2) small').text();
            let optionsHtml = '<option value="">Pilih Aroma/Variant...</option>';
            if (window.aromaProductsList) {
                window.aromaProductsList.forEach(function(product) {
                    optionsHtml += `<option value="${product.id}" data-variant="${product.variant || ''}" data-packaging-size="${product.packaging_size || ''}">${product.display_name}</option>`;
                });
            }
            const dropdownHtml = `
                <div class="card mb-3" style="border-left: 4px solid #3b82f6;">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <h6 class="mb-0">
                                    <i class="fas fa-door-open me-2 text-primary"></i>${roomName}
                                </h6>
                                <small class="text-muted">${roomType}</small>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label mb-1"><strong>Pilih Aroma/Variant:</strong></label>
                                <select class="form-select aroma-select" 
                                        name="room_aroma[${surveyId}][${roomId}]" 
                                        data-survey="${surveyId}" 
                                        data-room="${roomId}"
                                        data-master-room-id="${masterRoomId}">
                                    ${optionsHtml}
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            aromaContainer.append(dropdownHtml);
            const existingSelection = window.globalRoomSelections ? 
                window.globalRoomSelections.find(function(rs) { 
                    // 1. Match by Detail ID
                    if (rs.room_id == roomId) return true;
                    // 2. Match by MasterRoom ID (Critical for Renewal)
                    if (masterRoomId && (rs.room_id == masterRoomId || rs.master_room_id == masterRoomId)) return true;
                    // 3. Match by Name (Exact)
                    const storedName = rs.room_name ? rs.room_name.trim().toLowerCase() : '';
                    const currentName = roomName ? roomName.trim().toLowerCase() : '';
                    if (storedName && currentName && storedName === currentName) return true;
                    
                    // 4. Match by Name (Fuzzy/Contains) + Type
                    if (storedName && currentName) {
                        const nameMatch = storedName.includes(currentName) || currentName.includes(storedName);
                        const storedType = rs.room_type ? rs.room_type.trim().toLowerCase() : '';
                        const currentType = roomType ? roomType.trim().toLowerCase() : '';
                        const typeMatch = !storedType || !currentType || storedType.includes(currentType) || currentType.includes(storedType);
                        
                        if (nameMatch && typeMatch) {
                            console.log(`Aroma Restoration: Fuzzy name match found! ("${storedName}" matches "${currentName}")`);
                            return true;
                        }
                    }
                    return false;
                }) : null;

            if (existingSelection && existingSelection.aroma_product_id && window.hasAromaProductOption(existingSelection.aroma_product_id)) {
                const selectElement = aromaContainer.find(`.aroma-select[data-room="${roomId}"]`);
                selectElement.attr('data-selected-value', existingSelection.aroma_product_id);
                selectElement.data('selected-value', existingSelection.aroma_product_id); // Ensure jQuery data cache is updated
                selectElement.val(existingSelection.aroma_product_id);
            } else if (existingSelection && existingSelection.aroma_product_id) {
                console.warn('Skipped invalid existing aroma product id:', existingSelection.aroma_product_id);
            }
        });
        window.populateAromaDropdowns();
    };

// ===== CLEAR QUOTATION WIZARD DATA FUNCTION (Global Scope) =====
// Clear all localStorage data related to quotation wizard
// This ensures a fresh start every time the wizard is opened
function clearQuotationWizardData() {
    const keysToRemove = [
        'quotation_step1_data',
        'quotation_step2_data',
        'quotation_step3_data',
        'quotation_step4_data',
        'quotation_step5_data',
        'quotation_step6_data',
        'quotation_survey_selections',
        'quotation_room_selections',
        'quotation_rental_configurations'
    ];
    
    keysToRemove.forEach(key => {
        localStorage.removeItem(key);
    });
    
    console.log('✓ Cleared all quotation wizard data from localStorage');
}

/**
 * Initialize wizard data from existing quotation (Edit Mode)
 */
function initializeFromExistingQuotation(data) {
    console.log('=== INITIALIZING WIZARD FROM EXISTING QUOTATION ===', data);
    
    // Set flag to prevent overwrites during restoration
    window.isRestoringData = true;
    
    // STEP 1: Basic Info
    // Handle legacy/malformed data (e.g. rental_period = "1 Bulan")
    let rentalPeriod = data.rental_period;
    let rentalUnit = data.rental_unit;
    
    // Parse partial data if rental_period contains unit
    if (rentalPeriod && isNaN(rentalPeriod) && typeof rentalPeriod === 'string') {
        const parts = rentalPeriod.split(' ');
        if (parts.length >= 1) {
            rentalPeriod = parts[0]; // Extract number
            if (!rentalUnit && parts.length >= 2) {
                rentalUnit = parts[1].toLowerCase(); // Extract unit (hari/bulan) check lower case
            }
        }
    }

    const step1Data = {
        marketing_id: data.marketing_id,
        branch_id: data.branch_id,
        quotation_date: data.quotation_date ? String(data.quotation_date).slice(0, 10) : null,
        quotation_type: data.quotation_type,
        existing_contract_id: data.existing_contract_id,
        rental_period: rentalPeriod,
        rental_unit: rentalUnit, 
        payment_method: data.payment_method || data.billing_methods, // Fallback to billing_methods
        term_of_payment: data.term_of_payment || data.terms_of_payment,
        quotation_id: data.id
    };
    localStorage.setItem('quotation_step1_data', JSON.stringify(step1Data));
    console.log('✓ Restored Step 1 Data');
    
    // STEP 2: Surveys
    let surveyIds = [];
    
    // Extract from details
    if (data.quotation_details && data.quotation_details.length > 0) {
        const detailSurveyIds = data.quotation_details.map(d => d.survey_id).filter(id => id != null);
        surveyIds = [...surveyIds, ...detailSurveyIds];
    }
    
    // Extract from attached surveys relation (Many-to-Many)
    if (data.surveys && data.surveys.length > 0) {
        const attachedSurveyIds = data.surveys.map(s => s.id);
        surveyIds = [...surveyIds, ...attachedSurveyIds];
    }
    
    // Extract from single survey relation
    if (data.survey_id && !surveyIds.includes(data.survey_id)) {
        surveyIds.push(data.survey_id);
    }
    
    // De-duplicate
    surveyIds = [...new Set(surveyIds)];
    
    console.log('Restored Survey IDs from all sources:', surveyIds);
    
    // We need to store globalSurveySelections as well because loadSurveys reads it
    // But format for step2 storage might be just the array?
    // Based on restoreSurveySelections implementation:
    // It reads 'quotation_survey_selections' (global var name used as key)
    localStorage.setItem('quotation_survey_selections', JSON.stringify(surveyIds));
    
    // Update global variable strictly
    if (typeof globalSurveySelections !== 'undefined') {
        globalSurveySelections = surveyIds;
    } else {
        window.globalSurveySelections = surveyIds;
    }
    console.log('✓ Restored Survey Selections:', surveyIds);

    // STEP 3: Room Selections
    // We infer room selections from quotation details AND quotation_rooms
    const roomSelections = [];
    const processedRooms = new Set();
    
    console.log('--- Room Extraction Debug ---');
    if (data.quotation_rooms) {
        console.log('Found quotation_rooms:', data.quotation_rooms);
        data.quotation_rooms.forEach(room => {
             // If QuotationRoom has room_id, usage it.
             // Note: survey_id might be missing in QuotationRoom, we might need to find it from details or context.
             // For now, if we can match room_id, we use it.
             if (room.room_id && !processedRooms.has(room.room_id)) {
                 console.log('Extracting from QuotationRoom:', room);
                 
                 // Try to find survey_id from details that share this room_id, or default to first survey?
                 // This is tricky if survey_id is not in QuotationRoom.
                 let surveyId = null;
                 if (data.quotation_details) {
                     const linkedDetail = data.quotation_details.find(d => d.room_id == room.room_id);
                     if (linkedDetail) surveyId = linkedDetail.survey_id;
                 }
                 if (!surveyId && data.surveys && data.surveys.length > 0) {
                     // Fallback: use the survey_id from the first attached survey (imperfect but better than nothing)
                     // Or check if room_id implies survey via some look up?
                     // For now, let's assume one survey context or try to match.
                     surveyId = data.surveys[0].id; // Fallback
                 }

                 roomSelections.push({
                     room_id: room.room_id,
                     survey_id: surveyId, 
                     aroma_product_id: room.aroma_product_id,
                     aroma_variant: room.aroma_variant,
                     room_name: room.room_name // Capture room name for fallback matching
                 });
                 processedRooms.add(room.room_id);
             }
        });
    }

    if (data.quotation_details) {
        data.quotation_details.forEach(detail => {
            console.log('Detail Keys:', Object.keys(detail));
            if (detail.room_id) {
                if (!processedRooms.has(detail.room_id)) {
                    roomSelections.push({
                        room_id: detail.room_id,
                        survey_id: detail.survey_id,
                        aroma_product_id: detail.product_id, // Map product to aroma selection
                        // If you have variant info stored, map it here
                        aroma_variant: detail.product ? (detail.product.variant || '') : ''
                    });
                    processedRooms.add(detail.room_id);
                     console.log('Added room selection from Detail:', detail.room_id);
                }
            }
        });
    }
    localStorage.setItem('quotation_room_selections', JSON.stringify(roomSelections));
    
    // Update global variable strictly
    if (typeof globalRoomSelections !== 'undefined') {
        globalRoomSelections = roomSelections;
    } else {
        window.globalRoomSelections = roomSelections;
    }
    console.log('✓ Restored Room Selections:', roomSelections.length, roomSelections);

    // STEP 4: Rental Configurations
    console.log('=== STEP 4: Processing quotation_details ===');
    console.log('quotation_details exists:', !!data.quotation_details);
    console.log('quotation_details length:', data.quotation_details ? data.quotation_details.length : 0);
    console.log('quotation_details data:', data.quotation_details);
    
    const rentalConfigs = [];
    if (data.quotation_details && data.quotation_details.length > 0) {
        data.quotation_details.forEach((detail, index) => {
            // Generate a unique ID or use existing ID if suitable
            // We use a format that won't collision easily
            const uniqueId = `edit-${detail.id}-${Date.now()}-${index}`;
            
            // Determine room name
            let roomName = '';
            let roomId = detail.room_id;

            // DEBUG: Log each detail to see what's missing
            console.log(`Detail ${index}:`, detail);

            if (detail.room) {
                roomName = detail.room.name || detail.room.room_name; // Handle both name fields
            } else if (detail.room_name) {
                roomName = detail.room_name;
            } else if (detail.rental_alias) {
                roomName = detail.rental_alias;
            } else {
                roomName = 'Rental Item';
            }

            // 1. INITIAL SURVEY ID GUESS
            let surveyId = detail.survey_id;
            if (!surveyId && detail.room) {
                surveyId = detail.room.survey_id;
            }

            // 2. RESOLVE ROOM ID (Fallback by Name)
            // If roomId is null, try to find it by name in the surveys
            if (!roomId && data.surveys) {
                for (const survey of data.surveys) {
                    // Check both casing styles
                    const details = survey.survey_details || survey.surveyDetails;
                    
                    if (details && details.length > 0) {
                        // 1. Try Name Match
                        let foundRoom = details.find(d => 
                            d.room_name && roomName && d.room_name.toLowerCase().trim() === roomName.toLowerCase().trim()
                        );
                        
                        // 2. Fallback: Use FIRST room if name match fails AND this is the expected survey
                        if (!foundRoom && surveyId && survey.id == surveyId) {
                             foundRoom = details[0];
                             console.warn(`Detail ${index}: Room name match failed for "${roomName}" in survey ${surveyId}. Fell back to first room: ${foundRoom.room_name} (${foundRoom.id})`);
                        } 
                        // 3. Last Resort: Use first room of first survey if we have absolutely nothing
                        else if (!foundRoom && !surveyId && data.surveys.indexOf(survey) === 0) {
                             foundRoom = details[0];
                             console.warn(`Detail ${index}: No surveyId and no name match for "${roomName}". Fell back to first room of first survey: ${foundRoom.room_name} (${foundRoom.id})`);
                             // If we find a room here, we MUST adopt its survey ID
                             if (!surveyId) surveyId = survey.id;
                        }

                        if (foundRoom) {
                            roomId = foundRoom.id;
                            // Update roomName to match the actual found room to avoid confusion
                            roomName = foundRoom.room_name; 
                            
                            // If we didn't have surveyId, we have it now
                            if (!surveyId) surveyId = survey.id;
                            
                            console.warn(`Detail ${index} resolved roomId ${roomId} using room "${roomName}" in survey ${surveyId}`);
                            break; // Stop once found
                        }
                    }
                }
            }
            
            // Ensure roomId is not undefined (so it survives JSON.stringify)
            if (roomId === undefined) roomId = null;

            // 3. FINAL SURVEY ID FALLBACK
            // Try to find room in loaded surveys using ID (reverse lookup if roomId was present initially)
            if (!surveyId && roomId && data.surveys) {
                for (const survey of data.surveys) {
                     const details = survey.survey_details || survey.surveyDetails;
                    if (details) {
                        const foundRoom = details.find(d => d.id == roomId);
                        if (foundRoom) {
                            surveyId = survey.id;
                            break;
                        }
                    }
                }
            }
            
            // Fallback to first survey
            if (!surveyId && data.surveys && data.surveys.length > 0) {
                surveyId = data.surveys[0].id;
                console.warn(`Detail ${index} missing survey_id. Fell back to first survey:`, surveyId);
            }

            // Handle DB field differences (master_rental_id vs product_id, unit_price vs price)
            // Backend sends master_rental_id (DB column) but wizard expects product_id
            const productId = detail.master_rental_id || detail.product_id;
            // Backend sends master_rental relation (with rental_name) but logic might look for product
            const productName = detail.product ? detail.product.name : (detail.master_rental ? detail.master_rental.rental_name : '');
            // Backend sends unit_price but wizard expects price
            const price = detail.unit_price || detail.price;

            const config = {
                uniqueId: uniqueId,
                surveyId: surveyId,
                roomId: roomId, 
                roomName: roomName,
                productName: productName,
                formData: {
                    room_id: roomId,
                    roomName: roomName,
                    product_id: productId,
                    productName: productName,
                    price: price,
                    quantity: detail.quantity,
                    remark: detail.remark || '',
                    rental_alias: detail.rental_alias
                }
            };
            rentalConfigs.push(config);
        });
    }
    
    // Save to both keys used by Step 4 logic
    localStorage.setItem('quotation_step4_data', JSON.stringify(rentalConfigs));
    
    // Create summary configs for the second key (used for summary view)
    const summaryConfigs = rentalConfigs.map(c => ({
        roomId: c.roomId,
        roomName: c.roomName,
        productId: c.formData.product_id,
        productName: c.productName,
        price: c.formData.price,
        quantity: c.formData.quantity,
        remark: c.formData.remark,
        rentalAlias: c.formData.rental_alias
    }));
    localStorage.setItem('quotation_rental_configurations', JSON.stringify(summaryConfigs));
    console.log('✓ Restored Rental Configurations:', rentalConfigs.length);

    // STEP 5: Price Basis & Remarks
    const step5Data = {
        remark_internal: data.internal_notes || '', // DB field mapping: internal_notes
        remark_external: data.additional_notes || '', // DB field mapping: additional_notes
        price_basis: data.price_basis || ''
    };
    
    localStorage.setItem('quotation_step5_data', JSON.stringify(step5Data));
    console.log('✓ Restored Step 5 Data');

    // STEP 6: PIC
    const step6Data = {
        pic_quotation: data.pic_name || '' // DB field mapping: pic_name
    };
    localStorage.setItem('quotation_step6_data', JSON.stringify(step6Data));
    console.log('✓ Restored Step 6 Data');
    
    
    // Clear restoration flag after sufficient delay
    // (Extended to 3000ms to ensure flag stays active during initial step display)
    setTimeout(() => {
        window.isRestoringData = false;
        console.log('✓ Restoration complete, flag cleared');
    }, 3000);
    
    // Show success message
    Swal.fire({
        icon: 'success',
        title: 'Edit Mode',
        text: 'Data quotation berhasil dimuat.',
        timer: 1500,
        showConfirmButton: false
    });
}

$(document).ready(function() {
    // Define existing quotation data from server
    @if(isset($quotation))
        const existingQuotation = @json($quotation);
        console.log('=== EXISTING QUOTATION FROM SERVER ===');
        console.log('existingQuotation keys:', Object.keys(existingQuotation));
        console.log('quotation_details exists?', 'quotation_details' in existingQuotation);
        console.log('quotationDetails exists?', 'quotationDetails' in existingQuotation);
        console.log('Full quotation data:', existingQuotation);
    @else
        const existingQuotation = null;
    @endif

    // Clear data on page load, BUT populate if editing
    clearQuotationWizardData();
    
    if (existingQuotation) {
        initializeFromExistingQuotation(existingQuotation);
    }
    

    // ===== BRANCH SELECTION HANDLER =====
    function loadUserBranches(userId, preselectedBranchId = null) {
        const branchSelect = $('#branch_id');
        const branchContainer = $('#branch-field-container');
        const readonlyDisplay = branchContainer.find('.branch-readonly-display');
        const branchNameSpan = readonlyDisplay.find('.branch-name');
        const hiddenInput = $('#branch_id_hidden');
        
        if (!userId) {
            branchSelect.html('<option value="">Pilih marketing terlebih dahulu...</option>').show();
            readonlyDisplay.hide();
            return;
        }
        
        branchSelect.html('<option value="">Memuat cabang...</option>');
        // Ensure dropdown is visible during loading (in case it was hidden by is_single logic)
        branchSelect.show();
        readonlyDisplay.hide();
        
        $.ajax({
            url: '{{ route("marketing.quotations.wizard.get-user-branches") }}',
            type: 'GET',
            data: { user_id: userId },
            success: function(response) {
                if (response.success) {
                    const branches = response.branches;
                    const primaryBranchId = response.primary_branch_id;
                    const isSingle = response.is_single;
                    
                    if (isSingle && branches.length === 1) {
                        // Single branch - show readonly display
                        branchSelect.hide().removeAttr('name');
                        readonlyDisplay.show();
                        branchNameSpan.text(branches[0].name + ' (' + (branches[0].code || branches[0].name.substring(0,3).toUpperCase()) + ')');
                        hiddenInput.val(branches[0].id).attr('name', 'branch_id');
                    } else if (branches.length > 0) {
                        // Multiple branches - show dropdown
                        branchSelect.show().attr('name', 'branch_id');
                        readonlyDisplay.hide();
                        hiddenInput.removeAttr('name');
                        
                        let options = '<option value="">Pilih cabang...</option>';
                        branches.forEach(function(branch) {
                            // Logic: Select if matches preselectedBranchId (priority) OR matches primaryBranchId (fallback if no preselection)
                            let isSelected = false;
                            if (preselectedBranchId) {
                                isSelected = (branch.id == preselectedBranchId);
                            } else {
                                isSelected = (branch.id == primaryBranchId);
                            }
                            
                            const selected = isSelected ? 'selected' : '';
                            const branchCode = branch.code || branch.name.substring(0,3).toUpperCase();
                            options += `<option value="${branch.id}" ${selected}>${branch.name} (${branchCode})</option>`;
                        });
                        branchSelect.html(options);
                    } else {
                        branchSelect.html('<option value="">Tidak ada cabang tersedia</option>').show();
                        readonlyDisplay.hide();
                    }
                    
                    updateNextButtonState();
                } else {
                    branchSelect.html('<option value="">Error: ' + (response.message || 'Gagal memuat cabang') + '</option>');
                }
            },
            error: function(xhr) {
                console.error('Error loading branches:', xhr);
                branchSelect.html('<option value="">Error memuat cabang</option>');
            }
        });
    }
    
    // Marketing selection change handler
    $('#marketing_id').change(function() {
        const userId = $(this).val();
        loadUserBranches(userId);
        
        // If renewal type is selected, reload contracts for new marketing user
        if ($('#quotation_type').val() === 'renewal' && $('#contract-field').hasClass('show')) {
            loadEligibleContracts();
        }
    });

    $('#branch_id, #branch_id_hidden').on('change', function() {
        if ($('#quotation_type').val() === 'renewal' && $('#contract-field').hasClass('show')) {
            $('#existing_contract_id').val('');
            loadEligibleContracts();
        }
    });
    
    // Load branches on page load if marketing is pre-selected
    // REMOVED: This is now handled by restoreStep1Data() inside showStep(1) 
    // to prevent race conditions during page initialization.
    /*
    const preselectedMarketing = $('#marketing_id').val();
    if (preselectedMarketing) {
        loadUserBranches(preselectedMarketing);
    }
    */

    // ===== STEP NAVIGATION =====
    function showStep(step) {
        console.log('Showing step:', step);
        
        // Re-enable all Next/Previous buttons when step changes (reset double-click prevention)
        if (typeof window.enableSubmitButton === 'function') {
            $('#nextBtn, #prevBtn').each(function() {
                window.enableSubmitButton(this);
            });
        }
        
        // Hide all steps
        $('.wizard-step').removeClass('active').hide();
        
        // Show current step
        const currentStepElement = $(`#step-${step}`);
        currentStepElement.addClass('active').show();
        
        // Update step indicators
        updateStepIndicator(step);
        
        // Update navigation buttons
        updateNavigationButtons(step);
        
        // Load step data (includes restore)
        // For step 1, restore will handle updateNextButtonState() internally
        loadStepData(step);
        
        // For other steps, update button state after a delay
        // Use longer delay for step 4 to ensure rental configs are restored
        if (step !== 1) {
            const delay = step === 4 ? 800 : 300;
            setTimeout(() => {
                updateNextButtonState();
                console.log('Button state updated for step:', step);
            }, delay);
        }
    }

    function updateStepIndicator(step) {
        $('.wizard-steps .step').each(function(index) {
            const stepNumber = index + 1;
            if (stepNumber < step) {
                $(this).removeClass('active disabled').addClass('completed');
            } else if (stepNumber === step) {
                $(this).removeClass('disabled completed').addClass('active');
            } else {
                $(this).removeClass('active completed').addClass('disabled');
            }
        });
    }

    function updateNavigationButtons(step) {
        // Hide all step navigations
        $('.step-navigation').hide();
        
        // Show current step navigation
        $(`#step-${step}-nav`).show();
        
        // Update button states
        if (step > 1) {
            $(`#step-${step}-nav #prevBtn`).show();
        } else {
            $(`#step-${step}-nav #prevBtn`).hide();
        }
        
        if (step < totalSteps) {
            $(`#step-${step}-nav #nextBtn`).show();
        } else {
            $(`#step-${step}-nav #nextBtn`).hide();
        }
    }

    // ===== STEP DATA LOADING =====
    function loadStepData(step) {
        switch(step) {
            case 1:
                // Restore data first, then update button state after delay
                restoreStep1Data();
                // updateNextButtonState() will be called inside restoreStep1Data()
                break;
            case 2:
                loadSurveys();
                // restoreSurveySelections(); // REMOVED to prevent race condition - loadSurveys handles it
                break;
            case 3:
                loadRooms();
                // restoreRoomSelections() is now called inside loadRooms() after AJAX success
                break;
            case 4:
                loadRentalConfiguration();
                // restoreStep4Data() will be called inside displayUnitConfiguration()
                break;
            case 5:
                // loadTaxSettings(); // Removed: PPN not shown in Quotation per report-mom5.md
                restoreStep5Data();
                calculateStep5Totals();
                break;
            case 6:
                loadPicContacts();
                // restoreStep6Data(); // REMOVED: Redundant and causes race condition. loadPicContacts handles restoration.
                break;
            case 7:
                console.log('=== LOADING STEP 7 SUMMARY ===');
                loadSummary();
                break;
        }
    }

    function loadPicContacts() {
        console.log('Loading PIC contacts...');
        const selectedSurveys = $('#survey_tags').val() || [];
        const picSelect = $('#pic_quotation');
        
        if (selectedSurveys.length === 0) {
            picSelect.html('<option value="">Pilih survey terlebih dahulu...</option>');
            return;
        }

        // Show loading state
        if (picSelect.children().length <= 1) { // Only showing "Pilih PIC Customer..."
            picSelect.html('<option value="">Loading contacts...</option>');
        }

        $.ajax({
            url: '{{ route("marketing.quotations.wizard.get-pic-contacts") }}',
            method: 'GET',
            data: { survey_ids: selectedSurveys },
            success: function(response) {
                console.log('PIC contacts loaded:', response);
                picSelect.empty().append('<option value="">Pilih PIC Customer...</option>');
                
                if (response.customer_contacts && response.customer_contacts.length > 0) {
                    response.customer_contacts.forEach(function(contact) {
                        const position = contact.position ? ` - ${contact.position}` : '';
                        const phone = contact.phone ? ` (${contact.phone})` : '';
                        const label = `${contact.name}${position}${phone}`;
                        
                        picSelect.append(`<option value="${contact.name}">${label}</option>`);
                    });
                } else {
                    picSelect.append('<option value="" disabled>Tidak ada kontak tersedia</option>');
                }
                
                // If value was restored, select it
                const savedData = localStorage.getItem('quotation_step6_data');
                let picToRestore = null;

                // Strategy 1: LocalStorage
                if (savedData) {
                    try {
                        const data = JSON.parse(savedData);
                        if (data.pic_quotation) {
                            picToRestore = data.pic_quotation;
                        }
                    } catch(e) { console.error('Error in PIC storage parse:', e); }
                }

                // Strategy 2: Renewal Data Fallback
                if (!picToRestore && 
                    window.renewalContractData && 
                    window.renewalContractData.pic_name) { // Assuming backend sends pic_name or similar
                    console.log('Using Renewal Data fallback for PIC');
                    picToRestore = window.renewalContractData.pic_name;
                }

                if (picToRestore) {
                    console.log('Restoring PIC selection:', picToRestore);
                    
                    // Try exact match first
                    let optionExists = picSelect.find(`option[value="${picToRestore}"]`).length > 0;
                    
                    if (optionExists) {
                        picSelect.val(picToRestore).trigger('change');
                    } else {
                        // Try to match by name if the value might be slightly different
                        picSelect.find('option').each(function() {
                            const optVal = $(this).val();
                            if (optVal && picToRestore && optVal.toLowerCase().trim() === picToRestore.toLowerCase().trim()) {
                                picSelect.val(optVal).trigger('change');
                                optionExists = true;
                                return false;
                            }
                        });
                    }
                    
                    if (!optionExists) {
                        console.warn(`PIC "${picToRestore}" not found in loaded contacts. Adding as temporary option.`);
                        // Add it as a new option so it can be selected
                        const label = picToRestore + ' (contact sudah tidak ada, pilih contact yang tersedia)';
                        const newOption = new Option(label, picToRestore, true, true);
                        picSelect.append(newOption).trigger('change');
                        optionExists = true;
                    }
                }
            },
            error: function(xhr) {
                console.error('Error loading PIC contacts:', xhr);
                picSelect.html('<option value="">Error loading contacts</option>');
            }
        });
    }
    
    // ===== SAVE/RESTORE FORM DATA =====
    function saveStep1Data() {
        if (window.isRestoringData || window.isPopulatingData) {
            console.log('Skipping saveStep1Data: Restoration/Population in progress');
            return;
        }
        
        const step1Data = {
            marketing_id: $('#marketing_id').val(),
            branch_id: $('#branch_id').val() || $('#branch_id_hidden').val(),
            quotation_date: $('#quotation_date').val(),
            quotation_type: $('#quotation_type').val(),
            existing_contract_id: $('#existing_contract_id').val(),
            rental_period: $('#rental_period').val(),
            rental_unit: $('#rental_unit').val(),
            payment_method: $('#payment_method').val(),
            term_of_payment: $('#term_of_payment').val()
        };
        localStorage.setItem('quotation_step1_data', JSON.stringify(step1Data));
        console.log('Step 1 data saved:', step1Data);
    }
    
    function restoreStep1Data() {
        const savedData = localStorage.getItem('quotation_step1_data');
        if (savedData) {
            try {
                const data = JSON.parse(savedData);
                console.log('=== RESTORING STEP 1 DATA ===');
                console.log('Saved data:', data);
                
                // Restore values immediately (synchronously)
                if (data.marketing_id) {
                    $('#marketing_id').val(data.marketing_id);
                    console.log('✓ Restored marketing_id:', data.marketing_id);
                    
                    // Manually load branches with pre-selection instead of triggering change
                    loadUserBranches(data.marketing_id, data.branch_id);
                } else {
                    // FALLBACK: If no marketing_id in storage, use the current selected value (default login)
                    const currentMarketing = $('#marketing_id').val();
                    if (currentMarketing) {
                        console.log('No saved marketing, loading branches for current selection:', currentMarketing);
                        loadUserBranches(currentMarketing);
                    }
                }
                
                if (data.quotation_type) {
                    $('#quotation_type').val(data.quotation_type);
                    console.log('✓ Restored quotation_type:', data.quotation_type);
                }
                if (data.quotation_date) {
                    $('#quotation_date').val(data.quotation_date);
                    console.log('✓ Restored quotation_date:', data.quotation_date);
                }
                if (data.rental_period) {
                    $('#rental_period').val(data.rental_period);
                    console.log('✓ Restored rental_period:', data.rental_period);
                }
                if (data.rental_unit) {
                    $('#rental_unit').val(data.rental_unit);
                    console.log('✓ Restored rental_unit:', data.rental_unit);
                }
                if (data.payment_method) {
                    $('#payment_method').val(data.payment_method);
                    console.log('✓ Restored payment_method:', data.payment_method);
                }
                if (data.term_of_payment) {
                    $('#term_of_payment').val(data.term_of_payment);
                    console.log('✓ Restored term_of_payment:', data.term_of_payment);
                }
                
                // Verify values were set
                console.log('Verification after restore:');
                console.log('  marketing_id:', $('#marketing_id').val());
                console.log('  branch_id:', $('#branch_id').val() || $('#branch_id_hidden').val());
                console.log('  quotation_date:', $('#quotation_date').val());
                console.log('  quotation_type:', $('#quotation_type').val());
                console.log('  rental_period:', $('#rental_period').val());
                console.log('  rental_unit:', $('#rental_unit').val());
                console.log('  payment_method:', $('#payment_method').val());
                console.log('  term_of_payment:', $('#term_of_payment').val());
                
                // Trigger change events after a short delay (except marketing_id which we handled)
                setTimeout(() => {
                    console.log('--- TRIGGERING CHANGE EVENTS FOR RESTORATION ---');
                    
                    if (data.quotation_type) {
                        console.log('Triggering change for quotation_type:', data.quotation_type);
                        $('#quotation_type').trigger('change');
                    }
                    
                    console.log('Triggering change for other fields...');
                    $('#rental_period, #rental_unit, #payment_method, #term_of_payment').trigger('change');
                    
                    // Update button state after restore completes
                    setTimeout(() => {
                        console.log('Final updateNextButtonState call for Step 1 restoration');
                        updateNextButtonState();
                        console.log('✓ Step 1 restore completed, button state updated');
                    }, 1000); // Increased to 1000ms to be safe
                }, 200);
            } catch (e) {
                console.error('Error restoring Step 1 data:', e);
            }
        } else {
            console.log('No Step 1 data to restore');
            // Fix: Even if no saved data, if marketing_id is already selected (e.g. default to current user),
            // we should still load the branches for that marketing.
            const currentMarketing = $('#marketing_id').val();
            if (currentMarketing) {
                console.log('Marketing ID already selected, loading branches for current selection:', currentMarketing);
                loadUserBranches(currentMarketing);
            }
            // Still update button state even if no data to restore
            setTimeout(() => {
                updateNextButtonState();
            }, 100);
        }
    }
    
    function saveStep4Data() {
        if (window.isRestoringData || window.isPopulatingData) {
            console.log('Skipping saveStep4Data: Restoration/Population in progress');
            return;
        }
        const rentalConfigs = [];
        $('.rental-configuration').each(function() {
            const config = $(this);
            const uniqueId = config.attr('id') ? config.attr('id').replace('rental-config-', '') : '';
            if (!uniqueId) return; // Skip if no unique ID
            
            // Get room ID from data attribute or hidden input
            const roomId = config.data('room-id') || config.find('input[name*="[room_id]"]').val();
            const surveyId = config.data('survey-id');
            
            // Get room name
            const roomName = config.find('select[name*="[room_id]"] option:selected').text() || 
                           config.find('.room-display-container input[type="text"]').val() || '';
            
            // Get product name
            const productName = config.find('select[name*="[product_id]"] option:selected').text() || '';
            
            const formData = {
                room_id: roomId,
                roomName: roomName,
                product_id: config.find('select[name*="[product_id]"]').val() || '',
                productName: productName,
                price: config.find('input[name*="[price]"]').val() || '',
                quantity: config.find('input[name*="[quantity]"]').val() || '1',
                remark: config.find('input[name*="[remark]"]').val() || '',
                rental_alias: config.find('input[name*="[rental_alias]"]').val() || ''
            };
            
            rentalConfigs.push({
                uniqueId: uniqueId,
                surveyId: surveyId,
                roomId: roomId,
                roomName: roomName,
                productName: productName,
                formData: formData
            });
        });
        localStorage.setItem('quotation_step4_data', JSON.stringify(rentalConfigs));
        // Also save to a separate key for summary access
        const summaryConfigs = rentalConfigs.map(c => ({
            roomId: c.roomId,
            roomName: c.roomName || c.formData.roomName || '',
            productId: c.formData.product_id || '',
            productName: c.formData.productName || c.productName || '',
            price: c.formData.price || '',
            quantity: c.formData.quantity || '1',
            remark: c.formData.remark || '',
            rentalAlias: c.formData.rental_alias || ''
        })).filter(c => c.productId && c.productId !== ''); // Only save configs with product selected
        
        localStorage.setItem('quotation_rental_configurations', JSON.stringify(summaryConfigs));
        console.log('Step 4 data saved:', rentalConfigs.length, 'configurations');
        console.log('Summary configs saved:', summaryConfigs.length, 'configurations');
    }
    

    
    function saveStep5Data() {
        if (window.isRestoringData || window.isPopulatingData) {
            console.log('Skipping saveStep5Data: Restoration/Population in progress');
            return;
        }
        const step5Data = {
            remark_internal: $('#remark_internal').val() || '',
            remark_external: $('#remark_external').val() || '',
            price_basis: $('#price_basis').val() || ''
        };
        localStorage.setItem('quotation_step5_data', JSON.stringify(step5Data));
        console.log('Step 5 data saved:', step5Data);
    }
    
    function restoreStep5Data() {
        // Strategy 1: LocalStorage
        const savedData = localStorage.getItem('quotation_step5_data');
        let data = savedData ? JSON.parse(savedData) : {};
        
        // Strategy 2: Renewal Data Fallback
        // Check if data is empty (no internal/external remark) and renewal data exists
        if ((!data.remark_internal && !data.remark_external) && window.renewalContractData) {
             console.log('Using Renewal Data fallback for Step 5');
             if (window.renewalContractData.remark_internal) {
                 data.remark_internal = window.renewalContractData.remark_internal;
             }
             if (window.renewalContractData.remark_external) {
                 data.remark_external = window.renewalContractData.remark_external;
             }
             if (window.renewalContractData.price_basis) {
                 data.price_basis = window.renewalContractData.price_basis;
             }
        }

        if (savedData || (window.renewalContractData && window.renewalContractData.price_basis)) {
            try {
                console.log('Restoring Step 5 data:', data);
                console.log('Targeting fields - remark_internal:', $('#remark_internal').length, 'price_basis:', $('#price_basis').length);
                
                if (data.remark_internal) {
                    $('#remark_internal').val(data.remark_internal);
                }
                if (data.remark_external) {
                    $('#remark_external').val(data.remark_external);
                }
                if (data.price_basis) {
                    $('#price_basis').val(data.price_basis).trigger('change');
                }
            } catch (e) {
                console.error('Error restoring Step 5 data:', e);
            }
        }
    }
    
    function saveStep6Data() {
        if (window.isRestoringData || window.isPopulatingData) {
            console.log('Skipping saveStep6Data: Restoration/Population in progress');
            return;
        }
        const picSelect = $('#pic_quotation');
        const picVal = picSelect.val();
        
        // Defensive check: Don't save if dropdown is still loading or has no valid selection
        // especially during data restoration (isRestoringData).
        if (!picVal && (window.isRestoringData || picSelect.children('option').length <= 1)) {
            console.log('Skipping saveStep6Data: Dropdown is loading or restoring');
            return;
        }

        const step6Data = {
            pic_quotation: picVal || ''
        };
        localStorage.setItem('quotation_step6_data', JSON.stringify(step6Data));
        console.log('Step 6 data saved:', step6Data);
    }
    
    function restoreStep6Data() {
        const savedData = localStorage.getItem('quotation_step6_data');
        if (savedData) {
            try {
                const data = JSON.parse(savedData);
                console.log('Restoring Step 6 data:', data);
                
                if (data.pic_quotation) {
                    $('#pic_quotation').val(data.pic_quotation).trigger('change');
                }
            } catch (e) {
                console.error('Error restoring Step 6 data:', e);
            }
        }
    }

    // ===== SURVEY MANAGEMENT =====
    function loadSurveys() {
        const marketingId = $('#marketing_id').val();
        const surveySelect = $('#survey_tags');
        
        if (!marketingId) {
            console.log('No marketing selected');
            return;
        }

        // SMART LOAD: Check if surveys are already loaded for this marketing ID
        const loadedMarketingId = surveySelect.data('loaded-marketing-id');
        if (loadedMarketingId == marketingId && surveySelect.children('option').length > 1) {
            console.log('Surveys already loaded for marketing:', marketingId, '- skipping reload');
            restoreSurveySelections();
            return;
        }
        
        console.log('Loading surveys for marketing:', marketingId);
        
        // Clear existing options
        surveySelect.empty();
        
        // Destroy existing Select2
        if (surveySelect.hasClass('select2-hidden-accessible')) {
            surveySelect.select2('destroy');
        }
        
        // Show loading
        surveySelect.html('<option value="">Loading surveys...</option>');
        
        $.ajax({
            url: '{{ route("marketing.quotations.wizard.get-surveys-by-customer") }}',
            method: 'GET',
            data: { marketing_id: marketingId },
            success: function(response) {
                console.log('Surveys loaded:', response);
                
                // Clear loading option
                surveySelect.empty();
                
                // Add surveys to select
                if (response && response.length > 0) {
                    response.forEach(function(survey) {
                        console.log('Survey data:', survey);
                        console.log('Survey number:', survey.survey_number);
                        console.log('Customer data:', survey.customer);
                        console.log('Customer name:', survey.customer ? survey.customer.name : 'No customer');
                        
                        const option = $('<option></option>')
                            .val(survey.id)
                            .text(`${survey.survey_number} - ${survey.customer ? survey.customer.name : 'Unknown Customer'} - ${survey.building ? (survey.building.name || survey.building.nama_gedung) : 'Unknown Building'}`)
                            .data('customer', survey.customer ? survey.customer.name : 'Unknown Customer')
                            .data('company-type', survey.customer && survey.customer.company_type ? survey.customer.company_type.toUpperCase() : '-')
                            .data('building', survey.building ? (survey.building.name || survey.building.nama_gedung) : 'Unknown Building');
                        surveySelect.append(option);
                    });
                }
                
                // Initialize Select2
                surveySelect.select2({
                    placeholder: 'Pilih survey...',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $('body')
                });
                
                // Restore previous selections
                // Restore previous selections
                console.log('Attempting to restore survey selections in AJAX callback...');
                
                let selectionsToRestore = null;
                
                // Strategy 1: Global variable
                if (typeof globalSurveySelections !== 'undefined' && globalSurveySelections && globalSurveySelections.length > 0) {
                    console.log('Strategy 1: Found in globalSurveySelections:', globalSurveySelections);
                    selectionsToRestore = globalSurveySelections;
                }
                
                // Strategy 2: Window property (if shadowed)
                else if (typeof window.globalSurveySelections !== 'undefined' && window.globalSurveySelections && window.globalSurveySelections.length > 0) {
                     console.log('Strategy 2: Found in window.globalSurveySelections:', window.globalSurveySelections);
                     selectionsToRestore = window.globalSurveySelections;
                }
                
                // Strategy 3: LocalStorage
                else {
                    const stored = localStorage.getItem('quotation_survey_selections');
                    if (stored) {
                        try {
                            const parsed = JSON.parse(stored);
                            if (parsed && parsed.length > 0) {
                                console.log('Strategy 3: Found in localStorage:', parsed);
                                selectionsToRestore = parsed;
                                // Restore to global for consistency
                                window.globalSurveySelections = parsed;
                            }
                        } catch(e) { console.error('Error parsing localStorage survey selections', e); }
                    }
                }
                
                if (selectionsToRestore && selectionsToRestore.length > 0) {
                    // Convert to strings to ensure Select2 match
                    const stringSelections = selectionsToRestore.map(String);
                    console.log('Applying selections:', stringSelections);
                    
                    surveySelect.val(stringSelections).trigger('change');
                    
                    console.log('Survey select value after set:', surveySelect.val());
                } else {
                    console.warn('All strategies failed: No survey selections found to restore.');
                }
                
                // Mark as loaded for this marketing ID
                surveySelect.data('loaded-marketing-id', marketingId);
                
                // Update button state
                updateNextButtonState();
            },
            error: function(xhr, status, error) {
                console.error('Error loading surveys:', error);
                surveySelect.html('<option value="">Error loading surveys</option>');
                
                // Initialize Select2 even on error
                surveySelect.select2({
                    placeholder: 'Pilih survey...',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $('body')
                });
            }
        });
    }

    function saveSurveySelections() {
        if (window.isRestoringData || window.isPopulatingData) {
            console.log('Skipping saveSurveySelections: Restoration/Population in progress');
            return;
        }
        const selectedSurveys = $('#survey_tags').val() || [];
        globalSurveySelections = selectedSurveys;
        localStorage.setItem('quotation_survey_selections', JSON.stringify(globalSurveySelections));
        console.log('Survey selections saved:', globalSurveySelections);
    }

    function restoreSurveySelections() {
        if (globalSurveySelections.length > 0) {
            console.log('Restoring survey selections:', globalSurveySelections);
            setTimeout(function() {
                $('#survey_tags').val(globalSurveySelections).trigger('change');
                updateNextButtonState();
            }, 500);
        }
    }

    function saveRoomSelections() {
        if (window.isRestoringData || window.isPopulatingData) {
            console.log('Skipping saveRoomSelections: Restoration/Population in progress');
            return;
        }
        const selectedRooms = $('.room-checkbox:checked').map(function() { 
            return $(this).val(); 
        }).get();
        globalRoomSelections = selectedRooms;
        console.log('Room selections saved:', globalRoomSelections);
    }

    function restoreRoomSelections() {
        console.log('--- restoreRoomSelections called ---');
        window.isRestoringData = true; // Set guard flag
        
        let selectionsToRestore = null;
        
        // Strategy 1: Global variable
        if (typeof globalRoomSelections !== 'undefined' && globalRoomSelections && globalRoomSelections.length > 0) {
            console.log('Strategy 1: Found in globalRoomSelections:', globalRoomSelections);
            selectionsToRestore = globalRoomSelections;
        }
        
        // Strategy 2: Window property
        else if (typeof window.globalRoomSelections !== 'undefined' && window.globalRoomSelections && window.globalRoomSelections.length > 0) {
             console.log('Strategy 2: Found in window.globalRoomSelections:', window.globalRoomSelections);
             selectionsToRestore = window.globalRoomSelections;
        }
        
        // Strategy 3: LocalStorage
        else {
            const stored = localStorage.getItem('quotation_room_selections');
            if (stored) {
                try {
                    const parsed = JSON.parse(stored);
                    if (parsed && parsed.length > 0) {
                        console.log('Strategy 3: Found in localStorage:', parsed);
                        selectionsToRestore = parsed;
                        window.globalRoomSelections = parsed;
                    }
                } catch(e) { console.error('Error parsing localStorage room selections', e); }
            }
        }
        
        // Strategy 4: Renewal Data Fallback (Crucial for Renewal Flow)
        if ((!selectionsToRestore || selectionsToRestore.length === 0) && 
            window.renewalContractData && 
            window.renewalContractData.rooms && 
            window.renewalContractData.rooms.length > 0) {
            
            console.log('Strategy 4: Found in window.renewalContractData (Renewal Fallback)');
            selectionsToRestore = window.renewalContractData.rooms.map(room => ({
                room_id: room.survey_detail_id || room.room_id,
                survey_detail_id: room.survey_detail_id || null,
                master_room_id: room.master_room_id || room.room_id || null,
                contract_room_id: room.contract_room_id || null,
                room_name: room.room_name,
                survey_id: room.survey_id || window.renewalContractData.survey_id || 'custom',
                aroma_product_id: room.aroma_product_id || null,
                aroma_variant: room.aroma_variant || null,
                room_type: room.room_type || null
            }));
            // Update global to sync
            window.globalRoomSelections = selectionsToRestore;
        }

        if (selectionsToRestore && selectionsToRestore.length > 0) {
            console.log('Restoring room selections with Fallback Strategy:', selectionsToRestore);
            
            setTimeout(function() {
                // First pass: Check all checkboxes
                const surveysToUpdate = new Set();
                const resolvedIdMap = {}; // Map stored ID to actual DOM ID
                let restoredCount = 0;
                // Debug logs to see what's available in the DOM
                console.log('Available master-room-ids in DOM:');
                $('.room-checkbox').each(function() {
                    console.log(`- Value: ${$(this).val()}, MasterID: ${$(this).data('master-room-id')}, Name: ${$(this).closest('tr').find('td:eq(2) strong').text().trim()}`);
                });

                selectionsToRestore.forEach(function(room) {
                    const roomId = room.room_id || room; // Handle both object and simple ID
                    const masterRoomId = typeof room === 'object' ? room.master_room_id : null;
                    const roomName = room.room_name || (typeof room === 'object' ? room.room_name : null);
                    const roomType = room.room_type || (typeof room === 'object' ? room.room_type : null);

                    // Support both standard and custom rooms
                    let checkbox = $(`.room-checkbox[value="${roomId}"]`);
                    
                    // STRATEGY 2: Match by Master Room ID (Critical for Renewal)
                    if (checkbox.length === 0) {
                         checkbox = $(`.room-checkbox[data-master-room-id="${masterRoomId || roomId}"]`);
                         if (checkbox.length > 0) {
                             console.log(`MasterRoom ID match found! Mapping Room ${masterRoomId || roomId} -> Checkbox Value ${checkbox.val()}`);
                         }
                    }
                    
                    // FALLBACK: Match by Name if ID matches fail
                    if (checkbox.length === 0 && roomName) {
                         console.log(`ID matches failed for Room ${roomId}. Trying Name Match: "${roomName}" (Type: ${roomType || 'N/A'})`);
                         
                         // First Pass: Exact Match
                         $('.room-checkbox').each(function() {
                             const row = $(this).closest('tr');
                             const nameInRow = row.find('td:eq(2) strong').text().trim();
                             
                             if (nameInRow.toLowerCase() === roomName.trim().toLowerCase()) {
                                 console.log(`Found exact name match! Mapping Room ${roomId} -> Checkbox Value ${$(this).val()}`);
                                 checkbox = $(this);
                                 return false;
                             }
                         });

                         // Second Pass: Fuzzy Match (Contains) if still not found
                         if (checkbox.length === 0) {
                             $('.room-checkbox').each(function() {
                                 const row = $(this).closest('tr');
                                 const nameInRow = row.find('td:eq(2) strong').text().trim().toLowerCase();
                                 const typeInRow = row.find('td:eq(2) small').text().trim().toLowerCase();
                                 const targetName = roomName.trim().toLowerCase();
                                 const targetType = roomType ? roomType.trim().toLowerCase() : null;

                                 // Match if one contains the other AND types match (if provided)
                                 const nameMatch = nameInRow.includes(targetName) || targetName.includes(nameInRow);
                                 const typeMatch = !targetType || typeInRow.includes(targetType) || targetType.includes(typeInRow);

                                 if (nameMatch && typeMatch) {
                                     console.log(`Found fuzzy name match! ("${nameInRow}" matches "${targetName}") Mapping Room ${roomId} -> Checkbox Value ${$(this).val()}`);
                                     checkbox = $(this);
                                     return false;
                                 }
                             });
                         }
                    }

                    // MOM: If checkbox STILL not found and we have a room name, create it as a CUSTOM ROOM
                    // This handles cases where renewal rooms don't match the current survey master data
                    if (checkbox.length === 0 && roomName) {
                        console.log(`Room "${roomName}" not found in survey tables. Creating as Custom Room...`);
                        
                        // Create custom room object
                        // Use original ID if it looks like a custom ID or string, otherwise generate new
                        const customId = (roomId && typeof roomId === 'string' && roomId.startsWith('custom_')) 
                            ? roomId 
                            : 'custom_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5);
                            
                        const newCustomRoom = {
                            id: customId,
                            survey_id: (typeof room === 'object' ? room.survey_id : 'custom') || 'custom',
                            survey_detail_id: (typeof room === 'object' ? room.survey_detail_id : null) || null,
                            master_room_id: (typeof room === 'object' ? room.master_room_id : null) || null,
                            contract_room_id: (typeof room === 'object' ? room.contract_room_id : null) || null,
                            room_name: roomName,
                            room_type: roomType || 'Custom Room',
                            room_area: 0,
                            quantity_needed: 1,
                            is_custom: true,
                            specifications: JSON.stringify({
                                remark: 'Restored from Renewal Data'
                            })
                        };

                        // Add to customRooms array
                        if (!window.customRooms) {
                            window.customRooms = [];
                        }
                        
                        // Check if already exists by name to avoid duplicates
                        const exists = window.customRooms.find(r => r.room_name === roomName);
                        if (!exists) {
                            window.customRooms.push(newCustomRoom);
                            console.log('Added auto-generated custom room:', newCustomRoom);
                            
                            // Refresh custom rooms display
                            if (typeof window.displayCustomRooms === 'function') {
                                window.displayCustomRooms();
                                
                                // Now find the checkbox for this new room
                                checkbox = $(`.custom-room-checkbox[value="${newCustomRoom.id}"]`);
                                if (checkbox.length > 0) {
                                    console.log('Successfully created and found custom room checkbox:', newCustomRoom.id);
                                }
                            }
                        } else {
                            console.log('Custom room already exists, finding checkbox for:', exists.id);
                            checkbox = $(`.custom-room-checkbox[value="${exists.id}"]`);
                        }
                    }

                    if (checkbox.length > 0) {
                        const actualId = checkbox.val();
                        resolvedIdMap[roomId] = actualId; // Store mapping
                        
                        // Store aroma mapping persistently (survives updateRoomSelections)
                        if (!window.persistentAromaMap) window.persistentAromaMap = {};
                        if (room.aroma_product_id) {
                            window.persistentAromaMap[roomId] = {
                                resolvedRoomId: actualId,
                                master_room_id: room.master_room_id || null,
                                contract_room_id: room.contract_room_id || null,
                                room_name: room.room_name || null,
                                aroma_product_id: room.aroma_product_id,
                                aroma_variant: room.aroma_variant
                            };
                            console.log('Stored persistent aroma mapping:', roomId, '->', window.persistentAromaMap[roomId]);
                        }
                        
                        console.log('Found checkbox for room:', roomId, 'Actual ID:', actualId, 'checking it.');
                        checkbox.prop('checked', true);
                        const surveyId = checkbox.data('survey');
                        if (surveyId) {
                            surveysToUpdate.add(surveyId);
                        }
                        restoredCount++;
                    } else {
                        console.warn('Checkbox not found for room:', roomId, 'Name:', roomName);
                    }
                });

                console.log(`Restored ${restoredCount} rooms. Updating ${surveysToUpdate.size} surveys.`);

                // Second pass: Trigger updates for each affected survey to rebuild dropdowns
                surveysToUpdate.forEach(function(surveyId) {
                    console.log('Triggering update for survey:', surveyId);
                    rebuildAromaDropdowns(surveyId);
                });
                
                // Update button state
                updateNextButtonState();
                
                // Continue to restore specific aromas/custom rooms
                
                // For custom rooms, always try to rebuild
                if (typeof window.rebuildAromaDropdownsForCustomRooms === 'function') {
                    window.rebuildAromaDropdownsForCustomRooms();
                }

                // Third pass: Restore specific aroma selections
                setTimeout(function() {
                    const selectionsToIterate = (typeof globalRoomSelections !== 'undefined' && globalRoomSelections.length > 0) 
                        ? globalRoomSelections 
                        : selectionsToRestore;

                    selectionsToIterate.forEach(function(room) {
                        if (typeof room === 'object' && room.aroma_product_id) {
                            const resolvedRoomId = resolvedIdMap[room.room_id] || room.room_id;
                            let aromaSelect;
                            
                            if (room.survey_id === 'custom' || String(resolvedRoomId).startsWith('custom_')) {
                                aromaSelect = $(`.custom-aroma-select[data-room-id="${resolvedRoomId}"]`);
                            } else {
                                // Use resolvedRoomId to find the dropdown
                                aromaSelect = $(`.aroma-select[data-survey="${room.survey_id}"][data-room="${resolvedRoomId}"]`);
                                
                                // Fallback: try finding by just room ID if survey undefined/mismatch
                                if (aromaSelect.length === 0) {
                                     aromaSelect = $(`.aroma-select[data-room="${resolvedRoomId}"]`);
                                }
                            }
                            
                            if (aromaSelect.length > 0) {
                                aromaSelect.val(room.aroma_product_id).trigger('change');
                                console.log(`Restored aroma for room ${room.room_id} (Resolved: ${resolvedRoomId}): ${room.aroma_product_id}`);
                            } else {
                                console.warn(`Aroma select not found for room ${room.room_id} (Resolved: ${resolvedRoomId})`);
                            }
                        }
                    });
                    
                    updateNextButtonState();
                    window.isRestoringData = false; // Reset guard flag when done
                    console.log('✓ restoreRoomSelections: All restoration passes complete.');
                }, 200); // Wait for dropdowns to be built
                
            }, 500);
        } else {
            console.warn('No room selections found to restore.');
            window.isRestoringData = false; // Reset guard if nothing to restore
        }
    }

    // ===== CUSTOM ROOM FUNCTIONS (defined inside document.ready to access local variables) =====
    // Make them globally accessible via window object
    window.openAddRoomModal = function() {
        // Reset form
        $('#addRoomForm')[0].reset();
        $('#custom_room_qty').val(1);
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('addRoomModal'));
        modal.show();
    };

    window.saveCustomRoom = function() {
        const roomName = $('#custom_room_name').val().trim();
        const roomType = $('#custom_room_type').val();
        
        // Validate required fields
        if (!roomName) {
            Swal.fire('Error', 'Nama ruangan harus diisi', 'error');
            return;
        }
        if (!roomType) {
            Swal.fire('Error', 'Tipe ruangan harus dipilih', 'error');
            return;
        }
        
        // Get first survey ID for association
        const selectedSurveyIds = $('#survey_tags').val() || [];
        if (selectedSurveyIds.length === 0) {
            Swal.fire('Error', 'Pilih survey terlebih dahulu di Step 2', 'error');
            return;
        }
        const firstSurveyId = selectedSurveyIds[0];
        
        // Create custom room object
        const customRoom = {
            id: 'custom_' + Date.now(),
            survey_id: firstSurveyId,
            room_name: roomName,
            room_type: roomType,
            room_area: parseFloat($('#custom_room_area').val()) || 0,
            quantity_needed: parseInt($('#custom_room_qty').val()) || 1,
            is_custom: true,
            specifications: JSON.stringify({
                floor: $('#custom_room_floor').val() || '',
                intensity: $('#custom_room_intensity').val() || '',
                installation_type: $('#custom_room_installation').val() || '',
                length: $('#custom_room_length').val() || '',
                width: $('#custom_room_width').val() || '',
                height: $('#custom_room_height').val() || '',
                temperature: $('#custom_room_temperature').val() || '',
                remark: $('#custom_room_remark').val() || ''
            })
        };
        
        // Add to customRooms array
        if (!window.customRooms) {
            window.customRooms = [];
        }
        window.customRooms.push(customRoom);
        
        console.log('Custom room added:', customRoom);
        
        // Close modal using getOrCreateInstance to avoid null error
        const modalEl = document.getElementById('addRoomModal');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.hide();
        
        // Refresh display
        window.displayCustomRooms();
        
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: 'Ruangan berhasil ditambahkan',
            timer: 1500,
            showConfirmButton: false
        });
    };

    window.displayCustomRooms = function() {
        if (!window.customRooms || window.customRooms.length === 0) {
            $('#custom-rooms-container').remove();
            return;
        }
        
        let container = $('#custom-rooms-container');
        if (container.length === 0) {
            container = $(`
                <div id="custom-rooms-container" class="mt-4">
                    <div class="card border-success">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-plus-square me-2"></i>Ruangan Tambahan (Custom)
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" id="custom-rooms-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="5%"><input type="checkbox" class="select-all-custom-checkbox"></th>
                                            <th width="5%">#</th>
                                            <th width="25%">Nama Ruangan</th>
                                            <th width="55%">Specifications</th>
                                            <th width="10%">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="custom-rooms-tbody"></tbody>
                                </table>
                            </div>
                            <div class="aroma-selection-section mt-4" id="aroma-section-custom" style="display:none;">
                                <div class="alert alert-info">
                                    <i class="fas fa-spray-can me-2"></i>
                                    <strong>Pilih Aroma/Variant untuk Ruangan Custom</strong>
                                </div>
                                <div id="aroma-selection-container-custom"></div>
                            </div>
                        </div>
                    </div>
                </div>
            `);
            $('#room-selection-container').append(container);
        }
        
        let rows = '';
        window.customRooms.forEach((room, index) => {
            const specs = typeof room.specifications === 'string' ? JSON.parse(room.specifications) : room.specifications;
            rows += `
                <tr>
                    <td><input type="checkbox" class="room-checkbox custom-room-checkbox" value="${room.id}" data-survey="${room.survey_id || 'custom'}" data-room="${room.id}" data-master-room-id="${room.master_room_id || ''}" data-contract-room-id="${room.contract_room_id || ''}" data-is-custom="true"></td>
                    <td>${index + 1}</td>
                    <td><strong>${room.room_name}</strong><br><small class="text-muted">${room.room_type}</small></td>
                    <td>
                        <div class="row">
                            <div class="col-md-6">
                                <small><strong>Area:</strong> ${room.room_area || 0} m² | <strong>Qty:</strong> ${room.quantity_needed || 1}</small><br>
                                <small><strong>Floor:</strong> ${specs.floor || '-'} | <strong>Intensity:</strong> ${specs.intensity || '-'}</small>
                            </div>
                            <div class="col-md-6">
                                <small><strong>Installation:</strong> ${specs.installation_type || '-'}</small><br>
                                <small><strong>L×W×H:</strong> ${specs.length || '-'}×${specs.width || '-'}×${specs.height || '-'}</small>
                            </div>
                        </div>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-danger" onclick="window.deleteCustomRoom('${room.id}')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        
        $('#custom-rooms-tbody').html(rows);
        
        // Setup handlers
        $(document).off('change', '.custom-room-checkbox').on('change', '.custom-room-checkbox', function() {
            window.rebuildAromaDropdownsForCustomRooms();
        });
        $(document).off('change', '.select-all-custom-checkbox').on('change', '.select-all-custom-checkbox', function() {
            $('.custom-room-checkbox').prop('checked', $(this).is(':checked')).trigger('change');
        });
    };

    window.deleteCustomRoom = function(roomId) {
        Swal.fire({
            title: 'Hapus Ruangan?',
            text: 'Ruangan ini akan dihapus',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.customRooms = window.customRooms.filter(room => room.id !== roomId);
                window.displayCustomRooms();
                Swal.fire({ icon: 'success', title: 'Dihapus!', timer: 1500, showConfirmButton: false });
            }
        });
    };

    window.rebuildAromaDropdownsForCustomRooms = function() {
        const checkedRooms = $('.custom-room-checkbox:checked');
        const aromaSection = $('#aroma-section-custom');
        const aromaContainer = $('#aroma-selection-container-custom');
        
        aromaContainer.empty();
        
        if (checkedRooms.length === 0) {
            aromaSection.hide();
            return;
        }
        
        aromaSection.show();
        const products = window.aromaProductsList || aromaProducts || [];
        
        checkedRooms.each(function() {
            const roomId = $(this).val();
            const room = window.customRooms.find(r => r.id === roomId);
            if (!room) return;
            
            const aromaHtml = `
                <div class="mb-3" data-room-id="${room.id}">
                    <label class="form-label"><i class="fas fa-door-open me-2"></i>${room.room_name}</label>
                    <select class="form-control aroma-select custom-aroma-select" data-room-id="${room.id}" data-is-custom="true">
                        <option value="">Pilih aroma...</option>
                        ${products.map(p => `<option value="${p.id}" data-variant="${p.variant || ''}">${p.display_name || p.name}</option>`).join('')}
                    </select>
                </div>
            `;
            aromaContainer.append(aromaHtml);

            const existingSelection = window.globalRoomSelections
                ? window.globalRoomSelections.find(rs => {
                    if (!rs || typeof rs !== 'object') return false;
                    return String(rs.room_id) === String(room.id)
                        || (room.master_room_id && String(rs.master_room_id || rs.room_id) === String(room.master_room_id))
                        || (rs.room_name && room.room_name && rs.room_name.trim().toLowerCase() === room.room_name.trim().toLowerCase());
                })
                : null;

            if (existingSelection && existingSelection.aroma_product_id && window.hasAromaProductOption(existingSelection.aroma_product_id)) {
                aromaContainer.find(`.custom-aroma-select[data-room-id="${room.id}"]`).val(existingSelection.aroma_product_id);
            }
        });
    };

    // Initialize
    window.customRooms = [];
    window.aromaProductsList = [];

    // ===== ROOM MANAGEMENT =====
    function loadRooms() {
        const currentSelectedSurveys = $('#survey_tags').val() || [];
        if (currentSelectedSurveys.length === 0) {
            Swal.fire('Error', 'Pilih survey terlebih dahulu', 'error');
            return;
        }
        
        selectedSurveys = currentSelectedSurveys;
        console.log('Loading rooms for surveys:', selectedSurveys);
        
        // Show loading
        $('#room-selection-container').html(`
            <div class="loading-spinner">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading room data...</p>
            </div>
        `);
        
        $.ajax({
            url: '{{ route("marketing.quotations.wizard.get-survey-rooms") }}',
            method: 'GET',
            data: { 
                survey_ids: selectedSurveys,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                console.log('=== ROOM DATA LOADED ===');
                console.log('Response:', response);
                console.log('Response type:', typeof response);
                console.log('Response length:', response.length);
                console.log('FULL RESPONSE:', JSON.stringify(response)); // Debug Room 107 existence
                
                if (response && response.length > 0) {
                    console.log('First survey data:', response[0]);
                    console.log('First survey number:', response[0].survey_number);
                    console.log('First survey ID:', response[0].id);
                }
                
                displayRoomTables(response);
                
                // Restore selections after table is rendered
                restoreRoomSelections();
            },
            error: function(xhr, status, error) {
                console.error('Error loading rooms:', error);
                $('#room-selection-container').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Error loading room data. Please try again.
                    </div>
                `);
            }
        });
    }

    // Function to parse JSON specifications
    function parseSpecifications(specs) {
        if (!specs || specs === '-') {
            return '<div class="text-muted">No specifications</div>';
        }
        
        try {
            const parsed = typeof specs === 'string' ? JSON.parse(specs) : specs;
            let html = '<div class="row">';
            
            // Common fields to display
            const fields = [
                { key: 'floor', label: 'Floor', icon: 'fas fa-layer-group' },
                { key: 'intensity', label: 'Intensity', icon: 'fas fa-tint' },
                { key: 'installation_type', label: 'Installation', icon: 'fas fa-tools' },
                { key: 'length', label: 'Length', icon: 'fas fa-ruler-horizontal' },
                { key: 'width', label: 'Width', icon: 'fas fa-ruler-vertical' },
                { key: 'height', label: 'Height', icon: 'fas fa-arrows-alt-v' },
                { key: 'area', label: 'Area', icon: 'fas fa-expand-arrows-alt' },
                { key: 'temperature', label: 'Temperature', icon: 'fas fa-thermometer-half' },
                { key: 'qty', label: 'Qty', icon: 'fas fa-hashtag' },
                { key: 'remark', label: 'Remark', icon: 'fas fa-sticky-note' }
            ];
            
            fields.forEach(field => {
                if (parsed[field.key] !== undefined && parsed[field.key] !== null && parsed[field.key] !== '') {
                    html += `
                        <div class="col-md-6">
                            <div class="spec-item mb-2">
                                <i class="${field.icon} me-2 text-primary"></i>
                                <strong>${field.label}:</strong> 
                                <span class="spec-value">${parsed[field.key]}</span>
                            </div>
                        </div>
                    `;
                }
            });
            
            html += '</div>'; // Close row
            return html || '<div class="text-muted">No valid specifications</div>';
        } catch (e) {
            return `<div class="text-muted">Invalid specifications format</div>`;
        }
    }

    function displayRoomTables(surveys) {
        let html = '';
        
        console.log('=== DISPLAY ROOM TABLES ===');
        console.log('Surveys data:', surveys);
        
        surveys.forEach(function(survey, index) {
            console.log('Processing survey:', survey);
            console.log('Survey number:', survey.survey_number);
            console.log('Survey ID:', survey.id);
            
            // Test template string
            const surveyHeader = `Survey: ${survey.survey_number || survey.id || 'N/A'}`;
            console.log('Generated survey header:', surveyHeader);
            console.log('Survey data for template:', {
                id: survey.id,
                survey_number: survey.survey_number,
                customer_name: survey.customer_name
            });
            
            html += `
                <div class="survey-room-section mb-4" data-survey-id="${survey.id}">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-2" style="color: #ffffff !important; font-size: 20px !important; font-weight: bold !important;">
                                <i class="fas fa-clipboard-list me-2"></i>
                                Survey: ${survey.survey_number || survey.id || 'N/A'}
                            </h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <small>Customer: ${survey.customer_name}</small>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-end">
                                        <small>
                                            <i class="fas fa-building me-1"></i>
                                            ${survey.building_name}
                                        </small><br>
                                        <small class="text-light">
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            ${survey.building_address}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Customer & Building Info - Side by Side -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="info-section">
                                        <h6 class="text-primary mb-2">
                                            <i class="fas fa-user me-2"></i>Customer Information
                                        </h6>
                                        <div class="info-item">
                                            <strong>Name:</strong> ${survey.customer_name}
                                        </div>
                                        <div class="info-item">
                                            <strong>Address:</strong> ${survey.customer_address}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-section">
                                        <h6 class="text-primary mb-2">
                                            <i class="fas fa-building me-2"></i>Building Information
                                        </h6>
                                        <div class="info-item">
                                            <strong>Name:</strong> ${survey.building_name}
                                        </div>
                                        <div class="info-item">
                                            <strong>Address:</strong> ${survey.building_address}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0">Pilih Ruangan</h6>
                                <button type="button" class="btn btn-sm btn-outline-primary select-all-btn" data-survey-id="${survey.id}">
                                    <i class="fas fa-check-square me-1"></i>Pilih Semua
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th width="50">
                                                <input type="checkbox" class="form-check-input select-all-checkbox" id="selectAll_${survey.id}" data-survey-id="${survey.id}">
                                            </th>
                                            <th>No</th>
                                            <th>Nama Ruangan</th>
                                            <th>Spesifikasi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${window.renderRoomRows(survey.rooms, survey.id)}
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Aroma Selection Section (Below Table) -->
                            <div class="aroma-selection-section mt-4" id="aroma-section-${survey.id}" style="display:none;">
                                <div class="alert alert-info">
                                    <i class="fas fa-spray-can me-2"></i>
                                    <strong>Pilih Aroma/Variant untuk Ruangan yang Dipilih</strong>
                                </div>
                                <div id="aroma-selection-container-${survey.id}">
                                    <!-- Aroma dropdowns will be dynamically added here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        console.log('=== HTML BEFORE INSERTION ===');
        console.log('HTML length:', html.length);
        console.log('HTML preview:', html.substring(0, 500));
        
        $('#room-selection-container').html(html);
        
        console.log('=== HTML AFTER INSERTION ===');
        console.log('Container HTML:', $('#room-selection-container').html().substring(0, 500));
        
        // Force visibility and debugging for Step 3
        setTimeout(function() {
            console.log('=== STEP 3 FORCE VISIBILITY DEBUG ===');
            const allCards = $('.card');
            console.log('All cards found in Step 3:', allCards.length);
            
            allCards.each(function(index) {
                const card = $(this);
                const header = card.find('.card-header h5, .card-header h6');
                console.log('Step 3 Card ' + index + ' header text:', header.text());
                console.log('Step 3 Card ' + index + ' header visible:', header.is(':visible'));
                console.log('Step 3 Card ' + index + ' header display:', header.css('display'));
                console.log('Step 3 Card ' + index + ' header color:', header.css('color'));
                
                // Force visibility
                header.css({
                    'display': 'block !important',
                    'visibility': 'visible !important',
                    'opacity': '1 !important',
                    'color': 'white !important',
                    'font-size': '20px !important',
                    'font-weight': 'bold !important'
                });
            });
            
            // Also check specifically for survey-room-section
            const surveySections = $('.survey-room-section');
            console.log('Survey room sections found:', surveySections.length);
            surveySections.each(function(index) {
                const section = $(this);
                const header = section.find('.card-header h5, .card-header h6');
                console.log('Survey section ' + index + ' header text:', header.text());
                console.log('Survey section ' + index + ' header visible:', header.is(':visible'));
            });
        }, 200);
        
        // Load aroma products after displaying rooms (only if not loaded yet)
        if (typeof aromaProducts === 'undefined' || aromaProducts.length === 0) {
            loadAromaProducts();
        } else {
            // If already loaded, trigger rebuild for this survey immediately
            const surveyIds = surveys.map(s => s.id);
            surveyIds.forEach(id => rebuildAromaDropdowns(id));
        }
        
        // Setup event handlers for room checkboxes
        setupRoomCheckboxHandlers();
        
        updateNextButtonState();
    }

    // Aroma management functions moved to global scope at the end of the file


    function updateRoomSelections() {
        if (window.isRestoringData) {
            console.log('DEBUG: updateRoomSelections skipped because restoration is in progress');
            return;
        }
        console.log('DEBUG: updateRoomSelections started');
        globalRoomSelections = [];
        
        try {
            const checkedBoxes = $('.room-checkbox:checked');
            console.log('DEBUG: Found checked boxes:', checkedBoxes.length);

            checkedBoxes.each(function(index) {
                try {
                    const checkbox = $(this);
                    const surveyId = checkbox.data('survey');
                    const roomId = checkbox.data('room');
                    const isCustom = checkbox.hasClass('custom-room-checkbox');
                    
                    // Safe name retrieval
                    let roomName = 'Unknown Room';
                    const nameEl = checkbox.closest('tr').find('td:eq(2) strong');
                    if (nameEl.length > 0) roomName = nameEl.text().trim();
                    else console.warn('DEBUG: Could not find room name element for index', index);

                    console.log(`DEBUG: Processing box ${index}. Survey: ${surveyId}, Room: ${roomId}, Custom: ${isCustom}, Name: ${roomName}`);
                    
                    let aromaSelect;
                    if (isCustom) {
                        const customRoomId = checkbox.val(); 
                        aromaSelect = $(`.custom-aroma-select[data-room-id="${customRoomId}"]`);
                        console.log(`DEBUG: Target Custom Aroma Select: .custom-aroma-select[data-room-id="${customRoomId}"] -> Found: ${aromaSelect.length}`);
                    } else {
                        aromaSelect = $(`.aroma-select[data-survey="${surveyId}"][data-room="${roomId}"]`);
                        console.log(`DEBUG: Target Standard Aroma Select: .aroma-select[data-survey="${surveyId}"][data-room="${roomId}"] -> Found: ${aromaSelect.length}`);
                    }
                    
                    const aromaProductId = aromaSelect.val();
                    const selectedOption = aromaSelect.find('option:selected');
                    const aromaVariant = selectedOption.data('variant') || '';
                    const aromaDisplayName = selectedOption.text();
                    
                    // Fix: Sync back to data attributes for re-population stability
                    if (aromaSelect.length > 0) {
                        aromaSelect.attr('data-selected-value', aromaProductId || '');
                        aromaSelect.data('selected-value', aromaProductId || '');
                    }
                    
                    globalRoomSelections.push({
                        survey_id: surveyId,
                        room_id: roomId,
                        survey_detail_id: !isCustom ? roomId : null,
                        master_room_id: checkbox.data('master-room-id') || null,
                        contract_room_id: checkbox.data('contract-room-id') || null,
                        room_name: roomName,
                        room_type: checkbox.closest('tr').find('td:eq(2) small').text().trim() || null,
                        aroma_product_id: aromaProductId || null,
                        aroma_variant: aromaVariant || null,
                        aroma_display_name: aromaDisplayName || 'Belum dipilih'
                    });
                } catch (innerErr) {
                    console.error('DEBUG: Error processing individual checkbox:', innerErr);
                }
            });
            
            console.log('Updated room selections with aroma:', globalRoomSelections);
            
            // Fix: Sync with window object and localStorage
            window.globalRoomSelections = globalRoomSelections;
            localStorage.setItem('quotation_room_selections', JSON.stringify(globalRoomSelections));
            
            updateNextButtonState();
        } catch (err) {
            console.error('DEBUG: CRITICAL ERROR in updateRoomSelections:', err);
        }
    }
    
    // Make updateRoomSelections globally accessible for handlers defined in other script blocks
    window.updateRoomSelections = updateRoomSelections;

    // ===== STEP 5 CALCULATIONS =====
    
    function loadTaxSettings() {
        console.log('=== LOADING TAX SETTINGS ===');
        
        $.ajax({
            url: '/marketing/quotations/wizard/get-tax-settings',
            method: 'GET',
            success: function(response) {
                console.log('Tax settings loaded:', response);
                
                const taxSelect = $('#tax_id');
                taxSelect.empty();
                taxSelect.append('<option value="">Pilih PPN</option>');
                
                response.forEach(function(tax) {
                    taxSelect.append(`<option value="${tax.id}" data-rate="${tax.tax_rate}">${tax.name} (${tax.tax_rate}%)</option>`);
                });
            },
            error: function(xhr, status, error) {
                console.error('Error loading tax settings:', error);
            }
        });
    }
    
    function calculateStep5Totals() {
        console.log('=== CALCULATING STEP 5 TOTALS ===');
        
        // Calculate sub total from Step 4 rental configurations
        let subTotal = 0;
        $('.rental-configuration').each(function() {
            const quantity = parseFloat($(this).find('input[name*="quantity"]').val()) || 0;
            const price = parseFloat($(this).find('input[name*="price"]').val()) || 0;
            const itemTotal = quantity * price;
            subTotal += itemTotal;
            
            console.log('Rental item - Qty:', quantity, 'Price:', price, 'Total:', itemTotal);
        });
        
        console.log('Sub total calculated:', subTotal);
        
        // Update sub total field (remove decimal places)
        $('#sub_total').val(Math.round(subTotal).toLocaleString('id-ID'));
        
        // Calculate tax
        const taxRate = parseFloat($('#tax_id option:selected').data('rate')) || 0;
        const taxAmount = subTotal * (taxRate / 100);
        const totalPenawaran = subTotal + taxAmount;
        
        console.log('Tax rate:', taxRate, 'Tax amount:', taxAmount, 'Total:', totalPenawaran);
        
        // Update total penawaran (remove decimal places)
        $('#total_penawaran').val(Math.round(totalPenawaran).toLocaleString('id-ID'));
    }
    
    // Event listeners for Step 5 calculations
    $(document).on('change', '#tax_id', function() {
        calculateStep5Totals();
    });
    
    $(document).on('input change', '.rental-configuration input[name*="quantity"], .rental-configuration input[name*="price"]', function() {
        calculateStep5Totals();
        updateNextButtonState();
    });

    // Update Next button state when rental product changes
    $(document).on('change select2:select', '.rental-product-select', function() {
        updateNextButtonState();
    });

    // ===== STEP 7 SUMMARY =====
    
    function loadSummary() {
        console.log('=== LOADING SUMMARY ===');
        
        var marketing = $('#marketing_id option:selected').text();
        var quotation_type = $('#quotation_type').val();
        var rental_period = $('#rental_period').val();
        var rental_unit = $('#rental_unit').val();
        var payment_method = $('#payment_method').val();
        var term_of_payment = $('#term_of_payment').val();
        var remark_internal = $('#remark_internal').val();
        var remark_external = $('#remark_external').val();
        var sub_total = $('#sub_total').val();
        var total_penawaran = $('#total_penawaran').val();
        var pic_quotation = $('#pic_quotation option:selected').text() || $('#pic_quotation option:selected').val();

        var selectedSurveyIds = $('#survey_tags').val() || [];
        var customerName = '-';
        var customerType = '-'; 
        
        if (selectedSurveyIds.length > 0) {
            var firstSurveyOption = $('#survey_tags option[value="' + selectedSurveyIds[0] + '"]');
            if (firstSurveyOption.length > 0) {
                customerName = firstSurveyOption.data('customer') || '-';
                customerType = firstSurveyOption.data('company-type') || '-';
            }
        }

        var surveyListHtml = '';
        for (var i = 0; i < selectedSurveyIds.length; i++) {
            var id = selectedSurveyIds[i];
            var option = $('#survey_tags option[value="' + id + '"]');
            var text = option.text() || ('Survey ID: ' + id);
            surveyListHtml += '<li>' + text + '</li>';
        }

        var contractNumber = '';
        if (quotation_type === 'renewal') {
            contractNumber = $('#existing_contract_id option:selected').text();
            // If text is "Pilih Contract..." or similar, try to get from renewalContractData
            if ((!contractNumber || contractNumber.includes('Pilih')) && window.renewalContractData) {
                contractNumber = window.renewalContractData.contract_number;
            }
        }

        var summaryHtml = '<div class="card">' +
            '<div class="card-header bg-primary text-white">' +
                '<h4 class="mb-0"><i class="fas fa-file-alt me-2"></i>Summary Quotation</h4>' +
            '</div>' +
            '<div class="card-body">' +
                '<div class="row">' +
                    '<div class="col-md-6">' +
                        '<div class="card mb-3">' +
                            '<div class="card-header bg-dark text-white"><h6 class="mb-0">Data Quotation</h6></div>' +
                            '<div class="card-body">' +
                                '<p class="mb-2"><strong>Nama Marketing:</strong> ' + marketing + '</p>' +
                                '<p class="mb-2"><strong>Jenis Penawaran:</strong> ' + (quotation_type === 'new' ? 'New Quotation' : 'Renewal Quotation') + '</p>' +
                                (quotation_type === 'renewal' && contractNumber ? '<p class="mb-2"><strong>Nomor Contract:</strong> ' + contractNumber + '</p>' : '') +
                                '<p class="mb-2"><strong>Nomor Survey:</strong></p>' +
                                '<ul class="mb-2">' + surveyListHtml + '</ul>' +
                                '<p class="mb-0"><strong>Remark Internal:</strong> ' + (remark_internal || '-') + '</p>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                    '<div class="col-md-6">' +
                        '<div class="card mb-3">' +
                            '<div class="card-header bg-dark text-white"><h6 class="mb-0">Data Perusahaan</h6></div>' +
                            '<div class="card-body">' +
                                '<p class="mb-2"><strong>Nama Perusahaan:</strong> ' + customerName + '</p>' +
                                '<p class="mb-2"><strong>Jenis Perusahaan:</strong> ' + customerType + '</p>' +
                                '<p class="mb-2"><strong>Lama Sewa:</strong> ' + rental_period + ' ' + rental_unit + '</p>' +
                                '<p class="mb-2"><strong>Payment Method:</strong> ' + payment_method + '</p>' +
                                '<p class="mb-2"><strong>Term of Payment:</strong> ' + term_of_payment + '</p>' +
                                '<p class="mb-2"><strong>Remark External:</strong> ' + (remark_external || '-') + '</p>' +
                                '<p class="mb-2"><strong>PIC Name:</strong> ' + pic_quotation + '</p>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
                '<div class="card">' +
                    '<div class="card-header bg-secondary text-white"><h6 class="mb-0">List Penawaran</h6></div>' +
                    '<div class="card-body p-0">' +
                        '<div class="table-responsive">' +
                            '<table class="table table-striped mb-0">' +
                                '<thead class="table-dark"><tr>' +
                                    '<th style="width: 60%;">Deskripsi Item</th>' +
                                    '<th style="width: 10%;" class="text-center">Qty</th>' +
                                    '<th style="width: 15%;" class="text-center">Harga Rental</th>' +
                                    '<th style="width: 15%;" class="text-center">/ TOP</th>' +
                                '</tr></thead>' +
                                '<tbody id="summary-rental-items"></tbody>' +
                            '</table>' +
                        '</div>' +
                        '<div class="row">' +
                            '<div class="col-md-8"></div>' +
                            '<div class="col-md-4">' +
                                '<table class="table table-sm mb-0"><tbody>' +
                                    '<tr><td><strong>Sub Total</strong></td><td class="text-end"><strong>' + (sub_total || '0') + '</strong></td></tr>' +
                                    '<tr class="table-success"><td><strong>Grand Total</strong></td><td class="text-end"><strong>' + (total_penawaran || '0') + '</strong></td></tr>' +
                                '</tbody></table>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</div>' +
        '</div>';
        
        $('#summary-content').html(summaryHtml);
        loadSummaryRentalItems();
    }
    
    async function loadSummaryRentalItems() {
        console.log('=== LOADING SUMMARY RENTAL ITEMS ===');
        
        // Get price basis, default to 'rental' if empty
        let priceBasis = $('#price_basis').val() || 'rental';
        console.log('Price Basis:', priceBasis);
        
        let rentalItemsHtml = '';
        
        // Get rental configurations from DOM first
        const rentalConfigs = $('.rental-configuration');
        console.log('Rental configurations found in DOM:', rentalConfigs.length);
        
        // Always try to get from localStorage as backup
        let rentalConfigsData = [];
        const savedConfigs = localStorage.getItem('quotation_rental_configurations');
        if (savedConfigs) {
            try {
                rentalConfigsData = JSON.parse(savedConfigs);
                console.log('Loaded rental configs from localStorage:', rentalConfigsData.length);
            } catch (e) {
                console.error('Error parsing rental configs from localStorage:', e);
            }
        }
        
        // Also try to get from step4_data as additional backup
        if (rentalConfigsData.length === 0) {
            const step4Data = localStorage.getItem('quotation_step4_data');
            if (step4Data) {
                try {
                    const step4Configs = JSON.parse(step4Data);
                    console.log('Trying to load from step4_data:', step4Configs.length);
                    rentalConfigsData = step4Configs.map(c => ({
                        roomId: c.roomId,
                        roomName: c.roomName || c.formData?.roomName || '',
                        productId: c.formData?.product_id || '',
                        productName: c.formData?.productName || c.productName || '',
                        price: c.formData?.price || '',
                        quantity: c.formData?.quantity || '1',
                        remark: c.formData?.remark || '',
                        rentalAlias: c.formData?.rental_alias || ''
                    }));
                    console.log('Converted step4_data to rental configs:', rentalConfigsData.length);
                } catch (e) {
                    console.error('Error parsing step4_data:', e);
                }
            }
        }
        
        // Group by room if basis is "room", otherwise show each rental item
        if (priceBasis === 'room') {
            // ROOM BASIS: Show room name + aroma + total (no qty, no rental details)
            const roomTotals = {};
            const processedRooms = new Set(); // Track processed rooms to avoid duplicates
            
            // Process DOM elements first (priority)
            rentalConfigs.each(function() {
                const config = $(this);
                const roomId = config.find('select[name*="room_id"]').val() || config.data('room-id');
                const roomName = config.find('select[name*="room_id"] option:selected').text() || 
                               config.find('.room-display-container input[type="text"]').val() || '';
                const price = parseFloat(config.find('input[name*="price"]').val() || '0');
                const quantity = parseFloat(config.find('input[name*="quantity"]').val() || '1');
                const total = price * quantity;
                
                if (roomId && roomName) {
                    if (!roomTotals[roomId]) {
                        roomTotals[roomId] = {
                            name: roomName,
                            total: 0
                        };
                    }
                    roomTotals[roomId].total += total;
                }
            });
            
            // Process localStorage data ONLY if DOM is empty (to avoid double counting)
            if (rentalConfigs.length === 0 && rentalConfigsData.length > 0) {
                rentalConfigsData.forEach(function(config) {
                    const roomId = config.roomId;
                    const roomName = config.roomName || 'Room ' + roomId;
                    const price = parseFloat(config.price || '0');
                    const quantity = parseFloat(config.quantity || '1');
                    const total = price * quantity;
                    
                    if (roomId) {
                        if (!roomTotals[roomId]) {
                            roomTotals[roomId] = {
                                name: roomName,
                                total: 0
                            };
                        }
                        roomTotals[roomId].total += total;
                    }
                });
            }
            
            // Generate HTML for room-based summary
            // Use Promise.all to handle async aroma fetching
            const roomPromises = Object.keys(roomTotals).map(async function(roomId) {
                const room = roomTotals[roomId];
                
                // Find aroma for this room - first check step 3 selection
                let aromaText = '';
                const roomSelection = globalRoomSelections.find(r => r.room_id == roomId);
                
                if (roomSelection && roomSelection.aroma_display_name && roomSelection.aroma_display_name !== 'Belum dipilih') {
                    // Use aroma from step 3 if selected
                    aromaText = `<br><small class="text-success"><i class="fas fa-leaf me-1"></i>Aroma: ${roomSelection.aroma_display_name}</small>`;
                } else {
                    // If no aroma selected in step 3, try to get from master rental
                    // Find rental product for this room
                    let rentalProductId = null;
                    
                    // Try to get from DOM first
                    const rentalConfig = rentalConfigs.filter(function() {
                        const configRoomId = $(this).find('select[name*="room_id"]').val() || $(this).data('room-id');
                        return configRoomId == roomId;
                    }).first();
                    
                    if (rentalConfig.length > 0) {
                        rentalProductId = rentalConfig.find('select[name*="product_id"]').val();
                    } else if (rentalConfigsData.length > 0) {
                        // Try from localStorage
                        const configData = rentalConfigsData.find(c => c.roomId == roomId);
                        if (configData) {
                            rentalProductId = configData.productId;
                        }
                    }
                    
                    // If we have rental product ID, fetch default aroma
                    if (rentalProductId) {
                        try {
                            const routeUrl = '{{ route("marketing.quotations.wizard.get-rental-aroma", ["rentalId" => ":rentalId"]) }}';
                            const url = routeUrl.replace(':rentalId', rentalProductId);
                            const response = await fetch(url);
                            if (response.ok) {
                                const aromaData = await response.json();
                                if (aromaData && aromaData.display_name) {
                                    aromaText = `<br><small class="text-muted"><i class="fas fa-leaf me-1"></i>Aroma: ${aromaData.display_name} (Default)</small>`;
                                }
                            }
                        } catch (e) {
                            console.error('Error fetching rental aroma:', e);
                        }
                    }
                }
                
                return {
                    roomId: roomId,
                    room: room,
                    aromaText: aromaText
                };
            });
            
            // Wait for all promises to resolve
            const roomDataArray = await Promise.all(roomPromises);
            
            // Generate HTML for each room
            roomDataArray.forEach(function(roomData) {
                rentalItemsHtml += `
                    <tr>
                        <td>
                            <strong>${roomData.room.name}</strong>
                            ${roomData.aromaText}
                        </td>
                        <td class="text-center" colspan="2">-</td>
                        <td class="text-end"><strong>Rp ${Math.round(roomData.room.total).toLocaleString('id-ID')}</strong></td>
                    </tr>
                `;
            });
            
        } else {
            // RENTAL BASIS: Show rental alias (or rental name) + qty + price (no room name)
            
            // Track processed items to avoid duplicates
            const processedItems = new Set();
            
            // Process DOM elements first
            rentalConfigs.each(function() {
                const config = $(this);
                const productId = config.find('select[name*="product_id"]').val();
                const productName = config.find('select[name*="product_id"] option:selected').text();
                const rentalAlias = config.find('input[name*="rental_alias"]').val() || '';
                const displayName = rentalAlias || productName; // Use rental alias if exists, otherwise rental name
                const quantity = config.find('input[name*="quantity"]').val() || '1';
                const price = config.find('input[name*="price"]').val() || '0';
                const priceNum = parseFloat(price);
                const qtyNum = parseFloat(quantity);
                const total = priceNum * qtyNum;
                const remark = config.find('textarea[name*="remark"]').val() || config.find('input[name*="remark"]').val() || '';
                const roomId = config.data('room-id') || config.find('input[name*="room_id"]').val() || 'unknown';
                
                // Find aroma for this room
                let aromaText = '';
                if (typeof globalRoomSelections !== 'undefined' && globalRoomSelections) {
                    const aromaSelection = globalRoomSelections.find(r => r.room_id == roomId);
                    if (aromaSelection && aromaSelection.aroma_display_name && aromaSelection.aroma_display_name !== 'Belum dipilih') {
                        aromaText = `<br><small class="text-success"><i class="fas fa-leaf me-1"></i>Aroma: ${aromaSelection.aroma_display_name}</small>`;
                    }
                }

                const uniqueId = config.attr('id') ? config.attr('id').replace('rental-config-', '') : Math.random().toString(36).substr(2, 9);
                if (displayName && productName && productName !== 'Pilih Produk Rental' && productId) {
                    const itemKey = uniqueId;
                    if (!processedItems.has(itemKey)) {
                        processedItems.add(itemKey);
                        rentalItemsHtml += `
                            <tr>
                                <td>
                                    <strong>${displayName}</strong>
                                    ${rentalAlias ? `<br><small class="text-muted">(${productName})</small>` : ''}
                                    ${aromaText}
                                    ${remark ? `<br><small class="text-info">Remark: ${remark}</small>` : ''}
                                </td>
                                <td class="text-center">${quantity}</td>
                                <td class="text-end">Rp ${Math.round(priceNum).toLocaleString('id-ID')}</td>
                                <td class="text-end">Rp ${Math.round(total).toLocaleString('id-ID')}</td>
                            </tr>
                        `;
                    }
                }
            });
            
            // Process localStorage data ONLY if DOM is empty (priority to current form state)
            if (rentalConfigs.length === 0 && rentalConfigsData.length > 0) {
                rentalConfigsData.forEach(function(config) {
                    const productId = config.productId || '';
                    const productName = config.productName || 'Product';
                    const rentalAlias = config.rentalAlias || '';
                    const displayName = rentalAlias || productName;
                    const quantity = config.quantity || '1';
                    const price = config.price || '0';
                    const priceNum = parseFloat(price);
                    const qtyNum = parseFloat(quantity);
                    const total = priceNum * qtyNum;
                    const remark = config.remark || '';
                    const roomId = config.roomId || '';
                    
                    // Find aroma for this room
                    let aromaText = '';
                    if (typeof globalRoomSelections !== 'undefined' && globalRoomSelections) {
                        const aromaSelection = globalRoomSelections.find(r => r.room_id == roomId);
                        if (aromaSelection && aromaSelection.aroma_display_name && aromaSelection.aroma_display_name !== 'Belum dipilih') {
                            aromaText = `<br><small class="text-success"><i class="fas fa-leaf me-1"></i>Aroma: ${aromaSelection.aroma_display_name}</small>`;
                        }
                    }

                    const uniqueId = config.uniqueId || Math.random().toString(36).substr(2, 9);
                    if (displayName && productName && productName !== 'Pilih Produk Rental' && productId) {
                        const itemKey = uniqueId;
                        if (!processedItems.has(itemKey)) {
                            processedItems.add(itemKey);
                            rentalItemsHtml += `
                                <tr>
                                    <td>
                                        <strong>${displayName}</strong>
                                        ${rentalAlias ? `<br><small class="text-muted">(${productName})</small>` : ''}
                                        ${aromaText}
                                        ${remark ? `<br><small class="text-info">Remark: ${remark}</small>` : ''}
                                    </td>
                                    <td class="text-center">${quantity}</td>
                                    <td class="text-end">Rp ${Math.round(priceNum).toLocaleString('id-ID')}</td>
                                    <td class="text-end">Rp ${Math.round(total).toLocaleString('id-ID')}</td>
                                </tr>
                            `;
                        }
                    }
                });
            }
        }
        
        if (rentalItemsHtml === '') {
            rentalItemsHtml = '<tr><td colspan="4" class="text-center">No rental items configured</td></tr>';
        }
        
        $('#summary-rental-items').html(rentalItemsHtml);
    }

    // ===== RENTAL CONFIGURATION =====
    function loadRentalConfiguration() {
        const currentSelectedRooms = $('.room-checkbox:checked').map(function() { 
            return $(this).val(); 
        }).get();
        
        if (currentSelectedRooms.length === 0) {
            Swal.fire('Error', 'Pilih ruangan terlebih dahulu', 'error');
            return;
        }
        
        // Save existing rental configurations before recreating HTML
        // MODIFIED: Do NOT save here. Saving on load overwrites valid data with empty/partial state.
        // potentially causing data loss if visiting step 4 for the first time in session.
        // saveStep4Data(); 

        
        selectedRooms = currentSelectedRooms;
        console.log('Loading rental configuration for rooms:', selectedRooms);
        
        // Load unit configuration based on selected rooms
        // restoreStep4Data() will be called inside displayUnitConfiguration() after HTML is rendered
        displayUnitConfiguration();
    }

    function displayUnitConfiguration() {
        console.log('=== DISPLAY UNIT CONFIGURATION ===');
        console.log('Selected rooms:', selectedRooms);
        console.log('Survey room sections:', $('.survey-room-section').length);
        
        if (selectedRooms.length === 0) {
            $('#unit-configuration-container').html(`
                <div class="text-center text-muted py-5">
                    <i class="fas fa-cogs fa-3x mb-3"></i>
                    <h5>No Rooms Selected</h5>
                    <p>Please select rooms in Step 3 first to configure rental units.</p>
                </div>
            `);
            return;
        }

        // Get room data from the room tables
        let html = '';

        $('.survey-room-section').each(function() {
            const surveySection = $(this);
            const selectedRoomsForSurvey = surveySection.find('.room-checkbox:checked');
            
            console.log('Survey section found, selected rooms:', selectedRoomsForSurvey.length);
            
            if (selectedRoomsForSurvey.length > 0) {
                // Get survey info from the header - try h5 first (actual), then h6 for backward compatibility
                const surveyHeaderH5 = surveySection.find('.card-header h5').text();
                const surveyHeaderH6 = surveySection.find('.card-header h6').text();
                const surveyHeaderText = surveyHeaderH5 || surveyHeaderH6;
                const surveyNumber = surveyHeaderText.replace('Survey:', '').replace('Survey: ', '').trim();
                const customerName = surveySection.find('.card-header small').first().text().replace('Customer:', '').replace('Customer: ', '').trim();
                const surveyId = selectedRoomsForSurvey.first().data('survey');
                
                console.log('Processing survey:', surveyNumber, 'Customer:', customerName, 'Survey ID:', surveyId);
                
                // Create room list for this survey
                let roomListHtml = '';
                selectedRoomsForSurvey.each(function(index) {
                    const roomRow = $(this).closest('tr');
                    const roomName = roomRow.find('td:eq(2) strong').text();
                    const roomType = roomRow.find('td:eq(2) small').text();
                    const roomSpecs = roomRow.find('td:eq(3) .room-specs').html();
                    const roomId = $(this).val();
                    const masterRoomId = $(this).data('master-room-id') || '';
                    
                    roomListHtml += `
                        <tr>
                            <td>${index + 1}</td>
                            <td>
                                <strong>${roomName}</strong><br>
                                <small class="text-muted">${roomType}</small>
                            </td>
                            <td>
                                <div class="room-specs-display">
                                    ${roomSpecs}
                                </div>
                            </td>
                        </tr>
                        <tr class="rental-button-row" data-room-id="${roomId}" data-master-room-id="${masterRoomId}">
                            <td colspan="3" class="text-center py-3" style="background-color: #f8f9fa;">
                                <button type="button" class="btn btn-primary btn-sm add-rental-btn" data-survey-id="${surveyId}" data-room-id="${roomId}" data-master-room-id="${masterRoomId}">
                                    <i class="fas fa-plus me-2"></i>TAMBAH RENTAL
                                </button>
                            </td>
                        </tr>
                        <tr class="rental-config-row" data-room-id="${roomId}" data-master-room-id="${masterRoomId}" style="display: none;">
                            <td colspan="3" class="rental-config-container" data-room-id="${roomId}" data-master-room-id="${masterRoomId}">
                                <!-- Rental configuration form will be inserted here -->
                            </td>
                        </tr>
                    `;
                });
                
                html += `
                    <div class="survey-unit-section mb-4" data-survey-id="${surveyId}">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-2" style="color: white !important; font-size: 20px !important; font-weight: bold !important;">
                                    <i class="fas fa-clipboard-list me-2"></i>
                                    Survey: ${surveyNumber}
                                </h5>
                                <small>Customer: ${customerName}</small>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead class="table-dark">
                                            <tr>
                                                <th width="10%">No</th>
                                                <th width="40%">Nama Ruangan</th>
                                                <th width="50%">Spesifikasi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${roomListHtml}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }
        });

        const selectedCustomRooms = $('#custom-rooms-container .custom-room-checkbox:checked');
        if (selectedCustomRooms.length > 0) {
            let customRoomListHtml = '';
            selectedCustomRooms.each(function(index) {
                const checkbox = $(this);
                const roomRow = checkbox.closest('tr');
                const roomName = roomRow.find('td:eq(2) strong').text();
                const roomType = roomRow.find('td:eq(2) small').text();
                const roomSpecs = roomRow.find('td:eq(3)').html();
                const roomId = checkbox.val();
                const surveyId = checkbox.data('survey') || 'custom';
                const masterRoomId = checkbox.data('master-room-id') || '';

                customRoomListHtml += `
                    <tr>
                        <td>${index + 1}</td>
                        <td>
                            <strong>${roomName}</strong><br>
                            <small class="text-muted">${roomType}</small>
                        </td>
                        <td>
                            <div class="room-specs-display">
                                ${roomSpecs}
                            </div>
                        </td>
                    </tr>
                    <tr class="rental-button-row" data-room-id="${roomId}" data-master-room-id="${masterRoomId}">
                        <td colspan="3" class="text-center py-3" style="background-color: #f8f9fa;">
                            <button type="button" class="btn btn-primary btn-sm add-rental-btn" data-survey-id="${surveyId}" data-room-id="${roomId}" data-master-room-id="${masterRoomId}">
                                <i class="fas fa-plus me-2"></i>TAMBAH RENTAL
                            </button>
                        </td>
                    </tr>
                    <tr class="rental-config-row" data-room-id="${roomId}" data-master-room-id="${masterRoomId}" style="display: none;">
                        <td colspan="3" class="rental-config-container" data-room-id="${roomId}" data-master-room-id="${masterRoomId}"></td>
                    </tr>
                `;
            });

            html += `
                <div class="survey-unit-section mb-4" data-survey-id="custom">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-2" style="color: white !important; font-size: 20px !important; font-weight: bold !important;">
                                <i class="fas fa-plus-square me-2"></i>
                                Ruangan Tambahan (Custom)
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead class="table-dark">
                                        <tr>
                                            <th width="10%">No</th>
                                            <th width="40%">Nama Ruangan</th>
                                            <th width="50%">Spesifikasi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${customRoomListHtml}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        if (html === '') {
            html = `
                <div class="text-center text-muted py-5">
                    <i class="fas fa-cogs fa-3x mb-3"></i>
                    <h5>No Selected Rooms</h5>
                    <p>Please select rooms in Step 3 first to configure rental units.</p>
                </div>
            `;
        }

        console.log('Generated HTML for unit configuration:', html);
        $('#unit-configuration-container').html(html);
        
        // Force visibility and debugging
        setTimeout(function() {
            console.log('=== FORCE VISIBILITY DEBUG ===');
            const allCards = $('.card');
            console.log('All cards found:', allCards.length);
            
            allCards.each(function(index) {
                const card = $(this);
                const header = card.find('.card-header h6');
                console.log(`Card ${index} header text:`, header.text());
                console.log(`Card ${index} header visible:`, header.is(':visible'));
                console.log(`Card ${index} header display:`, header.css('display'));
                console.log(`Card ${index} header color:`, header.css('color'));
                
                // Force visibility
                header.css({
                    'display': 'block !important',
                    'visibility': 'visible !important',
                    'opacity': '1 !important',
                    'color': 'white !important',
                    'font-size': '18px !important'
                });
            });
            
            // Restore rental configurations after HTML is fully rendered
            console.log('Calling restoreStep4Data after HTML render');
            restoreStep4Data();
        }, 500);
        
        // Debug: Check if survey headers are rendered
        setTimeout(function() {
            const surveyHeaders = $('.survey-room-section .card-header h6');
            console.log('Survey headers found:', surveyHeaders.length);
            surveyHeaders.each(function(index) {
                console.log(`Survey header ${index}:`, $(this).text());
            });
            
            // Also check unit configuration headers
            const unitHeaders = $('.survey-unit-section .card-header h6');
            console.log('Unit configuration headers found:', unitHeaders.length);
            unitHeaders.each(function(index) {
                console.log(`Unit header ${index}:`, $(this).text());
            });
        }, 100);
    }

    function restoreStep4Data() {
        console.log('=== RESTORING STEP 4 DATA ===');
        
        let savedData = localStorage.getItem('quotation_step4_data');
        
        // --- RENEWAL FALLBACK ---
        if (!savedData && window.renewalContractData && window.renewalContractData.rentals) {
            console.log('Using Renewal Data fallback for Step 4');
            const transformed = window.renewalContractData.rentals.map(rental => ({
                uniqueId: `renewal-${rental.contract_rental_id || Date.now()}-${Math.random()}`,
                surveyId: rental.survey_id || window.renewalContractData.survey_id || 'custom',
                roomId: rental.survey_detail_id || rental.room_id,
                masterRoomId: rental.master_room_id || rental.room_id || null,
                roomName: rental.room_name || ('Room ' + rental.room_id),
                roomType: rental.room_type || '', // Capture room type
                formData: {
                    product_id: rental.rental_id,
                    productName: rental.rental_name,
                    price: rental.rental_price,
                    quantity: rental.quantity,
                    remark: rental.notes,
                    rental_alias: rental.rental_alias
                }
            }));
            savedData = JSON.stringify(transformed);
            localStorage.setItem('quotation_step4_data', savedData);
        }

        if (!savedData) {
            console.log('No step4_data found in localStorage');
            return;
        }
        
        try {
            const rentalConfigs = JSON.parse(savedData);
            console.log('Found rental configurations to restore:', rentalConfigs.length, rentalConfigs);
            
            if (rentalConfigs.length === 0) {
                console.log('Found 0 rental configurations to restore');
                return;
            }
            
            // Restore each rental configuration
            rentalConfigs.forEach((config, index) => {
                console.log(`Restoring rental config ${index}:`, config);
                
                let surveyId = config.surveyId;
                const roomId = config.roomId;
                const masterRoomId = config.masterRoomId || null;
                const roomName = config.roomName;
                const roomType = config.roomType; // Get room type
                
                // Find the "Tambah Rental" button for this room
                let addButton = $(`.add-rental-btn[data-survey-id="${surveyId}"][data-room-id="${roomId}"]`);
                let actualRoomId = roomId;
                
                // STRATEGY 2: Match by Master Room ID (Critical for Renewal)
                if (addButton.length === 0) {
                    addButton = $(`.add-rental-btn[data-survey-id="${surveyId}"][data-master-room-id="${masterRoomId || roomId}"]`);
                    if (addButton.length > 0) {
                        console.log(`Step 4: MasterRoom ID match found! Room ${masterRoomId || roomId} -> actual id ${addButton.data('room-id')}`);
                        actualRoomId = addButton.data('room-id');
                    }
                }
                
                // FALLBACK: Match by Name if ID matches fail
                if (addButton.length === 0 && roomName && !roomName.startsWith('Room ')) {
                    console.log(`Step 4: ID match failed for Room ${roomId}. Trying Name Match: "${roomName}" (Type: ${roomType || 'N/A'})`);
                    
                    // Stage 3: Exact Name Match
                    $('.add-rental-btn').each(function() {
                        const btn = $(this);
                        if (btn.data('survey-id') == surveyId) {
                            const row = btn.closest('tr.rental-button-row').prev('tr');
                            const nameInTable = row.find('td:eq(1) strong').text().trim();
                            if (nameInTable.toLowerCase() === roomName.trim().toLowerCase()) {
                                console.log(`Found Step 4 exact name match! Room ${roomId} -> actual id ${btn.data('room-id')}`);
                                addButton = btn;
                                actualRoomId = btn.data('room-id');
                                return false;
                            }
                        }
                    });

                    // Stage 4: Fuzzy Name Match (Contains) + Type
                    if (addButton.length === 0) {
                        $('.add-rental-btn').each(function() {
                            const btn = $(this);
                            if (btn.data('survey-id') == surveyId) {
                                const row = btn.closest('tr.rental-button-row').prev('tr');
                                const nameInTable = row.find('td:eq(1) strong').text().trim().toLowerCase();
                                const typeInTable = row.find('td:eq(1) small').text().trim().toLowerCase();
                                const targetName = roomName.trim().toLowerCase();
                                const targetType = roomType ? roomType.trim().toLowerCase() : null;

                                const nameMatch = nameInTable.includes(targetName) || targetName.includes(nameInTable);
                                const typeMatch = !targetType || typeInTable.includes(targetType) || targetType.includes(typeInTable);

                                if (nameMatch && typeMatch) {
                                    console.log(`Found Step 4 fuzzy name match! ("${nameInTable}" matches "${targetName}") Mapping Room ${roomId} -> actual id ${btn.data('room-id')}`);
                                    addButton = btn;
                                    actualRoomId = btn.data('room-id');
                                    return false;
                                }
                            }
                        });
                    }

                    // Stage 5: Global Search (Cross-Survey)
                    if (addButton.length === 0) {
                        console.log(`Step 4: Local search failed. Trying GLOBAL Search across all surveys for "${roomName}"...`);
                        $('.add-rental-btn').each(function() {
                            const btn = $(this);
                            // Skip the survey we already checked
                            if (btn.data('survey-id') == surveyId) return true;
                            
                            const row = btn.closest('tr.rental-button-row').prev('tr');
                            const nameInTable = row.find('td:eq(1) strong').text().trim().toLowerCase();
                            
                            if (nameInTable === roomName.trim().toLowerCase()) {
                                console.log(`Found Step 4 GLOBAL match! Room "${roomName}" found in Survey ${btn.data('survey-id')}. Updating Survey ID.`);
                                addButton = btn;
                                actualRoomId = btn.data('room-id');
                                surveyId = btn.data('survey-id'); // Update the let variable
                                return false;
                            }
                        });
                    }
                }

                if (addButton.length === 0) {
                    console.warn(`Add rental button not found for survey ${surveyId}, room ${roomId}`);
                    return; // skip this config
                }
                
                // Use ACTUAL ID for the item creation
                console.log(`Directly restoring rental item with ID: ${config.uniqueId} for Room: ${actualRoomId}`);
                addRentalItem(surveyId, actualRoomId, addButton, config.uniqueId);
                
                // Wait for form to be created, then fill it
                // Increased delay to ensure populateRentalRoomOptions can find the DOM elements
                setTimeout(() => {
                    const uniqueId = config.uniqueId;
                    const rentalForm = $(`#rental-config-${uniqueId}`);
                    
                    // If form with exact uniqueId not found, find by survey+room
                    let targetForm = rentalForm.length > 0 ? rentalForm : null;
                    
                    if (!targetForm || targetForm.length === 0) {
                        // Find by survey+room (re-check with actual ID from addButton)
                        const configRow = $(`.rental-config-row[data-room-id="${actualRoomId}"]`);
                        targetForm = configRow.find('.rental-configuration').last();
                    }
                    
                    if (targetForm && targetForm.length > 0) {
                        console.log('Filling form for config:', config.uniqueId);
                        
                        // Fill product (Select2)
                        const productSelect = targetForm.find('.rental-product-select');
                        if (productSelect.length > 0 && config.formData.product_id) {
                            const productId = config.formData.product_id;
                            const productName = config.formData.productName;

                             // Check if Select2 is initialized
                            if (productSelect.hasClass('select2-hidden-accessible')) {
                                // Create option if it doesn't exist
                                if (productSelect.find(`option[value="${productId}"]`).length === 0) {
                                    const newOption = new Option(productName, productId, true, true);
                                    productSelect.append(newOption);
                                }
                                 productSelect.val(productId).trigger('change'); 
                                // REMOVED: productSelect.trigger('select2:select'); // This was causing TypeError
                            } else {
                                // Select2 not yet initialized, set value directly
                                if (productSelect.find(`option[value="${productId}"]`).length === 0) {
                                     productSelect.append(new Option(productName, productId, true, true));
                                }
                                productSelect.val(productId);
                            }
                            console.log('Set product:', productId, productName, 'Current Val:', productSelect.val());
                        }
                        
                        // Fill other fields
                        targetForm.find('input[name*="price"]').val(config.formData.price);
                        targetForm.find('input[name*="quantity"]').val(config.formData.quantity);
                        targetForm.find('input[name*="remark"]').val(config.formData.remark || '');
                        targetForm.find('[name*="rental_alias"]').val(config.formData.rental_alias || '');
                        
                        console.log('Filled form values:', config.formData);
                        
                        // Force update button state after filling THIS item
                        updateNextButtonState();
                    } else {
                        console.warn('Could not find form to restore for config:', config.uniqueId);
                    }
                }, 100 * (index + 1)); // Stagger slightly more than 50ms to be safe
            });
            
            console.log('Rental configurations restoration initiated');
        } catch (e) {
            console.error('Error restoring step 4 data:', e);
        }
    }

    function addRentalItem(surveyId, roomId, buttonElement, restoredUniqueId = null) {
        console.log('=== ADD RENTAL ITEM FUNCTION CALLED ===');
        console.log('Survey ID:', surveyId);
        console.log('Room ID:', roomId);
        console.log('Function called at:', new Date().toISOString());
        console.log('Current rental configurations count:', $('.rental-configuration').length);
        console.log('Stack trace:', new Error().stack);
        
        // Find the rental config row for this room
        const buttonRow = buttonElement.closest('tr.rental-button-row');
        const configRow = buttonRow.next('tr.rental-config-row');
        const configContainer = configRow.find('.rental-config-container');
        
        // Create rental configuration form with unique ID
        const uniqueId = restoredUniqueId || `${surveyId}-${roomId}-${Date.now()}-${Math.random().toString(36).substr(2, 5)}`;
        const rentalFormHtml = `
            <div class="rental-configuration mt-2" id="rental-config-${uniqueId}" data-survey-id="${surveyId}" data-room-id="${roomId}">
                <div class="card shadow-sm mb-3" style="border-left: 4px solid #10b981;">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                        <h6 class="mb-0 text-success">
                            <i class="fas fa-cogs me-2"></i>
                            Konfigurasi Rental #${configContainer.find('.rental-configuration').length + 1}
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">Pilih Ruangan</label>
                                    <div class="room-display-container">
                                        <!-- Will be populated by populateRentalRoomOptions -->
                                        <select class="form-control" name="rental_items[${uniqueId}][room_id]" required>
                                            <option value="">Pilih Ruangan</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">Produk Rental</label>
                                    <select class="form-control rental-product-select" name="rental_items[${uniqueId}][product_id]" required>
                                        <option value="">Pilih Produk Rental</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label class="form-label">Harga (Quote Price)</label>
                                    <input type="number" class="form-control" name="rental_items[${uniqueId}][price]" placeholder="Harga" min="0" step="1" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label class="form-label">Quantity</label>
                                    <input type="number" class="form-control" name="rental_items[${uniqueId}][quantity]" placeholder="Qty" min="1" value="1" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label class="form-label">Remark</label>
                                    <input type="text" class="form-control" name="rental_items[${uniqueId}][remark]" placeholder="Remark">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group mb-3">
                                    <label class="form-label">Rental Alias</label>
                                    <select class="form-control" name="rental_items[${uniqueId}][rental_alias]">
                                        <option value="">No Alias (Use Product Name)</option>
                                        ${window.rentalAliasOptions.map(opt => `<option value="${opt.option_name}">${opt.option_name}</option>`).join('')}
                                    </select>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Pilih alias rental (opsional, default: nama produk). Hanya tampil berbasis rental, bukan berbasis ruangan.
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-danger btn-sm remove-rental-config-btn" data-survey-id="${surveyId}" data-unique-id="${uniqueId}">
                                <i class="fas fa-trash me-1"></i>Hapus Konfigurasi
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Append form into the config container
        configContainer.append(rentalFormHtml);
        configRow.slideDown();
        
        console.log('Rental form appended successfully below button');
        console.log('Total rental configurations after append:', $('.rental-configuration').length);
        
        // Populate room options with the specific room pre-selected
        populateRentalRoomOptions(surveyId, uniqueId, roomId);
        
        // Initialize Select2 for product selection
        initializeProductSelect2(surveyId, uniqueId);
        
        // Scroll to the form
        $('html, body').animate({
            scrollTop: configRow.offset().top - 100
        }, 500);
        
        // Update Next button state
        updateNextButtonState();
    }

    function populateRentalRoomOptions(surveyId, uniqueId, roomId) {
        const rentalConfig = $(`#rental-config-${uniqueId}`);
        const roomDisplayContainer = rentalConfig.find('.room-display-container');
        
        console.log('=== POPULATE ROOM OPTIONS FOR SURVEY ===', surveyId, 'with uniqueId:', uniqueId, 'roomId:', roomId);
        
        // Find the specific room row to get room name
        let roomName = '';
        let roomType = '';
        
        // Try to find room in Step 4 table first (current step)
        const step4Table = $(`.survey-unit-section[data-survey-id="${surveyId}"] table tbody`);
        let found = false;
        
        if (step4Table.length > 0) {
            step4Table.find('tr').each(function() {
                const row = $(this);
                // Check if next row is rental-button-row with matching room-id
                const nextRow = row.next('tr.rental-button-row');
                if (nextRow.length > 0 && nextRow.data('room-id') == roomId) {
                    roomName = row.find('td:eq(1) strong').text();
                    roomType = row.find('td:eq(1) small').text();
                    found = true;
                    return false; // break
                }
            });
        }
        
        // If not found in Step 4, try Step 3
        if (!found) {
            const roomCheckbox = $(`.room-checkbox[value="${roomId}"]`);
            if (roomCheckbox.length > 0) {
                const roomRow = roomCheckbox.closest('tr');
                roomName = roomRow.find('td:eq(2) strong').text() || roomRow.find('td:nth-child(3) strong').text();
                roomType = roomRow.find('td:eq(2) small').text() || roomRow.find('td:nth-child(3) small').text();
                found = true;
            }
        }
        
        console.log('Found room:', roomName, '-', roomType, 'found:', found);
        
        // Replace select with readonly display and hidden input
        const displayText = roomName ? `${roomName}${roomType ? ' - ' + roomType : ''}` : `Room ID: ${roomId}`;
        
        // Critical: Ensure we replace the select, especially during restoration
        if (roomDisplayContainer.length > 0) {
            console.log('Replacing room select with hidden input for room:', roomId);
            roomDisplayContainer.html(`
                <input type="text" class="form-control" value="${displayText}" readonly style="background-color: #e9ecef; cursor: not-allowed;">
                <input type="hidden" name="rental_items[${uniqueId}][room_id]" value="${roomId}">
                <small class="form-text text-muted">
                    <i class="fas fa-lock me-1"></i>Ruangan otomatis terisi sesuai room yang dipilih
                </small>
            `);
        } else {
            console.warn('Room display container NOT FOUND for uniqueId:', uniqueId);
            // Fallback: If container not found, try to find the select by name and replace it directly
            const fallbackSelect = $(`select[name="rental_items[${uniqueId}][room_id]"]`);
            if (fallbackSelect.length > 0) {
                console.log('Found fallback select, replacing manually');
                const parent = fallbackSelect.parent();
                fallbackSelect.remove();
                parent.append(`
                    <input type="text" class="form-control" value="${displayText}" readonly style="background-color: #e9ecef; cursor: not-allowed;">
                    <input type="hidden" name="rental_items[${uniqueId}][room_id]" value="${roomId}">
                `);
            }
        }
    }

    function initializeProductSelect2(surveyId, uniqueId) {
        const productSelect = $(`#rental-config-${uniqueId} .rental-product-select`);
        
        console.log('Initializing Select2 for product select:', productSelect.length, 'uniqueId:', uniqueId);
        
        // Destroy existing Select2 if any
        if (productSelect.hasClass('select2-hidden-accessible')) {
            productSelect.select2('destroy');
        }
        
        productSelect.select2({
            placeholder: 'Pilih Produk Rental...',
            allowClear: true,
            width: '100%',
            dropdownParent: $(`#rental-config-${uniqueId}`),
            ajax: {
                url: '{{ route("marketing.quotations.wizard.get-products") }}',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { 
                        q: params.term,
                        survey_id: surveyId, // Send survey_id to get branch_id from building location
                        branch_id: null // Will be determined from survey/building location
                    };
                },
                processResults: function (data, params) {
                    return {
                        results: data.map(product => ({ 
                            id: product.id, 
                            text: product.text,
                            name: product.name,
                            code: product.code,
                            daily_price: product.daily_price,
                            monthly_price: product.monthly_price,
                            unit: product.unit,
                            is_corporate_price: product.is_corporate_price // Pass flag to selection
                        }))
                    };
                },
                cache: true
            }
        }).on('select2:select', function (e) {
            if (!e.params || !e.params.data) {
                console.warn('Select2:select triggered without data params');
                return;
            }
            
            const data = e.params.data;
            const rentalConfig = $(this).closest('.rental-configuration');
            const priceInput = rentalConfig.find('input[name*="price"]');
            const priceLabel = priceInput.closest('.form-group').find('label');

            // Handle Master Corporate Pricing
            if (data.is_corporate_price) {
                // Set price
                const price = data.monthly_price; // Always use monthly/agreed price for corporate
                priceInput.val(price).trigger('change');
                
                // Lock the field
                priceInput.prop('readonly', true).addClass('bg-light');
                
                // Add indicator if not present
                if (priceLabel.find('.corporate-badge').length === 0) {
                    priceLabel.append('<span class="badge bg-warning text-dark ms-2 corporate-badge"><i class="fas fa-lock me-1"></i>Corporate Price</span>');
                }
            } else {
                // Calculate standard price if not corporate
                if (!window.isPopulatingData && !window.isRestoringData) {
                    const rentalUnit = $('#rental_unit').val();
                    const price = rentalUnit === 'hari' ? data.daily_price : data.monthly_price;
                    priceInput.val(price).trigger('change');
                }
                
                // Unlock the field
                priceInput.prop('readonly', false).removeClass('bg-light');
                priceLabel.find('.corporate-badge').remove();
            }
            
            // Trigger validation immediately after selection
            setTimeout(() => {
                updateNextButtonState();
                console.log('Button state updated after product selection');
            }, 100);
        });
    }

    // Add global listener for rental inputs to update button state
    $(document).on('change input', '.rental-configuration input, .rental-configuration select, .rental-configuration textarea', function() {
        // Use debounce/timeout to avoid excessive updates
        const input = $(this);
        if (input.data('timeout')) {
            clearTimeout(input.data('timeout'));
        }
        
        input.data('timeout', setTimeout(() => {
            updateNextButtonState();
        }, 300));
    });

    function removeRentalConfiguration(surveyId, uniqueId) {
        console.log('Removing rental configuration for survey:', surveyId, 'with uniqueId:', uniqueId);
        
        // IMPORTANT: Properly destroy Select2 instances before removing the DOM element
        // This prevents Select2 from leaving orphaned dropdown containers
        const rentalConfig = $(`#rental-config-${uniqueId}`);
        
        // Find and destroy all Select2 instances within this rental configuration
        rentalConfig.find('.select2-hidden-accessible').each(function() {
            try {
                $(this).select2('destroy');
                console.log('Destroyed Select2 for:', $(this).attr('name'));
            } catch (e) {
                console.log('Error destroying Select2:', e);
            }
        });
        
        const container = rentalConfig.closest('.rental-config-container');
        
        // Remove the rental configuration
        rentalConfig.remove();
        
        // Renumber remaining configurations in this container
        if (container.length > 0) {
            container.find('.rental-configuration').each(function(index) {
                $(this).find('.card-header h6').html(`<i class="fas fa-cogs me-2"></i>Konfigurasi Rental #${index + 1}`);
            });
        }
        
        // Also clean up any orphaned Select2 dropdown containers
        $('.select2-container').each(function() {
            const $container = $(this);
            // Check if the parent element still exists in the DOM
            const parentId = $container.data('select2-id');
            if (parentId && !$(`#${parentId}`).length) {
                $container.remove();
                console.log('Removed orphaned Select2 container:', parentId);
            }
        });
        
        updateNextButtonState();
        calculateStep5Totals(); // Recalculate totals after removal
    }

    // Event delegation for 'Tambah Rental' button
    // IMPORTANT: Use .off() first to prevent duplicate event listeners
    $(document).off('click', '.add-rental-btn').on('click', '.add-rental-btn', function() {
        console.log('=== TAMBAH RENTAL BUTTON CLICKED ===');
        console.log('Button element:', this);
        console.log('Button data-survey-id:', $(this).data('survey-id'));
        console.log('Button data-room-id:', $(this).data('room-id'));
        console.log('Current time:', new Date().toISOString());
        console.log('Total .add-rental-btn buttons in DOM:', $('.add-rental-btn').length);
        console.log('Existing rental configurations before adding:', $('.rental-configuration').length);
        
        const surveyId = $(this).data('survey-id');
        const roomId = $(this).data('room-id');
        addRentalItem(surveyId, roomId, $(this));
        
        // Log after adding
        setTimeout(() => {
            console.log('Existing rental configurations after adding:', $('.rental-configuration').length);
        }, 100);
    });

    // Event delegation for 'Hapus Konfigurasi' button
    // IMPORTANT: Use .off() first to prevent duplicate event listeners
    $(document).off('click', '.remove-rental-config-btn').on('click', '.remove-rental-config-btn', function() {
        const surveyId = $(this).data('survey-id');
        const uniqueId = $(this).data('unique-id');
        removeRentalConfiguration(surveyId, uniqueId);
    });

    function populateRoomOptions(surveyId, itemCount) {
        const roomSelect = $(`#rental-item-${surveyId}-${itemCount} select[name*="room_id"]`);
        
        // Get rooms from the survey section
        const surveySection = $(`.survey-room-section:has(.room-checkbox[data-survey="${surveyId}"]:checked)`);
        const selectedRooms = surveySection.find('.room-checkbox:checked');
        
        selectedRooms.each(function() {
            const roomId = $(this).val();
            const roomName = $(this).closest('tr').find('td:nth-child(3) strong').text();
            const roomSpecs = $(this).closest('tr').find('td:nth-child(4) .room-specs').html();
            
            roomSelect.append(`<option value="${roomId}" data-specs="${roomSpecs}">${roomName}</option>`);
        });
        
        // Add change event to update specs when room is selected
        roomSelect.on('change', function() {
            const selectedOption = $(this).find('option:selected');
            const specs = selectedOption.data('specs');
            const specsContainer = $(this).closest('tr').find('.room-specs-display');
            
            if (specs) {
                specsContainer.html(specs);
            } else {
                specsContainer.html('<small class="text-muted">Pilih ruangan untuk melihat spesifikasi</small>');
            }
        });
    }

    function removeRentalItem(surveyId, itemCount) {
        $(`#rental-item-${surveyId}-${itemCount}`).remove();
        
        // Renumber remaining items
        const rentalItemsContainer = $(`#rental-items-${surveyId}`);
        rentalItemsContainer.find('tr').each(function(index) {
            $(this).find('td:first-child').text(index + 1);
        });
        
        updateNextButtonState();
    }

    // ===== SUMMARY =====
    // loadSummary() function is defined above in STEP 7 SUMMARY section

    // ===== VALIDATION =====
    function validateCurrentStep() {
        const isValid = validateStepFields(currentStep);
        
        if (!isValid) {
            const stepNames = {
                1: 'Marketing Information',
                2: 'Survey Selection',
                3: 'Room Selection',
                4: 'Unit Configuration',
                5: 'Remark & Discount',
                6: 'PIC Selection',
                7: 'Summary'
            };
            
            Swal.fire({
                title: 'Validation Error',
                text: `Please complete all required fields in ${stepNames[currentStep]}`,
                icon: 'warning',
                confirmButtonText: 'OK'
            });
        }
        
        return isValid;
    }

    function validateStepFields(step) {
        if (window.isPopulatingData || window.isRestoringData) {
            console.log(`Skipping validation for Step ${step} because data is being populated/restored`);
            return true;
        }
        
        let isValid = true;
        let missingFields = [];
        
        switch(step) {
            case 1:
                if (!window.isPopulatingData) console.log('=== STEP 1 VALIDATION DEBUG ===');
                console.log('Marketing ID:', $('#marketing_id').val());
                console.log('Quotation Date:', $('#quotation_date').val());
                console.log('Quotation Type:', $('#quotation_type').val());
                console.log('Rental Period:', $('#rental_period').val());
                console.log('Rental Unit:', $('#rental_unit').val());
                console.log('Payment Method:', $('#payment_method').val());
                console.log('Term of Payment:', $('#term_of_payment').val());
                
                if (!$('#marketing_id').val()) {
                    missingFields.push('Marketing');
                    isValid = false;
                }
                if (!$('#quotation_date').val()) {
                    missingFields.push('Tanggal Quotation');
                    isValid = false;
                }
                if (!$('#quotation_type').val()) {
                    missingFields.push('Jenis Penawaran');
                    isValid = false;
                }
                if ($('#quotation_type').val() === 'renewal' && $('#contract-field').hasClass('show') && !$('#existing_contract_id').val()) {
                    missingFields.push('Contract');
                    isValid = false;
                }
                if (!$('#rental_period').val()) {
                    missingFields.push('Periode Sewa');
                    isValid = false;
                }
                if (!$('#rental_unit').val()) {
                    missingFields.push('Satuan');
                    isValid = false;
                }
                if (!$('#payment_method').val()) {
                    missingFields.push('Payment Method');
                    isValid = false;
                }
                if (!$('#term_of_payment').val()) {
                    missingFields.push('Term of Payment');
                    isValid = false;
                }
                
                console.log('Missing fields:', missingFields);
                console.log('Is valid:', isValid);
                break;
            case 2:
                const selectedSurveys = $('#survey_tags').val() || [];
                const isRenewalWithExistingRooms = $('#quotation_type').val() === 'renewal'
                    && window.renewalContractData
                    && Array.isArray(window.renewalContractData.rooms)
                    && window.renewalContractData.rooms.length > 0;
                if (selectedSurveys.length === 0 && !isRenewalWithExistingRooms) {
                    missingFields.push('Survey');
                    isValid = false;
                }
                break;
            case 3:
                const selectedRooms = $('.room-checkbox:checked');
                if (selectedRooms.length === 0) {
                    missingFields.push('Room');
                    isValid = false;
                } else {
                    // Check if aroma is selected for each selected room
                    let missingAroma = false;
                    selectedRooms.each(function() {
                        const checkbox = $(this);
                        const isCustom = checkbox.hasClass('custom-room-checkbox');
                        let aromaSelect;
                        
                        const roomId = checkbox.val();
                        const surveyId = isCustom ? 'custom' : checkbox.data('survey');
                        
                        if (isCustom) {
                            aromaSelect = $(`.custom-aroma-select[data-room-id="${roomId}"]`);
                        } else {
                            aromaSelect = $(`.aroma-select[data-survey="${surveyId}"][data-room="${roomId}"]`);
                        }
                        
                        if (aromaSelect.length > 0 && !aromaSelect.val()) {
                            console.log(`Validation FAIL: Aroma not selected for Room ${roomId} (Survey ${surveyId})`);
                            missingAroma = true;
                            return false; // break loop
                        } else if (aromaSelect.length === 0) {
                            console.warn(`Validation WARNING: Aroma select not found for checked Room ${roomId} (Survey ${surveyId})`);
                        } else {
                            console.log(`Validation OK: Room ${roomId} has aroma ${aromaSelect.val()}`);
                        }
                    });
                    
                    if (missingAroma) {
                        missingFields.push('Aroma untuk setiap ruangan');
                        isValid = false;
                    }
                }
                break;
            case 4:
                // Step 4: Unit Configuration - Check rental configurations are complete
                const rentalConfigs = $('.rental-configuration');
                console.log('=== STEP 4 VALIDATION DEBUG ===');
                console.log('Rental configurations count:', rentalConfigs.length);
                console.log('Rental configuration elements:', rentalConfigs);
                
                if (rentalConfigs.length === 0) {
                    missingFields.push('Rental Configuration (minimal 1 ruangan harus dikonfigurasi)');
                    isValid = false;
                    console.log('Step 4 validation failed - no rental configurations');
                } else {
                    // Check each rental configuration has product and price
                    let incompleteConfigs = 0;
                    let missingProducts = [];
                    let missingPrices = [];
                    
                    rentalConfigs.each(function() {
                        const config = $(this);
                        const roomId = config.data('room-id');
                        const productSelect = config.find('.rental-product-select');
                        const priceInput = config.find('input[name*="price"]');
                        
                        // Get room name for better error message
                        const roomDisplay = config.find('.room-display-container input[type="text"]').val() || `Room ${roomId}`;
                        
                        const val = productSelect.val();
                        if (!val || val === '' || val === null) {
                            incompleteConfigs++;
                            missingProducts.push(roomDisplay);
                            console.log('Missing product for room:', roomDisplay, 'Val:', val);
                        }
                        
                        if (!priceInput.val() || parseFloat(priceInput.val()) <= 0) {
                            incompleteConfigs++;
                            missingPrices.push(roomDisplay);
                            console.log('Missing price for room:', roomDisplay);
                        }
                    });
                    
                    if (missingProducts.length > 0) {
                        missingFields.push('Produk Rental untuk: ' + missingProducts.join(', '));
                        isValid = false;
                    }
                    if (missingPrices.length > 0) {
                        missingFields.push('Harga untuk: ' + missingPrices.join(', '));
                        isValid = false;
                    }
                    
                    if (isValid) {
                        console.log('Step 4 validation passed - all configurations complete');
                    } else {
                        console.log('Step 4 validation failed - incomplete configurations');
                    }
                }
                break;
            case 5:
                // Step 5: Remark/Discount - Validate Basis Harga
                console.log('=== STEP 5 VALIDATION DEBUG ===');
                const priceBasis = $('#price_basis').val();
                console.log('Price Basis:', priceBasis);
                
                if (!priceBasis) {
                    missingFields.push('Basis Harga');
                    isValid = false;
                    console.log('Step 5 validation failed - Basis Harga not selected');
                } else {
                    console.log('Step 5 validation passed - Basis Harga:', priceBasis);
                }
                break;
            case 6:
                const picValue = $('#pic_quotation').val();
                if (!picValue) {
                    missingFields.push('PIC');
                    isValid = false;
                    // Only log as warning if this is during validation check (not just on step load)
                    // This prevents confusing console messages when step is first loaded
                } else {
                    console.log('Step 6 validation: PIC selected:', picValue);
                }
                break;
            case 7:
                // No required fields
                break;
        }
        
        if (!isValid) {
            // Only log missing fields if this is a validation check (not just on step load)
            // This prevents confusing console messages when step is first loaded
            if (step === currentStep) {
                console.log('Missing fields for step', step, ':', missingFields);
            } else {
                // Silent validation for other steps (just return false)
                console.log('Step', step, 'validation:', isValid ? 'PASSED' : 'FAILED (missing: ' + missingFields.join(', ') + ')');
            }
        } else {
            // Log successful validation only for debugging
            if (step === currentStep) {
                console.log('Step', step, 'validation: PASSED');
            }
        }
        
        return isValid;
    }

    window.updateNextButtonState = function() {
        const isStepValid = validateStepFields(currentStep);
        const nextBtn = $(`#step-${currentStep}-nav #nextBtn`);
        
        if (isStepValid) {
            nextBtn.prop('disabled', false).removeClass('btn-secondary').addClass('btn-primary');
        } else {
            nextBtn.prop('disabled', true).removeClass('btn-primary').addClass('btn-secondary');
        }
    }

    // ===== EVENT HANDLERS =====
    
    // Next button
    $(document).on('click', '#nextBtn', function() {
        console.log('Next button clicked, current step:', currentStep);
        
        // Save current step data before moving
        saveCurrentStepData();
        
        if (validateCurrentStep()) {
            if (currentStep < totalSteps) {
                currentStep++;
                showStep(currentStep);
            }
        }
    });

    // Previous button
    $(document).on('click', '#prevBtn', function() {
        console.log('Previous button clicked, current step:', currentStep);
        
        // Save current step data before moving
        saveCurrentStepData();
        
        if (currentStep > 1) {
            currentStep--;
            showStep(currentStep);
        }
    });
    
    // Function to save current step data
    function saveCurrentStepData() {
        switch(currentStep) {
            case 1:
                saveStep1Data();
                break;
            case 2:
                saveSurveySelections();
                break;
            case 3:
                updateRoomSelections();
                break;
            case 4:
                saveStep4Data();
                break;
            case 5:
                saveStep5Data();
                break;
            case 6:
                saveStep6Data();
                break;
        }
    }

    // Quotation type change
    $('#quotation_type').on('change', function() {
        const type = $(this).val();
        if (type === 'renewal') {
            $('#contract-field').addClass('show');
            loadEligibleContracts(); // Load contracts for renewal
        } else {
            $('#contract-field').removeClass('show');
            $('#existing_contract_id').val('').trigger('change');
        }
        
        // Only update button state if not currently restoring data
        if (!window.isPopulatingData && !window.isRestoringData) {
            updateNextButtonState();
        }
    });

    // Load eligible contracts for renewal
    // Load eligible contracts for renewal
    function loadEligibleContracts() {
        // If we're in edit mode, get the current contract ID to ensure it's included
        let includeId = null;
        const savedData = localStorage.getItem('quotation_step1_data');
        if (savedData) {
            try {
                const data = JSON.parse(savedData);
                includeId = data.existing_contract_id;
            } catch(e) {}
        }
        
        console.log('Loading eligible contracts. Including ID:', includeId);
        
        // Prepare data object
        const requestData = {
            marketing_id: $('#marketing_id').val(),
            branch_id: $('#branch_id').val() || $('#branch_id_hidden').val()
        };
        if (includeId) {
            requestData.include_id = includeId;
        }

        $.ajax({
            url: '{{ route("marketing.contract-renewals.eligible-contracts") }}',
            method: 'GET',
            data: requestData,
            success: function(response) {
                if (response.status === 'success') {
                    const select = $('#existing_contract_id');
                    select.empty().append('<option value="">Pilih contract...</option>');
                    
                    // Use all returned contracts (backend already handles filtering active status)
                    const eligibleContracts = response.data;
                    
                    eligibleContracts.forEach(function(contract) {
                        // Display format: No. Contract - Customer Name
                        const label = `${contract.contract_number} - ${contract.customer_name}`;
                        
                        const option = $('<option></option>')
                            .val(contract.id)
                            .text(label)
                            .data('contract', contract);
                        
                        select.append(option);
                    });
                    
                    console.log('Loaded ' + eligibleContracts.length + ' eligible contracts');
                    
                    // If we have a pre-selected value (from edit/restore), re-select it
                    const savedData = localStorage.getItem('quotation_step1_data');
                    if (savedData) {
                        try {
                            const data = JSON.parse(savedData);
                            if (data.existing_contract_id) {
                                console.log('Restoring existing_contract_id after load:', data.existing_contract_id);
                                
                                // FIX: Prevent infinite loop by checking if we're just restoring the same contract
                                if (window.currentLoadingContractId && window.currentLoadingContractId == data.existing_contract_id) {
                                    console.log('Preventing infinite loop: Setting value without triggering change event');
                                    select.val(data.existing_contract_id); // Set value WITHOUT triggering change
                                } else {
                                    select.val(data.existing_contract_id).trigger('change');
                                }
                                
                                // Critical: Update button state after select is populated
                                setTimeout(() => {
                                    updateNextButtonState();
                                }, 100);
                            }
                        } catch(e) {
                            console.error('Error restoring existing_contract_id:', e);
                        }
                    }
                } else {
                    console.error('Failed to load contracts:', response);
                    // alert('Failed to load contracts'); // Suppress alert to avoid UI noise
                }
            },
            error: function(xhr) {
                console.error('Error loading contracts:', xhr);
                // alert('Error loading contracts: ' + (xhr.responseJSON?.message || 'Unknown error'));
            }
        });
    }

    // Contract selection change - copy data from existing contract
    $('#existing_contract_id').on('change', function() {
        const contractId = $(this).val();
        if (contractId) {
            loadContractDataForRenewal(contractId);
        }
    });

    // Load contract data for renewal
    function loadContractDataForRenewal(contractId) {
        console.log('Loading contract data for renewal:', contractId);
        window.currentLoadingContractId = contractId; // Track which contract is being loaded/restored
        $.ajax({
            url: `/marketing/contract-renewals/${contractId}/for-renewal`,
            method: 'GET',
            success: function(response) {
                if (response.status === 'success') {
                    const data = response.data;
                    console.log('Contract data loaded:', data);
                    
                    // Populate step 1 fields
                    window.isPopulatingData = true;
                    
                    $('#marketing_id').val(data.marketing_id); // Don't trigger change yet, we'll call manually
                    if (data.branch_id) {
                        loadUserBranches(data.marketing_id, data.branch_id);
                    } else {
                    $('#marketing_id').trigger('change');
                    }
                    

                    
                    // PARSE RENTAL PERIOD: Extract number from string (e.g. "2 bulan" -> 2)
                    // The server sometimes sends "2 bulan" but input type="number" rejects it.
                    let rentalPeriodVal = data.rental_period;
                    if (typeof rentalPeriodVal === 'string') {
                        // Extract first number found
                        const match = rentalPeriodVal.match(/\d+/);
                        if (match) {
                            rentalPeriodVal = parseInt(match[0]);
                        }
                    }
                    $('#rental_period').val(rentalPeriodVal);
                    
                    // Also try to detect unit if not explicitly provided or if embedded in period string
                    let rentalUnitVal = data.rental_unit;
                    if ((!rentalUnitVal || rentalUnitVal === '') && typeof data.rental_period === 'string') {
                         if (data.rental_period.toLowerCase().includes('bulan')) rentalUnitVal = 'bulan';
                         else if (data.rental_period.toLowerCase().includes('hari')) rentalUnitVal = 'hari';
                    }
                    
                    $('#rental_unit').val(rentalUnitVal).trigger('change');
                    $('#payment_method').val(data.payment_method).trigger('change');
                    
                    // Robust Term of Payment selection
                    let topValue = (data.term_of_payment || '').trim();
                    
                    // Check compatibility before setting
                    let rentalMonths = data.rental_period || 12;
                    let topMonths = 0;
                    if (topValue.includes('bulan')) topMonths = parseInt(topValue);
                    else if (topValue !== 'Tahunan' && topValue.includes('tahunan')) topMonths = parseInt(topValue) * 12;
                    
                    if (topMonths > 0 && rentalMonths % topMonths !== 0) {
                        console.warn(`Incompatible TOP "${topValue}" for rental period ${rentalMonths}. Standardizing...`);
                        // Fallback: If 1 month rental, set 1 month TOP. Else set 1 month TOP as safest default.
                        topValue = '1 bulan 1x';
                    }
                    
                    // Ensure TOP exists in options
                    if ($('#term_of_payment option[value="' + topValue + '"]').length === 0) {
                        console.warn('TOP "' + topValue + '" not found in options. Defaulting to 1 bulan 1x.');
                        topValue = '1 bulan 1x';
                    }

                    $('#term_of_payment').val(topValue).trigger('change');
                    
                    // Store contract data for later use in wizard
                    window.renewalContractData = data;
                    // window.isPopulatingData = false; // Moved to timeout below
                    
                    // Delay clearing the flag to ensure all change events and async UI updates allow time to settle
                    // This prevents race conditions with saveStep1Data triggering on intermediate states
                    setTimeout(() => {
                        window.isPopulatingData = false;
                        console.log('Population completed, flag cleared.');
                    }, 1000);
                    
                    // Check for notes_sales and show pop-up if available
                    if (data.notes_sales && data.notes_sales.trim() !== '') {
                        console.log('Notes Sales found:', data.notes_sales);
                        showNotesSalesPopup(data.notes_sales);
                    } else {
                        // No notes_sales, show success notification
                        Swal.fire({
                            icon: 'success',
                            title: 'Contract Data Loaded',
                            html: `<div style="text-align: left;">
                                <p><strong>Contract:</strong> ${data.contract_number}</p>
                                <p><strong>Customer:</strong> ${data.customer.name}</p>
                                <p><strong>Rooms:</strong> ${data.rooms.length}</p>
                                ${data.eligibility.days_until_expiry !== null && data.eligibility.days_until_expiry !== undefined 
                                    ? `<p><strong>Days until expiry:</strong> ${data.eligibility.days_until_expiry}</p>` 
                                    : `<p style="color: #f59e0b;"><strong>Info:</strong> Belum ada BA date (kontrak belum dimulai)</p>`}
                            </div>`,
                            confirmButtonText: 'Continue',
                            confirmButtonColor: '#10b981'
                        });
                    }
                    
                    updateNextButtonState();

                    // === FULL DATA POPULATION FOR RENEWAL ===
                    console.log('=== POPULATING RENEWAL DATA ===');
                    
                    // 1. Set Survey
                    if (data.survey_id) {
                        console.log('Setting Survey ID for restoration:', data.survey_id);
                        
                        // Set global variable for loadSurveys() to pick up when it finishes
                        const surveyIds = [data.survey_id.toString()];
                        window.globalSurveySelections = surveyIds;
                        localStorage.setItem('quotation_survey_selections', JSON.stringify(surveyIds));
                        
                        // Force manual update of Select2 if surveys are already loaded or loadSurveys is slow
                        // This handles the case where marketing_id didn't change (so loadSurveys didn't fire)
                        // or just to be safe.
                        if ($('#survey_tags').hasClass('select2-hidden-accessible')) {
                             // If data.survey is available (from updated backend), we can create option
                             if (data.survey) {
                                  const label = `${data.survey.survey_number} - ${data.survey.customer_name}`;
                                  // Check if option exists
                                  if ($('#survey_tags option[value="' + data.survey.id + '"]').length === 0) {
                                      const newOption = new Option(label, data.survey.id, true, true);
                                      $('#survey_tags').append(newOption);
                                  }
                             }
                             $('#survey_tags').val(surveyIds).trigger('change');
                        }
                    }

                    // 2. Pre-fill Room Selections (Step 3)
                    const renewalRooms = Array.isArray(data.rooms) ? data.rooms : [];
                    let roomSelections = renewalRooms.map(room => ({
                            room_id: room.survey_detail_id || room.room_id,
                            survey_detail_id: room.survey_detail_id || null,
                            master_room_id: room.master_room_id || room.room_id || null,
                            contract_room_id: room.contract_room_id || null,
                            room_name: room.room_name,
                            survey_id: room.survey_id || data.survey_id || 'custom',
                            aroma_product_id: room.aroma_product_id, // Map from backend
                            aroma_variant: room.aroma_variant, // Map from backend
                            room_type: room.room_type // Map from backend
                        }));

                    // Fallback: If no contract rooms found, try to extract unique rooms from rentals
                    if (roomSelections.length === 0 && data.rentals && data.rentals.length > 0) {
                        console.log('DEBUG: No contract rooms found. Falling back to unique rooms from rentals. Rentals count:', data.rentals.length);
                        const seenRooms = new Set();
                        data.rentals.forEach(rental => {
                            console.log(`DEBUG: Checking rental for room fallback. ID: ${rental.room_id}, Type: ${rental.room_type}, Name: ${rental.room_name}`);
                            const roomKey = rental.master_room_id || rental.room_id || rental.room_name;
                            if (roomKey && !seenRooms.has(roomKey)) {
                                seenRooms.add(roomKey);
                                roomSelections.push({
                                    room_id: rental.survey_detail_id || rental.room_id,
                                    survey_detail_id: rental.survey_detail_id || null,
                                    master_room_id: rental.master_room_id || rental.room_id || null,
                                    contract_room_id: rental.contract_room_id || null,
                                    room_name: rental.room_name,
                                    survey_id: rental.survey_id || data.survey_id || 'custom',
                                    room_type: rental.room_type
                                });
                            }
                        });
                    }

                    if (roomSelections.length > 0) {
                        console.log('Pre-filling Room Selections:', roomSelections.length);
                        console.log('DEBUG: Final roomSelections computed:', roomSelections);

                        // Set globals for Step 3 to pick up
                        window.globalRoomSelections = roomSelections;
                        localStorage.setItem('quotation_room_selections', JSON.stringify(roomSelections));
                        
                        // DO NOT call loadRooms() here.
                        // It will be called naturally when the user navigates to Step 3.
                        // Calling it here causes "Select survey first" error because survey might not be ready.
                    }

                    // 3. Pre-fill Rental Configurations (Step 4)
                    if (data.rentals && data.rentals.length > 0) {
                        console.log('Pre-filling Rental Configurations:', data.rentals.length);
                        
                        // Transform contract rentals to Step 4 format
                        const step4Data = data.rentals.map(rental => {
                            return {
                                uniqueId: `renewal-${rental.contract_rental_id || Date.now()}-${Math.random()}`,
                                surveyId: rental.survey_id || data.survey_id || 'custom',
                                roomId: rental.survey_detail_id || rental.room_id,
                                masterRoomId: rental.master_room_id || rental.room_id || null,
                                roomName: rental.room_name || ('Room ' + rental.room_id),
                                roomType: rental.room_type || '', // Added for fuzzy matching
                                formData: {
                                    product_id: rental.rental_id,
                                    productName: rental.rental_name,
                                    price: rental.rental_price,
                                    quantity: rental.quantity,
                                    remark: rental.notes,
                                    rental_alias: rental.rental_alias
                                }
                            };
                        });
                        
                        // Set globals for Step 4 to pick up
                        localStorage.setItem('quotation_step4_data', JSON.stringify(step4Data));
                        localStorage.setItem('quotation_rental_configurations', JSON.stringify(step4Data));

                        console.log('Step 4 Data Prepared:', step4Data.length, 'items');
                    }

                    // 4. Pre-fill Step 5: Price Basis & Remarks
                    console.log('Pre-filling Step 5 data:', data.price_basis, data.remark_internal, data.remark_external);
                    const step5Data = {
                        price_basis: data.price_basis || '',
                        remark_internal: data.remark_internal || '',
                        remark_external: data.remark_external || ''
                    };
                    localStorage.setItem('quotation_step5_data', JSON.stringify(step5Data));
                    
                    // Directly populate DOM fields if they exist (even if currently hidden)
                    console.log('Directly populating Step 5 DOM fields...');
                    if (step5Data.price_basis) {
                        console.log('Setting price_basis to:', step5Data.price_basis);
                        $('#price_basis').val(step5Data.price_basis).trigger('change');
                    }
                    if (step5Data.remark_internal) $('#remark_internal').val(step5Data.remark_internal);
                    if (step5Data.remark_external) $('#remark_external').val(step5Data.remark_external);
                    
                    // If we're already on step 5 (unlikely but possible during initialization), restore it
                    if (currentStep === 5) {
                        restoreStep5Data();
                    }

                } else {
                    console.error('Failed to load contract data:', response);
                    alert('Failed to load contract data');
                }
            },
            error: function(xhr) {
                console.error('Error loading contract data:', xhr);
                alert('Error: ' + (xhr.responseJSON?.message || 'Failed to load contract data'));
                $('#existing_contract_id').val('').trigger('change');
            }
        });
    }

    // Survey selection change with customer filtering
    $('#survey_tags').on('change', function() {
        saveSurveySelections();
        updateNextButtonState();
    });

    // Apply customer filtering when dropdown opens
    $(document).on('select2:open', '#survey_tags', function() {
        const selectedValues = $(this).val() || [];
        if (selectedValues.length > 0) {
            const firstCustomer = $(this).find('option:selected').first().data('customer');
            $(this).find('option').each(function() {
                const optionCustomer = $(this).data('customer');
                const optionValue = $(this).val();
                const isSelected = selectedValues.includes(optionValue);
                
                if (optionValue && !isSelected) {
                    if (optionCustomer !== firstCustomer) {
                        $(this).remove(); // Remove from DOM
                    }
                }
            });
        }
    });

    // On unselect event - reset filtering
    $('#survey_tags').on('select2:unselect', function(e) {
        const remainingValues = $(this).val() || [];
        if (remainingValues.length === 0) {
            // If no surveys left, reload all options
            loadSurveys();
        }
    });

    // Room checkbox change
    $(document).on('change', '.room-checkbox', function() {
        updateRoomSelections(); // Update room selections when changed
        updateNextButtonState();
    });

    // Select All button click
    $(document).on('click', '.select-all-btn', function(e) {
        e.preventDefault();
        const surveyId = $(this).data('survey-id');
        console.log('Select All button clicked for survey:', surveyId);
        
        // Find all room checkboxes for this survey
        const roomCheckboxes = $(`.room-checkbox[data-survey="${surveyId}"]`);
        console.log('Found room checkboxes:', roomCheckboxes.length);
        
        if (roomCheckboxes.length > 0) {
            // Check all room checkboxes and trigger change event
            roomCheckboxes.prop('checked', true).trigger('change');
            
            // Update select all checkbox
            const selectAllCheckbox = $(`#selectAll_${surveyId}`);
            if (selectAllCheckbox.length > 0) {
                selectAllCheckbox.prop('checked', true);
            }
            
            // Rebuild aroma dropdowns
            rebuildAromaDropdowns(surveyId);
            updateRoomSelections();
            updateNextButtonState();
            
            console.log('All rooms selected for survey:', surveyId);
        } else {
            console.error('No room checkboxes found for survey:', surveyId);
            // Debug: log all room checkboxes
            const allRoomCheckboxes = $('.room-checkbox');
            console.log('Total room checkboxes found:', allRoomCheckboxes.length);
            allRoomCheckboxes.each(function() {
                console.log('Checkbox - data-survey:', $(this).data('survey'), 'value:', $(this).val());
            });
        }
    });

    // Form field changes
    $('input, select, textarea').on('change', function() {
        console.log('Field changed:', $(this).attr('id'), 'Value:', $(this).val());
        updateNextButtonState();
    });

    // Specific handler for term_of_payment
    $('#term_of_payment').on('change', function() {
        console.log('Term of Payment changed:', $(this).val());
        updateNextButtonState();
    });

    // Handler for payment method change
    $('#payment_method').on('change', function() {
        const paymentMethod = $(this).val();
        console.log('Payment Method changed:', paymentMethod);
        
        // Show notification based on payment method
        if (paymentMethod === 'Before Service') {
            Swal.fire({
                title: 'Before Service',
                text: 'Pembayaran dilakukan sebelum layanan dimulai. Pastikan customer telah melakukan pembayaran sebelum service dimulai.',
                icon: 'info',
                confirmButtonText: 'OK'
            });
        } else if (paymentMethod === 'After Service') {
            Swal.fire({
                title: 'After Service',
                text: 'Pembayaran dilakukan setelah layanan selesai. Service akan dimulai terlebih dahulu, kemudian pembayaran dilakukan setelah service selesai.',
                icon: 'info',
                confirmButtonText: 'OK'
            });
        }
        
        // Check rental period vs TOP compatibility
        checkRentalPeriodCompatibility();
        updateNextButtonState();
    });

    // Handler for rental period change
    $('#rental_period, #rental_unit, #term_of_payment').on('change', function() {
        checkRentalPeriodCompatibility();
        updateNextButtonState();
    });

    // Function to check rental period vs TOP compatibility
    function checkRentalPeriodCompatibility() {
        if (window.isPopulatingData) {
            console.log('Skipping compatibility check during data population');
            return;
        }
        
        const rentalPeriod = parseInt($('#rental_period').val()) || 0;
        const rentalUnit = $('#rental_unit').val();
        const termOfPayment = $('#term_of_payment').val();
        const paymentMethod = $('#payment_method').val();

        if (rentalPeriod > 0 && rentalUnit && termOfPayment) {
            console.log('=== RENTAL PERIOD COMPATIBILITY CHECK ===');
            console.log('Rental Period:', rentalPeriod, rentalUnit);
            console.log('Term of Payment:', termOfPayment);

            // Convert rental period to months
            let rentalMonths = 0;
            if (rentalUnit === 'hari') {
                // If less than 30 days, consider as 1 month
                rentalMonths = rentalPeriod < 30 ? 1 : Math.ceil(rentalPeriod / 30);
            } else if (rentalUnit === 'bulan') {
                rentalMonths = rentalPeriod;
            }
            
            // Extract months from TOP
            let topMonths = 0;
            if (termOfPayment.includes('bulan')) {
                topMonths = parseInt(termOfPayment.split(' ')[0]);
            } else if (termOfPayment === 'Tahunan') {
                console.log('Validation PASSED: Tahunan means 1x payment for the whole contract period');
                return;
            } else if (termOfPayment.includes('tahunan')) {
                topMonths = parseInt(termOfPayment.split(' ')[0]) * 12;
            }

            console.log('Rental Months:', rentalMonths);
            console.log('TOP Months:', topMonths);

            // VALIDATION: Rental period must be divisible by TOP
            if (topMonths > 0 && rentalMonths > 0) {
                const remainder = rentalMonths % topMonths;
                
                console.log('Modulo Result (remainder):', remainder);
                
                // If not divisible (remainder > 0), show error
                if (remainder !== 0) {
                    // Find valid TOP options that divide evenly
                    let validTOPs = [];
                    const possibleTOPs = [1, 2, 3, 4, 6, 12];
                    
                    for (let i = 0; i < possibleTOPs.length; i++) {
                        if (rentalMonths % possibleTOPs[i] === 0 && possibleTOPs[i] <= rentalMonths) {
                            if (possibleTOPs[i] === 1) {
                                validTOPs.push('1 bulan 1x');
                            } else if (possibleTOPs[i] === 2) {
                                validTOPs.push('2 bulan 1x');
                            } else if (possibleTOPs[i] === 3) {
                                validTOPs.push('3 bulan 1x');
                            } else if (possibleTOPs[i] === 4) {
                                validTOPs.push('4 bulan 1x');
                            } else if (possibleTOPs[i] === 6) {
                                validTOPs.push('6 bulan 1x');
                            } else if (possibleTOPs[i] === 12) {
                                validTOPs.push('Tahunan');
                            }
                        }
                    }
                    
                    // Also add rental period itself as valid option
                    if (rentalMonths <= 12 && !validTOPs.includes(`${rentalMonths} bulan 1x`)) {
                        validTOPs.push(`${rentalMonths} bulan 1x`);
                    }

                    console.log('Valid TOPs:', validTOPs);

                    Swal.fire({
                        title: 'Error: Term of Payment Tidak Valid',
                        html: `
                            <div class="text-start">
                                <p><strong>Periode Sewa:</strong> ${rentalPeriod} ${rentalUnit} (${rentalMonths} bulan)</p>
                                <p><strong>Term of Payment:</strong> ${termOfPayment} (${topMonths} bulan)</p>
                                <p class="text-danger"><strong>Masalah:</strong> Periode sewa harus habis dibagi dengan Term of Payment!</p>
                                <p><strong>Perhitungan:</strong> ${rentalMonths} bulan ÷ ${topMonths} bulan = ${(rentalMonths / topMonths).toFixed(2)} (tidak habis)</p>
                                <hr>
                                <p><strong>Pilihan Term of Payment yang Valid:</strong></p>
                                <ul class="text-start">
                                    ${validTOPs.map(top => `<li><strong>${top}</strong></li>`).join('')}
                                </ul>
                            </div>
                        `,
                        icon: 'error',
                        showCancelButton: true,
                        confirmButtonText: validTOPs.length > 0 ? 'Ubah ke ' + validTOPs[0] : 'OK',
                        cancelButtonText: 'Batal',
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#6c757d',
                        allowOutsideClick: false
                    }).then((result) => {
                        if (result.isConfirmed && validTOPs.length > 0) {
                            $('#term_of_payment').val(validTOPs[0]).trigger('change');
                        } else {
                            // Reset TOP to empty if user cancels
                            $('#term_of_payment').val('').trigger('change');
                        }
                    });
                } else {
                    console.log('✅ Validation PASSED: Rental period is divisible by TOP');
                }
            }
        }
    }

    // ===== UTILITY FUNCTIONS =====
    // Note: selectAllRooms and toggleAllRooms functions are no longer needed
    // as the functionality is now handled directly in event handlers above

    // ===== FINALIZE BUTTON =====
    $(document).on('click', '#finalizeBtn', async function(e) {
        e.preventDefault();
        
        console.log('=== FINALIZE BUTTON CLICKED ===');
        
        // Get selected survey ID for operational area validation
        const selectedSurveyIds = $('#survey_tags').val() || [];
        const surveyId = selectedSurveyIds[0];
        const isRenewalWithExistingRooms = $('#quotation_type').val() === 'renewal'
            && window.renewalContractData
            && Array.isArray(window.renewalContractData.rooms)
            && window.renewalContractData.rooms.length > 0;
        if (!surveyId && !isRenewalWithExistingRooms) {
            Swal.fire({
                title: 'Error',
                text: 'Pilih survey terlebih dahulu',
                icon: 'error',
                confirmButtonText: 'OK'
            });
            return;
        }

        if (!surveyId && isRenewalWithExistingRooms) {
            Swal.fire({
                title: 'Finalize Quotation?',
                text: 'This will submit the quotation for approval. You will not be able to edit it after submission.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, Finalize!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    submitQuotation('finalize');
                }
            });
            return;
        }
        
        // Show loading while checking operational area
        Swal.fire({
            title: 'Memvalidasi...',
            text: 'Mengecek operational area',
            icon: 'info',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        try {
            // Check if building's city is in operational area
            const response = await fetch(`/operational/api/check-operational-area-by-survey/${surveyId}`);
            const data = await response.json();
            
            console.log('Operational area check result:', data);
            
            if (!data.is_valid) {
                // City is NOT in operational area - block finalize
                Swal.fire({
                    title: 'Tidak Dapat Finalize',
                    html: `
                        <div class="text-start">
                            <p>${data.message}</p>
                            <p class="text-muted mb-0">
                                <strong>Building:</strong> ${data.building_name || 'N/A'}<br>
                                <strong>City:</strong> ${data.city_name || 'Unknown'}
                            </p>
                        </div>
                    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-map-marker-alt"></i> Ke Operational Areas',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#6c757d'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.open(data.branches_url, '_blank');
                    }
                });
                return;
            }
            
            // City IS in operational area - proceed with finalize confirmation
            Swal.fire({
                title: 'Finalize Quotation?',
                text: 'This will submit the quotation for approval. You will not be able to edit it after submission.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, Finalize!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    submitQuotation('finalize');
                }
            });
            
        } catch (error) {
            console.error('Error checking operational area:', error);
            Swal.fire({
                title: 'Error',
                text: 'Gagal mengecek operational area: ' + error.message,
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
    });

    // ===== SUBMIT QUOTATION FUNCTION =====
    function submitQuotation(action = 'draft') {
        console.log('=== SUBMIT QUOTATION ===', action);
        
        // Update room selections before submitting to ensure latest data
        updateRoomSelections();
        console.log('Updated room selections before submit:', globalRoomSelections);
        
        // Collect all form data
        const form = $('#quotationWizardForm')[0];
        const formData = new FormData(form);

        // Handle edit mode: inject quotation_id
        const savedStep1DataForEdit = JSON.parse(localStorage.getItem('quotation_step1_data') || '{}');
        if (savedStep1DataForEdit.quotation_id) {
            formData.append('quotation_id', savedStep1DataForEdit.quotation_id);
            console.log('Included quotation_id for update:', savedStep1DataForEdit.quotation_id);
        }
        
        // Add selected surveys as array
        const selectedSurveys = $('#survey_tags').val() || [];
        console.log('Selected surveys:', selectedSurveys);
        selectedSurveys.forEach((surveyId, index) => {
            formData.append(`survey_tags[${index}]`, surveyId);
        });
        
        // Add selected rooms as array format
        const selectedRooms = {};
        $('.room-checkbox:checked').each(function() {
            const surveyId = $(this).data('survey');
            const roomId = $(this).val();
            if (!selectedRooms[surveyId]) {
                selectedRooms[surveyId] = [];
            }
            selectedRooms[surveyId].push(roomId);
        });
        
        // Convert selectedRooms to form data format
        Object.keys(selectedRooms).forEach(surveyId => {
            selectedRooms[surveyId].forEach((roomId, index) => {
                formData.append(`selected_rooms[${surveyId}][${index}]`, roomId);
            });
        });
        
        // Add rental items as array format - support multiple items per survey
        const rentalItems = {};
        $('.rental-configuration').each(function() {
            const surveyId = $(this).data('survey-id');
            const roomId = $(this).find('select[name*="room_id"]').val() || $(this).find('input[name*="room_id"]').val();
            const productId = $(this).find('select[name*="product_id"]').val();
            const price = $(this).find('input[name*="price"]').val();
            const quantity = $(this).find('input[name*="quantity"]').val();
            const remark = $(this).find('input[name*="remark"]').val();
            
            // Get room name from display container or from room selection
            let roomName = '';
            const roomDisplayContainer = $(this).find('.room-display-container');
            if (roomDisplayContainer.length > 0) {
                roomName = roomDisplayContainer.find('input[type="text"]').val() || roomDisplayContainer.text().trim();
            }
            
            // If room name not found, try to get from globalRoomSelections
            if (!roomName && roomId) {
                const roomSelection = globalRoomSelections.find(r => r.room_id == roomId);
                if (roomSelection) {
                    roomName = roomSelection.room_name || '';
                }
            }
            
            // Get room specifications from room selection or survey detail
            let specifications = '';
            if (roomId && surveyId) {
                // Try to get from globalRoomSelections first (if available)
                const roomSelection = globalRoomSelections.find(r => r.room_id == roomId && r.survey_id == surveyId);
                if (roomSelection && roomSelection.specifications) {
                    specifications = typeof roomSelection.specifications === 'string' 
                        ? roomSelection.specifications 
                        : JSON.stringify(roomSelection.specifications);
                } else {
                    // Try to find room in survey details from Step 3
                    const roomCheckbox = $(`.room-checkbox[data-survey="${surveyId}"][value="${roomId}"]`);
                    if (roomCheckbox.length > 0) {
                        const roomData = roomCheckbox.data('room-data');
                        if (roomData && roomData.specifications) {
                            specifications = typeof roomData.specifications === 'string' 
                                ? roomData.specifications 
                                : JSON.stringify(roomData.specifications);
                        }
                    }
                }
            }
            
            if (surveyId && roomId && productId) {
                // Use unique ID from the form field names to support multiple items per survey
                const fieldName = $(this).find('select[name*="room_id"], input[name*="room_id"]').first().attr('name');
                const uniqueId = fieldName ? fieldName.match(/rental_items\[([^\]]+)\]/)[1] : `survey-${surveyId}-room-${roomId}-${Date.now()}`;
                
                const rentalAlias = $(this).find('input[name*="rental_alias"]').val() || '';
                
                rentalItems[uniqueId] = {
                    survey_id: surveyId,
                    room_id: roomId,
                    room_name: roomName,
                    product_id: productId,
                    price: price,
                    quantity: quantity,
                    remark: remark,
                    rental_alias: rentalAlias,
                    specifications: specifications
                };
            }
        });
        
        // Convert rentalItems to form data format
        Object.keys(rentalItems).forEach(uniqueId => {
            const item = rentalItems[uniqueId];
            formData.append(`rental_items[${uniqueId}][survey_id]`, item.survey_id);
            formData.append(`rental_items[${uniqueId}][room_id]`, item.room_id);
            if (item.room_name) {
                formData.append(`rental_items[${uniqueId}][room_name]`, item.room_name);
            }
            formData.append(`rental_items[${uniqueId}][product_id]`, item.product_id);
            formData.append(`rental_items[${uniqueId}][price]`, item.price);
            formData.append(`rental_items[${uniqueId}][quantity]`, item.quantity);
            formData.append(`rental_items[${uniqueId}][remark]`, item.remark || '');
            formData.append(`rental_items[${uniqueId}][rental_alias]`, item.rental_alias || '');
            if (item.specifications) {
                formData.append(`rental_items[${uniqueId}][specifications]`, item.specifications);
            }
        });
        
        // Add room selections with aroma data
        if (globalRoomSelections && globalRoomSelections.length > 0) {
            globalRoomSelections.forEach((room, index) => {
                formData.append(`room_selections_data[${index}][survey_id]`, room.survey_id);
                formData.append(`room_selections_data[${index}][room_id]`, room.room_id);
                formData.append(`room_selections_data[${index}][survey_detail_id]`, room.survey_detail_id || '');
                formData.append(`room_selections_data[${index}][master_room_id]`, room.master_room_id || '');
                formData.append(`room_selections_data[${index}][contract_room_id]`, room.contract_room_id || '');
                formData.append(`room_selections_data[${index}][room_name]`, room.room_name || '');
                formData.append(`room_selections_data[${index}][room_type]`, room.room_type || '');
                formData.append(`room_selections_data[${index}][aroma_product_id]`, room.aroma_product_id || '');
                formData.append(`room_selections_data[${index}][aroma_variant]`, room.aroma_variant || '');
            });
            console.log('Added aroma data to form:', globalRoomSelections);
        } else {
            console.warn('No globalRoomSelections found - aroma data will not be saved');
        }
        
        // Add action parameter
        formData.append('action', action);
        
        console.log('Form data being submitted:', Object.fromEntries(formData));
        console.log('Selected surveys array:', selectedSurveys);
        console.log('Selected rooms object:', selectedRooms);
        console.log('Rental items object:', rentalItems);
        
        // Submit form via AJAX
        $.ajax({
            url: form.getAttribute('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('Form submission success:', response);
                
                if (response.success) {
                    // Show success message based on action
                    const successTitle = action === 'finalize' ? 'Quotation Finalized!' : 'Quotation Saved!';
                    const successText = action === 'finalize' 
                        ? 'Quotation has been submitted for approval.' 
                        : response.message;
                    
                    // Clear localStorage after successful submission
                    clearQuotationWizardData();
                    
                    Swal.fire({
                        title: successTitle,
                        text: successText,
                        icon: 'success',
                        confirmButtonText: 'OK'
                        }).then(() => {
                            // Redirect to quotations index
                            if (response.redirect_url) {
                                window.location.href = response.redirect_url;
                            } else {
                                window.location.href = '{{ route("marketing.quotations.index") }}';
                            }
                        });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: response.message,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('Form submission error:', error);
                console.error('Response:', xhr.responseText);
                
                let errorMessage = 'An error occurred while saving the quotation.';
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                Swal.fire({
                    title: 'Error!',
                    text: errorMessage,
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });
    }

    // ===== FORM SUBMISSION =====
    $('#quotationWizardForm').on('submit', function(e) {
        e.preventDefault();
        submitQuotation('draft');
    });

   
    showStep(1);
    
    // Auto-save form data on input change
    $(document).on('change input', '#step-1 input, #step-1 select', function() {
        saveStep1Data();
    });
    
    $(document).on('change input', '#step-5 input, #step-5 textarea, #step-5 select', function() {
        saveStep5Data();
    });
    
    $(document).on('change', '#step-6 select', function() {
        saveStep6Data();
    });
    
    // Auto-save rental configurations when changed
    $(document).on('change input', '.rental-configuration input, .rental-configuration select', function() {
        saveStep4Data();
    });
});

// MOM6: Notes Sales Popup Functions (Global scope for onclick handlers)
function showNotesSalesPopup(notes) {
    // Set notes content
    document.getElementById('notesSalesContent').textContent = notes || 'No notes available';
    
    // Show modal with animation
    const modal = document.getElementById('notesSalesModal');
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeNotesSalesPopup() {
    const modal = document.getElementById('notesSalesModal');
    modal.classList.remove('show');
    document.body.style.overflow = '';
    
    // Also show success notification after closing notes
    Swal.fire({
        icon: 'success',
        title: 'Ready to Proceed',
        text: 'You can now continue with the renewal quotation',
        timer: 2000,
        showConfirmButton: false
    });
}
</script>

<!-- MOM6: Notes Sales Pop-up Modal for Renewal - BEAUTIFUL VERSION -->
<div id="notesSalesModal" class="notes-sales-overlay" onclick="closeNotesSalesPopup()">
    <div class="notes-sales-container" onclick="event.stopPropagation()">
        <!-- Decorative Top Wave -->
        <div class="notes-sales-wave">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 120">
                <path fill="url(#gradient)" d="M0,32L48,37.3C96,43,192,53,288,58.7C384,64,480,64,576,58.7C672,53,768,43,864,48C960,53,1056,75,1152,80C1248,85,1344,75,1392,69.3L1440,64L1440,0L1392,0C1344,0,1248,0,1152,0C1056,0,960,0,864,0C768,0,672,0,576,0C480,0,384,0,288,0C192,0,96,0,48,0L0,0Z"></path>
                <defs>
                    <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="0%">
                        <stop offset="0%" style="stop-color:#ef4444;stop-opacity:1" />
                        <stop offset="50%" style="stop-color:#f97316;stop-opacity:1" />
                        <stop offset="100%" style="stop-color:#fbbf24;stop-opacity:1" />
                    </linearGradient>
                </defs>
            </svg>
        </div>
        
        <!-- Header with Animated Icon -->
        <div class="notes-sales-header">
            <div class="header-icon-wrapper">
                <div class="pulse-ring"></div>
                <div class="pulse-ring delay-1"></div>
                <div class="header-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
            </div>
            <h2 class="notes-sales-title">Important Sales Notes</h2>
            <p class="notes-sales-subtitle">Please review before proceeding with renewal</p>
            <button class="notes-sales-close" onclick="closeNotesSalesPopup()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <!-- Body -->
        <div class="notes-sales-body">
            <!-- Alert Box -->
            <div class="notes-alert">
                <div class="alert-icon">
                    <i class="fas fa-lightbulb"></i>
                </div>
                <div class="alert-content">
                    <div class="alert-title">Context from Sales Team</div>
                    <div class="alert-text">This note was added by the sales/marketing team for this contract renewal. Please take these points into consideration.</div>
                </div>
            </div>
            
            <!-- Notes Content -->
            <div class="notes-sales-content-wrapper">
                <label class="notes-label">
                    <i class="fas fa-sticky-note me-2"></i>
                    <span>Sales Notes</span>
                    <span class="notes-badge">Important</span>
                </label>
                <div id="notesSalesContent" class="notes-sales-content">
                    <!-- Content will be populated dynamically -->
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="notes-sales-footer">
            <button type="button" class="btn-continue" onclick="closeNotesSalesPopup()">
                <span class="btn-icon">
                    <i class="fas fa-check-circle"></i>
                </span>
                <span class="btn-text">I Understand, Continue</span>
                <span class="btn-arrow">
                    <i class="fas fa-arrow-right"></i>
                </span>
            </button>
        </div>
    </div>
</div>

<style>
/* MOM6: Beautiful Notes Sales Pop-up Styles */
.notes-sales-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.8));
    backdrop-filter: blur(4px);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 99999;
    opacity: 0;
    transition: opacity 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.notes-sales-overlay.show {
    display: flex !important;
    opacity: 1;
}

.notes-sales-container {
    background: white;
    border-radius: 20px;
    max-width: 650px;
    width: 92%;
    max-height: 85vh;
    overflow: hidden;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
    transform: scale(0.8) translateY(30px);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
}

.notes-sales-overlay.show .notes-sales-container {
    transform: scale(1) translateY(0);
}

/* Decorative Wave */
.notes-sales-wave {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 120px;
    overflow: hidden;
}

.notes-sales-wave svg {
    width: 100%;
    height: 100%;
}

/* Header */
.notes-sales-header {
    position: relative;
    text-align: center;
    padding: 3rem 2rem 2rem;
    background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 50%, #fed7aa 100%);
}

/* Animated Icon */
.header-icon-wrapper {
    position: relative;
    width: 90px;
    height: 90px;
    margin: 0 auto 1.5rem;
}

.pulse-ring {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 90px;
    height: 90px;
    border: 3px solid #f97316;
    border-radius: 50%;
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

.pulse-ring.delay-1 {
    animation-delay: 0.5s;
}

@keyframes pulse {
    0%, 100% {
        transform: translate(-50%, -50%) scale(1);
        opacity: 1;
    }
    50% {
        transform: translate(-50%, -50%) scale(1.3);
        opacity: 0;
    }
}

.header-icon {
    position: relative;
    width: 70px;
    height: 70px;
    margin: 10px auto 0;
    background: linear-gradient(135deg, #ef4444, #f97316);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 2rem;
    box-shadow: 0 10px 25px rgba(239, 68, 68, 0.3);
    animation: bounce 2s ease-in-out infinite;
}

@keyframes bounce {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-10px);
    }
}

.notes-sales-title {
    font-size: 1.75rem;
    font-weight: 800;
    margin: 0 0 0.5rem;
    background: linear-gradient(135deg, #dc2626, #ea580c);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.notes-sales-subtitle {
    font-size: 0.95rem;
    color: #6b7280;
    margin: 0;
    font-weight: 500;
}

.notes-sales-close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: white;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #9ca3af;
    padding: 0;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.3s;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.notes-sales-close:hover {
    background: #fee2e2;
    color: #dc2626;
    transform: rotate(90deg);
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2);
}

/* Body */
.notes-sales-body {
    padding: 2rem;
    overflow-y: auto;
    max-height: calc(85vh - 260px);
}

.notes-sales-body::-webkit-scrollbar {
    width: 8px;
}

.notes-sales-body::-webkit-scrollbar-track {
    background: #f3f4f6;
    border-radius: 10px;
}

.notes-sales-body::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #ef4444, #f97316);
    border-radius: 10px;
}

/* Alert Box */
.notes-alert {
    display: flex;
    gap: 1rem;
    padding: 1.25rem;
    background: linear-gradient(135deg, #fef3c7 0%, #fed7aa 100%);
    border-left: 4px solid #f59e0b;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 6px rgba(245, 158, 11, 0.1);
}

.alert-icon {
    flex-shrink: 0;
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    box-shadow: 0 4px 10px rgba(245, 158, 11, 0.3);
}

.alert-content {
    flex: 1;
}

.alert-title {
    font-weight: 700;
    font-size: 1rem;
    color: #92400e;
    margin-bottom: 0.25rem;
}

.alert-text {
    font-size: 0.875rem;
    color: #78350f;
    line-height: 1.5;
}

/* Notes Content */
.notes-sales-content-wrapper {
    background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05);
}

.notes-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 700;
    font-size: 1rem;
    color: #374151;
    margin-bottom: 1rem;
}

.notes-badge {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    margin-left: auto;
    box-shadow: 0 2px 4px rgba(220, 38, 38, 0.2);
}

.notes-sales-content {
    font-size: 1rem;
    line-height: 1.8;
    color: #1f2937;
    white-space: pre-wrap;
    word-wrap: break-word;
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    border: 1px solid #d1d5db;
    min-height: 120px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

/* Footer */
.notes-sales-footer {
    display: flex;
    justify-content: center;
    padding: 1.5rem 2rem 2rem;
    background: linear-gradient(to top, #f9fafb, white);
}

.btn-continue {
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    border: none;
    padding: 0.875rem 2rem;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.btn-continue::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.3);
    transform: translate(-50%, -50%);
    transition: width 0.6s, height 0.6s;
}

.btn-continue:hover::before {
    width: 300px;
    height: 300px;
}

.btn-continue:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
}

.btn-continue:active {
    transform: translateY(0);
}

.btn-icon, .btn-text, .btn-arrow {
    position: relative;
    z-index: 1;
}

.btn-arrow {
    transition: transform 0.3s;
}

.btn-continue:hover .btn-arrow {
    transform: translateX(4px);
}

/* Responsive */
@media (max-width: 640px) {
    .notes-sales-container {
        width: 95%;
        max-height: 90vh;
    }
    
    .notes-sales-header {
        padding: 2rem 1.5rem 1.5rem;
    }
    
    .notes-sales-title {
        font-size: 1.5rem;
    }
    
    .notes-sales-body {
        padding: 1.5rem;
    }
    
    .header-icon-wrapper {
        width: 70px;
        height: 70px;
    }
    
    .header-icon {
        width: 55px;
        height: 55px;
        font-size: 1.5rem;
    }
    
    .pulse-ring {
        width: 70px;
        height: 70px;
    }
}
</style>

@endsection

@section('scripts')
<script>
    // Global flags for data population
    window.isPopulatingData = false;
    
    $(document).ready(function() {
        console.log('!!! DOCUMENT READY STARTED !!!');
        
        // Start loading aroma products early to prevent race condition in Step 3
        if (typeof loadAromaProducts === 'function') {
            loadAromaProducts();
        }

        @if(isset($quotation))
            const existingQuotation = @json($quotation);
            initializeFromExistingQuotation(existingQuotation);
        @endif
    });
</script>
@endsection

