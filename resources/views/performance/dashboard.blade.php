@extends('layouts.app')

@section('title', 'Performance Dashboard')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-tachometer-alt"></i>
                        Performance Dashboard
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary btn-sm" onclick="runOptimization()">
                            <i class="fas fa-cog"></i> Run Optimization
                        </button>
                        <button type="button" class="btn btn-info btn-sm" onclick="refreshDashboard()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Performance Overview Cards -->
                    <div class="row mb-4">
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3 id="api-health-score">-</h3>
                                    <p>API Health Score</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-globe"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3 id="database-health-score">-</h3>
                                    <p>Database Health Score</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-database"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3 id="cache-health-score">-</h3>
                                    <p>Cache Health Score</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-memory"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-danger">
                                <div class="inner">
                                    <h3 id="system-health-score">-</h3>
                                    <p>System Health Score</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-server"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Performance Charts -->
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">API Response Times</h3>
                                </div>
                                <div class="card-body">
                                    <canvas id="responseTimeChart" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Error Rates</h3>
                                </div>
                                <div class="card-body">
                                    <canvas id="errorRateChart" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Performance Metrics Tables -->
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">API Performance Metrics</h3>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Metric</th>
                                                    <th>Value</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody id="api-metrics-table">
                                                <tr>
                                                    <td colspan="3" class="text-center">Loading...</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">System Metrics</h3>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Metric</th>
                                                    <th>Value</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody id="system-metrics-table">
                                                <tr>
                                                    <td colspan="3" class="text-center">Loading...</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Performance Alerts -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Performance Alerts</h3>
                                </div>
                                <div class="card-body">
                                    <div id="performance-alerts">
                                        <div class="text-center">Loading alerts...</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Optimization Modal -->
<div class="modal fade" id="optimizationModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Performance Optimization</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="optimizationType">Optimization Type</label>
                    <select class="form-control" id="optimizationType">
                        <option value="all">All Components</option>
                        <option value="database">Database Only</option>
                        <option value="cache">Cache Only</option>
                        <option value="api">API Only</option>
                    </select>
                </div>
                <div id="optimization-progress" style="display: none;">
                    <div class="progress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                    </div>
                    <div class="text-center mt-2">
                        <span id="optimization-status">Starting optimization...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="startOptimization()">Start Optimization</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let responseTimeChart, errorRateChart;

$(document).ready(function() {
    loadDashboardData();
    initializeCharts();
    
    // Auto-refresh every 30 seconds
    setInterval(loadDashboardData, 30000);
});

function loadDashboardData() {
    loadApiPerformance();
    loadSystemOverview();
    loadPerformanceAlerts();
}

function loadApiPerformance() {
    $.get('/api/performance/api-performance')
        .done(function(data) {
            updateApiMetrics(data);
            updateResponseTimeChart(data);
        })
        .fail(function() {
            console.error('Failed to load API performance data');
        });
}

function loadSystemOverview() {
    $.get('/api/performance/system-overview')
        .done(function(data) {
            updateSystemMetrics(data);
            updateHealthScores(data);
        })
        .fail(function() {
            console.error('Failed to load system overview data');
        });
}

function loadPerformanceAlerts() {
    $.get('/api/performance/alerts')
        .done(function(data) {
            updatePerformanceAlerts(data);
        })
        .fail(function() {
            console.error('Failed to load performance alerts');
        });
}

function updateApiMetrics(data) {
    const tableBody = $('#api-metrics-table');
    tableBody.empty();
    
    if (data.response_times) {
        tableBody.append(`
            <tr>
                <td>Average Response Time</td>
                <td>${data.response_times.avg_response_time_ms || 0}ms</td>
                <td>${getStatusBadge(data.response_times.avg_response_time_ms, 500, 1000)}</td>
            </tr>
        `);
        
        tableBody.append(`
            <tr>
                <td>Error Rate</td>
                <td>${data.error_rates?.error_rate_percentage || 0}%</td>
                <td>${getStatusBadge(data.error_rates?.error_rate_percentage || 0, 1, 5, true)}</td>
            </tr>
        `);
        
        tableBody.append(`
            <tr>
                <td>Total Requests</td>
                <td>${data.throughput?.total_requests || 0}</td>
                <td><span class="badge badge-info">Info</span></td>
            </tr>
        `);
    }
}

function updateSystemMetrics(data) {
    const tableBody = $('#system-metrics-table');
    tableBody.empty();
    
    if (data.system) {
        if (data.system.cpu_usage) {
            tableBody.append(`
                <tr>
                    <td>CPU Usage</td>
                    <td>${data.system.cpu_usage}%</td>
                    <td>${getStatusBadge(data.system.cpu_usage, 70, 90)}</td>
                </tr>
            `);
        }
        
        if (data.system.memory_usage) {
            tableBody.append(`
                <tr>
                    <td>Memory Usage</td>
                    <td>${data.system.memory_usage.current_mb || 0}MB</td>
                    <td>${getStatusBadge(data.system.memory_usage.usage_percentage || 0, 70, 90)}</td>
                </tr>
            `);
        }
        
        if (data.system.disk_usage) {
            tableBody.append(`
                <tr>
                    <td>Disk Usage</td>
                    <td>${data.system.disk_usage.usage_percentage || 0}%</td>
                    <td>${getStatusBadge(data.system.disk_usage.usage_percentage || 0, 80, 95)}</td>
                </tr>
            `);
        }
    }
}

function updateHealthScores(data) {
    if (data.api) {
        $('#api-health-score').text(data.api.health_score || 0);
    }
    if (data.database) {
        $('#database-health-score').text(data.database.health_score || 0);
    }
    if (data.cache) {
        $('#cache-health-score').text(data.cache.health_score || 0);
    }
    if (data.system) {
        $('#system-health-score').text(data.system.health_score || 0);
    }
}

function updatePerformanceAlerts(alerts) {
    const alertsContainer = $('#performance-alerts');
    alertsContainer.empty();
    
    if (alerts.length === 0) {
        alertsContainer.html('<div class="alert alert-success">No performance alerts</div>');
        return;
    }
    
    alerts.forEach(alert => {
        const alertClass = alert.severity === 'high' ? 'danger' : 
                          alert.severity === 'medium' ? 'warning' : 'info';
        
        alertsContainer.append(`
            <div class="alert alert-${alertClass}">
                <strong>${alert.type.toUpperCase()}</strong>: ${alert.message}
                <small class="float-right">${new Date(alert.timestamp).toLocaleString()}</small>
            </div>
        `);
    });
}

function updateResponseTimeChart(data) {
    if (responseTimeChart && data.response_times) {
        responseTimeChart.data.datasets[0].data = [
            data.response_times.avg_response_time_ms || 0,
            data.response_times.min_response_time_ms || 0,
            data.response_times.max_response_time_ms || 0
        ];
        responseTimeChart.update();
    }
}

function initializeCharts() {
    // Response Time Chart
    const responseTimeCtx = document.getElementById('responseTimeChart').getContext('2d');
    responseTimeChart = new Chart(responseTimeCtx, {
        type: 'line',
        data: {
            labels: ['Average', 'Minimum', 'Maximum'],
            datasets: [{
                label: 'Response Time (ms)',
                data: [0, 0, 0],
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1
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

    // Error Rate Chart
    const errorRateCtx = document.getElementById('errorRateChart').getContext('2d');
    errorRateChart = new Chart(errorRateCtx, {
        type: 'doughnut',
        data: {
            labels: ['Success', 'Errors'],
            datasets: [{
                data: [100, 0],
                backgroundColor: [
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(255, 99, 132, 0.8)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}

function getStatusBadge(value, warningThreshold, dangerThreshold, reverse = false) {
    if (reverse) {
        if (value <= warningThreshold) return '<span class="badge badge-success">Good</span>';
        if (value <= dangerThreshold) return '<span class="badge badge-warning">Warning</span>';
        return '<span class="badge badge-danger">Critical</span>';
    } else {
        if (value <= warningThreshold) return '<span class="badge badge-success">Good</span>';
        if (value <= dangerThreshold) return '<span class="badge badge-warning">Warning</span>';
        return '<span class="badge badge-danger">Critical</span>';
    }
}

function runOptimization() {
    $('#optimizationModal').modal('show');
}

function startOptimization() {
    const type = $('#optimizationType').val();
    
    $('#optimization-progress').show();
    $('#optimization-status').text('Starting optimization...');
    
    $.post('/performance/optimize', {
        type: type,
        _token: $('meta[name="csrf-token"]').attr('content')
    })
    .done(function(response) {
        $('#optimization-status').text('Optimization completed successfully!');
        setTimeout(() => {
            $('#optimizationModal').modal('hide');
            loadDashboardData();
        }, 2000);
    })
    .fail(function() {
        $('#optimization-status').text('Optimization failed!');
    });
}

function refreshDashboard() {
    loadDashboardData();
}
</script>
@endsection
