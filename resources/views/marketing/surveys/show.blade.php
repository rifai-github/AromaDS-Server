@extends('layouts.app')

@section('title', 'Survey Detail - Updated')

@section('content')

<!-- Force CSS untuk layout -->
<style>
    .survey-layout-fix {
        display: flex !important;
        flex-wrap: wrap !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .survey-layout-fix .col-lg-6 {
        flex: 0 0 50% !important;
        max-width: 50% !important;
        padding: 15px !important;
        display: block !important;
    }
    .survey-card {
        height: 100% !important;
        border: 1px solid #dee2e6 !important;
        border-radius: 8px !important;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
    }
    .survey-card-header {
        background-color: #6c757d !important;
        color: white !important;
        padding: 1rem 1.5rem !important;
        border-bottom: 1px solid rgba(0, 0, 0, 0.125) !important;
        border-radius: 8px 8px 0 0 !important;
    }
    .survey-card-body {
        padding: 1.5rem !important;
    }
    .survey-field {
        margin-bottom: 1rem !important;
        display: flex !important;
        align-items: center !important;
    }
    .survey-field-label {
        flex: 0 0 40% !important;
        font-weight: bold !important;
        color: #495057 !important;
    }
    .survey-field-value {
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
    }
    
    #survey-detail {
        width: 100% !important;
        min-height: 500px !important;
    }
    @media (max-width: 991.98px) {
        .survey-layout-fix .col-lg-6 {
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
                            <a href="{{ route('marketing.surveys.index') }}" class="btn btn-light btn-sm">
                                <i class="fas fa-arrow-left"></i> Back to List
                            </a>
                        </div>
                        <div>
                            <h3 class="card-title mb-0" style="color: white; font-size: 1.5rem; font-weight: bold;">
                                 {{ $survey->survey_number }}
                            </h3>
                        </div>
                        <div>
                            @if($survey->status === 'draft')
                                <button class="btn btn-primary btn-sm me-2" id="save-draft-btn">
                                    <i class="fas fa-save"></i> SAVE DRAFT
                                </button>
                                <button class="btn btn-success btn-sm" id="finalize-survey-btn">
                                    <i class="fas fa-check"></i> FINALIZE SURVEY
                                </button>
                            @elseif($survey->status === 'submitted')
                                <span class="badge badge-warning fs-6 me-2">
                                    {{ $survey->status_text }}
                                </span>
                                @if(Auth::check() && Auth::user()->canApprove('surveys'))
                                    <button type="button" class="btn btn-success btn-sm me-2" onclick="approveSurvey({{ $survey->id }})">
                                        <i class="fas fa-check"></i> APPROVE
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="rejectSurvey({{ $survey->id }})">
                                        <i class="fas fa-times"></i> REJECT
                                    </button>
                                @endif
                            @else
                                <span class="badge badge-{{ $survey->status == 'approved' ? 'success' : 'warning' }} fs-6">
                                    {{ $survey->status_text }}
                                </span>
                                @if($survey->status === 'approved' && !$survey->is_used_in_quotation)
                                    <button class="btn btn-warning btn-sm ms-2" id="unpost-survey-btn" onclick="unpostSurvey({{ $survey->id }})">
                                        <i class="fas fa-undo"></i> UNPOST
                                    </button>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Tabs - HORIZONTAL LAYOUT -->
            <div class="card mb-3">
                <div class="card-body p-0">
                    <ul class="nav nav-tabs" id="surveyTabs" role="tablist" style="border-bottom: 2px solid #1e3a8a; margin: 0; display: flex; flex-direction: row;">
                        <li class="nav-item" role="presentation" style="flex: 1;">
                            <button class="nav-link active" id="basic-info-tab" data-bs-toggle="tab" data-bs-target="#basic-info" type="button" role="tab" aria-controls="basic-info" aria-selected="true" style="border-bottom: 3px solid #1e3a8a; color: #1e3a8a; font-weight: bold; padding: 12px 20px; width: 100%; text-align: center;">
                                <i class="fas fa-info-circle me-2"></i>BASIC INFO
                            </button>
                        </li>
                        <li class="nav-item" role="presentation" style="flex: 1;">
                            <button class="nav-link" id="survey-detail-tab" data-bs-toggle="tab" data-bs-target="#survey-detail" type="button" role="tab" aria-controls="survey-detail" aria-selected="false" style="color: #6c757d; padding: 12px 20px; width: 100%; text-align: center;">
                                <i class="fas fa-list-alt me-2"></i>SURVEY DETAIL
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Tab Content -->
            <div class="tab-content" id="surveyTabsContent">
                <!-- Basic Info Tab -->
                <div class="tab-pane fade show active" id="basic-info" role="tabpanel" aria-labelledby="basic-info-tab">
                    <div class="row survey-layout-fix">
                        <!-- Survey Information Section 1 -->
                        <div class="col-lg-6 mb-4">
                            <div class="card survey-card">
                                <div class="survey-card-header">
                                    <h5 class="card-title mb-0">
                                        No Survey: {{ $survey->survey_number }}
                                    </h5>
                                </div>
                                <div class="survey-card-body">
                                    <div class="survey-field">
                                        <div class="survey-field-label">Nomor Survey</div>
                                        <div class="survey-field-value">{{ $survey->survey_number }}</div>
                                    </div>
                                    <div class="survey-field">
                                        <div class="survey-field-label">Status Survey</div>
                                        <div class="survey-field-value">
                                            <span class="badge badge-{{ $survey->status == 'approved' ? 'success' : 'warning' }}">
                                    {{ $survey->status_text }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="survey-field">
                                        <div class="survey-field-label">Nama Marketing</div>
                                        <div class="survey-field-value">
                                            @if($survey->status === 'draft')
                                                <select id="edit_marketing_id" class="form-control select2">
                                                    <option value="{{ $survey->marketing_id }}" selected>{{ $survey->marketing->name ?? '-' }}</option>
                                                </select>
                                            @else
                                                <div class="input-group">
                                                    <input type="text" class="form-control" value="{{ $survey->marketing->name ?? '-' }}" readonly>
                                                    <div class="input-group-append">
                                                        <button class="btn btn-outline-secondary" type="button" disabled>
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                        <button class="btn btn-outline-secondary" type="button" disabled>
                                                            <i class="fas fa-chevron-down"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="survey-field">
                                        <div class="survey-field-label">Tanggal Survey</div>
                                        <div class="survey-field-value">
                                            @if($survey->status === 'draft')
                                                <input type="date" id="edit_survey_date" class="form-control" value="{{ $survey->survey_date ? $survey->survey_date->format('Y-m-d') : '' }}">
                                            @else
                                                <div class="input-group">
                                                    <input type="text" class="form-control" value="{{ $survey->survey_date ? \Carbon\Carbon::parse($survey->survey_date)->locale('id')->isoFormat('D MMM Y') : '-' }}" readonly>
                                                    <div class="input-group-append">
                                                        <button class="btn btn-outline-secondary" type="button" disabled>
                                                            <i class="fas fa-calendar"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="survey-field">
                                        <div class="survey-field-label">Dibuat Pada</div>
                                        <div class="survey-field-value">{{ $survey->created_at->locale('id')->isoFormat('dddd, D MMM Y - HH:mm') }}</div>
                                    </div>
                                    <div class="survey-field">
                                        <div class="survey-field-label">Oleh</div>
                                        <div class="survey-field-value">{{ $survey->creator->name ?? '-' }}</div>
                                    </div>
                                    <div class="survey-field">
                                        <div class="survey-field-label">Terakhir Di Update</div>
                                        <div class="survey-field-value">{{ $survey->updated_at->locale('id')->isoFormat('dddd, D MMM Y - HH:mm') }}</div>
                                    </div>
                                    <div class="survey-field">
                                        <div class="survey-field-label">Oleh</div>
                                        <div class="survey-field-value">{{ $survey->updater->name ?? '-' }}</div>
                                    </div>
                                    <div class="survey-field">
                                        <div class="survey-field-label">Survey Report</div>
                                        <div class="survey-field-value">
                                                <a href="{{ route('marketing.surveys.download-pdf', $survey->id) }}" class="btn btn-lg" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 12px 30px; font-weight: bold; font-size: 16px; border-radius: 50px; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4); transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 10px;" onmouseover="this.style.transform=`translateY(-2px)`; this.style.boxShadow=`0 6px 20px rgba(102, 126, 234, 0.6)`" onmouseout="this.style.transform=`translateY(0)`; this.style.boxShadow=`0 4px 15px rgba(102, 126, 234, 0.4)`" target="_blank" rel="noopener noreferrer">
                                                <i class="fas fa-file-pdf" style="font-size: 20px;"></i> <span style="font-weight: bold;">Download PDF Report</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Company & Location Information Section 2 -->
                        <div class="col-lg-6 mb-4">
                            <div class="card survey-card">
                                <div class="survey-card-header">
                                    <h5 class="card-title mb-0">
                                        Data Company & Lokasi
                                    </h5>
                                </div>
                                <div class="survey-card-body">
                                    <div class="survey-field">
                                        <div class="survey-field-label">Nama Company</div>
                                        <div class="survey-field-value">
                                            @if($survey->customer_id)
                                                <a href="{{ route('company.customers.show', $survey->customer_id) }}" class="text-primary fw-bold" target="_blank" rel="noopener noreferrer">
                                                    {{ $survey->display_company_name }}
                                                    <i class="fas fa-external-link-alt fa-xs ms-1"></i>
                                                </a>
                                            @else
                                                {{ $survey->display_company_name }}
                                            @endif
                                        </div>
                                    </div>
                                    <div class="survey-field">
                                        <div class="survey-field-label">Jenis Customer</div>
                                        <div class="survey-field-value">{{ strtoupper($survey->customer_type ?? $survey->customer->company_type ?? '-') }}</div>
                                    </div>
                                    <div class="survey-field">
                                        <div class="survey-field-label">PIC</div>
                                        <div class="survey-field-value">{{ $survey->contact_person ?? '-' }}</div>
                                    </div>
                                    <div class="survey-field">
                                        <div class="survey-field-label">E-mail</div>
                                        <div class="survey-field-value">{{ $survey->display_email }}</div>
                                    </div>
                                    <div class="survey-field">
                                        <div class="survey-field-label">Phone 1</div>
                                        <div class="survey-field-value">{{ $survey->display_phone_one }}</div>
                                    </div>
                                    <div class="survey-field">
                                        <div class="survey-field-label">Jabatan / Posisi</div>
                                        <div class="survey-field-value">{{ $survey->position ?? '-' }}</div>
                                    </div>
                                    <div class="survey-field">
                                        <div class="survey-field-label">Nama Gedung</div>
                                        <div class="survey-field-value">
                                            @if($survey->building_id)
                                                <a href="{{ route('operational.buildings.show', $survey->building_id) }}" class="text-primary fw-bold" target="_blank" rel="noopener noreferrer">
                                                    {{ $survey->display_building_name }}
                                                    <i class="fas fa-external-link-alt fa-xs ms-1"></i>
                                                </a>
                                            @else
                                                {{ $survey->display_building_name }}
                                            @endif
                                        </div>
                                    </div>
                                    <div class="survey-field">
                                        <div class="survey-field-label">Alamat Gedung</div>
                                        <div class="survey-field-value">
                                            {{ $survey->display_address_one ?? '-' }}
                                            @if($survey->display_address_two)
                                                <br>{{ $survey->display_address_two }}
                                            @endif
                                        </div>
                                    </div>
                                    <div class="survey-field">
                                        <div class="survey-field-label">Lokasi Detail</div>
                                        <div class="survey-field-value">
                                            @if($survey->status === 'draft')
                                                <span class="inline-edit-trigger text-primary cursor-pointer border-bottom border-primary border-dotted" 
                                                      data-id="{{ $survey->id }}" 
                                                      id="lokasi-detail-display" 
                                                      title="Klik untuk edit"
                                                      style="cursor: pointer; border-bottom: 1px dashed #1e3a8a;">
                                                    {{ $survey->building_location_detail ?? 'Klik untuk isi lokasi detail' }}
                                                </span>
                                                <input type="text" 
                                                       id="lokasi-detail-input" 
                                                       class="form-control form-control-sm" 
                                                       style="display: none;"
                                                       value="{{ $survey->building_location_detail }}"
                                                       placeholder="Ketik lokasi detail lalu tekan Enter">
                                            @else
                                                {{ $survey->building_location_detail ?? '-' }}
                                            @endif
                                        </div>
                                    </div>
                                    <div class="survey-field">
                                        <div class="survey-field-label">Provinsi</div>
                                        <div class="survey-field-value">{{ $survey->display_province ?? '-' }}</div>
                                    </div>
                                    <div class="survey-field">
                                        <div class="survey-field-label">Kota/Kabupaten</div>
                                        <div class="survey-field-value">{{ $survey->display_city ?? '-' }}</div>
                                    </div>
                                    <div class="survey-field">
                                        <div class="survey-field-label">Kecamatan</div>
                                        <div class="survey-field-value">{{ $survey->display_district ?? '-' }}</div>
                                    </div>
                                    <div class="survey-field">
                                        <div class="survey-field-label">Kelurahan</div>
                                        <div class="survey-field-value">{{ $survey->display_village ?? '-' }}</div>
                                    </div>
                                    <div class="survey-field">
                                        <div class="survey-field-label">Kode Pos</div>
                                        <div class="survey-field-value">{{ $survey->display_postal_code ?? '-' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Survey Detail Tab -->
                <div class="tab-pane fade" id="survey-detail" role="tabpanel" aria-labelledby="survey-detail-tab" style="display: none;">
                    <div class="card" style="width: 100%; min-height: 500px;">
                        <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #1e3a8a;">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0" style="color: #1e3a8a;">
                                    <i class="fas fa-list-alt me-2"></i>
                                    Survey Detail
                                    @if($survey->status === 'draft')
                                        <span class="badge badge-warning ml-2">DRAFT - Editable</span>
                                    @else
                                        <span class="badge badge-success ml-2">FINALIZED - Read Only</span>
                                    @endif
                                </h5>
                                @if($survey->status === 'draft')
                                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addSurveyDetailModal">
                                        <i class="fas fa-plus me-1"></i> ADD NEW
                                    </button>
                                @endif
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive" style="overflow-x: auto; max-width: 100%;">
                                <table class="table table-bordered table-striped" id="surveyDetailsTable" style="min-width: 1400px; white-space: nowrap;">
                                    <thead>
                                        <tr>
                                            <th data-no-filter>
                                                <input type="checkbox" id="selectAll">
                                            </th>
                                            <th data-column="survey.survey_number">No Survey</th>
                                            <th data-column="room_name">Nama Ruangan</th>
                                            <th data-column="room_type">Tipe Ruangan</th>
                                            <th data-column="floor">Lantai</th>
                                            <th data-column="intensity">Intensitas Wangi</th>
                                            <th data-column="installation_type">Installation Type</th>
                                            <th data-column="qty" data-type="numeric">Qty</th>
                                            <th data-column="temperature" data-type="numeric">Temperatur ({!! '&deg;C' !!})</th>
                                            <th data-column="length" data-type="numeric">Panjang (m)</th>
                                            <th data-column="width" data-type="numeric">Lebar (m)</th>
                                            <th data-column="height" data-type="numeric">Tinggi (m)</th>
                                            <th data-column="remark">Remark</th>
                                            <th data-no-filter>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($survey->surveyDetails as $index => $detail)
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="row-checkbox" value="{{ $detail->id }}">
                                            </td>
                                            <td>{{ $survey->survey_number }}</td>
                                            <td>{{ $detail->room_name ?? '-' }}</td>
                                            <td>{{ $detail->room_type ?? '-' }}</td>
                                            <td>
                                                @php
                                                    $specs = json_decode($detail->specifications ?? '{}', true);
                                                @endphp
                                                {{ $specs['floor'] ?? '-' }}
                                            </td>
                                            <td>
                                                @php
                                                    $specs = json_decode($detail->specifications ?? '{}', true);
                                                @endphp
                                                {{ $specs['intensity'] ?? '-' }}
                                            </td>
                                            <td>
                                                @php
                                                    $specs = json_decode($detail->specifications ?? '{}', true);
                                                @endphp
                                                {{ $specs['installation_type'] ?? '-' }}
                                            </td>
                                            <td>{{ $detail->quantity_needed ?? '-' }}</td>
                                            <td>
                                                @php
                                                    $specs = json_decode($detail->specifications ?? '{}', true);
                                                @endphp
                                                {{ $specs['temperature'] ?? '-' }}{{ isset($specs['temperature']) ? '°C' : '' }}
                                            </td>
                                            <td>
                                                @php
                                                    $specs = json_decode($detail->specifications ?? '{}', true);
                                                @endphp
                                                {{ $specs['length'] ?? '-' }}
                                            </td>
                                            <td>
                                                @php
                                                    $specs = json_decode($detail->specifications ?? '{}', true);
                                                @endphp
                                                {{ $specs['width'] ?? '-' }}
                                            </td>
                                            <td>
                                                @php
                                                    $specs = json_decode($detail->specifications ?? '{}', true);
                                                @endphp
                                                {{ $specs['height'] ?? '-' }}
                                            </td>
                                            <td>
                                                @php
                                                    $specs = json_decode($detail->specifications ?? '{}', true);
                                                @endphp
                                                {{ $specs['remark'] ?? '-' }}
                                            </td>
                                            <td>
                                                @if($survey->status === 'draft')
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <button type="button" class="btn btn-primary btn-sm edit-survey-detail" data-detail-id="{{ $detail->id }}" style="background-color: #007bff; border-color: #007bff; color: white; padding: 5px 10px; margin: 2px;">
                                                            <i class="fas fa-edit"></i> UBAH
                                                        </button>
                                                        <button type="button" class="btn btn-info btn-sm copy-survey-detail" data-detail-id="{{ $detail->id }}" style="background-color: #17a2b8; border-color: #17a2b8; color: white; padding: 5px 10px; margin: 2px;">
                                                            <i class="fas fa-copy"></i> COPY
                                                        </button>
                                                        <button type="button" class="btn btn-danger btn-sm delete-survey-detail" data-detail-id="{{ $detail->id }}" style="background-color: #dc3545; border-color: #dc3545; color: white; padding: 5px 10px; margin: 2px;">
                                                            <i class="fas fa-trash"></i> HAPUS
                                                        </button>
                                                    </div>
                                                @else
                                                    <span class="badge badge-success">READ ONLY</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                @if($survey->surveyDetails->isEmpty())
                                    <div class="alert alert-light border text-muted mt-3 mb-0">
                                        <i class="fas fa-info-circle me-2"></i>
                                        @if($survey->status === 'draft')
                                            No survey details found. Click "ADD NEW" to add room data.
                                        @else
                                            No survey details found. This finalized survey has no room data.
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Survey Detail Modal - FIXED POPUP -->
<div class="modal fade" id="addSurveyDetailModal" tabindex="-1" role="dialog" aria-labelledby="addSurveyDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #1e3a8a; color: white; border-radius: 10px 10px 0 0;">
                <h5 class="modal-title" id="addSurveyDetailModalLabel">
                    <i class="fas fa-plus me-2"></i>Tambah Survey Detail
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addSurveyDetailForm">
                <div class="modal-body" style="padding: 40px; max-height: 70vh; overflow-y: auto;">
                    <!-- Section 1: Basic Information -->
                    <div class="card mb-5" style="border: 1px solid #e3e6f0; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        <div class="card-header" style="background-color: #f8f9fc; border-bottom: 1px solid #e3e6f0; padding: 20px 25px; border-radius: 12px 12px 0 0;">
                            <h6 class="mb-0 text-primary fw-bold">
                                <i class="fas fa-info-circle me-2"></i>Informasi Dasar
                            </h6>
                        </div>
                        <div class="card-body" style="padding: 25px;">
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label for="room_name" class="form-label fw-bold">Nama Ruangan <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="room_name" name="room_name" required placeholder="Masukkan nama ruangan">
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label for="room_type" class="form-label fw-bold">Tipe Ruangan <span class="text-danger">*</span></label>
                                    <select class="form-control" id="room_type" name="room_type" required>
                                        <option value="">Pilih tipe ruangan...</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label for="floor" class="form-label fw-bold">Lantai <span class="text-danger">*</span></label>
                                    <select class="form-control" id="floor" name="floor" required>
                                        <option value="">Pilih lantai...</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label for="scent_intensity" class="form-label fw-bold">Intensitas Wangi <span class="text-danger">*</span></label>
                                    <select class="form-control" id="scent_intensity" name="scent_intensity" required>
                                        <option value="">Pilih intensitas wangi...</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label for="installation_type" class="form-label fw-bold">Installation Type <span class="text-danger">*</span></label>
                                    <select class="form-control" id="installation_type" name="installation_type" required>
                                        <option value="">Pilih installation type...</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label for="qty" class="form-label fw-bold">Qty <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="qty" name="qty" required placeholder="Masukkan jumlah" min="1">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label for="temperature" class="form-label fw-bold">Temperature (&deg;C)</label>
                                    <input type="number" step="0.1" class="form-control" id="temperature" name="temperature" placeholder="Masukkan suhu ruangan" min="0" max="100">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Dimensions -->
                    <div class="card mb-5" style="border: 1px solid #e3e6f0; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        <div class="card-header" style="background-color: #f8f9fc; border-bottom: 1px solid #e3e6f0; padding: 20px 25px; border-radius: 12px 12px 0 0;">
                            <h6 class="mb-0 text-primary fw-bold">
                                <i class="fas fa-ruler me-2"></i>Dimensi Ruangan
                            </h6>
                        </div>
                        <div class="card-body" style="padding: 25px;">
                            <div class="row">
                                <div class="col-md-4 mb-4">
                                    <label for="length" class="form-label fw-bold">Panjang (m) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control" id="length" name="length" required placeholder="0.00" min="0">
                                </div>
                                <div class="col-md-4 mb-4">
                                    <label for="width" class="form-label fw-bold">Lebar (m) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control" id="width" name="width" required placeholder="0.00" min="0">
                                </div>
                                <div class="col-md-4 mb-4">
                                    <label for="height" class="form-label fw-bold">Tinggi (m) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control" id="height" name="height" required placeholder="0.00" min="0">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Additional Information -->
                    <div class="card mb-4" style="border: 1px solid #e3e6f0; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        <div class="card-header" style="background-color: #f8f9fc; border-bottom: 1px solid #e3e6f0; padding: 20px 25px; border-radius: 12px 12px 0 0;">
                            <h6 class="mb-0 text-primary fw-bold">
                                <i class="fas fa-sticky-note me-2"></i>Informasi Tambahan
                            </h6>
                        </div>
                        <div class="card-body" style="padding: 25px;">
                            <div class="row">
                                <div class="col-md-12">
                                    <label for="remark" class="form-label fw-bold">Catatan tambahan</label>
                                    <textarea class="form-control" id="remark" name="remark" rows="3" placeholder="Masukkan catatan tambahan jika ada..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">SIMPAN</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Edit Survey Detail Modal - FIXED UI & SCROLLING -->
<div class="modal fade" id="editSurveyDetailModal" tabindex="-1" role="dialog" aria-labelledby="editSurveyDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #1e3a8a; color: white; border-radius: 10px 10px 0 0;">
                <h5 class="modal-title" id="editSurveyDetailModalLabel">
                    <i class="fas fa-edit me-2"></i>Edit Informasi Ruangan
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editSurveyDetailForm">
                <div class="modal-body" style="padding: 40px; max-height: 70vh; overflow-y: auto;">
                    <!-- Section 1: Basic Information -->
                    <div class="card mb-5" style="border: 1px solid #e3e6f0; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        <div class="card-header" style="background-color: #f8f9fc; border-bottom: 1px solid #e3e6f0; padding: 20px 25px; border-radius: 12px 12px 0 0;">
                            <h6 class="mb-0 text-primary fw-bold">
                                <i class="fas fa-info-circle me-2"></i>Informasi Dasar
                            </h6>
                        </div>
                        <div class="card-body" style="padding: 25px;">
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label for="edit_room_name" class="form-label fw-bold">Nama Ruangan <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="edit_room_name" name="edit_room_name" required placeholder="Contoh: Meeting Room A">
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label for="edit_room_type" class="form-label fw-bold">Tipe Ruangan <span class="text-danger">*</span></label>
                                    <select class="form-control" id="edit_room_type" name="edit_room_type" required>
                                        <option value="">Pilih tipe ruangan...</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label for="edit_floor" class="form-label fw-bold">Lantai <span class="text-danger">*</span></label>
                                    <select class="form-control" id="edit_floor" name="edit_floor" required>
                                        <option value="">Pilih lantai...</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label for="edit_scent_intensity" class="form-label fw-bold">Intensitas Wangi <span class="text-danger">*</span></label>
                                    <select class="form-control" id="edit_scent_intensity" name="edit_scent_intensity" required>
                                        <option value="">Pilih intensitas wangi...</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label for="edit_installation_type" class="form-label fw-bold">Installation Type <span class="text-danger">*</span></label>
                                    <select class="form-control" id="edit_installation_type" name="edit_installation_type" required>
                                        <option value="">Pilih installation type...</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label for="edit_qty" class="form-label fw-bold">Qty <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="edit_qty" name="edit_qty" required placeholder="0" min="1">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label for="edit_temperature" class="form-label fw-bold">Temperature (&deg;C)</label>
                                    <input type="number" step="0.1" class="form-control" id="edit_temperature" name="edit_temperature" placeholder="Masukkan suhu ruangan" min="0" max="100">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Dimensions -->
                    <div class="card mb-5" style="border: 1px solid #e3e6f0; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        <div class="card-header" style="background-color: #f8f9fc; border-bottom: 1px solid #e3e6f0; padding: 20px 25px; border-radius: 12px 12px 0 0;">
                            <h6 class="mb-0 text-primary fw-bold">
                                <i class="fas fa-ruler me-2"></i>Dimensi Ruangan (Meter)
                            </h6>
                        </div>
                        <div class="card-body" style="padding: 25px;">
                            <div class="row">
                                <div class="col-md-4 mb-4">
                                    <label for="edit_length" class="form-label fw-bold">Panjang (m) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control" id="edit_length" name="edit_length" required placeholder="0.00" min="0">
                                </div>
                                <div class="col-md-4 mb-4">
                                    <label for="edit_width" class="form-label fw-bold">Lebar (m) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control" id="edit_width" name="edit_width" required placeholder="0.00" min="0">
                                </div>
                                <div class="col-md-4 mb-4">
                                    <label for="edit_height" class="form-label fw-bold">Tinggi (m) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control" id="edit_height" name="edit_height" required placeholder="0.00" min="0">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Additional Information -->
                    <div class="card mb-4" style="border: 1px solid #e3e6f0; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        <div class="card-header" style="background-color: #f8f9fc; border-bottom: 1px solid #e3e6f0; padding: 20px 25px; border-radius: 12px 12px 0 0;">
                            <h6 class="mb-0 text-primary fw-bold">
                                <i class="fas fa-sticky-note me-2"></i>Catatan
                            </h6>
                        </div>
                        <div class="card-body" style="padding: 25px;">
                            <div class="row">
                                <div class="col-md-12">
                                    <label for="edit_remark" class="form-label fw-bold">Catatan tambahan</label>
                                    <textarea class="form-control" id="edit_remark" name="edit_remark" rows="3" placeholder="Masukkan catatan tambahan jika ada..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">SIMPAN PERUBAHAN</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css">
<style>
    /* ========================================
       PAGE-LEVEL LAYOUT FIXES (NOT FOR MODALS)
       ======================================== */
    
    /* Scope these fixes to the main page content only */
    .content-wrapper .container-fluid,
    #survey-info .container-fluid,
    #survey-detail .container-fluid {
        padding: 0;
        margin: 0;
        width: 100%;
        max-width: 100%;
    }
    
    #survey-info .row,
    #survey-detail .row {
        margin: 0;
        display: flex;
        flex-wrap: wrap;
        width: 100%;
    }
    
    #survey-info .col-12,
    #survey-detail .col-12 {
        padding: 0;
        width: 100%;
    }
    
    #survey-info .col-lg-6,
    #survey-detail .col-lg-6 {
        flex: 0 0 50%;
        max-width: 50%;
        padding: 15px;
        display: block;
    }
    
    @media (max-width: 991.98px) {
        #survey-info .col-lg-6,
        #survey-detail .col-lg-6 {
            flex: 0 0 100%;
            max-width: 100%;
        }
    }

    /* ========================================
       MODAL-SPECIFIC STYLES (RESET + ENHANCE)
       ======================================== */
    
    /* Reset Bootstrap grid inside modals to work properly */
    .modal .row {
        display: flex !important;
        flex-wrap: wrap !important;
        margin-right: -0.75rem !important;
        margin-left: -0.75rem !important;
        width: auto !important;
    }
    
    .modal .col-md-3,
    .modal .col-md-4,
    .modal .col-md-6,
    .modal .col-12 {
        position: relative !important;
        padding-right: 0.75rem !important;
        padding-left: 0.75rem !important;
        box-sizing: border-box !important;
    }
    
    .modal .col-md-3 { flex: 0 0 25% !important; max-width: 25% !important; }
    .modal .col-md-4 { flex: 0 0 33.333333% !important; max-width: 33.333333% !important; }
    .modal .col-md-6 { flex: 0 0 50% !important; max-width: 50% !important; }
    .modal .col-12 { flex: 0 0 100% !important; max-width: 100% !important; }
    
    /* Responsive stacking on small screens */
    @media (max-width: 767.98px) {
        .modal .col-md-3,
        .modal .col-md-4,
        .modal .col-md-6 {
            flex: 0 0 100% !important;
            max-width: 100% !important;
        }
    }
    
    /* Modal body padding */
    #editSurveyDetailModal .modal-body,
    #addSurveyDetailModal .modal-body {
        padding: 1.5rem !important;
    }
    
    /* Form spacing inside modal */
    .modal .row.g-3 > [class*="col-"] {
        margin-bottom: 1rem !important;
    }
    
    /* Section headers in modal */
    .modal h6.border-bottom {
        font-size: 0.9rem;
        font-weight: 600;
        padding-bottom: 0.5rem;
        margin-top: 1rem;
    }
    
    .modal h6.border-bottom:first-child {
        margin-top: 0;
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
    
    /* Modal Popup Fix */
    .modal {
        display: none !important;
        z-index: 9999 !important;
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 100% !important;
        height: 100% !important;
    }
    
    .modal.show {
        display: block !important;
    }
    
    .modal.fade {
        opacity: 0 !important;
        transition: opacity 0.15s linear !important;
    }
    
    .modal.fade.show {
        opacity: 1 !important;
    }
    
    .modal-dialog {
        margin: 50px auto !important;
        max-width: 800px !important;
        position: relative !important;
        z-index: 10000 !important;
    }
    
    .modal-content {
        border-radius: 10px !important;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3) !important;
        background-color: white !important;
        border: 1px solid #dee2e6 !important;
        opacity: 1 !important;
        position: relative !important;
        z-index: 10001 !important;
    }
    
    .modal-body {
        background-color: white !important;
        opacity: 1 !important;
    }
    
    .modal-header {
        background-color: #1e3a8a !important;
        color: white !important;
        border-bottom: 1px solid #dee2e6 !important;
    }
    
    .modal-footer {
        background-color: #f8f9fa !important;
        border-top: 1px solid #dee2e6 !important;
    }
    
    .modal-backdrop {
        z-index: 9998 !important;
        background-color: rgba(0,0,0,0.5) !important;
        backdrop-filter: blur(3px) !important;
        -webkit-backdrop-filter: blur(3px) !important;
        opacity: 1 !important;
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 100% !important;
        height: 100% !important;
    }
    
    .modal-backdrop.show {
        opacity: 1 !important;
    }
    
    .modal-backdrop.fade {
        opacity: 0 !important;
    }
    
    .modal-backdrop.fade.show {
        opacity: 1 !important;
    }
    
    /* Force modal focus */
    .modal.show .modal-dialog {
        z-index: 10000 !important;
        position: relative !important;
    }
    
    .modal.show .modal-content {
        z-index: 10001 !important;
        position: relative !important;
        background-color: white !important;
        box-shadow: 0 20px 60px rgba(0,0,0,0.5) !important;
    }
    
    /* Darken body when modal is open */
    body.modal-open {
        overflow: hidden !important;
    }
    
    body.modal-open .modal-backdrop {
        background-color: rgba(0,0,0,0.5) !important;
    }
    
    /* Container Full Width Fix - SCOPED TO SURVEY DETAILS CARD ONLY */
    #survey-detail .container-fluid {
        padding: 0;
        margin: 0;
        width: 100%;
        max-width: 100%;
    }
    
    #survey-detail .row {
        margin-right: -15px;
        margin-left: -15px;
    }
    
    /* Ensure modal rows behave correctly */
    /* Ensure specific modal padding */
    #editSurveyDetailModal .modal-body {
        padding: 2rem !important;
    }

    
    /* Pagination Styling */
    .dataTables_paginate {
        margin-top: 20px !important;
        text-align: center !important;
    }
    
    .dataTables_paginate .paginate_button {
        display: inline-block !important;
        margin: 0 2px !important;
        padding: 6px 12px !important;
        background-color: #007bff !important;
        color: white !important;
        border: 1px solid #007bff !important;
        border-radius: 4px !important;
        text-decoration: none !important;
        cursor: pointer !important;
    }
    
    .dataTables_paginate .paginate_button:hover {
        background-color: #0056b3 !important;
        border-color: #0056b3 !important;
        color: white !important;
    }
    
    .dataTables_paginate .paginate_button.current {
        background-color: #28a745 !important;
        border-color: #28a745 !important;
        color: white !important;
    }
    
    .dataTables_paginate .paginate_button.disabled {
        background-color: #6c757d !important;
        border-color: #6c757d !important;
        color: white !important;
        cursor: not-allowed !important;
    }
    
    /* DataTable Info Styling */
    .dataTables_info {
        color: #6c757d !important;
        font-size: 14px !important;
        margin-top: 10px !important;
    }
    
    /* DataTable Length Styling */
    .dataTables_length select {
        padding: 4px 8px !important;
        border: 1px solid #ced4da !important;
        border-radius: 4px !important;
        background-color: white !important;
    }
    
    /* DataTable Search Styling */
    .dataTables_filter input {
        padding: 4px 8px !important;
        border: 1px solid #ced4da !important;
        border-radius: 4px !important;
        margin-left: 5px !important;
    }
    
    /* Ensure DataTables rows have spacing */
    .dataTables_wrapper .row {
        margin-bottom: 1rem !important;
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
<script>
$(document).ready(function() {
    console.log('Survey show page loaded - FIXED VERSION');
    
    // Load Surveyors for the marketing dropdown if in draft mode
    if ($('#edit_marketing_id').length > 0) {
        $.get('{{ route("marketing.surveys.surveyors") }}')
            .done(function(data) {
                const $select = $('#edit_marketing_id');
                const currentId = $select.val();
                let options = '<option value="">Pilih Marketing...</option>';
                let foundMatch = false;

                data.forEach(function(item) {
                    const isSelected = item.id == currentId;
                    if (isSelected) foundMatch = true;
                    options += `<option value="${item.id}" ${isSelected ? 'selected' : ''}>${item.name}</option>`;
                });

                // If currently selected ID is not in the list, keep the current one
                if (!foundMatch && currentId) {
                    const currentText = $select.find('option:selected').text();
                    options += `<option value="${currentId}" selected>${currentText}</option>`;
                }

                $select.html(options).trigger('change');
            });
    }

    // Save Draft button handler
    $(document).on('click', '#save-draft-btn', function(e) {
        e.preventDefault();
        
        const marketingId = $('#edit_marketing_id').val();
        const surveyDate = $('#edit_survey_date').val();
        
        if (!surveyDate) {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Tanggal Survey harus diisi',
                confirmButtonText: 'OK'
            });
            return;
        }

        // Prepare data for update
        const updateData = {
            _token: '{{ csrf_token() }}',
            status: 'draft',
            survey_number: '{{ $survey->survey_number }}',
            survey_date: surveyDate,
            surveyor_id: '{{ $survey->surveyor_id }}',
            marketing_id: marketingId,
            survey_location: '{{ $survey->survey_location }}',
            temperature: '{{ $survey->temperature }}',
            latitude: '{{ $survey->latitude }}',
            longitude: '{{ $survey->longitude }}',
            prospect_id: '{{ $survey->prospect_id }}',
            building_id: '{{ $survey->building_id }}',
            company_name: '{{ $survey->customer->name ?? $survey->company_name }}',
            customer_type: '{{ $survey->customer_type }}',
            contact_person: '{{ $survey->contact_person }}',
            email: '{{ $survey->email }}',
            phone_1: '{{ $survey->phone_1 }}',
            phone_2: '{{ $survey->phone_2 }}',
            position: '{{ $survey->position }}',
            building_name: '{{ $survey->building->building_name ?? $survey->building_name }}',
            address_1: '{{ $survey->building->building_address ?? $survey->address_1 }}',
            address_2: '{{ $survey->building->alamat_2 ?? $survey->address_2 }}',
            province: '{{ $survey->province }}',
            city: '{{ $survey->city }}',
            district: '{{ $survey->district }}',
            village: '{{ $survey->village }}',
            postal_code: '{{ $survey->postal_code }}',
            survey_result: `{!! addslashes($survey->survey_result) !!}`,
            recommendations: `{!! addslashes($survey->recommendations) !!}`
        };

        Swal.fire({
            title: 'Menyimpan draft...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: '{{ route("marketing.surveys.update", $survey->id) }}',
            type: 'PUT',
            data: updateData,
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Survey draft berhasil disimpan',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        location.reload();
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
            error: function(xhr) {
                const response = xhr.responseJSON;
                let errorMsg = 'Terjadi kesalahan saat menyimpan draft';
                if (response && response.errors) {
                    errorMsg = Object.values(response.errors).flat().join('<br>');
                } else if (response && response.message) {
                    errorMsg = response.message;
                }
                
                Swal.fire({
                    title: 'Error!',
                    html: errorMsg,
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });
    });

    // Finalize Survey button handler with operational area validation - VALIDATION REMOVED
    $(document).on('click', '#finalize-survey-btn', function(e) {
        e.preventDefault();
        
        // Basic validation - ensure marketing and date are filled
        const marketingId = $('#edit_marketing_id').val();
        const surveyDate = $('#edit_survey_date').val();
        
        if (!marketingId || !surveyDate) {
            Swal.fire({
                icon: 'error',
                title: 'Error validation',
                text: 'Nama Marketing dan Tanggal Survey harus diisi',
                confirmButtonText: 'OK'
            });
            return;
        }

        // Proceed directly to finalize confirmation
        proceedWithFinalizeSurvey(marketingId, surveyDate);
    });
    
    // Proceed with finalize survey after validation passes
    function proceedWithFinalizeSurvey(marketingId, surveyDate) {
        Swal.fire({
            title: 'Finalize Survey?',
            text: "Survey yang sudah difinalisasi tidak dapat diubah lagi. Pastikan semua data sudah benar.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Finalize!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                // Prepare data
                const updateData = {
                    _token: '{{ csrf_token() }}',
                    status: 'submitted', // Set status to submitted
                    survey_number: '{{ $survey->survey_number }}',
                    survey_date: surveyDate,
                    surveyor_id: '{{ $survey->surveyor_id }}',
                    marketing_id: marketingId,
                    survey_location: '{{ $survey->survey_location }}',
                    temperature: '{{ $survey->temperature }}',
                    latitude: '{{ $survey->latitude }}',
                    longitude: '{{ $survey->longitude }}',
                    prospect_id: '{{ $survey->prospect_id }}',
                    building_id: '{{ $survey->building_id }}',
                    company_name: '{{ $survey->customer->name ?? $survey->company_name }}',
                    customer_type: '{{ $survey->customer_type }}',
                    contact_person: '{{ $survey->contact_person }}',
                    email: '{{ $survey->email }}',
                    phone_1: '{{ $survey->phone_1 }}',
                    phone_2: '{{ $survey->phone_2 }}',
                    position: '{{ $survey->position }}',
                    building_name: '{{ $survey->building->building_name ?? $survey->building_name }}',
                    address_1: '{{ $survey->building->building_address ?? $survey->address_1 }}',
                    address_2: '{{ $survey->building->alamat_2 ?? $survey->address_2 }}',
                    province: '{{ $survey->province }}',
                    city: '{{ $survey->city }}',
                    district: '{{ $survey->district }}',
                    village: '{{ $survey->village }}',
                    postal_code: '{{ $survey->postal_code }}',
                    survey_result: `{!! addslashes($survey->survey_result) !!}`,
                    recommendations: `{!! addslashes($survey->recommendations) !!}`
                };

                Swal.fire({
                    title: 'Memproses...',
                    text: 'Mohon tunggu sebentar...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: '{{ route("marketing.surveys.update", $survey->id) }}',
                    type: 'PUT',
                    data: updateData,
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                title: 'Berhasil!',
                                text: 'Survey berhasil difinalisasi',
                                icon: 'success',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                location.reload();
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
                    error: function(xhr) {
                        const response = xhr.responseJSON;
                        let errorMsg = 'Terjadi kesalahan saat memfinalisasi survey';
                        if (response && response.errors) {
                            errorMsg = Object.values(response.errors).flat().join('<br>');
                        } else if (response && response.message) {
                            errorMsg = response.message;
                        }
                        
                        Swal.fire({
                            title: 'Error!',
                            html: errorMsg,
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            }
        });
    }

    // Global function to force close all SweetAlert2 and loading overlays
    window.forceCloseAllSweetAlert = function() {
        try {
            // Close all SweetAlert2 instances
            if (typeof Swal !== 'undefined' && Swal.close) {
                Swal.close();
            }
            
            // Remove all SweetAlert2 DOM elements
            $('.swal2-container').remove();
            $('.swal2-backdrop').remove();
            $('.swal2-loading').remove();
            $('.swal2-no-backdrop').remove();
            
            // Force remove any stuck modals
            $('body').removeClass('swal2-shown swal2-no-backdrop');
            $('html').removeClass('swal2-shown swal2-no-backdrop');
            
            // CRITICAL: Force hide loading overlay
            $('#loadingOverlay').hide();
            $('#loadingOverlay').css('display', 'none');
            $('.loading-overlay').hide();
            $('.loading-overlay').css('display', 'none');
            
            console.log('SweetAlert2 and loading overlay cleanup completed');
        } catch (error) {
            console.error('Error during cleanup:', error);
        }
    };
    
    // Call cleanup on page load
    window.forceCloseAllSweetAlert();
    
    // Emergency cleanup button (hidden)
    $('body').append('<button id="emergencyCleanup" style="position:fixed;top:10px;right:10px;z-index:9999;background:red;color:white;border:none;padding:5px;display:none;">CLEANUP</button>');
    
    // Show emergency button if SweetAlert2 is stuck
    setTimeout(function() {
        if ($('.swal2-container').length > 0 || $('.swal2-backdrop').length > 0) {
            $('#emergencyCleanup').show();
            console.log('Emergency cleanup button shown - SweetAlert2 detected');
        }
    }, 3000);
    
    // Emergency cleanup click
    $('#emergencyCleanup').on('click', function() {
        // CRITICAL: Force hide loading overlay first
        $('#loadingOverlay').hide();
        $('#loadingOverlay').css('display', 'none');
        $('.loading-overlay').hide();
        $('.loading-overlay').css('display', 'none');
        
        window.forceCloseAllSweetAlert();
        $(this).hide();
        console.log('Emergency cleanup completed - loading overlay hidden');
    });
    
    // Initialize DataTable with robust configuration
    $('#surveyDetailsTable').DataTable({
        responsive: true,
        pageLength: 25,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
        order: [[0, 'asc']],
        columnDefs: [
            { orderable: false, targets: [0, 13] } // Disable ordering for checkbox and action columns
        ],
        // Explicit DOM positioning to prevent overlap
        // l: length changing input control
        // f: filtering input
        // r: processing display element
        // t: The table!
        // i: Table information summary
        // p: pagination control
        dom: '<"row mb-3"<"col-md-6"l><"col-md-6"f>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
        language: {
            lengthMenu: "Show _MENU_ entries",
            search: "Search:",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "Showing 0 to 0 of 0 entries",
            infoFiltered: "(filtered from _MAX_ total entries)",
            emptyTable: "No survey details found.",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            }
        }
    });

    // Tab switching functionality - FIXED
    $('#surveyTabs button[data-bs-toggle="tab"]').on('click', function(e) {
        e.preventDefault();
        var target = $(this).attr('data-bs-target');
        var tabId = $(this).attr('id');
        
        console.log('Tab clicked:', tabId, 'Target:', target);
        
        // Remove active class from all tabs and content
        $('#surveyTabs button').removeClass('active').css({
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
    });

    // MOM29: Inline Edit Lokasi Detail
    const $display = $('#lokasi-detail-display');
    const $input = $('#lokasi-detail-input');

    $display.on('click', function() {
        $display.hide();
        $input.show().focus();
    });

    $input.on('blur', function() {
        saveLokasiDetail();
    });

    $input.on('keypress', function(e) {
        if (e.which == 13) { // Enter
            saveLokasiDetail();
        }
    });

    function saveLokasiDetail() {
        const newValue = $input.val();
        const oldValue = $display.text().trim();

        if (newValue === oldValue) {
            $input.hide();
            $display.show();
            return;
        }

        // Show loading state if needed, but per request just autosave
        $.ajax({
            url: '{{ route("marketing.surveys.update-location-detail", $survey->id) }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                building_location_detail: newValue
            },
            success: function(response) {
                $display.text(newValue || 'Klik untuk isi lokasi detail');
                $input.hide();
                $display.show();
                
                // Show notification using Swal for consistency
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: response.message || 'Lokasi detail berhasil diperbarui',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
            },
            error: function(xhr) {
                $input.hide();
                $display.show();
                $input.val(oldValue === 'Klik untuk isi lokasi detail' ? '' : oldValue);
                
                const errorMsg = xhr.responseJSON ? xhr.responseJSON.message : 'Gagal memperbarui lokasi detail';
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMsg,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 4000,
                    timerProgressBar: true
                });
            }
        });
    }

    // Modal functionality - ENHANCED
    console.log('Initializing add survey detail modal...');
    
    // Button click handler - IMPROVED
    $(document).on('click', '[data-bs-target="#addSurveyDetailModal"]', function(e) {
        e.preventDefault();
        console.log('Add Survey Detail button clicked');
        
        // Reset form
        $('#addSurveyDetailForm')[0].reset();
        
        // Show modal
        $('#addSurveyDetailModal').modal('show');
    });
    
    // Modal events
    $('#addSurveyDetailModal').on('show.bs.modal', function (e) {
        console.log('Modal opening...');
        // CRITICAL: Force hide loading overlay first
        $('#loadingOverlay').hide();
        $('#loadingOverlay').css('display', 'none');
        $('.loading-overlay').hide();
        $('.loading-overlay').css('display', 'none');
        
        // Force close all SweetAlert2 instances
        window.forceCloseAllSweetAlert();
    });
    
    $('#addSurveyDetailModal').on('shown.bs.modal', function (e) {
        console.log('Modal opened successfully');
        // Focus on first input
        $('#room_name').focus();
    });
    
    // Form submit handler
    $('#addSurveyDetailForm').on('submit', function(e) {
        e.preventDefault();
        console.log('Form submitted');
        
        // CRITICAL: Force hide loading overlay first
        $('#loadingOverlay').hide();
        $('#loadingOverlay').css('display', 'none');
        $('.loading-overlay').hide();
        $('.loading-overlay').css('display', 'none');
        
        // Force close all SweetAlert2 instances
        window.forceCloseAllSweetAlert();
        
        // Collect form data
        const formData = {
            survey_id: {{ $survey->id }},
            room_name: $('#room_name').val(),
            room_type: $('#room_type').val(),
            floor: $('#floor').val(),
            scent_intensity: $('#scent_intensity').val(),
            installation_type: $('#installation_type').val(),
            qty: $('#qty').val(),
            length: $('#length').val(),
            width: $('#width').val(),
            height: $('#height').val(),
            temperature: $('#temperature').val(),
            remark: $('#remark').val()
        };
        
        console.log('Form data:', formData);
        
        // Submit via AJAX (you'll need to implement the route)
        $.post('{{ route("marketing.surveys.store-detail", $survey->id) }}', {
            ...formData,
            _token: '{{ csrf_token() }}'
        })
        .done(function(response) {
            console.log('Survey detail saved:', response);
            $('#addSurveyDetailModal').modal('hide');
            
            // Show success message with better styling
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Survey detail berhasil ditambahkan',
                confirmButtonText: 'OK',
                confirmButtonColor: '#28a745'
            }).then(() => {
                // Auto refresh to remove any stuck overlay
                location.reload();
            });
            
            // Auto refresh after 2 seconds even if user doesn't click OK
            setTimeout(function() {
                location.reload();
            }, 2000);
        })
        .fail(function(xhr, status, error) {
            console.error('Error saving survey detail:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Gagal menyimpan survey detail: ' + error,
                confirmButtonText: 'OK',
                confirmButtonColor: '#dc3545'
            });
        });
    });

    // Select All checkbox functionality
    $('#selectAll').on('change', function() {
        $('.row-checkbox').prop('checked', this.checked);
    });

    // Individual checkbox change
    $('.row-checkbox').on('change', function() {
        if (!this.checked) {
            $('#selectAll').prop('checked', false);
        }
        
        // Check if all checkboxes are checked
        var totalCheckboxes = $('.row-checkbox').length;
        var checkedCheckboxes = $('.row-checkbox:checked').length;
        
        if (checkedCheckboxes === totalCheckboxes) {
            $('#selectAll').prop('checked', true);
        }
    });

    // Add Survey Detail Form Handler
    // Duplicate event handler removed - using the first one above

    // Edit Survey Detail Form Handler
    $('#editSurveyDetailForm').on('submit', function(e) {
        e.preventDefault();
        
        // CRITICAL: Force hide loading overlay first
        $('#loadingOverlay').hide();
        $('#loadingOverlay').css('display', 'none');
        $('.loading-overlay').hide();
        $('.loading-overlay').css('display', 'none');
        
        // Force close all SweetAlert2 instances
        window.forceCloseAllSweetAlert();
        
        const detailId = $(this).data('detail-id');
        const formData = {
            room_name: $('#edit_room_name').val(),
            room_type: $('#edit_room_type').val(),
            floor: $('#edit_floor').val(),
            scent_intensity: $('#edit_scent_intensity').val(),
            installation_type: $('#edit_installation_type').val(),
            qty: $('#edit_qty').val(),
            length: $('#edit_length').val(),
            width: $('#edit_width').val(),
            height: $('#edit_height').val(),
            temperature: $('#edit_temperature').val(),
            remark: $('#edit_remark').val(),
            _token: '{{ csrf_token() }}'
        };
        
        console.log('Edit form data:', formData);
        
        // Submit langsung tanpa loading spinner
        
        // Submit via AJAX
        $.ajax({
            url: '{{ route("marketing.surveys.detail.update", ":id") }}'.replace(':id', detailId),
            type: 'PUT',
            data: formData,
            success: function(response) {
                console.log('Survey detail updated:', response);
                
                // CRITICAL: Force hide loading overlay first
                $('#loadingOverlay').hide();
                $('#loadingOverlay').css('display', 'none');
                $('.loading-overlay').hide();
                $('.loading-overlay').css('display', 'none');
                
                // Force close all SweetAlert2
                window.forceCloseAllSweetAlert();
                
                // Close modal
                $('#editSurveyDetailModal').modal('hide');
                
                // Show success message
                Swal.fire({
                    title: 'Berhasil!',
                    text: 'Survey detail berhasil diperbarui',
                    icon: 'success',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#28a745'
                }).then(() => {
                    location.reload();
                });
            },
            error: function(xhr, status, error) {
                console.error('Error updating survey detail:', error);
                
                // CRITICAL: Force hide loading overlay first
                $('#loadingOverlay').hide();
                $('#loadingOverlay').css('display', 'none');
                $('.loading-overlay').hide();
                $('.loading-overlay').css('display', 'none');
                
                // Force close all SweetAlert2
                window.forceCloseAllSweetAlert();
                
                Swal.fire({
                    title: 'Error!',
                    text: 'Gagal memperbarui survey detail: ' + error,
                    icon: 'error',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#dc3545'
                });
            }
        });
    });

    // Edit Survey Detail Handler
    $(document).on('click', '.edit-survey-detail', function(e) {
        e.preventDefault();
        const detailId = $(this).data('detail-id');
        console.log('Edit survey detail:', detailId);
        
        // CRITICAL: Force hide loading overlay first
        $('#loadingOverlay').hide();
        $('#loadingOverlay').css('display', 'none');
        $('.loading-overlay').hide();
        $('.loading-overlay').css('display', 'none');
        
        // Force close all SweetAlert2 instances
        window.forceCloseAllSweetAlert();
        
        // Show edit confirmation first
        Swal.fire({
            title: 'Edit Survey Detail',
            text: 'Apakah Anda yakin ingin mengedit data ruangan ini?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Edit!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                // Load survey detail data
                loadSurveyDetailForEdit(detailId);
            }
        });
    });
    
    function loadSurveyDetailForEdit(detailId) {
        // Show loading state
        Swal.fire({
            title: 'Loading Data...',
            text: 'Mohon tunggu sebentar...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        console.log('Loading detail ID:', detailId);

        $.get('{{ route("marketing.surveys.detail.show", ":id") }}'.replace(':id', detailId))
            .done(function(response) {
                if (response.status === 'success') {
                    try {
                        const detail = response.data;
                    let specs = {};
                    try {
                        specs = JSON.parse(detail.specifications || '{}');
                    } catch (e) {
                        console.error('JSON Parse Error:', e);
                        specs = {};
                    }
                    
                    // Store detail ID for update
                    $('#editSurveyDetailForm').data('detail-id', detailId);
                    
                    // Helper for safe trimming
                    const safeVal = (v) => (v ? String(v).trim() : '');

                    // Helper to debug and set value
                    const setValAndLog = (id, val) => {
                        const el = $(id);
                        if(el.length === 0) { console.error('Element not found:', id); return; }
                        
                        // Try exact match first
                        el.val(val).trigger('change');
                        
                        // Debugging
                        const assignedVal = el.val();
                        if (!assignedVal && val) {
                            console.warn(`Mismatch for ${id}: Target '${val}' not found in options.`);
                            // Optional: fuzzy match case insensitive
                            el.find('option').each(function() {
                                if ($(this).text().trim().toLowerCase() === String(val).trim().toLowerCase()) {
                                    console.log(`Fuzzy match found for ${id}: '${$(this).val()}' (text: ${$(this).text()})`);
                                    el.val($(this).val()).trigger('change');
                                    return false; // break
                                }
                            });
                        }
                    };

                    // Load master data for edit modal first
                    loadMasterDataForEdit().then(() => {
                        console.log('Master data loaded, setting values...');
                        Swal.close(); 
                        
                        $('#edit_room_name').val(detail.room_name);
                        
                        // Set dropdowns with debug helper
                        setValAndLog('#edit_room_type', safeVal(detail.room_type));
                        setValAndLog('#edit_floor', safeVal(specs.floor));
                        setValAndLog('#edit_scent_intensity', safeVal(specs.intensity));
                        setValAndLog('#edit_installation_type', safeVal(specs.installation_type));
                        
                        $('#edit_qty').val(specs.qty || '');
                        $('#edit_length').val(specs.length || '');
                        $('#edit_width').val(specs.width || '');
                        $('#edit_height').val(specs.height || '');
                        $('#edit_temperature').val(specs.temperature || '');
                        $('#edit_remark').val(specs.remark || '');
                        
                        $('#editSurveyDetailModal').modal('show');
                    }).catch(err => {
                        console.error('Master data load failed:', err);
                        Swal.fire('Error', 'Gagal memuat master data', 'error');
                        // Attempt to show modal anyway
                        $('#editSurveyDetailModal').modal('show');
                    });
                    
                    // Failsafe: Force show modal after 3 seconds if promise hangs
                    setTimeout(() => {
                        if (!$('#editSurveyDetailModal').hasClass('show')) {
                            console.warn('Force showing modal due to timeout');
                            Swal.close();
                            $('#editSurveyDetailModal').modal('show');
                        }
                    }, 3000);
                    } catch (err) {
                        console.error('Critical Error in loadSurveyDetailForEdit:', err);
                        Swal.fire('Error', 'Javascript Error: ' + err.message, 'error');
                    }
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Gagal memuat data survey detail',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            })
            .fail(function(xhr, status, error) {
                console.error('AJAX Error:', error);
                Swal.fire({
                    title: 'Error!',
                    text: 'Gagal memuat data survey detail: ' + error,
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            });
    }

    // Copy Survey Detail Handler
    $(document).on('click', '.copy-survey-detail', function(e) {
        e.preventDefault();
        const detailId = $(this).data('detail-id');
        console.log('Copy survey detail:', detailId);
        
        // CRITICAL: Force hide loading overlay first
        $('#loadingOverlay').hide();
        $('#loadingOverlay').css('display', 'none');
        $('.loading-overlay').hide();
        $('.loading-overlay').css('display', 'none');
        
        // Force close all SweetAlert2 instances
        window.forceCloseAllSweetAlert();
        
        // Show copy confirmation
        Swal.fire({
            title: 'Duplicate Survey Detail',
            text: 'Apakah Anda yakin ingin menduplikasi ruangan ini?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#17a2b8',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Duplicate!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                // Send copy request
                $.ajax({
                    url: '{{ route("marketing.surveys.detail.copy", ":id") }}'.replace(':id', detailId),
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        Swal.fire({
                            title: 'Berhasil!',
                            text: 'Survey detail berhasil diduplikasi',
                            icon: 'success',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#17a2b8'
                        }).then(() => {
                            location.reload();
                        });
                    },
                    error: function(xhr, status, error) {
                        Swal.fire({
                            title: 'Error!',
                            text: 'Gagal menduplikasi data: ' + error,
                            icon: 'error',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#dc3545'
                        });
                    }
                });
            }
        });
    });

    // Delete Survey Detail Handler
    $(document).on('click', '.delete-survey-detail', function(e) {
        e.preventDefault();
        const detailId = $(this).data('detail-id');
        console.log('Delete survey detail:', detailId);
        
        // CRITICAL: Force hide loading overlay first
        $('#loadingOverlay').hide();
        $('#loadingOverlay').css('display', 'none');
        $('.loading-overlay').hide();
        $('.loading-overlay').css('display', 'none');
        
        // Force close all SweetAlert2 instances
        window.forceCloseAllSweetAlert();
        
        // Show delete confirmation
        Swal.fire({
            title: 'Hapus Survey Detail',
            text: 'Apakah Anda yakin ingin menghapus data ini?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                // Send delete request
                $.ajax({
                    url: '{{ route("marketing.surveys.detail.destroy", ":id") }}'.replace(':id', detailId),
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        Swal.fire({
                            title: 'Berhasil!',
                            text: 'Data telah berhasil dihapus',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            location.reload();
                        });
                    },
                    error: function(xhr, status, error) {
                        Swal.fire({
                            title: 'Error!',
                            text: 'Gagal menghapus data: ' + error,
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            }
        });
    });

    // Load master data for dropdowns
    loadMasterData();
    
    // Fix aria-hidden warning for modals - REMOVE aria-hidden completely
    $('#editSurveyDetailModal').on('shown.bs.modal', function () {
        // Don't set aria-hidden on mainContent to avoid conflict
        // Let Bootstrap handle modal focus management
    });

    $('#editSurveyDetailModal').on('hidden.bs.modal', function () {
        // Modal closed - no need to change aria-hidden
    });

    $('#addSurveyDetailModal').on('shown.bs.modal', function () {
        // Don't set aria-hidden on mainContent to avoid conflict
        // Let Bootstrap handle modal focus management
    });

    $('#addSurveyDetailModal').on('hidden.bs.modal', function () {
        // Modal closed - no need to change aria-hidden
    });
});

function loadMasterDataForEdit() {
    return new Promise((resolve, reject) => {
        let completedRequests = 0;
        const totalRequests = 4;
        
        function checkComplete() {
            completedRequests++;
            if (completedRequests === totalRequests) {
                resolve();
            }
        }
        
        // Load room types for edit modal
        $.get('{{ route("marketing.surveys.wizard.get-room-types") }}')
            .done(function(response) {
                console.log('Room types response:', response);
                const roomTypeSelect = $('#edit_room_type');
                roomTypeSelect.empty().append('<option value="">Pilih tipe ruangan...</option>');
                if (Array.isArray(response)) {
                    response.forEach(function(roomType) {
                        const value = roomType.value || roomType.text || roomType;
                        const text = roomType.text || roomType.value || roomType;
                        roomTypeSelect.append(`<option value="${value}">${text}</option>`);
                    });
                }
                checkComplete();
            })
            .fail(function(xhr, status, error) {
                console.error('Failed to load room types:', error);
                checkComplete();
            });

        // Load floors for edit modal
        $.get('{{ route("marketing.surveys.wizard.get-floors") }}')
            .done(function(response) {
                const floorSelect = $('#edit_floor');
                floorSelect.empty().append('<option value="">Pilih lantai...</option>');
                if (Array.isArray(response)) {
                    response.forEach(function(floor) {
                        const value = floor.value || floor.text || floor;
                        const text = floor.text || floor.value || floor;
                        floorSelect.append(`<option value="${value}">${text}</option>`);
                    });
                }
                checkComplete();
            })
            .fail(function() {
                checkComplete();
            });

        // Load scent intensities for edit modal
        $.get('{{ route("marketing.surveys.wizard.get-scent-intensities") }}')
            .done(function(response) {
                const intensitySelect = $('#edit_scent_intensity');
                intensitySelect.empty().append('<option value="">Pilih intensitas wangi...</option>');
                if (Array.isArray(response)) {
                    response.forEach(function(intensity) {
                        const value = intensity.value || intensity.text || intensity;
                        const text = intensity.text || intensity.value || intensity;
                        intensitySelect.append(`<option value="${value}">${text}</option>`);
                    });
                }
                checkComplete();
            })
            .fail(function() {
                checkComplete();
            });

        // Load installation types for edit modal
        $.get('{{ route("marketing.surveys.wizard.get-installation-types") }}')
            .done(function(response) {
                const installationSelect = $('#edit_installation_type');
                installationSelect.empty().append('<option value="">Pilih installation type...</option>');
                if (Array.isArray(response)) {
                    response.forEach(function(installation) {
                        const value = installation.value || installation.text || installation;
                        const text = installation.text || installation.value || installation;
                        installationSelect.append(`<option value="${value}">${text}</option>`);
                    });
                }
                checkComplete();
            })
            .fail(function() {
                checkComplete();
            });
    });
}

function loadMasterData() {
    // Load room types
    $.get('{{ route("marketing.surveys.wizard.get-room-types") }}')
        .done(function(data) {
            var options = '<option value="">Pilih atau ketik disini..</option>';
            data.forEach(function(item) {
                options += '<option value="' + item.value + '">' + item.text + '</option>';
            });
            $('#room_type').html(options);
        });

    // Load floors
    $.get('{{ route("marketing.surveys.wizard.get-floors") }}')
        .done(function(data) {
            var options = '<option value="">Pilih atau ketik disini..</option>';
            data.forEach(function(item) {
                options += '<option value="' + item.value + '">' + item.text + '</option>';
            });
            $('#floor').html(options);
        });

    // Load scent intensities
    $.get('{{ route("marketing.surveys.wizard.get-scent-intensities") }}')
        .done(function(data) {
            var options = '<option value="">Pilih atau ketik disini..</option>';
            data.forEach(function(item) {
                options += '<option value="' + item.value + '">' + item.text + '</option>';
            });
            $('#scent_intensity').html(options);
        });

    // Load installation types
    $.get('{{ route("marketing.surveys.wizard.get-installation-types") }}')
        .done(function(data) {
            var options = '<option value="">Pilih atau ketik disini..</option>';
            data.forEach(function(item) {
                options += '<option value="' + item.value + '">' + item.text + '</option>';
            });
            $('#installation_type').html(options);
        });
}

function editSurveyDetail(detailId) {
    // Implementation for editing survey detail
    console.log('Edit survey detail:', detailId);
}

function copySurveyDetail(detailId) {
    // Implementation for copying survey detail
    console.log('Copy survey detail:', detailId);
}

function approveSurvey(id) {
    Swal.fire({
        title: 'Approve Survey?',
        text: "Are you sure you want to approve this survey?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Approve!'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Processing...',
                text: 'Please wait...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: '{{ route("marketing.surveys.approve", ":id") }}'.replace(':id', id),
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Approved!',
                        text: 'Survey has been approved successfully.',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        location.reload();
                    });
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: xhr.responseJSON.message || 'Failed to approve survey.'
                    });
                }
            });
        }
    });
}

function unpostSurvey(id) {
    Swal.fire({
        title: 'Unpost Survey?',
        text: "Are you sure you want to revert this survey to Draft status?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Unpost!',
        confirmButtonTextColor: '#000'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Processing...',
                text: 'Please wait...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: '{{ route("marketing.surveys.unpost", ":id") }}'.replace(':id', id),
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Unposted!',
                        text: response.message,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        location.reload();
                    });
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: xhr.responseJSON.message || 'Failed to unpost survey.'
                    });
                }
            });
        }
    });
}

function rejectSurvey(id) {
    Swal.fire({
        title: 'Reject Survey',
        text: "Please provide a reason for rejection:",
        input: 'textarea',
        inputPlaceholder: 'Type your reason here...',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Reject Survey',
        inputValidator: (value) => {
            if (!value) {
                return 'You need to write a reason!'
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Processing...',
                text: 'Please wait...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: '{{ route("marketing.surveys.reject", ":id") }}'.replace(':id', id),
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    rejection_reason: result.value
                },
                success: function(response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Rejected!',
                        text: 'Survey has been rejected.',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        location.reload();
                    });
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: xhr.responseJSON.message || 'Failed to reject survey.'
                    });
                }
            });
        }
    });
}



function deleteSurveyDetail(detailId) {
    showConfirmDialog({
        title: 'Hapus Survey Detail?',
        text: 'Apakah Anda yakin ingin menghapus survey detail ini?',
        icon: 'warning',
        confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            console.log('Delete survey detail:', detailId);
        }
    });
}
</script>
<script>
function approveSurvey(id) {
    Swal.fire({
        title: 'Approve Survey?',
        text: 'Apakah Anda yakin ingin approve survey ini?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, approve',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (!result.isConfirmed) return;

        Swal.fire({
            title: 'Memproses...',
            text: 'Mohon tunggu...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: '{{ route("marketing.surveys.approve", ":id") }}'.replace(':id', id),
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function () {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: 'Survey berhasil di-approve.',
                    confirmButtonText: 'OK'
                }).then(() => {
                    location.reload();
                });
            },
            error: function (xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: xhr.responseJSON?.message || 'Gagal approve survey.'
                });
            }
        });
    });
}

function unpostSurvey(id) {
    Swal.fire({
        title: 'Unpost Survey?',
        text: 'Apakah Anda yakin ingin mengembalikan survey ini ke status Draft?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, unpost',
        cancelButtonText: 'Batal',
        confirmButtonTextColor: '#000'
    }).then((result) => {
        if (!result.isConfirmed) return;

        Swal.fire({
            title: 'Memproses...',
            text: 'Mohon tunggu...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: '{{ route("marketing.surveys.unpost", ":id") }}'.replace(':id', id),
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function (response) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: response.message,
                    confirmButtonText: 'OK'
                }).then(() => {
                    location.reload();
                });
            },
            error: function (xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: xhr.responseJSON?.message || 'Gagal unpost survey.'
                });
            }
        });
    });
}

function rejectSurvey(id) {
    Swal.fire({
        title: 'Tolak Survey',
        text: 'Silakan isi alasan penolakan:',
        input: 'textarea',
        inputPlaceholder: 'Tulis alasan di sini...',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Tolak Survey',
        cancelButtonText: 'Batal',
        inputValidator: (value) => {
            if (!value) {
                return 'Alasan penolakan wajib diisi.';
            }
        }
    }).then((result) => {
        if (!result.isConfirmed) return;

        Swal.fire({
            title: 'Memproses...',
            text: 'Mohon tunggu...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: '{{ route("marketing.surveys.reject", ":id") }}'.replace(':id', id),
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                rejection_reason: result.value
            },
            success: function () {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: 'Survey berhasil ditolak.',
                    confirmButtonText: 'OK'
                }).then(() => {
                    location.reload();
                });
            },
            error: function (xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: xhr.responseJSON?.message || 'Gagal menolak survey.'
                });
            }
        });
    });
}

function deleteSurveyDetail(detailId) {
    showConfirmDialog({
        title: 'Hapus Survey Detail?',
        text: 'Apakah Anda yakin ingin menghapus survey detail ini?',
        icon: 'warning',
        confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (!result.isConfirmed) return;

        console.log('Delete survey detail:', detailId);
    });
}
</script>
@endpush
