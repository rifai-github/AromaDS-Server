@extends('layouts.app')

@section('title', 'Inventory Transfer Detail')

@section('content')

@php
    $transferUser = auth()->user();
    $hasTransferPermissionBypass = $transferUser->hasRole('Admin')
        || $transferUser->hasRole('super_admin')
        || $transferUser->hasRoleStartingWith('Management');
    $submitTransferPermissions = [
        'warehouse.inventory-transfers.submit',
        'warehouse.inventory-transfers.submit.create',
        'warehouse.inventory-transfers.submit.approve',
    ];
    $canSubmitTransfer = $hasTransferPermissionBypass || $transferUser->hasAnyPermission($submitTransferPermissions);
    $canApproveTransfer = $hasTransferPermissionBypass || $transferUser->hasPermission('warehouse.inventory-transfers.approve');
    $canRejectTransfer = $hasTransferPermissionBypass || $transferUser->hasPermission('warehouse.inventory-transfers.reject');
    $canMarkTransferred = ($hasTransferPermissionBypass || $transferUser->hasAnyPermission(\App\Models\InventoryTransfer::MARK_TRANSFERRED_PERMISSIONS))
        && $transfer->userCanMarkTransferredFromSource($transferUser);
    $canMarkReceived = ($hasTransferPermissionBypass || $transferUser->hasAnyPermission(\App\Models\InventoryTransfer::MARK_RECEIVED_PERMISSIONS))
        && $transfer->userCanMarkReceivedAtDestination($transferUser);
    $canUpdateTransfer = $hasTransferPermissionBypass || $transferUser->hasPermission('warehouse.inventory-transfers.update');
@endphp

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
        color: #334155 !important;
    }

    @media (max-width: 768px) {
        .d-flex.justify-content-between {
            flex-direction: column !important;
            gap: 1rem !important;
        }
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
                            <a href="{{ route('warehouse.inventory-transfers.index') }}" class="btn btn-light btn-sm">
                                <i class="fas fa-arrow-left"></i> Back to List
                            </a>
                        </div>
                        <div>
                            <h3 class="card-title mb-0" style="color: white; font-size: 1.5rem; font-weight: bold;">
                                {{ $transfer->transfer_number }} - <span style="font-size: 0.9rem; font-weight: normal;">{{ $transfer->status_text }}</span>
                            </h3>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            @if($transfer->requiresCentralApproval() && in_array($transfer->approval_status, ['draft', 'rejected']) && $canSubmitTransfer)
                            <button type="button" class="btn btn-sm btn-light" onclick="submitTransferApproval({{ $transfer->id }})">
                                <i class="fas fa-paper-plane me-1"></i> Ajukan Approval
                            </button>
                            @endif
                            @if($transfer->requiresCentralApproval() && $transfer->approval_status === 'pending' && $canApproveTransfer && (int) $transfer->created_by !== (int) auth()->id())
                            <button type="button" class="btn btn-sm btn-success" onclick="approveTransfer({{ $transfer->id }})">
                                <i class="fas fa-check me-1"></i> Approve
                            </button>
                            @endif
                            @if($transfer->requiresCentralApproval() && $transfer->approval_status === 'pending' && $canRejectTransfer)
                            <button type="button" class="btn btn-sm btn-danger" onclick="rejectTransfer({{ $transfer->id }})">
                                <i class="fas fa-times me-1"></i> Reject
                            </button>
                            @endif
                            @if($transfer->status === 'draft' && (!$transfer->requiresCentralApproval() || $transfer->approval_status === 'approved') && $canMarkTransferred)
                            <button type="button" class="btn btn-sm" onclick="transitionTransferStatus({{ $transfer->id }}, 'transferred')"
                                style="background-color: #dbeafe; color: #1d4ed8; border: 1px solid #bfdbfe; font-weight: 600; border-radius: 6px; padding: 6px 14px; cursor: pointer;">
                                <i class="fas fa-truck me-1"></i> Tandai Transferred
                            </button>
                            @elseif($transfer->status === 'transferred' && $canMarkReceived)
                            <button type="button" class="btn btn-sm" onclick="transitionTransferStatus({{ $transfer->id }}, 'received')"
                                style="background-color: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; font-weight: 600; border-radius: 6px; padding: 6px 14px; cursor: pointer;">
                                <i class="fas fa-box-open me-1"></i> Tandai Received
                            </button>
                            @endif
                            @if($transfer->status === 'draft' && in_array($transfer->approval_status, ['not_required', 'draft', 'rejected']) && $canUpdateTransfer)
                            <button type="button" class="btn btn-sm" onclick="openEditModal({{ $transfer->id }})"
                                style="background-color: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; font-weight: 600; border-radius: 6px; padding: 6px 14px; cursor: pointer;">
                                <i class="fas fa-edit me-1"></i> Edit Transfer
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            @if($serializedReceiving)
            <!-- Serial Number Queue Notice -->
            <div class="card mb-3" style="border: 1px solid #bfdbfe; background-color: #eff6ff; border-radius: 10px;">
                <div class="card-body" style="padding: 1rem 1.5rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <div style="font-weight: 700; color: #1e40af; font-size: 0.95rem;">
                            <i class="fas fa-barcode me-2"></i>Item ber-Serial Number di-queue ke Inventory Receiving {{ $serializedReceiving->receiving_number }}
                        </div>
                        <div style="font-size: 0.85rem; color: #1e40af; margin-top: 0.25rem;">
                            Status: <strong>{{ ucfirst($serializedReceiving->status) }}</strong> &middot; Stok & Serial Number di gudang tujuan baru masuk setelah Receiving ini diverifikasi & di-finalize.
                        </div>
                    </div>
                    <a href="{{ route('warehouse.inventory-receivings.show', $serializedReceiving->id) }}" class="btn btn-sm" style="background-color: #1e40af; color: white; border-radius: 6px; padding: 6px 14px;">
                        <i class="fas fa-arrow-right me-1"></i> Buka Inventory Receiving
                    </a>
                </div>
            </div>
            @endif

            <!-- Basic Info Section -->
            <div class="card mb-3">
                <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #1e3a8a;">
                    <h5 class="card-title mb-0" style="color: #1e3a8a;">
                        <i class="fas fa-info-circle me-2"></i>Basic Info
                    </h5>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 2rem;">
                        <!-- Row 1 -->
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Transfer No</div>
                            <div style="font-size: 1rem; font-weight: 600; color: #212529;">{{ $transfer->transfer_number }}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Status</div>
                            <div>
                                <span class="badge" style="background-color: {{ $transfer->status === 'received' ? '#059669' : ($transfer->status === 'transferred' ? '#2563eb' : '#d97706') }}; color: white; padding: 0.35rem 0.75rem; font-size: 0.8rem;">
                                    {{ $transfer->status_text }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Transfer Date</div>
                            <div style="font-size: 1rem; color: #212529;">{{ $transfer->transfer_date ? $transfer->transfer_date->format('d/M/Y') : '-' }}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Direct Branch Transfer</div>
                            <div style="font-size: 1rem; color: #212529;">{{ $transfer->is_direct_branch_transfer ? 'Ya — perlu approval pusat' : 'Tidak' }}</div>
                        </div>

                        <!-- Row 2 -->
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">From Warehouse</div>
                            <div style="font-size: 1rem; color: #212529;">{{ $transfer->fromWarehouse->name ?? '-' }}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">To Warehouse</div>
                            <div style="font-size: 1rem; color: #212529;">{{ $transfer->toWarehouse->name ?? '-' }}</div>
                        </div>
                        <div style="grid-column: span 2;">
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Notes</div>
                            <div style="font-size: 1rem; color: #212529;">{{ $transfer->notes ?? '-' }}</div>
                        </div>

                        @if($transfer->isFromMaterialReturn() && $transfer->sourceMaterialReturn)
                        <div style="grid-column: span 4; background-color: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 1rem; margin-top: 0.5rem;">
                            <div style="font-size: 0.75rem; font-weight: 700; color: #1e40af; text-transform: uppercase; margin-bottom: 0.5rem;">Dibuat Otomatis dari Material Return</div>
                            <div style="font-size: 1rem; color: #1e3a8a; font-weight: 500;">{{ $transfer->sourceMaterialReturn->return_number }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            @if($transfer->requiresCentralApproval())
            <div class="card mb-3" style="border: 1px solid #cbd5e1; background: #f8fafc;">
                <div class="card-header" style="background: #0f172a; color: white;">
                    <h5 class="card-title mb-0" style="color: white;"><i class="fas fa-shield-alt me-2"></i>Approval Pusat</h5>
                </div>
                <div class="card-body">
                    <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1.5rem;">
                        <div><div class="text-muted text-uppercase" style="font-size:.72rem;font-weight:700;">Status</div><div style="font-weight:700;margin-top:.35rem;">{{ $transfer->approval_status_text }}</div></div>
                        <div><div class="text-muted text-uppercase" style="font-size:.72rem;font-weight:700;">Diajukan oleh</div><div style="margin-top:.35rem;">{{ $transfer->approvalSubmitter->name ?? '-' }} @if($transfer->submitted_for_approval_at)<br><small>{{ $transfer->submitted_for_approval_at->format('d/M/Y H:i') }}</small>@endif</div></div>
                        <div><div class="text-muted text-uppercase" style="font-size:.72rem;font-weight:700;">Keputusan pusat</div><div style="margin-top:.35rem;">{{ $transfer->centralApprover->name ?? $transfer->centralRejector->name ?? '-' }}</div></div>
                    </div>
                    @if($transfer->central_rejection_reason)
                    <div class="alert alert-danger mt-3 mb-0"><strong>Alasan penolakan:</strong> {{ $transfer->central_rejection_reason }}</div>
                    @elseif($transfer->central_approval_notes)
                    <div class="alert alert-success mt-3 mb-0"><strong>Catatan approval:</strong> {{ $transfer->central_approval_notes }}</div>
                    @endif
                    @if($transfer->approvalHistories->isNotEmpty())
                    <div class="mt-4"><div class="text-muted text-uppercase mb-2" style="font-size:.72rem;font-weight:700;">Audit trail</div>
                        @foreach($transfer->approvalHistories as $history)
                        <div style="display:flex;gap:1rem;padding:.65rem 0;border-top:1px solid #e2e8f0;">
                            <strong style="min-width:105px;">{{ ucfirst($history->action) }}</strong>
                            <span>{{ $history->actor->name ?? 'System' }} · {{ $history->created_at->format('d/M/Y H:i') }} @if($history->notes) — {{ $history->notes }} @endif</span>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
            @endif

            @php
                $reasonLabels = [
                    'slow_moving' => 'Slow Moving',
                    'near_expired' => 'Near Expired',
                    'customer_need_changed' => 'Perubahan Kebutuhan Customer',
                    'damaged' => 'Rusak',
                    'other' => 'Lainnya',
                ];
            @endphp
            @if($transfer->return_reason_category || $transfer->return_reason)
            <!-- Return Reason Section -->
            <div class="card mb-3">
                <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #1e3a8a;">
                    <h5 class="card-title mb-0" style="color: #1e3a8a;">
                        <i class="fas fa-undo-alt me-2"></i>Alasan Return
                    </h5>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 2rem;">
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Kategori Alasan</div>
                            <div style="font-size: 1rem; color: #212529;">{{ $reasonLabels[$transfer->return_reason_category] ?? ($transfer->return_reason_category ?? '-') }}</div>
                        </div>
                        <div style="grid-column: span 3;">
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Detail Alasan</div>
                            <div style="font-size: 1rem; color: #212529;">{{ $transfer->return_reason ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Dokumen Section -->
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center" style="background-color: #f8f9fa; border-bottom: 2px solid #1e3a8a;">
                    <h5 class="card-title mb-0" style="color: #1e3a8a;">
                        <i class="fas fa-file-alt me-2"></i>Dokumen
                    </h5>
                    @if($canUpdateTransfer)
                    <button type="button" class="btn btn-sm" onclick="openDocumentUploadModal({{ $transfer->id }})"
                        style="background-color: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; font-weight: 600; border-radius: 6px; padding: 4px 12px; cursor: pointer;">
                        <i class="fas fa-upload me-1"></i> Upload Dokumen
                    </button>
                    @endif
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem;">
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Surat Pengajuan (Cabang)</div>
                            @if($transfer->submission_letter_file)
                            <div style="font-size: 1rem;"><a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($transfer->submission_letter_file) }}" target="_blank" class="text-primary" style="text-decoration: underline;">Lihat file</a></div>
                            <div style="font-size: 0.8rem; color: #6c757d; margin-top: 0.25rem;">
                                {{ $transfer->submissionLetterUploader->name ?? '-' }}
                                @if($transfer->submission_letter_uploaded_at) &middot; {{ $transfer->submission_letter_uploaded_at->format('d/M/Y H:i') }} @endif
                            </div>
                            @else
                            <div style="font-size: 1rem; color: #94a3b8;">Belum diupload</div>
                            @endif
                        </div>
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Surat Jalan (Pusat)</div>
                            @if($transfer->delivery_note_file)
                            <div style="font-size: 1rem;"><a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($transfer->delivery_note_file) }}" target="_blank" class="text-primary" style="text-decoration: underline;">Lihat file</a></div>
                            <div style="font-size: 0.8rem; color: #6c757d; margin-top: 0.25rem;">
                                {{ $transfer->deliveryNoteUploader->name ?? '-' }}
                                @if($transfer->delivery_note_uploaded_at) &middot; {{ $transfer->delivery_note_uploaded_at->format('d/M/Y H:i') }} @endif
                            </div>
                            @else
                            <div style="font-size: 1rem; color: #94a3b8;">Belum diupload</div>
                            @endif
                        </div>
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Delivery Order (Branch↔Branch)</div>
                            @if($transfer->delivery_order_file)
                            <div style="font-size: 1rem;"><a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($transfer->delivery_order_file) }}" target="_blank" class="text-primary" style="text-decoration: underline;">Lihat file</a></div>
                            @else
                            <div style="font-size: 1rem; color: #94a3b8;">-</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Audit Information Section -->
            <div class="card mb-3">
                <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #1e3a8a; padding: 0.75rem 1.5rem;">
                    <h5 class="card-title mb-0" style="color: #1e3a8a; font-size: 0.95rem;">
                        <i class="fas fa-history me-2"></i>Audit Information
                    </h5>
                </div>
                <div class="card-body" style="padding: 1rem 1.5rem;">
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem;">
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.25rem;">Created By</div>
                            <div style="font-size: 0.9rem; color: #212529;">{{ $transfer->creator->name ?? '-' }}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.25rem;">Created At</div>
                            <div style="font-size: 0.9rem; color: #212529;">{{ $transfer->created_at ? $transfer->created_at->format('d/M/Y H:i') : '-' }}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.25rem;">Last Updated By</div>
                            <div style="font-size: 0.9rem; color: #212529;">{{ $transfer->updatedBy->name ?? '-' }}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.25rem;">Last Updated At</div>
                            <div style="font-size: 0.9rem; color: #212529;">{{ $transfer->updated_at ? $transfer->updated_at->format('d/M/Y H:i') : '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Item List Section -->
            <div class="card">
                <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #1e3a8a;">
                    <h5 class="card-title mb-0" style="color: #1e3a8a;">
                        <i class="fas fa-list-alt me-2"></i>Item List
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="overflow-x: auto; max-width: 100%;">
                        <table class="table table-bordered table-striped" style="min-width: 700px;">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Quantity</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transfer->transferItems as $item)
                                <tr>
                                    <td>{{ $item->product->name ?? '-' }}</td>
                                    <td>{{ $item->product->sku ?? '-' }}</td>
                                    <td>{{ number_format($item->quantity, 0) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">
                                        <i class="fas fa-info-circle me-2"></i>No items found.
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

@include('warehouse.inventory-transfers._transfer-form-modal')

@endsection
