@extends('layouts.app')

@section('title', 'Asset Allocations')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Asset Allocations</h1>
            <p class="mb-0 text-muted">Manage asset assignments and returns</p>
        </div>
        <div>
            <button type="button" class="btn btn-outline-info me-2" onclick="showStatistics()">
                <i class="fas fa-chart-bar"></i> Statistics
            </button>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#allocateAssetModal">
                <i class="fas fa-plus"></i> Allocate Asset
            </button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalAllocations">0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Active</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="activeAllocations">0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Returned</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="returnedAllocations">0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-undo fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Overdue</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="overdueAllocations">0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Due Today</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="dueTodayAllocations">0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-secondary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Due Tomorrow</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="dueTomorrowAllocations">0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="loadOverdueAllocations()">
                                <i class="fas fa-exclamation-triangle"></i> View Overdue
                            </button>
                        </div>
                        <div class="col-md-3 mb-2">
                            <button type="button" class="btn btn-outline-warning btn-sm w-100" onclick="loadDueTodayAllocations()">
                                <i class="fas fa-clock"></i> Due Today
                            </button>
                        </div>
                        <div class="col-md-3 mb-2">
                            <button type="button" class="btn btn-outline-info btn-sm w-100" onclick="loadDueTomorrowAllocations()">
                                <i class="fas fa-calendar-day"></i> Due Tomorrow
                            </button>
                        </div>
                        <div class="col-md-3 mb-2">
                            <button type="button" class="btn btn-outline-success btn-sm w-100" onclick="generateReport()">
                                <i class="fas fa-file-export"></i> Generate Report
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filters & Search</h6>
        </div>
        <div class="card-body">
            <form id="filterForm">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="searchAllocation" class="form-label">Search</label>
                        <input type="text" class="form-control" id="searchAllocation" placeholder="Search allocations...">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="statusFilter" class="form-label">Status</label>
                        <select class="form-select" id="statusFilter">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="returned">Returned</option>
                            <option value="overdue">Overdue</option>
                            <option value="lost">Lost</option>
                            <option value="damaged">Damaged</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="employeeFilter" class="form-label">Employee</label>
                        <select class="form-select" id="employeeFilter">
                            <option value="">All Employees</option>
                            <!-- Will be populated dynamically -->
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="categoryFilter" class="form-label">Category</label>
                        <select class="form-select" id="categoryFilter">
                            <option value="">All Categories</option>
                            <!-- Will be populated dynamically -->
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="dateRangeFilter" class="form-label">Date Range</label>
                        <select class="form-select" id="dateRangeFilter">
                            <option value="">All Dates</option>
                            <option value="today">Today</option>
                            <option value="yesterday">Yesterday</option>
                            <option value="this_week">This Week</option>
                            <option value="last_week">Last Week</option>
                            <option value="this_month">This Month</option>
                            <option value="last_month">Last Month</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                </div>
                <div class="row" id="customDateRange" style="display: none;">
                    <div class="col-md-3 mb-3">
                        <label for="startDate" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="startDate">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="endDate" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="endDate">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="sortBy" class="form-label">Sort By</label>
                        <select class="form-select" id="sortBy">
                            <option value="allocated_date">Allocation Date</option>
                            <option value="expected_return_date">Expected Return</option>
                            <option value="employee_name">Employee Name</option>
                            <option value="item_name">Item Name</option>
                            <option value="status">Status</option>
                        </select>
                    </div>
                    <div class="col-md-9 mb-3 d-flex align-items-end">
                        <button type="button" class="btn btn-primary me-2" onclick="applyFilters()">
                            <i class="fas fa-search"></i> Apply Filters
                        </button>
                        <button type="button" class="btn btn-secondary me-2" onclick="clearFilters()">
                            <i class="fas fa-times"></i> Clear
                        </button>
                        <button type="button" class="btn btn-info" onclick="exportAllocations()">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Allocations Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Asset Allocations</h6>
            <div>
                <div class="btn-group" role="group">
                    <input type="checkbox" class="btn-check" id="selectAll" autocomplete="off">
                    <label class="btn btn-outline-secondary btn-sm" for="selectAll">Select All</label>
                </div>
                <div class="btn-group ms-2" role="group">
                    <button type="button" class="btn btn-outline-success btn-sm" onclick="bulkAction('return')" disabled id="bulkReturn">
                        <i class="fas fa-undo"></i> Return
                    </button>
                    <button type="button" class="btn btn-outline-warning btn-sm" onclick="bulkAction('extend')" disabled id="bulkExtend">
                        <i class="fas fa-calendar-plus"></i> Extend
                    </button>
                    <button type="button" class="btn btn-outline-info btn-sm" onclick="bulkAction('remind')" disabled id="bulkRemind">
                        <i class="fas fa-bell"></i> Send Reminder
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="allocationsTable">
                    <thead>
                        <tr>
                            <th width="30"><input type="checkbox" id="selectAllTable"></th>
                            <th>Asset</th>
                            <th>Employee</th>
                            <th>Allocated Date</th>
                            <th>Expected Return</th>
                            <th>Status</th>
                            <th>Days Used</th>
                            <th>Condition</th>
                            <th width="150">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="allocationsTableBody">
                        <!-- Dynamic content will be loaded here -->
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <nav aria-label="Allocations pagination">
                <ul class="pagination justify-content-center" id="allocationsPagination">
                    <!-- Pagination will be generated dynamically -->
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- Allocate Asset Modal -->
<div class="modal fade" id="allocateAssetModal" tabindex="-1" aria-labelledby="allocateAssetModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="allocateAssetModalLabel">Allocate Asset</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="allocationForm">
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="assetSelect" class="form-label">Asset *</label>
                            <select class="form-select" id="assetSelect" name="inventory_item_id" required>
                                <option value="">Select Asset</option>
                                <!-- Will be populated dynamically -->
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="employeeSelect" class="form-label">Employee *</label>
                            <select class="form-select" id="employeeSelect" name="allocated_to" required>
                                <option value="">Select Employee</option>
                                <!-- Will be populated dynamically -->
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="allocatedDate" class="form-label">Allocation Date *</label>
                            <input type="date" class="form-control" id="allocatedDate" name="allocated_date" required>
                        </div>
                        <div class="col-md-6">
                            <label for="expectedReturnDate" class="form-label">Expected Return Date</label>
                            <input type="date" class="form-control" id="expectedReturnDate" name="expected_return_date">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="purpose" class="form-label">Purpose</label>
                            <input type="text" class="form-control" id="purpose" name="purpose" placeholder="Purpose of allocation">
                        </div>
                        <div class="col-md-6">
                            <label for="location" class="form-label">Location</label>
                            <input type="text" class="form-control" id="location" name="location" placeholder="Where asset will be used">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Additional notes or instructions"></textarea>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="condition" class="form-label">Condition at Allocation</label>
                            <select class="form-select" id="condition" name="condition">
                                <option value="excellent">Excellent</option>
                                <option value="good" selected>Good</option>
                                <option value="fair">Fair</option>
                                <option value="poor">Poor</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" id="sendNotification" name="send_notification" checked>
                                <label class="form-check-label" for="sendNotification">
                                    Send notification to employee
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Allocate Asset</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Return Asset Modal -->
<div class="modal fade" id="returnAssetModal" tabindex="-1" aria-labelledby="returnAssetModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="returnAssetModalLabel">Return Asset</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="returnAssetForm">
                <div class="modal-body">
                    <input type="hidden" id="returnAllocationId" name="allocation_id">
                    
                    <div class="mb-3">
                        <label for="returnDate" class="form-label">Return Date *</label>
                        <input type="date" class="form-control" id="returnDate" name="return_date" required>
                    </div>

                    <div class="mb-3">
                        <label for="returnCondition" class="form-label">Condition at Return *</label>
                        <select class="form-select" id="returnCondition" name="return_condition" required>
                            <option value="">Select Condition</option>
                            <option value="excellent">Excellent</option>
                            <option value="good">Good</option>
                            <option value="fair">Fair</option>
                            <option value="poor">Poor</option>
                            <option value="damaged">Damaged</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="returnNotes" class="form-label">Return Notes</label>
                        <textarea class="form-control" id="returnNotes" name="return_notes" rows="3" placeholder="Any issues or observations"></textarea>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="requiresMaintenance" name="requires_maintenance">
                        <label class="form-check-label" for="requiresMaintenance">
                            Asset requires maintenance
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Return Asset</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Extend Allocation Modal -->
<div class="modal fade" id="extendAllocationModal" tabindex="-1" aria-labelledby="extendAllocationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="extendAllocationModalLabel">Extend Allocation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="extendAllocationForm">
                <div class="modal-body">
                    <input type="hidden" id="extendAllocationId" name="allocation_id">
                    
                    <div class="mb-3">
                        <label for="newReturnDate" class="form-label">New Expected Return Date *</label>
                        <input type="date" class="form-control" id="newReturnDate" name="new_return_date" required>
                    </div>

                    <div class="mb-3">
                        <label for="extensionReason" class="form-label">Reason for Extension *</label>
                        <textarea class="form-control" id="extensionReason" name="reason" rows="3" placeholder="Explain why the allocation needs to be extended" required></textarea>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="notifyEmployee" name="notify_employee" checked>
                        <label class="form-check-label" for="notifyEmployee">
                            Notify employee about extension
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Extend Allocation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Statistics Modal -->
<div class="modal fade" id="statisticsModal" tabindex="-1" aria-labelledby="statisticsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="statisticsModalLabel">Allocation Statistics</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <canvas id="statusChart"></canvas>
                    </div>
                    <div class="col-md-6">
                        <canvas id="monthlyTrendChart"></canvas>
                    </div>
                </div>
                <div class="row mt-4">
                    <div class="col-12">
                        <h6>Top Allocated Assets</h6>
                        <div class="table-responsive">
                            <table class="table table-sm" id="topAssetsTable">
                                <thead>
                                    <tr>
                                        <th>Asset</th>
                                        <th>Category</th>
                                        <th>Allocations</th>
                                        <th>Avg. Duration</th>
                                    </tr>
                                </thead>
                                <tbody id="topAssetsTableBody">
                                    <!-- Will be populated dynamically -->
                                </tbody>
                            </table>
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
let currentPage = 1;
let selectedAllocations = [];

$(document).ready(function() {
    loadAllocationsSummary();
    loadAllocations();
    loadEmployees();
    loadCategories();
    loadAvailableAssets();
    
    // Set default allocation date to today
    $('#allocatedDate').val(new Date().toISOString().split('T')[0]);
    $('#returnDate').val(new Date().toISOString().split('T')[0]);
    
    // Initialize form validation
    $('#allocationForm').on('submit', handleAllocationSubmit);
    $('#returnAssetForm').on('submit', handleReturnSubmit);
    $('#extendAllocationForm').on('submit', handleExtendSubmit);
    
    // Search functionality
    $('#searchAllocation').on('input', debounce(function() {
        currentPage = 1;
        loadAllocations();
    }, 300));
    
    // Date range filter
    $('#dateRangeFilter').on('change', function() {
        if ($(this).val() === 'custom') {
            $('#customDateRange').show();
        } else {
            $('#customDateRange').hide();
        }
    });
    
    // Select all functionality
    $('#selectAllTable').on('change', function() {
        const isChecked = $(this).is(':checked');
        $('.allocation-checkbox').prop('checked', isChecked);
        updateSelectedAllocations();
    });
    
    // Individual checkbox change
    $(document).on('change', '.allocation-checkbox', function() {
        updateSelectedAllocations();
    });
});

function loadAllocationsSummary() {
    $.get('/api/allocations/summary')
        .done(function(data) {
            $('#totalAllocations').text(data.total || 0);
            $('#activeAllocations').text(data.active || 0);
            $('#returnedAllocations').text(data.returned || 0);
            $('#overdueAllocations').text(data.overdue || 0);
            $('#dueTodayAllocations').text(data.due_today || 0);
            $('#dueTomorrowAllocations').text(data.due_tomorrow || 0);
        })
        .fail(function() {
            showToast('Error loading allocations summary', 'error');
        });
}

function loadAllocations(page = 1) {
    const filters = {
        search: $('#searchAllocation').val(),
        status: $('#statusFilter').val(),
        employee: $('#employeeFilter').val(),
        category: $('#categoryFilter').val(),
        date_range: $('#dateRangeFilter').val(),
        start_date: $('#startDate').val(),
        end_date: $('#endDate').val(),
        sort_by: $('#sortBy').val(),
        page: page
    };
    
    $.get('/api/allocations', filters)
        .done(function(data) {
            renderAllocationsTable(data.data);
            renderPagination(data);
            currentPage = page;
        })
        .fail(function() {
            showToast('Error loading allocations', 'error');
        });
}

function renderAllocationsTable(allocations) {
    const tbody = $('#allocationsTableBody');
    tbody.empty();
    
    if (allocations.length === 0) {
        tbody.append(`
            <tr>
                <td colspan="9" class="text-center py-4">
                    <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No allocations found</p>
                </td>
            </tr>
        `);
        return;
    }
    
    allocations.forEach(allocation => {
        const statusBadge = getStatusBadge(allocation.status);
        const conditionBadge = getConditionBadge(allocation.condition);
        const daysUsed = calculateDaysUsed(allocation.allocated_date, allocation.return_date);
        const isOverdue = allocation.status === 'active' && 
            allocation.expected_return_date && 
            new Date(allocation.expected_return_date) < new Date();
        
        tbody.append(`
            <tr class="${isOverdue ? 'table-warning' : ''}">
                <td>
                    <input type="checkbox" class="allocation-checkbox" value="${allocation.id}">
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center me-2">
                            <i class="fas fa-laptop text-white"></i>
                        </div>
                        <div>
                            <div class="fw-bold">${allocation.inventory_item.name}</div>
                            <small class="text-muted">${allocation.inventory_item.barcode || 'No barcode'}</small>
                        </div>
                    </div>
                </td>
                <td>
                    <div>
                        <div class="fw-bold">${allocation.employee.name}</div>
                        <small class="text-muted">${allocation.employee.email || ''}</small>
                    </div>
                </td>
                <td>${new Date(allocation.allocated_date).toLocaleDateString()}</td>
                <td>
                    ${allocation.expected_return_date ? 
                        `<span class="${isOverdue ? 'text-danger fw-bold' : ''}">${new Date(allocation.expected_return_date).toLocaleDateString()}</span>` : 
                        '<span class="text-muted">Not set</span>'
                    }
                </td>
                <td>${statusBadge}</td>
                <td>
                    <span class="badge bg-info">${daysUsed} days</span>
                </td>
                <td>${conditionBadge}</td>
                <td>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewAllocation(${allocation.id})" title="View">
                            <i class="fas fa-eye"></i>
                        </button>
                        ${allocation.status === 'active' ? `
                            <button type="button" class="btn btn-sm btn-outline-success" onclick="returnAsset(${allocation.id})" title="Return">
                                <i class="fas fa-undo"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-warning" onclick="extendAllocation(${allocation.id})" title="Extend">
                                <i class="fas fa-calendar-plus"></i>
                            </button>
                        ` : ''}
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="editAllocation(${allocation.id})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `);
    });
}

function getStatusBadge(status) {
    const badges = {
        'active': '<span class="badge bg-success">Active</span>',
        'returned': '<span class="badge bg-info">Returned</span>',
        'overdue': '<span class="badge bg-danger">Overdue</span>',
        'lost': '<span class="badge bg-dark">Lost</span>',
        'damaged': '<span class="badge bg-warning">Damaged</span>'
    };
    return badges[status] || '<span class="badge bg-secondary">Unknown</span>';
}

function getConditionBadge(condition) {
    const badges = {
        'excellent': '<span class="badge bg-success">Excellent</span>',
        'good': '<span class="badge bg-primary">Good</span>',
        'fair': '<span class="badge bg-warning">Fair</span>',
        'poor': '<span class="badge bg-danger">Poor</span>',
        'damaged': '<span class="badge bg-dark">Damaged</span>'
    };
    return badges[condition] || '<span class="badge bg-secondary">Unknown</span>';
}

function calculateDaysUsed(allocatedDate, returnDate) {
    const start = new Date(allocatedDate);
    const end = returnDate ? new Date(returnDate) : new Date();
    const diffTime = Math.abs(end - start);
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
}

function renderPagination(data) {
    const pagination = $('#allocationsPagination');
    pagination.empty();
    
    if (data.last_page <= 1) return;
    
    // Previous button
    pagination.append(`
        <li class="page-item ${data.current_page === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="loadAllocations(${data.current_page - 1})">Previous</a>
        </li>
    `);
    
    // Page numbers
    for (let i = 1; i <= data.last_page; i++) {
        if (i === data.current_page || 
            i === 1 || 
            i === data.last_page || 
            (i >= data.current_page - 1 && i <= data.current_page + 1)) {
            pagination.append(`
                <li class="page-item ${i === data.current_page ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="loadAllocations(${i})">${i}</a>
                </li>
            `);
        } else if (i === data.current_page - 2 || i === data.current_page + 2) {
            pagination.append('<li class="page-item disabled"><span class="page-link">...</span></li>');
        }
    }
    
    // Next button
    pagination.append(`
        <li class="page-item ${data.current_page === data.last_page ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="loadAllocations(${data.current_page + 1})">Next</a>
        </li>
    `);
}

function loadEmployees() {
    $.get('/api/employees')
        .done(function(employees) {
            const select = $('#employeeSelect, #employeeFilter');
            select.find('option:not(:first)').remove();
            employees.forEach(employee => {
                select.append(`<option value="${employee.id}">${employee.name}</option>`);
            });
        });
}

function loadCategories() {
    $.get('/api/categories')
        .done(function(categories) {
            const select = $('#categoryFilter');
            select.find('option:not(:first)').remove();
            categories.forEach(category => {
                select.append(`<option value="${category.id}">${category.name}</option>`);
            });
        });
}

function loadAvailableAssets() {
    $.get('/api/inventory-items/available-assets')
        .done(function(assets) {
            const select = $('#assetSelect');
            select.find('option:not(:first)').remove();
            assets.forEach(asset => {
                select.append(`<option value="${asset.id}">${asset.name} (${asset.barcode || 'No barcode'})</option>`);
            });
        });
}

function applyFilters() {
    currentPage = 1;
    loadAllocations();
}

function clearFilters() {
    $('#filterForm')[0].reset();
    $('#customDateRange').hide();
    currentPage = 1;
    loadAllocations();
}

function updateSelectedAllocations() {
    selectedAllocations = $('.allocation-checkbox:checked').map(function() {
        return $(this).val();
    }).get();
    
    const hasSelection = selectedAllocations.length > 0;
    $('#bulkReturn, #bulkExtend, #bulkRemind').prop('disabled', !hasSelection);
    
    // Update select all checkbox
    const totalCheckboxes = $('.allocation-checkbox').length;
    const checkedCheckboxes = $('.allocation-checkbox:checked').length;
    $('#selectAllTable').prop('indeterminate', checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes);
    $('#selectAllTable').prop('checked', checkedCheckboxes === totalCheckboxes && totalCheckboxes > 0);
}

function handleAllocationSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    $.ajax({
        url: '/api/allocations',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            $('#allocateAssetModal').modal('hide');
            showToast('Asset allocated successfully', 'success');
            loadAllocations(currentPage);
            loadAllocationsSummary();
            loadAvailableAssets();
            $('#allocationForm')[0].reset();
        },
        error: function(xhr) {
            const errors = xhr.responseJSON?.errors;
            if (errors) {
                Object.keys(errors).forEach(key => {
                    showToast(errors[key][0], 'error');
                });
            } else {
                showToast('Error allocating asset', 'error');
            }
        }
    });
}

function handleReturnSubmit(e) {
    e.preventDefault();
    
    const allocationId = $('#returnAllocationId').val();
    const formData = new FormData(this);
    
    $.ajax({
        url: `/api/allocations/${allocationId}/return`,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            $('#returnAssetModal').modal('hide');
            showToast('Asset returned successfully', 'success');
            loadAllocations(currentPage);
            loadAllocationsSummary();
            $('#returnAssetForm')[0].reset();
        },
        error: function(xhr) {
            showToast(xhr.responseJSON?.message || 'Error returning asset', 'error');
        }
    });
}

function handleExtendSubmit(e) {
    e.preventDefault();
    
    const allocationId = $('#extendAllocationId').val();
    const formData = new FormData(this);
    
    $.ajax({
        url: `/api/allocations/${allocationId}/extend`,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            $('#extendAllocationModal').modal('hide');
            showToast('Allocation extended successfully', 'success');
            loadAllocations(currentPage);
            loadAllocationsSummary();
            $('#extendAllocationForm')[0].reset();
        },
        error: function(xhr) {
            showToast(xhr.responseJSON?.message || 'Error extending allocation', 'error');
        }
    });
}

function viewAllocation(id) {
    window.location.href = `/inventory/allocations/${id}`;
}

function editAllocation(id) {
    window.location.href = `/inventory/allocations/${id}/edit`;
}

function returnAsset(id) {
    $('#returnAllocationId').val(id);
    $('#returnAssetModal').modal('show');
}

function extendAllocation(id) {
    $('#extendAllocationId').val(id);
    $('#extendAllocationModal').modal('show');
}

function loadOverdueAllocations() {
    $('#statusFilter').val('overdue');
    applyFilters();
}

function loadDueTodayAllocations() {
    $('#dateRangeFilter').val('today');
    applyFilters();
}

function loadDueTomorrowAllocations() {
    $('#dateRangeFilter').val('tomorrow');
    applyFilters();
}

function showStatistics() {
    $.get('/api/allocations/statistics')
        .done(function(data) {
            renderStatisticsCharts(data);
            $('#statisticsModal').modal('show');
        })
        .fail(function() {
            showToast('Error loading statistics', 'error');
        });
}

function renderStatisticsCharts(data) {
    // Status distribution chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(data.status_distribution),
            datasets: [{
                data: Object.values(data.status_distribution),
                backgroundColor: ['#28a745', '#17a2b8', '#dc3545', '#6c757d', '#ffc107']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Allocation Status Distribution'
                }
            }
        }
    });
    
    // Monthly trend chart
    const trendCtx = document.getElementById('monthlyTrendChart').getContext('2d');
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: data.monthly_trends.map(item => item.month),
            datasets: [{
                label: 'Allocations',
                data: data.monthly_trends.map(item => item.count),
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Monthly Allocation Trends'
                }
            }
        }
    });
    
    // Top assets table
    const tbody = $('#topAssetsTableBody');
    tbody.empty();
    data.top_allocated_items.forEach(item => {
        tbody.append(`
            <tr>
                <td>${item.name}</td>
                <td>${item.category}</td>
                <td><span class="badge bg-primary">${item.allocations_count}</span></td>
                <td>${item.avg_duration} days</td>
            </tr>
        `);
    });
}

function bulkAction(action) {
    if (selectedAllocations.length === 0) {
        showToast('Please select allocations first', 'warning');
        return;
    }
    
    let url, method, message;
    
    switch (action) {
        case 'return':
            url = '/api/allocations/bulk-return';
            method = 'POST';
            message = 'returned';
            break;
        case 'extend':
            url = '/api/allocations/bulk-extend';
            method = 'POST';
            message = 'extended';
            break;
        case 'remind':
            url = '/api/allocations/bulk-remind';
            method = 'POST';
            message = 'reminded';
            break;
    }
    
    $.ajax({
        url: url,
        method: method,
        data: {
            allocation_ids: selectedAllocations
        },
        success: function(response) {
            showToast(`Successfully ${message} ${selectedAllocations.length} allocation(s)`, 'success');
            loadAllocations(currentPage);
            loadAllocationsSummary();
            selectedAllocations = [];
            updateSelectedAllocations();
        },
        error: function() {
            showToast(`Error ${action}ing allocations`, 'error');
        }
    });
}

function generateReport() {
    const filters = {
        search: $('#searchAllocation').val(),
        status: $('#statusFilter').val(),
        employee: $('#employeeFilter').val(),
        category: $('#categoryFilter').val(),
        date_range: $('#dateRangeFilter').val(),
        start_date: $('#startDate').val(),
        end_date: $('#endDate').val()
    };
    
    const queryString = new URLSearchParams(filters).toString();
    window.open(`/api/allocations/report?${queryString}`, '_blank');
}

function exportAllocations() {
    const filters = {
        search: $('#searchAllocation').val(),
        status: $('#statusFilter').val(),
        employee: $('#employeeFilter').val(),
        category: $('#categoryFilter').val(),
        date_range: $('#dateRangeFilter').val(),
        start_date: $('#startDate').val(),
        end_date: $('#endDate').val()
    };
    
    const queryString = new URLSearchParams(filters).toString();
    window.open(`/api/allocations/export?${queryString}`, '_blank');
}

// Utility functions
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

function showToast(message, type = 'info') {
    // Implement your toast notification system here
    console.log(`${type.toUpperCase()}: ${message}`);
}
</script>
@endsection