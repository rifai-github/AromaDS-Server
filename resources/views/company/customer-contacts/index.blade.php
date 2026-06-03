@extends('layouts.app')

@section('title', 'Customer Contacts')
@section('breadcrumb', 'Home / Company / Customer Contacts')

@section('content')
<style>
    /* Global overflow control */
    html, body {
        overflow-x: hidden;
        max-width: 100vw;
    }

    *, *::before, *::after {
        box-sizing: border-box;
    }

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

    /* Table Container */
    .table-container {
        background: white;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        border-radius: 0 0 10px 10px;
        position: relative;
        width: 100%;
        max-width: 100%;
        margin: 0;
        padding: 0;
        overflow-x: auto;
        overflow-y: visible;
        -webkit-overflow-scrolling: touch;
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
        display: flex;
        justify-content: center;
        margin-bottom: 24px;
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
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-cancel:hover {
        background-color: #1e40af;
        color: white;
    }

    .btn-hide {
        background-color: #1e40af;
        color: white;
        border: 2px solid #1e40af;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-hide:hover {
        background-color: #1e3a8a;
        border-color: #1e3a8a;
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

    .form-control {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.2s ease;
    }

    .form-control:focus {
        outline: none;
        border-color: #214589;
        box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
    }

    .grid {
        display: grid;
    }

    .grid-cols-1 {
        grid-template-columns: repeat(1, minmax(0, 1fr));
    }

    .gap-6 {
        gap: 1.5rem;
    }

    @media (min-width: 768px) {
        .md\\:grid-cols-2 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    .detail-item {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .detail-label {
        font-size: 12px;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .detail-value {
        font-size: 14px;
        color: #1f2937;
        font-weight: 500;
    }

    /* Table Styles */
    .table {
        width: 100%;
        border-collapse: collapse;
        margin: 0;
    }

    .table th {
        background-color: #214589;
        color: white;
        font-weight: 600;
        padding: 12px 8px;
        text-align: left;
        border-bottom: 2px solid #dee2e6;
        font-size: 13px;
        white-space: nowrap;
    }

    .table td {
        padding: 12px 8px;
        border-bottom: 1px solid #dee2e6;
        vertical-align: middle;
        font-size: 13px;
    }

    .table tbody tr:hover {
        background-color: #f8f9fa;
    }

    .badge {
        display: inline-block;
        padding: 4px 8px;
        font-size: 11px;
        font-weight: 500;
        border-radius: 4px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .badge-success {
        background-color: #d1fae5;
        color: #065f46;
    }

    .badge-danger {
        background-color: #fee2e2;
        color: #991b1b;
    }

    .badge-light {
        background-color: #f3f4f6;
        color: #6b7280;
    }

    .text-primary {
        color: #214589 !important;
    }

    .text-success {
        color: #059669 !important;
    }

    .text-info {
        color: #0891b2 !important;
    }

    .text-secondary {
        color: #6b7280 !important;
    }

    .text-muted {
        color: #6b7280 !important;
    }

    /* Loading Animation */
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Extra small button */
    .btn-xs {
        padding: 2px 6px;
        font-size: 10px;
        border-radius: 4px;
    }

    /* Outline primary button */
    .btn-outline-primary {
        background-color: transparent;
        color: #214589;
        border: 1px solid #214589;
    }

    .btn-outline-primary:hover {
        background-color: #214589;
        color: white;
    }

    /* Email verification badge styling */
    .badge.bg-success {
        background-color: #10b981 !important;
    }
    .badge.bg-warning {
        background-color: #f59e0b !important;
    }
    .badge.bg-danger {
        background-color: #ef4444 !important;
    }

    /* Validation Feedback */
    .invalid-feedback {
        display: none;
        width: 100%;
        margin-top: 4px;
        font-size: 12px;
        color: #ef4444;
        font-weight: 500;
    }

    .form-control.is-invalid {
        border-color: #ef4444;
    }

    .form-control.is-invalid + .invalid-feedback {
        display: block;
    }

    .form-control.is-warning {
        border-color: #f59e0b;
    }

    .form-control.is-warning + .warning-feedback {
        display: block;
    }

    .warning-feedback {
        display: none;
        width: 100%;
        margin-top: 4px;
        font-size: 12px;
        color: #f59e0b;
        font-weight: 500;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <!-- Filter Section -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="filter_customer" class="form-label">Filter by Customer</label>
                                <select class="form-control" id="filter_customer" name="filter_customer">
                                    <option value="">All Customers</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="filter_status" class="form-label">Filter by Status</label>
                                <select class="form-control" id="filter_status" name="filter_status">
                                    <option value="">All Status</option>
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="search_contact" class="form-label">Search Contact</label>
                                <input type="text" class="form-control" id="search_contact" name="search_contact" placeholder="Search by name, email, or phone">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-secondary" id="clear_filters">
                                        <i class="fas fa-times mr-2"></i>Clear
                                    </button>
                                    <button type="button" class="btn btn-primary" onclick="openCreateModal()">
                                        <i class="fas fa-plus mr-2"></i>Add Contact
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Data Table -->
                    <div class="table-container">
                        <table class="table" id="customerContactsTable">
                            <thead>
                                <tr>
                                    <th width="5%" data-no-filter>No</th>
                                    <th width="13%" data-column="customer__name">Customer</th>
                                    <th width="13%" data-column="name">Contact Name</th>
                                    <th width="10%" data-column="position">Position</th>
                                    <th width="12%" data-column="email">Email</th>
                                    <th width="10%" data-column="phone">Phone</th>
                                    <th width="6%" data-column="is_active">Status</th>
                                    <th data-column="createdBy__name">Created By</th>
                                    <th data-column="created_at" data-type="date">Created At</th>
                                    <th data-column="updatedBy__name">Last Updated By</th>
                                    <th data-column="updated_at" data-type="date">Last Updated At</th>
                                    <th width="8%" data-no-filter>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($customerContacts as $index => $contact)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            @if($contact->customers && $contact->customers->count() > 0)
                                                @foreach($contact->customers as $cust)
                                                    <div class="d-flex align-items-center mb-1">
                                                        <i class="fas fa-building text-primary mr-2"></i>
                                                        <span class="font-weight-bold">{{ $cust->name }}</span>
                                                        @if($cust->pivot && $cust->pivot->is_primary)
                                                            <span class="badge badge-success ml-2" style="font-size: 9px;">Primary</span>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            @elseif($contact->customer)
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-building text-primary mr-2"></i>
                                                    <span class="font-weight-bold">{{ $contact->customer->name }}</span>
                                                </div>
                                            @else
                                                <span class="text-muted">No Customer</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-user text-info mr-2"></i>
                                            <span>{{ $contact->salutation ? $contact->salutation . ' ' : '' }}{{ $contact->name }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-light">{{ $contact->position ?: 'N/A' }}</span>
                                    </td>
                                    <td>
                                        @if($contact->email)
                                            <div class="d-flex align-items-center gap-2">
                                                <a href="mailto:{{ $contact->email }}" class="text-primary">
                                                    <i class="fas fa-envelope mr-1"></i>{{ $contact->email }}
                                                </a>
                                                {!! $contact->email_verification_badge !!}
                                                @if(!$contact->isEmailVerified())
                                                    <button type="button" class="btn btn-xs btn-outline-primary" onclick="resendVerification({{ $contact->id }})" title="{{ $contact->isEmailVerificationPending() ? 'Kirim ulang email verifikasi' : 'Kirim email verifikasi' }}">
                                                        <i class="fas fa-paper-plane"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($contact->phone)
                                            <a href="tel:{{ $contact->phone }}" class="text-success">
                                                <i class="fas fa-phone mr-1"></i>{{ $contact->phone }}
                                            </a>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $contact->is_active ? 'success' : 'danger' }}">
                                            {{ $contact->is_active_text }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-user-circle text-secondary mr-2"></i>
                                            <span>{{ $contact->createdBy->name ?? 'System' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        @if($contact->created_at)
                                            {{ \Carbon\Carbon::parse($contact->created_at)->format('d/M/Y') }}<br>
                                            at {{ \Carbon\Carbon::parse($contact->created_at)->format('H.i') }} WIB
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $contact->updatedBy->name ?? '-' }}</td>
                                    <td>
                                        @if($contact->updated_at)
                                            {{ \Carbon\Carbon::parse($contact->updated_at)->format('d/M/Y') }}<br>
                                            at {{ \Carbon\Carbon::parse($contact->updated_at)->format('H.i') }} WIB
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-info" onclick="openViewModal({{ $contact->id }})" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-warning" onclick="openEditModal({{ $contact->id }})" title="Edit Contact">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="openDeleteModal({{ $contact->id }})" title="Delete Contact">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        <i class="fas fa-info-circle fa-2x mb-3 d-block"></i>
                                        <h5>No Customer Contacts Found</h5>
                                        <p>Start by adding your first customer contact using the "Add Contact" button above.</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination Links -->
                    <div class="mt-4 d-flex justify-content-end">
                        {{ $customerContacts->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">View Contact</h2>
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

<!-- Delete Confirmation Modal -->
<div id="deleteModalOverlay" class="delete-modal-overlay" onclick="closeDeleteModal()">
    <div class="delete-modal-container" onclick="event.stopPropagation()">
        <div class="delete-icon-container">
            <svg class="delete-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 19.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
        </div>
        <h3 class="delete-modal-title">Delete Contact</h3>
        <p class="delete-modal-description" id="deleteMessage">Are you sure you want to delete this contact? This action cannot be undone.</p>
        <div class="delete-modal-buttons">
            <button class="btn btn-cancel" onclick="closeDeleteModal()">Cancel</button>
            <button class="btn btn-hide" onclick="confirmDelete()">Yes, Delete</button>
        </div>
    </div>
</div>

<script>
let selectedContactId = null;

// Modal functions
function openModal(title) {
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('modalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
    
    // Initialize Select2 if library exists and modal has select2 elements
    setTimeout(() => {
        if (typeof $ !== 'undefined' && $.fn.select2) {
            $('.select2-multiple').select2({
                placeholder: "Select Customers",
                allowClear: true,
                width: '100%',
                dropdownParent: $('#modalOverlay .modal-container')
            });
        }
    }, 100);
}

function closeModal() {
    document.getElementById('modalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
    document.getElementById('modalBody').innerHTML = '';
    document.getElementById('modalFooter').innerHTML = '';
}

// CRUD Modal functions
function openCreateModal() {
    openModal('Create New Contact');
    
    // Get dynamic data for dropdowns
    const customers = @json($customers ?? []);
    const salutations = @json($salutations ?? []);
    const positions = @json($positions ?? []);
    
    let customersOptions = '<option value="">Select Customer</option>';
    customers.forEach(customer => {
        customersOptions += `<option value="${customer.id}">${customer.name}</option>`;
    });
    
    let salutationsOptions = '';
    salutations.forEach(salutation => {
        salutationsOptions += `<option value="${salutation}">${salutation}</option>`;
    });
    
    let positionsOptions = '';
    positions.forEach(position => {
        positionsOptions += `<option value="${position}">${position}</option>`;
    });
    
    document.getElementById('modalBody').innerHTML = `
        <form id="form" onsubmit="submitForm(event)">
            <div class="modal-section">
                <div class="modal-section-title">Contact Information</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="detail-item">
                        <label class="form-label">Customers</label>
                        <select class="form-control select2-multiple" name="customer_ids[]" multiple="multiple">
                            ${customersOptions}
                        </select>
                        <div class="warning-feedback"></div>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Salutation</label>
                        <select class="form-control" name="salutation">
                            <option value="">Select Salutation</option>
                            ${salutationsOptions}
                        </select>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Contact Name <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" name="name" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Position</label>
                        <select class="form-control" name="position">
                            <option value="">Select Position</option>
                            ${positionsOptions}
                        </select>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email">
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone">
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Status</label>
                        <div style="display: flex; align-items: center; gap: 8px; margin-top: 8px;">
                            <input type="checkbox" name="is_active" value="1" checked>
                            <span>Active</span>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    `;
    
    // Add modal footer
    document.getElementById('modalFooter').innerHTML = `
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
        <button type="submit" form="form" class="btn btn-primary">Create Contact</button>
    `;
}

function openViewModal(id) {
    openModal('View Contact');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Loading...</p></div>';
    
    fetch(`/company/customer-contacts/${id}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            document.getElementById('modalBody').innerHTML = `
                <div class="modal-section">
                    <div class="modal-section-title">Contact Information</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <div class="detail-label">Salutation</div>
                            <div class="detail-value">${data.salutation || 'N/A'}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Contact Name</div>
                            <div class="detail-value">${data.name || 'N/A'}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Position</div>
                            <div class="detail-value">${data.position || 'N/A'}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Email</div>
                            <div class="detail-value">${data.email || 'N/A'}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Phone</div>
                            <div class="detail-value">${data.phone || 'N/A'}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Status</div>
                            <div class="detail-value">
                                <span class="badge ${data.is_active ? 'badge-success' : 'badge-danger'}">
                                    ${data.is_active ? 'Active' : 'Inactive'}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-section">
                    <div class="modal-section-title">Customer Information</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        ${data.customers && data.customers.length > 0 ? 
                            `<div class="detail-item" style="grid-column: 1 / -1;">
                                <div class="detail-label">Linked Customers</div>
                                <div class="detail-value">
                                    <ul style="list-style: none; padding: 0; margin: 0;">
                                        ${data.customers.map(cust => `
                                            <li style="margin-bottom: 4px; display: flex; align-items: center;">
                                                <i class="fas fa-building text-primary mr-2" style="font-size: 10px;"></i>
                                                <span class="font-weight-bold mr-2">${cust.name}</span>
                                                ${cust.pivot && cust.pivot.is_primary == 1 ? '<span class="badge badge-success" style="font-size: 9px;">Primary</span>' : ''}
                                            </li>
                                        `).join('')}
                                    </ul>
                                </div>
                            </div>` 
                            : 
                            `<div class="detail-item">
                                <div class="detail-label">Customer</div>
                                <div class="detail-value">${data.customer ? data.customer.name : 'N/A'}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Customer Type</div>
                                <div class="detail-value">${data.customer ? (data.customer.customer_type || 'N/A') : 'N/A'}</div>
                            </div>`
                        }
                    </div>
                </div>
            `;
        
        // Add modal footer for view modal
            document.getElementById('modalFooter').innerHTML = `
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
            <button type="button" class="btn btn-primary" onclick="openEditModal(${id})">Edit Contact</button>
        `;
        })
        .catch(error => {
        console.error('Error:', error);
        document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading details.</div>';
        document.getElementById('modalFooter').innerHTML = `
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
        `;
        });
}

function openEditModal(id) {
    openModal('Edit Contact');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Loading...</p></div>';
    
    fetch(`/company/customer-contacts/${id}/edit`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            // Get dynamic data for dropdowns
            const customers = @json($customers ?? []);
            const salutations = @json($salutations ?? []);
            const positions = @json($positions ?? []);
            
            let customersOptions = '<option value="">Select Customer</option>';
            customers.forEach(customer => {
                const selected = customer.id == data.customer_id ? 'selected' : '';
                customersOptions += `<option value="${customer.id}" ${selected}>${customer.name}</option>`;
            });
            
            let salutationsOptions = '';
            salutations.forEach(salutation => {
                const selected = salutation == data.salutation ? 'selected' : '';
                salutationsOptions += `<option value="${salutation}" ${selected}>${salutation}</option>`;
            });
            
            let positionsOptions = '';
            positions.forEach(position => {
                const selected = position == data.position ? 'selected' : '';
                positionsOptions += `<option value="${position}" ${selected}>${position}</option>`;
            });

            // Re-render customersOptions with multiple selected support
            customersOptions = '';
            const linkedCustomerIds = data.customers ? data.customers.map(c => c.id) : (data.customer_id ? [data.customer_id] : []);
            customers.forEach(customer => {
                const isSelected = linkedCustomerIds.includes(customer.id) ? 'selected' : '';
                customersOptions += `<option value="${customer.id}" ${isSelected}>${customer.name}</option>`;
            });
            
            document.getElementById('modalBody').innerHTML = `
                <form id="form" onsubmit="submitForm(event, ${id})">
                    <div class="modal-section">
                        <div class="modal-section-title">Contact Information</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="detail-item">
                                <label class="form-label">Customers</label>
                                <select class="form-control select2-multiple" name="customer_ids[]" multiple="multiple">
                                    ${customersOptions}
                                </select>
                                <div class="warning-feedback"></div>
                            </div>
                            <div class="detail-item">
                                <label class="form-label">Salutation</label>
                                <select class="form-control" name="salutation">
                                    <option value="">Select Salutation</option>
                                    ${salutationsOptions}
                                </select>
                            </div>
                            <div class="detail-item">
                                <label class="form-label">Contact Name <span style="color: red;">*</span></label>
                                <input type="text" class="form-control" name="name" value="${data.name || ''}" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="detail-item">
                                <label class="form-label">Position</label>
                                <select class="form-control" name="position">
                                    <option value="">Select Position</option>
                                    ${positionsOptions}
                                </select>
                            </div>
                            <div class="detail-item">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" value="${data.email || ''}">
                            </div>
                            <div class="detail-item">
                                <label class="form-label">Phone</label>
                                <input type="text" class="form-control" name="phone" value="${data.phone || ''}">
                            </div>
                            <div class="detail-item">
                                <label class="form-label">Status</label>
                                <div style="display: flex; align-items: center; gap: 8px; margin-top: 8px;">
                                    <input type="checkbox" name="is_active" value="1" ${data.is_active ? 'checked' : ''}>
                                    <span>Active</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            `;
            
            // Add modal footer for edit modal
            document.getElementById('modalFooter').innerHTML = `
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" form="form" class="btn btn-primary">Update Contact</button>
            `;
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading details.</div>';
            document.getElementById('modalFooter').innerHTML = `
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
            `;
        });
}

function submitForm(event, id = null) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const data = {};
    
    // Manual data collection to handle multiple values (customer_ids)
    formData.forEach((value, key) => {
        if (key.endsWith('[]')) {
            const cleanKey = key.slice(0, -2);
            if (!data[cleanKey]) data[cleanKey] = [];
            data[cleanKey].push(value);
        } else {
            data[key] = value;
        }
    });

    // Special handling for customer_ids array
    if (!data.customer_ids && formData.getAll) {
        data.customer_ids = formData.getAll('customer_ids[]');
    }
    
    // Clear previous validation errors
    form.querySelectorAll('.is-invalid, .is-warning').forEach(el => el.classList.remove('is-invalid', 'is-warning'));
    form.querySelectorAll('.invalid-feedback, .warning-feedback').forEach(el => {
        el.textContent = '';
        el.style.display = 'none';
    });
    
    // Add custom soft-warning for customer_id if empty
    const customerInput = form.querySelector('[name="customer_id"]');
    if (customerInput && !customerInput.value) {
        customerInput.classList.add('is-warning');
        const warningFeedback = customerInput.closest('.detail-item').querySelector('.warning-feedback');
        if (warningFeedback) {
            warningFeedback.textContent = 'customer kosong silahkan isi';
            warningFeedback.style.display = 'block';
        }
    }
    
    // Convert is_active to boolean
    data.is_active = data.is_active === '1';
    
    const url = id ? `/company/customer-contacts/${id}` : '/company/customer-contacts';
    const method = id ? 'PUT' : 'POST';
    
    // Add CSRF token
    data._token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    data._method = method;
    
    // Disable submit button
    const submitBtn = document.querySelector('button[form="form"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
    submitBtn.disabled = true;

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': data._token,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(data)
    })
    .then(async response => {
        const result = await response.json();
        
        if (response.status === 422) {
            // Handle validation errors
            if (result.errors) {
                Object.keys(result.errors).forEach(key => {
                    const input = form.querySelector(`[name="${key}"]`);
                    if (input) {
                        input.classList.add('is-invalid');
                        const feedback = input.closest('.detail-item').querySelector('.invalid-feedback');
                        if (feedback) {
                            feedback.textContent = result.errors[key][0];
                        }
                    }
                });
            }
            throw new Error(result.message || 'Validation failed');
        }
        
        if (!response.ok) {
            throw new Error(result.message || `HTTP error! status: ${response.status}`);
        }
        
        return result;
    })
    .then(result => {
        if (result.success) {
            closeModal();
            location.reload();
        } else {
            showErrorDialog('Gagal', result.message || 'Terjadi kesalahan.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (error.message !== 'Validation failed') {
            showErrorDialog('Gagal', error.message);
        }
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

// Delete Modal functions
function openDeleteModal(id) {
    console.log('openDeleteModal called with id:', id);
    selectedContactId = id;
    console.log('selectedContactId set to:', selectedContactId);
    document.getElementById('deleteModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeDeleteModal() {
    document.getElementById('deleteModalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
    // Don't reset selectedContactId here, let confirmDelete handle it
}

function confirmDelete() {
    console.log('confirmDelete called, selectedContactId:', selectedContactId);
    if (!selectedContactId || selectedContactId === null || selectedContactId === 'null') {
        console.log('No valid selectedContactId, returning');
        showErrorDialog('Gagal', 'Tidak ada contact yang dipilih untuk dihapus.');
        return;
    }
    
    // Store the ID in a local variable before closing modal
    const contactId = selectedContactId;
    console.log('Stored contactId:', contactId);
    
    closeDeleteModal();
    
    console.log('Making delete request to:', `/company/customer-contacts/${contactId}`);
    fetch(`/company/customer-contacts/${contactId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ _method: 'DELETE' })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(result => {
        selectedContactId = null; // Reset after successful request
        if (result.success) {
            location.reload();
        } else {
            showErrorDialog('Gagal', result.message || 'Terjadi kesalahan.');
        }
    })
    .catch(error => {
        selectedContactId = null; // Reset after error
        console.error('Error:', error);
        showErrorDialog('Gagal', 'Terjadi kesalahan.');
    });
}

// Filter functionality
document.addEventListener('DOMContentLoaded', function() {
    const filterCustomer = document.getElementById('filter_customer');
    const filterStatus = document.getElementById('filter_status');
    const searchContact = document.getElementById('search_contact');
    const clearFilters = document.getElementById('clear_filters');
    
    if (filterCustomer) {
        filterCustomer.addEventListener('change', function() {
            // Simple client-side filtering
            filterTable();
        });
    }
    
    if (filterStatus) {
        filterStatus.addEventListener('change', function() {
            filterTable();
        });
    }
    
    if (searchContact) {
        searchContact.addEventListener('keyup', function() {
            filterTable();
        });
    }
    
    if (clearFilters) {
        clearFilters.addEventListener('click', function() {
            filterCustomer.value = '';
            filterStatus.value = '';
            searchContact.value = '';
            filterTable();
        });
    }
    
    function filterTable() {
        const customerFilter = filterCustomer ? filterCustomer.value.toLowerCase() : '';
        const statusFilter = filterStatus ? filterStatus.value : '';
        const searchFilter = searchContact ? searchContact.value.toLowerCase() : '';
        
        const rows = document.querySelectorAll('#customerContactsTable tbody tr');
        
        rows.forEach(row => {
            if (!row.cells || row.cells.length < 7) {
                return; // Skip rows that don't have enough cells
            }
            
            const customer = row.cells[1] ? row.cells[1].textContent.toLowerCase() : '';
            const status = row.cells[6] ? row.cells[6].textContent.toLowerCase() : '';
            const contactName = row.cells[2] ? row.cells[2].textContent.toLowerCase() : '';
            const email = row.cells[4] ? row.cells[4].textContent.toLowerCase() : '';
            const phone = row.cells[5] ? row.cells[5].textContent.toLowerCase() : '';
            
            let show = true;
            
            if (customerFilter && !customer.includes(customerFilter)) {
                show = false;
            }
            
            if (statusFilter && !status.includes(statusFilter === '1' ? 'active' : 'inactive')) {
                show = false;
            }
            
            if (searchFilter && !contactName.includes(searchFilter) && !email.includes(searchFilter) && !phone.includes(searchFilter)) {
                show = false;
            }
            
            row.style.display = show ? '' : 'none';
        });
    }
});

// Resend verification email function
function resendVerification(contactId) {
    if (false) {
        return;
    }
    
    const button = event.target.closest('button');
    const originalHtml = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    button.disabled = true;
    
    fetch(`/company/customer-contacts/${contactId}/resend-verification`, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessDialog(data.message || 'Email verifikasi berhasil dikirim.');
            // Optionally refresh the page to show updated status
            location.reload();
        } else {
            showErrorDialog('Gagal', data.message || 'Gagal mengirim email verifikasi.');
            button.innerHTML = originalHtml;
            button.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorDialog('Gagal', 'Terjadi kesalahan saat mengirim email verifikasi.');
        button.innerHTML = originalHtml;
        button.disabled = false;
    });
}

resendVerification = function(contactId) {
    const button = event.target.closest('button');
    const originalHtml = button.innerHTML;

    showConfirmDialog(
        'Kirim email verifikasi?',
        'Email verifikasi akan dikirim ke contact ini.'
    ).then((result) => {
        if (!result.isConfirmed) {
            return;
        }

        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        button.disabled = true;

        fetch(`/company/customer-contacts/${contactId}/resend-verification`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSuccessDialog(data.message || 'Email verifikasi berhasil dikirim.');
                location.reload();
            } else {
                showErrorDialog('Gagal', data.message || 'Gagal mengirim email verifikasi.');
                button.innerHTML = originalHtml;
                button.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorDialog('Gagal', 'Terjadi kesalahan saat mengirim email verifikasi.');
            button.innerHTML = originalHtml;
            button.disabled = false;
        });
    });
}
</script>
@endsection
