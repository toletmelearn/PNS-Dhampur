@extends('layouts.app')

@section('title', 'Biometric Reports')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Biometric Reports</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('biometric.index') }}">Biometric</a></li>
                        <li class="breadcrumb-item active">Reports</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Filters -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-filter me-2"></i>
                        Report Filters
                    </h5>
                </div>
                <div class="card-body">
                    <form id="reportFilters" class="row g-3">
                        <div class="col-md-3">
                            <label for="report_type" class="form-label">Report Type</label>
                            <select class="form-select" id="report_type" name="report_type" required>
                                <option value="">Select Report Type</option>
                                <option value="daily" {{ request('report_type') == 'daily' ? 'selected' : '' }}>Daily Attendance</option>
                                <option value="monthly" {{ request('report_type') == 'monthly' ? 'selected' : '' }}>Monthly Summary</option>
                                <option value="employee" {{ request('report_type') == 'employee' ? 'selected' : '' }}>Employee Performance</option>
                                <option value="department" {{ request('report_type') == 'department' ? 'selected' : '' }}>Department Analysis</option>
                                <option value="device" {{ request('report_type') == 'device' ? 'selected' : '' }}>Device Performance</option>
                                <option value="overtime" {{ request('report_type') == 'overtime' ? 'selected' : '' }}>Overtime Report</option>
                                <option value="late_arrivals" {{ request('report_type') == 'late_arrivals' ? 'selected' : '' }}>Late Arrivals</option>
                                <option value="early_departures" {{ request('report_type') == 'early_departures' ? 'selected' : '' }}>Early Departures</option>
                                <option value="missing_punches" {{ request('report_type') == 'missing_punches' ? 'selected' : '' }}>Missing Punches</option>
                                <option value="custom" {{ request('report_type') == 'custom' ? 'selected' : '' }}>Custom Report</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="date_range" class="form-label">Date Range</label>
                            <input type="text" class="form-control" id="date_range" name="date_range" 
                                   value="{{ request('date_range', date('Y-m-d', strtotime('-30 days')) . ' - ' . date('Y-m-d')) }}">
                        </div>
                        <div class="col-md-2">
                            <label for="department" class="form-label">Department</label>
                            <select class="form-select" id="department" name="department">
                                <option value="">All Departments</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ request('department') == $dept->id ? 'selected' : '' }}>
                                        {{ $dept->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="employee" class="form-label">Employee</label>
                            <select class="form-select" id="employee" name="employee">
                                <option value="">All Employees</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}" {{ request('employee') == $emp->id ? 'selected' : '' }}>
                                        {{ $emp->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="mdi mdi-magnify me-1"></i>Generate Report
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Report Actions -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-lightning-bolt me-2"></i>
                        Quick Reports
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <button type="button" class="btn btn-outline-primary w-100" onclick="generateQuickReport('today')">
                                <i class="mdi mdi-calendar-today me-2"></i>
                                Today's Attendance
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-outline-success w-100" onclick="generateQuickReport('this_week')">
                                <i class="mdi mdi-calendar-week me-2"></i>
                                This Week
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-outline-info w-100" onclick="generateQuickReport('this_month')">
                                <i class="mdi mdi-calendar-month me-2"></i>
                                This Month
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-outline-warning w-100" onclick="generateQuickReport('late_arrivals_today')">
                                <i class="mdi mdi-clock-alert me-2"></i>
                                Late Arrivals Today
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Results -->
    <div class="row" id="reportResults" style="display: none;">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-file-chart me-2"></i>
                        <span id="reportTitle">Report Results</span>
                    </h5>
                    <div class="card-header-actions">
                        <div class="btn-group">
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="exportReport('pdf')">
                                <i class="mdi mdi-file-pdf me-1"></i>PDF
                            </button>
                            <button type="button" class="btn btn-outline-success btn-sm" onclick="exportReport('excel')">
                                <i class="mdi mdi-file-excel me-1"></i>Excel
                            </button>
                            <button type="button" class="btn btn-outline-info btn-sm" onclick="exportReport('csv')">
                                <i class="mdi mdi-file-delimited me-1"></i>CSV
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="printReport()">
                                <i class="mdi mdi-printer me-1"></i>Print
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Report Summary -->
                    <div id="reportSummary" class="row mb-4" style="display: none;">
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="mb-1" id="summaryTotal">0</h4>
                                <p class="text-muted mb-0">Total Records</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="mb-1 text-success" id="summaryPresent">0</h4>
                                <p class="text-muted mb-0">Present</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="mb-1 text-danger" id="summaryAbsent">0</h4>
                                <p class="text-muted mb-0">Absent</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="mb-1 text-warning" id="summaryLate">0</h4>
                                <p class="text-muted mb-0">Late Arrivals</p>
                            </div>
                        </div>
                    </div>

                    <!-- Report Chart -->
                    <div id="reportChart" class="mb-4" style="display: none;">
                        <canvas id="reportChartCanvas" height="300"></canvas>
                    </div>

                    <!-- Report Table -->
                    <div id="reportTable" class="table-responsive">
                        <table class="table table-striped table-hover" id="reportDataTable">
                            <thead id="reportTableHead">
                                <!-- Dynamic headers will be inserted here -->
                            </thead>
                            <tbody id="reportTableBody">
                                <!-- Dynamic data will be inserted here -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div id="reportPagination" class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                            <span id="paginationInfo">Showing 0 to 0 of 0 entries</span>
                        </div>
                        <nav>
                            <ul class="pagination pagination-sm mb-0" id="paginationLinks">
                                <!-- Pagination links will be inserted here -->
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Saved Reports -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-content-save me-2"></i>
                        Saved Reports
                    </h5>
                    <div class="card-header-actions">
                        <button type="button" class="btn btn-primary btn-sm" onclick="saveCurrentReport()">
                            <i class="mdi mdi-content-save me-1"></i>Save Current Report
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Report Name</th>
                                    <th>Type</th>
                                    <th>Date Range</th>
                                    <th>Created</th>
                                    <th>Created By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($savedReports as $report)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="mdi mdi-file-chart me-2 text-primary"></i>
                                            <div>
                                                <h6 class="mb-0">{{ $report['name'] }}</h6>
                                                @if($report['description'])
                                                    <small class="text-muted">{{ $report['description'] }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $report['type'])) }}</span>
                                    </td>
                                    <td>{{ $report['date_range'] }}</td>
                                    <td>{{ $report['created_at'] }}</td>
                                    <td>{{ $report['created_by'] }}</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary" 
                                                    onclick="loadSavedReport({{ $report['id'] }})" 
                                                    title="Load Report">
                                                <i class="mdi mdi-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-success" 
                                                    onclick="downloadSavedReport({{ $report['id'] }})" 
                                                    title="Download">
                                                <i class="mdi mdi-download"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-warning" 
                                                    onclick="editSavedReport({{ $report['id'] }})" 
                                                    title="Edit">
                                                <i class="mdi mdi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger" 
                                                    onclick="deleteSavedReport({{ $report['id'] }})" 
                                                    title="Delete">
                                                <i class="mdi mdi-delete"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scheduled Reports -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-calendar-clock me-2"></i>
                        Scheduled Reports
                    </h5>
                    <div class="card-header-actions">
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#scheduleReportModal">
                            <i class="mdi mdi-plus me-1"></i>Schedule Report
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Report Name</th>
                                    <th>Type</th>
                                    <th>Schedule</th>
                                    <th>Recipients</th>
                                    <th>Next Run</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($scheduledReports as $schedule)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="mdi mdi-calendar-clock me-2 text-info"></i>
                                            <div>
                                                <h6 class="mb-0">{{ $schedule['name'] }}</h6>
                                                <small class="text-muted">{{ $schedule['description'] }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $schedule['type'])) }}</span>
                                    </td>
                                    <td>{{ $schedule['schedule'] }}</td>
                                    <td>
                                        <span class="badge bg-primary">{{ $schedule['recipients_count'] }} recipients</span>
                                    </td>
                                    <td>{{ $schedule['next_run'] }}</td>
                                    <td>
                                        <span class="badge bg-{{ $schedule['status'] == 'active' ? 'success' : 'warning' }}">
                                            {{ ucfirst($schedule['status']) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary" 
                                                    onclick="runScheduledReport({{ $schedule['id'] }})" 
                                                    title="Run Now">
                                                <i class="mdi mdi-play"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-warning" 
                                                    onclick="editScheduledReport({{ $schedule['id'] }})" 
                                                    title="Edit">
                                                <i class="mdi mdi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-{{ $schedule['status'] == 'active' ? 'secondary' : 'success' }}" 
                                                    onclick="toggleScheduledReport({{ $schedule['id'] }})" 
                                                    title="{{ $schedule['status'] == 'active' ? 'Pause' : 'Resume' }}">
                                                <i class="mdi mdi-{{ $schedule['status'] == 'active' ? 'pause' : 'play' }}"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger" 
                                                    onclick="deleteScheduledReport({{ $schedule['id'] }})" 
                                                    title="Delete">
                                                <i class="mdi mdi-delete"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Save Report Modal -->
<div class="modal fade" id="saveReportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Save Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="saveReportForm">
                    <div class="mb-3">
                        <label for="report_name" class="form-label">Report Name</label>
                        <input type="text" class="form-control" id="report_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="report_description" class="form-label">Description</label>
                        <textarea class="form-control" id="report_description" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="make_public" name="make_public">
                            <label class="form-check-label" for="make_public">
                                Make this report available to other users
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="processSaveReport()">
                    <i class="mdi mdi-content-save me-1"></i>Save Report
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Schedule Report Modal -->
<div class="modal fade" id="scheduleReportModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Schedule Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="scheduleReportForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="schedule_name" class="form-label">Schedule Name</label>
                                <input type="text" class="form-control" id="schedule_name" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="schedule_report_type" class="form-label">Report Type</label>
                                <select class="form-select" id="schedule_report_type" name="report_type" required>
                                    <option value="">Select Report Type</option>
                                    <option value="daily">Daily Attendance</option>
                                    <option value="monthly">Monthly Summary</option>
                                    <option value="employee">Employee Performance</option>
                                    <option value="department">Department Analysis</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="schedule_frequency" class="form-label">Frequency</label>
                                <select class="form-select" id="schedule_frequency" name="frequency" required>
                                    <option value="">Select Frequency</option>
                                    <option value="daily">Daily</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly">Monthly</option>
                                    <option value="quarterly">Quarterly</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="schedule_time" class="form-label">Time</label>
                                <input type="time" class="form-control" id="schedule_time" name="time" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="schedule_recipients" class="form-label">Recipients</label>
                        <textarea class="form-control" id="schedule_recipients" name="recipients" 
                                  placeholder="Enter email addresses separated by commas" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="schedule_description" class="form-label">Description</label>
                        <textarea class="form-control" id="schedule_description" name="description" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="schedule_active" name="active" checked>
                            <label class="form-check-label" for="schedule_active">
                                Activate schedule immediately
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="processScheduleReport()">
                    <i class="mdi mdi-calendar-plus me-1"></i>Schedule Report
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let currentReportData = null;
let reportChart = null;

$(document).ready(function() {
    // Initialize date range picker
    $('#date_range').daterangepicker({
        locale: {
            format: 'YYYY-MM-DD'
        }
    });
    
    // Form submission
    $('#reportFilters').on('submit', function(e) {
        e.preventDefault();
        generateReport();
    });
    
    // Report type change
    $('#report_type').on('change', function() {
        const reportType = $(this).val();
        toggleCustomFields(reportType);
    });
});

function generateReport() {
    const formData = $('#reportFilters').serialize();
    
    // Show loading
    $('#reportResults').show();
    $('#reportTitle').text('Generating Report...');
    $('#reportTableBody').html('<tr><td colspan="100%" class="text-center"><div class="spinner-border" role="status"></div></td></tr>');
    
    $.ajax({
        url: '{{ route("biometric.reports.generate") }}',
        method: 'GET',
        data: formData,
        success: function(response) {
            if (response.success) {
                currentReportData = response.data;
                displayReport(response.data);
            } else {
                toastr.error(response.message || 'Failed to generate report');
            }
        },
        error: function(xhr) {
            console.error('Failed to generate report:', xhr);
            toastr.error('Failed to generate report');
            $('#reportResults').hide();
        }
    });
}

function generateQuickReport(type) {
    let reportType, dateRange;
    
    switch(type) {
        case 'today':
            reportType = 'daily';
            dateRange = moment().format('YYYY-MM-DD') + ' - ' + moment().format('YYYY-MM-DD');
            break;
        case 'this_week':
            reportType = 'daily';
            dateRange = moment().startOf('week').format('YYYY-MM-DD') + ' - ' + moment().endOf('week').format('YYYY-MM-DD');
            break;
        case 'this_month':
            reportType = 'monthly';
            dateRange = moment().startOf('month').format('YYYY-MM-DD') + ' - ' + moment().endOf('month').format('YYYY-MM-DD');
            break;
        case 'late_arrivals_today':
            reportType = 'late_arrivals';
            dateRange = moment().format('YYYY-MM-DD') + ' - ' + moment().format('YYYY-MM-DD');
            break;
    }
    
    $('#report_type').val(reportType);
    $('#date_range').val(dateRange);
    generateReport();
}

function displayReport(data) {
    $('#reportTitle').text(data.title);
    
    // Update summary
    if (data.summary) {
        $('#reportSummary').show();
        $('#summaryTotal').text(data.summary.total || 0);
        $('#summaryPresent').text(data.summary.present || 0);
        $('#summaryAbsent').text(data.summary.absent || 0);
        $('#summaryLate').text(data.summary.late || 0);
    } else {
        $('#reportSummary').hide();
    }
    
    // Update chart
    if (data.chart) {
        $('#reportChart').show();
        updateReportChart(data.chart);
    } else {
        $('#reportChart').hide();
    }
    
    // Update table
    updateReportTable(data.table);
    
    // Update pagination
    updatePagination(data.pagination);
}

function updateReportChart(chartData) {
    const ctx = document.getElementById('reportChartCanvas').getContext('2d');
    
    if (reportChart) {
        reportChart.destroy();
    }
    
    reportChart = new Chart(ctx, {
        type: chartData.type || 'bar',
        data: {
            labels: chartData.labels || [],
            datasets: chartData.datasets || []
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top'
                },
                title: {
                    display: true,
                    text: chartData.title || 'Report Chart'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

function updateReportTable(tableData) {
    // Update headers
    const thead = $('#reportTableHead');
    thead.empty();
    
    if (tableData.headers && tableData.headers.length > 0) {
        const headerRow = $('<tr></tr>');
        tableData.headers.forEach(function(header) {
            headerRow.append(`<th>${header}</th>`);
        });
        thead.append(headerRow);
    }
    
    // Update body
    const tbody = $('#reportTableBody');
    tbody.empty();
    
    if (tableData.rows && tableData.rows.length > 0) {
        tableData.rows.forEach(function(row) {
            const tableRow = $('<tr></tr>');
            row.forEach(function(cell) {
                tableRow.append(`<td>${cell}</td>`);
            });
            tbody.append(tableRow);
        });
    } else {
        tbody.append('<tr><td colspan="100%" class="text-center text-muted">No data available</td></tr>');
    }
}

function updatePagination(paginationData) {
    if (!paginationData) {
        $('#reportPagination').hide();
        return;
    }
    
    $('#reportPagination').show();
    $('#paginationInfo').text(`Showing ${paginationData.from} to ${paginationData.to} of ${paginationData.total} entries`);
    
    const paginationLinks = $('#paginationLinks');
    paginationLinks.empty();
    
    // Previous button
    if (paginationData.current_page > 1) {
        paginationLinks.append(`
            <li class="page-item">
                <a class="page-link" href="#" onclick="changePage(${paginationData.current_page - 1})">Previous</a>
            </li>
        `);
    }
    
    // Page numbers
    for (let i = 1; i <= paginationData.last_page; i++) {
        const activeClass = i === paginationData.current_page ? 'active' : '';
        paginationLinks.append(`
            <li class="page-item ${activeClass}">
                <a class="page-link" href="#" onclick="changePage(${i})">${i}</a>
            </li>
        `);
    }
    
    // Next button
    if (paginationData.current_page < paginationData.last_page) {
        paginationLinks.append(`
            <li class="page-item">
                <a class="page-link" href="#" onclick="changePage(${paginationData.current_page + 1})">Next</a>
            </li>
        `);
    }
}

function changePage(page) {
    const formData = $('#reportFilters').serialize() + '&page=' + page;
    
    $.ajax({
        url: '{{ route("biometric.reports.generate") }}',
        method: 'GET',
        data: formData,
        success: function(response) {
            if (response.success) {
                currentReportData = response.data;
                displayReport(response.data);
            }
        }
    });
}

function exportReport(format) {
    if (!currentReportData) {
        toastr.error('No report data to export');
        return;
    }
    
    const formData = $('#reportFilters').serialize();
    const exportUrl = '{{ route("biometric.reports.export") }}?' + formData + '&format=' + format;
    
    // Create temporary link and trigger download
    const link = document.createElement('a');
    link.href = exportUrl;
    link.download = '';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    toastr.success('Export started. Download will begin shortly.');
}

function printReport() {
    if (!currentReportData) {
        toastr.error('No report data to print');
        return;
    }
    
    const printWindow = window.open('', '_blank');
    const printContent = generatePrintContent(currentReportData);
    
    printWindow.document.write(printContent);
    printWindow.document.close();
    printWindow.print();
}

function generatePrintContent(data) {
    return `
        <!DOCTYPE html>
        <html>
        <head>
            <title>${data.title}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
                .summary { display: flex; justify-content: space-around; margin: 20px 0; }
                .summary-item { text-align: center; }
                .summary-item h3 { margin: 0; }
                .summary-item p { margin: 5px 0 0 0; color: #666; }
            </style>
        </head>
        <body>
            <h1>${data.title}</h1>
            <p>Generated on: ${new Date().toLocaleString()}</p>
            
            ${data.summary ? `
                <div class="summary">
                    <div class="summary-item">
                        <h3>${data.summary.total || 0}</h3>
                        <p>Total Records</p>
                    </div>
                    <div class="summary-item">
                        <h3>${data.summary.present || 0}</h3>
                        <p>Present</p>
                    </div>
                    <div class="summary-item">
                        <h3>${data.summary.absent || 0}</h3>
                        <p>Absent</p>
                    </div>
                    <div class="summary-item">
                        <h3>${data.summary.late || 0}</h3>
                        <p>Late Arrivals</p>
                    </div>
                </div>
            ` : ''}
            
            <table>
                <thead>
                    <tr>
                        ${data.table.headers.map(header => `<th>${header}</th>`).join('')}
                    </tr>
                </thead>
                <tbody>
                    ${data.table.rows.map(row => `
                        <tr>
                            ${row.map(cell => `<td>${cell}</td>`).join('')}
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </body>
        </html>
    `;
}

function saveCurrentReport() {
    if (!currentReportData) {
        toastr.error('No report data to save');
        return;
    }
    
    $('#saveReportModal').modal('show');
}

function processSaveReport() {
    const formData = new FormData($('#saveReportForm')[0]);
    formData.append('report_data', JSON.stringify(currentReportData));
    formData.append('filters', $('#reportFilters').serialize());
    
    $.ajax({
        url: '{{ route("biometric.reports.save") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                toastr.success('Report saved successfully');
                $('#saveReportModal').modal('hide');
                location.reload(); // Refresh to show new saved report
            } else {
                toastr.error(response.message || 'Failed to save report');
            }
        },
        error: function(xhr) {
            console.error('Failed to save report:', xhr);
            toastr.error('Failed to save report');
        }
    });
}

function processScheduleReport() {
    const formData = new FormData($('#scheduleReportForm')[0]);
    
    $.ajax({
        url: '{{ route("biometric.reports.schedule") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                toastr.success('Report scheduled successfully');
                $('#scheduleReportModal').modal('hide');
                location.reload(); // Refresh to show new scheduled report
            } else {
                toastr.error(response.message || 'Failed to schedule report');
            }
        },
        error: function(xhr) {
            console.error('Failed to schedule report:', xhr);
            toastr.error('Failed to schedule report');
        }
    });
}

function loadSavedReport(reportId) {
    $.ajax({
        url: '{{ route("biometric.reports.load") }}',
        method: 'GET',
        data: { id: reportId },
        success: function(response) {
            if (response.success) {
                currentReportData = response.data;
                displayReport(response.data);
                
                // Update filters
                if (response.filters) {
                    Object.keys(response.filters).forEach(function(key) {
                        $(`#${key}`).val(response.filters[key]);
                    });
                }
            } else {
                toastr.error(response.message || 'Failed to load report');
            }
        },
        error: function(xhr) {
            console.error('Failed to load report:', xhr);
            toastr.error('Failed to load report');
        }
    });
}

function deleteSavedReport(reportId) {
    if (confirm('Are you sure you want to delete this saved report?')) {
        $.ajax({
            url: '{{ route("biometric.reports.delete") }}',
            method: 'DELETE',
            data: { id: reportId },
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success('Report deleted successfully');
                    location.reload();
                } else {
                    toastr.error(response.message || 'Failed to delete report');
                }
            },
            error: function(xhr) {
                console.error('Failed to delete report:', xhr);
                toastr.error('Failed to delete report');
            }
        });
    }
}

function runScheduledReport(scheduleId) {
    $.ajax({
        url: '{{ route("biometric.reports.run-scheduled") }}',
        method: 'POST',
        data: { id: scheduleId },
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                toastr.success('Scheduled report executed successfully');
            } else {
                toastr.error(response.message || 'Failed to run scheduled report');
            }
        },
        error: function(xhr) {
            console.error('Failed to run scheduled report:', xhr);
            toastr.error('Failed to run scheduled report');
        }
    });
}

function toggleScheduledReport(scheduleId) {
    $.ajax({
        url: '{{ route("biometric.reports.toggle-schedule") }}',
        method: 'POST',
        data: { id: scheduleId },
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                toastr.success('Schedule status updated successfully');
                location.reload();
            } else {
                toastr.error(response.message || 'Failed to update schedule status');
            }
        },
        error: function(xhr) {
            console.error('Failed to update schedule status:', xhr);
            toastr.error('Failed to update schedule status');
        }
    });
}

function deleteScheduledReport(scheduleId) {
    if (confirm('Are you sure you want to delete this scheduled report?')) {
        $.ajax({
            url: '{{ route("biometric.reports.delete-schedule") }}',
            method: 'DELETE',
            data: { id: scheduleId },
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success('Scheduled report deleted successfully');
                    location.reload();
                } else {
                    toastr.error(response.message || 'Failed to delete scheduled report');
                }
            },
            error: function(xhr) {
                console.error('Failed to delete scheduled report:', xhr);
                toastr.error('Failed to delete scheduled report');
            }
        });
    }
}

function toggleCustomFields(reportType) {
    // Show/hide custom fields based on report type
    // This can be expanded based on specific requirements
}
</script>
@endpush