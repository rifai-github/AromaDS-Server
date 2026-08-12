@extends('layouts.app')

@section('title', 'Contract Detail')
@section('breadcrumb', 'Home / Marketing / Contract / Detail')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/airbnb.css">
<style>
    .flatpickr-calendar {
        z-index: 1060 !important;
    }
</style>
@endpush

@section('content')
<style>
    .contract-layout-fix {
        display: flex !important;
        flex-wrap: wrap !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .contract-layout-fix .col-lg-6 {
        flex: 0 0 50% !important;
        max-width: 50% !important;
        padding: 15px !important;
        display: block !important;
    }
    .contract-card {
        height: 100% !important;
        border: 1px solid #dee2e6 !important;
        border-radius: 8px !important;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
    }
    .contract-card-header {
        background-color: #6c757d !important;
        color: white !important;
        padding: 1rem 1.5rem !important;
        border-bottom: 1px solid rgba(0, 0, 0, 0.125) !important;
        border-radius: 8px 8px 0 0 !important;
    }
    .contract-card-body {
        padding: 1.5rem !important;
    }
    .contract-field {
        margin-bottom: 1rem !important;
        display: flex !important;
        align-items: center !important;
    }
    .contract-field-label {
        flex: 0 0 40% !important;
        font-weight: bold !important;
        color: #495057 !important;
    }
    .contract-field-value {
        flex: 0 0 60% !important;
        color: #6c757d !important;
    }

    .contract-status-badge {
        display: inline-flex !important;
        align-items: center !important;
        min-width: 72px !important;
        justify-content: center !important;
        padding: 0.4rem 0.75rem !important;
        border-radius: 999px !important;
        font-size: 0.82rem !important;
        font-weight: 700 !important;
        letter-spacing: 0.01em !important;
        line-height: 1.1 !important;
    }
    
    /* Tab Content Fix */
    .tab-content {
        width: 100% !important;
        min-height: 500px !important;
    }
    
    .tab-pane {
        width: 100% !important;
        min-height: 500px !important;
    }
    
    #contract-detail {
        width: 100% !important;
        min-height: 500px !important;
    }
    
    @media (max-width: 991.98px) {
        .contract-layout-fix .col-lg-6 {
            flex: 0 0 100% !important;
            max-width: 100% !important;
        }
    }
    
    /* Contract Notes Responsive - Full Width */
    .contract-notes-row {
        width: 100% !important;
        max-width: 100% !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
        padding-left: 15px !important;
        padding-right: 15px !important;
    }
    
    .contract-notes-row .col-12 {
        width: 100% !important;
        max-width: 100% !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
        flex: 0 0 100% !important;
    }
    
    .contract-notes-row .card {
        width: 100% !important;
        max-width: 100% !important;
    }
    
    /* Tab Navigation */
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
    
    /* Table Styles */
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
    
    .btn-sm {
        padding: 0.375rem 0.75rem !important;
        font-size: 0.875rem !important;
    }
    
    /* Tab Content Visibility */
    .tab-content .tab-pane {
        display: none !important;
    }
    
    .tab-content .tab-pane.active {
        display: block !important;
    }
    
    .tab-content .tab-pane.show.active {
        display: block !important;
    }
    
    /* Modal Z-Index Fix - Ensure modals appear above all content */
    body .modal {
        z-index: 1055 !important;
    }
    
    body .modal-backdrop {
        z-index: 1050 !important;
    }
    
    /* Ensure modal is centered */
    body .modal-dialog {
        margin: 1.75rem auto;
    }
    
    /* Ensure modal body scrolls properly */
    .modal-body {
        max-height: calc(100vh - 210px);
        overflow-y: auto;
    }
    
    /* Critical: Let Bootstrap control modal display */
    #billingGroupModal.modal.show,
    #manageBuildingsModal.modal.show,
    #roomModal.modal.show {
        display: block !important;
    }
    
    /* Room Modal Specific Styling - Ensure centered popup */
    #roomModal {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 100% !important;
        height: 100% !important;
        z-index: 1055 !important;
        overflow-x: hidden !important;
        overflow-y: auto !important;
        outline: 0 !important;
        background-color: rgba(0, 0, 0, 0.5) !important; /* Add backdrop as background */
    }
    
    #roomModal .modal-dialog {
        position: relative !important;
        display: flex !important;
        align-items: center !important;
        min-height: calc(100% - 1rem) !important;
        max-width: 800px !important;
        margin: 0.5rem auto !important;
        pointer-events: auto !important;
    }
    
    #roomModal .modal-content {
        position: relative !important;
        display: flex !important;
        flex-direction: column !important;
        width: 100% !important;
        pointer-events: auto !important;
        background-color: #fff !important;
        background-clip: padding-box !important;
        border: 1px solid rgba(0, 0, 0, 0.2) !important;
        border-radius: 0.5rem !important;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.5) !important;
    }
    
    #roomModal .modal-header {
        display: flex !important;
        flex-shrink: 0 !important;
        align-items: center !important;
        justify-content: space-between !important;
        padding: 1rem !important;
        border-bottom: 1px solid #dee2e6 !important;
        border-top-left-radius: 0.5rem !important;
        border-top-right-radius: 0.5rem !important;
    }
    
    #roomModal .modal-body {
        position: relative !important;
        flex: 1 1 auto !important;
        padding: 1rem !important;
        background-color: #fff !important;
    }
    
    #roomModal .modal-footer {
        display: flex !important;
        flex-wrap: wrap !important;
        flex-shrink: 0 !important;
        align-items: center !important;
        justify-content: flex-end !important;
        padding: 0.75rem !important;
        background-color: #fff !important;
        border-top: 1px solid #dee2e6 !important;
        border-bottom-right-radius: 0.5rem !important;
        border-bottom-left-radius: 0.5rem !important;
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
                            <a href="{{ route('marketing.contracts.index') }}" class="btn btn-light btn-sm">
                                <i class="fas fa-arrow-left"></i> Back to List
                            </a>
                        </div>
                        <div>
                            <h3 class="card-title mb-0" style="color: white; font-size: 1.5rem; font-weight: bold;">
                                {{ $contract->contract_number }} - {{ $contract->customer->name ?? 'N/A' }}
                            </h3>
                        </div>
                        <div>
                            @if($contract->contract_status == 'draft')
                                <button class="btn btn-primary btn-sm me-2" onclick="saveDraft({{ $contract->id }})">
                                    <i class="fas fa-save"></i> SAVE DRAFT
                                </button>
                                <button class="btn btn-success btn-sm" onclick="finalizeContract({{ $contract->id }})">
                                    <i class="fas fa-check"></i> FINALIZE CONTRACT
                                </button>
                            @elseif($contract->contract_status == 'waiting_for_approval')
                                @php
                                    $canApproveContract = auth()->user()->canApprove('contracts');
                                @endphp
                                
                                @if($canApproveContract)
                                    <button class="btn btn-success btn-sm me-2" onclick="approveContract({{ $contract->id }})">
                                        <i class="fas fa-check-circle"></i> APPROVE
                                    </button>
                                @endif
                                <button class="btn btn-warning btn-sm" onclick="unpostContract({{ $contract->id }})">
                                    <i class="fas fa-undo"></i> UNPOST
                                </button>
                                
                                @if(!$canApproveContract)
                                    <div class="mt-2">
                                        <span class="badge bg-warning text-dark fs-6">
                                            <i class="fas fa-clock"></i> Waiting for Approval
                                        </span>
                                    </div>
                                @endif
                            @elseif($contract->contract_status == 'active')
                                <span class="badge badge-success fs-6 me-2">
                                    Active
                                </span>
                                <button class="btn btn-primary btn-sm me-2" onclick="openActiveContractEditModal()">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn btn-warning btn-sm" onclick="unpostContract({{ $contract->id }})">
                                    <i class="fas fa-undo"></i> UNPOST
                                </button>
                            @else
                                <span class="badge badge-{{ $contract->contract_status == 'active' ? 'success' : ($contract->contract_status == 'running' ? 'primary' : ($contract->contract_status == 'terminated' ? 'danger' : 'secondary')) }} fs-6">
                                    {{ ucfirst(str_replace('_', ' ', $contract->contract_status)) }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Tabs -->
            <div class="card mb-3">
                <div class="card-body p-0">
                    <ul class="nav nav-tabs" id="contractTabs" role="tablist" style="border-bottom: 2px solid #1e3a8a; margin: 0; display: flex; flex-direction: row;">
                        <li class="nav-item" role="presentation" style="flex: 1;">
                            <button class="nav-link active" id="basic-info-tab" data-bs-toggle="tab" data-bs-target="#basic-info" type="button" role="tab" aria-controls="basic-info" aria-selected="true" style="border-bottom: 3px solid #1e3a8a; color: #1e3a8a; font-weight: bold; padding: 12px 20px; width: 100%; text-align: center;">
                                <i class="fas fa-info-circle me-2"></i>BASIC INFO
                            </button>
                        </li>
                        <li class="nav-item" role="presentation" style="flex: 1;">
                            <button class="nav-link" id="additional-info-tab" data-bs-toggle="tab" data-bs-target="#additional-info" type="button" role="tab" aria-controls="additional-info" aria-selected="false" style="color: #6c757d; padding: 12px 20px; width: 100%; text-align: center;">
                                <i class="fas fa-info me-2"></i>ADDITIONAL INFO
                            </button>
                        </li>
                        <li class="nav-item" role="presentation" style="flex: 1;">
                            <button class="nav-link" id="rooms-tab" data-bs-toggle="tab" data-bs-target="#rooms" type="button" role="tab" aria-controls="rooms" aria-selected="false" style="color: #6c757d; padding: 12px 20px; width: 100%; text-align: center;">
                                <i class="fas fa-door-open me-2"></i>ROOMS
                            </button>
                        </li>
                        <li class="nav-item" role="presentation" style="flex: 1;">
                            <button class="nav-link" id="rentals-tab" data-bs-toggle="tab" data-bs-target="#rentals" type="button" role="tab" aria-controls="rentals" aria-selected="false" style="color: #6c757d; padding: 12px 20px; width: 100%; text-align: center;">
                                <i class="fas fa-boxes me-2"></i>RENTALS
                            </button>
                        </li>
                        <li class="nav-item" role="presentation" style="flex: 1;">
                            <button class="nav-link" id="billing-group-tab" data-bs-toggle="tab" data-bs-target="#billing-group" type="button" role="tab" aria-controls="billing-group" aria-selected="false" style="color: #6c757d; padding: 12px 20px; width: 100%; text-align: center;">
                                <i class="fas fa-credit-card me-2"></i>BILLING GROUP
                            </button>
                        </li>
                        <li class="nav-item" role="presentation" style="flex: 1;">
                            <button class="nav-link" id="buildings-tab" data-bs-toggle="tab" data-bs-target="#buildings" type="button" role="tab" aria-controls="buildings" aria-selected="false" style="color: #6c757d; padding: 12px 20px; width: 100%; text-align: center;">
                                <i class="fas fa-building me-2"></i>BUILDING(S)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation" style="flex: 1;">
                            <button class="nav-link" id="files-tab" data-bs-toggle="tab" data-bs-target="#files" type="button" role="tab" aria-controls="files" aria-selected="false" style="color: #6c757d; padding: 12px 20px; width: 100%; text-align: center;">
                                <i class="fas fa-file me-2"></i>FILE(S)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation" style="flex: 1;">
                            <button class="nav-link" id="remarks-tab" data-bs-toggle="tab" data-bs-target="#remarks" type="button" role="tab" aria-controls="remarks" aria-selected="false" style="color: #6c757d; padding: 12px 20px; width: 100%; text-align: center;">
                                <i class="fas fa-comments me-2"></i>REMARKS
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Tab Content -->
            <div class="tab-content" id="contractTabsContent">
                <!-- Basic Info Tab -->
                <div class="tab-pane fade show active" id="basic-info" role="tabpanel" aria-labelledby="basic-info-tab">
                    <div class="row contract-layout-fix">
                        <!-- Contract Information Section 1 -->
                        <div class="col-lg-6 mb-4">
                            <div class="card contract-card">
                                <div class="contract-card-header">
                                    <h5 class="card-title mb-0">
                                        Contract Information
                                    </h5>
                                </div>
                                <div class="contract-card-body">
                                    <div class="contract-field">
                                        <div class="contract-field-label">Contract Number</div>
                                        <div class="contract-field-value">{{ $contract->contract_number }}</div>
                                    </div>
                                    @php
                                        // Old Contract: jika contract ini dibuat dari renewal quotation
                                        $showOldContract = $contract->quotation && $contract->quotation->existing_contract_id;
                                        $oldContractObj = $showOldContract ? $contract->quotation->existingContract : null;
                                        
                                        // Current Contract: cek via relasi renewedByContract (Quotation.existing_contract_id)
                                        $currentContractObj = $contract->renewedByContract;
                                        
                                        // Fallback: via ContractRenewal record
                                        if (!$currentContractObj) {
                                            $completedRenewal = $contract->renewals->where('status', 'completed')->whereNotNull('new_contract_id')->first();
                                            $currentContractObj = $completedRenewal ? $completedRenewal->newContract : null;
                                        }
                                    @endphp
                                    <div class="contract-field">
                                        <div class="contract-field-label">Old Contract</div>
                                        <div class="contract-field-value">
                                            @if($oldContractObj)
                                                <a href="{{ route('marketing.contracts.show', $oldContractObj->id) }}" class="text-primary fw-bold" target="_blank" rel="noopener noreferrer">
                                                    {{ $oldContractObj->contract_number }}
                                                    <i class="fas fa-external-link-alt fa-xs ms-1"></i>
                                                </a>
                                            @else
                                                {{ $contract->contract_number }}
                                            @endif
                                        </div>
                                    </div>
                                    <div class="contract-field">
                                        <div class="contract-field-label">Current Contract</div>
                                        <div class="contract-field-value">
                                            @if($currentContractObj)
                                                <a href="{{ route('marketing.contracts.show', $currentContractObj->id) }}" class="text-primary fw-bold" target="_blank" rel="noopener noreferrer">
                                                    {{ $currentContractObj->contract_number }}
                                                    <i class="fas fa-external-link-alt fa-xs ms-1"></i>
                                                </a>
                                            @else
                                                {{ $contract->contract_number }}
                                            @endif
                                        </div>
                                    </div>

                                    <!-- [NEW] Contract Merging Visibility -->
                                    @if($contract->mergedSources->isNotEmpty())
                                        <div class="contract-field" style="background-color: #f0fdf4; border-radius: 4px; padding: 5px;">
                                            <div class="contract-field-label font-weight-bold" style="color: #166534;">Merged From</div>
                                            <div class="contract-field-value">
                                                @foreach($contract->mergedSources as $source)
                                                    <a href="{{ route('marketing.contracts.show', $source->id) }}" class="badge badge-success mb-1" style="font-size: 0.85rem; padding: 5px 10px;" target="_blank" rel="noopener noreferrer">
                                                        <i class="fas fa-link mr-1"></i> {{ $source->contract_number }}
                                                    </a>
                                                    @if(!$loop->last) <br> @endif
                                                @endforeach
                                                <div class="text-xs text-muted mt-1" style="font-size: 0.75rem;">
                                                    <i class="fas fa-info-circle"></i> Kontrak ini gabungan dari {{ $contract->mergedSources->count() }} kontrak di atas.
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    @php
                                        // Cek apakah kontrak ini pernah digabung KE kontrak lain
                                        $mergedInto = \App\Models\ContractMerge::where('source_contract_id', $contract->id)->with('newContract')->first();
                                    @endphp
                                    @if($mergedInto && $mergedInto->newContract)
                                        <div class="contract-field" style="background-color: #fff1f2; border-radius: 4px; padding: 5px;">
                                            <div class="contract-field-label font-weight-bold" style="color: #991b1b;">Merged Into</div>
                                            <div class="contract-field-value text-red">
                                                <a href="{{ route('marketing.contracts.show', $mergedInto->newContract->id) }}" class="badge badge-danger" style="font-size: 0.85rem; padding: 5px 10px;" target="_blank" rel="noopener noreferrer">
                                                    <i class="fas fa-arrow-right mr-1"></i> {{ $mergedInto->newContract->contract_number }}
                                                </a>
                                                <div class="text-xs text-muted mt-1" style="font-size: 0.75rem;">
                                                    <i class="fas fa-exclamation-triangle"></i> Kontrak ini sudah digabung ke kontrak baru di atas.
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                    <div class="contract-field">
                                        <div class="contract-field-label">Status</div>
                                        <div class="contract-field-value">
                                            @php
                                                $contractStatus = $contract->contract_status ?? 'draft';
                                                $contractStatusClass = match ($contractStatus) {
                                                    'active', 'running' => 'bg-success text-white',
                                                    'contract' => 'bg-primary text-white',
                                                    'draft' => 'bg-warning text-dark',
                                                    'terminated', 'rejected' => 'bg-danger text-white',
                                                    'waiting_for_approval' => 'bg-info text-dark',
                                                    default => 'bg-secondary text-white',
                                                };
                                            @endphp
                                            <span class="badge contract-status-badge {{ $contractStatusClass }}">
                                                {{ ucwords(str_replace('_', ' ', $contractStatus)) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="contract-field">
                                        <div class="contract-field-label">Contract Type</div>
                                        <div class="contract-field-value">{{ $contract->display_contract_type }}</div>
                                    </div>
                                    <div class="contract-field">
                                        <div class="contract-field-label">Contract Date</div>
                                        <div class="contract-field-value">
                                            @if($contract->contract_status === 'draft')
                                                <div class="position-relative" style="max-width: 160px;">
                                                    <input type="text"
                                                           id="contractDateInput"
                                                           class="form-control form-control-sm pe-4"
                                                           value="{{ $contract->contract_date ? $contract->contract_date->format('Y-m-d') : '' }}"
                                                           placeholder="Select date"
                                                           readonly>
                                                    <i class="fas fa-calendar-alt text-muted small position-absolute"
                                                       style="right: 10px; top: 50%; transform: translateY(-50%); pointer-events: none;"></i>
                                                </div>
                                            @else
                                                {{ $contract->contract_date ? $contract->contract_date->format('d/M/Y') : '-' }}
                                            @endif
                                        </div>
                                    </div>
                                    <div class="contract-field">
                                        <div class="contract-field-label">Start Date</div>
                                        <div class="contract-field-value">{{ $contract->actual_start_date ? \Carbon\Carbon::parse($contract->actual_start_date)->format('d/M/Y') : '-' }}</div>
                                    </div>
                                    <div class="contract-field">
                                        <div class="contract-field-label">End Date</div>
                                        <div class="contract-field-value">{{ $contract->actual_end_date ? \Carbon\Carbon::parse($contract->actual_end_date)->format('d/M/Y') : '-' }}</div>
                                    </div>
                                    <div class="contract-field">
                                        <div class="contract-field-label">Contract Value</div>
                                        <div class="contract-field-value">Rp {{ number_format($contract->contract_value, 0, ',', '.') }}</div>
                                    </div>
                                    @php
                                        $hasViewNet = Auth::user()->hasPermission('marketing.contract-net.view') || Auth::user()->hasPermission('contractNet_approved');
                                        $hasEditNet = Auth::user()->hasPermission('marketing.contract-net.edit') || Auth::user()->hasPermission('marketing.contract-net.approve') || Auth::user()->hasPermission('contractNet_approved');
                                        $netValue = $contract->net_value ?? $contract->contract_value;
                                    @endphp

                                    @if($hasViewNet)
                                        <div class="contract-field">
                                            <div class="contract-field-label">Contract Net</div>
                                            <div class="contract-field-value">
                                                <div style="display: flex !important; align-items: center !important;">
                                                    <span style="margin-right: 8px;">Rp</span>
                                                    <div style="position: relative;">
                                                        <input type="number" 
                                                               id="contractNetInput" 
                                                               class="form-control form-control-sm @if($hasEditNet) border-primary @else border-0 bg-transparent @endif" 
                                                               value="{{ $netValue }}" 
                                                               @if(!$hasEditNet) readonly @endif
                                                               onchange="updateContractNet(this)"
                                                               style="width: 160px; @if(!$hasEditNet) cursor: default; padding: 0; background: transparent; @endif">
                                                        <span id="contractNetLoading" style="display: none; position: absolute; right: -25px; top: 50%; transform: translateY(-50%);">
                                                            <i class="fas fa-spinner fa-spin text-primary"></i>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                    <div class="contract-field">
                                        <div class="contract-field-label">Term of Payment</div>
                                        <div class="contract-field-value">{{ $contract->display_term_of_payment }}</div>
                                    </div>
                                    <div class="contract-field">
                                        <div class="contract-field-label">BA Files Supported</div>
                                        <div class="contract-field-value">
                                            <div class="btn-group btn-group-sm" role="group" aria-label="BA Files Supported Toggle">
                                                <button type="button" 
                                                        id="btnBaSupportedYes"
                                                        class="btn btn-{{ $contract->ba_files_supported ? 'success' : 'outline-success' }}" 
                                                        onclick="updateBaFilesSupported({{ $contract->id }}, true)">YES</button>
                                                <button type="button" 
                                                        id="btnBaSupportedNo"
                                                        class="btn btn-{{ !$contract->ba_files_supported ? 'danger' : 'outline-danger' }}" 
                                                        onclick="updateBaFilesSupported({{ $contract->id }}, false)">NO</button>
                                            </div>
                                            <small class="text-muted d-block mt-1" id="baSupportedStatus">
                                                {{ $contract->ba_files_supported ? 'Invoice requires BA Files' : 'Invoice can generate without BA Files' }}
                                            </small>
                                        </div>
                                    </div>
                                    <div class="contract-field">
                                        <div class="contract-field-label">Hold Invoice</div>
                                        <div class="contract-field-value">
                                            <div class="btn-group btn-group-sm" role="group" aria-label="Hold Invoice Toggle">
                                                <button type="button" 
                                                        id="btnHoldInvoiceYes"
                                                        class="btn btn-{{ $contract->hold_invoice ? 'warning' : 'outline-warning' }}" 
                                                        onclick="updateHoldInvoice({{ $contract->id }}, true)">YES</button>
                                                <button type="button" 
                                                        id="btnHoldInvoiceNo"
                                                        class="btn btn-{{ !$contract->hold_invoice ? 'success' : 'outline-success' }}" 
                                                        onclick="updateHoldInvoice({{ $contract->id }}, false)">NO</button>
                                            </div>
                                            <small class="text-muted d-block mt-1" id="holdInvoiceStatus">
                                                {{ $contract->hold_invoice ? 'Invoices are currently HELD' : 'Invoices are generated normally' }}
                                            </small>
                                        </div>
                                    </div>
                                    <div class="contract-field">
                                        <div class="contract-field-label">Contract</div>
                                        <div class="contract-field-value">
                                            @if(Auth::user()->hasPermission('marketing.contracts.target.update') || Auth::user()->hasPermission('marketing.contracts.target.create'))
                                                <div class="btn-group btn-group-sm" role="group" aria-label="Contract Target Toggle">
                                                    <button type="button" 
                                                            id="btnContractTargetYes"
                                                            class="btn btn-{{ $contract->is_contract ? 'success' : 'outline-secondary' }}" 
                                                            style="{{ $contract->is_contract ? 'color: white;' : 'color: #6c757d;' }}"
                                                            onclick="updateContractTarget({{ $contract->id }}, true)">YES</button>
                                                    <button type="button" 
                                                            id="btnContractTargetNo"
                                                            class="btn btn-{{ !$contract->is_contract ? 'danger' : 'outline-secondary' }}" 
                                                            style="{{ !$contract->is_contract ? 'color: white;' : 'color: #6c757d;' }}"
                                                            onclick="updateContractTarget({{ $contract->id }}, false)">NO</button>
                                                </div>
                                            @else
                                                <span class="badge badge-{{ $contract->is_contract ? 'success' : 'danger' }}">
                                                    {{ $contract->is_contract ? 'YES' : 'NO' }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Customer Information Section 2 -->
                        <div class="col-lg-6 mb-4">
                            <div class="card contract-card">
                                <div class="contract-card-header">
                                    <h5 class="card-title mb-0">
                                        Customer Information
                                    </h5>
                                </div>
                                <div class="contract-card-body">
                                    <div class="contract-field">
                                        <div class="contract-field-label">Customer Name</div>
                                        <div class="contract-field-value">
                                            @if($contract->customer_id)
                                                <a href="{{ route('company.customers.show', $contract->customer_id) }}" class="text-primary fw-bold" target="_blank" rel="noopener noreferrer">
                                                    {{ $contract->customer->name ?? '-' }}
                                                    <i class="fas fa-external-link-alt fa-xs ms-1"></i>
                                                </a>
                                            @else
                                                {{ $contract->customer->name ?? '-' }}
                                            @endif
                                        </div>
                                    </div>
                                    <div class="contract-field">
                                        <div class="contract-field-label">Customer Code</div>
                                        <div class="contract-field-value">{{ $contract->customer->customer_code ?? '-' }}</div>
                                    </div>
                                    <div class="contract-field">
                                        <div class="contract-field-label">Virtual Account</div>
                                        <div class="contract-field-value">{{ $contract->display_virtual_accounts }}</div>
                                    </div>
                                    <div class="contract-field">
                                        <div class="contract-field-label">Email</div>
                                        <div class="contract-field-value">{{ $contract->customer->email ?? '-' }}</div>
                                    </div>
                                    <div class="contract-field">
                                        <div class="contract-field-label">Phone</div>
                                        <div class="contract-field-value">{{ $contract->customer->phone ?? '-' }}</div>
                                    </div>
                                    <div class="contract-field">
                                        <div class="contract-field-label">Address</div>
                                        <div class="contract-field-value">{{ $contract->customer->address ?? '-' }}</div>
                                    </div>
                                    <div class="contract-field">
                                        <div class="contract-field-label">Company Type</div>
                                        <div class="contract-field-value">{{ strtoupper($contract->customer->company_type ?? '-') }}</div>
                                    </div>
                                    <div class="contract-field">
                                        <div class="contract-field-label">Member Since</div>
                                        <div class="contract-field-value">{{ $contract->customer->member_since ? $contract->customer->member_since->format('d/M/Y') : '-' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Marketing Information Section 3 -->
                        <div class="col-lg-6 mb-4">
                            <div class="card contract-card">
                                <div class="contract-card-header">
                                    <h5 class="card-title mb-0">
                                        Marketing Information
                                    </h5>
                                </div>
                                <div class="contract-card-body">
                                    <div class="contract-field">
                                        <div class="contract-field-label">Marketing Name</div>
                                        <div class="contract-field-value">{{ $contract->marketing->name ?? '-' }}</div>
                                    </div>
                                    <div class="contract-field">
                                        <div class="contract-field-label">Marketing Email</div>
                                        <div class="contract-field-value">{{ $contract->marketing->email ?? '-' }}</div>
                                    </div>
                                    <div class="contract-field">
                                        <div class="contract-field-label">Marketing Phone</div>
                                        <div class="contract-field-value">{{ $contract->marketing->handphone_1 ?? '-' }}</div>
                                    </div>
                                    <div class="contract-field">
                                        <div class="contract-field-label">Department</div>
                                        <div class="contract-field-value">{{ $contract->marketing->department_name ?? '-' }}</div>
                                    </div>
                                    <div class="contract-field">
                                        <div class="contract-field-label">Position</div>
                                        <div class="contract-field-value">{{ $contract->marketing->position_name ?? '-' }}</div>
                                    </div>
                                    <div class="contract-field">
                                        <div class="contract-field-label">Achiever</div>
                                        <div class="contract-field-value">
                                            @if($contract->contract_status === 'active')
                                                <select id="achieverSelect" 
                                                        class="form-select form-select-sm" 
                                                        style="min-width: 300px; display: inline-block;"
                                                        onchange="updateAchiever({{ $contract->id }}, this.value)">
                                                    @foreach(\App\Models\User::where('is_active', true)
                                                        ->where(function($q) use ($contract) {
                                                            $q->where('is_commission_achiever', true)
                                                              ->orWhere('id', $contract->commission_recipient_id ?? 0)
                                                              ->orWhere('id', $contract->marketing_id ?? 0);
                                                        })
                                                        ->orderBy('name')
                                                        ->get() as $user)
                                                        <option value="{{ $user->id }}" 
                                                            {{ ($contract->commission_recipient_id ?? $contract->marketing_id) == $user->id ? 'selected' : '' }}>
                                                            {{ $user->name }}{{ $user->is_commission_achiever ? ' ★' : '' }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <small class="text-muted d-block mt-1">Penerima komisi untuk kontrak ini (★ = Commission Achiever)</small>
                                            @else
                                                {{ $contract->commissionRecipient->name ?? $contract->marketing->name ?? '-' }}
                                                <small class="text-muted d-block">Status harus Active untuk mengubah</small>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quotation Information Section 4 -->
                        <div class="col-lg-6 mb-4">
                            <div class="card contract-card">
                                <div class="contract-card-header">
                                    <h5 class="card-title mb-0">
                                        Quotation Information
                                    </h5>
                                </div>
                                <div class="contract-card-body">
                                    <div class="contract-field">
                                        <div class="contract-field-label">Quotation Number</div>
                                        <div class="contract-field-value">
                                            @forelse($contract->display_quotations as $quotation)
                                                <a href="{{ route('marketing.quotations.show', $quotation->id) }}" class="text-primary fw-bold me-2" target="_blank" rel="noopener noreferrer">
                                                    {{ $quotation->quotation_number }}
                                                    <i class="fas fa-external-link-alt fa-xs ms-1"></i>
                                                </a>{{ !$loop->last ? ',' : '' }}
                                            @empty
                                                -
                                            @endforelse
                                        </div>
                                    </div>
                                    <div class="contract-field">
                                        <div class="contract-field-label">Survey Number(s)</div>
                                        <div class="contract-field-value">
                                            @php
                                                // Get all survey data for clickable links
                                                $surveys = collect();
                                                if ($contract->contractSurveys->isNotEmpty()) {
                                                    foreach ($contract->contractSurveys as $contractSurvey) {
                                                        if ($contractSurvey->survey) {
                                                            $surveys->push($contractSurvey->survey);
                                                        }
                                                    }
                                                } elseif ($contract->quotation && $contract->quotation->quotationSurveys->isNotEmpty()) {
                                                    foreach ($contract->quotation->quotationSurveys as $quotationSurvey) {
                                                        if ($quotationSurvey->survey) {
                                                            $surveys->push($quotationSurvey->survey);
                                                        }
                                                    }
                                                } elseif ($contract->quotation && $contract->quotation->survey) {
                                                    $surveys->push($contract->quotation->survey);
                                                } elseif ($contract->is_merged_contract) {
                                                    foreach ($contract->mergeDisplaySources() as $sourceContract) {
                                                        if ($sourceContract->contractSurveys->isNotEmpty()) {
                                                            foreach ($sourceContract->contractSurveys as $contractSurvey) {
                                                                if ($contractSurvey->survey) {
                                                                    $surveys->push($contractSurvey->survey);
                                                                }
                                                            }
                                                        } elseif ($sourceContract->quotation && $sourceContract->quotation->quotationSurveys->isNotEmpty()) {
                                                            foreach ($sourceContract->quotation->quotationSurveys as $quotationSurvey) {
                                                                if ($quotationSurvey->survey) {
                                                                    $surveys->push($quotationSurvey->survey);
                                                                }
                                                            }
                                                        } elseif ($sourceContract->quotation && $sourceContract->quotation->survey) {
                                                            $surveys->push($sourceContract->quotation->survey);
                                                        }
                                                    }
                                                }
                                                $surveys = $surveys->unique('id');
                                            @endphp
                                            
                                            @forelse($surveys as $survey)
                                                <a href="{{ route('marketing.surveys.show', $survey->id) }}" class="text-primary fw-bold me-2" target="_blank" rel="noopener noreferrer">
                                                    {{ $survey->survey_number }}
                                                    <i class="fas fa-external-link-alt fa-xs ms-1"></i>
                                                </a>{{ !$loop->last ? ',' : '' }}
                                            @empty
                                                -
                                            @endforelse
                                        </div>
                                    </div>
                                    <div class="contract-field">
                                        <div class="contract-field-label">Quotation Date</div>
                                        <div class="contract-field-value">{{ $contract->display_quotation_date }}</div>
                                    </div>
                                    <div class="contract-field">
                                        <div class="contract-field-label">Valid Until</div>
                                        <div class="contract-field-value">{{ $contract->display_valid_until }}</div>
                                    </div>
                                    <div class="contract-field">
                                        <div class="contract-field-label">Total Amount</div>
                                        <div class="contract-field-value">Rp {{ number_format($contract->display_quotation_total_amount, 0, ',', '.') }}</div>
                                    </div>
                                    <div class="contract-field">
                                        <div class="contract-field-label">Grand Total</div>
                                        <div class="contract-field-value">Rp {{ number_format($contract->display_quotation_grand_total, 0, ',', '.') }}</div>
                                    </div>
                                    <div class="contract-field">
                                        <div class="contract-field-label">Status</div>
                                        <div class="contract-field-value">
                                            <span class="badge badge-{{ strtolower($contract->display_quotation_status) == 'approved' || strtolower($contract->display_quotation_status) == 'contract' ? 'success' : 'warning' }}">
                                                {{ $contract->display_quotation_status }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- MOM6: Contract Notes Section - Full Width Row -->
                    <div class="row contract-notes-row">
                        <div class="col-12 mb-4">
                            <div class="card contract-card" style="border: 2px solid #f59e0b; width: 100%; max-width: 100%; margin: 0;">
                                <div class="contract-card-header" style="background-color: #f59e0b !important;">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-sticky-note me-2"></i>Contract Notes
                                        </h5>
                                        <button class="btn btn-light btn-sm" onclick="openNotesModal({{ $contract->id }})">
                                            <i class="fas fa-edit me-1"></i>Edit Notes
                                        </button>
                                    </div>
                                </div>
                                <div class="contract-card-body">
                                    @php
                                        $canViewOperationNotes = auth()->user()->hasRoleStartingWith('Operational')
                                            || auth()->user()->hasRoleStartingWith('Marketing')
                                            || auth()->user()->hasRoleStartingWith('Management')
                                            || auth()->user()->hasRole('Admin');
                                        $canViewFinanceNotes = auth()->user()->hasRoleStartingWith('Finance')
                                            || auth()->user()->hasRoleStartingWith('Marketing')
                                            || auth()->user()->hasRoleStartingWith('Management')
                                            || auth()->user()->hasRole('Admin');
                                        $canViewSalesNotes = auth()->user()->hasRoleStartingWith('Marketing')
                                            || auth()->user()->hasRoleStartingWith('Management')
                                            || auth()->user()->hasRole('Admin');
                                    @endphp
                                    <div class="row g-3">
                                        <!-- Notes Operation -->
                                        @if($canViewOperationNotes)
                                        <div class="col-lg-4 col-md-6 col-sm-12 mb-3">
                                            <div class="p-3 h-100" style="background-color: #eff6ff; border-left: 4px solid #2563eb; border-radius: 4px; min-height: 120px;">
                                                <h6 class="fw-bold mb-2" style="color: #2563eb;">
                                                    <i class="fas fa-tools me-2"></i>Notes Operation
                                                </h6>
                                                <small class="text-muted d-block mb-2">Catatan untuk operation team</small>
                                                <p class="mb-0" style="font-size: 0.9rem; white-space: pre-wrap; text-align: left;">
                                                    {{ $contract->notes_operation ?? '-' }}
                                                </p>
                                            </div>
                                        </div>
                                        @endif

                                        <!-- Notes Finance -->
                                        @if($canViewFinanceNotes)
                                        <div class="col-lg-4 col-md-6 col-sm-12 mb-3">
                                            <div class="p-3 h-100" style="background-color: #f0fdf4; border-left: 4px solid #059669; border-radius: 4px; min-height: 120px;">
                                                <h6 class="fw-bold mb-2" style="color: #059669;">
                                                    <i class="fas fa-dollar-sign me-2"></i>Notes Finance
                                                </h6>
                                                <small class="text-muted d-block mb-2">Catatan untuk finance/invoice</small>
                                                <p class="mb-0" style="font-size: 0.9rem; white-space: pre-wrap; text-align: left;">
                                                    {{ $contract->notes_finance ?? '-' }}
                                                </p>
                                            </div>
                                        </div>
                                        @endif

                                        <!-- Notes Sales -->
                                        @if($canViewSalesNotes)
                                        <div class="col-lg-4 col-md-12 col-sm-12 mb-3">
                                            <div class="p-3 h-100" style="background-color: #fef2f2; border-left: 4px solid #dc2626; border-radius: 4px; min-height: 120px;">
                                                <h6 class="fw-bold mb-2" style="color: #dc2626;">
                                                    <i class="fas fa-chart-line me-2"></i>Notes Sales
                                                </h6>
                                                <small class="text-muted d-block mb-2">Muncul saat renewal SQ</small>
                                                <p class="mb-0" style="font-size: 0.9rem; white-space: pre-wrap; text-align: left;">
                                                    {{ $contract->notes_sales ?? '-' }}
                                                </p>
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Additional Info Tab -->
                <div class="tab-pane fade" id="additional-info" role="tabpanel" aria-labelledby="additional-info-tab">
                    <div class="card mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Additional Information</h5>
                            @if($contract->contract_status !== 'active')
                                <button type="button" class="btn btn-primary btn-sm no-double-click-prevention" id="editAdditionalInfoBtn" onclick="editAdditionalInfo()">
                                    <i class="fas fa-edit me-1"></i>Edit
                                </button>
                            @endif
                        </div>
                        <div class="card-body">
                            <div id="additionalInfoView">
                                <div class="row">
                                     <div class="col-md-6 mb-3">
                                         <div class="contract-field">
                                             <div class="contract-field-label">Kode PPN</div>
                                             <div class="contract-field-value">{{ $contract->ppn_code ?? '-' }}</div>
                                         </div>
                                     </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="contract-field">
                                            <div class="contract-field-label">TTD Customer 1</div>
                                            <div class="contract-field-value">{{ $contract->customerSigning1->name ?? '-' }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="contract-field">
                                            <div class="contract-field-label">TTD Customer 2</div>
                                            <div class="contract-field-value">{{ $contract->customerSigning2->name ?? '-' }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="contract-field">
                                            <div class="contract-field-label">TTD Customer 3</div>
                                            <div class="contract-field-value">{{ $contract->customerSigning3->name ?? '-' }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="contract-field">
                                            <div class="contract-field-label">TTD Customer 4</div>
                                            <div class="contract-field-value">{{ $contract->customerSigning4->name ?? '-' }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="contract-field">
                                            <div class="contract-field-label">TTD Internal Staff</div>
                                            <div class="contract-field-value">{{ $contract->internalSigning->name ?? '-' }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="contract-field">
                                            <div class="contract-field-label">Tanggal Install</div>
                                            <div class="contract-field-value">{{ $contract->install_date ? $contract->install_date->format('d/M/Y') : '-' }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="contract-field">
                                            <div class="contract-field-label">Tanggal Service Pertama</div>
                                            <div class="contract-field-value">{{ $contract->first_service_date ? $contract->first_service_date->format('d/M/Y') : '-' }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="contract-field">
                                            <div class="contract-field-label">PIC Service (Email)</div>
                                            <div class="contract-field-value">{{ $contract->pic_service_email ?? '-' }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <div class="contract-field">
                                            <div class="contract-field-label">Catatan Tambahan</div>
                                            <div class="contract-field-value">{{ $contract->external_remark ?? '-' }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <div class="contract-field">
                                            <div class="contract-field-label">Catatan Internal</div>
                                            <div class="contract-field-value">{{ $contract->internal_remark ?? '-' }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="additionalInfoEdit" style="display: none;">
                                <form id="additionalInfoForm">
                                    @csrf
                                    <div class="row">
                                         <div class="col-md-6 mb-3">
                                             <label class="form-label">Kode PPN <span class="text-danger">*</span></label>
                                             <select name="ppn_code" id="editPpnCode" class="form-control" required>
                                                 <option value="">Pilih Kode Transaksi PPN...</option>
                                                 <option value="01" {{ $contract->ppn_code == '01' ? 'selected' : '' }}>01 - Penyerahan BKP/JKP yang PPN dipungut oleh PKP penyerah</option>
                                                 <option value="02" {{ $contract->ppn_code == '02' ? 'selected' : '' }}>02 - Penyerahan kepada pemungut PPN instansi pemerintah</option>
                                                 <option value="03" {{ $contract->ppn_code == '03' ? 'selected' : '' }}>03 - Penyerahan kepada pemungut PPN lainnya</option>
                                                 <option value="04" {{ $contract->ppn_code == '04' ? 'selected' : '' }}>04 - Penyerahan dengan dasar pengenaan nilai lain</option>
                                                 <option value="05" {{ $contract->ppn_code == '05' ? 'selected' : '' }}>05 - Penyerahan dengan PPN dipungut besaran tertentu</option>
                                                 <option value="06" {{ $contract->ppn_code == '06' ? 'selected' : '' }}>06 - Penyerahan lainnya yang PPN dipungut PKP penyerah</option>
                                                 <option value="07" {{ $contract->ppn_code == '07' ? 'selected' : '' }}>07 - Penyerahan yang mendapat fasilitas tidak dipungut</option>
                                                 <option value="08" {{ $contract->ppn_code == '08' ? 'selected' : '' }}>08 - Penyerahan yang mendapat fasilitas dibebaskan</option>
                                                 <option value="09" {{ $contract->ppn_code == '09' ? 'selected' : '' }}>09 - Penyerahan aktiva yang tidak untuk diperjualbelikan</option>
                                             </select>
                                         </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">TTD Customer 1 <span class="text-danger">*</span></label>
                                            <select name="customer_signing_1_id" id="editCustomerSigning1" class="form-control" required>
                                                <option value="">Pilih Customer Contact...</option>
                                                @foreach($contract->customer->customerContacts->where('is_active', true) as $contact)
                                                    <option value="{{ $contact->id }}" {{ $contract->customer_signing_1_id == $contact->id ? 'selected' : '' }}>{{ $contact->name }} {{ $contact->position ? ' - ' . $contact->position : '' }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">TTD Customer 2</label>
                                            <select name="customer_signing_2_id" id="editCustomerSigning2" class="form-control">
                                                <option value="">Pilih Customer Contact...</option>
                                                @foreach($contract->customer->customerContacts->where('is_active', true) as $contact)
                                                    <option value="{{ $contact->id }}" {{ $contract->customer_signing_2_id == $contact->id ? 'selected' : '' }}>{{ $contact->name }} {{ $contact->position ? ' - ' . $contact->position : '' }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">TTD Customer 3</label>
                                            <select name="customer_signing_3_id" id="editCustomerSigning3" class="form-control">
                                                <option value="">Pilih Customer Contact...</option>
                                                @foreach($contract->customer->customerContacts->where('is_active', true) as $contact)
                                                    <option value="{{ $contact->id }}" {{ $contract->customer_signing_3_id == $contact->id ? 'selected' : '' }}>{{ $contact->name }} {{ $contact->position ? ' - ' . $contact->position : '' }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">TTD Customer 4</label>
                                            <select name="customer_signing_4_id" id="editCustomerSigning4" class="form-control">
                                                <option value="">Pilih Customer Contact...</option>
                                                @foreach($contract->customer->customerContacts->where('is_active', true) as $contact)
                                                    <option value="{{ $contact->id }}" {{ $contract->customer_signing_4_id == $contact->id ? 'selected' : '' }}>{{ $contact->name }} {{ $contact->position ? ' - ' . $contact->position : '' }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">TTD Internal Staff <span class="text-danger">*</span></label>
                                            <select name="internal_signing_id" id="editInternalSigning" class="form-control" required>
                                                <option value="">Pilih Internal Staff...</option>
                                                @foreach(\App\Models\User::where('is_active', true)->orderBy('name')->get() as $user)
                                                    <option value="{{ $user->id }}" {{ $contract->internal_signing_id == $user->id ? 'selected' : '' }}>{{ $user->name }} {{ $user->position_name ? ' - ' . $user->position_name : '' }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Tanggal Install <span class="text-danger">*</span></label>
                                            <input type="date" name="install_date" id="editInstallDate" class="form-control" value="{{ $contract->install_date ? $contract->install_date->format('Y-m-d') : '' }}" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Tanggal Service Pertama <span class="text-danger">*</span></label>
                                            <input type="date" name="first_service_date" id="editFirstServiceDate" class="form-control" value="{{ $contract->first_service_date ? $contract->first_service_date->format('Y-m-d') : '' }}" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">PIC Service (Email)</label>
                                            <input type="email" name="pic_service_email" id="editPicServiceEmail" class="form-control" value="{{ $contract->pic_service_email ?? '' }}">
                                        </div>
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">Catatan Tambahan</label>
                                            <textarea name="external_remark" id="editExternalRemark" class="form-control" rows="3">{{ $contract->external_remark ?? '' }}</textarea>
                                        </div>
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">Catatan Internal</label>
                                            <textarea name="internal_remark" id="editInternalRemark" class="form-control" rows="3">{{ $contract->internal_remark ?? '' }}</textarea>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-end gap-2">
                                        <button type="button" class="btn btn-secondary" onclick="cancelEditAdditionalInfo()">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Save</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rooms Tab -->
                <div class="tab-pane fade" id="rooms" role="tabpanel" aria-labelledby="rooms-tab">
                    <div class="card" style="width: 100%; min-height: 500px;">
                        <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #1e3a8a;">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0" style="color: #1e3a8a;">
                                    <i class="fas fa-door-open me-2"></i>
                                    Contract Rooms
                                </h5>
                                <!-- Add Button hidden for read-only -->
                                <!--
                                <button class="btn btn-primary btn-sm" onclick="openAddRoomModal({{ $contract->id }})" {{ $contract->contract_status !== 'draft' ? 'disabled' : '' }}>
                                    <i class="fas fa-plus me-1"></i> ADD ROOM
                                </button>
                                -->
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="roomsTable">
                                    <thead>
                                        <tr>
                                            <th data-no-filter>No</th>
                                            <th data-column="building.nama_gedung|building.name">Building</th>
                                            <th data-column="room.room_name">Room</th>
                                            <th data-column="room.room_type">Room Type</th>
                                            <th data-column="room.floor">Floor</th>
                                            <!-- <th data-no-filter>Actions</th> -->
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($contract->contractRooms ?? [] as $index => $contractRoom)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                @if($contractRoom->building)
                                                    <a href="{{ route('operational.buildings.show', $contractRoom->building) }}" target="_blank" rel="noopener noreferrer">{{ $contractRoom->building->building_name ?? '-' }}</a>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>{{ $contractRoom->room->room_name ?? '-' }}</td>
                                            <td>{{ $contractRoom->room->room_type ?? '-' }}</td>
                                            <td>{{ $contractRoom->room->room_floor ?? '-' }}</td>
                                            <!-- Actions column hidden for read-only -->
                                            <!--
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button type="button" class="btn btn-primary btn-sm" onclick="openEditRoomModal({{ $contractRoom->id }})" {{ $contract->contract_status !== 'draft' ? 'disabled' : '' }}>
                                                        <i class="fas fa-edit"></i> EDIT
                                                    </button>
                                                    <button type="button" class="btn btn-danger btn-sm" onclick="confirmDeleteRoom({{ $contractRoom->id }})" {{ $contract->contract_status !== 'draft' ? 'disabled' : '' }}>
                                                        <i class="fas fa-trash"></i> DELETE
                                                    </button>
                                                </div>
                                            </td>
                                            -->
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">
                                                <i class="fas fa-info-circle me-2"></i>
                                                No rooms found.
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rentals Tab -->
                <div class="tab-pane fade" id="rentals" role="tabpanel" aria-labelledby="rentals-tab">
                    <div class="card" style="width: 100%; min-height: 500px;">
                        <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #1e3a8a;">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0" style="color: #1e3a8a;">
                                    <i class="fas fa-boxes me-2"></i>
                                    Contract Rentals
                                </h5>
                                <!-- Add Button hidden for read-only -->
                                <!--
                                <button class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus me-1"></i> ADD RENTAL
                                </button>
                                -->
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="rentalsTable">
                                    <thead>
                                        <tr>
                                            <th data-no-filter>No</th>
                                            <th data-column="masterRental.rental_name">Rental Name</th>
                                            <th data-column="quantity">Qty</th>
                                            <th data-column="qty_free">Qty Free</th>
                                            <th data-column="unit_price">Price</th>
                                            <th data-column="total_price">Total</th>
                                            <th data-column="masterRental.description">Description</th>
                                            <!-- <th data-no-filter>Actions</th> -->
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($contract->contractRentals ?? [] as $index => $contractRental)
                                        <tr id="rental-row-{{ $contractRental->id }}">
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $contractRental->masterRental->rental_name ?? ($contractRental->rental_alias ?: '-') }}</td>
                                            <td>{{ $contractRental->quantity}}</td>
                                            <td>{{ $contractRental->qty_free ?? 0 }}</td>
                                            <td>Rp {{ number_format($contractRental->unit_price, 0, ',', '.') }}</td>
                                            <td>Rp {{ number_format($contractRental->total_price, 0, ',', '.') }}</td>
                                            <td>{{ $contractRental->masterRental->description ?? '-' }}</td>
                                            <!-- Actions column hidden for read-only -->
                                            <!--
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button type="button" class="btn btn-primary btn-sm" onclick="openEditRentalModal({{ $contractRental->id }}, '{{ $contractRental->rental_alias }}', {{ $contractRental->quantity }}, {{ $contractRental->unit_price }})">
                                                        <i class="fas fa-edit"></i> EDIT
                                                    </button>
                                                    <button type="button" class="btn btn-danger btn-sm" onclick="deleteRental({{ $contractRental->id }})">
                                                        <i class="fas fa-trash"></i> DELETE
                                                    </button>
                                                </div>
                                            </td>
                                            -->
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">
                                                <i class="fas fa-info-circle me-2"></i>
                                                No rentals found.
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Billing Group Tab -->
                <div class="tab-pane fade" id="billing-group" role="tabpanel" aria-labelledby="billing-group-tab">
                    <div class="card" style="width: 100%; min-height: 500px;">
                        <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #1e3a8a;">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0" style="color: #1e3a8a;">
                                    <i class="fas fa-credit-card me-2"></i>
                                    Contract Billing Groups
                                </h5>
                                <a href="{{ route('finance.billing-groups.add', $contract->id) }}" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus me-1"></i> ADD NEW
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive" style="overflow-x: auto; max-width: 100%;">
                                <table id="billingGroupsTable" class="table table-bordered table-striped table-hover" style="width: 100%; min-width: 2000px;">
                                    <thead>
                                        <tr>
                                            <th><input type="checkbox" id="selectAllBillingGroups"></th>
                                            <th>Billing Id</th>
                                            <th>Nomor Kontrak</th>
                                            <th>NPWP</th>
                                            <th>NITKU</th>
                                             <th>NIK</th>
                                             <th>Alamat Penagihan</th>
                                            <th>PIC Finance</th>
                                            <th>E-Mail</th>
                                            <th>Phone 1</th>
                                            <th>Bank Name</th>
                                            <th>Account Name</th>
                                            <th>Account No</th>
                                            <th>Wajib Pungut?</th>
                                            <th>Name</th>
                                            <th>Alamat 1</th>
                                            <th>Alamat 2</th>
                                            <th>Provinsi</th>
                                            <th>Kota / Kabupaten</th>
                                            <th>Kecamatan</th>
                                            <th>Kelurahan</th>
                                            <th>Kode Pos</th>
                                            <th>Terakhir Update</th>
                                            <th>Oleh</th>
                                            <th>Action</th>
                                        </tr>
                                        <tr class="filter-row">
                                            <th></th>
                                            <th><input type="text" class="form-control form-control-sm" placeholder="Filter"></th>
                                            <th><input type="text" class="form-control form-control-sm" placeholder="Filter"></th>
                                            <th><input type="text" class="form-control form-control-sm" placeholder="Filter"></th>
                                            <th><input type="text" class="form-control form-control-sm" placeholder="Filter"></th>
                                            <th><input type="text" class="form-control form-control-sm" placeholder="Filter"></th>
                                            <th><input type="text" class="form-control form-control-sm" placeholder="Filter"></th>
                                            <th><input type="text" class="form-control form-control-sm" placeholder="Filter"></th>
                                            <th><input type="text" class="form-control form-control-sm" placeholder="Filter"></th>
                                            <th><input type="text" class="form-control form-control-sm" placeholder="Filter"></th>
                                            <th><input type="text" class="form-control form-control-sm" placeholder="Filter"></th>
                                            <th><input type="text" class="form-control form-control-sm" placeholder="Filter"></th>
                                            <th><input type="text" class="form-control form-control-sm" placeholder="Filter"></th>
                                            <th><input type="text" class="form-control form-control-sm" placeholder="Filter"></th>
                                            <th><input type="text" class="form-control form-control-sm" placeholder="Filter"></th>
                                            <th><input type="text" class="form-control form-control-sm" placeholder="Filter"></th>
                                            <th><input type="text" class="form-control form-control-sm" placeholder="Filter"></th>
                                            <th><input type="text" class="form-control form-control-sm" placeholder="Filter"></th>
                                            <th><input type="text" class="form-control form-control-sm" placeholder="Filter"></th>
                                            <th><input type="text" class="form-control form-control-sm" placeholder="Filter"></th>
                                            <th><input type="text" class="form-control form-control-sm" placeholder="Filter"></th>
                                            <th><input type="text" class="form-control form-control-sm" placeholder="Filter"></th>
                                            <th><input type="text" class="form-control form-control-sm" placeholder="Filter"></th>
                                            <th><input type="text" class="form-control form-control-sm" placeholder="Filter"></th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($contract->billingGroups ?? [] as $billingGroup)
                                            @php
                                                // Get first building from billing group
                                                // Try to get from billing_group_buildings pivot first
                                                $firstBuilding = null;
                                                
                                                // First, try to get from buildings relationship (billing_group_buildings)
                                                if ($billingGroup->relationLoaded('buildings')) {
                                                    $firstBuilding = $billingGroup->buildings->first();
                                                } else {
                                                    // If not loaded, load it manually
                                                    $billingGroup->load('buildings.province', 'buildings.city', 'buildings.district', 'buildings.subdistrict');
                                                    $firstBuilding = $billingGroup->buildings->first();
                                                }
                                                
                                                // If still no building, try to get from contract_buildings (backward compatibility)
                                                if (!$firstBuilding) {
                                                    $contractBuilding = \App\Models\ContractBuilding::where('billing_id', $billingGroup->id)
                                                        ->with('building.province', 'building.city', 'building.district', 'building.subdistrict')
                                                        ->first();
                                                    if ($contractBuilding && $contractBuilding->building) {
                                                        $firstBuilding = $contractBuilding->building;
                                                    }
                                                }
                                                
                                                $customer = $billingGroup->customer;
                                                
                                                // Get NIK, NPWP, NITKU based on tax_type (supports comma-separated values)
                                                $taxTypes = array_map('trim', explode(',', strtoupper($billingGroup->tax_type ?? '')));
                                                $taxNumbers = array_map('trim', explode(',', $billingGroup->tax_number ?? ''));
                                                
                                                // Create a map for easy lookup
                                                $taxMap = [];
                                                foreach ($taxTypes as $idx => $type) {
                                                    if ($type && isset($taxNumbers[$idx])) {
                                                        $taxMap[$type] = $taxNumbers[$idx];
                                                    }
                                                }
                                                
                                                $nik = $billingGroup->nik ?? $taxMap['NIK'] ?? '-';
                                                $npwp = $billingGroup->npwp ?? $taxMap['NPWP'] ?? ($customer->npwp ?? '-');
                                                $nitku = $billingGroup->nitku ?? $taxMap['NITKU'] ?? ($customer->nitku ?? '-');
                                                
                                                // Jika masih '-', coba fallback ke legacy npwp_number jika berisi 22 digit (NITKU)
                                                if ($nitku === '-' && $billingGroup->npwp_number && strlen(preg_replace('/[^0-9]/', '', $billingGroup->npwp_number)) == 22) {
                                                    $nitku = $billingGroup->npwp_number;
                                                }
                                                
                                                // Alamat Penagihan
                                                $alamatPenagihan = '-';
                                                
                                                if ($billingGroup->npwp_address) {
                                                    $alamatPenagihan = $billingGroup->npwp_address;
                                                } elseif ($firstBuilding) {
                                                    // Fallback to Building Name if no tax info
                                                     $alamatPenagihan = $firstBuilding->building_name ?? '-';
                                                }
                                                
                                                // PIC Finance (customer contact)
                                                $picFinance = $billingGroup->pic_name ?? '-';
                                                
                                                // E-Mail - dari pic_email (customer contact email)
                                                $email = $billingGroup->pic_email ?? '-';
                                                
                                                // Phone 1 & 2 dari PIC Finance (customer contact)
                                                // Note: CustomerContact hanya punya 1 field phone, tidak ada phone_1 dan phone_2
                                                // Kita perlu cari customer contact berdasarkan pic_name atau simpan contact_id
                                                $phone1 = '-';
                                                $phone2 = '-';
                                                
                                                // Try to find customer contact by name or email
                                                if ($billingGroup->pic_name || $billingGroup->pic_email) {
                                                    $picContact = \App\Models\CustomerContact::where('customer_id', $customer->id)
                                                        ->where(function($query) use ($billingGroup) {
                                                            if ($billingGroup->pic_name) {
                                                                $query->where('name', $billingGroup->pic_name);
                                                            }
                                                            if ($billingGroup->pic_email) {
                                                                $query->orWhere('email', $billingGroup->pic_email);
                                                            }
                                                        })
                                                        ->where('is_active', true)
                                                        ->first();
                                                    
                                                    if ($picContact) {
                                                        $phone1 = $picContact->phone ?? '-';
                                                        // CustomerContact hanya punya 1 phone, jadi phone2 tetap '-'
                                                    }
                                                }
                                                
                                                // Bank Name
                                                $bankName = $billingGroup->bank_name ?? '-';
                                                
                                                // Get account name and number from virtual_account_number
                                                // Format: "Bank Name - Account Name (Account Number)"
                                                $accountName = '-';
                                                $accountNo = '-';
                                                if ($billingGroup->virtual_account_number) {
                                                    // Try to parse format: "Bank Name - Account Name (Account Number)"
                                                    if (preg_match('/^(.+?)\s*-\s*(.+?)\s*\((.+?)\)$/', $billingGroup->virtual_account_number, $matches)) {
                                                        $accountName = $matches[2] ?? '-';
                                                        $accountNo = $matches[3] ?? '-';
                                                    } else {
                                                        // If format doesn't match, use as account number
                                                        $accountNo = $billingGroup->virtual_account_number;
                                                    }
                                                }
                                                
                                                // Wajib Pungut? - check if there's a field for this, or use customer is_pkp
                                                $wajibPungut = ($customer->is_pkp ?? false) ? 'Yes' : 'No';
                                                
                                                // Invoice type label
                                                $invoiceTypeLabel = $billingGroup->invoice_type_label ?? ucfirst($billingGroup->invoice_type ?? '-');
                                                
                                                // Building address fields
                                                // Check if firstBuilding exists and has relationships loaded
                                                $alamat1 = '-';
                                                $alamat2 = '-';
                                                $provinsi = '-';
                                                $kota = '-';
                                                $kecamatan = '-';
                                                $kelurahan = '-';
                                                $kodePos = '-';
                                                
                                                if ($firstBuilding) {
                                                    $alamat1 = $firstBuilding->alamat_1 ?? $firstBuilding->address ?? '-';
                                                    $alamat2 = $firstBuilding->alamat_2 ?? '-';
                                                    
                                                    // Check if relationships are loaded
                                                    if ($firstBuilding->relationLoaded('province') && $firstBuilding->province) {
                                                        $provinsi = $firstBuilding->province->name ?? '-';
                                                    } elseif (isset($firstBuilding->province_id)) {
                                                        // If relationship not loaded, try to load it
                                                        $province = \App\Models\Province::find($firstBuilding->province_id);
                                                        $provinsi = $province ? $province->name : '-';
                                                    }
                                                    
                                                    if ($firstBuilding->relationLoaded('city') && $firstBuilding->city) {
                                                        $kota = $firstBuilding->city->name ?? '-';
                                                    } elseif (isset($firstBuilding->city_id)) {
                                                        $city = \App\Models\City::find($firstBuilding->city_id);
                                                        $kota = $city ? $city->name : '-';
                                                    }
                                                    
                                                    if ($firstBuilding->relationLoaded('district') && $firstBuilding->district) {
                                                        $kecamatan = $firstBuilding->district->name ?? '-';
                                                    } elseif (isset($firstBuilding->district_id)) {
                                                        $district = \App\Models\District::find($firstBuilding->district_id);
                                                        $kecamatan = $district ? $district->name : '-';
                                                    }
                                                    
                                                    if ($firstBuilding->relationLoaded('subdistrict') && $firstBuilding->subdistrict) {
                                                        $kelurahan = $firstBuilding->subdistrict->name ?? '-';
                                                    } elseif (isset($firstBuilding->subdistrict_id)) {
                                                        $subdistrict = \App\Models\Subdistrict::find($firstBuilding->subdistrict_id);
                                                        $kelurahan = $subdistrict ? $subdistrict->name : '-';
                                                    }
                                                    
                                                    $kodePos = $firstBuilding->kode_pos ?? $firstBuilding->postal_code ?? '-';
                                                }
                                            @endphp
                                            <tr>
                                                <td><input type="checkbox" class="billing-group-checkbox" value="{{ $billingGroup->id }}"></td>
                                                <td>{{ $billingGroup->id }}</td>
                                                <td>{{ $contract->contract_number ?? '-' }}</td>
                                                <td>{{ $npwp }}</td>
                                                <td>{{ $nitku }}</td>
                                                 <td>{{ $nik }}</td>
                                                 <td>{{ $alamatPenagihan }}</td>
                                                <td>{{ $picFinance }}</td>
                                                <td>{{ $email }}</td>
                                                <td>{{ $phone1 }}</td>
                                                <td>{{ $bankName }}</td>
                                                <td>{{ $accountName }}</td>
                                                <td>{{ $accountNo }}</td>
                                                <td>{{ $wajibPungut }}</td>
                                                <td>{{ $invoiceTypeLabel }}</td>
                                                <td>{{ $alamat1 }}</td>
                                                <td>{{ $alamat2 }}</td>
                                                <td>{{ $provinsi }}</td>
                                                <td>{{ $kota }}</td>
                                                <td>{{ $kecamatan }}</td>
                                                <td>{{ $kelurahan }}</td>
                                                <td>{{ $kodePos }}</td>
                                                <td>{{ $billingGroup->updated_at ? $billingGroup->updated_at->format('d/M/Y H:i') : '-' }}</td>
                                                <td>{{ $billingGroup->updater->name ?? ($billingGroup->creator->name ?? '-') }}</td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="{{ route('finance.billing-groups.edit-for-contract', [$contract->id, $billingGroup->id]) }}" class="btn btn-primary btn-sm" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-danger btn-sm" onclick="deleteBillingGroup({{ $billingGroup->id }})" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                 <td colspan="25" class="text-center text-muted">
                                                    <i class="fas fa-info-circle fa-2x mb-2"></i>
                                                    <p>No billing groups found. <a href="{{ route('finance.billing-groups.add', $contract->id) }}">Add new billing group</a></p>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Buildings Tab -->
                <div class="tab-pane fade" id="buildings" role="tabpanel" aria-labelledby="buildings-tab">
                    <div class="card" style="width: 100%; min-height: 500px;">
                        <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #1e3a8a;">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0" style="color: #1e3a8a;">
                                    <i class="fas fa-building me-2"></i>
                                    Contract Buildings
                                </h5>
                                <!-- Add Button hidden for read-only -->
                                <!--
                                <button class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus me-1"></i> ADD BUILDING
                                </button>
                                -->
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="buildingsTable">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Building Name</th>
                                            <th>Postal Code</th>
                                            <th>Address</th>
                                            <th>City</th>
                                            <th>Status</th>
                                            <!-- <th>Actions</th> -->
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            // Aggregate buildings from multiple sources
                                            $uniqueBuildings = collect();
                                            
                                            // 1. From Contract Rooms
                                            foreach($contract->contractRooms as $room) {
                                                if ($room->building) {
                                                    $uniqueBuildings->push($room->building);
                                                }
                                            }
                                            
                                            // 2. From Contract Surveys
                                            foreach($contract->contractSurveys as $survey) {
                                                if ($survey->survey && $survey->survey->building) {
                                                    $uniqueBuildings->push($survey->survey->building);
                                                }
                                            }
                                            
                                            // 3. From Billing Groups
                                            foreach($contract->billingGroups as $bg) {
                                                foreach($bg->buildings as $bgBuilding) {
                                                    $uniqueBuildings->push($bgBuilding);
                                                }
                                            }
                                            
                                            // Unique by ID
                                            $uniqueBuildings = $uniqueBuildings->unique('id');
                                        @endphp

                                        @forelse($uniqueBuildings as $index => $building)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $building->building_name ?? '-' }}</td>
                                            <td>{{ $building->kode_pos ?? $building->postal_code ?? '-' }}</td>
                                            <td>{{ $building->alamat_1 ?? $building->address ?? '-' }}</td>
                                            <td>{{ $building->city->name ?? '-' }}</td>
                                            <td>
                                                <span class="badge badge-{{ $building->is_active ? 'success' : 'secondary' }}">
                                                    {{ $building->is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </td>
                                            <!-- Actions column hidden for read-only -->
                                            <!--
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button type="button" class="btn btn-primary btn-sm">
                                                        <i class="fas fa-edit"></i> EDIT
                                                    </button>
                                                    <button type="button" class="btn btn-danger btn-sm">
                                                        <i class="fas fa-trash"></i> DELETE
                                                    </button>
                                                </div>
                                            </td>
                                            -->
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">
                                                <i class="fas fa-info-circle me-2"></i>
                                                No buildings found.
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Files Tab -->
                <div class="tab-pane fade" id="files" role="tabpanel" aria-labelledby="files-tab">
                    <div class="card" style="width: 100%; min-height: 500px;">
                        <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #1e3a8a;">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <h5 class="card-title mb-0" style="color: #1e3a8a;">
                                    <i class="fas fa-file me-2"></i>
                                    Contract Files
                                </h5>
                                <div class="d-flex gap-2">
                                    @if(auth()->user()->hasPermission('marketing.contract_files.approve') || auth()->user()->canApprove('contract_files'))
                                    <button class="btn btn-success btn-sm" id="btnBulkApprove" onclick="bulkApproveFiles()" disabled>
                                        <i class="fas fa-check me-1"></i> Approve Selected
                                    </button>
                                    @endif
                                    @php
                                        $user = auth()->user();
                                        $canUpload = $user->hasPermission('marketing.contract_files.create') 
                                                   || $user->hasPermission('marketing.contracts.view')
                                                   || $user->hasRole('Marketing');
                                    @endphp
                                    @if($canUpload)
                                    <button class="btn btn-primary btn-sm" onclick="openUploadFileModal()">
                                        <i class="fas fa-plus me-1"></i> ADD FILE
                                    </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover" id="filesTable">
                                    <thead style="background-color: #e3f2fd;">
                                        <tr>
                                            <th style="width: 40px;">
                                                <input type="checkbox" id="selectAllFiles" onchange="toggleAllFileCheckboxes(this)">
                                            </th>
                                            <th style="width: 50px;">No</th>
                                            <th>File Name</th>
                                            <th style="width: 110px;">File Type</th>
                                            <th style="width: 90px;">Size</th>
                                            <th style="width: 110px;">Upload Date</th>
                                            <th style="width: 100px;">Uploaded By</th>
                                            <th style="width: 80px;">Status</th>
                                            <th style="width: 100px;">Approved By</th>
                                            <th style="width: 110px;">Approved At</th>
                                            <th style="width: 120px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($contract->contractFiles ?? [] as $index => $file)
                                        <tr id="file-row-{{ $file->id }}">
                                            <td class="text-center">
                                                @if($file->verification_status === 'pending')
                                                <input type="checkbox" class="file-checkbox" value="{{ $file->id }}" onchange="updateBulkApproveButton()">
                                                @endif
                                            </td>
                                            <td class="text-center">{{ $index + 1 }}</td>
                                            <td>
                                                <i class="fas fa-file-pdf me-2 text-danger"></i>
                                                <strong>{{ $file->file_name ?? '-' }}</strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">{{ ucfirst($file->file_type ?? 'other') }}</span>
                                            </td>
                                            <td class="text-end">
                                                {{ $file->file_size ? number_format($file->file_size / 1024, 2) . ' KB' : '-' }}
                                            </td>
                                            <td class="text-center">
                                                {{ $file->uploaded_at ? $file->uploaded_at->format('d/M/Y H:i') : '-' }}
                                            </td>
                                            <td>{{ $file->uploader->name ?? '-' }}</td>
                                            <td class="text-center">
                                                @if($file->verification_status === 'pending')
                                                    <span class="badge bg-warning text-dark">
                                                        <i class="fas fa-clock me-1"></i>Pending
                                                    </span>
                                                @elseif($file->verification_status === 'verified')
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check me-1"></i>Verified
                                                    </span>
                                                @elseif($file->verification_status === 'rejected')
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-times me-1"></i>Rejected
                                                    </span>
                                                @endif
                                            </td>
                                            <td>{{ $file->verifier->name ?? '-' }}</td>
                                            <td class="text-center">
                                                {{ $file->verified_at ? $file->verified_at->format('d/M/Y H:i') : '-' }}
                                            </td>
                                            <td>
                                                <div class="d-flex gap-1 flex-wrap">
                                                    <!-- Download Button -->
                                                    <a href="{{ asset($file->file_path) }}" class="btn btn-sm btn-info" target="_blank" rel="noopener noreferrer" title="Download">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                    
                                                    <!-- Verify Button (Admin/Manager only, for pending files) -->
                                                    @if($file->verification_status === 'pending' && (auth()->user()->hasPermission('marketing.contract_files.approve') || auth()->user()->canApprove('contract_files')))
                                                        <button type="button" class="btn btn-sm btn-success" onclick="verifyFile({{ $contract->id }}, {{ $file->id }})" title="Verify">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-warning" onclick="rejectFile({{ $contract->id }}, {{ $file->id }})" title="Reject">
                                                            <i class="fas fa-ban"></i>
                                                        </button>
                                                    @endif
                                                    
                                                    <!-- Delete Button -->
                                                    @if(auth()->user()->hasPermission('marketing.contract_files.delete') || auth()->user()->hasPermission('admin.delete'))
                                                        <button type="button" class="btn btn-sm btn-danger" onclick="deleteFile({{ $contract->id }}, {{ $file->id }})" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="11" class="text-center text-muted py-5">
                                                <i class="fas fa-folder-open fa-3x mb-3 d-block" style="color: #ccc;"></i>
                                                <p class="mb-0">No files uploaded yet.</p>
                                                <small>Click "ADD FILE" button above to upload files</small>
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                
                <!-- Remarks Tab -->
                <div class="tab-pane fade" id="remarks" role="tabpanel" aria-labelledby="remarks-tab">
                    <div class="card" style="width: 100%; min-height: 500px;">
                        <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #1e3a8a;">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                                <h5 class="card-title mb-0" style="color: #1e3a8a;">
                                    <i class="fas fa-comments me-2"></i>
                                    Contract Remarks
                                </h5>
                                <div class="d-flex gap-2 align-items-center">
                                    <select id="remarkTypeFilter" class="form-select form-select-sm" style="width: auto;">
                                        <option value="">All Types</option>
                                        <option value="contract">Contract</option>
                                        <option value="operation">Operation</option>
                                        <option value="finance">Finance</option>
                                        <option value="marketing">Marketing</option>
                                    </select>
                                    <button type="button" class="btn btn-primary btn-sm" id="btnAddRemark">
                                        <i class="fas fa-plus me-1"></i> ADD REMARK
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-3">
                            <div id="remarksContainer">
                                <div class="text-center py-5">
                                    <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                                    <p class="mt-2 text-muted">Loading remarks...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

<!-- Billing Group modals removed - now using separate pages (addbg.blade.php and editbg.blade.php) -->

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
// Contract and building data
const contractId = {{ $contract->id }};
const customerId = {{ $contract->customer_id ?? 0 }};

function openActiveContractEditModal() {
    const modalOverlay = document.getElementById('activeContractEditModal');
    modalOverlay.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeActiveContractEditModal() {
    const modalOverlay = document.getElementById('activeContractEditModal');
    modalOverlay.classList.remove('show');
    document.body.style.overflow = '';
}

function saveActiveContractEdit() {
    const form = document.getElementById('activeContractEditForm');
    const saveBtn = document.getElementById('saveActiveContractEditBtn');
    const formData = new FormData(form);
    const payload = {};

    formData.forEach((value, key) => {
        payload[key] = value;
    });

    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';

    fetch(`/marketing/contracts/${contractId}/editable-fields`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
    })
    .then(response => response.json().then(data => ({ ok: response.ok, data })))
    .then(({ ok, data }) => {
        if (!ok || data.status !== 'success') {
            throw new Error(data.message || 'Gagal menyimpan perubahan kontrak.');
        }

        closeActiveContractEditModal();

        if (typeof showSuccessDialog === 'function') {
            showSuccessDialog('Berhasil', 'Contract berhasil diperbarui.');
            setTimeout(() => location.reload(), 800);
            return;
        }

        location.reload();
    })
    .catch(error => {
        console.error('Error updating contract:', error);
        if (typeof showErrorDialog === 'function') {
            showErrorDialog('Gagal', error.message || 'Gagal menyimpan perubahan kontrak.');
            return;
        }

        alert(error.message || 'Gagal menyimpan perubahan kontrak.');
    })
    .finally(() => {
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="fas fa-save me-1"></i>Save';
    });
}

@php
    try {
        $buildings = ($contract->contractRooms ?? collect())
            ->load('room.building')
            ->pluck('room.building')
            ->filter(function($building) {
                return $building !== null && isset($building->id);
            })
            ->unique('id')
            ->map(function($building) {
                $name = $building->building_name ?? 'Building #' . ($building->id ?? 0);
                // Ensure name is safe for JSON
                $name = str_replace(["\n", "\r", "\t"], ' ', $name);
                return [
                    'id' => (int)($building->id ?? 0),
                    'name' => (string)$name
                ];
            })
            ->values()
            ->toArray();
    } catch (\Exception $e) {
        $buildings = [];
    }
    // Ensure it's always an array
    if (!is_array($buildings)) {
        $buildings = [];
    }
    // Encode to ensure it's safe for JavaScript
    $buildingsJson = json_encode($buildings, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    // Fallback to empty array if encoding fails
    if ($buildingsJson === false) {
        $buildingsJson = '[]';
    }
@endphp
try {
    const contractBuildings = {!! $buildingsJson !!};
} catch (e) {
    console.error('Error parsing contractBuildings:', e);
    const contractBuildings = [];
}

// Define finalizeContract in global scope early to ensure it's available
window.finalizeContract = function(contractId) {
    Swal.fire({
        title: 'Finalize Contract?',
        html: '<p class="mb-0">After finalization, the contract will be submitted for approval and you cannot edit it.</p>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-check me-2"></i>Yes, Finalize',
        cancelButtonText: '<i class="fas fa-times me-2"></i>Cancel',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return fetch(`/marketing/contracts/${contractId}/finalize`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        throw new Error(err.message || 'Failed to finalize contract');
                    });
                }
                return response.json();
            })
            .catch(error => {
                Swal.showValidationMessage(
                    `Request failed: ${error.message}`
                );
                return false; // Prevent confirmation on error
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            const contractNumber = result.value.data?.contract_number || 'Contract';
            const contractStatus = result.value.data?.contract_status || 'Active';
            Swal.fire({
                title: 'Finalized!',
                html: `<p class="mb-2">Contract <strong>${contractNumber}</strong> has been finalized.</p><p class="mb-0">Status: <span class="badge bg-success">${contractStatus}</span></p>`,
                icon: 'success',
                confirmButtonColor: '#10b981',
                confirmButtonText: '<i class="fas fa-check me-2"></i>OK'
            }).then(() => {
                window.location.reload();
            });
        }
    });
};

window.saveDraft = function(contractId) {
    Swal.fire({
        title: 'Save Draft?',
        text: 'Save contract as draft',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3b82f6',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-save me-2"></i>Yes, Save',
        cancelButtonText: '<i class="fas fa-times me-2"></i>Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Saved!',
                text: 'Draft saved successfully',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                window.location.href = `/marketing/contracts/${contractId}`;
            });
        }
    });
};

$(document).ready(function() {
    console.log('Contract show page loaded');
    
    // Note: Modals are now injected via JavaScript template above
    // No need to move them anymore!
    
    // Clean up any lingering modal states
    $('.modal-backdrop').remove();
    $('body').removeClass('modal-open').css('padding-right', '');
    
    // Initialize tab visibility - hide all tabs except the first one
    $('.tab-pane').removeClass('show active').css('display', 'none');
    $('#basic-info').addClass('show active').css('display', 'block');
    
    // Tab switching functionality
    $('#contractTabs button[data-bs-toggle="tab"]').on('click', function(e) {
        e.preventDefault();
        var target = $(this).attr('data-bs-target');
        var tabId = $(this).attr('id');
        
        console.log('Tab clicked:', tabId, 'Target:', target);
        
        // Remove active class from all tabs and content
        $('#contractTabs button').removeClass('active').css({
            'border-bottom': 'none',
            'color': '#6c757d',
            'font-weight': 'normal'
        });
        $('.tab-pane').removeClass('show active').css('display', 'none');
        
        // Add active class to clicked tab
        $(this).addClass('active').css({
            'border-bottom': '3px solid #1e3a8a',
            'color': '#1e3a8a',
            'font-weight': 'bold'
        });
        $(target).addClass('show active').css('display', 'block');
        
        console.log('Tab switched to:', target, 'Active classes applied');
        
        // If billing group tab is activated, update building coverage
        if (target === '#billing-group') {
            updateBuildingCoverage();
        }
    });
    
    // Room actions
    $(document).on('click', '.edit-room', function() {
        const roomId = $(this).data('room-id');
        console.log('Edit room:', roomId);
        // Implement edit room functionality
    });
    
    $(document).on('click', '.delete-room', function() {
        const roomId = $(this).data('room-id');
        console.log('Delete room:', roomId);
        showConfirmDialog({
            title: 'Hapus Ruangan?',
            text: 'Apakah Anda yakin ingin menghapus ruangan ini?',
            icon: 'warning',
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal'
        });
    });
    
    // Rental actions
    $(document).on('click', '.edit-rental', function() {
        const rentalId = $(this).data('rental-id');
        console.log('Edit rental:', rentalId);
        // Implement edit rental functionality
    });
    
    $(document).on('click', '.delete-rental', function() {
        const rentalId = $(this).data('rental-id');
        console.log('Delete rental:', rentalId);
        showConfirmDialog({
            title: 'Hapus Rental?',
            text: 'Apakah Anda yakin ingin menghapus rental ini?',
            icon: 'warning',
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal'
        });
    });
    
    // Billing actions
    $(document).on('click', '.edit-billing', function() {
        const billingId = $(this).data('billing-id');
        console.log('Edit billing:', billingId);
        // Implement edit billing functionality
    });
    
    $(document).on('click', '.delete-billing', function() {
        const billingId = $(this).data('billing-id');
        console.log('Delete billing:', billingId);
        showConfirmDialog({
            title: 'Hapus Billing Group?',
            text: 'Apakah Anda yakin ingin menghapus billing group ini?',
            icon: 'warning',
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal'
        });
    });
    
    // Building actions
    $(document).on('click', '.edit-building', function() {
        const buildingId = $(this).data('building-id');
        console.log('Edit building:', buildingId);
        // Implement edit building functionality
    });
    
    $(document).on('click', '.delete-building', function() {
        const buildingId = $(this).data('building-id');
        console.log('Delete building:', buildingId);
        showConfirmDialog({
            title: 'Hapus Gedung?',
            text: 'Apakah Anda yakin ingin menghapus gedung ini?',
            icon: 'warning',
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal'
        });
    });
    
    // File actions
    $(document).on('click', '.delete-file', function() {
        const fileId = $(this).data('file-id');
        console.log('Delete file:', fileId);
        showConfirmDialog({
            title: 'Hapus File?',
            text: 'Apakah Anda yakin ingin menghapus file ini?',
            icon: 'warning',
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal'
        });
    });
    
    // Existing billing group checkbox toggle
    $('#useExistingBillingGroup').on('change', function() {
        if ($(this).is(':checked')) {
            $('#existingBillingGroupSection').slideDown();
            $('#newBillingGroupSection').slideUp();
            loadExistingBillingGroups();
        } else {
            $('#existingBillingGroupSection').slideUp();
            $('#newBillingGroupSection').slideDown();
        }
    });
    
    // Initialize building coverage on load
    updateBuildingCoverage();
    
    // Billing Groups Table - Select All Checkbox
    $('#selectAllBillingGroups').on('change', function() {
        $('.billing-group-checkbox').prop('checked', $(this).prop('checked'));
    });
    
    // Billing Groups Table - Individual Checkbox
    $(document).on('change', '.billing-group-checkbox', function() {
        var totalCheckboxes = $('.billing-group-checkbox').length;
        var checkedCheckboxes = $('.billing-group-checkbox:checked').length;
        $('#selectAllBillingGroups').prop('checked', totalCheckboxes === checkedCheckboxes);
    });
    
    // Billing Groups Table - Filter functionality
    $('#billingGroupsTable .filter-row input').on('keyup', function() {
        var columnIndex = $(this).parent().index();
        var searchValue = $(this).val().toLowerCase();
        
        $('#billingGroupsTable tbody tr').each(function() {
            var cellValue = $(this).find('td').eq(columnIndex).text().toLowerCase();
            if (cellValue.indexOf(searchValue) === -1) {
                $(this).hide();
            } else {
                $(this).show();
            }
        });
    });
});

// Billing Group Functions
// Modal functions removed - now using separate pages:
// - Add: /contracts/{id}/billing-groups/add (addbg.blade.php)
// - Edit: /contracts/{id}/billing-groups/{bg}/edit (editbg.blade.php)

function deleteBillingGroup(billingGroupId) {
    showConfirmDialog({
        title: 'Hapus Billing Group?',
        text: 'Apakah Anda yakin ingin menghapus billing group ini? Semua assignment gedung akan ikut dilepas.',
        icon: 'warning',
        confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (!result.isConfirmed) {
            return;
        }
        
        $.ajax({
            url: `/api/billing-groups/${billingGroupId}`,
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                showSuccessDialog('Billing group berhasil dihapus.').then(() => location.reload());
            },
            error: function(xhr) {
                showErrorDialog('Gagal menghapus billing group: ' + (xhr.responseJSON?.message || 'Terjadi kesalahan.'));
            }
        });
    });
}

function manageBuildingsInBillingGroup(billingGroupId) {
    $('#current_billing_group_id').val(billingGroupId);
    loadBuildingsForBillingGroup(billingGroupId);
    
    var modal = new bootstrap.Modal(document.getElementById('manageBuildingsModal'));
    modal.show();
}

function loadExistingBillingGroups() {
    $.ajax({
        url: `/api/customers/${customerId}/billing-groups`,
        method: 'GET',
        success: function(response) {
            const select = $('#existing_billing_group_id');
            select.empty().append('<option value="">-- Select Billing Group --</option>');
            
            response.forEach(bg => {
                select.append(`<option value="${bg.id}">${bg.billing_group_name} (${bg.contract_number})</option>`);
            });
        },
        error: function(xhr) {
            console.error('Error loading existing billing groups');
        }
    });
}

function loadBuildingsForBillingGroup(billingGroupId) {
    $.ajax({
        url: `/api/contracts/${contractId}/billing-groups/${billingGroupId}/buildings`,
        method: 'GET',
        success: function(response) {
            const availableList = $('#availableBuildingsList');
            const assignedList = $('#assignedBuildingsList');
            
            availableList.empty();
            assignedList.empty();
            
            // Access data from response.data wrapper
            const data = response.data || response;
            const availableBuildings = data.available || [];
            const assignedBuildings = data.assigned || [];
            
            // Populate available buildings (not assigned to this billing group)
            availableBuildings.forEach(building => {
                const isDisabled = building.assigned_to_other ? 'disabled' : '';
                const disabledClass = building.assigned_to_other ? 'list-group-item-secondary' : '';
                const disabledText = building.assigned_to_other ? `<small class="text-muted">(Assigned to: ${building.assigned_to_billing_group})</small>` : '';
                
                availableList.append(`
                    <div class="list-group-item ${disabledClass}" data-building-id="${building.id}">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-building me-2"></i>
                                <strong>${building.name}</strong>
                                ${disabledText}
                            </div>
                            <button class="btn btn-sm btn-primary" onclick="assignBuilding(${building.id})" ${isDisabled}>
                                <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                `);
            });
            
            // Populate assigned buildings
            assignedBuildings.forEach(building => {
                assignedList.append(`
                    <div class="list-group-item list-group-item-success" data-building-id="${building.id}">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-building me-2"></i>
                                <strong>${building.name}</strong>
                            </div>
                            <button class="btn btn-sm btn-danger" onclick="unassignBuilding(${building.id})">
                                <i class="fas fa-arrow-left"></i>
                            </button>
                        </div>
                    </div>
                `);
            });
            
            if (availableBuildings.length === 0) {
                availableList.append('<div class="text-center text-muted p-3">Tidak ada gedung yang tersedia</div>');
            }
            
            if (assignedBuildings.length === 0) {
                assignedList.append('<div class="text-center text-muted p-3">Belum ada gedung yang di-assign</div>');
            }
        },
        error: function(xhr) {
            console.error('Error loading buildings:', xhr.status, xhr.responseText);
            showErrorDialog('Gagal memuat data gedung.');
        }
    });
}

function assignBuilding(buildingId) {
    const billingGroupId = $('#current_billing_group_id').val();
    
    $.ajax({
        url: `/api/billing-groups/${billingGroupId}/buildings`,
        method: 'POST',
        data: {
            building_id: buildingId
        },
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            loadBuildingsForBillingGroup(billingGroupId);
            updateBuildingCoverage();
        },
        error: function(xhr) {
            showErrorDialog('Gagal assign gedung: ' + (xhr.responseJSON?.message || 'Gedung ini sudah di-assign ke billing group lain.'));
        }
    });
}

function unassignBuilding(buildingId) {
    const billingGroupId = $('#current_billing_group_id').val();
    
    $.ajax({
        url: `/api/billing-groups/${billingGroupId}/buildings/${buildingId}`,
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            loadBuildingsForBillingGroup(billingGroupId);
            updateBuildingCoverage();
        },
        error: function(xhr) {
            showErrorDialog('Gagal melepas assignment gedung.');
        }
    });
}

function saveBuildingsAssignment() {
    $('#manageBuildingsModal').modal('hide');
    updateBuildingCoverage();
    location.reload();
}

function updateBuildingCoverage() {
    $.ajax({
        url: `/api/contracts/${contractId}/billing-groups/coverage`,
        method: 'GET',
        success: function(response) {
            // Access data from response.data wrapper
            const data = response.data || response;
            const totalBuildings = data.total_buildings || 0;
            const assignedBuildings = data.assigned_buildings || 0;
            const unassignedBuildings = data.unassigned_buildings || [];
            const coveragePercent = data.coverage_percentage || 0;
            
            $('#assignedBuildingsCount').text(assignedBuildings);
            $('#totalBuildingsCount').text(totalBuildings);
            $('#buildingCoveragePercent').text(coveragePercent + '%');
            $('#buildingCoverageBar').css('width', coveragePercent + '%');
            
            if (unassignedBuildings.length > 0) {
                $('#unassignedBuildingsAlert').show();
                const list = $('#unassignedBuildingsList');
                list.empty();
                unassignedBuildings.forEach(building => {
                    list.append(`<span class="badge bg-warning me-1 mb-1"><i class="fas fa-building me-1"></i>${building.name}</span>`);
                });
            } else {
                $('#unassignedBuildingsAlert').hide();
            }
        },
        error: function(xhr) {
            console.error('Error loading building coverage:', xhr.status, xhr.responseText);
        }
    });
}

// ============================================
// CONTRACT REMARKS FUNCTIONALITY
// ============================================

let allRemarks = [];
let currentRemarkId = null;
let remarkModalInstance = null;

// Create modal HTML and inject to body
function createRemarkModal() {
    const modalHTML = `
    <div class="modal fade" id="remarkModal" tabindex="-1" role="dialog" style="display: none; z-index: 10000;">
        <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 700px; width: 90%;">
            <div class="modal-content">
                <div class="modal-header" style="background: #0d6efd; color: white;">
                    <h5 class="modal-title" id="remarkModalLabel">
                        <i class="fas fa-comment-alt me-2"></i>Add Remark
                    </h5>
                    <button type="button" class="btn-close btn-close-white" onclick="closeRemarkModal(); return false;" aria-label="Close"></button>
                </div>
                <form id="remarkForm" onsubmit="event.preventDefault(); saveRemark(); return false;">
                    <div class="modal-body">
                        <input type="hidden" id="remarkId" name="remark_id">
                        
                        <div class="mb-3">
                            <label for="remarkType" class="form-label fw-bold">Remark Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="remarkType" name="remark_type" required>
                                <option value="">Select Type</option>
                                <option value="contract">Contract Remark</option>
                                <option value="operation">Operation Remark</option>
                                <option value="finance">Finance Remark</option>
                                <option value="marketing">Marketing Remark</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="remarkText" class="form-label fw-bold">Remark <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="remarkText" name="remark_content" rows="6" maxlength="5000" required placeholder="Enter your remark here..."></textarea>
                            <small class="text-muted"><i class="fas fa-info-circle me-1"></i>Maximum 5000 characters</small>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="isEditableAfterApproval" name="is_editable_after_approval" checked>
                            <label class="form-check-label" for="isEditableAfterApproval">
                                <i class="fas fa-lock-open text-success me-1"></i> Allow Edit After Approval
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeRemarkModal(); return false;">
                            <i class="fas fa-times me-1"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Save Remark
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade" id="remarkModalBackdrop" onclick="closeRemarkModal()" style="display: none; z-index: 9999;"></div>
    `;
    
    // Remove existing modal if any
    const existing = document.getElementById('remarkModal');
    if (existing) {
        existing.remove();
    }
    const existingBackdrop = document.getElementById('remarkModalBackdrop');
    if (existingBackdrop) {
        existingBackdrop.remove();
    }
    
    // Inject modal at body level
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    console.log('✅ Remark modal created and injected to body (HIDDEN)');
}

// Load remarks when Remarks tab is shown
document.addEventListener('DOMContentLoaded', function() {
    // Create modal on page load
    createRemarkModal();
    
    const remarksTab = document.getElementById('remarks-tab');
    if (remarksTab) {
        remarksTab.addEventListener('shown.bs.tab', function() {
            loadRemarks();
        });
    }
    
    // Filter remarks by type
    const filterSelect = document.getElementById('remarkTypeFilter');
    if (filterSelect) {
        filterSelect.addEventListener('change', function() {
            renderRemarks();
        });
    }
    
    // Add Remark button handler
    const btnAddRemark = document.getElementById('btnAddRemark');
    if (btnAddRemark) {
        btnAddRemark.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            openAddRemarkModal();
        });
    }
    
    // Event delegation for Edit and Delete buttons (dynamically generated)
    document.addEventListener('click', function(e) {
        // Edit button
        if (e.target.closest('.btn-edit-remark')) {
            e.preventDefault();
            const button = e.target.closest('.btn-edit-remark');
            const remarkId = parseInt(button.getAttribute('data-remark-id'));
            if (remarkId) {
                editRemark(remarkId);
            }
        }
        
        // Delete button
        if (e.target.closest('.btn-delete-remark')) {
            e.preventDefault();
            const button = e.target.closest('.btn-delete-remark');
            const remarkId = parseInt(button.getAttribute('data-remark-id'));
            if (remarkId) {
                deleteRemark(remarkId);
            }
        }
    });
});

// Load all remarks from server
function loadRemarks() {
    fetch(`/marketing/contracts/{{ $contract->id }}/remarks`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                allRemarks = data.data;
                renderRemarks();
            }
        })
        .catch(error => {
            console.error('Error loading remarks:', error);
            document.getElementById('remarksContainer').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Error loading remarks. Please try again.
                </div>
            `;
        });
}

// Render remarks with optional filter
function renderRemarks() {
    const container = document.getElementById('remarksContainer');
    const filterType = document.getElementById('remarkTypeFilter').value;
    
    let filteredRemarks = allRemarks;
    if (filterType) {
        filteredRemarks = allRemarks.filter(r => r.remark_type === filterType);
    }
    
    if (filteredRemarks.length === 0) {
        container.innerHTML = `
            <div class="text-center py-5 text-muted">
                <i class="fas fa-comments fa-3x mb-3"></i>
                <p>No remarks found.</p>
            </div>
        `;
        return;
    }
    
    let html = '<div class="remarks-list">';
    filteredRemarks.forEach(remark => {
        const badgeClass = getRemarkBadgeClass(remark.remark_type);
        const badgeText = getRemarkTypeLabel(remark.remark_type);
        const createdDate = remark.created_at ? new Date(remark.created_at).toLocaleString('id-ID') : '-';
        const updatedDate = remark.updated_at ? new Date(remark.updated_at).toLocaleString('id-ID') : null;
        const creatorName = (remark.creator && typeof remark.creator === 'object' && remark.creator.name) ? remark.creator.name : '-';
        
        html += `
            <div class="card mb-3 remark-card" data-remark-id="${remark.id || ''}">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="badge ${badgeClass} me-2">${badgeText}</span>
                            ${remark.is_editable_after_approval ? '<i class="fas fa-lock-open text-success" title="Editable after approval"></i>' : '<i class="fas fa-lock text-danger" title="Locked after approval"></i>'}
                        </div>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-primary btn-sm btn-edit-remark" data-remark-id="${remark.id || ''}" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm btn-delete-remark" data-remark-id="${remark.id || ''}" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <p class="card-text mb-2" style="white-space: pre-wrap;">${escapeHtml(remark.remark_content || '')}</p>
                    <small class="text-muted">
                        <i class="fas fa-user me-1"></i>${creatorName}
                        <i class="fas fa-clock ms-3 me-1"></i>${createdDate}
                        ${updatedDate && updatedDate !== createdDate ? `<span class="ms-2">(Updated: ${updatedDate})</span>` : ''}
                    </small>
                </div>
            </div>
        `;
    });
    html += '</div>';
    
    container.innerHTML = html;
}

// Open modal to add new remark - MANUAL CONTROL
function openAddRemarkModal() {
    console.log('🔧 Opening Add Remark Modal (Manual)...');
    
    currentRemarkId = null;
    document.getElementById('remarkModalLabel').innerHTML = '<i class="fas fa-comment-alt me-2"></i>Add Remark';
    document.getElementById('remarkForm').reset();
    document.getElementById('remarkId').value = '';
    
    const modal = document.getElementById('remarkModal');
    const backdrop = document.getElementById('remarkModalBackdrop');
    
    if (!modal || !backdrop) {
        console.error('Modal not found, recreating...');
        createRemarkModal();
        return openAddRemarkModal();
    }
    
    // Show modal with animation
    backdrop.style.display = 'block';
    modal.style.display = 'block';
    
    setTimeout(() => {
        backdrop.classList.add('show');
        modal.classList.add('show');
        document.body.classList.add('modal-open');
        document.body.style.overflow = 'hidden';
        document.body.style.paddingRight = '0px';
    }, 10);
    
    console.log('✅ Modal opened manually');
}

// Close remark modal - MANUAL CONTROL
function closeRemarkModal() {
    console.log('🔧 Closing modal...');
    
    const modal = document.getElementById('remarkModal');
    const backdrop = document.getElementById('remarkModalBackdrop');
    
    if (modal && backdrop) {
        // Remove show class for fade out animation
        modal.classList.remove('show');
        backdrop.classList.remove('show');
        
        // Wait for animation then hide completely
        setTimeout(() => {
            modal.style.display = 'none';
            backdrop.style.display = 'none';
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
            console.log('✅ Modal closed');
        }, 150);
    } else {
        console.error('❌ Modal or backdrop not found!');
    }
    
    return false; // Prevent any default action
}

// Edit existing remark
function editRemark(remarkId) {
    const remark = allRemarks.find(r => r.id === remarkId);
    if (!remark) return;
    
    currentRemarkId = remarkId;
    document.getElementById('remarkModalLabel').innerHTML = '<i class="fas fa-edit me-2"></i>Edit Remark';
    document.getElementById('remarkId').value = remarkId;
    document.getElementById('remarkType').value = remark.remark_type;
    document.getElementById('remarkText').value = remark.remark_content;
    document.getElementById('isEditableAfterApproval').checked = remark.is_editable_after_approval;
    
    // Use manual open
    const modal = document.getElementById('remarkModal');
    const backdrop = document.getElementById('remarkModalBackdrop');
    
    if (modal && backdrop) {
        backdrop.style.display = 'block';
        modal.style.display = 'block';
        
        setTimeout(() => {
            backdrop.classList.add('show');
            modal.classList.add('show');
            document.body.classList.add('modal-open');
            document.body.style.overflow = 'hidden';
        }, 10);
    }
}

// Save remark (create or update)
function saveRemark() {
    const form = document.getElementById('remarkForm');
    const formData = new FormData(form);
    
    const data = {
        remark_type: formData.get('remark_type'),
        remark_content: formData.get('remark_content'),
        is_editable_after_approval: document.getElementById('isEditableAfterApproval').checked
    };
    
    const url = currentRemarkId 
        ? `/marketing/contracts/{{ $contract->id }}/remarks/${currentRemarkId}`
        : `/marketing/contracts/{{ $contract->id }}/remarks`;
    
    const method = currentRemarkId ? 'PUT' : 'POST';
    
    fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Close modal manually
            closeRemarkModal();
            
            // Reload remarks
            loadRemarks();
            
            // Show success notification
            showNotification('success', data.message);
        } else {
            showNotification('error', data.message || 'Failed to save remark');
        }
    })
    .catch(error => {
        console.error('Error saving remark:', error);
        showNotification('error', 'Error saving remark. Please try again.');
    });
}

// Delete remark
function deleteRemark(remarkId) {
    showConfirmDialog({
        title: 'Hapus Remark?',
        text: 'Apakah Anda yakin ingin menghapus remark ini?',
        icon: 'warning',
        confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (!result.isConfirmed) {
            return;
        }
        
        fetch(`/marketing/contracts/{{ $contract->id }}/remarks/${remarkId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                loadRemarks();
                showNotification('success', data.message);
            } else {
                showNotification('error', data.message || 'Gagal menghapus remark.');
            }
        })
        .catch(error => {
            console.error('Error deleting remark:', error);
            showNotification('error', 'Gagal menghapus remark. Silakan coba lagi.');
        });
    });
}

// Helper functions
function getRemarkBadgeClass(type) {
    const badges = {
        'contract': 'bg-primary text-white',
        'operation': 'bg-success text-white',
        'finance': 'bg-warning text-dark',
        'marketing': 'bg-purple text-white'
    };
    return badges[type] || 'bg-secondary text-white';
}

function getRemarkTypeLabel(type) {
    const labels = {
        'contract': 'Contract',
        'operation': 'Operation',
        'finance': 'Finance',
        'marketing': 'Marketing'
    };
    return labels[type] || type.toUpperCase();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showNotification(type, message) {
    if (type === 'success') {
        showSuccessDialog(message);
    } else {
        showErrorDialog(message);
    }
}
</script>
<style>
.bg-purple {
    background-color: #9333ea !important;
}

.remark-card {
    transition: box-shadow 0.2s;
}

.remark-card:hover {
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.remarks-list {
    max-height: 600px;
    overflow-y: auto;
}

/* Remark Modal - Manual Control Styles */
#remarkModal {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    z-index: 10000 !important;
    width: 100% !important;
    height: 100% !important;
    overflow-x: hidden !important;
    overflow-y: auto !important;
    outline: 0 !important;
    opacity: 0 !important;
    transition: opacity 0.15s linear !important;
}

#remarkModal.show {
    display: block !important;
    opacity: 1 !important;
}

#remarkModal .modal-dialog {
    position: relative !important;
    max-width: 700px !important;
    width: 90% !important;
    margin: 1.75rem auto !important;
    pointer-events: none !important;
    transform: translate(0, -50px) !important;
    transition: transform 0.3s ease-out !important;
}

@media (max-width: 768px) {
    #remarkModal .modal-dialog {
        max-width: 95% !important;
        width: 95% !important;
        margin: 1rem auto !important;
    }
}

#remarkModal.show .modal-dialog {
    transform: translate(0, 0) !important;
}

#remarkModal .modal-dialog-centered {
    display: flex !important;
    align-items: center !important;
    min-height: calc(100% - 3.5rem) !important;
}

#remarkModal .modal-content {
    position: relative !important;
    display: flex !important;
    flex-direction: column !important;
    width: 100% !important;
    pointer-events: auto !important;
    background-color: #fff !important;
    background-clip: padding-box !important;
    border: 1px solid rgba(0,0,0,.2) !important;
    border-radius: 0.5rem !important;
    outline: 0 !important;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.5) !important;
}

#remarkModalBackdrop {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    z-index: 9999 !important;
    width: 100vw !important;
    height: 100vh !important;
    background-color: rgba(0, 0, 0, 0.5) !important;
    opacity: 0 !important;
    transition: opacity 0.15s linear !important;
    cursor: pointer !important;
}

#remarkModalBackdrop.show {
    opacity: 1 !important;
}

body.modal-open {
    overflow: hidden !important;
}

/* MOM6: Contract Notes Modal Styles */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 10000;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.modal-overlay.show {
    display: flex !important;
    opacity: 1;
}

.modal-container {
    background: white;
    border-radius: 8px;
    max-width: 800px;
    width: 90%;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    transform: scale(0.9);
    transition: transform 0.3s ease;
}

.modal-overlay.show .modal-container {
    transform: scale(1);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e5e7eb;
    background-color: #f9fafb;
}

.modal-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #6b7280;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    transition: background-color 0.2s;
}

.modal-close:hover {
    background-color: #e5e7eb;
}

.modal-body {
    padding: 1.5rem;
    overflow-y: auto;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    padding: 1rem 1.5rem;
    border-top: 1px solid #e5e7eb;
    background-color: #f9fafb;
}

        /* Ensure Select2 dropdown appears above modal overlay */
#uploadFileModal .select2-container {
    z-index: 10009 !important;
    pointer-events: auto !important;
    position: relative !important;
}

#uploadFileModal .select2-container--open {
    z-index: 10010 !important;
    pointer-events: auto !important;
}

#uploadFileModal .select2-dropdown {
    z-index: 10010 !important;
    pointer-events: auto !important;
}

/* Ensure Select2 results/options are clickable */
#uploadFileModal .select2-results__option {
    pointer-events: auto !important;
    cursor: pointer !important;
    z-index: 10011 !important;
}

#uploadFileModal .select2-results {
    pointer-events: auto !important;
}

#uploadFileModal .select2-search__field {
    pointer-events: auto !important;
    z-index: 10011 !important;
}

#uploadFileModal .select2-selection {
    pointer-events: auto !important;
    cursor: pointer !important;
}

#uploadFileModal .select2-selection__arrow {
    pointer-events: auto !important;
    cursor: pointer !important;
}

/* Prevent modal from closing when clicking on select2 dropdown */
#uploadFileModal .select2-dropdown {
    position: absolute !important;
}

/* Ensure file input and buttons are clickable */
#uploadFileModal .modal-container input[type="file"],
#uploadFileModal .modal-container button,
#uploadFileModal .modal-container .btn {
    pointer-events: auto !important;
    z-index: 1 !important;
    position: relative;
}

/* Ensure modal container doesn't block Select2 clicks */
#uploadFileModal .modal-container {
    pointer-events: auto !important;
}
</style>

<!-- Active Contract Limited Edit Modal -->
<div id="activeContractEditModal" class="modal-overlay" onclick="closeActiveContractEditModal()">
    <div class="modal-container" onclick="event.stopPropagation()" style="max-width: 760px;">
        <div class="modal-header">
            <h2 class="modal-title">Edit Contract</h2>
            <button class="modal-close" onclick="closeActiveContractEditModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="alert alert-info mb-3">
                Hanya field terbatas yang dapat diedit saat kontrak sudah aktif.
            </div>
            <form id="activeContractEditForm">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold" for="active_contract_date">Contract Date</label>
                        <input type="date"
                               id="active_contract_date"
                               name="contract_date"
                               class="form-control"
                               value="{{ $contract->contract_date ? $contract->contract_date->format('Y-m-d') : '' }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold" for="active_signatory_name">Signatory Name</label>
                        <input type="text"
                               id="active_signatory_name"
                               name="signatory_name"
                               class="form-control"
                               value="{{ $contract->signatory_name }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold" for="active_signatory_position">Signatory Position</label>
                        <input type="text"
                               id="active_signatory_position"
                               name="signatory_position"
                               class="form-control"
                               value="{{ $contract->signatory_position }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold" for="active_signatory_npwp">Signatory NPWP</label>
                        <input type="text"
                               id="active_signatory_npwp"
                               name="signatory_npwp"
                               class="form-control"
                               value="{{ $contract->signatory_npwp }}">
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label fw-bold" for="active_signatory_address">Signatory Address</label>
                        <textarea id="active_signatory_address"
                                  name="signatory_address"
                                  rows="3"
                                  class="form-control">{{ $contract->signatory_address }}</textarea>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold" for="active_marketing_name">Marketing Name</label>
                        <input type="text"
                               id="active_marketing_name"
                               name="marketing_name"
                               class="form-control"
                               value="{{ $contract->marketing_name }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold" for="active_marketing_phone">Marketing Phone</label>
                        <input type="text"
                               id="active_marketing_phone"
                               name="marketing_phone"
                               class="form-control"
                               value="{{ $contract->marketing_phone }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold" for="active_marketing_email">Marketing Email</label>
                        <input type="email"
                               id="active_marketing_email"
                               name="marketing_email"
                               class="form-control"
                               value="{{ $contract->marketing_email }}">
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeActiveContractEditModal()">
                <i class="fas fa-times me-1"></i>Cancel
            </button>
            <button type="button" id="saveActiveContractEditBtn" class="btn btn-primary" onclick="saveActiveContractEdit()">
                <i class="fas fa-save me-1"></i>Save
            </button>
        </div>
    </div>
</div>

<!-- MOM6: File Upload Modal -->
<div id="uploadFileModal" class="modal-overlay" style="z-index: 10000;">
    <div class="modal-container" onclick="event.stopPropagation()" style="max-width: 600px; position: relative; z-index: 10000;">
        <div class="modal-header">
            <h2 class="modal-title">📄 Upload Contract File</h2>
            <button class="modal-close" onclick="closeUploadFileModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="uploadFileForm" enctype="multipart/form-data">
                <input type="hidden" id="uploadContractId" value="{{ $contract->id }}">
                
                <!-- File Type -->
                <div class="form-group mb-3">
                    <label class="form-label fw-bold">File Type <span class="text-danger">*</span></label>
                    <select id="file_type" name="file_type" class="form-control no-select2" required style="width: 100%;">
                        <option value="">-- Select File Type --</option>
                        @foreach($fileTypes ?? [] as $type)
                            <option value="{{ $type->code }}">{{ $type->option_name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- File Upload -->
                <div class="form-group mb-3">
                    <label class="form-label fw-bold">Select File <span class="text-danger">*</span></label>
                    <input type="file" id="file_upload" name="file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                    <small class="text-muted">Accepted formats: PDF, JPG, PNG, DOC, DOCX (Max: 5MB)</small>
                </div>

                <!-- Preview -->
                <div id="filePreview" class="alert alert-info" style="display: none;">
                    <strong>Selected File:</strong>
                    <div id="fileInfo"></div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeUploadFileModal()">
                <i class="fas fa-times me-1"></i>Cancel
            </button>
            <button type="button" class="btn btn-primary" onclick="uploadFile()">
                <i class="fas fa-upload me-1"></i>Upload File
            </button>
        </div>
    </div>
</div>

<!-- MOM6: Contract Notes Modal -->
<div id="contractNotesModal" class="modal-overlay" onclick="closeNotesModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 class="modal-title">📝 Edit Contract Notes</h2>
            <button class="modal-close" onclick="closeNotesModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            @php
                $userCanEditOperation = auth()->user()->hasRoleStartingWith('Operational')
                    || auth()->user()->hasRoleStartingWith('Marketing')
                    || auth()->user()->hasRoleStartingWith('Management')
                    || auth()->user()->hasRole('Admin');
                $userCanEditFinance = auth()->user()->hasRoleStartingWith('Finance')
                    || auth()->user()->hasRoleStartingWith('Marketing')
                    || auth()->user()->hasRoleStartingWith('Management')
                    || auth()->user()->hasRole('Admin');
                $userCanEditSales = auth()->user()->hasRoleStartingWith('Marketing')
                    || auth()->user()->hasRoleStartingWith('Management')
                    || auth()->user()->hasRole('Admin');
            @endphp
            <form id="contractNotesForm">
                <input type="hidden" id="notesContractId" value="{{ $contract->id }}">

                <!-- Notes Operation -->
                <div class="form-group mb-4" style="{{ $userCanEditOperation ? '' : 'display:none;' }}">
                    <label class="form-label" style="font-weight: 600; color: #2563eb;">
                        <i class="fas fa-tools me-2"></i>Notes Operation
                    </label>
                    <small class="text-muted d-block mb-2">Catatan untuk operation team (install, service, dll)</small>
                    <textarea
                        id="notes_operation"
                        name="notes_operation"
                        rows="4"
                        class="form-control"
                        {{ $userCanEditOperation ? '' : 'readonly' }}
                        placeholder="Contoh: Install unit di ruang meeting lantai 5, pastikan selesai sebelum jam 3 sore"
                    >{{ $contract->notes_operation }}</textarea>
                </div>

                <!-- Notes Finance -->
                <div class="form-group mb-4" style="{{ $userCanEditFinance ? '' : 'display:none;' }}">
                    <label class="form-label" style="font-weight: 600; color: #059669;">
                        <i class="fas fa-dollar-sign me-2"></i>Notes Finance
                    </label>
                    <small class="text-muted d-block mb-2">Catatan untuk finance/invoice team</small>
                    <textarea
                        id="notes_finance"
                        name="notes_finance"
                        rows="4"
                        class="form-control"
                        {{ $userCanEditFinance ? '' : 'readonly' }}
                        placeholder="Contoh: TOP 30 hari, invoice dikirim setiap tanggal 1, PIC: Ibu Siti"
                    >{{ $contract->notes_finance }}</textarea>
                </div>

                <!-- Notes Sales -->
                <div class="form-group mb-4" style="{{ $userCanEditSales ? '' : 'display:none;' }}">
                    <label class="form-label" style="font-weight: 600; color: #dc2626;">
                        <i class="fas fa-chart-line me-2"></i>Notes Sales
                    </label>
                    <small class="text-muted d-block mb-2">Catatan untuk sales/renewal (muncul pop-up saat renewal)</small>
                    <textarea
                        id="notes_sales"
                        name="notes_sales"
                        rows="4"
                        class="form-control"
                        {{ $userCanEditSales ? '' : 'readonly' }}
                        placeholder="Contoh: Renewal reminder 2 bulan sebelum expire. Customer prefer komunikasi via WA: 081234567890"
                    >{{ $contract->notes_sales }}</textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeNotesModal()">
                <i class="fas fa-times me-1"></i>Cancel
            </button>
            <button type="button" class="btn btn-primary" onclick="saveContractNotes()">
                <i class="fas fa-save me-1"></i>Save Notes
            </button>
        </div>
    </div>
</div>

<script>
// Contract Actions: Save Draft & Finalize
// Functions are now defined in global scope in the first script tag to ensure availability

// MOM6: File Upload Functions
function openUploadFileModal() {
    const modalOverlay = document.getElementById('uploadFileModal');
    modalOverlay.classList.add('show');
    document.body.style.overflow = 'hidden';
    // Reset form
    document.getElementById('uploadFileForm').reset();
    document.getElementById('filePreview').style.display = 'none';
    
    // Add click handler for modal overlay (click outside to close)
    $('#uploadFileModal').off('click.modal_close_handler').on('click.modal_close_handler', function(e) {
        if (e.target === this) {
            closeUploadFileModal();
        }
    });
    
    // Initialize Select2 for file_type dropdown after modal is shown
    // Use setTimeout to ensure modal is fully rendered and global Select2 doesn't interfere
    setTimeout(() => {
        const fileTypeSelect = $('#file_type');
        console.log('Initializing Select2 for file_type', fileTypeSelect.length, typeof $.fn.select2);
        
        if (fileTypeSelect.length && typeof $.fn.select2 !== 'undefined') {
            // IMPORTANT: Force destroy existing select2 instance if any (including from global init)
            try {
                if (fileTypeSelect.hasClass('select2-hidden-accessible')) {
                    fileTypeSelect.select2('destroy');
                }
                // Also try to destroy any Select2 containers that might exist
                const existingContainer = fileTypeSelect.next('.select2-container');
                if (existingContainer.length) {
                    existingContainer.remove();
                }
            } catch(e) {
                console.log('Error destroying Select2:', e);
            }
            
            // Remove all Select2 related classes
            fileTypeSelect.removeClass('select2-hidden-accessible no-select2');
            
            // Reset select to native temporarily to clear any Select2 state
            fileTypeSelect.css('display', 'block');
            fileTypeSelect.css('width', '100%');
            
            // Small delay to ensure DOM is clean
            setTimeout(() => {
                // Initialize select2 with dropdownParent pointing to body to avoid z-index issues
                try {
                    fileTypeSelect.select2({
                        dropdownParent: $('body'), // Use body to avoid z-index and event bubbling issues
                        placeholder: '-- Select File Type --',
                        allowClear: false,
                        minimumResultsForSearch: Infinity, // Disable search for small list
                        width: '100%'
                    });
                    
                    console.log('Select2 initialized successfully for file_type');
                    
                    // Ensure Select2 container is clickable
                    fileTypeSelect.next('.select2-container').css({
                        'pointer-events': 'auto',
                        'z-index': '9999'
                    });
                    
                    // CRITICAL: Disable modal overlay click handler when Select2 is open
                    const modalOverlay = $('#uploadFileModal');
                    
                    // Prevent modal from closing when clicking on select2 dropdown
                    $(document).off('select2:open.file_type_modal select2:close.file_type_modal').on('select2:open.file_type_modal', (e) => {
                        // Only handle select2 for file_type in upload modal
                        if ($(e.target).attr('id') === 'file_type') {
                            console.log('Select2 opened for file_type - disabling modal overlay click');
                            
                            // TEMPORARILY disable modal overlay click handler
                            modalOverlay.off('click.modal_close_handler');
                            
                            setTimeout(() => {
                                const select2Container = $('#uploadFileModal .select2-container--open');
                                const select2Dropdown = $('#uploadFileModal .select2-dropdown');
                                
                                // Ensure dropdown has high z-index and is clickable
                                if (select2Dropdown.length) {
                                    select2Dropdown.css({
                                        'z-index': '10010',
                                        'pointer-events': 'auto'
                                    });
                                }
                                
                                // Make sure all Select2 elements are clickable
                                $('#uploadFileModal .select2-container, #uploadFileModal .select2-dropdown, #uploadFileModal .select2-results__option').css({
                                    'pointer-events': 'auto',
                                    'cursor': 'pointer'
                                });
                                
                                console.log('Select2 dropdown ready, options should be clickable now');
                            }, 50);
                        }
                    }).on('select2:close.file_type_modal', (e) => {
                        // Re-enable modal overlay click handler when Select2 closes
                        if ($(e.target).attr('id') === 'file_type') {
                            console.log('Select2 closed for file_type - re-enabling modal overlay click');
                            modalOverlay.on('click.modal_close_handler', function(evt) {
                                if (evt.target === this) {
                                    closeUploadFileModal();
                                }
                            });
                        }
                    });
                } catch(e) {
                    console.error('Error initializing Select2:', e);
                    // Fallback: show native select if Select2 fails
                    fileTypeSelect.css('display', 'block');
                }
            }, 50);
        } else {
            console.error('Select2 not available or file_type select not found');
        }
    }, 200); // Increased timeout to ensure global Select2 has finished
}

function closeUploadFileModal() {
    // Destroy select2 instance before closing modal
    const fileTypeSelect = $('#file_type');
    if (fileTypeSelect.length && typeof $.fn.select2 !== 'undefined') {
        if (fileTypeSelect.hasClass('select2-hidden-accessible')) {
            fileTypeSelect.select2('destroy');
        }
        // Remove classes to prevent conflicts
        fileTypeSelect.removeClass('select2-hidden-accessible');
        // Add no-select2 class back to prevent global Select2 from re-initializing
        fileTypeSelect.addClass('no-select2');
    }
    
    // Remove event handlers to prevent memory leaks
    $(document).off('select2:open.file_type_modal');
    $('.select2-container--open, .select2-dropdown').off('mousedown.file_type_modal click.file_type_modal');
    
    document.getElementById('uploadFileModal').classList.remove('show');
    document.body.style.overflow = '';
}

// File preview on selection
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('file_upload');
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const preview = document.getElementById('filePreview');
                const info = document.getElementById('fileInfo');
                info.innerHTML = `
                    <i class="fas fa-file me-2"></i><strong>${file.name}</strong><br>
                    <small>Size: ${(file.size / 1024).toFixed(2)} KB</small>
                `;
                preview.style.display = 'block';
            }
        });
    }
});

function uploadFile() {
    const contractId = document.getElementById('uploadContractId').value;
    const fileType = document.getElementById('file_type').value;
    const fileInput = document.getElementById('file_upload');
    const file = fileInput.files[0];
    
    // Validation
    if (!fileType) {
        showWarningDialog('Pilih tipe file terlebih dahulu.');
        return;
    }
    
    if (!file) {
        showWarningDialog('Pilih file yang ingin di-upload.');
        return;
    }
    
    // Check file size (5MB max)
    if (file.size > 5 * 1024 * 1024) {
        showWarningDialog('Ukuran file melebihi 5MB. Silakan upload file yang lebih kecil.');
        return;
    }
    
    // Prepare FormData
    const formData = new FormData();
    formData.append('file', file);
    formData.append('file_type', fileType);
    
    // Show loading
    const uploadBtn = event.target;
    const originalText = uploadBtn.innerHTML;
    uploadBtn.disabled = true;
    uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Uploading...';
    
    // Send upload request
    fetch(`/marketing/contracts/${contractId}/upload-file`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            closeUploadFileModal();
            showSuccessDialog('File berhasil di-upload.').then(() => window.location.reload());
        } else {
            throw new Error(data.message || 'Failed to upload file');
        }
    })
    .catch(error => {
        console.error('Error uploading file:', error);
        showErrorDialog('Gagal mengunggah file: ' + error.message);
    })
    .finally(() => {
        uploadBtn.disabled = false;
        uploadBtn.innerHTML = originalText;
    });
}

function verifyFile(contractId, fileId) {
    return;
    
    const notes = 'File verified';
    
    fetch(`/marketing/contracts/${contractId}/files/${fileId}/verify`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify({ verification_notes: notes })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showSuccessDialog('File berhasil diverifikasi.').then(() => window.location.reload());
        } else {
            throw new Error(data.message || 'Failed to verify file');
        }
    })
    .catch(error => {
        console.error('Error verifying file:', error);
        showErrorDialog('Gagal memverifikasi file: ' + error.message);
    });
}

function rejectFile(contractId, fileId) {
    const reason = '';
    
    if (!reason || reason.trim() === '') {
        showWarningDialog('Alasan penolakan wajib diisi.');
        return;
    }
    
    fetch(`/marketing/contracts/${contractId}/files/${fileId}/reject`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify({ verification_notes: reason })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showWarningDialog('File berhasil ditolak.').then(() => window.location.reload());
        } else {
            throw new Error(data.message || 'Failed to reject file');
        }
    })
    .catch(error => {
        console.error('Error rejecting file:', error);
        showErrorDialog('Gagal menolak file: ' + error.message);
    });
}

function deleteFile(contractId, fileId) {
    return;
    
    fetch(`/marketing/contracts/${contractId}/files/${fileId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showSuccessDialog('File berhasil dihapus.');
            // Remove row from table
            const row = document.getElementById(`file-row-${fileId}`);
            if (row) row.remove();
        } else {
            throw new Error(data.message || 'Failed to delete file');
        }
    })
    .catch(error => {
        console.error('Error deleting file:', error);
        showErrorDialog('Gagal menghapus file: ' + error.message);
    });
}

// Bulk file approval functions
function toggleAllFileCheckboxes(selectAllCheckbox) {
    const checkboxes = document.querySelectorAll('.file-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = selectAllCheckbox.checked;
    });
    updateBulkApproveButton();
}

function updateBulkApproveButton() {
    const checkedBoxes = document.querySelectorAll('.file-checkbox:checked');
    const bulkApproveBtn = document.getElementById('btnBulkApprove');
    if (bulkApproveBtn) {
        bulkApproveBtn.disabled = checkedBoxes.length === 0;
    }
    
    // Update select all checkbox state
    const allCheckboxes = document.querySelectorAll('.file-checkbox');
    const selectAllCheckbox = document.getElementById('selectAllFiles');
    if (selectAllCheckbox && allCheckboxes.length > 0) {
        selectAllCheckbox.checked = checkedBoxes.length === allCheckboxes.length;
        selectAllCheckbox.indeterminate = checkedBoxes.length > 0 && checkedBoxes.length < allCheckboxes.length;
    }
}

async function bulkApproveFiles() {
    const checkedBoxes = document.querySelectorAll('.file-checkbox:checked');
    if (checkedBoxes.length === 0) {
        showWarningDialog('Pilih minimal satu file yang ingin di-approve.');
        return;
    }
    
    // Get contract ID from hidden input
    const contractId = document.getElementById('uploadContractId').value;
    if (!contractId) {
        showErrorDialog('Contract ID tidak ditemukan.');
        return;
    }
    
    const fileIds = Array.from(checkedBoxes).map(cb => parseInt(cb.value));
    
    const confirmResult = await showConfirmDialog({
        title: 'Approve File?',
        text: `Apakah Anda yakin ingin approve ${fileIds.length} file?`,
        icon: 'question',
        confirmButtonText: 'Ya, approve',
        cancelButtonText: 'Batal'
    });
    if (!confirmResult.isConfirmed) {
        return;
    }
    
    const btn = document.getElementById('btnBulkApprove');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Approving...';
    
    fetch(`/marketing/contracts/${contractId}/files/bulk-approve`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify({ file_ids: fileIds })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showSuccessDialog(data.message).then(() => window.location.reload());
        } else {
            throw new Error(data.message || 'Failed to approve files');
        }
    })
    .catch(error => {
        console.error('Error bulk approving files:', error);
        showErrorDialog('Gagal approve file: ' + error.message);
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        updateBulkApproveButton();
    });
}

// MOM6: Contract Notes Functions
function openNotesModal(contractId) {
    document.getElementById('contractNotesModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeNotesModal() {
    document.getElementById('contractNotesModal').classList.remove('show');
    document.body.style.overflow = '';
}

function saveContractNotes() {
    const contractId = document.getElementById('notesContractId').value;
    const notes_operation = document.getElementById('notes_operation').value;
    const notes_finance = document.getElementById('notes_finance').value;
    const notes_sales = document.getElementById('notes_sales').value;
    
    // Show loading
    const saveBtn = event.target;
    const originalText = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';
    
    // Send update request
    fetch(`/marketing/contracts/${contractId}/update-notes`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            notes_operation: notes_operation,
            notes_finance: notes_finance,
            notes_sales: notes_sales
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            closeNotesModal();
            showSuccessDialog('Catatan kontrak berhasil diperbarui.').then(() => window.location.reload());
        } else {
            throw new Error(data.message || 'Failed to update notes');
        }
    })
    .catch(error => {
        console.error('Error saving notes:', error);
        showErrorDialog('Gagal menyimpan catatan: ' + error.message);
    })
    .finally(() => {
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
    });
}

// Update Contract Date (Auto-save)
document.addEventListener('DOMContentLoaded', function() {
    const contractDateInput = document.getElementById('contractDateInput');

    if (!contractDateInput) {
        return;
    }

    if (typeof flatpickr !== 'undefined') {
        flatpickr(contractDateInput, {
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd/M/Y',
            allowInput: false,
            clickOpens: true,
            defaultDate: contractDateInput.value || null,
            onChange: function(selectedDates, dateStr) {
                updateContractDate(dateStr, contractDateInput);
            }
        });

        return;
    }

    contractDateInput.type = 'date';
    contractDateInput.readOnly = false;
    contractDateInput.addEventListener('change', function() {
        updateContractDate(this.value, this);
    });
});

function updateContractDate(newDate, input = document.getElementById('contractDateInput')) {
    if (!newDate || !input) {
        return;
    }
    
    // Visual feedback
    input.classList.add('bg-light-warning');
    
    fetch(`/marketing/contracts/${contractId}/editable-fields`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            contract_date: newDate
        })
    })
    .then(response => response.json())
        .then(data => {
        if (data.status === 'success') {
            // Success feedback
            input.classList.remove('bg-light-warning');
            input.classList.add('bg-light-success');
            setTimeout(() => {
                input.classList.remove('bg-light-success');
            }, 500);
            
            // Optional toast for success
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
            Toast.fire({
                icon: 'success',
                title: 'Tanggal kontrak berhasil diperbarui'
            });
        } else {
            throw new Error(data.message || 'Gagal memperbarui tanggal');
        }
    })
    .catch(error => {
        console.error('Error updating contract date:', error);
        
        // Show SweetAlert Error
        Swal.fire({
            icon: 'error',
            title: 'Gagal Update Tanggal',
            text: error.message,
            confirmButtonColor: '#3085d6',
            confirmButtonText: 'OK'
        });

        // Reset style
        input.classList.remove('bg-light-warning');
        input.classList.add('is-invalid');
    });
}

// Additional Info Edit Functions
function editAdditionalInfo() {
    document.getElementById('additionalInfoView').style.display = 'none';
    document.getElementById('additionalInfoEdit').style.display = 'block';
    document.getElementById('editAdditionalInfoBtn').style.display = 'none';
}

function cancelEditAdditionalInfo() {
    document.getElementById('additionalInfoView').style.display = 'block';
    document.getElementById('additionalInfoEdit').style.display = 'none';
    document.getElementById('editAdditionalInfoBtn').style.display = 'block';
    // Reset form to original values
    location.reload();
}

// Handle Additional Info Form Submit
document.addEventListener('DOMContentLoaded', function() {
    const additionalInfoForm = document.getElementById('additionalInfoForm');
    if (additionalInfoForm) {
        additionalInfoForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';
            
            fetch(`/marketing/contracts/${contractId}/update-additional-info`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessDialog('Informasi tambahan berhasil diperbarui.').then(() => window.location.reload());
                } else {
                    throw new Error(data.message || 'Failed to update additional info');
                }
            })
            .catch(error => {
                console.error('Error updating additional info:', error);
                showErrorDialog('Gagal memperbarui informasi tambahan: ' + error.message);
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });
    }
});

// verifyFile function is already defined above, no need to duplicate

function rejectFile(contractId, fileId) {
    const reason = '';
    
    if (!reason || reason.trim() === '') {
        showWarningDialog('Alasan penolakan wajib diisi.');
        return;
    }
    
    fetch(`/marketing/contracts/${contractId}/files/${fileId}/reject`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify({ verification_notes: reason })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showWarningDialog('File berhasil ditolak.').then(() => window.location.reload());
        } else {
            throw new Error(data.message || 'Failed to reject file');
        }
    })
    .catch(error => {
        console.error('Error rejecting file:', error);
        showErrorDialog('Gagal menolak file: ' + error.message);
    });
}

function deleteFile(contractId, fileId) {
    return;
    
    fetch(`/marketing/contracts/${contractId}/files/${fileId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showSuccessDialog('File berhasil dihapus.');
            // Remove row from table
            const row = document.getElementById(`file-row-${fileId}`);
            if (row) row.remove();
        } else {
            throw new Error(data.message || 'Failed to delete file');
        }
    })
    .catch(error => {
        console.error('Error deleting file:', error);
        showErrorDialog('Gagal menghapus file: ' + error.message);
    });
}

// MOM6: Contract Notes Functions
function openNotesModal(contractId) {
    document.getElementById('contractNotesModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeNotesModal() {
    document.getElementById('contractNotesModal').classList.remove('show');
    document.body.style.overflow = '';
}

function saveContractNotes() {
    const contractId = document.getElementById('notesContractId').value;
    const notes_operation = document.getElementById('notes_operation').value;
    const notes_finance = document.getElementById('notes_finance').value;
    const notes_sales = document.getElementById('notes_sales').value;
    
    // Show loading
    const saveBtn = event.target;
    const originalText = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';
    
    // Send update request
    fetch(`/marketing/contracts/${contractId}/update-notes`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            notes_operation: notes_operation,
            notes_finance: notes_finance,
            notes_sales: notes_sales
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            closeNotesModal();
            showSuccessDialog('Catatan kontrak berhasil diperbarui.').then(() => window.location.reload());
        } else {
            throw new Error(data.message || 'Failed to update notes');
        }
    })
    .catch(error => {
        console.error('Error saving notes:', error);
        showErrorDialog('Gagal menyimpan catatan: ' + error.message);
    })
    .finally(() => {
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
    });
}

// Contract Status Action Functions
// saveDraft and finalizeContract are now defined in global scope in the first script tag

async function approveContract(contractId) {
    const confirmResult = await showConfirmDialog({
        title: 'Approve Contract?',
        text: 'Apakah Anda yakin ingin approve kontrak ini? Kontrak akan menjadi aktif.',
        icon: 'question',
        confirmButtonText: 'Ya, approve',
        cancelButtonText: 'Batal'
    });
    if (!confirmResult.isConfirmed) {
        return;
    }
    
    fetch(`/marketing/contracts/${contractId}/approve`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showSuccessDialog(data.message).then(() => window.location.reload());
        } else {
            throw new Error(data.message || 'Failed to approve contract');
        }
    })
    .catch(error => {
        console.error('Error approving contract:', error);
        showErrorDialog('Gagal approve kontrak: ' + error.message);
    });
}

// ================================================
// Room Management Functions
// ================================================
function createRoomModal() {
    // Prevent duplicate modals
    if (document.getElementById('roomModal')) {
        return;
    }
    
    const modalHTML = `
    <div class="modal fade" id="roomModal" tabindex="-1" aria-labelledby="roomModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #1e3a8a; color: white;">
                    <h5 class="modal-title" id="roomModalLabel">
                        <i class="fas fa-door-open me-2"></i><span id="roomModalTitle">Add Room</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="roomForm">
                        <input type="hidden" id="roomId" name="room_id">
                        <input type="hidden" id="roomContractId" name="contract_id">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="roomBuildingSelect" class="form-label">Building <span class="text-danger">*</span></label>
                                <select class="form-control" id="roomBuildingSelect" name="building_id" required onchange="loadRoomsForBuilding(this.value)">
                                    <option value="">Select Building...</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="roomSelect" class="form-label">Room <span class="text-danger">*</span></label>
                                <select class="form-control" id="roomSelect" name="master_room_id" required>
                                    <option value="">Select Room...</option>
                                </select>
                            </div>
                        </div>
                        
                        <div id="roomDetails" class="alert alert-info" style="display: none;">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Room Type:</strong> <span id="roomTypeDisplay">-</span>
                                </div>
                                <div class="col-md-6">
                                    <strong>Floor:</strong> <span id="roomFloorDisplay">-</span>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveRoom()">
                        <i class="fas fa-save me-1"></i>Save Room
                    </button>
                </div>
            </div>
        </div>
    </div>`;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

// Modal is created when needed (not on page load to avoid visibility issues)
function openAddRoomModal(contractId) {
    createRoomModal();
    
    document.getElementById('roomModalTitle').textContent = 'Add Room';
    document.getElementById('roomId').value = '';
    document.getElementById('roomContractId').value = contractId;
    document.getElementById('roomForm').reset();
    document.getElementById('roomDetails').style.display = 'none';
    
    // Reset dropdowns
    document.getElementById('roomBuildingSelect').innerHTML = '<option value="">Select Building...</option>';
    document.getElementById('roomSelect').innerHTML = '<option value="">Select Room...</option>';
    
    // Load buildings from contract
    loadBuildingsForContract(contractId);
    
    // Get or create modal instance with proper options
    const modalElement = document.getElementById('roomModal');
    let modalInstance = bootstrap.Modal.getInstance(modalElement);
    if (!modalInstance) {
        modalInstance = new bootstrap.Modal(modalElement, {
            backdrop: true,
            keyboard: true,
            focus: true
        });
    }
    modalInstance.show();
}

function openEditRoomModal(contractRoomId) {
    createRoomModal();
    
    document.getElementById('roomModalTitle').textContent = 'Edit Room';
    
    // Fetch room data
    fetch(`/marketing/contracts/rooms/${contractRoomId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const room = data.data;
                document.getElementById('roomId').value = room.id;
                document.getElementById('roomContractId').value = room.contract_id;
                
                // Load buildings and pre-select
                loadBuildingsForContract(room.contract_id, room.room?.building_id || null);
                
                // Load rooms for that building and pre-select
                setTimeout(() => {
                    loadRoomsForBuilding(room.room?.building_id || 0, room.room_id);
                }, 300);
                
                // Get or create modal instance with proper options
                const modalElement = document.getElementById('roomModal');
                let modalInstance = bootstrap.Modal.getInstance(modalElement);
                if (!modalInstance) {
                    modalInstance = new bootstrap.Modal(modalElement, {
                        backdrop: true,
                        keyboard: true,
                        focus: true
                    });
                }
                modalInstance.show();
            } else {
                showErrorDialog('Gagal memuat data ruangan.');
            }
        })
        .catch(error => {
            console.error('Error loading room:', error);
            showErrorDialog('Gagal memuat data ruangan.');
        });
}

function loadBuildingsForContract(contractId, preselectedBuildingId = null) {
    const select = document.getElementById('roomBuildingSelect');
    select.innerHTML = '<option value="">Loading...</option>';
    
    // Fetch buildings from contract's surveys
    fetch(`/marketing/contracts/${contractId}/buildings-for-rooms`)
        .then(response => response.json())
        .then(data => {
            select.innerHTML = '<option value="">Select Building...</option>';
            if (data.success && data.buildings) {
                data.buildings.forEach(building => {
                    const option = document.createElement('option');
                    option.value = building.id;
                    option.textContent = building.nama_gedung || building.name;
                    if (preselectedBuildingId && building.id == preselectedBuildingId) {
                        option.selected = true;
                    }
                    select.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading buildings:', error);
            select.innerHTML = '<option value="">Error loading buildings</option>';
        });
}

function loadRoomsForBuilding(buildingId, preselectedRoomId = null) {
    const select = document.getElementById('roomSelect');
    select.innerHTML = '<option value="">Loading...</option>';
    document.getElementById('roomDetails').style.display = 'none';
    
    if (!buildingId) {
        select.innerHTML = '<option value="">Select Room...</option>';
        return;
    }
    
    fetch(`/api/master-rooms/by-building/${buildingId}`)
        .then(response => response.json())
        .then(data => {
            select.innerHTML = '<option value="">Select Room...</option>';
            const rooms = data.data || data.rooms || data;
            if (Array.isArray(rooms)) {
                rooms.forEach(room => {
                    const option = document.createElement('option');
                    option.value = room.id;
                    option.textContent = room.room_name;
                    option.setAttribute('data-type', room.room_type || '');
                    option.setAttribute('data-floor', room.room_floor || room.floor || '');
                    if (preselectedRoomId && room.id == preselectedRoomId) {
                        option.selected = true;
                    }
                    select.appendChild(option);
                });
                
                // Subscribe to change event to show details
                select.onchange = function() {
                    const selected = this.options[this.selectedIndex];
                    if (selected.value) {
                        document.getElementById('roomTypeDisplay').textContent = selected.getAttribute('data-type') || '-';
                        document.getElementById('roomFloorDisplay').textContent = selected.getAttribute('data-floor') || '-';
                        document.getElementById('roomDetails').style.display = 'block';
                    } else {
                        document.getElementById('roomDetails').style.display = 'none';
                    }
                };
            }
        })
        .catch(error => {
            console.error('Error loading rooms:', error);
            select.innerHTML = '<option value="">Error loading rooms</option>';
        });
}

function saveRoom() {
    const contractId = document.getElementById('roomContractId').value;
    const roomId = document.getElementById('roomId').value;
    const masterRoomId = document.getElementById('roomSelect').value;
    
    if (!masterRoomId) {
        showWarningDialog('Pilih ruangan terlebih dahulu.');
        return;
    }
    
    const url = roomId 
        ? `/marketing/contracts/${contractId}/rooms/${roomId}` 
        : `/marketing/contracts/${contractId}/rooms`;
    const method = roomId ? 'PUT' : 'POST';
    
    fetch(url, {
        method: method,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            room_id: masterRoomId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessDialog('Ruangan berhasil disimpan.').then(() => {
                bootstrap.Modal.getInstance(document.getElementById('roomModal')).hide();
                window.location.reload();
            });
        } else {
            throw new Error(data.message || 'Failed to save room');
        }
    })
    .catch(error => {
        console.error('Error saving room:', error);
        showErrorDialog(error.message);
    });
}

async function confirmDeleteRoom(contractRoomId) {
    const confirmResult = await showConfirmDialog({
        title: 'Hapus Ruangan?',
        text: 'Apakah Anda yakin ingin menghapus ruangan ini dari kontrak?',
        icon: 'warning',
        confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Batal'
    });
    if (!confirmResult.isConfirmed) {
        return;
    }
    
    fetch(`/marketing/contracts/rooms/${contractRoomId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessDialog('Ruangan berhasil dihapus.').then(() => window.location.reload());
        } else {
            throw new Error(data.message || 'Failed to delete room');
        }
    })
    .catch(error => {
        console.error('Error deleting room:', error);
        showErrorDialog(error.message);
    });
}

// Save Draft function
function saveDraft(contractId) {
    Swal.fire({
        title: 'Memproses...',
        text: 'Menyimpan draft...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    fetch(`/marketing/contracts/${contractId}/save-draft`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success || data.status === 'success') {
            Swal.fire({
                title: 'Berhasil!',
                text: 'Draft berhasil disimpan',
                icon: 'success'
            }).then(() => location.reload());
        } else {
            throw new Error(data.message || 'Failed to save draft');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Error!', error.message, 'error');
    });
}

// Finalize Contract function with operational area validation
async function finalizeContract(contractId) {
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
        // Get first building from contract to check operational area
        const buildingCheckResponse = await fetch(`/marketing/contracts/${contractId}/buildings-for-rooms`);
        const buildingData = await buildingCheckResponse.json();
        
        if (buildingData.success && buildingData.buildings && buildingData.buildings.length > 0) {
            const firstBuilding = buildingData.buildings[0];
            
            // Check if building's city is in operational area
            const response = await fetch(`/operational/api/check-operational-area/${firstBuilding.id}`);
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
                                <strong>Building:</strong> ${firstBuilding.nama_gedung || firstBuilding.name || 'N/A'}<br>
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
        }
        
        // Proceed with finalize confirmation
        proceedWithFinalizeContract(contractId);
        
    } catch (error) {
        console.error('Error checking operational area:', error);
        // If error, proceed anyway (don't block user)
        proceedWithFinalizeContract(contractId);
    }
}

function proceedWithFinalizeContract(contractId) {
    Swal.fire({
        title: 'Finalize Contract?',
        text: 'Contract yang sudah difinalisasi akan dikirim untuk approval. Pastikan semua data sudah benar.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Finalize!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Memproses...',
                text: 'Memfinalisasi contract...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch(`/marketing/contracts/${contractId}/finalize`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success || data.status === 'success') {
                    Swal.fire({
                        title: 'Berhasil!',
                        text: 'Contract berhasil difinalisasi',
                        icon: 'success'
                    }).then(() => location.reload());
                } else {
                    throw new Error(data.message || 'Failed to finalize contract');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error!', error.message, 'error');
            });
        }
    });
}

// Approve Contract function
function approveContract(contractId) {
    Swal.fire({
        title: 'Approve Contract?',
        text: 'Contract akan disetujui dan statusnya berubah menjadi Active.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Approve!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Memproses...',
                text: 'Menyetujui contract...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch(`/marketing/contracts/${contractId}/approve`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success || data.status === 'success') {
                    Swal.fire({
                        title: 'Berhasil!',
                        text: 'Contract berhasil disetujui',
                        icon: 'success'
                    }).then(() => location.reload());
                } else {
                    throw new Error(data.message || 'Failed to approve contract');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error!', error.message, 'error');
            });
        }
    });
}

// Unpost Contract function
function unpostContract(contractId) {
    Swal.fire({
        title: 'Unpost Contract?',
        text: 'Contract akan dikembalikan ke status Draft agar bisa diedit kembali.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Unpost!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Memproses...',
                text: 'Mengembalikan ke draft...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch(`/marketing/contracts/${contractId}/unpost`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success || data.status === 'success') {
                    Swal.fire({
                        title: 'Berhasil!',
                        text: 'Contract berhasil dikembalikan ke draft',
                        icon: 'success'
                    }).then(() => location.reload());
                } else {
                    throw new Error(data.message || 'Failed to unpost contract');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error!', error.message, 'error');
            });
        }
    });
}

// Reject Contract function
function rejectContract(contractId) {
    Swal.fire({
        title: 'Reject Contract?',
        text: 'Contract akan ditolak dan statusnya berubah menjadi Rejected.',
        icon: 'error',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Reject!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Memproses...',
                text: 'Menolak contract...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch(`/marketing/contracts/${contractId}/reject`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success || data.status === 'success') {
                    Swal.fire({
                        title: 'Berhasil!',
                        text: 'Contract telah ditolak',
                        icon: 'success'
                    }).then(() => location.reload());
                } else {
                    throw new Error(data.message || 'Failed to reject contract');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error!', error.message, 'error');
            });
        }
    });
}

function updateBaFilesSupported(contractId, supported) {
    const message = supported 
        ? "Anda yakin memilih Yes? Invoice akan membutuhkan verifikasi BA files."
        : "Anda yakin memilih No? Invoice akan tergenerate tanpa BA files.";

    Swal.fire({
        title: 'Konfirmasi',
        text: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, Lanjutkan',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            const btnYes = document.getElementById('btnBaSupportedYes');
            const btnNo = document.getElementById('btnBaSupportedNo');
            const statusText = document.getElementById('baSupportedStatus');
            
            // Disable buttons during request
            btnYes.disabled = true;
            btnNo.disabled = true;
            
            fetch(`/marketing/contracts/${contractId}/toggle-ba-supported`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    ba_files_supported: supported
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update UI
                    if (supported) {
                        btnYes.classList.remove('btn-outline-success');
                        btnYes.classList.add('btn-success');
                        btnNo.classList.remove('btn-danger');
                        btnNo.classList.add('btn-outline-danger');
                        statusText.textContent = 'Invoice requires BA Files';
                    } else {
                        btnYes.classList.remove('btn-success');
                        btnYes.classList.add('btn-outline-success');
                        btnNo.classList.remove('btn-outline-danger');
                        btnNo.classList.add('btn-danger');
                        statusText.textContent = 'Invoice can generate without BA Files';
                    }
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                } else {
                    throw new Error(data.message || 'Gagal memperbarui status');
                }
            })
            .catch(error => {
                console.error('Error updating BA Files Supported:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message
                });
            })
            .finally(() => {
                btnYes.disabled = false;
                btnNo.disabled = false;
            });
        }
    });
}

function updateHoldInvoice(contractId, hold) {
    const message = hold 
        ? "Anda yakin ingin MENUNDA pembuatan invoice untuk kontrak ini? Invoice tidak akan tergenerate secara otomatis."
        : "Anda yakin ingin mengaktifkan kembali pembuatan invoice? Invoice akan tergenerate secara normal.";

    Swal.fire({
        title: 'Konfirmasi',
        text: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: hold ? '#e67e22' : '#28a745',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, Lanjutkan',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            const btnYes = document.getElementById('btnHoldInvoiceYes');
            const btnNo = document.getElementById('btnHoldInvoiceNo');
            const statusText = document.getElementById('holdInvoiceStatus');
            
            // Disable buttons during request
            btnYes.disabled = true;
            btnNo.disabled = true;
            
            fetch(`/marketing/contracts/${contractId}/toggle-hold-invoice`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    hold_invoice: hold
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update UI
                    if (hold) {
                        btnYes.classList.remove('btn-outline-warning');
                        btnYes.classList.add('btn-warning');
                        btnNo.classList.remove('btn-success');
                        btnNo.classList.add('btn-outline-success');
                        statusText.textContent = 'Invoices are currently HELD';
                    } else {
                        btnYes.classList.remove('btn-warning');
                        btnYes.classList.add('btn-outline-warning');
                        btnNo.classList.remove('btn-outline-success');
                        btnNo.classList.add('btn-success');
                        statusText.textContent = 'Invoices are generated normally';
                    }
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                } else {
                    throw new Error(data.message || 'Gagal memperbarui status');
                }
            })
            .catch(error => {
                console.error('Error updating Hold Invoice status:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message
                });
            })
            .finally(() => {
                btnYes.disabled = false;
                btnNo.disabled = false;
            });
        }
    });
}


function updateContractTarget(contractId, isContract) {
    const message = isContract 
        ? "Anda yakin ingin mengaktifkan Contract untuk kontrak ini?"
        : "Anda yakin ingin menonaktifkan Contract untuk kontrak ini?";

    Swal.fire({
        title: 'Konfirmasi Contract',
        text: message,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: isContract ? '#28a745' : '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Lanjutkan',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            const btnYes = document.getElementById('btnContractTargetYes');
            const btnNo = document.getElementById('btnContractTargetNo');
            
            // Disable buttons during request
            btnYes.disabled = true;
            btnNo.disabled = true;
            
            fetch(`/marketing/contracts/${contractId}/toggle-contract-target`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    is_contract: isContract
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update UI
                    if (isContract) {
                        btnYes.classList.remove('btn-outline-secondary');
                        btnYes.classList.add('btn-success');
                        btnYes.style.color = 'white';
                        
                        btnNo.classList.remove('btn-danger');
                        btnNo.classList.add('btn-outline-secondary');
                        btnNo.style.color = '#6c757d';
                    } else {
                        btnYes.classList.remove('btn-success');
                        btnYes.classList.add('btn-outline-secondary');
                        btnYes.style.color = '#6c757d';
                        
                        btnNo.classList.remove('btn-outline-secondary');
                        btnNo.classList.add('btn-danger');
                        btnNo.style.color = 'white';
                    }
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                } else {
                    throw new Error(data.message || 'Gagal memperbarui status Contract Target');
                }
            })
            .catch(error => {
                console.error('Error updating Contract Target status:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message
                });
            })
            .finally(() => {
                btnYes.disabled = false;
                btnNo.disabled = false;
            });
        }
    });
}

// Update Achiever (Commission Recipient)
function updateAchiever(contractId, userId) {
    if (!userId) return;
    
    Swal.fire({
        title: 'Mengubah Achiever',
        text: 'Apakah Anda yakin ingin mengubah penerima komisi untuk kontrak ini?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#214589',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Ubah',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`/marketing/contracts/${contractId}/update-achiever`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    commission_recipient_id: userId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                } else {
                    throw new Error(data.message || 'Gagal mengubah achiever');
                }
            })
            .catch(error => {
                console.error('Error updating achiever:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message
                });
                // Reload to reset dropdown
                location.reload();
            });
        } else {
            // Reset dropdown to previous value
            location.reload();
        }
    });
}

// Update Contract Net
// function toggleContractNetEdit(show) removed as UI is now single-line

// Update Contract Net
function updateContractNet(input) {
    const contractId = {{ $contract->id }};
    const netValue = input.value;
    const loadingEl = document.getElementById('contractNetLoading');
    
    if (netValue === '' || isNaN(netValue)) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Nilai Contract Net harus berupa angka'
        });
        return;
    }

    // Show loading
    if (loadingEl) loadingEl.style.display = 'block';
    input.classList.add('bg-light-warning');
    
    fetch(`/marketing/contracts/${contractId}/update-net-value`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            net_value: netValue
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Success feedback
            input.classList.remove('bg-light-warning');
            input.classList.add('bg-light-success');
            setTimeout(() => {
                input.classList.remove('bg-light-success');
            }, 1500);

            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
            Toast.fire({
                icon: 'success',
                title: 'Contract Net berhasil diperbarui'
            });
        } else {
            throw new Error(data.message || 'Gagal memperbarui Contract Net');
        }
    })
    .catch(error => {
        console.error('Error updating Contract Net:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message
        });
    })
    .finally(() => {
        if (loadingEl) loadingEl.style.display = 'none';
    });
}
</script>
<script>
function showNotification(type, message) {
    if (type === 'success') {
        return showSuccessDialog(message);
    }

    return showErrorDialog(message);
}

function deleteBillingGroup(billingGroupId) {
    showConfirmDialog({
        title: 'Hapus Billing Group?',
        text: 'Apakah Anda yakin ingin menghapus billing group ini? Semua assignment gedung akan ikut dilepas.',
        icon: 'warning',
        confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (!result.isConfirmed) return;

        $.ajax({
            url: `/api/billing-groups/${billingGroupId}`,
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function () {
                showSuccessDialog('Billing group berhasil dihapus.').then(() => location.reload());
            },
            error: function (xhr) {
                showErrorDialog('Gagal menghapus billing group: ' + (xhr.responseJSON?.message || 'Terjadi kesalahan.'));
            }
        });
    });
}

function deleteRemark(remarkId) {
    showConfirmDialog({
        title: 'Hapus Remark?',
        text: 'Apakah Anda yakin ingin menghapus remark ini?',
        icon: 'warning',
        confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (!result.isConfirmed) return;

        fetch(`/marketing/contracts/{{ $contract->id }}/remarks/${remarkId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                loadRemarks();
                showNotification('success', data.message);
            } else {
                showNotification('error', data.message || 'Gagal menghapus remark.');
            }
        })
        .catch(error => {
            console.error('Error deleting remark:', error);
            showNotification('error', 'Gagal menghapus remark. Silakan coba lagi.');
        });
    });
}

async function verifyFile(contractId, fileId) {
    const confirmResult = await showConfirmDialog({
        title: 'Verifikasi File?',
        text: 'Apakah file ini valid dan siap diverifikasi?',
        icon: 'question',
        confirmButtonText: 'Ya, verifikasi',
        cancelButtonText: 'Batal'
    });

    if (!confirmResult.isConfirmed) return;

    const noteResult = await Swal.fire({
        title: 'Catatan Verifikasi',
        input: 'textarea',
        inputLabel: 'Catatan verifikasi opsional',
        inputPlaceholder: 'Tulis catatan verifikasi di sini...',
        inputValue: 'File verified',
        showCancelButton: true,
        confirmButtonText: 'Simpan',
        cancelButtonText: 'Batal'
    });

    if (noteResult.dismiss === Swal.DismissReason.cancel) return;

    fetch(`/marketing/contracts/${contractId}/files/${fileId}/verify`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify({ verification_notes: noteResult.value || 'File verified' })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showSuccessDialog('File berhasil diverifikasi.').then(() => window.location.reload());
        } else {
            throw new Error(data.message || 'Gagal memverifikasi file');
        }
    })
    .catch(error => {
        console.error('Error verifying file:', error);
        showErrorDialog('Gagal memverifikasi file: ' + error.message);
    });
}

async function rejectFile(contractId, fileId) {
    const reasonResult = await Swal.fire({
        title: 'Tolak File',
        input: 'textarea',
        inputLabel: 'Alasan penolakan',
        inputPlaceholder: 'Tulis alasan penolakan...',
        showCancelButton: true,
        confirmButtonText: 'Tolak File',
        cancelButtonText: 'Batal',
        inputValidator: (value) => {
            if (!value || !value.trim()) {
                return 'Alasan penolakan wajib diisi.';
            }
        }
    });

    if (!reasonResult.isConfirmed) return;

    fetch(`/marketing/contracts/${contractId}/files/${fileId}/reject`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify({ verification_notes: reasonResult.value })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showWarningDialog('File berhasil ditolak.').then(() => window.location.reload());
        } else {
            throw new Error(data.message || 'Gagal menolak file');
        }
    })
    .catch(error => {
        console.error('Error rejecting file:', error);
        showErrorDialog('Gagal menolak file: ' + error.message);
    });
}

function deleteFile(contractId, fileId) {
    showConfirmDialog({
        title: 'Hapus File?',
        text: 'Apakah Anda yakin ingin menghapus file ini? Tindakan ini tidak bisa dibatalkan.',
        icon: 'warning',
        confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (!result.isConfirmed) return;

        fetch(`/marketing/contracts/${contractId}/files/${fileId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showSuccessDialog('File berhasil dihapus.');
                const row = document.getElementById(`file-row-${fileId}`);
                if (row) row.remove();
            } else {
                throw new Error(data.message || 'Gagal menghapus file');
            }
        })
        .catch(error => {
            console.error('Error deleting file:', error);
            showErrorDialog('Gagal menghapus file: ' + error.message);
        });
    });
}

async function bulkApproveFiles() {
    const checkedBoxes = document.querySelectorAll('.file-checkbox:checked');
    if (checkedBoxes.length === 0) {
        showWarningDialog('Pilih minimal satu file yang ingin di-approve.');
        return;
    }

    const contractId = document.getElementById('uploadContractId').value;
    if (!contractId) {
        showErrorDialog('Contract ID tidak ditemukan.');
        return;
    }

    const fileIds = Array.from(checkedBoxes).map(cb => parseInt(cb.value));

    showConfirmDialog({
        title: 'Approve File?',
        text: `Apakah Anda yakin ingin approve ${fileIds.length} file?`,
        icon: 'question',
        confirmButtonText: 'Ya, approve',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (!result.isConfirmed) return;

        const btn = document.getElementById('btnBulkApprove');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Memproses...';

        fetch(`/marketing/contracts/${contractId}/files/bulk-approve`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ file_ids: fileIds })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showSuccessDialog(data.message).then(() => window.location.reload());
            } else {
                throw new Error(data.message || 'Gagal approve file');
            }
        })
        .catch(error => {
            console.error('Error bulk approving files:', error);
            showErrorDialog('Gagal approve file: ' + error.message);
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            updateBulkApproveButton();
        });
    });
}

function approveContract(contractId) {
    showConfirmDialog({
        title: 'Approve Contract?',
        text: 'Apakah Anda yakin ingin approve kontrak ini? Kontrak akan menjadi aktif.',
        icon: 'question',
        confirmButtonText: 'Ya, approve',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (!result.isConfirmed) return;

        fetch(`/marketing/contracts/${contractId}/approve`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showSuccessDialog(data.message).then(() => window.location.reload());
            } else {
                throw new Error(data.message || 'Gagal approve kontrak');
            }
        })
        .catch(error => {
            console.error('Error approving contract:', error);
            showErrorDialog('Gagal approve kontrak: ' + error.message);
        });
    });
}

function saveRoom() {
    const contractId = document.getElementById('roomContractId').value;
    const roomId = document.getElementById('roomId').value;
    const masterRoomId = document.getElementById('roomSelect').value;

    if (!masterRoomId) {
        showWarningDialog('Pilih ruangan terlebih dahulu.');
        return;
    }

    const url = roomId
        ? `/marketing/contracts/${contractId}/rooms/${roomId}`
        : `/marketing/contracts/${contractId}/rooms`;
    const method = roomId ? 'PUT' : 'POST';

    fetch(url, {
        method: method,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            room_id: masterRoomId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessDialog('Ruangan berhasil disimpan.').then(() => {
                bootstrap.Modal.getInstance(document.getElementById('roomModal')).hide();
                window.location.reload();
            });
        } else {
            throw new Error(data.message || 'Gagal menyimpan ruangan');
        }
    })
    .catch(error => {
        console.error('Error saving room:', error);
        showErrorDialog(error.message);
    });
}

function confirmDeleteRoom(contractRoomId) {
    showConfirmDialog({
        title: 'Hapus Ruangan?',
        text: 'Apakah Anda yakin ingin menghapus ruangan ini dari kontrak?',
        icon: 'warning',
        confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (!result.isConfirmed) return;

        fetch(`/marketing/contracts/rooms/${contractRoomId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSuccessDialog('Ruangan berhasil dihapus.').then(() => window.location.reload());
            } else {
                throw new Error(data.message || 'Gagal menghapus ruangan');
            }
        })
        .catch(error => {
            console.error('Error deleting room:', error);
            showErrorDialog(error.message);
        });
    });
}
</script>
@endpush

