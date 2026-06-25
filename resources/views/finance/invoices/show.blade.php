@extends('layouts.app')

@section('title', 'Invoice Detail')
@section('breadcrumb', 'Home / Finance / Invoice / Detail')

@section('content')
<!-- Temporary Bootstrap Fix: Project is Tailwind, but this view is Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    /* Contract-style Layout Modifications */
    .invoice-layout-fix {
        display: flex !important;
        flex-wrap: wrap !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .invoice-card {
        height: 100% !important;
        border: 1px solid #dee2e6 !important;
        border-radius: 8px !important;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
        background: #fff;
    }
    .invoice-card-header {
        background-color: #6c757d !important;
        color: white !important;
        padding: 1rem 1.5rem !important;
        border-bottom: 1px solid rgba(0, 0, 0, 0.125) !important;
        border-radius: 8px 8px 0 0 !important;
    }
    .invoice-card-body {
        padding: 1.5rem !important;
    }
    .invoice-field {
        margin-bottom: 1rem !important;
        display: flex !important;
        align-items: center !important;
        border-bottom: 1px dashed #eee; /* Added for better readability in invoice */
        padding-bottom: 0.5rem;
    }
    .invoice-field:last-child {
        border-bottom: none;
        margin-bottom: 0 !important;
    }
    .invoice-field-label {
        flex: 0 0 40% !important;
        font-weight: bold !important;
        color: #495057 !important;
        font-size: 0.9rem;
    }
    .invoice-field-value {
        flex: 0 0 60% !important;
        color: #6c757d !important;
        font-weight: 500;
    }

    /* Tab Navigation - Matching Contract Detail */
    .nav-tabs {
        border-bottom: 2px solid #1e3a8a !important;
        margin: 0 !important;
        display: flex !important;
        flex-direction: row !important;
    }
    .nav-tabs .nav-item {
        margin-bottom: -2px !important;
        flex: 1 !important; /* Make tabs distribute evenly */
    }
    .nav-tabs .nav-link {
        border: none !important;
        border-radius: 0 !important;
        transition: all 0.3s ease !important;
        padding: 12px 20px !important;
        width: 100% !important; /* Full width of flex item */
        text-align: center !important;
        color: #64748b;
        font-weight: 600;
    }
    .nav-tabs .nav-link:hover {
        background-color: #f8f9fa !important;
        color: #1e3a8a !important;
    }
    .nav-tabs .nav-link.active {
        background-color: white !important;
        border-bottom: 3px solid #1e3a8a !important;
        color: #1e3a8a !important;
        font-weight: bold !important;
    }

    /* Table Styles - Matching Contract Detail (Light Headers) */
    .table thead th {
        background-color: #f8f9fa !important;
        color: #495057 !important;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.05em;
        border-bottom: 2px solid #dee2e6 !important;
        border-top: none !important;
        padding: 12px 15px;
        white-space: nowrap;
    }
    .table tbody td {
        padding: 12px 15px;
        vertical-align: middle;
        color: #495057;
    }
    .table-hover tbody tr:hover {
        background-color: rgba(0,0,0,.02);
    }
        border-radius: 0 !important;
        padding: 12px 20px !important;
        color: #64748b !important;
        font-weight: 500 !important;
        transition: all 0.2s ease !important;
    }
    
    .nav-tabs .nav-link:hover {
        background-color: #f8f9fa !important;
        color: #1e3a8a !important;
    }
    
    .nav-tabs .nav-link.active {
        color: #1e3a8a !important;
        font-weight: 700 !important;
        border-bottom: 3px solid #1e3a8a !important;
        background-color: transparent !important;
    }

    /* Grid Layout for Basic Info */
    .info-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.5rem;
    }

    .info-item {
        margin-bottom: 1rem;
    }

    .info-label {
        font-size: 0.75rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.025em;
        margin-bottom: 0.25rem;
    }

    .info-value {
        font-size: 0.95rem;
        color: #1e293b;
        font-weight: 500;
    }

    /* Responsive adjustments */
    @media (max-width: 991.98px) {
        .info-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (max-width: 575.98px) {
        .info-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Table consistency */
    .table thead th {
        background-color: #f8fafc;
        color: #475569;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
        border-bottom: 2px solid #e2e8f0;
    }
</style>

@php
    $invoiceTaxRule = $invoice->tax_code
        ? \App\Models\FinanceTaxCode::where('code', $invoice->tax_code)->first()
        : null;
    $showDiscountSummary = (float) $invoice->discount_amount > 0;
    $showTaxSummary = (float) $invoice->tax_amount > 0 || ($invoiceTaxRule && $invoiceTaxRule->hasZeroTaxPrint());
    $taxSummaryLabel = $showTaxSummary
        ? ($invoiceTaxRule && $invoiceTaxRule->hasZeroTaxPrint() ? 'PPN (0%)' : 'PPN')
        : null;
@endphp

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="card mb-3" style="background-color: #1e3a8a; color: white; border: none; border-radius: 10px;">
                <div class="card-header" style="background-color: #1e3a8a; border: none; padding: 1rem 1.5rem;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <a href="{{ route('finance.invoices.index') }}" class="btn btn-light btn-sm">
                                <i class="fas fa-arrow-left"></i> Back to List
                            </a>
                        </div>
                        <div>
                            <h3 class="card-title mb-0" style="color: white; font-size: 1.5rem; font-weight: bold;">
                                {{ $invoice->invoice_number }} - {{ $invoice->customer->name ?? 'N/A' }}
                            </h3>
                            <div class="text-center mt-1">
                                <span class="badge bg-light text-dark">{{ $invoice->status_text }}</span>
                            </div>
                        </div>
                        <div>
                            <div class="d-flex gap-2">
                                <button type="button" id="btnHeaderSave" class="btn btn-info btn-sm text-white"><i class="fas fa-save me-1"></i> SAVE</button>
                                <button type="button" id="btnHeaderReceipt" class="btn btn-info btn-sm text-white"><i class="fas fa-receipt me-1"></i> T. TERIMA</button>
                                @if($invoice->faktur_pajak && $invoice->faktur_pajak_status !== 'cancelled')
                                    <button type="button" id="btnCancelFaktur" class="btn btn-warning btn-sm"><i class="fas fa-times-circle me-1"></i> CANCEL FP</button>
                                @endif
                                <button type="button" id="btnCancelInvoice" class="btn btn-danger btn-sm" {{ $invoice->canCancel() ? '' : 'disabled' }} title="{{ $invoice->canCancel() ? '' : 'Cancel Faktur Pajak first' }}">
                                    <i class="fas fa-ban me-1"></i> CANCEL
                                </button>
                                @if(($regenerationContext['can_regenerate'] ?? false) && $invoice->invoice_status === 'cancelled')
                                    <button type="button" id="btnRegenerateInvoice" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-rotate-right me-1"></i> REGENERATE
                                    </button>
                                @endif
                                <button type="button" id="btnHeaderPrint" class="btn btn-primary btn-sm"><i class="fas fa-print me-1"></i> PRINT</button>
                                @if($invoice->invoice_status === 'draft')
                                    <button type="button" id="btnApprove" class="btn btn-success btn-sm"><i class="fas fa-check-circle me-1"></i> APPROVE</button>
                                @endif
                                @if($invoice->invoice_status === 'approved')
                                    <button type="button" id="btnTaxApprove" class="btn btn-warning btn-sm text-white"><i class="fas fa-stamp me-1"></i> TAX APPROVE</button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Tabs -->
            <div class="card mb-3">
                <div class="card-body p-0">
                    <ul class="nav nav-tabs" id="invoiceTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="basic-info-tab" data-bs-toggle="tab" data-bs-target="#basic-info" type="button" role="tab">
                                <i class="fas fa-info-circle me-2"></i>BASIC INFO
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="detail-tab" data-bs-toggle="tab" data-bs-target="#detail" type="button" role="tab">
                                <i class="fas fa-list me-2"></i>DETAIL
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="rental-tab" data-bs-toggle="tab" data-bs-target="#rental" type="button" role="tab">
                                <i class="fas fa-building me-2"></i>RENTAL
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="files-tab" data-bs-toggle="tab" data-bs-target="#files" type="button" role="tab">
                                <i class="fas fa-file me-2"></i>FILE(S)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="bank-receipt-tab" data-bs-toggle="tab" data-bs-target="#bank-receipt" type="button" role="tab">
                                <i class="fas fa-receipt me-2"></i>RECEIPT
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="activities-tab" data-bs-toggle="tab" data-bs-target="#activities" type="button" role="tab">
                                <i class="fas fa-history me-2"></i>ACTIVITIES
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="other-info-tab" data-bs-toggle="tab" data-bs-target="#other-info" type="button" role="tab">
                                <i class="fas fa-ellipsis-h me-2"></i>OTHER INFO
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
            <div class="tab-content" id="invoiceTabsContent">
                <div class="tab-pane fade show active" id="basic-info" role="tabpanel">
                    <div class="row invoice-layout-fix">
                        <!-- Invoice Information Section -->
                        <div class="col-lg-6 mb-4">
                            <div class="card invoice-card">
                                <div class="invoice-card-header">
                                    <h5 class="card-title mb-0">
                                        Invoice Information
                                    </h5>
                                </div>
                                <div class="invoice-card-body">
                                    <div class="invoice-field">
                                        <div class="invoice-field-label">Invoice Number</div>
                                        <div class="invoice-field-value copyable text-primary fw-bold" title="Click to copy">{{ $invoice->invoice_number }}</div>
                                    </div>

                                    <div class="invoice-field">
                                        <div class="invoice-field-label">Contract Number</div>
                                        <div class="invoice-field-value">
                                            @if($invoice->contract)
                                                <a href="{{ route('marketing.contracts.show', $invoice->contract) }}" target="_blank" rel="noopener noreferrer">{{ $invoice->contract_number ?? $invoice->contract->contract_number ?? '-' }}</a>
                                            @else
                                                {{ $invoice->contract_number ?? '-' }}
                                            @endif
                                        </div>
                                    </div>
                                    <div class="invoice-field">
                                        <div class="invoice-field-label">Invoice Period</div>
                                        <div class="invoice-field-value">{{ $invoice->period_invoice ?? '-' }}</div>
                                    </div>
                                    <div class="invoice-field">
                                        <div class="invoice-field-label">Invoice Date</div>
                                        <div class="invoice-field-value">
                                            @if($invoice->invoice_status === 'draft')
                                                <div class="row g-2 align-items-center">
                                                    <div class="col-auto">
                                                        <select id="invoiceDatePreference" class="form-select form-select-sm" style="min-width: 250px;">
                                                            <option value="" disabled selected>Select Date Rule</option>
                                                            
                                                            @if(isset($dateOptions['first_service']))
                                                                <option value="first_service" data-date="{{ $dateOptions['first_service']['date'] }}" {{ ($invoice->contract->invoice_date_preference ?? '') === 'first_service' ? 'selected' : '' }}>
                                                                    {{ $dateOptions['first_service']['label'] }}
                                                                </option>
                                                            @endif

                                                            @if(isset($dateOptions['contract_date']))
                                                                <option value="contract_date" data-date="{{ $dateOptions['contract_date']['date'] }}" {{ ($invoice->contract->invoice_date_preference ?? '') === 'contract_date' ? 'selected' : '' }}>
                                                                    {{ $dateOptions['contract_date']['label'] }}
                                                                </option>
                                                            @endif

                                                            @if(isset($dateOptions['end_of_month']))
                                                                <option value="end_of_month" data-date="{{ $dateOptions['end_of_month']['date'] }}" {{ ($invoice->contract->invoice_date_preference ?? '') === 'end_of_month' ? 'selected' : '' }}>
                                                                    {{ $dateOptions['end_of_month']['label'] }}
                                                                </option>
                                                            @endif

                                                            <option value="manual" {{ ($invoice->contract->invoice_date_preference ?? 'manual') === 'manual' ? 'selected' : '' }}>Manual Input</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-auto {{ ($invoice->contract->invoice_date_preference ?? 'manual') !== 'manual' ? 'd-none' : '' }}" id="manualDateInputContainer">
                                                        <input type="date" id="invoiceDateInput" class="form-control form-control-sm" value="{{ $invoice->invoice_date ? $invoice->invoice_date->format('Y-m-d') : '' }}">
                                                    </div>
                                                    <div class="col-auto">
                                                        <button class="btn btn-sm btn-primary" type="button" id="btnUpdateDate" title="Update Date & Preference">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="fw-bold text-dark">{{ $invoice->formatted_invoice_date }}</span>
                                            @endif
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <!-- Customer Information Section -->
                        <div class="col-lg-6 mb-4">
                            <div class="card invoice-card">
                                <div class="invoice-card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">
                                        Customer Information
                                    </h5>
                                    @if($invoice->invoice_status === 'draft' && empty($invoice->faktur_pajak))
                                        <button type="button" id="btnReloadTax" class="btn btn-sm btn-info" title="Reload Tax Data from Customer Master">
                                            <i class="fas fa-sync-alt me-1"></i> Reload Tax
                                        </button>
                                    @endif
                                </div>
                                <div class="invoice-card-body">
                                    <div class="invoice-field">
                                        <div class="invoice-field-label">Customer Name</div>
                                        <div class="invoice-field-value fw-bold">
                                            @if($invoice->customer)
                                                <a href="{{ route('company.customers.show', $invoice->customer) }}" target="_blank" rel="noopener noreferrer">{{ $invoice->customer->name }}</a>
                                            @else
                                                -
                                            @endif
                                        </div>
                                    </div>
                                    <div class="invoice-field">
                                        <div class="invoice-field-label">Billing Group</div>
                                        <div class="invoice-field-value text-primary fw-bold">
                                            {{ $invoice->billingGroup->billing_group_name ?? $invoice->contract->billingGroup->billing_group_name ?? '-' }}
                                        </div>
                                    </div>
                                    <div class="invoice-field">
                                        <div class="invoice-field-label">Billing Address</div>
                                        <div class="invoice-field-value text-secondary" style="line-height: 1.4;">{{ $invoice->billing_address ?? '-' }}</div>
                                    </div>
                                    @php
                                        $taxSetting = $invoice->customer->customerTaxSettings->first() ?? $invoice->customer->taxSetting;
                                    @endphp
                                    <div class="invoice-field">
                                        <div class="invoice-field-label">NPWP</div>
                                        <div class="invoice-field-value">{{ $invoice->npwp_number ?? $taxSetting->tax_number ?? '-' }}</div>
                                    </div>
                                    <div class="invoice-field">
                                        <div class="invoice-field-label">Tax Address</div>
                                        <div class="invoice-field-value">{{ $invoice->tax_address ?? $taxSetting->tax_address ?? '-' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Financial Summary Section -->
                        <div class="col-lg-6 mb-4">
                            <div class="card invoice-card">
                                <div class="invoice-card-header">
                                    <h5 class="card-title mb-0">
                                        Financial Summary
                                    </h5>
                                </div>
                                <div class="invoice-card-body">
                                    <div class="invoice-field">
                                        <div class="invoice-field-label">Subtotal</div>
                                        <div class="invoice-field-value" id="basic-subtotal">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</div>
                                    </div>
                                    <div class="invoice-field" id="basic-tax-row" style="{{ $showTaxSummary ? '' : 'display:none;' }}">
                                        <div class="invoice-field-label" id="basic-tax-label">{{ $taxSummaryLabel }}</div>
                                        <div class="invoice-field-value" id="basic-tax">Rp {{ number_format($invoice->tax_amount, 0, ',', '.') }}</div>
                                    </div>
                                    <div class="invoice-field" id="basic-discount-row" style="{{ $showDiscountSummary ? '' : 'display:none;' }}">
                                        <div class="invoice-field-label">Discount</div>
                                        <div class="invoice-field-value text-danger" id="basic-discount">Rp {{ number_format($invoice->discount_amount, 0, ',', '.') }}</div>
                                    </div>
                                    <div class="invoice-field">
                                        <div class="invoice-field-label">Total Amount</div>
                                        <div class="invoice-field-value text-primary fw-bold fs-5" id="basic-grand-total">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</div>
                                    </div>
                                    <div class="invoice-field">
                                        <div class="invoice-field-label">Paid Amount</div>
                                        <div class="invoice-field-value text-success">Rp {{ number_format($invoice->paid_amount, 0, ',', '.') }}</div>
                                    </div>
                                    <div class="invoice-field">
                                        <div class="invoice-field-label">Outstanding</div>
                                        @php
                                            $outstanding = $invoice->outstanding > 0 ? $invoice->outstanding : ($invoice->total_amount - $invoice->total_paid);
                                            // Ensure no negative outstanding if paid > total (shouldn't happen but safe to handle)
                                            $outstanding = max(0, $outstanding);
                                        @endphp
                                        <div class="invoice-field-value text-danger fw-bold" id="basic-outstanding">Rp {{ number_format($outstanding, 0, ',', '.') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Status & Tax Info Section -->
                        <div class="col-lg-6 mb-4">
                            <div class="card invoice-card">
                                <div class="invoice-card-header">
                                    <h5 class="card-title mb-0">
                                        Status & Tax Info
                                    </h5>
                                </div>
                                <div class="invoice-card-body">
                                    <div class="invoice-field">
                                        <div class="invoice-field-label">Tax Exported?</div>
                                        <div class="invoice-field-value">
                                            @if($invoice->is_tax_exported)
                                                <span class="badge bg-primary rounded-pill px-3">YES</span>
                                            @else
                                                <span class="badge bg-light text-dark border rounded-pill px-3">NO</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="invoice-field">
                                        <div class="invoice-field-label">Email Status</div>
                                        <div class="invoice-field-value">
                                            @if($invoice->is_emailed)
                                                <span class="badge bg-success rounded-pill px-3" title="Sent at {{ $invoice->emailed_at->format('d/M/Y H:i') }}">SENT</span>
                                            @else
                                                <span class="badge bg-warning text-dark rounded-pill px-3">NOT SENT</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="invoice-field">
                                        <div class="invoice-field-label">Tax Code</div>
                                        <div class="invoice-field-value">{{ $invoice->tax_code ?? '010' }}</div>
                                    </div>
                                    <div class="invoice-field">
                                        <div class="invoice-field-label">Faktur Pajak</div>
                                        <div class="invoice-field-value">
                                            {{ $invoice->faktur_pajak ?? 'Not Set' }}
                                            @if($invoice->faktur_pajak_status === 'cancelled')
                                                <span class="badge bg-danger ms-2">CANCELLED</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Notes Section (Full Width) -->
                        <div class="col-12 mb-4">
                            <div class="card invoice-card">
                                <div class="invoice-card-header">
                                    <h5 class="card-title mb-0">
                                        Additional Notes
                                    </h5>
                                </div>
                                <div class="invoice-card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="invoice-field border-0">
                                                <div class="invoice-field-label">Catatan Internal</div>
                                                @if($invoice->invoice_status === 'draft')
                                                    <textarea 
                                                        id="internal-notes-input"
                                                        class="form-control bg-light text-muted"
                                                        data-invoice-id="{{ $invoice->id }}"
                                                        style="min-height: 80px; font-style: italic;"
                                                        placeholder="Press Enter to save internal notes...">{{ $invoice->internal_notes }}</textarea>
                                                    <small class="text-muted">Press Enter to save</small>
                                                @else
                                                    <div class="invoice-field-value p-2 bg-light rounded text-muted fst-italic w-100" style="min-height: 60px;">{{ $invoice->internal_notes ?? 'No internal notes.' }}</div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="invoice-field border-0">
                                                <div class="invoice-field-label">Catatan Tambahan</div>
                                                @if($invoice->invoice_status === 'draft')
                                                    <textarea 
                                                        id="additional-notes-input"
                                                        class="form-control bg-light text-muted"
                                                        data-invoice-id="{{ $invoice->id }}"
                                                        style="min-height: 80px; font-style: italic;"
                                                        placeholder="Press Enter to save notes...">{{ $invoice->additional_notes }}</textarea>
                                                    <small class="text-muted">Press Enter to save</small>
                                                @else
                                                    <div class="invoice-field-value p-2 bg-light rounded text-muted fst-italic w-100" style="min-height: 60px;">{{ $invoice->additional_notes ?? 'No additional notes.' }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 2: DETAIL -->
                <div class="tab-pane fade" id="detail" role="tabpanel">
                    @php
                        $invoiceDetailRows = $invoice->invoiceDetails
                            ->map(function ($detail) use ($invoice) {
                                return [
                                    'reference_no' => trim((string) (\Illuminate\Support\Str::of($detail->description ?? '')->explode('|')->last() ?? '')) ?: ($invoice->contract_number ?? '-'),
                                    'item_name' => $detail->description ?? '-',
                                    'unit_price' => $detail->unit_price,
                                    'quantity' => $detail->quantity,
                                    'total_price' => $detail->total_price,
                                    'updated_at' => $detail->updated_at,
                                    'updated_by' => $detail->updater->name ?? $invoice->updater->name ?? '-',
                                ];
                            })
                            ->concat($invoice->invoiceRentalDetails->map(function ($rental) use ($invoice) {
                                $roomName = $rental->room_name ?: ($rental->jobSchedule->room->room_name ?? null);
                                $rentalName = $rental->rental_name ?? $rental->masterRental->rental_name ?? 'Rental';

                                return [
                                    'reference_no' => $rental->job_no ?? $invoice->contract_number ?? '-',
                                    'item_name' => $roomName ? "{$rentalName} - {$roomName}" : $rentalName,
                                    'unit_price' => $rental->unit_price,
                                    'quantity' => $rental->quantity,
                                    'total_price' => $rental->total_price,
                                    'updated_at' => $rental->updated_at,
                                    'updated_by' => $rental->updater->name ?? $invoice->updater->name ?? '-',
                                ];
                            }));
                    @endphp
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 40px;"><input type="checkbox"></th>
                                    <th>Invoice No</th>
                                    <th>Reference No</th>
                                    <th>Nama Item</th>
                                    <th class="text-end">Price</th>
                                    <th class="text-center">Quantity</th>
                                    <th class="text-end">Total</th>
                                    <th>Terakhir Update</th>
                                    <th>Oleh</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($invoiceDetailRows as $detail)
                                <tr>
                                    <td><input type="checkbox"></td>
                                    <td>{{ $invoice->invoice_number }}</td>
                                    <td>{{ $detail['reference_no'] }}</td>
                                    <td>{{ $detail['item_name'] }}</td>
                                    <td class="text-end">Rp {{ number_format($detail['unit_price'], 0, ',', '.') }}</td>
                                    <td class="text-center">{{ number_format($detail['quantity'], 0) }}</td>
                                    <td class="text-end fw-bold">Rp {{ number_format($detail['total_price'], 0, ',', '.') }}</td>
                                    <td>{{ $detail['updated_at']?->format('d/M/Y - H:i') ?? '-' }}</td>
                                    <td>{{ $detail['updated_by'] }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-muted">
                                        <i class="fas fa-info-circle me-2"></i>No data found in detail.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="6" class="text-end fw-bold">Subtotal</td>
                                    <td class="text-end fw-bold" id="detail-subtotal">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</td>
                                    <td colspan="2"></td>
                                </tr>
                                <tr id="detail-discount-row" style="{{ $invoice->invoice_status === 'draft' || $showDiscountSummary ? '' : 'display:none;' }}">
                                    <td colspan="6" class="text-end fw-bold text-danger" id="detail-discount-label">Discount</td>
                                    <td class="text-end fw-bold text-danger">
                                        @if($invoice->invoice_status === 'draft')
                                            <input type="number"
                                                   class="form-control form-control-sm text-end discount-amount-input"
                                                   data-invoice-id="{{ $invoice->id }}"
                                                   value="{{ $invoice->discount_amount }}"
                                                   step="1000"
                                                   min="0"
                                                   style="width: 150px; display: inline-block;">
                                        @else
                                            <span id="detail-discount">Rp {{ number_format($invoice->discount_amount, 0, ',', '.') }}</span>
                                        @endif
                                    </td>
                                    <td colspan="2"></td>
                                </tr>
                                <tr id="detail-tax-row" style="{{ $showTaxSummary ? '' : 'display:none;' }}">
                                    <td colspan="6" class="text-end fw-bold" id="detail-tax-label">{{ $taxSummaryLabel }}</td>
                                    <td class="text-end fw-bold" id="detail-tax">Rp {{ number_format($invoice->tax_amount, 0, ',', '.') }}</td>
                                    <td colspan="2"></td>
                                </tr>
                                <tr>
                                    <td colspan="6" class="text-end fw-bold text-primary fs-6">Grand Total</td>
                                    <td class="text-end fw-bold text-primary fs-6" id="detail-grand-total">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Tab 3: INVOICE RENTAL -->
                <div class="tab-pane fade" id="rental" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 40px;"><input type="checkbox"></th>
                                    <th>Job No</th>
                                    <th>Ruangan</th>
                                    <th>Rental</th>
                                    <th class="text-center">Quantity</th>
                                    <th class="text-center">Qty Free</th>
                                    <th class="text-end">Price</th>
                                    <th class="text-end">Total</th>
                                    <th>Terakhir Update</th>
                                    <th>Oleh</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($invoice->invoiceRentalDetails as $rental)
                                <tr>
                                    <td><input type="checkbox"></td>
                                    <td>{{ $rental->job_no ?? $invoice->contract_number ?? '-' }}</td>
                                    <td>{{ $rental->room_name ?: ($rental->jobSchedule->room->room_name ?? '-') }}</td>
                                    <td>{{ $rental->rental_name ?? $rental->masterRental->rental_name ?? '-' }}</td>
                                    <td class="text-center">{{ number_format($rental->quantity, 0) }}</td>
                                    <td class="text-center">{{ number_format($rental->qty_free ?? 0, 0) }}</td>
                                    <td class="text-end">
                                        Rp {{ number_format($rental->unit_price, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end rental-total-{{ $rental->id }}">Rp {{ number_format($rental->total_price, 0, ',', '.') }}</td>
                                    <td>{{ $rental->updated_at->format('d/M/Y - H:i') }}</td>
                                    <td>{{ $invoice->updater->name ?? '-' }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="10" class="text-center py-5 text-muted">
                                        <i class="fas fa-info-circle me-2"></i>No data found in rentals.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="files" role="tabpanel">
                    <form action="{{ route('finance.invoices.download-combined', $invoice->id) }}" method="POST" target="_blank">
                        @csrf
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="card-title mb-0">Files & Attachments</h6>
                                <small class="text-muted">Select files to include in the combined PDF download.</small>
                            </div>
                            <div>
                                <!-- <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 me-2">
                                    <i class="fas fa-file-pdf me-2"></i>Download Combined PDF
                                </button> -->
                                <button type="button" id="btnAddFile" class="btn btn-outline-secondary btn-sm rounded-pill px-4"><i class="fas fa-plus me-2"></i>ADD NEW</button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th style="width: 40px;"><input type="checkbox" id="checkAll"></th>
                                        <th>Nomor Invoice</th>
                                        <th>Jenis File</th>
                                        <th>File Name</th>
                                        <th>Action</th>
                                        <th>Description</th>
                                        <th>Terakhir Update</th>
                                        <th>Oleh</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($files as $file)
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="file_ids[]" value="{{ $file->id }}" class="file-checkbox form-check-input">
                                        </td>
                                        <td>{{ $file->invoice_number }}</td>
                                        <td>
                                            {!! $file->source_badge !!}
                                            <br><small class="text-muted">{{ $file->file_type_label }}</small>
                                        </td>
                                        <td>{{ $file->file_name }}</td>
                                        <td>
                                            <a href="{{ $file->file_url }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm px-3">
                                                <i class="fas fa-download me-2"></i>Download
                                            </a>
                                        </td>
                                        <td>{{ $file->description ?? '-' }}</td>
                                        <td>{{ $file->updated_at->format('d/M/Y - H:i') }}</td>
                                        <td>{{ $file->uploaded_by_name ?? '-' }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-5">
                                            <div class="bg-light p-4 rounded d-inline-block">No files found from Invoice, Contract, or related Jobs.</div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </form>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const checkAll = document.getElementById('checkAll');
                            if(checkAll) {
                                checkAll.addEventListener('change', function() {
                                    const checkboxes = document.querySelectorAll('.file-checkbox');
                                    checkboxes.forEach(cb => cb.checked = this.checked);
                                });
                            }
                        });
                    </script>
                </div>

                <!-- Tab 5: BANK RECEIPT -->
                <div class="tab-pane fade" id="bank-receipt" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Receipt Number</th>
                                    <th>Account Number</th>
                                    <th>Account Holder</th>
                                    <th>Payment Date</th>
                                    <th class="text-end">Amount</th>
                                    <th>Method</th>
                                    <th>Oleh</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($invoice->bankReceipts as $receipt)
                                <tr>
                                    <td class="fw-bold">{{ $receipt->receipt_number }}</td>
                                    <td>{{ $receipt->account_number ?? '-' }}</td>
                                    <td>{{ $receipt->account_holder_name ?? '-' }}</td>
                                    <td>{{ $receipt->payment_date ? $receipt->payment_date->format('d/M/Y') : '-' }}</td>
                                    <td class="text-end">Rp {{ number_format($receipt->amount, 0, ',', '.') }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ ucfirst($receipt->payment_method) }}</span></td>
                                    <td>{{ $receipt->creator->name ?? '-' }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">No receipts recorded yet.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab 6: ACTIVITIES -->
                <div class="tab-pane fade" id="activities" role="tabpanel">
                    <div class="timeline p-3">
                        @forelse($invoice->activity_timeline as $activity)
                        <div class="activity-item d-flex gap-3 mb-4 pb-4 border-bottom">
                            <div class="activity-icon">
                                <span class="bg-{{ $activity['color'] }} bg-opacity-10 text-{{ $activity['color'] }} p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                    <i class="{{ $activity['icon'] }} fa-lg"></i>
                                </span>
                            </div>
                            <div class="activity-content flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="fw-bold text-dark mb-1">{{ $activity['title'] }}</h6>
                                        <div class="text-muted small">
                                            <i class="fas fa-calendar me-1"></i> {{ $activity['occurred_at']->format('d/M/Y') }}
                                            <span class="mx-2">|</span>
                                            <i class="fas fa-user-circle me-1"></i> {{ $activity['performed_by'] }}
                                            <span class="mx-2">|</span>
                                            <i class="fas fa-clock me-1"></i> {{ $activity['occurred_at']->format('d/M/Y H:i') }}
                                        </div>
                                    </div>
                                    @if($activity['status'])
                                    <span class="badge bg-info">{{ ucfirst($activity['status']) }}</span>
                                    @endif
                                </div>
                                
                                @if($activity['notes'])
                                <div class="mt-2 p-3 bg-light rounded border-start border-primary border-4">
                                    <div class="small text-muted mb-1"><i class="fas fa-sticky-note me-1"></i> Catatan:</div>
                                    <div style="white-space: pre-wrap;">{{ $activity['notes'] }}</div>
                                </div>
                                @endif
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Belum ada aktivitas untuk invoice ini.</p>
                        </div>
                        @endforelse
                    </div>
                </div>

                <!-- Tab 7: OTHER INFO (Delivery) -->
                <!-- Tab 7: OTHER INFO (Delivery) -->
                <div class="tab-pane fade" id="other-info" role="tabpanel">
                    <form id="deliveryForm">
                        @csrf
                        <div class="row invoice-layout-fix">
                            <!-- Delivery Information -->
                            <div class="col-lg-6 mb-4">
                                <div class="card invoice-card">
                                    <div class="invoice-card-header">
                                        <h5 class="card-title mb-0">
                                            Delivery Information
                                        </h5>
                                    </div>
                                    <div class="invoice-card-body">
                                        <div class="invoice-field">
                                            <div class="invoice-field-label">Metode Pengiriman</div>
                                            <div class="invoice-field-value w-100">
                                                <select name="kirim" class="form-select form-select-sm border-0 bg-light">
                                                    <option value="soft_copy" {{ ($invoice->kirim ?? 'soft_copy') == 'soft_copy' ? 'selected' : '' }}>Soft Copy</option>
                                                    <option value="hard_copy" {{ ($invoice->kirim ?? '') == 'hard_copy' ? 'selected' : '' }}>Hard Copy</option>
                                                    <option value="both" {{ ($invoice->kirim ?? '') == 'both' ? 'selected' : '' }}>Both</option>
                                                    <option value="manual" {{ ($invoice->kirim ?? '') == 'manual' ? 'selected' : '' }}>Manual</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="invoice-field">
                                            <div class="invoice-field-label">Dikirim Oleh</div>
                                            <div class="invoice-field-value w-100">
                                                <input type="text" name="dikirim_oleh" class="form-control form-control-sm border-0 bg-light" value="{{ $invoice->dikirim_oleh }}" placeholder="Nama Pengirim">
                                            </div>
                                        </div>
                                        <div class="invoice-field">
                                            <div class="invoice-field-label">Dikirim Pada</div>
                                            <div class="invoice-field-value w-100">
                                                <input type="datetime-local" name="dikirim_pada" class="form-control form-control-sm border-0 bg-light" value="{{ $invoice->dikirim_pada ? $invoice->dikirim_pada->format('Y-m-d\TH:i') : '' }}">
                                            </div>
                                        </div>
                                        <div class="invoice-field">
                                            <div class="invoice-field-label">Diterima Oleh</div>
                                            <div class="invoice-field-value w-100">
                                                <input type="text" name="diterima_oleh" class="form-control form-control-sm border-0 bg-light" value="{{ $invoice->diterima_oleh }}" placeholder="Nama Penerima">
                                            </div>
                                        </div>
                                        <div class="invoice-field">
                                            <div class="invoice-field-label">Diterima Pada</div>
                                            <div class="invoice-field-value w-100">
                                                <input type="datetime-local" name="pada" class="form-control form-control-sm border-0 bg-light" value="{{ $invoice->pada ? $invoice->pada->format('Y-m-d\TH:i') : '' }}">
                                            </div>
                                        </div>
                                        <div class="invoice-field border-0 flex-column align-items-start">
                                            <div class="invoice-field-label mb-2 w-100">Catatan Pengiriman</div>
                                            <div class="invoice-field-value w-100">
                                                <textarea name="catatan_pengiriman" class="form-control border-0 bg-light" rows="3" placeholder="Tambahkan catatan pengiriman...">{{ $invoice->catatan_pengiriman }}</textarea>
                                            </div>
                                        </div>
                                        <div class="mt-3 text-end">
                                            <button type="button" id="btnSaveDelivery" class="btn btn-primary btn-sm">
                                                <i class="fas fa-save me-2"></i>SAVE INFO
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Audit Information -->
                            <div class="col-lg-6 mb-4">
                                <div class="card invoice-card">
                                    <div class="invoice-card-header">
                                        <h5 class="card-title mb-0">
                                            Audit Information
                                        </h5>
                                    </div>
                                    <div class="invoice-card-body">
                                        <div class="invoice-field">
                                            <div class="invoice-field-label">Created By</div>
                                            <div class="invoice-field-value">{{ $invoice->creator->name ?? '-' }}</div>
                                        </div>
                                        <div class="invoice-field">
                                            <div class="invoice-field-label">Created At</div>
                                            <div class="invoice-field-value">{{ $invoice->created_at->format('d/M/Y - H:i:s') }}</div>
                                        </div>
                                        <div class="invoice-field">
                                            <div class="invoice-field-label">Last Updated By</div>
                                            <div class="invoice-field-value">{{ $invoice->updater->name ?? '-' }}</div>
                                        </div>
                                        <div class="invoice-field">
                                            <div class="invoice-field-label">Last Updated At</div>
                                            <div class="invoice-field-value">{{ $invoice->updated_at->format('d/M/Y - H:i:s') }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Add New File Modal -->
<div class="modal fade" id="addFileModal" tabindex="-1" aria-labelledby="addFileModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addFileModalLabel">Add New File</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="uploadFileForm" enctype="multipart/form-data">
                <div class="modal-body">
                    @csrf
                    <!-- File Category Selection -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">File Category</label>
                        <div class="d-flex gap-3">
                            <div class="form-check card p-3 flex-fill text-center" style="cursor: pointer;" onclick="document.getElementById('cat_tax').checked = true">
                                <input class="form-check-input float-none mb-2" type="radio" name="file_category" id="cat_tax" value="tax_invoice">
                                <label class="form-check-label d-block text-primary fw-bold" for="cat_tax">
                                    Faktur Pajak
                                </label>
                                <small class="text-muted d-block" style="font-size: 0.75rem;">Triggers tax invoice features</small>
                            </div>
                            <div class="form-check card p-3 flex-fill text-center" style="cursor: pointer;" onclick="document.getElementById('cat_attach').checked = true">
                                <input class="form-check-input float-none mb-2" type="radio" name="file_category" id="cat_attach" value="attachment" checked>
                                <label class="form-check-label d-block text-secondary fw-bold" for="cat_attach">
                                    Supporting File
                                </label>
                                <small class="text-muted d-block" style="font-size: 0.75rem;">Standard attachment</small>
                            </div>
                        </div>
                    </div>

                    <!-- File Input -->
                    <div class="mb-3">
                        <label for="fileInput" class="form-label">Select File</label>
                        <input class="form-control" type="file" id="fileInput" name="file" required>
                        <div class="form-text">Allowed: PDF, Images, Word, Excel (Max 10MB)</div>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label for="fileDescription" class="form-label">Description (Optional)</label>
                        <textarea class="form-control" id="fileDescription" name="description" rows="2" placeholder="e.g., Bukti Potong, Memo, etc."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmitUpload">
                        <i class="fas fa-upload me-1"></i> Upload
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {

    // --- Save Functions Helpers ---

    function saveDeliveryInfo() {
        const btn = $('#btnSaveDelivery');
        const originalText = btn.html();
        
        // Provide feedback on the button itself if visible
        if(btn.is(':visible')) {
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>SAVING...');
        }
        
        // Return promise for chaining
        return $.ajax({
            url: "{{ route('finance.invoices.update-delivery', $invoice->id) }}",
            type: "POST",
            data: $('#deliveryForm').serialize(),
            success: function(response) {
                if (response.success) {
                    showNotification('success', response.message || 'Informasi pengiriman berhasil disimpan.');
                } else {
                    showNotification('error', response.message || 'Gagal menyimpan informasi pengiriman.');
                }
            },
            error: function(xhr) {
                let errorMsg = 'Terjadi kesalahan saat menyimpan informasi pengiriman.';
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    errorMsg = Object.values(errors).flat().join(', ');
                }
                showNotification('error', errorMsg);
            },
            complete: function() {
                if(btn.is(':visible')) {
                    btn.prop('disabled', false).html(originalText);
                }
            }
        });
    }

    function saveAdditionalNotes(inputSelector = '#additional-notes-input', silent = false) {
        const input = $(inputSelector);
        if(input.length === 0) return Promise.resolve(); // Skip if not present

        const invoiceId = input.data('invoice-id');
        const notes = input.val();
        
        return $.ajax({
            url: `/finance/invoices/${invoiceId}/update-notes`,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                additional_notes: notes
            },
            success: function(response) {
                if(!silent) showNotification('success', 'Catatan tambahan berhasil disimpan.');
            },
            error: function(xhr) {
                if(!silent) showNotification('error', 'Gagal menyimpan catatan tambahan.');
            }
        });
    }

    function saveInternalNotes(inputSelector = '#internal-notes-input', silent = false) {
        const input = $(inputSelector);
        if(input.length === 0) return Promise.resolve();

        const invoiceId = input.data('invoice-id');
        const internalNotes = input.val();
        
        return $.ajax({
            url: `/finance/invoices/${invoiceId}/update-internal-notes`,
            method: 'POST',
            data: {
                internal_notes: internalNotes,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if(!silent) showNotification('success', 'Catatan internal berhasil disimpan.');
            },
            error: function(xhr) {
                if(!silent) showNotification('error', 'Gagal menyimpan catatan internal.');
            }
        });
    }

    // Open Modal
    $('#btnAddFile').on('click', function() {
        $('#uploadFileForm')[0].reset();
        $('#addFileModal').modal('show');
    });

    // Handle Upload
    $('#uploadFileForm').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        var btn = $('#btnSubmitUpload');
        var originalText = btn.html();

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Mengunggah...');

        $.ajax({
            url: "{{ route('finance.invoices.upload', $invoice->id) }}",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                if(response.status === 'success') {
                    showNotification('success', response.message);
                    $('#addFileModal').modal('hide');
                    setTimeout(function() {
                        location.reload(); // Simple reload to show new file
                    }, 1000);
                } else {
                    showNotification('error', response.message);
                    btn.prop('disabled', false).html(originalText);
                }
            },
            error: function(xhr) {
                var errorMsg = 'Gagal mengunggah file.';
                if(xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    errorMsg = Object.values(xhr.responseJSON.errors).flat().join(', ');
                }
                showNotification('error', errorMsg);
                btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Ensure tabs work with query params if needed
    var url = window.location.href;
    if (url.indexOf("#") != -1) {
        var activeTab = url.substring(url.indexOf("#") + 1);
        $('.nav-tabs button[data-bs-target="#' + activeTab + '"]').tab('show');
    }

    // Update URL when tab changes
    $('.nav-tabs button').on('shown.bs.tab', function(e) {
        var id = $(this).attr('data-bs-target');
        window.history.replaceState(null, null, id);
    });

    // Handle Delivery Info Save
    $('#btnSaveDelivery').on('click', function() {
        saveDeliveryInfo();
    });

    // Handle Reload Tax Data button
    $('#btnReloadTax').on('click', function() {
        const btn = $(this);
        const originalHtml = btn.html();
        
        Swal.fire({
            title: 'Muat Ulang Data Pajak?',
            text: "NPWP dan alamat pajak akan diperbarui dari data master customer.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, muat ulang',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Memuat...');
                
                $.ajax({
                    url: "{{ route('finance.invoices.reload-tax', $invoice->id) }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        if (response.success) {
                            showNotification('success', response.message);
                            // Reload page to show updated data
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            showNotification('error', response.message);
                            btn.prop('disabled', false).html(originalHtml);
                        }
                    },
                    error: function(xhr) {
                        let errorMsg = 'Gagal memuat ulang data pajak.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        showNotification('error', errorMsg);
                        btn.prop('disabled', false).html(originalHtml);
                    }
                });
            }
        });
    });

    // Rule 43: Cancel Faktur Pajak
    $('#btnCancelFaktur').on('click', function() {
        Swal.fire({
            title: 'Batalkan Faktur Pajak?',
            text: "Faktur pajak akan ditandai sebagai dibatalkan.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, batalkan',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>MEMBATALKAN...');
                $.ajax({
                    url: "{{ route('finance.invoices.cancel-faktur', $invoice->id) }}",
                    type: "POST",
                    data: { _token: "{{ csrf_token() }}" },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Berhasil Dibatalkan', response.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Gagal', response.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Gagal', 'Terjadi kesalahan sistem.', 'error');
                    },
                    complete: function() {
                        $('#btnCancelFaktur').prop('disabled', false).html('<i class="fas fa-times-circle me-2"></i>CANCEL FAKTUR PAJAK');
                    }
                });
            }
        });
    });

    // Cancel Invoice with restriction check
    $('#btnCancelInvoice').on('click', function() {
        @if(!$invoice->canCancel())
            Swal.fire('Diblokir', 'Invoice tidak dapat dibatalkan. Batalkan faktur pajak terlebih dahulu.', 'warning');
            return;
        @endif

        Swal.fire({
            title: 'Batalkan Invoice?',
            text: "Anda yakin ingin membatalkan invoice ini?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, batalkan invoice',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                // ... same AJAX logic as existing cancel if any, or standard update
                $.ajax({
                    url: "{{ route('finance.invoices.cancel', $invoice->id) }}",
                    type: "POST",
                    data: { _token: "{{ csrf_token() }}" },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Berhasil Dibatalkan', 'Invoice berhasil dibatalkan.', 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Gagal', response.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        let msg = 'Invoice gagal dibatalkan.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        }
                        Swal.fire('Gagal', msg, 'error');
                    }
                });
            }
        });
    });

    $('#btnRegenerateInvoice').on('click', function() {
        Swal.fire({
            title: 'Regenerate invoice?',
            text: 'Invoice cancelled ini akan dibuatkan invoice baru berdasarkan data job/invoice terbaru.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, regenerate',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (!result.isConfirmed) {
                return;
            }

            const $button = $(this);
            const originalHtml = $button.html();
            $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> REGENERATING...');

            fetch("{{ route('finance.invoices.regenerate', $invoice->id) }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}",
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(async response => {
                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || `HTTP error! status: ${response.status}`);
                }
                return data;
            })
            .then(data => {
                Swal.fire('Berhasil', data.message || 'Invoice berhasil diregenerate.', 'success').then(() => {
                    window.location.href = "{{ route('finance.invoices.index') }}";
                });
            })
            .catch(error => {
                console.error('Error regenerating invoice:', error);
                Swal.fire('Gagal', error.message || 'Gagal regenerate invoice.', 'error');
            })
            .finally(() => {
                $button.prop('disabled', false).html(originalHtml);
            });
        });
    });

    // Update Send Email Button (assuming it has ID or class, usually btn-primary or similar)
    // For now, I'll add a specific ID if I can find it, or use a selector.
    // Based on usual patterns, let's assume it's handling via a common class or I'll add one.
    
    $('#btnSendEmail').on('click', function() {
        $.ajax({
            url: "{{ route('finance.invoices.send', $invoice->id) }}",
            type: "POST",
            data: { _token: "{{ csrf_token() }}" },
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire('Berhasil Dikirim', response.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Gagal', response.message, 'error');
                }
            },
            error: function(xhr) {
                let msg = 'Gagal mengirim email.';
                if (xhr.status === 422) {
                    msg = xhr.responseJSON.message;
                }
                Swal.fire('Validasi Gagal', msg, 'error');
            }
        });
    });

    // Approve Invoice
    $('#btnApprove').on('click', function() {
        Swal.fire({
            title: 'Setujui Invoice?',
            text: "Invoice akan ditandai sebagai APPROVED.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, approve',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Memproses...');
                $.ajax({
                    url: "{{ route('finance.invoices.approve', $invoice->id) }}",
                    type: "POST",
                    data: { _token: "{{ csrf_token() }}" },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Berhasil Di-Approve', response.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Gagal', response.message, 'error');
                            $('#btnApprove').prop('disabled', false).html('<i class="fas fa-check-circle me-1"></i> APPROVE');
                        }
                    },
                    error: function(xhr) {
                        let msg = 'Terjadi kesalahan sistem.';
                        if (xhr.status === 422) { msg = xhr.responseJSON.message; }
                        Swal.fire('Gagal', msg, 'error');
                        $('#btnApprove').prop('disabled', false).html('<i class="fas fa-check-circle me-1"></i> APPROVE');
                    }
                });
            }
        });
    });
    // Tax Approve Invoice
    $('#btnTaxApprove').on('click', function() {
        Swal.fire({
            title: 'Setujui Pajak Invoice?',
            text: "Invoice akan ditandai sebagai TAX APPROVED.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, tax approve',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Memproses...');
                $.ajax({
                    url: "{{ route('finance.invoices.tax-approve', $invoice->id) }}",
                    type: "POST",
                    data: { _token: "{{ csrf_token() }}" },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Berhasil Tax Approve', response.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Gagal', response.message, 'error');
                            $('#btnTaxApprove').prop('disabled', false).html('<i class="fas fa-stamp me-1"></i> TAX APPROVE');
                        }
                    },
                    error: function(xhr) {
                        let msg = 'Terjadi kesalahan sistem.';
                        if (xhr.status === 422) { msg = xhr.responseJSON.message; }
                        Swal.fire('Gagal', msg, 'error');
                        $('#btnTaxApprove').prop('disabled', false).html('<i class="fas fa-stamp me-1"></i> TAX APPROVE');
                    }
                });
            }
        });
    });

    // --- Header Buttons Handlers ---
    
    // Header SAVE -> Triggers Delivery Save AND Notes Save
    $('#btnHeaderSave').on('click', function() {
        // Trigger all save actions
        saveDeliveryInfo(); // This shows its own feedback on the Save Delivery button if visible
        saveInternalNotes('#internal-notes-input', true);
        saveAdditionalNotes('#additional-notes-input', true);
        
        showNotification('info', 'Sedang menyimpan perubahan...');
    });

    // Header PRINT -> Window Print
    // Header PRINT -> Open Print Template with Validation
    $('#btnHeaderPrint').on('click', function() {
        const status = "{{ $invoice->invoice_status }}";

        if (status === 'draft') {
            Swal.fire({
                title: 'Belum di-Approve',
                text: 'Mohon Approve Invoice terlebih dahulu sebelum mencetak.',
                icon: 'warning',
                confirmButtonColor: '#3085d6'
            });
            return;
        }

        // Use Combined Download logic but inline for printing
        window.open("{{ route('finance.invoices.download-combined', $invoice->id) }}?inline=true", '_blank');
    });

    // Header T. TERIMA -> Export Delivery Receipt
    $('#btnHeaderReceipt').on('click', function() {
        // Validate delivery info filled (Dikirim Oleh & Pada/Diterima Pada)
        // Kita cek field inputnya langsung atau value dari PHP jika view belum di-update via AJAX
        // Untuk amannya, kita cek value input field di tab Other Info karena user mungkin baru saja input
        const dikirimOleh = $('input[name="dikirim_oleh"]').val();
        const pada = $('input[name="pada"]').val();

        if (!dikirimOleh || !pada) {
            showNotification('warning', 'Mohon lengkapi "Dikirim Oleh" dan "Diterima Pada" di tab Other Info terlebih dahulu.');
            // Switch to Other Info tab
            $('.nav-tabs button[data-bs-target="#other-info"]').tab('show');
            return;
        }
        
        // Open PDF in new tab
        window.open("{{ route('finance.invoices.delivery-receipt', $invoice->id) }}", '_blank');
    });

    // --- Smart Invoice Date Logic (Re-implemented) ---
    const datePrefSelect = $('#invoiceDatePreference');
    const dateInput = $('#invoiceDateInput');
    const manualContainer = $('#manualDateInputContainer');
    const btnUpdateDate = $('#btnUpdateDate'); // Still used for Manual check triggering if needed
    
    if(datePrefSelect.length) {
        function toggleManualInput() {
            const selectedOpt = datePrefSelect.find('option:selected');
            const pref = datePrefSelect.val();
            
            if(pref === 'manual') {
                manualContainer.removeClass('d-none');
                dateInput.prop('readonly', false).focus();
                // Show update check button only for manual? 
                // User wants auto-save. For manual, we can hide button and auto-save on date change?
                // Let's keep button hidden for auto modes, show for manual.
                btnUpdateDate.removeClass('d-none');
            } else {
                manualContainer.addClass('d-none');
                btnUpdateDate.addClass('d-none'); // Hide check button for auto modes

                // Auto-fill date from data-date
                const dateVal = selectedOpt.data('date');
                if (dateVal) {
                    dateInput.val(dateVal);
                }
                dateInput.prop('readonly', true);
            }
        }

        // Function to save preference
        function saveDatePreference() {
            const preference = datePrefSelect.val();
            const manualDate = dateInput.val();
            const btn = btnUpdateDate;
            const originalHtml = btn.html();

            if (!preference) return;
            if (preference === 'manual' && !manualDate) return; // Don't auto-save if manual date missing

            // Visual feedback (maybe toast or small loading?)
            // If button is visible (manual), spin it
             if(preference === 'manual') {
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
             }

            $.ajax({
                url: "{{ route('finance.invoices.update-date-preference', $invoice->id) }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    preference: preference,
                    manual_date: manualDate
                },
                success: function(response) {
                    if(response.success) {
                         // Toast notification for auto-save
                         const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 2000,
                            timerProgressBar: true
                        });
                        Toast.fire({
                            icon: 'success',
                            title: 'Tanggal invoice berhasil diperbarui'
                        }).then(() => {
                           if(preference !== 'manual') location.reload(); // Reload to reflect changes if needed (e.g. Due Date)
                           else {
                               // For manual, we might want to reload too to ensure consistent state
                               location.reload();
                           }
                        });
                    } else {
                        Swal.fire('Gagal', response.message, 'error');
                    }
                },
                error: function(xhr) {
                    let msg = 'Gagal memperbarui data.';
                    if(xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    Swal.fire('Gagal', msg, 'error');
                },
                complete: function() {
                    if(preference === 'manual') {
                         btn.prop('disabled', false).html(originalHtml);
                    }
                }
            });
        }

        // Run on load
        toggleManualInput();

        // On Preference Change
        datePrefSelect.on('change', function() {
            toggleManualInput();
            
            const pref = $(this).val();
            // Auto-save immediately if NOT manual
            if(pref !== 'manual') {
                saveDatePreference();
            }
        });

        // On Date Change (for Manual) - Auto save? or use button?
        // User said: "harusnya dia auto save ketika di pilih bukan harus ceklis dulu"
        // Interpreting this for Manual: when date is picked.
        dateInput.on('change', function() {
            if(datePrefSelect.val() === 'manual') {
                saveDatePreference();
            }
        });

        // Keep button just in case user wants to force update manually
        btnUpdateDate.on('click', function() {
             saveDatePreference();
        });
    }

    // Auto-save rental price on change
    function syncInvoiceTotals(response) {
        $('#detail-subtotal').text('Rp ' + response.formatted_subtotal);
        $('#detail-tax').text('Rp ' + response.formatted_tax);
        $('#detail-grand-total').text('Rp ' + response.formatted_grand_total);

        $('#basic-subtotal').text('Rp ' + response.formatted_subtotal);
        $('#basic-discount').text('Rp ' + response.formatted_discount);
        $('#basic-tax').text('Rp ' + response.formatted_tax);
        $('#basic-grand-total').text('Rp ' + response.formatted_grand_total);
        $('#basic-outstanding').text('Rp ' + response.formatted_outstanding);

        $('#basic-discount-row').toggle(!!response.show_discount || $('.discount-amount-input').length > 0);
        $('#detail-discount-row').toggle(!!response.show_discount || $('.discount-amount-input').length > 0);
        $('#basic-tax-row').toggle(!!response.show_tax);
        $('#detail-tax-row').toggle(!!response.show_tax);

        if (response.tax_label) {
            $('#basic-tax-label').text(response.tax_label);
            $('#detail-tax-label').text(response.tax_label);
        }
    }

    $('.discount-amount-input').on('change', function() {
        const invoiceId = $(this).data('invoice-id');
        const discountAmount = $(this).val();
        const $input = $(this);

        $input.prop('disabled', true);

        $.ajax({
            url: `/finance/invoices/${invoiceId}/update-discount`,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                discount_amount: discountAmount
            },
            success: function(response) {
                syncInvoiceTotals(response);

                showNotification('success', 'Diskon berhasil diperbarui.');

                setTimeout(function() {
                    window.location.reload();
                }, 1000);
            },
            error: function(xhr) {
                const errorMsg = xhr.responseJSON?.message || 'Gagal memperbarui diskon.';
                showNotification('error', errorMsg);
                $input.prop('disabled', false);
            }
        });
    });
    
    // Auto-save additional notes on Enter or Blur
    $('#additional-notes-input').on('keypress', function(e) {
        if (e.which === 13 && !e.shiftKey) { // Enter without Shift
            e.preventDefault();
            $(this).blur(); // Trigger blur which triggers save
        }
    }).on('blur', function() {
         saveAdditionalNotes('#additional-notes-input');
    });

    // Auto-save Internal Notes on Enter or Blur
    $('#internal-notes-input').on('keypress', function(e) {
        if (e.which === 13 && !e.shiftKey) { // Enter key without Shift
            e.preventDefault();
            $(this).blur(); // Trigger blur which triggers save
        }
    }).on('blur', function() {
        saveInternalNotes('#internal-notes-input');
    });

});
</script>

<!-- Toastr JS -->
@endpush
