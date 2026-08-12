@extends('layouts.app')

@section('title', 'Job Team Assignments')
@section('breadcrumb', 'Home / Operational / Job Schedules / Team Assignments')

@section('content')
<div class="flex flex-col w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">
        
        <!-- Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Team Assignments</h1>
                <span class="ml-2 text-sm text-gray-500">Job #{{ $jobSchedule->job_number ?? 'N/A' }}</span>
            </div>
            
            <div class="flex flex-row justify-end items-center gap-3">
                <button type="button" class="btn btn-primary" onclick="openAssignTeamModal()">
                    <i class="fas fa-plus"></i> Assign Team
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
                    <p class="text-lg font-semibold text-gray-900">{{ $jobSchedule->building->building_name ?? 'N/A' }}</p>
                </div>
            </div>
        </div>
        
        <!-- Assignments Section -->
        <div class="w-full bg-white rounded-b-[10px] p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-lg font-semibold text-[#214589]">Team Assignments</h2>
                <div class="text-sm text-gray-500">
                    <span id="assignmentsCount">Loading...</span> assignments
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full table-auto border-collapse">
                    <thead>
                        <tr class="bg-[#214589] text-white">
                            <th class="px-6 py-4 text-left font-semibold">Team</th>
                            <th class="px-6 py-4 text-left font-semibold">Assigned By</th>
                            <th class="px-6 py-4 text-center font-semibold">Status</th>
                            <th class="px-6 py-4 text-center font-semibold">Assigned Date</th>
                            <th class="px-6 py-4 text-center font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="assignmentsTable" class="bg-white">
                        <tr>
                            <td colspan="5" class="text-center py-12">
                                <div class="flex flex-col items-center">
                                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[#214589] mb-4"></div>
                                    <p class="text-gray-500">Loading assignments...</p>
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
    
    // Load assignments on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadAssignments();
    });
    
    function loadAssignments() {
        fetch(`/operational/job-schedules/${jobScheduleId}/assignments/api`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                const tbody = document.getElementById('assignmentsTable');
                const countElement = document.getElementById('assignmentsCount');
                
                if (data.data && data.data.length > 0) {
                    // Update count
                    if (countElement) {
                        countElement.textContent = data.data.length;
                    }
                    
                    tbody.innerHTML = data.data.map(assignment => `
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-6 py-4 font-semibold text-gray-900">${assignment.team ? assignment.team.team_name : 'N/A'}</td>
                            <td class="px-6 py-4 text-gray-700">${assignment.assigned_by_user ? assignment.assigned_by_user.name : 'N/A'}</td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-3 py-1 rounded-full text-xs font-medium ${getStatusClass(assignment.status)}">
                                    ${assignment.status.replace('_', ' ').toUpperCase()}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center text-gray-700">${assignment.assigned_date ? new Date(assignment.assigned_date).toLocaleDateString() : 'N/A'}</td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex justify-center space-x-2">
                                    ${getActionButtons(assignment)}
                                </div>
                            </td>
                        </tr>
                    `).join('');
                } else {
                    if (countElement) {
                        countElement.textContent = '0';
                    }
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-12 text-gray-500">No assignments found</td></tr>';
                }
            })
            .catch(error => {
                console.error('Error loading assignments:', error);
                document.getElementById('assignmentsTable').innerHTML = '<tr><td colspan="5" class="text-center py-8 text-red-500">Error loading assignments</td></tr>';
            });
    }
    
    function getStatusClass(status) {
        const classes = {
            'assigned': 'bg-blue-100 text-blue-800',
            'accepted': 'bg-green-100 text-green-800',
            'in_progress': 'bg-yellow-100 text-yellow-800',
            'completed': 'bg-green-100 text-green-800',
            'cancelled': 'bg-red-100 text-red-800'
        };
        return classes[status] || 'bg-gray-100 text-gray-800';
    }
    
    function getActionButtons(assignment) {
        let buttons = '';
        
        if (assignment.status === 'assigned') {
            buttons += `<button onclick="acceptAssignment(${assignment.id})" class="btn btn-sm btn-success">Accept</button>`;
        }
        
        if (assignment.status === 'accepted') {
            buttons += `<button onclick="startAssignment(${assignment.id})" class="btn btn-sm btn-primary">Start</button>`;
        }
        
        if (assignment.status === 'in_progress') {
            buttons += `<button onclick="completeAssignment(${assignment.id})" class="btn btn-sm btn-success">Complete</button>`;
        }
        
        return buttons;
    }
    
    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    function openAssignTeamModal() {
        openModal('Assign Team to Job');
        document.getElementById('modalBody').innerHTML = `
            <form id="assignTeamForm" onsubmit="assignTeam(event)">
                <div class="form-group">
                    <label class="form-label">Select Team *</label>
                    <select name="team_id" class="form-input" required>
                        <option value="">Select Team</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Assignment Notes</label>
                    <textarea name="assignment_notes" class="form-input" rows="3" placeholder="Optional notes for this assignment"></textarea>
                </div>
            </form>
        `;
        
        document.getElementById('modalFooter').innerHTML = `
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
            <button type="submit" form="assignTeamForm" class="btn btn-primary">Assign Team</button>
        `;
        
        loadTeams();
    }
    
    function loadTeams() {
        console.log('Loading teams...');
        fetch('/api/teams-test')
            .then(response => {
                console.log('Teams response status:', response.status);
                console.log('Teams response headers:', response.headers);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text().then(text => {
                    console.log('Raw response:', text);
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('JSON parse error:', e);
                        throw new Error('Invalid JSON response');
                    }
                });
            })
            .then(data => {
                console.log('Teams data received:', data);
                const select = document.querySelector('select[name="team_id"]');
                if (select) {
                    select.innerHTML = '<option value="">Select Team</option>';
                    if (data.data && Array.isArray(data.data)) {
                        console.log('Teams count:', data.data.length);
                        data.data.forEach(team => {
                            console.log('Adding team:', team.team_name);
                            select.innerHTML += `<option value="${team.id}">${team.team_name}</option>`;
                        });
                    } else {
                        console.log('No teams data found in response:', data);
                    }
                } else {
                    console.log('Select element not found');
                }
            })
            .catch(error => {
                console.error('Error loading teams:', error);
                const select = document.querySelector('select[name="team_id"]');
                if (select) {
                    select.innerHTML = '<option value="">Error loading teams</option>';
                }
            });
    }
    
    function assignTeam(event) {
        event.preventDefault();
        const formData = new FormData(event.target);
        
        fetch(`/operational/job-schedules/${jobScheduleId}/assign-team`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                team_id: formData.get('team_id'),
                assignment_notes: formData.get('assignment_notes')
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showNotification('Team assigned successfully!', 'success');
                closeModal();
                loadAssignments();
            } else {
                showNotification(data.message || 'Error assigning team', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error assigning team', 'error');
        });
    }
    
    function acceptAssignment(assignmentId) {
        fetch(`/operational/job-assignments/${assignmentId}/accept`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showNotification('Assignment accepted!', 'success');
                loadAssignments();
            } else {
                showNotification(data.message || 'Error accepting assignment', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error accepting assignment', 'error');
        });
    }
    
    function startAssignment(assignmentId) {
        fetch(`/operational/job-assignments/${assignmentId}/start`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showNotification('Assignment started!', 'success');
                loadAssignments();
            } else {
                showNotification(data.message || 'Error starting assignment', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error starting assignment', 'error');
        });
    }
    
    function completeAssignment(assignmentId) {
        const notes = prompt('Enter completion notes (optional):');
        fetch(`/operational/job-assignments/${assignmentId}/complete`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                completion_notes: notes || ''
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showNotification('Assignment completed!', 'success');
                loadAssignments();
            } else {
                showNotification(data.message || 'Error completing assignment', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error completing assignment', 'error');
        });
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
    
    .btn-success {
        background-color: #10b981;
        color: white;
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
