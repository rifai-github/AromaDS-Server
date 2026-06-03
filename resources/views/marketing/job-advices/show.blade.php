@extends('layouts.app')

@section('title', 'Job Advice Detail')
@section('breadcrumb', 'Home / Marketing / Job Advice / Detail')

@section('content')
<style>
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
    
    /* Tab Content */
    .tab-content {
        width: 100% !important;
        min-height: 500px !important;
    }
    
    .tab-pane {
        width: 100% !important;
        min-height: 500px !important;
        display: none !important;
    }
    
    .tab-pane.active {
        display: block !important;
    }
    
    .tab-pane.show.active {
        display: block !important;
    }
    
    /* Card Styles */
    .info-card {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        margin-bottom: 20px;
    }
    
    .info-card-header {
        background-color: #6c757d;
        color: white;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid rgba(0, 0, 0, 0.125);
        border-radius: 8px 8px 0 0;
    }
    
    .info-card-body {
        padding: 1.5rem;
    }
    
    .info-field {
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
    }
    
    .info-field-label {
        flex: 0 0 40%;
        font-weight: bold;
        color: #495057;
    }
    
    .info-field-value {
        flex: 0 0 60%;
        color: #6c757d;
    }
    
    /* Table Styles */
    .table th {
        background-color: #214589;
        color: white;
        font-weight: 600;
        padding: 12px;
        border: 1px solid #ddd;
        text-align: center;
        white-space: nowrap;
    }
    
    .table td {
        padding: 12px;
        vertical-align: middle;
        border: 1px solid #ddd;
    }
    
    .table-responsive {
        overflow-x: auto;
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
                            <a href="{{ route('marketing.job-advices.index') }}" class="btn btn-light btn-sm">
                                <i class="fas fa-arrow-left"></i> Back to List
                            </a>
                        </div>
                        <div>
                            <h3 class="card-title mb-0" style="color: white; font-size: 1.5rem; font-weight: bold;">
                                {{ $jobAdvice->job_advice_number }} - {{ ucfirst($jobAdvice->type) }}
                            </h3>
                        </div>
                        <div>
                            @if($jobAdvice->status === 'draft')
                                <button class="btn btn-warning btn-sm me-2" onclick="openEditModal()">
                                    <i class="fas fa-edit"></i> EDIT
                                </button>
                                <button class="btn btn-primary btn-sm me-2" onclick="finalizeJobAdvice()">
                                    <i class="fas fa-paper-plane"></i> Submit for Approve
                                </button>
                            @elseif($jobAdvice->status === 'waiting_for_approval')
                                @php
                                    $canApproveJobAdvice = auth()->user()->canApprove('job_advices');
                                @endphp
                                @if($canApproveJobAdvice)
                                    <button class="btn btn-success btn-sm me-2" onclick="approveJobAdvice()">
                                        <i class="fas fa-check"></i> APPROVE
                                    </button>
                                    <button class="btn btn-danger btn-sm me-2" onclick="cancelJobAdvice()">
                                        <i class="fas fa-times"></i> CANCEL
                                    </button>
                                @else
                                    <span class="badge bg-warning text-dark ms-2">Waiting for Approval</span>
                                @endif
                            @elseif($jobAdvice->status === 'approved')
                                <span class="badge bg-success me-2">Approved</span>
                                
                                @php
                                    // Check if we can show Cancel Request / Unpost buttons
                                    // Only show if all associated schedules are 'new_job' or 'scheduled'
                                    // OR if no schedules exist yet (to allow re-triggering after fix)
                                    $canCancelOrUnpost = true; // Default to true for approved
                                    if ($jobAdvice->jobSchedules && $jobAdvice->jobSchedules->count() > 0) {
                                        $nonNewJobSchedules = $jobAdvice->jobSchedules->whereNotIn('status', ['new_job', 'scheduled']);
                                        $canCancelOrUnpost = $nonNewJobSchedules->isEmpty();
                                    }
                                @endphp
                                
                                @if($canCancelOrUnpost)
                                    <button class="btn btn-warning btn-sm me-2" onclick="unpostJobAdvice()">
                                        <i class="fas fa-undo"></i> Unpost
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="cancelJobRequest()">
                                        <i class="fas fa-times-circle"></i> Cancel
                                    </button>
                                @endif
                            @elseif($jobAdvice->status === 'rejected')
                                <span class="badge bg-danger">Rejected</span>
                            @elseif($jobAdvice->status === 'cancelled')
                                <span class="badge bg-secondary me-2">Cancelled</span>
                                <button class="btn btn-danger btn-sm" onclick="deleteJobAdvice()">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            @endif
                            
                            {{-- Delete button for Draft status --}}
                            @if($jobAdvice->status === 'draft')
                                <button class="btn btn-danger btn-sm ms-2" onclick="deleteJobAdvice()">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Tabs -->
            <div class="card mb-3">
                <div class="card-body p-0">
                    <ul class="nav nav-tabs" id="jobAdviceTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="basic-info-tab" data-bs-toggle="tab" data-bs-target="#basic-info" type="button" role="tab">
                                <i class="fas fa-info-circle me-2"></i>BASIC INFO
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="rooms-tab" data-bs-toggle="tab" data-bs-target="#rooms" type="button" role="tab">
                                <i class="fas fa-door-open me-2"></i>ROOMS
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="rentals-tab" data-bs-toggle="tab" data-bs-target="#rentals" type="button" role="tab">
                                <i class="fas fa-boxes me-2"></i>RENTALS
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Tab Content -->
            <div class="tab-content" id="jobAdviceTabsContent">
                <!-- Basic Info Tab -->
                <div class="tab-pane fade show active" id="basic-info" role="tabpanel">
                    <div class="row justify-content-center">
                        <!-- Job Advice Basic Information (Sesuai JA.md) -->
                        <div class="col-lg-8">
                            <div class="info-card">
                                <div class="info-card-header">
                                    <h5 class="card-title mb-0">Job Advice Information</h5>
                                </div>
                                <div class="info-card-body">
                                    <div class="info-field">
                                        <div class="info-field-label">Job Advice No</div>
                                        <div class="info-field-value">{{ $jobAdvice->job_advice_number ?? 'N/A' }}</div>
                                    </div>
                                    <div class="info-field">
                                        <div class="info-field-label">Request By</div>
                                        <div class="info-field-value">{{ $jobAdvice->requestedBy->name ?? ($jobAdvice->submittedBy->name ?? 'N/A') }}</div>
                                    </div>
                                    <div class="info-field">
                                        <div class="info-field-label">PIC</div>
                                        <div class="info-field-value">
                                            {{ $jobAdvice->customerContact->name ?? '-' }}
                                            @if($jobAdvice->customerContact && $jobAdvice->customerContact->phone)
                                                <br><small class="text-muted"><i class="fas fa-phone-alt me-1"></i> {{ $jobAdvice->customerContact->phone }}</small>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="info-field">
                                        <div class="info-field-label">Status</div>
                                        <div class="info-field-value">
                                            @if($jobAdvice->status === 'draft')
                                                <span class="badge badge-warning">Draft</span>
                                            @elseif($jobAdvice->status === 'waiting_for_approval')
                                                <span class="badge badge-info">Waiting for Approval</span>
                                            @elseif($jobAdvice->status === 'approved')
                                                <span class="badge badge-success">Approved</span>
                                            @elseif($jobAdvice->status === 'rejected')
                                                <span class="badge badge-danger">Rejected</span>
                                            @elseif($jobAdvice->status === 'cancelled')
                                                <span class="badge badge-secondary">Cancelled</span>
                                            @else
                                                <span class="badge badge-secondary">{{ ucfirst($jobAdvice->status) }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="info-field">
                                        <div class="info-field-label">Job Advice Type</div>
                                        <div class="info-field-value">{{ $jobAdvice->type ?? 'N/A' }}</div>
                                    </div>
                                    <div class="info-field">
                                        <div class="info-field-label">Reference No (Quotation)</div>
                                        <div class="info-field-value">{{ $jobAdvice->reference_number ?? ($jobAdvice->contract->quotation->quotation_number ?? 'N/A') }}</div>
                                    </div>
                                    <div class="info-field">
                                        <div class="info-field-label">Expected Date</div>
                                        <div class="info-field-value">{{ $jobAdvice->expected_date ? $jobAdvice->expected_date->format('d/M/Y') : 'N/A' }}</div>
                                    </div>
                                    <div class="info-field">
                                        <div class="info-field-label">Remove Date</div>
                                        <div class="info-field-value">{{ $jobAdvice->remove_date ? $jobAdvice->remove_date->format('d/M/Y') : 'N/A' }}</div>
                                    </div>
                                    <div class="info-field">
                                        <div class="info-field-label">Catatan Tambahan</div>
                                        <div class="info-field-value" style="white-space: pre-wrap;">{{ $jobAdvice->notes ?? '-' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rooms Tab -->
                <div class="tab-pane fade" id="rooms" role="tabpanel">
                    <div class="card">
                        <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #1e3a8a;">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0" style="color: #1e3a8a;">
                                    <i class="fas fa-door-open me-2"></i>
                                    Job Advice Rooms ({{ ($jobAdvice->rooms ?? collect())->groupBy(function($item) {
                                        return $item->contract_room_id ? 'c_' . $item->contract_room_id : ($item->quotation_room_id ? 'q_' . $item->quotation_room_id : 'n_' . $item->room_name);
                                    })->count() }})
                                </h5>
                                @if($jobAdvice->status === 'draft')
                                    <button class="btn btn-primary btn-sm" onclick="openAddRoomModal()">
                                        <i class="fas fa-plus me-1"></i> Add/Choose Rooms
                                    </button>
                                @endif
                            </div>
                        </div>
                        <div class="card-body p-0">
                            @php
                                // MOM9: Check both contract room and quotation room
                                $hasEmptyRoomData = false;
                                foreach($jobAdvice->rooms ?? [] as $jr) {
                                    $hasRoom = false;
                                    if ($jr->contractRoom && $jr->contractRoom->room) {
                                        $hasRoom = true;
                                    } elseif ($jr->quotationRoom && $jr->quotationRoom->room) {
                                        $hasRoom = true;
                                    }
                                    if (!$hasRoom) {
                                        $hasEmptyRoomData = true;
                                        break;
                                    }
                                }
                            @endphp
                            
                            @if($hasEmptyRoomData)
                            <div class="alert alert-warning m-3">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Perhatian:</strong> Beberapa data room tidak memiliki data survey lengkap di Master Rooms. 
                                Pastikan room yang dipilih sudah memiliki data survey (jenis, lantai, dimensi, dll) di Master Rooms.
                            </div>
                            @endif
                            
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Nama Perusahaan</th>
                                            <th>Gedung</th>
                                            <th>Nama Ruangan</th>
                                            <th>Aroma / Variant</th>
                                            <th>Temperature</th>
                                            <th>Jenis</th>
                                            <th>Lantai</th>
                                            <th>Wangi</th>
                                            <th>Type AHU</th>
                                            <th>AC Qty</th>
                                            <th>Tinggi</th>
                                            <th>Lebar</th>
                                            <th>Panjang</th>
                                            <th>Remark</th>
                                            <th>Terakhir Update</th>
                                            <th>Oleh</th>
                                            @if($jobAdvice->status === 'draft')
                                                <th>Action</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            // Group rooms by unique room identifier
                                            $groupedRooms = ($jobAdvice->rooms ?? collect())->groupBy(function($item) {
                                                return $item->contract_room_id ? 'c_' . $item->contract_room_id : ($item->quotation_room_id ? 'q_' . $item->quotation_room_id : 'n_' . $item->room_name);
                                            });
                                            $roomCounter = 1;
                                        @endphp

                                        @forelse($groupedRooms as $groupKey => $roomsInGroup)
                                        @php
                                            // Extract data from the first room in the group for display
                                            $jaRoom = $roomsInGroup->first();
                                            $contractRoom = $jaRoom->contractRoom ?? null;
                                            $quotationRoom = $jaRoom->quotationRoom ?? null;
                                            $room = null;
                                            $roomSpecs = null;
                                            
                                            if ($contractRoom) {
                                                $room = $contractRoom->room;
                                            } elseif ($quotationRoom) {
                                                $room = $quotationRoom->room;
                                                if (!$room && $quotationRoom->room_specifications) {
                                                    $roomSpecs = is_string($quotationRoom->room_specifications) 
                                                        ? json_decode($quotationRoom->room_specifications, true) 
                                                        : $quotationRoom->room_specifications;
                                                }
                                            }
                                            
                                            $building = null;
                                            if ($contractRoom && $contractRoom->building) {
                                                $building = $contractRoom->building;
                                            } elseif ($quotationRoom && $room?->building) {
                                                // MOM: For quotation room, get building from room directly (per-room, not global survey)
                                                $building = $room->building;
                                            } elseif ($room?->building) {
                                                $building = $room->building;
                                            } elseif ($jobAdvice->contract?->quotation?->survey?->building) {
                                                $building = $jobAdvice->contract->quotation->survey->building;
                                            } elseif ($jobAdvice->quotation?->survey?->building) {
                                                $building = $jobAdvice->quotation->survey->building;
                                            }
                                            
                                            $customer = $jobAdvice->contract?->customer ?? $jobAdvice->quotation?->customer ?? $jobAdvice->quotation?->prospect ?? null;
                                            $roomName = $room?->room_name ?? $jaRoom->room_name ?? 'N/A';
                                            
                                            // Aroma display (usually same for group)
                                            $aromaProduct = null;
                                            $aromaVariant = null;
                                            if ($quotationRoom) {
                                                $aromaProduct = $quotationRoom->aromaProduct;
                                                $aromaVariant = $quotationRoom->aroma_variant;
                                            } elseif ($jobAdvice->contract?->quotation) {
                                                $quotation = $jobAdvice->contract->quotation;
                                                $foundQuotationRoom = $room ? $quotation->quotationRooms->where('room_id', $room->id)->first() : null;
                                                if (!$foundQuotationRoom) {
                                                    $foundQuotationRoom = $quotation->quotationRooms->where('room_name', $roomName)->first();
                                                }
                                                if ($foundQuotationRoom) {
                                                    $aromaProduct = $foundQuotationRoom->aromaProduct;
                                                    $aromaVariant = $foundQuotationRoom->aroma_variant;
                                                }
                                            } elseif ($jobAdvice->quotation) {
                                                $quotation = $jobAdvice->quotation;
                                                $foundQuotationRoom = $room ? $quotation->quotationRooms->where('room_id', $room->id)->first() : null;
                                                if (!$foundQuotationRoom) {
                                                    $foundQuotationRoom = $quotation->quotationRooms->where('room_name', $roomName)->first();
                                                }
                                                if ($foundQuotationRoom) {
                                                    $aromaProduct = $foundQuotationRoom->aromaProduct;
                                                    $aromaVariant = $foundQuotationRoom->aroma_variant;
                                                }
                                            }
                                        @endphp
                                        <tr>
                                            <td class="text-center">{{ $roomCounter++ }}</td>
                                            <td>{{ $customer?->name ?? 'N/A' }}</td>
                                            <td>{{ $building?->nama_gedung ?? $building?->name ?? 'N/A' }}</td>
                                            <td>{{ $roomName }}</td>
                                            <td>
                                                @if($aromaProduct)
                                                    <span class="text-success">
                                                        <i class="fas fa-leaf me-1"></i>
                                                        {{ $aromaProduct->name ?? 'N/A' }}
                                                        @if($aromaProduct->variant_name)
                                                            - {{ $aromaProduct->variant_name }}
                                                        @elseif($aromaVariant)
                                                            - {{ $aromaVariant }}
                                                        @endif
                                                    </span>
                                                @elseif($aromaVariant)
                                                    <span class="text-success">
                                                        <i class="fas fa-leaf me-1"></i>
                                                        {{ $aromaVariant }}
                                                    </span>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @php
                                                    $temperature = $room?->room_temperature ?? null;
                                                    if (!$temperature && $jobAdvice->quotation?->survey && $room) {
                                                        $surveyDetail = $jobAdvice->quotation->survey->surveyDetails->where('room_id', $room->id)->first();
                                                        if ($surveyDetail && $surveyDetail->specifications) {
                                                            $specs = is_string($surveyDetail->specifications) ? json_decode($surveyDetail->specifications, true) : $surveyDetail->specifications;
                                                            $temperature = $specs['temperature'] ?? null;
                                                        }
                                                    }
                                                    if (!$temperature && $quotationRoom && $quotationRoom->room_specifications) {
                                                        $specs = is_string($quotationRoom->room_specifications) ? json_decode($quotationRoom->room_specifications, true) : $quotationRoom->room_specifications;
                                                        $temperature = $specs['temperature'] ?? null;
                                                    }
                                                @endphp
                                                @if($temperature)
                                                    <span class="badge bg-info">
                                                        <i class="fas fa-thermometer-half me-1"></i>
                                                        {{ $temperature }}°C
                                                    </span>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>{{ $room?->room_type ?? $roomSpecs['room_type'] ?? 'N/A' }}</td>
                                            <td class="text-center">{{ $room?->room_floor ?? $roomSpecs['floor'] ?? $roomSpecs['room_floor'] ?? '-' }}</td>
                                            <td>{{ $room?->room_intensity ?? $roomSpecs['intensity'] ?? $roomSpecs['room_intensity'] ?? '-' }}</td>
                                            <td>{{ $room?->room_installation_type ?? $roomSpecs['installation_type'] ?? $roomSpecs['room_installation_type'] ?? '-' }}</td>
                                            <td class="text-center">{{ $room?->room_qty ?? $roomSpecs['qty'] ?? $roomSpecs['room_qty'] ?? '-' }}</td>
                                            <td class="text-right">{{ ($room && $room->room_height) ? $room->room_height . ' m' : (isset($roomSpecs['height']) || isset($roomSpecs['room_height']) ? ($roomSpecs['height'] ?? $roomSpecs['room_height']) . ' m' : '-') }}</td>
                                            <td class="text-right">{{ ($room && $room->room_width) ? $room->room_width . ' m' : (isset($roomSpecs['width']) || isset($roomSpecs['room_width']) ? ($roomSpecs['width'] ?? $roomSpecs['room_width']) . ' m' : '-') }}</td>
                                            <td class="text-right">{{ ($room && $room->room_length) ? $room->room_length . ' m' : (isset($roomSpecs['length']) || isset($roomSpecs['room_length']) ? ($roomSpecs['length'] ?? $roomSpecs['room_length']) . ' m' : '-') }}</td>
                                            <td>{{ $room?->room_remark ?? $roomSpecs['remark'] ?? $roomSpecs['room_remark'] ?? '-' }}</td>
                                            <td class="text-center">{{ $jaRoom->updated_at ? $jaRoom->updated_at->format('d/M/Y H:i') : 'N/A' }}</td>
                                            <td>{{ $jaRoom->updater?->name ?? 'N/A' }}</td>
                                            @if($jobAdvice->status === 'draft')
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-danger btn-sm" onclick="deleteRoom({{ $jaRoom->id }}, '{{ $roomName }}')" title="Remove All Rentals in this Room">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            @endif
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="18" class="text-center text-muted py-4">
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
                <div class="tab-pane fade" id="rentals" role="tabpanel">
                    <div class="card">
                        <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #1e3a8a;">
                            <h5 class="card-title mb-0" style="color: #1e3a8a;">
                                <i class="fas fa-boxes me-2"></i>
                                Job Advice Rentals ({{ $jobAdvice->rooms->count() }})
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Job Advice No</th>
                                            <th>Nama Ruangan</th>
                                            <th>Action</th>
                                            <th>Nama Rental</th>
                                            <th>Qty</th>
                                            <th>Rental Price</th>
                                            <th>Nama Perusahaan</th>
                                            <th>Gedung</th>
                                            <th>Jenis Ruangan</th>
                                            <th>Lantai</th>
                                            <th>Wangi</th>
                                            <th>Tinggi</th>
                                            <th>Lebar</th>
                                            <th>Panjang</th>
                                            <th>Install Type</th>
                                            <th>AC Qty</th>
                                            <th>Remark</th>
                                            <th>Terakhir Update</th>
                                            <th>Oleh</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($jobAdvice->rooms ?? [] as $index => $jaRoom)
                                        @php
                                            // MOM9: Handle both contract room and quotation room
                                            $contractRoom = $jaRoom->contractRoom ?? null;
                                            $quotationRoom = $jaRoom->quotationRoom ?? null;
                                            $room = null;
                                            
                                            if ($contractRoom) {
                                                $room = $contractRoom->room;
                                            } elseif ($quotationRoom) {
                                                $room = $quotationRoom->room;
                                            }
                                            
                                            // MOM9: Get building from contract or quotation
                                            $building = null;
                                            if ($contractRoom && $contractRoom->building) {
                                                $building = $contractRoom->building; // Using accessor
                                            } elseif ($quotationRoom && $room?->building) {
                                                // MOM: For quotation room, prioritize room.building (per-room) over survey building (global)
                                                $building = $room->building;
                                            } elseif ($room?->building) {
                                                $building = $room->building; // Fallback per room
                                            } elseif ($jobAdvice->contract?->quotation?->survey?->building) {
                                                $building = $jobAdvice->contract->quotation->survey->building;
                                            } elseif ($jobAdvice->quotation?->survey?->building) {
                                                // MOM9: Get building from quotation only as last resort
                                                $building = $jobAdvice->quotation->survey->building;
                                            }
                                            
                                            // MOM9: Get customer from contract or quotation
                                            $customer = $jobAdvice->contract?->customer ?? $jobAdvice->quotation?->customer ?? $jobAdvice->quotation?->prospect ?? null;
                                            $rental = $jaRoom->rentalProduct;
                                            
                                            // Use snapshot data from job_advice_rooms if master_rooms is empty
                                            $roomName = $room?->room_name ?? $jaRoom->room_name ?? 'N/A';
                                            
                                            // MOM9: Get rental_alias from contract rental or quotation detail
                                            $rentalAlias = null;
                                            
                                            // For contract-based job advice
                                            if ($jobAdvice->contract && $jaRoom->rental_product_id) {
                                                // Try to find contract rental by master_rental_id and room_id
                                                $contractRental = $jobAdvice->contract->contractRentals
                                                    ->where('master_rental_id', $jaRoom->rental_product_id)
                                                    ->where('room_id', $room?->id)
                                                    ->first();
                                                
                                                if ($contractRental && $contractRental->rental_alias) {
                                                    $rentalAlias = $contractRental->rental_alias;
                                                } else {
                                                    // Fallback: try to find from quotation detail through contract
                                                    if ($jobAdvice->contract->quotation) {
                                                        $quotationDetail = $jobAdvice->contract->quotation->quotationDetails
                                                            ->where('master_rental_id', $jaRoom->rental_product_id)
                                                            ->where('room_name', $roomName)
                                                            ->first();
                                                        
                                                        if ($quotationDetail && $quotationDetail->rental_alias) {
                                                            $rentalAlias = $quotationDetail->rental_alias;
                                                        }
                                                    }
                                                }
                                            }
                                            
                                            // MOM9: For quotation-based job advice, get rental_alias and price from quotation detail
                                            $quotationDetail = null;
                                            if ($jobAdvice->quotation && $jaRoom->rental_product_id) {
                                                $quotationDetail = $jobAdvice->quotation->quotationDetails
                                                    ->where('master_rental_id', $jaRoom->rental_product_id)
                                                    ->where('room_name', $roomName)
                                                    ->first();
                                                
                                                if ($quotationDetail && $quotationDetail->rental_alias) {
                                                    $rentalAlias = $quotationDetail->rental_alias;
                                                }
                                            }
                                            
                                            // Use rental_alias if available, otherwise use default rental name
                                            $rentalName = $rentalAlias ?? ($rental?->rental_name ?? $jaRoom->rental_name ?? 'N/A');
                                            
                                            // MOM9: Get rental price from quotation detail or quotation rental if available
                                            $rentalPrice = null;
                                            if ($quotationDetail && $quotationDetail->unit_price) {
                                                $rentalPrice = $quotationDetail->unit_price;
                                            } elseif ($jobAdvice->quotation && $quotationRoom) {
                                                // Try to get from quotation_rentals
                                                $quotationRental = \App\Models\QuotationRental::where('quotation_room_id', $quotationRoom->id)
                                                    ->where('master_rental_id', $jaRoom->rental_product_id)
                                                    ->first();
                                                
                                                if ($quotationRental && $quotationRental->unit_price) {
                                                    $rentalPrice = $quotationRental->unit_price;
                                                }
                                            }
                                            
                                            // MOM9: Get aroma from quotation room (for wangi column)
                                            $aromaProduct = null;
                                            $aromaVariant = null;
                                            
                                            // If we have quotationRoom directly, use it
                                            if ($quotationRoom) {
                                                $aromaProduct = $quotationRoom->aromaProduct;
                                                $aromaVariant = $quotationRoom->aroma_variant;
                                            }
                                            // Otherwise, try to find from quotation through contract
                                            elseif ($jobAdvice->contract?->quotation && $room) {
                                                $quotation = $jobAdvice->contract->quotation;
                                                $foundQuotationRoom = $quotation->quotationRooms
                                                    ->where('room_id', $room->id)
                                                    ->first();
                                                
                                                if (!$foundQuotationRoom) {
                                                    $foundQuotationRoom = $quotation->quotationRooms
                                                        ->where('room_name', $room->room_name)
                                                        ->first();
                                                }
                                                
                                                if (!$foundQuotationRoom && $roomName) {
                                                    $foundQuotationRoom = $quotation->quotationRooms
                                                        ->where('room_name', $roomName)
                                                        ->first();
                                                }
                                                
                                                if ($foundQuotationRoom) {
                                                    $aromaProduct = $foundQuotationRoom->aromaProduct;
                                                    $aromaVariant = $foundQuotationRoom->aroma_variant;
                                                }
                                            }
                                            // MOM9: Try to find from quotation directly (for job advice from quotation)
                                            elseif ($jobAdvice->quotation && $room) {
                                                $quotation = $jobAdvice->quotation;
                                                $foundQuotationRoom = $quotation->quotationRooms
                                                    ->where('room_id', $room->id)
                                                    ->first();
                                                
                                                if (!$foundQuotationRoom) {
                                                    $foundQuotationRoom = $quotation->quotationRooms
                                                        ->where('room_name', $room->room_name)
                                                        ->first();
                                                }
                                                
                                                if (!$foundQuotationRoom && $roomName) {
                                                    $foundQuotationRoom = $quotation->quotationRooms
                                                        ->where('room_name', $roomName)
                                                        ->first();
                                                }
                                                
                                                if ($foundQuotationRoom) {
                                                    $aromaProduct = $foundQuotationRoom->aromaProduct;
                                                    $aromaVariant = $foundQuotationRoom->aroma_variant;
                                                }
                                            }
                                        @endphp
                                        <tr>
                                            <td class="text-center">{{ $index + 1 }}</td>
                                            <td>{{ $jobAdvice->job_advice_number ?? 'N/A' }}</td>
                                            <td>{{ $roomName }}</td>
                                            <td>{{ ucfirst($jobAdvice->type) }}</td>
                                            <td>
                                                @if(strtolower($jobAdvice->type) === 'change_rental')
                                                    <div>
                                                        <div class="mb-1">
                                                            <small class="text-muted">Current:</small><br>
                                                            <span class="badge bg-secondary text-wrap text-start" style="width: 100%;">
                                                                {{ optional($contractRoom->rentalProduct)->rental_name ?? 'N/A' }}
                                                            </span>
                                                        </div>
                                                        <div class="mb-1">
                                                            <small class="text-muted">New:</small><br>
                                                            <span class="badge bg-success text-wrap text-start" style="width: 100%;">
                                                                {{ optional($jaRoom->rentalProduct)->rental_name ?? 'Not Selected' }}
                                                            </span>
                                                        </div>
                                                        @if($jobAdvice->status === 'draft')
                                                            <span class="badge bg-primary mt-1 w-100 p-2 rounded-pill text-uppercase" 
                                                                  style="cursor: pointer; display: inline-block; box-shadow: 0 2px 4px rgba(0,0,0,0.1); background-color: #0d6efd !important; color: white !important;"
                                                                  onclick="openChangeRentalModal({{ $jaRoom->id }}, {{ $jaRoom->rental_product_id ?? 'null' }}, {{ $jaRoom->quantity ?? 1 }})"
                                                                  title="Change Rental">
                                                                <i class="fas fa-exchange-alt me-1"></i> Change Rental
                                                            </span>
                                                        @endif
                                                    </div>
                                                @else
                                                    {{ $rentalName }}
                                                @endif
                                            </td>
                                            <td class="text-center">{{ $jaRoom->quantity ?? 1 }}</td>
                                            <td class="text-right">
                                                @php
                                                    // MOM9: Use price from quotation detail/rental if available, otherwise from master rental
                                                    $displayPrice = $rentalPrice ?? ($rental?->monthly_price ?? null);
                                                @endphp
                                                {{ $displayPrice ? 'Rp ' . number_format($displayPrice, 0, ',', '.') : '-' }}
                                            </td>
                                            <td>{{ $customer?->name ?? 'N/A' }}</td>
                                            <td>{{ $building?->nama_gedung ?? $building?->name ?? 'N/A' }}</td>
                                            <td>{{ $room?->room_type ?? $roomSpecs['room_type'] ?? 'N/A' }}</td>
                                            <td class="text-center">{{ $room?->room_floor ?? $roomSpecs['floor'] ?? $roomSpecs['room_floor'] ?? '-' }}</td>
                                            <td>
                                                {{-- Display Aroma/Variant from Quotation Room --}}
                                                @if($aromaProduct)
                                                    <span class="text-success">
                                                        <i class="fas fa-leaf me-1"></i>
                                                        {{ $aromaProduct->name ?? 'N/A' }}
                                                        @if($aromaProduct->variant_name)
                                                            - {{ $aromaProduct->variant_name }}
                                                        @elseif($aromaVariant)
                                                            - {{ $aromaVariant }}
                                                        @endif
                                                    </span>
                                                @elseif($aromaVariant)
                                                    <span class="text-success">
                                                        <i class="fas fa-leaf me-1"></i>
                                                        {{ $aromaVariant }}
                                                    </span>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="text-right">{{ ($room && $room->room_height) ? $room->room_height . ' m' : (isset($roomSpecs['height']) || isset($roomSpecs['room_height']) ? ($roomSpecs['height'] ?? $roomSpecs['room_height']) . ' m' : '-') }}</td>
                                            <td class="text-right">{{ ($room && $room->room_width) ? $room->room_width . ' m' : (isset($roomSpecs['width']) || isset($roomSpecs['room_width']) ? ($roomSpecs['width'] ?? $roomSpecs['room_width']) . ' m' : '-') }}</td>
                                            <td class="text-right">{{ ($room && $room->room_length) ? $room->room_length . ' m' : (isset($roomSpecs['length']) || isset($roomSpecs['room_length']) ? ($roomSpecs['length'] ?? $roomSpecs['room_length']) . ' m' : '-') }}</td>
                                            <td>{{ $room?->room_installation_type ?? $roomSpecs['installation_type'] ?? $roomSpecs['room_installation_type'] ?? '-' }}</td>
                                            <td class="text-center">{{ $room?->room_qty ?? '-' }}</td>
                                            <td>{{ $room?->room_remark ?? '-' }}</td>
                                            <td class="text-center">{{ $jaRoom->updated_at ? $jaRoom->updated_at->format('d/M/Y H:i') : 'N/A' }}</td>
                                            <td>{{ $jaRoom->updater?->name ?? 'N/A' }}</td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="20" class="text-center text-muted py-4">
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
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    console.log('Job Advice show page loaded');
    
    // Initialize tab visibility
    $('.tab-pane').removeClass('show active').css('display', 'none');
    $('#basic-info').addClass('show active').css('display', 'block');
    
    // Tab switching functionality
    $('#jobAdviceTabs button[data-bs-toggle="tab"]').on('click', function(e) {
        e.preventDefault();
        var target = $(this).attr('data-bs-target');
        
        // Remove active class from all tabs
        $('#jobAdviceTabs button').removeClass('active').css({
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
    });
});

// Finalize Job Advice (Renamed to Submit for Approve)
function finalizeJobAdvice() {
    // Validation: Check if rooms exist
    // We check using PHP injected variable first or check DOM elements if needed
    const roomCount = {{ $jobAdvice->rooms->count() }};
    
    if (roomCount === 0) {
        Swal.fire({
            icon: 'error',
            title: 'Room Empty',
            text: 'Tidak bisa submit for approve karena Room kosong. Silakan tambahkan room terlebih dahulu.',
            confirmButtonColor: '#3085d6'
        });
        return;
    }

    Swal.fire({
        title: 'Submit for Approve?',
        html: `
            <p>Apakah Anda yakin ingin <strong>Submit for Approve</strong> Job Advice ini?</p>
            <p class="text-sm text-gray-600 mt-2">Status akan berubah menjadi <strong>Waiting for Approval</strong>.</p>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="fas fa-paper-plane"></i> Ya, Submit',
        cancelButtonText: 'Batal',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return fetch(`/marketing/job-advices/{{ $jobAdvice->id }}/finalize`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' || data.success) {
                    return data;
                }
                throw new Error(data.message || 'Failed to submit');
            })
            .catch(error => {
                Swal.showValidationMessage(`Request failed: ${error.message}`);
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                icon: 'success',
                title: 'Submitted!',
                text: 'Job Advice telah disubmit dan menunggu approval.',
                confirmButtonColor: '#3085d6',
            }).then(() => {
                location.reload();
            });
        }
    });
}

// Save to Draft (Revert from Waiting for Approval)
function saveToDraft() {
    Swal.fire({
        title: 'Save to Draft?',
        html: `
            <p>Apakah Anda yakin ingin mengembalikan status Job Advice ini menjadi <strong>Draft</strong>?</p>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f59e0b',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="fas fa-file-alt"></i> Ya, Save to Draft',
        cancelButtonText: 'Batal',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return fetch(`/marketing/job-advices/{{ $jobAdvice->id }}/revert-to-draft`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' || data.success) {
                    return data;
                }
                throw new Error(data.message || 'Failed to save to draft');
            })
            .catch(error => {
                Swal.showValidationMessage(`Request failed: ${error.message}`);
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                icon: 'success',
                title: 'Saved to Draft!',
                text: 'Status Job Advice telah kembali menjadi Draft.',
                confirmButtonColor: '#3085d6',
            }).then(() => {
                location.reload();
            });
        }
    });
}

// Approve Job Advice (Manager only)
function approveJobAdvice() {
    Swal.fire({
        title: 'Approve Job Advice',
        html: `
            <p>Apakah Anda yakin ingin <strong>menyetujui</strong> Job Advice ini?</p>
            <p class="text-sm text-gray-600 mt-2">Setelah disetujui, Job Schedule akan otomatis dibuat.</p>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="fas fa-check"></i> Ya, Approve',
        cancelButtonText: 'Batal',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return fetch(`/marketing/job-advices/{{ $jobAdvice->id }}/approve`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' || data.success) {
                    return data;
                }
                throw new Error(data.message || 'Failed to approve');
            })
            .catch(error => {
                Swal.showValidationMessage(`Request failed: ${error.message}`);
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                icon: 'success',
                title: 'Approved!',
                text: 'Job Advice telah disetujui.',
                confirmButtonColor: '#3085d6',
            }).then(() => {
                location.reload();
            });
        }
    });
}

// Cancel Job Advice (Manager Reject/Cancel)
function cancelJobAdvice() {
    Swal.fire({
        title: 'Cancel Job Advice',
        html: `
            <p>Apakah Anda yakin ingin <strong>membatalkan</strong> Job Advice ini?</p>
            <div class="mt-4">
                <label class="block text-left text-sm font-medium text-gray-700 mb-2">Alasan Pembatalan:</label>
                <textarea id="cancellation_reason" class="w-full px-3 py-2 border border-gray-300 rounded-md" rows="3" placeholder="Masukkan alasan pembatalan..."></textarea>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="fas fa-times"></i> Ya, Batalkan',
        cancelButtonText: 'Batal',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            const reason = document.getElementById('cancellation_reason').value;
            if (!reason) {
                Swal.showValidationMessage('Alasan pembatalan harus diisi!');
                return false;
            }
            
            return fetch(`/marketing/job-advices/{{ $jobAdvice->id }}/cancel`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    cancellation_reason: reason
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' || data.success) {
                    return data;
                }
                throw new Error(data.message || 'Failed to cancel');
            })
            .catch(error => {
                Swal.showValidationMessage(`Request failed: ${error.message}`);
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                icon: 'success',
                title: 'Cancelled!',
                text: 'Job Advice telah dibatalkan.',
                confirmButtonColor: '#3085d6',
            }).then(() => {
                location.reload();
            });
        }
    });
}

// Cancel Job Request (Undo Approval)
function cancelJobRequest() {
    Swal.fire({
        title: 'Cancel Job Request',
        html: `
            <p>Apakah Anda yakin ingin <strong>membatalkan request</strong> ini?</p>
            <p class="text-sm text-gray-600 mt-2">Job Schedule dengan status New Job akan dihapus.</p>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="fas fa-undo-alt"></i> Ya, Cancel Request',
        cancelButtonText: 'Batal',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return fetch(`/marketing/job-advices/{{ $jobAdvice->id }}/cancel-request`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' || data.success) {
                    return data;
                }
                throw new Error(data.message || 'Failed to cancel request');
            })
            .catch(error => {
                Swal.showValidationMessage(`Request failed: ${error.message}`);
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            const message = result.value.message || 'Job Request berhasil dibatalkan.';
            Swal.fire({
                icon: 'success',
                title: 'Cancelled!',
                text: message,
                confirmButtonColor: '#3085d6',
            }).then(() => {
                location.reload();
            });
        }
    });
}

// Unpost Job Advice - Revert approved back to draft
function unpostJobAdvice() {
    Swal.fire({
        title: 'Unpost Job Advice?',
        html: `
            <p>Apakah Anda yakin ingin <strong>mengembalikan</strong> Job Advice ini ke status <strong>Draft</strong>?</p>
            <p class="text-sm text-gray-600 mt-2">Semua Job Schedule yang terkait akan dihapus.</p>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f59e0b',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="fas fa-undo"></i> Ya, Unpost',
        cancelButtonText: 'Batal',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return fetch(`/marketing/job-advices/{{ $jobAdvice->id }}/unpost`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' || data.success) {
                    return data;
                }
                throw new Error(data.message || 'Failed to unpost');
            })
            .catch(error => {
                Swal.showValidationMessage(`Request failed: ${error.message}`);
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            const message = result.value.message || 'Job Advice berhasil di-unpost ke Draft.';
            Swal.fire({
                icon: 'success',
                title: 'Unposted!',
                text: message,
                confirmButtonColor: '#3085d6',
            }).then(() => {
                location.reload();
            });
        }
    });
}

// Delete Job Advice (for cancelled or draft status)
function deleteJobAdvice() {
    Swal.fire({
        title: 'Hapus Job Advice?',
        html: `
            <p>Apakah Anda yakin ingin <strong>menghapus</strong> Job Advice ini?</p>
            <p class="text-sm text-red-500 mt-2"><strong>Peringatan:</strong> Data yang dihapus tidak dapat dikembalikan!</p>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="fas fa-trash"></i> Ya, Hapus',
        cancelButtonText: 'Batal',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return fetch(`/marketing/job-advices/{{ $jobAdvice->id }}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                // Handle redirect response (success usually redirects)
                if (response.redirected) {
                    window.location.href = response.url;
                    return { status: 'success', redirected: true };
                }
                return response.json();
            })
            .then(data => {
                if (data.redirected) return data;
                if (data.status === 'success' || data.success) {
                    return data;
                }
                throw new Error(data.message || 'Failed to delete');
            })
            .catch(error => {
                Swal.showValidationMessage(`Request failed: ${error.message}`);
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed && !result.value.redirected) {
            Swal.fire({
                icon: 'success',
                title: 'Deleted!',
                text: 'Job Advice berhasil dihapus.',
                confirmButtonColor: '#3085d6',
            }).then(() => {
                window.location.href = '/marketing/job-advices';
            });
        }
    });
}

// Add/Choose Rooms Modal
function openAddRoomModal() {
    // Show loading
    Swal.fire({
        title: 'Loading Rooms...',
        html: 'Mengambil daftar ruangan...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Check if job advice is from contract or quotation
    const contractId = {{ $jobAdvice->contract_id ?? 'null' }};
    const quotationId = {{ $jobAdvice->quotation_id ?? 'null' }};
    const jobType = '{{ strtolower($jobAdvice->type) }}'; // Use server-side parsed type
    const currentJobAdviceId = {{ $jobAdvice->id }};
    
    let apiUrl = '';
    if (contractId) {
        apiUrl = `/api/contracts/${contractId}/for-job-advice?job_advice_id=${currentJobAdviceId}&type=${jobType}`;
    } else if (quotationId) {
        apiUrl = `/api/quotations/${quotationId}/for-job-advice?job_advice_id=${currentJobAdviceId}&type=${jobType}`;
    } else {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Job Advice tidak memiliki contract atau quotation yang valid.',
            confirmButtonColor: '#3085d6'
        });
        return;
    }
    
    // Fetch rooms (contract or quotation)
    fetch(apiUrl, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        const contractRooms = data.contract_rooms || [];
        const brokenRooms = data.broken_rooms || 0;
        const warningMessage = data.message || null;
        
        // Show warning if there are broken rooms
        if (brokenRooms > 0 && warningMessage) {
            console.warn(warningMessage);
        }
        
        if (contractRooms.length === 0) {
            const isQuotation = {{ $jobAdvice->quotation_id ? 'true' : 'false' }};
            const sourceType = isQuotation ? 'Quotation' : 'Contract';
            let errorText = `${sourceType} ini tidak memiliki ruangan yang bisa ditambahkan.`;
            if (brokenRooms > 0) {
                errorText += `\n\n${warningMessage}\n\nSilakan perbaiki data ${isQuotation ? 'quotation_rooms' : 'contract_rooms'} terlebih dahulu.`;
            }
            
            Swal.fire({
                icon: 'warning',
                title: 'No Valid Rooms Found',
                html: errorText.replace(/\n/g, '<br>'),
                confirmButtonColor: '#3085d6',
                footer: brokenRooms > 0 ? `<a href="#" onclick="alert('Hubungi admin untuk memperbaiki data ${isQuotation ? 'quotation_rooms' : 'contract_rooms'}')">Butuh bantuan?</a>` : ''
            });
            return;
        }
        
        // Get already added room IDs (check both contract_room_id and quotation_room_id)
        const addedRoomIds = [
            @foreach($jobAdvice->rooms ?? [] as $jaRoom)
                @if($jaRoom->contract_room_id)
                    {{ $jaRoom->contract_room_id }},
                @endif
                @if($jaRoom->quotation_room_id)
                    {{ $jaRoom->quotation_room_id }},
                @endif
            @endforeach
        ];
        
        // Build rooms checklist HTML
        let roomsHtml = '<div style="text-align: left; max-height: 400px; overflow-y: auto;">';
        
        // Show warning if there are broken rooms
        const isQuotation = {{ $jobAdvice->quotation_id ? 'true' : 'false' }};
        const sourceType = isQuotation ? 'Quotation' : 'Contract';
        if (brokenRooms > 0) {
            roomsHtml += `
                <div class="alert alert-warning mb-3" style="font-size: 0.9em;">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Perhatian:</strong> ${sourceType} ini memiliki ${brokenRooms} ruangan yang tidak valid.
                    <br><small>Hanya menampilkan ${contractRooms.length} ruangan yang valid.</small>
                </div>
            `;
        }
        
        roomsHtml += '<p class="mb-3"><strong>Pilih ruangan yang ingin ditambahkan:</strong></p>';
        
        let availableRoomsCount = 0;
        
        contractRooms.forEach((contractRoom, index) => {
            const room = contractRoom.room || {};
            const building = room.building || {};
            const isAdded = addedRoomIds.includes(contractRoom.id);
            const rentals = contractRoom.rentals || [];
            const rentalCount = contractRoom.rental_count || rentals.length || 0;
            
            // Check if room is used in another JA - FIX ISSUE 2: Skip completely instead of showing
            const isUsedInOtherJa = contractRoom.is_used_in_other_ja || false;
            
            // FIX ISSUE 2: Completely skip rooms used in other JA (don't show them at all)
            if (isUsedInOtherJa) {
                return; // Skip this room
            }
            
            // Only count rooms that are added in THIS JA (not skip entirely)
            let isDisabled = isAdded;
            
            // Determine styling
            let checkedClass = '';
            let opacityStyle = '';
            let statusBadge = '';
            
            if (isAdded) {
                checkedClass = 'bg-secondary';
                opacityStyle = 'opacity: 0.7;';
                statusBadge = '<br><span class="badge bg-success mt-1">Sudah ditambahkan</span>';
            }
            
            // Build rental info - rooms with multiple rentals will auto-add all rentals
            let rentalInfoHtml = '';
            if (rentalCount > 1) {
                rentalInfoHtml = `<br><small class="text-info"><i class="fas fa-box me-1"></i>${rentalCount} rental akan ditambahkan</small>`;
            } else if (rentalCount === 1 && rentals[0]) {
                rentalInfoHtml = `<br><small class="text-info"><i class="fas fa-box me-1"></i>${rentals[0].rental_name}</small>`;
            }
            
            roomsHtml += `
                <div class="form-check mb-2 p-3 border rounded ${checkedClass}" style="cursor: ${isDisabled ? 'not-allowed' : 'pointer'}; ${opacityStyle} padding-left: 0.75rem; position: relative; overflow: visible;">
                    <input class="form-check-input room-checkbox" 
                           type="checkbox" 
                           value="${contractRoom.id}" 
                           id="room_${contractRoom.id}"
                           ${isDisabled ? 'disabled' : ''}
                           data-room-name="${room.room_name || 'N/A'}"
                           data-building-name="${building.nama_gedung || building.name || 'N/A'}"
                           data-rental-product-id="${contractRoom.rental_product_id || ''}"
                           data-rental-count="${rentalCount}"
                           style="margin-left: 0; position: absolute; left: 14px; top: 18px; z-index: 2;">
                    <label class="form-check-label w-100 d-block" for="room_${contractRoom.id}" style="cursor: ${isDisabled ? 'not-allowed' : 'pointer'}; margin-left: 1.75rem; padding-left: 0.25rem;">
                        <strong style="display: block;">${room.room_name || 'N/A'}</strong>
                        <br>
                        <small class="text-muted">
                            <i class="fas fa-building me-1"></i>${building.nama_gedung || building.name || 'N/A'}
                            ${room.room_type ? `<br><i class="fas fa-tag me-1"></i>${room.room_type}` : ''}
                            ${room.room_floor ? `<br><i class="fas fa-layer-group me-1"></i>Lantai: ${room.room_floor}` : ''}
                        </small>
                        ${statusBadge}
                        ${rentalInfoHtml}
                    </label>
                </div>
            `;
            
            availableRoomsCount++;
        });
        
        // Show message if no rooms available
        if (availableRoomsCount === 0) {
            roomsHtml += '<div class="alert alert-warning">Tidak ada ruangan yang tersedia untuk ditambahkan.</div>';
        }
        
        roomsHtml += '</div>';
        
        Swal.fire({
            title: 'Add/Choose Rooms',
            html: roomsHtml,
            width: '700px',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#6b7280',
            confirmButtonText: '<i class="fas fa-check me-1"></i> Add Selected Rooms',
            cancelButtonText: 'Cancel',
            preConfirm: () => {
                const checkboxes = document.querySelectorAll('.room-checkbox:checked');
                const isQuotation = {{ $jobAdvice->quotation_id ? 'true' : 'false' }};
                const selectedRooms = Array.from(checkboxes).map(cb => {
                    const roomId = cb.value;
                    
                    // Note: rental_product_id is NOT sent - backend will auto-add ALL rentals for this room
                    const roomData = {
                        room_name: cb.dataset.roomName,
                        building_name: cb.dataset.buildingName,
                        rental_product_id: null // Backend will get ALL rentals
                    };
                    // Use quotation_room_id if from quotation, contract_room_id if from contract
                    if (isQuotation) {
                        roomData.quotation_room_id = roomId;
                    } else {
                        roomData.contract_room_id = roomId;
                    }
                    return roomData;
                });
                
                if (selectedRooms.length === 0) {
                    Swal.showValidationMessage('Pilih minimal 1 ruangan');
                    return false;
                }
                
                return selectedRooms;
            }
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                addRoomsToJobAdvice(result.value);
            }
        });
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Gagal mengambil data ruangan: ' + error.message,
            confirmButtonColor: '#3085d6'
        });
    });
}

// Function to add selected rooms to Job Advice
function addRoomsToJobAdvice(selectedRooms) {
    Swal.fire({
        title: 'Adding Rooms...',
        html: 'Menambahkan ruangan ke Job Advice...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // MOM9: Check if job advice is from quotation or contract
    const isQuotation = {{ $jobAdvice->quotation_id ? 'true' : 'false' }};
    
    // Prepare data
    const roomsData = selectedRooms.map(room => {
        const roomData = {
            rental_product_id: room.rental_product_id, // Fixed: Pass the rental_product_id from frontend
            quantity: 1,
            notes: null
        };
        
        // MOM9: Use quotation_room_id if from quotation, contract_room_id if from contract
        if (isQuotation) {
            roomData.quotation_room_id = room.quotation_room_id;
        } else {
            roomData.contract_room_id = room.contract_room_id;
        }
        
        return roomData;
    });
    
    // Send to backend
    fetch('/marketing/job-advices/{{ $jobAdvice->id }}/add-rooms', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            rooms: roomsData
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success' || data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: `${selectedRooms.length} ruangan berhasil ditambahkan.`,
                confirmButtonColor: '#3085d6',
            }).then(() => {
                location.reload();
            });
        } else {
            throw new Error(data.message || 'Failed to add rooms');
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Gagal menambahkan ruangan: ' + error.message,
            confirmButtonColor: '#3085d6'
        });
    });
}

// Edit Job Advice Modal (Read-only basic info, editable: expected_date, remove_date, notes)
function openEditModal() {
    // Show loading
    Swal.fire({
        title: 'Loading...',
        html: 'Mengambil data Job Advice...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Fetch Job Advice data
    fetch('/marketing/job-advices/{{ $jobAdvice->id }}/edit', {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        // Format dates
        const formatDate = (dateString) => {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toISOString().split('T')[0];
        };
        
        // Helper to check type
        const type = (data.type || '').toLowerCase();
        const isInstallFree = (type === 'install_free' || type === 'install free');
        
        Swal.fire({
            title: 'Edit Job Advice',
            html: `
                <div style="max-height: 600px; overflow-y: auto; text-align: left;">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Job Advice No</label>
                        <input type="text" class="form-control" value="${data.job_advice_number}" readonly disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Request By</label>
                        <input type="text" class="form-control" value="${data.requested_by ? data.requested_by.name : '-'}" readonly disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Job Advice Type</label>
                        <input type="text" class="form-control" value="${data.type}" readonly disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Reference No (Quotation)</label>
                        <input type="text" class="form-control" value="${data.reference_number || (data.contract?.quotation?.quotation_number || '-')}" readonly disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Expected Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="edit_expected_date" value="${formatDate(data.expected_date)}" required>
                    </div>
                    <div class="mb-3" style="display: ${isInstallFree ? 'block' : 'none'};">
                        <label class="form-label fw-bold">Remove Date ${isInstallFree ? '<span class="text-danger">*</span>' : ''}</label>
                        <input type="date" class="form-control" id="edit_remove_date" value="${formatDate(data.remove_date)}" ${isInstallFree ? 'required' : ''}>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Catatan Tambahan</label>
                        <textarea class="form-control" id="edit_notes" rows="4" placeholder="Masukkan catatan tambahan...">${data.notes || ''}</textarea>
                    </div>
                </div>
            `,
            width: '600px',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#6b7280',
            confirmButtonText: '<i class="fas fa-save me-1"></i> Save Changes',
            cancelButtonText: 'Cancel',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                const expectedDate = document.getElementById('edit_expected_date').value;
                const removeDate = document.getElementById('edit_remove_date').value;
                const notes = document.getElementById('edit_notes').value;
                
                if (!expectedDate) {
                    Swal.showValidationMessage('Expected Date wajib diisi!');
                    return false;
                }
                
                // Validate Remove Date for Install Free
                if (isInstallFree && !removeDate) {
                    Swal.showValidationMessage('Remove Date wajib diisi untuk Install Free!');
                    return false;
                }
                
                return fetch('/marketing/job-advices/{{ $jobAdvice->id }}', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        expected_date: expectedDate,
                        remove_date: removeDate,
                        notes: notes,
                        _method: 'PUT'
                    })
                })
                .then(response => response.json())
                .then(result => {
                    if (result.status === 'success' || result.success) {
                        return result;
                    }
                    throw new Error(result.message || 'Failed to update');
                })
                .catch(error => {
                    Swal.showValidationMessage(`Request failed: ${error.message}`);
                });
            },
            allowOutsideClick: false
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    icon: 'success',
                    title: 'Updated!',
                    text: 'Job Advice berhasil diupdate.',
                    confirmButtonColor: '#3085d6',
                }).then(() => {
                    location.reload();
                });
            }
        });
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to load Job Advice data: ' + error.message,
            confirmButtonColor: '#3085d6'
        });
    });
}

    // Cancel Job Advice Request
    window.cancelJobAdvice = function() {
        Swal.fire({
            title: 'Batalkan Job Request?',
            text: "Tindakan ini akan menghapus semua Job Schedule yang berstatus 'New Job' dan mengubah status Job Advice menjadi Cancelled.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Batalkan!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading
                Swal.fire({
                    title: 'Memproses...',
                    text: 'Mohon tunggu sebentar.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: "{{ route('marketing.job-advices.cancel-request', $jobAdvice->id) }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        Swal.fire({
                            title: 'Berhasil!',
                            text: response.message,
                            icon: 'success'
                        }).then(() => {
                            window.location.reload();
                        });
                    },
                    error: function(xhr) {
                        let errorMessage = 'Terjadi kesalahan.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire({
                            title: 'Gagal!',
                            text: errorMessage,
                            icon: 'error'
                        });
                    }
                });
            }
        });
    }

    function deleteRoom(roomId, roomName) {
        Swal.fire({
            title: 'Hapus Room?',
            text: `Apakah Anda yakin ingin menghapus room "${roomName}" dari Job Advice ini?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading
                Swal.fire({
                    title: 'Menghapus...',
                    text: 'Mohon tunggu sebentar.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Send DELETE request
                fetch("{{ url('marketing/job-advices') }}/{{ $jobAdvice->id }}/rooms/" + roomId, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => {
                    return response.json().then(data => {
                        if (!response.ok) {
                            throw new Error(data.message || 'Terjadi kesalahan saat menghapus room.');
                        }
                        return data;
                    });
                })
                .then(data => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: data.message || 'Room berhasil dihapus.',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: error.message || 'Terjadi kesalahan saat menghapus room.',
                    });
                });
            }
        });
    }
</script>
<script>
// Change Rental (Old vs New)
function openChangeRentalModal(roomId, currentRentalId, currentQty = 1) { // Default qty 1
    // 1. Fetch rental products for dropdown
    Swal.fire({
        title: 'Loading Rentals...',
        html: 'Mengambil daftar rental...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch('/warehouse/rental-products/dropdown', {
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        let rentals = [];
        // Data format check: usually {status: 'success', data: [...]}
        if (data.status === 'success' && Array.isArray(data.data)) {
            rentals = data.data;
        } else if (Array.isArray(data)) {
            rentals = data; 
        } else if (data.data && Array.isArray(data.data)) {
            rentals = data.data; // Handle potential paginated response structure
        } else {
            throw new Error('Format data rental tidak valid');
        }

        // Build options
        let optionsHtml = '<option value="">-- Pilih Rental Baru --</option>';
        rentals.forEach(r => {
            const selected = (r.id == currentRentalId) ? 'selected' : '';
            optionsHtml += `<option value="${r.id}" ${selected}>${r.rental_name}</option>`;
        });

        const contentHtml = `
            <div class="mb-3 text-start">
                <label class="form-label fw-bold">Pilih Rental Baru</label>
                <select id="new_rental_select" class="form-select swal2-select" style="width: 100%;">
                    ${optionsHtml}
                </select>
                <small class="text-muted">Pilih rental pengganti untuk ruangan ini.</small>
            </div>
            <div class="mb-3 text-start">
                <label class="form-label fw-bold">Quantity (Qty)</label>
                <input type="number" id="new_rental_qty" class="form-control" value="${currentQty}" min="1">
                <small class="text-muted text-danger">Perubahan Qty akan mempengaruhi Invoice bulan berikutnya.</small>
            </div>
        `;

        Swal.fire({
            title: 'Change Rental',
            html: contentHtml,
            width: '500px',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#6b7280',
            confirmButtonText: '<i class="fas fa-save"></i> Simpan Perubahan',
            cancelButtonText: 'Batal',
            didOpen: () => {
                 if (typeof $.fn.select2 !== 'undefined') {
                    $('#new_rental_select').select2({
                        dropdownParent: Swal.getPopup(),
                         width: '100%',
                         placeholder: 'Cari Rental...'
                    });
                }
            },
            preConfirm: () => {
                let selectedId = document.getElementById('new_rental_select').value;
                let newQty = document.getElementById('new_rental_qty').value;

                 // If Select2 is used, value might need to be retrieved from jQuery
                if (typeof $.fn.select2 !== 'undefined') {
                    selectedId = $('#new_rental_select').val();
                }
                
                if (!selectedId) {
                    Swal.showValidationMessage('Silakan pilih rental baru');
                    return false;
                }

                if (!newQty || newQty < 1) {
                    Swal.showValidationMessage('Qty minimal 1');
                    return false;
                }

                return { rentalId: selectedId, qty: newQty };
            }
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                updateRoomRental(roomId, result.value.rentalId, result.value.qty);
            }
        });
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Gagal memuat data rental: ' + error.message
        });
    });
}

function updateRoomRental(roomId, newRentalId, newQty) {
    Swal.fire({
        title: 'Menyimpan...',
        text: 'Mengubah data rental...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch(`/marketing/job-advices/rooms/${roomId}/update-rental`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            rental_product_id: newRentalId,
            quantity: newQty // Send quantity
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success' || data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Rental dan Quantity berhasil diubah.',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                location.reload();
            });
        } else {
            throw new Error(data.message || 'Gagal mengubah rental');
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Gagal!',
            text: error.message
        });
    });
}
</script>
@endpush
