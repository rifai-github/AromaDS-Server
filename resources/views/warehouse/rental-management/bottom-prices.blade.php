@extends('layouts.app')

@section('title', 'Bottom Prices')
@section('breadcrumb', 'Home / Warehouse / Rental Management / Bottom Prices')

@section('content')
<style>
    .table-container {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        overflow: auto;
    }

    .table-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .table-title {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 600;
    }

    .btn {
        padding: 8px 16px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-primary { background: #007bff; color: white; }
    .btn-primary:hover { background: #0056b3; }
    .btn-warning { background: #f59e0b; color: white; }
    .btn-warning:hover { background: #d97706; }
    .btn-danger { background: #dc3545; color: white; }
    .btn-danger:hover { background: #c82333; }
    .btn-sm { padding: 4px 8px; font-size: 12px; }
    .btn-light {
        background: rgba(255, 255, 255, 0.2) !important;
        color: white !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
    }

    .table-wrapper { overflow-x: auto; }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        margin: 0;
    }

    .data-table th,
    .data-table td {
        padding: 12px 16px;
        text-align: left;
        border-bottom: 1px solid #e9ecef;
        color: #374151;
    }

    .data-table th {
        background: #f8f9fa;
        font-weight: 600;
        color: #495057;
        font-size: 14px;
    }

    .data-table tbody tr:hover { background: #f8f9fa; }
    .data-table tbody tr.row-active { background: #f0fdf4; }
    .data-table tbody tr.row-active:hover { background: #e6fbec; }

    .badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
        display: inline-block;
    }

    .badge-info { background: #dbeafe; color: #1e40af; }
    .badge-primary { background: #e0e7ff; color: #3730a3; }
    .badge-success { background: #d4edda; color: #155724; }
    .badge-danger { background: #f8d7da; color: #721c24; }

    .empty-state { text-align: center; padding: 60px 20px; color: #6c757d; }
    .empty-state i { font-size: 4rem; margin-bottom: 20px; color: #dee2e6; }

    .modal-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1050;
        justify-content: center;
        align-items: center;
    }

    .modal-overlay.show { display: flex; }

    .modal-content {
        background: white;
        border-radius: 8px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        max-width: 90vw;
        width: 500px;
    }

    .modal-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        border-radius: 8px 8px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-close {
        background: none;
        border: none;
        color: white;
        font-size: 24px;
        cursor: pointer;
    }

    .modal-body { padding: 20px; }

    .modal-footer {
        padding: 15px 20px;
        border-top: 1px solid #dee2e6;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    .form-row-split {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }

    .form-label { font-weight: 500; color: #495057; margin-bottom: 5px; }
    .form-control, .form-select {
        border: 1px solid #ced4da;
        border-radius: 4px;
        padding: 8px 12px;
        font-size: 14px;
        width: 100%;
    }
    .text-danger { color: #dc3545 !important; }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="table-container">
                <div class="table-header">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <a href="{{ route('warehouse.master-rentals.show', $rental->id) }}" class="btn btn-light">
                            <i class="fas fa-arrow-left"></i>
                            Back to Master Rental
                        </a>
                        <h3 class="table-title" style="margin: 0;">
                            <i class="fas fa-tags"></i>
                            Bottom Prices &mdash; {{ $rental->rental_name }}
                        </h3>
                    </div>
                    <div>
                        <button class="btn btn-primary" onclick="openAddModal()">
                            <i class="fas fa-plus"></i>
                            Add Bottom Price
                        </button>
                    </div>
                </div>

                <div class="table-wrapper">
                    @if($bottomPrices->count() > 0)
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Branch</th>
                                    <th>Tipe Penawaran</th>
                                    <th>Bottom Price</th>
                                    <th>Status</th>
                                    <th>Updated By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($bottomPrices as $bp)
                                <tr class="{{ $bp->is_active ? 'row-active' : '' }}">
                                    <td>{{ $bp->branch->name ?? '-' }}</td>
                                    <td>
                                        <span class="badge badge-info">{{ $bp->offer_type === 'hari' ? 'Harian' : 'Bulanan' }}</span>
                                    </td>
                                    <td>{{ $bp->formatted_bottom_price }}</td>
                                    <td>
                                        @if($bp->is_active)
                                            <span class="badge badge-success"><i class="fas fa-check-circle"></i> Active</span>
                                        @else
                                            <span class="badge badge-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td>{{ $bp->updatedBy->name ?? '-' }}<br><small class="text-muted">{{ optional($bp->updated_at)->format('d/m/Y H:i') }}</small></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" onclick="openEditModal({
                                            id: {{ $bp->id }},
                                            branch_id: {{ $bp->branch_id }},
                                            offer_type: '{{ $bp->offer_type }}',
                                            bottom_price: '{{ $bp->bottom_price }}',
                                            replacement_price: '{{ $bp->replacement_price }}'
                                        })" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteBottomPrice({{ $bp->id }})" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="empty-state">
                            <i class="fas fa-tags"></i>
                            <h5>Belum ada Bottom Price</h5>
                            <p>Tambahkan harga jual terendah per cabang dan tipe penawaran (harian/bulanan).</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Bottom Price Modal -->
<div class="modal-overlay" id="addBottomPriceModal">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="bottomPriceModalTitle">Add Bottom Price</h5>
            <button type="button" class="modal-close" onclick="closeAddModal()">&times;</button>
        </div>
        <form id="bottomPriceForm" onsubmit="return false;">
            <input type="hidden" id="bottom_price_id" name="bottom_price_id">
            <div class="modal-body">
                <div class="mb-3 form-row-split">
                    <div>
                        <label class="form-label">Branch <span class="text-danger">*</span></label>
                        <select class="form-select" id="branch_id" name="branch_id" required>
                            <option value="">-- Pilih Branch --</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">TOP <span class="text-danger">*</span></label>
                        <select class="form-select" id="offer_type" name="offer_type" required>
                            <option value="bulan">Bulanan</option>
                            <option value="hari">Harian</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Bottom Price (Rp) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="bottom_price" name="bottom_price" min="0" step="0.01" required>
                </div>
                {{-- Replacement Price hidden from UI (not used by any pricing/approval logic yet,
                     only confused users). Kept as a hidden field so backend validation/storage
                     still work unchanged: defaults to 0 for new rows, keeps its stored value on edit. --}}
                <input type="hidden" id="replacement_price" name="replacement_price" value="0">
                <p class="text-muted" style="font-size: 13px; margin: 0;">
                    <i class="fas fa-info-circle"></i>
                    Bottom price yang dipakai untuk cabang &amp; tipe penawaran ini otomatis mengikuti input terakhir.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" style="background:#f3f4f6;color:#374151;" onclick="closeAddModal()">Cancel</button>
                <button type="button" class="btn btn-primary" id="bottomPriceSubmitBtn" onclick="submitBottomPriceForm()">
                    <i class="fas fa-save me-1"></i>
                    Save
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const rentalId = {{ $rental->id }};
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    let editingBottomPriceId = null;

    function openAddModal() {
        editingBottomPriceId = null;
        document.getElementById('bottomPriceForm').reset();
        document.getElementById('bottom_price_id').value = '';
        setSelectValue('branch_id', '');
        setSelectValue('offer_type', 'bulan');
        document.getElementById('bottomPriceModalTitle').textContent = 'Add Bottom Price';
        document.getElementById('bottomPriceSubmitBtn').innerHTML = '<i class="fas fa-save me-1"></i>Save';
        document.getElementById('addBottomPriceModal').classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function setSelectValue(selectId, value) {
        const select = document.getElementById(selectId);
        if (!select) return;

        select.value = value ?? '';

        if (typeof jQuery !== 'undefined' && jQuery(select).data('select2')) {
            jQuery(select).trigger('change.select2');
        } else {
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    function openEditModal(bottomPrice) {
        editingBottomPriceId = bottomPrice.id;
        document.getElementById('bottomPriceForm').reset();
        document.getElementById('bottom_price_id').value = bottomPrice.id;
        setSelectValue('branch_id', bottomPrice.branch_id);
        setSelectValue('offer_type', bottomPrice.offer_type);
        document.getElementById('bottom_price').value = bottomPrice.bottom_price;
        document.getElementById('replacement_price').value = bottomPrice.replacement_price;
        document.getElementById('bottomPriceModalTitle').textContent = 'Edit Bottom Price';
        document.getElementById('bottomPriceSubmitBtn').innerHTML = '<i class="fas fa-save me-1"></i>Update';
        document.getElementById('addBottomPriceModal').classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeAddModal() {
        document.getElementById('addBottomPriceModal').classList.remove('show');
        document.body.style.overflow = 'auto';
    }

    function submitBottomPriceForm() {
        const submitBtn = document.getElementById('bottomPriceSubmitBtn');
        if (submitBtn.disabled) return;
        submitBtn.disabled = true;
        const originalHtml = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';

        const formData = new FormData(document.getElementById('bottomPriceForm'));
        formData.append('_token', csrfToken);
        const url = editingBottomPriceId
            ? `/warehouse/rental-management/bottom-prices/${editingBottomPriceId}`
            : `/warehouse/rental-management/rentals/${rentalId}/bottom-prices`;

        if (editingBottomPriceId) {
            formData.append('_method', 'PUT');
        }

        fetch(url, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Gagal menyimpan'));
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalHtml;
            }
        })
        .catch(error => {
            alert('Terjadi kesalahan: ' + error.message);
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalHtml;
        });
    }

    function deleteBottomPrice(id) {
        if (!confirm('Hapus bottom price ini?')) return;

        fetch(`/warehouse/rental-management/bottom-prices/${id}`, {
            method: 'DELETE',
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Gagal menghapus'));
            }
        })
        .catch(error => alert('Terjadi kesalahan: ' + error.message));
    }
</script>
@endpush
