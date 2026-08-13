@extends('layouts.app')

@section('title', 'Master Rental Details')
@section('breadcrumb', 'Home / Warehouse / Master Rentals / ' . $masterRental->rental_name)

@section('content')
<style>
    /* Global styles */
    .btn {
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
    }

    .btn-primary {
        background-color: #214589;
        color: white;
    }

    .btn-primary:hover {
        background-color: #1e3a8a;
        color: white;
    }

    .btn-secondary {
        background-color: #f3f4f6;
        color: #6b7280;
        border: 1px solid #d1d5db;
    }

    .btn-secondary:hover {
        background-color: #214589;
        color: white;
        border-color: #214589;
    }

    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
    }

    .btn-danger {
        background-color: #ef4444;
        color: white;
    }

    .btn-danger:hover {
        background-color: #dc2626;
    }

    /* Tabs */
    .tabs-container {
        margin-bottom: 20px;
        border-bottom: 2px solid #e5e7eb;
    }

    .tabs {
        display: flex;
        gap: 10px;
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .tab-item {
        padding: 12px 24px;
        cursor: pointer;
        border-bottom: 3px solid transparent;
        font-weight: 500;
        color: #6b7280;
        transition: all 0.2s ease;
        margin-bottom: -2px;
    }

    .tab-item:hover {
        color: #214589;
        background-color: #f9fafb;
    }

    .tab-item.active {
        color: #214589;
        border-bottom-color: #214589;
        background-color: #eff6ff;
    }

    .tab-content {
        display: none;
        animation: fadeIn 0.3s ease-in;
    }

    .tab-content.active {
        display: block;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    /* Table */
    .table-container {
        background: white;
        border-radius: 8px;
        overflow: visible;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        margin-top: 20px;
    }

    .table-scroll-shell {
        overflow-x: auto;
        overflow-y: auto;
        max-height: min(68vh, 820px);
        border-radius: 8px;
    }

    .table-scroll-shell .responsive-table {
        min-width: 960px;
    }

    /* Header row sticky */
    .table-scroll-shell thead tr:first-child th {
        position: sticky;
        top: 0;
        z-index: 3;
    }

    /* Filter row sticky — tepat di bawah header (44px = tinggi header) */
    .table-scroll-shell thead tr.filter-row th {
        position: sticky;
        top: 44px !important; /* override JS-calculated top */
        z-index: 2 !important;
        background-color: white !important;
        padding: 4px !important;
    }

    #detailsTable thead th,
    #pricesTable thead th,
    #lostUnitPricesTable thead th {
        position: static !important;
        top: auto !important;
        z-index: auto !important;
    }

    .responsive-table {
        width: 100%;
        border-collapse: collapse;
    }

    .responsive-table th,
    .responsive-table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #e5e7eb;
    }

    .responsive-table th {
        background-color: #214589;
        color: white;
        font-weight: 600;
        font-size: 13px;
        vertical-align: top;
    }

    .responsive-table tbody tr:hover {
        background-color: #eff6ff;
    }

    /* Modal */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        backdrop-filter: blur(2px);
        align-items: center;
        justify-content: center;
    }

    .modal-overlay.show {
        display: flex;
    }

    .modal-container {
        background: white;
        border-radius: 12px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        max-width: 90vw;
        max-height: 90vh;
        width: 600px;
        overflow: visible; /* Changed from hidden to avoid clipping body scroll */
    }

    .modal-header {
        background: #214589;
        color: white;
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-title {
        font-size: 18px;
        font-weight: 600;
        margin: 0;
    }

    .modal-close {
        background: none;
        border: none;
        color: white;
        font-size: 24px;
        cursor: pointer;
        padding: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: background-color 0.2s ease;
    }

    .modal-close:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }

    .modal-body {
        padding: 20px;
        overflow-y: auto;
        max-height: calc(90vh - 140px);
    }

    .modal-footer {
        padding: 20px;
        background: #f9fafb;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    /* Form */
    .form-group {
        margin-bottom: 16px;
    }

    .form-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #1f2937;
        font-size: 14px;
    }

    .form-input,
    .form-select {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.2s ease;
    }

    .form-input:focus,
    .form-select:focus {
        outline: none;
        border-color: #214589;
        box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
    }

    /* Card */
    .info-card {
        background: white;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
        overflow: hidden;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }

    .info-item {
        padding: 12px 0;
        border-bottom: 1px solid #f3f4f6;
    }

    .info-item:last-child {
        border-bottom: none;
    }

    .info-label {
        font-size: 12px;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }

    .info-value {
        font-size: 16px;
        color: #1f2937;
        font-weight: 500;
    }

    /* Badge */
    .badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
    }

    .badge-success {
        background-color: #d1fae5;
        color: #065f46;
    }

    .badge-danger {
        background-color: #fee2e2;
        color: #991b1b;
    }

    .badge-info {
        background-color: #dbeafe;
        color: #1e40af;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #6b7280;
    }

    .empty-state i {
        font-size: 48px;
        margin-bottom: 16px;
        opacity: 0.5;
    }

    .empty-state p {
        font-size: 16px;
        margin-bottom: 20px;
    }

    .product-stack {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .product-stack small {
        color: #6b7280;
        font-size: 12px;
    }
</style>

<div class="flex flex-col w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">
        
        <!-- Header Section -->
        <div class="info-card" style="width: 100%;">
            <div class="flex justify-between items-center mb-4">
                <div class="flex items-center gap-6">
                    <a href="{{ route('warehouse.master-rentals.index') }}" class="btn btn-secondary mr-2">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                    <h1 class="text-2xl font-bold text-[#214589]">
                        {{ $masterRental->rental_code }} - {{ $masterRental->rental_name }}
                    </h1>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('warehouse.rental-management.bottom-prices', $masterRental->id) }}" class="btn btn-secondary">
                        <i class="fas fa-tags"></i> Bottom Prices
                    </a>
                    <button class="btn btn-primary" onclick="openEditModal()">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                </div>
            </div>

            <!-- Basic Info Grid -->
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Rental Type</div>
                    <div class="info-value">
                        {{ $masterRental->rental_type_text }}
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Category</div>
                    <div class="info-value">
                        <span class="badge badge-info">{{ strtoupper($masterRental->category) }}</span>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Service Frequency</div>
                    <div class="info-value">
                        {{ $masterRental->serviceFrequency ? $masterRental->serviceFrequency->name . ' (' . $masterRental->serviceFrequency->frequency_months . ' months)' : '-' }}
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Daily Price</div>
                    <div class="info-value">
                        {{ $masterRental->daily_price ? 'Rp ' . number_format($masterRental->daily_price, 0, ',', '.') : '-' }}
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Monthly Price</div>
                    <div class="info-value">
                        {{ $masterRental->monthly_price ? 'Rp ' . number_format($masterRental->monthly_price, 0, ',', '.') : '-' }}
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Lost Unit Price</div>
                    <div class="info-value">
                        {{ $masterRental->lost_unit_price ? 'Rp ' . number_format($masterRental->lost_unit_price, 0, ',', '.') : '-' }}
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Durasi Install</div>
                    <div class="info-value">
                        {{ $masterRental->install_duration ? $masterRental->install_duration . ' menit' : '-' }}
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Durasi Service</div>
                    <div class="info-value">
                        {{ $masterRental->service_duration ? $masterRental->service_duration . ' menit' : '-' }}
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Status</div>
                    <div class="info-value">
                        <span class="badge {{ $masterRental->is_active ? 'badge-success' : 'badge-danger' }}">
                            {{ $masterRental->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Last Updated</div>
                    <div class="info-value">
                        {{ $masterRental->updated_at ? $masterRental->updated_at->format('d/M/Y H:i') : '-' }}
                        <small class="text-gray-500">by {{ $masterRental->updatedBy->name ?? $masterRental->createdBy->name ?? '-' }}</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs Section -->
        <div class="info-card" style="width: 100%;">
            <div class="tabs-container">
                <ul class="tabs">
                    <li class="tab-item active" onclick="switchTab(event, 'basicInfo')">
                        <i class="fas fa-info-circle"></i> Basic Info
                    </li>
                    <li class="tab-item" onclick="switchTab(event, 'rentalPrice')">
                        <i class="fas fa-dollar-sign"></i> Rental Price
                    </li>
                    <li class="tab-item" onclick="switchTab(event, 'lostUnitPrice')">
                        <i class="fas fa-exclamation-triangle"></i> Lost Unit Price
                    </li>
                </ul>
            </div>

            <!-- Basic Info Tab Content -->
            <div id="basicInfo" class="tab-content active">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="text-lg font-semibold">Rental Details</h3>
                    <button class="btn btn-primary btn-sm" onclick="openAddDetailModal()">
                        <i class="fas fa-plus"></i> Add New Product
                    </button>
                </div>

                <div class="table-container">
                    <div class="table-scroll-shell">
                        <table class="responsive-table" id="detailsTable">
                            <thead>
                                <tr>
                                    <th data-column="productCategory.name">Material Type</th>
                                    <th data-column="service_frequency_multiplier" data-type="numeric" title="Interval penggantian material dalam jumlah service (bukan bulan). Contoh: 6 = diganti tiap 6 kali service. 0 = unit permanen, tidak pernah diganti.">Frequency <i class="fas fa-info-circle text-gray-400" style="font-size: 11px;"></i></th>
                                    <!-- <th data-column="quantity" data-type="numeric">Quantity</th> -->
                                    <th data-column="masterProduct.name">Product</th>
                                    <!-- <th>Package Size</th> -->
                                    <th data-column="bom_rental_qty" data-type="numeric">BOM Rental Qty</th>
                                    <th data-column="updated_at" data-type="date">Last Updated</th>
                                    <th data-column="updater.name">Updated By</th>
                                    <th data-no-filter>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($masterRental->rentalDetails as $detail)
                                @php
                                    $allowedProducts = $detail->allowedProducts
                                        ->map(function ($product) {
                                            return [
                                                'name' => $product->name,
                                                'sku' => $product->sku,
                                            ];
                                        })
                                        ->filter(fn ($product) => filled($product['name']))
                                        ->unique(fn ($product) => strtolower(trim($product['name'] . '|' . ($product['sku'] ?? ''))))
                                        ->values();
                                    $visibleAllowedProducts = $allowedProducts->take(3);
                                    $remainingAllowedProducts = max(0, $allowedProducts->count() - $visibleAllowedProducts->count());
                                @endphp
                                <tr onclick="editDetail({{ $detail->id }})" style="cursor: pointer;" title="Click to edit">
                                    <td>{{ $detail->productCategory->name ?? '-' }}</td>
                                    <td>{{ $detail->service_frequency_multiplier }}x</td>
                                    <!-- <td>{{ $detail->quantity }}</td> -->
                                    <td>
                                        @if($detail->masterProduct)
                                            <div class="product-stack">
                                                <span>{{ $detail->masterProduct->name }}</span>
                                                @if($detail->masterProduct->sku)
                                                    <small>{{ $detail->masterProduct->sku }}</small>
                                                @endif
                                                @if($allowedProducts->count() > 1)
                                                    <small style="color:#9ca3af;">+{{ $allowedProducts->count() - 1 }} opsi material lain</small>
                                                @endif
                                            </div>
                                        @elseif($allowedProducts->isNotEmpty())
                                            <div class="product-stack">
                                                @foreach($visibleAllowedProducts as $product)
                                                    <span>{{ $product['name'] }}</span>
                                                    @if(!empty($product['sku']))
                                                        <small>{{ $product['sku'] }}</small>
                                                    @endif
                                                @endforeach
                                                @if($remainingAllowedProducts > 0)
                                                    <small style="color:#9ca3af;">+{{ $remainingAllowedProducts }} product lain</small>
                                                @endif
                                                <small style="color:#9ca3af;">Material list aktif untuk category ini. Klik untuk lihat &amp; edit.</small>
                                            </div>
                                        @elseif($detail->productType)
                                            <div class="product-stack">
                                                <span>{{ $detail->productType->name }}</span>
                                                <small>Belum ada product tunggal, masih by product type</small>
                                            </div>
                                        @elseif($detail->productCategory)
                                            <div class="product-stack">
                                                <span>{{ $detail->productCategory->name }}</span>
                                                <small>Belum ada material list yang terhubung</small>
                                            </div>
                                        @else
                                            <span class="text-gray-400 italic">-</span>
                                        @endif
                                    </td>
                                    <!-- <td>
                                        @if($detail->masterProduct && $detail->masterProduct->packagingSize)
                                            {{ $detail->masterProduct->packagingSize->name }}
                                        @else
                                            -
                                        @endif
                                    </td> -->
                                    <td>
                                        {{ number_format($detail->bom_rental_qty ?? 0, 0) }}
                                    </td>
                                    <td>{{ $detail->updated_at ? $detail->updated_at->format('d/M/Y H:i') : '-' }}</td>
                                    <td>{{ $detail->updater->name ?? $detail->creator->name ?? '-' }}</td>
                                    <td onclick="event.stopPropagation()">
                                        <div class="flex gap-4">
                                            <button class="btn btn-secondary btn-sm" onclick="openMaterialList({{ $detail->id }})" title="Daftar Material">
                                                <i class="fas fa-book"></i>
                                            </button>
                                            <button class="btn btn-danger btn-sm" onclick="deleteDetail({{ $detail->id }})" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7">
                                        <div class="empty-state">
                                            <i class="fas fa-box-open"></i>
                                            <p>No rental details found</p>
                                            <button class="btn btn-primary btn-sm" onclick="openAddDetailModal()">
                                                <i class="fas fa-plus"></i> Add First Detail
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Rental Price Tab Content -->
            <div id="rentalPrice" class="tab-content">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="text-lg font-semibold">Branch Pricing</h3>
                </div>

                <div class="table-container">
                    <div class="table-scroll-shell">
                    <table class="responsive-table" id="pricesTable">
                        <thead>
                            <tr>
                                <th data-column="branch.code">Branch Code</th>
                                <th data-column="branch.name">Branch Name</th>
                                <th data-column="daily_price" data-type="numeric">Daily Price</th>
                                <th data-column="monthly_price" data-type="numeric">Monthly Price</th>
                                <th data-column="updated_at" data-type="date">Last Updated</th>
                                <th data-column="updater.name">Updated By</th>
                                <th data-no-filter>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($branches as $branch)
                            @php
                                $price = $masterRental->rentalPrices->firstWhere('branch_id', $branch->id);
                            @endphp
                            <tr>
                                <td>{{ $branch->code }}</td>
                                <td>{{ $branch->name }}</td>
                                <td>
                                    <input type="number" 
                                           class="form-input" 
                                           id="daily_price_{{ $branch->id }}" 
                                           value="{{ $price ? $price->daily_price : $masterRental->daily_price }}" 
                                           placeholder="0" 
                                           step="0.01"
                                           style="width: 150px;">
                                </td>
                                <td>
                                    <input type="number" 
                                           class="form-input" 
                                           id="monthly_price_{{ $branch->id }}" 
                                           value="{{ $price ? $price->monthly_price : $masterRental->monthly_price }}" 
                                           placeholder="0" 
                                           step="0.01"
                                           style="width: 150px;">
                                </td>
                                <td>{{ $price && $price->updated_at ? $price->updated_at->format('d/M/Y H:i') : '-' }}</td>
                                <td>{{ $price && $price->updater ? $price->updater->name : ($price && $price->creator ? $price->creator->name : '-') }}</td>
                                <td>
                                    @if($price)
                                        <button class="btn btn-primary btn-sm" onclick="updatePrice({{ $branch->id }}, {{ $price->id }})">
                                            <i class="fas fa-save"></i> Perbarui
                                        </button>
                                    @else
                                        <button class="btn btn-primary btn-sm" onclick="savePrice({{ $branch->id }})">
                                            <i class="fas fa-save"></i> Simpan
                                        </button>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>

            <!-- Lost Unit Price Tab Content -->
            <div id="lostUnitPrice" class="tab-content">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="text-lg font-semibold">Lost Unit Pricing per Branch</h3>
                </div>

                <div class="table-container">
                    <div class="table-scroll-shell">
                    <table class="responsive-table" id="lostUnitPricesTable">
                        <thead>
                            <tr>
                                <th data-column="branch.code">Branch Code</th>
                                <th data-column="branch.name">Branch Name</th>
                                <th data-column="lost_unit_price" data-type="numeric">Lost Unit Price</th>
                                <th data-column="updated_at" data-type="date">Last Updated</th>
                                <th data-column="updater.name">Updated By</th>
                                <th data-no-filter>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($branches as $branch)
                            @php
                                $price = $masterRental->rentalPrices->firstWhere('branch_id', $branch->id);
                            @endphp
                            <tr>
                                <td>{{ $branch->code }}</td>
                                <td>{{ $branch->name }}</td>
                                <td>
                                    <input type="number" 
                                           class="form-input" 
                                           id="lost_unit_price_{{ $branch->id }}" 
                                           value="{{ $price ? $price->lost_unit_price : $masterRental->lost_unit_price }}" 
                                           placeholder="0" 
                                           step="0.01"
                                           style="width: 200px;">
                                </td>
                                <td>{{ $price && $price->updated_at ? $price->updated_at->format('d/M/Y H:i') : '-' }}</td>
                                <td>{{ $price && $price->updater ? $price->updater->name : ($price && $price->creator ? $price->creator->name : '-') }}</td>
                                <td>
                                    @if($price)
                                        <button class="btn btn-primary btn-sm" onclick="updateLostUnitPrice({{ $branch->id }}, {{ $price->id }})">
                                            <i class="fas fa-save"></i> Perbarui
                                        </button>
                                    @else
                                        <button class="btn btn-primary btn-sm" onclick="saveLostUnitPrice({{ $branch->id }})">
                                            <i class="fas fa-save"></i> Simpan
                                        </button>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Detail Modal -->
<div id="detailModal" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header">
            <h2 class="modal-title" id="detailModalTitle">Tambah Detail Rental</h2>
            <button class="modal-close" onclick="closeDetailModal()">
                <i class="fas fa-times"></i>
            </button>
                </div>
        <form id="detailForm" onsubmit="submitDetail(event)" novalidate>
            <div class="modal-body">
                <input type="hidden" id="detail_id" name="detail_id">
                
                <div class="form-group">
                    <label class="form-label">Material Type <span class="text-red-500">*</span></label>
                    <select class="form-select" id="product_category_id" name="product_category_id" required>
                        <option value="">Select Material Type</option>
                        @foreach($productCategories as $category)
                            <option value="{{ $category->id }}" data-is-unit="{{ $category->is_unit ? 'true' : 'false' }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Frequency (tiap N service) <span class="text-red-500">*</span></label>
                    <input type="number" class="form-input" id="service_frequency_multiplier" name="service_frequency_multiplier" value="1" required>
                    <small class="text-gray-500">Material diganti tiap N kali service (bukan bulan). 0 = unit permanen (tidak pernah diganti).</small>
                </div>

                <!-- Quantity hidden field, default to 1 -->
                <input type="hidden" id="quantity" name="quantity" value="1">
                <input type="hidden" id="auto_expand" name="auto_expand" value="0">

                <div class="form-group">
                    <label class="form-label">BOM Rental Qty <span class="text-red-500">*</span></label>
                    <input type="number" class="form-input" id="bom_rental_qty" name="bom_rental_qty" step="0.01" min="0" value="0" required>
                    <small class="text-gray-500">Manual input for BOM Qty requirement</small>
                </div>

                <div class="form-group" id="product_selection_group">
                    <label class="form-label">Product(s)</label>
                    <div id="single_product_container" style="display: none;">
                        <select class="form-select select2-basic" id="master_product_id" name="master_product_id">
                            <option value="">Select Product (Optional)</option>
                        </select>
                    </div>
                    <div id="multi_product_container">
                        <select class="form-select select2-multiple" id="master_product_ids" name="master_product_ids[]" multiple="multiple" style="width: 100%;">
                            <!-- Will be populated dynamically -->
                        </select>
                        <div class="mt-2 flex gap-2">
                            <button type="button" class="btn btn-secondary btn-sm" onclick="selectAllFromCategory()">
                                <i class="fas fa-check-double"></i> Select All in Category
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="clearProductSelection()">
                                <i class="fas fa-times"></i> Clear
                            </button>
                        </div>
                    </div>
                    <small class="text-gray-500">Semua category support multiple products selection. Pilih semua product yang ingin dipakai untuk BOM rental ini.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeDetailModal()">Batal</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Master Rental Modal -->
<div id="editMasterRentalModal" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header">
            <h2 class="modal-title">Edit Master Rental</h2>
            <button class="modal-close" onclick="closeEditModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="editMasterRentalForm" onsubmit="submitEditMasterRental(event)">
            <div class="modal-body">
                <input type="hidden" id="edit_rental_code" name="rental_code">
                
                <div class="form-group">
                    <label class="form-label">Rental Name <span class="text-red-500">*</span></label>
                    <input type="text" class="form-input" id="edit_rental_name" name="rental_name" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Rental Type (Spesifikasi) <span class="text-red-500">*</span></label>
                    <select class="form-select" id="edit_rental_type" name="rental_type" required>
                        <option value="unit_refill">Unit + Refill</option>
                        <option value="unit_only">Unit Only</option>
                        <option value="refill_only">Refill Only</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Service Frequency <span class="text-red-500">*</span></label>
                    <select class="form-select" id="edit_service_frequency_id" name="service_frequency_id" required>
                        <option value="">Select Service Frequency</option>
                        @foreach($serviceFrequencies as $freq)
                            <option value="{{ $freq->id }}">{{ $freq->name }} ({{ $freq->frequency_months }} months)</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Category <span class="text-red-500">*</span></label>
                    <select class="form-select" id="edit_category" name="category" required>
                        <option value="">Select Category</option>
                        @foreach($masterRentalCategories as $category)
                            <option value="{{ $category->name }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Daily Price</label>
                    <input type="number" class="form-input" id="edit_daily_price" name="daily_price" step="0.01" min="0" value="0">
                </div>

                <div class="form-group">
                    <label class="form-label">Monthly Price</label>
                    <input type="number" class="form-input" id="edit_monthly_price" name="monthly_price" step="0.01" min="0" value="0">
                </div>

                <div class="form-group">
                    <label class="form-label">Lost Unit Price</label>
                    <input type="number" class="form-input" id="edit_lost_unit_price" name="lost_unit_price" step="0.01" min="0" value="0">
                </div>

                <div class="form-group">
                    <label class="form-label">Durasi Install (menit)</label>
                    <input type="number" class="form-input" id="edit_install_duration" name="install_duration" step="1" min="0" placeholder="Masukkan durasi instalasi">
                </div>

                <div class="form-group">
                    <label class="form-label">Durasi Service (menit)</label>
                    <input type="number" class="form-input" id="edit_service_duration" name="service_duration" step="1" min="0" placeholder="Masukkan durasi service">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Batal</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Perbarui
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Material List Modal -->
<div id="materialListModal" class="modal-overlay">
    <div class="modal-container" style="width: 800px;">
        <div class="modal-header">
            <h2 class="modal-title">Daftar Material</h2>
            <button class="modal-close" onclick="closeMaterialListModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="selected_detail_id">
            
            <div class="mb-3">
                <input type="text" class="form-input" id="materialSearch" placeholder="Cari produk..." onkeyup="filterMaterials()" oninput="filterMaterials()" onpaste="filterMaterials()">
            </div>

            <!-- Product Type Grouping per report-mom5.md -->
            <div class="mb-4">
                <h4 class="text-md font-semibold mb-2">1. Pilih Kategori Produk (Opsional)</h4>
                <p class="text-sm text-gray-600 mb-3">Pilih kategori produk untuk menambahkan semua produk dalam kategori tersebut</p>
                <div id="productTypeList" class="space-y-2">
                    <!-- Will be populated dynamically -->
                </div>
            </div>

            <hr class="my-4">

            <div>
                <h4 class="text-md font-semibold mb-2">2. Pilih Produk Satuan</h4>
                <p class="text-sm text-gray-600 mb-3">Pilih produk satuan setelah memilih kategori produk</p>
                
                <div class="table-container">
                    <table class="responsive-table">
                        <thead>
                            <tr>
                                <th data-no-filter style="width: 50px;">
                                    <input type="checkbox" id="selectAllMaterials" onchange="toggleAllMaterials(this)">
                                </th>
                                <th>Product Code</th>
                                <th>Product Name</th>
                                <th>Package Size</th>
                            </tr>
                        </thead>
                        <tbody id="materialListBody">
                            <!-- Will be populated dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeMaterialListModal()">Tutup</button>
            <button type="button" class="btn btn-primary" onclick="applyMaterialList()">
                <i class="fas fa-check"></i> Terapkan Pilihan
            </button>
        </div>
    </div>
</div>

<script>
// Fix filter row sticky position after DOM renders
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        document.querySelectorAll('.table-scroll-shell table').forEach(function(table) {
            const thead = table.tHead;
            if (!thead || thead.rows.length < 2) return;
            const headerRow = thead.rows[0];
            const filterRow = thead.querySelector('tr.filter-row');
            if (!filterRow) return;
            const headerHeight = headerRow.getBoundingClientRect().height || 44;
            filterRow.querySelectorAll('th').forEach(function(th) {
                th.style.top = headerHeight + 'px';
            });
        });
    }, 200);
});

// Tab switching
function switchTab(event, tabName) {
    // Remove active class from all tabs
    document.querySelectorAll('.tab-item').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Remove active class from all tab contents
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Add active class to clicked tab
    event.currentTarget.classList.add('active');
    
    // Show corresponding tab content
    document.getElementById(tabName).classList.add('active');
}

// Detail Modal Functions
let currentDetailId = null;
const masterProductsCatalog = {{ \Illuminate\Support\Js::from(
    $masterProducts->map(function ($product) {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'product_category_id' => $product->product_category_id,
            'product_type_id' => $product->product_type_id,
            'packaging_size' => optional($product->packagingSize)->name,
            'is_unit' => optional($product->productCategory)->is_unit ?? optional($product->productType)->is_unit ?? false,
            'bom_quantity' => $product->bom_quantity ?? 0,
        ];
    })->values()
) }};

function getSelectedMultiProductIds() {
    const multiSelect = document.getElementById('master_product_ids');
    if (!multiSelect) return [];

    return Array.from(multiSelect.selectedOptions || [])
        .map(option => parseInt(option.value, 10))
        .filter(id => !Number.isNaN(id));
}

function getAllCurrentCategoryProductIds() {
    return Array.from(document.querySelectorAll('#master_product_ids option'))
        .map(option => parseInt(option.value, 10))
        .filter(id => !Number.isNaN(id));
}

function setAutoExpandMode(enabled) {
    const autoExpandInput = document.getElementById('auto_expand');
    if (autoExpandInput) {
        autoExpandInput.value = enabled ? '1' : '0';
    }
}

function syncAutoExpandModeFromProductSelection() {
    const selectedProductIds = getSelectedMultiProductIds();
    const allProductIds = getAllCurrentCategoryProductIds();
    setAutoExpandMode(allProductIds.length > 0 && selectedProductIds.length === allProductIds.length);
}

function populateProductSelections(products, selectedIds = [], primaryProductId = null) {
    const normalizedSelectedIds = (selectedIds || []).map(id => String(id));
    const normalizedPrimaryId = primaryProductId != null ? String(primaryProductId) : (normalizedSelectedIds[0] || '');
    const productSelect = document.getElementById('master_product_id');
    const productSelectMulti = document.getElementById('master_product_ids');

    if (!productSelect || !productSelectMulti) {
        return;
    }

    productSelect.innerHTML = '<option value="">Select Product</option>';
    productSelectMulti.innerHTML = '';

    (products || []).forEach(product => {
        const displayText = (product.name || '-') + (product.packaging_size ? ` (${product.packaging_size})` : '');

        const option = document.createElement('option');
        option.value = product.id;
        option.text = displayText;
        option.setAttribute('data-text', displayText);
        option.setAttribute('data-is-unit', product.is_unit ? 'true' : 'false');
        option.setAttribute('data-bom-quantity', product.bom_quantity || 0);
        if (String(product.id) === normalizedPrimaryId) {
            option.selected = true;
        }
        productSelect.appendChild(option);

        const multiOption = option.cloneNode(true);
        if (normalizedSelectedIds.includes(String(product.id))) {
            multiOption.selected = true;
        }
        productSelectMulti.appendChild(multiOption);
    });
}

function getProductsForSelectedCategory() {
    const categorySelect = document.getElementById('product_category_id');
    const categoryId = categorySelect?.value;

    if (!categoryId) {
        return [];
    }

    return masterProductsCatalog.filter(product => String(product.product_category_id) === String(categoryId));
}

function refreshProductsForSelectedCategory(preservedSelectedIds = []) {
    const products = getProductsForSelectedCategory();
    populateProductSelections(products, preservedSelectedIds);

    if (typeof $ !== 'undefined' && $.fn.select2) {
        const $single = $('#master_product_id');
        const $multi = $('#master_product_ids');

        if ($single.data('select2')) {
            $single.trigger('change.select2');
        }

        if ($multi.data('select2')) {
            $multi.trigger('change.select2');
        }
    }
}

function initializeDetailProductSelect2() {
    if (typeof $ === 'undefined' || !$.fn.select2) {
        return;
    }

    const setupSelect2 = (id, isMulti) => {
        const $el = $('#' + id);
        if (!$el.length) {
            return;
        }

        if ($el.data('select2')) {
            $el.select2('destroy');
        }

        $el.select2({
            dropdownParent: $('#detailModal'),
            placeholder: isMulti ? 'Select Multiple Products (Optional)' : 'Select Product (Optional)',
            allowClear: true,
            width: '100%',
            templateResult: function(data) {
                if (!data.id) return data.text;
                var $option = $(data.element);
                var displayText = $option.data('text') || $option.text();
                var isUnit = $option.data('is-unit') === true || $option.attr('data-is-unit') === 'true';
                if (isUnit) {
                    return $('<span>' + displayText + ' <span class="badge badge-info" style="font-size: 10px; padding: 2px 6px;">Unit</span></span>');
                }
                return $('<span>' + displayText + '</span>');
            }
        }).on('change', function() {
            if (window.handleFrequencyValidation) window.handleFrequencyValidation();
            if (!isMulti) {
                const selectedOption = $(this).find('option:selected');
                if (selectedOption.length) {
                    const bomQty = parseFloat(selectedOption.attr('data-bom-quantity') || 0);
                    if (bomQty > 0) $('#bom_rental_qty').val(bomQty);
                }
            }
        });
    };

    setupSelect2('master_product_id', false);
    setupSelect2('master_product_ids', true);
}

function openAddDetailModal() {
    currentDetailId = null;
    document.getElementById('detailModalTitle').textContent = 'Add Rental Detail';

    // Destroy Select2 before resetting/repopulating native options.
    if (typeof $ !== 'undefined' && $.fn.select2) {
        const $productSelect = $('#master_product_id');
        if ($productSelect.length && $productSelect.data('select2')) {
            $productSelect.select2('destroy');
        }
        const $productSelectMulti = $('#master_product_ids');
        if ($productSelectMulti.length && $productSelectMulti.data('select2')) {
            $productSelectMulti.select2('destroy');
        }
    }

    document.getElementById('detailForm').reset();
    document.getElementById('detail_id').value = '';
    setAutoExpandMode(false);
    document.getElementById('bom_rental_qty').value = 1;
    
    // Clear product dropdowns
    const productSelect = document.getElementById('master_product_id');
    productSelect.innerHTML = '<option value="">Select Product (Optional)</option>';
    const productSelectsMulti = document.getElementById('master_product_ids');
    productSelectsMulti.innerHTML = '';
    
    // Reset selection UI
    updateProductSelectionUI();
    refreshProductsForSelectedCategory();
    initializeDetailProductSelect2();
    
    document.getElementById('detailModal').classList.add('show');
    
    // Explicitly call validation and trigger change
    if (window.handleFrequencyValidation) {
        window.handleFrequencyValidation();
    }
    if (typeof $ !== 'undefined') {
        $('#product_category_id').trigger('change');
    }
}

function editDetail(detailId) {
    currentDetailId = detailId;
    document.getElementById('detailModalTitle').textContent = 'Edit Rental Detail';
    
    // Fetch detail data and allowed products
    Promise.all([
        fetch(`/warehouse/master-rentals/{{ $masterRental->id }}/details/${detailId}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(res => res.json()),
        fetch(`/warehouse/master-rentals/{{ $masterRental->id }}/details/${detailId}/materials`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(res => res.json())
    ])
    .then(([detailResponse, materialsResponse]) => {
        if (detailResponse.status === 'success') {
            const detail = detailResponse.data;
            console.log('📦 Loading Detail:', detail);
            
            document.getElementById('detail_id').value = detail.id;
            setAutoExpandMode(Boolean(detail.auto_expand));
            
            // Logic to determine BOM Rental Qty
            let bomQty = parseFloat(detail.bom_rental_qty || 0);
            
            // If saved value is 0, try to find Master Product's BOM
            if (bomQty === 0 && detail.master_product_id && materialsResponse.status === 'success' && materialsResponse.data.allowed_products) {
                const product = materialsResponse.data.allowed_products.find(p => p.id == detail.master_product_id);
                if (product) {
                    const productBom = parseFloat(product.bom_quantity || 0);
                    if (productBom > 0) {
                        bomQty = productBom;
                    }
                }
            }
            
            document.getElementById('bom_rental_qty').value = bomQty;
            
            // Set Product Category ID with Select2 support
            const productCategorySelect = document.getElementById('product_category_id');
            if (productCategorySelect) {
                productCategorySelect.value = detail.product_category_id;
                if (typeof $ !== 'undefined') {
                    $(productCategorySelect).val(detail.product_category_id).trigger('change');
                }
                if (window.handleFrequencyValidation) {
                    window.handleFrequencyValidation();
                }
            }
            document.getElementById('service_frequency_multiplier').value = detail.service_frequency_multiplier;
            document.getElementById('quantity').value = detail.quantity;
            
            // Sync UI based on category
            updateProductSelectionUI();

            // Populate product dropdowns
            if (materialsResponse.status === 'success') {
                const allowedProducts = materialsResponse.data.all_products || materialsResponse.data.allowed_products || [];
                const allowedIds = materialsResponse.data.allowed_product_ids || [];
                populateProductSelections(allowedProducts, allowedIds, detail.master_product_id);
                setAutoExpandMode(Boolean(materialsResponse.data.auto_expand || detail.auto_expand));
            }
            
            // Initialize Select2
            if (typeof $ !== 'undefined' && $.fn.select2) {
                const setupSelect2 = (id, isMulti) => {
                    const $el = $('#' + id);
                    if ($el.data('select2')) $el.select2('destroy');
                    $el.select2({
                        dropdownParent: $('#detailModal'),
                        placeholder: isMulti ? 'Select Multiple Products' : 'Select Product (Optional)',
                        allowClear: true,
                        templateResult: function(data) {
                            if (!data.id) return data.text;
                            var $option = $(data.element);
                            var displayText = $option.data('text') || $option.text();
                            var isUnit = $option.data('is-unit') === true;
                            if (isUnit) {
                                return $('<span>' + displayText + ' <span class="badge badge-info" style="font-size: 10px; padding: 2px 6px;">Unit</span></span>');
                            }
                            return $('<span>' + displayText + '</span>');
                        }
                    }).on('change', function() {
                        if (window.handleFrequencyValidation) window.handleFrequencyValidation();
                        const selectedOption = $(this).find('option:selected');
                        if (!isMulti && selectedOption.length) {
                            const bomQty = parseFloat(selectedOption.attr('data-bom-quantity') || 0);
                            if (bomQty > 0) $('#bom_rental_qty').val(bomQty);
                        }
                    });
                };

                setupSelect2('master_product_id', false);
                setupSelect2('master_product_ids', true);
            }
            
            document.getElementById('detailModal').classList.add('show');
        }
    })
    .catch(error => console.error('Error:', error));
}

function closeDetailModal() {
    document.getElementById('detailModal').classList.remove('show');
    
    // Destroy Select2 if initialized (check if Select2 is attached to element)
    if (typeof $ !== 'undefined' && $.fn.select2) {
        const $productSelect = $('#master_product_id');
        const $productSelectMulti = $('#master_product_ids');
        if ($productSelect.length && $productSelect.data('select2')) {
            $productSelect.select2('destroy');
        }
        if ($productSelectMulti.length && $productSelectMulti.data('select2')) {
            $productSelectMulti.select2('destroy');
        }
    }
}

function submitDetail(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const detailId = formData.get('detail_id');
    
    const selectedProductIds = getSelectedMultiProductIds();
    const allProductIds = getAllCurrentCategoryProductIds();
    const autoExpandInput = document.getElementById('auto_expand');
    const shouldAutoExpand = autoExpandInput?.value === '1'
        || (allProductIds.length > 0 && selectedProductIds.length === allProductIds.length);

    formData.delete('master_product_id');
    formData.delete('master_product_ids[]');
    formData.set('auto_expand', shouldAutoExpand ? '1' : '0');

    if (selectedProductIds.length > 0) {
        formData.append('master_product_id', String(selectedProductIds[0]));
    }

    selectedProductIds.forEach(productId => {
        formData.append('master_product_ids[]', String(productId));
    });
    
    let url = `/warehouse/master-rentals/{{ $masterRental->id }}/details`;
    let method = 'POST';
    
    if (detailId) {
        url += `/${detailId}`;
        formData.append('_method', 'PUT');
    }
    
    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            closeDetailModal();
            location.reload();
        } else {
            showErrorDialog('Gagal', data.message || 'Terjadi kesalahan.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorDialog('Gagal', 'Terjadi kesalahan.');
    });
}

function updateProductSelectionUI() {
    const singleContainer = document.getElementById('single_product_container');
    const multiContainer = document.getElementById('multi_product_container');

    if (singleContainer) singleContainer.style.display = 'none';
    if (multiContainer) multiContainer.style.display = 'block';
}

function selectAllFromCategory() {
    if (typeof $ !== 'undefined') {
        $('#master_product_ids option').prop('selected', true);
        $('#master_product_ids').trigger('change');
    }
    setAutoExpandMode(true);
}

function clearProductSelection() {
    if (typeof $ !== 'undefined') {
        $('#master_product_ids').val(null).trigger('change');
    }
    setAutoExpandMode(false);
}

// Add event listener for category change
document.addEventListener('DOMContentLoaded', function() {
    const categorySelect = document.getElementById('product_category_id');
    if (categorySelect) {
        categorySelect.addEventListener('change', updateProductSelectionUI);
        // Also handle Select2 change if jQuery is present
        if (typeof $ !== 'undefined') {
            $(categorySelect).on('select2:select', updateProductSelectionUI);
        }
    }
});

function deleteDetail(detailId) {
    showConfirmDialog(
        'Hapus Detail',
        'Apakah Anda yakin ingin menghapus detail ini?',
        'Ya, Hapus',
        'Batal'
    ).then((confirmed) => {
        if (!confirmed) return;
    
        fetch(`/warehouse/master-rentals/{{ $masterRental->id }}/details/${detailId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ _method: 'DELETE' })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                location.reload();
            } else {
                showErrorDialog('Gagal', data.message || 'Terjadi kesalahan.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorDialog('Gagal', 'Terjadi kesalahan.');
        });
    });
}

// Material List Functions
let globalProductTypes = [];
let globalAllProducts = [];
let globalSelectedProductIds = [];

function openMaterialList(detailId) {
    document.getElementById('selected_detail_id').value = detailId;
    
    // Clear search input
    const searchInput = document.getElementById('materialSearch');
    if (searchInput) {
        searchInput.value = '';
    }
    
    // Show loading state
    const tbody = document.getElementById('materialListBody');
    tbody.innerHTML = '<tr><td colspan="3" class="text-center">Loading products...</td></tr>';
    
    // Load material list data from API (includes product types and products)
    fetch(`/warehouse/master-rentals/{{ $masterRental->id }}/details/${detailId}/materials`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        if (data.status === 'success') {
            globalProductTypes = data.data.product_types || [];
            globalAllProducts = data.data.all_products || [];
            setAutoExpandMode(Boolean(data.data.auto_expand));
            // Normalize selected product IDs to numbers for consistent comparison
            const allowedIds = data.data.allowed_product_ids || [];
            globalSelectedProductIds = allowedIds.map(id => parseInt(id));
            
            console.log('Loaded products:', globalAllProducts.length);
            console.log('Loaded product types:', globalProductTypes.length);
            console.log('Loaded selected product IDs:', globalSelectedProductIds);
            
            // Render Product Types Section
            renderProductTypes();
            
            // Render Individual Products Section
            renderIndividualProducts();
            
            // Debug: Log rendered rows
            setTimeout(() => {
                const rows = document.querySelectorAll('.material-row');
                console.log('Total material rows rendered:', rows.length);
                if (rows.length > 0) {
                    console.log('First row text:', rows[0].textContent.trim().substring(0, 50));
                    console.log('Last row text:', rows[rows.length-1].textContent.trim().substring(0, 50));
                }
            }, 500);
            
            // Clear any existing search filter
            if (searchInput) {
                filterMaterials();
            }
        } else {
            throw new Error(data.message || 'Failed to load material list');
        }
    })
    .catch(error => {
        console.error('Error loading material list:', error);
        tbody.innerHTML = `<tr><td colspan="3" class="text-center text-danger">Error loading products: ${error.message}</td></tr>`;
        showErrorDialog('Gagal', 'Material list tidak berhasil dimuat: ' + error.message);
    });
    
    document.getElementById('materialListModal').classList.add('show');
}

function renderProductTypes() {
    const container = document.getElementById('productTypeList');
    container.innerHTML = '';
    
    globalProductTypes.forEach(productType => {
        const productsInType = productType.products || [];
        const productCount = productsInType.length;
        
        // Check if all products in this type are selected
        const productIdsInType = productsInType.map(p => p.id);
        const allProductsSelected = productIdsInType.length > 0 && 
            productIdsInType.every(productId => {
                const productIdNum = parseInt(productId);
                return globalSelectedProductIds.some(id => parseInt(id) === productIdNum);
            });
        
        const div = document.createElement('div');
        div.className = 'flex items-center gap-2 p-3 bg-gray-50 border border-gray-200 rounded-lg';
        div.innerHTML = `
            <input type="checkbox" 
                class="product-type-checkbox" 
                value="${productType.id}" 
                data-products='${JSON.stringify(productIdsInType)}'
                ${allProductsSelected ? 'checked' : ''}
                onchange="handleProductTypeCheck(this)">
            <div class="flex-1">
                <strong>${productType.code || ''} - ${productType.name}</strong>
                <small class="text-gray-600 ml-2">(${productCount} product${productCount !== 1 ? 's' : ''})</small>
            </div>
        `;
        container.appendChild(div);
    });
}

function renderIndividualProducts() {
    const tbody = document.getElementById('materialListBody');
    tbody.innerHTML = '';
    
    if (!globalAllProducts || globalAllProducts.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No products found. Please ensure products are active in the system.</td></tr>';
        return;
    }
    
    globalAllProducts.forEach(product => {
        const tr = document.createElement('tr');
        tr.className = 'material-row';
        const productId = product.id || product.product_id;
        // Ensure both are numbers for comparison
        const productIdNum = parseInt(productId);
        const isChecked = globalSelectedProductIds.some(id => parseInt(id) === productIdNum);
        tr.innerHTML = `
            <td>
                <input type="checkbox" 
                    class="material-checkbox" 
                    value="${productId}" 
                    ${isChecked ? 'checked' : ''}>
            </td>
            <td>${product.sku || '-'}</td>
            <td>${product.name || '-'}</td>
            <td>${product.packaging_size || '-'}</td>
        `;
        tbody.appendChild(tr);
    });
}

function handleProductTypeCheck(checkbox) {
    const productIds = JSON.parse(checkbox.getAttribute('data-products'));
    const isChecked = checkbox.checked;
    
    // Auto-expand: Check/uncheck all products in this type
    productIds.forEach(productId => {
        const productCheckbox = document.querySelector(`.material-checkbox[value="${productId}"]`);
        if (productCheckbox) {
            productCheckbox.checked = isChecked;
        }
    });
    
    console.log(`Product Type ${isChecked ? 'checked' : 'unchecked'}: ${productIds.length} products ${isChecked ? 'added' : 'removed'}`);
}

function closeMaterialListModal() {
    // Clear search input when closing
    const searchInput = document.getElementById('materialSearch');
    if (searchInput) {
        searchInput.value = '';
        // Reset filter to show all products
        filterMaterials();
    }
    
    document.getElementById('materialListModal').classList.remove('show');
}

function filterMaterials() {
    const searchTerm = document.getElementById('materialSearch').value.toLowerCase().trim();
    
    // Filter product rows in table
    const productRows = document.querySelectorAll('.material-row');
    let visibleCount = 0;
    
    productRows.forEach(row => {
        const sku = row.querySelector('td:nth-child(2)')?.textContent?.toLowerCase() || '';
        const name = row.querySelector('td:nth-child(3)')?.textContent?.toLowerCase() || '';
        const rowText = (sku + ' ' + name).toLowerCase();
        
        if (searchTerm === '' || rowText.includes(searchTerm)) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    // Also filter product types
    const productTypeDivs = document.querySelectorAll('#productTypeList > div');
    productTypeDivs.forEach(div => {
        const typeName = div.textContent?.toLowerCase() || '';
        if (searchTerm === '' || typeName.includes(searchTerm)) {
            div.style.display = '';
        } else {
            div.style.display = 'none';
        }
    });
    
    // Show message if no results
    const tbody = document.getElementById('materialListBody');
    if (visibleCount === 0 && searchTerm !== '') {
        // Check if there's already a "no results" row
        const existingNoResults = tbody.querySelector('.no-results-row');
        if (!existingNoResults) {
            const tr = document.createElement('tr');
            tr.className = 'no-results-row';
            tr.innerHTML = `<td colspan="3" class="text-center text-muted">No products found matching "${searchTerm}"</td>`;
            tbody.appendChild(tr);
        }
    } else {
        // Remove "no results" row if it exists
        const existingNoResults = tbody.querySelector('.no-results-row');
        if (existingNoResults) {
            existingNoResults.remove();
        }
    }
}

function toggleAllMaterials(checkbox) {
    document.querySelectorAll('.material-checkbox').forEach(cb => {
        if (cb.closest('tr').style.display !== 'none') {
            cb.checked = checkbox.checked;
        }
    });
}

function applyMaterialList() {
    const selectedCheckboxes = document.querySelectorAll('.material-checkbox:checked');
    const detailId = document.getElementById('selected_detail_id').value;
    
    if (selectedCheckboxes.length === 0) {
        showConfirmDialog(
            'Kosongkan Material List',
            'Tidak ada produk yang dipilih. Material list akan dikosongkan. Lanjutkan?',
            'Ya, Lanjutkan',
            'Batal'
        ).then((confirmed) => {
            if (!confirmed) {
                return;
            }
            applyMaterialListConfirmed(detailId, []);
        });
        return;
    }
    
    // Get all selected product IDs
    const productIds = Array.from(selectedCheckboxes).map(cb => parseInt(cb.value));

    applyMaterialListConfirmed(detailId, productIds);
}

function applyMaterialListConfirmed(detailId, productIds) {
    const allProductIds = (globalAllProducts || []).map(product => parseInt(product.id || product.product_id, 10)).filter(id => !Number.isNaN(id));
    const selectedAll = allProductIds.length > 0 && productIds.length === allProductIds.length;

    fetch(`/warehouse/master-rentals/{{ $masterRental->id }}/details/${detailId}/materials`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            product_ids: productIds,
            auto_expand: selectedAll ? 1 : 0
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showSuccessDialog('Berhasil', 'Material list berhasil disimpan.');
            closeMaterialListModal();
            // Optionally reload to see changes
            // location.reload();
        } else {
            showErrorDialog('Gagal', data.message || 'Material list tidak berhasil disimpan.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorDialog('Gagal', 'Material list tidak berhasil disimpan.');
    });
}

// Price Functions
function savePrice(branchId) {
    const dailyPrice = document.getElementById(`daily_price_${branchId}`).value;
    const monthlyPrice = document.getElementById(`monthly_price_${branchId}`).value;
    
    fetch(`/warehouse/master-rentals/{{ $masterRental->id }}/prices`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            branch_id: branchId,
            daily_price: dailyPrice || 0,
            monthly_price: monthlyPrice || 0
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showSuccessDialog('Berhasil', 'Harga berhasil disimpan.');
            location.reload();
        } else {
            showErrorDialog('Gagal', data.message || 'Terjadi kesalahan.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorDialog('Gagal', 'Terjadi kesalahan.');
    });
}

function updatePrice(branchId, priceId) {
    const dailyPrice = document.getElementById(`daily_price_${branchId}`).value;
    const monthlyPrice = document.getElementById(`monthly_price_${branchId}`).value;
    
    fetch(`/warehouse/master-rentals/{{ $masterRental->id }}/prices/${priceId}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            _method: 'PUT',
            branch_id: branchId,
            daily_price: dailyPrice || 0,
            monthly_price: monthlyPrice || 0
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showSuccessDialog('Berhasil', 'Harga berhasil diperbarui.');
            location.reload();
        } else {
            showErrorDialog('Gagal', data.message || 'Terjadi kesalahan.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorDialog('Gagal', 'Terjadi kesalahan.');
    });
}

function saveLostUnitPrice(branchId) {
    const lostUnitPrice = document.getElementById(`lost_unit_price_${branchId}`).value;
    
    fetch(`/warehouse/master-rentals/{{ $masterRental->id }}/prices`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            branch_id: branchId,
            lost_unit_price: lostUnitPrice || 0
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showSuccessDialog('Berhasil', 'Lost Unit Price berhasil disimpan.');
            location.reload();
        } else {
            showErrorDialog('Gagal', data.message || 'Terjadi kesalahan.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorDialog('Gagal', 'Terjadi kesalahan.');
    });
}

function updateLostUnitPrice(branchId, priceId) {
    const lostUnitPrice = document.getElementById(`lost_unit_price_${branchId}`).value;
    
    fetch(`/warehouse/master-rentals/{{ $masterRental->id }}/prices/${priceId}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            _method: 'PUT',
            branch_id: branchId,
            lost_unit_price: lostUnitPrice || 0
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showSuccessDialog('Berhasil', 'Lost Unit Price berhasil diperbarui.');
            location.reload();
        } else {
            showErrorDialog('Gagal', data.message || 'Terjadi kesalahan.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorDialog('Gagal', 'Terjadi kesalahan.');
    });
}

function saveRecord() {
    showInfoDialog('Informasi', 'Semua perubahan tersimpan otomatis. Tombol ini hanya sebagai referensi.');
}

// Edit Master Rental Functions
function openEditModal() {
    // Fetch master rental data
    fetch(`{{ route('warehouse.master-rentals.edit', $masterRental->id) }}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const rental = data.data;
            document.getElementById('edit_rental_code').value = rental.rental_code || '';
            document.getElementById('edit_rental_name').value = rental.rental_name || '';
            document.getElementById('edit_daily_price').value = rental.daily_price || 0;
            document.getElementById('edit_monthly_price').value = rental.monthly_price || 0;
            document.getElementById('edit_lost_unit_price').value = rental.lost_unit_price || 0;
            document.getElementById('edit_install_duration').value = rental.install_duration || '';
            document.getElementById('edit_service_duration').value = rental.service_duration || '';
            
            // Initialize Select2 for dropdowns
            if (typeof $ !== 'undefined' && $.fn.select2) {
                $('#edit_rental_type').val(rental.rental_type || 'unit_refill').select2({
                    dropdownParent: $('#editMasterRentalModal'),
                    placeholder: 'Select Rental Type',
                    allowClear: false
                }).trigger('change');
                
                $('#edit_service_frequency_id').val(rental.service_frequency_id || '').select2({
                    dropdownParent: $('#editMasterRentalModal'),
                    placeholder: 'Select Service Frequency',
                    allowClear: true
                }).trigger('change');
                
                $('#edit_category').val(rental.category || '').select2({
                    dropdownParent: $('#editMasterRentalModal'),
                    placeholder: 'Select Category',
                    allowClear: true
                }).trigger('change');
            }
            
            document.getElementById('editMasterRentalModal').classList.add('show');
        } else {
            showErrorDialog('Gagal', data.message || 'Data tidak berhasil dimuat.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorDialog('Gagal', 'Data tidak berhasil dimuat.');
    });
}

function closeEditModal() {
    document.getElementById('editMasterRentalModal').classList.remove('show');
    // Destroy Select2 if initialized
    if (typeof $ !== 'undefined' && $.fn.select2) {
        $('#edit_rental_type').select2('destroy');
        $('#edit_service_frequency_id').select2('destroy');
        $('#edit_category').select2('destroy');
    }
}

function submitEditMasterRental(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    formData.append('_method', 'PUT');
    
    fetch(`{{ route('warehouse.master-rentals.update', $masterRental->id) }}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showSuccessDialog('Berhasil', 'Master Rental berhasil diperbarui.');
            closeEditModal();
            location.reload();
        } else {
            let errorMessage = data.message || 'Terjadi kesalahan.';
            if (data.errors) {
                errorMessage = Object.values(data.errors).flat().join('\n');
            }
            showErrorDialog('Gagal', errorMessage);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorDialog('Gagal', 'Terjadi kesalahan.');
    });
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDetailModal();
        closeMaterialListModal();
        closeEditModal();
    }
});

// Product Type validation for Frequency and Rental Type Compliance
// Define global function to be accessible from editDetail
const currentRentalType = "{{ $masterRental->rental_type }}"; // unit_only, refill_only, or unit_refill

window.validateRentalCompliance = function() {
    const $productTypeSelect = $('#product_type_id');
    const $productSelect = $('#master_product_id');
    const $multiProductSelect = $('#master_product_ids');
    
    // Determine if selection is "Unit" based on Product Type or specific Product
    let hasUnitProduct = false;
    let hasNonUnitProduct = false;
    let source = ''; // 'type' or 'product'

    // Check specific Product(s) first (if selected)
    if ($multiProductSelect.length && $multiProductSelect.val() && $multiProductSelect.val().length) {
        const $selectedProductOptions = $multiProductSelect.find('option:selected');
        $selectedProductOptions.each(function() {
            const optionIsUnit = $(this).attr('data-is-unit') === 'true' || $(this).data('is-unit') === true;
            if (optionIsUnit) {
                hasUnitProduct = true;
            } else {
                hasNonUnitProduct = true;
            }
        });
        source = 'product';
    } else if ($productSelect.length && $productSelect.val()) {
        const $selectedProductOption = $productSelect.find('option:selected');
        const optionIsUnit = $selectedProductOption.attr('data-is-unit') === 'true' || $selectedProductOption.data('is-unit') === true;
        hasUnitProduct = optionIsUnit;
        hasNonUnitProduct = !optionIsUnit;
        source = 'product';
    } 
    // Fallback to Product Type
    else if ($productTypeSelect.length && $productTypeSelect.val()) {
        const $selectedTypeOption = $productTypeSelect.find('option:selected');
        hasUnitProduct = $selectedTypeOption.attr('data-is-unit') === 'true';
        hasNonUnitProduct = !hasUnitProduct;
        source = 'type';
    } else {
        return; // Nothing selected yet
    }

    console.log(`Compliance Check: RentalType=${currentRentalType}, hasUnit=${hasUnitProduct}, hasNonUnit=${hasNonUnitProduct} (Source: ${source})`);

    // Rule 1: Unit Only - cannot select non-unit
    if (currentRentalType === 'unit_only' && hasNonUnitProduct) {
        Swal.fire({
            icon: 'error',
            title: 'Invalid Selection',
            text: 'Rental type anda Unit Only, anda tidak dapat memilih product non unit.'
        }).then(() => {
            // Reset selection
            if (source === 'product') {
                $productSelect.val(null).trigger('change.select2');
                $multiProductSelect.val(null).trigger('change.select2');
            } else {
                $productTypeSelect.val('').trigger('change');
            }
        });
        return;
    }

    // Rule 2: Refill Only - cannot select unit
    if (currentRentalType === 'refill_only' && hasUnitProduct) {
        Swal.fire({
            icon: 'error',
            title: 'Invalid Selection',
            text: 'Rental type anda Refill Only, anda tidak dapat memilih product unit.'
        }).then(() => {
            // Reset selection
            if (source === 'product') {
                $productSelect.val(null).trigger('change.select2');
                $multiProductSelect.val(null).trigger('change.select2');
            } else {
                $productTypeSelect.val('').trigger('change');
            }
        });
        return;
    }
};

window.handleFrequencyValidation = function() {
    const $productCategorySelect = $('#product_category_id');
    const $productSelect = $('#master_product_id');
    const $multiProductSelect = $('#master_product_ids');
    const frequencyInput = document.getElementById('service_frequency_multiplier');
    
    if ($productCategorySelect.length && frequencyInput) {
        // Check Product Category first
        const selectedCategoryValue = $productCategorySelect.val();
        const $selectedCategoryOption = $productCategorySelect.find('option:selected');
        let isUnit = $selectedCategoryOption.attr('data-is-unit') === 'true';
        
        // If Product Type isn't unit, check the specific Product (if selected)
        if (!isUnit && $multiProductSelect.length && $multiProductSelect.val() && $multiProductSelect.val().length) {
            const hasNonUnitSelected = $multiProductSelect.find('option:selected').toArray().some(option => {
                return !($(option).attr('data-is-unit') === 'true' || $(option).data('is-unit') === true);
            });
            isUnit = !hasNonUnitSelected;
        } else if (!isUnit && $productSelect.length && $productSelect.val()) {
            const $selectedProductOption = $productSelect.find('option:selected');
            isUnit = $selectedProductOption.attr('data-is-unit') === 'true' || $selectedProductOption.data('is-unit') === true;
        }
        
        if (isUnit) {
            // If it is a unit (e.g. Dispenser), frequency CAN be 0
            frequencyInput.removeAttribute('min');
            $(frequencyInput).attr('min', '0').prop('min', 0);
        } else {
            // If NOT a Unit (e.g. Chemical), frequency must be at least 1
            $(frequencyInput).attr('min', '1').prop('min', 1);
            if (parseInt(frequencyInput.value) < 1) {
                frequencyInput.value = "1";
            }
        }
    }
};

document.addEventListener('DOMContentLoaded', function() {
    const productCategorySelect = document.getElementById('product_category_id');
    const productSelect = document.getElementById('master_product_id');
    const multiProductSelect = document.getElementById('master_product_ids');
    
    if (productCategorySelect) {
        // Attach event listener
        // Combine validation calls
        const handleChange = function() {
            if (this && this.id === 'product_category_id') {
                refreshProductsForSelectedCategory(getSelectedMultiProductIds());
            }
            window.validateRentalCompliance();
            window.handleFrequencyValidation();
        };

        productCategorySelect.addEventListener('change', handleChange);
        
        // Also listen for jQuery change if Select2 is used
        if (typeof $ !== 'undefined') {
            $(productCategorySelect).on('change', handleChange);
            $(document).on('change', '#master_product_id', handleChange);
            $(document).on('change', '#master_product_ids', handleChange);
            $(document).on('change', '#master_product_ids', syncAutoExpandModeFromProductSelection);
        }
    }

    if (multiProductSelect && typeof $ === 'undefined') {
        multiProductSelect.addEventListener('change', function() {
            syncAutoExpandModeFromProductSelection();
            window.validateRentalCompliance();
            window.handleFrequencyValidation();
        });
    }
});
</script>

@endsection

