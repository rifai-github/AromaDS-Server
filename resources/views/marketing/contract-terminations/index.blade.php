@extends('layouts.app')

@section('title', 'Contract Termination')
@section('breadcrumb', 'Home / Marketing / Contract Termination')

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
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
    .stat-icon.info { background: #dbeafe; color: #0ea5e9; }
    .stat-icon.success { background: #dcfce7; color: #16a34a; }

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
        z-index: 9999999;
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
        max-width: 800px;
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

    .spinner-text {
        color: #214589;
        font-size: 16px;
        font-weight: 500;
        margin-top: 10px;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .loading-dots {
        display: flex;
        gap: 5px;
        margin-top: 10px;
    }

    .loading-dot {
        width: 8px;
        height: 8px;
        background-color: #214589;
        border-radius: 50%;
        animation: pulse 1.4s ease-in-out infinite both;
    }

    .loading-dot:nth-child(1) { animation-delay: -0.32s; }
    .loading-dot:nth-child(2) { animation-delay: -0.16s; }
    .loading-dot:nth-child(3) { animation-delay: 0s; }

    @keyframes pulse {
        0%, 80%, 100% {
            transform: scale(0.8);
            opacity: 0.5;
        }
        40% {
            transform: scale(1);
            opacity: 1;
        }
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

    .alert-info {
        background: #dbeafe;
        border: 1px solid #93c5fd;
        color: #1e40af;
        padding: 12px;
        border-radius: 6px;
        margin-bottom: 16px;
        display: flex;
        align-items: start;
        gap: 10px;
    }

    .text-danger {
        color: #dc2626;
    }

    .text-muted {
        color: #6b7280;
        font-size: 13px;
    }
    /* Select2 modal only. Do not lift page filters above the modal overlay. */
    .mktg-modal .select2-container {
        width: 100% !important;
    }

    .mktg-modal .select2-dropdown {
        z-index: 10000000 !important;
    }
    .marketing-toast {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 100000000;
        max-width: 460px;
        padding: 14px 18px;
        border-radius: 10px;
        color: #fff;
        font-weight: 600;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.24);
        white-space: pre-line;
    }
    .marketing-toast.success { background: #059669; }
    .marketing-toast.error { background: #dc2626; }
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
                <div class="stat-value">{{ $statistics['draft'] }}</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Waiting Approval</div>
                <div class="stat-value">{{ $statistics['pending_approval'] }}</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon success">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Approved</div>
                <div class="stat-value">{{ $statistics['approved'] }}</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon info">
                <i class="fas fa-exchange-alt"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Rejected</div>
                <div class="stat-value">{{ $statistics['rejected'] }}</div>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <div class="filter-title">
            <i class="fas fa-filter"></i>
            Filter Contract Termination
        </div>
        <form action="{{ route('marketing.contract-terminations.index') }}" method="GET">
            <div class="filter-grid">
                <div class="form-group">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Termination #, contract, customer..." value="{{ request('search') }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="">All Status</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="waiting_for_approval" {{ request('status') == 'waiting_for_approval' ? 'selected' : '' }}>Waiting for Approval</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Apply Filter
                </button>
                <a href="{{ route('marketing.contract-terminations.index') }}" class="btn btn-secondary">
                    <i class="fas fa-sync-alt"></i> Reset Filter
                </a>
                <button type="button" class="btn btn-success" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i> Add Termination
                </button>
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <div class="table-container">
        <div class="table-header">
            <h3 class="table-title">
                <i class="fas fa-ban"></i>
                Contract Termination List
            </h3>
            <div class="table-actions">
                <span class="text-sm text-gray-600">
                    Showing {{ $terminations->firstItem() ?? 0 }} to {{ $terminations->lastItem() ?? 0 }} 
                    of {{ $terminations->total() }} entries
                </span>
            </div>
        </div>

        <div class="table-wrapper">
            @if($terminations->count() > 0)
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Termination #</th>
                            <th>Contract No</th>
                            <th>Customer</th>
                            <th>Reason</th>
                            <th>Requested By</th>
                            <th>Approved By</th>
                            <th>Penalty (Rp)</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Created At</th>
                            <th>Last Updated By</th>
                            <th>Last Updated At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($terminations as $item)
                        <tr onclick="window.location='{{ route('marketing.contract-terminations.show', $item->id) }}'" style="cursor: pointer;">
                            <td><strong>{{ $item->termination_number }}</strong></td>
                            <td>
                                <a href="{{ route('marketing.contracts.show', $item->contract_id) }}" target="_blank" onclick="event.stopPropagation()">
                                    {{ $item->contract->contract_number ?? '-' }}
                                </a>
                            </td>
                            <td>{{ $item->contract->customer->name ?? '-' }}</td>
                            <td>{{ Str::limit($item->reason, 30) }}</td>
                            <td>
                                <i class="fas fa-user"></i>
                                {{ $item->requestedBy->name ?? '-' }}
                            </td>
                            <td>
                                @if($item->approved_by)
                                    <i class="fas fa-user-check text-success"></i>
                                    {{ $item->approvedBy->name }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>{{ number_format($item->penalty_amount, 0, ',', '.') }}</td>
                            <td>
                                <span class="badge {{ $item->status_badge }}">
                                    {{ $item->status_text }}
                                </span>
                            </td>
                            <td>{{ $item->createdBy->name ?? '-' }}</td>
                            <td>
                                <div>
                                    <strong>{{ $item->created_at ? $item->created_at->format('d M Y') : '-' }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $item->created_at ? $item->created_at->format('H:i') : '' }}</small>
                                </div>
                            </td>
                            <td>{{ $item->updatedBy->name ?? '-' }}</td>
                            <td>
                                <div>
                                    <strong>{{ $item->updated_at ? $item->updated_at->format('d M Y') : '-' }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $item->updated_at ? $item->updated_at->format('H:i') : '' }}</small>
                                </div>
                            </td>
                            <td onclick="event.stopPropagation()">
                                <div style="display: flex; gap: 4px;">
                                    <a href="{{ route('marketing.contract-terminations.show', $item->id) }}" class="btn btn-info btn-sm" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if($item->status == 'draft')
                                    <button class="btn btn-primary btn-sm" onclick="submitTermination({{ $item->id }})" title="Submit">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                    @endif
                                    @if($item->status == 'pending_approval' && auth()->user()->canApprove('contract_terminations'))
                                    <button class="btn btn-success btn-sm" onclick="approveTermination({{ $item->id }})" title="Approve">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="rejectTermination({{ $item->id }})" title="Reject">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    @endif
                                    @if($item->status == 'approved' && auth()->user()->canApprove('contract_terminations'))
                                    <button class="btn btn-warning btn-sm {{ !$item->is_unpostable ? 'disabled opacity-50' : '' }}" 
                                            onclick="{{ $item->is_unpostable ? 'unpostTermination(' . $item->id . ')' : 'void(0)' }}" 
                                            title="{{ $item->is_unpostable ? 'Unpost' : $item->unpostable_reason }}"
                                            {{ !$item->is_unpostable ? 'disabled' : '' }}>
                                        <i class="fas fa-undo"></i>
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
                    <i class="fas fa-ban"></i>
                    <h3>No Terminations Found</h3>
                    <p>No contract terminations match your current filter criteria.</p>
                    <p><a href="javascript:void(0)" onclick="openCreateModal()">Create the first one</a></p>
                </div>
            @endif
        </div>

        @if($terminations->hasPages())
            <div class="pagination-wrapper">
                {{ $terminations->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Create Termination Modal -->
<div class="mktg-modal" id="createTerminationModal">
    <div class="mktg-dialog" onclick="event.stopPropagation()">
        <div class="mktg-header">
            <h5 class="mktg-title">
                <i class="fas fa-ban"></i>
                Create Contract Termination
            </h5>
            <button type="button" class="mktg-close" onclick="closeCreateModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="mktg-body">
            <form id="createTerminationForm">
                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label">Contract <span class="text-danger">*</span></label>
                    <select name="contract_id" id="contractSelect" class="form-control" required>
                        <option value="">Select contract...</option>
                    </select>
                    <small class="text-muted">Pilih contract yang akan di-terminate</small>
                </div>

                <div id="contractInfo" style="display:none; background: #f8fafc; padding: 12px; border-radius: 6px; border: 1px solid #e2e8f0; margin-bottom: 16px;">
                    <strong>Contract Info:</strong>
                    <div id="contractInfoContent"></div>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label">Termination Reason <span class="text-danger">*</span></label>
                    <select name="reason" id="reasonSelect" class="form-control" required>
                        <option value="">Select reason...</option>
                    </select>
                    <small class="text-muted">Alasan pengajuan terminate contract</small>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label">Requested By</label>
                    <input type="text" class="form-control" value="{{ auth()->user()->name }}" disabled>
                    <small class="text-muted">Diajukan oleh (auto-filled)</small>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label">Penalty Amount (Rp) <span class="text-danger">*</span></label>
                    <input type="number" name="penalty_amount" id="penaltyAmount" class="form-control" min="0" step="1000" required placeholder="0">
                    <small class="text-muted">Besaran penalty kontrak</small>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label">Notes (Optional)</label>
                    <textarea name="notes" class="form-control" rows="3" placeholder="Catatan tambahan..."></textarea>
                </div>
            </form>
        </div>
        <div class="mktg-footer">
            <button type="button" class="btn btn-secondary" onclick="closeCreateModal()">
                <i class="fas fa-times"></i> Cancel
            </button>
            <button type="button" class="btn btn-primary" id="createTerminationSubmitBtn">
                <i class="fas fa-save"></i> Create Termination
            </button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function showMarketingMessage(type, message) {
    $('.marketing-toast').remove();
    const toast = $(`<div class="marketing-toast ${type}"></div>`).text(message || (type === 'success' ? 'Berhasil' : 'Terjadi kesalahan'));
    $('body').append(toast);
    setTimeout(() => toast.fadeOut(250, function() { $(this).remove(); }), 4500);
}

function initModalSelect2(select, modalSelector) {
    if (!$.fn.select2 || !select.length) return;
    if (select.hasClass('select2-hidden-accessible')) {
        select.select2('destroy');
    }
    select.select2({
        dropdownParent: $(`${modalSelector} .mktg-dialog`),
        width: '100%',
        minimumResultsForSearch: 0
    });
}

function closeOpenPageSelect2() {
    if (!$.fn.select2) return;
    $('.select2-hidden-accessible').each(function() {
        const select = $(this);
        if (select.closest('#createTerminationModal').length === 0) {
            try {
                select.select2('close');
            } catch (e) {
                // Some legacy Select2 instances may already be detached.
            }
        }
    });
}

$(document).ready(function() {
    const pendingToast = sessionStorage.getItem('marketingToast');
    if (pendingToast) {
        sessionStorage.removeItem('marketingToast');
        try {
            const toast = JSON.parse(pendingToast);
            showMarketingMessage(toast.type || 'success', toast.message || 'Berhasil');
        } catch (e) {
            showMarketingMessage('success', 'Berhasil');
        }
    }
    // Load contracts and reasons on page load
    loadContracts();
    loadReasons();
    $('#createTerminationSubmitBtn').off('click.contractTermination').on('click.contractTermination', function(e) {
        e.preventDefault();
        submitCreateForm();
    });
});

// Open create modal
function openCreateModal() {
    closeOpenPageSelect2();
    $('#createTerminationModal').addClass('show');
    $('#createTerminationForm')[0].reset();
    $('#contractInfo').hide();
    loadContracts();
    loadReasons();
}

// Close create modal
function closeCreateModal() {
    $('#createTerminationModal').removeClass('show');
    $('#createTerminationForm')[0].reset();
    $('#contractInfo').hide();
}

// Close modal with ESC key
$(document).on('keydown', function(e) {
    if (e.key === 'Escape') {
        closeCreateModal();
    }
});
// NOTE: Modal hanya bisa ditutup via tombol Close atau X (user request).
// Click diluar area dialog (overlay) tidak akan menutup modal karena event.stopPropagation() pada .mktg-dialog.

// Load active contracts
function loadContracts() {
    $.ajax({
        url: '{{ route("marketing.contract-terminations.create") }}',
        method: 'GET',
        success: function(response) {
            const select = $('#contractSelect');
            select.empty().append('<option value="">Select contract...</option>');
            
            if (response.data && response.data.contracts) {
                response.data.contracts.forEach(contract => {
                    const option = `<option value="${contract.id}">${contract.contract_number} - ${contract.customer.name}</option>`;
                    select.append(option);
                });
            }
            
            initModalSelect2(select, '#createTerminationModal');
        },
        error: function(xhr) {
            console.error('Error loading contracts:', xhr);
            showMarketingMessage('error', 'Error loading contracts. Please refresh the page.');
        }
    });
}

// Load termination reasons
function loadReasons() {
    $.ajax({
        url: '{{ route("marketing.contract-terminations.create") }}',
        method: 'GET',
        success: function(response) {
            const select = $('#reasonSelect');
            select.empty().append('<option value="">Select reason...</option>');
            
            if (response.data && response.data.reasons) {
                response.data.reasons.forEach(reason => {
                    const option = `<option value="${reason.option_name}">${reason.option_name}</option>`;
                    select.append(option);
                });
            }
            
            initModalSelect2(select, '#createTerminationModal');
        },
        error: function(xhr) {
            console.error('Error loading reasons:', xhr);
            showMarketingMessage('error', 'Gagal memuat alasan termination.');
        }
    });
}

// When contract selected, show contract info
$('#contractSelect').on('change', function() {
    const contractId = $(this).val();
    $('#contractInfo').hide();
    
    if (contractId) {
        $.ajax({
            url: `/marketing/contracts/${contractId}`,
            method: 'GET',
            success: function(response) {
                if (response.data) {
                    const contract = response.data;
                    
                    // Format dates properly
                    const formatDate = (dateString) => {
                        if (!dateString) return '-';
                        const date = new Date(dateString);
                        return date.toLocaleDateString('id-ID', { 
                            year: 'numeric', 
                            month: 'short', 
                            day: 'numeric' 
                        });
                    };
                    
                    const info = `
                        <div style="font-size: 13px; margin-top: 8px;">
                            <div><strong>Customer:</strong> ${contract.customer?.name || '-'}</div>
                            <div><strong>Start Date:</strong> ${formatDate(contract.start_date)}</div>
                            <div><strong>End Date:</strong> ${formatDate(contract.end_date)}</div>
                            <div><strong>Total Value:</strong> Rp ${new Intl.NumberFormat('id-ID').format(contract.contract_value || 0)}</div>
                        </div>
                    `;
                    $('#contractInfoContent').html(info);
                    $('#contractInfo').show();
                }
            },
            error: function(xhr) {
                console.error('Error loading contract info:', xhr);
            }
        });
    }
});

// Submit create form
function submitCreateForm() {
    const form = $('#createTerminationForm');
    const formData = {
        contract_id: form.find('[name="contract_id"]').val(),
        reason: form.find('[name="reason"]').val(),
        penalty_amount: form.find('[name="penalty_amount"]').val(),
        notes: form.find('[name="notes"]').val(),
        _token: '{{ csrf_token() }}'
    };

    // Validation
    if (!formData.contract_id) {
        showMarketingMessage('error', 'Silakan pilih contract');
        return;
    }
    if (!formData.reason) {
        showMarketingMessage('error', 'Silakan pilih alasan termination');
        return;
    }
    if (!formData.penalty_amount || formData.penalty_amount < 0) {
        showMarketingMessage('error', 'Silakan isi nilai penalty');
        return;
    }

    const submitBtn = $('#createTerminationSubmitBtn');
    submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Creating...');

    $.ajax({
        url: '{{ route("marketing.contract-terminations.store") }}',
        method: 'POST',
        data: formData,
        success: function(response) {
            if (response.status === 'success') {
                closeCreateModal();
                sessionStorage.setItem('marketingToast', JSON.stringify({ type: 'success', message: 'Contract termination berhasil dibuat!' }));
                location.reload();
            } else {
                showMarketingMessage('error', 'Gagal: ' + (response.message || 'Gagal membuat termination'));
            }
        },
        error: function(xhr) {
            console.error('Error:', xhr);
            if (xhr.responseJSON && xhr.responseJSON.errors) {
                let errorMsg = 'Kesalahan validasi:\n';
                Object.values(xhr.responseJSON.errors).forEach(errors => {
                    errors.forEach(error => {
                        errorMsg += '- ' + error + '\n';
                    });
                });
                showMarketingMessage('error', errorMsg);
            } else {
                showMarketingMessage('error', 'Gagal: ' + (xhr.responseJSON?.message || 'Gagal membuat termination'));
            }
        },
        complete: function() {
            submitBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Create Termination');
        }
    });
}

// Submit for approval
function submitTermination(id) {
    if(!confirm('Ajukan termination untuk persetujuan?')) return;
    $.ajax({
        url: `/marketing/contract-terminations/${id}/submit`,
        method: 'POST',
        data: { _token: '{{ csrf_token() }}' },
        success: function(response) {
            if (response.status === 'success') {
                alert('Termination berhasil diajukan untuk approval!');
                location.reload();
            } else {
                alert('Gagal: ' + (response.message || 'Gagal mengajukan termination'));
            }
        },
        error: function(xhr) {
            alert('Gagal: ' + (xhr.responseJSON?.message || 'Gagal mengajukan termination'));
        }
    });
}

// Approve termination
function approveTermination(id) {
    const notes = prompt('Catatan approval (opsional):');
    if (notes !== null) {
        $.ajax({
            url: `/marketing/contract-terminations/${id}/approve`,
            method: 'POST',
            data: { 
                approval_notes: notes,
                _token: '{{ csrf_token() }}' 
            },
            success: function(response) {
                if (response.status === 'success') {
                    alert('Termination berhasil disetujui! Contract dan job schedule sudah diterminasi.');
                    location.reload();
                } else {
                    alert('Gagal: ' + (response.message || 'Gagal menyetujui termination'));
                }
            },
            error: function(xhr) {
                alert('Gagal: ' + (xhr.responseJSON?.message || 'Gagal menyetujui termination'));
            }
        });
    }
}

// Reject termination
function rejectTermination(id) {
    const notes = prompt('Alasan penolakan (wajib diisi):');
    if (notes && notes.trim()) {
        $.ajax({
            url: `/marketing/contract-terminations/${id}/reject`,
            method: 'POST',
            data: { 
                approval_notes: notes,
                _token: '{{ csrf_token() }}' 
            },
            success: function(response) {
                if (response.status === 'success') {
                    alert('Termination ditolak.');
                    location.reload();
                } else {
                    alert('Gagal: ' + (response.message || 'Gagal menolak termination'));
                }
            },
            error: function(xhr) {
                alert('Gagal: ' + (xhr.responseJSON?.message || 'Gagal menolak termination'));
            }
        });
    } else {
        alert('Alasan penolakan wajib diisi');
    }
}

// Unpost termination
function unpostTermination(id) {
    if(!confirm('Anda yakin ingin membatalkan (unpost) terminasi ini? Kontrak akan kembali aktif. Pastikan seluruh Job Remove terkait sudah di-unpost dan di-unassign hingga kembali New Job.')) return;
    $.ajax({
        url: `/marketing/contract-terminations/${id}/unpost`,
        method: 'POST',
        data: { _token: '{{ csrf_token() }}' },
        success: function(response) {
            if (response.status === 'success') {
                alert('Termination berhasil dibatalkan (unpost). Kontrak kembali aktif.');
                location.reload();
            } else {
                alert('Gagal: ' + (response.message || 'Gagal unpost termination'));
            }
        },
        error: function(xhr) {
            alert('Gagal: ' + (xhr.responseJSON?.message || 'Gagal unpost termination'));
        }
    });
}
</script>
@endpush
