@extends('layouts.app')

@section('title', 'Quotation Detail')

@php
    $quotationBackUrl = route('marketing.quotations.index');
    $returnUrl = request('return_url');

    if (is_string($returnUrl) && $returnUrl !== '') {
        $decodedReturnUrl = urldecode($returnUrl);
        $returnPath = parse_url($decodedReturnUrl, PHP_URL_PATH);
        $returnHost = parse_url($decodedReturnUrl, PHP_URL_HOST);
        $quotationIndexPath = parse_url(route('marketing.quotations.index'), PHP_URL_PATH);

        if ($returnPath === $quotationIndexPath && ($returnHost === null || $returnHost === request()->getHost())) {
            $quotationBackUrl = $decodedReturnUrl;
        }
    }
@endphp

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
                            <a href="{{ $quotationBackUrl }}" class="btn btn-light btn-sm" id="quotationBackToList">
                                <i class="fas fa-arrow-left"></i> Back to List
                            </a>
                        </div>
                        <div>
                            <h3 class="card-title mb-0" style="color: white; font-size: 1.5rem; font-weight: bold;">
                                 {{ $quotation->quotation_number }}
                            </h3>
                        </div>
                        <div>
                            @if($quotation->status === 'draft')
                                <a href="{{ route('marketing.quotations.edit', $quotation->id) }}" class="btn btn-warning btn-sm me-2">
                                    <i class="fas fa-edit"></i> EDIT
                                </a>
                                <button class="btn btn-success btn-sm me-2" onclick="finalizeQuotation()">
                                    <i class="fas fa-check-circle"></i> FINALIZE
                                </button>
                            @elseif($quotation->status === 'waiting_for_approval')
                                @php
                                    $canApproveQuotation = auth()->user()->canApprove('quotations');
                                @endphp
                                @if($canApproveQuotation)
                                    <button class="btn btn-success btn-sm me-2" onclick="approveQuotation()">
                                        <i class="fas fa-check"></i> APPROVE
                                    </button>
                                    <button class="btn btn-danger btn-sm me-2" onclick="cancelQuotation()">
                                        <i class="fas fa-times"></i> CANCEL
                                    </button>
                                @else
                                    <span class="badge bg-warning text-dark">Waiting for Approval</span>
                                @endif
                            @elseif($quotation->status === 'approved')
                                @php
                                    $canDownloadPdf = auth()->user()->hasPermission('marketing.quotations.download')
                                        || auth()->user()->hasPermission('quotations.download');
                                    $canCreateContract = $quotation->canCreateContract();
                                @endphp
                                
                                @if($canDownloadPdf)
                                    <a href="{{ route('marketing.quotations.download-pdf', ['quotation' => $quotation->id, 'inline' => 'true']) }}" target="_blank" class="btn btn-primary btn-sm me-2">
                                        <i class="fas fa-print"></i> PRINT
                                    </a>
                                @endif
                                
                                @if($canCreateContract)
                                    <a href="{{ route('marketing.contracts.wizard.create', ['quotation_id' => $quotation->id]) }}" class="btn btn-success btn-sm me-2">
                                        <i class="fas fa-file-contract"></i> CREATE CONTRACT
                                    </a>
                                @endif
                                
                                {{-- MOM9: Create Job Advice (Install Free) from Quotation --}}
                                <button type="button" onclick="openJobAdviceModalFromQuotation({{ $quotation->id }})" class="btn btn-info btn-sm me-2">
                                    <i class="fas fa-tools"></i> CREATE JOB ADVICE (INSTALL FREE)
                                </button>
                            @elseif($quotation->status === 'rejected')
                                <span class="badge bg-danger">Rejected</span>
                            @elseif($quotation->status === 'cancelled')
                                <span class="badge bg-secondary">Cancelled</span>
                            @elseif($quotation->status === 'expired')
                                <span class="badge bg-secondary">Expired</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Success Message -->
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
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
                            <button class="nav-link" id="quotation-detail-tab" data-bs-toggle="tab" data-bs-target="#quotation-detail" type="button" role="tab" aria-controls="quotation-detail" aria-selected="false" style="color: #6c757d; padding: 12px 20px; width: 100%; text-align: center;">
                                <i class="fas fa-list-alt me-2"></i>QUOTATION DETAIL
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
                                        No Quotation: {{ $quotation->quotation_number }}
                                    </h5>
                                </div>
                                <div class="quotation-card-body">
                                    <div class="quotation-field">
                                        <div class="quotation-field-label">Nomor Quotation</div>
                                        <div class="quotation-field-value">{{ $quotation->quotation_number }}</div>
                                    </div>
                                    <div class="quotation-field">
                                        <div class="quotation-field-label">Status Quotation</div>
                                        <div class="quotation-field-value">
                                            @php
                                                $statusBadge = match($quotation->status) {
                                                    'approved' => 'success',
                                                    'draft' => 'warning',
                                                    'waiting_for_approval' => 'info',
                                                    'rejected' => 'danger',
                                                    'cancelled' => 'secondary',
                                                    'expired' => 'secondary',
                                                    default => 'info'
                                                };
                                            @endphp
                                            <span class="badge badge-{{ $statusBadge }}">
                                                {{ ucfirst(str_replace('_', ' ', $quotation->status)) }}
                                            </span>
                                        </div>
                                    </div>
                                    @if(in_array($quotation->status, ['approved', 'accepted', 'contract']))
                                    <div class="quotation-field">
                                        <div class="quotation-field-label">Goal SQ</div>
                                        <div class="quotation-field-value">
                                            <select id="goal_sq" class="form-control form-control-sm autosave-goal" data-id="{{ $quotation->id }}" style="width: auto; min-width: 120px;">
                                                <option value="">- Pilih Goal -</option>
                                                @for($i = 10; $i <= 100; $i += 10)
                                                    <option value="{{ $i }}" {{ $quotation->goal_sq == $i ? 'selected' : '' }}>{{ $i }}%</option>
                                                @endfor
                                            </select>
                                        </div>
                                    </div>
                                    @endif
                                    <div class="quotation-field">
                                        <div class="quotation-field-label">Nama Marketing</div>
                                        <div class="quotation-field-value">{{ $quotation->marketing->name ?? '-' }}</div>
                                    </div>
                                    <div class="quotation-field">
                                        <div class="quotation-field-label">Tanggal Quotation</div>
                                        <div class="quotation-field-value">
                                            @php
                                                $canEditQuotationDate = $quotation->contracts->isEmpty() && !in_array($quotation->status, ['cancelled', 'contract'], true);
                                            @endphp
                                            @if($canEditQuotationDate)
                                                <div class="position-relative" style="max-width: 160px;">
                                                    <input type="text"
                                                           id="quotationDateInput"
                                                           class="form-control form-control-sm pe-4"
                                                           value="{{ $quotation->quotation_date ? $quotation->quotation_date->format('Y-m-d') : '' }}"
                                                           placeholder="Select date"
                                                           readonly>
                                                    <i class="fas fa-calendar-alt text-muted small position-absolute"
                                                       style="right: 10px; top: 50%; transform: translateY(-50%); pointer-events: none;"></i>
                                                </div>
                                            @else
                                                {{ $quotation->quotation_date ? $quotation->quotation_date->locale('id')->isoFormat('D MMM Y') : '-' }}
                                            @endif
                                        </div>
                                    </div>
                                    <div class="quotation-field">
                                        <div class="quotation-field-label">Jenis Penawaran</div>
                                        <div class="quotation-field-value">{{ ucfirst($quotation->quotation_type) }}</div>
                                    </div>
                                    @if($quotation->quotation_type === 'renewal' && $quotation->existing_contract_id)
                                    <div class="quotation-field">
                                        <div class="quotation-field-label">Nomor Contract</div>
                                        <div class="quotation-field-value">
                                            @if($quotation->existingContract)
                                                <a href="{{ route('marketing.contracts.show', $quotation->existing_contract_id) }}" class="text-primary fw-bold">
                                                    {{ $quotation->existingContract->contract_number }}
                                                </a>
                                            @else
                                                <span class="text-muted">ID: {{ $quotation->existing_contract_id }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    @endif
                                    <div class="quotation-field">
                                        <div class="quotation-field-label">Periode Sewa</div>
                                        <div class="quotation-field-value">{{ $quotation->rental_period }} {{ $quotation->rental_unit ? ucfirst($quotation->rental_unit) : '' }}</div>
                                    </div>
                                    <div class="quotation-field">
                                        <div class="quotation-field-label">Payment Method</div>
                                        <div class="quotation-field-value">{{ ucfirst(str_replace('_', ' ', $quotation->billing_methods)) }}</div>
                                    </div>
                                    <div class="quotation-field">
                                        <div class="quotation-field-label">Term of Payment</div>
                                        <div class="quotation-field-value">{{ $quotation->terms_of_payment_label }}</div>
                                    </div>
                                    <div class="quotation-field">
                                        <div class="quotation-field-label">Cabang</div>
                                        <div class="quotation-field-value">
                                            @if($quotation->branch)
                                                <span class="badge bg-primary">{{ $quotation->branch->name ?? '-' }} ({{ $quotation->branch->branch_code ?? '-' }})</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="quotation-field">
                                        <div class="quotation-field-label">Dibuat Pada</div>
                                        <div class="quotation-field-value">{{ $quotation->created_at->locale('id')->isoFormat('dddd, D MMM Y - HH:mm') }}</div>
                                    </div>
                                    <div class="quotation-field">
                                        <div class="quotation-field-label">Oleh</div>
                                        <div class="quotation-field-value">{{ $quotation->creator->name ?? '-' }}</div>
                                    </div>
                                    <div class="quotation-field">
                                        <div class="quotation-field-label">Terakhir Di Update</div>
                                        <div class="quotation-field-value">{{ $quotation->updated_at->locale('id')->isoFormat('dddd, D MMM Y - HH:mm') }}</div>
                                    </div>
                                    <div class="quotation-field">
                                        <div class="quotation-field-label">Update Oleh</div>
                                        <div class="quotation-field-value">{{ $quotation->updater->name ?? '-' }}</div>
                                    </div>
                                    @if($quotation->status === 'approved')
                                        <div class="quotation-field">
                                            <div class="quotation-field-label">Disetujui Oleh</div>
                                            <div class="quotation-field-value">{{ $quotation->approver->name ?? '-' }}</div>
                                        </div>
                                        <div class="quotation-field">
                                            <div class="quotation-field-label">Tanggal Disetujui</div>
                                            <div class="quotation-field-value">
                                                @if($quotation->date_approved)
                                                    {{ $quotation->date_approved->locale('id')->isoFormat('dddd, D MMM Y - HH:mm') }}
                                                @else
                                                    -
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Company & Customer Information Section 2 -->
                        <div class="col-lg-6 mb-4">
                            <div class="card quotation-card">
                                <div class="quotation-card-header">
                                    <h5 class="card-title mb-0">
                                        Data Perusahaan & Customer
                                    </h5>
                                </div>
                                <div class="quotation-card-body">
                                    <div class="quotation-field">
                                        <div class="quotation-field-label">Nama Perusahaan</div>
                                        <div class="quotation-field-value">
                                            @if($quotation->customer_id)
                                                <a href="{{ route('company.customers.show', $quotation->customer_id) }}" class="text-primary fw-bold">
                                                    {{ $quotation->company_name ?? '-' }}
                                                    <i class="fas fa-external-link-alt fa-xs ms-1"></i>
                                                </a>
                                            @else
                                                {{ $quotation->company_name ?? '-' }}
                                            @endif
                                        </div>
                                    </div>
                                    <div class="quotation-field">
                                        <div class="quotation-field-label">Jenis Perusahaan</div>
                                        <div class="quotation-field-value">{{ ucfirst($quotation->customer->company_type ?? '-') }}</div>
                                    </div>
                                    <div class="quotation-field">
                                        <div class="quotation-field-label">PIC Quotation</div>
                                        <div class="quotation-field-value">{{ $quotation->pic_name ?? '-' }}</div>
                                    </div>
                                    <div class="quotation-field">
                                        <div class="quotation-field-label">PIC Email</div>
                                        <div class="quotation-field-value">
                                            {{ $quotation->prospect->contact_email ?? ($quotation->customer->email ?? '-') }}
                                        </div>
                                    </div>
                                    <div class="quotation-field">
                                        <div class="quotation-field-label">PIC Phone</div>
                                        <div class="quotation-field-value">
                                            {{ $quotation->prospect->contact_phone ?? ($quotation->customer->phone ?? '-') }}
                                        </div>
                                    </div>
                                    <div class="quotation-field">
                                        <div class="quotation-field-label">Lama Sewa</div>
                                        <div class="quotation-field-value">{{ $quotation->rental_period }} {{ $quotation->rental_unit ? ucfirst($quotation->rental_unit) : '' }}</div>
                                    </div>
                                    <div class="quotation-field">
                                        <div class="quotation-field-label">Payment Method</div>
                                        <div class="quotation-field-value">{{ ucfirst(str_replace('_', ' ', $quotation->billing_methods)) }}</div>
                                    </div>
                                    <div class="quotation-field">
                                        <div class="quotation-field-label">Term of Payment</div>
                                        <div class="quotation-field-value">{{ $quotation->terms_of_payment_label }}</div>
                                    </div>
                                    <div class="quotation-field">
                                        <div class="quotation-field-label">Remark External</div>
                                        <div class="quotation-field-value">{{ $quotation->additional_notes ?? '-' }}</div>
                                    </div>
                                    <div class="quotation-field">
                                        <div class="quotation-field-label">Remark Internal</div>
                                        <div class="quotation-field-value">{{ $quotation->internal_notes ?? '-' }}</div>
                                    </div>
                                    <div class="quotation-field">
                                        <div class="quotation-field-label">Sub Total</div>
                                        <div class="quotation-field-value">Rp {{ number_format($quotation->total_amount ?? 0, 0, ',', '.') }}</div>
                                    </div>
                                   <!-- <div class="quotation-field">
                                        <div class="quotation-field-label">PPN</div>
                                        <div class="quotation-field-value">Rp {{ number_format($quotation->tax_amount ?? 0, 0, ',', '.') }}</div>
                                    </div> -->
                                    <div class="quotation-field">
                                        <div class="quotation-field-label">Total Penawaran</div>
                                        <div class="quotation-field-value"><strong>Rp {{ number_format($quotation->grand_total ?? 0, 0, ',', '.') }}</strong></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quotation Detail Tab -->
                <div class="tab-pane fade" id="quotation-detail" role="tabpanel" aria-labelledby="quotation-detail-tab">
                    <div class="card" style="width: 100%; min-height: 500px;">
                        <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #1e3a8a;">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0" style="color: #1e3a8a;">
                                    <i class="fas fa-list-alt me-2"></i>
                                    Quotation Detail
                                </h5>
                                @php
                                    $canApprove = auth()->user()->canApprove('quotations');
                                @endphp
                                @if($canApprove && $quotation->status === 'waiting_for_approval')
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="checkComplimentFee" style="width: 18px; height: 18px; cursor: pointer;">
                                    <label class="form-check-label" for="checkComplimentFee" style="margin-left: 8px; cursor: pointer; font-weight: 600; color: #495057;">
                                        Check Compliment/Supporter Fee
                                    </label>
                                </div>
                                @endif
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="overflow-x: auto; max-width: 100%;">
                                <table class="table table-bordered table-striped" id="quotationDetailsTable" style="min-width: 1550px; white-space: nowrap;">
                                    <thead>
                                        <tr>
                                            <th data-no-filter>No</th>
                                            <th data-column="surveyDetail.survey.survey_number">Survey No</th>
                                            <th data-column="building">Building</th>
                                            <th data-column="surveyDetail.room_name">Nama Ruangan</th>
                                            <th data-column="specification">Spesifikasi</th>
                                            <th data-column="remark">Remark</th>
                                            <th data-column="masterRental.name">Rental</th>
                                            <th data-column="qty" data-type="numeric">Qty</th>
                                            <th data-column="qty_free" data-type="numeric">Qty Free</th>
                                            <th data-column="rental_price" data-type="numeric">Harga Rental / Bulan</th>
                                            <th data-column="total_price" data-type="numeric">Total</th>
                                            @if($quotation->status === 'draft')
                                            <th data-no-filter style="width: 100px; text-align: center;">Action</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $sanitizeRenewalRoomName = static function ($value): string {
                                                return trim((string) preg_replace('/\s*Aroma\s*Lama\s*:.*$/iu', '', (string) ($value ?? '')));
                                            };

                                            $decodeSpecs = static function ($value): array {
                                                if (is_array($value)) {
                                                    return $value;
                                                }

                                                if (is_string($value) && trim($value) !== '') {
                                                    $decoded = json_decode($value, true);
                                                    return is_array($decoded) ? $decoded : [];
                                                }

                                                return [];
                                            };

                                            $hasMeaningfulSpecs = static function (array $specs): bool {
                                                foreach (['floor', 'intensity', 'installation_type', 'length', 'width', 'height', 'remark'] as $key) {
                                                    $value = trim((string) ($specs[$key] ?? ''));
                                                    if ($value !== '' && $value !== '-') {
                                                        return true;
                                                    }
                                                }

                                                return false;
                                            };

                                            $masterRoomSpecs = static function ($room): array {
                                                if (!$room) {
                                                    return [];
                                                }

                                                return [
                                                    'floor' => $room->room_floor,
                                                    'intensity' => $room->room_intensity,
                                                    'installation_type' => $room->room_installation_type,
                                                    'qty' => $room->room_qty,
                                                    'length' => $room->room_length,
                                                    'width' => $room->room_width,
                                                    'height' => $room->room_height,
                                                    'temperature' => $room->room_temperature,
                                                    'remark' => $room->room_remark,
                                                ];
                                            };

                                            $resolveQuotationRoom = function ($detail, string $displayRoomName) use ($quotation, $sanitizeRenewalRoomName) {
                                                $masterRoomId = $detail->room?->room_id;

                                                if ($masterRoomId) {
                                                    $matched = $quotation->quotationRooms->firstWhere('room_id', $masterRoomId);
                                                    if ($matched) {
                                                        return $matched;
                                                    }
                                                }

                                                if ($detail->room_id) {
                                                    $matched = $quotation->quotationRooms->firstWhere('room_id', $detail->room_id);
                                                    if ($matched) {
                                                        return $matched;
                                                    }
                                                }

                                                if ($displayRoomName !== '') {
                                                    return $quotation->quotationRooms->first(function ($room) use ($displayRoomName, $sanitizeRenewalRoomName) {
                                                        return mb_strtolower($sanitizeRenewalRoomName($room->room_name)) === mb_strtolower($displayRoomName);
                                                    });
                                                }

                                                return null;
                                            };

                                            $resolveContractRoom = function ($detail, string $displayRoomName, $quotationRoom) use ($quotation, $sanitizeRenewalRoomName) {
                                                $contract = $quotation->existingContract;
                                                if (!$contract) {
                                                    return null;
                                                }

                                                $masterRoomId = $quotationRoom?->room_id ?: $detail->room?->room_id;
                                                if ($masterRoomId) {
                                                    $matched = $contract->contractRooms->firstWhere('room_id', $masterRoomId);
                                                    if ($matched) {
                                                        return $matched;
                                                    }
                                                }

                                                if ($displayRoomName !== '') {
                                                    return $contract->contractRooms->first(function ($contractRoom) use ($displayRoomName, $sanitizeRenewalRoomName) {
                                                        return mb_strtolower($sanitizeRenewalRoomName($contractRoom->room?->room_name)) === mb_strtolower($displayRoomName);
                                                    });
                                                }

                                                return null;
                                            };
                                        @endphp
                                        @forelse($quotation->quotationDetails as $index => $detail)
                                        <tr>
                                            @php
                                                $displayRoomName = $sanitizeRenewalRoomName($detail->room_name ?? '');
                                                $quotationRoom = $resolveQuotationRoom($detail, $displayRoomName);
                                                $contractRoom = $resolveContractRoom($detail, $displayRoomName, $quotationRoom);
                                                $contractRental = null;

                                                if ($contractRoom && $quotation->existingContract) {
                                                    $contractRental = $quotation->existingContract->contractRentals
                                                        ->firstWhere('room_id', $contractRoom->room_id);
                                                }
                                                $contractRoomRental = $contractRental?->masterRental ?: $contractRoom?->rentalProduct;

                                                $specs = $decodeSpecs($detail->specifications ?? '{}');
                                                if (!$hasMeaningfulSpecs($specs) && $detail->room) {
                                                    $specs = $decodeSpecs($detail->room->specifications ?? '{}');
                                                }
                                                if (!$hasMeaningfulSpecs($specs) && $quotationRoom?->room) {
                                                    $specs = $masterRoomSpecs($quotationRoom->room);
                                                }
                                                if (!$hasMeaningfulSpecs($specs) && $contractRoom?->room) {
                                                    $specs = $masterRoomSpecs($contractRoom->room);
                                                }

                                                $displayRentalName = $detail->rental_alias
                                                    ?? ($contractRental?->rental_alias ?: null)
                                                    ?? ($contractRoomRental?->rental_name ?: null)
                                                    ?? ($detail->masterRental->rental_name ?? '-');
                                            @endphp
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                @php
                                                    // Show the survey that owns this room/detail, not every
                                                    // survey attached to the quotation.
                                                    $surveys = collect();

                                                    if ($detail->survey) {
                                                        $surveys->push($detail->survey);
                                                    } elseif ($detail->room && $detail->room->survey) {
                                                        $surveys->push($detail->room->survey);
                                                    } else {
                                                        $roomName = trim(mb_strtolower($displayRoomName));
                                                        $matchedSurveys = collect();

                                                        if ($roomName !== '') {
                                                            foreach ($quotation->quotationSurveys as $quotationSurvey) {
                                                                $survey = $quotationSurvey->survey;
                                                                if (!$survey) {
                                                                    continue;
                                                                }

                                                                $hasMatchingRoom = $survey->surveyDetails
                                                                    ->contains(function ($surveyDetail) use ($roomName) {
                                                                        return trim(mb_strtolower($surveyDetail->room_name ?? '')) === $roomName;
                                                                    });

                                                                if ($hasMatchingRoom) {
                                                                    $matchedSurveys->push($survey);
                                                                }
                                                            }
                                                        }

                                                        if ($matchedSurveys->unique('id')->count() === 1) {
                                                            $surveys->push($matchedSurveys->first());
                                                        }
                                                    }

                                                    $surveys = $surveys->unique('id');
                                                @endphp
                                                
                                                @forelse($surveys as $survey)
                                                    <a href="{{ route('marketing.surveys.show', $survey->id) }}" class="text-primary fw-bold">
                                                        {{ $survey->survey_number }}
                                                        <i class="fas fa-external-link-alt fa-xs ms-1"></i>
                                                    </a>{{ !$loop->last ? ',' : '' }}
                                                @empty
                                                    -
                                                @endforelse
                                            </td>
                                            <td>
                                                @php
                                                    $displayBuilding = $quotationRoom?->room?->building
                                                        ?? $detail->survey?->building
                                                        ?? $detail->room?->survey?->building
                                                        ?? $surveys->first()?->building;
                                                    $buildingName = $displayBuilding?->nama_gedung ?: $displayBuilding?->name;
                                                    $buildingAddress = $displayBuilding?->alamat_1 ?: $displayBuilding?->address;
                                                @endphp

                                                @if($displayBuilding)
                                                    <strong>{{ $buildingName ?: '-' }}</strong>
                                                    @if($buildingAddress)
                                                        <br>
                                                        <small class="text-muted">{{ $buildingAddress }}</small>
                                                    @endif
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>
                                                {{ $displayRoomName !== '' ? $displayRoomName : '-' }}
                                                @if($quotationRoom && $quotationRoom->aromaProduct)
                                                    <br>
                                                    <small class="text-success">
                                                        <i class="fas fa-leaf me-1"></i>
                                                        <strong>Aroma:</strong> {{ $quotationRoom->aromaProduct->name }}
                                                        @if($quotationRoom->aroma_variant)
                                                            - {{ $quotationRoom->aroma_variant }}
                                                        @endif
                                                    </small>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="spec-details">
                                                    <div><strong>Lantai:</strong> {{ $specs['floor'] ?? '-' }}</div>
                                                    <div><strong>Wangi:</strong> {{ $specs['intensity'] ?? '-' }}</div>
                                                    <div><strong>Installation:</strong> {{ $specs['installation_type'] ?? '-' }}</div>
                                                    <div><strong>Dimensi:</strong> {{ $specs['length'] ?? '-' }} x {{ $specs['width'] ?? '-' }} x {{ $specs['height'] ?? '-' }}</div>
                                                    @if($specs['remark'] ?? false)
                                                        <div><strong>Remark:</strong> {{ $specs['remark'] }}</div>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                @if($detail->remark)
                                                    <div class="text-muted" style="font-size: 0.9rem;">
                                                        <i class="fas fa-comment-alt me-1"></i>
                                                        {{ $detail->remark }}
                                                    </div>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>{{ $displayRentalName }}</td>
                                            <td>{{ $detail->quantity ?? '-' }}</td>
                                            <td>{{ $detail->qty_free ?? 0 }}</td>
                                            <td>Rp {{ number_format($detail->unit_price ?? 0, 0, ',', '.') }}</td>
                                            <td>Rp {{ number_format($detail->total_price ?? 0, 0, ',', '.') }}</td>
                                            @if($quotation->status === 'draft')
                                            <td style="text-align: center;">
                                                <button class="btn btn-sm btn-danger" onclick="removeRoom({{ $detail->id }}, '{{ $displayRoomName }}')" title="Hapus Room">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                            @endif
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="{{ $quotation->status === 'draft' ? '12' : '11' }}" class="text-center text-muted">
                                                <i class="fas fa-info-circle me-2"></i>
                                                No quotation details found.
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="10"><strong>Sub Total</strong></td>
                                            <td><strong>Rp {{ number_format($quotation->total_amount ?? 0, 0, ',', '.') }}</strong></td>
                                            @if($quotation->status === 'draft')
                                            <td></td>
                                            @endif
                                        </tr>
                                        <!--<tr>
                                            <td colspan="{{ $quotation->status === 'draft' ? '8' : '7' }}"><strong>PPN</strong></td>
                                            <td><strong>Rp {{ number_format($quotation->tax_amount ?? 0, 0, ',', '.') }}</strong></td>
                                        </tr> -->
                                        <tr>
                                            <td colspan="10"><strong>Grand Total</strong></td>
                                            <td><strong>Rp {{ number_format($quotation->grand_total ?? 0, 0, ',', '.') }}</strong></td>
                                            @if($quotation->status === 'draft')
                                            <td></td>
                                            @endif
                                        </tr>
                                    </tfoot>
                                </table>
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
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/airbnb.css">
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
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
$(document).ready(function() {
    console.log('Quotation show page loaded');
    
    // Initialize tab state - ensure only Basic Info is active
    $('.tab-pane').removeClass('show active').css('display', 'none');
    $('#basic-info').addClass('show active').css('display', 'block');
    
    // Skip DataTable initialization to avoid column count issues
    console.log('Skipping DataTable initialization to avoid column count issues');

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

document.addEventListener('DOMContentLoaded', function() {
    const quotationDateInput = document.getElementById('quotationDateInput');

    if (!quotationDateInput) {
        return;
    }

    if (typeof flatpickr !== 'undefined') {
        flatpickr(quotationDateInput, {
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd/M/Y',
            allowInput: false,
            clickOpens: true,
            defaultDate: quotationDateInput.value || null,
            onChange: function(selectedDates, dateStr) {
                updateQuotationDate(dateStr, quotationDateInput);
            }
        });

        return;
    }

    quotationDateInput.type = 'date';
    quotationDateInput.readOnly = false;
    quotationDateInput.addEventListener('change', function() {
        updateQuotationDate(this.value, this);
    });
});

function updateQuotationDate(newDate, input = document.getElementById('quotationDateInput')) {
    if (!newDate || !input) {
        return;
    }

    const originalValue = input.value;
    input.disabled = true;

    fetch('{{ url("marketing/quotations/{$quotation->id}/editable-fields") }}', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        },
        body: JSON.stringify({
            quotation_date: newDate
        })
    })
    .then(async response => {
        const data = await response.json();

        if (!response.ok || data.status !== 'success') {
            throw new Error(data.message || 'Gagal memperbarui Tanggal Quotation');
        }

        input.value = data.data?.quotation_date || newDate;
        Swal.fire({
            icon: 'success',
            title: 'Berhasil',
            text: data.message || 'Tanggal Quotation berhasil diperbarui',
            timer: 1800,
            showConfirmButton: false
        });
    })
    .catch(error => {
        input.value = originalValue;
        Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: error.message || 'Gagal memperbarui Tanggal Quotation'
        });
    })
    .finally(() => {
        input.disabled = false;
    });
}

// Finalize quotation function with operational area validation
async function finalizeQuotation() {
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
        // Get building ID from quotation's surveys
        @php
            $surveyId = null;
            if ($quotation->quotationSurveys->isNotEmpty()) {
                $survey = $quotation->quotationSurveys->first()?->survey;
                $surveyId = $survey?->id;
            }
        @endphp
        
        const surveyId = {{ $surveyId ?? 'null' }};
        
        if (!surveyId) {
            // If no survey, skip validation and proceed
            proceedWithFinalize();
            return;
        }
        
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
        proceedWithFinalize();
        
    } catch (error) {
        console.error('Error checking operational area:', error);
        Swal.fire({
            title: 'Error',
            text: 'Gagal mengecek operational area: ' + error.message,
            icon: 'error',
            confirmButtonText: 'OK'
        });
    }
}

// Proceed with finalize after validation passes
function proceedWithFinalize() {
    Swal.fire({
        title: 'Finalize Quotation',
        text: 'Apakah Anda yakin ingin memfinalisasi quotation ini? Quotation akan dikirim untuk persetujuan.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Finalisasi!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            // Send finalize request
            $.ajax({
                url: '{{ route("marketing.quotations.finalize", $quotation->id) }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    Swal.fire('Berhasil!', 'Quotation telah difinalisasi dan dikirim untuk persetujuan', 'success').then(() => {
                        location.reload();
                    });
                },
                error: function(xhr) {
                    Swal.fire('Error!', 'Gagal memfinalisasi quotation', 'error');
                }
            });
        }
    });
}

// Approve quotation function
function approveQuotation() {
    // Check if checkbox is checked
    const checkComplimentFee = document.getElementById('checkComplimentFee');
    
    if (!checkComplimentFee || !checkComplimentFee.checked) {
        // Show sweet alert with option to check checkbox
        Swal.fire({
            title: 'Checklist Wajib',
            html: `
                <div style="text-align: left;">
                    <p style="margin-bottom: 15px;">Anda harus mencentang checkbox <strong>"Check Compliment/Supporter Fee"</strong> di tab Quotation Detail terlebih dahulu sebelum dapat menyetujui quotation.</p>
                    <div style="background-color: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid #dee2e6;">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="swalCheckComplimentFee" style="width: 18px; height: 18px; cursor: pointer;">
                            <label class="form-check-label" for="swalCheckComplimentFee" style="margin-left: 8px; cursor: pointer; font-weight: 600;">
                                Check Compliment/Supporter Fee
                            </label>
                        </div>
                    </div>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Centang & Setujui',
            cancelButtonText: 'Batal',
            allowOutsideClick: false,
            preConfirm: () => {
                const swalCheckbox = document.getElementById('swalCheckComplimentFee');
                if (!swalCheckbox || !swalCheckbox.checked) {
                    Swal.showValidationMessage('Anda harus mencentang checkbox terlebih dahulu!');
                    return false;
                }
                return true;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Check the actual checkbox if user checked in sweet alert
                if (checkComplimentFee) {
                    checkComplimentFee.checked = true;
                }
                
                // Proceed with approval
                Swal.fire({
                    title: 'Approve Quotation',
                    text: 'Apakah Anda yakin ingin menyetujui quotation ini?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Setujui!',
                    cancelButtonText: 'Batal'
                }).then((confirmResult) => {
                    if (confirmResult.isConfirmed) {
                        // Send approval request
                        $.ajax({
                            url: '{{ route("marketing.quotations.approve", $quotation->id) }}',
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                Swal.fire('Berhasil!', 'Quotation telah disetujui', 'success').then(() => {
                                    location.reload();
                                });
                            },
                            error: function(xhr) {
                                Swal.fire('Error!', 'Gagal menyetujui quotation', 'error');
                            }
                        });
                    }
                });
            }
        });
        return;
    }
    
    // If checkbox is already checked, proceed with normal approval flow
    Swal.fire({
        title: 'Approve Quotation',
        text: 'Apakah Anda yakin ingin menyetujui quotation ini?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Setujui!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            // Send approval request
            $.ajax({
                url: '{{ route("marketing.quotations.approve", $quotation->id) }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    Swal.fire('Berhasil!', 'Quotation telah disetujui', 'success').then(() => {
                        location.reload();
                    });
                },
                error: function(xhr) {
                    Swal.fire('Error!', 'Gagal menyetujui quotation', 'error');
                }
            });
        }
    });
}

// Cancel quotation function
function cancelQuotation() {
    Swal.fire({
        title: 'Cancel Quotation',
        input: 'textarea',
        inputLabel: 'Alasan Pembatalan',
        inputPlaceholder: 'Masukkan alasan pembatalan...',
        inputValidator: (value) => {
            if (!value) {
                return 'Alasan pembatalan harus diisi!'
            }
        },
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Batalkan!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            // Send cancellation request
            $.ajax({
                url: '{{ route("marketing.quotations.cancel", $quotation->id) }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    reason: result.value
                },
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire('Berhasil!', response.message || 'Quotation telah dibatalkan', 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error!', response.message || 'Gagal membatalkan quotation', 'error');
                    }
                },
                error: function(xhr) {
                    var errorMsg = 'Gagal membatalkan quotation';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    Swal.fire('Error!', errorMsg, 'error');
                }
            });
        }
    });
}

// Delete quotation function
function deleteQuotation() {
    Swal.fire({
        title: 'Hapus Quotation?',
        text: 'Apakah Anda yakin ingin menghapus quotation ini? Tindakan ini tidak dapat dibatalkan!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal',
        input: 'textarea',
        inputLabel: 'Alasan Penghapusan (Opsional)',
        inputPlaceholder: 'Masukkan alasan penghapusan...',
        inputAttributes: {
            'aria-label': 'Alasan penghapusan'
        },
        showLoaderOnConfirm: true,
        preConfirm: (reason) => {
            return fetch('{{ route("marketing.quotations.destroy", $quotation->id) }}', {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    reason: reason || null
                })
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        throw new Error(err.message || 'Gagal menghapus quotation');
                    });
                }
                return response.json();
            })
            .catch(error => {
                Swal.showValidationMessage(error.message || 'Gagal menghapus quotation');
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Berhasil!',
                text: 'Quotation telah dihapus',
                icon: 'success',
                confirmButtonText: 'OK'
            }).then(() => {
                window.location.href = document.getElementById('quotationBackToList')?.href || '{{ route("marketing.quotations.index") }}';
            });
        }
    });
}

// Remove room from quotation detail
function removeRoom(detailId, roomName) {
    Swal.fire({
        title: 'Hapus Room?',
        html: `Apakah Anda yakin ingin menghapus room <strong>${roomName}</strong> dari quotation ini?<br><br>Total quotation akan otomatis dihitung ulang.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            Swal.fire({
                title: 'Menghapus...',
                text: 'Mohon tunggu',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Send delete request
            const deleteUrl = `/marketing/quotations/{{ $quotation->id }}/details/${detailId}`;
            fetch(deleteUrl, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(result => {
                if (result.status === 'success') {
                    Swal.fire({
                        title: 'Berhasil!',
                        text: result.message || 'Room berhasil dihapus',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: result.message || 'Gagal menghapus room',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error!',
                    text: 'Terjadi kesalahan saat menghapus room',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            });
        }
    });
}

// MOM9: Open Job Advice Modal from Quotation
function openJobAdviceModalFromQuotation(quotationId) {
    window.location.href = '{{ route("marketing.job-advices.index") }}?quotation_id=' + quotationId + '&type=install_free&open_modal=true';
}

// Goal SQ Auto-save logic
$(document).on('change', '.autosave-goal', function() {
    var id = $(this).data('id');
    var value = $(this).val();
    
    if (!id) return;

    // Show loading toast
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });

    $.ajax({
        url: '{{ route("marketing.quotations.update-goal", $quotation->id) }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            goal_sq: value
        },
        success: function(response) {
            if (response.status === 'success') {
                Toast.fire({
                    icon: 'success',
                    title: 'Goal SQ berhasil diperbarui'
                });
            } else {
                Toast.fire({
                    icon: 'error',
                    title: response.message || 'Gagal memperbarui Goal SQ'
                });
            }
        },
        error: function(xhr) {
            var errorMsg = 'Gagal memperbarui Goal SQ';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            }
            Toast.fire({
                icon: 'error',
                title: errorMsg
            });
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const backLink = document.getElementById('quotationBackToList');
    const quotationIndexPath = @json(parse_url(route('marketing.quotations.index'), PHP_URL_PATH));
    const hasReturnUrl = new URLSearchParams(window.location.search).has('return_url');
    const storedListUrl = sessionStorage.getItem('aroma:list:marketing.quotations');

    if (!backLink || hasReturnUrl || !storedListUrl) {
        return;
    }

    try {
        const storedUrl = new URL(storedListUrl, window.location.origin);
        if (storedUrl.origin === window.location.origin && storedUrl.pathname === quotationIndexPath) {
            backLink.href = storedUrl.toString();
        }
    } catch (error) {
        console.warn('Invalid quotation list URL state:', error);
    }
});
</script>
@endpush
