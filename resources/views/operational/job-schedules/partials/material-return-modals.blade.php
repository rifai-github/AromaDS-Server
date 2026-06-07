{{-- STUDY CASE B1: Material Return Modals --}}
<!-- Create Material Return Modal -->
<div class="modal fade" id="createMaterialReturnModal" tabindex="-1" aria-labelledby="createMaterialReturnModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="createMaterialReturnModalLabel">
                    <i class="fas fa-undo me-2"></i>Create Material Return
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createMaterialReturnForm">
                <div class="modal-body">
                    <input type="hidden" id="material_return_room_id" name="room_id">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Room Name</label>
                            <input type="text" class="form-control" id="material_return_room_name" readonly style="background-color: #f8f9fa;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Return Date</label>
                            <input type="date" class="form-control" id="material_return_date" name="return_date" readonly style="background-color: #f8f9fa;">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Gudang Cabang</label>
                        <select class="form-select d-none" id="material_return_warehouse_id" name="warehouse_id">
                            <option value="">Select Warehouse</option>
                            @foreach($warehouses ?? [] as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-control bg-light" id="material_return_warehouse_label">Otomatis mengikuti warehouse aktif cabang</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Kategori Alasan Retur <span class="text-danger">*</span></label>
                        <select class="form-select" id="material_return_reason_category" name="return_reason_category" required>
                            <option value="slow_moving">Slow moving</option>
                            <option value="near_expired">Mendekati expired</option>
                            <option value="customer_need_changed">Perubahan kebutuhan customer</option>
                            <option value="damaged">Rusak</option>
                            <option value="other">Lainnya</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Return Reason</label>
                        <textarea class="form-control" id="material_return_reason" name="return_reason" rows="3" placeholder="Auto return semua material issue untuk room..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Notes</label>
                        <textarea class="form-control" id="material_return_notes" name="notes" rows="2" placeholder="Additional notes (optional)..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Return Items <span class="text-danger">*</span></label>
                        <div class="border rounded p-3" style="background-color: #f8f9fa;">
                            <button type="button" class="btn btn-sm btn-outline-primary mb-3 d-none" id="add_return_item_btn">
                                <i class="fas fa-plus me-1"></i> Add Item
                            </button>
                            <div id="material_return_items_list"></div>
                            <div class="text-muted small mt-2">
                                <i class="fas fa-info-circle me-1"></i>Semua material awal untuk room ini akan direturn otomatis.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Create Material Return
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Material Return Modal -->
<div class="modal fade" id="viewMaterialReturnModal" tabindex="-1" aria-labelledby="viewMaterialReturnModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="viewMaterialReturnModalLabel">
                    <i class="fas fa-info-circle me-2"></i>Material Return Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="viewMaterialReturnContent">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading material return details...</p>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Close
                </button>
                <button type="button" class="btn btn-info d-none" id="approveMaterialReturnBtn">
                    <i class="fas fa-check me-1"></i>Approve
                </button>
                <button type="button" class="btn btn-success d-none" id="completeMaterialReturnBtn">
                    <i class="fas fa-check-circle me-1"></i>Complete Return
                </button>
            </div>
        </div>
    </div>
</div>

