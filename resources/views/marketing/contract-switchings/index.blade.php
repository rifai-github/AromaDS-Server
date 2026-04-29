@extends('layouts.app')

@section('title', 'Contract Switching')
@section('breadcrumb', 'Home / Marketing / Contract Switching')

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
    /* Select2 dropdown di dalam marketing modal harus di atas overlay */
    .select2-dropdown, .select2-container--open .select2-dropdown {
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
                <div class="stat-label">Completed</div>
                <div class="stat-value">{{ $stats['completed'] ?? 0 }}</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon info">
                <i class="fas fa-exchange-alt"></i>
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
            Filter Contract Switching
        </div>
        <form action="{{ route('marketing.contract-switchings.index') }}" method="GET">
            <div class="filter-grid">
                <div class="form-group">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Transfer #, contract, marketing..." value="{{ request('search') }}">
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
                <a href="{{ route('marketing.contract-switchings.index') }}" class="btn btn-secondary">
                    <i class="fas fa-sync-alt"></i> Reset Filter
                </a>
                <button type="button" class="btn btn-success" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i> Switch Customer
                </button>
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <div class="table-container">
        <div class="table-header">
            <h3 class="table-title">
                <i class="fas fa-exchange-alt"></i>
                Contract Switching List
            </h3>
            <div class="table-actions">
                <span class="text-sm text-gray-600">
                    Showing {{ $assigned->firstItem() ?? 0 }} to {{ $assigned->lastItem() ?? 0 }} 
                    of {{ $assigned->total() }} entries
                </span>
            </div>
        </div>

        <div class="table-wrapper">
            @if($assigned->count() > 0)
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Switching #</th>
                            <th>Contract</th>
                            <th>Old Customer</th>
                            <th>New Customer</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Created At</th>
                            <th>Last Updated By</th>
                            <th>Last Updated At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($assigned as $item)
                        <tr>
                            <td><strong>{{ $item->switching_number }}</strong></td>
                            <td>
                                <a href="{{ route('marketing.contracts.show', $item->old_contract_id) }}" target="_blank">
                                    {{ $item->oldContract->contract_number ?? '-' }}
                                </a>
                            </td>
                            <td>
                                <i class="fas fa-building text-danger"></i>
                                {{ $item->oldCustomer->name ?? '-' }}
                            </td>
                            <td>
                                <i class="fas fa-building text-success"></i>
                                <strong>{{ $item->newCustomer->name ?? '-' }}</strong>
                            </td>
                            <td>{{ Str::limit($item->switching_reason, 30) }}</td>
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
                            <td>
                                <div style="display: flex; gap: 4px;">
                                    <button class="btn btn-info btn-sm" onclick="viewSwitching({{ $item->id }})" title="View">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    @if($item->status == 'draft')
                                    <button class="btn btn-primary btn-sm" onclick="submitSwitching({{ $item->id }})" title="Submit">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="deleteSwitching({{ $item->id }})" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    @endif
                                    @if($item->status == 'pending_approval')
                                    <button class="btn btn-success btn-sm" onclick="approveSwitching({{ $item->id }})" title="Approve">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="rejectSwitching({{ $item->id }})" title="Reject">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    @endif
                                    @if($item->status == 'approved')
                                    <button class="btn btn-warning btn-sm" onclick="executeSwitching({{ $item->id }})" title="Execute">
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
                    <i class="fas fa-exchange-alt"></i>
                    <h3>No Switchings Found</h3>
                    <p>No contract switchings match your current filter criteria.</p>
                    <p><a href="javascript:void(0)" onclick="openCreateModal()">Create the first one</a></p>
                </div>
            @endif
        </div>

        @if($assigned->hasPages())
            <div class="pagination-wrapper">
                {{ $assigned->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Create Switching Modal -->
<div class="mktg-modal" id="createTransferModal">
    <div class="mktg-dialog" onclick="event.stopPropagation()">
        <div class="mktg-header">
            <h5 class="mktg-title">
                <i class="fas fa-exchange-alt"></i>
                Switch Customer
            </h5>
            <button type="button" class="mktg-close" onclick="closeCreateModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="mktg-body">
            <form id="createTransferForm">
                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label">Old Contract <span class="text-danger">*</span></label>
                    <select name="old_contract_id" id="contractSelect" class="form-control" required>
                        <option value="">Memuat...</option>
                    </select>
                    <small class="text-muted">Contract yang akan di-switch customernya</small>
                </div>

                <div id="contractInfo" style="display:none; background: #f8fafc; padding: 12px; border-radius: 6px; border: 1px solid #e2e8f0; margin-bottom: 16px;">
                    <strong>Info Contract Saat Ini:</strong>
                    <div id="contractInfoContent"></div>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label">New Customer <span class="text-danger">*</span></label>
                    <select name="new_customer_id" id="newCustomerSelect" class="form-control" required>
                        <option value="">Memuat...</option>
                    </select>
                    <small class="text-muted">Customer baru </small>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label">Switching Reason <span class="text-danger">*</span></label>
                    <select name="switching_reason" id="reasonSelect" class="form-control" required>
                        <option value="">Pilih alasan...</option>
                        <option value="Company Name Change">Company Name Change (Ganti nama)</option>
                        <option value="Company Rebranding">Company Rebranding</option>
                        <option value="Company Merger">Company Merger (Merger)</option>
                        <option value="Company Acquisition">Company Acquisition (Akuisisi)</option>
                        <option value="Corporate Restructuring">Corporate Restructuring</option>
                        <option value="Business Transfer">Business Transfer</option>
                        <option value="Other">Other (Lainnya)</option>
                    </select>
                </div>

                <div class="form-group" id="otherReasonField" style="display:none; margin-bottom: 16px;">
                    <label class="form-label">Tulis Alasan</label>
                    <input type="text" name="other_reason" class="form-control" placeholder="Masukkan alasan spesifik...">
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label">Continue Settings</label>
                    <div style="background: #f8fafc; padding: 12px; border-radius: 6px;">
                        <div style="margin-bottom: 8px;">
                            <input type="checkbox" name="continue_period" id="continuePeriod" checked>
                            <label for="continuePeriod" style="margin-left: 8px;">Continue same period (tanggal tetap sama)</label>
                        </div>
                        <div style="margin-bottom: 8px;">
                            <input type="checkbox" name="continue_top" id="continueTop" checked>
                            <label for="continueTop" style="margin-left: 8px;">Continue TOP (Terms of Payment tetap sama)</label>
                        </div>
                        <div>
                            <input type="checkbox" name="reset_dates" id="resetDates">
                            <label for="resetDates" style="margin-left: 8px;">Reset contract dates (mulai dari sekarang)</label>
                        </div>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label">Deskripsi (Opsional)</label>
                    <textarea name="switching_description" class="form-control" rows="3" placeholder="Detail tambahan..."></textarea>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label">Catatan (Opsional)</label>
                    <textarea name="switching_notes" class="form-control" rows="2" placeholder="Catatan internal..."></textarea>
                </div>
            </form>
        </div>
        <div class="mktg-footer">
            <button type="button" class="btn btn-secondary" onclick="closeCreateModal()">
                <i class="fas fa-times"></i> Batal
            </button>
            <button type="button" class="btn btn-primary" onclick="submitCreateForm()">
                <i class="fas fa-save"></i> Simpan Switching
            </button>
        </div>
    </div>
</div>

<!-- View Switching Modal -->
<div class="mktg-modal" id="viewTransferModal">
    <div class="mktg-dialog" onclick="event.stopPropagation()">
        <div class="mktg-header">
            <h5 class="mktg-title">
                <i class="fas fa-info-circle"></i>
                Detail Switching
            </h5>
            <button type="button" class="mktg-close" onclick="closeViewModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="mktg-body">
            <div id="viewTransferContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
        <div class="mktg-footer">
            <button type="button" class="btn btn-secondary" onclick="closeViewModal()">
                <i class="fas fa-times"></i> Tutup
            </button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // No need to load on page ready, load when modal opens
});

// Open create modal
function openCreateModal() {
    $('#createTransferModal').addClass('show');
    loadContracts();
    loadCustomers();
    
    // Inisialisasi Select2 untuk dropdown reason (karena ini statis, bukan via AJAX)
    const reasonSelect = $('#reasonSelect');
    if (reasonSelect.hasClass('select2-hidden-accessible')) {
        reasonSelect.select2('destroy');
    }
    reasonSelect.select2({
        dropdownParent: $('#createTransferModal .mktg-dialog'),
        width: '100%',
        minimumResultsForSearch: 0
    });
}

// Close create modal
function closeCreateModal() {
    $('#createTransferModal').removeClass('show');
    $('#createTransferForm')[0].reset();
    $('#contractInfo').hide();
}

// Close view modal
function closeViewModal() {
    $('#viewTransferModal').removeClass('show');
}

// Tutup modal dengan tombol ESC
$(document).on('keydown', function(e) {
    if (e.key === 'Escape') {
        closeCreateModal();
        closeViewModal();
    }
});
// NOTE: Modal hanya bisa ditutup via tombol Close atau X (user request).
// Click diluar area dialog (overlay) tidak akan menutup modal karena event.stopPropagation() pada .mktg-dialog.

// Load contracts
function loadContracts() {
    $.ajax({
        url: '{{ route("marketing.contract-switchings.active-contracts") }}',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            const select = $('#contractSelect');
            select.empty().append('<option value="">Pilih contract...</option>');
            
            let contracts = [];
            
            if (response.data) {
                if (response.data.data && Array.isArray(response.data.data)) {
                    contracts = response.data.data;
                } else if (Array.isArray(response.data)) {
                    contracts = response.data;
                }
            }
            
            if (contracts.length > 0) {
                contracts.forEach(contract => {
                    const customerName = contract.customer?.name || 'Customer Tidak Diketahui';
                    const customerId = contract.customer?.id || contract.customer_id || '';
                    const startDate = contract.start_date || '';
                    const endDate = contract.end_date || '';
                    
                    select.append($('<option>', {
                        value: contract.id,
                        'data-customer-id': customerId,
                        'data-customer-name': customerName,
                        'data-start': startDate,
                        'data-end': endDate,
                        text: contract.contract_number + ' - ' + customerName
                    }));
                });
            } else {
                select.append('<option value="">Tidak ada contract aktif</option>');
            }
            
            // Inisialisasi Select2 setelah data terisi
            if (select.hasClass('select2-hidden-accessible')) {
                select.select2('destroy');
            }
            select.select2({
                dropdownParent: $('#createTransferModal .mktg-dialog'),
                width: '100%',
                minimumResultsForSearch: 0
            });
        },
        error: function(xhr) {
            console.error('Error loading contracts:', xhr);
            alert('Gagal memuat contract. Silakan refresh halaman.');
        }
    });
}

// Load customers
function loadCustomers() {
    $.ajax({
        url: '{{ route("marketing.contract-switchings.customers") }}',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            const select = $('#newCustomerSelect');
            select.empty().append('<option value="">Select new customer...</option>');
            
            let customers = [];
            
            if (response.data) {
                if (response.data.data && Array.isArray(response.data.data)) {
                    customers = response.data.data;
                } else if (Array.isArray(response.data)) {
                    customers = response.data;
                }
            }
            
            if (customers.length > 0) {
                customers.forEach(customer => {
                    const code = customer.customer_code || customer.code || 'N/A';
                    select.append($('<option>', {
                        value: customer.id,
                        text: customer.name + ' (' + code + ')'
                    }));
                });
            } else {
                select.append('<option value="">No customers found</option>');
            }
            
            // Inisialisasi Select2 setelah data terisi
            if (select.hasClass('select2-hidden-accessible')) {
                select.select2('destroy');
            }
            select.select2({
                dropdownParent: $('#createTransferModal .mktg-dialog'),
                width: '100%',
                minimumResultsForSearch: 0
            });
        },
        error: function(xhr) {
            console.error('Error loading customers:', xhr);
            alert('Error loading customers. Please refresh the page.');
        }
    });
}

// When contract selected - show info and filter customers
$(document).on('change', '#contractSelect', function() {
    const contractId = $(this).val();
    const selectedOption = $(this).find('option:selected');
    
    if (contractId) {
        let customerName = selectedOption.attr('data-customer-name') || selectedOption.data('customerName');
        let customerId = selectedOption.attr('data-customer-id') || selectedOption.data('customerId');
        let startDate = selectedOption.attr('data-start') || selectedOption.data('start');
        let endDate = selectedOption.attr('data-end') || selectedOption.data('end');
        
        $('#contractInfo').show();
        $('#contractInfoContent').html(`
            Customer: <strong>${customerName || 'Not found'}</strong><br>
            Period: ${formatDate(startDate)} - ${formatDate(endDate)}
        `);
        
        $('#newCustomerSelect option').prop('disabled', false);
        if (customerId) {
            $('#newCustomerSelect option[value="' + customerId + '"]').prop('disabled', true);
        }
    } else {
        $('#contractInfo').hide();
        $('#newCustomerSelect option').prop('disabled', false);
    }
});

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

// Show/hide other reason
$('#reasonSelect').on('change', function() {
    if ($(this).val() === 'Other') {
        $('#otherReasonField').show();
    } else {
        $('#otherReasonField').hide();
    }
});

// Submit create form
function submitCreateForm() {
    let reason = $('[name="switching_reason"]').val();
    if (reason === 'Other') {
        reason = $('[name="other_reason"]').val();
        if (!reason) {
            alert('Silakan isi alasannya');
            return;
        }
    }
    
    const formData = {
        old_contract_id: $('[name="old_contract_id"]').val(),
        new_customer_id: $('[name="new_customer_id"]').val(),
        switching_reason: reason,
        switching_description: $('[name="switching_description"]').val(),
        switching_notes: $('[name="switching_notes"]').val(),
        continue_period: $('[name="continue_period"]').is(':checked') ? 1 : 0,
        continue_top: $('[name="continue_top"]').is(':checked') ? 1 : 0,
        reset_dates: $('[name="reset_dates"]').is(':checked') ? 1 : 0,
        _token: '{{ csrf_token() }}'
    };

    $.ajax({
        url: '{{ route("marketing.contract-switchings.store") }}',
        method: 'POST',
        data: formData,
        success: function(response) {
            closeCreateModal();
            alert('Contract switching berhasil dibuat!');
            location.reload();
        },
        error: function(xhr) {
            let message = xhr.responseJSON?.message || 'Gagal membuat switching';
            if (xhr.responseJSON?.errors) {
                message = Object.values(xhr.responseJSON.errors).flat().join('\n');
            }
            alert('Gagal: ' + message);
        }
    });
}

// View switching
function viewSwitching(id) {
    $('#viewTransferModal').addClass('show');
    $('#viewTransferContent').html(`
        <div class="loading-spinner">
            <div class="spinner"></div>
            <div class="spinner-text">Memuat detail...</div>
            <div class="loading-dots">
                <div class="loading-dot"></div>
                <div class="loading-dot"></div>
                <div class="loading-dot"></div>
            </div>
        </div>
    `);
    
    $.ajax({
        url: `/marketing/contract-switchings/${id}`,
        method: 'GET',
        success: function(response) {
            const data = response.data;
            $('#viewTransferContent').html(`
                <table class="data-table">
                    <tr>
                        <th width="30%">Switching Number</th>
                        <td><strong>${data.switching_number}</strong></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td><span class="badge ${data.status_badge}">${data.status_text}</span></td>
                    </tr>
                    <tr>
                        <th>Old Contract</th>
                        <td>${data.old_contract?.contract_number || '-'}</td>
                    </tr>
                    <tr>
                        <th>Old Customer</th>
                        <td><i class="fas fa-building text-danger"></i> ${data.old_customer?.name || '-'}</td>
                    </tr>
                    <tr>
                        <th>New Customer</th>
                        <td><i class="fas fa-building text-success"></i> <strong>${data.new_customer?.name || '-'}</strong></td>
                    </tr>
                    <tr>
                        <th>New Contract</th>
                        <td>${data.new_contract?.contract_number || '<span class="text-muted">Belum dibuat</span>'}</td>
                    </tr>
                    <tr>
                        <th>Switching Reason</th>
                        <td>${data.switching_reason}</td>
                    </tr>
                    ${data.switching_description ? `
                    <tr>
                        <th>Description</th>
                        <td>${data.switching_description}</td>
                    </tr>
                    ` : ''}
                    <tr>
                        <th>Created</th>
                        <td>${new Date(data.created_at).toLocaleString()}</td>
                    </tr>
                </table>
            `);
        },
        error: function(xhr) {
            $('#viewTransferContent').html(`
                <div class="empty-state">
                    <i class="fas fa-exclamation-triangle" style="color: #dc2626;"></i>
                    <h3>Gagal Memuat Detail</h3>
                    <p>Gagal memuat detail transfer. Silakan coba lagi.</p>
                </div>
            `);
        }
    });
}

// Submit for approval
function submitSwitching(id) {
    if(!confirm('Ajukan switching untuk persetujuan?')) return;
    $.ajax({
        url: `/marketing/contract-switchings/${id}/submit`,
        method: 'POST',
        data: { _token: '{{ csrf_token() }}' },
        success: function() {
            alert('Switching berhasil diajukan untuk approval');
            location.reload();
        },
        error: function(xhr) {
            alert('Gagal: ' + (xhr.responseJSON?.message || 'Gagal mengajukan switching'));
        }
    });
}

// Approve
function approveSwitching(id) {
    const notes = prompt('Catatan approval (opsional):');
    if (notes !== null) {
        $.ajax({
            url: `/marketing/contract-switchings/${id}/approve`,
            method: 'POST',
            data: { 
                approval_notes: notes,
                _token: '{{ csrf_token() }}'
            },
            success: function() {
                alert('Switching berhasil disetujui');
                location.reload();
            },
            error: function(xhr) {
                alert('Gagal: ' + (xhr.responseJSON?.message || 'Gagal menyetujui switching'));
            }
        });
    }
}

// Reject
function rejectSwitching(id) {
    const reason = prompt('Alasan penolakan (wajib diisi):');
    if (reason) {
        $.ajax({
            url: `/marketing/contract-switchings/${id}/reject`,
            method: 'POST',
            data: { 
                rejection_reason: reason,
                _token: '{{ csrf_token() }}'
            },
            success: function() {
                alert('Switching ditolak');
                location.reload();
            },
            error: function(xhr) {
                alert('Gagal: ' + (xhr.responseJSON?.message || 'Gagal menolak switching'));
            }
        });
    }
}

// Execute
function executeSwitching(id) {
    if(!confirm('Jalankan switching ini? Tindakan ini akan membuat contract BARU dengan customer baru dan tidak bisa dibatalkan.')) return;
    $.ajax({
        url: `/marketing/contract-switchings/${id}/execute`,
        method: 'POST',
        data: { _token: '{{ csrf_token() }}' },
        success: function() {
            alert('Switching berhasil dijalankan! Contract baru sudah dibuat.');
            location.reload();
        },
        error: function(xhr) {
            alert('Gagal: ' + (xhr.responseJSON?.message || 'Gagal menjalankan switching'));
        }
    });
}

// Delete switching
function deleteSwitching(id) {
    if(!confirm('Hapus contract switching ini? Tindakan ini tidak bisa dibatalkan.')) return;
    $.ajax({
        url: `/marketing/contract-switchings/${id}`,
        method: 'DELETE',
        data: { _token: '{{ csrf_token() }}' },
        success: function(response) {
            alert('Contract switching berhasil dihapus');
            location.reload();
        },
        error: function(xhr) {
            alert('Gagal: ' + (xhr.responseJSON?.message || 'Gagal menghapus switching'));
        }
    });
}
</script>
@endpush
