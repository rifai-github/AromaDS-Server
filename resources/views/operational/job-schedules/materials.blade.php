@extends('layouts.app')

@section('title', 'Job Materials')
@section('breadcrumb', 'Home / Operational / Job Schedules / Materials')

@section('content')
<div class="flex flex-col w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">
        
        <!-- Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Job Materials</h1>
                <span class="ml-2 text-sm text-gray-500">Job #{{ $jobSchedule->job_number ?? 'N/A' }}</span>
            </div>
            
            <div class="flex flex-row justify-end items-center gap-3">
                <button type="button" class="btn btn-primary" onclick="openAddMaterialModal()">
                    <i class="fas fa-plus"></i> Add Material
                </button>
                <a href="{{ route('operational.job-schedules.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Jobs
                </a>
            </div>
        </div>
        
        <!-- Job Info Section -->
        <div class="w-full bg-white p-6 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-[#214589] mb-4">Job Information</h2>
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-gray-50 p-4 rounded-lg">
                    <label class="text-sm font-medium text-gray-500 block mb-2">Job Number</label>
                    <p class="text-lg font-semibold text-gray-900">{{ $jobSchedule->job_number ?? 'N/A' }}</p>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <label class="text-sm font-medium text-gray-500 block mb-2">Job Type</label>
                    <p class="text-lg font-semibold text-gray-900">{{ ucfirst($jobSchedule->type ?? 'N/A') }}</p>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <label class="text-sm font-medium text-gray-500 block mb-2">Customer</label>
                    <p class="text-lg font-semibold text-gray-900">{{ $jobSchedule->company_name ?? 'N/A' }}</p>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <label class="text-sm font-medium text-gray-500 block mb-2">Building</label>
                    <p class="text-lg font-semibold text-gray-900">{{ $jobSchedule->building->nama_gedung ?? 'N/A' }}</p>
                </div>
            </div>
        </div>
        
        <!-- Materials Section -->
        <div class="w-full bg-white rounded-b-[10px] p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-lg font-semibold text-[#214589]">Materials Required & Issued</h2>
                <div class="text-sm text-gray-500">
                    <span id="materialsCount">Loading...</span> materials
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full table-auto border-collapse">
                    <thead>
                        <tr class="bg-[#214589] text-white">
                            <th class="px-6 py-4 text-left font-semibold">Product</th>
                            <th class="px-6 py-4 text-center font-semibold">Required</th>
                            <th class="px-6 py-4 text-center font-semibold">Issued</th>
                            <th class="px-6 py-4 text-center font-semibold">Used</th>
                            <th class="px-6 py-4 text-center font-semibold">Status</th>
                            <th class="px-6 py-4 text-center font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="materialsTable" class="bg-white">
                        <tr>
                            <td colspan="6" class="text-center py-12">
                                <div class="flex flex-col items-center">
                                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[#214589] mb-4"></div>
                                    <p class="text-gray-500">Loading materials...</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div id="modal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Modal Title</h2>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <div class="modal-body" id="modalBody">
            <!-- Modal content will be loaded here -->
        </div>
        <div class="modal-footer" id="modalFooter">
            <!-- Modal footer will be loaded here -->
        </div>
    </div>
</div>

<script>
    let jobScheduleId = {{ $jobSchedule->id }};
    
    // Load materials on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadMaterials();
    });
    
    function loadMaterials() {
        fetch(`/operational/job-schedules/${jobScheduleId}/materials/api`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                const tbody = document.getElementById('materialsTable');
                const countElement = document.getElementById('materialsCount');
                
                if (data.data && data.data.length > 0) {
                    // Update count
                    if (countElement) {
                        countElement.textContent = data.data.length;
                    }
                    
                    tbody.innerHTML = data.data.map(material => `
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div>
                                    <div class="font-semibold text-gray-900">${material.master_product ? material.master_product.name : 'Unknown Product'}</div>
                                    <div class="text-sm text-gray-500">${material.notes || 'No notes'}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center font-semibold">${material.required_quantity || 0}</td>
                            <td class="px-6 py-4 text-center font-semibold">${material.issued_quantity || 0}</td>
                            <td class="px-6 py-4 text-center font-semibold">${material.used_quantity || 0}</td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-3 py-1 rounded-full text-xs font-medium ${getStatusClass(material)}">
                                    ${getStatusText(material)}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex justify-center space-x-2">
                                    <button onclick="issueMaterial(${material.id})" class="btn btn-sm btn-primary">Issue</button>
                                    <button onclick="returnMaterial(${material.id})" class="btn btn-sm btn-secondary">Return</button>
                                </div>
                            </td>
                        </tr>
                    `).join('');
                } else {
                    if (countElement) {
                        countElement.textContent = '0';
                    }
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-12 text-gray-500">No materials found</td></tr>';
                }
            })
            .catch(error => {
                console.error('Error loading materials:', error);
                document.getElementById('materialsTable').innerHTML = '<tr><td colspan="6" class="text-center py-8 text-red-500">Error loading materials</td></tr>';
            });
    }
    
    function getStatusClass(material) {
        const required = material.required_quantity || 0;
        const issued = material.issued_quantity || 0;
        const used = material.used_quantity || 0;
        
        if (issued === 0) return 'bg-gray-100 text-gray-800';
        if (used >= required) return 'bg-green-100 text-green-800';
        if (issued >= required) return 'bg-blue-100 text-blue-800';
        return 'bg-yellow-100 text-yellow-800';
    }
    
    function getStatusText(material) {
        const required = material.required_quantity || 0;
        const issued = material.issued_quantity || 0;
        const used = material.used_quantity || 0;
        
        if (issued === 0) return 'NOT ISSUED';
        if (used >= required) return 'COMPLETED';
        if (issued >= required) return 'ISSUED';
        return 'PARTIAL';
    }
    
    function openAddMaterialModal() {
        openModal('Add Material Requirement');
        document.getElementById('modalBody').innerHTML = `
            <form id="addMaterialForm" onsubmit="addMaterial(event)">
                <div class="form-group">
                    <label class="form-label">Product *</label>
                    <select name="master_product_id" class="form-input" required>
                        <option value="">Select Product</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Required Quantity *</label>
                    <input type="number" name="required_quantity" class="form-input" min="1" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-input" rows="3" placeholder="Optional notes for this material"></textarea>
                </div>
            </form>
        `;
        
        document.getElementById('modalFooter').innerHTML = `
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
            <button type="submit" form="addMaterialForm" class="btn btn-primary">Add Material</button>
        `;
        
        loadProducts();
    }
    
    // MOM9: Get job type for filtering products
    const jobType = '{{ $jobSchedule->type ?? "install" }}';
    // Get quotation_id from job schedule or from job advice
    const quotationId = '{{ $jobSchedule->quotation_id ?? ($jobSchedule->jobAdvice->quotation_id ?? "") }}';
    
    function loadProducts() {
        // MOM9: Filter products based on job type
        // install -> is_unit = true (diffusers, devices)
        // service -> is_unit = false (refills, oils, batteries, cleaners)
        // install_free -> products from quotation rental list
        let apiUrl = '/api/master-products';
        
        if (jobType === 'install_free' && quotationId) {
            // For Install Free, load products from quotation rental list
            apiUrl = `/api/quotations/${quotationId}/rental-products`;
        } else if (jobType === 'install') {
            apiUrl = '/api/master-products?job_type=install';
        } else if (jobType === 'service') {
            apiUrl = '/api/master-products?job_type=service';
        }
        
        console.log('Loading products for job type:', jobType, 'from:', apiUrl);
        
        fetch(apiUrl)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                const select = document.querySelector('select[name="master_product_id"]');
                if (select) {
                    select.innerHTML = '<option value="">Select Product</option>';
                    const products = data.data || data || [];
                    if (Array.isArray(products)) {
                        products.forEach(product => {
                            const productName = product.name || product.product_name || 'Unknown Product';
                            const productType = product.productType?.name || product.product_type_name || '';
                            const displayName = productType ? `${productName} (${productType})` : productName;
                            select.innerHTML += `<option value="${product.id}">${displayName}</option>`;
                        });
                    }
                    
                    // Show message if no products found
                    if (products.length === 0) {
                        select.innerHTML = '<option value="">No products available for this job type</option>';
                    }
                }
            })
            .catch(error => {
                console.error('Error loading products:', error);
                const select = document.querySelector('select[name="master_product_id"]');
                if (select) {
                    select.innerHTML = '<option value="">Error loading products</option>';
                }
            });
    }
    
    function addMaterial(event) {
        event.preventDefault();
        const formData = new FormData(event.target);
        
        fetch(`/operational/job-schedules/${jobScheduleId}/materials`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                master_product_id: formData.get('master_product_id'),
                required_quantity: formData.get('required_quantity'),
                notes: formData.get('notes')
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showNotification('Material added successfully!', 'success');
                closeModal();
                loadMaterials();
            } else {
                showNotification(data.message || 'Error adding material', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error adding material', 'error');
        });
    }
    
    function issueMaterial(materialId) {
        const quantity = prompt('Enter quantity to issue:');
        if (quantity && !isNaN(quantity)) {
            fetch(`/operational/job-materials/${materialId}/issue`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    issued_quantity: parseInt(quantity),
                    received_by: 1 // This should be the current user ID
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showNotification('Material issued successfully!', 'success');
                    loadMaterials();
                } else {
                    showNotification(data.message || 'Error issuing material', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error issuing material', 'error');
            });
        }
    }
    
    function returnMaterial(materialId) {
        const quantity = prompt('Enter quantity to return:');
        if (quantity && !isNaN(quantity)) {
            fetch(`/operational/job-materials/${materialId}/return`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    returned_quantity: parseInt(quantity),
                    notes: 'Returned via web interface'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showNotification('Material returned successfully!', 'success');
                    loadMaterials();
                } else {
                    showNotification(data.message || 'Error returning material', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error returning material', 'error');
            });
        }
    }
    
    // Modal functions
    function openModal(title) {
        document.getElementById('modalTitle').textContent = title;
        document.getElementById('modal').style.display = 'block';
    }
    
    function closeModal() {
        document.getElementById('modal').style.display = 'none';
    }
    
    function showNotification(message, type) {
        // Simple notification - you can enhance this
        alert(message);
    }
</script>

<style>
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
    
    .btn-secondary {
        background-color: #f3f4f6;
        color: #6b7280;
        border: 1px solid #d1d5db;
    }
    
    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
    }
    
    .form-group {
        margin-bottom: 1rem;
    }
    
    .form-label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: #374151;
    }
    
    .form-input {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        font-size: 0.875rem;
    }
    
    .modal {
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
    }
    
    .modal-content {
        background-color: white;
        margin: 5% auto;
        padding: 0;
        border-radius: 8px;
        width: 90%;
        max-width: 500px;
    }
    
    .modal-header {
        padding: 1rem;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .modal-body {
        padding: 1rem;
    }
    
    .modal-footer {
        padding: 1rem;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
    }
    
    .close {
        color: #aaa;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }
    
    .close:hover {
        color: black;
    }
    
    .spinner {
        border: 4px solid #f3f3f3;
        border-top: 4px solid #3498db;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        animation: spin 1s linear infinite;
        margin: 0 auto;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>
@endsection
