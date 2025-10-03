@extends('layouts.app')

@section('title', 'Allocation Reports')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('inventory.reports.index') }}">Reports</a></li>
                    <li class="breadcrumb-item active">Allocation Reports</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-800">Allocation Reports</h1>
            <p class="mb-0 text-muted">Comprehensive asset allocation analysis and employee utilization reports</p>
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
                                <label for="employee_filter" class="form-label">Employee</label>
                                <select class="form-select" id="employee_filter" name="employee">
                                    <option value="">All Employees</option>
                                    <!-- Employees will be loaded dynamically -->
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="department_filter" class="form-label">Department</label>
                                <select class="form-select" id="department_filter" name="department">
                                    <option value="">All Departments</option>
                                    <!-- Departments will be loaded dynamically -->
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="asset_category_filter" class="form-label">Asset Category</label>
                                <select class="form-select" id="asset_category_filter" name="asset_category">
                                    <option value="">All Categories</option>
                                    <!-- Categories will be loaded dynamically -->
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="allocation_status" class="form-label">Status</label>
                                <select class="form-select" id="allocation_status" name="status">
                                    <option value="">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="returned">Returned</option>
                                    <option value="overdue">Overdue</option>
                                    <option value="lost">Lost</option>
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
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Allocations</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalAllocations">0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-handshake fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Overdue Returns</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="overdueReturns">0</div>
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
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Utilization Rate</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="utilizationRate">0%</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <!-- Allocation Trends -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Allocation Trends (Last 12 Months)</h6>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-primary active" onclick="loadTrendChart('monthly')">Monthly</button>
                        <button type="button" class="btn btn-outline-primary" onclick="loadTrendChart('weekly')">Weekly</button>
                        <button type="button" class="btn btn-outline-primary" onclick="loadTrendChart('daily')">Daily</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="allocationTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Allocation Status Distribution -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Allocation Status Distribution</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="allocationStatusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Department and Asset Analysis -->
    <div class="row mb-4">
        <!-- Allocations by Department -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Allocations by Department</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="departmentChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Allocated Assets -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Most Allocated Asset Categories</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="assetCategoryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Reports -->
    <div class="row">
        <!-- Current Active Allocations -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Current Active Allocations</h6>
                    <button class="btn btn-sm btn-outline-primary" onclick="exportReport('active-allocations')">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="activeAllocationsTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>Asset</th>
                                    <th>Employee</th>
                                    <th>Department</th>
                                    <th>Allocated Date</th>
                                    <th>Expected Return</th>
                                    <th>Days Used</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be loaded dynamically -->
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted">
                            Showing <span id="activeAllocationsStart">0</span> to <span id="activeAllocationsEnd">0</span> of <span id="activeAllocationsTotal">0</span> allocations
                        </div>
                        <nav>
                            <ul class="pagination pagination-sm mb-0" id="activeAllocationsPagination">
                                <!-- Pagination will be generated dynamically -->
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Employees by Allocations -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Top Employees by Allocations</h6>
                    <button class="btn btn-sm btn-outline-primary" onclick="exportReport('top-employees')">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="topEmployeesTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Employee</th>
                                    <th>Department</th>
                                    <th>Total Allocations</th>
                                    <th>Active</th>
                                    <th>Returned</th>
                                    <th>Overdue</th>
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

    <!-- Overdue and Alert Reports -->
    <div class="row">
        <!-- Overdue Returns -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Overdue Returns</h6>
                    <button class="btn btn-sm btn-outline-primary" onclick="exportReport('overdue-returns')">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="overdueReturnsTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>Asset</th>
                                    <th>Employee</th>
                                    <th>Expected Return</th>
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

        <!-- Asset Utilization -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Asset Utilization Analysis</h6>
                    <button class="btn btn-sm btn-outline-primary" onclick="exportReport('asset-utilization')">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="assetUtilizationTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>Asset</th>
                                    <th>Category</th>
                                    <th>Total Allocations</th>
                                    <th>Days Allocated</th>
                                    <th>Utilization Rate</th>
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

    <!-- Return Performance -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Return Performance Analysis</h6>
                    <button class="btn btn-sm btn-outline-primary" onclick="exportReport('return-performance')">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- On-Time Returns -->
                        <div class="col-md-4">
                            <h6 class="text-success"><i class="fas fa-check-circle"></i> On-Time Returns</h6>
                            <div id="onTimeReturns" class="performance-list">
                                <!-- On-time returns will be loaded here -->
                            </div>
                        </div>

                        <!-- Late Returns -->
                        <div class="col-md-4">
                            <h6 class="text-warning"><i class="fas fa-clock"></i> Late Returns</h6>
                            <div id="lateReturns" class="performance-list">
                                <!-- Late returns will be loaded here -->
                            </div>
                        </div>

                        <!-- Never Returned -->
                        <div class="col-md-4">
                            <h6 class="text-danger"><i class="fas fa-times-circle"></i> Never Returned</h6>
                            <div id="neverReturned" class="performance-list">
                                <!-- Never returned items will be loaded here -->
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
let allocationTrendChart;
let allocationStatusChart;
let departmentChart;
let assetCategoryChart;
let currentFilters = {};

$(document).ready(function() {
    initializeFilters();
    loadSummaryData();
    initializeCharts();
    loadActiveAllocations();
    loadTopEmployees();
    loadOverdueReturns();
    loadAssetUtilization();
    loadReturnPerformance();
    
    // Set default date range (last 30 days)
    const today = new Date();
    const thirtyDaysAgo = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000));
    $('#date_to').val(today.toISOString().split('T')[0]);
    $('#date_from').val(thirtyDaysAgo.toISOString().split('T')[0]);
});

function initializeFilters() {
    // Load employees
    $.get('/api/employees')
        .done(function(employees) {
            const employeeSelect = $('#employee_filter');
            employees.forEach(employee => {
                employeeSelect.append(`<option value="${employee.id}">${employee.name}</option>`);
            });
        });
    
    // Load departments
    $.get('/api/departments')
        .done(function(departments) {
            const departmentSelect = $('#department_filter');
            departments.forEach(department => {
                departmentSelect.append(`<option value="${department.id}">${department.name}</option>`);
            });
        });
    
    // Load asset categories
    $.get('/api/inventory/categories')
        .done(function(categories) {
            const categorySelect = $('#asset_category_filter');
            categories.forEach(category => {
                categorySelect.append(`<option value="${category.id}">${category.name}</option>`);
            });
        });
}

function loadSummaryData() {
    $.get('/api/reports/allocations/summary', currentFilters)
        .done(function(data) {
            $('#totalAllocations').text(data.total_allocations || 0);
            $('#activeAllocations').text(data.active_allocations || 0);
            $('#overdueReturns').text(data.overdue_returns || 0);
            $('#utilizationRate').text(`${parseFloat(data.utilization_rate || 0).toFixed(1)}%`);
        })
        .fail(function() {
            console.error('Error loading summary data');
        });
}

function initializeCharts() {
    // Allocation Trend Chart
    const trendCtx = document.getElementById('allocationTrendChart').getContext('2d');
    allocationTrendChart = new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'New Allocations',
                data: [],
                borderColor: '#4e73df',
                backgroundColor: 'rgba(78, 115, 223, 0.1)',
                tension: 0.3
            }, {
                label: 'Returns',
                data: [],
                borderColor: '#1cc88a',
                backgroundColor: 'rgba(28, 200, 138, 0.1)',
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
    
    // Allocation Status Chart
    const statusCtx = document.getElementById('allocationStatusChart').getContext('2d');
    allocationStatusChart = new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Active', 'Returned', 'Overdue', 'Lost'],
            datasets: [{
                data: [],
                backgroundColor: ['#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b']
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
    
    // Department Chart
    const deptCtx = document.getElementById('departmentChart').getContext('2d');
    departmentChart = new Chart(deptCtx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Allocations',
                data: [],
                backgroundColor: '#4e73df'
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
    
    // Asset Category Chart
    const assetCtx = document.getElementById('assetCategoryChart').getContext('2d');
    assetCategoryChart = new Chart(assetCtx, {
        type: 'horizontalBar',
        data: {
            labels: [],
            datasets: [{
                label: 'Allocations',
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
    
    // Load initial chart data
    loadTrendChart('monthly');
    loadAllocationStatusChart();
    loadDepartmentChart();
    loadAssetCategoryChart();
}

function loadTrendChart(period) {
    // Update active button
    $('.btn-group .btn').removeClass('active');
    $(`button[onclick="loadTrendChart('${period}')"]`).addClass('active');
    
    $.get(`/api/reports/allocations/trend-chart?period=${period}`, currentFilters)
        .done(function(data) {
            allocationTrendChart.data.labels = data.labels;
            allocationTrendChart.data.datasets[0].data = data.allocations;
            allocationTrendChart.data.datasets[1].data = data.returns;
            allocationTrendChart.update();
        })
        .fail(function() {
            console.error('Error loading trend chart');
        });
}

function loadAllocationStatusChart() {
    $.get('/api/reports/allocations/status-chart', currentFilters)
        .done(function(data) {
            allocationStatusChart.data.datasets[0].data = data.values;
            allocationStatusChart.update();
        })
        .fail(function() {
            console.error('Error loading status chart');
        });
}

function loadDepartmentChart() {
    $.get('/api/reports/allocations/department-chart', currentFilters)
        .done(function(data) {
            departmentChart.data.labels = data.labels;
            departmentChart.data.datasets[0].data = data.values;
            departmentChart.update();
        })
        .fail(function() {
            console.error('Error loading department chart');
        });
}

function loadAssetCategoryChart() {
    $.get('/api/reports/allocations/asset-category-chart', currentFilters)
        .done(function(data) {
            assetCategoryChart.data.labels = data.labels;
            assetCategoryChart.data.datasets[0].data = data.values;
            assetCategoryChart.update();
        })
        .fail(function() {
            console.error('Error loading asset category chart');
        });
}

function loadActiveAllocations(page = 1) {
    $.get(`/api/reports/allocations/active?page=${page}`, currentFilters)
        .done(function(data) {
            const tbody = $('#activeAllocationsTable tbody');
            tbody.empty();
            
            data.data.forEach(allocation => {
                const statusBadge = getAllocationStatusBadge(allocation.status);
                const daysUsed = calculateDaysUsed(allocation.allocated_date);
                
                tbody.append(`
                    <tr>
                        <td>${allocation.asset_name}</td>
                        <td>${allocation.employee_name}</td>
                        <td>${allocation.department}</td>
                        <td>${formatDate(allocation.allocated_date)}</td>
                        <td>${formatDate(allocation.expected_return_date)}</td>
                        <td>${daysUsed}</td>
                        <td>${statusBadge}</td>
                    </tr>
                `);
            });
            
            // Update pagination info
            $('#activeAllocationsStart').text(data.from || 0);
            $('#activeAllocationsEnd').text(data.to || 0);
            $('#activeAllocationsTotal').text(data.total || 0);
            
            // Generate pagination
            generatePagination('activeAllocationsPagination', data, 'loadActiveAllocations');
        })
        .fail(function() {
            console.error('Error loading active allocations');
        });
}

function loadTopEmployees() {
    $.get('/api/reports/allocations/top-employees', currentFilters)
        .done(function(data) {
            const tbody = $('#topEmployeesTable tbody');
            tbody.empty();
            
            data.forEach((employee, index) => {
                tbody.append(`
                    <tr>
                        <td>${index + 1}</td>
                        <td>${employee.name}</td>
                        <td>${employee.department}</td>
                        <td>${employee.total_allocations}</td>
                        <td><span class="badge bg-success">${employee.active_allocations}</span></td>
                        <td><span class="badge bg-info">${employee.returned_allocations}</span></td>
                        <td><span class="badge bg-warning">${employee.overdue_allocations}</span></td>
                    </tr>
                `);
            });
        })
        .fail(function() {
            console.error('Error loading top employees');
        });
}

function loadOverdueReturns() {
    $.get('/api/reports/allocations/overdue', currentFilters)
        .done(function(data) {
            const tbody = $('#overdueReturnsTable tbody');
            tbody.empty();
            
            data.forEach(allocation => {
                const priorityBadge = getPriorityBadge(allocation.priority);
                const daysOverdue = calculateDaysOverdue(allocation.expected_return_date);
                
                tbody.append(`
                    <tr>
                        <td>${allocation.asset_name}</td>
                        <td>${allocation.employee_name}</td>
                        <td>${formatDate(allocation.expected_return_date)}</td>
                        <td><span class="badge bg-danger">${daysOverdue} days</span></td>
                        <td>${priorityBadge}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="sendReminder(${allocation.id})">
                                <i class="fas fa-bell"></i> Remind
                            </button>
                        </td>
                    </tr>
                `);
            });
        })
        .fail(function() {
            console.error('Error loading overdue returns');
        });
}

function loadAssetUtilization() {
    $.get('/api/reports/allocations/asset-utilization', currentFilters)
        .done(function(data) {
            const tbody = $('#assetUtilizationTable tbody');
            tbody.empty();
            
            data.forEach(asset => {
                const utilizationBadge = getUtilizationBadge(asset.utilization_rate);
                const statusBadge = getAssetStatusBadge(asset.status);
                
                tbody.append(`
                    <tr>
                        <td>${asset.name}</td>
                        <td>${asset.category}</td>
                        <td>${asset.total_allocations}</td>
                        <td>${asset.days_allocated}</td>
                        <td>${utilizationBadge}</td>
                        <td>${statusBadge}</td>
                    </tr>
                `);
            });
        })
        .fail(function() {
            console.error('Error loading asset utilization');
        });
}

function loadReturnPerformance() {
    // Load on-time returns
    $.get('/api/reports/allocations/return-performance/on-time', currentFilters)
        .done(function(data) {
            const container = $('#onTimeReturns');
            container.empty();
            
            if (data.length === 0) {
                container.append('<p class="text-muted">No on-time returns</p>');
            } else {
                data.forEach(item => {
                    container.append(`
                        <div class="performance-item">
                            <strong>${item.asset_name}</strong><br>
                            <small class="text-muted">${item.employee_name} - ${formatDate(item.returned_date)}</small>
                        </div>
                    `);
                });
            }
        });
    
    // Load late returns
    $.get('/api/reports/allocations/return-performance/late', currentFilters)
        .done(function(data) {
            const container = $('#lateReturns');
            container.empty();
            
            if (data.length === 0) {
                container.append('<p class="text-muted">No late returns</p>');
            } else {
                data.forEach(item => {
                    container.append(`
                        <div class="performance-item">
                            <strong>${item.asset_name}</strong><br>
                            <small class="text-muted">${item.employee_name} - ${item.days_late} days late</small>
                        </div>
                    `);
                });
            }
        });
    
    // Load never returned
    $.get('/api/reports/allocations/return-performance/never', currentFilters)
        .done(function(data) {
            const container = $('#neverReturned');
            container.empty();
            
            if (data.length === 0) {
                container.append('<p class="text-muted">No unreturned items</p>');
            } else {
                data.forEach(item => {
                    container.append(`
                        <div class="performance-item">
                            <strong>${item.asset_name}</strong><br>
                            <small class="text-muted">${item.employee_name} - ${item.days_outstanding} days outstanding</small>
                        </div>
                    `);
                });
            }
        });
}

function applyFilters() {
    currentFilters = {
        employee: $('#employee_filter').val(),
        department: $('#department_filter').val(),
        asset_category: $('#asset_category_filter').val(),
        status: $('#allocation_status').val(),
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
    loadTrendChart('monthly');
    loadAllocationStatusChart();
    loadDepartmentChart();
    loadAssetCategoryChart();
    loadActiveAllocations();
    loadTopEmployees();
    loadOverdueReturns();
    loadAssetUtilization();
    loadReturnPerformance();
}

function exportReport(type) {
    const params = new URLSearchParams({
        type: type,
        ...currentFilters
    });
    
    window.location.href = `/api/reports/allocations/export?${params.toString()}`;
}

function exportAllReports(format) {
    const params = new URLSearchParams({
        format: format,
        ...currentFilters
    });
    
    window.location.href = `/api/reports/allocations/export-all?${params.toString()}`;
}

function sendReminder(allocationId) {
    $.post(`/api/allocations/${allocationId}/send-reminder`)
        .done(function() {
            showToast('Reminder sent successfully', 'success');
        })
        .fail(function() {
            showToast('Error sending reminder', 'error');
        });
}

// Utility functions
function getAllocationStatusBadge(status) {
    const badges = {
        'active': '<span class="badge bg-success">Active</span>',
        'returned': '<span class="badge bg-info">Returned</span>',
        'overdue': '<span class="badge bg-warning">Overdue</span>',
        'lost': '<span class="badge bg-danger">Lost</span>'
    };
    return badges[status] || '<span class="badge bg-secondary">Unknown</span>';
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

function getUtilizationBadge(rate) {
    const percentage = parseFloat(rate);
    if (percentage >= 80) {
        return `<span class="badge bg-success">${percentage.toFixed(1)}%</span>`;
    } else if (percentage >= 60) {
        return `<span class="badge bg-warning">${percentage.toFixed(1)}%</span>`;
    } else {
        return `<span class="badge bg-danger">${percentage.toFixed(1)}%</span>`;
    }
}

function getAssetStatusBadge(status) {
    const badges = {
        'available': '<span class="badge bg-success">Available</span>',
        'allocated': '<span class="badge bg-primary">Allocated</span>',
        'maintenance': '<span class="badge bg-warning">Maintenance</span>',
        'retired': '<span class="badge bg-secondary">Retired</span>'
    };
    return badges[status] || '<span class="badge bg-secondary">Unknown</span>';
}

function calculateDaysUsed(allocatedDate) {
    const allocated = new Date(allocatedDate);
    const today = new Date();
    const diffTime = Math.abs(today - allocated);
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
}

function calculateDaysOverdue(expectedReturnDate) {
    const expected = new Date(expectedReturnDate);
    const today = new Date();
    const diffTime = Math.abs(today - expected);
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
.performance-list {
    max-height: 300px;
    overflow-y: auto;
}

.performance-item {
    padding: 0.5rem;
    margin-bottom: 0.5rem;
    border: 1px solid #e3e6f0;
    border-radius: 0.35rem;
    background-color: #f8f9fc;
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