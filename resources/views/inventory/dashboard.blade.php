@extends('layouts.app')

@section('title', 'Inventory Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">Inventory Dashboard</h1>
                    <p class="text-muted">Comprehensive inventory management system overview</p>
                </div>
                <div>
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-plus"></i> Quick Add
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('inventory.items.create') }}">
                                <i class="fas fa-box"></i> New Item
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('inventory.allocations.create') }}">
                                <i class="fas fa-user-plus"></i> New Allocation
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('inventory.maintenance.create') }}">
                                <i class="fas fa-tools"></i> Schedule Maintenance
                            </a></li>
                        </ul>
                    </div>
                    <button class="btn btn-outline-secondary" onclick="refreshDashboard()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
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
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Items
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalItems">
                                <div class="spinner-border spinner-border-sm" role="status"></div>
                            </div>
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
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Value
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalValue">
                                <div class="spinner-border spinner-border-sm" role="status"></div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-rupee-sign fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Active Allocations
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="activeAllocations">
                                <div class="spinner-border spinner-border-sm" role="status"></div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-friends fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Pending Maintenance
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="pendingMaintenance">
                                <div class="spinner-border spinner-border-sm" role="status"></div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tools fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Cards Row -->
    <div class="row mb-4">
        <div class="col-xl-4 col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-danger">Low Stock Alerts</h6>
                    <span class="badge bg-danger" id="lowStockCount">0</span>
                </div>
                <div class="card-body">
                    <div id="lowStockItems" style="max-height: 200px; overflow-y: auto;">
                        <div class="text-center text-muted">
                            <i class="fas fa-spinner fa-spin"></i> Loading...
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-warning">Overdue Returns</h6>
                    <span class="badge bg-warning" id="overdueReturnsCount">0</span>
                </div>
                <div class="card-body">
                    <div id="overdueReturns" style="max-height: 200px; overflow-y: auto;">
                        <div class="text-center text-muted">
                            <i class="fas fa-spinner fa-spin"></i> Loading...
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-info">Upcoming Maintenance</h6>
                    <span class="badge bg-info" id="upcomingMaintenanceCount">0</span>
                </div>
                <div class="card-body">
                    <div id="upcomingMaintenance" style="max-height: 200px; overflow-y: auto;">
                        <div class="text-center text-muted">
                            <i class="fas fa-spinner fa-spin"></i> Loading...
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Inventory Overview</h6>
                    <div class="dropdown no-arrow">
                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                            <div class="dropdown-header">Chart Options:</div>
                            <a class="dropdown-item" href="#" onclick="switchChart('stock')">Stock Levels</a>
                            <a class="dropdown-item" href="#" onclick="switchChart('allocation')">Allocation Trends</a>
                            <a class="dropdown-item" href="#" onclick="switchChart('maintenance')">Maintenance Costs</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="inventoryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Category Distribution</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="categoryChart"></canvas>
                    </div>
                    <div class="mt-4 text-center small">
                        <span class="mr-2">
                            <i class="fas fa-circle text-primary"></i> Electronics
                        </span>
                        <span class="mr-2">
                            <i class="fas fa-circle text-success"></i> Furniture
                        </span>
                        <span class="mr-2">
                            <i class="fas fa-circle text-info"></i> Vehicles
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activities and Quick Stats -->
    <div class="row mb-4">
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Activities</h6>
                    <a href="{{ route('inventory.reports.activities') }}" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <div id="recentActivities">
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-spinner fa-spin"></i> Loading recent activities...
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Stats</h6>
                </div>
                <div class="card-body">
                    <div class="row no-gutters align-items-center mb-3">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Items Added This Month</div>
                            <div class="h6 mb-0 font-weight-bold text-gray-800" id="monthlyItems">-</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-plus-circle fa-lg text-success"></i>
                        </div>
                    </div>
                    
                    <div class="row no-gutters align-items-center mb-3">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Allocations This Week</div>
                            <div class="h6 mb-0 font-weight-bold text-gray-800" id="weeklyAllocations">-</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-handshake fa-lg text-info"></i>
                        </div>
                    </div>
                    
                    <div class="row no-gutters align-items-center mb-3">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Maintenance Completed</div>
                            <div class="h6 mb-0 font-weight-bold text-gray-800" id="completedMaintenance">-</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-lg text-success"></i>
                        </div>
                    </div>
                    
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">System Uptime</div>
                            <div class="h6 mb-0 font-weight-bold text-gray-800" id="systemUptime">99.9%</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-server fa-lg text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Warranty and Asset Management -->
    <div class="row mb-4">
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Warranty Expiring Soon</h6>
                    <a href="{{ route('inventory.reports.warranty') }}" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <div id="warrantyExpiring" style="max-height: 300px; overflow-y: auto;">
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-spinner fa-spin"></i> Loading warranty information...
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Asset Performance</h6>
                    <a href="{{ route('inventory.reports.assets') }}" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <div id="assetPerformance" style="max-height: 300px; overflow-y: auto;">
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-spinner fa-spin"></i> Loading asset performance...
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addItemModalLabel">Add New Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addItemForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="itemName" class="form-label">Item Name *</label>
                                <input type="text" class="form-control" id="itemName" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="itemCategory" class="form-label">Category *</label>
                                <select class="form-select" id="itemCategory" name="category_id" required>
                                    <option value="">Select Category</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="itemBarcode" class="form-label">Barcode</label>
                                <input type="text" class="form-control" id="itemBarcode" name="barcode">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="itemUnitPrice" class="form-label">Unit Price *</label>
                                <input type="number" class="form-control" id="itemUnitPrice" name="unit_price" step="0.01" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="itemCurrentStock" class="form-label">Current Stock *</label>
                                <input type="number" class="form-control" id="itemCurrentStock" name="current_stock" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="itemMinimumStock" class="form-label">Minimum Stock *</label>
                                <input type="number" class="form-control" id="itemMinimumStock" name="minimum_stock" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="itemDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="itemDescription" name="description" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="isAsset" name="is_asset">
                                    <label class="form-check-label" for="isAsset">
                                        Is Asset
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="itemStatus" class="form-label">Status</label>
                                <select class="form-select" id="itemStatus" name="status">
                                    <option value="available">Available</option>
                                    <option value="allocated">Allocated</option>
                                    <option value="maintenance">Under Maintenance</option>
                                    <option value="damaged">Damaged</option>
                                    <option value="lost">Lost</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    // Initialize dashboard
    loadDashboardData();
    loadCategories();
    initializeCharts();
    
    // Auto-refresh every 5 minutes
    setInterval(loadDashboardData, 300000);
});

let inventoryChart, categoryChart;
let currentChartType = 'stock';

function initializeCharts() {
    // Initialize main inventory chart
    const ctx1 = document.getElementById('inventoryChart').getContext('2d');
    inventoryChart = new Chart(ctx1, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Stock Levels',
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
                    beginAtZero: true
                }
            }
        }
    });

    // Initialize category chart
    const ctx2 = document.getElementById('categoryChart').getContext('2d');
    categoryChart = new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: [],
            datasets: [{
                data: [],
                backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'],
                hoverBackgroundColor: ['#2e59d9', '#17a673', '#2c9faf', '#f4b619', '#e02d1b'],
                hoverBorderColor: "rgba(234, 236, 244, 1)",
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
}

function loadDashboardData() {
    // Load summary data
    $.get('/api/inventory/dashboard/summary')
        .done(function(data) {
            updateSummaryCards(data);
        })
        .fail(function() {
            console.error('Failed to load dashboard summary');
        });

    // Load chart data
    loadChartData(currentChartType);

    // Load alerts and activities
    loadAlerts();
    loadRecentActivities();
    loadWarrantyExpiring();
    loadAssetPerformance();
    loadQuickStats();
}

function updateSummaryCards(data) {
    $('#totalItems').html(`<strong>${data.totalItems || 0}</strong>`);
    $('#totalValue').html(`<strong>₹${formatNumber(data.totalValue || 0)}</strong>`);
    $('#activeAllocations').html(`<strong>${data.activeAllocations || 0}</strong>`);
    $('#pendingMaintenance').html(`<strong>${data.pendingMaintenance || 0}</strong>`);
}

function loadChartData(type) {
    $.get(`/api/inventory/dashboard/chart/${type}`)
        .done(function(data) {
            updateInventoryChart(data, type);
        })
        .fail(function() {
            console.error('Failed to load chart data');
        });

    // Load category distribution
    $.get('/api/inventory/dashboard/categories')
        .done(function(data) {
            updateCategoryChart(data);
        })
        .fail(function() {
            console.error('Failed to load category data');
        });
}

function updateInventoryChart(data, type) {
    const chartConfig = {
        'stock': {
            label: 'Stock Levels',
            color: '#4e73df',
            bgColor: 'rgba(78, 115, 223, 0.1)'
        },
        'allocation': {
            label: 'Allocations',
            color: '#36b9cc',
            bgColor: 'rgba(54, 185, 204, 0.1)'
        },
        'maintenance': {
            label: 'Maintenance Costs',
            color: '#f6c23e',
            bgColor: 'rgba(246, 194, 62, 0.1)'
        }
    };

    const config = chartConfig[type] || chartConfig['stock'];
    
    inventoryChart.data.labels = data.labels || [];
    inventoryChart.data.datasets[0].label = config.label;
    inventoryChart.data.datasets[0].data = data.values || [];
    inventoryChart.data.datasets[0].borderColor = config.color;
    inventoryChart.data.datasets[0].backgroundColor = config.bgColor;
    inventoryChart.update();
}

function updateCategoryChart(data) {
    categoryChart.data.labels = data.labels || [];
    categoryChart.data.datasets[0].data = data.values || [];
    categoryChart.update();
}

function switchChart(type) {
    currentChartType = type;
    loadChartData(type);
}

function loadAlerts() {
    // Load low stock items
    $.get('/api/inventory/dashboard/low-stock')
        .done(function(data) {
            renderLowStockItems(data);
        })
        .fail(function() {
            $('#lowStockItems').html('<div class="text-center text-muted">Failed to load data</div>');
        });

    // Load overdue returns
    $.get('/api/inventory/dashboard/overdue-returns')
        .done(function(data) {
            renderOverdueReturns(data);
        })
        .fail(function() {
            $('#overdueReturns').html('<div class="text-center text-muted">Failed to load data</div>');
        });

    // Load upcoming maintenance
    $.get('/api/inventory/dashboard/upcoming-maintenance')
        .done(function(data) {
            renderUpcomingMaintenance(data);
        })
        .fail(function() {
            $('#upcomingMaintenance').html('<div class="text-center text-muted">Failed to load data</div>');
        });
}

function renderLowStockItems(data) {
    $('#lowStockCount').text(data.length);
    
    if (data.length === 0) {
        $('#lowStockItems').html('<div class="text-center text-muted">No low stock items</div>');
        return;
    }

    let html = '';
    data.forEach(item => {
        html += `
            <div class="d-flex align-items-center mb-2">
                <div class="flex-grow-1">
                    <div class="font-weight-bold">${item.name}</div>
                    <small class="text-muted">Current: ${item.current_stock} | Min: ${item.minimum_stock}</small>
                </div>
                <span class="badge badge-danger">${item.current_stock}</span>
            </div>
        `;
    });
    $('#lowStockItems').html(html);
}

function renderOverdueReturns(data) {
    $('#overdueReturnsCount').text(data.length);
    
    if (data.length === 0) {
        $('#overdueReturns').html('<div class="text-center text-muted">No overdue returns</div>');
        return;
    }

    let html = '';
    data.forEach(item => {
        const daysOverdue = Math.floor((new Date() - new Date(item.expected_return_date)) / (1000 * 60 * 60 * 24));
        html += `
            <div class="d-flex align-items-center mb-2">
                <div class="flex-grow-1">
                    <div class="font-weight-bold">${item.item_name}</div>
                    <small class="text-muted">Allocated to: ${item.allocated_to}</small>
                </div>
                <span class="badge badge-warning">${daysOverdue}d</span>
            </div>
        `;
    });
    $('#overdueReturns').html(html);
}

function renderUpcomingMaintenance(data) {
    $('#upcomingMaintenanceCount').text(data.length);
    
    if (data.length === 0) {
        $('#upcomingMaintenance').html('<div class="text-center text-muted">No upcoming maintenance</div>');
        return;
    }

    let html = '';
    data.forEach(item => {
        const daysUntil = Math.ceil((new Date(item.scheduled_date) - new Date()) / (1000 * 60 * 60 * 24));
        html += `
            <div class="d-flex align-items-center mb-2">
                <div class="flex-grow-1">
                    <div class="font-weight-bold">${item.asset_name}</div>
                    <small class="text-muted">${item.maintenance_type}</small>
                </div>
                <span class="badge badge-info">${daysUntil}d</span>
            </div>
        `;
    });
    $('#upcomingMaintenance').html(html);
}

function loadRecentActivities() {
    $.get('/api/inventory/dashboard/recent-activities')
        .done(function(data) {
            renderRecentActivities(data);
        })
        .fail(function() {
            $('#recentActivities').html('<div class="text-center text-muted">Failed to load activities</div>');
        });
}

function renderRecentActivities(data) {
    if (data.length === 0) {
        $('#recentActivities').html('<div class="text-center text-muted">No recent activities</div>');
        return;
    }

    let html = '';
    data.forEach(activity => {
        const icon = getActivityIcon(activity.type);
        const color = getActivityColor(activity.type);
        html += `
            <div class="d-flex align-items-center mb-3">
                <div class="mr-3">
                    <div class="icon-circle bg-${color}">
                        <i class="fas ${icon} text-white"></i>
                    </div>
                </div>
                <div class="flex-grow-1">
                    <div class="font-weight-bold">${activity.description}</div>
                    <small class="text-muted">${formatDateTime(activity.created_at)} by ${activity.user_name}</small>
                </div>
            </div>
        `;
    });
    $('#recentActivities').html(html);
}

function loadWarrantyExpiring() {
    $.get('/api/inventory/dashboard/warranty-expiring')
        .done(function(data) {
            renderWarrantyExpiring(data);
        })
        .fail(function() {
            $('#warrantyExpiring').html('<div class="text-center text-muted">Failed to load warranty data</div>');
        });
}

function renderWarrantyExpiring(data) {
    if (data.length === 0) {
        $('#warrantyExpiring').html('<div class="text-center text-muted">No warranties expiring soon</div>');
        return;
    }

    let html = '';
    data.forEach(item => {
        const daysUntil = Math.ceil((new Date(item.warranty_expiry) - new Date()) / (1000 * 60 * 60 * 24));
        const badgeClass = daysUntil <= 30 ? 'badge-danger' : daysUntil <= 90 ? 'badge-warning' : 'badge-info';
        html += `
            <div class="d-flex align-items-center mb-2">
                <div class="flex-grow-1">
                    <div class="font-weight-bold">${item.name}</div>
                    <small class="text-muted">Expires: ${formatDate(item.warranty_expiry)}</small>
                </div>
                <span class="badge ${badgeClass}">${daysUntil}d</span>
            </div>
        `;
    });
    $('#warrantyExpiring').html(html);
}

function loadAssetPerformance() {
    $.get('/api/inventory/dashboard/asset-performance')
        .done(function(data) {
            renderAssetPerformance(data);
        })
        .fail(function() {
            $('#assetPerformance').html('<div class="text-center text-muted">Failed to load asset performance</div>');
        });
}

function renderAssetPerformance(data) {
    if (data.length === 0) {
        $('#assetPerformance').html('<div class="text-center text-muted">No asset performance data</div>');
        return;
    }

    let html = '';
    data.forEach(asset => {
        const performanceClass = asset.performance_score >= 80 ? 'success' : asset.performance_score >= 60 ? 'warning' : 'danger';
        html += `
            <div class="d-flex align-items-center mb-3">
                <div class="flex-grow-1">
                    <div class="font-weight-bold">${asset.name}</div>
                    <div class="progress mt-1" style="height: 5px;">
                        <div class="progress-bar bg-${performanceClass}" style="width: ${asset.performance_score}%"></div>
                    </div>
                    <small class="text-muted">Uptime: ${asset.uptime_percentage}% | Maintenance: ${asset.maintenance_count}</small>
                </div>
                <span class="badge badge-${performanceClass}">${asset.performance_score}%</span>
            </div>
        `;
    });
    $('#assetPerformance').html(html);
}

function loadQuickStats() {
    $.get('/api/inventory/dashboard/quick-stats')
        .done(function(data) {
            $('#monthlyItems').text(data.monthlyItems || 0);
            $('#weeklyAllocations').text(data.weeklyAllocations || 0);
            $('#completedMaintenance').text(data.completedMaintenance || 0);
            $('#systemUptime').text((data.systemUptime || 99.9) + '%');
        })
        .fail(function() {
            console.error('Failed to load quick stats');
        });
}

function loadCategories() {
    $.get('/api/inventory/categories')
        .done(function(data) {
            let options = '<option value="">Select Category</option>';
            data.forEach(category => {
                options += `<option value="${category.id}">${category.name}</option>`;
            });
            $('#itemCategory').html(options);
        })
        .fail(function() {
            console.error('Failed to load categories');
        });
}

function refreshDashboard() {
    loadDashboardData();
    showToast('Dashboard refreshed successfully!', 'success');
}

// Add Item Form Submission
$('#addItemForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    $.ajax({
        url: '{{ route("inventory.items.store") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            $('#addItemModal').modal('hide');
            $('#addItemForm')[0].reset();
            loadDashboardData();
            showToast('Item added successfully!', 'success');
        },
        error: function(xhr) {
            let errorMessage = 'Failed to add item';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            showToast(errorMessage, 'error');
        }
    });
});

// Utility Functions
function formatNumber(num) {
    return new Intl.NumberFormat('en-IN').format(num);
}

function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('en-IN');
}

function formatDateTime(dateString) {
    return new Date(dateString).toLocaleString('en-IN');
}

function getActivityIcon(type) {
    const icons = {
        'item_added': 'fa-plus',
        'item_updated': 'fa-edit',
        'allocation_created': 'fa-handshake',
        'allocation_returned': 'fa-undo',
        'maintenance_scheduled': 'fa-calendar-plus',
        'maintenance_completed': 'fa-check-circle',
        'stock_updated': 'fa-boxes'
    };
    return icons[type] || 'fa-info-circle';
}

function getActivityColor(type) {
    const colors = {
        'item_added': 'success',
        'item_updated': 'info',
        'allocation_created': 'primary',
        'allocation_returned': 'warning',
        'maintenance_scheduled': 'info',
        'maintenance_completed': 'success',
        'stock_updated': 'secondary'
    };
    return colors[type] || 'secondary';
}

function showToast(message, type = 'info') {
    const bgClass = type === 'success' ? 'bg-success' : type === 'error' ? 'bg-danger' : 'bg-info';
    const toast = `
        <div class="toast align-items-center text-white ${bgClass} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    
    // Create toast container if it doesn't exist
    if (!$('#toastContainer').length) {
        $('body').append('<div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3"></div>');
    }
    
    const $toast = $(toast);
    $('#toastContainer').append($toast);
    
    // Show toast and auto-hide after 3 seconds
    $toast.toast('show');
    setTimeout(() => $toast.remove(), 3000);
}
</script>
@endsection