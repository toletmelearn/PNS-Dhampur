@extends('layouts.app')

@section('title', 'Stock Reports')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('inventory.reports.index') }}">Reports</a></li>
                    <li class="breadcrumb-item active">Stock Reports</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-800">Stock Reports</h1>
            <p class="mb-0 text-muted">Comprehensive stock analysis and inventory management reports</p>
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
                                <label for="category_filter" class="form-label">Category</label>
                                <select class="form-select" id="category_filter" name="category">
                                    <option value="">All Categories</option>
                                    <!-- Categories will be loaded dynamically -->
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="location_filter" class="form-label">Location</label>
                                <select class="form-select" id="location_filter" name="location">
                                    <option value="">All Locations</option>
                                    <!-- Locations will be loaded dynamically -->
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="date_from" class="form-label">Date From</label>
                                <input type="date" class="form-control" id="date_from" name="date_from">
                            </div>
                            <div class="col-md-3">
                                <label for="date_to" class="form-label">Date To</label>
                                <input type="date" class="form-control" id="date_to" name="date_to">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-3">
                                <label for="stock_status" class="form-label">Stock Status</label>
                                <select class="form-select" id="stock_status" name="stock_status">
                                    <option value="">All Status</option>
                                    <option value="in_stock">In Stock</option>
                                    <option value="low_stock">Low Stock</option>
                                    <option value="out_of_stock">Out of Stock</option>
                                    <option value="overstock">Overstock</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="value_range" class="form-label">Value Range</label>
                                <select class="form-select" id="value_range" name="value_range">
                                    <option value="">All Values</option>
                                    <option value="0-1000">$0 - $1,000</option>
                                    <option value="1000-5000">$1,000 - $5,000</option>
                                    <option value="5000-10000">$5,000 - $10,000</option>
                                    <option value="10000+">$10,000+</option>
                                </select>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
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
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Items</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalItems">0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-boxes fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Value</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalValue">$0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Low Stock Items</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="lowStockItems">0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Out of Stock</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="outOfStockItems">0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <!-- Stock Value by Category -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Stock Value by Category</h6>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-primary active" onclick="loadStockChart('value')">Value</button>
                        <button type="button" class="btn btn-outline-primary" onclick="loadStockChart('quantity')">Quantity</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="stockCategoryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stock Status Distribution -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Stock Status Distribution</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="stockStatusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Movement Trend -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Stock Movement Trend (Last 12 Months)</h6>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-primary active" onclick="loadMovementChart('in')">Stock In</button>
                        <button type="button" class="btn btn-outline-primary" onclick="loadMovementChart('out')">Stock Out</button>
                        <button type="button" class="btn btn-outline-primary" onclick="loadMovementChart('both')">Both</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="stockMovementChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Reports -->
    <div class="row">
        <!-- Current Stock Levels -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Current Stock Levels</h6>
                    <button class="btn btn-sm btn-outline-primary" onclick="exportReport('stock-levels')">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="stockLevelsTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>Item</th>
                                    <th>Category</th>
                                    <th>Current Stock</th>
                                    <th>Min Level</th>
                                    <th>Max Level</th>
                                    <th>Status</th>
                                    <th>Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be loaded dynamically -->
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted">
                            Showing <span id="stockLevelsStart">0</span> to <span id="stockLevelsEnd">0</span> of <span id="stockLevelsTotal">0</span> items
                        </div>
                        <nav>
                            <ul class="pagination pagination-sm mb-0" id="stockLevelsPagination">
                                <!-- Pagination will be generated dynamically -->
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Value Items -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Top Value Items</h6>
                    <button class="btn btn-sm btn-outline-primary" onclick="exportReport('top-value')">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="topValueTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Item</th>
                                    <th>Category</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Total Value</th>
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

    <!-- Stock Alerts -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Stock Alerts</h6>
                    <button class="btn btn-sm btn-outline-primary" onclick="exportReport('stock-alerts')">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Low Stock Alerts -->
                        <div class="col-md-4">
                            <h6 class="text-warning"><i class="fas fa-exclamation-triangle"></i> Low Stock Items</h6>
                            <div id="lowStockAlerts" class="alert-list">
                                <!-- Low stock items will be loaded here -->
                            </div>
                        </div>

                        <!-- Out of Stock Alerts -->
                        <div class="col-md-4">
                            <h6 class="text-danger"><i class="fas fa-times-circle"></i> Out of Stock Items</h6>
                            <div id="outOfStockAlerts" class="alert-list">
                                <!-- Out of stock items will be loaded here -->
                            </div>
                        </div>

                        <!-- Overstock Alerts -->
                        <div class="col-md-4">
                            <h6 class="text-info"><i class="fas fa-arrow-up"></i> Overstock Items</h6>
                            <div id="overstockAlerts" class="alert-list">
                                <!-- Overstock items will be loaded here -->
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
let stockCategoryChart;
let stockStatusChart;
let stockMovementChart;
let currentFilters = {};

$(document).ready(function() {
    initializeFilters();
    loadSummaryData();
    initializeCharts();
    loadStockLevels();
    loadTopValueItems();
    loadStockAlerts();
    
    // Set default date range (last 30 days)
    const today = new Date();
    const thirtyDaysAgo = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000));
    $('#date_to').val(today.toISOString().split('T')[0]);
    $('#date_from').val(thirtyDaysAgo.toISOString().split('T')[0]);
});

function initializeFilters() {
    // Load categories
    $.get('/api/inventory/categories')
        .done(function(categories) {
            const categorySelect = $('#category_filter');
            categories.forEach(category => {
                categorySelect.append(`<option value="${category.id}">${category.name}</option>`);
            });
        });
    
    // Load locations
    $.get('/api/inventory/locations')
        .done(function(locations) {
            const locationSelect = $('#location_filter');
            locations.forEach(location => {
                locationSelect.append(`<option value="${location.id}">${location.name}</option>`);
            });
        });
}

function loadSummaryData() {
    $.get('/api/reports/stock/summary', currentFilters)
        .done(function(data) {
            $('#totalItems').text(data.total_items || 0);
            $('#totalValue').text(`$${parseFloat(data.total_value || 0).toLocaleString()}`);
            $('#lowStockItems').text(data.low_stock_items || 0);
            $('#outOfStockItems').text(data.out_of_stock_items || 0);
        })
        .fail(function() {
            console.error('Error loading summary data');
        });
}

function initializeCharts() {
    // Stock Category Chart
    const categoryCtx = document.getElementById('stockCategoryChart').getContext('2d');
    stockCategoryChart = new Chart(categoryCtx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Value',
                data: [],
                backgroundColor: '#4e73df',
                borderColor: '#4e73df',
                borderWidth: 1
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
    
    // Stock Status Chart
    const statusCtx = document.getElementById('stockStatusChart').getContext('2d');
    stockStatusChart = new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['In Stock', 'Low Stock', 'Out of Stock', 'Overstock'],
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
    
    // Stock Movement Chart
    const movementCtx = document.getElementById('stockMovementChart').getContext('2d');
    stockMovementChart = new Chart(movementCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: []
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
    
    // Load initial chart data
    loadStockChart('value');
    loadStockStatusChart();
    loadMovementChart('both');
}

function loadStockChart(type) {
    // Update active button
    $('.btn-group .btn').removeClass('active');
    $(`button[onclick="loadStockChart('${type}')"]`).addClass('active');
    
    $.get(`/api/reports/stock/category-chart?type=${type}`, currentFilters)
        .done(function(data) {
            stockCategoryChart.data.labels = data.labels;
            stockCategoryChart.data.datasets[0].data = data.values;
            stockCategoryChart.data.datasets[0].label = type === 'value' ? 'Value' : 'Quantity';
            
            // Update y-axis formatter
            if (type === 'value') {
                stockCategoryChart.options.scales.y.ticks.callback = function(value) {
                    return '$' + value.toLocaleString();
                };
                stockCategoryChart.options.plugins.tooltip.callbacks.label = function(context) {
                    return 'Value: $' + context.parsed.y.toLocaleString();
                };
            } else {
                stockCategoryChart.options.scales.y.ticks.callback = function(value) {
                    return value.toLocaleString();
                };
                stockCategoryChart.options.plugins.tooltip.callbacks.label = function(context) {
                    return 'Quantity: ' + context.parsed.y.toLocaleString();
                };
            }
            
            stockCategoryChart.update();
        })
        .fail(function() {
            console.error('Error loading stock category chart');
        });
}

function loadStockStatusChart() {
    $.get('/api/reports/stock/status-chart', currentFilters)
        .done(function(data) {
            stockStatusChart.data.datasets[0].data = data.values;
            stockStatusChart.update();
        })
        .fail(function() {
            console.error('Error loading stock status chart');
        });
}

function loadMovementChart(type) {
    // Update active button
    $('.btn-group .btn').removeClass('active');
    $(`button[onclick="loadMovementChart('${type}')"]`).addClass('active');
    
    $.get(`/api/reports/stock/movement-chart?type=${type}`, currentFilters)
        .done(function(data) {
            stockMovementChart.data.labels = data.labels;
            stockMovementChart.data.datasets = data.datasets;
            stockMovementChart.update();
        })
        .fail(function() {
            console.error('Error loading stock movement chart');
        });
}

function loadStockLevels(page = 1) {
    $.get(`/api/reports/stock/levels?page=${page}`, currentFilters)
        .done(function(data) {
            const tbody = $('#stockLevelsTable tbody');
            tbody.empty();
            
            data.data.forEach(item => {
                const statusBadge = getStockStatusBadge(item.status);
                tbody.append(`
                    <tr>
                        <td>${item.name}</td>
                        <td>${item.category}</td>
                        <td>${item.current_stock}</td>
                        <td>${item.min_level}</td>
                        <td>${item.max_level}</td>
                        <td>${statusBadge}</td>
                        <td>$${parseFloat(item.total_value).toLocaleString()}</td>
                    </tr>
                `);
            });
            
            // Update pagination info
            $('#stockLevelsStart').text(data.from || 0);
            $('#stockLevelsEnd').text(data.to || 0);
            $('#stockLevelsTotal').text(data.total || 0);
            
            // Generate pagination
            generatePagination('stockLevelsPagination', data, 'loadStockLevels');
        })
        .fail(function() {
            console.error('Error loading stock levels');
        });
}

function loadTopValueItems() {
    $.get('/api/reports/stock/top-value', currentFilters)
        .done(function(data) {
            const tbody = $('#topValueTable tbody');
            tbody.empty();
            
            data.forEach((item, index) => {
                tbody.append(`
                    <tr>
                        <td>${index + 1}</td>
                        <td>${item.name}</td>
                        <td>${item.category}</td>
                        <td>${item.quantity}</td>
                        <td>$${parseFloat(item.unit_price).toLocaleString()}</td>
                        <td>$${parseFloat(item.total_value).toLocaleString()}</td>
                    </tr>
                `);
            });
        })
        .fail(function() {
            console.error('Error loading top value items');
        });
}

function loadStockAlerts() {
    // Load low stock alerts
    $.get('/api/reports/stock/alerts/low', currentFilters)
        .done(function(data) {
            const container = $('#lowStockAlerts');
            container.empty();
            
            if (data.length === 0) {
                container.append('<p class="text-muted">No low stock items</p>');
            } else {
                data.forEach(item => {
                    container.append(`
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <strong>${item.name}</strong><br>
                            Current: ${item.current_stock} | Min: ${item.min_level}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `);
                });
            }
        });
    
    // Load out of stock alerts
    $.get('/api/reports/stock/alerts/out', currentFilters)
        .done(function(data) {
            const container = $('#outOfStockAlerts');
            container.empty();
            
            if (data.length === 0) {
                container.append('<p class="text-muted">No out of stock items</p>');
            } else {
                data.forEach(item => {
                    container.append(`
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>${item.name}</strong><br>
                            Stock: ${item.current_stock}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `);
                });
            }
        });
    
    // Load overstock alerts
    $.get('/api/reports/stock/alerts/over', currentFilters)
        .done(function(data) {
            const container = $('#overstockAlerts');
            container.empty();
            
            if (data.length === 0) {
                container.append('<p class="text-muted">No overstock items</p>');
            } else {
                data.forEach(item => {
                    container.append(`
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <strong>${item.name}</strong><br>
                            Current: ${item.current_stock} | Max: ${item.max_level}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `);
                });
            }
        });
}

function applyFilters() {
    currentFilters = {
        category: $('#category_filter').val(),
        location: $('#location_filter').val(),
        date_from: $('#date_from').val(),
        date_to: $('#date_to').val(),
        stock_status: $('#stock_status').val(),
        value_range: $('#value_range').val()
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
    loadStockChart('value');
    loadStockStatusChart();
    loadMovementChart('both');
    loadStockLevels();
    loadTopValueItems();
    loadStockAlerts();
}

function exportReport(type) {
    const params = new URLSearchParams({
        type: type,
        ...currentFilters
    });
    
    window.location.href = `/api/reports/stock/export?${params.toString()}`;
}

function exportAllReports(format) {
    const params = new URLSearchParams({
        format: format,
        ...currentFilters
    });
    
    window.location.href = `/api/reports/stock/export-all?${params.toString()}`;
}

function getStockStatusBadge(status) {
    const badges = {
        'in_stock': '<span class="badge bg-success">In Stock</span>',
        'low_stock': '<span class="badge bg-warning">Low Stock</span>',
        'out_of_stock': '<span class="badge bg-danger">Out of Stock</span>',
        'overstock': '<span class="badge bg-info">Overstock</span>'
    };
    return badges[status] || '<span class="badge bg-secondary">Unknown</span>';
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
</script>

<style>
.alert-list {
    max-height: 300px;
    overflow-y: auto;
}

.alert-list .alert {
    margin-bottom: 0.5rem;
    padding: 0.5rem;
    font-size: 0.875rem;
}

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

.border-left-danger {
    border-left: 0.25rem solid #e74a3b !important;
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