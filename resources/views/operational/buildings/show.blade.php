@extends('layouts.app')

@section('title', 'Building Detail - ' . ($building->building_name))

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
     /* Edit Modal Specific Styles */
    .form-input, 
    .select2-container--default .select2-selection--single {
        border: 1px solid #d1d5db !important;
        border-radius: 0.375rem !important;
        background-color: #fff;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        width: 100% !important;
    }
    
    .form-input:focus, 
    .select2-container--default.select2-container--open .select2-selection--single {
        border-color: #214589 !important;
        outline: 0;
        box-shadow: 0 0 0 0.25rem rgba(33, 69, 137, 0.25) !important;
    }
    
    .select2-container--default .select2-selection--single {
        height: 42px !important;
        padding: 5px 0 !important;
        display: flex !important;
        align-items: center !important;
    }
    
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px !important;
    }

    /* Readonly input style */
    .form-input[readonly] {
        background-color: #f3f4f6 !important;
        cursor: default;
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
            <!-- Header - Layout Aruna Warehouse -->
            <div class="card mb-3" style="background-color: #1e3a8a; color: white; border: none; border-radius: 10px;">
                <div class="card-header" style="background-color: #1e3a8a; border: none; padding: 1rem 1.5rem;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <a href="{{ route('operational.buildings.index') }}" class="btn btn-light btn-sm">
                                <i class="fas fa-arrow-left"></i> Back to List
                            </a>
                        </div>
                        <div class="text-center">
                            <h3 class="card-title mb-0" style="color: white; font-size: 1.5rem; font-weight: bold;">
                                {{ $building->building_name }}
                            </h3>
                            <div class="mt-1">
                                <span class="badge" style="background-color: {{ $building->status_update ? '#059669' : '#dc2626' }}; color: white; padding: 0.35rem 0.75rem; font-size: 0.8rem;">
                                    {{ $building->status_update ? 'Active' : 'Inactive' }}
                                </span>
                                <span class="ms-2" style="font-size: 0.9rem; opacity: 0.9;">Building ID: {{ $building->id }}</span>
                            </div>
                        </div>
                        <div>
                            <button type="button" class="btn btn-edit-premium" onclick="openEditModal()">
                                <i class="fas fa-edit me-1"></i> Edit Building
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Building Basic Information -->
            <div class="card mb-3">
                <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #1e3a8a;">
                    <h5 class="card-title mb-0" style="color: #1e3a8a;">
                        <i class="fas fa-info-circle me-2"></i>Building Technical Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <!-- Row 1 -->
                        <div style="grid-column: span 2;">
                            <div class="info-label">Building Name</div>
                            <div class="info-value">{{ $building->building_name ?? '-' }}</div>
                        </div>
                        <div style="grid-column: span 2;">
                            <div class="info-label">Jenis Alamat</div>
                            <div class="info-value">{{ $building->building_type ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="info-label">Total Floors</div>
                            <div class="info-value">{{ $building->total_floors ?? '-' }} Floors</div>
                        </div>
                        <div>
                            <div class="info-label">Total Area</div>
                            <div class="info-value">{{ $building->total_area ? $building->total_area . ' m²' : '-' }}</div>
                        </div>
                        
                        <!-- Row 2 -->
                        <div style="grid-column: span 2;">
                            <div class="info-label">Email</div>
                            <div class="info-value-text">{{ $building->email ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="info-label">Phone 1</div>
                            <div class="info-value-text">{{ $building->phone_1 ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="info-label">Phone 2</div>
                            <div class="info-value-text">{{ $building->phone_2 ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Location & Address Section -->
            <div class="card mb-3">
                <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #ef4444;">
                    <h5 class="card-title mb-0" style="color: #ef4444;">
                        <i class="fas fa-map-marked-alt me-2"></i>Location & Address
                    </h5>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <!-- Row 1 -->
                        <div style="grid-column: span 2;">
                            <div class="info-label">Primary Address</div>
                            <div class="info-value-text">{{ $building->alamat_1 ?? $building->address ?? '-' }}</div>
                        </div>
                        <div style="grid-column: span 2;">
                            <div class="info-label">Secondary Address</div>
                            <div class="info-value-text">{{ $building->alamat_2 ?? '-' }}</div>
                        </div>
                        
                        <!-- Row 2 -->
                        <div>
                            <div class="info-label">Province</div>
                            <div class="info-value-text">{{ $building->province->name ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="info-label">City</div>
                            <div class="info-value-text">{{ $building->city->name ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="info-label">District</div>
                            <div class="info-value-text">{{ $building->district->name ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="info-label">Subdistrict</div>
                            <div class="info-value-text">{{ $building->subdistrict->name ?? '-' }}</div>
                        </div>

                        <!-- Row 3 -->
                        <div>
                            <div class="info-label">Postal Code</div>
                            <div class="info-value-text">{{ $building->kode_pos ?? $building->postal_code ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="info-label">Fax</div>
                            <div class="info-value-text">{{ $building->fax ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes Section -->
            <div class="card mb-3">
                <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #64748b;">
                    <h5 class="card-title mb-0" style="color: #64748b;">
                        <i class="fas fa-sticky-note me-2"></i>Notes & Description
                    </h5>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div style="grid-column: span 2;">
                            <div class="info-label">Description</div>
                            <div class="info-value-text">{{ $building->description ?? '-' }}</div>
                        </div>
                        <div style="grid-column: span 2;">
                            <div class="info-label">Notes</div>
                            <div class="info-value-text">{{ $building->notes ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Associated Customers -->
            <div class="card mb-3">
                <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #1e3a8a;">
                    <h5 class="card-title mb-0" style="color: #1e3a8a;">
                        <i class="fas fa-users me-2"></i>Associated Customers
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped mb-0">
                            <thead>
                                <tr>
                                    <th style="padding: 12px; background: #f8f9fa;">Customer Name</th>
                                    <th style="padding: 12px; background: #f8f9fa;">Category</th>
                                    <th style="padding: 12px; background: #f8f9fa;">Phone</th>
                                    <th style="padding: 12px; background: #f8f9fa; text-align: center;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($building->customers as $customer)
                                <tr>
                                    <td style="padding: 12px;"><strong>{{ $customer->name }}</strong></td>
                                    <td style="padding: 12px;">{{ $customer->customerCategory->name ?? '-' }}</td>
                                    <td style="padding: 12px;">{{ $customer->phone }}</td>
                                    <td style="padding: 12px; text-align: center;">
                                        <span class="badge" style="background-color: {{ $customer->is_active ? '#059669' : '#6b7280' }}; color: white;">
                                            {{ $customer->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">No customers associated with this building.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
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
                            <div style="font-size: 0.9rem; color: #212529;">{{ $building->createdBy->name ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="info-label">Created At</div>
                            <div style="font-size: 0.9rem; color: #212529;">{{ $building->created_at ? $building->created_at->format('d/M/Y H:i') : '-' }}</div>
                        </div>
                        <div>
                            <div class="info-label">Updated By</div>
                            <div style="font-size: 0.9rem; color: #212529;">{{ $building->updatedBy->name ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="info-label">Updated At</div>
                            <div style="font-size: 0.9rem; color: #212529;">{{ $building->updated_at ? $building->updated_at->format('d/M/Y H:i') : '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModalOverlay" class="modal-overlay" onclick="closeEditModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 class="modal-title">Edit Building</h2>
            <button class="btn-close" onclick="closeEditModal()"></button>
        </div>
        <div class="modal-body">
            <form id="editForm">
                @csrf
                @method('PUT')
                
                <!-- Basic Information -->
                <div class="mb-6">
                    <h6 class="text-[#214589] font-bold mb-4 border-b pb-2 flex items-center">
                        <i class="fas fa-info-circle mr-2"></i> Basic Information
                    </h6>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-group col-span-2">
                            <label class="form-label">Building Name *</label>
                            <input type="text" name="nama_gedung" class="form-input" value="{{ $building->nama_gedung }}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Jenis Alamat</label>
                            <select name="building_type" class="form-input select2">
                                <option value="">Pilih Jenis Alamat</option>
                                @php
                                    $addressTypes = \App\Models\MasterOption::with(['optionDetails' => function($query) {
                                        $query->where('is_active', true)->orderBy('option_name');
                                    }])->find(10)?->optionDetails ?? collect();
                                @endphp
                                @foreach($addressTypes as $type)
                                    <option value="{{ $type->option_name }}" {{ ($building->building_type == $type->option_name) ? 'selected' : '' }}>
                                        {{ $type->option_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Internal Name</label>
                            <input type="text" name="name" class="form-input" value="{{ $building->name }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Total Floors</label>
                            <input type="number" name="total_floors" class="form-input" value="{{ $building->total_floors }}" min="1">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Total Area (m²)</label>
                            <input type="number" name="total_area" class="form-input" value="{{ $building->total_area }}" step="0.01">
                        </div>
                    </div>
                </div>

                <!-- Location & Address -->
                <div class="mb-6">
                    <h6 class="text-[#ef4444] font-bold mb-4 border-b pb-2 flex items-center">
                        <i class="fas fa-map-marked-alt mr-2"></i> Location & Address
                    </h6>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Province *</label>
                            <select name="province_id" id="province_select" class="form-input select2" required onchange="loadCities(this.value)">
                                <option value="">Select Province</option>
                                @foreach($provinces as $province)
                                    <option value="{{ $province->id }}" {{ $building->province_id == $province->id ? 'selected' : '' }}>{{ $province->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">City *</label>
                            <select name="city_id" id="city_select" class="form-input select2" required onchange="loadDistricts(this.value)">
                                <option value="">Select City</option>
                                @foreach($cities as $city)
                                    <option value="{{ $city->id }}" {{ $building->city_id == $city->id ? 'selected' : '' }}>{{ $city->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">District *</label>
                            <select name="district_id" id="district_select" class="form-input select2" required onchange="loadSubdistricts(this.value)">
                                <option value="">Select District</option>
                                @foreach($districts as $district)
                                    <option value="{{ $district->id }}" {{ $building->district_id == $district->id ? 'selected' : '' }}>{{ $district->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Subdistrict *</label>
                            <select name="subdistrict_id" id="subdistrict_select" class="form-input select2" required onchange="fetchPostalCode(this.value)">
                                <option value="">Select Subdistrict</option>
                                @foreach($subdistricts as $subdistrict)
                                    <option value="{{ $subdistrict->id }}" {{ $building->subdistrict_id == $subdistrict->id ? 'selected' : '' }}>{{ $subdistrict->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-span-2">
                            <label class="form-label">Address 1 *</label>
                            <textarea name="alamat_1" class="form-input" rows="2" required>{{ $building->alamat_1 }}</textarea>
                        </div>
                        <div class="form-group col-span-2 md:col-span-1">
                            <label class="form-label">Address 2</label>
                            <input type="text" name="alamat_2" class="form-input" value="{{ $building->alamat_2 }}">
                        </div>
                        <div class="form-group col-span-2 md:col-span-1">
                            <label class="form-label">Postal Code</label>
                            <input type="text" name="kode_pos" id="postal_code_input" class="form-input bg-gray-50" value="{{ $building->kode_pos }}" readonly>
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div>
                     <h6 class="text-[#10b981] font-bold mb-4 border-b pb-2 flex items-center">
                        <i class="fas fa-phone mr-2"></i> Contact Information
                    </h6>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-input" value="{{ $building->email }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Fax</label>
                            <input type="text" name="fax" class="form-input" value="{{ $building->fax }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone 1</label>
                            <input type="text" name="phone_1" class="form-input" value="{{ $building->phone_1 }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone 2</label>
                            <input type="text" name="phone_2" class="form-input" value="{{ $building->phone_2 }}">
                        </div>
                        <div class="form-group col-span-2">
                            <div class="flex items-center p-3 border rounded bg-gray-50">
                                <input type="checkbox" id="edit_status" name="status_update" class="w-5 h-5 text-[#214589] rounded mr-3" {{ $building->status_update ? 'checked' : '' }}>
                                <label for="edit_status" class="font-medium text-gray-700 cursor-pointer select-none">Active Status</label>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="submitEditForm()">Update Building</button>
        </div>
    </div>
</div>

<script>
    function openEditModal() {
        document.getElementById('editModalOverlay').classList.add('show');
        document.body.style.overflow = 'hidden';
        
        // Initialize Select2 with proper width and parent
        if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
             jQuery('.select2').each(function() {
                jQuery(this).select2({
                    width: '100%',
                    dropdownParent: jQuery('#editModalOverlay')
                });
            });
        }
    }

    function closeEditModal() {
        document.getElementById('editModalOverlay').classList.remove('show');
        document.body.style.overflow = 'auto';
    }

    // Helper to safely reset select2
    function resetSelect2(id, placeholder) {
        const select = jQuery(`#${id}`);
        select.empty().append(`<option value="">${placeholder}</option>`);
        select.trigger('change.select2'); // Only trigger select2 change, not native change
    }

    function loadCities(provinceId) {
        if (!provinceId) {
             resetSelect2('city_select', 'Select City');
             resetSelect2('district_select', 'Select District');
             resetSelect2('subdistrict_select', 'Select Subdistrict');
             return;
        }

        fetch(`/api/cities/${provinceId}`)
            .then(res => res.json())
            .then(data => {
                const select = jQuery('#city_select');
                select.empty().append('<option value="">Select City</option>');
                
                data.forEach(item => {
                    select.append(new Option(item.name, item.id));
                });
                
                select.trigger('change'); // Update UI
                
                // Clear dependent fields
                resetSelect2('district_select', 'Select District');
                resetSelect2('subdistrict_select', 'Select Subdistrict');
            })
            .catch(err => console.error('Error loading cities:', err));
    }

    function loadDistricts(cityId) {
        if (!cityId) {
            resetSelect2('district_select', 'Select District');
            resetSelect2('subdistrict_select', 'Select Subdistrict');
            return;
        }
        
        fetch(`/api/districts/${cityId}`)
            .then(res => res.json())
            .then(data => {
                const select = jQuery('#district_select');
                select.empty().append('<option value="">Select District</option>');
                
                data.forEach(item => {
                    select.append(new Option(item.name, item.id));
                });
                
                select.trigger('change');
                resetSelect2('subdistrict_select', 'Select Subdistrict');
            });
    }

    function loadSubdistricts(districtId) {
        if (!districtId) {
            resetSelect2('subdistrict_select', 'Select Subdistrict');
            return;
        }

        fetch(`/api/subdistricts/${districtId}`)
            .then(res => res.json())
            .then(data => {
                const select = jQuery('#subdistrict_select');
                select.empty().append('<option value="">Select Subdistrict</option>');
                
                data.forEach(item => {
                    select.append(new Option(item.name, item.id));
                });
                
                select.trigger('change');
            });
    }


    function fetchPostalCode(subdistrictId) {
        if (!subdistrictId) return;
        fetch(`/api/subdistricts/${subdistrictId}/postal-code`)
            .then(res => res.json())
            .then(data => {
                document.getElementById('postal_code_input').value = data.postal_code || '';
            });
    }

    function submitEditForm() {
        const form = document.getElementById('editForm');
        const formData = new FormData(form);
        const buildingId = {{ $building->id }};
        
        // Convert FormData to JSON
        const data = {};
        formData.forEach((value, key) => {
            if (key === 'status_update') {
                data[key] = form.elements['status_update'].checked ? 1 : 0;
            } else {
                data[key] = value;
            }
        });

        const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        Swal.fire({
            title: 'Updating...',
            text: 'Please wait while we save your changes',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        fetch(`/operational/buildings/${buildingId}`, {
            method: 'POST',
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
                    title: 'Success!',
                    text: 'Building has been updated.',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            } else {
                let errorMessage = result.message || 'Something went wrong!';
                
                // Construct detailed error message from validation errors if available
                if (result.errors) {
                    errorMessage += '<div style="text-align: left; margin-top: 10px; font-size: 0.9em; color: #dc2626;"><ul>';
                    for (const [field, messages] of Object.entries(result.errors)) {
                        messages.forEach(msg => {
                            errorMessage += `<li>• ${msg}</li>`;
                        });
                    }
                    errorMessage += '</ul></div>';
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    html: errorMessage
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'System Error',
                text: 'Failed to update building. Please check console for details.'
            });
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
