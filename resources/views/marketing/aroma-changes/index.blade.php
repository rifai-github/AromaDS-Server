@extends('layouts.app')

@section('title', 'Aroma Switching')
@section('breadcrumb', 'Home / Marketing / Aroma Switching')

@section('content')
<style>
    /* Global overflow control */
    html, body {
        overflow-x: hidden;
        max-width: 100vw;
    }

    *, *::before, *::after {
        box-sizing: border-box;
    }

    /* Button Styles */
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
    }

    .btn-secondary {
        background-color: #f3f4f6;
        color: #6b7280;
        border: 1px solid #d1d5db;
    }

    .btn-secondary:hover {
        background-color: #e5e7eb;
        color: #4b5563;
    }

    .btn-success {
        background-color: #16a34a;
        color: white;
    }

    .btn-info {
        background-color: #0ea5e9;
        color: white;
    }

    .btn-warning {
        background-color: #f59e0b;
        color: white;
    }

    .btn-danger {
        background-color: #dc2626;
        color: white;
    }

    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
    }

    /* Filter Section */
    .filter-section {
        background: #f8fafc;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        border: 1px solid #e2e8f0;
    }

    .filter-title {
        font-size: 16px;
        font-weight: 600;
        color: #334155;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .filter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 16px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
    }

    .form-label {
        font-size: 13px;
        font-weight: 500;
        color: #475569;
        margin-bottom: 4px;
    }

    .form-control {
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.2s ease;
    }

    .form-control:focus {
        outline: none;
        border-color: #214589;
        box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
    }

    .filter-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    /* Stats Cards */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 16px;
        margin-bottom: 20px;
    }

    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }

    .stat-icon.secondary { background: #f1f5f9; color: #64748b; }
    .stat-icon.warning { background: #fef3c7; color: #f59e0b; }
    .stat-icon.success { background: #dcfce7; color: #16a34a; }
    .stat-icon.info { background: #dbeafe; color: #0ea5e9; }
    .stat-icon.primary { background: #e0e7ff; color: #4f46e5; }

    .stat-content {
        flex: 1;
    }

    .stat-label {
        font-size: 13px;
        color: #64748b;
        font-weight: 500;
    }

    .stat-value {
        font-size: 28px;
        font-weight: 700;
        color: #1e293b;
    }

    /* Table Styles */
    .table-container {
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .table-header {
        background: #f1f5f9;
        padding: 16px 20px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }

    .table-title {
        font-size: 18px;
        font-weight: 600;
        color: #1e293b;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .table-wrapper {
        overflow-x: auto;
        max-height: 70vh;
        overflow-y: auto;
        border-top: 1px solid #e2e8f0;
    }

    .data-table thead th {
        position: sticky;
        top: 0;
        background: #f8fafc;
        z-index: 10;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
    }

    .data-table thead {
        background: #f8fafc;
    }

    .data-table th {
        padding: 12px 16px;
        text-align: left;
        font-size: 13px;
        font-weight: 600;
        color: #475569;
        border-bottom: 2px solid #e2e8f0;
        white-space: nowrap;
    }

    .data-table td {
        padding: 12px 16px;
        font-size: 14px;
        color: #334155;
        border-bottom: 1px solid #f1f5f9;
    }

    .data-table tbody tr {
        transition: background-color 0.2s ease;
    }

    .data-table tbody tr:hover {
        background-color: #f8fafc;
    }

    /* Badge Styles */
    .badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
        line-height: 1;
        white-space: nowrap;
    }

    .badge-success {
        background-color: #dcfce7;
        color: #166534;
        border: 1px solid #bbf7d0;
    }

    .badge-warning {
        background-color: #fef3c7;
        color: #d97706;
        border: 1px solid #fde68a;
    }

    .badge-danger {
        background-color: #fee2e2;
        color: #dc2626;
        border: 1px solid #fecaca;
    }

    .badge-info {
        background-color: #dbeafe;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
    }

    .badge-secondary {
        background-color: #f1f5f9;
        color: #64748b;
        border: 1px solid #e2e8f0;
    }

    .badge-primary {
        background-color: #e0e7ff;
        color: #4f46e5;
        border: 1px solid #c7d2fe;
    }

    .badge-dark {
        background-color: #e2e8f0;
        color: #334155;
        border: 1px solid #cbd5e1;
    }

    /* Modal Styles */
    .mktg-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }

    .mktg-modal.show {
        display: flex !important;
    }

    .mktg-dialog {
        background: white;
        border-radius: 8px;
        width: 90%;
        max-width: 900px;
        max-height: 90vh;
        overflow: hidden;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    }

    .mktg-header {
        background: #f1f5f9;
        padding: 16px 20px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .mktg-title {
        font-size: 18px;
        font-weight: 600;
        color: #1e293b;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .mktg-close {
        background: none;
        border: none;
        font-size: 20px;
        color: #64748b;
        cursor: pointer;
        padding: 4px;
        border-radius: 4px;
        transition: color 0.2s ease;
    }

    .mktg-close:hover {
        color: #334155;
    }

    .mktg-body {
        padding: 20px;
        max-height: 60vh;
        overflow-y: auto;
    }

    .mktg-footer {
        background: #f8fafc;
        padding: 16px 20px;
        border-top: 1px solid #e2e8f0;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    /* Loading Spinner */
    .loading-spinner {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
    }

    .spinner {
        width: 50px;
        height: 50px;
        border: 4px solid #e3f2fd;
        border-top: 4px solid #214589;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin-bottom: 15px;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #64748b;
    }

    .empty-state i {
        font-size: 64px;
        margin-bottom: 20px;
        color: #cbd5e1;
    }

    .empty-state h3 {
        font-size: 20px;
        font-weight: 600;
        color: #334155;
        margin-bottom: 8px;
    }

    .empty-state p {
        font-size: 14px;
        color: #64748b;
    }

    .text-danger {
        color: #dc2626;
    }

    .text-muted {
        color: #6b7280;
        font-size: 13px;
    }

    .text-success {
        color: #16a34a;
    }

    /* Aroma Info Box */
    .aroma-info-box {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 16px;
    }

    .aroma-info-box strong {
        font-size: 14px;
    }

    .aroma-change-arrow {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 12px;
        background: #f8fafc;
        border-radius: 8px;
        margin-bottom: 16px;
    }

    .aroma-change-arrow .old-aroma,
    .aroma-change-arrow .new-aroma {
        flex: 1;
        padding: 12px;
        border-radius: 6px;
        text-align: center;
    }

    .aroma-change-arrow .old-aroma {
        background: #fee2e2;
        color: #991b1b;
    }

    .aroma-change-arrow .new-aroma {
        background: #dcfce7;
        color: #166534;
    }

    .aroma-change-arrow .arrow-icon {
        font-size: 24px;
        color: #64748b;
    }
    
    /* Select2 dropdown di dalam marketing modal harus di atas overlay */
    .select2-dropdown {
        z-index: 99999999 !important;
    }
    .select2-container--open .select2-dropdown {
        z-index: 99999999 !important;
    }
</style>

<div class="container-fluid">
    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon secondary">
                <i class="fas fa-file-alt"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Draft</div>
                <div class="stat-value">{{ $stats['draft'] ?? 0 }}</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Pending</div>
                <div class="stat-value">{{ $stats['pending'] ?? 0 }}</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon success">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Approved</div>
                <div class="stat-value">{{ $stats['approved'] ?? 0 }}</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="fas fa-check-double"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Completed</div>
                <div class="stat-value">{{ $stats['completed'] ?? 0 }}</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon info">
                <i class="fas fa-spray-can"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Total</div>
                <div class="stat-value">{{ $stats['total'] ?? 0 }}</div>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <div class="filter-title">
            <i class="fas fa-filter"></i>
            Filter Aroma Switching
        </div>
        <form action="{{ route('marketing.aroma-changes.index') }}" method="GET">
            <div class="filter-grid">
                <div class="form-group">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Change #, contract, building..." value="{{ request('search') }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="">All Status</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="waiting_for_approval" {{ request('status') == 'waiting_for_approval' ? 'selected' : '' }}>Waiting for Approval</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Apply Filter
                </button>
                <a href="{{ route('marketing.aroma-changes.index') }}" class="btn btn-secondary">
                    <i class="fas fa-sync-alt"></i> Reset Filter
                </a>
                <button type="button" class="btn btn-success" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i> Request Aroma Change
                </button>
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <div class="table-container">
        <div class="table-header">
            <h3 class="table-title">
                <i class="fas fa-spray-can"></i>
                Aroma Change Requests
            </h3>
            <div class="table-actions">
                <span class="text-sm text-gray-600">
                    Showing {{ $changes->firstItem() ?? 0 }} to {{ $changes->lastItem() ?? 0 }} 
                    of {{ $changes->total() }} entries
                </span>
            </div>
        </div>

        <div class="table-wrapper">
            @if($changes->count() > 0)
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Change #</th>
                            <th>Contract</th>
                            <th>Building / Room</th>
                            <th>Previous Aroma</th>
                            <th>New Aroma</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Requested By</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($changes as $item)
                        <tr>
                            <td><strong>{{ $item->change_number }}</strong></td>
                            <td>
                                @if($item->contract)
                                <a href="{{ route('marketing.contracts.show', $item->contract_id) }}" target="_blank" rel="noopener noreferrer">
                                    {{ $item->contract->contract_number ?? '-' }}
                                </a>
                                <br>
                                <small class="text-muted">{{ $item->contract->customer->name ?? '' }}</small>
                                @else
                                -
                                @endif
                            </td>
                            <td>
                                @if($item->building)
                                <i class="fas fa-building text-info"></i>
                                {{ $item->building->name ?? '-' }}
                                @endif
                                @if($item->room)
                                <br>
                                <small class="text-muted"><i class="fas fa-door-open"></i> {{ $item->room->room_name ?? '' }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-danger">
                                    {{ $item->previous_aroma_name ?: $item->previous_aroma_code ?: '-' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-success">
                                    {{ $item->new_aroma_name ?: $item->new_aroma_code ?: '-' }}
                                </span>
                            </td>
                            <td>{{ Str::limit($item->change_reason, 30) }}</td>
                            <td>
                                <span class="badge {{ $item->status_badge }}">
                                    {{ $item->status_text }}
                                </span>
                            </td>
                            <td>{{ $item->requestedBy->name ?? '-' }}</td>
                            <td>
                                <div>
                                    <strong>{{ $item->created_at ? $item->created_at->format('d/M/Y') : '-' }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $item->created_at ? $item->created_at->format('H:i') : '' }}</small>
                                </div>
                            </td>
                            <td>
                                <div style="display: flex; gap: 4px;">
                                    <button class="btn btn-info btn-sm" onclick="viewAromaChange({{ $item->id }})" title="View">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    @if($item->status == 'draft')
                                    <button class="btn btn-primary btn-sm" onclick="submitAromaChange({{ $item->id }})" title="Submit for Approval">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="deleteAromaChange({{ $item->id }})" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    @endif
                                    @if($item->status == 'pending')
                                    <button class="btn btn-success btn-sm" onclick="approveAromaChange({{ $item->id }})" title="Approve">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="rejectAromaChange({{ $item->id }})" title="Reject">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    @endif
                                    @if($item->status == 'approved')
                                    <button class="btn btn-warning btn-sm" onclick="applyAromaChange({{ $item->id }})" title="Apply Change">
                                        <i class="fas fa-play"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty-state">
                    <i class="fas fa-spray-can"></i>
                    <h3>No Aroma Changes Found</h3>
                    <p>No Aroma change requests match your current filter criteria.</p>
                    <p><a href="javascript:void(0)" onclick="openCreateModal()">Create the first aroma change request</a></p>
                </div>
            @endif
        </div>

        @if($changes->hasPages())
            <div class="pagination-wrapper" style="padding: 16px;">
                {{ $changes->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Create Aroma Change Modal -->
<div class="mktg-modal" id="createAromaModal">
    <div class="mktg-dialog" onclick="event.stopPropagation()">
        <div class="mktg-header">
            <h5 class="mktg-title">
                <i class="fas fa-spray-can"></i>
                Request Aroma Change
            </h5>
            <button type="button" class="mktg-close" onclick="closeCreateModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="mktg-body">
            <div class="aroma-info-box">
                <i class="fas fa-info-circle"></i>
                <strong>Catatan:</strong> Pergantian aroma akan berlaku di periode service routine <strong>berikutnya</strong>, bukan periode saat ini.
            </div>
            
            <form id="createAromaForm">
                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label">Contract <span class="text-danger">*</span></label>
                    <select name="contract_id" id="contractSelect" class="form-control" required>
                        <option value="">Loading...</option>
                    </select>
                    <small class="text-muted">Pilih contract yang aktif</small>
                </div>

                <div id="contractInfo" style="display:none; background: #f8fafc; padding: 12px; border-radius: 6px; border: 1px solid #e2e8f0; margin-bottom: 16px;">
                    <strong>Contract Info:</strong>
                    <div id="contractInfoContent"></div>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label">Room <span class="text-danger">*</span></label>
                    <select name="contract_room_id" id="roomSelect" class="form-control" required disabled>
                        <option value="">Select contract first...</option>
                    </select>
                    <small class="text-muted">Pilih room yang akan diganti aromanya</small>
                </div>

                <div id="currentAromaInfo" style="display:none; margin-bottom: 16px;">
                    <label class="form-label">Current Aroma</label>
                    <div id="currentAromaContent" style="background: #fee2e2; padding: 12px; border-radius: 6px; color: #991b1b;"></div>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label">New Aroma <span class="text-danger">*</span></label>
                    <select name="new_aroma" id="aromaSelect" class="form-control" required>
                        <option value="">Loading aromas...</option>
                    </select>
                    <small class="text-muted">Pilih aroma baru yang diinginkan</small>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label">Effective From (Optional)</label>
                    <select name="effective_schedule_id" id="effectiveScheduleSelect" class="form-control">
                        <option value="">Start from immediate next service...</option>
                    </select>
                    <small class="text-muted">Biarkan kosong untuk segera (Immediate Next Service). Atau pilih bulan spesifik.</small>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label">Reason <span class="text-danger">*</span></label>
                    <textarea name="change_reason" class="form-control" rows="3" placeholder="Alasan pergantian aroma..." required></textarea>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label">Notes (Optional)</label>
                    <textarea name="change_notes" class="form-control" rows="2" placeholder="Catatan tambahan..."></textarea>
                </div>
            </form>
        </div>
        <div class="mktg-footer">
            <button type="button" class="btn btn-secondary" onclick="closeCreateModal()">
                <i class="fas fa-times"></i> Cancel
            </button>
            <button type="button" class="btn btn-primary" onclick="submitCreateForm()">
                <i class="fas fa-save"></i> Create Request
            </button>
        </div>
    </div>
</div>

<!-- View Aroma Change Modal -->
<div class="mktg-modal" id="viewAromaModal">
    <div class="mktg-dialog" onclick="event.stopPropagation()">
        <div class="mktg-header">
            <h5 class="mktg-title">
                <i class="fas fa-info-circle"></i>
                Aroma Change Details
            </h5>
            <button type="button" class="mktg-close" onclick="closeViewModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="mktg-body">
            <div id="viewAromaContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
        <div class="mktg-footer">
            <button type="button" class="btn btn-secondary" onclick="closeViewModal()">
                <i class="fas fa-times"></i> Close
            </button>
        </div>
    </div>
</div>

@endsection
@push('scripts')
<script>
const aromaChangeBootstrap = {
    contracts: @json(($activeContracts ?? collect())->values()),
    aromas: [],
};

$(document).ready(function() {
    loadAromas();
});

// Open create modal
function openCreateModal() {
    $('#createAromaModal').addClass('show');
    loadContracts();
    loadAromas();
}

// Close create modal
function closeCreateModal() {
    $('#createAromaModal').removeClass('show');
    $('#createAromaForm')[0].reset();
    $('#contractInfo').hide();
    $('#currentAromaInfo').hide();
    $('#roomSelect').prop('disabled', true).html('<option value="">Select contract first...</option>');
}

// Close view modal
function closeViewModal() {
    $('#viewAromaModal').removeClass('show');
}

// Close modal when clicking outside - REMOVED per user request
// NOTE: Klik di luar area dialog (overlay) tidak akan menutup modal karena event.stopPropagation() pada .mktg-dialog.

// Close modal with ESC key
$(document).on('keydown', function(e) {
    if (e.key === 'Escape') {
        $('.mktg-modal').removeClass('show');
    }
});

// Load aromas (from MasterProduct - same as Quotation Wizard)
function loadAromas() {
    if (Array.isArray(aromaChangeBootstrap.aromas) && aromaChangeBootstrap.aromas.length > 0) {
        populateAromaOptions(aromaChangeBootstrap.aromas);
        return;
    }

    $.ajax({
        url: '{{ route("marketing.aroma-changes.get-aroma-products") }}',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            let products = Array.isArray(response)
                ? response
                : (Array.isArray(response?.data) ? response.data : []);

            aromaChangeBootstrap.aromas = products;
            populateAromaOptions(products);
        },
        error: function(xhr) {
            console.error('Error loading aromas:', xhr);
            $('#aromaSelect').html('<option value="">Error loading aromas</option>');
        }
    });
}

function loadContracts() {
    if (Array.isArray(aromaChangeBootstrap.contracts) && aromaChangeBootstrap.contracts.length > 0) {
        populateContractOptions(aromaChangeBootstrap.contracts);
        return;
    }

    $.ajax({
        url: '/marketing/aroma-changes/get-contracts',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            let contracts = Array.isArray(response)
                ? response
                : (Array.isArray(response?.data) ? response.data : []);

            aromaChangeBootstrap.contracts = contracts;
            populateContractOptions(contracts);
        },
        error: function(xhr) {
            console.error('Error loading contracts:', xhr);
            alert('Error loading contracts. Please refresh the page.');
        }
    });
}

// When contract selected - load rooms
$(document).on('change', '#contractSelect', function() {
    const contractId = $(this).val();
    const roomSelect = $('#roomSelect');
    
    if (contractId) {
        roomSelect.prop('disabled', false).html('<option value="">Loading rooms...</option>');
        
        // Get contract rooms via AJAX
        $.ajax({
            url: `/marketing/aroma-changes/create?contract_id=${contractId}`,
            method: 'GET',
            dataType: 'json',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                roomSelect.empty().append('<option value="">Select room...</option>');
                
                if (response.data && response.data.contract_rooms) {
                    const rooms = response.data.contract_rooms;

                    if (rooms.length > 0) {
                        rooms.forEach(contractRoom => {
                            const roomName = contractRoom.room?.room_name || contractRoom.room?.name || 'Unknown Room';
                            const buildingName = contractRoom.room?.building?.name
                                || contractRoom.room?.building?.nama_gedung
                                || contractRoom.room?.building?.building_name
                                || '';
                            const currentAroma = contractRoom.aroma_name || contractRoom.aroma_code || 'No aroma set';

                            roomSelect.append($('<option>', {
                                value: contractRoom.id,
                                'data-room-id': contractRoom.room_id,
                                'data-building-id': contractRoom.room?.building_id,
                                'data-aroma-code': contractRoom.aroma_code || '',
                                'data-aroma-name': contractRoom.aroma_name || '',
                                'data-aroma-product-id': contractRoom.aroma_product_id || '',
                                'data-aroma-product-name': contractRoom.aroma_product_name || '',
                                'data-aroma-sku': contractRoom.aroma_sku || '',
                                'data-aroma-packaging-size': contractRoom.aroma_packaging_size || '',
                                'data-aroma-brand-line': contractRoom.aroma_brand_line || '',
                                text: buildingName ? `${buildingName} - ${roomName} (${currentAroma})` : `${roomName} (${currentAroma})`
                            }));
                        });
                    } else {
                        roomSelect.append('<option value="">No rooms found for this contract</option>');
                    }
                    
                    // Show contract info
                    const contract = response.data.contract;
                    if (contract) {
                        const customerName = contract.customer?.company_name || contract.customer?.name || '-';
                        $('#contractInfoContent').html(`
                            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-top: 8px;">
                                <div><strong>Customer:</strong> ${customerName}</div>
                                <div><strong>Period:</strong> ${formatDate(contract.start_date)} - ${formatDate(contract.end_date)}</div>
                            </div>
                        `);
                        $('#contractInfo').show();
                    }
                } else {
                    roomSelect.append('<option value="">No rooms found</option>');
                }
                
                initSelect2For(roomSelect);
            },
            error: function(xhr) {
                console.error('Error loading rooms:', xhr);
                roomSelect.html('<option value="">Error loading rooms</option>');
            }
        });
    } else {
        roomSelect.prop('disabled', true).html('<option value="">Select contract first...</option>');
        $('#contractInfo').hide();
        $('#currentAromaInfo').hide();
    }
});

// When room selected - show current aroma
$(document).on('change', '#roomSelect', function() {
    const selectedOption = $(this).find('option:selected');
    const aromaCode = selectedOption.data('aroma-code');
    const aromaName = selectedOption.data('aroma-name');
    const aromaProductId = selectedOption.data('aroma-product-id');
    const aromaProductName = selectedOption.data('aroma-product-name');
    const aromaSku = selectedOption.data('aroma-sku');
    const aromaPackagingSize = selectedOption.data('aroma-packaging-size');
    const brandLine = selectedOption.data('aroma-brand-line') || '';
    
    if (aromaCode || aromaName) {
        $('#currentAromaContent').html(`
            <i class="fas fa-spray-can"></i> 
            <strong>${aromaName || aromaCode}</strong>
            ${aromaProductName && aromaProductName !== aromaName ? `<br><small>Product: ${aromaProductName}</small>` : ''}
            ${aromaSku || aromaCode ? `<br><small>Code/SKU: ${aromaSku || aromaCode}</small>` : ''}
            ${aromaPackagingSize ? `<br><small>Packaging: ${aromaPackagingSize}</small>` : ''}
        `);
        $('#currentAromaInfo').show();
    } else {
        $('#currentAromaInfo').hide();
    }
    
    // Load available schedules for "Effective From"
    const contractId = $('#contractSelect').val();
    const contractRoomId = selectedOption.val();
    loadSchedules(contractId, contractRoomId);
    populateAromaOptions(aromaChangeBootstrap.aromas || [], brandLine, aromaName, aromaProductId);
});

// Load Schedules Function
function loadSchedules(contractId, contractRoomId) {
    const scheduleSelect = $('#effectiveScheduleSelect');
    scheduleSelect.html('<option value="">Loading...</option>');
    
    if (!contractId || !contractRoomId) {
         scheduleSelect.html('<option value="">Start from immediate next service...</option>');
         return;
    }

    $.ajax({
        url: '{{ route("marketing.aroma-changes.get-schedules") }}',
        method: 'GET',
        data: { contract_id: contractId, contract_room_id: contractRoomId },
        success: function(response) {
            scheduleSelect.empty();
            scheduleSelect.append('<option value="">Start from immediate next service...</option>');
            
            if (response && response.length > 0) {
                response.forEach(function(schedule) {
                    scheduleSelect.append(new Option(schedule.label, schedule.id));
                });
            }
            initSelect2For(scheduleSelect);
        },
        error: function(xhr) {
            console.error('Error loading schedules:', xhr);
            scheduleSelect.html('<option value="">Error loading schedules</option>');
            initSelect2For(scheduleSelect);
        }
    });
}

// Format date helper
function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    const day = String(date.getDate()).padStart(2, '0');
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const month = months[date.getMonth()];
    const year = date.getFullYear();
    return `${day} ${month} ${year}`;
}

function normalizeBrandLine(value) {
    return (value || '').toString().trim().toLowerCase().replace(/\s+/g, ' ');
}

function normalizeAromaName(value) {
    return (value || '').toString().trim().toLowerCase().replace(/\s+/g, ' ');
}

// Grade order client-confirmed: Luxo (1, lowest) < Artisan (2) < Signature (3, highest).
// Keep in sync with AromaChange::BRAND_LINE_GRADE on the backend.
const BRAND_LINE_GRADE = { luxo: 1, artisan: 2, signature: 3 };

function brandLineGrade(brandLine) {
    const key = normalizeBrandLine(brandLine);
    return BRAND_LINE_GRADE[key] ?? null;
}

let currentAromaGrade = null;

function populateAromaOptions(products, currentBrandLine = '', currentAromaName = '', currentProductId = '') {
    const select = $('#aromaSelect');
    select.empty().append('<option value="">Select new aroma...</option>');

    currentAromaGrade = brandLineGrade(currentBrandLine);

    const normalizedCurrentAromaName = normalizeAromaName(currentAromaName);
    const currentProductIdText = currentProductId ? currentProductId.toString() : '';

    // Show every grade (Luxo/Artisan/Signature), not just the current one — the technician
    // can switch to any variant; the down-grade approval rule is enforced server-side.
    const availableProducts = products.filter(product => {
        const sameProduct = currentProductIdText && product.id?.toString() === currentProductIdText;
        const sameAroma = normalizedCurrentAromaName && normalizeAromaName(product.display_name || product.name) === normalizedCurrentAromaName;

        return !sameProduct && !sameAroma;
    });

    if (products.length === 0) {
        select.append('<option value="">No aroma products found</option>');
        initSelect2For(select);
        return;
    }

    if (availableProducts.length === 0) {
        select.append('<option value="">No other aroma products found</option>');
        initSelect2For(select);
        return;
    }

    const groups = {};
    availableProducts.forEach(product => {
        const groupLabel = product.brand_line || 'Other';
        groups[groupLabel] = groups[groupLabel] || [];
        groups[groupLabel].push(product);
    });

    Object.keys(groups)
        .sort((a, b) => (brandLineGrade(a) ?? 99) - (brandLineGrade(b) ?? 99))
        .forEach(groupLabel => {
            const grade = brandLineGrade(groupLabel);
            const gradeNote = currentAromaGrade && grade
                ? (grade < currentAromaGrade ? ' ↓ perlu approval' : (grade > currentAromaGrade ? ' ↑ naik grade' : ''))
                : '';
            const optgroup = $('<optgroup>', { label: groupLabel + gradeNote });

            groups[groupLabel].forEach(product => {
                const displayName = product.display_name || product.name;

                optgroup.append($('<option>', {
                    value: product.id,
                    'data-name': displayName || '',
                    'data-product-name': product.product_name || product.name || '',
                    'data-sku': product.sku || '',
                    'data-variant': product.variant || '',
                    'data-brand-line': product.brand_line || '',
                    'data-brand-line-grade': grade ?? '',
                    'data-display-name': displayName,
                    text: displayName
                }));
            });

            select.append(optgroup);
        });

    initSelect2For(select);
}

// Show a warning when the selected new aroma is a lower grade than the current one,
// mirroring the server-side rule: grade turun = perlu approval atasan, naik/sama = auto.
$(document).on('change', '#aromaSelect', function() {
    const selected = $(this).find('option:selected');
    const newGrade = selected.data('brand-line-grade');
    const warningId = 'aromaGradeWarning';
    $('#' + warningId).remove();

    if (!currentAromaGrade || !newGrade) {
        return;
    }

    if (newGrade < currentAromaGrade) {
        $('<div>', {
            id: warningId,
            class: 'text-muted',
            style: 'color: #d97706; margin-top: 6px;',
            html: '<i class="fas fa-exclamation-triangle"></i> Turun grade ke <strong>' + escapeHtml(selected.data('brand-line') || '') + '</strong> — request ini akan menunggu approval atasan sebelum diterapkan.'
        }).insertAfter($(this).next('.select2-container'));
    }
});

function populateContractOptions(contracts) {
    const select = $('#contractSelect');
    select.empty().append('<option value="">Select contract...</option>');

    if (contracts.length > 0) {
        contracts.forEach(contract => {
            select.append($('<option>', {
                value: contract.id,
                text: contract.text
            }));
        });
        initSelect2For(select);
        return;
    }

    select.append('<option value="">No active contracts found</option>');
    initSelect2For(select);
}

// Submit create form
function submitCreateForm() {
    const contractId = $('#contractSelect').val();
    const contractRoomId = $('#roomSelect').val();
    const newAromaOption = $('#aromaSelect option:selected');
    const roomOption = $('#roomSelect option:selected');
    
    if (!contractId || !contractRoomId || !newAromaOption.val()) {
        alert('Please fill all required fields');
        return;
    }
    
    // Get aroma data from selected option
    const aromaName = newAromaOption.data('display-name') || newAromaOption.data('name') || newAromaOption.text();
    const aromaVariant = newAromaOption.data('variant') || '';
    const aromaSku = newAromaOption.data('sku') || '';
    
    const formData = {
        contract_id: contractId,
        building_id: roomOption.data('building-id'),
        room_id: roomOption.data('room-id'),
        contract_room_id: contractRoomId,
        new_aroma_code: aromaSku || aromaVariant || aromaName,
        new_aroma_name: aromaName,
        new_product_type_id: newAromaOption.val(),
        change_reason: $('[name="change_reason"]').val(),
        change_description: $('[name="change_description"]').val() || '', // Optional
        change_notes: $('[name="change_notes"]').val() || '', // Optional
        effective_schedule_id: $('#effectiveScheduleSelect').val()
    };

    $.ajax({
        url: '{{ route("marketing.aroma-changes.store") }}',
        method: 'POST',
        data: formData,
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(response) {
            closeCreateModal();
            alert('Aroma change request created successfully!');
            location.reload();
        },
        error: function(xhr) {
            let message = xhr.responseJSON?.message || 'Failed to create aroma change';
            if (xhr.responseJSON?.errors) {
                message = Object.values(xhr.responseJSON.errors).flat().join('\n');
            }
            alert('Error: ' + message);
        }
    });
}

function escapeHtml(value) {
    return $('<div>').text(value ?? '').html();
}

function stripPackagingSuffix(value, packagingSize = '') {
    let text = (value || '').toString().trim();
    const packageText = (packagingSize || '').toString().trim();

    if (packageText) {
        text = text.replace(new RegExp(`\\s*${packageText.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}\\.?$`, 'i'), '').trim();
    }

    return text.replace(/\s+\d+(?:[\.,]\d+)?\s*(ml|ltr|liter|l|gr|gram|g|kg)\.?$/i, '').trim();
}

function resolveAromaDetail(product, fallbackName, fallbackCode) {
    const packagingSize = product?.packaging_size?.name || product?.packaging_size || '';
    const productName = product?.name || product?.product_name || '';
    const baseName = stripPackagingSuffix(productName, packagingSize);
    const variantName = product?.variant_name || product?.variant || '';
    const brandLine = product?.brand_line || '';
    const fallback = fallbackName || fallbackCode || '';
    const fallbackLooksGeneric = fallback && (
        normalizeAromaName(fallback) === normalizeAromaName(variantName)
        || normalizeAromaName(fallback) === normalizeAromaName(brandLine)
    );
    const displayName = baseName && (!fallback || fallbackLooksGeneric) ? baseName : (fallback || baseName || '-');
    const sku = product?.sku || product?.product_code || fallbackCode || '';
    const category = product?.product_category?.name || product?.category || '';

    return {
        displayName,
        productName,
        sku,
        brandLine,
        variantName,
        category,
        packagingSize
    };
}

function renderAromaCard(product, fallbackName, fallbackCode) {
    const detail = resolveAromaDetail(product, fallbackName, fallbackCode);
    const meta = [
        detail.brandLine ? `Brand: ${detail.brandLine}` : '',
        detail.sku ? `SKU: ${detail.sku}` : ''
    ].filter(Boolean).join(' | ');

    return `
        <span>${escapeHtml(detail.displayName)}</span>
        ${meta ? `<br><small>${escapeHtml(meta)}</small>` : ''}
    `;
}

function renderAromaDetail(product, fallbackName, fallbackCode) {
    const detail = resolveAromaDetail(product, fallbackName, fallbackCode);
    const lines = [
        `<strong>${escapeHtml(detail.displayName)}</strong>`,
        detail.productName ? `Product: ${escapeHtml(detail.productName)}` : '',
        detail.brandLine ? `Brand Line: ${escapeHtml(detail.brandLine)}` : '',
        detail.variantName ? `Variant: ${escapeHtml(detail.variantName)}` : '',
        detail.sku ? `Code/SKU: ${escapeHtml(detail.sku)}` : '',
        detail.packagingSize ? `Packaging: ${escapeHtml(detail.packagingSize)}` : '',
        detail.category ? `Category: ${escapeHtml(detail.category)}` : ''
    ].filter(Boolean);

    return lines.join('<br>');
}

// View aroma change
function viewAromaChange(id) {
    $('#viewAromaModal').addClass('show');
    $('#viewAromaContent').html(`
        <div class="loading-spinner">
            <div class="spinner"></div>
            <div style="color: #214589; font-weight: 500;">Loading details...</div>
        </div>
    `);
    
    $.ajax({
        url: `/marketing/aroma-changes/${id}`,
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function(response) {
            const data = response.data;
            const previousAromaCard = renderAromaCard(data.previous_product, data.previous_aroma_name, data.previous_aroma_code);
            const newAromaCard = renderAromaCard(data.new_product, data.new_aroma_name, data.new_aroma_code);
            const previousAromaDetail = renderAromaDetail(data.previous_product, data.previous_aroma_name, data.previous_aroma_code);
            const newAromaDetail = renderAromaDetail(data.new_product, data.new_aroma_name, data.new_aroma_code);

            $('#viewAromaContent').html(`
                <div class="aroma-change-arrow">
                    <div class="old-aroma">
                        <strong>Previous Aroma</strong><br>
                        ${previousAromaCard}
                    </div>
                    <div class="arrow-icon">
                        <i class="fas fa-arrow-right"></i>
                    </div>
                    <div class="new-aroma">
                        <strong>New Aroma</strong><br>
                        ${newAromaCard}
                    </div>
                </div>
                
                <table class="data-table">
                    <tr>
                        <th width="30%">Change Number</th>
                        <td><strong>${data.change_number}</strong></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td><span class="badge ${data.status_badge}">${data.status_text}</span></td>
                    </tr>
                    <tr>
                        <th>Aroma Lama</th>
                        <td>${previousAromaDetail}</td>
                    </tr>
                    <tr>
                        <th>Aroma Baru</th>
                        <td>${newAromaDetail}</td>
                    </tr>
                    <tr>
                        <th>Contract</th>
                        <td>${data.contract?.contract_number || '-'}</td>
                    </tr>
                    <tr>
                        <th>Customer</th>
                        <td>${data.contract?.customer?.name || '-'}</td>
                    </tr>
                    <tr>
                        <th>Building</th>
                        <td>${data.building?.name || '-'}</td>
                    </tr>
                    <tr>
                        <th>Room</th>
                        <td>${data.room?.room_name || '-'}</td>
                    </tr>
                    <tr>
                        <th>Reason</th>
                        <td>${data.change_reason || '-'}</td>
                    </tr>
                    ${data.change_notes ? `
                    <tr>
                        <th>Notes</th>
                        <td>${data.change_notes}</td>
                    </tr>
                    ` : ''}
                    <tr>
                        <th>Requested By</th>
                        <td>${data.requested_by?.name || '-'}</td>
                    </tr>
                    <tr>
                        <th>Created At</th>
                        <td>${new Date(data.created_at).toLocaleString()}</td>
                    </tr>
                    ${data.approved_at ? `
                    <tr>
                        <th>Approved By</th>
                        <td>${data.approved_by?.name || '-'} at ${new Date(data.approved_at).toLocaleString()}</td>
                    </tr>
                    ` : ''}
                    ${data.applied_at ? `
                    <tr>
                        <th>Applied By</th>
                        <td>${data.applied_by?.name || '-'} at ${new Date(data.applied_at).toLocaleString()}</td>
                    </tr>
                    ` : ''}
                </table>
            `);
        },
        error: function(xhr) {
            $('#viewAromaContent').html(`
                <div class="empty-state">
                    <i class="fas fa-exclamation-triangle" style="color: #dc2626;"></i>
                    <h3>Error Loading Details</h3>
                    <p>Failed to load aroma change details. Please try again.</p>
                </div>
            `);
        }
    });
}

// Submit for approval
function submitAromaChange(id) {
    if (confirm('Submit ini aroma change untuk approval?')) {
        $.ajax({
            url: `/marketing/aroma-changes/${id}/submit`,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function() {
                alert('Aroma change submitted for approval');
                location.reload();
            },
            error: function(xhr) {
                alert('Error: ' + (xhr.responseJSON?.message || 'Failed to submit'));
            }
        });
    }
}

// Approve
function approveAromaChange(id) {
    const notes = prompt('Approval Notes (optional):');
    if (notes !== null) {
        $.ajax({
            url: `/marketing/aroma-changes/${id}/approve`,
            method: 'POST',
            data: { approval_notes: notes },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function() {
                alert('Aroma change approved successfully');
                location.reload();
            },
            error: function(xhr) {
                alert('Error: ' + (xhr.responseJSON?.message || 'Failed to approve'));
            }
        });
    }
}

// Reject
function rejectAromaChange(id) {
    const reason = prompt('Rejection Reason (required):');
    if (reason) {
        $.ajax({
            url: `/marketing/aroma-changes/${id}/reject`,
            method: 'POST',
            data: { approval_notes: reason },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function() {
                alert('Aroma change rejected');
                location.reload();
            },
            error: function(xhr) {
                alert('Error: ' + (xhr.responseJSON?.message || 'Failed to reject'));
            }
        });
    }
}

// Apply change
function applyAromaChange(id) {
    if (confirm('Apply this aroma change? Perubahan akan diterapkan ke Contract Room. Proses ini tidak dapat dibatalkan!')) {
        $.ajax({
            url: `/marketing/aroma-changes/${id}/apply`,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function() {
                alert('Aroma change applied successfully! Contract room updated.');
                location.reload();
            },
            error: function(xhr) {
                alert('Error: ' + (xhr.responseJSON?.message || 'Failed to apply'));
            }
        });
    }
}

// Delete
function deleteAromaChange(id) {
    if (confirm('Are you sure you want to delete this aroma change request? This action cannot be undone!')) {
        $.ajax({
            url: `/marketing/aroma-changes/${id}`,
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                alert('Aroma change deleted successfully');
                location.reload();
            },
            error: function(xhr) {
                alert('Error: ' + (xhr.responseJSON?.message || 'Failed to delete'));
            }
        });
    }
}

function initSelect2For(selectObj) {
    if (selectObj.hasClass('select2-hidden-accessible')) {
        selectObj.select2('destroy');
    }
    selectObj.select2({
        dropdownParent: selectObj.closest('.mktg-modal').find('.mktg-dialog'),
        width: '100%',
        minimumResultsForSearch: 0
    });
}
</script>
@endpush
