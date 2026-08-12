@extends('layouts.app')

@section('title', 'Job Schedule Detail')

@section('content')
<!-- Flatpickr CSS (Required for calendar to work properly) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/airbnb.css">

<style>
    /* Fix for button click issues: ensure clicks on icons pass through to the button */
    .btn i, .btn-sm i {
        pointer-events: none;
    }
</style>

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
    
    .info-card {
        border: 1px solid #dee2e6 !important;
        border-radius: 8px !important;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
    }
    
    .row.g-3 {
        margin-left: -0.75rem !important;
        margin-right: -0.75rem !important;
    }
    
    .row.g-3 > * {
        padding-left: 0.75rem !important;
        padding-right: 0.75rem !important;
    }
    
    .info-field {
        margin-bottom: 1rem !important;
        display: flex !important;
        align-items: flex-start !important;
    }
    
    .info-field-label {
        flex: 0 0 40% !important;
        font-weight: bold !important;
        color: #495057 !important;
        padding-top: 0.25rem !important;
    }
    
    .info-field-value {
        flex: 0 0 60% !important;
        color: #6c757d !important;
        word-wrap: break-word !important;
        word-break: break-word !important;
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
    
    .status-badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
        display: inline-block;
    }
    
    /* Badge Colors */
    .badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
        display: inline-block;
    }
    
    .badge-info {
        background-color: #0dcaf0;
        color: #000;
    }
    
    .badge-warning {
        background-color: #ffc107;
        color: #000;
    }
    
    .badge-success {
        background-color: #198754;
        color: #fff;
    }
    
    .badge-secondary {
        background-color: #6c757d;
        color: #fff;
    }
    
    .status-badge.status-pending {
        background-color: #6c757d;
        color: #fff;
    }
    
    .status-badge.status-in-progress {
        background-color: #0dcaf0;
        color: #000;
    }
    
    .status-badge.status-completed {
        background-color: #198754;
        color: #fff;
    }
    
    .status-badge.status-cancelled {
        background-color: #dc3545;
        color: #fff;
    }

    /* STUDY CASE B1: Material Return Modal Styling (only for view modal) */
    #viewMaterialReturnModal {
        z-index: 1055;
    }

    #viewMaterialReturnModal .modal-content {
        border: none;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        border-radius: 8px;
    }

    #viewMaterialReturnModal .modal-header {
        border-bottom: 2px solid rgba(255, 255, 255, 0.2);
        border-radius: 8px 8px 0 0;
    }

    #viewMaterialReturnModal .modal-footer {
        border-top: 1px solid #dee2e6;
        border-radius: 0 0 8px 8px;
    }
    
    /* STUDY CASE B1: Material Return Form Section (Inline) */
    #materialReturnFormSection {
        margin-top: 20px;
    }
    
    #materialReturnFormSection .card-header {
        border-radius: 8px 8px 0 0;
    }

    .return-item-row {
        border: 1px solid #dee2e6;
        border-radius: 6px;
        margin-bottom: 12px;
        background-color: #fff;
    }

    .return-item-row .card-body {
        padding: 15px;
    }

    .return-item-row .form-label {
        font-size: 13px;
        font-weight: 500;
        color: #495057;
        margin-bottom: 5px;
    }

    .return-item-row .form-control,
    .return-item-row .form-select {
        font-size: 14px;
    }
    
    .status-scheduled,
    .status-new-job {
        background-color: #dbeafe;
        color: #1e40af;
    }

    .status-assign-team {
        background-color: #e0e7ff;
        color: #3730a3;
    }

    .status-assign-material {
        background-color: #fef3c7;
        color: #92400e;
    }

    .status-barang-dipersiapkan {
        background-color: #fed7aa;
        color: #9a3412;
    }

    .status-barang-diambil {
        background-color: #fde68a;
        color: #78350f;
    }

    .status-teknisi-tiba-dilokasi {
        background-color: #c7d2fe;
        color: #312e81;
    }

    .status-teknisi-sedang-pengerjaan {
        background-color: #fbbf24;
        color: #78350f;
    }

    .status-teknisi-selesai-pengerjaan {
        background-color: #86efac;
        color: #14532d;
    }

    .status-meninggalkan-lokasi {
        background-color: #fef3c7;
        color: #92400e;
    }

    .status-done-job,
    .status-completed {
        background-color: #d1fae5;
        color: #065f46;
    }
    
    .status-in-progress {
        background-color: #fef3c7;
        color: #92400e;
    }
    
    .status-cancelled {
        background-color: #fee2e2;
        color: #991b1b;
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
    
    /* Print Styles - Show all tabs and optimize for printing */
    @media print {
        /* Set landscape orientation for better table display */
        @page {
            size: A4 landscape;
            margin: 0.5cm;
        }
        
        /* Hide unnecessary elements including entire header section */
        .btn, button, .alert, .alert-dismissible {
            display: none !important;
        }
        
        #jobScheduleTabs, .nav-tabs {
            display: none !important;
        }
        
        /* Hide ALL header cards - the blue header with job number */
        .container-fluid > .row > .col-12 > .card.mb-3 {
            display: none !important;
        }
        
        /* Also hide by background color */
        .card[style*="background-color: #1e3a8a"] {
            display: none !important;
        }
        
        body {
            margin: 0;
            padding: 0;
        }
        
        /* Show all tab content */
        .tab-pane {
            display: block !important;
            page-break-inside: avoid;
            margin-bottom: 30px;
        }
        
        /* Add section headers before each tab */
        #basic-info::before {
            content: "BASIC INFORMATION";
            display: block;
            font-size: 18px;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 3px solid #1e3a8a;
            color: #1e3a8a;
        }
        
        #rental-team::before {
            content: "RENTAL & TEAM ASSIGNMENT";
            display: block;
            font-size: 18px;
            font-weight: bold;
            margin-top: 30px;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 3px solid #1e3a8a;
            color: #1e3a8a;
            page-break-before: always;
        }
        
        #material-issue::before {
            content: "MATERIAL ISSUE";
            display: block;
            font-size: 18px;
            font-weight: bold;
            margin-top: 30px;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 3px solid #1e3a8a;
            color: #1e3a8a;
            page-break-before: always;
        }
        
        #serial-numbers::before {
            content: "SERIAL NUMBERS";
            display: block;
            font-size: 18px;
            font-weight: bold;
            margin-top: 30px;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 3px solid #1e3a8a;
            color: #1e3a8a;
            page-break-before: always;
        }
        
        #team-location::before {
            content: "TEAM LOCATION";
            display: block;
            font-size: 18px;
            font-weight: bold;
            margin-top: 30px;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 3px solid #1e3a8a;
            color: #1e3a8a;
            page-break-before: always;
        }
        
        #photos::before {
            content: "PHOTOS";
            display: block;
            font-size: 18px;
            font-weight: bold;
            margin-top: 30px;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 3px solid #1e3a8a;
            color: #1e3a8a;
            page-break-before: always;
        }
        
        /* Adjust card styling for print */
        .card {
            box-shadow: none !important;
            border: 1px solid #000 !important;
            margin-bottom: 15px !important;
            page-break-inside: avoid;
        }
        
        .card-header {
            background-color: #f0f0f0 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            border-bottom: 2px solid #000 !important;
            padding: 10px 15px !important;
        }
        
        .card-body {
            padding: 15px !important;
        }
        
        /* Table adjustments for better printing in landscape - ultra compact */
        table {
            font-size: 7px !important;
            width: 100% !important;
            table-layout: fixed !important;
        }
        
        table th {
            background-color: #e0e0e0 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            padding: 4px 2px !important;
            font-size: 7px !important;
            word-break: break-word !important;
        }
        
        table td {
            padding: 4px 2px !important;
            font-size: 7px !important;
            word-break: break-word !important;
            overflow-wrap: break-word !important;
        }
        
        /* Status badges - preserve colors */
        .status-badge, .badge {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            padding: 3px 6px !important;
            font-size: 8px !important;
        }
        
        /* Info fields */
        .info-field {
            margin-bottom: 8px !important;
            font-size: 11px !important;
        }
        
        .info-field-label {
            font-size: 11px !important;
            font-weight: bold !important;
        }
        
        .info-field-value {
            font-size: 11px !important;
        }
        
        /* Images - adjust size for print */
        img {
            max-width: 200px !important;
            max-height: 200px !important;
            page-break-inside: avoid;
        }
        
        /* Hide form controls and interactive elements */
        input, select, textarea, .form-control, .form-select {
            border: none !important;
            background: transparent !important;
            padding: 0 !important;
        }
        
        /* Ensure proper spacing */
        .row {
            margin-bottom: 10px !important;
        }
        
        /* Header styling */
        .card-title {
            font-size: 14px !important;
            font-weight: bold !important;
        }
        
        /* Container adjustments */
        .container-fluid {
            padding: 0 !important;
        }
        
        /* Remove unnecessary spacing */
        .mb-3, .mb-4 {
            margin-bottom: 10px !important;
        }
        
        /* Table responsive wrapper */
        .table-responsive {
            overflow: visible !important;
        }
        
        /* Prevent page breaks inside important elements */
        tr, .info-card {
            page-break-inside: avoid;
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
                            <a href="{{ route('operational.job-schedules.index', ['view_mode' => $viewMode ?? 'job']) }}" class="btn btn-light btn-sm">
                                <i class="fas fa-arrow-left"></i> Back to List
                            </a>
                        </div>
                        <div>
                            <h3 class="card-title mb-0" style="color: white; font-size: 1.5rem; font-weight: bold;">
                                {{ $jobSchedule->job_number ?? '-' }}
                            </h3>
                        </div>
                        <div>
                            @if($jobSchedule->status === 'done_job' || $jobSchedule->status === 'completed')
                            <a href="{{ route('operational.job-schedules.print-csr') }}?ids={{ $jobSchedule->id }}" target="_blank" rel="noopener noreferrer" class="btn btn-light btn-sm me-2">
                                <i class="fas fa-print"></i> Print
                            </a>
                            @endif
                            @php
                                $anyPendingRoom = $relatedJobScheduleRooms->where('status', '!=', 'completed')->isNotEmpty();
                                $doneAllowedStatuses = ['in_progress', 'teknisi_sedang_pengerjaan', 'teknisi_selesai_pengerjaan'];
                                $canDoneFromStatus = in_array($jobSchedule->status, $doneAllowedStatuses, true);
                                // Pending rooms no longer disable the button outright: the BA modal
                                // now has a "Tidak dapat menyelesaikan semua ruangan" checkbox that
                                // routes incomplete rooms into a follow-up (outstanding) job instead.
                                $doneButtonDisabled = !$canDoneFromStatus;
                                $doneButtonTitle = $anyPendingRoom
                                    ? 'Ada room belum selesai — centang "Tidak dapat menyelesaikan semua ruangan" di form jika ingin lanjut'
                                    : ($canDoneFromStatus ? 'Selesaikan pekerjaan' : 'Done Job hanya bisa setelah On Progress Teknisi');
                            @endphp
                            @php
                                $currentUser = auth()->user();
                                $canUseWebFallbackActions = $currentUser && (
                                    $currentUser->hasPermission('operational.job-schedules-complete-ba.update') ||
                                    $currentUser->hasPermission('operational.job-schedules.update') ||
                                    $currentUser->hasRole('Admin') ||
                                    $currentUser->hasRole('super_admin') ||
                                    $currentUser->hasRoleStartingWith('Management')
                                );
                                $arrivedAllowed = $jobSchedule->job_number && in_array($jobSchedule->status, ['barang_diambil','barang_dipersiapkan','assign_material','assign_team','scheduled','new_job','meninggalkan_lokasi'], true);
                                $startAllowed = in_array($jobSchedule->status, ['teknisi_tiba_dilokasi','barang_diambil'], true);
                                $leaveAllowed = in_array($jobSchedule->status, ['teknisi_tiba_dilokasi','in_progress','teknisi_sedang_pengerjaan','teknisi_selesai_pengerjaan'], true);
                                // Phase 3 material lifecycle (only for non-remove jobs that use materials)
                                $isMaterialJob = !in_array(strtolower($jobSchedule->type), ['remove','remove_free','remove free'], true);
                                $confirmMaterialAllowed = $isMaterialJob && $jobSchedule->status === 'barang_siap_diambil';
                                $verifyMaterialAllowed = $isMaterialJob && in_array($jobSchedule->status, ['barang_dipersiapkan','barang_siap_diambil','assign_material'], true);
                                $photoUploadAllowed = $canDoneFromStatus && $anyPendingRoom;
                            @endphp
                            {{-- Phase 3 web fallback: material confirm / verify --}}
                            @if($canUseWebFallbackActions && $confirmMaterialAllowed)
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="webMaterialAction('confirm-materials','Konfirmasi material yang akan diambil teknisi?')" title="Konfirmasi Material dari web">
                                <i class="fas fa-clipboard-check"></i> Lihat Barang & Konfirmasi
                            </button>
                            @endif
                            @if($canUseWebFallbackActions && $verifyMaterialAllowed)
                            <button type="button" class="btn btn-outline-dark btn-sm" onclick="webMaterialAction('verify-materials','Verifikasi pengambilan barang (Barang Diambil)?')" title="Verifikasi Ambil Barang dari web">
                                <i class="fas fa-box-open"></i> Lihat Barang & Ambil
                            </button>
                            @endif
                            {{-- Phase 2 web fallback: technician location lifecycle --}}
                            @if($canUseWebFallbackActions && $arrivedAllowed)
                            <button type="button" class="btn btn-info btn-sm" onclick="webLifecycleAction('arrived','Catat kedatangan teknisi (Tiba di Lokasi)?')" title="Catat Tiba di Lokasi dari web">
                                <i class="fas fa-map-marker-alt"></i> Tiba di Lokasi
                            </button>
                            @endif
                            @if($canUseWebFallbackActions && $startAllowed)
                            <button type="button" class="btn btn-primary btn-sm" onclick="webLifecycleAction('start-work','Mulai pekerjaan (On Progress Teknisi)?')" title="Mulai Kerja dari web">
                                <i class="fas fa-play"></i> Mulai Kerja
                            </button>
                            @endif
                            @if($canUseWebFallbackActions && $leaveAllowed)
                            <button type="button" class="btn btn-warning btn-sm" onclick="webLifecycleAction('leave-location','Tandai teknisi meninggalkan lokasi sementara?')" title="Tinggalkan Lokasi dari web">
                                <i class="fas fa-sign-out-alt"></i> Tinggalkan Lokasi
                            </button>
                            @endif
                            @if($canUseWebFallbackActions && $photoUploadAllowed)
                            <button type="button" class="btn btn-outline-light btn-sm" onclick="scrollToRoomCompletion()" title="Upload foto pengerjaan per ruangan">
                                <i class="fas fa-camera"></i> Upload Foto
                            </button>
                            @endif
                            @if($canUseWebFallbackActions && !in_array($jobSchedule->status, ['completed', 'done_job', 'cancelled', 'suspend', 'dpf']))
                            <button type="button" id="headerDoneButton" class="btn btn-success btn-sm" data-can-done-from-status="{{ $canDoneFromStatus ? '1' : '0' }}" {{ $doneButtonDisabled ? 'disabled' : '' }}
                                style="{{ $doneButtonDisabled ? 'background-color: #6c757d; border-color: #6c757d; cursor: not-allowed; opacity: 1;' : '' }}"
                                title="{{ $doneButtonTitle }}" onclick="openDoneJobBaModal()">
                                <i class="fas fa-file-signature"></i> Konfirmasi Pekerjaan
                            </button>
                            @endif
                            @if($jobSchedule->status === 'done_job' || $jobSchedule->status === 'completed')
                            <form method="POST" action="{{ route('operational.job-schedules.undone', $jobSchedule->id) }}" style="display: inline;" onsubmit="return confirmUndoneJob(event);">
                                @csrf
                                <button type="submit" class="btn btn-warning btn-sm" {{ $hasActiveInvoice ? 'disabled' : '' }} title="{{ $hasActiveInvoice ? 'Maaf, Job ini sudah memiliki Invoice yang aktif. Silakan hapus atau batalkan invoice tersebut terlebih dahulu di menu Finance.' : '' }}">
                                    <i class="fas fa-undo"></i> Undone Job & Cancel BA
                                </button>
                            </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Context Alerts -->
            @if(($viewMode ?? 'job') === 'room' && ($filterRoomId ?? null))
            <div class="alert alert-info d-flex align-items-center mb-3" role="alert">
                <i class="fas fa-filter me-2"></i>
                <div>
                    <strong>Room View Mode Active:</strong> Showing details only for room 
                    <span class="fw-bold">{{ $relatedJobScheduleRooms->first()->room_name ?? 'Selected Room' }}</span>.
                </div>
                <a href="{{ route('operational.job-schedules.show', $jobSchedule->id) }}" class="btn btn-sm btn-outline-primary ms-auto" style="border-color: #0dcaf0; color: #055160;">
                    Show All Rooms
                </a>
            </div>
            @elseif($filterBuildingId ?? null)
            <div class="alert alert-info d-flex align-items-center mb-3" role="alert">
                <i class="fas fa-building me-2"></i>
                <div>
                    <strong>Building Filter Active:</strong> Showing rooms for building 
                    <span class="fw-bold">{{ $relatedJobScheduleRooms->first()->jobAdviceRoom->contractRoom->room->building->building_name ?? 'Selected Building' }}</span>.
                </div>
                <a href="{{ route('operational.job-schedules.show', $jobSchedule->id) }}" class="btn btn-sm btn-outline-primary ms-auto" style="border-color: #0dcaf0; color: #055160;">
                    Show All Rooms
                </a>
            </div>
            @endif

            <!-- Success Message -->
            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert" style="margin: 15px;">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif
            
            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert" style="margin: 15px;">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            <!-- Navigation Tabs - HORIZONTAL LAYOUT -->
            <div class="card mb-3">
                <div class="card-body p-0">
                    <ul class="nav nav-tabs" id="jobScheduleTabs" role="tablist">
                        <li class="nav-item" role="presentation" style="flex: 1;">
                            <button class="nav-link active" id="basic-info-tab" data-bs-toggle="tab" data-bs-target="#basic-info" type="button" role="tab" aria-controls="basic-info" aria-selected="true">
                                <i class="fas fa-info-circle me-2"></i>BASIC INFO
                            </button>
                        </li>
                        <li class="nav-item" role="presentation" style="flex: 1;">
                            <button class="nav-link" id="rental-team-tab" data-bs-toggle="tab" data-bs-target="#rental-team" type="button" role="tab" aria-controls="rental-team" aria-selected="false">
                                <i class="fas fa-building me-2"></i>RENTAL & TEAM
                            </button>
                        </li>
                        @if(!in_array(strtolower($jobSchedule->type), ['check', 'remove', 'remove_free', 'remove free']))
                        <li class="nav-item" role="presentation" style="flex: 1;">
                            <button class="nav-link" id="material-issue-tab" data-bs-toggle="tab" data-bs-target="#material-issue" type="button" role="tab" aria-controls="material-issue" aria-selected="false">
                                <i class="fas fa-box me-2"></i>MATERIAL ISSUE
                            </button>
                        </li>
                        @endif
                        <li class="nav-item" role="presentation" style="flex: 1;">
                            <button class="nav-link" id="serial-numbers-tab" data-bs-toggle="tab" data-bs-target="#serial-numbers" type="button" role="tab" aria-controls="serial-numbers" aria-selected="false">
                                <i class="fas fa-barcode me-2"></i>SERIAL NUMBERS
                            </button>
                        </li>
                        <li class="nav-item" role="presentation" style="flex: 1;">
                            <button class="nav-link" id="team-location-tab" data-bs-toggle="tab" data-bs-target="#team-location" type="button" role="tab" aria-controls="team-location" aria-selected="false">
                                <i class="fas fa-map-marker-alt me-2"></i>TEAM LOCATION
                            </button>
                        </li>
                        <li class="nav-item" role="presentation" style="flex: 1;">
                            <button class="nav-link" id="photos-tab" data-bs-toggle="tab" data-bs-target="#photos" type="button" role="tab" aria-controls="photos" aria-selected="false">
                                <i class="fas fa-camera me-2"></i>PHOTOS
                            </button>
                        </li>
                        <li class="nav-item" role="presentation" style="flex: 1;">
                            <button class="nav-link" id="mobile-sync-tab" data-bs-toggle="tab" data-bs-target="#mobile-sync" type="button" role="tab" aria-controls="mobile-sync" aria-selected="false">
                                <i class="fas fa-mobile-alt me-2"></i>MOBILE SYNC
                            </button>
                        </li>
                        <li class="nav-item" role="presentation" style="flex: 1;">
                            <button class="nav-link" id="ba-files-tab" data-bs-toggle="tab" data-bs-target="#ba-files" type="button" role="tab" aria-controls="ba-files" aria-selected="false">
                                <i class="fas fa-file-alt me-2"></i>BA FILES
                            </button>
                        </li>
                        <li class="nav-item" role="presentation" style="flex: 1;">
                            <button class="nav-link" id="device-details-tab" data-bs-toggle="tab" data-bs-target="#device-details" type="button" role="tab" aria-controls="device-details" aria-selected="false">
                                <i class="fas fa-microchip me-2"></i>DEVICE DETAILS
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Tab Content -->
            <div class="tab-content" id="jobScheduleTabsContent">
                <!-- Basic Info Tab -->
                <div class="tab-pane fade show active" id="basic-info" role="tabpanel" aria-labelledby="basic-info-tab">
                    <div class="row g-3">
                        <div class="col-12 col-lg-6">
                            <div class="card info-card h-100">
                                <div class="card-header" style="background-color: #6c757d; color: white; border-radius: 8px 8px 0 0;">
                                    <h5 class="card-title mb-0">Job Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="info-field">
                                        <div class="info-field-label">Job Type</div>
                                        <div class="info-field-value">{{ $jobSchedule->display_type ?? ucfirst($jobSchedule->type ?? '-') }}</div>
                                    </div>
                                    <div class="info-field">
                                        <div class="info-field-label">Reference No</div>
                                        <div class="info-field-value">{{ $jobSchedule->jobAdvice?->job_advice_number ?? $jobSchedule->jobAdvice?->reference_number ?? $jobSchedule->reference_number ?? '-' }}</div>
                                    </div>
                                    <div class="info-field">
                                        <div class="info-field-label">Status</div>
                                        <div class="info-field-value">
                                            <span class="status-badge status-{{ str_replace('_', '-', $jobSchedule->status) }}">
                                                {{ $jobSchedule->status_text }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="info-field">
                                        <div class="info-field-label">Quotation No</div>
                                        <div class="info-field-value">
                                            @php $jobScheduleQuotation = $jobSchedule->jobAdvice?->contract?->quotation ?? ($jobScheduleQuotationFallback ?? null); @endphp
                                            @if($jobScheduleQuotation)
                                                <a href="{{ route('marketing.quotations.show', $jobScheduleQuotation) }}" target="_blank" rel="noopener noreferrer">{{ $jobSchedule->quotation_number ?? $jobScheduleQuotation->quotation_number ?? '-' }}</a>
                                            @else
                                                {{ $jobSchedule->quotation_number ?? '-' }}
                                            @endif
                                        </div>
                                    </div>
                                    <div class="info-field">
                                        <div class="info-field-label">Contract No</div>
                                        <div class="info-field-value">
                                            @if($jobSchedule->jobAdvice?->contract)
                                                <a href="{{ route('marketing.contracts.show', $jobSchedule->jobAdvice->contract) }}" target="_blank" rel="noopener noreferrer">{{ $jobSchedule->contract_number ?? $jobSchedule->jobAdvice->contract->contract_number ?? '-' }}</a>
                                            @else
                                                {{ $jobSchedule->contract_number ?? '-' }}
                                            @endif
                                        </div>
                                    </div>
                                    <div class="info-field">
                                        <div class="info-field-label">Customer</div>
                                        <div class="info-field-value">
                                            @if($jobSchedule->jobAdvice?->customer)
                                                <a href="{{ route('company.customers.show', $jobSchedule->jobAdvice->customer) }}" target="_blank" rel="noopener noreferrer">{{ $jobSchedule->jobAdvice->customer->name }}</a>
                                            @else
                                                {{ $jobSchedule->company_name ?? '-' }}
                                            @endif
                                        </div>
                                    </div>
                                    <div class="info-field">
                                        <div class="info-field-label">P.Service</div>
                                        <div class="info-field-value">{{ $jobSchedule->period ?? '-' }}</div>
                                    </div>
                                    <div class="info-field">
                                        <div class="info-field-label">P.Invoice</div>
                                        <div class="info-field-value">{{ $jobSchedule->invoice_period ?? '-' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="card info-card h-100">
                                <div class="card-header" style="background-color: #6c757d; color: white; border-radius: 8px 8px 0 0;">
                                    <h5 class="card-title mb-0">Schedule Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="info-field">
                                        <div class="info-field-label">Gedung</div>
                                        <div class="info-field-value">
                                            @if($jobSchedule->building)
                                                <a href="{{ route('operational.buildings.show', $jobSchedule->building) }}" target="_blank" rel="noopener noreferrer">{{ $jobSchedule->building->building_name ?? '-' }}</a>
                                            @else
                                                -
                                            @endif
                                        </div>
                                    </div>
                                    <div class="info-field">
                                        <div class="info-field-label">Schedule Date</div>
                                        <div class="info-field-value">
                                @php
                                    // MOM15: Allow schedule_date change as long as status is not done_job/completed/undone
                                    // Setelah Unpost BA (status=undone), schedule_date tidak boleh diedit. Hanya BA date yg bisa diedit.
                                    $finalStatuses = ['done_job', 'completed', 'suspend', 'dpf', 'cancelled', 'undone'];
                                    $canEditScheduleDate = !in_array($jobSchedule->status, $finalStatuses);
                                @endphp
                                            
                                            @if($canEditScheduleDate)
                                                <div class="inline-edit-wrapper" id="scheduleDateWrapper">
                                                    <span id="scheduleDateDisplay" class="cursor-pointer text-primary" onclick="editScheduleDate()" style="text-decoration: underline;">
                                                        {{ $jobSchedule->schedule_date?->format('d/M/Y') ?? '-' }}
                                                        <i class="fas fa-edit ms-1" style="font-size: 0.8rem;"></i>
                                                    </span>
                                                    <input type="text" id="scheduleDateInput" class="form-control form-control-sm" 
                                                        style="display: none;"
                                                        value="{{ $jobSchedule->schedule_date?->format('Y-m-d') ?? '' }}"
                                                        placeholder="Pilih tanggal...">
                                                </div>
                                            @else
                                                <span class="text-muted">
                                                    {{ $jobSchedule->schedule_date?->format('d/M/Y') ?? '-' }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="info-field">
                                        <div class="info-field-label">Expected Date</div>
                                        <div class="info-field-value">{{ $jobSchedule->expected_date?->format('d/M/Y') ?? '-' }}</div>
                                    </div>
                                    {{-- MOM15: BA Date field with inline edit capability --}}
                                    {{-- MOM: BA Date ditampilkan saat done_job, completed, MAUPUN undone (setelah Unpost BA, field tetap ada meski kosong) --}}
                                    @if(in_array($jobSchedule->status, ['done_job', 'completed', 'undone']))
                                    <div class="info-field">
                                        <div class="info-field-label">BA Date</div>
                                        <div class="info-field-value">
                                            @php
                                                // Check if user has ba-date edit permission
                                                $user = Auth::user();
                                                $canEditBaDate = $user && (
                                                    $user->hasPermission('operational.job-schedules.ba-date.update') ||
                                                    $user->hasPermission('operational.job-schedules.ba-date')
                                                ) && !($hasActiveInvoice ?? false);
                                            @endphp
                                            
                                            @if($canEditBaDate)
                                                <div class="inline-edit-wrapper" id="baDateWrapper">
                                                    <span id="baDateDisplay" class="cursor-pointer text-primary" onclick="editBaDate()" style="text-decoration: underline;">
                                                        {{ $jobSchedule->ba_date?->format('d/M/Y') ?? 'Set BA Date' }}
                                                        <i class="fas fa-edit ms-1" style="font-size: 0.8rem;"></i>
                                                    </span>
                                                    <input type="text" id="baDateInput" class="form-control form-control-sm" 
                                                        style="display: none;"
                                                        value="{{ $jobSchedule->ba_date?->format('Y-m-d') ?? '' }}"
                                                        placeholder="Pilih tanggal...">
                                                </div>
                                                @if($hasActiveInvoice ?? false)
                                                <small class="text-muted d-block mt-1">
                                                    <i class="fas fa-info-circle"></i> Tidak bisa edit karena invoice sudah ada
                                                </small>
                                                @endif
                                            @else
                                                <span class="text-muted">
                                                    {{ $jobSchedule->ba_date?->format('d/M/Y') ?? '-' }}
                                                </span>
                                                @if($hasActiveInvoice ?? false)
                                                <small class="text-muted d-block mt-1">
                                                    <i class="fas fa-info-circle"></i> Tidak bisa edit karena invoice aktif sudah ada
                                                </small>
                                                @else
                                                <small class="text-muted d-block mt-1">
                                                    <i class="fas fa-info-circle"></i> Tidak bisa edit karena permission BA Date belum diberikan
                                                </small>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                    {{-- Hidden per request (2026-06-28): QA kept asking where this number's
                                    source document was (not found in SQ/CA/JA/JS/Invoice/Inventory) since it's
                                    only an internally-generated reference shown once in the "Konfirmasi
                                    Pekerjaan" success popup. Hiding from this permanent display until a final
                                    decision is made on whether/how to surface it. Do not delete - data and
                                    bug #11/#30 fix are still intact, only this display is hidden. --}}
                                    {{-- @if($jobSchedule->ba_number)
                                    <div class="info-field">
                                        <div class="info-field-label">No. BA</div>
                                        <div class="info-field-value">{{ $jobSchedule->ba_number }}</div>
                                    </div>
                                    @endif --}}
                                    @endif
                                    <div class="info-field" style="align-items: flex-start;">
                                        <div class="info-field-label" style="margin-top: 0.25rem;">Catatan Internal</div>
                                        <div class="info-field-value" style="flex: 1;">
                                            <div id="internalNotesWrapper" class="inline-edit-wrapper">
                                                <div id="internalNotesDisplay" class="cursor-pointer" onclick="editInternalNotes()" 
                                                    style="word-wrap: break-word; word-break: break-word; white-space: normal; max-width: 100%; min-height: 20px; border-bottom: 1px dashed #007bff;">
                                                    {{ $jobSchedule->internal_notes ?? 'Klik untuk tambah catatan...' }}
                                                </div>
                                                <textarea id="internalNotesInput" class="form-control" rows="3" 
                                                    style="display: none;"
                                                    onblur="saveInternalNotesInline()">{{ $jobSchedule->internal_notes ?? '' }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    @php
                                        // [FIX Masalah 1 & 3] Loop semua sibling room untuk tampilkan PIC/TTD per room
                                        // $allJobReportsPerJS di-index by job_schedule_id dari controller
                                        $reportsToDisplay = collect();
                                        if (isset($relatedJobScheduleRooms) && $relatedJobScheduleRooms->count() > 0) {
                                            foreach ($relatedJobScheduleRooms as $jsr) {
                                                $rpt = isset($allJobReportsPerJS) ? ($allJobReportsPerJS[$jsr->job_schedule_id] ?? null) : null;
                                                if ($rpt && ($rpt->pic_name || $rpt->notes || $rpt->photo_pic || $rpt->signature_file)) {
                                                    $reportsToDisplay->push([
                                                        'report' => $rpt,
                                                        'room_name' => $jsr->room_name,
                                                    ]);
                                                }
                                            }
                                        } else {
                                            // Fallback: single-room job
                                            $rpt = isset($allJobReportsPerJS)
                                                ? ($allJobReportsPerJS[$jobSchedule->id] ?? null)
                                                : $jobSchedule->jobReports->first();
                                            if ($rpt && ($rpt->pic_name || $rpt->notes || $rpt->photo_pic || $rpt->signature_file)) {
                                                $reportsToDisplay->push([
                                                    'report' => $rpt,
                                                    'room_name' => $jobSchedule->room_name,
                                                ]);
                                            }
                                        }
                                    @endphp
                                    
                                    @foreach($reportsToDisplay as $reportItem)
                                    @php $jobReport = $reportItem['report']; $reportRoomLabel = $reportItem['room_name']; @endphp
                                    <div class="info-field" style="align-items: flex-start; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #dee2e6;">
                                        <div class="info-field-label" style="margin-top: 0.25rem; color: #1e3a8a; font-weight: 600;">
                                            <i class="fas fa-check-circle me-2"></i>Verifikasi Pekerjaan
                                            @if($reportsToDisplay->count() > 1 && $reportRoomLabel)
                                                <span class="badge bg-secondary ms-1" style="font-size: 0.75rem;">{{ $reportRoomLabel }}</span>
                                            @endif
                                        </div>
                                        <div class="info-field-value" style="flex: 1;">
                                            @if($jobReport->notes)
                                            <div style="margin-bottom: 1rem;">
                                                <div style="font-weight: 600; margin-bottom: 0.25rem; color: #495057;">Catatan Verifikasi:</div>
                                                <div style="word-wrap: break-word; word-break: break-word; white-space: normal; padding: 0.5rem; background-color: #f8f9fa; border-radius: 4px;">
                                                    {{ $jobReport->notes }}
                                                </div>
                                            </div>
                                            @endif
                                            
                                            @if($jobReport->pic_name)
                                            <div style="margin-bottom: 1rem;">
                                                <div style="font-weight: 600; margin-bottom: 0.25rem; color: #495057;">Nama PIC Lapangan:</div>
                                                <div style="padding: 0.5rem; background-color: #f8f9fa; border-radius: 4px;">
                                                    {{ $jobReport->pic_name }}
                                                </div>
                                            </div>
                                            @endif
                                            
                                            @if($jobReport->photo_pic)
                                            <div style="margin-bottom: 1rem;">
                                                <div style="font-weight: 600; margin-bottom: 0.25rem; color: #495057;">Foto PIC:</div>
                                                <div>
                                                    @php
                                                        $picPhotoPath = $jobReport->photo_pic;
                                                        if (strpos($picPhotoPath, 'job-verifications/') === 0) {
                                                            $picPhotoPath = 'uploads/' . $picPhotoPath;
                                                        } elseif (strpos($picPhotoPath, 'uploads/') !== 0) {
                                                            $picPhotoPath = 'uploads/' . $picPhotoPath;
                                                        }
                                                    @endphp
                                                    <img src="{{ asset($picPhotoPath) }}" alt="Foto PIC" style="max-width: 300px; max-height: 300px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); cursor: pointer;" onclick="window.open('{{ asset($picPhotoPath) }}', '_blank')">
                                                </div>
                                            </div>
                                            @endif
                                            
                                            @if($jobReport->signature_file)
                                            <div style="margin-bottom: 1rem;">
                                                <div style="font-weight: 600; margin-bottom: 0.25rem; color: #495057;">Tanda Tangan Digital:</div>
                                                <div>
                                                    @php
                                                        $signaturePath = $jobReport->signature_file;
                                                        if (strpos($signaturePath, 'job-verifications/') === 0) {
                                                            $signaturePath = 'uploads/' . $signaturePath;
                                                        } elseif (strpos($signaturePath, 'uploads/') !== 0) {
                                                            $signaturePath = 'uploads/' . $signaturePath;
                                                        }
                                                    @endphp
                                                    <img src="{{ asset($signaturePath) }}" alt="Tanda Tangan" style="max-width: 300px; max-height: 150px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); cursor: pointer; background-color: white; padding: 10px;" onclick="window.open('{{ asset($signaturePath) }}', '_blank')">
                                                </div>
                                            </div>
                                            @endif
                                            
                                            @if($jobReport->completed_at)
                                            <div style="margin-top: 0.5rem; font-size: 0.875rem; color: #6c757d;">
                                                <i class="fas fa-clock me-1"></i>Diverifikasi pada: {{ $jobReport->completed_at->format('d/M/Y H:i') }}
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                    @endforeach

                                    
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rental & Team Tab -->
                <div class="tab-pane fade" id="rental-team" role="tabpanel" aria-labelledby="rental-team-tab">
                    <div class="card" style="width: 100%; min-height: 500px;">
                        <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #1e3a8a;">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0" style="color: #1e3a8a;">
                                    <i class="fas fa-building me-2"></i>Rental & Team
                                </h5>
                                <!-- <div>
                                    <label class="text-sm font-medium text-gray-700 me-2">Filter Team Code:</label>
                                    <select id="filterTeamCodeRental" class="form-control form-control-sm d-inline-block" style="width: auto;" onchange="filterRentalTeam()">
                                        <option value="">Semua Team</option>
                                        @foreach($jobSchedule->jobAssignSchedules as $assignSchedule)
                                        <option value="{{ $assignSchedule->team?->team_code ?? '' }}">{{ $assignSchedule->team?->team_code ?? '-' }}</option>
                                        @endforeach
                                    </select>
                                </div> -->
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="overflow-x: auto; max-width: 100%;">
                                <table class="table table-bordered table-striped" style="min-width: 1600px; white-space: nowrap;">
                                    <thead>
                                        <tr>
                                            <th>Job No</th>
                                            <th>Lantai</th>
                                            <th>Nama Ruangan</th>
                                            <th>Remark Ruangan</th>
                                            <th>Status Room</th>
                                            <th>Team Assignment</th>
                                            <th>User Team</th>
                                            <th>Start Job (Loc & Time)</th>
                                            <th>Finish Job (Loc & Time)</th>
                                            <th>Material Return</th>
                                            <th>Rental</th>
                                            <th>Rental Remark</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($relatedJobScheduleRooms ?? [] as $jobScheduleRoom)
                                        @php
                                            // Get room data from jobAdviceRoom
                                            $jaRoom = $jobScheduleRoom->jobAdviceRoom;
                                            $roomData = $jaRoom?->contractRoom?->room
                                                ?? $jaRoom?->quotationRoom?->room
                                                ?? $jobScheduleRoom->room
                                                ?? null;
                                            
                                            // Get related job schedule (might be different from current one)
                                            $roomJobSchedule = $jobScheduleRoom->jobSchedule;
                                            
                                            // Get room assignment (custom or global)
                                            $roomAssignment = $jobScheduleRoom->roomAssignment;
                                            $effectiveTeam = null;
                                            $isCustom = false;
                                            
                                            if ($roomAssignment) {
                                                $isCustom = $roomAssignment->is_custom;
                                                // Get effective team: custom team or fallback to global
                                                if ($roomAssignment->team_id) {
                                                    $effectiveTeam = $roomAssignment->team;
                                                } elseif ($roomAssignment->jobAssignSchedule && $roomAssignment->jobAssignSchedule->team) {
                                                    $effectiveTeam = $roomAssignment->jobAssignSchedule->team;
                                                }
                                            } elseif ($roomJobSchedule && $roomJobSchedule->jobAssignSchedules->where('status', '!=', 'cancelled')->isNotEmpty()) {
                                                // Fallback to global assignment of its own job schedule
                                                $effectiveTeam = $roomJobSchedule->jobAssignSchedules->where('status', '!=', 'cancelled')->sortByDesc('id')->first()->team;
                                            }
                                            
                                            // Check if editable (only if its own job schedule status = assign_team)
                                            $isRoomEditable = $roomJobSchedule && $roomJobSchedule->status === 'assign_team';
                                            $roomDoneAllowedStatuses = ['in_progress', 'teknisi_sedang_pengerjaan', 'teknisi_selesai_pengerjaan'];
                                            $canCompleteRoomFromWeb = $roomJobSchedule && in_array($roomJobSchedule->status, $roomDoneAllowedStatuses, true);
                                            $displayRentalName = $jaRoom?->rentalProduct?->rental_name
                                                ?? $jaRoom?->rental_name
                                                ?? null;
                                            if (($displayRentalName === '-' || blank($displayRentalName)) && filled($jobScheduleRoom->fallback_rental_name ?? null)) {
                                                $displayRentalName = $jobScheduleRoom->fallback_rental_name;
                                            }
                                            if (($displayRentalName === '-' || blank($displayRentalName))) {
                                                $displayRentalName = $jobScheduleRoom->display_rental_name;
                                            }
                                        @endphp
                                        <tr data-room-id="{{ $jobScheduleRoom->id }}">
                                            <td>
                                                @if($roomJobSchedule)
                                                    <a href="{{ route('operational.job-schedules.show', $roomJobSchedule->id) }}" class="{{ $roomJobSchedule->id == $jobSchedule->id ? 'fw-bold text-dark' : 'text-primary' }}" target="_blank" rel="noopener noreferrer">
                                                        {{ $roomJobSchedule->job_number ?? '-' }}
                                                    </a>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>{{ $roomData?->room_floor ?? '-' }}</td>
                                            <td>{{ $jobScheduleRoom->room_name ?? '-' }}</td>
                                            <td>{{ $roomData?->room_remark ?? '-' }}</td>
                                             <td class="room-status-cell">
                                                <span class="status-badge status-{{ str_replace('_', '-', $jobScheduleRoom->status) }}">
                                                    {{ ucfirst(str_replace('_', ' ', $jobScheduleRoom->status)) }}
                                                </span>
                                                @if($jobScheduleRoom->status !== 'completed' && $jobSchedule->id == ($roomJobSchedule->id ?? null) && $canCompleteRoomFromWeb)
                                                    <button type="button" class="btn btn-xs badge badge-success ms-1 btn-complete-room border-0" 
                                                            onclick="completeRoomManual({{ $jobScheduleRoom->id }})"
                                                            style="cursor: pointer; font-size: 0.75rem; font-weight: 500; vertical-align: middle;"
                                                            title="Upload foto before/after lalu selesaikan ruangan ini">
                                                        <i class="fas fa-camera"></i> Upload Foto & Done
                                                    </button>
                                                @elseif($jobScheduleRoom->status !== 'completed' && $jobSchedule->id == ($roomJobSchedule->id ?? null))
                                                    <span class="badge badge-secondary ms-1" title="Room baru bisa di-Done setelah job masuk On Progress Teknisi">
                                                        Done locked
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="text-muted">{{ $effectiveTeam?->team_name ?? 'No Team Assigned' }}</span>
                                                    <span class="badge badge-{{ $isCustom ? 'warning' : 'info' }}">
                                                        {{ $isCustom ? 'Custom' : 'Global' }}
                                                    </span>
                                                </div>
                                            </td>
                                            <td>
                                                @if($jobScheduleRoom->status === 'completed' && $jobScheduleRoom->completedBy)
                                                    <div>{{ $jobScheduleRoom->completedBy->name ?? '-' }}</div>
                                                    <div style="font-size: 0.85rem; color: #6c757d;">
                                                        {{ $jobScheduleRoom->completed_at?->format('d/M/Y H:i') }}
                                                    </div>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $startAt = $roomJobSchedule && $roomJobSchedule->started_at ? \Carbon\Carbon::parse($roomJobSchedule->started_at) : null;
                                                    $startLat = $roomJobSchedule?->latitude;
                                                    $startLng = $roomJobSchedule?->longitude;

                                                    // MOM: Backup location from Team Location History if missing
                                                    if ($roomJobSchedule && (!$startLat || !$startLng)) {
                                                        $locStart = \App\Models\JobTeamLocation::where('job_schedule_id', $roomJobSchedule->id)
                                                            ->where(function($q) {
                                                                $q->where('action', 'arrived')
                                                                  ->orWhere('action', 'like', '%start%');
                                                            })
                                                            ->orderBy('recorded_at', 'asc')
                                                            ->first();
                                                        if ($locStart && $locStart->latitude && $locStart->longitude) {
                                                            $startLat = $locStart->latitude;
                                                            $startLng = $locStart->longitude;
                                                        }
                                                    }
                                                @endphp
                                                @if($startAt)
                                                    <div style="font-size: 0.85rem;">{{ $startAt->format('d/M/Y H:i') }}</div>
                                                    @if($startLat && $startLng)
                                                        <a href="https://www.google.com/maps?q={{ $startLat }},{{ $startLng }}" target="_blank" rel="noopener noreferrer" class="badge badge-info mt-1" style="text-decoration: none;">
                                                            <i class="fas fa-map-marker-alt"></i> View Map
                                                        </a>
                                                    @endif
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $finishTime = $jobScheduleRoom->completed_at ?? $roomJobSchedule?->completed_at;
                                                    if ($finishTime) $finishTime = \Carbon\Carbon::parse($finishTime);
                                                    
                                                    $finishLat = $roomJobSchedule?->latitude;
                                                    $finishLng = $roomJobSchedule?->longitude;
                                                    
                                                    // Try to get location from JobReport
                                                    $report = $roomJobSchedule?->jobReports->first();
                                                    if ($report && $report->latitude && $report->longitude) {
                                                        $finishLat = $report->latitude;
                                                        $finishLng = $report->longitude;
                                                    }
                                                    
                                                    // MOM: Backup location from Team Location History if still missing
                                                    if ($roomJobSchedule && (!$finishLat || !$finishLng)) {
                                                        $locFinish = \App\Models\JobTeamLocation::where('job_schedule_id', $roomJobSchedule->id)
                                                            ->where(function($q) {
                                                                $q->where('action', 'left')
                                                                  ->orWhere('action', 'like', '%finish%')
                                                                  ->orWhere('action', 'arrived'); // Fallback to arrived if only one location
                                                            })
                                                            ->orderBy('recorded_at', 'desc')
                                                            ->first();
                                                        if ($locFinish && $locFinish->latitude && $locFinish->longitude) {
                                                            $finishLat = $locFinish->latitude;
                                                            $finishLng = $locFinish->longitude;
                                                        }
                                                    }
                                                @endphp
                                                @if($finishTime)
                                                    <div style="font-size: 0.85rem;">{{ $finishTime->format('d/M/Y H:i') }}</div>
                                                    @if($finishLat && $finishLng)
                                                        <a href="https://www.google.com/maps?q={{ $finishLat }},{{ $finishLng }}" target="_blank" rel="noopener noreferrer" class="badge badge-success mt-1" style="text-decoration: none;">
                                                            <i class="fas fa-map-marker-alt"></i> View Map
                                                        </a>
                                                    @endif
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $materialReturn = $jobScheduleRoom->materialReturn;
                                                    $materialReturnStatus = $jobScheduleRoom->material_return_status;
                                                    $returnJobStatus = $jobScheduleRoom->jobSchedule?->status ?? $jobSchedule->status;
                                                    $canCreateMaterialReturn = !in_array($returnJobStatus, ['done_job', 'completed', 'selesai'], true)
                                                        && in_array($jobScheduleRoom->status, ['in_progress', 'cancelled'], true);
                                                @endphp
                                                <div class="d-flex align-items-center gap-2">
                                                    @if($materialReturn)
                                                        <span class="badge badge-{{ $materialReturn->status === 'returned' ? 'success' : ($materialReturn->status === 'approved' ? 'info' : 'warning') }}">
                                                            {{ strtoupper($materialReturn->return_number) }}
                                                        </span>
                                                        <span class="badge badge-{{ $materialReturnStatus === 'returned' ? 'success' : ($materialReturnStatus === 'pending' ? 'warning' : 'secondary') }}">
                                                            {{ ucfirst(str_replace('_', ' ', $materialReturnStatus)) }}
                                                        </span>
                                                        <button class="btn btn-sm btn-outline-info view-material-return" 
                                                                data-return-id="{{ $materialReturn->id }}"
                                                                title="View Material Return">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    @else
                                                        <span class="badge badge-secondary">No Return</span>
                                                        @if($canCreateMaterialReturn)
                                                            <button class="btn btn-sm btn-outline-primary create-material-return" 
                                                                    data-room-id="{{ $jobScheduleRoom->id }}"
                                                                    data-room-name="{{ $jobScheduleRoom->room_name }}"
                                                                    title="Create Material Return">
                                                                <i class="fas fa-undo"></i> Create Return
                                                            </button>
                                                        @endif
                                                    @endif
                                                </div>
                                            </td>
                                            <td>{{ $displayRentalName ?? '-' }}</td>
                                            <td>{{ $jaRoom?->notes ?? $jobScheduleRoom->notes ?? '-' }}</td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="9" class="text-center text-muted">
                                                <i class="fas fa-info-circle me-2"></i>No rooms found for this job advice.
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        {{-- Form Section removed from here and moved to @push('modals') --}}
                    </div>
                </div>

                <!-- Material Issue Tab -->
                @if(!in_array(strtolower($jobSchedule->type), ['check', 'remove', 'remove_free', 'remove free']))
                <div class="tab-pane fade" id="material-issue" role="tabpanel" aria-labelledby="material-issue-tab">
                    <div class="card" style="width: 100%; min-height: 500px;">
                        <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #1e3a8a;">
                            <h5 class="card-title mb-0" style="color: #1e3a8a;">
                                <i class="fas fa-box me-2"></i>Material Issue
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="overflow-x: auto; max-width: 100%;">
                                <table class="table table-bordered table-striped" style="min-width: 1400px; white-space: nowrap;">
                                    <thead>
                                        <tr>
                                            <th>Material Issue No</th>
                                            <th>Material Status</th>
                                            <th>Issue Date</th>
                                            <th>Product Category</th>
                                            <th>Product Name</th>
                                            <th>Package Size</th>
                                            <th>Qty</th>
                                            <th>Warehouse</th>
                                            <th>Team Code</th>
                                            <th>Last Update</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($materialIssueItems ?? [] as $item)
                                        <tr>
                                            <td>{{ $item->materialIssue->issue_number ?? '-' }}</td>
                                            <td>
                                                @php
                                                    $effectiveStatus = $item->effective_usage_status ?? $item->usage_status ?? $item->materialIssue->status ?? '-';
                                                @endphp
                                                <span class="status-badge status-{{ $effectiveStatus }}">
                                                    {{ ucfirst(str_replace('_', ' ', $effectiveStatus)) }}
                                                </span>
                                            </td>
                                            <td>{{ $item->materialIssue->issue_date?->format('d/M/Y') ?? '-' }}</td>
                                            <td>{{ $item->product?->productCategory?->name ?? '-' }}</td>
                                            <td>{{ $item->product?->name ?? '-' }}</td>
                                            <td>{{ $item->product?->packagingSize?->name ?? $item->product?->packaging_size ?? '-' }}</td>
                                            <td>{{ $item->quantity ?? 0 }}</td>
                                            <td>{{ $item->materialIssue->warehouse?->name ?? '-' }}</td>
                                            <td>{{ $item->materialIssue->team?->team_code ?? '-' }}</td>
                                            <td>{{ $item->updated_at?->format('d/M/Y H:i') ?? '-' }}</td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="10" class="text-center text-muted">
                                                <i class="fas fa-info-circle me-2"></i>No material items found.
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            @if(($inventoryIssuings ?? collect())->isNotEmpty())
                            <div class="px-3 pb-3">
                                <h6 class="mt-3 mb-2" style="color:#1e3a8a;font-weight:700;">
                                    <i class="fas fa-dolly me-1"></i>Inventory Issuing Detail
                                </h6>
                                <div class="table-responsive" style="overflow-x:auto; max-width:100%;">
                                    <table class="table table-bordered table-sm mb-0" style="min-width: 1200px; white-space: nowrap;">
                                        <thead>
                                            <tr>
                                                <th>Issuing No</th>
                                                <th>Reference No</th>
                                                <th>Issuing Status</th>
                                                <th>Product Category</th>
                                                <th>Product Name</th>
                                                <th>Package Size</th>
                                                <th class="text-end">Qty Requested</th>
                                                <th class="text-end">Qty Issued</th>
                                                <th class="text-end">Qty Received</th>
                                                <th>Serial Number</th>
                                                <th>Warehouse</th>
                                                <th>Team</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($inventoryIssuings as $issuing)
                                                @forelse($issuing->items as $issuingItem)
                                                <tr>
                                                    <td>{{ $issuing->issuing_number ?? 'ISU-' . $issuing->id }}</td>
                                                    <td>{{ $issuing->reference_no ?? '-' }}</td>
                                                    <td>
                                                        <span class="status-badge status-{{ $issuing->status }}">
                                                            {{ ucfirst(str_replace('_', ' ', $issuing->status ?? '-')) }}
                                                        </span>
                                                    </td>
                                                    <td>{{ $issuingItem->product?->productCategory?->name ?? '-' }}</td>
                                                    <td>{{ $issuingItem->product?->name ?? '-' }}</td>
                                                    <td>{{ $issuingItem->product?->packagingSize?->name ?? $issuingItem->product?->packaging_size ?? '-' }}</td>
                                                    <td class="text-end">{{ $issuingItem->quantity_requested ?? 0 }}</td>
                                                    <td class="text-end">{{ $issuingItem->quantity_issued ?? 0 }}</td>
                                                    <td class="text-end">{{ $issuingItem->quantity_received ?? 0 }}</td>
                                                    @php
                                                        $issuingSerials = $issuingItem->relationLoaded('serialLinks')
                                                            ? $issuingItem->serialLinks->pluck('serialNumber')->filter()
                                                            : collect();
                                                        if ($issuingSerials->isEmpty() && $issuingItem->serialNumber) {
                                                            $issuingSerials = collect([$issuingItem->serialNumber]);
                                                        }
                                                        $issuingSerialLabels = $issuingSerials
                                                            ->pluck('serial_number')
                                                            ->filter()
                                                            ->unique()
                                                            ->values();
                                                    @endphp
                                                    <td>
                                                        @forelse($issuingSerialLabels as $serialLabel)
                                                            <div>{{ $serialLabel }}</div>
                                                        @empty
                                                            -
                                                        @endforelse
                                                    </td>
                                                    <td>{{ $issuing->warehouse?->name ?? '-' }}</td>
                                                    <td>{{ $issuing->team?->team_code ?? $issuing->team?->team_name ?? '-' }}</td>
                                                </tr>
                                                @empty
                                                <tr>
                                                    <td colspan="12" class="text-center text-muted">No inventory issuing items found.</td>
                                                </tr>
                                                @endforelse
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endif

                <!-- Serial Numbers Tab -->
                <div class="tab-pane fade" id="serial-numbers" role="tabpanel" aria-labelledby="serial-numbers-tab">
                    <div class="card" style="width: 100%; min-height: 500px;">
                        <div class="card-header d-flex justify-content-between align-items-center" style="background-color: #f8f9fa; border-bottom: 2px solid #1e3a8a;">
                            <h5 class="card-title mb-0" style="color: #1e3a8a;">
                                <i class="fas fa-barcode me-2"></i>Serial Numbers
                            </h5>
                            @php
                                $aromaJobAllowed = !in_array(strtolower($jobSchedule->type), ['remove','remove_free','remove free'], true)
                                    && !in_array($jobSchedule->status, ['done_job','completed','cancelled','undone'], true)
                                    && ($jobSchedule->jobScheduleRooms->isNotEmpty());
                            @endphp
                            @if($aromaJobAllowed)
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="openAromaUnitModal()" title="Set unit & jadwal aroma dari web (fallback APK)">
                                <i class="fas fa-spray-can me-1"></i> Set Unit & Jadwal Aroma
                            </button>
                            @endif
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="overflow-x: auto; max-width: 100%;">
                                <table class="table table-bordered table-striped" style="min-width: 1200px; white-space: nowrap;">
                                    <thead>
                                        <tr>
                                            <th>Serial Number</th>
                                            <th>Product</th>
                                            <th>Product Category</th>
                                            <th>Status</th>
                                            <th>Warehouse</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($serialNumbers ?? [] as $sn)
                                        <tr>
                                            <td><strong>{{ $sn->serial_number }}</strong></td>
                                            <td>{{ $sn->masterProduct->name ?? '-' }}</td>
                                            <td>{{ $sn->masterProduct->productCategory->name ?? '-' }}</td>
                                            <td>
                                                @php
                                                    $statusClass = 'secondary';
                                                    $statusText = ucfirst(str_replace('_', ' ', $sn->status ?? 'unknown'));
                                                    if (in_array($sn->status, ['ready', 'available'])) {
                                                        $statusClass = 'success';
                                                        $statusText = 'Ready';
                                                    } elseif ($sn->status === 'in_use') {
                                                        $statusClass = 'primary'; // Blue badge for in_use
                                                        $statusText = 'In Use';
                                                    } elseif (in_array($sn->status, ['broken', 'damaged'])) {
                                                        $statusClass = 'danger';
                                                        $statusText = 'Broken';
                                                    } elseif (in_array($sn->status, ['on_service', 'maintenance'])) {
                                                        $statusClass = 'warning';
                                                        $statusText = 'On Service';
                                                    }
                                                @endphp
                                                <span class="badge bg-{{ $statusClass }}">
                                                    {{ $statusText }}
                                                </span>
                                            </td>
                                            <td>{{ $sn->warehouse->name ?? '-' }}</td>
                                            <td>{{ $sn->notes ?? '-' }}</td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">
                                                <i class="fas fa-info-circle me-2"></i>No serial numbers found for this job.
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Team Location Tab -->
                <div class="tab-pane fade" id="team-location" role="tabpanel" aria-labelledby="team-location-tab">
                    <div class="card" style="width: 100%; min-height: 500px;">
                        <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #1e3a8a;">
                            <h5 class="card-title mb-0" style="color: #1e3a8a;">
                                <i class="fas fa-map-marker-alt me-2"></i>Team Location History
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Location History Table -->
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th style="width: 18%;">Timestamp</th>
                                            <th style="width: 18%;">Team Member</th>
                                            <th style="width: 12%;">Action</th>
                                            <th style="width: 25%;">Location</th>
                                            <th style="width: 15%;">Device Info</th>
                                            <th style="width: 12%;">Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($teamLocations ?? [] as $location)
                                        <tr>
                                            <td>{{ $location->recorded_at->setTimezone('Asia/Jakarta')->format('d/M/Y H:i:s') }} WIB</td>
                                            <td>{{ $location->user->name ?? '-' }}</td>
                                            <td>
                                                <span class="badge {{ $location->action_badge_class }}">
                                                    {{ $location->action_text }}
                                                </span>
                                            </td>
                                            <td>
                                                <a href="{{ $location->google_maps_link }}" target="_blank" rel="noopener noreferrer" class="text-primary">
                                                    {{ $location->formatted_location }}
                                                    <i class="fas fa-external-link-alt ms-1" style="font-size: 0.8rem;"></i>
                                                </a>
                                            </td>
                                            <td>{{ $location->device_info ?? '-' }}</td>
                                            <td>{{ $location->notes ?? '-' }}</td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">
                                                <i class="fas fa-info-circle me-2"></i>No location data recorded yet.
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Photos Tab -->
                <div class="tab-pane fade" id="photos" role="tabpanel" aria-labelledby="photos-tab">
                    <div class="card" style="width: 100%; min-height: 500px;">
                        <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #1e3a8a;">
                            <h5 class="card-title mb-0" style="color: #1e3a8a;">
                                <i class="fas fa-camera me-2"></i>Photos
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="overflow-x: auto; max-width: 100%;">
                                <table class="table table-bordered table-striped" style="min-width: 1000px; white-space: nowrap;">
                                    <thead>
                                        <tr>
                                            <th>Job No</th>
                                            <th>Room</th>
                                            <th>Rental Name</th>
                                            <th>Photo Type</th>
                                            <th>Image</th>
                                            <th>Terakhir Update</th>
                                            <th>Oleh</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($jobPhotos ?? [] as $photo)
                                        <tr>
                                            <td>{{ $jobSchedule->job_number ?? '-' }}</td>
                                            <td>
                                                @php
                                                    $photoRoomName = '-';
                                                    $photoRentalName = '-';
                                                    $resolveSpecificRentalName = function ($scheduleRoom) {
                                                        if (!$scheduleRoom) {
                                                            return '-';
                                                        }

                                                        $adviceRoom = $scheduleRoom->jobAdviceRoom;
                                                        $rentalName = $adviceRoom?->rentalProduct?->rental_name
                                                            ?? $adviceRoom?->rental_name
                                                            ?? null;

                                                        if (($rentalName === '-' || blank($rentalName)) && filled($scheduleRoom->fallback_rental_name ?? null)) {
                                                            $rentalName = $scheduleRoom->fallback_rental_name;
                                                        }

                                                        return ($rentalName === '-' || blank($rentalName))
                                                            ? ($scheduleRoom->display_rental_name ?? '-')
                                                            : $rentalName;
                                                    };
                                                    
                                                    // Priority 0: Digital ID (New permanent solution)
                                                    if ($photo->job_schedule_room_id && $photo->jobScheduleRoom) {
                                                        $photoRoomName = $photo->jobScheduleRoom->room_name;
                                                        $photoRentalName = $resolveSpecificRentalName($photo->jobScheduleRoom);
                                                    }
                                                    // Priority 1: Regex dari description (format: "Room: ..." atau "- Room: ...")
                                                    elseif (!empty($photo->description) && preg_match('/[\-\s]*Room:\s*(.+)/i', $photo->description, $matches)) {
                                                        $extractedRoomName = trim($matches[1]);
                                                        $matchedRoom = $jobSchedule->jobScheduleRooms->first(function($r) use ($extractedRoomName) {
                                                            $roomName = strtolower(trim($r->room_name));
                                                            $extracted = strtolower(trim($extractedRoomName));
                                                            return $roomName === $extracted || strpos($extracted, $roomName) !== false || strpos($roomName, $extracted) !== false;
                                                        });
                                                        if ($matchedRoom) {
                                                            $photoRoomName = $matchedRoom->room_name;
                                                            $photoRentalName = $resolveSpecificRentalName($matchedRoom);
                                                        } else {
                                                            $photoRoomName = $extractedRoomName;
                                                        }
                                                    }
                                                    // Priority 2: Hanya 1 room -> pakai langsung
                                                    elseif ($jobSchedule->jobScheduleRooms->count() == 1) {
                                                        $firstRoom = $jobSchedule->jobScheduleRooms->first();
                                                        $photoRoomName = $firstRoom->room_name;
                                                        $photoRentalName = $resolveSpecificRentalName($firstRoom);
                                                    }
                                                    // Priority 2.5: Foto dari sibling JS yang berbeda -> cari room berdasarkan job_schedule_id foto
                                                    // Fix cross-room bug: foto JS 306 (Toilet Wanita) jangan berlabel Toilet Pria (JS 304)
                                                    elseif ($photo->job_schedule_id && $photo->job_schedule_id != $jobSchedule->id) {
                                                        $photoOwnJsr = isset($relatedJobScheduleRooms)
                                                            ? $relatedJobScheduleRooms->first(function($r) use ($photo) {
                                                                return $r->job_schedule_id == $photo->job_schedule_id;
                                                              })
                                                            : null;
                                                        if ($photoOwnJsr) {
                                                            $photoRoomName = $photoOwnJsr->room_name;
                                                            $photoRentalName = $resolveSpecificRentalName($photoOwnJsr);
                                                        }
                                                    }
                                                    // Priority 3: Fallback ke room_name JS yang sedang dibuka (hanya jika foto memang milik JS ini)
                                                    elseif ($jobSchedule->room_name && $jobSchedule->room_name !== '-') {
                                                        $photoRoomName = $jobSchedule->room_name;
                                                        if ($jobSchedule->jobScheduleRooms->count() > 0) {
                                                            $photoRentalName = $resolveSpecificRentalName($jobSchedule->jobScheduleRooms->first());
                                                        }
                                                    }
                                                    // Last resort: banyak room, tidak bisa ditentukan
                                                    elseif ($jobSchedule->jobScheduleRooms->count() > 1) {
                                                        $photoRoomName = 'Multiple Rooms';
                                                    }
                                                @endphp
                                                {{ $photoRoomName }}
                                            </td>
                                            <td>{{ $photoRentalName }}</td>
                                            <td>{{ $photo->photo_type ?? '-' }}</td>
                                            <td>
                                                @if($photo->photo_path)
                                                    @php
                                                        // Direct path to public/uploads folder
                                                        $photoPath = $photo->photo_path;
                                                        // Remove 'job-verifications/' prefix if exists, or use path as is
                                                        if (strpos($photoPath, 'job-verifications/') === 0) {
                                                            // Path: job-verifications/filename.jpg -> uploads/job-verifications/filename.jpg
                                                            $photoUrl = asset('uploads/' . $photoPath);
                                                        } elseif (strpos($photoPath, 'uploads/') === 0) {
                                                            // Already has uploads/ prefix
                                                            $photoUrl = asset($photoPath);
                                                        } else {
                                                            // Direct path to uploads/
                                                            $photoUrl = asset('uploads/' . $photoPath);
                                                        }
                                                    @endphp
                                                    <a href="{{ $photoUrl }}" target="_blank" rel="noopener noreferrer" title="Klik untuk melihat foto lengkap">
                                                        <img src="{{ $photoUrl }}" alt="{{ $photo->photo_type }}" class="img-thumbnail" style="width: 80px; height: 80px; object-fit: cover; cursor: pointer;" onerror="this.onerror=null; this.parentElement.innerHTML='<span class=\'text-muted\'>Gambar tidak ditemukan</span>';">
                                                    </a>
                                                @else
                                                -
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    // Prefer the real last update time; fallback for legacy report photos.
                                                    $displayUpdatedAt = null;
                                                    if (is_object($photo)) {
                                                        if (method_exists($photo, 'getAttribute')) {
                                                            $displayUpdatedAt = $photo->getAttribute('display_updated_at')
                                                                ?? $photo->getAttribute('updated_at')
                                                                ?? $photo->getAttribute('created_at');
                                                        } elseif (isset($photo->updated_at) || isset($photo->created_at)) {
                                                            $displayUpdatedAt = $photo->updated_at ?? $photo->created_at;
                                                        }
                                                    }
                                                    
                                                    // Convert to Carbon if needed
                                                    if ($displayUpdatedAt) {
                                                        if (is_string($displayUpdatedAt)) {
                                                            $displayUpdatedAt = \Carbon\Carbon::parse($displayUpdatedAt);
                                                        } elseif (!$displayUpdatedAt instanceof \Carbon\Carbon) {
                                                            $displayUpdatedAt = \Carbon\Carbon::parse($displayUpdatedAt);
                                                        }
                                                    }
                                                @endphp
                                                @if($displayUpdatedAt)
                                                    {{ $displayUpdatedAt->setTimezone('Asia/Jakarta')->format('d/M/Y H:i:s') }} WIB
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>{{ $photo->uploadedBy?->name ?? '-' }}</td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">
                                                <i class="fas fa-info-circle me-2"></i>No photos found.
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mobile Sync Tab -->
                <div class="tab-pane fade" id="mobile-sync" role="tabpanel" aria-labelledby="mobile-sync-tab">
                    <div class="card" style="width: 100%; min-height: 360px;">
                        <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #0f766e;">
                            <h5 class="card-title mb-0" style="color: #0f766e;">
                                <i class="fas fa-mobile-alt me-2"></i>Mobile Sync
                            </h5>
                        </div>
                        <div class="card-body">
                            @if(($mobileSyncLogs ?? collect())->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Action</th>
                                                <th>Teknisi</th>
                                                <th>Waktu Klik Kirim</th>
                                                <th>Mode</th>
                                                <th>Diterima Server</th>
                                                <th>Delay Sync</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($mobileSyncLogs as $syncLog)
                                                @php
                                                    $clickedAt = $syncLog->client_clicked_at;
                                                    $receivedAt = $syncLog->server_received_at;
                                                    $delaySeconds = ($clickedAt && $receivedAt) ? $clickedAt->diffInSeconds($receivedAt, false) : null;
                                                    $delayLabel = '-';
                                                    if ($delaySeconds !== null && $delaySeconds >= 0) {
                                                        $delayLabel = $delaySeconds < 60
                                                            ? $delaySeconds . ' detik'
                                                            : floor($delaySeconds / 60) . ' menit ' . ($delaySeconds % 60) . ' detik';
                                                    }
                                                    $isOffline = $syncLog->client_delivery_mode === 'queued_offline';
                                                @endphp
                                                <tr>
                                                    <td>{{ ucwords(str_replace('_', ' ', $syncLog->action)) }}</td>
                                                    <td>{{ $syncLog->user?->name ?? '-' }}</td>
                                                    <td>{{ $clickedAt ? $clickedAt->format('d M Y H:i:s') : '-' }}</td>
                                                    <td>
                                                        <span class="badge {{ $isOffline ? 'bg-warning text-dark' : 'bg-success' }}">
                                                            {{ $isOffline ? 'Offline cache' : 'Langsung' }}
                                                        </span>
                                                    </td>
                                                    <td>{{ $receivedAt ? $receivedAt->format('d M Y H:i:s') : '-' }}</td>
                                                    <td>{{ $isOffline ? $delayLabel : '-' }}</td>
                                                    <td>
                                                        <span class="badge bg-primary">
                                                            {{ ucfirst($syncLog->sync_status ?? 'synced') }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="alert alert-info mb-0">
                                    <i class="fas fa-info-circle me-2"></i>Belum ada audit mobile sync untuk job ini.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- BA Files Tab -->
                <div class="tab-pane fade" id="ba-files" role="tabpanel" aria-labelledby="ba-files-tab">
                    <div class="card" style="width: 100%; min-height: 500px;">
                        <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #1e3a8a;">
                            <h5 class="card-title mb-0" style="color: #1e3a8a;">
                                <i class="fas fa-file-alt me-2"></i>BA Files (Berita Acara)
                            </h5>
                        </div>
                        <div class="card-body">
                            @php
                                // Get rooms from relatedJobScheduleRooms (same as Rental & Team tab)
                                $baRooms = collect();
                                if (isset($relatedJobScheduleRooms) && $relatedJobScheduleRooms->count() > 0) {
                                    $baRooms = $relatedJobScheduleRooms->map(function($jsr) {
                                        $jaRoom = $jsr->jobAdviceRoom;
                                        $roomData = null;
                                        $roomId = null;
                                        $roomName = 'Unknown Room';
                                        
                                        if ($jaRoom) {
                                            $roomData = $jaRoom->contractRoom?->room ?? $jaRoom->quotationRoom?->room ?? null;
                                            if ($roomData) {
                                                $roomId = $roomData->id;
                                                $roomName = $roomData->room_name;
                                            } else {
                                                $roomId = $jsr->room_id ?? $jsr->id;
                                                $roomName = $jsr->room_name ?? 'Room';
                                            }
                                        } else {
                                            $roomId = $jsr->room_id ?? $jsr->id;
                                            $roomName = $jsr->room_name ?? 'Room';
                                        }
                                        
                                        return (object)[
                                            'id' => $roomId,
                                            'room_name' => $roomName
                                        ];
                                    })->unique('id');
                                } elseif ($jobSchedule->room_id) {
                                    $baRooms = collect([(object)[
                                        'id' => $jobSchedule->room_id,
                                        'room_name' => $jobSchedule->room_name ?? ($jobSchedule->room->room_name ?? 'Main Room')
                                    ]]);
                                }
                                
                                // Get existing BA files grouped by room (already aggregated from siblings in controller)
                                $baFiles = $baFiles ?? collect();
                                $baFilesByRoom = $baFiles->groupBy('room_id');
                            @endphp
                            
                            @if($baRooms->count() > 0)
                                @foreach($baRooms as $room)
                                <div class="ba-room-section mb-4" style="border: 1px solid #dee2e6; border-radius: 8px; overflow: hidden;">
                                    <div class="ba-room-header" style="background-color: #f8f9fa; padding: 12px 16px; border-bottom: 1px solid #dee2e6; border-left: 5px solid #1e3a8a;">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0" style="color: #495057;">
                                                <i class="fas fa-door-open me-2"></i>{{ $room->room_name }}
                                            </h6>
                                            <form class="ba-upload-form d-flex gap-2" data-room-id="{{ $room->id }}" data-job-schedule-id="{{ $jobSchedule->id }}">
                                                @csrf
                                                <input type="file" name="file" class="form-control form-control-sm" style="max-width: 250px;" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                                <button type="submit" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-upload me-1"></i>Upload
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    <div class="ba-room-files" style="padding: 16px;">
                                        @php
                                            $roomFiles = $baFilesByRoom->get($room->id, collect());
                                        @endphp
                                        @if($roomFiles->count() > 0)
                                        <table class="table table-sm table-bordered mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 30%;">File Name</th>
                                                    <th style="width: 15%;">Size</th>
                                                    <th style="width: 15%;">Status</th>
                                                    <th style="width: 10%;">Needed for Invoice</th>
                                                    <th style="width: 10%;">Approved</th>
                                                    <th style="width: 15%;">Uploaded</th>
                                                    <th style="width: 15%;">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($roomFiles as $file)
                                                @php
                                                    $user = auth()->user();
                                                    $canUpdateBA = $user->hasPermission('operational.job-schedules.update') || $user->hasPermission('operational.job-schedules.edit') || $user->hasPermission('operational.job-schedules.update.view');
                                                    $canApproveBA = $user->hasPermission('operational.job-schedules.approve-ba') || 
                                                                   $user->hasPermission('operational.job-schedules.approve-ba.view') || 
                                                                   $user->hasPermission('operational.job-schedules.approve');
                                                @endphp
                                                <tr data-file-id="{{ $file->id }}">
                                                    <td>
                                                        <i class="fas fa-file-pdf text-danger me-1"></i>
                                                        {{ Str::limit($file->file_name, 30) }}
                                                    </td>
                                                    <td>{{ $file->formatted_file_size }}</td>
                                                    <td>
                                                        <span class="badge {{ $file->status_badge_class }}">
                                                            {{ $file->status_text }}
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        <input type="checkbox" class="form-check-input ba-file-checkbox" 
                                                               data-field="needed_for_invoice" 
                                                               data-id="{{ $file->id }}"
                                                               {{ $file->needed_for_invoice ? 'checked' : '' }}
                                                               {{ $canUpdateBA ? '' : 'disabled' }}>
                                                    </td>
                                                    <td class="text-center">
                                                        @if($file->is_approved)
                                                            <span class="badge badge-success">
                                                                <i class="fas fa-check-circle me-1"></i>Approved
                                                            </span>
                                                        @else
                                                            <span class="text-muted small">Pending Approval</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        {{ $file->uploaded_at ? $file->uploaded_at->format('d/M/Y H:i') : '-' }}<br>
                                                        <small class="text-muted">{{ $file->uploader->name ?? '-' }}</small>
                                                    </td>
                                                    <td>
                                                        <a href="{{ route('operational.job-schedules.ba-files.preview', $file->id) }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary" title="Preview">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        @if(!$file->is_approved && $canApproveBA)
                                                        <button type="button" class="btn btn-sm btn-success btn-approve-ba" 
                                                                data-id="{{ $file->id }}" data-file-id="{{ $file->id }}" title="Approve BA File">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        @endif
                                                        <button type="button" class="btn btn-sm btn-danger btn-delete-ba" 
                                                                data-id="{{ $file->id }}" data-file-id="{{ $file->id }}" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                        @else
                                        <p class="text-muted text-center mb-0">
                                            <i class="fas fa-info-circle me-1"></i>No BA files uploaded for this room.
                                        </p>
                                        @endif
                                    </div>
                                </div>
                                @endforeach
                            @else
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>No rooms found for this job schedule.
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Device Technical Details Tab -->
                <div class="tab-pane fade" id="device-details" role="tabpanel" aria-labelledby="device-details-tab">
                    <div class="card" style="width: 100%; min-height: 500px;">
                        <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #1e3a8a;">
                            <h5 class="card-title mb-0" style="color: #1e3a8a;">
                                <i class="fas fa-microchip me-2"></i>Device Technical Details
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Room Name</th>
                                            <th>Device Type</th>
                                            <th>Device Name</th>
                                            <th>MAC / Serial Number</th>
                                            <th>Liquid Level</th>
                                            <th>Fan Level</th>
                                            <th>Run / Suspend Time</th>
                                            <th style="min-width: 150px;">Operating Schedule</th>
                                            <th>Work Status</th>
                                            <th>Last Scanned At</th>
                                            <th>Technical Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $dayNamesShort = [
                                                1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 
                                                4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'
                                            ];
                                        @endphp
                                        @forelse($unitDetails ?? [] as $detail)
                                        <tr>
                                            <td>{{ $detail->room_name ?? '-' }}</td>
                                            <td>{{ !empty($detail->device_type) ? $detail->device_type : '-' }}</td>
                                            <td>{{ !empty($detail->device_name) ? $detail->device_name : '-' }}</td>
                                            <td>
                                                <code>{{ !empty($detail->mac) && $detail->mac !== '-' ? $detail->mac : (!empty($detail->unit_serial_number) && $detail->unit_serial_number !== '-' ? $detail->unit_serial_number : 'NO MAC') }}</code>
                                            </td>
                                            <td>
                                                @php
                                                    $liquidRaw = $detail->snapshot['liquidLevel'] ?? null;
                                                    // Mobile's manual refill input stores a bucketed code
                                                    // ('0','<=10','>10','50','100'), not always a raw percent.
                                                    $liquidCodeLabels = ['0' => '0%', '<=10' => '≤10%', '>10' => '>10%', '50' => '50%', '100' => '100%'];
                                                    $liquidCodeClass = ['0' => 'bg-danger', '<=10' => 'bg-warning text-dark'];
                                                @endphp
                                                @if($liquidRaw !== null && $liquidRaw !== '')
                                                    @if(is_numeric($liquidRaw))
                                                        @php $liquid = intval($liquidRaw); @endphp
                                                        <div class="progress" style="height: 20px;" title="{{ $liquid }}%">
                                                            <div class="progress-bar {{ $liquid < 20 ? 'bg-danger' : ($liquid < 50 ? 'bg-warning' : 'bg-success') }}"
                                                                   role="progressbar" style="width: {{ $liquid }}%; min-width: 2.5rem;"
                                                                   aria-valuenow="{{ $liquid }}" aria-valuemin="0" aria-valuemax="100">
                                                                {{ $liquid }}%
                                                            </div>
                                                        </div>
                                                    @else
                                                        <span class="badge {{ $liquidCodeClass[$liquidRaw] ?? 'bg-success' }}">
                                                            {{ $liquidCodeLabels[$liquidRaw] ?? $liquidRaw }}
                                                        </span>
                                                    @endif
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if(isset($detail->snapshot['fanLevel']) && $detail->snapshot['fanLevel'] !== '')
                                                    <span class="badge bg-info text-dark">Level {{ $detail->snapshot['fanLevel'] }}</span>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td style="font-size: 0.8rem;">
                                                @if((isset($detail->snapshot['run']) && $detail->snapshot['run'] !== '') || (isset($detail->snapshot['suspend']) && $detail->snapshot['suspend'] !== ''))
                                                    <div>Run: {{ $detail->snapshot['run'] ?? 0 }}s</div>
                                                    <div>Suspend: {{ $detail->snapshot['suspend'] ?? 0 }}s</div>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td style="font-size: 0.8rem;">
                                                @php
                                                    $workSessions = null;
                                                    if (isset($detail->snapshot['workTime'])) {
                                                        $workSessions = $detail->snapshot['workTime'];
                                                    } elseif (isset($detail->snapshot['schedule'])) {
                                                        $workSessions = $detail->snapshot['schedule'];
                                                    }
                                                    
                                                    $sessions = [];
                                                    if ($workSessions) {
                                                        $sessions = isset($workSessions[0]) && is_array($workSessions[0]) ? $workSessions : [$workSessions];
                                                    }
                                                @endphp
                                                
                                                @forelse($sessions as $session)
                                                    @php
                                                        $days = $session['days'] ?? [];
                                                        $dayLabels = collect($days)->map(fn($d) => $dayNamesShort[$d] ?? $d)->implode(',');
                                                        if (count($days) === 7) $dayLabels = 'Everyday';
                                                        elseif (count($days) === 5 && !in_array(6, $days) && !in_array(7, $days)) $dayLabels = 'Weekdays';
                                                    @endphp
                                                    <div class="mb-1 d-flex align-items-center">
                                                        <span class="text-primary fw-bold me-1" style="min-width: 55px;">{{ $dayLabels }}:</span>
                                                        <span class="badge bg-light text-dark border shadow-xs py-1" style="font-size: 0.75rem;">
                                                            {{ $session['startTime'] ?? '--' }} - {{ $session['endTime'] ?? '--' }}
                                                        </span>
                                                    </div>
                                                    @if(isset($session['gear']) || isset($session['workTimeMinutes']) || isset($session['pauseTimeMinutes']))
                                                        <div class="text-muted mb-1" style="font-size: 0.7rem;">
                                                            @if(isset($session['gear'])) Gear: {{ $session['gear'] }} @endif
                                                            @if(isset($session['workTimeMinutes'])) &middot; Work: {{ $session['workTimeMinutes'] }}m @endif
                                                            @if(isset($session['pauseTimeMinutes'])) &middot; Pause: {{ $session['pauseTimeMinutes'] }}m @endif
                                                        </div>
                                                    @endif
                                                @empty
                                                    <span class="text-muted small">No schedule</span>
                                                @endforelse
                                            </td>
                                            <td>
                                                @if(isset($detail->snapshot['status']) && $detail->snapshot['status'] !== '')
                                                    <span class="badge {{ ($detail->snapshot['status'] == 1 || $detail->snapshot['status'] === 'running') ? 'bg-success' : 'bg-danger' }}">
                                                        {{ ($detail->snapshot['status'] == 1 || $detail->snapshot['status'] === 'running') ? 'RUNNING' : 'STOPPED' }}
                                                    </span>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>
                                                {{ $detail->scanned_at ? \Carbon\Carbon::parse($detail->scanned_at)->format('d/M/Y H:i') : '-' }}
                                            </td>
                                            <td>
                                                @if($detail->notes)
                                                    <small class="text-muted">{{ $detail->notes }}</small>
                                                @else
                                                    -
                                                @endif
                                                @if(isset($detail->source) && $detail->source === 'JobReport')
                                                    <br><span class="badge bg-secondary" style="font-size: 0.6rem;">Verification Log</span>
                                                @elseif(isset($detail->mac))
                                                    <br><span class="badge bg-primary" style="font-size: 0.6rem;">Scan Log</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="11" class="text-center text-muted py-4">
                                                <i class="fas fa-info-circle me-1"></i> No technical data available for this job yet.
                                            </td>
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
    </div>
</div>


@endsection

@push('modals')
{{-- STUDY CASE B1: Only include View Material Return Modal (for viewing details) --}}
<!-- View Material Return Modal (only for viewing, not for creating) -->
<div class="modal fade" id="viewMaterialReturnModal" tabindex="-1" aria-labelledby="viewMaterialReturnModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="viewMaterialReturnModalLabel">
                    <i class="fas fa-info-circle me-2"></i>Material Return Details
                </h5>
                <!-- Close Button Option -->
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="viewMaterialReturnContent">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading material return details...</p>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Close
                </button>
                <button type="button" class="btn btn-info d-none" id="approveMaterialReturnBtn">
                    <i class="fas fa-check me-1"></i>Approve
                </button>
                <button type="button" class="btn btn-success d-none" id="completeMaterialReturnBtn">
                    <i class="fas fa-check-circle me-1"></i>Complete Return
                </button>
            </div>
        </div>
    </div>
</div>
<!-- Create Material Return Modal -->
<div class="modal fade" id="createMaterialReturnModal" tabindex="-1" aria-labelledby="createMaterialReturnModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="createMaterialReturnModalLabel">
                    <i class="fas fa-undo me-2"></i>Create Material Return
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="createMaterialReturnForm">
                    <input type="hidden" id="material_return_room_id" name="room_id">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Room Name</label>
                            <input type="text" class="form-control" id="material_return_room_name" readonly style="background-color: #f8f9fa;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Return Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="material_return_date" name="return_date" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Warehouse <span class="text-danger">*</span></label>
                        <select class="form-select" id="material_return_warehouse_id" name="warehouse_id" required>
                            <option value="">Select Warehouse</option>
                            @foreach($warehouses ?? [] as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Return Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="material_return_reason" name="return_reason" rows="3" required placeholder="Enter the reason for material return..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Notes</label>
                        <textarea class="form-control" id="material_return_notes" name="notes" rows="2" placeholder="Additional notes (optional)..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Return Items <span class="text-danger">*</span></label>
                        <div class="border rounded p-3" style="background-color: #f8f9fa;">
                            <button type="button" class="btn btn-sm btn-outline-primary mb-3" id="add_return_item_btn">
                                <i class="fas fa-plus me-1"></i> Add Item
                            </button>
                            <div id="material_return_items_list"></div>
                            <div class="text-muted small mt-2">
                                <i class="fas fa-info-circle me-1"></i>Click "Add Item" to add products to return
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-primary" id="submitMaterialReturnBtn">
                            <i class="fas fa-save me-1"></i>Create Material Return
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Web fallback: Done Job with Berita Acara modal -->
<div class="modal fade" id="doneJobBaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background:#1e3a8a;color:#fff;">
                <h5 class="modal-title"><i class="fas fa-file-signature me-2"></i>Konfirmasi Pekerjaan - PIC & TTD</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p style="font-size:13px;color:#6b7280;">
                    Pastikan semua ruangan sudah selesai dengan foto before/after sebelum menyimpan PIC, tanda tangan, dan BA.
                    Jika sebagian ruangan belum selesai, centang opsi di bawah agar ruangan tersebut dipindahkan ke Job baru.
                </p>
                <form id="doneJobBaForm" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama PIC Lapangan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="pic_name" id="baPicName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Foto PIC <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" name="pic_photo" id="baPicPhoto" accept="image/*" capture="environment">
                        <div class="form-text">Di tablet/HP tombol ini menawarkan kamera. Bisa juga unggah file.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Foto Dokumentasi Tambahan (opsional)</label>
                        <input type="file" class="form-control" name="photos[]" id="baWorkPhotos" accept="image/*" capture="environment" multiple>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tanda Tangan PIC <span class="text-danger">*</span></label>
                        <div style="border:1px solid #d1d5db;border-radius:6px;background:#fff;">
                            <canvas id="baSignaturePad" style="width:100%;height:180px;touch-action:none;"></canvas>
                        </div>
                        <button type="button" class="btn btn-outline-secondary btn-sm mt-2" onclick="clearBaSignature()">
                            <i class="fas fa-eraser"></i> Hapus Tanda Tangan
                        </button>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Catatan (opsional)</label>
                        <textarea class="form-control" name="notes" id="baNotes" rows="2"></textarea>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" name="cannot_complete_all_rooms" id="baCannotCompleteAllRooms">
                        <label class="form-check-label fw-bold" for="baCannotCompleteAllRooms">
                            Tidak dapat menyelesaikan semua ruangan
                        </label>
                        <div class="form-text">
                            Centang jika sebagian ruangan belum selesai dikerjakan. Job akan ditandai "meninggalkan lokasi" dan ruangan yang belum selesai dipindahkan ke Job baru (outstanding).
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-success" id="baSubmitBtn" onclick="submitDoneJobBa()">
                    <i class="fas fa-check-circle"></i> Selesaikan & Buat BA
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Phase 3 web fallback: set scanned unit + aroma schedule (DB-record-only) --}}
<div class="modal fade" id="aromaUnitModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background:#1e3a8a;color:#fff;">
                <h5 class="modal-title"><i class="fas fa-spray-can me-2"></i>Set Unit & Jadwal Aroma</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p style="font-size:13px;color:#6b7280;">
                    Catat unit yang dipasang dan jadwal aromanya bila aplikasi teknisi tidak dapat dipakai.
                    Data disimpan ke job (record-only); pengaturan tidak dikirim langsung ke perangkat SmartScent fisik.
                </p>
                <div class="mb-3">
                    <label class="form-label fw-bold">Ruangan <span class="text-danger">*</span></label>
                    <select class="form-select" id="aromaRoom">
                        <option value="">— Pilih ruangan —</option>
                        @foreach($jobSchedule->jobScheduleRooms as $jsr)
                            @php $jarId = $jsr->job_advice_room_id; @endphp
                            @if($jarId)
                            <option value="{{ $jarId }}">{{ $jsr->room_name ?? ('Room #'.$jarId) }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="row">
                    <div class="col-md-7 mb-3">
                        <label class="form-label fw-bold">Serial Number / MAC Unit <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="aromaMac" placeholder="cth. AA:BB:CC:DD:EE:FF">
                    </div>
                    <div class="col-md-5 mb-3">
                        <label class="form-label fw-bold">Tipe Unit</label>
                        <input type="text" class="form-control" id="aromaDeviceType" value="SmartScent">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Jam Mulai</label>
                        <input type="time" class="form-control" id="aromaStart" value="08:00">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Jam Selesai</label>
                        <input type="time" class="form-control" id="aromaEnd" value="17:00">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Intensitas</label>
                        <select class="form-select" id="aromaIntensity">
                            <option value="low">Rendah</option>
                            <option value="medium" selected>Sedang</option>
                            <option value="high">Tinggi</option>
                        </select>
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-bold">Catatan (opsional)</label>
                    <textarea class="form-control" id="aromaNotes" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="aromaSubmitBtn" onclick="submitAromaUnit()">
                    <i class="fas fa-save"></i> Simpan Unit & Jadwal
                </button>
            </div>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<script>
    // Permissions for JS
    @php
        $user = auth()->user();
        $hasApproveMR = $user->hasPermission('operational.job-schedules.approve-material-return') || 
                         $user->hasPermission('operational.job-schedules.approve-material-return.view') || 
                         $user->hasPermission('operational.job-schedules.approve');
    @endphp
    window.canApproveMaterialReturn = {{ $hasApproveMR ? 'true' : 'false' }};
</script>

{{-- STUDY CASE B1: Include Material Return Scripts --}}
@include('operational.job-schedules.partials.material-return-scripts')

<script>
$(document).ready(function() {
    // Tab switching functionality using Bootstrap 5
    $('#jobScheduleTabs button[data-bs-toggle="tab"]').on('click', function(e) {
        e.preventDefault();
        var target = $(this).attr('data-bs-target');
        
        // Remove active class from all tabs and content
        $('#jobScheduleTabs button').removeClass('active');
        $('.tab-pane').removeClass('show active');
        
        // Add active class to clicked tab
        $(this).addClass('active');
        $(target).addClass('show active');
    });

    // Auto-save BA File checkboxes (Point 10) - Using Event Delegation for robustness
    $(document).on('change', '.ba-file-checkbox', function() {
        const checkbox = $(this);
        const fileId = checkbox.data('id');
        const field = checkbox.data('field');
        const value = checkbox.prop('checked') ? 1 : 0;
        
        // Disable temporarily to prevent double click
        checkbox.prop('disabled', true);
        
        $.ajax({
            url: "{{ url('operational/job-schedules/ba-files') }}/" + fileId + "/update-checkbox",
            method: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                [field]: value
            },
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: response.message || 'Data berhasil diperbarui',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true
                    });
                } else {
                    checkbox.prop('checked', !checkbox.prop('checked')); // Revert
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: response.message || 'Gagal memperbarui data'
                    });
                }
            },
            error: function(xhr) {
                checkbox.prop('checked', !checkbox.prop('checked')); // Revert
                Swal.fire({
                    icon: 'error',
                    title: 'System Error',
                    text: 'Terjadi kesalahan sistem saat memperbarui data.'
                });
            },
            complete: function() {
                checkbox.prop('disabled', false);
            }
        });
    });

    // BA Files Upload Handler
    $(document).on('submit', '.ba-upload-form', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const roomId = form.data('room-id');
        const jobScheduleId = form.data('job-schedule-id');
        const fileInput = form.find('input[type="file"]')[0];
        
        if (!fileInput.files.length) {
            Swal.fire('Info', 'Silakan pilih file untuk diupload.', 'info');
            return;
        }
        
        const formData = new FormData();
        formData.append('file', fileInput.files[0]);
        formData.append('room_id', roomId);
        formData.append('_token', '{{ csrf_token() }}');
        
        const submitBtn = form.find('button[type="submit"]');
        const originalHtml = submitBtn.html();
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Uploading...');
        
        $.ajax({
            url: '{{ url("operational/job-schedules") }}/' + jobScheduleId + '/ba-files',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(data) {
                if (data.status === 'success') {
                    Swal.fire('Berhasil', data.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message || 'Upload failed', 'error');
                }
            },
            error: function(xhr) {
                Swal.fire('Error', 'Gagal mengupload file.', 'error');
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalHtml);
                fileInput.value = '';
            }
        });
    });
    
    // BA Files Approve Handler
    $(document).on('click', '.btn-approve-ba', function(e) {
        const btn = $(this).closest('.btn-approve-ba');
        const fileId = btn.attr('data-id') || btn.attr('data-file-id') || btn.data('id');
        
        if (!fileId) {
            Swal.fire('Error', 'File ID tidak ditemukan.', 'error');
            return;
        }
        
        Swal.fire({
            title: 'Konfirmasi',
            text: 'Apakah Anda yakin ingin meng-approve file ini?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Approve',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
                
                $.ajax({
                    url: '{{ url("operational/job-schedules/ba-files") }}/' + fileId + '/approve',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(data) {
                        if (data.status === 'success') {
                            Swal.fire('Berhasil', data.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', data.message || 'Approve failed', 'error');
                            btn.prop('disabled', false).html('<i class="fas fa-check"></i>');
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error', 'Gagal melakukan approval.', 'error');
                        btn.prop('disabled', false).html('<i class="fas fa-check"></i>');
                    }
                });
            }
        });
    });
    
    // BA Files Delete Handler
    $(document).on('click', '.btn-delete-ba', function(e) {
        const btn = $(this).closest('.btn-delete-ba');
        const fileId = btn.attr('data-id') || btn.attr('data-file-id') || btn.data('id');
        
        if (!fileId) {
            Swal.fire('Error', 'File ID tidak ditemukan.', 'error');
            return;
        }
        
        Swal.fire({
            title: 'Hapus File?',
            text: 'Apakah Anda yakin ingin menghapus file ini?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
                
                $.ajax({
                    url: '{{ url("operational/job-schedules/ba-files") }}/' + fileId,
                    method: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(data) {
                        if (data.status === 'success') {
                            Swal.fire('Berhasil', data.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', data.message || 'Delete failed', 'error');
                            btn.prop('disabled', false).html('<i class="fas fa-trash"></i>');
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error', 'Gagal menghapus file.', 'error');
                        btn.prop('disabled', false).html('<i class="fas fa-trash"></i>');
                    }
                });
            }
        });
    });
});

// Initialize Flatpickr
let fpScheduleDate = null;
document.addEventListener('DOMContentLoaded', function() {
    fpScheduleDate = flatpickr("#scheduleDateInput", {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "d/M/Y",
        allowInput: true,
        onReady: function(selectedDates, dateStr, instance) {
            // Hide the altInput initially since we use the display span
            if (instance.altInput) {
                instance.altInput.style.display = 'none';
                instance.altInput.classList.add('form-control', 'form-control-sm');
            }
        },
        onClose: function(selectedDates, dateStr, instance) {
            // Always attempt verification in saveScheduleDateInline
            saveScheduleDateInline(dateStr);
        }
    });
});

// Track which field is being edited
let editingField = null;

function editScheduleDate() {
    document.getElementById('scheduleDateDisplay').style.display = 'none';
    if (fpScheduleDate) {
        if (fpScheduleDate.altInput) {
            fpScheduleDate.altInput.style.display = 'block';
            fpScheduleDate.altInput.focus();
        }
        fpScheduleDate.open();
    } else {
        document.getElementById('scheduleDateInput').style.display = 'block';
        document.getElementById('scheduleDateInput').focus();
    }
}

function saveScheduleDateInline(dateStr) {
    const input = document.getElementById('scheduleDateInput');
    const display = document.getElementById('scheduleDateDisplay');
    const scheduleDate = dateStr || input.value.trim();
    
    if (!scheduleDate) {
        display.style.display = 'inline';
        input.style.display = 'none';
        return;
    }
    
    // If no change, just revert UI
    const originalDate = '{{ $jobSchedule->schedule_date?->format("Y-m-d") ?? "" }}';
    if (scheduleDate === originalDate) {
        display.style.display = 'inline';
        if (fpScheduleDate && fpScheduleDate.altInput) {
            fpScheduleDate.altInput.style.display = 'none';
        } else {
            input.style.display = 'none';
        }
        return;
    }

    $.ajax({
        url: `{{ route('operational.job-schedules.update', $jobSchedule->id) }}`,
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'X-HTTP-Method-Override': 'PUT',
            'Accept': 'application/json'
        },
        data: JSON.stringify({ schedule_date: scheduleDate }),
        contentType: 'application/json',
        success: function(data) {
            if (data.status === 'success') {
                toast('Berhasil', 'Schedule date berhasil diperbarui', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                Swal.fire('Error', data.message || 'Gagal memperbarui data', 'error');
            }
        },
        error: function(xhr) {
            Swal.fire('Error', 'Terjadi kesalahan sistem', 'error');
        }
    });
}

function editInternalNotes() {
    document.getElementById('internalNotesDisplay').style.display = 'none';
    document.getElementById('internalNotesInput').style.display = 'block';
    document.getElementById('internalNotesInput').focus();
}

function saveInternalNotesInline() {
    const input = document.getElementById('internalNotesInput');
    const display = document.getElementById('internalNotesDisplay');
    const internalNotes = input.value;
    
    // If no change, just revert UI
    const originalNotes = @json($jobSchedule->internal_notes ?? '');
    if (internalNotes === originalNotes) {
        display.style.display = 'block';
        input.style.display = 'none';
        return;
    }

    $.ajax({
        url: `{{ route('operational.job-schedules.update', $jobSchedule->id) }}`,
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'X-HTTP-Method-Override': 'PUT',
            'Accept': 'application/json'
        },
        data: JSON.stringify({ internal_notes: internalNotes }),
        contentType: 'application/json',
        success: function(data) {
            if (data.status === 'success') {
                toast('Berhasil', 'Catatan internal berhasil diperbarui', 'success');
                display.innerText = internalNotes || 'Klik untuk tambah catatan...';
                display.style.display = 'block';
                input.style.display = 'none';
            } else {
                Swal.fire('Error', data.message || 'Gagal memperbarui data', 'error');
            }
        },
        error: function(xhr) {
            Swal.fire('Error', 'Terjadi kesalahan sistem', 'error');
        }
    });
}

function filterRentalTeam() {
    const teamCode = document.getElementById('filterTeamCodeRental').value;
    const rows = document.querySelectorAll('#rental-team tbody tr');
    rows.forEach(row => {
        // Implement actual filtering if needed
    });
}

// Confirm Done Job before submitting
function confirmDoneJob(event) {
    const form = event.target;

    if (form.dataset.confirmedDone === 'true') {
        delete form.dataset.confirmedDone;
        return true;
    }

    event.preventDefault();

    showConfirmDialog(
        'Tandai job ini sebagai selesai?',
        'Status akan berubah menjadi Completed, bisa memicu auto-create Unit On Wall, Remove Job, dan invoice generation.'
    ).then((confirmed) => {
        if (!confirmed) {
            return;
        }

        form.dataset.confirmedDone = 'true';
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            form.submit();
        }
    });

    return false;
}

function confirmUndoneJob(event) {
    const form = event.target;

    if (form.dataset.confirmedUndone === 'true') {
        delete form.dataset.confirmedUndone;
        return true;
    }

    event.preventDefault();

    showConfirmDialog(
        'Yakin ingin undone job?',
        'Status akan berubah menjadi Undone dan BA Date akan dihapus.'
    ).then((confirmed) => {
        if (!confirmed) {
            return;
        }

        form.dataset.confirmedUndone = 'true';
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            form.submit();
        }
    });

    return false;
}

/**
 * Handle manual room completion via AJAX
 */
function completeRoomManual(roomId) {
    const btn = event.currentTarget;
    const originalContent = btn.innerHTML;
    Swal.fire({
        title: 'Selesaikan Ruangan',
        html: `
            <p style="font-size:13px;color:#6b7280;margin-bottom:12px;text-align:left;">
                Unggah foto dokumentasi pekerjaan. Di tablet/HP, tombol unggah otomatis menawarkan kamera.
            </p>
            <div style="text-align:left;margin-bottom:10px;">
                <label style="font-weight:600;font-size:13px;">Foto Before Work</label>
                <input type="file" id="manualBeforePhotos" class="swal2-file" accept="image/*" capture="environment" multiple>
            </div>
            <div style="text-align:left;margin-bottom:10px;">
                <label style="font-weight:600;font-size:13px;">Foto After Work</label>
                <input type="file" id="manualAfterPhotos" class="swal2-file" accept="image/*" capture="environment" multiple>
            </div>
            <div style="text-align:left;">
                <label style="font-weight:600;font-size:13px;">Catatan (opsional)</label>
                <textarea id="manualRoomNotes" class="swal2-textarea" style="margin:0;" placeholder="Catatan ruangan"></textarea>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Tandai Selesai',
        cancelButtonText: 'Batal',
        focusConfirm: false,
        preConfirm: () => {
            const fd = new FormData();
            const before = document.getElementById('manualBeforePhotos').files;
            const after = document.getElementById('manualAfterPhotos').files;
            for (let i = 0; i < before.length; i++) fd.append('before_photos[]', before[i]);
            for (let i = 0; i < after.length; i++) fd.append('after_photos[]', after[i]);
            const notes = document.getElementById('manualRoomNotes').value;
            if (notes) fd.append('notes', notes);
            return fd;
        }
    }).then((result) => {
        if (!result.isConfirmed) {
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        $.ajax({
            url: `{{ route('operational.job-schedules.rooms.complete-manual', [ 'jobSchedule' => $jobSchedule->id, 'roomId' => ':roomId' ]) }}`.replace(':roomId', roomId),
            method: 'POST',
            data: result.value,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(data) {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        const cell = $(btn).closest('td');
                        cell.html('<span class="status-badge status-completed">Completed</span>');
                        checkHeaderDoneButton();
                    });
                } else {
                    Swal.fire('Gagal', data.message || 'Gagal mengupdate status', 'error');
                    btn.disabled = false;
                    btn.innerHTML = originalContent;
                }
            },
            error: function(xhr) {
                const msg = xhr.responseJSON ? xhr.responseJSON.message : 'Terjadi kesalahan sistem';
                Swal.fire('Gagal', msg, 'error');
                btn.disabled = false;
                btn.innerHTML = originalContent;
            }
        });
    });
}

/**
 * Check room statuses and toggle Done Job button
 */
function checkHeaderDoneButton() {
    // Find all rows in the current view that are NOT completed
    // We check for absence of status-completed badge
    const anyPending = $('.room-status-cell').find('.status-badge:not(.status-completed)').length > 0;
    const headerBtn = $('#headerDoneButton');
    const canDoneFromStatus = headerBtn.data('can-done-from-status') == 1;
    
    if (headerBtn.length) {
        if (anyPending || !canDoneFromStatus) {
            headerBtn.prop('disabled', true);
            headerBtn.attr('title', anyPending ? 'Selesaikan semua room terlebih dahulu' : 'Done Job hanya bisa setelah On Progress Teknisi');
            headerBtn.css({
                'background-color': '#6c757d',
                'border-color': '#6c757d',
                'cursor': 'not-allowed'
            });
        } else {
            headerBtn.prop('disabled', false);
            headerBtn.attr('title', 'Selesaikan pekerjaan');
            headerBtn.css({
                'background-color': '', // Reset to original btn-success
                'border-color': '',
                'cursor': 'pointer'
            });
            
            // Optional: If this view just finished all rooms, tell the user they can now click Done Job
            toast('Info', 'Semua ruangan telah selesai. Silakan klik "Konfirmasi Pekerjaan" di header untuk menyimpan PIC, tanda tangan, dan BA.', 'info');
        }
    }
}

// Simple toast helper if not defined
function toast(title, text, icon) {
    if (typeof Swal !== 'undefined' && Swal.mixin) {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
        Toast.fire({ icon, title: text });
    }
}

// MOM15: BA Date inline edit functionality
let fpBaDate = null;
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Flatpickr for BA Date if element exists
    const baDateInput = document.getElementById("baDateInput");
    if (baDateInput) {
        fpBaDate = flatpickr("#baDateInput", {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d/M/Y",
            allowInput: true,
            onReady: function(selectedDates, dateStr, instance) {
                // Hide the altInput initially since we use the display span
                if (instance.altInput) {
                    instance.altInput.style.display = 'none';
                    instance.altInput.classList.add('form-control', 'form-control-sm');
                }
            },
            onClose: function(selectedDates, dateStr, instance) {
                // Always attempt save when closing the picker
                saveBaDateInline(dateStr);
            }
        });
    }
});

function editBaDate() {
    const display = document.getElementById('baDateDisplay');
    if (display) {
        display.style.display = 'none';
    }
    if (fpBaDate) {
        if (fpBaDate.altInput) {
            fpBaDate.altInput.style.display = 'block';
            fpBaDate.altInput.focus();
        }
        fpBaDate.open();
    } else {
        const input = document.getElementById('baDateInput');
        if (input) {
            input.style.display = 'block';
            input.focus();
        }
    }
}

function saveBaDateInline(dateStr) {
    const input = document.getElementById('baDateInput');
    const display = document.getElementById('baDateDisplay');
    const baDate = dateStr || (input ? input.value.trim() : '');
    
    if (!baDate) {
        if (display) display.style.display = 'inline';
        if (input) input.style.display = 'none';
        if (fpBaDate && fpBaDate.altInput) {
            fpBaDate.altInput.style.display = 'none';
        }
        return;
    }
    
    // If no change, just revert UI
    const originalDate = '{{ $jobSchedule->ba_date?->format("Y-m-d") ?? "" }}';
    if (baDate === originalDate) {
        if (display) display.style.display = 'inline';
        if (fpBaDate && fpBaDate.altInput) {
            fpBaDate.altInput.style.display = 'none';
        } else if (input) {
            input.style.display = 'none';
        }
        return;
    }

    $.ajax({
        url: `{{ route('operational.job-schedules.update-ba-date', $jobSchedule->id) }}`,
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json'
        },
        data: JSON.stringify({ ba_date: baDate }),
        contentType: 'application/json',
        success: function(data) {
            if (data.status === 'success') {
                toast('Berhasil', 'BA Date berhasil diperbarui', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                Swal.fire('Error', data.message || 'Gagal memperbarui BA Date', 'error');
                // Revert UI
                if (display) display.style.display = 'inline';
                if (fpBaDate && fpBaDate.altInput) {
                    fpBaDate.altInput.style.display = 'none';
                } else if (input) {
                    input.style.display = 'none';
                }
            }
        },
        error: function(xhr) {
            const msg = xhr.responseJSON ? xhr.responseJSON.message : 'Terjadi kesalahan sistem';
            Swal.fire('Error', msg, 'error');
            // Revert UI
            if (display) display.style.display = 'inline';
            if (fpBaDate && fpBaDate.altInput) {
                fpBaDate.altInput.style.display = 'none';
            } else if (input) {
                input.style.display = 'none';
            }
        }
    });
}
</script>
<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<!-- Web fallback: Done Job with Berita Acara -->
<script>
const webIssuedMaterialRows = @json($webIssuedMaterialRows ?? []);

function escapeHtml(value) {
    return String(value ?? '-')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function renderIssuedMaterialsTable() {
    if (!webIssuedMaterialRows.length) {
        return `
            <div class="alert alert-warning text-start mb-0">
                <i class="fas fa-exclamation-triangle me-1"></i>
                Detail barang issued belum tersedia untuk job ini.
            </div>
        `;
    }

    const rows = webIssuedMaterialRows.map((row) => `
        <tr>
            <td>${escapeHtml(row.issuing_number)}</td>
            <td>${escapeHtml(row.issuing_status)}</td>
            <td>${escapeHtml(row.product_category)}</td>
            <td>${escapeHtml(row.product_name)}</td>
            <td>${escapeHtml(row.package_size)}</td>
            <td class="text-end">${escapeHtml(row.quantity_requested ?? 0)}</td>
            <td class="text-end">${escapeHtml(row.quantity_issued ?? 0)}</td>
            <td>${escapeHtml(row.serial_number)}</td>
            <td>${escapeHtml(row.warehouse)}</td>
            <td>${escapeHtml(row.team)}</td>
        </tr>
    `).join('');

    return `
        <div class="table-responsive" style="max-height: 360px; overflow:auto; text-align:left;">
            <table class="table table-sm table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Issuing No</th>
                        <th>Status</th>
                        <th>Category</th>
                        <th>Product</th>
                        <th>Package</th>
                        <th class="text-end">Req</th>
                        <th class="text-end">Issued</th>
                        <th>SN</th>
                        <th>Warehouse</th>
                        <th>Team</th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>
        </div>
    `;
}

function scrollToRoomCompletion() {
    const rentalTab = document.getElementById('rental-team-tab');
    if (rentalTab && window.bootstrap) {
        new bootstrap.Tab(rentalTab).show();
    }

    setTimeout(() => {
        const target = document.querySelector('.btn-complete-room');
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
            target.classList.add('btn-warning');
            setTimeout(() => target.classList.remove('btn-warning'), 1600);
        }
    }, 250);
}

// Phase 2: technician location lifecycle actions from the dashboard
function webLifecycleAction(action, confirmText) {
    const routes = {
        'arrived': `{{ route('operational.job-schedules.arrived', $jobSchedule->id) }}`,
        'start-work': `{{ route('operational.job-schedules.start-work', $jobSchedule->id) }}`,
        'leave-location': `{{ route('operational.job-schedules.leave-location', $jobSchedule->id) }}`,
    };
    Swal.fire({
        title: 'Konfirmasi',
        text: confirmText,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, lanjutkan',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (!result.isConfirmed) return;
        $.ajax({
            url: routes[action],
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(data) {
                const html = action === 'start-work'
                    ? `${escapeHtml(data.message)}<br><small>Upload foto pengerjaan tersedia pada tombol <b>Upload Foto & Done</b> di tiap ruangan.</small>`
                    : escapeHtml(data.message);
                Swal.fire({ icon: 'success', title: 'Berhasil', html, timer: 2200, showConfirmButton: false })
                    .then(() => window.location.reload());
            },
            error: function(xhr) {
                const msg = xhr.responseJSON ? (xhr.responseJSON.message || 'Aksi gagal') : 'Terjadi kesalahan sistem';
                Swal.fire('Gagal', msg, 'error');
            }
        });
    });
}

// Phase 3 web fallback: material confirm / verify
function webMaterialAction(action, confirmText) {
    const routes = {
        'confirm-materials': `{{ route('operational.job-schedules.confirm-materials', $jobSchedule->id) }}`,
        'verify-materials': `{{ route('operational.job-schedules.verify-materials', $jobSchedule->id) }}`,
    };
    Swal.fire({
        title: action === 'confirm-materials' ? 'Konfirmasi Barang' : 'Ambil Barang',
        html: `
            <p class="text-start" style="font-size:13px;color:#6b7280;">${escapeHtml(confirmText)}</p>
            ${renderIssuedMaterialsTable()}
        `,
        width: 1100,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, lanjutkan',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (!result.isConfirmed) return;
        $.ajax({
            url: routes[action],
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(data) {
                Swal.fire({ icon: 'success', title: 'Berhasil', text: data.message, timer: 1600, showConfirmButton: false })
                    .then(() => window.location.reload());
            },
            error: function(xhr) {
                const msg = xhr.responseJSON ? (xhr.responseJSON.message || 'Aksi gagal') : 'Terjadi kesalahan sistem';
                Swal.fire('Gagal', msg, 'error');
            }
        });
    });
}

// Phase 3 web fallback: set unit + aroma schedule
function openAromaUnitModal() {
    new bootstrap.Modal(document.getElementById('aromaUnitModal')).show();
}

function submitAromaUnit() {
    const roomId = document.getElementById('aromaRoom').value;
    const mac = document.getElementById('aromaMac').value.trim();
    if (!roomId) { Swal.fire('Lengkapi Data', 'Ruangan wajib dipilih.', 'warning'); return; }
    if (!mac) { Swal.fire('Lengkapi Data', 'Serial Number / MAC unit wajib diisi.', 'warning'); return; }

    const payload = {
        room_id: roomId,
        mac: mac,
        device_type: document.getElementById('aromaDeviceType').value.trim() || 'SmartScent',
        notes: document.getElementById('aromaNotes').value || null,
        schedule: {
            start: document.getElementById('aromaStart').value,
            end: document.getElementById('aromaEnd').value,
            intensity: document.getElementById('aromaIntensity').value,
        },
    };

    const btn = document.getElementById('aromaSubmitBtn');
    const original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';

    $.ajax({
        url: `{{ route('operational.job-schedules.save-scanned-unit', $jobSchedule->id) }}`,
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        data: payload,
        success: function(data) {
            Swal.fire({ icon: 'success', title: 'Berhasil', text: data.message, timer: 1600, showConfirmButton: false })
                .then(() => window.location.reload());
        },
        error: function(xhr) {
            btn.disabled = false;
            btn.innerHTML = original;
            const msg = xhr.responseJSON ? (xhr.responseJSON.message || 'Gagal menyimpan') : 'Terjadi kesalahan sistem';
            Swal.fire('Gagal', msg, 'error');
        }
    });
}

let baSignaturePad = null;

function openDoneJobBaModal() {
    const modalEl = document.getElementById('doneJobBaModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();

    // Init/resize signature pad after the modal is visible (canvas needs layout).
    modalEl.addEventListener('shown.bs.modal', function initPad() {
        const canvas = document.getElementById('baSignaturePad');
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = canvas.offsetHeight * ratio;
        canvas.getContext('2d').scale(ratio, ratio);
        if (!baSignaturePad) {
            baSignaturePad = new SignaturePad(canvas, { backgroundColor: 'rgba(255,255,255,0)' });
        } else {
            baSignaturePad.clear();
        }
    }, { once: true });
}

function clearBaSignature() {
    if (baSignaturePad) baSignaturePad.clear();
}

function submitDoneJobBa() {
    const picName = document.getElementById('baPicName').value.trim();
    if (!picName) {
        Swal.fire('Lengkapi Data', 'Nama PIC wajib diisi.', 'warning');
        return;
    }
    if (!baSignaturePad || baSignaturePad.isEmpty()) {
        Swal.fire('Lengkapi Data', 'Tanda tangan PIC wajib diisi.', 'warning');
        return;
    }
    const picPhoto = document.getElementById('baPicPhoto').files;
    if (!picPhoto.length) {
        Swal.fire('Lengkapi Data', 'Foto PIC wajib diunggah.', 'warning');
        return;
    }

    const fd = new FormData();
    fd.append('pic_name', picName);
    fd.append('signature', baSignaturePad.toDataURL('image/png'));
    fd.append('pic_photo', picPhoto[0]);
    const work = document.getElementById('baWorkPhotos').files;
    for (let i = 0; i < work.length; i++) fd.append('photos[]', work[i]);
    const notes = document.getElementById('baNotes').value;
    if (notes) fd.append('notes', notes);
    const cannotCompleteAllRooms = document.getElementById('baCannotCompleteAllRooms').checked;
    fd.append('cannot_complete_all_rooms', cannotCompleteAllRooms ? '1' : '0');

    const btn = document.getElementById('baSubmitBtn');
    const original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';

    $.ajax({
        url: `{{ route('operational.job-schedules.complete-with-ba', $jobSchedule->id) }}`,
        method: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {
            if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    html: data.message + (data.data && data.data.ba_number ? '<br><small>No. BA: <b>' + data.data.ba_number + '</b></small>' : ''),
                }).then(() => window.location.reload());
            } else {
                Swal.fire('Gagal', data.message || 'Gagal menyimpan BA', 'error');
                btn.disabled = false;
                btn.innerHTML = original;
            }
        },
        error: function(xhr) {
            const msg = xhr.responseJSON ? (xhr.responseJSON.message || 'Validasi gagal') : 'Terjadi kesalahan sistem';
            Swal.fire('Gagal', msg, 'error');
            btn.disabled = false;
            btn.innerHTML = original;
        }
    });
}
</script>
@endpush
