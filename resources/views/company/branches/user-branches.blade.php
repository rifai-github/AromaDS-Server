@extends('layouts.app')

@section('title', 'Multi-Branch User Assignment')
@section('breadcrumb', 'Home / Company / Multi-Branch User Assignment')

@section('content')
<style>
    .page-container {
        background: white;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        padding: 24px;
        margin: 20px 0;
    }
    
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .page-title {
        font-size: 24px;
        font-weight: 700;
        color: #1f2937;
        margin: 0;
    }
    
    .page-subtitle {
        font-size: 14px;
        color: #6b7280;
        margin-top: 4px;
    }
    
    .back-btn {
        background: #f3f4f6;
        color: #374151;
        border: 1px solid #d1d5db;
        padding: 10px 20px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
    }
    
    .back-btn:hover {
        background: #e5e7eb;
        color: #1f2937;
    }
    
    .form-section {
        max-width: 800px;
    }
    
    .form-group {
        margin-bottom: 24px;
    }
    
    .form-label {
        display: block;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
        font-size: 14px;
    }
    
    .form-select {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        background: white;
        transition: border-color 0.2s;
    }
    
    .form-select:focus {
        outline: none;
        border-color: #214589;
        box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
    }
    
    .branch-list {
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 16px;
        max-height: 400px;
        overflow-y: auto;
    }
    
    .branch-item {
        display: flex;
        align-items: center;
        padding: 12px 16px;
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        margin-bottom: 8px;
        transition: all 0.2s;
    }
    
    .branch-item:last-child {
        margin-bottom: 0;
    }
    
    .branch-item:hover {
        border-color: #214589;
        background: #f0f5ff;
    }
    
    .branch-item.selected {
        border-color: #214589;
        background: #eff6ff;
    }
    
    .branch-checkbox {
        width: 20px;
        height: 20px;
        margin-right: 12px;
        accent-color: #214589;
        cursor: pointer;
    }
    
    .branch-info {
        flex: 1;
    }
    
    .branch-name {
        font-weight: 600;
        color: #1f2937;
        font-size: 14px;
    }
    
    .branch-code {
        font-size: 12px;
        color: #6b7280;
        margin-top: 2px;
    }
    
    .primary-badge {
        background: #fef3c7;
        color: #92400e;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .primary-btn {
        background: #f3f4f6;
        color: #6b7280;
        border: 1px solid #d1d5db;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .primary-btn:hover {
        background: #214589;
        color: white;
        border-color: #214589;
    }
    
    .save-btn {
        background: #214589;
        color: white;
        border: none;
        padding: 12px 32px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .save-btn:hover {
        background: #1e3a8a;
    }
    
    .save-btn:disabled {
        background: #9ca3af;
        cursor: not-allowed;
    }
    
    .assigned-count {
        background: #dbeafe;
        color: #1e40af;
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        margin-bottom: 16px;
    }
    
    .empty-state {
        text-align: center;
        padding: 40px;
        color: #6b7280;
    }
    
    .empty-state i {
        font-size: 48px;
        color: #d1d5db;
        margin-bottom: 16px;
    }
    
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255,255,255,0.8);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }
    
    .loading-overlay.show {
        display: flex;
    }
    
    .loading-spinner {
        width: 48px;
        height: 48px;
        border: 4px solid #e5e7eb;
        border-top-color: #214589;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    
    .alert {
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 16px;
        display: none;
    }
    
    .alert-success {
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #a7f3d0;
    }
    
    .alert-error {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }
</style>

<div class="page-container">
    <div class="page-header">
        <div>
            <h1 class="page-title">Multi-Branch User Assignment</h1>
            <p class="page-subtitle">Assign users to multiple branches</p>
        </div>
        <a href="{{ route('company.branches.index') }}" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Branches
        </a>
    </div>

    <div id="alertBox" class="alert"></div>

    <div class="form-section">
        <div class="form-group">
            <label class="form-label">Select User</label>
            <select id="userSelect" class="form-select" onchange="loadUserBranches()">
                <option value="">-- Select a User --</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                @endforeach
            </select>
        </div>

        <div id="branchSection" style="display: none;">
            <div class="form-group">
                <label class="form-label">Available Branches</label>
                <div id="assignedCount" class="assigned-count" style="display: none;">
                    <i class="fas fa-check-circle"></i> <span id="countText">0 branches assigned</span>
                </div>
                <div class="branch-list" id="branchList">
                    @foreach($branches as $branch)
                        <div class="branch-item" id="branchItem{{ $branch->id }}">
                            <input type="checkbox" 
                                   class="branch-checkbox" 
                                   id="branch{{ $branch->id }}" 
                                   value="{{ $branch->id }}"
                                   onchange="updateSelection()">
                            <div class="branch-info">
                                <div class="branch-name">{{ $branch->name }}</div>
                                <div class="branch-code">Code: {{ $branch->code }}</div>
                            </div>
                            <button type="button" 
                                    class="primary-btn" 
                                    id="primaryBtn{{ $branch->id }}"
                                    onclick="setPrimary({{ $branch->id }})"
                                    style="display: none;">
                                Set Primary
                            </button>
                            <span class="primary-badge" id="primaryBadge{{ $branch->id }}" style="display: none;">
                                <i class="fas fa-star"></i> Primary
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            <button type="button" class="save-btn" onclick="saveAssignments()" id="saveBtn" disabled>
                <i class="fas fa-save"></i> Save Assignments
            </button>
        </div>
    </div>
</div>

<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner"></div>
</div>

<script>
let selectedUserId = null;
let primaryBranchId = null;
let originalAssignments = [];

function showLoading() {
    document.getElementById('loadingOverlay').classList.add('show');
}

function hideLoading() {
    document.getElementById('loadingOverlay').classList.remove('show');
}

function showAlert(message, type) {
    const alertBox = document.getElementById('alertBox');
    alertBox.className = 'alert alert-' + type;
    alertBox.textContent = message;
    alertBox.style.display = 'block';
    setTimeout(() => { alertBox.style.display = 'none'; }, 5000);
}

function loadUserBranches() {
    selectedUserId = document.getElementById('userSelect').value;
    
    if (!selectedUserId) {
        document.getElementById('branchSection').style.display = 'none';
        return;
    }
    
    showLoading();
    
    fetch(`{{ url('company/branches/user-assignments') }}/${selectedUserId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                document.getElementById('branchSection').style.display = 'block';
                
                // Reset all checkboxes and buttons
                document.querySelectorAll('.branch-checkbox').forEach(cb => {
                    cb.checked = false;
                    const branchId = cb.value;
                    document.getElementById('branchItem' + branchId).classList.remove('selected');
                    document.getElementById('primaryBtn' + branchId).style.display = 'none';
                    document.getElementById('primaryBadge' + branchId).style.display = 'none';
                });
                
                // Set assigned branches
                originalAssignments = data.assigned_branch_ids;
                primaryBranchId = data.primary_branch_id;
                
                data.assigned_branch_ids.forEach(branchId => {
                    const cb = document.getElementById('branch' + branchId);
                    if (cb) {
                        cb.checked = true;
                        document.getElementById('branchItem' + branchId).classList.add('selected');
                        document.getElementById('primaryBtn' + branchId).style.display = 'inline-block';
                    }
                });
                
                // Set primary branch
                if (primaryBranchId) {
                    document.getElementById('primaryBtn' + primaryBranchId).style.display = 'none';
                    document.getElementById('primaryBadge' + primaryBranchId).style.display = 'inline-block';
                }
                
                updateSelection();
            }
            hideLoading();
        })
        .catch(error => {
            hideLoading();
            showAlert('Failed to load user branches: ' + error.message, 'error');
        });
}

function updateSelection() {
    const checkedBoxes = document.querySelectorAll('.branch-checkbox:checked');
    const count = checkedBoxes.length;
    
    // Update count display
    document.getElementById('assignedCount').style.display = count > 0 ? 'block' : 'none';
    document.getElementById('countText').textContent = count + ' branch' + (count !== 1 ? 'es' : '') + ' assigned';
    
    // Update primary buttons visibility
    document.querySelectorAll('.branch-checkbox').forEach(cb => {
        const branchId = cb.value;
        const isPrimary = parseInt(branchId) === parseInt(primaryBranchId);
        
        if (cb.checked) {
            document.getElementById('branchItem' + branchId).classList.add('selected');
            if (isPrimary) {
                document.getElementById('primaryBtn' + branchId).style.display = 'none';
                document.getElementById('primaryBadge' + branchId).style.display = 'inline-block';
            } else {
                document.getElementById('primaryBtn' + branchId).style.display = 'inline-block';
                document.getElementById('primaryBadge' + branchId).style.display = 'none';
            }
        } else {
            document.getElementById('branchItem' + branchId).classList.remove('selected');
            document.getElementById('primaryBtn' + branchId).style.display = 'none';
            document.getElementById('primaryBadge' + branchId).style.display = 'none';
            
            // If unchecked branch was primary, reset primary
            if (isPrimary) {
                primaryBranchId = null;
            }
        }
    });
    
    // Enable save button
    document.getElementById('saveBtn').disabled = false;
}

function setPrimary(branchId) {
    // Remove primary from previous
    if (primaryBranchId) {
        document.getElementById('primaryBtn' + primaryBranchId).style.display = 'inline-block';
        document.getElementById('primaryBadge' + primaryBranchId).style.display = 'none';
    }
    
    // Set new primary
    primaryBranchId = branchId;
    document.getElementById('primaryBtn' + branchId).style.display = 'none';
    document.getElementById('primaryBadge' + branchId).style.display = 'inline-block';
    
    document.getElementById('saveBtn').disabled = false;
}

function saveAssignments() {
    if (!selectedUserId) {
        showAlert('Please select a user first', 'error');
        return;
    }
    
    const checkedBoxes = document.querySelectorAll('.branch-checkbox:checked');
    const branchIds = Array.from(checkedBoxes).map(cb => parseInt(cb.value));
    
    showLoading();
    
    fetch('{{ route("company.branches.user-assignments.update") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            user_id: selectedUserId,
            branch_ids: branchIds,
            primary_branch_id: primaryBranchId
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.status === 'success') {
            showAlert(data.message, 'success');
            originalAssignments = branchIds;
        } else {
            showAlert(data.message || 'Failed to save', 'error');
        }
    })
    .catch(error => {
        hideLoading();
        showAlert('Failed to save: ' + error.message, 'error');
    });
}
</script>
@endsection
