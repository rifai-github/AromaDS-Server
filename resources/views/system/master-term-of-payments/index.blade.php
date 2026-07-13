@extends('layouts.app')

@section('title', 'Master Term of Payment')
@section('breadcrumb', 'Home / Finance / Master Term of Payment')

@section('content')
<style>
    .top-page { padding: 0; }
    .top-card { background: #fff; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,.08); overflow: hidden; }
    .top-header { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid #e5e7eb; }
    .top-title { color: #214589; font-size: 20px; font-weight: 700; margin: 0; }
    .top-subtitle { color: #6b7280; font-size: 13px; margin: 4px 0 0; }
    .top-table { width: 100%; border-collapse: collapse; }
    .top-table th { background: #214589; color: #fff; font-size: 13px; padding: 12px; text-align: left; }
    .top-table td { border-bottom: 1px solid #e5e7eb; font-size: 13px; padding: 12px; vertical-align: middle; }
    .status-badge { border-radius: 999px; display: inline-flex; font-size: 12px; font-weight: 600; padding: 4px 10px; }
    .status-active { background: #dcfce7; color: #166534; }
    .status-inactive { background: #fee2e2; color: #991b1b; }
    .btn-top { border: none; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; padding: 8px 12px; text-decoration: none; }
    .btn-primary-top { background: #214589; color: #fff; }
    .btn-secondary-top { background: #f3f4f6; color: #374151; }
    .btn-danger-top { background: #dc2626; color: #fff; }
    .btn-warning-top { background: #f59e0b; color: #111827; }
    .actions { display: flex; gap: 8px; flex-wrap: wrap; }
    .modal-overlay { align-items: center; background: rgba(0,0,0,.45); display: none; inset: 0; justify-content: center; position: fixed; z-index: 9999; }
    .modal-overlay.show { display: flex; }
    .modal-box { background: #fff; border-radius: 8px; max-width: 560px; width: calc(100% - 32px); }
    .modal-header { border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; padding: 16px 20px; }
    .modal-title { color: #214589; font-size: 18px; font-weight: 700; margin: 0; }
    .modal-body { padding: 20px; }
    .modal-footer { border-top: 1px solid #e5e7eb; display: flex; gap: 8px; justify-content: flex-end; padding: 16px 20px; }
    .form-row { margin-bottom: 14px; }
    .form-row label { color: #374151; display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px; }
    .form-control { border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; padding: 9px 10px; width: 100%; }
    .form-check { align-items: center; display: flex; gap: 8px; margin-top: 8px; }
    .error-list { background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; color: #991b1b; margin-bottom: 16px; padding: 10px 14px; }
    .success-box { background: #ecfdf5; border: 1px solid #bbf7d0; border-radius: 6px; color: #166534; margin-bottom: 16px; padding: 10px 14px; }
</style>

<div class="top-page">
    @if(session('success'))
        <div class="success-box">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="error-list">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="top-card">
        <div class="top-header">
            <div>
                <h1 class="top-title">Master Term of Payment</h1>
                <p class="top-subtitle">Mengatur pilihan Terms of Payment untuk Quotation Wizard.</p>
            </div>
            <button type="button" class="btn-top btn-primary-top" onclick="openCreateModal()">
                <i class="fas fa-plus"></i>
                Add Term
            </button>
        </div>

        <div class="table-responsive">
            <table class="top-table">
                <thead>
                    <tr>
                        <th style="width: 60px;">No</th>
                        <th>Label</th>
                        <th>Value</th>
                        <th style="width: 150px;">Mode</th>
                        <th style="width: 110px;">Months</th>
                        <th style="width: 120px;">Payment Count</th>
                        <th>Description</th>
                        <th style="width: 110px;">Status</th>
                        <th style="width: 260px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($terms as $index => $term)
                        @php
                            $isAdvance = $term->code === 'advance';
                            $metadata = json_decode($term->option_description ?? '', true);
                            $hasMetadata = is_array($metadata);
                            $billingMode = $hasMetadata
                                ? ($metadata['billing_mode'] ?? ($isAdvance ? 'advance' : 'fixed_interval'))
                                : ($term->code === 'installments' ? 'per_contract_period' : ($isAdvance ? 'advance' : 'fixed_interval'));
                            $description = $hasMetadata ? ($metadata['description'] ?? null) : $term->option_description;
                            $months = $billingMode === 'fixed_interval' && ! $isAdvance
                                ? (int) ($metadata['months'] ?? $term->code)
                                : null;
                            $paymentCount = $billingMode === 'per_contract_period'
                                ? (int) ($metadata['payment_count'] ?? 0)
                                : null;
                            $modeLabel = $isAdvance
                                ? '1x Advance'
                                : ($billingMode === 'per_contract_period' ? 'Periode Kontrak' : 'Fixed Interval');
                            $termPayload = [
                                'id' => $term->id,
                                'label' => $term->label ?: $term->option_name,
                                'value' => $term->option_name,
                                'months' => $months,
                                'billing_mode' => $billingMode === 'advance' ? 'fixed_interval' : $billingMode,
                                'payment_count' => $paymentCount,
                                'is_advance' => $isAdvance,
                                'description' => $description,
                                'is_active' => $term->is_active,
                            ];
                        @endphp
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $term->label ?: $term->option_name }}</td>
                            <td>{{ $term->option_name }}</td>
                            <td>{{ $modeLabel }}</td>
                            <td>{{ $months ?: '-' }}</td>
                            <td>{{ $paymentCount ? $paymentCount . 'x' : '-' }}</td>
                            <td>{{ $description ?: '-' }}</td>
                            <td>
                                <span class="status-badge {{ $term->is_active ? 'status-active' : 'status-inactive' }}">
                                    {{ $term->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <div class="actions">
                                    <button
                                        type="button"
                                        class="btn-top btn-secondary-top"
                                        onclick='openEditModal({!! json_encode($termPayload, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) !!})'
                                    >
                                        <i class="fas fa-edit"></i>
                                        Edit
                                    </button>
                                    <form method="POST" action="{{ route('system.master-term-of-payments.toggle-status', $term) }}">
                                        @csrf
                                        <button type="submit" class="btn-top btn-warning-top">
                                            {{ $term->is_active ? 'Disable' : 'Enable' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('system.master-term-of-payments.destroy', $term) }}" onsubmit="return confirm('Hapus Term of Payment ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-top btn-danger-top">
                                            <i class="fas fa-trash"></i>
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align: center; color: #6b7280; padding: 28px;">Belum ada Term of Payment.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal-overlay" id="termModal">
    <div class="modal-box">
        <form method="POST" id="termForm" action="{{ route('system.master-term-of-payments.store') }}">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">Add Term of Payment</h2>
                <button type="button" class="btn-top btn-secondary-top" onclick="closeTermModal()">Close</button>
            </div>
            <div class="modal-body">
                <div class="form-row">
                    <label for="label">Label</label>
                    <input type="text" class="form-control" id="label" name="label" required placeholder="Contoh: 1 bulan 1x">
                </div>
                <div class="form-row">
                    <label for="value">Value yang disimpan</label>
                    <input type="text" class="form-control" id="value" name="value" required placeholder="Contoh: 1 bulan 1x">
                </div>
                <div class="form-row">
                    <label for="billing_mode">Mode Pembayaran</label>
                    <select class="form-control" id="billing_mode" name="billing_mode" onchange="toggleTermModeState()">
                        <option value="fixed_interval">Fixed Interval - tiap N bulan</option>
                        <option value="per_contract_period">Periode Kontrak - N kali dalam satu kontrak</option>
                    </select>
                </div>
                <div class="form-row">
                    <label for="months">Jumlah Bulan</label>
                    <input type="number" class="form-control" id="months" name="months" min="1" max="120" placeholder="Contoh: 1">
                </div>
                <div class="form-row">
                    <label for="payment_count">Jumlah Pembayaran Dalam Periode Kontrak</label>
                    <input type="number" class="form-control" id="payment_count" name="payment_count" min="2" max="24" placeholder="Contoh: 2, 3, atau 4">
                </div>
                <div class="form-row">
                    <label class="form-check">
                        <input type="checkbox" id="is_advance" name="is_advance" value="1" onchange="toggleTermModeState()">
                        <span>1x Advance / bayar sekali untuk seluruh periode</span>
                    </label>
                </div>
                <div class="form-row">
                    <label for="description">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                </div>
                <label class="form-check">
                    <input type="checkbox" id="is_active" name="is_active" value="1" checked>
                    <span>Active</span>
                </label>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-top btn-secondary-top" onclick="closeTermModal()">Cancel</button>
                <button type="submit" class="btn-top btn-primary-top">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openCreateModal() {
        document.getElementById('modalTitle').textContent = 'Add Term of Payment';
        document.getElementById('termForm').action = '{{ route('system.master-term-of-payments.store') }}';
        document.getElementById('formMethod').value = 'POST';
        document.getElementById('termForm').reset();
        document.getElementById('is_active').checked = true;
        document.getElementById('billing_mode').value = 'fixed_interval';
        toggleTermModeState();
        document.getElementById('termModal').classList.add('show');
    }

    function openEditModal(term) {
        document.getElementById('modalTitle').textContent = 'Edit Term of Payment';
        document.getElementById('termForm').action = '{{ url('/system/master-term-of-payments') }}/' + term.id;
        document.getElementById('formMethod').value = 'PUT';
        document.getElementById('label').value = term.label || '';
        document.getElementById('value').value = term.value || '';
        document.getElementById('months').value = term.months || '';
        document.getElementById('billing_mode').value = term.billing_mode || 'fixed_interval';
        document.getElementById('payment_count').value = term.payment_count || '';
        document.getElementById('is_advance').checked = !!term.is_advance;
        document.getElementById('description').value = term.description || '';
        document.getElementById('is_active').checked = !!term.is_active;
        toggleTermModeState();
        document.getElementById('termModal').classList.add('show');
    }

    function closeTermModal() {
        document.getElementById('termModal').classList.remove('show');
    }

    function toggleTermModeState() {
        const isAdvance = document.getElementById('is_advance').checked;
        const billingMode = document.getElementById('billing_mode');
        const months = document.getElementById('months');
        const paymentCount = document.getElementById('payment_count');

        billingMode.disabled = isAdvance;
        months.disabled = isAdvance || billingMode.value !== 'fixed_interval';
        paymentCount.disabled = isAdvance || billingMode.value !== 'per_contract_period';

        if (isAdvance) {
            months.value = '';
            paymentCount.value = '';
        } else if (billingMode.value === 'fixed_interval') {
            paymentCount.value = '';
        } else {
            months.value = '';
        }
    }
</script>
@endsection
