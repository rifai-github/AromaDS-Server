@extends('layouts.app')

@section('title', 'Stock Adjustment Detail')

@section('content')

<style>
    .container-fluid {
        padding: 0 !important;
        margin: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
    }
    
    .row {
        margin: 0 !important;
        display: flex !important;
        flex-wrap: wrap !important;
        width: 100% !important;
    }
    
    .col-12 {
        padding: 15px !important;
        width: 100% !important;
    }
    
    .card {
        width: 100% !important;
        margin-bottom: 1rem !important;
        border-radius: 8px !important;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
        border: 1px solid rgba(0, 0, 0, 0.125) !important;
    }
    
    .card-header {
        padding: 1rem 1.5rem !important;
        border-bottom: 1px solid rgba(0, 0, 0, 0.125) !important;
    }
    
    .card-body {
        padding: 1.5rem !important;
    }

    .table th {
        background-color: #f8f9fa !important;
        border-top: none !important;
        font-weight: 600 !important;
        color: #495057 !important;
        padding: 12px !important;
    }
    
    .table td {
        padding: 12px !important;
        vertical-align: middle !important;
    }

    .adjustment-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        text-transform: uppercase;
    }
    .adjustment-increase { background-color: #d1fae5; color: #065f46; }
    .adjustment-decrease { background-color: #fee2e2; color: #991b1b; }
    
    .d-none { display: none !important; }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header Section -->
            <div class="card mb-3" style="background-color: #1e3a8a; color: white; border: none; border-radius: 10px;">
                <div class="card-header" style="background-color: #1e3a8a; border: none; padding: 1rem 1.5rem;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <a href="{{ route('warehouse.stock-adjustments.index') }}" class="btn btn-light btn-sm">
                                <i class="fas fa-arrow-left"></i> Back to List
                            </a>
                        </div>
                        <div>
                            <h3 class="card-title mb-0" style="color: white; font-size: 1.5rem; font-weight: bold;">
                                {{ $adjustment->adjustment_no }} - 
                                <span style="font-size: 0.9rem; font-weight: normal;">
                                    {{ strtoupper($adjustment->status) }}
                                </span>
                            </h3>
                        </div>
                        <div>
                            @if($adjustment->status === 'draft')
                            <button type="button" class="btn btn-primary btn-sm" onclick="submitForApproval()">
                                <i class="fas fa-paper-plane"></i> Submit for Approval
                            </button>
                            <!-- Edit Header Button could go here -->
                            @endif
                            
                            @if($adjustment->status === 'waiting for approval' && auth()->user()->hasPermission('warehouse.stock-adjustments.approve'))
                            <div style="display: inline-flex; gap: 10px;">
                                <button type="button" class="btn btn-success btn-sm" onclick="approveAdjustment()">
                                    <i class="fas fa-check-circle"></i> Approve
                                </button>
                                <button type="button" class="btn btn-danger btn-sm" onclick="openRejectModal()">
                                    <i class="fas fa-times-circle"></i> Reject
                                </button>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Basic Info Section -->
            <div class="card mb-3">
                <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #1e3a8a;">
                    <h5 class="card-title mb-0" style="color: #1e3a8a;">
                        <i class="fas fa-info-circle me-2"></i>Header Info
                    </h5>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 2rem;">
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Adjustment No</div>
                            <div style="font-size: 1rem; font-weight: 600; color: #212529;">{{ $adjustment->adjustment_no }}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Status</div>
                            <div>
                                <span class="badge" style="background-color: {{ $adjustment->status === 'approved' ? '#059669' : ($adjustment->status === 'waiting for approval' ? '#d97706' : ($adjustment->status === 'rejected' ? '#ef4444' : '#2563eb')) }}; color: white; padding: 0.35rem 0.75rem; font-size: 0.8rem;">
                                    {{ strtoupper($adjustment->status) }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Adjustment Date</div>
                            <div style="font-size: 1rem; color: #212529;">
                                {{ $adjustment->adjustment_date ? \Carbon\Carbon::parse($adjustment->adjustment_date)->format('d M Y') : '-' }}
                            </div>
                        </div>
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Warehouse</div>
                            <div style="font-size: 1rem; color: #212529;">{{ $adjustment->warehouse->name ?? '-' }}</div>
                        </div>
                        
                        <div style="grid-column: span 2;">
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Reason</div>
                            <div style="font-size: 1rem; color: #212529;">{{ $adjustment->reason ?? '-' }}</div>
                        </div>
                        <div style="grid-column: span 2;">
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Notes</div>
                            <div style="font-size: 1rem; color: #212529;">{{ $adjustment->notes ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Bar -->
            <div class="card mb-3" style="border: none; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                <div class="card-body" style="padding: 1.25rem 2rem;">
                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 2rem;">
                        <div style="display: flex; align-items: center; gap: 3rem;">
                            <!-- Total Items -->
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <div style="width: 48px; height: 48px; background: #1e3a8a; border-radius: 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(30, 58, 138, 0.2);">
                                    <i class="fas fa-boxes" style="color: white; font-size: 1.2rem;"></i>
                                </div>
                                <div>
                                    <div style="font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 2px;">Total Items</div>
                                    <div style="font-size: 1.1rem; font-weight: 600; color: #1e293b;">
                                        {{ $adjustment->items->count() }}
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Total Increase -->
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <div style="width: 48px; height: 48px; background: #059669; border-radius: 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(5, 150, 105, 0.2);">
                                    <i class="fas fa-arrow-up" style="color: white; font-size: 1.2rem;"></i>
                                </div>
                                <div>
                                    <div style="font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 2px;">Total Increase</div>
                                    <div style="font-size: 1.1rem; font-weight: 600; color: #059669;">
                                        +{{ $adjustment->items->where('adjustment_type', 'increase')->sum('adjustment_qty') }}
                                    </div>
                                </div>
                            </div>

                            <!-- Total Decrease -->
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <div style="width: 48px; height: 48px; background: #dc2626; border-radius: 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(220, 38, 38, 0.2);">
                                    <i class="fas fa-arrow-down" style="color: white; font-size: 1.2rem;"></i>
                                </div>
                                <div>
                                    <div style="font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 2px;">Total Decrease</div>
                                    <div style="font-size: 1.1rem; font-weight: 600; color: #dc2626;">
                                        -{{ $adjustment->items->where('adjustment_type', 'decrease')->sum('adjustment_qty') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Item List Section -->
            <div class="card mb-3">
                <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #1e3a8a;">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0" style="color: #1e3a8a;">
                            <i class="fas fa-list-alt me-2"></i>Item List
                        </h5>
                        @if($adjustment->status === 'draft')
                        <button class="btn btn-primary btn-sm" onclick="openAddItemModal()">
                            <i class="fas fa-plus"></i> Add Item
                        </button>
                        @endif
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Product Name</th>
                                    <th>SKU</th>
                                    <th class="text-center">Type</th>
                                    <th class="text-end">Quantity</th>
                                    <th>Notes</th>
                                    @if($adjustment->status === 'draft')
                                    <th class="text-center" style="width: 50px;">Action</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($adjustment->items as $item)
                                <tr>
                                    <td>{{ $item->masterProduct->name }}</td>
                                    <td>{{ $item->masterProduct->sku }}</td>
                                    <td class="text-center">
                                        <span class="adjustment-badge {{ $item->adjustment_type === 'increase' ? 'adjustment-increase' : 'adjustment-decrease' }}">
                                            {{ strtoupper($item->adjustment_type) }}
                                        </span>
                                    </td>
                                    <td class="text-end font-weight-bold">{{ number_format($item->adjustment_qty, 0) }}</td>
                                    <td>{{ $item->notes ?: '-' }}</td>
                                    @if($adjustment->status === 'draft')
                                    <td class="text-center">
                                        <button class="btn btn-danger btn-sm" onclick="deleteItem({{ $item->id }})" title="Remove Item">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                    @endif
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted p-4">
                                        <i class="fas fa-info-circle me-2"></i>No items added to this adjustment yet.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Audit Info -->
            <div class="card mb-3">
                <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #1e3a8a; padding: 0.75rem 1.5rem;">
                    <h5 class="card-title mb-0" style="color: #1e3a8a; font-size: 0.95rem;">
                        <i class="fas fa-history me-2"></i>Audit Information
                    </h5>
                </div>
                <div class="card-body" style="padding: 1rem 1.5rem;">
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem;">
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.25rem;">Created By</div>
                            <div style="font-size: 0.9rem; color: #212529;">{{ $adjustment->createdBy->name ?? '-' }}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.25rem;">Created At</div>
                            <div style="font-size: 0.9rem; color: #212529;">{{ $adjustment->created_at ? $adjustment->created_at->format('j M Y H:i') : '-' }}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.25rem;">Last Updated By</div>
                            <div style="font-size: 0.9rem; color: #212529;">{{ $adjustment->updatedBy->name ?? '-' }}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.25rem;">Last Updated At</div>
                            <div style="font-size: 0.9rem; color: #212529;">{{ $adjustment->updated_at ? $adjustment->updated_at->format('j M Y H:i') : '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Add Item Modal -->
<div id="addItemModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50" style="display: none;">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4 overflow-hidden" style="max-width: 500px; margin: 100px auto;">
        <div class="bg-blue-900 text-white p-4 flex justify-between items-center" style="background-color: #1e3a8a; padding: 1rem;">
            <h3 class="text-lg font-semibold m-0" style="color: white;">Add Product to Adjustment</h3>
            <button onclick="closeAddItemModal()" class="text-white hover:text-gray-200" style="background: none; border: none; color: white;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-6" style="padding: 1.5rem;">
            <form id="addItemForm" onsubmit="submitAddItem(event)">
                <div class="mb-4" style="margin-bottom: 1rem;">
                    <label class="block text-sm font-medium text-gray-700 mb-1" style="display: block; margin-bottom: 0.5rem;">Product</label>
                    <select id="item_product_id" class="form-control" required style="width: 100%; padding: 0.5rem;">
                        <option value="">Loading products...</option>
                    </select>
                </div>
                <div class="row mb-4" style="margin-bottom: 1rem;">
                    <div class="col-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1" style="display: block; margin-bottom: 0.5rem;">Type</label>
                        <select id="item_type" class="form-control" required style="width: 100%; padding: 0.5rem;">
                            <option value="increase">Increase (+)</option>
                            <option value="decrease">Decrease (-)</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1" style="display: block; margin-bottom: 0.5rem;">Quantity</label>
                        <input type="number" id="item_qty" step="0.01" min="0.01" class="form-control" required style="width: 100%; padding: 0.5rem;">
                    </div>
                </div>
                <div class="mb-4" style="margin-bottom: 1rem;">
                    <label class="block text-sm font-medium text-gray-700 mb-1" style="display: block; margin-bottom: 0.5rem;">Notes (Optional)</label>
                    <textarea id="item_notes" class="form-control" rows="2" style="width: 100%; padding: 0.5rem;"></textarea>
                </div>
                <div class="flex justify-end gap-3 mt-6 text-end">
                    <button type="button" onclick="closeAddItemModal()" class="btn btn-secondary me-2">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50" style="display: none;">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4 overflow-hidden" style="max-width: 500px; margin: 100px auto;">
        <div class="bg-red-600 text-white p-4 flex justify-between items-center" style="background-color: #dc2626; padding: 1rem;">
            <h3 class="text-lg font-semibold m-0" style="color: white;">Reject Adjustment</h3>
            <button onclick="closeRejectModal()" class="text-white hover:text-gray-200" style="background: none; border: none; color: white;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-6" style="padding: 1.5rem;">
            <form id="rejectForm" onsubmit="submitReject(event)">
                <div class="mb-4" style="margin-bottom: 1rem;">
                    <label class="block text-sm font-medium text-gray-700 mb-1" style="display: block; margin-bottom: 0.5rem;">Rejection Reason</label>
                    <textarea id="reject_notes" class="form-control" rows="4" required style="width: 100%; padding: 0.5rem;"></textarea>
                </div>
                <div class="flex justify-end gap-3 mt-6 text-end">
                    <button type="button" onclick="closeRejectModal()" class="btn btn-secondary me-2">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    const adjustmentId = {{ $adjustment->id }};
    const warehouseId = {{ $adjustment->warehouse_id }};

    function submitForApproval() {
        showConfirmDialog(
            'Ajukan untuk Persetujuan?',
            'Stock adjustment akan dikirim untuk persetujuan.',
            'Ya, ajukan',
            'Batal'
        ).then((confirmed) => {
            if (!confirmed) return;

            fetch(`/warehouse/stock-adjustments/${adjustmentId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ status: 'waiting for approval' })
            })
            .then(r => r.json())
            .then(res => {
                if(res.status === 'success') location.reload();
                else showErrorDialog('Gagal', res.message);
            });
        });
    }

    function approveAdjustment() {
        showConfirmDialog(
            'Setujui Stock Adjustment?',
            'Seluruh stok akan diperbarui setelah adjustment disetujui.',
            'Ya, setujui',
            'Batal'
        ).then((confirmed) => {
            if (!confirmed) return;

            fetch(`/warehouse/stock-adjustments/${adjustmentId}/approve`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            })
            .then(r => r.json())
            .then(res => {
                if(res.status === 'success') location.reload();
                else showErrorDialog('Gagal', res.message);
            });
        });
    }

    // Modal Controls
    function openAddItemModal() {
        const modal = document.getElementById('addItemModal');
        modal.style.display = 'block';
        modal.style.position = 'fixed';
        modal.style.top = '0';
        modal.style.left = '0';
        modal.style.width = '100%';
        modal.style.height = '100%';
        modal.style.backgroundColor = 'rgba(0,0,0,0.5)';
        modal.style.zIndex = '1050';
        loadProducts();
    }

    function closeAddItemModal() {
        document.getElementById('addItemModal').style.display = 'none';
    }

    function openRejectModal() {
        const modal = document.getElementById('rejectModal');
        modal.style.display = 'block';
        modal.style.position = 'fixed';
        modal.style.top = '0';
        modal.style.left = '0';
        modal.style.width = '100%';
        modal.style.height = '100%';
        modal.style.backgroundColor = 'rgba(0,0,0,0.5)';
        modal.style.zIndex = '1050';
    }

    function closeRejectModal() {
        document.getElementById('rejectModal').style.display = 'none';
    }

    function loadProducts() {
        const select = document.getElementById('item_product_id');
        if(select.options.length > 1) return; // already loaded

        fetch(`/warehouse/stock-adjustments/create?warehouse_id=${warehouseId}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            select.innerHTML = '<option value="">Pilih Produk</option>';
            if(data.data.products) {
                data.data.products.forEach(p => {
                    let text = p.name;
                    if(p.sku) text += ` (${p.sku})`;
                    
                    // Add packaging size if available (either from relation or direct field)
                    const pkgSize = p.packaging_size?.name || p.packaging_size;
                    if(pkgSize) text += ` - ${pkgSize}`;
                    
                    select.innerHTML += `<option value="${p.id}">${text}</option>`;
                });
            }
        })
        .catch(err => {
            console.error(err);
            select.innerHTML = '<option value="">Error loading products</option>';
        });
    }

    function submitAddItem(e) {
        e.preventDefault();
        
        const data = {
            master_product_id: document.getElementById('item_product_id').value,
            adjustment_type: document.getElementById('item_type').value,
            adjustment_qty: document.getElementById('item_qty').value,
            notes: document.getElementById('item_notes').value
        };

        fetch(`/warehouse/stock-adjustments/${adjustmentId}/add-item`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(data)
        })
        .then(r => r.json())
        .then(res => {
            if(res.status === 'success') location.reload();
            else showErrorDialog('Gagal', res.message);
        });
    }

    function submitReject(e) {
        e.preventDefault();
        const notes = document.getElementById('reject_notes').value;

        fetch(`/warehouse/stock-adjustments/${adjustmentId}/reject`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ notes: notes })
        })
        .then(r => r.json())
        .then(res => {
            if(res.status === 'success') location.reload();
            else showErrorDialog('Gagal', res.message);
        });
    }

    function deleteItem(itemId) {
        showConfirmDialog(
            'Hapus Item?',
            'Item ini akan dihapus dari stock adjustment.',
            'Ya, hapus',
            'Batal'
        ).then((confirmed) => {
            if (!confirmed) return;

            fetch(`/warehouse/stock-adjustments/items/${itemId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(r => r.json())
            .then(res => {
                if(res.status === 'success') location.reload();
                else showErrorDialog('Gagal', res.message);
            });
        });
    }
</script>
@endpush
