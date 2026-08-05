@extends('layouts.app')

@section('title', 'Master Price Slab')
@section('breadcrumb', 'Home / Marketing / Master Price Slab')

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

    .table-title { margin: 0; font-size: 1.5rem; font-weight: 600; }

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

    .table-wrapper { overflow-x: auto; }

    .data-table { width: 100%; border-collapse: collapse; margin: 0; }

    .data-table th,
    .data-table td {
        padding: 12px 16px;
        text-align: left;
        border-bottom: 1px solid #e9ecef;
        color: #374151;
        vertical-align: top;
    }

    .data-table th {
        background: #f8f9fa;
        font-weight: 600;
        color: #495057;
        font-size: 14px;
    }

    .data-table tbody tr:hover { background: #f8f9fa; }

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
    .badge-muted { background: #f3f4f6; color: #6b7280; }

    .discount-cell { font-size: 1.15rem; font-weight: 700; color: #3730a3; }

    .hint { color: #6b7280; font-size: 12px; margin-top: 4px; display: block; }

    .alert-warning {
        background: #fef3c7;
        border-left: 4px solid #f59e0b;
        color: #92400e;
        padding: 14px 18px;
        margin: 0;
        font-size: 14px;
    }

    .info-strip {
        background: #eef2ff;
        border-bottom: 1px solid #e0e7ff;
        padding: 14px 20px;
        color: #3730a3;
        font-size: 13px;
    }

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
        width: 560px;
        max-height: 90vh;
        overflow-y: auto;
    }

    .modal-header {
        padding: 16px 20px;
        border-bottom: 1px solid #e9ecef;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-title { margin: 0; font-size: 1.15rem; font-weight: 600; }
    .modal-close { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #6b7280; }
    .modal-body { padding: 20px; }
    .modal-footer { padding: 16px 20px; border-top: 1px solid #e9ecef; display: flex; justify-content: flex-end; gap: 10px; }

    .form-label { display: block; font-weight: 500; margin-bottom: 6px; color: #374151; font-size: 14px; }
    .form-control, .form-select {
        border: 1px solid #d1d5db;
        border-radius: 4px;
        padding: 8px 12px;
        font-size: 14px;
        width: 100%;
    }
    .form-control:disabled { background: #f3f4f6; color: #6b7280; }
    .mb-3 { margin-bottom: 16px; }
    .text-danger { color: #dc3545 !important; }

    .role-picker {
        border: 1px solid #d1d5db;
        border-radius: 4px;
        max-height: 190px;
        overflow-y: auto;
        padding: 8px 12px;
    }
    .role-picker label {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 4px 0;
        font-size: 14px;
        color: #374151;
        cursor: pointer;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="table-container">
                <div class="table-header">
                    <h3 class="table-title">
                        <i class="fas fa-layer-group"></i>
                        Master Price Slab
                    </h3>
                    <button class="btn btn-primary" onclick="openAddModal()">
                        <i class="fas fa-plus"></i>
                        Tambah Level
                    </button>
                </div>

                <div class="info-strip">
                    <i class="fas fa-info-circle"></i>
                    <strong>Maks Diskon</strong> = seberapa dalam diskon di bawah <em>bottom price</em> yang boleh disetujui level tersebut.
                    Angka makin besar = wewenang makin tinggi. Quotation yang harganya sama atau di atas bottom price tetap otomatis disetujui tanpa approval.
                </div>

                @if(!$hasFullCoverage)
                    <div class="alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Belum ada level yang mencapai <strong>100%</strong>. Quotation dengan diskon sangat dalam tidak akan bisa disetujui siapa pun sampai ada level yang menutupinya.
                    </div>
                @endif

                <div class="table-wrapper">
                    @if($levels->count() > 0)
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Level</th>
                                    <th>Maks Diskon</th>
                                    <th>Contoh (bottom price Rp 1.000.000)</th>
                                    <th>Role Berwenang</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($levels as $level)
                                    @php
                                        $maxDiscount = (float) $level->max_discount_percentage;
                                        $minPrice = 1000000 * (1 - ($maxDiscount / 100));
                                        $levelRoles = $roles->whereIn('id', $levelRoleIds[$level->id] ?? []);
                                    @endphp
                                    <tr>
                                        <td>
                                            <strong>{{ $level->level_name }}</strong>
                                            <span class="hint">kode: {{ $level->level_code }}</span>
                                            @if($level->description)
                                                <span class="hint">{{ $level->description }}</span>
                                            @endif
                                        </td>
                                        <td class="discount-cell">{{ rtrim(rtrim(number_format($maxDiscount, 2, ',', '.'), '0'), ',') }}%</td>
                                        <td>
                                            boleh sampai <strong>Rp {{ number_format($minPrice, 0, ',', '.') }}</strong>
                                            <span class="hint">harga terendah yang bisa disetujui level ini</span>
                                        </td>
                                        <td>
                                            @forelse($levelRoles as $role)
                                                <span class="badge badge-primary">{{ $role->name }}</span>
                                            @empty
                                                <span class="badge badge-muted">Belum ada role</span>
                                            @endforelse
                                        </td>
                                        <td>
                                            @if($level->is_active)
                                                <span class="badge badge-success">Active</span>
                                            @else
                                                <span class="badge badge-danger">Inactive</span>
                                            @endif
                                        </td>
                                        <td style="white-space: nowrap;">
                                            <button class="btn btn-sm btn-warning" onclick="openEditModal({{ $level->id }})" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteLevel({{ $level->id }})" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="empty-state">
                            <i class="fas fa-layer-group"></i>
                            <h5>Belum ada Level Approval</h5>
                            <p>Tambahkan level beserta maksimal diskon yang boleh disetujuinya.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal-overlay" id="levelModal">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="levelModalTitle">Tambah Level</h5>
            <button type="button" class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form id="levelForm" onsubmit="return false;">
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Kode Level <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="level_code" name="level_code" maxlength="50" required>
                    <span class="hint">Huruf/angka/strip saja. Tidak bisa diubah setelah disimpan karena dipakai sebagai nama permission.</span>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nama Level <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="level_name" name="level_name" maxlength="100" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Maks Diskon (%) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="max_discount_percentage" name="max_discount_percentage" min="0" max="100" step="0.01" required>
                    <span class="hint" id="discountPreview"></span>
                </div>
                <div class="mb-3">
                    <label class="form-label">Urutan Tampilan</label>
                    <input type="number" class="form-control" id="sort_order" name="sort_order" min="0" step="1" value="0">
                    <span class="hint">Hanya mempengaruhi urutan tabel, bukan besar wewenang.</span>
                </div>
                <div class="mb-3">
                    <label class="form-label">Keterangan</label>
                    <input type="text" class="form-control" id="description" name="description">
                </div>
                <div class="mb-3">
                    <label class="form-label">Role Berwenang</label>
                    <div class="role-picker" id="rolePicker">
                        @foreach($roles as $role)
                            <label>
                                <input type="checkbox" class="role-checkbox" value="{{ $role->id }}">
                                {{ $role->name }}
                            </label>
                        @endforeach
                    </div>
                    <span class="hint">Tersimpan sebagai permission, jadi tetap terlihat dan bisa diubah dari menu Role &amp; Permission.</span>
                </div>
                <div>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="checkbox" id="is_active" checked>
                        Active
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" style="background:#f3f4f6;color:#374151;" onclick="closeModal()">Cancel</button>
                <button type="button" class="btn btn-primary" id="levelSubmitBtn" onclick="submitLevelForm()">
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
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const levels = @json($levelsPayload);

    let editingLevelId = null;
    let editingOriginalDiscount = null;

    function updateDiscountPreview() {
        const value = parseFloat(document.getElementById('max_discount_percentage').value);
        const preview = document.getElementById('discountPreview');

        if (isNaN(value)) {
            preview.textContent = '';
            return;
        }

        const minPrice = 1000000 * (1 - (value / 100));
        preview.textContent = 'Dengan bottom price Rp 1.000.000, level ini boleh menyetujui harga sampai Rp '
            + minPrice.toLocaleString('id-ID', { maximumFractionDigits: 0 }) + '.';
    }

    function setRoleCheckboxes(roleIds) {
        const selected = (roleIds || []).map(String);
        document.querySelectorAll('.role-checkbox').forEach(function (checkbox) {
            checkbox.checked = selected.includes(String(checkbox.value));
        });
    }

    function selectedRoleIds() {
        return Array.from(document.querySelectorAll('.role-checkbox:checked')).map(function (checkbox) {
            return checkbox.value;
        });
    }

    function openAddModal() {
        editingLevelId = null;
        editingOriginalDiscount = null;
        document.getElementById('levelForm').reset();
        document.getElementById('level_code').disabled = false;
        document.getElementById('sort_order').value = 0;
        document.getElementById('is_active').checked = true;
        setRoleCheckboxes([]);
        updateDiscountPreview();
        document.getElementById('levelModalTitle').textContent = 'Tambah Level';
        document.getElementById('levelSubmitBtn').innerHTML = '<i class="fas fa-save me-1"></i>Save';
        document.getElementById('levelModal').classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function openEditModal(id) {
        const level = levels.find(function (item) { return item.id === id; });
        if (!level) return;

        editingLevelId = id;
        editingOriginalDiscount = level.max_discount_percentage;

        document.getElementById('levelForm').reset();
        document.getElementById('level_code').value = level.level_code;
        // The code backs the permission name, so it must stay stable.
        document.getElementById('level_code').disabled = true;
        document.getElementById('level_name').value = level.level_name;
        document.getElementById('max_discount_percentage').value = level.max_discount_percentage;
        document.getElementById('sort_order').value = level.sort_order ?? 0;
        document.getElementById('description').value = level.description ?? '';
        document.getElementById('is_active').checked = level.is_active;
        setRoleCheckboxes(level.role_ids);
        updateDiscountPreview();

        document.getElementById('levelModalTitle').textContent = 'Edit Level';
        document.getElementById('levelSubmitBtn').innerHTML = '<i class="fas fa-save me-1"></i>Update';
        document.getElementById('levelModal').classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        document.getElementById('levelModal').classList.remove('show');
        document.body.style.overflow = 'auto';
    }

    async function confirmLoweringDiscount(newValue) {
        if (editingLevelId === null || editingOriginalDiscount === null) return true;
        if (newValue >= editingOriginalDiscount) return true;

        // Lowering the ceiling can push quotations already waiting onto a higher level.
        let pending = null;
        try {
            const response = await fetch(`/marketing/quotation-approval-levels/${editingLevelId}/impact`, {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }
            });
            const data = await response.json();
            pending = data?.data?.pending_quotations ?? null;
        } catch (error) {
            pending = null;
        }

        let message = `Anda menurunkan maks diskon dari ${editingOriginalDiscount}% ke ${newValue}%.\n\n`
            + 'Wewenang level ini akan berkurang.';

        if (pending !== null) {
            message += `\n\nSaat ini ada ${pending} quotation yang menunggu approval di level ini dan bisa naik ke level yang lebih tinggi.`;
        }

        return confirm(message + '\n\nLanjutkan?');
    }

    async function submitLevelForm() {
        const submitBtn = document.getElementById('levelSubmitBtn');
        if (submitBtn.disabled) return;

        const newDiscount = parseFloat(document.getElementById('max_discount_percentage').value);

        if (!(await confirmLoweringDiscount(newDiscount))) return;

        submitBtn.disabled = true;
        const originalHtml = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';

        const formData = new FormData();
        formData.append('_token', csrfToken);
        formData.append('level_name', document.getElementById('level_name').value);
        formData.append('max_discount_percentage', document.getElementById('max_discount_percentage').value);
        formData.append('sort_order', document.getElementById('sort_order').value || 0);
        formData.append('description', document.getElementById('description').value || '');
        formData.append('is_active', document.getElementById('is_active').checked ? '1' : '0');
        selectedRoleIds().forEach(function (roleId) {
            formData.append('role_ids[]', roleId);
        });

        let url = '/marketing/quotation-approval-levels';

        if (editingLevelId) {
            url += `/${editingLevelId}`;
            formData.append('_method', 'PUT');
        } else {
            formData.append('level_code', document.getElementById('level_code').value);
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
                return;
            }

            const errors = data.errors ? Object.values(data.errors).flat().join('\n') : null;
            alert('Error: ' + (errors || data.message || 'Gagal menyimpan'));
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalHtml;
        })
        .catch(error => {
            alert('Terjadi kesalahan: ' + error.message);
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalHtml;
        });
    }

    function deleteLevel(id) {
        if (!confirm('Hapus level approval ini? Role yang sudah ditugaskan tetap tersimpan bila level dipulihkan.')) return;

        fetch(`/marketing/quotation-approval-levels/${id}`, {
            method: 'DELETE',
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                location.reload();
                return;
            }
            alert('Error: ' + (data.message || 'Gagal menghapus'));
        })
        .catch(error => alert('Terjadi kesalahan: ' + error.message));
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('max_discount_percentage').addEventListener('input', updateDiscountPreview);
    });
</script>
@endpush
