{{-- STUDY CASE B1: Material Return JavaScript --}}
<script>
    $(document).ready(function() {
        let materialReturnItemIndex = 0;
        let materialIssueItems = [];

        // Load material issue items for the room when button clicked
        $(document).on('click', '.create-material-return', function() {
            const roomId = $(this).data('room-id');
            const roomName = $(this).data('room-name');
            
            $('#material_return_room_id').val(roomId);
            $('#material_return_room_name').val(roomName);
            $('#material_return_date').val(new Date().toISOString().split('T')[0]);
            $('#material_return_reason').val('');
            $('#material_return_notes').val('');
            $('#material_return_items_list').empty();
            materialReturnItemIndex = 0;
            
            // Open modal popup
            const createModal = new bootstrap.Modal(document.getElementById('createMaterialReturnModal'));
            createModal.show();
            
            // Load material issue items for this room
            loadMaterialIssueItemsForRoom(roomId);
        });

        // Close material return form
        window.closeMaterialReturnForm = function() {
            const createModalEl = document.getElementById('createMaterialReturnModal');
            const createModal = bootstrap.Modal.getInstance(createModalEl);
            if (createModal) {
                createModal.hide();
            }
            $('#createMaterialReturnForm')[0].reset();
            $('#material_return_items_list').empty();
            materialReturnItemIndex = 0;
        };

        // Add return item row
        $('#add_return_item_btn').on('click', function() {
            addMaterialReturnItemRow();
        });

        function addMaterialReturnItemRow(item = null) {
            const index = materialReturnItemIndex++;
            
            // Build product options
            let productOptions = '<option value="">Select Product</option>';
            if (materialIssueItems.length > 0) {
                materialIssueItems.forEach(function(miItem) {
                    const isSelected = item && item.product_id == miItem.product_id;
                    productOptions += `<option value="${miItem.product_id}" data-material-issue-item-id="${miItem.id}" ${isSelected ? 'selected' : ''}>${miItem.product_name}</option>`;
                });
            }
            
            const row = `
                <div class="card mb-2 return-item-row" data-index="${index}">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-5">
                                <label class="form-label">Product <span class="text-danger">*</span></label>
                                <select class="form-select return-item-product" name="items[${index}][product_id]" required>
                                    ${productOptions}
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Quantity <span class="text-danger">*</span></label>
                                <input type="number" class="form-control return-item-quantity" name="items[${index}][quantity]" step="0.01" min="0.01" value="${item ? item.quantity : ''}" readonly>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Convert</label>
                                <input type="number" class="form-control return-item-convert" name="items[${index}][convert]" step="0.01" min="0" value="${item ? item.convert : 1}" readonly>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">BOM Qty</label>
                                <input type="number" class="form-control return-item-bom" name="items[${index}][bom_quantity]" step="0.01" min="0" value="${item ? item.bom_quantity : 0}" readonly>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">&nbsp;</label>
                                <button type="button" class="btn btn-sm btn-danger w-100 remove-return-item d-none">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-12">
                                <label class="form-label">Return Reason</label>
                                <textarea class="form-control" name="items[${index}][return_reason]" rows="1" readonly>Auto return semua material issue untuk room</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            $('#material_return_items_list').append(row);
            
            // Initialize Select2 for product dropdown
            const $productSelect = $('#material_return_items_list').find('.return-item-product').last();
            $productSelect.prop('disabled', true);
            if ($productSelect.length && typeof $.fn.select2 !== 'undefined') {
                $productSelect.select2({
                    placeholder: 'Select Product',
                    allowClear: true,
                    dropdownParent: $('#createMaterialReturnModal')
                });
            }
        }

        // Remove return item row
        $(document).on('click', '.remove-return-item', function() {
            $(this).closest('.return-item-row').remove();
        });

        // Load material issue items for room
        function loadMaterialIssueItemsForRoom(roomId) {
            const jobScheduleId = {{ $jobSchedule->id }};
            
            // Fetch material issue items for this room
            $.ajax({
                url: `/operational/job-schedules/${jobScheduleId}/rooms/${roomId}/material-issue-items`,
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                },
                success: function(response) {
                    materialIssueItems = [];
                    
                    if (response.status === 'success' && response.data && response.data.length > 0) {
                        response.data.forEach(function(item) {
                            materialIssueItems.push({
                                id: item.id,
                                product_id: item.product_id,
                                product_name: item.product_name,
                                quantity: item.quantity,
                                convert: item.convert || 1,
                                bom_quantity: item.bom_quantity || 0,
                                selected: false
                            });
                        });
                        
                        // Auto-set warehouse if available from first item
                        const firstItem = response.data[0];
                        if (firstItem.warehouse_id) {
                            $('#material_return_warehouse_id').val(firstItem.warehouse_id).trigger('change');
                            const warehouseName = $('#material_return_warehouse_id option:selected').text();
                            $('#material_return_warehouse_label').text(warehouseName || 'Otomatis mengikuti warehouse aktif cabang');
                        }

                        // Add row for each item
                        materialIssueItems.forEach(function(item) {
                            addMaterialReturnItemRow(item);
                        });
                    } else {
                        // If no items found, add empty row
                        addMaterialReturnItemRow();
                    }
                },
                error: function() {
                    console.error('Failed to load material issue items');
                    // Add empty row as fallback
                    addMaterialReturnItemRow();
                }
            });
        }

        // Submit create material return form
        $('#createMaterialReturnForm').on('submit', function(e) {
            e.preventDefault();
            
            const formData = {
                return_date: $('#material_return_date').val(),
                warehouse_id: $('#material_return_warehouse_id').val(),
                return_reason_category: $('#material_return_reason_category').val(),
                return_reason: $('#material_return_reason').val(),
                notes: $('#material_return_notes').val(),
                items: []
            };
            
            $('.return-item-row').each(function() {
                const productSelect = $(this).find('.return-item-product');
                const materialIssueItemId = productSelect.find('option:selected').data('material-issue-item-id');
                
                formData.items.push({
                    product_id: productSelect.val(),
                    quantity: $(this).find('.return-item-quantity').val(),
                    convert: $(this).find('.return-item-convert').val() || 1,
                    bom_quantity: $(this).find('.return-item-bom').val() || 0,
                    material_issue_item_id: materialIssueItemId || null,
                    return_reason: $(this).find('textarea[name*="[return_reason]"]').val(),
                    notes: null
                });
            });
            
            const roomId = $('#material_return_room_id').val();
            const csrfToken = $('meta[name="csrf-token"]').attr('content');
            
            $.ajax({
                url: `/operational/job-schedules/{{ $jobSchedule->id }}/rooms/${roomId}/material-return`,
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                data: formData,
                success: function(response) {
                    if (response.status === 'success') {
                        toast('Success', 'Material return created successfully.', 'success');
                        closeMaterialReturnForm();
                        location.reload();
                    }
                },
                error: function(xhr) {
                    const errorMsg = xhr.responseJSON?.message || 'Failed to create material return.';
                    toast('Error', errorMsg, 'error');
                }
            });
        });

        // View material return
        $(document).on('click', '.view-material-return', function() {
            const returnId = $(this).data('return-id');
            
            $.ajax({
                url: `/operational/job-schedules/{{ $jobSchedule->id }}/material-returns`,
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                },
                success: function(response) {
                    if (response.status === 'success') {
                        const materialReturn = response.data.find(r => r.id == returnId);
                        if (materialReturn) {
                            displayMaterialReturn(materialReturn);
                            // Use Bootstrap 5 modal API for view modal (still use popup for viewing)
                            const modal = new bootstrap.Modal(document.getElementById('viewMaterialReturnModal'));
                            modal.show();
                        }
                    }
                },
                error: function() {
                    toast('Error', 'Failed to load material return details.', 'error');
                }
            });
        });

        // Return cabang -> pusat: label the transfer progress toward the central warehouse.
        function forwardTransferBadge(status) {
            const map = {
                draft: { cls: 'warning', text: 'Menunggu Dikirim' },
                transferred: { cls: 'info', text: 'Dalam Perjalanan' },
                received: { cls: 'success', text: 'Diterima Pusat' }
            };
            const m = map[status] || { cls: 'secondary', text: (status || '-').toUpperCase() };
            return `<span class="badge badge-${m.cls}">${m.text}</span>`;
        }

        function renderDispositionSection(materialReturn) {
            const isPending = materialReturn.status === 'pending';

            // Pending + can approve: let the branch admin choose where the returned stock goes.
            if (isPending && window.canApproveMaterialReturn) {
                return `
                    <div class="mb-3">
                        <strong>Tujuan Barang Return:</strong>
                        <div class="form-check mt-1">
                            <input class="form-check-input" type="radio" name="return_disposition" id="disp_keep" value="keep_branch" checked>
                            <label class="form-check-label" for="disp_keep">Simpan di gudang cabang</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="return_disposition" id="disp_forward" value="forward_to_center">
                            <label class="form-check-label" for="disp_forward">Teruskan ke gudang pusat (buat transfer otomatis saat Complete)</label>
                        </div>
                    </div>
                `;
            }

            // Already decided: show the disposition and any linked center transfer.
            const isForward = materialReturn.disposition === 'forward_to_center';
            let html = `
                <div class="mb-3">
                    <strong>Tujuan Barang Return:</strong>
                    ${isForward ? '<span class="badge badge-info">Diteruskan ke Pusat</span>' : '<span class="badge badge-secondary">Simpan di Cabang</span>'}
                </div>
            `;

            if (materialReturn.inventory_transfer) {
                const t = materialReturn.inventory_transfer;
                html += `
                    <div class="mb-3">
                        <strong>Transfer ke Pusat:</strong>
                        ${t.transfer_number} &rarr; ${t.to_warehouse?.name || 'Gudang Pusat'}
                        ${forwardTransferBadge(t.status)}
                    </div>
                `;
            }

            return html;
        }

        function displayMaterialReturn(materialReturn) {
            let html = `
                <div class="mb-3">
                    <strong>Return Number:</strong> ${materialReturn.return_number}
                </div>
                <div class="mb-3">
                    <strong>Status:</strong> 
                    <span class="badge badge-${materialReturn.status === 'returned' ? 'success' : (materialReturn.status === 'approved' ? 'info' : 'warning')}">
                        ${materialReturn.status.toUpperCase()}
                    </span>
                </div>
                <div class="mb-3">
                    <strong>Return Date:</strong> ${materialReturn.return_date || '-'}
                </div>
                <div class="mb-3">
                    <strong>Warehouse:</strong> ${materialReturn.warehouse?.name || '-'}
                </div>
                <div class="mb-3">
                    <strong>Return Reason:</strong> ${materialReturn.return_reason || '-'}
                </div>
                <div class="mb-3">
                    <strong>Notes:</strong> ${materialReturn.notes || '-'}
                </div>
                ${renderDispositionSection(materialReturn)}
                <hr>
                <h6>Return Items:</h6>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Convert</th>
                            <th>BOM Qty</th>
                            <th>Return Reason</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            if (materialReturn.items && materialReturn.items.length > 0) {
                materialReturn.items.forEach(item => {
                    html += `
                        <tr>
                            <td>${item.product?.name || '-'}</td>
                            <td>${item.quantity || 0}</td>
                            <td>${item.convert || 1}</td>
                            <td>${item.bom_quantity || 0}</td>
                            <td>${item.return_reason || '-'}</td>
                        </tr>
                    `;
                });
            } else {
                html += '<tr><td colspan="5" class="text-center">No items</td></tr>';
            }
            
            html += `
                    </tbody>
                </table>
            `;
            
            $('#viewMaterialReturnContent').html(html);
            
            // Show/hide action buttons based on status
            if (materialReturn.status === 'pending') {
                if (window.canApproveMaterialReturn) {
                    $('#approveMaterialReturnBtn').removeClass('d-none').data('return-id', materialReturn.id);
                } else {
                    $('#approveMaterialReturnBtn').addClass('d-none');
                }
                $('#completeMaterialReturnBtn').addClass('d-none');
            } else if (materialReturn.status === 'approved') {
                $('#approveMaterialReturnBtn').addClass('d-none');
                $('#completeMaterialReturnBtn').removeClass('d-none').data('return-id', materialReturn.id);
            } else {
                $('#approveMaterialReturnBtn').addClass('d-none');
                $('#completeMaterialReturnBtn').addClass('d-none');
            }
        }

        // Approve material return
        $('#approveMaterialReturnBtn').on('click', function() {
            const returnId = $(this).data('return-id');
            const csrfToken = $('meta[name="csrf-token"]').attr('content');
            
            const disposition = $('input[name="return_disposition"]:checked').val() || 'keep_branch';
            const confirmMsg = disposition === 'forward_to_center'
                ? 'Approve return ini dan tandai untuk diteruskan ke gudang pusat?'
                : 'Are you sure you want to approve this material return?';

            if (!confirm(confirmMsg)) {
                return;
            }

            $.ajax({
                url: `/operational/job-schedules/{{ $jobSchedule->id }}/material-returns/${returnId}/approve`,
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                data: { disposition: disposition },
                success: function(response) {
                    if (response.status === 'success') {
                        toast('Success', response.message || 'Material return approved successfully.', 'success');
                        const modal = bootstrap.Modal.getInstance(document.getElementById('viewMaterialReturnModal'));
                        if (modal) modal.hide();
                        location.reload();
                    }
                },
                error: function(xhr) {
                    const errorMsg = xhr.responseJSON?.message || 'Failed to approve material return.';
                    toast('Error', errorMsg, 'error');
                }
            });
        });

        // Complete material return
        $('#completeMaterialReturnBtn').on('click', function() {
            const returnId = $(this).data('return-id');
            const csrfToken = $('meta[name="csrf-token"]').attr('content');
            
            if (!confirm('Are you sure you want to complete this material return? Warehouse stock will be updated.')) {
                return;
            }
            
            $.ajax({
                url: `/operational/job-schedules/{{ $jobSchedule->id }}/material-returns/${returnId}/complete`,
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                success: function(response) {
                    if (response.status === 'success') {
                        toast('Success', response.message || 'Material return completed successfully and warehouse stock updated.', 'success');
                        const modal = bootstrap.Modal.getInstance(document.getElementById('viewMaterialReturnModal'));
                        if (modal) modal.hide();
                        location.reload();
                    }
                },
                error: function(xhr) {
                    const errorMsg = xhr.responseJSON?.message || 'Failed to complete material return.';
                    toast('Error', errorMsg, 'error');
                }
            });
        });
    });
</script>

