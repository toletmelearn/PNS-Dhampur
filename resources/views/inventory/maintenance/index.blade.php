@extends('layouts.app')

@section('title', 'Maintenance Schedule')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Maintenance Schedule</h1>
            <p class="mb-0 text-muted">Manage asset maintenance schedules and track service history</p>
        </div>
        <div>
            <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#scheduleMaintenanceModal">
                <i class="fas fa-plus"></i> Schedule Maintenance
            </button>
            <div class="btn-group">
                <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="fas fa-download"></i> Export
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#" onclick="exportMaintenance('pdf')"><i class="fas fa-file-pdf"></i> Export PDF</a></li>
                    <li><a class="dropdown-item" href="#" onclick="exportMaintenance('excel')"><i class="fas fa-file-excel"></i> Export Excel</a></li>
                    <li><a class="dropdown-item" href="#" onclick="exportMaintenance('csv')"><i class="fas fa-file-csv"></i> Export CSV</a></li>
                </ul>
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
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Schedules</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalSchedules">0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-alt fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Due Today</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="dueToday">0</div>
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
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Overdue</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="overdue">0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
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
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="completedMonth">0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
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
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <button class="btn btn-warning btn-block" onclick="filterByStatus('due_today')">
                                <i class="fas fa-exclamation-triangle"></i> Due Today
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-danger btn-block" onclick="filterByStatus('overdue')">
                                <i class="fas fa-clock"></i> Overdue
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-info btn-block" onclick="filterByStatus('upcoming')">
                                <i class="fas fa-calendar-plus"></i> Upcoming
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-outline-primary btn-block" onclick="showMaintenanceCalendar()">
                                <i class="fas fa-calendar"></i> Calendar View
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
            <h6 class="m-0 font-weight-bold text-primary">Filters</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <label for="searchMaintenance" class="form-label">Search</label>
                    <input type="text" class="form-control" id="searchMaintenance" placeholder="Search by asset, type, or technician">
                </div>
                <div class="col-md-2">
                    <label for="statusFilter" class="form-label">Status</label>
                    <select class="form-select" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="scheduled">Scheduled</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="overdue">Overdue</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="typeFilter" class="form-label">Type</label>
                    <select class="form-select" id="typeFilter">
                        <option value="">All Types</option>
                        <option value="preventive">Preventive</option>
                        <option value="corrective">Corrective</option>
                        <option value="emergency">Emergency</option>
                        <option value="inspection">Inspection</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="priorityFilter" class="form-label">Priority</label>
                    <select class="form-select" id="priorityFilter">
                        <option value="">All Priorities</option>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="dateRangeFilter" class="form-label">Date Range</label>
                    <select class="form-select" id="dateRangeFilter">
                        <option value="">All Dates</option>
                        <option value="today">Today</option>
                        <option value="tomorrow">Tomorrow</option>
                        <option value="this_week">This Week</option>
                        <option value="next_week">Next Week</option>
                        <option value="this_month">This Month</option>
                        <option value="custom">Custom Range</option>
                    </select>
                </div>
            </div>
            <div class="row mt-3" id="customDateRange" style="display: none;">
                <div class="col-md-3">
                    <label for="startDate" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="startDate">
                </div>
                <div class="col-md-3">
                    <label for="endDate" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="endDate">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary" onclick="applyDateFilter()">Apply</button>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <button class="btn btn-outline-secondary" onclick="clearFilters()">
                        <i class="fas fa-times"></i> Clear Filters
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Maintenance Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Maintenance Schedules</h6>
            <div>
                <div class="btn-group me-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleView('table')" id="tableViewBtn">
                        <i class="fas fa-table"></i> Table
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleView('calendar')" id="calendarViewBtn">
                        <i class="fas fa-calendar"></i> Calendar
                    </button>
                </div>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                        Bulk Actions
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" onclick="bulkAction('complete')"><i class="fas fa-check"></i> Mark Complete</a></li>
                        <li><a class="dropdown-item" href="#" onclick="bulkAction('reschedule')"><i class="fas fa-calendar-alt"></i> Reschedule</a></li>
                        <li><a class="dropdown-item" href="#" onclick="bulkAction('cancel')"><i class="fas fa-times"></i> Cancel</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#" onclick="bulkAction('export')"><i class="fas fa-download"></i> Export Selected</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="card-body">
            <!-- Table View -->
            <div id="tableView">
                <div class="table-responsive">
                    <table class="table table-bordered" id="maintenanceTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll"></th>
                                <th>Asset</th>
                                <th>Type</th>
                                <th>Scheduled Date</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Technician</th>
                                <th>Estimated Cost</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="maintenanceTableBody">
                            <!-- Data will be populated dynamically -->
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>
                        <span class="text-muted">Showing <span id="showingStart">0</span> to <span id="showingEnd">0</span> of <span id="totalRecords">0</span> entries</span>
                    </div>
                    <nav>
                        <ul class="pagination mb-0" id="pagination">
                            <!-- Pagination will be populated dynamically -->
                        </ul>
                    </nav>
                </div>
            </div>

            <!-- Calendar View -->
            <div id="calendarView" style="display: none;">
                <div id="maintenanceCalendar"></div>
            </div>
        </div>
    </div>
</div>

<!-- Schedule Maintenance Modal -->
<div class="modal fade" id="scheduleMaintenanceModal" tabindex="-1" aria-labelledby="scheduleMaintenanceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="scheduleMaintenanceModalLabel">Schedule Maintenance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="scheduleMaintenanceForm">
                @csrf
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="asset_id" class="form-label">Asset *</label>
                            <select class="form-select" id="asset_id" name="asset_id" required>
                                <option value="">Select Asset</option>
                                <!-- Will be populated dynamically -->
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="maintenance_type" class="form-label">Maintenance Type *</label>
                            <select class="form-select" id="maintenance_type" name="maintenance_type" required>
                                <option value="">Select Type</option>
                                <option value="preventive">Preventive</option>
                                <option value="corrective">Corrective</option>
                                <option value="emergency">Emergency</option>
                                <option value="inspection">Inspection</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="scheduled_date" class="form-label">Scheduled Date *</label>
                            <input type="datetime-local" class="form-control" id="scheduled_date" name="scheduled_date" required>
                        </div>
                        <div class="col-md-6">
                            <label for="priority" class="form-label">Priority *</label>
                            <select class="form-select" id="priority" name="priority" required>
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="assigned_to" class="form-label">Assign To</label>
                            <select class="form-select" id="assigned_to" name="assigned_to">
                                <option value="">Select Technician</option>
                                <!-- Will be populated dynamically -->
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="estimated_duration" class="form-label">Estimated Duration (hours)</label>
                            <input type="number" class="form-control" id="estimated_duration" name="estimated_duration" step="0.5" min="0.5">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="estimated_cost" class="form-label">Estimated Cost</label>
                            <input type="number" class="form-control" id="estimated_cost" name="estimated_cost" step="0.01" min="0">
                        </div>
                        <div class="col-md-6">
                            <label for="vendor_id" class="form-label">Vendor</label>
                            <select class="form-select" id="vendor_id" name="vendor_id">
                                <option value="">Select Vendor</option>
                                <!-- Will be populated dynamically -->
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" placeholder="Describe the maintenance work to be performed"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Additional notes or instructions"></textarea>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="send_notification" name="send_notification" checked>
                                <label class="form-check-label" for="send_notification">
                                    Send notification to technician
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="recurring" name="recurring">
                                <label class="form-check-label" for="recurring">
                                    Recurring maintenance
                                </label>
                            </div>
                        </div>
                    </div>
                    <div id="recurringOptions" style="display: none;">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="recurring_frequency" class="form-label">Frequency</label>
                                <select class="form-select" id="recurring_frequency" name="recurring_frequency">
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly">Monthly</option>
                                    <option value="quarterly">Quarterly</option>
                                    <option value="semi_annually">Semi-Annually</option>
                                    <option value="annually">Annually</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="recurring_end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="recurring_end_date" name="recurring_end_date">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Schedule Maintenance</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Complete Maintenance Modal -->
<div class="modal fade" id="completeMaintenanceModal" tabindex="-1" aria-labelledby="completeMaintenanceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="completeMaintenanceModalLabel">Complete Maintenance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="completeMaintenanceForm">
                @csrf
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="completion_date" class="form-label">Completion Date *</label>
                            <input type="datetime-local" class="form-control" id="completion_date" name="completion_date" required>
                        </div>
                        <div class="col-md-6">
                            <label for="actual_duration" class="form-label">Actual Duration (hours)</label>
                            <input type="number" class="form-control" id="actual_duration" name="actual_duration" step="0.5" min="0.5">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="actual_cost" class="form-label">Actual Cost</label>
                            <input type="number" class="form-control" id="actual_cost" name="actual_cost" step="0.01" min="0">
                        </div>
                        <div class="col-md-6">
                            <label for="completion_status" class="form-label">Status *</label>
                            <select class="form-select" id="completion_status" name="completion_status" required>
                                <option value="completed">Completed</option>
                                <option value="partially_completed">Partially Completed</option>
                                <option value="requires_followup">Requires Follow-up</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="work_performed" class="form-label">Work Performed *</label>
                        <textarea class="form-control" id="work_performed" name="work_performed" rows="4" required placeholder="Describe the work that was performed"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="parts_used" class="form-label">Parts Used</label>
                        <textarea class="form-control" id="parts_used" name="parts_used" rows="2" placeholder="List any parts or materials used"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="completion_notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="completion_notes" name="completion_notes" rows="2" placeholder="Additional notes or recommendations"></textarea>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="schedule_next" name="schedule_next">
                        <label class="form-check-label" for="schedule_next">
                            Schedule next maintenance
                        </label>
                    </div>
                    <div id="nextMaintenanceOptions" style="display: none;">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="next_maintenance_date" class="form-label">Next Maintenance Date</label>
                                <input type="date" class="form-control" id="next_maintenance_date" name="next_maintenance_date">
                            </div>
                            <div class="col-md-6">
                                <label for="next_maintenance_type" class="form-label">Next Maintenance Type</label>
                                <select class="form-select" id="next_maintenance_type" name="next_maintenance_type">
                                    <option value="preventive">Preventive</option>
                                    <option value="inspection">Inspection</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Complete Maintenance</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
let currentPage = 1;
let currentFilters = {};
let currentView = 'table';

$(document).ready(function() {
    loadSummaryData();
    loadMaintenanceData();
    loadAssets();
    loadTechnicians();
    loadVendors();
    
    // Initialize event listeners
    initializeEventListeners();
    
    // Set default scheduled date to tomorrow
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    $('#scheduled_date').val(tomorrow.toISOString().slice(0, 16));
    
    // Set default completion date to now
    $('#completion_date').val(new Date().toISOString().slice(0, 16));
});

function initializeEventListeners() {
    // Search functionality
    $('#searchMaintenance').on('input', debounce(function() {
        currentFilters.search = $(this).val();
        loadMaintenanceData();
    }, 300));
    
    // Filter changes
    $('#statusFilter, #typeFilter, #priorityFilter, #dateRangeFilter').on('change', function() {
        const filterId = $(this).attr('id');
        const filterKey = filterId.replace('Filter', '');
        currentFilters[filterKey] = $(this).val();
        
        if (filterId === 'dateRangeFilter' && $(this).val() === 'custom') {
            $('#customDateRange').show();
        } else if (filterId === 'dateRangeFilter') {
            $('#customDateRange').hide();
            loadMaintenanceData();
        } else {
            loadMaintenanceData();
        }
    });
    
    // Select all checkbox
    $('#selectAll').on('change', function() {
        $('.maintenance-checkbox').prop('checked', $(this).is(':checked'));
    });
    
    // Form submissions
    $('#scheduleMaintenanceForm').on('submit', function(e) {
        e.preventDefault();
        scheduleMaintenanceSubmit();
    });
    
    $('#completeMaintenanceForm').on('submit', function(e) {
        e.preventDefault();
        completeMaintenanceSubmit();
    });
    
    // Recurring maintenance toggle
    $('#recurring').on('change', function() {
        if ($(this).is(':checked')) {
            $('#recurringOptions').show();
        } else {
            $('#recurringOptions').hide();
        }
    });
    
    // Schedule next maintenance toggle
    $('#schedule_next').on('change', function() {
        if ($(this).is(':checked')) {
            $('#nextMaintenanceOptions').show();
        } else {
            $('#nextMaintenanceOptions').hide();
        }
    });
}

function loadSummaryData() {
    $.get('/api/maintenance/summary')
        .done(function(data) {
            $('#totalSchedules').text(data.total_schedules || 0);
            $('#dueToday').text(data.due_today || 0);
            $('#overdue').text(data.overdue || 0);
            $('#completedMonth').text(data.completed_month || 0);
        })
        .fail(function() {
            console.error('Error loading summary data');
        });
}

function loadMaintenanceData(page = 1) {
    const params = {
        page: page,
        ...currentFilters
    };
    
    $.get('/api/maintenance', params)
        .done(function(response) {
            populateMaintenanceTable(response.data);
            updatePagination(response);
            updateTableInfo(response);
        })
        .fail(function() {
            showToast('Error loading maintenance data', 'error');
        });
}

function populateMaintenanceTable(maintenances) {
    const tbody = $('#maintenanceTableBody');
    tbody.empty();
    
    if (maintenances.length === 0) {
        tbody.append(`
            <tr>
                <td colspan="9" class="text-center text-muted">No maintenance schedules found</td>
            </tr>
        `);
        return;
    }
    
    maintenances.forEach(maintenance => {
        const statusBadge = getStatusBadge(maintenance.status);
        const priorityBadge = getPriorityBadge(maintenance.priority);
        const typeBadge = getTypeBadge(maintenance.maintenance_type);
        
        tbody.append(`
            <tr>
                <td><input type="checkbox" class="maintenance-checkbox" value="${maintenance.id}"></td>
                <td>
                    <div class="d-flex align-items-center">
                        <div>
                            <div class="fw-bold">${maintenance.inventory_item.name}</div>
                            <small class="text-muted">${maintenance.inventory_item.barcode || 'N/A'}</small>
                        </div>
                    </div>
                </td>
                <td>${typeBadge}</td>
                <td>
                    <div>${new Date(maintenance.scheduled_date).toLocaleDateString()}</div>
                    <small class="text-muted">${new Date(maintenance.scheduled_date).toLocaleTimeString()}</small>
                </td>
                <td>${statusBadge}</td>
                <td>${priorityBadge}</td>
                <td>${maintenance.assigned_to?.name || 'Unassigned'}</td>
                <td>$${maintenance.estimated_cost || '0.00'}</td>
                <td>
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                            Actions
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/inventory/maintenance/${maintenance.id}"><i class="fas fa-eye"></i> View</a></li>
                            <li><a class="dropdown-item" href="#" onclick="editMaintenance(${maintenance.id})"><i class="fas fa-edit"></i> Edit</a></li>
                            ${maintenance.status === 'scheduled' || maintenance.status === 'in_progress' ? 
                                `<li><a class="dropdown-item" href="#" onclick="completeMaintenance(${maintenance.id})"><i class="fas fa-check"></i> Complete</a></li>` : ''}
                            <li><a class="dropdown-item" href="#" onclick="rescheduleMaintenance(${maintenance.id})"><i class="fas fa-calendar-alt"></i> Reschedule</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="#" onclick="cancelMaintenance(${maintenance.id})"><i class="fas fa-times"></i> Cancel</a></li>
                        </ul>
                    </div>
                </td>
            </tr>
        `);
    });
}

function loadAssets() {
    $.get('/api/inventory-items', { type: 'asset' })
        .done(function(assets) {
            const select = $('#asset_id');
            select.find('option:not(:first)').remove();
            assets.forEach(asset => {
                select.append(`<option value="${asset.id}">${asset.name} (${asset.barcode || 'No barcode'})</option>`);
            });
        });
}

function loadTechnicians() {
    $.get('/api/employees', { role: 'technician' })
        .done(function(technicians) {
            const select = $('#assigned_to');
            select.find('option:not(:first)').remove();
            technicians.forEach(tech => {
                select.append(`<option value="${tech.id}">${tech.name}</option>`);
            });
        });
}

function loadVendors() {
    $.get('/api/vendors')
        .done(function(vendors) {
            const select = $('#vendor_id');
            select.find('option:not(:first)').remove();
            vendors.forEach(vendor => {
                select.append(`<option value="${vendor.id}">${vendor.name}</option>`);
            });
        });
}

function scheduleMaintenanceSubmit() {
    const formData = new FormData($('#scheduleMaintenanceForm')[0]);
    
    $.ajax({
        url: '/api/maintenance',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            $('#scheduleMaintenanceModal').modal('hide');
            showToast('Maintenance scheduled successfully', 'success');
            loadMaintenanceData();
            loadSummaryData();
            $('#scheduleMaintenanceForm')[0].reset();
        },
        error: function(xhr) {
            if (xhr.status === 422) {
                const errors = xhr.responseJSON.errors;
                Object.keys(errors).forEach(key => {
                    const input = $(`[name="${key}"]`);
                    input.addClass('is-invalid');
                });
                showToast('Please correct the form errors', 'error');
            } else {
                showToast(xhr.responseJSON?.message || 'Error scheduling maintenance', 'error');
            }
        }
    });
}

function completeMaintenance(id) {
    // Load maintenance details and show completion modal
    $.get(`/api/maintenance/${id}`)
        .done(function(maintenance) {
            $('#completeMaintenanceForm').data('maintenance-id', id);
            $('#completeMaintenanceModal').modal('show');
        });
}

function completeMaintenanceSubmit() {
    const maintenanceId = $('#completeMaintenanceForm').data('maintenance-id');
    const formData = new FormData($('#completeMaintenanceForm')[0]);
    
    $.ajax({
        url: `/api/maintenance/${maintenanceId}/complete`,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            $('#completeMaintenanceModal').modal('hide');
            showToast('Maintenance completed successfully', 'success');
            loadMaintenanceData();
            loadSummaryData();
            $('#completeMaintenanceForm')[0].reset();
        },
        error: function(xhr) {
            if (xhr.status === 422) {
                const errors = xhr.responseJSON.errors;
                Object.keys(errors).forEach(key => {
                    const input = $(`#completeMaintenanceForm [name="${key}"]`);
                    input.addClass('is-invalid');
                });
                showToast('Please correct the form errors', 'error');
            } else {
                showToast(xhr.responseJSON?.message || 'Error completing maintenance', 'error');
            }
        }
    });
}

function filterByStatus(status) {
    $('#statusFilter').val(status === 'due_today' ? 'scheduled' : status);
    if (status === 'due_today') {
        currentFilters.due_today = true;
    } else if (status === 'upcoming') {
        currentFilters.upcoming = true;
    }
    currentFilters.status = status === 'due_today' ? 'scheduled' : status;
    loadMaintenanceData();
}

function toggleView(view) {
    currentView = view;
    if (view === 'table') {
        $('#tableView').show();
        $('#calendarView').hide();
        $('#tableViewBtn').addClass('active');
        $('#calendarViewBtn').removeClass('active');
    } else {
        $('#tableView').hide();
        $('#calendarView').show();
        $('#tableViewBtn').removeClass('active');
        $('#calendarViewBtn').addClass('active');
        loadMaintenanceCalendar();
    }
}

function loadMaintenanceCalendar() {
    // Initialize calendar (you would use a calendar library like FullCalendar)
    $('#maintenanceCalendar').html('<p class="text-center text-muted">Calendar view would be implemented here using a calendar library like FullCalendar</p>');
}

function clearFilters() {
    currentFilters = {};
    $('#searchMaintenance').val('');
    $('#statusFilter, #typeFilter, #priorityFilter, #dateRangeFilter').val('');
    $('#customDateRange').hide();
    loadMaintenanceData();
}

function applyDateFilter() {
    const startDate = $('#startDate').val();
    const endDate = $('#endDate').val();
    
    if (startDate && endDate) {
        currentFilters.start_date = startDate;
        currentFilters.end_date = endDate;
        loadMaintenanceData();
    }
}

function bulkAction(action) {
    const selectedIds = $('.maintenance-checkbox:checked').map(function() {
        return $(this).val();
    }).get();
    
    if (selectedIds.length === 0) {
        showToast('Please select at least one maintenance schedule', 'warning');
        return;
    }
    
    // Implement bulk actions based on the action parameter
    console.log(`Bulk action: ${action} for IDs:`, selectedIds);
}

function exportMaintenance(format) {
    const params = new URLSearchParams(currentFilters);
    params.append('format', format);
    window.location.href = `/api/maintenance/export?${params.toString()}`;
}

function updatePagination(response) {
    const pagination = $('#pagination');
    pagination.empty();
    
    if (response.last_page <= 1) return;
    
    // Previous button
    if (response.current_page > 1) {
        pagination.append(`
            <li class="page-item">
                <a class="page-link" href="#" onclick="loadMaintenanceData(${response.current_page - 1})">Previous</a>
            </li>
        `);
    }
    
    // Page numbers
    for (let i = Math.max(1, response.current_page - 2); i <= Math.min(response.last_page, response.current_page + 2); i++) {
        pagination.append(`
            <li class="page-item ${i === response.current_page ? 'active' : ''}">
                <a class="page-link" href="#" onclick="loadMaintenanceData(${i})">${i}</a>
            </li>
        `);
    }
    
    // Next button
    if (response.current_page < response.last_page) {
        pagination.append(`
            <li class="page-item">
                <a class="page-link" href="#" onclick="loadMaintenanceData(${response.current_page + 1})">Next</a>
            </li>
        `);
    }
}

function updateTableInfo(response) {
    const start = (response.current_page - 1) * response.per_page + 1;
    const end = Math.min(response.current_page * response.per_page, response.total);
    
    $('#showingStart').text(start);
    $('#showingEnd').text(end);
    $('#totalRecords').text(response.total);
}

// Utility functions
function getStatusBadge(status) {
    const badges = {
        'scheduled': '<span class="badge bg-primary">Scheduled</span>',
        'in_progress': '<span class="badge bg-warning">In Progress</span>',
        'completed': '<span class="badge bg-success">Completed</span>',
        'cancelled': '<span class="badge bg-secondary">Cancelled</span>',
        'overdue': '<span class="badge bg-danger">Overdue</span>'
    };
    return badges[status] || '<span class="badge bg-secondary">Unknown</span>';
}

function getPriorityBadge(priority) {
    const badges = {
        'low': '<span class="badge bg-secondary">Low</span>',
        'medium': '<span class="badge bg-primary">Medium</span>',
        'high': '<span class="badge bg-warning">High</span>',
        'critical': '<span class="badge bg-danger">Critical</span>'
    };
    return badges[priority] || '<span class="badge bg-secondary">Unknown</span>';
}

function getTypeBadge(type) {
    const badges = {
        'preventive': '<span class="badge bg-info">Preventive</span>',
        'corrective': '<span class="badge bg-warning">Corrective</span>',
        'emergency': '<span class="badge bg-danger">Emergency</span>',
        'inspection': '<span class="badge bg-success">Inspection</span>'
    };
    return badges[type] || '<span class="badge bg-secondary">Unknown</span>';
}

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