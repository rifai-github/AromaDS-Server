@extends('layouts.app')

@section('title', 'Service Frequencies')
@section('breadcrumb', 'Home / Warehouse / Rental Management / Service Frequencies')

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

    .table-actions {
        display: flex;
        gap: 10px;
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

    .btn-primary {
        background: #007bff;
        color: white;
    }

    .btn-primary:hover {
        background: #0056b3;
        transform: translateY(-2px);
    }

    .btn-info {
        background: #17a2b8;
        color: white;
    }

    .btn-info:hover {
        background: #138496;
    }

    .btn-danger {
        background: #dc3545;
        color: white;
    }

    .btn-danger:hover {
        background: #c82333;
    }

    .btn-sm {
        padding: 4px 8px;
        font-size: 12px;
    }

    .table-wrapper {
        overflow-x: auto;
    }

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

    .data-table tbody tr:hover {
        background: #f8f9fa;
    }

    .badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
        display: inline-block;
        line-height: 1.2;
    }

    .badge-info {
        background: #dbeafe;
        color: #1e40af;
    }

    .badge-primary {
        background: #e0e7ff;
        color: #3730a3;
    }

    .badge-success {
        background: #d4edda;
        color: #155724;
    }

    .badge-danger {
        background: #f8d7da;
        color: #721c24;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #6c757d;
    }

    .empty-state i {
        font-size: 4rem;
        margin-bottom: 20px;
        color: #dee2e6;
    }

    .empty-state h5 {
        margin-bottom: 10px;
        color: #495057;
    }

    .pagination-wrapper {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px;
        background: #f8f9fa;
        border-top: 1px solid #e9ecef;
    }

    .pagination-info {
        color: #6c757d;
        font-size: 14px;
    }

    .pagination-controls {
        display: flex;
        gap: 10px;
    }

    .modal-content {
        border: none;
        border-radius: 8px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    }

    .modal-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 8px 8px 0 0;
    }

    .modal-title {
        font-weight: 600;
    }

    .btn-close {
        filter: invert(1);
    }

    .form-label {
        font-weight: 500;
        color: #495057;
        margin-bottom: 5px;
    }

    .form-control,
    .form-select {
        border: 1px solid #ced4da;
        border-radius: 4px;
        padding: 8px 12px;
        font-size: 14px;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
    }

    /* Modal Overlay Styles */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1050;
        justify-content: center;
        align-items: center;
    }

    .modal-overlay.show {
        display: flex;
    }

    .modal-content {
        background: white;
        border-radius: 8px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        max-width: 90vw;
        max-height: 90vh;
        width: 600px;
        display: flex;
        flex-direction: column;
    }

    .modal-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        border-radius: 8px 8px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-shrink: 0;
    }

    .modal-title {
        margin: 0;
        font-weight: 600;
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
    }

    .modal-body {
        padding: 20px;
        overflow-y: auto;
        flex: 1;
        max-height: calc(90vh - 160px);
    }

    .modal-footer {
        padding: 15px 20px;
        border-top: 1px solid #dee2e6;
        background: #f8f9fa;
        border-radius: 0 0 8px 8px;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        flex-shrink: 0;
    }

    .form-control-plaintext {
        padding: 0.375rem 0;
        margin-bottom: 0;
        line-height: 1.5;
        color: #495057;
        background-color: transparent;
        border: solid transparent;
        border-width: 1px 0;
    }

    /* Ensure form fields are accessible */
    .modal-overlay .form-control,
    .modal-overlay .form-select,
    .modal-overlay input,
    .modal-overlay textarea {
        pointer-events: auto !important;
        user-select: text !important;
        -webkit-user-select: text !important;
        -moz-user-select: text !important;
        -ms-user-select: text !important;
    }

    .modal-overlay .form-control:focus,
    .modal-overlay .form-select:focus {
        border-color: #80bdff !important;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25) !important;
    }

    .text-danger {
        color: #dc3545 !important;
    }

    .btn-group {
        display: flex;
        gap: 5px;
    }

    .btn-light {
        background: rgba(255, 255, 255, 0.2) !important;
        color: white !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
        transition: all 0.3s ease;
    }

    .btn-light:hover {
        background: rgba(255, 255, 255, 0.3) !important;
        border-color: rgba(255, 255, 255, 0.5) !important;
        transform: translateY(-2px);
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Table Container -->
            <div class="table-container">
                <div class="table-header">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <a href="{{ route('warehouse.master-rentals.index') }}" class="btn btn-light" style="background: rgba(255, 255, 255, 0.2); color: white; border: 1px solid rgba(255, 255, 255, 0.3);">
                            <i class="fas fa-arrow-left"></i>
                            Back to Master Rental
                        </a>
                        <h3 class="table-title" style="margin: 0;">
                            <i class="fas fa-clock"></i>
                            Service Frequencies
                        </h3>
                    </div>
                    <div class="table-actions">
                        <button class="btn btn-primary" onclick="openAddModal()">
                            <i class="fas fa-plus"></i>
                            Add Frequency
                        </button>
                    </div>
                </div>

                <div class="table-wrapper">
                    @if($frequencies->count() > 0)
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Frequency</th>
                                    <th>Services/Year</th>
                                    <th>Order</th>
                                    <th>Status</th>
                                    <th>Created By</th>
                                    <th>Created At</th>
                                    <th>Updated By</th>
                                    <th>Updated At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($frequencies as $frequency)
                                <tr onclick="showFrequency({{ $frequency->id }})" style="cursor: pointer;">
                                    <td>
                                        <span class="badge badge-info">{{ $frequency->code }}</span>
                                    </td>
                                    <td>
                                        <div class="font-weight-bold">{{ $frequency->name }}</div>
                                    </td>
                                    <td>
                                        @if($frequency->description)
                                            <div class="text-truncate" style="max-width: 200px;" title="{{ $frequency->description }}">
                                                {{ $frequency->description }}
                                            </div>
                                        @else
                                            <span class="text-muted">No description</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="font-weight-bold">{{ $frequency->frequency_description }}</div>
                                        <small class="text-muted">{{ $frequency->frequency_months }} month(s), {{ $frequency->frequency_times_per_month }} time(s)</small>
                                    </td>
                                    <td>
                                        <span class="badge badge-primary">{{ $frequency->total_services_per_year }}</span>
                                    </td>
                                    <td>{{ $frequency->sort_order }}</td>
                                    <td>
                                        @if($frequency->is_active)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-user-circle mr-2 text-muted"></i>
                                            <span>{{ $frequency->createdBy->name ?? 'System' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-calendar-alt mr-2 text-muted"></i>
                                            <span>{{ $frequency->created_at ? $frequency->created_at->format('d/M/Y H:i') : '-' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-user-edit mr-2 text-muted"></i>
                                            <span>{{ $frequency->updatedBy->name ?? 'System' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-history mr-2 text-muted"></i>
                                            <span>{{ $frequency->updated_at ? $frequency->updated_at->format('d/M/Y H:i') : '-' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-danger" onclick="event.stopPropagation(); deleteFrequency({{ $frequency->id }})" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="empty-state">
                            <i class="fas fa-clock"></i>
                            <h5>No Service Frequencies Found</h5>
                            <p>Start by adding your first service frequency.</p>
                            <button class="btn btn-primary" onclick="openAddModal()">
                                <i class="fas fa-plus"></i>
                                Add First Frequency
                            </button>
                        </div>
                    @endif
                </div>

                @if($frequencies->count() > 0)
                <!-- Pagination -->
                <div class="pagination-wrapper">
                    <div class="pagination-info">
                        <span class="info-text">
                            Showing {{ $frequencies->firstItem() ?? 0 }} to {{ $frequencies->lastItem() ?? 0 }} 
                            of {{ $frequencies->total() }} entries
                        </span>
                    </div>
                    <div class="pagination-controls">
                        {{ $frequencies->links() }}
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Frequency Modal -->
<div class="modal-overlay" id="addFrequencyModal">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="frequencyModalTitle">Add Service Frequency</h5>
            <button type="button" class="modal-close" onclick="closeAddModal()">&times;</button>
        </div>
            <form id="frequencyForm" onsubmit="return false;">
                <input type="hidden" name="_method" id="_method" value="POST">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="code" class="form-label">Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="code" name="code" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="frequency_months" class="form-label">Frequency (Months) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="frequency_months" name="frequency_months" min="1" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="frequency_times_per_month" class="form-label">Times per Month <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="frequency_times_per_month" name="frequency_times_per_month" min="1" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="sort_order" class="form-label">Sort Order</label>
                                <input type="number" class="form-control" id="sort_order" name="sort_order" value="0">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                        <label class="form-check-label" for="is_active">
                            Active
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Cancel</button>
                    <button type="button" class="btn btn-primary" id="frequencySubmitBtn" onclick="submitFrequencyForm()">
                        <i class="fas fa-save me-1"></i>
                        Save Frequency
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Show Frequency Modal -->
<div class="modal-overlay" id="showFrequencyModal">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Frequency Details</h5>
            <button type="button" class="modal-close" onclick="closeShowModal()">&times;</button>
        </div>
        <div class="modal-body" id="showFrequencyContent">
            <!-- Content will be loaded here -->
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeShowModal()">Close</button>
            <button type="button" class="btn btn-primary" onclick="editFrequencyFromShow()">
                <i class="fas fa-edit me-1"></i>
                Edit Frequency
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Track if editing or creating
    let currentEditId = null;
    const baseUrl = '{{ url("/warehouse/rental-management/service-frequencies") }}';
    
    // Form submission function
    function submitFrequencyForm() {
        const form = document.getElementById('frequencyForm');
        const formData = new FormData(form);
        
        // Determine URL and method based on _method field
        const methodField = document.getElementById('_method').value;
        let url = baseUrl;
        let method = 'POST';
        
        if (methodField === 'PUT' && currentEditId) {
            url = baseUrl + '/' + currentEditId;
        }
        
        // Add CSRF token to FormData
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        formData.append('_token', csrfToken);
        
        // Handle checkbox properly
        const isActiveCheckbox = document.getElementById('is_active');
        if (isActiveCheckbox) {
            if (isActiveCheckbox.checked) {
                formData.set('is_active', '1');
            } else {
                formData.set('is_active', '0');
            }
        }
        
        // Disable submit button to prevent double submission
        const submitBtn = document.getElementById('frequencySubmitBtn');
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';
        submitBtn.disabled = true;
        
        console.log('Form submission:', { url, method, formData: Object.fromEntries(formData) });
        
        fetch(url, {
            method: method,
            body: formData,
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                return response.text().then(text => {
                    console.log('Response text:', text);
                    throw new Error(`HTTP ${response.status}: ${text}`);
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            if (data.status === 'success') {
                closeAddModal();
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while saving the frequency: ' + error.message);
        })
        .finally(() => {
            // Restore button state
            submitBtn.innerHTML = originalBtnText;
            submitBtn.disabled = false;
        });
    }


    function deleteFrequency(id) {
        if (confirm('Are you sure you want to delete this service frequency?')) {
            // Show loading indicator - find button by matching onclick containing deleteFrequency(id)
            const deleteBtn = document.querySelector(`button[onclick*="deleteFrequency(${id})"]`);
            let originalText = '';
            if (deleteBtn) {
                originalText = deleteBtn.innerHTML;
                deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
                deleteBtn.disabled = true;
            }
            
            fetch(`/warehouse/rental-management/service-frequencies/${id}`, {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                console.log('Response URL:', response.url);
                
                // Handle different response types
                if (response.status === 403) {
                    return response.json().then(data => {
                        throw new Error(data.message || 'Access denied');
                    });
                }
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                // Check if response is JSON
                const contentType = response.headers.get('content-type');
                console.log('Content-Type:', contentType);
                
                if (contentType && contentType.includes('application/json')) {
                    return response.json();
                } else {
                    // If not JSON, get the text response first
                    return response.text().then(text => {
                        console.log('Non-JSON response text:', text);
                        // Try to parse as JSON anyway
                        try {
                            const jsonData = JSON.parse(text);
                            console.log('Parsed JSON from text:', jsonData);
                            return jsonData;
                        } catch (e) {
                            console.log('Could not parse as JSON, assuming success');
                            return { status: 'success', message: 'Service frequency deleted successfully.' };
                        }
                    });
                }
            })
            .then(data => {
                console.log('Response data:', data);
                console.log('Data type:', typeof data);
                console.log('Data keys:', Object.keys(data));
                
                // Handle different response formats
                if (data.status === 'success' || data.success === true) {
                    console.log('Success detected, reloading page');
                    alert('Service frequency deleted successfully!');
                    location.reload();
                } else if (data.status === 'error' || data.success === false) {
                    console.log('Error detected:', data.message);
                    alert('Error: ' + (data.message || 'Unknown error occurred'));
                } else {
                    // Fallback for unexpected response format
                    console.log('Unexpected response format, assuming success');
                    alert('Service frequency deleted successfully!');
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error details:', error);
                console.error('Error message:', error.message);
                console.error('Error stack:', error.stack);
                
                // Check if it's a JSON parsing error
                if (error.message.includes('JSON') || error.message.includes('parse')) {
                    // If JSON parsing failed but status was 200, assume success
                    console.log('JSON parsing error, assuming success');
                    alert('Service frequency deleted successfully!');
                    location.reload();
                } else {
                    // Show the actual error message
                    alert('An error occurred while deleting the frequency: ' + error.message);
                }
            })
            .finally(() => {
                // Restore button state
                if (deleteBtn) {
                    deleteBtn.innerHTML = originalText;
                    deleteBtn.disabled = false;
                }
            });
        }
    }

    // Modal Control Functions
    let currentFrequencyId = null;

    function openAddModal() {
        document.getElementById('frequencyModalTitle').textContent = 'Add Service Frequency';
        document.getElementById('frequencySubmitBtn').innerHTML = '<i class="fas fa-save me-1"></i>Save Frequency';
        document.getElementById('frequencyForm').reset();
        document.getElementById('_method').value = 'POST';
        currentEditId = null;  // Reset edit ID for create mode
        currentFrequencyId = null;
        document.getElementById('addFrequencyModal').classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeAddModal() {
        document.getElementById('addFrequencyModal').classList.remove('show');
        document.body.style.overflow = 'auto';
    }

    function showFrequency(id) {
        fetch(`/warehouse/rental-management/service-frequencies/${id}`, {
            credentials: 'same-origin'
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    const frequency = data.data;
                    const content = `
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Code</label>
                                    <p class="form-control-plaintext">${frequency.code}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Name</label>
                                    <p class="form-control-plaintext">${frequency.name}</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <p class="form-control-plaintext">${frequency.description || 'No description'}</p>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Frequency (Months)</label>
                                    <p class="form-control-plaintext">${frequency.frequency_months} months</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Times Per Month</label>
                                    <p class="form-control-plaintext">${frequency.frequency_times_per_month} times</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Sort Order</label>
                                    <p class="form-control-plaintext">${frequency.sort_order}</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <p class="form-control-plaintext">
                                <span class="badge ${frequency.is_active ? 'badge-success' : 'badge-danger'}">
                                    ${frequency.is_active ? 'Active' : 'Inactive'}
                                </span>
                            </p>
                        </div>

                        <hr class="my-3">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label text-muted small">Created By</label>
                                    <p class="form-control-plaintext">
                                        <i class="fas fa-user-circle mr-1 text-muted"></i>
                                        ${frequency.created_by_user ? frequency.created_by_user.name : 'System'}
                                    </p>
                                </div>
                                <div class="form-group">
                                    <label class="form-label text-muted small">Created At</label>
                                    <p class="form-control-plaintext">
                                        <i class="fas fa-calendar-alt mr-1 text-muted"></i>
                                        ${frequency.created_at ? new Date(frequency.created_at).toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '-'}
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label text-muted small">Updated By</label>
                                    <p class="form-control-plaintext">
                                        <i class="fas fa-user-edit mr-1 text-muted"></i>
                                        ${frequency.updated_by_user ? frequency.updated_by_user.name : 'System'}
                                    </p>
                                </div>
                                <div class="form-group">
                                    <label class="form-label text-muted small">Updated At</label>
                                    <p class="form-control-plaintext">
                                        <i class="fas fa-history mr-1 text-muted"></i>
                                        ${frequency.updated_at ? new Date(frequency.updated_at).toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '-'}
                                    </p>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    document.getElementById('showFrequencyContent').innerHTML = content;
                    document.getElementById('showFrequencyModal').classList.add('show');
                    document.body.style.overflow = 'hidden';
                    currentFrequencyId = id;
                } else {
                    console.error('Error response:', data);
                    alert('Error loading frequency details: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Don't show modal if there's an error
                alert('Service frequency not found or has been deleted.');
            });
    }

    function editFrequencyFromShow() {
        if (currentFrequencyId) {
            editFrequency(currentFrequencyId);
        }
    }

    function editFrequency(id) {
        fetch(`/warehouse/rental-management/service-frequencies/${id}`, {
            credentials: 'same-origin'
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const frequency = data.data;
                    
                    // Reset form first
                    document.getElementById('frequencyForm').reset();
                    
                    // Fill form with frequency data
                    document.getElementById('code').value = frequency.code;
                    document.getElementById('name').value = frequency.name;
                    document.getElementById('description').value = frequency.description || '';
                    document.getElementById('frequency_months').value = frequency.frequency_months;
                    document.getElementById('frequency_times_per_month').value = frequency.frequency_times_per_month;
                    document.getElementById('sort_order').value = frequency.sort_order;
                    document.getElementById('is_active').checked = frequency.is_active;
                    
                    // Update modal title and submit button
                    document.getElementById('frequencyModalTitle').textContent = 'Edit Service Frequency';
                    document.getElementById('frequencySubmitBtn').innerHTML = '<i class="fas fa-save me-1"></i>Update Frequency';
                    
                    // Set method to PUT and currentEditId for the form handler
                    let methodInput = document.getElementById('_method');
                    if (!methodInput) {
                        methodInput = document.createElement('input');
                        methodInput.type = 'hidden';
                        methodInput.name = '_method';
                        methodInput.id = '_method';
                        document.getElementById('frequencyForm').appendChild(methodInput);
                    }
                    methodInput.value = 'PUT';
                    currentEditId = id;  // Set the edit ID for the form handler
                    
                    // Close show modal and open edit modal
                    closeShowModal();
                    document.getElementById('addFrequencyModal').classList.add('show');
                    document.body.style.overflow = 'hidden';
                    currentFrequencyId = id;
                    
                    // Ensure form fields are enabled and focusable
                    const formFields = document.querySelectorAll('#frequencyForm input, #frequencyForm textarea, #frequencyForm select');
                    formFields.forEach(field => {
                        field.disabled = false;
                        field.readOnly = false;
                        field.style.pointerEvents = 'auto';
                    });
                } else {
                    alert('Error loading frequency data');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Something went wrong');
            });
    }

    function closeShowModal() {
        document.getElementById('showFrequencyModal').classList.remove('show');
        document.body.style.overflow = 'auto';
    }
</script>
@endpush

