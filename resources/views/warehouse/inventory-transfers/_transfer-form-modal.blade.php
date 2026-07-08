{{-- Shared Add/Edit Inventory Transfer modal: HTML shell + CSS + JS.
     Included by both index.blade.php (list page) and show.blade.php (detail page)
     so "Edit Transfer" can open in place instead of navigating away. --}}
<style>
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

    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
    }

    /* Modal Styles */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        backdrop-filter: blur(2px);
    }

    .modal-overlay.show {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .modal-container {
        background: white;
        border-radius: 12px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        max-width: 90vw;
        max-height: 90vh;
        width: 600px;
        overflow: hidden;
        position: relative;
    }

    .modal-header {
        background: #214589;
        color: white;
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: sticky;
        top: 0;
        z-index: 20;
    }

    .modal-title {
        font-size: 18px;
        font-weight: 600;
        margin: 0;
    }

    .modal-close {
        background: none;
        border: none;
        color: white;
        font-size: 24px;
        cursor: pointer;
        padding: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: background-color 0.2s ease;
    }

    .modal-close:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }

    .modal-body {
        padding: 20px;
        overflow-y: auto;
        max-height: calc(90vh - 140px);
    }

    .modal-footer {
        padding: 20px;
        background: #f9fafb;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: center;
        gap: 20px;
        position: sticky;
        bottom: 0;
    }

    /* Delete Confirmation Modal */
    .delete-modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        z-index: 2000;
        align-items: center;
        justify-content: center;
    }

    .delete-modal-overlay.show {
        display: flex;
    }

    .delete-modal-container {
        background: #f0f9ff;
        border-radius: 16px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        max-width: 90vw;
        width: 500px;
        overflow: hidden;
        position: relative;
        padding: 40px 30px 30px;
        text-align: center;
    }

    .delete-icon-container {
        margin-bottom: 24px;
        display: flex;
        justify-content: center;
    }

    .delete-icon {
        width: 80px;
        height: 80px;
    }

    .delete-modal-title {
        font-size: 24px;
        font-weight: 700;
        color: #1e40af;
        margin: 0 0 16px 0;
        line-height: 1.2;
    }

    .delete-modal-description {
        font-size: 16px;
        color: #6b7280;
        margin: 0 0 32px 0;
        line-height: 1.5;
        max-width: 400px;
        margin-left: auto;
        margin-right: auto;
    }

    .delete-modal-buttons {
        display: flex;
        justify-content: center;
        gap: 16px;
    }

    .btn-cancel {
        background-color: white;
        color: #1e40af;
        border: 2px solid #1e40af;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.2s ease;
        min-width: 100px;
    }

    .btn-cancel:hover {
        background-color: #f8fafc;
        border-color: #1e3a8a;
        color: #1e3a8a;
    }

    .btn-hide {
        background-color: #1e40af;
        color: white;
        border: 2px solid #1e40af;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.2s ease;
        min-width: 100px;
    }

    .btn-hide:hover {
        background-color: #1e3a8a;
        border-color: #1e3a8a;
    }

    /* Error Modal */
    .error-modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        z-index: 3000;
        align-items: center;
        justify-content: center;
    }

    .error-modal-overlay.show {
        display: flex;
    }

    .error-modal-container {
        background: #f0fdf4;
        border-radius: 16px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        max-width: 90vw;
        width: 500px;
        overflow: hidden;
        position: relative;
        padding: 40px 30px 30px;
        text-align: center;
    }

    /* Success Modal */
    .success-modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        z-index: 4000;
        align-items: center;
        justify-content: center;
    }

    .success-modal-overlay.show {
        display: flex;
    }

    .success-modal-container {
        background: #f0fdf4;
        border-radius: 16px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        max-width: 90vw;
        width: 500px;
        overflow: hidden;
        position: relative;
        padding: 40px 30px 30px;
        text-align: center;
    }

    /* Form Input Styling */
    input[type="date"], input[type="text"], select {
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
        background-color: white;
    }

    input[type="date"]:focus, input[type="text"]:focus, select:focus {
        outline: none;
        border-color: #214589;
        box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
    }

    /* Form Styles */
    .form-group {
        margin-bottom: 20px;
    }

    .space-y-4 > * + * {
        margin-top: 1rem;
    }

    .space-y-6 > * + * {
        margin-top: 1.5rem;
    }

    /* Grid Layout for Modal */
    .grid {
        display: grid;
    }

    .grid-cols-1 {
        grid-template-columns: repeat(1, minmax(0, 1fr));
    }

    .md\:grid-cols-2 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .gap-6 {
        gap: 1.5rem;
    }

    /* Modal Section Styles */
    .modal-section {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        background: #f9fafb;
    }

    .modal-section:last-child {
        margin-bottom: 0;
    }

    .modal-section-title {
        font-size: 16px;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 16px;
        padding-bottom: 8px;
        border-bottom: 1px solid #d1d5db;
    }

    .form-label {
        display: block;
        font-size: 14px;
        font-weight: 500;
        color: #374151;
        margin-bottom: 6px;
    }

    .form-input {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
        background-color: white;
    }

    .form-input:focus {
        outline: none;
        border-color: #214589;
        box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
    }

    .detail-item {
        margin-bottom: 16px;
    }

    .detail-value {
        font-size: 14px;
        color: #6b7280;
        margin: 0;
        padding: 8px 12px;
        background-color: white;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
    }

</style>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">Lihat Inventory Transfer</h2>
            <button class="modal-close" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="modalBody" class="modal-body">
            <!-- Modal content will be loaded here -->
        </div>
        <div id="modalFooter" class="modal-footer">
            <!-- Modal footer content will be loaded here -->
        </div>
    </div>
</div>

<script>
// Modal functions
function openModal(title) {
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('modalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('modalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
    document.getElementById('modalBody').innerHTML = '';
    document.getElementById('modalFooter').innerHTML = '';
}

// CRUD Modal functions
function openCreateModal() {
    openModal('Tambah Inventory Transfer');

    // Reset addedItems
    addedItems = [];

    // Show loading state
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Memuat...</p></div>';

    // Load dynamic data
    fetch('{{ route("warehouse.inventory-transfers.api.warehouses") }}', {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(warehousesResponse => {
        const warehouses = warehousesResponse.data || [];

        let warehousesOptions = '<option value="">Pilih Warehouse</option>';
        warehouses.forEach(warehouse => {
            warehousesOptions += `<option value="${warehouse.id}" data-is-center="${warehouse.is_center ? 1 : 0}">${warehouse.name}</option>`;
        });

    document.getElementById('modalBody').innerHTML = `
        <form id="form" onsubmit="submitForm(event)">
            <div class="modal-section">
                <div class="modal-section-title">Transfer Information</div>
                <div class="form-group" style="max-width: 280px;">
                    <label class="form-label">Transfer Date *</label>
                    <input type="date" name="transfer_date" class="form-input" required>
                </div>
            </div>

            <div class="modal-section">
                <div class="modal-section-title">Warehouse Details</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">From Warehouse *</label>
                        <select name="from_warehouse_id" id="from_warehouse_id" class="form-input" required data-no-select2 data-managed-select data-force-search onchange="loadTransferWarehouses()">
                            ${warehousesOptions}
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">To Warehouse *</label>
                        <select name="to_warehouse_id" id="to_warehouse_id" class="form-input" required data-no-select2 data-managed-select data-force-search onchange="syncReturnAuto('create', true)">
                            <option value="">Pilih Warehouse Asal Terlebih Dahulu</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="modal-section">
                <div class="modal-section-title">Transfer Items</div>

                <!-- Add New Item Form -->
                <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 14px 16px; margin-bottom: 16px;">
                    <div style="font-size: 12px; font-weight: 700; color: #1e40af; text-transform: uppercase; letter-spacing: 0.03em; margin-bottom: 10px;">
                        <i class="fas fa-plus-circle"></i> Tambah Produk
                    </div>
                    <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end;">
                        <div style="flex: 2 1 220px;">
                            <label class="form-label" style="margin-bottom: 4px;">Produk</label>
                            <select id="new-product-select" class="form-input" data-no-select2 data-managed-select data-force-search onchange="updateNewItemStock(this)" style="margin: 0;">
                                <option value="">Pilih Produk</option>
                            </select>
                        </div>
                        <div style="flex: 0 0 110px;">
                            <label class="form-label" style="margin-bottom: 4px;">Stok Tersedia</label>
                            <input type="text" id="new-available-stock" class="form-input" readonly placeholder="0" style="margin: 0; text-align: center; background: #f3f4f6;">
                        </div>
                        <div style="flex: 0 0 100px;">
                            <label class="form-label" style="margin-bottom: 4px;">Qty</label>
                            <input type="number" id="new-quantity" class="form-input" step="1" min="1" placeholder="0" style="margin: 0; text-align: center;">
                        </div>
                        <div style="flex: 0 0 auto;">
                            <button type="button" class="btn btn-primary" onclick="addItemToList()" style="white-space: nowrap;">
                                <i class="fas fa-plus"></i> Tambah
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Added Items Table -->
                <div id="transfer-items-container">
                    <div class="flex items-center justify-between" style="margin-bottom: 8px;">
                        <h4 class="text-sm font-medium text-gray-700" style="margin: 0;">Item untuk Ditransfer</h4>
                        <span id="items-count" class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded-full">0 item</span>
                    </div>
                    <div style="border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                            <thead>
                                <tr style="background: #f3f4f6; text-align: left;">
                                    <th style="padding: 8px 12px; font-size: 11px; text-transform: uppercase; color: #6b7280;">Produk</th>
                                    <th style="padding: 8px 12px; font-size: 11px; text-transform: uppercase; color: #6b7280; text-align: right;">Stok</th>
                                    <th style="padding: 8px 12px; font-size: 11px; text-transform: uppercase; color: #6b7280; text-align: right;">Qty Transfer</th>
                                    <th style="padding: 8px 12px; width: 40px;"></th>
                                </tr>
                            </thead>
                            <tbody id="items-list">
                                <tr id="no-items-message"><td colspan="4" style="padding: 16px; text-align: center; color: #9ca3af;">Belum ada item yang ditambahkan</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="modal-section">
                <div class="modal-section-title">Transfer Details</div>
                <input type="hidden" name="status" value="draft">
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-input" rows="3" placeholder="Masukkan catatan transfer"></textarea>
                </div>
                <div style="display: none;">
                    <input type="checkbox" id="create-is-return" data-target="create-return-section" disabled>
                </div>
                <div id="create-return-section" class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-2" style="display: none;">
                    <div class="form-group">
                        <label class="form-label">Kategori Alasan Return</label>
                        <select name="return_reason_category" class="form-input" data-no-select2>
                            <option value="">- Pilih kategori -</option>
                            <option value="slow_moving">Slow Moving</option>
                            <option value="near_expired">Near Expired</option>
                            <option value="customer_need_changed">Perubahan Kebutuhan Customer</option>
                            <option value="damaged">Rusak</option>
                            <option value="other">Lainnya</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Detail Alasan Return</label>
                        <textarea name="return_reason" class="form-input" rows="2" placeholder="Detail alasan return (opsional)"></textarea>
                    </div>
                </div>
            </div>

            <div class="modal-section">
                <div class="modal-section-title">Dokumen</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Surat Pengajuan (dari Cabang)</label>
                        <input type="file" name="submission_letter_file" class="form-input" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                        <p class="text-xs text-gray-500 mt-1">Dokumen pengajuan return dari cabang ke pusat. Opsional, bisa diupload/diganti belakangan.</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Surat Jalan (dari Pusat)</label>
                        <input type="file" name="delivery_note_file" class="form-input" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                        <p class="text-xs text-gray-500 mt-1">Dokumen surat jalan/acknowledgement dari pusat untuk cabang. Opsional, biasanya diupload saat status Transferred/Received.</p>
                    </div>
                </div>
            </div>
        </form>
    `;

        // Add modal footer
        document.getElementById('modalFooter').innerHTML = `
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
            <button type="submit" form="form" class="btn btn-primary">Tambah Transfer</button>
        `;

        // Upgrade date field + make warehouse/product dropdowns searchable
        initTransferModalEnhancements();
    })
    .catch(error => {
        console.error('Error loading data:', error);
        document.getElementById('modalBody').innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <p style="color: #e74c3c;">Gagal memuat data. Silakan coba lagi.</p>
                <button onclick="openCreateModal()" class="btn btn-primary">Coba Lagi</button>
            </div>
        `;
    });
}


function openEditModal(id) {
    openModal('Edit Inventory Transfer');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Memuat...</p></div>';

    // Load dynamic data
    Promise.all([
        fetch(`{{ url('warehouse/inventory-transfers/api/get-transfer') }}/${id}`, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin'
        }),
        fetch('{{ route("warehouse.inventory-transfers.api.warehouses") }}', {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin'
        })
    ])
    .then(responses => Promise.all(responses.map(r => r.json())))
    .then(([transferResponse, warehousesResponse]) => {
        if (transferResponse.status === 'success') {
            const data = transferResponse.data;
            const warehouses = warehousesResponse.data || [];
            const isReturn = !!(data.return_reason_category || data.return_reason);

            let warehousesOptions = '<option value="">Pilih Warehouse</option>';
            warehouses.forEach(warehouse => {
                const selected = (data.from_warehouse_id == warehouse.id || data.fromWarehouse?.id == warehouse.id) ? 'selected' : '';
                warehousesOptions += `<option value="${warehouse.id}" data-is-center="${warehouse.is_center ? 1 : 0}" ${selected}>${warehouse.name}</option>`;
            });

            let toWarehousesOptions = '<option value="">Pilih Warehouse</option>';
            warehouses.forEach(warehouse => {
                const selected = (data.to_warehouse_id == warehouse.id || data.toWarehouse?.id == warehouse.id) ? 'selected' : '';
                toWarehousesOptions += `<option value="${warehouse.id}" data-is-center="${warehouse.is_center ? 1 : 0}" ${selected}>${warehouse.name}</option>`;
            });


            document.getElementById('modalBody').innerHTML = `
                <form id="form" onsubmit="submitForm(event, ${id})">
                    <input type="hidden" name="id" value="${data.id}">
                    <div class="modal-section">
                        <div class="modal-section-title">Transfer Information</div>
                        <div class="form-group" style="max-width: 280px;">
                            <label class="form-label">Transfer Date *</label>
                            <input type="date" name="transfer_date" class="form-input" value="${data.transfer_date ? data.transfer_date.split('T')[0] : ''}" required>
                        </div>
                    </div>

                <div class="modal-section">
                    <div class="modal-section-title">Warehouse Details</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-group">
                            <label class="form-label">From Warehouse *</label>
                            <select name="from_warehouse_id" id="edit_from_warehouse_id" class="form-input" required data-no-select2 data-managed-select data-force-search onchange="loadEditTransferWarehouses()">
                                ${warehousesOptions}
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">To Warehouse *</label>
                            <select name="to_warehouse_id" id="edit_to_warehouse_id" class="form-input" required data-no-select2 data-managed-select data-force-search onchange="syncReturnAuto('edit', true)">
                                ${toWarehousesOptions}
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-section">
                    <div class="modal-section-title">Transfer Items</div>
                    <div style="border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; margin-bottom: 12px;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                            <thead>
                                <tr style="background: #f3f4f6; text-align: left;">
                                    <th style="padding: 8px 12px; font-size: 11px; text-transform: uppercase; color: #6b7280;">Produk</th>
                                    <th style="padding: 8px 12px; font-size: 11px; text-transform: uppercase; color: #6b7280; width: 120px; text-align: right;">Stok</th>
                                    <th style="padding: 8px 12px; font-size: 11px; text-transform: uppercase; color: #6b7280; width: 110px; text-align: right;">Qty</th>
                                    <th style="padding: 8px 12px; width: 40px;"></th>
                                </tr>
                            </thead>
                            <tbody id="edit-transfer-items-container">
                                ${buildEditTransferItemsHtml(data.transfer_items || [])}
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="addEditTransferItem()">
                        <i class="fas fa-plus"></i> Tambah Item
                    </button>
                </div>

                <div class="modal-section">
                    <div class="modal-section-title">Transfer Details</div>
                    <input type="hidden" name="status" value="${data.status}">
                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-input" rows="3" placeholder="Masukkan catatan transfer">${data.notes || ''}</textarea>
                    </div>
                    <div style="display: none;">
                        <input type="checkbox" id="edit-is-return" data-target="edit-return-section" disabled ${isReturn ? 'checked' : ''}>
                    </div>
                    <div id="edit-return-section" class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-2" style="display: ${isReturn ? '' : 'none'};">
                        <div class="form-group">
                            <label class="form-label">Kategori Alasan Return</label>
                            <select name="return_reason_category" class="form-input" data-no-select2>
                                <option value="">- Pilih kategori -</option>
                                <option value="slow_moving" ${data.return_reason_category == 'slow_moving' ? 'selected' : ''}>Slow Moving</option>
                                <option value="near_expired" ${data.return_reason_category == 'near_expired' ? 'selected' : ''}>Near Expired</option>
                                <option value="customer_need_changed" ${data.return_reason_category == 'customer_need_changed' ? 'selected' : ''}>Perubahan Kebutuhan Customer</option>
                                <option value="damaged" ${data.return_reason_category == 'damaged' ? 'selected' : ''}>Rusak</option>
                                <option value="other" ${data.return_reason_category == 'other' ? 'selected' : ''}>Lainnya</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Detail Alasan Return</label>
                            <textarea name="return_reason" class="form-input" rows="2" placeholder="Detail alasan return (opsional)">${data.return_reason || ''}</textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-section">
                    <div class="modal-section-title">Dokumen</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-group">
                            <label class="form-label">Surat Pengajuan (dari Cabang)</label>
                            ${data.submission_letter_file ? `<p class="text-xs text-gray-600 mb-1">Sudah ada: <a href="/storage/${data.submission_letter_file}" target="_blank" class="text-blue-600 underline">Lihat file</a></p>` : ''}
                            <input type="file" name="submission_letter_file" class="form-input" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Surat Jalan (dari Pusat)</label>
                            ${data.delivery_note_file ? `<p class="text-xs text-gray-600 mb-1">Sudah ada: <a href="/storage/${data.delivery_note_file}" target="_blank" class="text-blue-600 underline">Lihat file</a></p>` : ''}
                            <input type="file" name="delivery_note_file" class="form-input" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Upload file baru untuk mengganti dokumen yang sudah ada. Kosongkan jika tidak ingin mengubah.</p>
                </div>
            </form>
        `;

            // Add modal footer for edit modal
            document.getElementById('modalFooter').innerHTML = `
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
                <button type="submit" form="form" class="btn btn-primary">Perbarui Transfer</button>
            `;

            // Upgrade date field + make warehouse/product dropdowns searchable
            initTransferModalEnhancements();

            // Reflect current From/To warehouses in the auto Return checkbox without
            // wiping already-saved reason text just because the form was (re)built.
            syncReturnAuto('edit', false);

            // Preload product stock for the already-selected source warehouse so the
            // existing rows show Available Stock without re-picking a warehouse.
            const editFromId = document.getElementById('edit_from_warehouse_id').value;
            if (editFromId) {
                loadEditProductsForWarehouse(editFromId);
            }
        } else {
            document.getElementById('modalBody').innerHTML = `
                <div style="text-align: center; padding: 40px;">
                    <p style="color: #e74c3c;">Gagal memuat data transfer.</p>
                    <button onclick="openEditModal(${id})" class="btn btn-primary">Coba Lagi</button>
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error loading data:', error);
        document.getElementById('modalBody').innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <p style="color: #e74c3c;">Gagal memuat data. Silakan coba lagi.</p>
                <button onclick="openEditModal(${id})" class="btn btn-primary">Coba Lagi</button>
            </div>
        `;
    });
}

function submitForm(event, id = null) {
    event.preventDefault();

    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData.entries());

    // An empty <input type="file"> still produces a (zero-size) File entry in
    // FormData. Object.fromEntries() + JSON.stringify() turns that into "{}" in the
    // request body - present, but neither null nor a valid file - which trips
    // Laravel's 'nullable|file' validation ("field must be a file") even though no
    // file was actually chosen. Strip these keys entirely when empty so the field is
    // truly absent, matching what 'nullable' expects.
    ['delivery_order_file', 'submission_letter_file', 'delivery_note_file'].forEach(name => {
        if (data[name] instanceof File && data[name].size === 0) {
            delete data[name];
        }
    });

    // Use addedItems for create modal, or collect from form for edit modal
    let items = [];

    if (typeof addedItems !== 'undefined' && addedItems.length > 0) {
        // Create modal - use addedItems
        items = addedItems.map(item => ({
            product_id: item.product_id,
            quantity: item.quantity
        }));
    } else {
        // Edit modal - collect from form
        const itemRows = document.querySelectorAll('.transfer-item-row');
        itemRows.forEach((row, index) => {
            const productSelect = row.querySelector('.product-select');
            const quantityInput = row.querySelector('input[name*="[quantity]"]');

            if (productSelect.value && quantityInput.value) {
                items.push({
                    product_id: productSelect.value,
                    quantity: parseInt(quantityInput.value, 10)
                });
            }
        });
    }

    // Add items to data
    data.items = items;

    // Validate items
    if (items.length === 0) {
        showWarningDialog('Tambahkan minimal satu item transfer.');
        return;
    }

    const url = id ? `{{ url('warehouse/inventory-transfers/api') }}/${id}/update` : '{{ route('warehouse.inventory-transfers.api.store') }}';
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // File inputs (submission_letter_file, delivery_note_file, delivery_order_file) can't
    // be sent through the JSON.stringify(data) path below - a File object doesn't survive
    // JSON serialization. When any of these actually has a file selected, submit the raw
    // FormData instead (multipart/form-data, browser-set boundary) so PHP can populate
    // $_FILES. Requests with no files keep the original JSON path untouched.
    const fileFieldNames = ['delivery_order_file', 'submission_letter_file', 'delivery_note_file'];
    const hasFile = fileFieldNames.some(name => {
        const file = formData.get(name);
        return file instanceof File && file.size > 0;
    });

    let fetchOptions;
    if (hasFile) {
        // PHP only populates $_FILES for POST bodies, not PUT - use Laravel's method
        // override (_method field) so an edit still reaches updateTransfer() while the
        // real HTTP method stays POST.
        if (id) {
            formData.set('_method', 'PUT');
        }
        formData.delete('items');
        items.forEach((item, index) => {
            formData.append(`items[${index}][product_id]`, item.product_id);
            formData.append(`items[${index}][quantity]`, item.quantity);
        });

        fetchOptions = {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: formData
        };
    } else {
        fetchOptions = {
            method: id ? 'PUT' : 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(data)
        };
    }

    fetch(url, fetchOptions)
    .then(response => response.json())
    .then(result => {
        if (result.status === 'success') {
            closeModal();
            location.reload();
        } else {
            showErrorDialog('Gagal', result.message || 'Terjadi kesalahan.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorDialog('Gagal', 'Terjadi kesalahan.');
    });
}


// Transfer Items Functions
let transferItemIndex = 0;

// --- Modal UX helpers: searchable dropdowns, datepicker, conditional sections ---

// select2 dropdowns inside this custom (non-Bootstrap) modal need an un-clipped
// positioned parent; the fixed full-screen overlay works and never clips.
function transferModalDropdownParent() {
    return $('#modalOverlay');
}

// Init select2 on a single managed select. These carry data-no-select2 so the
// global auto-init leaves them alone and we fully own their lifecycle (options
// on these get replaced via innerHTML after AJAX, which would break a select2
// the global observer had already wrapped).
function initManagedSelect(selectEl, forceSearch) {
    if (!window.jQuery || typeof $.fn === 'undefined' || typeof $.fn.select2 === 'undefined') return;
    const $el = $(selectEl);
    if ($el.hasClass('select2-hidden-accessible')) {
        $el.select2('destroy');
    }
    $el.select2({
        dropdownParent: transferModalDropdownParent(),
        width: '100%',
        placeholder: (selectEl.options[0] ? selectEl.options[0].text : 'Pilih') || 'Pilih',
        allowClear: !selectEl.required,
        minimumResultsForSearch: forceSearch ? 0 : 10
    });
}

// Replace a managed select's <option>s without leaving a broken select2 behind.
function setManagedSelectOptions(selectEl, optionsHtml, forceSearch) {
    if (!selectEl) return;
    const $el = window.jQuery ? $(selectEl) : null;
    if ($el && $el.hasClass('select2-hidden-accessible')) {
        $el.select2('destroy');
    }
    selectEl.innerHTML = optionsHtml;
    initManagedSelect(selectEl, forceSearch);
}

// Init all managed selects + upgrade the date field in the current modal body.
function initTransferModalEnhancements() {
    const body = document.getElementById('modalBody');
    if (!body) return;
    if (window.initFlatpickr) window.initFlatpickr(body);
    body.querySelectorAll('select[data-managed-select]').forEach(function (sel) {
        initManagedSelect(sel, sel.hasAttribute('data-force-search'));
    });
}

// Show/hide the "Alasan Return" fields based on the (now auto-driven, disabled)
// toggle checkbox. clearFields defaults to true; pass false to leave already-saved
// reason text alone (used for the initial Edit-modal load pass).
function toggleReturnReasonSection(checkbox, clearFields) {
    if (clearFields === undefined) clearFields = true;
    const section = document.getElementById(checkbox.getAttribute('data-target'));
    if (!section) return;
    section.style.display = checkbox.checked ? '' : 'none';
    if (!checkbox.checked && clearFields) {
        const cat = section.querySelector('[name="return_reason_category"]');
        const reason = section.querySelector('[name="return_reason"]');
        if (cat) cat.value = '';
        if (reason) reason.value = '';
    }
}

// Auto-detect "this transfer is a Branch -> Center return" from the currently
// selected From/To warehouses (via their data-is-center attribute) and drive the
// disabled checkbox + return-reason section from it, instead of a manual toggle.
// clearFields=true wipes any reason text when the pair no longer qualifies -
// used on real warehouse-change events; pass false for the initial modal-open
// pass so already-saved reason text isn't wiped just because the section is
// briefly re-evaluated while the form is being built.
function syncReturnAuto(prefix, clearFields) {
    const fromSelect = document.getElementById(prefix === 'create' ? 'from_warehouse_id' : 'edit_from_warehouse_id');
    const toSelect = document.getElementById(prefix === 'create' ? 'to_warehouse_id' : 'edit_to_warehouse_id');
    const checkbox = document.getElementById(prefix + '-is-return');
    if (!fromSelect || !toSelect || !checkbox) return;

    const fromOpt = fromSelect.options[fromSelect.selectedIndex];
    const toOpt = toSelect.options[toSelect.selectedIndex];
    const fromIsCenter = !!(fromOpt && fromOpt.getAttribute('data-is-center') === '1');
    const toIsCenter = !!(toOpt && toOpt.getAttribute('data-is-center') === '1');
    const isReturn = !!(fromSelect.value && toSelect.value && !fromIsCenter && toIsCenter);

    checkbox.checked = isReturn;
    toggleReturnReasonSection(checkbox, clearFields);
}

// Fill an edit item row's "Available Stock" from the selected product option.
// (Referenced by the edit rows' onchange but previously undefined -> JS error.)
function updateAvailableStock(selectElement) {
    const row = selectElement.closest('.transfer-item-row');
    if (!row) return;
    const stockInput = row.querySelector('.available-stock');
    const opt = selectElement.options[selectElement.selectedIndex];
    const stock = opt ? (opt.getAttribute('data-stock') || '0') : '0';
    if (stockInput) stockInput.value = stock;
    const qtyInput = row.querySelector('input[name*="[quantity]"]');
    if (qtyInput) qtyInput.max = stock;
}

function loadTransferWarehouses() {
    const fromWarehouseId = document.getElementById('from_warehouse_id').value;
    const toWarehouseSelect = document.getElementById('to_warehouse_id');

    if (!fromWarehouseId) {
        setManagedSelectOptions(toWarehouseSelect, '<option value="">Pilih Warehouse Asal Terlebih Dahulu</option>', true);
        syncReturnAuto('create', true);
        return;
    }

    // Load transfer warehouses based on business rules
    fetch(`{{ url('warehouse/inventory-transfers/api/transfer-warehouses') }}/${fromWarehouseId}`, {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(result => {
        if (result.status === 'success') {
            const warehouses = result.data || [];
            let options = '<option value="">Pilih Warehouse Tujuan</option>';
            warehouses.forEach(warehouse => {
                options += `<option value="${warehouse.id}" data-is-center="${warehouse.is_center ? 1 : 0}">${warehouse.name}</option>`;
            });
            setManagedSelectOptions(toWarehouseSelect, options, true);
            syncReturnAuto('create', true);

            // Load products for the from warehouse
            loadProductsForWarehouse(fromWarehouseId);
        }
    })
    .catch(error => {
        console.error('Error loading transfer warehouses:', error);
    });
}

function loadProductsForWarehouse(warehouseId) {
    const productSelect = document.getElementById('new-product-select');

    fetch(`{{ url('warehouse/inventory-transfers/api/products') }}/${warehouseId}`, {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(result => {
        if (result.status === 'success') {
            const products = result.data || [];
            let options = '<option value="">Pilih Produk</option>';
            products.forEach(product => {
                options += `<option value="${product.master_product_id}" data-stock="${product.quantity}" data-name="${product.master_product.name}">${product.master_product.name} (${product.quantity})</option>`;
            });

            setManagedSelectOptions(productSelect, options, true);
        }
    })
    .catch(error => {
        console.error('Error loading products:', error);
    });
}

function updateNewItemStock(selectElement) {
    const selectedOption = selectElement.options[selectElement.selectedIndex];
    const stock = selectedOption.getAttribute('data-stock') || '0';
    document.getElementById('new-available-stock').value = stock;
    document.getElementById('new-quantity').max = stock;
}

// Global variable to store added items
let addedItems = [];

function addItemToList() {
    const productSelect = document.getElementById('new-product-select');
    const quantityInput = document.getElementById('new-quantity');
    const availableStock = document.getElementById('new-available-stock');

    // Validation
    if (!productSelect.value) {
        showWarningDialog('Pilih produk terlebih dahulu.');
        return;
    }

    if (!quantityInput.value || parseFloat(quantityInput.value) <= 0) {
        showWarningDialog('Masukkan jumlah yang valid.');
        return;
    }

    if (!Number.isInteger(parseFloat(quantityInput.value))) {
        showWarningDialog('Quantity harus berupa angka bulat (tanpa desimal).');
        return;
    }

    const quantity = parseInt(quantityInput.value, 10);
    const stock = parseFloat(availableStock.value);

    if (quantity > stock) {
        showWarningDialog(`Jumlah tidak boleh melebihi stok tersedia (${stock}).`);
        return;
    }

    // Check if product already added
    const existingItem = addedItems.find(item => item.product_id === productSelect.value);
    if (existingItem) {
        showWarningDialog('Produk ini sudah ditambahkan ke daftar.');
        return;
    }

    // Get product info
    const selectedOption = productSelect.options[productSelect.selectedIndex];
    const productName = selectedOption.getAttribute('data-name');

    // Add to items list
    const newItem = {
        product_id: productSelect.value,
        product_name: productName,
        quantity: quantity,
        available_stock: stock
    };

    addedItems.push(newItem);

    // Update UI
    updateItemsList();

    // Clear form
    if (window.jQuery && $(productSelect).hasClass('select2-hidden-accessible')) {
        $(productSelect).val('').trigger('change');
    } else {
        productSelect.value = '';
    }
    document.getElementById('new-available-stock').value = '';
    document.getElementById('new-quantity').value = '';
}

function updateItemsList() {
    const itemsList = document.getElementById('items-list');
    const itemsCount = document.getElementById('items-count');

    // Update items counter
    if (itemsCount) {
        const count = addedItems.length;
        itemsCount.textContent = `${count} item${count !== 1 ? 's' : ''}`;
    }

    if (addedItems.length === 0) {
        itemsList.innerHTML = '<tr id="no-items-message"><td colspan="4" style="padding: 16px; text-align: center; color: #9ca3af;">Belum ada item yang ditambahkan</td></tr>';
        return;
    }

    let html = '';
    addedItems.forEach((item, index) => {
        html += `
            <tr style="border-top: 1px solid #e5e7eb;">
                <td style="padding: 10px 12px; font-weight: 500; color: #111827;">${item.product_name}</td>
                <td style="padding: 10px 12px; text-align: right; color: #6b7280;">${item.available_stock}</td>
                <td style="padding: 10px 12px; text-align: right; font-weight: 600; color: #214589;">${item.quantity}</td>
                <td style="padding: 10px 12px; text-align: center;">
                    <button type="button" onclick="removeItemFromList(${index})" title="Hapus item" style="background: none; border: none; cursor: pointer; color: #dc2626; padding: 4px;">
                        <i class="fas fa-trash text-sm"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    itemsList.innerHTML = html;
}

function removeItemFromList(index) {
    if (index >= 0 && index < addedItems.length) {
        addedItems.splice(index, 1);
        updateItemsList();
    }
}

function validateStock(inputElement) {
    const rawValue = parseFloat(inputElement.value) || 0;
    const stockInput = inputElement.closest('.transfer-item-row').querySelector('.available-stock');
    const availableStock = parseFloat(stockInput.value) || 0;

    if (!Number.isInteger(rawValue)) {
        showWarningDialog('Quantity harus berupa angka bulat (tanpa desimal).');
        inputElement.value = Math.trunc(rawValue);
        return;
    }

    const quantity = rawValue;

    if (quantity > availableStock) {
        showWarningDialog(`Jumlah tidak boleh melebihi stok tersedia (${availableStock}).`);
        inputElement.value = availableStock;
    }
}

function addTransferItem() {
    transferItemIndex++;
    const container = document.getElementById('transfer-items-container');
    const newRow = document.createElement('div');
    newRow.className = 'transfer-item-row grid grid-cols-1 md:grid-cols-4 gap-4 items-end mb-4';
    newRow.innerHTML = `
        <div class="form-group">
            <label class="form-label">Product *</label>
            <select name="items[${transferItemIndex}][product_id]" class="form-input product-select" required onchange="updateAvailableStock(this)">
                <option value="">Pilih Produk</option>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Available Stock</label>
            <input type="text" class="form-input available-stock" readonly placeholder="0">
        </div>
        <div class="form-group">
            <label class="form-label">Quantity *</label>
            <input type="number" name="items[${transferItemIndex}][quantity]" class="form-input" step="1" min="1" required onchange="validateStock(this)">
        </div>
        <div class="form-group">
            <button type="button" class="btn btn-danger btn-sm" onclick="removeTransferItem(this)">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;

    container.appendChild(newRow);

    // Load products for the new select
    const fromWarehouseId = document.getElementById('from_warehouse_id').value;
    if (fromWarehouseId) {
        loadProductsForWarehouse(fromWarehouseId);
    }

    // Show delete buttons for all rows
    document.querySelectorAll('.transfer-item-row .btn-danger').forEach(btn => {
        btn.style.display = 'block';
    });
}

function removeTransferItem(buttonElement) {
    const row = buttonElement.closest('.transfer-item-row');
    row.remove();

    // Hide delete button if only one row left
    const remainingRows = document.querySelectorAll('.transfer-item-row');
    if (remainingRows.length === 1) {
        remainingRows[0].querySelector('.btn-danger').style.display = 'none';
    }
}

// Edit Modal Functions
let editTransferItemIndex = 0;

function buildEditTransferItemsHtml(transferItems) {
    if (!transferItems || transferItems.length === 0) {
        return `
            <tr class="transfer-item-row">
                <td style="padding: 8px 12px;">
                    <select name="items[0][product_id]" class="form-input product-select" required data-no-select2 data-managed-select data-force-search onchange="updateAvailableStock(this)" style="margin: 0;">
                        <option value="">Pilih Produk</option>
                    </select>
                </td>
                <td style="padding: 8px 12px;">
                    <input type="text" class="form-input available-stock" readonly placeholder="0" style="margin: 0; text-align: right; background: #f3f4f6;">
                </td>
                <td style="padding: 8px 12px;">
                    <input type="number" name="items[0][quantity]" class="form-input" step="1" min="1" required onchange="validateStock(this)" style="margin: 0; text-align: right;">
                </td>
                <td style="padding: 8px 12px; text-align: center;">
                    <button type="button" class="remove-item-btn" onclick="removeEditTransferItem(this)" style="display: none; background: none; border: none; cursor: pointer; color: #dc2626; padding: 4px;">
                        <i class="fas fa-trash text-sm"></i>
                    </button>
                </td>
            </tr>
        `;
    }

    let html = '';
    transferItems.forEach((item, index) => {
        editTransferItemIndex = Math.max(editTransferItemIndex, index);
        html += `
            <tr class="transfer-item-row">
                <td style="padding: 8px 12px;">
                    <select name="items[${index}][product_id]" class="form-input product-select" required data-no-select2 data-managed-select data-force-search onchange="updateAvailableStock(this)" style="margin: 0;">
                        <option value="">Pilih Produk</option>
                        <option value="${item.product_id}" selected>${item.product?.name || 'Unknown Product'}</option>
                    </select>
                </td>
                <td style="padding: 8px 12px;">
                    <input type="text" class="form-input available-stock" readonly placeholder="0" style="margin: 0; text-align: right; background: #f3f4f6;">
                </td>
                <td style="padding: 8px 12px;">
                    <input type="number" name="items[${index}][quantity]" class="form-input" step="1" min="1" value="${item.quantity || 0}" required onchange="validateStock(this)" style="margin: 0; text-align: right;">
                </td>
                <td style="padding: 8px 12px; text-align: center;">
                    <button type="button" class="remove-item-btn" onclick="removeEditTransferItem(this)" style="${transferItems.length === 1 ? 'display: none; ' : ''}background: none; border: none; cursor: pointer; color: #dc2626; padding: 4px;">
                        <i class="fas fa-trash text-sm"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    return html;
}

function loadEditTransferWarehouses() {
    const fromWarehouseId = document.getElementById('edit_from_warehouse_id').value;
    const toWarehouseSelect = document.getElementById('edit_to_warehouse_id');

    if (!fromWarehouseId) {
        setManagedSelectOptions(toWarehouseSelect, '<option value="">Pilih Warehouse Asal Terlebih Dahulu</option>', true);
        syncReturnAuto('edit', true);
        return;
    }

    // Load transfer warehouses based on business rules
    fetch(`{{ url('warehouse/inventory-transfers/api/transfer-warehouses') }}/${fromWarehouseId}`, {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(result => {
        if (result.status === 'success') {
            const warehouses = result.data || [];
            let options = '<option value="">Pilih Warehouse Tujuan</option>';
            warehouses.forEach(warehouse => {
                const selected = document.getElementById('edit_to_warehouse_id').dataset.selectedValue == warehouse.id ? 'selected' : '';
                options += `<option value="${warehouse.id}" data-is-center="${warehouse.is_center ? 1 : 0}" ${selected}>${warehouse.name}</option>`;
            });
            setManagedSelectOptions(toWarehouseSelect, options, true);
            syncReturnAuto('edit', true);

            // Load products for the from warehouse
            loadEditProductsForWarehouse(fromWarehouseId);
        }
    })
    .catch(error => {
        console.error('Error loading transfer warehouses:', error);
    });
}

function loadEditProductsForWarehouse(warehouseId) {
    const productSelects = document.querySelectorAll('#edit-transfer-items-container .product-select');

    fetch(`{{ url('warehouse/inventory-transfers/api/products') }}/${warehouseId}`, {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(result => {
        if (result.status === 'success') {
            const products = result.data || [];
            let options = '<option value="">Pilih Produk</option>';
            products.forEach(product => {
                options += `<option value="${product.master_product_id}" data-stock="${product.quantity}">${product.master_product.name} (${product.quantity})</option>`;
            });

            productSelects.forEach(select => {
                const currentValue = select.value;
                // Keep the currently-selected product available even if it's not in
                // the fresh stock list (e.g. its warehouse stock is now 0).
                let optionsForSelect = options;
                const selectedOpt = select.options[select.selectedIndex];
                if (currentValue && optionsForSelect.indexOf(`value="${currentValue}"`) === -1 && selectedOpt) {
                    optionsForSelect += `<option value="${currentValue}" data-stock="0">${selectedOpt.textContent}</option>`;
                }
                setManagedSelectOptions(select, optionsForSelect, true);
                if (currentValue) {
                    select.value = currentValue;
                    if (window.jQuery && $(select).hasClass('select2-hidden-accessible')) {
                        $(select).val(currentValue).trigger('change.select2');
                    }
                    updateAvailableStock(select);

                    // Draft transfers never deduct stock, so this row's already-saved
                    // quantity was never actually reserved in the warehouse - if other
                    // stock movements happened since this draft was created, the fresh
                    // reading above can come back lower than (even 0 vs) what this row
                    // already committed to. Don't let that make the existing quantity
                    // look "over stock" and block editing/saving.
                    const row = select.closest('.transfer-item-row');
                    const qtyInput = row ? row.querySelector('input[name*="[quantity]"]') : null;
                    const stockInput = row ? row.querySelector('.available-stock') : null;
                    if (qtyInput && stockInput) {
                        const committedQty = parseFloat(qtyInput.value) || 0;
                        const shownStock = parseFloat(stockInput.value) || 0;
                        if (committedQty > shownStock) {
                            stockInput.value = committedQty;
                        }
                    }
                }
            });
        }
    })
    .catch(error => {
        console.error('Error loading products:', error);
    });
}

function addEditTransferItem() {
    editTransferItemIndex++;
    const container = document.getElementById('edit-transfer-items-container');
    const newRow = document.createElement('tr');
    newRow.className = 'transfer-item-row';
    newRow.innerHTML = `
        <td style="padding: 8px 12px;">
            <select name="items[${editTransferItemIndex}][product_id]" class="form-input product-select" required data-no-select2 data-managed-select data-force-search onchange="updateAvailableStock(this)" style="margin: 0;">
                <option value="">Pilih Produk</option>
            </select>
        </td>
        <td style="padding: 8px 12px;">
            <input type="text" class="form-input available-stock" readonly placeholder="0" style="margin: 0; text-align: right; background: #f3f4f6;">
        </td>
        <td style="padding: 8px 12px;">
            <input type="number" name="items[${editTransferItemIndex}][quantity]" class="form-input" step="1" min="1" required onchange="validateStock(this)" style="margin: 0; text-align: right;">
        </td>
        <td style="padding: 8px 12px; text-align: center;">
            <button type="button" class="remove-item-btn" onclick="removeEditTransferItem(this)" style="background: none; border: none; cursor: pointer; color: #dc2626; padding: 4px;">
                <i class="fas fa-trash text-sm"></i>
            </button>
        </td>
    `;

    container.appendChild(newRow);

    // Make the new row's product dropdown searchable straight away
    initManagedSelect(newRow.querySelector('.product-select'), true);

    // Load products for the new select
    const fromWarehouseId = document.getElementById('edit_from_warehouse_id').value;
    if (fromWarehouseId) {
        loadEditProductsForWarehouse(fromWarehouseId);
    }

    // Show delete buttons for all rows
    document.querySelectorAll('#edit-transfer-items-container .transfer-item-row .remove-item-btn').forEach(btn => {
        btn.style.display = '';
    });
}

function removeEditTransferItem(buttonElement) {
    const row = buttonElement.closest('.transfer-item-row');
    row.remove();

    // Hide delete button if only one row left
    const remainingRows = document.querySelectorAll('#edit-transfer-items-container .transfer-item-row');
    if (remainingRows.length === 1) {
        remainingRows[0].querySelector('.remove-item-btn').style.display = 'none';
    }
}

// Status is no longer editable from the Add/Edit form - it's draft-by-default on
// create, and only moves forward one step at a time (draft -> transferred ->
// received) via this action, normally triggered from the detail page. Reuses the
// existing update endpoint (which only moves stock on the draft -> non-draft
// transition) so the stock-moving logic stays in one place.
function transitionTransferStatus(id, toStatus) {
    const labels = { transferred: 'Transferred', received: 'Received' };
    const confirmText = toStatus === 'transferred'
        ? 'Stok akan berpindah dari gudang asal ke gudang tujuan setelah ini.'
        : 'Transfer akan ditandai sebagai selesai diterima.';

    showConfirmDialog(
        `Tandai sebagai ${labels[toStatus] || toStatus}?`,
        confirmText,
        'Ya, lanjutkan',
        'Batal'
    ).then(confirmed => {
        if (!confirmed) return;

        fetch(`{{ url('warehouse/inventory-transfers/api/get-transfer') }}/${id}`, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            credentials: 'same-origin'
        })
        .then(r => r.json())
        .then(getResult => {
            if (getResult.status !== 'success') {
                showErrorDialog('Gagal memuat data transfer terbaru.', 'Gagal');
                return;
            }
            const data = getResult.data;
            const payload = {
                transfer_date: data.transfer_date ? data.transfer_date.split('T')[0] : data.transfer_date,
                from_warehouse_id: data.from_warehouse_id,
                to_warehouse_id: data.to_warehouse_id,
                status: toStatus,
                is_direct_branch_transfer: data.is_direct_branch_transfer ? 1 : 0,
                central_approval_notes: data.central_approval_notes,
                notes: data.notes,
                return_reason: data.return_reason,
                return_reason_category: data.return_reason_category
            };

            fetch(`{{ url('warehouse/inventory-transfers/api') }}/${id}/update`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(result => {
                if (result.status === 'success') {
                    location.reload();
                } else {
                    showErrorDialog(result.message || 'Gagal memperbarui status.', 'Gagal');
                }
            })
            .catch(() => showErrorDialog('Terjadi kesalahan jaringan.', 'Gagal'));
        })
        .catch(() => showErrorDialog('Terjadi kesalahan jaringan.', 'Gagal'));
    });
}
</script>
