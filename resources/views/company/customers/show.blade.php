@extends('layouts.app')

@section('title', 'Customer Detail - ' . $customer->name)

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
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
        padding: 15px !important;
        width: 100% !important;
    }
    
    .card {
        width: 100% !important;
        margin-bottom: 1rem !important;
        border-radius: 8px !important;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
        border: 1px solid rgba(0, 0, 0, 0.125) !important;
    }
    
    .card-header {
        padding: 1rem 1.5rem !important;
        border-bottom: 1px solid rgba(0, 0, 0, 0.125) !important;
    }
    
    .card-body {
        padding: 1.5rem !important;
    }
    
    .bg-aruna {
        background-color: #1e3a8a !important;
    }

    /* Information Grid Styles (Warehouse Style) */
    .info-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 2rem;
    }

    .info-label {
        font-size: 0.75rem;
        font-weight: 600;
        color: #6c757d;
        text-transform: uppercase;
        margin-bottom: 0.5rem;
    }

    .info-value {
        font-size: 1rem;
        font-weight: 600;
        color: #212529;
    }

    .info-value-text {
        font-size: 1rem;
        color: #212529;
        font-weight: 500;
    }

    /* Modal Styles */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 1000;
        backdrop-filter: blur(4px);
    }

    .modal-overlay.show {
        display: flex;
    }

    .modal-container {
        background: white;
        border-radius: 12px;
        width: 90%;
        max-width: 900px;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        animation: modalSlideUp 0.3s ease-out;
    }

    @keyframes modalSlideUp {
        from { transform: translateY(20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }

    .modal-header {
        padding: 20px 24px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f9fafb;
        border-radius: 12px 12px 0 0;
    }

    .modal-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #111827;
    }

    .modal-body {
        padding: 24px;
        overflow-y: auto;
    }

    .modal-footer {
        padding: 16px 24px;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        background: #f9fafb;
        border-radius: 0 0 12px 12px;
    }

    .form-section {
        background: #ffffff;
        padding: 0;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        margin-bottom: 24px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }

    .form-section-body {
        padding: 28px 32px;
    }

    .form-label {
        margin-bottom: 0.75rem;
        display: block;
        color: #374151;
        font-weight: 600;
        font-size: 0.875rem;
    }

    .row.gy-4.gx-5 {
        --bs-gutter-y: 1.5rem;
        --bs-gutter-x: 2.5rem;
    }

    .section-title {
        font-size: 13px;
        font-weight: 700;
        color: #374151;
        margin-bottom: 0;
        padding: 14px 32px;
        background: #f8fafc;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        gap: 12px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .section-title i {
        font-size: 16px;
    }

    .btn-edit-premium {
        background-color: #fbbf24;
        color: #92400e;
        border: 1px solid #f59e0b;
        font-weight: 600;
        border-radius: 6px;
        padding: 8px 16px;
        transition: all 0.2s;
    }

    .btn-edit-premium:hover {
        background-color: #f59e0b;
        color: white;
    }

    @media (max-width: 768px) {
        .info-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="card mb-3" style="background-color: #1e3a8a; color: white; border: none; border-radius: 10px;">
                <div class="card-header" style="background-color: #1e3a8a; border: none; padding: 1rem 1.5rem;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <a href="{{ route('company.customers.index') }}" class="btn btn-light btn-sm">
                                <i class="fas fa-arrow-left"></i> Back to List
                            </a>
                        </div>
                        <div class="text-center">
                            <h3 class="card-title mb-0" style="color: white; font-size: 1.5rem; font-weight: bold;">
                                {{ $customer->name }}
                            </h3>
                            <div class="mt-1">
                                <span class="badge" style="background-color: {{ $customer->is_active ? '#059669' : '#dc2626' }}; color: white; padding: 0.35rem 0.75rem; font-size: 0.8rem;">
                                    {{ $customer->is_active ? 'Active' : 'Inactive' }}
                                </span>
                                <span class="ms-2" style="font-size: 0.9rem; opacity: 0.9;">{{ $customer->customer_code }}</span>
                            </div>
                        </div>
                        <div>
                            <button type="button" class="btn btn-edit-premium" onclick="openEditModal()">
                                <i class="fas fa-edit me-1"></i> Edit Customer
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Basic Info Section -->
            <div class="card mb-3">
                <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #1e3a8a;">
                    <h5 class="card-title mb-0" style="color: #1e3a8a;">
                        <i class="fas fa-info-circle me-2"></i>Basic Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div>
                            <div class="info-label">Customer Code</div>
                            <div class="info-value">{{ $customer->customer_code ?? '-' }}</div>
                        </div>
                        <div style="grid-column: span 2;">
                            <div class="info-label">Name</div>
                            <div class="info-value-text">{{ $customer->name ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="info-label">Badan Hukum</div>
                            <div class="info-value">{{ strtoupper($customer->company_type ?? '-') }}</div>
                        </div>
                        
                        <div>
                            <div class="info-label">Category</div>
                            <div class="info-value">{{ $customer->customerCategory->name ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="info-label">Classification</div>
                            <div class="info-value">{{ $customer->classification->option_name ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="info-label">Label / Alias</div>
                            <div class="info-value">{{ $customer->label_alias ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="info-label">Email</div>
                            <div class="info-value-text">{{ $customer->email ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="info-label">Phone</div>
                            <div class="info-value-text">{{ $customer->phone ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>



            <!-- Payment Information -->
            <div class="card mb-3">
                <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #d97706;">
                    <h5 class="card-title mb-0" style="color: #d97706;">
                        <i class="fas fa-file-invoice-dollar me-2"></i>Payment Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="info-grid mb-4">
                        <div>
                            <div class="info-label">Customer Group</div>
                            <div class="info-value">{{ $customer->customer_group ?? '-' }}</div>
                        </div>
                        <div style="grid-column: span 2;">
                            <div class="info-label">Default Bank Payment</div>
                            <div class="info-value-text">
                                @if($customer->defaultBankPayment)
                                    <strong>{{ $customer->defaultBankPayment->bank->name }}</strong><br>
                                    {{ $customer->defaultBankPayment->account_number }} ({{ $customer->defaultBankPayment->account_name }})
                                @else
                                    -
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <div class="info-grid mt-4">
                        <div style="grid-column: span 2;">
                            <div class="info-label">NIB (Nomor Induk Berusaha)</div>
                            <div class="info-value-text">{{ $customer->nib ?? '-' }}</div>
                        </div>
                    </div>

                    <div class="info-label mt-4 mb-2">Multi PIC</div>
                    <div class="info-value-text mb-4">
                        @if($customer->contacts && count($customer->contacts) > 0)
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($customer->contacts as $contact)
                                    <span class="badge bg-light text-dark border"><i class="fas fa-user me-1 text-muted"></i>{{ $contact->name }} ({{ $contact->phone }})</span>
                                @endforeach
                            </div>
                        @else
                            -
                        @endif
                    </div>

                    <div class="info-label mt-4 mb-2">Tax Data List</div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr class="bg-light">
                                    <th>Tax Type</th>
                                    <th>Tax Name</th>
                                    <th>Tax Number</th>
                                    <th>NITKU</th>
                                    <th>PPN Code</th>
                                    <th>Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($customer->customerTaxSettings as $tax)
                                <tr>
                                    <td>{{ $tax->tax_type }}</td>
                                    <td>{{ $tax->tax_name }}</td>
                                    <td>{{ $tax->tax_number }}</td>
                                    <td>{{ $tax->nitku }}</td>
                                    <td>{{ $tax->ppn_code }}</td>
                                    <td><small>{{ $tax->tax_address }}</small></td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-2 text-muted">No tax records found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Location & Address Section -->
            <div class="card mb-3">
                <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #7c3aed;">
                    <h5 class="card-title mb-0" style="color: #7c3aed;">
                        <i class="fas fa-map-marked-alt me-2"></i>Location & Address
                    </h5>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div style="grid-column: span 4;">
                            <div class="info-label">Address</div>
                            <div class="info-value-text">{{ $customer->address ?? '-' }}</div>
                        </div>
                        
                        <div>
                            <div class="info-label">Province</div>
                            <div class="info-value-text">{{ $customer->province->name ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="info-label">City</div>
                            <div class="info-value-text">{{ $customer->city->name ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="info-label">District</div>
                            <div class="info-value-text">{{ $customer->district->name ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="info-label">Subdistrict</div>
                            <div class="info-value-text">{{ $customer->subdistrict->name ?? '-' }}</div>
                        </div>
                        
                        <div>
                            <div class="info-label">Postal Code</div>
                            <div class="info-value-text">{{ $customer->postal_code ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="card mb-3">
                <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #10b981;">
                    <h5 class="card-title mb-0" style="color: #10b981;">
                        <i class="fas fa-envelope-open-text me-2"></i>Contact Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div style="grid-column: span 2;">
                            <div class="info-label">Email</div>
                            <div class="info-value-text">{{ $customer->email ?? '-' }}</div>
                        </div>
                        <div style="grid-column: span 2;">
                            <div class="info-label">Phone</div>
                            <div class="info-value-text">{{ $customer->phone ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Assignment & Status -->
            <div class="card mb-3">
                <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #6366f1;">
                    <h5 class="card-title mb-0" style="color: #6366f1;">
                        <i class="fas fa-user-check me-2"></i>Assignment & Status
                    </h5>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div>
                            <div class="info-label">Status</div>
                            <div>
                                <span class="badge" style="background-color: {{ $customer->is_active ? '#059669' : '#dc2626' }}; color: white; padding: 0.35rem 0.75rem; font-size: 0.8rem;">
                                    {{ $customer->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Information -->
            <div class="card mb-3">
                <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #64748b; padding: 0.75rem 1.5rem;">
                    <h5 class="card-title mb-0" style="color: #64748b; font-size: 0.95rem;">
                        <i class="fas fa-history me-2"></i>System Information
                    </h5>
                </div>
                <div class="card-body" style="padding: 1rem 1.5rem;">
                    <div class="info-grid">
                        <div>
                            <div class="info-label">Created By</div>
                            <div style="font-size: 0.9rem; color: #212529;">{{ $customer->createdBy->name ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="info-label">Created At</div>
                            <div style="font-size: 0.9rem; color: #212529;">{{ $customer->created_at ? $customer->created_at->format('j M Y H:i') : '-' }}</div>
                        </div>
                        <div>
                            <div class="info-label">Updated By</div>
                            <div style="font-size: 0.9rem; color: #212529;">{{ $customer->updatedBy->name ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="info-label">Updated At</div>
                            <div style="font-size: 0.9rem; color: #212529;">{{ $customer->updated_at ? $customer->updated_at->format('j M Y H:i') : '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Associated Buildings -->
            <div class="card mb-3">
                <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #1e3a8a;">
                    <h5 class="card-title mb-0" style="color: #1e3a8a;">
                        <i class="fas fa-building me-2"></i>Associated Buildings
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped mb-0">
                            <thead>
                                <tr>
                                    <th style="padding: 12px; background: #f8f9fa;">Building Name</th>
                                    <th style="padding: 12px; background: #f8f9fa;">City</th>
                                    <th style="padding: 12px; background: #f8f9fa;">Address</th>
                                    <th style="padding: 12px; background: #f8f9fa; text-align: center;">Floors</th>
                                    <th style="padding: 12px; background: #f8f9fa; text-align: center;">Area</th>
                                    <th style="padding: 12px; background: #f8f9fa; text-align: center;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($customer->buildingCustomers as $building)
                                <tr>
                                    <td style="padding: 12px;"><strong>{{ $building->nama_gedung ?? $building->name }}</strong></td>
                                    <td style="padding: 12px;">{{ $building->city->name ?? '-' }}</td>
                                    <td style="padding: 12px;"><small>{{ $building->alamat_1 }}</small></td>
                                    <td style="padding: 12px; text-align: center;">{{ $building->total_floors ?? '-' }}</td>
                                    <td style="padding: 12px; text-align: center;">{{ $building->total_area ? $building->total_area . ' m²' : '-' }}</td>
                                    <td style="padding: 12px; text-align: center;">
                                        <span class="badge" style="background-color: {{ $building->status_update ? '#059669' : '#6b7280' }}; color: white;">
                                            {{ $building->status_update ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No buildings found for this customer</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Customer Contacts Section (Pegawai/Staff) -->
            <div class="card mb-3">
                <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #1e3a8a;">
                    <h5 class="card-title mb-0" style="color: #1e3a8a;">
                        <i class="fas fa-user-friends me-2"></i>Customer Contacts (Pegawai/Staff)
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped mb-0">
                            <thead>
                                <tr>
                                    <th style="padding: 12px; background: #f8f9fa;">Contact Name</th>
                                    <th style="padding: 12px; background: #f8f9fa;">Position</th>
                                    <th style="padding: 12px; background: #f8f9fa;">Email</th>
                                    <th style="padding: 12px; background: #f8f9fa;">Phone</th>
                                    <th style="padding: 12px; background: #f8f9fa; text-align: center;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($customer->customerContacts as $staff)
                                <tr>
                                    <td style="padding: 12px;"><strong>{{ $staff->salutation }} {{ $staff->name }}</strong></td>
                                    <td style="padding: 12px;">{{ $staff->position ?? '-' }}</td>
                                    <td style="padding: 12px;">{{ $staff->email ?? '-' }}</td>
                                    <td style="padding: 12px;">{{ $staff->phone ?? '-' }}</td>
                                    <td style="padding: 12px; text-align: center;">
                                        <span class="badge" style="background-color: {{ $staff->is_active ? '#059669' : '#6b7280' }}; color: white;">
                                            {{ $staff->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No contacts found for this customer<br><small class="text-xs">Customer contacts are the employees/staff of the company</small></td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModalOverlay" class="modal-overlay" onclick="closeEditModal()">
    <div class="modal-container" style="max-width: 1000px; display: flex; flex-direction: column; max-height: 90vh;" onclick="event.stopPropagation()">
        <div class="modal-header" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 1.25rem 1.5rem;">
            <h2 class="modal-title" style="font-size: 1.25rem; font-weight: 700; color: #1e293b;">
                <i class="fas fa-user-edit me-2 text-blue-600"></i>Edit Customer
            </h2>
            <button class="btn-close" onclick="closeEditModal()"></button>
        </div>
        <div class="modal-body" style="flex: 1; overflow-y: auto; padding: 1.5rem; background: #ffffff;">
            <form id="editForm">
                @csrf
                @method('PUT')
                
                <!-- 1. Basic Information Section -->
                <div class="form-section" style="border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 1.5rem; overflow: hidden;">
                    <h3 class="section-title" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 0.75rem 1.5rem; font-size: 0.85rem; font-weight: 700; color: #334155; text-transform: uppercase; letter-spacing: 0.025em; display: flex; align-items: center;">
                        <i class="fas fa-id-card me-2 text-blue-500"></i>Basic Information
                    </h3>
                    <div class="form-section-body" style="padding: 1.5rem;">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="form-label" style="font-size: 0.875rem; font-weight: 600; color: #475569; margin-bottom: 0.5rem;">Badan Hukum *</label>
                                <select id="edit_company_type" name="company_type" class="form-select select2" required>
                                    <option value="">Pilih Badan Hukum</option>
                                    @if(isset($companyTypeOptions) && count($companyTypeOptions) > 0)
                                        @foreach($companyTypeOptions as $type)
                                            <option value="{{ $type->option_name }}" {{ isset($customer->company_type) && strtolower($customer->company_type) == strtolower($type->option_name) ? 'selected' : '' }}>
                                                {{ $type->option_name }}
                                            </option>
                                        @endforeach
                                    @else
                                        <!-- Fallback if options not loaded -->
                                        <option value="PT" {{ strtolower($customer->company_type ?? '') == 'pt' ? 'selected' : '' }}>PT</option>
                                        <option value="CV" {{ strtolower($customer->company_type ?? '') == 'cv' ? 'selected' : '' }}>CV</option>
                                    @endif
                                </select>
                            </div>
                            <div>
                                <label class="form-label" style="font-size: 0.875rem; font-weight: 600; color: #475569; margin-bottom: 0.5rem;">Customer Name *</label>
                                <input type="text" id="edit_name" name="name" class="form-control" value="{{ $customer->name }}" required style="border-radius: 0.375rem; border: 1px solid #d1d5db; padding: 0.5rem 0.75rem;">
                            </div>
                            <div>
                                <label class="form-label" style="font-size: 0.875rem; font-weight: 600; color: #475569; margin-bottom: 0.5rem;">Customer Code</label>
                                <input type="text" id="edit_customer_code" name="customer_code" class="form-control" value="{{ $customer->customer_code }}" readonly style="background-color: #f1f5f9; border-radius: 0.375rem; border: 1px solid #d1d5db; cursor: not-allowed;">
                            </div>
                            <div>
                                <label class="form-label" style="font-size: 0.875rem; font-weight: 600; color: #475569; margin-bottom: 0.5rem;">Label / Alias</label>
                                <input type="text" id="edit_label_alias" name="label_alias" class="form-control" value="{{ $customer->label_alias }}" style="border-radius: 0.375rem; border: 1px solid #d1d5db;">
                            </div>
                            <div class="md:col-span-2">
                                <label class="form-label" style="font-size: 0.875rem; font-weight: 600; color: #475569; margin-bottom: 0.5rem;">Category</label>
                                <select id="edit_customer_category_id" name="customer_category_id" class="form-select select2">
                                    <option value="">Pilih Category</option>
                                    @foreach($categories ?? [] as $category)
                                        <option value="{{ $category->id }}" {{ (string)$customer->customer_category_id == (string)$category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="form-label" style="font-size: 0.875rem; font-weight: 600; color: #475569; margin-bottom: 0.5rem;">Classification</label>
                                <select id="edit_classification_id" name="classification_id" class="form-select select2">
                                    <option value="">Select Classification</option>
                                    @foreach($classificationOptions ?? [] as $option)
                                        <option value="{{ $option->id }}" {{ (string)$customer->classification_id == (string)$option->id ? 'selected' : '' }}>{{ $option->option_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Payment & Business Information Section -->
                <div class="form-section" style="border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 1.5rem; overflow: hidden;">
                    <h3 class="section-title" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 0.75rem 1.5rem; font-size: 0.85rem; font-weight: 700; color: #334155; text-transform: uppercase; letter-spacing: 0.025em; display: flex; align-items: center;">
                        <i class="fas fa-file-invoice-dollar me-2 text-warning"></i>Payment & Business Info
                    </h3>
                    <div class="form-section-body" style="padding: 1.5rem;">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="form-label" style="font-size: 0.875rem; font-weight: 600; color: #475569; margin-bottom: 0.5rem;">NIB (Nomor Induk Berusaha)</label>
                                <input type="text" id="edit_nib" name="nib" class="form-control" value="{{ $customer->nib }}" placeholder="Nomor Induk Berusaha" style="border-radius: 0.375rem; border: 1px solid #d1d5db;">
                            </div>
                            <div>
                                <label class="form-label" style="font-size: 0.875rem; font-weight: 600; color: #475569; margin-bottom: 0.5rem;">Customer Group</label>
                                <input type="text" id="edit_customer_group" name="customer_group" class="form-control" value="{{ $customer->customer_group }}" placeholder="e.g., Sinarmas, Unilever" style="border-radius: 0.375rem; border: 1px solid #d1d5db;">
                            </div>
                            <div class="md:col-span-2">
                                <label class="form-label" style="font-size: 0.875rem; font-weight: 600; color: #475569; margin-bottom: 0.5rem;">Default Bank Payment</label>
                                <select id="edit_default_bank_payment_id" name="default_bank_payment_id" class="form-select select2">
                                    <option value="">Pilih Bank Payment</option>
                                    @foreach($bankPayments as $bankPayment)
                                        <option value="{{ $bankPayment->id }}" {{ $customer->default_bank_payment_id == $bankPayment->id ? 'selected' : '' }}>
                                            {{ $bankPayment->bank->name ?? '' }} - {{ $bankPayment->account_name }} ({{ $bankPayment->account_number }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="form-label" style="font-size: 0.875rem; font-weight: 600; color: #475569; margin-bottom: 0.5rem;">Multi PIC (Person In Charge)</label>
                                <div class="flex gap-2 items-start">
                                    <div class="flex-grow">
                                        <select id="edit_contact_ids" name="contact_ids[]" multiple class="form-select select2" style="min-height: 120px;">
                                            @foreach($allContacts as $contact)
                                                @php
                                                    $isSelected = $customer->contacts->contains('id', $contact->id) || 
                                                                  $customer->assigned_to == $contact->id ||
                                                                  $contact->customer_id == $customer->id;
                                                @endphp
                                                <option value="{{ $contact->id }}" {{ $isSelected ? 'selected' : '' }}>
                                                    {{ $contact->name }} - {{ $contact->position ?? 'No Position' }} ({{ $contact->phone }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button type="button" onclick="openCreateContactModal(true)" class="btn btn-success" title="Add New Contact" style="height: 44px; width: 44px; display: flex; align-items: center; justify-content: center; background-color: #10b981; border: none; color: white; border-radius: 0.375rem;">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                                <small class="text-xs text-slate-500 mt-2 block">Tekan Ctrl+Click untuk pilih lebih dari satu. Klik <i class="fas fa-plus text-green-500"></i> untuk tambah contact baru.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Contact & Location Section -->
                <div class="form-section" style="border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 1.5rem; overflow: hidden;">
                    <h3 class="section-title" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 0.75rem 1.5rem; font-size: 0.85rem; font-weight: 700; color: #334155; text-transform: uppercase; letter-spacing: 0.025em; display: flex; align-items: center;">
                        <i class="fas fa-map-marked-alt me-2 text-danger"></i>Contact & Location
                    </h3>
                    <div class="form-section-body" style="padding: 1.5rem;">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="form-label" style="font-size: 0.875rem; font-weight: 600; color: #475569; margin-bottom: 0.5rem;">Email *</label>
                                <input type="email" id="edit_email" name="email" class="form-control" value="{{ $customer->email }}" required style="border-radius: 0.375rem; border: 1px solid #d1d5db;">
                            </div>
                            <div>
                                <label class="form-label" style="font-size: 0.875rem; font-weight: 600; color: #475569; margin-bottom: 0.5rem;">Phone *</label>
                                <input type="text" id="edit_phone" name="phone" class="form-control" value="{{ $customer->phone }}" required style="border-radius: 0.375rem; border: 1px solid #d1d5db;">
                            </div>
                            <div>
                                <label class="form-label" style="font-size: 0.875rem; font-weight: 600; color: #475569; margin-bottom: 0.5rem;">Province</label>
                                <select id="edit_province_id" name="province_id" class="form-select select2" onchange="loadCities(this.value)">
                                    <option value="">Select Province</option>
                                    @foreach($provinces as $province)
                                        <option value="{{ $province->id }}" {{ $customer->province_id == $province->id ? 'selected' : '' }}>{{ $province->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="form-label" style="font-size: 0.875rem; font-weight: 600; color: #475569; margin-bottom: 0.5rem;">City</label>
                                <select id="edit_city_id" name="city_id" class="form-select select2" onchange="loadDistricts(this.value)">
                                    <option value="">Select City</option>
                                    @php 
                                        $selectedCityId = $customer->city_id ?? ($customer->district->city_id ?? null);
                                    @endphp
                                    @if(isset($cities) && count($cities) > 0)
                                        @foreach($cities as $city)
                                            <option value="{{ $city->id }}" {{ (string)$selectedCityId === (string)$city->id ? 'selected' : '' }}>{{ $city->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div>
                                <label class="form-label" style="font-size: 0.875rem; font-weight: 600; color: #475569; margin-bottom: 0.5rem;">District</label>
                                <select id="edit_district_id" name="district_id" class="form-select select2" onchange="loadSubdistricts(this.value)">
                                    <option value="">Select District</option>
                                    @if(isset($districts) && count($districts) > 0)
                                        @foreach($districts as $district)
                                            <option value="{{ $district->id }}" {{ (string)$customer->district_id === (string)$district->id ? 'selected' : '' }}>{{ $district->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div>
                                <label class="form-label" style="font-size: 0.875rem; font-weight: 600; color: #475569; margin-bottom: 0.5rem;">Subdistrict</label>
                                <select id="edit_subdistrict_id" name="subdistrict_id" class="form-select select2" onchange="loadPostalCode(this.value)">
                                    <option value="">Select Subdistrict</option>
                                    @if(isset($subdistricts) && count($subdistricts) > 0)
                                        @foreach($subdistricts as $subdistrict)
                                            <option value="{{ $subdistrict->id }}" {{ (string)$customer->subdistrict_id === (string)$subdistrict->id ? 'selected' : '' }} data-postal-code="{{ $subdistrict->postal_code }}">{{ $subdistrict->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div>
                                <label class="form-label" style="font-size: 0.875rem; font-weight: 600; color: #475569; margin-bottom: 0.5rem;">Postal Code</label>
                                <input type="text" id="edit_postal_code" name="postal_code" class="form-control" value="{{ $customer->postal_code }}" readonly style="background-color: #f1f5f9; border-radius: 0.375rem; border: 1px solid #d1d5db; cursor: not-allowed;">
                            </div>
                            <div class="md:col-span-2">
                                <label class="form-label" style="font-size: 0.875rem; font-weight: 600; color: #475569; margin-bottom: 0.5rem;">Full Address *</label>
                                <textarea id="edit_address" name="address" class="form-control" rows="3" required style="border-radius: 0.375rem; border: 1px solid #d1d5db;">{{ $customer->address }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. Settings & Status Section -->
                <div class="form-section" style="border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 1rem; overflow: hidden;">
                    <h3 class="section-title" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 0.75rem 1.5rem; font-size: 0.85rem; font-weight: 700; color: #334155; text-transform: uppercase; letter-spacing: 0.025em; display: flex; align-items: center;">
                        <i class="fas fa-cog me-2 text-slate-500"></i>Settings & Status
                    </h3>
                    <div class="form-section-body" style="padding: 1.5rem;">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="flex items-center p-4 bg-slate-50 rounded-lg border border-slate-200">
                                <div class="form-check form-switch m-0" style="padding-left: 3.5rem;">
                                    <input class="form-check-input" type="checkbox" id="edit_is_pkp" name="is_pkp" {{ $customer->is_pkp ? 'checked' : '' }} style="width: 3rem; height: 1.5rem; margin-left: -3.5rem; cursor: pointer;">
                                    <label class="form-check-label font-bold text-slate-700 cursor-pointer ml-3 flex flex-col" for="edit_is_pkp">
                                        <span>PKP Status</span>
                                        <small class="font-normal text-slate-500">Pengusaha Kena Pajak</small>
                                    </label>
                                </div>
                            </div>
                            <div class="flex items-center p-4 bg-slate-50 rounded-lg border border-slate-200">
                                <div class="form-check form-switch m-0" style="padding-left: 3.5rem;">
                                    <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active" {{ $customer->is_active ? 'checked' : '' }} style="width: 3rem; height: 1.5rem; margin-left: -3.5rem; cursor: pointer;">
                                    <label class="form-check-label font-bold text-slate-700 cursor-pointer ml-3 flex flex-col" for="edit_is_active">
                                        <span>Active Account</span>
                                        <small class="font-normal text-slate-500">Customer status di sistem</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer" style="padding: 1.25rem 1.5rem; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 0.75rem; background: #f8fafc; border-radius: 0 0 12px 12px;">
            <button type="button" class="btn btn-secondary px-6 py-2" onclick="closeEditModal()" style="border-radius: 0.5rem; font-weight: 600; border: 1px solid #e2e8f0; background: white; color: #64748b;">Cancel</button>
            <button type="button" class="btn btn-primary px-8 py-2" onclick="submitEditForm()" style="border-radius: 0.5rem; font-weight: 600; background: #214589; border: none;">Update Customer</button>
        </div>
    </div>
</div>

<!-- Create Contact Modal (Inline) -->
<div id="createContactModalOverlay" class="modal-overlay" onclick="closeCreateContactModal()">
    <div class="modal-container" style="max-width: 600px;" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 class="modal-title">Add New Contact Person</h2>
            <button class="btn-close" onclick="closeCreateContactModal()"></button>
        </div>
        <div class="modal-body">
            <form id="createContactForm">
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-user text-purple-500"></i>
                        Contact Person Information
                    </h3>
                    <div class="form-section-body">
                        <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Salutation</label>
                            <select id="contact_salutation_id" name="salutation" class="form-select">
                                <option value="">Pilih Sapaan</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Full Name *</label>
                            <input type="text" id="contact_name" name="name" class="form-control" required placeholder="Enter full name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Email *</label>
                            <input type="email" id="contact_email" name="email" class="form-control" required placeholder="email@example.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Phone *</label>
                            <input type="tel" id="contact_phone" name="phone" class="form-control" required placeholder="08123456789" pattern="[0-9\-\+\(\)\s]+" oninput="this.value = this.value.replace(/[^0-9\-\+\(\)\s]/g, '')">
                            <small class="text-muted text-xs mt-1 d-block">Numbers, +, -, (), and spaces allowed</small>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Position</label>
                            <select id="contact_position_id" name="position" class="form-select">
                                <option value="">Pilih Posisi</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Customer</label>
                            <input type="text" id="contact_customer_name" class="form-control" value="{{ $customer->name }}" readonly style="background-color: #f9fafb;">
                            <input type="hidden" id="contact_customer_id" name="customer_id" value="{{ $customer->id }}">
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeCreateContactModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="submitCreateContactForm()">Add Contact</button>
        </div>
    </div>
</div>

<script>
    function openEditModal() {
        document.getElementById('editModalOverlay').classList.add('show');
        document.body.style.overflow = 'hidden';
        
        // Setup Select2 when modal opens
        if (typeof jQuery !== 'undefined') {
            // Only initialize if not already initialized or needed
            // We DO NOT trigger change here because it would fire the onchange events 
            // (loadCities, loadDistricts) which would WIPE OUT our server-rendered selections.
            
            if (jQuery.fn.select2) {
                jQuery('.select2').select2({
                    width: '100%',
                    dropdownParent: jQuery('#editModalOverlay')
                });
            }
        }
    }

    function closeEditModal() {
        document.getElementById('editModalOverlay').classList.remove('show');
        document.body.style.overflow = 'auto';
    }

    function loadCities(provinceId) {
        if (!provinceId) {
            document.getElementById('edit_city_id').innerHTML = '<option value="">Select City</option>';
            document.getElementById('edit_district_id').innerHTML = '<option value="">Select District</option>';
            document.getElementById('edit_subdistrict_id').innerHTML = '<option value="">Select Subdistrict</option>';
            document.getElementById('edit_postal_code').value = '';
            return;
        }
        fetch(`/api/v1/location/cities?province_id=${provinceId}`)
            .then(res => res.json())
            .then(data => {
                const select = document.getElementById('edit_city_id');
                select.innerHTML = '<option value="">Select City</option>';
                const cities = Array.isArray(data) ? data : (data.data || []);
                cities.forEach(item => {
                    select.innerHTML += `<option value="${item.id}">${item.name}</option>`;
                });
                document.getElementById('edit_district_id').innerHTML = '<option value="">Select District</option>';
                document.getElementById('edit_subdistrict_id').innerHTML = '<option value="">Select Subdistrict</option>';
                document.getElementById('edit_postal_code').value = '';
            });
    }

    function loadDistricts(cityId) {
        if (!cityId) {
            document.getElementById('edit_district_id').innerHTML = '<option value="">Select District</option>';
            document.getElementById('edit_subdistrict_id').innerHTML = '<option value="">Select Subdistrict</option>';
            document.getElementById('edit_postal_code').value = '';
            return;
        }
        fetch(`/api/v1/location/districts?city_id=${cityId}`)
            .then(res => res.json())
            .then(data => {
                const select = document.getElementById('edit_district_id');
                select.innerHTML = '<option value="">Select District</option>';
                const districts = Array.isArray(data) ? data : (data.data || []);
                districts.forEach(item => {
                    select.innerHTML += `<option value="${item.id}">${item.name}</option>`;
                });
                document.getElementById('edit_subdistrict_id').innerHTML = '<option value="">Select Subdistrict</option>';
                document.getElementById('edit_postal_code').value = '';
            });
    }

    function loadSubdistricts(districtId) {
        if (!districtId) {
            document.getElementById('edit_subdistrict_id').innerHTML = '<option value="">Select Subdistrict</option>';
            document.getElementById('edit_postal_code').value = '';
            return;
        }
        fetch(`/api/v1/location/subdistricts?district_id=${districtId}`)
            .then(res => res.json())
            .then(data => {
                const select = document.getElementById('edit_subdistrict_id');
                select.innerHTML = '<option value="">Select Subdistrict</option>';
                const subdistricts = Array.isArray(data) ? data : (data.data || []);
                subdistricts.forEach(item => {
                    select.innerHTML += `<option value="${item.id}" data-postal-code="${item.postal_code || ''}">${item.name}</option>`;
                });
                document.getElementById('edit_postal_code').value = '';
            });
    }

    function loadPostalCode(subdistrictId) {
        if (!subdistrictId) {
            document.getElementById('edit_postal_code').value = '';
            return;
        }
        const select = document.getElementById('edit_subdistrict_id');
        const option = select.options[select.selectedIndex];
        document.getElementById('edit_postal_code').value = option.getAttribute('data-postal-code') || '';
    }

    function submitEditForm() {
        const form = document.getElementById('editForm');
        const formData = new FormData(form);
        const customerId = {{ $customer->id }};
        
        // Convert FormData to JSON
        const data = {};
        formData.forEach((value, key) => {
            // Handle multiple values (contact_ids[])
            if (key.endsWith('[]')) {
                const realKey = key.slice(0, -2);
                if (!data[realKey]) {
                    data[realKey] = Array.from(form.elements[key].selectedOptions).map(opt => opt.value);
                }
            } else {
                data[key] = value;
            }
        });

        // Handle checkboxes/switches explicitly
        data.is_active = form.elements['is_active'] ? (form.elements['is_active'].checked ? 1 : 0) : 1;
        data.is_pkp = form.elements['is_pkp'] ? (form.elements['is_pkp'].checked ? 1 : 0) : 0;

        const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        Swal.fire({
            title: 'Memperbarui...',
            text: 'Mohon tunggu, perubahan sedang disimpan',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        fetch(`/company/customers/${customerId}`, {
            method: 'POST', // Use POST with _method=PUT for full compatibility
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                ...data,
                _method: 'PUT'
            })
        })
        .then(response => response.json())
        .then(result => {
            if (result.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: 'Customer berhasil diperbarui.',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: result.message || 'Terjadi kesalahan!'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: 'Gagal memperbarui customer.'
            });
        });
    }

    function openCreateContactModal(isEditMode = false) {
        loadSalutations();
        loadPositions();
        document.getElementById('createContactForm').reset();
        document.getElementById('createContactModalOverlay').classList.add('show');
    }

    function closeCreateContactModal() {
        document.getElementById('createContactModalOverlay').classList.remove('show');
    }

    function loadSalutations() {
        fetch('/other/master-options/13', {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            const select = document.getElementById('contact_salutation_id');
            select.innerHTML = '<option value="">Pilih Sapaan</option>';
            const options = data.optionDetails || data.option_details || [];
            options.forEach(item => {
                if (item.is_active !== false) {
                    select.innerHTML += `<option value="${item.option_name || item.name}">${item.option_name || item.name}</option>`;
                }
            });
        });
    }

    function loadPositions() {
        fetch('/other/master-options/1', {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            const select = document.getElementById('contact_position_id');
            select.innerHTML = '<option value="">Pilih Posisi</option>';
            const options = data.optionDetails || data.option_details || [];
            options.forEach(item => {
                if (item.is_active !== false) {
                    select.innerHTML += `<option value="${item.option_name || item.name}">${item.option_name || item.name}</option>`;
                }
            });
        });
    }

    function submitCreateContactForm() {
        const form = document.getElementById('createContactForm');
        if (!form.checkValidity()) { form.reportValidity(); return; }

        const formData = new FormData(form);
        const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        Swal.fire({ title: 'Membuat...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        fetch('/company/customer-contacts', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: formData
        })
        .then(res => res.json())
        .then(result => {
            if (result.status === 'success' || result.success) {
                Swal.fire({ icon: 'success', title: 'Berhasil!', text: 'Kontak berhasil dibuat.', timer: 1500, showConfirmButton: false });
                closeCreateContactModal();
                
                // Add new contact to the Multi PIC select
                const editSelect = document.getElementById('edit_contact_ids');
                if (editSelect && result.data) {
                    const optionText = `${result.data.name} - ${result.data.position || ''} (${result.data.phone})`;
                    const newOption = new Option(optionText, result.data.id, true, true);
                    editSelect.add(newOption);
                    // No triggerChange needed if not using select2, but let's be safe
                    if (typeof jQuery !== 'undefined' && jQuery(editSelect).data('select2')) {
                        jQuery(editSelect).trigger('change');
                    }
                }
            } else {
                Swal.fire({ icon: 'error', title: 'Gagal', text: result.message || 'Gagal membuat kontak' });
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire({ icon: 'error', title: 'Gagal', text: 'Terjadi kesalahan' });
        });
    }

    // Close on Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeEditModal();
        }
    });
</script>

@endsection
