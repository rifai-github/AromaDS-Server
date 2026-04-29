@extends('layouts.app')

@section('title', 'Master Company')
@section('breadcrumb', 'Home / Company / Master Company')

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
    background-color: #214589 !important;
    color: white !important;
    border-color: #214589 !important;
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

/* Responsive Table */
    .responsive-table {
        min-width: 1200px;
    table-layout: auto;
        width: 100%;
        border-collapse: collapse;
    margin: 0;
    padding: 0;
    height: auto;
    }
    
    .responsive-table th,
    .responsive-table td {
        padding: 12px 8px;
        text-align: left;
        border-bottom: 1px solid #e5e7eb;
        white-space: nowrap;
        font-size: 14px;
        line-height: 1.4;
    }
    
    .responsive-table th {
    background-color: #214589;
        color: white;
        font-weight: 600;
        font-size: 13px;
        position: sticky;
        top: 0;
        z-index: 10;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: visible;
    text-overflow: unset;
}

.responsive-table td {
    overflow: hidden;
    text-overflow: ellipsis;
    }
    
    .responsive-table tbody tr:hover {
        background-color: #eff6ff;
        transition: background-color 0.2s ease;
    }
    
    .responsive-table tbody tr {
        cursor: pointer;
    }
    
/* Column widths */
.responsive-table th:nth-child(1), .responsive-table td:nth-child(1) { width: 50px; min-width: 50px; }
.responsive-table th:nth-child(2), .responsive-table td:nth-child(2) { width: 120px; min-width: 120px; }
.responsive-table th:nth-child(3), .responsive-table td:nth-child(3) { width: 200px; min-width: 200px; }
.responsive-table th:nth-child(4), .responsive-table td:nth-child(4) { width: 100px; min-width: 100px; }
.responsive-table th:nth-child(5), .responsive-table td:nth-child(5) { width: 100px; min-width: 100px; }
.responsive-table th:nth-child(6), .responsive-table td:nth-child(6) { width: 200px; min-width: 200px; }
.responsive-table th:nth-child(7), .responsive-table td:nth-child(7) { width: 150px; min-width: 150px; }
.responsive-table th:nth-child(8), .responsive-table td:nth-child(8) { width: 200px; min-width: 200px; }
.responsive-table th:nth-child(9), .responsive-table td:nth-child(9) { width: 120px; min-width: 120px; }

/* Pagination CSS removed - only one company exists */

.page-dropdown-container {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-left: 1rem;
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
    width: 800px;
        overflow: hidden;
    position: relative;
    display: flex;
    flex-direction: column;
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
    max-height: calc(90vh - 180px);
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
    z-index: 10;
    border-radius: 0 0 12px 12px;
}

/* Form Input Styling */
input[type="date"], input[type="text"], input[type="email"], input[type="number"], select, textarea {
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
    background-color: white;
    width: 100%;
}

input[type="date"]:focus, input[type="text"]:focus, input[type="email"]:focus, input[type="number"]:focus, select:focus, textarea:focus {
    outline: none;
    border-color: #214589;
    box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
}

    .form-group {
    margin-bottom: 20px;
    }
    
.form-group label {
        display: block;
    margin-bottom: 5px;
        font-weight: 500;
        color: #374151;
    }
    
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

.section {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.section-title {
    font-size: 16px;
    font-weight: 600;
    color: #214589;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #e5e7eb;
}

/* Status Badge */
    .status-badge {
        padding: 4px 8px;
        border-radius: 12px;
    font-size: 12px;
        font-weight: 500;
    }
    
    .status-active {
        background-color: #d1fae5;
        color: #065f46;
    }
    
    .status-inactive {
        background-color: #fee2e2;
        color: #991b1b;
    }
    
/* Mobile Responsive */
@media (max-width: 768px) {
    .responsive-table th,
    .responsive-table td {
        padding: 8px 6px;
        font-size: 12px;
    }
    
    .responsive-table {
        min-width: 1200px;
    }
    
    .pagination-controls {
        justify-content: center;
        flex-wrap: wrap;
        gap: 5px;
        max-width: 100%;
        overflow-x: hidden;
    }
    
    .flex.flex-row.justify-between {
            flex-direction: column;
        gap: 1rem;
            align-items: stretch;
        }
        
    .flex.flex-row.justify-between > div:first-child {
        width: 100%;
    }
    
    .flex.flex-row.justify-between > div:last-child {
        width: 100%;
        justify-content: flex-start;
        }
    }
</style>

<!-- Module Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
        <h1 class="text-xl font-semibold text-[#214589]">Master Company</h1>
            </div>
            
        </div>

        <!-- Controls Row -->
        <div class="flex flex-row justify-between items-center w-full p-4 bg-white">
            <div class="flex flex-row justify-start items-center w-full">
                <div class="flex flex-row justify-start items-center w-auto">
                    <div class="flex flex-row items-center w-auto">
                        <input type="checkbox" id="selectAll" class="w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer">
                        <div class="flex flex-row justify-start items-center w-full px-2">
                            <p class="text-sm font-normal text-gray-700 w-auto ml-2 cursor-pointer" onclick="document.getElementById('selectAll').click()">Select all</p>
                        </div>
                    </div>
                </div>
                
        <!-- Delete Button removed - only one company exists -->
            </div>
            
    <!-- Search and Filter -->
    <div class="flex flex-row justify-end items-center gap-4">
        <input type="text" id="searchInput" placeholder="Search by name, code, alias, NPWP, or email..." value="{{ request('search') }}" onkeyup="filterTable()" style="min-width: 200px;">
        <select id="statusFilter" onchange="filterTable()">
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
        <select id="typeFilter" onchange="filterTable()">
            <option value="">All Types</option>
            <option value="pt">PT</option>
            <option value="cv">CV</option>
            <option value="ud">UD</option>
            <option value="foundation">Foundation</option>
            <option value="government">Government</option>
            <option value="other">Other</option>
        </select>
            </div>
        </div>

<!-- Pagination Controls removed - only one company exists -->

<!-- Table Container with Horizontal Scroll -->
<div class="table-container">
    <table class="responsive-table">
        <!-- Table Header -->
        <thead>
            <tr>
                <th>
                    <input type="checkbox" id="headerSelectAll" class="w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer">
                </th>
                <th>Code</th>
                <th>Company Name</th>
                <th>Label Alias</th>
                <th>NPWP</th>
                <th>NITKU</th>
                <th>NIK</th>
                <th>Type</th>
                <th>Status</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Address</th>
                <th>Member Since</th>
                <th>Created At</th>
                <th>Created By</th>
                <th>Updated At</th>
                <th>Updated By</th>
            </tr>
        </thead>
        
        <!-- Table Body -->
        <tbody id="companiesTableBody">
            <!-- Data will be loaded here -->
        </tbody>
    </table>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">View Company</h2>
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
// Global variables
let currentPage = 1;
let totalPages = 1;
let companies = [];
let filteredCompanies = [];

// Load companies data
async function loadCompanies() {
    try {
        console.log('Loading companies...');
        const response = await fetch('{{ url("/api/companies") }}', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        console.log('Response status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('API Response:', data);
        
        if (data.status === 'success' && data.data && Array.isArray(data.data)) {
            companies = data.data;
            filteredCompanies = [...companies];
            console.log('Companies loaded:', companies.length);
            renderTable();
            // Pagination removed - only one company exists
        } else {
            console.error('Error loading companies:', data.message || 'No data received');
            // Show empty state
            companies = [];
            filteredCompanies = [];
            renderTable();
            // Pagination removed - only one company exists
        }
    } catch (error) {
        console.error('Error loading companies:', error);
        // Show empty state
        companies = [];
        filteredCompanies = [];
        renderTable();
        updatePagination();
    }
}

// Render table
function renderTable() {
    const tbody = document.getElementById('companiesTableBody');
    tbody.innerHTML = '';
    
    if (filteredCompanies.length === 0) {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td colspan="17" class="p-8 text-center">
                <p class="text-lg text-gray-600">No companies found</p>
                <p class="text-sm text-gray-500 mt-2">No companies available</p>
            </td>
        `;
        tbody.appendChild(row);
        return;
    }
    
    const startIndex = (currentPage - 1) * 10;
    const endIndex = Math.min(startIndex + 10, filteredCompanies.length);
    
    for (let i = startIndex; i < endIndex; i++) {
        const company = filteredCompanies[i];
        const row = document.createElement('tr');
        
        row.innerHTML = `
            <td class="text-center">
                <input type="checkbox" class="row-checkbox w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer" value="${company.id}" onclick="event.stopPropagation()">
            </td>
            <td>${company.code || ''}</td>
            <td>${company.name}</td>
            <td>${company.label_alias || ''}</td>
            <td>${company.npwp || ''}</td>
            <td>${company.nitku || ''}</td>
            <td>${company.nik || ''}</td>
            <td>${company.company_type ? company.company_type.toUpperCase() : ''}</td>
            <td><span class="status-badge status-${company.status}">${company.status}</span></td>
            <td>${company.email || ''}</td>
            <td>${company.phone || ''}</td>
            <td>${company.address ? company.address.substring(0, 30) + '...' : ''}</td>
            <td>${company.member_since ? formatDate(company.member_since) : ''}</td>
            <td>${company.created_at ? formatDate(company.created_at) : '-'}</td>
            <td>${company.created_by ? company.created_by.name : (company.created_by_name || '-')}</td>
            <td>${company.updated_at ? formatDate(company.updated_at) : '-'}</td>
            <td>${company.updated_by ? company.updated_by.name : (company.updated_by_name || '-')}</td>
        `;
        
        row.setAttribute('data-id', company.id);
        row.onclick = () => openViewModal(company.id);
        
        tbody.appendChild(row);
    }
}

// Filter table
function filterTable() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const statusFilter = document.getElementById('statusFilter').value;
    const typeFilter = document.getElementById('typeFilter').value;
    
    filteredCompanies = companies.filter(company => {
        const matchesSearch = !searchTerm || 
            company.name.toLowerCase().includes(searchTerm) ||
            (company.code && company.code.toLowerCase().includes(searchTerm)) ||
            (company.label_alias && company.label_alias.toLowerCase().includes(searchTerm)) ||
            (company.npwp && company.npwp.toLowerCase().includes(searchTerm)) ||
            (company.nik && company.nik.toLowerCase().includes(searchTerm)) ||
            (company.nitku && company.nitku.toLowerCase().includes(searchTerm)) ||
            (company.email && company.email.toLowerCase().includes(searchTerm));
        
        const matchesStatus = !statusFilter || company.status === statusFilter;
        const matchesType = !typeFilter || company.company_type === typeFilter;
        
        return matchesSearch && matchesStatus && matchesType;
    });
    
    currentPage = 1;
    renderTable();
    // Pagination removed - only one company exists
}

// Update pagination
// Pagination update function removed - only one company exists

// Update page numbers
// Page numbers function removed - only one company exists

// Change page
// Pagination function removed - only one company exists

// Format date
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
}

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
    openModal('Create New Company');
    document.getElementById('modalBody').innerHTML = `
        <form id="form" onsubmit="submitForm(event)">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="section">
                    <div class="section-title">Basic Information</div>
                    <div class="form-group">
                        <label for="name">Company Name *</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="code">Company Code</label>
                        <input type="text" id="code" name="code" placeholder="Auto-generated if empty">
                    </div>
                    <div class="form-group">
                        <label for="npwp">NPWP</label>
                        <input type="text" id="npwp" name="npwp" placeholder="Nomor Pokok Wajib Pajak">
                    </div>
                    <div class="form-group">
                        <label for="nik">NIK</label>
                        <input type="text" id="nik" name="nik" placeholder="Nomor Induk Kependudukan">
                    </div>
                    <div class="form-group">
                        <label for="nitku">NITKU</label>
                        <input type="text" id="nitku" name="nitku" placeholder="Nomor Identitas Tempat Kegiatan Usaha">
                    </div>
                    <div class="form-group">
                        <label for="label_alias">Label Alias</label>
                        <input type="text" id="label_alias" name="label_alias" placeholder="Company label or alias">
                    </div>
                    <div class="form-group">
                        <label for="company_type">Company Type *</label>
                        <select id="company_type" name="company_type" required>
                            <option value="">Select Type</option>
                            <option value="pt" selected>PT</option>
                            <option value="cv">CV</option>
                            <option value="ud">UD</option>
                            <option value="foundation">Foundation</option>
                            <option value="government">Government</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="section">
                    <div class="section-title">Status & Contact</div>
                <div class="form-group">
                    <label for="status">Status *</label>
                    <select id="status" name="status" required>
                        <option value="">Select Status</option>
                        <option value="active" selected>Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            
            <div class="section">
                <div class="section-title">Contact Information</div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="company@example.com">
                </div>
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="text" id="phone" name="phone" placeholder="+62 21 1234 5678">
                </div>
            </div>
            
            <div class="section">
                <div class="section-title">Address Information</div>
                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" rows="3" placeholder="Company address"></textarea>
                </div>
            </div>
            
            <div class="section">
                <div class="section-title">Footer Settings (for Reports)</div>
                <div class="form-group">
                    <label for="footer_line_1">Footer Line 1</label>
                    <input type="text" id="footer_line_1" name="footer_line_1" placeholder="First line of footer">
                </div>
                <div class="form-group">
                    <label for="footer_line_2">Footer Line 2</label>
                    <input type="text" id="footer_line_2" name="footer_line_2" placeholder="Second line of footer">
                </div>
                <div class="form-group">
                    <label for="footer_line_3">Footer Line 3</label>
                    <input type="text" id="footer_line_3" name="footer_line_3" placeholder="Third line of footer">
                </div>
            </div>
            
        </form>
    `;
    
    document.getElementById('modalFooter').innerHTML = `
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="submitFormFromFooter()">Save Company</button>
    `;
}

function openViewModal(id) {
    openModal('View Company');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Loading...</p></div>';
    
    const company = companies.find(c => c.id === id);
    if (company) {
        document.getElementById('modalBody').innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="section">
                    <div class="section-title">Basic Information</div>
                <div class="form-group">
                        <label>Company Name</label>
                        <div style="padding: 8px 12px; background: #f9fafb; border-radius: 6px;">${company.name}</div>
                </div>
                <div class="form-group">
                        <label>Company Code</label>
                        <div style="padding: 8px 12px; background: #f9fafb; border-radius: 6px;">${company.code || ''}</div>
                </div>
                <div class="form-group">
                        <label>Label Alias</label>
                        <div style="padding: 8px 12px; background: #f9fafb; border-radius: 6px;">${company.label_alias || ''}</div>
                </div>
                <div class="form-group">
                        <label>NPWP</label>
                        <div style="padding: 8px 12px; background: #f9fafb; border-radius: 6px;">${company.npwp || '-'}</div>
                </div>
                <div class="form-group">
                        <label>NITKU</label>
                        <div style="padding: 8px 12px; background: #f9fafb; border-radius: 6px;">${company.nitku || '-'}</div>
                </div>
                <div class="form-group">
                        <label>NIK</label>
                        <div style="padding: 8px 12px; background: #f9fafb; border-radius: 6px;">${company.nik || '-'}</div>
                </div>
                <div class="form-group">
                        <label>Company Type</label>
                        <div style="padding: 8px 12px; background: #f9fafb; border-radius: 6px;">${company.company_type ? company.company_type.toUpperCase() : ''}</div>
                </div>
            </div>
            
                <div class="section">
                    <div class="section-title">Status & Contact</div>
                <div class="form-group">
                        <label>Status</label>
                        <div style="padding: 8px 12px; background: #f9fafb; border-radius: 6px;"><span class="status-badge status-${company.status}">${company.status}</span></div>
                </div>
                <div class="form-group">
                        <label>Email</label>
                        <div style="padding: 8px 12px; background: #f9fafb; border-radius: 6px;">${company.email || ''}</div>
                </div>
                <div class="form-group">
                        <label>Phone</label>
                        <div style="padding: 8px 12px; background: #f9fafb; border-radius: 6px;">${company.phone || ''}</div>
                </div>
                </div>
                </div>
            
            <div class="section">
                <div class="section-title">Additional Information</div>
                <div class="form-group">
                    <label>Address</label>
                    <div style="padding: 8px 12px; background: #f9fafb; border-radius: 6px; min-height: 60px;">${company.address || ''}</div>
                </div>
            </div>
            
            <div class="section">
                <div class="section-title">Footer Settings (for Reports)</div>
                <div class="form-group">
                    <label>Footer Line 1</label>
                    <div style="padding: 8px 12px; background: #f9fafb; border-radius: 6px;">${company.footer_line_1 || ''}</div>
                </div>
                <div class="form-group">
                    <label>Footer Line 2</label>
                    <div style="padding: 8px 12px; background: #f9fafb; border-radius: 6px;">${company.footer_line_2 || ''}</div>
                </div>
                <div class="form-group">
                    <label>Footer Line 3</label>
                    <div style="padding: 8px 12px; background: #f9fafb; border-radius: 6px;">${company.footer_line_3 || ''}</div>
                </div>
            </div>
            
        `;
        
        document.getElementById('modalFooter').innerHTML = `
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
            <button type="button" class="btn btn-primary" onclick="openEditModal(${company.id})">Edit</button>
        `;
    } else {
        document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Company not found.</div>';
    }
}

function openEditModal(id) {
    const company = companies.find(c => c.id === id);
    if (!company) return;
    
    // Debug: Log company data
    console.log('Company data for edit:', company);
    console.log('Company name:', company.name);
    
    openModal('Edit Company');
    
    // Debug: Log the HTML template
    const htmlTemplate = `
        <form id="form" onsubmit="submitForm(event, ${company.id})">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="section">
                    <div class="section-title">Basic Information</div>
                                <div class="form-group">
                        <label for="name">Company Name *</label>
                        <input type="text" id="name" name="name" value="${company.name}" required>
                                </div>
                                <div class="form-group">
                        <label for="code">Company Code</label>
                        <input type="text" id="code" name="code" value="${company.code || ''}" placeholder="Auto-generated if empty">
                                </div>
                                <div class="form-group">
                        <label for="npwp">NPWP</label>
                        <input type="text" id="npwp" name="npwp" value="${company.npwp || ''}" placeholder="Nomor Pokok Wajib Pajak">
                                </div>
                                <div class="form-group">
                        <label for="nitku">NITKU</label>
                        <input type="text" id="nitku" name="nitku" value="${company.nitku || ''}" placeholder="Nomor Identitas Tempat Kegiatan Usaha">
                                </div>
                                <div class="form-group">
                        <label for="nik">NIK</label>
                        <input type="text" id="nik" name="nik" value="${company.nik || ''}" placeholder="Nomor Induk Kependudukan">
                                </div>
                                <div class="form-group">
                        <label for="label_alias">Label Alias</label>
                        <input type="text" id="label_alias" name="label_alias" value="${company.label_alias || ''}" placeholder="Company label or alias">
                                </div>
                                <div class="form-group">
                        <label for="company_type">Company Type *</label>
                        <select id="company_type" name="company_type" required>
                            <option value="">Select Type</option>
                                        <option value="pt" ${company.company_type === 'pt' ? 'selected' : ''}>PT</option>
                                        <option value="cv" ${company.company_type === 'cv' ? 'selected' : ''}>CV</option>
                                        <option value="ud" ${company.company_type === 'ud' ? 'selected' : ''}>UD</option>
                                        <option value="foundation" ${company.company_type === 'foundation' ? 'selected' : ''}>Foundation</option>
                                        <option value="government" ${company.company_type === 'government' ? 'selected' : ''}>Government</option>
                                        <option value="other" ${company.company_type === 'other' ? 'selected' : ''}>Other</option>
                                    </select>
                            </div>
                        </div>
                        
                <div class="section">
                    <div class="section-title">Status & Contact</div>
                            <div class="form-group">
                        <label for="status">Status *</label>
                        <select id="status" name="status" required>
                            <option value="">Select Status</option>
                            <option value="active" ${company.status === 'active' ? 'selected' : ''}>Active</option>
                            <option value="inactive" ${company.status === 'inactive' ? 'selected' : ''}>Inactive</option>
                        </select>
                            </div>
                            </div>
            
            <div class="section">
                <div class="section-title">Contact Information</div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="${company.email || ''}" placeholder="company@example.com">
                </div>
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="text" id="phone" name="phone" value="${company.phone || ''}" placeholder="+62 21 1234 5678">
                </div>
            </div>
            
            <div class="section">
                <div class="section-title">Address Information</div>
                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" rows="3" placeholder="Company address">${company.address || ''}</textarea>
                </div>
            </div>
            
            <div class="section">
                <div class="section-title">Footer Settings (for Reports)</div>
                <div class="form-group">
                    <label for="footer_line_1">Footer Line 1</label>
                    <input type="text" id="footer_line_1" name="footer_line_1" value="${company.footer_line_1 || ''}" placeholder="First line of footer">
                </div>
                <div class="form-group">
                    <label for="footer_line_2">Footer Line 2</label>
                    <input type="text" id="footer_line_2" name="footer_line_2" value="${company.footer_line_2 || ''}" placeholder="Second line of footer">
                </div>
                <div class="form-group">
                    <label for="footer_line_3">Footer Line 3</label>
                    <input type="text" id="footer_line_3" name="footer_line_3" value="${company.footer_line_3 || ''}" placeholder="Third line of footer">
                </div>
            </div>
            
        </form>
    `;
    
    // Debug: Log the rendered HTML
    console.log('Rendered HTML template:', htmlTemplate);
    
    document.getElementById('modalBody').innerHTML = htmlTemplate;
    
    document.getElementById('modalFooter').innerHTML = `
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="submitFormFromFooter(${company.id})">Update Company</button>
    `;
}

function submitFormFromFooter(id = null) {
    const form = document.getElementById('form');
    if (form) {
        // Create a proper event object with the form as target
        const event = {
            preventDefault: () => {},
            target: form
        };
        submitForm(event, id);
    }
}

function submitForm(event, id = null) {
    event.preventDefault();
    
    // Get form element explicitly
    const form = document.getElementById('form');
    console.log('Form element:', form);
    
    if (!form) {
        console.error('Form element not found!');
        return;
    }
    
    // Debug: Check event.target if it exists
    if (event.target) {
        console.log('Event target:', event.target);
        console.log('Event target tag name:', event.target.tagName);
        console.log('Event target id:', event.target.id);
    } else {
        console.log('Event target is undefined, using form element directly');
    }
    
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    // Debug: Log all form data
    console.log('Form data entries:');
    for (let [key, value] of formData.entries()) {
        console.log(`${key}: ${value}`);
    }
    
    // Handle checkboxes - convert to boolean
    data.is_active = data.is_active === '1' || data.is_active === 'true' || data.is_active === true;
    
    // Ensure all required fields are present
    if (!data.company_type) {
        data.company_type = 'pt'; // Default value
    }
    if (!data.status) {
        data.status = 'active'; // Default value
    }
    
    // Debug: Check if name field is missing
    if (!data.name) {
        console.error('Name field is missing from form data!');
        console.log('Available fields:', Object.keys(data));
    }
    
    const url = id ? `/api/companies/${id}` : '/api/companies';
    const method = id ? 'PUT' : 'POST';
    
    // Add CSRF token
    data._token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    data._method = method;
    
    console.log('Sending data:', data);
    console.log('URL:', url);
    console.log('Method:', method);
    
    fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        // Check if response is JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
                console.log('Non-JSON response:', text);
                throw new Error('Server returned non-JSON response: ' + text.substring(0, 100));
            });
        }
        
        return response.json();
    })
    .then(result => {
        console.log('Response data:', result);
        if (result.status === 'success') {
            closeModal();
            const action = id ? 'updated' : 'created';
            showSuccessModal(`The company has been successfully ${action}.`);
        } else {
            showErrorModal(result.message || 'Something went wrong');
        }
    })
    .catch(error => {
        console.error('Detailed Error:', error);
        console.error('Error message:', error.message);
        console.error('Error stack:', error.stack);
        
        // Store form data for retry
        lastFormData = data;
        lastMethod = method;
        lastUrl = url;
        
        showErrorModal(error.message);
    });
}

// Delete selected function
// Delete function removed - only one company exists

// Select All functionality
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
    document.getElementById('headerSelectAll').checked = this.checked;
});

document.getElementById('headerSelectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
    document.getElementById('selectAll').checked = this.checked;
});

// Event listeners
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});

// Click outside to close modal
document.getElementById('modalOverlay').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});

// Add CSS for loading spinner and modals
const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    /* Success Modal Styles */
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
    
    .success-icon-container {
        margin-bottom: 24px;
        display: flex;
        justify-content: center;
    }
    
    .success-icon {
        width: 80px;
        height: 80px;
        color: #10b981;
    }
    
    .success-modal-title {
        font-size: 24px;
        font-weight: 700;
        color: #1e40af;
        margin: 0 0 16px 0;
        line-height: 1.2;
    }
    
    .success-modal-description {
        font-size: 16px;
        color: #6b7280;
        margin: 0;
        line-height: 1.5;
        max-width: 400px;
        margin-left: auto;
        margin-right: auto;
    }
    
    /* Error Modal Styles */
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
    
    .error-icon-container {
        margin-bottom: 24px;
        display: flex;
        justify-content: center;
    }
    
    .error-icon {
        width: 80px;
        height: 80px;
        color: #ef4444;
    }
    
    .error-modal-title {
        font-size: 24px;
        font-weight: 700;
        color: #1e40af;
        margin: 0 0 16px 0;
        line-height: 1.2;
    }
    
    .error-modal-description {
        font-size: 16px;
        color: #6b7280;
        margin: 0 0 32px 0;
        line-height: 1.5;
        max-width: 400px;
        margin-left: auto;
        margin-right: auto;
    }
    
    .error-modal-buttons {
        display: flex;
        justify-content: center;
        gap: 16px;
    }
    
    .btn-error-close {
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
    
    .btn-error-close:hover {
        background-color: #f8fafc;
        border-color: #1e3a8a;
        color: #1e3a8a;
    }
    
    .btn-error-retry {
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
    
    .btn-error-retry:hover {
        background-color: #1e3a8a;
        border-color: #1e3a8a;
    }
    
    /* Delete Modal CSS removed - only one company exists */
`;
document.head.appendChild(style);

// Global variables for modals
let successModalTimer = null;
let lastFormData = null;
let lastMethod = null;
let lastUrl = null;

// Success Modal functions
function showSuccessModal(message) {
    const successMessage = message || 'The company has been successfully saved.';
    document.getElementById('successMessage').textContent = successMessage;
    document.getElementById('successModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
    
    // Auto close after 3 seconds
    successModalTimer = setTimeout(() => {
        closeSuccessModal();
        loadCompanies(); // Reload data
    }, 3000);
}

function closeSuccessModal() {
    if (successModalTimer) {
        clearTimeout(successModalTimer);
        successModalTimer = null;
    }
    document.getElementById('successModalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
}

// Error Modal functions
function showErrorModal(message) {
    const errorMessage = message || 'We couldn\'t save the company. Please try again.';
    document.getElementById('errorMessage').textContent = errorMessage;
    document.getElementById('errorModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeErrorModal() {
    document.getElementById('errorModalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
}

function retrySubmit() {
    closeErrorModal();
    if (lastFormData && lastMethod && lastUrl) {
        submitFormWithData(lastFormData, lastMethod, lastUrl);
    }
}

// Delete Modal functions
// Delete modal functions removed - only one company exists

// Confirm delete function removed - only one company exists

function submitFormWithData(data, method, url) {
    console.log('Retrying with data:', data);
    console.log('URL:', url);
    console.log('Method:', method);
    
    fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        // Check if response is JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
                console.log('Non-JSON response:', text);
                throw new Error('Server returned non-JSON response: ' + text.substring(0, 100));
            });
        }
        
        return response.json();
    })
    .then(result => {
        console.log('Response data:', result);
        if (result.status === 'success') {
            const action = url.includes('/api/companies/') ? 'updated' : 'created';
            showSuccessModal(`The company has been successfully ${action}.`);
        } else {
            showErrorModal(result.message || 'Something went wrong');
        }
    })
    .catch(error => {
        console.error('Detailed Error:', error);
        console.error('Error message:', error.message);
        console.error('Error stack:', error.stack);
        
        showErrorModal(error.message);
    });
}

// Event listeners for keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
        closeDeleteModal();
        closeErrorModal();
        closeSuccessModal();
    }
});

// Load data on page load
document.addEventListener('DOMContentLoaded', function() {
    loadCompanies();
});
</script>
<!-- Delete Confirmation Modal removed - only one company exists -->

<!-- Error Modal -->
<div id="errorModalOverlay" class="error-modal-overlay" onclick="closeErrorModal()">
    <div class="error-modal-container" onclick="event.stopPropagation()">
        <div class="error-icon-container">
            <svg class="error-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <h3 class="error-modal-title">Hmm... Something Went Wrong</h3>
        <p class="error-modal-description" id="errorMessage">We couldn't save the company. Please try again.</p>
        <div class="error-modal-buttons">
            <button class="btn btn-error-close" onclick="closeErrorModal()">Close</button>
            <button class="btn btn-error-retry" onclick="retrySubmit()">Try Again</button>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div id="successModalOverlay" class="success-modal-overlay" onclick="closeSuccessModal()">
    <div class="success-modal-container" onclick="event.stopPropagation()">
        <div class="success-icon-container">
            <svg class="success-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <h3 class="success-modal-title">All Set!</h3>
        <p class="success-modal-description" id="successMessage">The company has been successfully saved.</p>
    </div>
</div>

@endsection
