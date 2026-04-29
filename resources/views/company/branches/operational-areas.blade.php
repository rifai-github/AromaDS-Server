@extends('layouts.app')

@section('title', 'Operational Areas - ' . $branch->name)
@section('breadcrumb', 'Home / Company / Branches / ' . $branch->name . ' / Operational Areas')

@section('content')
<style>
    /* Global styles */
    html, body {
        overflow-x: hidden;
        max-width: 100vw;
    }

    *, *::before, *::after {
        box-sizing: border-box;
    }

    /* Header Card */
    .header-card {
        background: linear-gradient(135deg, #214589 0%, #1e3a8a 100%);
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
        color: white;
        box-shadow: 0 4px 15px rgba(33, 69, 137, 0.3);
    }

    .header-card h2 {
        margin: 0 0 8px 0;
        font-size: 24px;
        font-weight: 600;
    }

    .header-card .branch-info {
        display: flex;
        gap: 24px;
        flex-wrap: wrap;
        margin-bottom: 16px;
    }

    .header-card .info-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        opacity: 0.9;
    }

    .header-card .info-item i {
        opacity: 0.8;
    }

    .description-input {
        width: 100%;
        padding: 12px;
        border: 1px solid rgba(255,255,255,0.3);
        border-radius: 8px;
        background: rgba(255,255,255,0.1);
        color: white;
        font-size: 14px;
        resize: vertical;
        min-height: 60px;
    }

    .description-input::placeholder {
        color: rgba(255,255,255,0.6);
    }

    .description-input:focus {
        outline: none;
        border-color: rgba(255,255,255,0.5);
        background: rgba(255,255,255,0.15);
    }

    /* Card Styles */
    .area-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        margin-bottom: 24px;
        overflow: hidden;
    }

    .area-card-header {
        background: #f8fafc;
        padding: 16px 20px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
    }

    .area-card-header h4 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .area-card-header h4 .badge {
        font-size: 12px;
        padding: 4px 8px;
        border-radius: 12px;
        background: #214589;
        color: white;
    }

    .area-card-body {
        padding: 20px;
    }

    /* Button Groups */
    .btn-group-actions {
        display: flex;
        gap: 8px;
    }

    .btn {
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
    }

    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
    }

    .btn-primary {
        background-color: #214589;
        color: white;
    }

    .btn-primary:hover {
        background-color: #1e3a8a;
    }

    .btn-secondary {
        background-color: #f1f5f9;
        color: #64748b;
        border: 1px solid #e2e8f0;
    }

    .btn-secondary:hover {
        background-color: #e2e8f0;
        color: #475569;
    }

    .btn-success {
        background-color: #16a34a;
        color: white;
    }

    .btn-success:hover {
        background-color: #15803d;
    }

    .btn-outline-primary {
        background: transparent;
        color: #214589;
        border: 1px solid #214589;
    }

    .btn-outline-primary:hover {
        background: #214589;
        color: white;
    }

    .btn-outline-secondary {
        background: transparent;
        color: #64748b;
        border: 1px solid #d1d5db;
    }

    .btn-outline-secondary:hover {
        background: #f1f5f9;
    }

    /* City Grid */
    .city-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 12px;
        max-height: 400px;
        overflow-y: auto;
        padding: 4px;
    }

    .city-item {
        display: flex;
        align-items: center;
        padding: 12px 16px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        transition: all 0.2s ease;
        cursor: pointer;
    }

    .city-item:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
    }

    .city-item.checked {
        background: #eff6ff;
        border-color: #214589;
    }

    .city-item.disabled {
        background: #f1f5f9;
        opacity: 0.6;
        cursor: not-allowed;
    }

    .city-item input[type="checkbox"] {
        margin-right: 12px;
        width: 18px;
        height: 18px;
        accent-color: #214589;
    }

    .city-item label {
        margin: 0;
        font-size: 14px;
        color: #334155;
        cursor: pointer;
        flex: 1;
    }

    .city-item.disabled label {
        cursor: not-allowed;
        color: #94a3b8;
    }

    .city-item .assigned-badge {
        font-size: 10px;
        padding: 2px 6px;
        background: #fee2e2;
        color: #dc2626;
        border-radius: 4px;
        margin-left: 8px;
    }

    .province-city-section {
        margin-bottom: 18px;
    }

    .province-city-section:last-child {
        margin-bottom: 0;
    }

    .province-city-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 600;
        color: #214589;
        margin-bottom: 10px;
    }

    /* Province Dropdown for Card 2 */
    .province-selector {
        margin-bottom: 16px;
    }

    .province-selector select {
        width: 100%;
        max-width: 400px;
        padding: 10px 14px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        background: white;
    }

    .province-selector select:focus {
        outline: none;
        border-color: #214589;
        box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #64748b;
    }

    .empty-state i {
        font-size: 48px;
        color: #cbd5e1;
        margin-bottom: 16px;
    }

    .empty-state h5 {
        font-size: 16px;
        font-weight: 600;
        color: #475569;
        margin-bottom: 8px;
    }

    .empty-state p {
        font-size: 14px;
    }

    /* Loading State */
    .loading-overlay {
        display: none;
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255,255,255,0.8);
        align-items: center;
        justify-content: center;
        z-index: 10;
    }

    .loading-overlay.show {
        display: flex;
    }

    .spinner {
        width: 40px;
        height: 40px;
        border: 3px solid #e2e8f0;
        border-top-color: #214589;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    /* Footer Actions */
    .form-footer {
        position: sticky;
        bottom: 0;
        background: white;
        padding: 16px 24px;
        border-top: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        box-shadow: 0 -4px 12px rgba(0,0,0,0.05);
        border-radius: 0 0 12px 12px;
        margin-top: 24px;
    }

    .form-footer .summary {
        font-size: 14px;
        color: #64748b;
    }

    .form-footer .summary strong {
        color: #214589;
    }

    /* Alert Styles */
    .alert {
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .alert-success {
        background: #dcfce7;
        color: #166534;
        border: 1px solid #bbf7d0;
    }

    .alert-danger {
        background: #fee2e2;
        color: #dc2626;
        border: 1px solid #fecaca;
    }

    .alert-info {
        background: #dbeafe;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .header-card .branch-info {
            flex-direction: column;
            gap: 8px;
        }

        .city-grid {
            grid-template-columns: 1fr;
        }

        .form-footer {
            flex-direction: column;
            text-align: center;
        }
    }
</style>

<div class="container-fluid">
    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            {{ session('error') }}
        </div>
    @endif

    <form id="operationalAreasForm" method="POST" action="{{ route('company.branches.operational-areas.sync', $branch) }}">
        @csrf

        {{-- Header Card --}}
        <div class="header-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="mb-0"><i class="fas fa-map-marked-alt me-2"></i> Operational Areas</h2>
                <a href="{{ route('company.branches.index') }}" class="btn btn-light" style="background: white; color: #214589; font-weight: 700; box-shadow: 0 2px 4px rgba(0,0,0,0.2); padding: 8px 16px;">
                    <i class="fas fa-arrow-left me-1"></i> Back to List
                </a>
            </div>
            <div class="branch-info">
                <div class="info-item">
                    <i class="fas fa-building"></i>
                    <span><strong>Branch:</strong> {{ $branch->name }}</span>
                </div>
                <div class="info-item">
                    <i class="fas fa-map"></i>
                    <span><strong>Province:</strong> {{ $branch->province->name ?? 'Belum di-set' }}</span>
                </div>
                <div class="info-item">
                    <i class="fas fa-city"></i>
                    <span><strong>City:</strong> {{ $branch->city->name ?? 'Belum di-set' }}</span>
                </div>
            </div>
            <div class="mt-3">
                <label class="d-block mb-2" style="font-size: 14px; opacity: 0.9;">
                    <i class="fas fa-sticky-note me-1"></i> Description (Catatan untuk operational area)
                </label>
                <textarea name="description" class="description-input" placeholder="Tambahkan catatan atau deskripsi untuk operational area branch ini...">{{ $branchDescription }}</textarea>
            </div>
        </div>

        {{-- Card 1: Cities in Branch Province --}}
        <div class="area-card">
            <div class="area-card-header">
                <h4>
                    <i class="fas fa-map-marker-alt text-primary"></i>
                    Kota dalam Provinsi {{ $branch->province->name ?? '' }}
                    <span class="badge" id="card1Count">{{ count($assignedCityIds) }}</span>
                </h4>
                <div class="btn-group-actions">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="checkAllCard1()">
                        <i class="fas fa-check-double"></i> Check All
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="uncheckAllCard1()">
                        <i class="fas fa-times"></i> Uncheck All
                    </button>
                </div>
            </div>
            <div class="area-card-body">
                @if($branch->province_id && count($citiesInProvince) > 0)
                    <div class="city-grid" id="card1CityGrid">
                        @foreach($citiesInProvince as $city)
                            @php
                                $isAssigned = in_array($city->id, $assignedCityIds);
                                $isAssignedToOther = in_array($city->id, $assignedToOtherBranches);
                            @endphp
                            <div class="city-item {{ $isAssigned ? 'checked' : '' }} {{ $isAssignedToOther ? 'disabled' : '' }}">
                                <input type="checkbox" 
                                       name="city_ids[]" 
                                       value="{{ $city->id }}" 
                                       id="city_{{ $city->id }}"
                                       class="city-checkbox card1-checkbox"
                                       {{ $isAssigned ? 'checked' : '' }}
                                       {{ $isAssignedToOther ? 'disabled' : '' }}
                                       onchange="updateCityItemStyle(this)">
                                <label for="city_{{ $city->id }}">{{ $city->name }}</label>
                                @if($isAssignedToOther)
                                    <span class="assigned-badge">Assigned</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h5>Province belum di-set</h5>
                        <p>Silakan set province untuk branch ini terlebih dahulu di halaman edit branch.</p>
                        <a href="{{ route('company.branches.index', ['edit_branch' => $branch->id]) }}" class="btn btn-primary mt-3">
                            <i class="fas fa-edit"></i> Edit Branch
                        </a>
                    </div>
                @endif
            </div>
        </div>

        {{-- Card 2: Cities Outside Branch Province --}}
        <div class="area-card">
            <div class="area-card-header">
                <h4>
                    <i class="fas fa-globe text-info"></i>
                    Kota di Luar Provinsi {{ $branch->province->name ?? '' }}
                    <span class="badge bg-info" id="card2Count">{{ count($citiesOutsideProvince) }}</span>
                </h4>
                <div class="btn-group-actions">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="checkAllCard2()">
                        <i class="fas fa-check-double"></i> Check All
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="uncheckAllCard2()">
                        <i class="fas fa-times"></i> Uncheck All
                    </button>
                </div>
            </div>
            <div class="area-card-body" style="position: relative;">
                <div class="loading-overlay" id="card2Loading">
                    <div class="spinner"></div>
                </div>
                
                <div class="province-selector">
                    <label class="form-label mb-2">
                        <i class="fas fa-search-location me-1"></i> Pilih Provinsi untuk menampilkan kota
                    </label>
                    <select id="card2ProvinceSelect" onchange="loadCitiesForCard2(this.value)">
                        <option value="">-- Pilih Provinsi --</option>
                        @foreach($otherProvinces as $province)
                            <option value="{{ $province->id }}">{{ $province->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div id="card2CityGrid">
                    {{-- Cities will be loaded dynamically --}}
                    @if(count($citiesOutsideProvince) > 0)
                        @foreach($citiesOutsideProvince->groupBy('province_id') as $provinceId => $areas)
                            <div class="province-city-section" data-province-id="{{ $provinceId }}">
                                <div class="province-city-title">
                                    <i class="fas fa-map"></i>
                                    {{ $areas->first()->provinceRelation->name ?? $areas->first()->province ?? 'Provinsi Lain' }}
                                </div>
                                <div class="city-grid">
                                    @foreach($areas as $area)
                                        <div class="city-item checked">
                                            <input type="checkbox" 
                                                   name="city_ids[]" 
                                                   value="{{ $area->city_id }}" 
                                                   id="city_outside_{{ $area->city_id }}"
                                                   class="city-checkbox card2-checkbox"
                                                   checked
                                                   onchange="updateCityItemStyle(this)">
                                            <label for="city_outside_{{ $area->city_id }}">
                                                {{ $area->cityRelation->name ?? $area->city }}
                                                <small class="text-muted">({{ $area->provinceRelation->name ?? $area->province }})</small>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="empty-state" id="card2EmptyState">
                            <i class="fas fa-map"></i>
                            <h5>Belum ada kota dari provinsi lain</h5>
                            <p>Pilih provinsi di atas untuk menambahkan kota dari luar provinsi branch.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Form Footer --}}
        <div class="form-footer">
            <div class="summary">
                <i class="fas fa-info-circle me-1"></i>
                Total kota dipilih: <strong id="totalSelected">{{ count($assignedCityIds) }}</strong>
            </div>
            <div class="btn-group-actions">
                <a href="{{ route('company.branches.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Simpan Perubahan
                </button>
            </div>
        </div>
    </form>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    @if(session('swal_error'))
        Swal.fire({
            icon: 'error',
            title: 'Validasi Gagal',
            text: {!! json_encode(session('swal_error')) !!},
            confirmButtonColor: '#214589',
            confirmButtonText: 'OK'
        });
    @endif

    updateTotalCount();
});

// Update city item visual style based on checkbox state
function updateCityItemStyle(checkbox) {
    const cityItem = checkbox.closest('.city-item');
    if (checkbox.checked) {
        cityItem.classList.add('checked');
    } else {
        cityItem.classList.remove('checked');
    }
    updateTotalCount();
}

// Update total selected count
function updateTotalCount() {
    const allChecked = document.querySelectorAll('.city-checkbox:checked:not(:disabled)');
    document.getElementById('totalSelected').textContent = allChecked.length;
    
    // Update Card 1 count
    const card1Checked = document.querySelectorAll('.card1-checkbox:checked:not(:disabled)');
    const card1CountEl = document.getElementById('card1Count');
    if (card1CountEl) card1CountEl.textContent = card1Checked.length;
    
    // Update Card 2 count
    const card2Checked = document.querySelectorAll('.card2-checkbox:checked:not(:disabled)');
    const card2CountEl = document.getElementById('card2Count');
    if (card2CountEl) card2CountEl.textContent = card2Checked.length;
}

// Card 1: Check all
function checkAllCard1() {
    const checkboxes = document.querySelectorAll('.card1-checkbox:not(:disabled)');
    checkboxes.forEach(cb => {
        cb.checked = true;
        updateCityItemStyle(cb);
    });
    updateTotalCount();
}

// Card 1: Uncheck all
function uncheckAllCard1() {
    const checkboxes = document.querySelectorAll('.card1-checkbox:not(:disabled)');
    checkboxes.forEach(cb => {
        cb.checked = false;
        updateCityItemStyle(cb);
    });
    updateTotalCount();
}

// Card 2: Check all
function checkAllCard2() {
    const checkboxes = document.querySelectorAll('.card2-checkbox:not(:disabled)');
    checkboxes.forEach(cb => {
        cb.checked = true;
        updateCityItemStyle(cb);
    });
    updateTotalCount();
}

// Card 2: Uncheck all
function uncheckAllCard2() {
    const checkboxes = document.querySelectorAll('.card2-checkbox:not(:disabled)');
    checkboxes.forEach(cb => {
        cb.checked = false;
        updateCityItemStyle(cb);
    });
    updateTotalCount();
}

// Load cities for Card 2 based on selected province
function loadCitiesForCard2(provinceId) {
    const grid = document.getElementById('card2CityGrid');
    const loading = document.getElementById('card2Loading');
    
    if (!provinceId) {
        return;
    }

    const existingSection = grid.querySelector(`[data-province-id="${provinceId}"]`);
    if (existingSection) {
        existingSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        return;
    }
    
    // Show loading
    loading.classList.add('show');
    
    // Fetch cities from API
    fetch(`{{ route('company.branches.operational-areas.cities', $branch) }}?province_id=${provinceId}`)
        .then(response => response.json())
        .then(result => {
            loading.classList.remove('show');
            
            if (result.status === 'success' && result.data.length > 0) {
                const emptyState = document.getElementById('card2EmptyState');
                if (emptyState) {
                    emptyState.remove();
                }

                let html = `
                    <div class="province-city-section" data-province-id="${result.province_id || provinceId}">
                        <div class="province-city-title">
                            <i class="fas fa-map"></i>
                            ${result.province_name || 'Provinsi Lain'}
                        </div>
                        <div class="city-grid">
                `;
                result.data.forEach(city => {
                    const alreadyRendered = grid.querySelector(`.card2-checkbox[value="${city.id}"]`);
                    const checkedAttr = city.checked || alreadyRendered ? 'checked' : '';
                    const disabledAttr = city.disabled ? 'disabled' : '';
                    const checkedClass = city.checked || alreadyRendered ? 'checked' : '';
                    const disabledClass = city.disabled ? 'disabled' : '';
                    
                    html += `
                        <div class="city-item ${checkedClass} ${disabledClass}">
                            <input type="checkbox" 
                                   name="city_ids[]" 
                                   value="${city.id}" 
                                   id="city_card2_${city.id}"
                                   class="city-checkbox card2-checkbox"
                                   ${checkedAttr}
                                   ${disabledAttr}
                                   onchange="updateCityItemStyle(this)">
                            <label for="city_card2_${city.id}">${city.name}</label>
                            ${city.assigned_to_other ? '<span class="assigned-badge">Assigned</span>' : ''}
                        </div>
                    `;
                });
                html += `
                        </div>
                    </div>
                `;
                grid.insertAdjacentHTML('beforeend', html);
            } else {
                const emptyState = document.getElementById('card2EmptyState');
                if (emptyState) {
                    emptyState.remove();
                }

                grid.insertAdjacentHTML('beforeend', `
                    <div class="empty-state">
                        <i class="fas fa-city"></i>
                        <h5>Tidak ada kota tersedia</h5>
                        <p>Tidak ada kota tersedia untuk provinsi yang dipilih.</p>
                    </div>
                `);
            }
            
            updateTotalCount();
        })
        .catch(error => {
            loading.classList.remove('show');
            console.error('Error loading cities:', error);
            grid.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-exclamation-triangle text-danger"></i>
                    <h5>Terjadi kesalahan</h5>
                    <p>Gagal memuat daftar kota. Silakan coba lagi.</p>
                </div>
            `;
        });
}
</script>
@endpush
