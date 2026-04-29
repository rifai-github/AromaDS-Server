@extends('layouts.app')

@section('title', 'Kode Pajak')
@section('breadcrumb', 'Home / Finance / Kode Pajak')

@section('content')
<style>
    .tax-code-wrapper {
        width: 100%;
        max-width: 96%;
        margin: 0 auto 40px;
    }

    .tax-code-shell {
        background: #fff;
        border-radius: 10px 10px 0 0;
        border-bottom: 1px solid #e5e7eb;
        padding: 16px 20px;
    }

    .tax-code-hint {
        font-size: 13px;
        color: #6b7280;
        line-height: 1.5;
    }

    .table-container {
        background: white;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        border-radius: 0 0 10px 10px;
        position: relative;
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        overflow-y: visible;
        -webkit-overflow-scrolling: touch;
    }

    .table-container::-webkit-scrollbar {
        height: 8px;
    }

    .table-container::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }

    .table-container::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 4px;
    }

    .table-container::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }

    .responsive-table {
        min-width: 1320px;
        width: 100%;
        border-collapse: collapse;
        table-layout: auto;
    }

    .responsive-table th,
    .responsive-table td {
        padding: 12px 10px;
        text-align: left;
        border-bottom: 1px solid #e5e7eb;
        font-size: 13px;
        line-height: 1.5;
        color: #1f2937;
    }

    .responsive-table th {
        background-color: #214589;
        color: white;
        font-weight: 600;
        font-size: 13px;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .responsive-table tbody tr {
        cursor: pointer;
        transition: background-color 0.2s ease;
    }

    .responsive-table tbody tr:hover {
        background-color: #eff6ff;
    }

    .responsive-table tbody tr.tax-code-special {
        background: #fff2e8;
    }

    .responsive-table tbody tr.tax-code-special:hover {
        background: #ffe8d6;
    }

    .tax-code-code {
        width: 110px;
        text-align: center;
        vertical-align: middle !important;
        font-weight: 700;
        white-space: nowrap;
    }

    .tax-code-description,
    .tax-code-ppn,
    .tax-code-customer {
        white-space: pre-line;
        line-height: 1.55;
    }

    .tax-code-short {
        white-space: nowrap;
        text-align: center;
        vertical-align: middle !important;
    }

    .tax-code-status-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        background: #dcfce7;
        color: #166534;
    }

    .tax-code-status-badge.inactive {
        background: #fee2e2;
        color: #991b1b;
    }

    .tax-code-modal {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.45);
        z-index: 1200;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .tax-code-modal.show {
        display: flex;
    }

    .tax-code-modal-card {
        width: min(880px, 100%);
        max-height: 92vh;
        overflow: hidden;
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.22);
    }

    .tax-code-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 18px 22px;
        background: #214589;
        color: #fff;
    }

    .tax-code-modal-title {
        margin: 0;
        font-size: 18px;
        font-weight: 700;
    }

    .tax-code-modal-close {
        background: transparent;
        border: none;
        color: #fff;
        font-size: 24px;
        cursor: pointer;
        line-height: 1;
    }

    .tax-code-modal-body {
        padding: 22px;
        overflow-y: auto;
        max-height: calc(92vh - 144px);
    }

    .tax-code-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px;
    }

    .tax-code-field-full {
        grid-column: 1 / -1;
    }

    .tax-code-label {
        display: block;
        margin-bottom: 8px;
        font-size: 13px;
        font-weight: 600;
        color: #1f2937;
    }

    .tax-code-input,
    .tax-code-textarea,
    .tax-code-select {
        width: 100%;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 10px 12px;
        font-size: 13px;
        color: #111827;
        background: #fff;
    }

    .tax-code-input[readonly] {
        background: #f8fafc;
        color: #475569;
    }

    .tax-code-textarea {
        min-height: 120px;
        resize: vertical;
        white-space: pre-wrap;
    }

    .tax-code-form-error {
        margin-top: 6px;
        font-size: 12px;
        color: #b91c1c;
        display: none;
    }

    .tax-code-form-error.show {
        display: block;
    }

    .tax-code-modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        padding: 18px 22px;
        border-top: 1px solid #e5e7eb;
        background: #f8fafc;
    }

    .tax-code-btn {
        border: none;
        border-radius: 8px;
        padding: 10px 16px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
    }

    .tax-code-btn-primary {
        background: #214589;
        color: #fff;
    }

    .tax-code-btn-secondary {
        background: #e5e7eb;
        color: #374151;
    }

    .tax-code-radio-group {
        display: grid;
        gap: 10px;
    }

    .tax-code-radio-option {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 12px 14px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background: #fff;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .tax-code-radio-option:hover {
        border-color: #214589;
        background: #f8fbff;
    }

    .tax-code-radio-option input {
        margin-top: 3px;
    }

    .tax-code-radio-text {
        font-size: 13px;
        line-height: 1.45;
        color: #1f2937;
    }

    @media (max-width: 768px) {
        .tax-code-form-grid {
            grid-template-columns: 1fr;
        }

        .responsive-table {
            min-width: 1180px;
        }
    }
</style>

<div class="tax-code-wrapper">
    <div class="tax-code-shell">
        <div class="tax-code-hint">
            Klik baris untuk melihat detail kode pajak{{ $canEdit ? ' dan mengubah isinya.' : '.' }}
        </div>
    </div>

    <div class="table-container">
        <table class="responsive-table">
            <thead>
                <tr>
                    <th style="width: 120px; text-align:center;">Kode Transaksi<br>di Faktur Pajak</th>
                    <th>Keterangan</th>
                    <th>Status PPn</th>
                    <th style="width: 150px; text-align:center;">Invoices</th>
                    <th style="width: 150px; text-align:center;">Faktur Pajak</th>
                    <th style="width: 260px;">Status Customer</th>
                    <th style="width: 110px; text-align:center;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($taxCodes as $taxCode)
                    @php
                        $isSpecialRow =
                            $taxCode->invoice_status === 'Tercetak (nol)' &&
                            $taxCode->faktur_pajak_status === 'Tercetak (nol)' &&
                            $taxCode->customer_status === 'customer dan penjual tidak dapat mengkreditkan';
                    @endphp
                    <tr
                        class="{{ $isSpecialRow ? 'tax-code-special' : '' }}"
                        onclick="openTaxCodeModal({{ $taxCode->id }})"
                    >
                        <td class="tax-code-code">{{ $taxCode->code }}</td>
                        <td class="tax-code-description">{{ $taxCode->description }}</td>
                        <td class="tax-code-ppn">{{ $taxCode->ppn_status }}</td>
                        <td class="tax-code-short">{{ $taxCode->invoice_status }}</td>
                        <td class="tax-code-short">{{ $taxCode->faktur_pajak_status }}</td>
                        <td class="tax-code-customer">{{ $taxCode->customer_status }}</td>
                        <td class="tax-code-short">
                            <span class="tax-code-status-badge {{ $taxCode->is_active ? '' : 'inactive' }}">
                                {{ $taxCode->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center; padding: 24px;">Belum ada data kode pajak.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="tax-code-modal" id="taxCodeModal">
    <div class="tax-code-modal-card">
        <div class="tax-code-modal-header">
            <h3 class="tax-code-modal-title" id="taxCodeModalTitle">Detail Kode Pajak</h3>
            <button type="button" class="tax-code-modal-close" onclick="closeTaxCodeModal()">&times;</button>
        </div>

        <form id="taxCodeForm">
            @csrf
            @method('PUT')
            <div class="tax-code-modal-body">
                <div class="tax-code-form-grid">
                    <div>
                        <label class="tax-code-label" for="modal_code">Kode</label>
                        <input type="text" id="modal_code" class="tax-code-input" readonly>
                    </div>
                    <div>
                        <label class="tax-code-label" for="modal_is_active">Status</label>
                        <select id="modal_is_active" name="is_active" class="tax-code-select">
                            <option value="1">Aktif</option>
                            <option value="0">Nonaktif</option>
                        </select>
                        <div class="tax-code-form-error" data-error-for="is_active"></div>
                    </div>

                    <div class="tax-code-field-full">
                        <label class="tax-code-label" for="modal_description">Keterangan</label>
                        <textarea id="modal_description" name="description" class="tax-code-textarea"></textarea>
                        <div class="tax-code-form-error" data-error-for="description"></div>
                    </div>

                    <div class="tax-code-field-full">
                        <label class="tax-code-label">Status PPn</label>
                        <div class="tax-code-radio-group">
                            @foreach($ppnStatusOptions as $option)
                                <label class="tax-code-radio-option">
                                    <input type="radio" name="ppn_status" value="{{ $option }}">
                                    <span class="tax-code-radio-text">{{ $option }}</span>
                                </label>
                            @endforeach
                        </div>
                        <div class="tax-code-form-error" data-error-for="ppn_status"></div>
                    </div>

                    <div>
                        <label class="tax-code-label">Invoices</label>
                        <div class="tax-code-radio-group">
                            @foreach($invoiceStatusOptions as $option)
                                <label class="tax-code-radio-option">
                                    <input type="radio" name="invoice_status" value="{{ $option }}">
                                    <span class="tax-code-radio-text">{{ $option }}</span>
                                </label>
                            @endforeach
                        </div>
                        <div class="tax-code-form-error" data-error-for="invoice_status"></div>
                    </div>

                    <div>
                        <label class="tax-code-label">Faktur Pajak</label>
                        <div class="tax-code-radio-group">
                            @foreach($invoiceStatusOptions as $option)
                                <label class="tax-code-radio-option">
                                    <input type="radio" name="faktur_pajak_status" value="{{ $option }}">
                                    <span class="tax-code-radio-text">{{ $option }}</span>
                                </label>
                            @endforeach
                        </div>
                        <div class="tax-code-form-error" data-error-for="faktur_pajak_status"></div>
                    </div>

                    <div class="tax-code-field-full">
                        <label class="tax-code-label">Status Customer</label>
                        <div class="tax-code-radio-group">
                            @foreach($customerStatusOptions as $option)
                                <label class="tax-code-radio-option">
                                    <input type="radio" name="customer_status" value="{{ $option }}">
                                    <span class="tax-code-radio-text">{{ $option }}</span>
                                </label>
                            @endforeach
                        </div>
                        <div class="tax-code-form-error" data-error-for="customer_status"></div>
                    </div>
                </div>
            </div>

            <div class="tax-code-modal-footer">
                <button type="button" class="tax-code-btn tax-code-btn-secondary" onclick="closeTaxCodeModal()">Tutup</button>
                @if($canEdit)
                    <button type="submit" class="tax-code-btn tax-code-btn-primary" id="taxCodeSaveButton">Simpan Perubahan</button>
                @endif
            </div>
        </form>
    </div>
</div>

<script>
    const taxCodes = @json($taxCodes->keyBy('id'));
    const canEditTaxCodes = @json($canEdit);
    let activeTaxCodeId = null;

    function openTaxCodeModal(id) {
        const taxCode = taxCodes[id];
        if (!taxCode) return;

        activeTaxCodeId = id;

        document.getElementById('taxCodeModalTitle').textContent = `Kode Pajak ${taxCode.code}`;
        document.getElementById('modal_code').value = taxCode.code ?? '';
        document.getElementById('modal_description').value = taxCode.description ?? '';
        document.getElementById('modal_is_active').value = taxCode.is_active ? '1' : '0';

        setRadioValue('ppn_status', taxCode.ppn_status ?? 'PPN dapat di kreditkan');
        setRadioValue('invoice_status', taxCode.invoice_status ?? 'Tercetak');
        setRadioValue('faktur_pajak_status', taxCode.faktur_pajak_status ?? 'Tercetak');
        setRadioValue('customer_status', taxCode.customer_status ?? 'Bayar & setor oleh penjual');

        document.querySelectorAll('.tax-code-form-error').forEach((el) => {
            el.textContent = '';
            el.classList.remove('show');
        });

        const fields = ['modal_description', 'modal_is_active'];
        fields.forEach((fieldId) => {
            document.getElementById(fieldId).disabled = !canEditTaxCodes;
        });

        ['ppn_status', 'invoice_status', 'faktur_pajak_status', 'customer_status'].forEach((fieldName) => {
            document.querySelectorAll(`input[name="${fieldName}"]`).forEach((radio) => {
                radio.disabled = !canEditTaxCodes;
            });
        });

        document.getElementById('taxCodeModal').classList.add('show');
    }

    function setRadioValue(fieldName, value) {
        document.querySelectorAll(`input[name="${fieldName}"]`).forEach((radio) => {
            radio.checked = radio.value === value;
        });
    }

    function closeTaxCodeModal() {
        document.getElementById('taxCodeModal').classList.remove('show');
        activeTaxCodeId = null;
    }

    document.getElementById('taxCodeModal').addEventListener('click', function (event) {
        if (event.target === this) {
            closeTaxCodeModal();
        }
    });

    document.getElementById('taxCodeForm').addEventListener('submit', async function (event) {
        event.preventDefault();

        if (!canEditTaxCodes || !activeTaxCodeId) {
            return;
        }

        const saveButton = document.getElementById('taxCodeSaveButton');
        saveButton.disabled = true;
        saveButton.textContent = 'Menyimpan...';

        document.querySelectorAll('.tax-code-form-error').forEach((el) => {
            el.textContent = '';
            el.classList.remove('show');
        });

        const formData = new FormData(this);

        try {
            const response = await fetch(`{{ url('/finance/tax-codes') }}/${activeTaxCodeId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: formData,
            });

            const payload = await response.json();

            if (!response.ok) {
                if (payload.errors) {
                    Object.entries(payload.errors).forEach(([field, messages]) => {
                        const errorNode = document.querySelector(`[data-error-for="${field}"]`);
                        if (errorNode) {
                            errorNode.textContent = messages[0];
                            errorNode.classList.add('show');
                        }
                    });
                    return;
                }

                throw new Error(payload.message || 'Gagal menyimpan data.');
            }

            taxCodes[activeTaxCodeId] = payload.data;
            window.location.reload();
        } catch (error) {
            showErrorDialog('Gagal', error.message || 'Terjadi kesalahan saat menyimpan data.');
        } finally {
            saveButton.disabled = false;
            saveButton.textContent = 'Simpan Perubahan';
        }
    });
</script>
@endsection
