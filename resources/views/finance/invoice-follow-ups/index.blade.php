@extends('layouts.app')

@section('title', 'Invoice Follow Ups')
@section('breadcrumb', 'Home / Finance / Invoice Follow Ups')

@section('content')
@include('finance.shared.responsive-table-styles')

<div class="flex flex-col w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">
        
        <!-- Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Invoice Follow Ups</h1>
            </div>
            <button class="btn btn-primary" onclick="openCreateModal()">
                <i class="fas fa-plus"></i>
                <span>Create Follow Up</span>
            </button>
        </div>
        
        <!-- Controls Row -->
        <div class="flex flex-row justify-between items-center w-full p-4 bg-white controls-row">
            <div class="flex flex-row justify-start items-center w-full controls-left">
                <div class="flex flex-row justify-start items-center w-auto">
                    <div class="flex flex-row items-center">
                        <input type="checkbox" id="selectAll" class="w-4 h-4 bg-white border border-[#888888] rounded cursor-pointer">
                        <label for="selectAll" class="ml-2 text-sm text-[#3d3d3d] cursor-pointer">Select all</label>
                    </div>
                </div>
                
                <button class="btn btn-secondary ml-4" onclick="deleteSelected()">
                    <i class="fas fa-trash"></i>
                    <span>Delete</span>
                </button>
            </div>
        </div>
        
        <!-- Table Container -->
        <div class="w-full bg-white rounded-b-[10px] table-container">
            <table class="responsive-table" id="invoiceFollowUpsTable">
                <thead>
                    <tr>
                        <th class="w-[50px]" data-no-filter>
                            <input type="checkbox" id="headerSelectAll" class="w-4 h-4 bg-white border border-[#888888] rounded cursor-pointer">
                        </th>
                        <th class="w-[150px]" data-column="invoice__invoice_number">Invoice Number</th>
                        <th class="w-[150px]" data-column="invoice__customer__name">Customer</th>
                        <th class="w-[120px]" data-column="follow_up_date" data-type="date">Follow Up Date</th>
                        <th class="w-[120px]" data-column="follow_up_type">Type</th>
                        <th class="w-[100px]" data-column="status">Status</th>
                        <th class="w-[120px]" data-column="creator.name">Created By</th>
                        <th class="w-[150px]" data-column="created_at" data-type="date">Created At</th>
                        <th class="w-[120px]" data-column="updater.name">Last Updated By</th>
                        <th class="w-[150px]" data-column="updated_at" data-type="date">Last Updated At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($followUps as $followUp)
                    <tr onclick="openViewModal({{ $followUp->id }})" data-id="{{ $followUp->id }}">
                        <td class="text-center">
                            <input type="checkbox" class="row-checkbox w-4 h-4 bg-white border border-[#888888] rounded cursor-pointer" onclick="event.stopPropagation()" value="{{ $followUp->id }}">
                        </td>
                        <td>{{ $followUp->invoice->invoice_number ?? 'N/A' }}</td>
                        <td>{{ $followUp->invoice->customer->name ?? 'N/A' }}</td>
                        <td>{{ $followUp->follow_up_date ? \Carbon\Carbon::parse($followUp->follow_up_date)->format('d M Y') : 'N/A' }}</td>
                        <td>{{ ucfirst($followUp->follow_up_type ?? 'N/A') }}</td>
                        <td>
                            @if($followUp->status == 'pending')
                                <span class="status-badge status-pending">Pending</span>
                            @elseif($followUp->status == 'completed')
                                <span class="status-badge status-completed">Completed</span>
                            @elseif($followUp->status == 'cancelled')
                                <span class="status-badge status-cancelled">Cancelled</span>
                            @else
                                <span class="status-badge status-pending">{{ ucfirst($followUp->status ?? 'Pending') }}</span>
                            @endif
                        </td>
                        <td>{{ $followUp->creator->name ?? 'N/A' }}</td>
                        <td>
                            @if($followUp->created_at)
                                {{ $followUp->created_at->format('d M Y') }}<br>
                                at {{ $followUp->created_at->format('H.i') }} WIB
                            @else
                                N/A
                            @endif
                        </td>
                        <td>{{ $followUp->updater->name ?? 'N/A' }}</td>
                        <td>
                            @if($followUp->updated_at)
                                {{ $followUp->updated_at->format('d M Y') }}<br>
                                at {{ $followUp->updated_at->format('H.i') }} WIB
                            @else
                                N/A
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="p-8 text-center">
                            <p class="text-lg font-inter text-center text-[#666]">No follow ups found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        <div class="flex flex-row justify-center items-center w-full p-4 bg-white rounded-b-[10px]">
            <div class="pagination-controls">
                @if($followUps->currentPage() > 1)
                    <a href="{{ $followUps->previousPageUrl() }}" class="btn btn-secondary btn-sm">Previous</a>
                @else
                    <button class="btn btn-secondary btn-sm" disabled>Previous</button>
                @endif
                
                @if($followUps->lastPage() > 0)
                    @php
                        $start = max(1, $followUps->currentPage() - 2);
                        $end = min($followUps->lastPage(), $followUps->currentPage() + 2);
                    @endphp
                    
                    <div class="flex items-center gap-2">
                        @if($start > 1)
                            <a href="{{ $followUps->url(1) }}" class="page-number">1</a>
                            @if($start > 2)
                                <span class="text-sm text-gray-500">...</span>
                            @endif
                        @endif
                        
                        @for($i = $start; $i <= $end; $i++)
                            @if($i == $followUps->currentPage())
                                <span class="page-number active">{{ $i }}</span>
                            @else
                                <a href="{{ $followUps->url($i) }}" class="page-number">{{ $i }}</a>
                            @endif
                        @endfor
                        
                        @if($end < $followUps->lastPage())
                            @if($end < $followUps->lastPage() - 1)
                                <span class="text-sm text-gray-500">...</span>
                            @endif
                            <a href="{{ $followUps->url($followUps->lastPage()) }}" class="page-number">{{ $followUps->lastPage() }}</a>
                        @endif
                    </div>
                @else
                    <span class="page-number active">1</span>
                @endif
                
                @if($followUps->currentPage() < $followUps->lastPage())
                    <a href="{{ $followUps->nextPageUrl() }}" class="btn btn-secondary btn-sm">Next</a>
                @else
                    <button class="btn btn-secondary btn-sm" disabled>Next</button>
                @endif
                
                <div class="page-dropdown-container">
                    <span class="text-sm text-gray-700">Page</span>
                    <select class="bg-gray-100 rounded-lg px-3 py-1 text-sm border border-gray-300 focus:outline-none focus:border-[#214589]">
                        <option>{{ $followUps->currentPage() }}</option>
                    </select>
                    <span class="text-sm text-gray-700">of <span class="inline">{{ $followUps->lastPage() }}</span></span>
                </div>
            </div>
        </div>
    </div>
</div>

@include('finance.shared.modal-overlay')

<script>
@include('finance.shared.table-scripts')

// Delete selected function
function deleteSelected() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    if (checkboxes.length === 0) {
        showWarningDialog('Pilih minimal satu follow up yang ingin dihapus.');
        return;
    }
    
    showConfirmDialog(
        'Hapus follow up yang dipilih?',
        'Data follow up yang dipilih akan dihapus.'
    ).then((result) => {
        if (!result.isConfirmed) {
            return;
        }
        const selectedIds = Array.from(checkboxes).map(checkbox => checkbox.value);
        
        fetch('/finance/invoice-follow-ups/bulk-delete', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ ids: selectedIds })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                showErrorDialog('Gagal', 'Gagal menghapus follow up: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorDialog('Gagal', 'Gagal menghapus follow up.');
        });
    });
}

// Modal functions
function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Tambah Invoice Follow Up';
    document.getElementById('modalBody').innerHTML = `
        <p class="text-gray-600 mb-6 text-center">Buat follow up baru untuk invoice.</p>
        <form id="createForm" class="needs-validation" novalidate>
            <div class="form-group">
                <label class="form-label">Invoice *</label>
                <select name="invoice_id" class="form-control" required>
                    <option value="">Pilih Invoice</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Tanggal Follow Up *</label>
                <input type="date" name="follow_up_date" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Tipe *</label>
                <select name="follow_up_type" class="form-control" required>
                    <option value="">Pilih Tipe</option>
                    <option value="email">Email</option>
                    <option value="phone">Telepon</option>
                    <option value="visit">Kunjungan</option>
                    <option value="letter">Surat</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Status *</label>
                <select name="status" class="form-control" required>
                    <option value="">Pilih Status</option>
                    <option value="pending">Menunggu</option>
                    <option value="completed">Selesai</option>
                    <option value="cancelled">Dibatalkan</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Catatan *</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Masukkan catatan follow up" required></textarea>
            </div>
        </form>
    `;
    
    // Populate invoice dropdown
    const invoiceSelect = document.querySelector('select[name="invoice_id"]');
    const invoices = @json($invoices);
    invoices.forEach(invoice => {
        const option = document.createElement('option');
        option.value = invoice.id;
        option.textContent = `${invoice.invoice_number} - ${invoice.customer_name}`;
        invoiceSelect.appendChild(option);
    });
    
    document.getElementById('modalFooter').innerHTML = `
        <div class="flex justify-center gap-6">
            <button type="button" class="btn btn-outline" onclick="closeModal()">Batal</button>
            <button type="button" class="btn btn-primary" onclick="submitCreateForm()">Simpan Follow Up</button>
        </div>
    `;
    openModal();
}

function openViewModal(id) {
    fetch(`/finance/invoice-follow-ups/${id}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(response => {
            if (!response.success) {
                throw new Error(response.message || 'Gagal memuat data follow up');
            }
            const data = response.data;
            document.getElementById('modalTitle').textContent = 'Detail Follow Up';
            document.getElementById('modalBody').innerHTML = `
                <div class="space-y-4">
                    <div class="detail-item">
                        <label class="form-label">Invoice Number</label>
                        <p class="detail-value">${data.invoice?.invoice_number || 'N/A'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Customer</label>
                        <p class="detail-value">${data.invoice?.customer?.name || 'N/A'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Follow Up Date</label>
                        <p class="detail-value">${data.follow_up_date ? (() => { const date = new Date(data.follow_up_date); const day = date.getDate().toString().padStart(2, '0'); const month = (date.getMonth() + 1).toString().padStart(3, '0'); const year = date.getFullYear(); return day + '/' + month + '/' + year; })() : 'N/A'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Type</label>
                        <p class="detail-value">${data.follow_up_type ? data.follow_up_type.charAt(0).toUpperCase() + data.follow_up_type.slice(1) : 'N/A'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Status</label>
                        <p class="detail-value">${data.status ? data.status.charAt(0).toUpperCase() + data.status.slice(1) : 'N/A'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Notes</label>
                        <p class="detail-value">${data.notes || 'N/A'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Created At</label>
                        <p class="detail-value">${data.created_at ? (() => { const date = new Date(data.created_at); const day = date.getDate().toString().padStart(2, '0'); const month = (date.getMonth() + 1).toString().padStart(3, '0'); const year = date.getFullYear(); const time = date.toLocaleTimeString('id-ID'); return day + '/' + month + '/' + year + ' ' + time; })() : 'N/A'}</p>
                    </div>
                </div>
            `;
            document.getElementById('modalFooter').innerHTML = `
                <div class="flex justify-center gap-6">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Tutup</button>
                    <button type="button" class="btn btn-primary" onclick="openEditModal(${id})">Edit</button>
                </div>
            `;
            openModal();
        })
        .catch(error => {
            console.error('Error loading follow up data:', error);
            showErrorDialog('Gagal', 'Gagal memuat data follow up.');
        });
}

function openEditModal(id) {
    fetch(`/finance/invoice-follow-ups/${id}/edit`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(response => {
            if (!response.success) {
                throw new Error(response.message || 'Gagal memuat data follow up');
            }
            const data = response.data;
            document.getElementById('modalTitle').textContent = 'Edit Follow Up';
            document.getElementById('modalBody').innerHTML = `
                <p class="text-gray-600 mb-6 text-center">Perbarui detail follow up.</p>
                <form id="editForm" class="needs-validation" novalidate>
                    <input type="hidden" name="id" value="${data.id}">
                    <div class="form-group">
                        <label class="form-label">Invoice *</label>
                        <select name="invoice_id" class="form-control" required>
                            <option value="">Pilih Invoice</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tanggal Follow Up *</label>
                        <input type="date" name="follow_up_date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tipe *</label>
                        <select name="follow_up_type" class="form-control" required>
                            <option value="">Pilih Tipe</option>
                            <option value="email">Email</option>
                            <option value="phone">Telepon</option>
                            <option value="visit">Kunjungan</option>
                            <option value="letter">Surat</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status *</label>
                        <select name="status" class="form-control" required>
                            <option value="">Pilih Status</option>
                            <option value="pending">Menunggu</option>
                            <option value="completed">Selesai</option>
                            <option value="cancelled">Dibatalkan</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Catatan *</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Masukkan catatan follow up" required></textarea>
                    </div>
                </form>
            `;
            
            // Populate invoice dropdown
            const invoiceSelect = document.querySelector('select[name="invoice_id"]');
            const invoices = @json($invoices);
            invoices.forEach(invoice => {
                const option = document.createElement('option');
                option.value = invoice.id;
                option.textContent = `${invoice.invoice_number} - ${invoice.customer_name}`;
                if (data.invoice_id == invoice.id) {
                    option.selected = true;
                }
                invoiceSelect.appendChild(option);
            });
            
            document.getElementById('modalFooter').innerHTML = `
                <div class="flex justify-center gap-6">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Batal</button>
                    <button type="button" class="btn btn-primary" onclick="submitEditForm()">Perbarui Follow Up</button>
                </div>
            `;
            
            // Wait for DOM to be ready, then populate form
            setTimeout(() => {
                const form = document.getElementById('editForm');
                
                if (!form) {
                    console.error('Form not found!');
                    return;
                }
                
                form.id.value = data.id || '';
                form.invoice_id.value = data.invoice_id || '';
                form.follow_up_date.value = data.follow_up_date ? data.follow_up_date.split('T')[0] : '';
                form.follow_up_type.value = data.follow_up_type || '';
                form.status.value = data.status || '';
                form.notes.value = data.notes || '';
            }, 100);
        })
        .catch(error => {
            console.error('Error loading follow up data:', error);
            showErrorDialog('Gagal', 'Gagal memuat data follow up.');
        });
}

function submitCreateForm() {
    const form = document.getElementById('createForm');
    
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return;
    }
    
    const formData = new FormData(form);
    
    fetch('/finance/invoice-follow-ups', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            if (response.status === 422) {
                return response.json().then(data => {
                    throw new Error('Validation failed: ' + JSON.stringify(data.errors));
                });
            }
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            closeModal();
            location.reload();
        } else {
            showErrorDialog('Gagal', 'Gagal membuat follow up: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorDialog('Gagal', 'Gagal membuat follow up: ' + error.message);
    });
}

function submitEditForm() {
    const form = document.getElementById('editForm');
    
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return;
    }
    
    const formData = new FormData(form);
    const id = formData.get('id');
    
    
    // Add _method field for Laravel method spoofing
    formData.append('_method', 'PUT');
    
    fetch(`/finance/invoice-follow-ups/${id}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            if (response.status === 422) {
                return response.json().then(data => {
                    throw new Error('Validation failed: ' + JSON.stringify(data.errors));
                });
            }
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            closeModal();
            location.reload();
        } else {
            showErrorDialog('Gagal', 'Gagal memperbarui follow up: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorDialog('Gagal', 'Gagal memperbarui follow up: ' + error.message);
    });
}
</script>
@endsection
