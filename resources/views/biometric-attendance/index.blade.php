@extends('layouts.app')

@section('title', 'Biometric Attendance')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Biometric Attendance</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard.redirect') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Biometric Attendance</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('biometric-attendance.analytics') }}" class="btn btn-primary">
                            <i class="fas fa-chart-line"></i> Analytics Dashboard
                        </a>
                        <a href="{{ route('biometric-attendance.regularization') }}" class="btn btn-info">
                            <i class="fas fa-edit"></i> Regularization Requests
                        </a>
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#bulkCheckInModal">
                            <i class="fas fa-users"></i> Bulk Check-In
                        </button>
                        <button type="button" class="btn btn-warning" id="importCsvBtn">
                            <i class="fas fa-upload"></i> Import CSV Data
                        </button>
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-download"></i> Export Reports
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="exportReport('daily')">Daily Report</a></li>
                                <li><a class="dropdown-item" href="#" onclick="exportReport('monthly')">Monthly Report</a></li>
                                <li><a class="dropdown-item" href="#" onclick="exportReport('detailed')">Detailed Analytics</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Total Teachers</p>
                        </div>
                        <div class="flex-shrink-0">
                            <h5 class="text-success fs-14 mb-0">
                                <i class="ri-user-3-line align-middle"></i>
                            </h5>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-2">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-4" id="totalTeachers">{{ $summary['total_teachers'] ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Present Today</p>
                        </div>
                        <div class="flex-shrink-0">
                            <h5 class="text-success fs-14 mb-0">
                                <i class="ri-checkbox-circle-line align-middle"></i>
                            </h5>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-2">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-4" id="presentToday">{{ $summary['present_today'] ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Absent Today</p>
                        </div>
                        <div class="flex-shrink-0">
                            <h5 class="text-danger fs-14 mb-0">
                                <i class="ri-close-circle-line align-middle"></i>
                            </h5>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-2">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-4" id="absentToday">{{ $summary['absent_today'] ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Attendance %</p>
                        </div>
                        <div class="flex-shrink-0">
                            <h5 class="text-info fs-14 mb-0">
                                <i class="ri-percent-line align-middle"></i>
                            </h5>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-2">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-4" id="attendancePercentage">{{ $summary['attendance_percentage'] ?? 0 }}%</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Metrics Row -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Late Arrivals</p>
                        </div>
                        <div class="flex-shrink-0">
                            <h5 class="text-warning fs-14 mb-0">
                                <i class="ri-alarm-warning-line align-middle"></i>
                            </h5>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-2">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-4" id="lateArrivals">{{ $summary['late_arrivals'] ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Early Departures</p>
                        </div>
                        <div class="flex-shrink-0">
                            <h5 class="text-orange fs-14 mb-0">
                                <i class="ri-logout-circle-line align-middle"></i>
                            </h5>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-2">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-4" id="earlyDepartures">{{ $summary['early_departures'] ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Pending Requests</p>
                        </div>
                        <div class="flex-shrink-0">
                            <h5 class="text-primary fs-14 mb-0">
                                <i class="ri-file-list-3-line align-middle"></i>
                            </h5>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-2">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-4" id="pendingRequests">{{ $summary['pending_requests'] ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Avg Working Hours</p>
                        </div>
                        <div class="flex-shrink-0">
                            <h5 class="text-purple fs-14 mb-0">
                                <i class="ri-time-fill align-middle"></i>
                            </h5>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-2">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-4" id="avgWorkingHours">{{ $summary['avg_working_hours'] ?? 0 }}h</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Actions -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <h5 class="card-title mb-0">Attendance Records</h5>
                        </div>
                        <div class="col-auto">
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#bulkCheckInModal">
                                    <i class="ri-login-box-line align-middle me-1"></i> Bulk Check-in
                                </button>
                                <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#markAbsentModal">
                                    <i class="ri-user-unfollow-line align-middle me-1"></i> Mark Absent
                                </button>
                                <button type="button" class="btn btn-success" onclick="exportReport()">
                                    <i class="ri-download-line align-middle me-1"></i> Export
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filter Form -->
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label for="date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="date" name="date" value="{{ $date }}">
                        </div>
                        <div class="col-md-3">
                            <label for="teacher_id" class="form-label">Teacher</label>
                            <select class="form-select" id="teacher_id" name="teacher_id">
                                <option value="">All Teachers</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher->id }}" {{ request('teacher_id') == $teacher->id ? 'selected' : '' }}>
                                        {{ $teacher->name }} ({{ $teacher->employee_id }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="ri-search-line align-middle me-1"></i> Filter
                            </button>
                            <a href="{{ route('biometric-attendance.index') }}" class="btn btn-secondary">
                                <i class="ri-refresh-line align-middle me-1"></i> Reset
                            </a>
                        </div>
                    </form>

                    <!-- Attendance Table -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>Teacher</th>
                                    <th>Employee ID</th>
                                    <th>Date</th>
                                    <th>Check-in Time</th>
                                    <th>Check-out Time</th>
                                    <th>Working Hours</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($attendances as $attendance)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-xs me-2">
                                                    <div class="avatar-title rounded-circle bg-soft-primary text-primary">
                                                        {{ substr($attendance->teacher->name, 0, 1) }}
                                                    </div>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0">{{ $attendance->teacher->name }}</h6>
                                                    <small class="text-muted">{{ $attendance->teacher->department ?? 'N/A' }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $attendance->teacher->employee_id }}</td>
                                        <td>{{ $attendance->date->format('d M Y') }}</td>
                                        <td>
                                            @if($attendance->check_in_time)
                                                <span class="badge bg-{{ $attendance->is_late ? 'warning' : 'success' }}">
                                                    {{ $attendance->check_in_time->format('H:i:s') }}
                                                    @if($attendance->is_late)
                                                        <i class="ri-time-line ms-1"></i>
                                                    @endif
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($attendance->check_out_time)
                                                <span class="badge bg-{{ $attendance->is_early_departure ? 'info' : 'success' }}">
                                                    {{ $attendance->check_out_time->format('H:i:s') }}
                                                    @if($attendance->is_early_departure)
                                                        <i class="ri-arrow-left-line ms-1"></i>
                                                    @endif
                                                </span>
                                            @else
                                                @if($attendance->status === 'present' && $attendance->check_in_time)
                                                    <button class="btn btn-sm btn-outline-primary" onclick="checkOut({{ $attendance->teacher->id }})">
                                                        Check Out
                                                    </button>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            @endif
                                        </td>
                                        <td>
                                            @if($attendance->working_hours)
                                                {{ number_format($attendance->working_hours, 2) }}h
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($attendance->status === 'present')
                                                <span class="badge bg-success">Present</span>
                                            @elseif($attendance->status === 'absent')
                                                <span class="badge bg-danger">Absent</span>
                                            @else
                                                <span class="badge bg-secondary">Unknown</span>
                                            @endif
                                            
                                            @if($attendance->is_late)
                                                <span class="badge bg-warning ms-1">Late</span>
                                            @endif
                                            
                                            @if($attendance->is_early_departure)
                                                <span class="badge bg-info ms-1">Early</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                    Actions
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="#" onclick="viewDetails({{ $attendance->id }})">
                                                        <i class="ri-eye-line me-2"></i>View Details
                                                    </a></li>
                                                    @if($attendance->status === 'absent')
                                                        <li><a class="dropdown-item" href="#" onclick="markPresent({{ $attendance->teacher->id }})">
                                                            <i class="ri-checkbox-circle-line me-2"></i>Mark Present
                                                        </a></li>
                                                    @endif
                                                    @if(!$attendance->check_in_time && $attendance->status !== 'absent')
                                                        <li><a class="dropdown-item" href="#" onclick="checkIn({{ $attendance->teacher->id }})">
                                                            <i class="ri-login-box-line me-2"></i>Check In
                                                        </a></li>
                                                    @endif
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="ri-file-list-line fs-1 mb-3 d-block"></i>
                                                No attendance records found for the selected date.
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Real-time Attendance Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Today's Attendance</h5>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="refreshAttendance()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="filterAttendance('all')">All</a></li>
                                <li><a class="dropdown-item" href="#" onclick="filterAttendance('present')">Present</a></li>
                                <li><a class="dropdown-item" href="#" onclick="filterAttendance('absent')">Absent</a></li>
                                <li><a class="dropdown-item" href="#" onclick="filterAttendance('late')">Late Arrivals</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="attendanceTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>Teacher ID</th>
                                    <th>Name</th>
                                    <th>Department</th>
                                    <th>Check In</th>
                                    <th>Check Out</th>
                                    <th>Working Hours</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="attendanceTableBody">
                                <!-- Data will be loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Bulk Check-In Modal -->
<div class="modal fade" id="bulkCheckInModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bulk Check-In</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="bulkCheckInForm">
                    <div class="mb-3">
                        <label class="form-label">Select Teachers</label>
                        <select class="form-select" name="teacher_ids[]" multiple id="teacherSelect">
                            <!-- Options will be loaded via AJAX -->
                        </select>
                        <div class="form-text">Hold Ctrl/Cmd to select multiple teachers</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Check-In Time</label>
                        <input type="time" class="form-control" name="check_in_time" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason (Optional)</label>
                        <textarea class="form-control" name="reason" rows="3" placeholder="Reason for bulk check-in..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="processBulkCheckIn()">Process Check-In</button>
            </div>
        </div>
    </div>
</div>

<!-- CSV Import Modal -->
<div class="modal fade" id="csvImportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import CSV Data</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="csvImportForm" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">CSV File</label>
                        <input type="file" class="form-control" name="csv_file" accept=".csv" required>
                        <div class="form-text">
                            CSV should contain: teacher_id, date, check_in_time, check_out_time
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="overwrite_existing" id="overwriteExisting">
                            <label class="form-check-label" for="overwriteExisting">
                                Overwrite existing records
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="processCsvImport()">Import Data</button>
            </div>
        </div>
    </div>
</div>

<!-- Teacher Details Modal -->
<div class="modal fade" id="teacherDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Teacher Attendance Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="teacherDetailsContent">
                <!-- Content will be loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#attendanceTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("biometric-attendance.daily-report") }}',
            type: 'GET'
        },
        columns: [
            { data: 'teacher_id', name: 'teacher_id' },
            { data: 'teacher_name', name: 'teacher.name' },
            { data: 'department', name: 'teacher.department' },
            { data: 'check_in_time', name: 'check_in_time' },
            { data: 'check_out_time', name: 'check_out_time' },
            { data: 'working_hours', name: 'working_hours' },
            { data: 'status', name: 'status' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[1, 'asc']],
        pageLength: 25,
        responsive: true
    });

    // Load teachers for bulk check-in
    loadTeachersForBulkCheckIn();

    // CSV Import button click
    $('#importCsvBtn').click(function() {
        $('#csvImportModal').modal('show');
    });

    // Auto-refresh every 5 minutes
    setInterval(refreshAttendance, 300000);
});

function refreshAttendance() {
    $('#attendanceTable').DataTable().ajax.reload();
    updateSummaryCards();
}

function updateSummaryCards() {
    $.get('{{ route("biometric-attendance.summary") }}', function(data) {
        $('#totalTeachers').text(data.total_teachers);
        $('#presentToday').text(data.present_today);
        $('#absentToday').text(data.absent_today);
        $('#attendancePercentage').text(data.attendance_percentage + '%');
        $('#lateArrivals').text(data.late_arrivals);
        $('#earlyDepartures').text(data.early_departures);
        $('#pendingRequests').text(data.pending_requests);
        $('#avgWorkingHours').text(data.avg_working_hours + 'h');
    });
}

function filterAttendance(status) {
    var table = $('#attendanceTable').DataTable();
    if (status === 'all') {
        table.column(6).search('').draw();
    } else {
        table.column(6).search(status).draw();
    }
}

function loadTeachersForBulkCheckIn() {
    $.get('{{ route("teachers.list") }}', function(data) {
        var select = $('#teacherSelect');
        select.empty();
        $.each(data, function(index, teacher) {
            select.append('<option value="' + teacher.id + '">' + teacher.name + ' (' + teacher.employee_id + ')</option>');
        });
    });
}

function processBulkCheckIn() {
    var formData = new FormData($('#bulkCheckInForm')[0]);
    
    $.ajax({
        url: '{{ route("biometric-attendance.bulk-checkin") }}',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: response.message,
                timer: 2000
            });
            $('#bulkCheckInModal').modal('hide');
            refreshAttendance();
        },
        error: function(xhr) {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: xhr.responseJSON?.message || 'An error occurred'
            });
        }
    });
}

function processCsvImport() {
    var formData = new FormData($('#csvImportForm')[0]);
    
    $.ajax({
        url: '{{ route("biometric-attendance.import-csv") }}',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            Swal.fire({
                icon: 'success',
                title: 'Import Successful!',
                html: `
                    <p>Records processed: ${response.total_processed}</p>
                    <p>Successfully imported: ${response.successful}</p>
                    <p>Errors: ${response.errors}</p>
                `,
                timer: 5000
            });
            $('#csvImportModal').modal('hide');
            refreshAttendance();
        },
        error: function(xhr) {
            Swal.fire({
                icon: 'error',
                title: 'Import Failed!',
                text: xhr.responseJSON?.message || 'An error occurred during import'
            });
        }
    });
}

function exportReport(type) {
    var url = '{{ route("biometric-attendance.export") }}?type=' + type;
    window.open(url, '_blank');
}

function viewTeacherDetails(teacherId) {
    $.get('{{ route("biometric-attendance.teacher-details") }}', { teacher_id: teacherId }, function(data) {
        $('#teacherDetailsContent').html(data);
        $('#teacherDetailsModal').modal('show');
    });
}

function markAbsent(teacherId) {
    Swal.fire({
        title: 'Mark as Absent',
        input: 'textarea',
        inputLabel: 'Reason for absence',
        inputPlaceholder: 'Enter reason...',
        showCancelButton: true,
        confirmButtonText: 'Mark Absent',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '{{ route("biometric-attendance.mark-absent") }}',
                type: 'POST',
                data: {
                    teacher_id: teacherId,
                    reason: result.value,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        timer: 2000
                    });
                    refreshAttendance();
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: xhr.responseJSON?.message || 'An error occurred'
                    });
                }
            });
        }
    });
}

function createRegularizationRequest(teacherId) {
    window.location.href = '{{ route("biometric-attendance.regularization") }}?teacher_id=' + teacherId;
}
</script>
@endsection