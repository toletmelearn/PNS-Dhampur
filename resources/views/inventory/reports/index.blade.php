@extends('layouts.app')

@section('title', 'Inventory Reports & Analytics')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Inventory Reports & Analytics</h1>
            <p class="mb-0 text-muted">Comprehensive reporting and analytics for inventory management</p>
        </div>
        <div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#customReportModal">
                <i class="fas fa-plus"></i> Create Custom Report
            </button>
            <div class="btn-group ms-2">
                <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="fas fa-download"></i> Export
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#" onclick="exportReport('pdf')"><i class="fas fa-file-pdf"></i> PDF</a></li>
                    <li><a class="dropdown-item" href="#" onclick="exportReport('excel')"><i class="fas fa-file-excel"></i> Excel</a></li>
                    <li><a class="dropdown-item" href="#" onclick="exportReport('csv')"><i class="fas fa-file-csv"></i> CSV</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Report Categories -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="report-category text-center p-3 border rounded cursor-pointer" onclick="showReportCategory('stock')">
                                <i class="fas fa-boxes fa-3x text-primary mb-3"></i>
                                <h5>Stock Reports</h5>
                                <p class="text-muted">Inventory levels, stock movements, and valuation</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="report-category text-center p-3 border rounded cursor-pointer" onclick="showReportCategory('allocation')">
                                <i class="fas fa-user-check fa-3x text-success mb-3"></i>
                                <h5>Allocation Reports</h5>
                                <p class="text-muted">Asset allocations, returns, and utilization</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="report-category text-center p-3 border rounded cursor-pointer" onclick="showReportCategory('maintenance')">
                                <i class="fas fa-tools fa-3x text-warning mb-3"></i>
                                <h5>Maintenance Reports</h5>
                                <p class="text-muted">Maintenance schedules, costs, and performance</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="report-category text-center p-3 border rounded cursor-pointer" onclick="showReportCategory('financial')">
                                <i class="fas fa-chart-line fa-3x text-info mb-3"></i>
                                <h5>Financial Reports</h5>
                                <p class="text-muted">Cost analysis, depreciation, and ROI</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dashboard Overview -->
    <div class="row mb-4">
        <!-- Key Metrics -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Inventory Value</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalInventoryValue">$0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Active Allocations</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="activeAllocations">0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-check fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Maintenance</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="pendingMaintenance">0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tools fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Low Stock Items</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="lowStockItems">0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <!-- Inventory Value Trend -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Inventory Value Trend</h6>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-primary active" onclick="loadInventoryTrend('6months')">6M</button>
                        <button type="button" class="btn btn-outline-primary" onclick="loadInventoryTrend('1year')">1Y</button>
                        <button type="button" class="btn btn-outline-primary" onclick="loadInventoryTrend('2years')">2Y</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="inventoryTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Category Distribution -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Category Distribution</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="categoryDistributionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Content Area -->
    <div id="reportContent" class="row" style="display: none;">
        <!-- Dynamic content will be loaded here -->
    </div>

    <!-- Quick Reports -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Reports</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="list-group">
                                <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Stock Level Report</h6>
                                        <small class="text-muted">Current stock levels by category</small>
                                    </div>
                                    <button class="btn btn-sm btn-outline-primary" onclick="generateQuickReport('stock-levels')">
                                        <i class="fas fa-play"></i>
                                    </button>
                                </div>
                                <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Low Stock Alert</h6>
                                        <small class="text-muted">Items below minimum threshold</small>
                                    </div>
                                    <button class="btn btn-sm btn-outline-primary" onclick="generateQuickReport('low-stock')">
                                        <i class="fas fa-play"></i>
                                    </button>
                                </div>
                                <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Stock Movement</h6>
                                        <small class="text-muted">Recent stock transactions</small>
                                    </div>
                                    <button class="btn btn-sm btn-outline-primary" onclick="generateQuickReport('stock-movement')">
                                        <i class="fas fa-play"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="list-group">
                                <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Active Allocations</h6>
                                        <small class="text-muted">Currently allocated assets</small>
                                    </div>
                                    <button class="btn btn-sm btn-outline-primary" onclick="generateQuickReport('active-allocations')">
                                        <i class="fas fa-play"></i>
                                    </button>
                                </div>
                                <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Overdue Returns</h6>
                                        <small class="text-muted">Assets past return date</small>
                                    </div>
                                    <button class="btn btn-sm btn-outline-primary" onclick="generateQuickReport('overdue-returns')">
                                        <i class="fas fa-play"></i>
                                    </button>
                                </div>
                                <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Allocation History</h6>
                                        <small class="text-muted">Past allocation records</small>
                                    </div>
                                    <button class="btn btn-sm btn-outline-primary" onclick="generateQuickReport('allocation-history')">
                                        <i class="fas fa-play"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="list-group">
                                <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Maintenance Schedule</h6>
                                        <small class="text-muted">Upcoming maintenance tasks</small>
                                    </div>
                                    <button class="btn btn-sm btn-outline-primary" onclick="generateQuickReport('maintenance-schedule')">
                                        <i class="fas fa-play"></i>
                                    </button>
                                </div>
                                <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Maintenance Costs</h6>
                                        <small class="text-muted">Maintenance cost analysis</small>
                                    </div>
                                    <button class="btn btn-sm btn-outline-primary" onclick="generateQuickReport('maintenance-costs')">
                                        <i class="fas fa-play"></i>
                                    </button>
                                </div>
                                <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Asset Performance</h6>
                                        <small class="text-muted">Asset reliability metrics</small>
                                    </div>
                                    <button class="btn btn-sm btn-outline-primary" onclick="generateQuickReport('asset-performance')">
                                        <i class="fas fa-play"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom Report Modal -->
<div class="modal fade" id="customReportModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Custom Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="customReportForm">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="report_name" class="form-label">Report Name *</label>
                            <input type="text" class="form-control" id="report_name" name="report_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="report_type" class="form-label">Report Type *</label>
                            <select class="form-select" id="report_type" name="report_type" required>
                                <option value="">Select Type</option>
                                <option value="stock">Stock Report</option>
                                <option value="allocation">Allocation Report</option>
                                <option value="maintenance">Maintenance Report</option>
                                <option value="financial">Financial Report</option>
                                <option value="custom">Custom Query</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="date_from" class="form-label">Date From</label>
                            <input type="date" class="form-control" id="date_from" name="date_from">
                        </div>
                        <div class="col-md-6">
                            <label for="date_to" class="form-label">Date To</label>
                            <input type="date" class="form-control" id="date_to" name="date_to">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="filters" class="form-label">Filters</label>
                        <div id="reportFilters">
                            <!-- Dynamic filters will be loaded based on report type -->
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="columns" class="form-label">Columns to Include</label>
                        <div id="reportColumns">
                            <!-- Dynamic columns will be loaded based on report type -->
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="group_by" class="form-label">Group By</label>
                            <select class="form-select" id="group_by" name="group_by">
                                <option value="">No Grouping</option>
                                <option value="category">Category</option>
                                <option value="location">Location</option>
                                <option value="department">Department</option>
                                <option value="date">Date</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="sort_by" class="form-label">Sort By</label>
                            <select class="form-select" id="sort_by" name="sort_by">
                                <option value="name">Name</option>
                                <option value="date">Date</option>
                                <option value="value">Value</option>
                                <option value="quantity">Quantity</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="format" class="form-label">Output Format</label>
                            <select class="form-select" id="format" name="format">
                                <option value="table">Table View</option>
                                <option value="chart">Chart View</option>
                                <option value="both">Table + Chart</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="schedule" class="form-label">Schedule</label>
                            <select class="form-select" id="schedule" name="schedule">
                                <option value="">One-time Report</option>
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                                <option value="quarterly">Quarterly</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="save_report" name="save_report">
                            <label class="form-check-label" for="save_report">
                                Save this report for future use
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3" id="emailSection" style="display: none;">
                        <label for="email_recipients" class="form-label">Email Recipients</label>
                        <input type="email" class="form-control" id="email_recipients" name="email_recipients" multiple placeholder="Enter email addresses separated by commas">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="generateCustomReport()">
                    <i class="fas fa-chart-bar"></i> Generate Report
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Report Results Modal -->
<div class="modal fade" id="reportResultsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reportResultsTitle">Report Results</h5>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-primary me-2" onclick="exportCurrentReport('pdf')">
                        <i class="fas fa-file-pdf"></i> PDF
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary me-2" onclick="exportCurrentReport('excel')">
                        <i class="fas fa-file-excel"></i> Excel
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body">
                <div id="reportResultsContent">
                    <!-- Report results will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let inventoryTrendChart;
let categoryDistributionChart;
let currentReportData = null;

$(document).ready(function() {
    loadDashboardMetrics();
    initializeCharts();
    
    // Initialize event listeners
    $('#report_type').on('change', function() {
        loadReportTypeOptions($(this).val());
    });
    
    $('#schedule').on('change', function() {
        if ($(this).val()) {
            $('#emailSection').show();
        } else {
            $('#emailSection').hide();
        }
    });
    
    // Set default date range (last 30 days)
    const today = new Date();
    const thirtyDaysAgo = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000));
    $('#date_to').val(today.toISOString().split('T')[0]);
    $('#date_from').val(thirtyDaysAgo.toISOString().split('T')[0]);
});

function loadDashboardMetrics() {
    $.get('/api/reports/dashboard-metrics')
        .done(function(metrics) {
            $('#totalInventoryValue').text(`$${parseFloat(metrics.total_inventory_value || 0).toLocaleString()}`);
            $('#activeAllocations').text(metrics.active_allocations || 0);
            $('#pendingMaintenance').text(metrics.pending_maintenance || 0);
            $('#lowStockItems').text(metrics.low_stock_items || 0);
        })
        .fail(function() {
            console.error('Error loading dashboard metrics');
        });
}

function initializeCharts() {
    // Initialize Inventory Trend Chart
    const trendCtx = document.getElementById('inventoryTrendChart').getContext('2d');
    inventoryTrendChart = new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Inventory Value',
                data: [],
                borderColor: '#4e73df',
                backgroundColor: 'rgba(78, 115, 223, 0.1)',
                borderWidth: 2,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Value: $' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            }
        }
    });
    
    // Initialize Category Distribution Chart
    const categoryCtx = document.getElementById('categoryDistributionChart').getContext('2d');
    categoryDistributionChart = new Chart(categoryCtx, {
        type: 'doughnut',
        data: {
            labels: [],
            datasets: [{
                data: [],
                backgroundColor: [
                    '#4e73df',
                    '#1cc88a',
                    '#36b9cc',
                    '#f6c23e',
                    '#e74a3b',
                    '#858796'
                ]
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
    
    // Load initial data
    loadInventoryTrend('6months');
    loadCategoryDistribution();
}

function loadInventoryTrend(period) {
    // Update active button
    $('.btn-group .btn').removeClass('active');
    $(`button[onclick="loadInventoryTrend('${period}')"]`).addClass('active');
    
    $.get(`/api/reports/inventory-trend?period=${period}`)
        .done(function(data) {
            inventoryTrendChart.data.labels = data.labels;
            inventoryTrendChart.data.datasets[0].data = data.values;
            inventoryTrendChart.update();
        })
        .fail(function() {
            console.error('Error loading inventory trend data');
        });
}

function loadCategoryDistribution() {
    $.get('/api/reports/category-distribution')
        .done(function(data) {
            categoryDistributionChart.data.labels = data.labels;
            categoryDistributionChart.data.datasets[0].data = data.values;
            categoryDistributionChart.update();
        })
        .fail(function() {
            console.error('Error loading category distribution data');
        });
}

function showReportCategory(category) {
    // Highlight selected category
    $('.report-category').removeClass('border-primary bg-light');
    $(event.target).closest('.report-category').addClass('border-primary bg-light');
    
    // Load category-specific reports
    loadCategoryReports(category);
}

function loadCategoryReports(category) {
    const reportContent = $('#reportContent');
    reportContent.show();
    
    // Show loading
    reportContent.html(`
        <div class="col-12">
            <div class="card shadow">
                <div class="card-body text-center">
                    <i class="fas fa-spinner fa-spin fa-2x text-primary mb-3"></i>
                    <p>Loading ${category} reports...</p>
                </div>
            </div>
        </div>
    `);
    
    $.get(`/api/reports/category/${category}`)
        .done(function(reports) {
            let content = `
                <div class="col-12">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">${category.charAt(0).toUpperCase() + category.slice(1)} Reports</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
            `;
            
            reports.forEach(report => {
                content += `
                    <div class="col-md-4 mb-3">
                        <div class="card border-left-${getReportColor(report.type)}">
                            <div class="card-body">
                                <h6 class="card-title">${report.name}</h6>
                                <p class="card-text text-muted">${report.description}</p>
                                <button class="btn btn-sm btn-outline-primary" onclick="generateReport('${report.id}')">
                                    <i class="fas fa-play"></i> Generate
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            content += `
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            reportContent.html(content);
        })
        .fail(function() {
            reportContent.html(`
                <div class="col-12">
                    <div class="card shadow">
                        <div class="card-body text-center">
                            <i class="fas fa-exclamation-triangle fa-2x text-warning mb-3"></i>
                            <p>Error loading reports for this category</p>
                        </div>
                    </div>
                </div>
            `);
        });
}

function loadReportTypeOptions(type) {
    const filtersContainer = $('#reportFilters');
    const columnsContainer = $('#reportColumns');
    
    filtersContainer.empty();
    columnsContainer.empty();
    
    if (!type) return;
    
    $.get(`/api/reports/type-options/${type}`)
        .done(function(options) {
            // Load filters
            if (options.filters) {
                options.filters.forEach(filter => {
                    filtersContainer.append(`
                        <div class="mb-2">
                            <label class="form-label">${filter.label}</label>
                            ${generateFilterInput(filter)}
                        </div>
                    `);
                });
            }
            
            // Load columns
            if (options.columns) {
                options.columns.forEach(column => {
                    columnsContainer.append(`
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="col_${column.key}" name="columns[]" value="${column.key}" ${column.default ? 'checked' : ''}>
                            <label class="form-check-label" for="col_${column.key}">${column.label}</label>
                        </div>
                    `);
                });
            }
        })
        .fail(function() {
            console.error('Error loading report type options');
        });
}

function generateFilterInput(filter) {
    switch (filter.type) {
        case 'select':
            let options = filter.options.map(opt => `<option value="${opt.value}">${opt.label}</option>`).join('');
            return `<select class="form-select" name="filter_${filter.key}"><option value="">All</option>${options}</select>`;
        case 'multiselect':
            let checkboxes = filter.options.map(opt => `
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="filter_${filter.key}[]" value="${opt.value}" id="filter_${filter.key}_${opt.value}">
                    <label class="form-check-label" for="filter_${filter.key}_${opt.value}">${opt.label}</label>
                </div>
            `).join('');
            return `<div>${checkboxes}</div>`;
        case 'date':
            return `<input type="date" class="form-control" name="filter_${filter.key}">`;
        case 'number':
            return `<input type="number" class="form-control" name="filter_${filter.key}" placeholder="${filter.placeholder || ''}">`;
        default:
            return `<input type="text" class="form-control" name="filter_${filter.key}" placeholder="${filter.placeholder || ''}">`;
    }
}

function generateQuickReport(reportType) {
    showLoading();
    
    $.get(`/api/reports/quick/${reportType}`)
        .done(function(data) {
            currentReportData = data;
            displayReportResults(data, `Quick Report: ${reportType.replace('-', ' ').toUpperCase()}`);
        })
        .fail(function(xhr) {
            hideLoading();
            showToast(xhr.responseJSON?.message || 'Error generating report', 'error');
        });
}

function generateCustomReport() {
    const formData = new FormData($('#customReportForm')[0]);
    
    // Get selected columns
    const columns = [];
    $('input[name="columns[]"]:checked').each(function() {
        columns.push($(this).val());
    });
    formData.append('columns', JSON.stringify(columns));
    
    showLoading();
    
    $.ajax({
        url: '/api/reports/custom',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(data) {
            currentReportData = data;
            $('#customReportModal').modal('hide');
            displayReportResults(data, $('#report_name').val() || 'Custom Report');
        },
        error: function(xhr) {
            hideLoading();
            if (xhr.status === 422) {
                const errors = xhr.responseJSON.errors;
                Object.keys(errors).forEach(key => {
                    const input = $(`[name="${key}"]`);
                    input.addClass('is-invalid');
                    
                    const errorDiv = input.next('.invalid-feedback');
                    if (errorDiv.length) {
                        errorDiv.text(errors[key][0]);
                    } else {
                        input.after(`<div class="invalid-feedback">${errors[key][0]}</div>`);
                    }
                });
                showToast('Please correct the form errors', 'error');
            } else {
                showToast(xhr.responseJSON?.message || 'Error generating report', 'error');
            }
        }
    });
}

function displayReportResults(data, title) {
    hideLoading();
    
    $('#reportResultsTitle').text(title);
    const container = $('#reportResultsContent');
    container.empty();
    
    // Add summary if available
    if (data.summary) {
        container.append(`
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="card-title">Summary</h6>
                            <div class="row">
                                ${Object.entries(data.summary).map(([key, value]) => `
                                    <div class="col-md-3 text-center">
                                        <h4 class="text-primary">${value}</h4>
                                        <small class="text-muted">${key.replace('_', ' ').toUpperCase()}</small>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `);
    }
    
    // Add chart if available
    if (data.chart) {
        container.append(`
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <canvas id="reportChart" height="400"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        `);
        
        // Initialize chart
        setTimeout(() => {
            const ctx = document.getElementById('reportChart').getContext('2d');
            new Chart(ctx, data.chart);
        }, 100);
    }
    
    // Add table
    if (data.table && data.table.length > 0) {
        const headers = Object.keys(data.table[0]);
        
        container.append(`
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            ${headers.map(header => `<th>${header.replace('_', ' ').toUpperCase()}</th>`).join('')}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${data.table.map(row => `
                                            <tr>
                                                ${headers.map(header => `<td>${row[header] || '-'}</td>`).join('')}
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `);
    }
    
    $('#reportResultsModal').modal('show');
}

function exportReport(format) {
    if (!currentReportData) {
        showToast('No report data to export', 'warning');
        return;
    }
    
    const params = new URLSearchParams({
        format: format,
        data: JSON.stringify(currentReportData)
    });
    
    window.location.href = `/api/reports/export?${params.toString()}`;
}

function exportCurrentReport(format) {
    exportReport(format);
}

function getReportColor(type) {
    const colors = {
        'stock': 'primary',
        'allocation': 'success',
        'maintenance': 'warning',
        'financial': 'info'
    };
    return colors[type] || 'secondary';
}

function showLoading() {
    // Show loading indicator
    $('body').append(`
        <div id="loadingOverlay" class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" style="background: rgba(0,0,0,0.5); z-index: 9999;">
            <div class="text-center text-white">
                <i class="fas fa-spinner fa-spin fa-3x mb-3"></i>
                <p>Generating report...</p>
            </div>
        </div>
    `);
}

function hideLoading() {
    $('#loadingOverlay').remove();
}

function showToast(message, type = 'info') {
    // Implement your toast notification system here
    console.log(`${type.toUpperCase()}: ${message}`);
}
</script>
@endsection