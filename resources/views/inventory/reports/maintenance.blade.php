@extends('layouts.app')

@section('title', 'Maintenance Reports')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('inventory.reports.index') }}">Reports</a></li>
                    <li class="breadcrumb-item active">Maintenance Reports</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-800">Maintenance Reports</h1>
            <p class="mb-0 text-muted">Comprehensive maintenance analysis, cost tracking, and performance metrics</p>
        </div>
        <div>
            <button type="button" class="btn btn-primary" onclick="refreshAllReports()">
                <i class="fas fa-sync-alt"></i> Refresh Data
            </button>
            <div class="btn-group ms-2">
                <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="fas fa-download"></i> Export All
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#" onclick="exportAllReports('pdf')"><i class="fas fa-file-pdf"></i> PDF</a></li>
                    <li><a class="dropdown-item" href="#" onclick="exportAllReports('excel')"><i class="fas fa-file-excel"></i> Excel</a></li>
                    <li><a class="dropdown-item" href="#" onclick="exportAllReports('csv')"><i class="fas fa-file-csv"></i> CSV</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Report Filters</h6>
                </div>
                <div class="card-body">
                    <form id="reportFilters">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="asset_filter" class="form-label">Asset</label>
                                <select class="form-select" id="asset_filter" name="asset">
                                    <option value="">All Assets</option>
                                    <!-- Assets will be loaded dynamically -->
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="technician_filter" class="form-label">Technician</label>
                                <select class="form-select" id="technician_filter" name="technician">
                                    <option value="">All Technicians</option>
                                    <!-- Technicians will be loaded dynamically -->
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="maintenance_type" class="form-label">Maintenance Type</label>
                                <select class="form-select" id="maintenance_type" name="type">
                                    <option value="">All Types</option>
                                    <option value="preventive">Preventive</option>
                                    <option value="corrective">Corrective</option>
                                    <option value="emergency">Emergency</option>
                                    <option value="upgrade">Upgrade</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="maintenance_status" class="form-label">Status</label>
                                <select class="form-select" id="maintenance_status" name="status">
                                    <option value="">All Status</option>
                                    <option value="scheduled">Scheduled</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                    <option value="overdue">Overdue</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-3">
                                <label for="date_from" class="form-label">Date From</label>
                                <input type="date" class="form-control" id="date_from" name="date_from">
                            </div>
                            <div class="col-md-3">
                                <label for="date_to" class="form-label">Date To</label>
                                <input type="date" class="form-control" id="date_to" name="date_to">
                            </div>
                            <div class="col-md-3">
                                <label for="priority_filter" class="form-label">Priority</label>
                                <select class="form-select" id="priority_filter" name="priority">
                                    <option value="">All Priorities</option>
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="button" class="btn btn-primary me-2" onclick="applyFilters()">
                                    <i class="fas fa-filter"></i> Apply Filters
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="resetFilters()">
                                    <i class="fas fa-undo"></i> Reset
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Maintenance</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalMaintenance">0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tools fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Completed This Month</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="completedMaintenance">0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Overdue Maintenance</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="overdueMaintenance">0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Cost (This Month)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalCost">₹0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-rupee-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <!-- Maintenance Trends -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Maintenance Trends (Last 12 Months)</h6>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-primary active" onclick="loadTrendChart('count')">Count</button>
                        <button type="button" class="btn btn-outline-primary" onclick="loadTrendChart('cost')">Cost</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="maintenanceTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Maintenance Type Distribution -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Maintenance Type Distribution</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="maintenanceTypeChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cost Analysis and Performance -->
    <div class="row mb-4">
        <!-- Cost Breakdown -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Cost Breakdown by Category</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="costBreakdownChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Technician Performance -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Technician Performance</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="technicianPerformanceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Reports -->
    <div class="row">
        <!-- Recent Maintenance Activities -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Maintenance Activities</h6>
                    <button class="btn btn-sm btn-outline-primary" onclick="exportReport('recent-activities')">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="recentActivitiesTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>Asset</th>
                                    <th>Type</th>
                                    <th>Technician</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Cost</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be loaded dynamically -->
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted">
                            Showing <span id="recentActivitiesStart">0</span> to <span id="recentActivitiesEnd">0</span> of <span id="recentActivitiesTotal">0</span> activities
                        </div>
                        <nav>
                            <ul class="pagination pagination-sm mb-0" id="recentActivitiesPagination">
                                <!-- Pagination will be generated dynamically -->
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- High Cost Maintenance -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">High Cost Maintenance</h6>
                    <button class="btn btn-sm btn-outline-primary" onclick="exportReport('high-cost')">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="highCostTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>Asset</th>
                                    <th>Type</th>
                                    <th>Date</th>
                                    <th>Technician</th>
                                    <th>Total Cost</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be loaded dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Asset Maintenance History and Overdue Items -->
    <div class="row">
        <!-- Asset Maintenance Frequency -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Asset Maintenance Frequency</h6>
                    <button class="btn btn-sm btn-outline-primary" onclick="exportReport('asset-frequency')">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="assetFrequencyTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>Asset</th>
                                    <th>Category</th>
                                    <th>Total Maintenance</th>
                                    <th>Last Maintenance</th>
                                    <th>Total Cost</th>
                                    <th>Avg Cost</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be loaded dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Overdue Maintenance -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Overdue Maintenance</h6>
                    <button class="btn btn-sm btn-outline-primary" onclick="exportReport('overdue-maintenance')">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="overdueMaintenanceTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>Asset</th>
                                    <th>Type</th>
                                    <th>Scheduled Date</th>
                                    <th>Days Overdue</th>
                                    <th>Priority</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be loaded dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Metrics -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Maintenance Performance Metrics</h6>
                    <button class="btn btn-sm btn-outline-primary" onclick="exportReport('performance-metrics')">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Completion Rate -->
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-success" id="completionRate">0%</h4>
                                <p class="text-muted">Completion Rate</p>
                            </div>
                        </div>

                        <!-- Average Response Time -->
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-info" id="avgResponseTime">0 days</h4>
                                <p class="text-muted">Avg Response Time</p>
                            </div>
                        </div>

                        <!-- Cost Efficiency -->
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-warning" id="costEfficiency">₹0</h4>
                                <p class="text-muted">Avg Cost per Maintenance</p>
                            </div>
                        </div>

                        <!-- Preventive vs Corrective -->
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-primary" id="preventiveRatio">0%</h4>
                                <p class="text-muted">Preventive Maintenance</p>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- Monthly Performance Chart -->
                    <div class="row">
                        <div class="col-12">
                            <h6 class="font-weight-bold text-primary mb-3">Monthly Performance Trends</h6>
                            <div class="chart-area">
                                <canvas id="performanceMetricsChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let maintenanceTrendChart;
let maintenanceTypeChart;
let costBreakdownChart;
let technicianPerformanceChart;
let performanceMetricsChart;
let currentFilters = {};

$(document).ready(function() {
    initializeFilters();
    loadSummaryData();
    initializeCharts();
    loadRecentActivities();
    loadHighCostMaintenance();
    loadAssetFrequency();
    loadOverdueMaintenance();
    loadPerformanceMetrics();
    
    // Set default date range (last 30 days)
    const today = new Date();
    const thirtyDaysAgo = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000));
    $('#date_to').val(today.toISOString().split('T')[0]);
    $('#date_from').val(thirtyDaysAgo.toISOString().split('T')[0]);
});

function initializeFilters() {
    // Load assets
    $.get('/api/inventory/assets')
        .done(function(assets) {
            const assetSelect = $('#asset_filter');
            assets.forEach(asset => {
                assetSelect.append(`<option value="${asset.id}">${asset.name}</option>`);
            });
        });
    
    // Load technicians
    $.get('/api/technicians')
        .done(function(technicians) {
            const technicianSelect = $('#technician_filter');
            technicians.forEach(technician => {
                technicianSelect.append(`<option value="${technician.id}">${technician.name}</option>`);
            });
        });
}

function loadSummaryData() {
    $.get('/api/reports/maintenance/summary', currentFilters)
        .done(function(data) {
            $('#totalMaintenance').text(data.total_maintenance || 0);
            $('#completedMaintenance').text(data.completed_maintenance || 0);
            $('#overdueMaintenance').text(data.overdue_maintenance || 0);
            $('#totalCost').text(`₹${parseFloat(data.total_cost || 0).toLocaleString()}`);
        })
        .fail(function() {
            console.error('Error loading summary data');
        });
}

function initializeCharts() {
    // Maintenance Trend Chart
    const trendCtx = document.getElementById('maintenanceTrendChart').getContext('2d');
    maintenanceTrendChart = new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Scheduled',
                data: [],
                borderColor: '#4e73df',
                backgroundColor: 'rgba(78, 115, 223, 0.1)',
                tension: 0.3
            }, {
                label: 'Completed',
                data: [],
                borderColor: '#1cc88a',
                backgroundColor: 'rgba(28, 200, 138, 0.1)',
                tension: 0.3
            }, {
                label: 'Overdue',
                data: [],
                borderColor: '#f6c23e',
                backgroundColor: 'rgba(246, 194, 62, 0.1)',
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    position: 'top'
                }
            }
        }
    });
    
    // Maintenance Type Chart
    const typeCtx = document.getElementById('maintenanceTypeChart').getContext('2d');
    maintenanceTypeChart = new Chart(typeCtx, {
        type: 'doughnut',
        data: {
            labels: ['Preventive', 'Corrective', 'Emergency', 'Upgrade'],
            datasets: [{
                data: [],
                backgroundColor: ['#1cc88a', '#f6c23e', '#e74a3b', '#36b9cc']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    
    // Cost Breakdown Chart
    const costCtx = document.getElementById('costBreakdownChart').getContext('2d');
    costBreakdownChart = new Chart(costCtx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Labor Cost',
                data: [],
                backgroundColor: '#4e73df'
            }, {
                label: 'Parts Cost',
                data: [],
                backgroundColor: '#1cc88a'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    stacked: true
                },
                y: {
                    stacked: true,
                    beginAtZero: true
                }
            }
        }
    });
    
    // Technician Performance Chart
    const techCtx = document.getElementById('technicianPerformanceChart').getContext('2d');
    technicianPerformanceChart = new Chart(techCtx, {
        type: 'horizontalBar',
        data: {
            labels: [],
            datasets: [{
                label: 'Completed Tasks',
                data: [],
                backgroundColor: '#1cc88a'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // Performance Metrics Chart
    const metricsCtx = document.getElementById('performanceMetricsChart').getContext('2d');
    performanceMetricsChart = new Chart(metricsCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Completion Rate (%)',
                data: [],
                borderColor: '#1cc88a',
                backgroundColor: 'rgba(28, 200, 138, 0.1)',
                yAxisID: 'y'
            }, {
                label: 'Avg Response Time (days)',
                data: [],
                borderColor: '#36b9cc',
                backgroundColor: 'rgba(54, 185, 204, 0.1)',
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    beginAtZero: true,
                    max: 100
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    beginAtZero: true,
                    grid: {
                        drawOnChartArea: false,
                    }
                }
            }
        }
    });
    
    // Load initial chart data
    loadTrendChart('count');
    loadMaintenanceTypeChart();
    loadCostBreakdownChart();
    loadTechnicianPerformanceChart();
    loadPerformanceMetricsChart();
}

function loadTrendChart(type) {
    // Update active button
    $('.btn-group .btn').removeClass('active');
    $(`button[onclick="loadTrendChart('${type}')"]`).addClass('active');
    
    $.get(`/api/reports/maintenance/trend-chart?type=${type}`, currentFilters)
        .done(function(data) {
            maintenanceTrendChart.data.labels = data.labels;
            maintenanceTrendChart.data.datasets[0].data = data.scheduled;
            maintenanceTrendChart.data.datasets[1].data = data.completed;
            maintenanceTrendChart.data.datasets[2].data = data.overdue;
            
            // Update y-axis label based on type
            if (type === 'cost') {
                maintenanceTrendChart.options.scales.y.title = {
                    display: true,
                    text: 'Cost (₹)'
                };
            } else {
                maintenanceTrendChart.options.scales.y.title = {
                    display: true,
                    text: 'Count'
                };
            }
            
            maintenanceTrendChart.update();
        })
        .fail(function() {
            console.error('Error loading trend chart');
        });
}

function loadMaintenanceTypeChart() {
    $.get('/api/reports/maintenance/type-chart', currentFilters)
        .done(function(data) {
            maintenanceTypeChart.data.datasets[0].data = data.values;
            maintenanceTypeChart.update();
        })
        .fail(function() {
            console.error('Error loading type chart');
        });
}

function loadCostBreakdownChart() {
    $.get('/api/reports/maintenance/cost-breakdown-chart', currentFilters)
        .done(function(data) {
            costBreakdownChart.data.labels = data.labels;
            costBreakdownChart.data.datasets[0].data = data.labor_costs;
            costBreakdownChart.data.datasets[1].data = data.parts_costs;
            costBreakdownChart.update();
        })
        .fail(function() {
            console.error('Error loading cost breakdown chart');
        });
}

function loadTechnicianPerformanceChart() {
    $.get('/api/reports/maintenance/technician-performance-chart', currentFilters)
        .done(function(data) {
            technicianPerformanceChart.data.labels = data.labels;
            technicianPerformanceChart.data.datasets[0].data = data.values;
            technicianPerformanceChart.update();
        })
        .fail(function() {
            console.error('Error loading technician performance chart');
        });
}

function loadPerformanceMetricsChart() {
    $.get('/api/reports/maintenance/performance-metrics-chart', currentFilters)
        .done(function(data) {
            performanceMetricsChart.data.labels = data.labels;
            performanceMetricsChart.data.datasets[0].data = data.completion_rates;
            performanceMetricsChart.data.datasets[1].data = data.response_times;
            performanceMetricsChart.update();
        })
        .fail(function() {
            console.error('Error loading performance metrics chart');
        });
}

function loadRecentActivities(page = 1) {
    $.get(`/api/reports/maintenance/recent-activities?page=${page}`, currentFilters)
        .done(function(data) {
            const tbody = $('#recentActivitiesTable tbody');
            tbody.empty();
            
            data.data.forEach(activity => {
                const statusBadge = getMaintenanceStatusBadge(activity.status);
                const cost = activity.total_cost ? `₹${parseFloat(activity.total_cost).toLocaleString()}` : 'N/A';
                
                tbody.append(`
                    <tr>
                        <td>${activity.asset_name}</td>
                        <td>${getMaintenanceTypeBadge(activity.type)}</td>
                        <td>${activity.technician_name || 'N/A'}</td>
                        <td>${formatDate(activity.scheduled_date)}</td>
                        <td>${statusBadge}</td>
                        <td>${cost}</td>
                    </tr>
                `);
            });
            
            // Update pagination info
            $('#recentActivitiesStart').text(data.from || 0);
            $('#recentActivitiesEnd').text(data.to || 0);
            $('#recentActivitiesTotal').text(data.total || 0);
            
            // Generate pagination
            generatePagination('recentActivitiesPagination', data, 'loadRecentActivities');
        })
        .fail(function() {
            console.error('Error loading recent activities');
        });
}

function loadHighCostMaintenance() {
    $.get('/api/reports/maintenance/high-cost', currentFilters)
        .done(function(data) {
            const tbody = $('#highCostTable tbody');
            tbody.empty();
            
            data.forEach(maintenance => {
                const statusBadge = getMaintenanceStatusBadge(maintenance.status);
                const cost = `₹${parseFloat(maintenance.total_cost).toLocaleString()}`;
                
                tbody.append(`
                    <tr>
                        <td>${maintenance.asset_name}</td>
                        <td>${getMaintenanceTypeBadge(maintenance.type)}</td>
                        <td>${formatDate(maintenance.scheduled_date)}</td>
                        <td>${maintenance.technician_name || 'N/A'}</td>
                        <td><strong>${cost}</strong></td>
                        <td>${statusBadge}</td>
                    </tr>
                `);
            });
        })
        .fail(function() {
            console.error('Error loading high cost maintenance');
        });
}

function loadAssetFrequency() {
    $.get('/api/reports/maintenance/asset-frequency', currentFilters)
        .done(function(data) {
            const tbody = $('#assetFrequencyTable tbody');
            tbody.empty();
            
            data.forEach(asset => {
                const totalCost = `₹${parseFloat(asset.total_cost).toLocaleString()}`;
                const avgCost = `₹${parseFloat(asset.avg_cost).toLocaleString()}`;
                
                tbody.append(`
                    <tr>
                        <td>${asset.asset_name}</td>
                        <td>${asset.category}</td>
                        <td><span class="badge bg-primary">${asset.maintenance_count}</span></td>
                        <td>${formatDate(asset.last_maintenance_date)}</td>
                        <td>${totalCost}</td>
                        <td>${avgCost}</td>
                    </tr>
                `);
            });
        })
        .fail(function() {
            console.error('Error loading asset frequency');
        });
}

function loadOverdueMaintenance() {
    $.get('/api/reports/maintenance/overdue', currentFilters)
        .done(function(data) {
            const tbody = $('#overdueMaintenanceTable tbody');
            tbody.empty();
            
            data.forEach(maintenance => {
                const priorityBadge = getPriorityBadge(maintenance.priority);
                const daysOverdue = calculateDaysOverdue(maintenance.scheduled_date);
                
                tbody.append(`
                    <tr>
                        <td>${maintenance.asset_name}</td>
                        <td>${getMaintenanceTypeBadge(maintenance.type)}</td>
                        <td>${formatDate(maintenance.scheduled_date)}</td>
                        <td><span class="badge bg-danger">${daysOverdue} days</span></td>
                        <td>${priorityBadge}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="rescheduleMaintenance(${maintenance.id})">
                                <i class="fas fa-calendar-alt"></i> Reschedule
                            </button>
                        </td>
                    </tr>
                `);
            });
        })
        .fail(function() {
            console.error('Error loading overdue maintenance');
        });
}

function loadPerformanceMetrics() {
    $.get('/api/reports/maintenance/performance-metrics', currentFilters)
        .done(function(data) {
            $('#completionRate').text(`${parseFloat(data.completion_rate || 0).toFixed(1)}%`);
            $('#avgResponseTime').text(`${parseFloat(data.avg_response_time || 0).toFixed(1)} days`);
            $('#costEfficiency').text(`₹${parseFloat(data.avg_cost || 0).toLocaleString()}`);
            $('#preventiveRatio').text(`${parseFloat(data.preventive_ratio || 0).toFixed(1)}%`);
        })
        .fail(function() {
            console.error('Error loading performance metrics');
        });
}

function applyFilters() {
    currentFilters = {
        asset: $('#asset_filter').val(),
        technician: $('#technician_filter').val(),
        type: $('#maintenance_type').val(),
        status: $('#maintenance_status').val(),
        date_from: $('#date_from').val(),
        date_to: $('#date_to').val(),
        priority: $('#priority_filter').val()
    };
    
    // Remove empty filters
    Object.keys(currentFilters).forEach(key => {
        if (!currentFilters[key]) {
            delete currentFilters[key];
        }
    });
    
    refreshAllReports();
}

function resetFilters() {
    $('#reportFilters')[0].reset();
    currentFilters = {};
    refreshAllReports();
}

function refreshAllReports() {
    loadSummaryData();
    loadTrendChart('count');
    loadMaintenanceTypeChart();
    loadCostBreakdownChart();
    loadTechnicianPerformanceChart();
    loadPerformanceMetricsChart();
    loadRecentActivities();
    loadHighCostMaintenance();
    loadAssetFrequency();
    loadOverdueMaintenance();
    loadPerformanceMetrics();
}

function exportReport(type) {
    const params = new URLSearchParams({
        type: type,
        ...currentFilters
    });
    
    window.location.href = `/api/reports/maintenance/export?${params.toString()}`;
}

function exportAllReports(format) {
    const params = new URLSearchParams({
        format: format,
        ...currentFilters
    });
    
    window.location.href = `/api/reports/maintenance/export-all?${params.toString()}`;
}

function rescheduleMaintenance(maintenanceId) {
    // Implementation for rescheduling maintenance
    window.location.href = `/inventory/maintenance/${maintenanceId}/edit`;
}

// Utility functions
function getMaintenanceStatusBadge(status) {
    const badges = {
        'scheduled': '<span class="badge bg-primary">Scheduled</span>',
        'in_progress': '<span class="badge bg-warning">In Progress</span>',
        'completed': '<span class="badge bg-success">Completed</span>',
        'cancelled': '<span class="badge bg-secondary">Cancelled</span>',
        'overdue': '<span class="badge bg-danger">Overdue</span>'
    };
    return badges[status] || '<span class="badge bg-secondary">Unknown</span>';
}

function getMaintenanceTypeBadge(type) {
    const badges = {
        'preventive': '<span class="badge bg-success">Preventive</span>',
        'corrective': '<span class="badge bg-warning">Corrective</span>',
        'emergency': '<span class="badge bg-danger">Emergency</span>',
        'upgrade': '<span class="badge bg-info">Upgrade</span>'
    };
    return badges[type] || '<span class="badge bg-secondary">Unknown</span>';
}

function getPriorityBadge(priority) {
    const badges = {
        'low': '<span class="badge bg-secondary">Low</span>',
        'medium': '<span class="badge bg-primary">Medium</span>',
        'high': '<span class="badge bg-warning">High</span>',
        'urgent': '<span class="badge bg-danger">Urgent</span>'
    };
    return badges[priority] || '<span class="badge bg-secondary">Unknown</span>';
}

function calculateDaysOverdue(scheduledDate) {
    const scheduled = new Date(scheduledDate);
    const today = new Date();
    const diffTime = Math.abs(today - scheduled);
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString();
}

function generatePagination(containerId, data, callback) {
    const container = $(`#${containerId}`);
    container.empty();
    
    if (data.last_page <= 1) return;
    
    // Previous button
    if (data.current_page > 1) {
        container.append(`
            <li class="page-item">
                <a class="page-link" href="#" onclick="${callback}(${data.current_page - 1})">Previous</a>
            </li>
        `);
    }
    
    // Page numbers
    for (let i = Math.max(1, data.current_page - 2); i <= Math.min(data.last_page, data.current_page + 2); i++) {
        const active = i === data.current_page ? 'active' : '';
        container.append(`
            <li class="page-item ${active}">
                <a class="page-link" href="#" onclick="${callback}(${i})">${i}</a>
            </li>
        `);
    }
    
    // Next button
    if (data.current_page < data.last_page) {
        container.append(`
            <li class="page-item">
                <a class="page-link" href="#" onclick="${callback}(${data.current_page + 1})">Next</a>
            </li>
        `);
    }
}

function showToast(message, type) {
    // Implementation for toast notifications
    console.log(`${type}: ${message}`);
}
</script>

<style>
.cursor-pointer {
    cursor: pointer;
}

.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}

.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}

.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}

.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}

.chart-area {
    position: relative;
    height: 400px;
}

.chart-pie {
    position: relative;
    height: 300px;
}
</style>
@endsection