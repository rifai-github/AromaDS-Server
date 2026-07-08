@extends('layouts.app')

@section('title', 'Inventory Transfer Detail')

@section('content')

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
                            @if($transfer->status === 'draft')
                            <button type="button" class="btn btn-sm" onclick="transitionTransferStatus({{ $transfer->id }}, 'transferred')"
                                style="background-color: #dbeafe; color: #1d4ed8; border: 1px solid #bfdbfe; font-weight: 600; border-radius: 6px; padding: 6px 14px; cursor: pointer;">
                                <i class="fas fa-truck me-1"></i> Tandai Transferred
                            </button>
                            @elseif($transfer->status === 'transferred')
                            <button type="button" class="btn btn-sm" onclick="transitionTransferStatus({{ $transfer->id }}, 'received')"
                                style="background-color: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; font-weight: 600; border-radius: 6px; padding: 6px 14px; cursor: pointer;">
                                <i class="fas fa-box-open me-1"></i> Tandai Received
                            </button>
                            @endif
                            @if($transfer->status === 'draft')
                            <button type="button" class="btn btn-sm" onclick="openEditModal({{ $transfer->id }})"
                                style="background-color: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; font-weight: 600; border-radius: 6px; padding: 6px 14px; cursor: pointer;">
                                <i class="fas fa-edit me-1"></i> Edit Transfer
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

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
                            <div style="font-size: 1rem; color: #212529;">{{ $transfer->is_direct_branch_transfer ? 'Ya' : 'Tidak' }}</div>
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
                <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #1e3a8a;">
                    <h5 class="card-title mb-0" style="color: #1e3a8a;">
                        <i class="fas fa-file-alt me-2"></i>Dokumen
                    </h5>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem;">
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Surat Pengajuan (Cabang)</div>
                            @if($transfer->submission_letter_file)
                            <div style="font-size: 1rem;"><a href="/storage/{{ $transfer->submission_letter_file }}" target="_blank" class="text-primary" style="text-decoration: underline;">Lihat file</a></div>
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
                            <div style="font-size: 1rem;"><a href="/storage/{{ $transfer->delivery_note_file }}" target="_blank" class="text-primary" style="text-decoration: underline;">Lihat file</a></div>
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
                            <div style="font-size: 1rem;"><a href="/storage/{{ $transfer->delivery_order_file }}" target="_blank" class="text-primary" style="text-decoration: underline;">Lihat file</a></div>
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
