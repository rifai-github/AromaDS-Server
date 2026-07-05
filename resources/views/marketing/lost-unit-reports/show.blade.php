@extends('layouts.app')

@section('title', 'Lost Unit Report Detail')

@section('content')
<style>
    .report-header {
        background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
        color: white;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
    }
    
    .report-header-title {
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0;
    }
    
    .report-header-subtitle {
        font-size: 0.875rem;
        opacity: 0.9;
        margin-top: 4px;
    }
    
    .status-badge {
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .status-draft { background: #fef3c7; color: #92400e; }
    .status-submitted { background: #dbeafe; color: #1e40af; }
    .status-approved { background: #d1fae5; color: #065f46; }
    .status-rejected { background: #fee2e2; color: #991b1b; }
    
    .info-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        margin-bottom: 24px;
    }
    
    .info-card-header {
        background: #f8fafc;
        padding: 16px 24px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .info-card-title {
        font-size: 1rem;
        font-weight: 600;
        color: #1e3a8a;
        margin: 0;
    }
    
    .info-card-body {
        padding: 24px;
    }
    
    .info-row {
        display: flex;
        margin-bottom: 16px;
        padding-bottom: 16px;
        border-bottom: 1px solid #f1f5f9;
    }
    
    .info-row:last-child {
        margin-bottom: 0;
        padding-bottom: 0;
        border-bottom: none;
    }
    
    .info-label {
        flex: 0 0 40%;
        font-weight: 500;
        color: #64748b;
    }
    
    .info-value {
        flex: 0 0 60%;
        color: #1e293b;
    }
    
    .price-display {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1e3a8a;
    }
    
    .price-original {
        font-size: 0.875rem;
        color: #64748b;
        text-decoration: line-through;
    }
    
    .price-manual-badge {
        background: #fef3c7;
        color: #92400e;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
        margin-left: 8px;
    }
    
    .btn-action {
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.875rem;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
        border: none;
        text-decoration: none;
    }
    
    .btn-primary { background: #1e3a8a; color: white; }
    .btn-primary:hover { background: #1e40af; color: white; }
    
    .btn-success { background: #059669; color: white; }
    .btn-success:hover { background: #047857; color: white; }
    
    .btn-warning { background: #f59e0b; color: white; }
    .btn-warning:hover { background: #d97706; color: white; }
    
    .btn-danger { background: #dc2626; color: white; }
    .btn-danger:hover { background: #b91c1c; color: white; }
    
    .btn-secondary { background: #6b7280; color: white; }
    .btn-secondary:hover { background: #4b5563; color: white; }
    
    .btn-outline {
        background: white;
        color: #1e3a8a;
        border: 2px solid #1e3a8a;
    }
    .btn-outline:hover {
        background: #1e3a8a;
        color: white;
    }
    
    .action-buttons {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }
    
    .edit-price-input {
        width: 200px;
        padding: 8px 12px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 600;
    }
    
    .edit-price-input:focus {
        outline: none;
        border-color: #1e3a8a;
    }
    
    .grid-2 {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 24px;
    }
    
    @media (max-width: 768px) {
        .grid-2 {
            grid-template-columns: 1fr;
        }
        
        .info-row {
            flex-direction: column;
            gap: 4px;
        }
        
        .info-label, .info-value {
            flex: 1;
        }
    }
</style>

<div class="container-fluid">
    <!-- Back Button & Header -->
    <div class="report-header">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <a href="{{ route('marketing.lost-unit-reports.index') }}" class="text-white text-decoration-none mb-2 d-inline-block">
                    <i class="fas fa-arrow-left me-2"></i> Back to List
                </a>
                <h1 class="report-header-title">{{ $report->report_number }}</h1>
                <p class="report-header-subtitle">Lost Unit Report</p>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="status-badge status-{{ $report->status }}">
                    {{ $report->status_text }}
                </span>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Action Buttons -->
    <div class="info-card">
        <div class="info-card-body">
            <div class="action-buttons">
                @if($report->status === 'draft')
                    <form action="{{ route('marketing.lost-unit-reports.finalize', $report->id) }}" method="POST" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn-action btn-success" onclick="return confirm('Apakah Anda yakin ingin finalize report ini?')">
                            <i class="fas fa-check-circle"></i> Finalize
                        </button>
                    </form>
                @elseif($report->status === 'submitted')
                    @if(Auth::check() && Auth::user()->canApprove('lost_unit_reports'))
                        <form action="{{ route('marketing.lost-unit-reports.approve', $report->id) }}" method="POST" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn-action btn-success" onclick="return confirm('Apakah Anda yakin ingin approve report ini? Invoice akan otomatis dibuat.')">
                                <i class="fas fa-check"></i> Approve
                            </button>
                        </form>
                        <button type="button" class="btn-action btn-danger" onclick="openRejectModal()">
                            <i class="fas fa-times"></i> Reject
                        </button>
                    @endif
                    <form action="{{ route('marketing.lost-unit-reports.unpost', $report->id) }}" method="POST" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn-action btn-secondary" onclick="return confirm('Apakah Anda yakin ingin unpost ke Draft?')">
                            <i class="fas fa-undo"></i> Unpost
                        </button>
                    </form>
                @elseif($report->status === 'approved')
                    @if($report->invoice)
                        <a href="{{ route('finance.invoices.show', $report->invoice_id) }}" class="btn-action btn-primary">
                            <i class="fas fa-file-invoice"></i> View Invoice
                        </a>
                    @endif
                    @if(!$report->hasInvoice())
                        <form action="{{ route('marketing.lost-unit-reports.unpost', $report->id) }}" method="POST" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn-action btn-secondary" onclick="return confirm('Apakah Anda yakin ingin unpost ke Draft?')">
                                <i class="fas fa-undo"></i> Unpost
                            </button>
                        </form>
                    @else
                        <span class="text-muted">
                            <i class="fas fa-info-circle"></i> Untuk unpost, hapus Invoice terlebih dahulu
                        </span>
                    @endif
                @endif
            </div>
        </div>
    </div>

    <div class="grid-2">
        <!-- Contract Information -->
        <div class="info-card">
            <div class="info-card-header">
                <h3 class="info-card-title"><i class="fas fa-file-contract me-2"></i>Contract Information</h3>
            </div>
            <div class="info-card-body">
                <div class="info-row">
                    <div class="info-label">Contract Number</div>
                    <div class="info-value">
                        @if($report->contract)
                            <a href="{{ route('marketing.contracts.show', $report->contract_id) }}" class="text-primary fw-bold" target="_blank" rel="noopener noreferrer">
                                {{ $report->contract_number }}
                            </a>
                        @else
                            {{ $report->contract_number ?? '-' }}
                        @endif
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Company Name</div>
                    <div class="info-value">{{ $report->company_name ?? '-' }}</div>
                </div>
                @if($report->contract)
                    <div class="info-row">
                        <div class="info-label">Contract Period</div>
                        <div class="info-value">
                            {{ $report->contract->start_date ? $report->contract->start_date->format('d/M/Y') : '-' }}
                            - 
                            {{ $report->contract->end_date ? $report->contract->end_date->format('d/M/Y') : '-' }}
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Contract Status</div>
                        <div class="info-value">
                            <span class="badge bg-{{ $report->contract->status === 'active' ? 'success' : 'secondary' }}">
                                {{ ucfirst($report->contract->status ?? '-') }}
                            </span>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Customer Information -->
        <div class="info-card">
            <div class="info-card-header">
                <h3 class="info-card-title"><i class="fas fa-building me-2"></i>Customer Information</h3>
            </div>
            <div class="info-card-body">
                @if($report->contract && $report->contract->customer)
                    <div class="info-row">
                        <div class="info-label">Customer Name</div>
                        <div class="info-value">{{ $report->contract->customer->name ?? '-' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Address</div>
                        <div class="info-value">{{ $report->contract->customer->address ?? '-' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Phone</div>
                        <div class="info-value">{{ $report->contract->customer->phone ?? '-' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Email</div>
                        <div class="info-value">{{ $report->contract->customer->email ?? '-' }}</div>
                    </div>
                @else
                    <p class="text-muted">Customer information not available</p>
                @endif
            </div>
        </div>
    </div>

    <div class="grid-2">
        <!-- Rental & Room Information -->
        <div class="info-card">
            <div class="info-card-header">
                <h3 class="info-card-title"><i class="fas fa-box me-2"></i>Rental & Room Information</h3>
            </div>
            <div class="info-card-body">
                @if($report->items && $report->items->count() > 0)
                    <div class="table-responsive">
                       <table class="table table-bordered table-striped">
                           <thead>
                               <tr>
                                   <th>Room</th>
                                   <th>Building</th>
                                   <th>Rental</th>
                                   <th>Price</th>
                               </tr>
                           </thead>
                           <tbody>
                               @foreach($report->items as $item)
                               <tr>
                                   <td>{{ $item->room->room_name ?? ($item->room->name ?? '-') }}</td>
                                   <td>{{ $item->room->building->name ?? ($item->room->building->nama_gedung ?? '-') }}</td>
                                   <td>{{ $item->masterRental->name ?? ($item->masterRental->rental_name ?? '-') }}</td>
                                   <td>
                                       @if($report->status === 'draft')
                                           <input type="number" 
                                                  class="form-control item-price-input" 
                                                  data-item-id="{{ $item->id }}"
                                                  data-report-id="{{ $report->id }}"
                                                  value="{{ $item->price }}" 
                                                  step="1000"
                                                  min="0">
                                       @else
                                           <span class="text-end">{{ 'Rp ' . number_format($item->price, 0, ',', '.') }}</span>
                                       @endif
                                   </td>
                               </tr>
                               @endforeach
                           </tbody>
                       </table>
                    </div>
                @else
                    <div class="info-row">
                        <div class="info-label">Rental</div>
                        <div class="info-value fw-bold">{{ $report->rental_name ?? ($report->masterRental->name ?? '-') }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Room</div>
                        <div class="info-value">{{ $report->room_name ?? ($report->room->room_name ?? '-') }}</div>
                    </div>
                @endif
                

                
                @if($report->items && $report->items->count() > 0)
                     {{-- Collect unique buildings from items --}}
                     @php
                        $buildings = $report->items->map(function($item) {
                            return $item->room->building ?? null;
                        })->filter()->unique('id');
                     @endphp
                     
                     <div class="info-row mt-3">
                        <div class="info-label">Building(s)</div>
                        <div class="info-value">
                            @if($buildings->count() > 0)
                                <ul class="mb-0 ps-3">
                                @foreach($buildings as $bding)
                                    <li>{{ $bding->name ?? $bding->nama_gedung ?? '-' }}</li>
                                @endforeach
                                </ul>
                            @else
                                {{ $report->building->name ?? $report->building->nama_gedung ?? '-' }}
                            @endif
                        </div>
                    </div>
                @elseif($report->building)
                    <div class="info-row mt-3">
                        <div class="info-label">Building</div>
                        <div class="info-value">{{ $report->building->name ?? $report->building->nama_gedung ?? '-' }}</div>
                    </div>
                @endif
                @if($report->branch)
                    <div class="info-row">
                        <div class="info-label">Branch</div>
                        <div class="info-value">
                            <span class="badge bg-primary">{{ $report->branch->name ?? '-' }} ({{ $report->branch->code ?? '-' }})</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Price Information -->
        <div class="info-card">
            <div class="info-card-header">
                <h3 class="info-card-title"><i class="fas fa-money-bill-wave me-2"></i>Price Information</h3>
            </div>
            <div class="info-card-body">
                <div class="info-row">
                    <div class="info-label">Lost Unit Price (Total)</div>
                    <div class="info-value">
                        <span class="price-display" id="total-price-display">{{ $report->formatted_lost_unit_price }}</span>
                        @if($report->items && $report->items->count() > 0)
                            <div><small class="text-muted">Auto-calculated from room prices</small></div>
                        @endif
                    </div>
                </div>
                @if($report->invoice)
                    <div class="info-row">
                        <div class="info-label">Invoice Number</div>
                        <div class="info-value">
                            <a href="{{ route('finance.invoices.show', $report->invoice_id) }}" class="text-primary fw-bold" target="_blank" rel="noopener noreferrer">
                                {{ $report->invoice->invoice_number ?? '-' }}
                            </a>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Invoice Status</div>
                        <div class="info-value">
                            <span class="badge bg-{{ $report->invoice->status === 'paid' ? 'success' : ($report->invoice->status === 'draft' ? 'warning' : 'info') }}">
                                {{ ucfirst($report->invoice->status ?? '-') }}
                            </span>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Remark Information -->
    <div class="info-card">
        <div class="info-card-header">
            <h3 class="info-card-title"><i class="fas fa-comment-alt me-2"></i>Remark</h3>
        </div>
        <div class="info-card-body">
            @if($report->status === 'draft')
                <form action="{{ route('marketing.lost-unit-reports.update', $report->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="lost_unit_price" value="{{ $report->lost_unit_price }}">
                    <div class="mb-3">
                        <textarea name="remark" class="form-control" rows="4" placeholder="Enter description about the lost unit..." required>{{ $report->remark }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Upload BAP <span class="text-danger">*</span></label>
                        <input type="file" name="bap_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                        @if($report->bap_file)
                            <div class="small mt-2">
                                <a href="{{ Storage::disk('public')->url($report->bap_file) }}" target="_blank" rel="noopener noreferrer">Lihat BAP saat ini</a>
                            </div>
                        @endif
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Customer dikenakan charge?</label>
                        <select name="charge_customer" class="form-control">
                            <option value="1" {{ $report->charge_customer ? 'selected' : '' }}>Ya</option>
                            <option value="0" {{ !$report->charge_customer ? 'selected' : '' }}>Tidak</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nominal Charge</label>
                        <input type="number" name="charge_amount" class="form-control" min="0" step="0.01" value="{{ old('charge_amount', $report->charge_amount ?? $report->lost_unit_price) }}">
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn-action btn-primary btn-sm">
                            <i class="fas fa-save me-1"></i> Save Remark
                        </button>
                    </div>
                </form>
            @else
                <p class="mb-0">{{ $report->remark ?? 'No remark provided' }}</p>
                <div class="small mt-2">
                    @if($report->bap_file)
                        <a href="{{ Storage::disk('public')->url($report->bap_file) }}" target="_blank" rel="noopener noreferrer">
                            <i class="fas fa-file-alt me-1"></i>Lihat BAP
                        </a>
                    @else
                        <span class="text-muted">Tidak ada file BAP</span>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <!-- Audit Trail -->
    <div class="info-card">
        <div class="info-card-header">
            <h3 class="info-card-title"><i class="fas fa-history me-2"></i>Audit Trail</h3>
        </div>
        <div class="info-card-body">
            <div class="grid-2">
                <div>
                    <div class="info-row">
                        <div class="info-label">Created By</div>
                        <div class="info-value">{{ $report->creator->name ?? '-' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Created At</div>
                        <div class="info-value">{{ $report->created_at ? $report->created_at->format('d/M/Y H:i') : '-' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Reported By</div>
                        <div class="info-value">{{ $report->reporter->name ?? '-' }}</div>
                    </div>
                </div>
                <div>
                    <div class="info-row">
                        <div class="info-label">Last Updated By</div>
                        <div class="info-value">{{ $report->updater->name ?? '-' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Last Updated At</div>
                        <div class="info-value">{{ $report->updated_at ? $report->updated_at->format('d/M/Y H:i') : '-' }}</div>
                    </div>
                    @if($report->finalized_at)
                        <div class="info-row">
                            <div class="info-label">Finalized By</div>
                            <div class="info-value">{{ $report->finalizer->name ?? '-' }} at {{ $report->finalized_at->format('d/M/Y H:i') }}</div>
                        </div>
                    @endif
                    @if($report->approved_at)
                        <div class="info-row">
                            <div class="info-label">Approved By</div>
                            <div class="info-value">{{ $report->approver->name ?? '-' }} at {{ $report->approved_at->format('d/M/Y H:i') }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@if(Auth::check() && Auth::user()->canApprove('lost_unit_reports'))
<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('marketing.lost-unit-reports.reject', $report->id) }}" method="POST">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Reject Lost Unit Report</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" class="form-control" rows="4" required placeholder="Please provide a reason for rejection..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Report</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openRejectModal() {
    var modal = new bootstrap.Modal(document.getElementById('rejectModal'));
    modal.show();
}

// Auto-update total price when item price changes
$(document).ready(function() {
    $('.item-price-input').on('change', function() {
        const itemId = $(this).data('item-id');
        const reportId = $(this).data('report-id');
        const newPrice = $(this).val();
        const $input = $(this);
        
        // Disable input while saving
        $input.prop('disabled', true);
        
        $.ajax({
            url: `/marketing/lost-unit-reports/items/${itemId}/update-price`,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                price: newPrice
            },
            success: function(response) {
                // Update total display
                $('#total-price-display').text(response.formatted_total);
                
                // Show success message
                if (typeof toastr !== 'undefined') {
                    toastr.success('Price updated successfully');
                } else {
                    alert('Price updated successfully');
                }
                
                // Re-enable input
                $input.prop('disabled', false);
            },
            error: function(xhr) {
                // Show error message
                const errorMsg = xhr.responseJSON?.message || 'Failed to update price';
                if (typeof toastr !== 'undefined') {
                    toastr.error(errorMsg);
                } else {
                    alert(errorMsg);
                }
                
                // Re-enable input
                $input.prop('disabled', false);
            }
        });
    });
});
</script>
@endif
@endsection
