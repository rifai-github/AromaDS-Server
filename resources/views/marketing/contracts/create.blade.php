@extends('layouts.app')

@section('title', 'Create Contract')
@section('breadcrumb', 'Home / Marketing / Contract / Create')

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

    .btn-success {
        background-color: #10b981;
        color: white;
    }

    .btn-success:hover {
        background-color: #059669;
    }

    .btn-info {
        background-color: #3b82f6;
        color: white;
    }

    .btn-info:hover {
        background-color: #2563eb;
    }

    .btn-warning {
        background-color: #f59e0b;
        color: white;
    }

    .btn-warning:hover {
        background-color: #d97706;
    }

    /* Form Styles */
    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        display: block;
        font-size: 14px;
        font-weight: 500;
        color: #374151;
        margin-bottom: 6px;
    }

    .form-label .required {
        color: #ef4444;
    }

    .form-control {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .form-control:focus {
        outline: none;
        border-color: #214589;
        box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
    }

    .form-section {
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 1px solid #e5e7eb;
    }

    .form-section:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }

    .section-title {
        font-size: 16px;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 15px;
        padding-bottom: 8px;
        border-bottom: 2px solid #214589;
    }

    /* Card Styles */
    .card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        overflow: hidden;
    }

    .card-header {
        background-color: #f8fafc;
        border-bottom: 1px solid #e5e7eb;
        padding: 20px;
    }

    .card-body {
        padding: 20px;
    }

    .card-title {
        font-size: 18px;
        font-weight: 600;
        color: #1f2937;
        margin: 0;
    }

    .card-subtitle {
        font-size: 14px;
        color: #6b7280;
        margin: 4px 0 0 0;
    }

    /* SQ Auto-Populate Section */
    .sq-section {
        background-color: #f0f9ff;
        border: 1px solid #0ea5e9;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
    }

    .sq-section h4 {
        color: #0c4a6e;
        margin-bottom: 10px;
        font-size: 16px;
        font-weight: 600;
    }

    .sq-info {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 10px;
        margin-bottom: 15px;
    }

    .sq-info-item {
        background: white;
        padding: 8px 12px;
        border-radius: 4px;
        border: 1px solid #e0f2fe;
    }

    .sq-info-label {
        font-size: 12px;
        color: #0369a1;
        font-weight: 500;
        margin-bottom: 2px;
    }

    .sq-info-value {
        font-size: 14px;
        color: #0c4a6e;
        font-weight: 600;
    }

    /* Alert Styles */
    .alert {
        padding: 12px 16px;
        border-radius: 6px;
        margin-bottom: 20px;
        border: 1px solid;
    }

    .alert-info {
        background-color: #eff6ff;
        border-color: #bfdbfe;
        color: #1e40af;
    }

    .alert-success {
        background-color: #f0fdf4;
        border-color: #bbf7d0;
        color: #166534;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .card-body {
            padding: 15px;
        }

        .form-section {
            margin-bottom: 20px;
        }

        .sq-info {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="flex flex-col w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">
        
        <!-- Contract Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">
                    <i class="fas fa-file-contract me-2"></i>
                    Create Contract
                </h1>
            </div>
            
            <div class="flex flex-row justify-end items-center gap-2">
                <a href="{{ route('marketing.contracts.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    <span class="hidden md:inline">Back to Contracts</span>
                    <span class="md:hidden">Back</span>
                </a>
            </div>
        </div>
        
        <!-- SQ Auto-Populate Section -->
        <div class="card w-full">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-magic me-2"></i>
                    Auto-Populate from Survey Quotation (SQ)
                </h2>
                <p class="card-subtitle">Select a quotation to automatically populate contract data</p>
            </div>
            
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Select Survey Quotation <span class="required">*</span></label>
                    <select id="quotationSelect" class="form-control" onchange="loadSQData()">
                        <option value="">Choose a quotation to auto-populate...</option>
                        @foreach($quotations as $quotation)
                            <option value="{{ $quotation->id }}" 
                                    data-quotation-number="{{ $quotation->quotation_number }}"
                                    data-company-name="{{ $quotation->company_name }}"
                                    data-pic-name="{{ $quotation->pic_name }}"
                                    data-total-amount="{{ $quotation->grand_total }}">
                                {{ $quotation->quotation_number }} - {{ $quotation->company_name }} ({{ $quotation->pic_name }})
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <!-- SQ Data Display -->
                <div id="sqDataDisplay" class="sq-section" style="display: none;">
                    <h4><i class="fas fa-info-circle me-2"></i>Quotation Information</h4>
                    <div class="sq-info" id="sqInfoContent">
                        <!-- Auto-populated data will appear here -->
                    </div>
                    <div class="flex gap-2">
                        <button onclick="applySQData()" class="btn btn-success">
                            <i class="fas fa-check"></i>
                            Apply This Data
                        </button>
                        <button onclick="clearSQData()" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                            Clear Selection
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Contract Form -->
        <div class="card w-full">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-plus-circle me-2"></i>
                    Contract Information
                </h2>
                <p class="card-subtitle">Fill in the contract details</p>
            </div>
            
            <div class="card-body">
                <form action="{{ route('marketing.contracts.store') }}" method="POST" id="contractForm">
                    @csrf
                    
                    <!-- Basic Information -->
                    <div class="form-section">
                        <div class="section-title">Basic Information</div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label">Contract Number <span class="required">*</span></label>
                                <input type="text" name="contract_number" id="contractNumber" class="form-control" value="{{ $contractNumber }}" readonly>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Contract Date <span class="required">*</span></label>
                                <input type="date" name="contract_date" id="contractDate" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label">Start Date <span class="required">*</span></label>
                                <input type="date" name="start_date" id="startDate" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">End Date <span class="required">*</span></label>
                                <input type="date" name="end_date" id="endDate" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Customer Information -->
                    <div class="form-section">
                        <div class="section-title">Customer Information</div>
                        
                        <div class="form-group">
                            <label class="form-label">Company Name <span class="required">*</span></label>
                            <input type="text" name="company_name" id="companyName" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Company Address <span class="required">*</span></label>
                            <textarea name="company_address" id="companyAddress" class="form-control" rows="3" required></textarea>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label">PIC Name <span class="required">*</span></label>
                                <input type="text" name="pic_name" id="picName" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">PIC Phone</label>
                                <input type="tel" name="pic_phone" id="picPhone" class="form-control">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">PIC Email</label>
                            <input type="email" name="pic_email" id="picEmail" class="form-control">
                        </div>
                    </div>
                    
                    <!-- Contract Details -->
                    <div class="form-section">
                        <div class="section-title">Contract Details</div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label">Contract Value <span class="required">*</span></label>
                                <input type="number" name="contract_value" id="contractValue" class="form-control" step="0.01" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Payment Terms</label>
                                <select name="payment_terms" id="paymentTerms" class="form-control">
                                    <option value="">Select Payment Terms</option>
                                    @foreach($paymentTerms as $term)
                                        <option value="{{ $term->option_value }}">{{ $term->option_value }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Contract Terms</label>
                            <textarea name="contract_terms" id="contractTerms" class="form-control" rows="4"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Rental Period</label>
                            <input type="text" name="rental_period" id="rentalPeriod" class="form-control">
                        </div>
                    </div>
                    
                    <!-- Marketing Assignment -->
                    <div class="form-section">
                        <div class="section-title">Marketing Assignment</div>
                        
                        <div class="form-group">
                            <label class="form-label">Marketing Staff <span class="required">*</span></label>
                            <select name="marketing_id" id="marketingId" class="form-control" required>
                                <option value="">Select Marketing Staff</option>
                                @foreach($marketingStaff as $staff)
                                    <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select name="status" id="status" class="form-control">
                                <option value="draft">Draft</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="expired">Expired</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Contract Type</label>
                            <select name="contract_type" id="contractType" class="form-control">
                                <option value="">Select Contract Type</option>
                                @foreach($types as $type)
                                    <option value="{{ $type->option_value }}">{{ $type->option_value }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    <!-- Additional Information -->
                    <div class="form-section">
                        <div class="section-title">Additional Information</div>
                        
                        <div class="form-group">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" id="notes" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <a href="{{ route('marketing.contracts.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Create Contract
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let selectedQuotationData = null;

// Load SQ data when quotation is selected
function loadSQData() {
    const quotationSelect = document.getElementById('quotationSelect');
    const quotationId = quotationSelect.value;
    
    if (!quotationId) {
        document.getElementById('sqDataDisplay').style.display = 'none';
        return;
    }
    
    // Show loading
    document.getElementById('sqDataDisplay').style.display = 'block';
    document.getElementById('sqInfoContent').innerHTML = '<div class="text-center">Loading quotation data...</div>';
    
    // Fetch quotation data
    fetch(`/marketing/contracts/get-latest-sq?quotation_id=${quotationId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                selectedQuotationData = data.data;
                displaySQData(data.data);
            } else {
                alert('Error: ' + data.message);
                document.getElementById('sqDataDisplay').style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to load quotation data');
            document.getElementById('sqDataDisplay').style.display = 'none';
        });
}

// Display SQ data in the info section
function displaySQData(data) {
    const sqInfoContent = document.getElementById('sqInfoContent');
    
    sqInfoContent.innerHTML = `
        <div class="sq-info-item">
            <div class="sq-info-label">Quotation Number</div>
            <div class="sq-info-value">${data.quotation_data.quotation_number}</div>
        </div>
        <div class="sq-info-item">
            <div class="sq-info-label">Company Name</div>
            <div class="sq-info-value">${data.customer_name}</div>
        </div>
        <div class="sq-info-item">
            <div class="sq-info-label">PIC Name</div>
            <div class="sq-info-value">${data.pic_name}</div>
        </div>
        <div class="sq-info-item">
            <div class="sq-info-label">Contract Value</div>
            <div class="sq-info-value">Rp ${parseFloat(data.contract_value).toLocaleString('id-ID')}</div>
        </div>
        <div class="sq-info-item">
            <div class="sq-info-label">Payment Terms</div>
            <div class="sq-info-value">${data.payment_terms || 'Not specified'}</div>
        </div>
        <div class="sq-info-item">
            <div class="sq-info-label">Rental Period</div>
            <div class="sq-info-value">${data.rental_period || 'Not specified'}</div>
        </div>
    `;
}

// Apply SQ data to form
function applySQData() {
    if (!selectedQuotationData) {
        alert('No quotation data selected');
        return;
    }
    
    const data = selectedQuotationData;
    
    // Populate form fields
    document.getElementById('companyName').value = data.customer_name || '';
    document.getElementById('companyAddress').value = data.customer_address || '';
    document.getElementById('picName').value = data.pic_name || '';
    document.getElementById('picPhone').value = data.pic_phone || '';
    document.getElementById('picEmail').value = data.pic_email || '';
    document.getElementById('contractValue').value = data.contract_value || '';
    document.getElementById('paymentTerms').value = data.payment_terms || '';
    document.getElementById('contractTerms').value = data.contract_terms || '';
    document.getElementById('rentalPeriod').value = data.rental_period || '';
    document.getElementById('marketingId').value = data.marketing_id || '';
    
    // Show success message
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-success';
    alertDiv.innerHTML = '<i class="fas fa-check-circle me-2"></i>Quotation data has been applied to the contract form!';
    document.querySelector('.card-body').insertBefore(alertDiv, document.querySelector('.form-section'));
    
    // Remove alert after 3 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 3000);
}

// Clear SQ data selection
function clearSQData() {
    document.getElementById('quotationSelect').value = '';
    document.getElementById('sqDataDisplay').style.display = 'none';
    selectedQuotationData = null;
}

// Form validation
document.getElementById('contractForm').addEventListener('submit', function(e) {
    const requiredFields = ['company_name', 'contract_date', 'start_date', 'end_date', 'contract_value', 'marketing_id'];
    let isValid = true;
    
    requiredFields.forEach(fieldName => {
        const field = document.querySelector(`[name="${fieldName}"]`);
        if (!field.value.trim()) {
            isValid = false;
            field.style.borderColor = '#ef4444';
        } else {
            field.style.borderColor = '#d1d5db';
        }
    });
    
    if (!isValid) {
        e.preventDefault();
        alert('Please fill in all required fields.');
        return;
    }
    
    // Date validation
    const startDate = new Date(document.getElementById('startDate').value);
    const endDate = new Date(document.getElementById('endDate').value);
    
    if (startDate >= endDate) {
        e.preventDefault();
        alert('End date must be after start date.');
        return;
    }
});

// Auto-calculate end date based on rental period
document.getElementById('rentalPeriod').addEventListener('change', function() {
    const rentalPeriod = this.value;
    const startDate = document.getElementById('startDate').value;
    
    if (rentalPeriod && startDate) {
        const start = new Date(startDate);
        let endDate = new Date(start);
        
        if (rentalPeriod.includes('month')) {
            const months = parseInt(rentalPeriod);
            endDate.setMonth(start.getMonth() + months);
        } else if (rentalPeriod.includes('year')) {
            const years = parseInt(rentalPeriod);
            endDate.setFullYear(start.getFullYear() + years);
        }
        
        document.getElementById('endDate').value = endDate.toISOString().split('T')[0];
    }
});
</script>
@endsection
