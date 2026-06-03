@extends('layouts.app')

@section('title', 'Master Corporate Detail')

@section('content')
{{-- Styles fixed --}}

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="card mb-3" style="background-color: #1e3a8a; color: white; border: none; border-radius: 10px;">
                <div class="card-header" style="background-color: #1e3a8a; border: none; padding: 1rem 1.5rem;">
                    <div class="row align-items-center">
                        <!-- Left: Back Button -->
                        <div class="col-auto">
                            <a href="{{ route('marketing.master-corporates.index') }}" class="btn btn-light btn-sm">
                                <i class="fas fa-arrow-left"></i> Back to List
                            </a>
                        </div>
                        
                        <!-- Center: Title & Status -->
                        <div class="col text-center">
                            <h3 class="card-title mb-1" style="color: white; font-size: 1.5rem; font-weight: bold;">
                                {{ $code }} 
                            </h3>
                            <div>
                                @if($items->where('status', 'draft')->count() > 0)
                                    <span class="badge rounded-pill" style="background-color: #ffffff !important; color: #000000 !important; font-size: 0.9rem; padding: 0.5em 1em; border-radius: 50px !important;">Draft</span>
                                @elseif($items->where('status', 'waiting_approval')->count() > 0)
                                    <span class="badge bg-primary rounded-pill">Waiting Approval</span>
                                @elseif($items->where('status', 'approved')->count() == $items->count())
                                    <span class="badge bg-success rounded-pill">Approved</span>
                                @else
                                    <span class="badge bg-warning text-dark rounded-pill">Mixed Status</span>
                                @endif
                            </div>
                        </div>

                        <!-- Right: Action Buttons -->
                        <div class="col-auto">
                            <div class="d-flex align-items-center gap-2" style="display: flex !important; flex-direction: row !important; align-items: center !important;">
                                @if($items->where('status', 'approved')->count() == 0)
                                    <a href="{{ route('marketing.master-corporates.edit-group', $code) }}" class="btn btn-warning btn-sm text-dark">
                                        <i class="fas fa-edit me-1"></i> Edit
                                    </a>
                                @endif

                                @if($items->where('status', 'draft')->count() > 0)
                                    <form action="{{ route('marketing.master-corporates.submit', $code) }}" method="POST" class="d-inline m-0">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Submit all draft items for approval?')">
                                            <i class="fas fa-paper-plane me-1"></i> Submit
                                        </button>
                                    </form>
                                @endif

                                @if($items->where('status', 'waiting_approval')->count() > 0)
                                    <button type="button" onclick="submitBulk('approve')" class="btn btn-success btn-sm text-white">
                                        <i class="fas fa-check me-1"></i> Approve
                                    </button>
                                    <button type="button" onclick="openRejectModal()" class="btn btn-danger btn-sm text-white">
                                        <i class="fas fa-times me-1"></i> Reject
                                    </button>
                                @endif
                                
                                @if($items->where('status', 'approved')->count() > 0) 
                                     <!-- Unpost Button -->
                                     <button form="unpostForm" type="submit" class="btn btn-warning btn-sm text-dark" onclick="return confirm('Are you sure you want to unpost? Status will revert to Draft.')">
                                        <i class="fas fa-undo me-1"></i> Unpost
                                     </button>
                                @endif
                            </div>
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
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Customer</div>
                            <div style="font-size: 1rem; font-weight: 600; color: #212529;">
                                {{ $customer->name ?? 'Unknown' }}
                                <div class="small text-muted">{{ $customer->code ?? '' }}</div>
                            </div>
                        </div>
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Created By</div>
                            <div style="font-size: 1rem; color: #212529;">
                                {{ $firstItem->createdBy->name ?? '-' }}
                                <div class="small text-muted">{{ $firstItem->created_at->format('d/M/Y H:i') }}</div>
                            </div>
                        </div>
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Total Items</div>
                            <div style="font-size: 1rem; color: #212529;">{{ $items->count() }}</div>
                        </div>
                        <div>
                             <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Status Summary</div>
                             <div>
                                @if($items->where('status', 'approved')->count() > 0)
                                    <span class="badge bg-success">{{ $items->where('status', 'approved')->count() }} Approved</span>
                                @endif
                                @if($items->where('status', 'waiting_approval')->count() > 0)
                                    <span class="badge bg-primary">{{ $items->where('status', 'waiting_approval')->count() }} Waiting</span>
                                @endif
                                @if($items->where('status', 'rejected')->count() > 0)
                                    <span class="badge bg-danger">{{ $items->where('status', 'rejected')->count() }} Rejected</span>
                                @endif
                             </div>
                        </div>
                    </div>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Item List Section -->
            <div class="card">
                <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #1e3a8a;">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0" style="color: #1e3a8a;">
                            <i class="fas fa-list-alt me-2"></i>Item List
                        </h5>
                    </div>
                </div>
                <div class="card-body p-0">
                     <form id="bulkActionForm" method="POST">
                        @csrf
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0 align-middle">
                                <thead class="bg-light">
                                    <tr>
                                        <th width="50" class="text-center">
                                            <input type="checkbox" id="selectAll" class="form-check-input" style="cursor: pointer;">
                                        </th>
                                        <th>Rental Unit</th>
                                        <th>Special Price</th>
                                        <th>Status</th>
                                        <th>Approved By</th>
                                        <th>Approved At</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($items as $item)
                                    <tr>
                                        <td class="text-center">
                                            @if($item->status == 'waiting_approval')
                                                <input type="checkbox" name="ids[]" value="{{ $item->id }}" class="form-check-input item-checkbox" style="cursor: pointer;">
                                            @else
                                                <input type="checkbox" disabled class="form-check-input" style="opacity: 0.5;">
                                            @endif
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark">{{ $item->masterRental->rental_name ?? '-' }}</div>
                                            <div class="small text-muted">{{ $item->masterRental->rental_type ?? '' }}</div>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-success">Rp {{ number_format($item->price, 0, ',', '.') }}</div>
                                        </td>
                                        <td>
                                            @php
                                                $badgeClass = match($item->status) {
                                                    'draft' => 'bg-info text-dark',
                                                    'approved' => 'bg-success',
                                                    'rejected' => 'bg-danger',
                                                    'waiting_approval' => 'bg-primary',
                                                    default => 'bg-secondary'
                                                };
                                                $statusLabel = match($item->status) {
                                                    'waiting_approval' => 'Waiting Approval',
                                                    default => ucfirst($item->status)
                                                };
                                            @endphp
                                            <span class="badge {{ $badgeClass }} rounded-pill">{{ $statusLabel }}</span>
                                        </td>
                                        <td>
                                            {{ $item->approvedBy->name ?? '-' }}
                                        </td>
                                        <td>
                                            {{ $item->approved_at ? $item->approved_at->format('d/M/Y H:i') : '-' }}
                                        </td>
                                        <td>
                                             @if($item->approval_notes)
                                                <span class="text-danger small fst-italic">"{{ $item->approval_notes }}"</span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <!-- Hidden inputs for rejection -->
                        <input type="hidden" name="note" id="rejectionNote">
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="rejectModalLabel">Reject Selected Items</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to reject specific items? Please provide a reason.</p>
                <div class="form-group">
                    <label class="form-label fw-bold">Rejection Reason <span class="text-danger">*</span></label>
                    <textarea id="modalRejectionNote" class="form-control" rows="3" required placeholder="Example: Price too low, incorrect rental type..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" onclick="confirmReject()" class="btn btn-danger">Confirm Reject</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.item-checkbox');
        
        if(selectAll) {
            selectAll.addEventListener('change', function() {
                const isChecked = this.checked;
                checkboxes.forEach(cb => {
                    cb.checked = isChecked;
                });
            });
        }
    });

    function submitBulk(action) {
        const checkboxes = document.querySelectorAll('.item-checkbox:checked');
        
        if (checkboxes.length === 0) {
            Swal.fire('Warning', 'Please select at least one item to approve.', 'warning');
            return;
        }

        if (action === 'approve') {
            Swal.fire({
                title: 'Approve ' + checkboxes.length + ' items?',
                text: "This will update the corporate price status to Approved.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, Approve!'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.getElementById('bulkActionForm');
                    form.action = "{{ route('marketing.master-corporates.approve', $code) }}";
                    form.submit();
                }
            });
        }
    }

    function openRejectModal() {
        const checkboxes = document.querySelectorAll('.item-checkbox:checked');
        if (checkboxes.length === 0) {
            Swal.fire('Warning', 'Please select at least one item to reject.', 'warning');
            return;
        }
        
        var myModal = new bootstrap.Modal(document.getElementById('rejectModal'));
        myModal.show();
    }

    function confirmReject() {
        const note = document.getElementById('modalRejectionNote').value;
        if (!note.trim()) {
            Swal.fire('Required', 'Please provide a rejection note.', 'error');
            return;
        }

        document.getElementById('rejectionNote').value = note;
        const form = document.getElementById('bulkActionForm');
        form.action = "{{ route('marketing.master-corporates.reject', $code) }}";
        form.submit();
    }
</script>
@endpush
@endsection
