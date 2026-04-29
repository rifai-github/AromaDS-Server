@extends('layouts.app')

@section('title', 'Report Alerts')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Report Alerts</h3>
                    <button type="button" class="btn btn-primary" onclick="openModal('create')">
                        <i class="fas fa-plus"></i> Create Alert
                    </button>
                </div>
                <div class="card-body">
                    <!-- Filter Section -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <select class="form-select" id="filterStatus">
                                <option value="">All Status</option>
                                <option value="active">Aktif</option>
                                <option value="inactive">Tidak Aktif</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="filterType">
                                <option value="">All Types</option>
                                <option value="email">Email</option>
                                <option value="sms">SMS</option>
                                <option value="push">Push Notification</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" class="form-control" id="searchInput" placeholder="Search alerts..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-outline-secondary" onclick="applyFilters()">
                                <i class="fas fa-search"></i> Filter
                            </button>
                        </div>
                    </div>

                    <!-- Table Container -->
                    <div class="table-container">
                        <table class="table table-striped responsive-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Report</th>
                                    <th>Condition</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Last Triggered</th>
                                </tr>
                            </thead>
                            <tbody id="alertsTableBody">
                                <!-- Data will be loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="pagination-info">
                            Showing <span id="showingFrom">0</span> to <span id="showingTo">0</span> of <span id="totalRecords">0</span> entries
                        </div>
                        <nav>
                            <ul class="pagination" id="pagination">
                                <!-- Pagination will be generated via JavaScript -->
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create/Edit Modal -->
<div class="modal-overlay" id="modalOverlay">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="modalTitle">Create Alert</h5>
            <button type="button" class="btn-close" onclick="closeModal()"></button>
        </div>
        <div class="modal-body">
            <form id="alertForm">
                @csrf
                <input type="hidden" id="alertId" name="id">
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="name" class="form-label">Alert Name *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="report_id" class="form-label">Report *</label>
                            <select class="form-select" id="report_id" name="report_id" required>
                                <option value="">Pilih Report</option>
                                <!-- Options will be loaded via AJAX -->
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="condition_field" class="form-label">Condition Field *</label>
                            <input type="text" class="form-control" id="condition_field" name="condition_field" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="condition_operator" class="form-label">Operator *</label>
                            <select class="form-select" id="condition_operator" name="condition_operator" required>
                                <option value="">Select Operator</option>
                                <option value=">">Greater Than</option>
                                <option value="<">Less Than</option>
                                <option value="=">Equals</option>
                                <option value="!=">Not Equals</option>
                                <option value=">=">Greater or Equal</option>
                                <option value="<=">Less or Equal</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="condition_value" class="form-label">Condition Value *</label>
                            <input type="text" class="form-control" id="condition_value" name="condition_value" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="notification_type" class="form-label">Notification Type *</label>
                            <select class="form-select" id="notification_type" name="notification_type" required>
                                <option value="">Select Type</option>
                                <option value="email">Email</option>
                                <option value="sms">SMS</option>
                                <option value="push">Push Notification</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="recipients" class="form-label">Recipients *</label>
                            <textarea class="form-control" id="recipients" name="recipients" rows="3" required placeholder="Enter email addresses or phone numbers separated by comma"></textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="message_template" class="form-label">Message Template</label>
                            <textarea class="form-control" id="message_template" name="message_template" rows="3" placeholder="Custom message template"></textarea>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="schedule" class="form-label">Schedule</label>
                            <select class="form-select" id="schedule" name="schedule">
                                <option value="">Select Schedule</option>
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                                <option value="realtime">Real-time</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                                <label class="form-check-label" for="is_active">
                                    Aktif
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
            <button type="button" class="btn btn-primary" onclick="saveAlert()">Simpan Alert</button>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="delete-modal-overlay" id="deleteModalOverlay">
    <div class="delete-modal-content">
        <div class="delete-modal-header">
            <h5>Konfirmasi Hapus</h5>
        </div>
        <div class="delete-modal-body">
            <p>Apakah kamu yakin ingin menghapus alert ini? Tindakan ini tidak bisa dibatalkan.</p>
        </div>
        <div class="delete-modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Batal</button>
            <button type="button" class="btn btn-danger" onclick="confirmDelete()">Hapus</button>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="success-modal-overlay" id="successModalOverlay">
    <div class="success-modal-content">
        <div class="success-modal-header">
            <h5>Berhasil</h5>
        </div>
        <div class="success-modal-body">
            <p id="successMessage">Operasi berhasil diselesaikan!</p>
        </div>
        <div class="success-modal-footer">
            <button type="button" class="btn btn-primary" onclick="closeSuccessModal()">OK</button>
        </div>
    </div>
</div>

<!-- Error Modal -->
<div class="error-modal-overlay" id="errorModalOverlay">
    <div class="error-modal-content">
        <div class="error-modal-header">
            <h5>Gagal</h5>
        </div>
        <div class="error-modal-body">
            <p id="errorMessage">Terjadi kesalahan. Silakan coba lagi.</p>
        </div>
        <div class="error-modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeErrorModal()">Batal</button>
            <button type="button" class="btn btn-primary" onclick="retryOperation()">Coba Lagi</button>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.table-container {
    overflow-x: auto;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
}

.responsive-table {
    width: 100%;
    margin-bottom: 0;
}

.responsive-table th,
.responsive-table td {
    white-space: nowrap;
    padding: 0.75rem;
    vertical-align: middle;
}

.responsive-table th {
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    font-weight: 600;
}

.responsive-table tbody tr:hover {
    background-color: #f8f9fa;
}

.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1050;
}

.modal-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    border-radius: 0.375rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    max-width: 800px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    padding: 1rem;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-body {
    padding: 1rem;
    max-height: 60vh;
    overflow-y: auto;
}

.modal-footer {
    padding: 1rem;
    border-top: 1px solid #dee2e6;
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
}

.delete-modal-overlay,
.success-modal-overlay,
.error-modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1060;
}

.delete-modal-content,
.success-modal-content,
.error-modal-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    border-radius: 0.375rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    max-width: 400px;
    width: 90%;
}

.delete-modal-header,
.success-modal-header,
.error-modal-header {
    padding: 1rem;
    border-bottom: 1px solid #dee2e6;
}

.delete-modal-body,
.success-modal-body,
.error-modal-body {
    padding: 1rem;
}

.delete-modal-footer,
.success-modal-footer,
.error-modal-footer {
    padding: 1rem;
    border-top: 1px solid #dee2e6;
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
}

.pagination {
    margin-bottom: 0;
}

.pagination-info {
    color: #6c757d;
    font-size: 0.875rem;
}

@media (max-width: 768px) {
    .modal-content {
        width: 95%;
        max-width: none;
    }
    
    .responsive-table {
        font-size: 0.875rem;
    }
    
    .responsive-table th,
    .responsive-table td {
        padding: 0.5rem;
    }
}
</style>
@endpush

@push('scripts')
<script>
let currentPage = 1;
let totalPages = 1;
let deleteId = null;
let retryFunction = null;

// Load data on page load
document.addEventListener('DOMContentLoaded', function() {
    loadAlerts();
    loadReports();
});

// Load alerts data
function loadAlerts(page = 1) {
    const status = document.getElementById('filterStatus').value;
    const type = document.getElementById('filterType').value;
    const search = document.getElementById('searchInput').value;
    
    const params = new URLSearchParams({
        page: page,
        status: status,
        type: type,
        search: search
    });
    
    fetch(`/reports/alerts?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                renderAlertsTable(data.data);
                renderPagination(data.meta);
                currentPage = page;
            }
        })
        .catch(error => {
            console.error('Error loading alerts:', error);
            showErrorModal('Gagal memuat data alert');
        });
}

// Load reports for dropdown
function loadReports() {
    fetch('/reports/list')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const select = document.getElementById('report_id');
                select.innerHTML = '<option value="">Pilih Report</option>';
                data.data.forEach(report => {
                    const option = document.createElement('option');
                    option.value = report.id;
                    option.textContent = report.name;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading reports:', error);
        });
}

// Render alerts table
function renderAlertsTable(alerts) {
    const tbody = document.getElementById('alertsTableBody');
    tbody.innerHTML = '';
    
    alerts.forEach(alert => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${alert.name}</td>
            <td>${alert.report ? alert.report.name : 'N/A'}</td>
            <td>${alert.condition_field} ${alert.condition_operator} ${alert.condition_value}</td>
            <td>
                <span class="badge bg-${getTypeBadgeColor(alert.notification_type)}">
                    ${alert.notification_type.toUpperCase()}
                </span>
            </td>
            <td>
                <span class="badge bg-${alert.is_active ? 'success' : 'secondary'}">
                    ${alert.is_active ? 'Aktif' : 'Tidak Aktif'}
                </span>
            </td>
            <td>${alert.last_triggered_at ? formatDate(alert.last_triggered_at) : 'Belum Pernah'}</td>
        `;
        tbody.appendChild(row);
    });
}

// Render pagination
function renderPagination(meta) {
    const pagination = document.getElementById('pagination');
    pagination.innerHTML = '';
    
    totalPages = meta.last_page;
    
    // Previous button
    const prevLi = document.createElement('li');
    prevLi.className = `page-item ${meta.current_page === 1 ? 'disabled' : ''}`;
    prevLi.innerHTML = `<a class="page-link" href="#" onclick="loadAlerts(${meta.current_page - 1})">Previous</a>`;
    pagination.appendChild(prevLi);
    
    // Page numbers
    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= meta.current_page - 2 && i <= meta.current_page + 2)) {
            const li = document.createElement('li');
            li.className = `page-item ${i === meta.current_page ? 'active' : ''}`;
            li.innerHTML = `<a class="page-link" href="#" onclick="loadAlerts(${i})">${i}</a>`;
            pagination.appendChild(li);
        } else if (i === meta.current_page - 3 || i === meta.current_page + 3) {
            const li = document.createElement('li');
            li.className = 'page-item disabled';
            li.innerHTML = '<span class="page-link">...</span>';
            pagination.appendChild(li);
        }
    }
    
    // Next button
    const nextLi = document.createElement('li');
    nextLi.className = `page-item ${meta.current_page === meta.last_page ? 'disabled' : ''}`;
    nextLi.innerHTML = `<a class="page-link" href="#" onclick="loadAlerts(${meta.current_page + 1})">Next</a>`;
    pagination.appendChild(nextLi);
    
    // Update pagination info
    document.getElementById('showingFrom').textContent = meta.from || 0;
    document.getElementById('showingTo').textContent = meta.to || 0;
    document.getElementById('totalRecords').textContent = meta.total || 0;
}

// Apply filters
function applyFilters() {
    loadAlerts(1);
}

// Open modal
function openModal(mode, id = null) {
    const modal = document.getElementById('modalOverlay');
    const title = document.getElementById('modalTitle');
    const form = document.getElementById('alertForm');
    
    if (mode === 'create') {
        title.textContent = 'Tambah Alert';
        form.reset();
        document.getElementById('alertId').value = '';
    } else if (mode === 'edit' && id) {
        title.textContent = 'Edit Alert';
        loadAlertData(id);
    }
    
    modal.style.display = 'block';
}

// Close modal
function closeModal() {
    document.getElementById('modalOverlay').style.display = 'none';
}

// Load alert data for editing
function loadAlertData(id) {
    fetch(`/reports/alerts/${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const alert = data.data;
                document.getElementById('alertId').value = alert.id;
                document.getElementById('name').value = alert.name;
                document.getElementById('report_id').value = alert.report_id;
                document.getElementById('condition_field').value = alert.condition_field;
                document.getElementById('condition_operator').value = alert.condition_operator;
                document.getElementById('condition_value').value = alert.condition_value;
                document.getElementById('notification_type').value = alert.notification_type;
                document.getElementById('recipients').value = alert.recipients;
                document.getElementById('message_template').value = alert.message_template || '';
                document.getElementById('schedule').value = alert.schedule || '';
                document.getElementById('is_active').checked = alert.is_active;
            }
        })
        .catch(error => {
            console.error('Error loading alert data:', error);
            showErrorModal('Gagal memuat data alert');
        });
}

// Save alert
function saveAlert() {
    const form = document.getElementById('alertForm');
    const formData = new FormData(form);
    const id = document.getElementById('alertId').value;
    
    const url = id ? `/reports/alerts/${id}` : '/reports/alerts';
    const method = id ? 'PUT' : 'POST';
    
    fetch(url, {
        method: method,
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            closeModal();
            showSuccessModal(data.message || 'Alert berhasil disimpan!');
            loadAlerts(currentPage);
        } else {
            showErrorModal(data.message || 'Gagal menyimpan alert');
        }
    })
    .catch(error => {
        console.error('Error saving alert:', error);
        showErrorModal('Gagal menyimpan alert');
    });
}

// View alert
function viewAlert(id) {
    // Implement view functionality
    console.log('View alert:', id);
}

// Edit alert
function editAlert(id) {
    openModal('edit', id);
}

// Delete alert
function deleteAlert(id) {
    deleteId = id;
    document.getElementById('deleteModalOverlay').style.display = 'block';
}

// Confirm delete
function confirmDelete() {
    if (deleteId) {
        fetch(`/reports/alerts/${deleteId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                closeDeleteModal();
                showSuccessModal(data.message || 'Alert berhasil dihapus!');
                loadAlerts(currentPage);
            } else {
                showErrorModal(data.message || 'Gagal menghapus alert');
            }
        })
        .catch(error => {
            console.error('Error deleting alert:', error);
            showErrorModal('Gagal menghapus alert');
        });
    }
}

// Close delete modal
function closeDeleteModal() {
    document.getElementById('deleteModalOverlay').style.display = 'none';
    deleteId = null;
}

// Show success modal
function showSuccessModal(message) {
    document.getElementById('successMessage').textContent = message;
    document.getElementById('successModalOverlay').style.display = 'block';
}

// Close success modal
function closeSuccessModal() {
    document.getElementById('successModalOverlay').style.display = 'none';
}

// Show error modal
function showErrorModal(message) {
    document.getElementById('errorMessage').textContent = message;
    document.getElementById('errorModalOverlay').style.display = 'block';
}

// Close error modal
function closeErrorModal() {
    document.getElementById('errorModalOverlay').style.display = 'none';
}

// Retry operation
function retryOperation() {
    closeErrorModal();
    if (retryFunction) {
        retryFunction();
    }
}

// Utility functions
function getTypeBadgeColor(type) {
    const colors = {
        'email': 'primary',
        'sms': 'success',
        'push': 'warning'
    };
    return colors[type] || 'secondary';
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
}

// Search functionality
document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        applyFilters();
    }
});
</script>
@endpush
