@extends('layouts.app')

@section('title', 'Force Majeure Dashboard')
@section('breadcrumb', 'Home / Operational / Force Majeure Dashboard')

@section('content')
<style>
    .stats-card {
        background: white;
        border-radius: 16px;
        padding: 32px 24px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        border: 1px solid #f3f4f6;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .stats-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }
    
    .stats-card.warning { 
        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        border-color: #f59e0b;
    }
    .stats-card.danger { 
        background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
        border-color: #ef4444;
    }
    .stats-card.info { 
        background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
        border-color: #3b82f6;
    }
    .stats-card.success { 
        background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
        border-color: #10b981;
    }
    
    .stats-number {
        font-size: 3rem;
        font-weight: 800;
        margin-bottom: 12px;
        line-height: 1;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    }
    
    .stats-label {
        color: #374151;
        font-size: 0.95rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    .stats-card.danger .stats-number { color: #dc2626; }
    .stats-card.warning .stats-number { color: #d97706; }
    .stats-card.info .stats-number { color: #2563eb; }
    .stats-card.success .stats-number { color: #059669; }
    
    .chart-container {
        background: white;
        border-radius: 16px;
        padding: 32px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        border: 1px solid #f3f4f6;
        transition: all 0.3s ease;
    }
    
    .chart-container:hover {
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }
    
    .table-container {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        border: 1px solid #f3f4f6;
        overflow: hidden;
    }
    
    .status-badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
        text-transform: uppercase;
    }
    
    .status-pending { background-color: #fef3c7; color: #92400e; }
    .status-resolved { background-color: #d1fae5; color: #065f46; }
    .status-escalated { background-color: #fee2e2; color: #991b1b; }
    
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

    /* Layout fixes */
    body {
        overflow-x: hidden;
    }
    
    .chart-container {
        min-height: 300px;
    }
    
    /* Responsive improvements */
    @media (max-width: 768px) {
        .stats-card {
            padding: 24px 20px;
        }
        
        .stats-number {
            font-size: 2.5rem;
        }
        
        .chart-container {
            padding: 24px 20px;
            min-height: 250px;
        }
        
        .stats-label {
            font-size: 0.875rem;
        }
    }
    
    @media (max-width: 640px) {
        .stats-card {
            padding: 20px 16px;
        }
        
        .stats-number {
            font-size: 2rem;
        }
        
        .chart-container {
            padding: 20px 16px;
        }
    }
</style>

<div class="w-full min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex flex-row justify-between items-center">
                    <div class="flex flex-row justify-start items-center">
                        <a href="{{ route('operational.job-schedules.index') }}" class="btn btn-secondary mr-8">
                            <i class="fas fa-arrow-left"></i>
                            <span class="hidden md:inline">Back to Job Schedules</span>
                            <span class="md:hidden">Back</span>
                        </a>
                        <h1 class="text-2xl font-bold text-gray-900">Force Majeure Dashboard</h1>
                    </div>
                    <div class="flex flex-row justify-end items-center gap-8">
                        <button class="btn btn-secondary" onclick="exportDashboard()">
                            <i class="fas fa-download"></i>
                            <span class="hidden md:inline">Export CSV</span>
                            <span class="md:hidden">Export</span>
                        </button>
                        <button class="btn btn-primary" onclick="refreshDashboard()">
                            <i class="fas fa-sync-alt"></i>
                            Refresh
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-8">
            <div class="stats-card danger">
                <div class="stats-number text-red-600" id="totalForceMajeure">-</div>
                <div class="stats-label">Total Force Majeure</div>
            </div>
            <div class="stats-card warning">
                <div class="stats-number text-yellow-600" id="pendingResolution">-</div>
                <div class="stats-label">Pending Resolution</div>
            </div>
            <div class="stats-card info">
                <div class="stats-number text-blue-600" id="technicianUnavailable">-</div>
                <div class="stats-label">Technician Unavailable</div>
            </div>
            <div class="stats-card success">
                <div class="stats-number text-green-600" id="resolvedToday">-</div>
                <div class="stats-label">Resolved Today</div>
            </div>
        </div>
        
        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Force Majeure Types Chart -->
            <div class="chart-container">
                <div class="flex items-center mb-6">
                    <div class="w-3 h-3 bg-blue-500 rounded-full mr-3"></div>
                    <h3 class="text-xl font-bold text-gray-900">Force Majeure Types</h3>
                </div>
                <div class="relative h-72">
                    <canvas id="forceMajeureTypesChart"></canvas>
                </div>
            </div>
            
            <!-- Resolution Status Chart -->
            <div class="chart-container">
                <div class="flex items-center mb-6">
                    <div class="w-3 h-3 bg-green-500 rounded-full mr-3"></div>
                    <h3 class="text-xl font-bold text-gray-900">Resolution Status</h3>
                </div>
                <div class="relative h-72">
                    <canvas id="resolutionStatusChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Recent Force Majeure Events -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-8 py-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <div class="flex items-center">
                        <div class="w-3 h-3 bg-orange-500 rounded-full mr-3"></div>
                        <h3 class="text-xl font-bold text-gray-900">Recent Force Majeure Events</h3>
                    </div>
                    <span class="text-sm text-gray-500 bg-gray-100 px-3 py-1 rounded-full">Last 10 events</span>
                </div>
            </div>
            <div class="p-8">
                <!-- Desktop Table View -->
                <div class="hidden lg:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Job Number</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reported At</th>
                                 
                            </tr>
                        </thead>
                        <tbody id="recentEventsTable" class="bg-white divide-y divide-gray-200">
                            <!-- Data will be loaded here -->
                        </tbody>
                    </table>
                </div>
                
                <!-- Mobile Card View -->
                <div class="lg:hidden space-y-4" id="recentEventsCards">
                    <!-- Cards will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
let forceMajeureTypesChart;
let resolutionStatusChart;

// Load dashboard data
async function loadDashboardData() {
    try {
        const response = await fetch('/operational/job-schedules/force-majeure/stats');
        const data = await response.json();
        
        if (data.status === 'success') {
            updateStatistics(data.data);
            updateCharts(data.data);
            loadRecentEvents();
        }
    } catch (error) {
        console.error('Error loading dashboard data:', error);
    }
}

// Update statistics cards
function updateStatistics(stats) {
    document.getElementById('totalForceMajeure').textContent = stats.total_force_majeure || 0;
    document.getElementById('pendingResolution').textContent = stats.pending_resolution || 0;
    document.getElementById('technicianUnavailable').textContent = stats.technician_unavailable || 0;
    document.getElementById('resolvedToday').textContent = stats.resolved_today || 0;
}

// Update charts
function updateCharts(stats) {
    // Force Majeure Types Chart
    const typesCtx = document.getElementById('forceMajeureTypesChart').getContext('2d');
    if (forceMajeureTypesChart) {
        forceMajeureTypesChart.destroy();
    }
    
    forceMajeureTypesChart = new Chart(typesCtx, {
        type: 'doughnut',
        data: {
            labels: ['Technician Unavailable', 'Material Shortage', 'Weather', 'Emergency', 'Equipment Failure', 'Other'],
            datasets: [{
                data: [
                    stats.technician_unavailable || 0,
                    stats.material_shortage || 0,
                    stats.weather || 0,
                    stats.emergency || 0,
                    stats.equipment_failure || 0,
                    stats.other || 0
                ],
                backgroundColor: [
                    '#ef4444',
                    '#f59e0b',
                    '#3b82f6',
                    '#dc2626',
                    '#8b5cf6',
                    '#6b7280'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
    
    // Resolution Status Chart
    const resolutionCtx = document.getElementById('resolutionStatusChart').getContext('2d');
    if (resolutionStatusChart) {
        resolutionStatusChart.destroy();
    }
    
    resolutionStatusChart = new Chart(resolutionCtx, {
        type: 'bar',
        data: {
            labels: ['Pending', 'Resolved', 'Escalated'],
            datasets: [{
                label: 'Count',
                data: [
                    stats.pending_resolution || 0,
                    stats.resolved_count || 0,
                    stats.escalated_count || 0
                ],
                backgroundColor: [
                    '#f59e0b',
                    '#10b981',
                    '#ef4444'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// Load recent events
async function loadRecentEvents() {
    try {
        const response = await fetch('/operational/job-schedules?force_majeure=1&limit=10');
        const data = await response.json();
        
        const tbody = document.getElementById('recentEventsTable');
        const cardsContainer = document.getElementById('recentEventsCards');
        
        tbody.innerHTML = '';
        cardsContainer.innerHTML = '';
        
        if (data.data && data.data.length > 0) {
            data.data.forEach(event => {
                // Desktop table row
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        ${event.job_number || '-'}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        ${event.force_majeure_status ? event.force_majeure_status.replace('_', ' ').toUpperCase() : '-'}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="status-badge status-${event.resolution_status || 'pending'}">
                            ${event.resolution_status || 'pending'}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        ${event.force_majeure_at ? new Date(event.force_majeure_at).toLocaleString() : '-'}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <a href="/operational/job-schedules/${event.id}" class="text-blue-600 hover:text-blue-900">View</a>
                    </td>
                `;
                tbody.appendChild(row);
                
                // Mobile card
                const card = document.createElement('div');
                card.className = 'bg-gray-50 rounded-lg p-4 border border-gray-200';
                card.innerHTML = `
                    <div class="flex justify-between items-start mb-2">
                        <h4 class="font-medium text-gray-900">${event.job_number || 'N/A'}</h4>
                        <span class="status-badge status-${event.resolution_status || 'pending'}">
                            ${event.resolution_status || 'pending'}
                        </span>
                    </div>
                    <div class="text-sm text-gray-600 mb-2">
                        <strong>Type:</strong> ${event.force_majeure_status ? event.force_majeure_status.replace('_', ' ').toUpperCase() : '-'}
                    </div>
                    <div class="text-sm text-gray-600 mb-3">
                        <strong>Reported:</strong> ${event.force_majeure_at ? new Date(event.force_majeure_at).toLocaleString() : '-'}
                    </div>
                    <div class="flex justify-end">
                        <a href="/operational/job-schedules/${event.id}" class="text-blue-600 hover:text-blue-900 text-sm font-medium">View Details →</a>
                    </div>
                `;
                cardsContainer.appendChild(card);
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">No recent force majeure events</td></tr>';
            cardsContainer.innerHTML = '<div class="text-center text-gray-500 py-8">No recent force majeure events</div>';
        }
    } catch (error) {
        console.error('Error loading recent events:', error);
    }
}

// Refresh dashboard
function refreshDashboard() {
    loadDashboardData();
}

// Export dashboard data
function exportDashboard() {
    try {
        // Show loading state
        const exportBtn = document.querySelector('button[onclick="exportDashboard()"]');
        const originalText = exportBtn.innerHTML;
        exportBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Exporting...';
        exportBtn.disabled = true;
        
        // Get current data
        fetch('/operational/job-schedules/force-majeure/stats')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Create CSV content
                    const csvContent = createCSVContent(data.data);
                    
                    // Download CSV
                    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `force-majeure-dashboard-${new Date().toISOString().split('T')[0]}.csv`;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    window.URL.revokeObjectURL(url);
                    
                    // Show success message
                    showNotification('Dashboard data exported successfully as CSV', 'success');
                } else {
                    showNotification('Error: ' + (data.message || 'Failed to export data'), 'error');
                }
            })
            .catch(error => {
                console.error('Error exporting dashboard:', error);
                showNotification('Error exporting dashboard data', 'error');
            })
            .finally(() => {
                // Reset button state
                exportBtn.innerHTML = originalText;
                exportBtn.disabled = false;
            });
    } catch (error) {
        console.error('Error exporting dashboard:', error);
        showNotification('Error exporting dashboard data', 'error');
    }
}

// Create CSV content
function createCSVContent(data) {
    const headers = [
        'Metric',
        'Count',
        'Percentage'
    ];
    
    const rows = [
        ['Total Force Majeure', data.total_force_majeure || 0, '100%'],
        ['Technician Unavailable', data.technician_unavailable || 0, calculatePercentage(data.technician_unavailable, data.total_force_majeure)],
        ['Material Shortage', data.material_shortage || 0, calculatePercentage(data.material_shortage, data.total_force_majeure)],
        ['Weather', data.weather || 0, calculatePercentage(data.weather, data.total_force_majeure)],
        ['Emergency', data.emergency || 0, calculatePercentage(data.emergency, data.total_force_majeure)],
        ['Equipment Failure', data.equipment_failure || 0, calculatePercentage(data.equipment_failure, data.total_force_majeure)],
        ['Other', data.other || 0, calculatePercentage(data.other, data.total_force_majeure)],
        ['Pending Resolution', data.pending_resolution || 0, calculatePercentage(data.pending_resolution, data.total_force_majeure)],
        ['Resolved', data.resolved_count || 0, calculatePercentage(data.resolved_count, data.total_force_majeure)],
        ['Escalated', data.escalated_count || 0, calculatePercentage(data.escalated_count, data.total_force_majeure)],
        ['Resolved Today', data.resolved_today || 0, calculatePercentage(data.resolved_today, data.total_force_majeure)]
    ];
    
    return [headers, ...rows].map(row => row.join(',')).join('\n');
}

// Calculate percentage
function calculatePercentage(value, total) {
    if (!total || total === 0) return '0%';
    return Math.round((value / total) * 100) + '%';
}

// Show notification
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm ${
        type === 'success' ? 'bg-green-500 text-white' : 
        type === 'error' ? 'bg-red-500 text-white' : 
        'bg-blue-500 text-white'
    }`;
    notification.innerHTML = `
        <div class="flex items-center">
            <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'} mr-2"></i>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Load data on page load
document.addEventListener('DOMContentLoaded', function() {
    loadDashboardData();
    
    // Auto refresh every 5 minutes
    setInterval(loadDashboardData, 300000);
});
</script>
@endsection
