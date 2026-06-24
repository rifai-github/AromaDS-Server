@extends('layouts.app')

@section('title', 'Contract Termination Detail')
@section('breadcrumb', 'Home / Marketing / Contract Termination / Detail')

@section('content')
<style>
    .detail-card {
        background: white;
        border-radius: 8px;
        padding: 24px;
        margin-bottom: 20px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .detail-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 2px solid #e2e8f0;
    }

    .detail-title {
        font-size: 24px;
        font-weight: 700;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .detail-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }

    .detail-item {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .detail-label {
        font-size: 13px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .detail-value {
        font-size: 15px;
        color: #1e293b;
        font-weight: 500;
    }

    .badge {
        display: inline-flex;
        align-items: center;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
    }

    .badge-secondary {
        background-color: #f1f5f9;
        color: #64748b;
    }

    .badge-warning {
        background-color: #fef3c7;
        color: #d97706;
    }

    .badge-success {
        background-color: #dcfce7;
        color: #166534;
    }

    .badge-danger {
        background-color: #fee2e2;
        color: #dc2626;
    }

    .btn {
        padding: 10px 20px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
    }

    .btn-primary {
        background-color: #214589;
        color: white;
    }

    .btn-success {
        background-color: #16a34a;
        color: white;
    }

    .btn-danger {
        background-color: #dc2626;
        color: white;
    }

    .btn-warning {
        background-color: #f59e0b;
        color: white;
    }

    .btn-secondary {
        background-color: #f3f4f6;
        color: #6b7280;
        border: 1px solid #d1d5db;
    }

    .btn:hover {
        opacity: 0.9;
        transform: translateY(-1px);
    }

    .room-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 16px;
    }

    .room-table th {
        background: #f8fafc;
        padding: 12px;
        text-align: left;
        font-size: 13px;
        font-weight: 600;
        color: #475569;
        border-bottom: 2px solid #e2e8f0;
    }

    .room-table td {
        padding: 12px;
        font-size: 14px;
        color: #334155;
        border-bottom: 1px solid #f1f5f9;
    }

    .section-title {
        font-size: 18px;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
</style>

<div class="container-fluid">
    <!-- Header -->
    <div class="detail-card">
        <div class="detail-header">
            <div class="detail-title">
                <i class="fas fa-ban text-danger"></i>
                Contract Termination Detail
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="{{ route('marketing.contract-terminations.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
                
                @if($contractTermination->status == 'draft')
                <button class="btn btn-primary" onclick="submitForApproval()">
                    <i class="fas fa-paper-plane"></i> Submit for Approval
                </button>
                @endif

                @if($contractTermination->status == 'pending_approval')
                    @php
                        $canApprove = auth()->user()->canApprove('contract_terminations');
                    @endphp
                    
                    @if($canApprove)
                    <button class="btn btn-success" onclick="approveTermination()">
                        <i class="fas fa-check"></i> Approve
                    </button>
                    <button class="btn btn-danger" onclick="rejectTermination()">
                        <i class="fas fa-times"></i> Reject
                    </button>
                    @endif
                @endif

                @if($contractTermination->status == 'approved')
                    @php
                        $canApprove = auth()->user()->canApprove('contract_terminations');
                    @endphp
                    
                    @if($canApprove)
                    <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 4px;">
                        <button class="btn btn-warning {{ !$contractTermination->is_unpostable ? 'disabled opacity-50' : '' }}" 
                                onclick="{{ $contractTermination->is_unpostable ? 'unpostTermination()' : 'void(0)' }}"
                                title="{{ $contractTermination->is_unpostable ? 'Unpost' : $contractTermination->unpostable_reason }}"
                                {{ !$contractTermination->is_unpostable ? 'disabled' : '' }}>
                            <i class="fas fa-undo"></i> Unpost/Rollback
                        </button>
                        @if(!$contractTermination->is_unpostable)
                            <small class="text-danger" style="font-weight: 600; max-width: 360px; text-align: right;">
                                <i class="fas fa-exclamation-triangle"></i> {{ $contractTermination->unpostable_reason }}
                            </small>
                        @endif
                    </div>
                    @endif
                @endif
            </div>
        </div>

        <!-- Termination Info -->
        <div class="detail-grid">
            <div class="detail-item">
                <div class="detail-label">Termination Number</div>
                <div class="detail-value">{{ $contractTermination->termination_number }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Status</div>
                <div class="detail-value">
                    <span class="badge {{ $contractTermination->status_badge }}">
                        {{ $contractTermination->status_text }}
                    </span>
                </div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Requested By</div>
                <div class="detail-value">
                    <i class="fas fa-user"></i> {{ $contractTermination->requestedBy->name ?? '-' }}
                </div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Requested At</div>
                <div class="detail-value">{{ $contractTermination->requested_at ? $contractTermination->requested_at->format('d/M/Y H:i') : '-' }}</div>
            </div>
        </div>
    </div>

    <!-- Contract Basic Info -->
    <div class="detail-card">
        <div class="section-title">
            <i class="fas fa-file-contract text-primary"></i>
            Contract Information
        </div>
        <div class="detail-grid">
            <div class="detail-item">
                <div class="detail-label">Contract Number</div>
                <div class="detail-value">
                    <a href="{{ route('marketing.contracts.show', $contractTermination->contract_id) }}" target="_blank" rel="noopener noreferrer">
                        {{ $contractTermination->contract->contract_number ?? '-' }}
                    </a>
                </div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Customer Name</div>
                <div class="detail-value">{{ $contractTermination->contract->customer->name ?? '-' }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Start Date</div>
                <div class="detail-value">{{ $contractTermination->contract->start_date ?? '-' }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Contract Period</div>
                <div class="detail-value">
                    @if($contractTermination->contract->start_date && $contractTermination->contract->end_date)
                        {{ round($contractTermination->contract->start_date->diffInMonths($contractTermination->contract->end_date)) }} Months
                    @else
                        -
                    @endif
                </div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Term of Payment</div>
                <div class="detail-value">{{ ucwords($contractTermination->contract->quotation->terms_of_payment ?? $contractTermination->contract->term_of_payment ?? $contractTermination->contract->payment_terms ?? '-') }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Contract Value</div>
                <div class="detail-value">Rp {{ number_format($contractTermination->contract->contract_value ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <!-- Termination Details -->
    <div class="detail-card">
        <div class="section-title">
            <i class="fas fa-info-circle text-warning"></i>
            Termination Details
        </div>
        <div class="detail-grid">
            <div class="detail-item">
                <div class="detail-label">Termination Reason</div>
                <div class="detail-value">{{ $contractTermination->reason }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Penalty Amount</div>
                <div class="detail-value text-danger">
                    <strong>Rp {{ number_format($contractTermination->penalty_amount, 0, ',', '.') }}</strong>
                </div>
            </div>
            @if($contractTermination->approved_by)
            <div class="detail-item">
                <div class="detail-label">Approved By</div>
                <div class="detail-value">
                    <i class="fas fa-user-check text-success"></i> {{ $contractTermination->approvedBy->name }}
                </div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Approved At</div>
                <div class="detail-value">{{ $contractTermination->approved_at ? $contractTermination->approved_at->format('d/M/Y H:i') : '-' }}</div>
            </div>
            @endif
        </div>

        @if($contractTermination->notes)
        <div class="detail-item" style="margin-top: 20px;">
            <div class="detail-label">Notes</div>
            <div class="detail-value">{{ $contractTermination->notes }}</div>
        </div>
        @endif

        @if($contractTermination->approval_notes)
        <div class="detail-item" style="margin-top: 20px;">
            <div class="detail-label">Approval Notes</div>
            <div class="detail-value">{{ $contractTermination->approval_notes }}</div>
        </div>
        @endif
    </div>

    <!-- Room List -->
    @if($contractTermination->contract->rooms && $contractTermination->contract->rooms->count() > 0)
    <div class="detail-card">
        <div class="section-title">
            <i class="fas fa-door-open text-info"></i>
            Room List & Rental
        </div>
        <table class="room-table">
            <thead>
                <tr>
                    <th>Building</th>
                    <th>Room Name</th>
                    <th>Room Type</th>
                    <th>Size (m²)</th>
                    <th>Rental Price (Rp)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($contractTermination->contract->rooms as $room)
                <tr>
                    <td>{{ $room->building->name ?? '-' }}</td>
                    <td>{{ $room->name }}</td>
                    <td>{{ $room->room_type ?? '-' }}</td>
                    <td>{{ $room->size ?? '-' }}</td>
                    <td>{{ number_format($room->pivot->rental_price ?? 0, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
function submitForApproval() {
    showConfirmDialog({
        title: 'Ajukan terminasi untuk persetujuan?',
        text: 'Data terminasi akan dikirim untuk proses persetujuan.',
        confirmButtonText: 'Ya, Ajukan',
        cancelButtonText: 'Batal'
    }).then((result) => {
    if (result.isConfirmed) {
        $.ajax({
            url: '{{ route("marketing.contract-terminations.submit", $contractTermination->id) }}',
            method: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.status === 'success') {
                    alert('Terminasi berhasil diajukan untuk persetujuan.');
                    location.reload();
                } else {
                    alert('Gagal: ' + (response.message || 'Gagal mengajukan terminasi.'));
                }
            },
            error: function(xhr) {
                alert('Gagal: ' + (xhr.responseJSON?.message || 'Gagal mengajukan terminasi.'));
            }
        });
    }
    });
}

function approveTermination() {
    const notes = prompt('Catatan persetujuan (opsional):');
    if (notes !== null) {
        $.ajax({
            url: '{{ route("marketing.contract-terminations.approve", $contractTermination->id) }}',
            method: 'POST',
            data: { 
                approval_notes: notes,
                _token: '{{ csrf_token() }}' 
            },
            success: function(response) {
                if (response.status === 'success') {
                    alert('Terminasi disetujui. Kontrak dan jadwal kerja terkait telah dihentikan.');
                    location.reload();
                } else {
                    alert('Gagal: ' + (response.message || 'Gagal menyetujui terminasi.'));
                }
            },
            error: function(xhr) {
                alert('Gagal: ' + (xhr.responseJSON?.message || 'Gagal menyetujui terminasi.'));
            }
        });
    }
}

function rejectTermination() {
    const notes = prompt('Alasan penolakan (wajib diisi):');
    if (notes && notes.trim()) {
        $.ajax({
            url: '{{ route("marketing.contract-terminations.reject", $contractTermination->id) }}',
            method: 'POST',
            data: { 
                approval_notes: notes,
                _token: '{{ csrf_token() }}' 
            },
            success: function(response) {
                if (response.status === 'success') {
                    alert('Terminasi ditolak.');
                    location.reload();
                } else {
                    alert('Gagal: ' + (response.message || 'Gagal menolak terminasi.'));
                }
            },
            error: function(xhr) {
                alert('Gagal: ' + (xhr.responseJSON?.message || 'Gagal menolak terminasi.'));
            }
        });
    } else {
        alert('Alasan penolakan wajib diisi.');
    }
}

function unpostTermination() {
    if(!confirm('Lakukan Unpost/Rollback pada terminasi ini? Status kontrak akan kembali Active. Pastikan seluruh Job Remove terkait sudah di-unpost dan di-unassign hingga kembali New Job.')) return;
    
    $.ajax({
        url: '{{ route("marketing.contract-terminations.unpost", $contractTermination->id) }}',
        method: 'POST',
        data: { _token: '{{ csrf_token() }}' },
        success: function(response) {
            if (response.status === 'success') {
                alert(response.message);
                location.reload();
            } else {
                alert('Gagal: ' + (response.message || 'Gagal melakukan unpost.'));
            }
        },
        error: function(xhr) {
            alert('Gagal: ' + (xhr.responseJSON?.message || 'Gagal melakukan unpost.'));
        }
    });
}
</script>
@endpush
