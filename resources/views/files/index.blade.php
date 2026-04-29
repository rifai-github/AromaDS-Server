@extends('layouts.app')

@section('title', 'File Management - System')
@section('breadcrumb', 'Home / System / File Management')

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

    .btn-danger {
        background-color: #ef4444;
        color: white;
    }

    .btn-danger:hover {
        background-color: #dc2626;
    }

    .btn-info {
        background-color: #3b82f6;
        color: white;
    }

    .btn-info:hover {
        background-color: #2563eb;
    }

    .btn-sm {
        padding: 4px 8px;
        font-size: 12px;
    }

    /* Card Styles */
    .card {
        background: white;
        border-radius: 8px;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        border: 1px solid #e5e7eb;
        overflow: hidden;
    }

    .card-header {
        padding: 16px 20px;
        border-bottom: 1px solid #e5e7eb;
        background-color: #f9fafb;
    }

    .card-body {
        padding: 20px;
    }

    .card-title {
        font-size: 18px;
        font-weight: 600;
        color: #111827;
        margin: 0;
    }

    /* Table Styles */
    .table {
        width: 100%;
        border-collapse: collapse;
        margin: 0;
    }

    .table th,
    .table td {
        padding: 12px 16px;
        text-align: left;
        border-bottom: 1px solid #e5e7eb;
        vertical-align: middle;
    }

    .table th {
        background-color: #f9fafb;
        font-weight: 600;
        color: #374151;
        font-size: 14px;
    }

    .table td {
        color: #6b7280;
        font-size: 14px;
    }

    .table tbody tr:hover {
        background-color: #f9fafb;
    }

    /* File Upload Styles */
    .upload-area {
        border: 2px dashed #d1d5db;
        border-radius: 8px;
        padding: 40px 20px;
        text-align: center;
        background-color: #f9fafb;
        transition: all 0.2s ease;
        cursor: pointer;
    }

    .upload-area:hover {
        border-color: #3b82f6;
        background-color: #eff6ff;
    }

    .upload-area.dragover {
        border-color: #3b82f6;
        background-color: #eff6ff;
    }

    .upload-icon {
        font-size: 48px;
        color: #9ca3af;
        margin-bottom: 16px;
    }

    .upload-text {
        font-size: 16px;
        color: #6b7280;
        margin-bottom: 8px;
    }

    .upload-subtext {
        font-size: 14px;
        color: #9ca3af;
    }

    /* File Item Styles */
    .file-item {
        display: flex;
        align-items: center;
        padding: 12px 16px;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        margin-bottom: 8px;
        background: white;
        transition: all 0.2s ease;
    }

    .file-item:hover {
        box-shadow: 0 2px 4px 0 rgba(0, 0, 0, 0.1);
        border-color: #3b82f6;
    }

    .file-icon {
        font-size: 24px;
        margin-right: 12px;
        width: 32px;
        text-align: center;
    }

    .file-info {
        flex: 1;
        min-width: 0;
    }

    .file-name {
        font-weight: 500;
        color: #111827;
        margin-bottom: 4px;
        word-break: break-word;
    }

    .file-meta {
        font-size: 12px;
        color: #6b7280;
    }

    .file-actions {
        display: flex;
        gap: 8px;
    }

    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
    }

    .modal.show {
        display: flex !important;
        align-items: center;
        justify-content: center;
    }

    .modal-content {
        background: white;
        border-radius: 8px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        max-width: 500px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
    }

    .modal-header {
        padding: 20px 24px 16px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .modal-title {
        font-size: 18px;
        font-weight: 600;
        color: #111827;
        margin: 0;
    }

    .modal-close {
        background: none;
        border: none;
        font-size: 20px;
        color: #6b7280;
        cursor: pointer;
        padding: 4px;
        border-radius: 4px;
        transition: all 0.2s ease;
    }

    .modal-close:hover {
        background-color: #f3f4f6;
        color: #374151;
    }

    .modal-body {
        padding: 20px 24px;
    }

    .modal-footer {
        padding: 16px 24px 20px;
        border-top: 1px solid #e5e7eb;
        display: flex;
        gap: 12px;
        justify-content: flex-end;
    }

    /* Form Styles */
    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        display: block;
        font-weight: 500;
        color: #374151;
        margin-bottom: 6px;
        font-size: 14px;
    }

    .form-control {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        transition: all 0.2s ease;
    }

    .form-control:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .form-select {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
        background-position: right 8px center;
        background-repeat: no-repeat;
        background-size: 16px;
        padding-right: 32px;
    }

    /* Loading Spinner */
    .loading-spinner {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
        text-align: center;
    }

    .spinner {
        width: 40px;
        height: 40px;
        border: 4px solid #e5e7eb;
        border-top: 4px solid #3b82f6;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin-bottom: 16px;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .spinner-text {
        color: #6b7280;
        font-size: 14px;
        margin-bottom: 8px;
    }

    .loading-dots {
        display: flex;
        gap: 4px;
        justify-content: center;
    }

    .loading-dot {
        width: 6px;
        height: 6px;
        background-color: #3b82f6;
        border-radius: 50%;
        animation: bounce 1.4s ease-in-out infinite both;
    }

    .loading-dot:nth-child(1) { animation-delay: -0.32s; }
    .loading-dot:nth-child(2) { animation-delay: -0.16s; }

    @keyframes bounce {
        0%, 80%, 100% {
            transform: scale(0);
        }
        40% {
            transform: scale(1);
        }
    }

    /* Responsive */
    @media (max-width: 768px) {
        .card-body {
            padding: 16px;
        }
        
        .table th,
        .table td {
            padding: 8px 12px;
        }
        
        .file-item {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .file-actions {
            margin-top: 8px;
            width: 100%;
            justify-content: flex-end;
        }
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="card mb-4">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-folder-open me-2"></i>
                            File Management
                        </h5>
                        <button class="btn btn-primary" onclick="openUploadModal()">
                            <i class="fas fa-upload"></i>
                            Upload File
                        </button>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">Category</label>
                                <select class="form-control form-select" id="categoryFilter">
                                    <option value="">All Categories</option>
                                    @foreach($categories ?? [] as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">Search</label>
                                <input type="text" class="form-control" id="searchInput" placeholder="Search files...">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">&nbsp;</label>
                                <div>
                                    <button class="btn btn-secondary" onclick="clearFilters()">
                                        <i class="fas fa-times"></i>
                                        Clear Filters
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Files List -->
            <div class="card">
                <div class="card-body">
                    <div id="filesList">
                        <div class="loading-spinner">
                            <div class="spinner"></div>
                            <div class="spinner-text">Loading files...</div>
                            <div class="loading-dots">
                                <div class="loading-dot"></div>
                                <div class="loading-dot"></div>
                                <div class="loading-dot"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upload Modal -->
<div id="uploadModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Upload File</h5>
            <button type="button" class="modal-close" onclick="closeUploadModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="uploadForm" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label class="form-label">File</label>
                    <div class="upload-area" onclick="document.getElementById('fileInput').click()">
                        <div class="upload-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <div class="upload-text">Click to select file or drag and drop</div>
                        <div class="upload-subtext">Maximum file size: 500MB</div>
                        <input type="file" id="fileInput" name="file" style="display: none;" required>
                    </div>
                    <div id="selectedFile" style="display: none; margin-top: 12px; padding: 12px; background: #f0f9ff; border: 1px solid #0ea5e9; border-radius: 6px;">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-file me-2 text-blue-500"></i>
                            <span id="fileName"></span>
                            <button type="button" class="btn btn-sm btn-danger ms-auto" onclick="clearFile()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select class="form-control form-select" name="category_id" required>
                        <option value="">Select Category</option>
                        @foreach($categories ?? [] as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="3" placeholder="Optional description..."></textarea>
                </div>

                <div class="form-group">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_public" id="isPublic">
                        <label class="form-check-label" for="isPublic">
                            Make file public (accessible to all users)
                        </label>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeUploadModal()">
                <i class="fas fa-times"></i>
                Cancel
            </button>
            <button type="button" class="btn btn-primary" onclick="uploadFile()">
                <i class="fas fa-upload"></i>
                Upload
            </button>
        </div>
    </div>
</div>

<script>
let currentFiles = [];
let currentPage = 1;

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    loadFiles();
    setupEventListeners();
});

function setupEventListeners() {
    // File input change
    document.getElementById('fileInput').addEventListener('change', function(e) {
        if (e.target.files.length > 0) {
            showSelectedFile(e.target.files[0]);
        }
    });

    // Drag and drop
    const uploadArea = document.querySelector('.upload-area');
    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });

    uploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
    });

    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            document.getElementById('fileInput').files = files;
            showSelectedFile(files[0]);
        }
    });

    // Search and filter
    document.getElementById('searchInput').addEventListener('input', debounce(loadFiles, 300));
    document.getElementById('categoryFilter').addEventListener('change', loadFiles);
}

function showSelectedFile(file) {
    document.getElementById('fileName').textContent = file.name;
    document.getElementById('selectedFile').style.display = 'block';
}

function clearFile() {
    document.getElementById('fileInput').value = '';
    document.getElementById('selectedFile').style.display = 'none';
}

function openUploadModal() {
    document.getElementById('uploadModal').classList.add('show');
}

function closeUploadModal() {
    document.getElementById('uploadModal').classList.remove('show');
    document.getElementById('uploadForm').reset();
    clearFile();
}

function uploadFile() {
    const form = document.getElementById('uploadForm');
    const formData = new FormData(form);

    // Show loading
    const uploadBtn = document.querySelector('#uploadModal .btn-primary');
    const originalText = uploadBtn.innerHTML;
    uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
    uploadBtn.disabled = true;

    fetch('{{ route("files.store") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeUploadModal();
            loadFiles();
            showNotification('File uploaded successfully!', 'success');
        } else {
            showNotification(data.message || 'Upload failed', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Upload failed', 'error');
    })
    .finally(() => {
        uploadBtn.innerHTML = originalText;
        uploadBtn.disabled = false;
    });
}

function loadFiles() {
    const search = document.getElementById('searchInput').value;
    const category = document.getElementById('categoryFilter').value;

    const params = new URLSearchParams();
    if (search) params.append('search', search);
    if (category) params.append('category_id', category);

    fetch(`{{ route("files.index") }}?${params.toString()}`, {
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            currentFiles = data.data.data;
            renderFiles(currentFiles);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('filesList').innerHTML = '<div class="text-center text-red-500">Error loading files</div>';
    });
}

function renderFiles(files) {
    if (files.length === 0) {
        document.getElementById('filesList').innerHTML = `
            <div class="text-center py-8">
                <i class="fas fa-folder-open text-4xl text-gray-400 mb-4"></i>
                <p class="text-gray-500">No files found</p>
            </div>
        `;
        return;
    }

    const filesHtml = files.map(file => `
        <div class="file-item">
            <div class="file-icon">
                <i class="${getFileIcon(file.file_extension)}"></i>
            </div>
            <div class="file-info">
                <div class="file-name">${file.original_name}</div>
                <div class="file-meta">
                    ${file.formatted_size} • ${file.category.name} • Uploaded by ${file.uploader.name} • ${formatDate(file.created_at)}
                </div>
            </div>
            <div class="file-actions">
                <button class="btn btn-info btn-sm" onclick="downloadFile(${file.id})">
                    <i class="fas fa-download"></i>
                </button>
                <button class="btn btn-danger btn-sm" onclick="deleteFile(${file.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `).join('');

    document.getElementById('filesList').innerHTML = filesHtml;
}

function getFileIcon(extension) {
    const icons = {
        'pdf': 'fas fa-file-pdf text-red-500',
        'doc': 'fas fa-file-word text-blue-500',
        'docx': 'fas fa-file-word text-blue-500',
        'xls': 'fas fa-file-excel text-green-500',
        'xlsx': 'fas fa-file-excel text-green-500',
        'jpg': 'fas fa-file-image text-purple-500',
        'jpeg': 'fas fa-file-image text-purple-500',
        'png': 'fas fa-file-image text-purple-500',
        'zip': 'fas fa-file-archive text-yellow-500',
        'rar': 'fas fa-file-archive text-yellow-500'
    };
    return icons[extension.toLowerCase()] || 'fas fa-file text-gray-500';
}

function downloadFile(fileId) {
    window.open(`{{ url('files') }}/${fileId}/download`, '_blank');
}

function deleteFile(fileId) {
    if (confirm('Are you sure you want to delete this file?')) {
        fetch(`{{ url('files') }}/${fileId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadFiles();
                showNotification('File deleted successfully!', 'success');
            } else {
                showNotification(data.message || 'Delete failed', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Delete failed', 'error');
        });
    }
}

function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('categoryFilter').value = '';
    loadFiles();
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function showNotification(message, type) {
    // Simple notification - you can enhance this with a proper notification system
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close ms-auto" onclick="this.parentElement.parentElement.remove()"></button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
</script>
@endsection
