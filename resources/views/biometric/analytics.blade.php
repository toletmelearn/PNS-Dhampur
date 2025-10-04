@extends('layouts.app')

@section('title', 'Biometric Analytics')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Biometric Analytics</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('biometric.index') }}">Biometric</a></li>
                        <li class="breadcrumb-item active">Analytics</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Analytics Filters -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form id="analyticsFilters" class="row g-3">
                        <div class="col-md-2">
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
                            <label for="employee_type" class="form-label">Employee Type</label>
                            <select class="form-select" id="employee_type" name="employee_type">
                                <option value="">All Types</option>
                                <option value="teacher" {{ request('employee_type') == 'teacher' ? 'selected' : '' }}>Teachers</option>
                                <option value="staff" {{ request('employee_type') == 'staff' ? 'selected' : '' }}>Staff</option>
                                <option value="admin" {{ request('employee_type') == 'admin' ? 'selected' : '' }}>Admin</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="device" class="form-label">Device</label>
                            <select class="form-select" id="device" name="device">
                                <option value="">All Devices</option>
                                @foreach($devices as $device)
                                    <option value="{{ $device->id }}" {{ request('device') == $device->id ? 'selected' : '' }}>
                                        {{ $device->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="view_type" class="form-label">View Type</label>
                            <select class="form-select" id="view_type" name="view_type">
                                <option value="daily" {{ request('view_type', 'daily') == 'daily' ? 'selected' : '' }}>Daily</option>
                                <option value="weekly" {{ request('view_type') == 'weekly' ? 'selected' : '' }}>Weekly</option>
                                <option value="monthly" {{ request('view_type') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="mdi mdi-filter me-1"></i>Apply Filters
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm">
                                <span class="avatar-title bg-primary rounded-circle">
                                    <i class="mdi mdi-account-check"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-1" id="totalAttendance">{{ $metrics['total_attendance'] ?? 0 }}</h5>
                            <p class="text-muted mb-0">Total Attendance</p>
                            <small class="text-success">
                                <i class="mdi mdi-arrow-up"></i>
                                {{ $metrics['attendance_change'] ?? 0 }}% from last period
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm">
                                <span class="avatar-title bg-success rounded-circle">
                                    <i class="mdi mdi-clock-check"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-1" id="avgAttendanceRate">{{ $metrics['avg_attendance_rate'] ?? 0 }}%</h5>
                            <p class="text-muted mb-0">Avg Attendance Rate</p>
                            <small class="text-{{ ($metrics['rate_change'] ?? 0) >= 0 ? 'success' : 'danger' }}">
                                <i class="mdi mdi-arrow-{{ ($metrics['rate_change'] ?? 0) >= 0 ? 'up' : 'down' }}"></i>
                                {{ abs($metrics['rate_change'] ?? 0) }}% from last period
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm">
                                <span class="avatar-title bg-warning rounded-circle">
                                    <i class="mdi mdi-clock-alert"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-1" id="lateArrivals">{{ $metrics['late_arrivals'] ?? 0 }}</h5>
                            <p class="text-muted mb-0">Late Arrivals</p>
                            <small class="text-warning">
                                {{ $metrics['late_percentage'] ?? 0 }}% of total attendance
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm">
                                <span class="avatar-title bg-danger rounded-circle">
                                    <i class="mdi mdi-account-remove"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-1" id="absentees">{{ $metrics['absentees'] ?? 0 }}</h5>
                            <p class="text-muted mb-0">Absentees</p>
                            <small class="text-danger">
                                {{ $metrics['absent_percentage'] ?? 0 }}% of total employees
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row">
        <!-- Attendance Trend Chart -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-chart-line me-2"></i>
                        Attendance Trend
                    </h5>
                    <div class="card-header-actions">
                        <div class="btn-group btn-group-sm" role="group">
                            <input type="radio" class="btn-check" name="trendType" id="trendDaily" value="daily" checked>
                            <label class="btn btn-outline-primary" for="trendDaily">Daily</label>
                            
                            <input type="radio" class="btn-check" name="trendType" id="trendWeekly" value="weekly">
                            <label class="btn btn-outline-primary" for="trendWeekly">Weekly</label>
                            
                            <input type="radio" class="btn-check" name="trendType" id="trendMonthly" value="monthly">
                            <label class="btn btn-outline-primary" for="trendMonthly">Monthly</label>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="attendanceTrendChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Department Comparison -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-chart-donut me-2"></i>
                        Department Comparison
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="departmentChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Analytics -->
    <div class="row">
        <!-- Time-based Analysis -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-clock-outline me-2"></i>
                        Time-based Analysis
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="timeAnalysisChart" height="250"></canvas>
                </div>
            </div>
        </div>

        <!-- Device Usage -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-devices me-2"></i>
                        Device Usage Statistics
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Device</th>
                                    <th>Total Scans</th>
                                    <th>Success Rate</th>
                                    <th>Avg Response</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="deviceStatsTable">
                                @foreach($deviceStats as $stat)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-xs me-2">
                                                <span class="avatar-title bg-{{ $stat['status'] == 'online' ? 'success' : 'danger' }} rounded-circle">
                                                    <i class="mdi mdi-{{ $stat['status'] == 'online' ? 'check' : 'close' }}"></i>
                                                </span>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">{{ $stat['name'] }}</h6>
                                                <small class="text-muted">{{ $stat['location'] }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ number_format($stat['total_scans']) }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1 me-2" style="height: 6px;">
                                                <div class="progress-bar bg-{{ $stat['success_rate'] >= 90 ? 'success' : ($stat['success_rate'] >= 70 ? 'warning' : 'danger') }}" 
                                                     style="width: {{ $stat['success_rate'] }}%"></div>
                                            </div>
                                            <small>{{ $stat['success_rate'] }}%</small>
                                        </div>
                                    </td>
                                    <td>{{ $stat['avg_response'] }}ms</td>
                                    <td>
                                        <span class="badge bg-{{ $stat['status'] == 'online' ? 'success' : 'danger' }}">
                                            {{ ucfirst($stat['status']) }}
                                        </span>
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

    <!-- Attendance Patterns -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-calendar-clock me-2"></i>
                        Attendance Patterns & Insights
                    </h5>
                    <div class="card-header-actions">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="exportAnalytics()">
                            <i class="mdi mdi-download me-1"></i>Export Report
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Weekly Pattern -->
                        <div class="col-lg-6">
                            <h6 class="mb-3">Weekly Attendance Pattern</h6>
                            <canvas id="weeklyPatternChart" height="200"></canvas>
                        </div>

                        <!-- Hourly Pattern -->
                        <div class="col-lg-6">
                            <h6 class="mb-3">Hourly Check-in Pattern</h6>
                            <canvas id="hourlyPatternChart" height="200"></canvas>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Key Insights -->
                    <div class="row">
                        <div class="col-lg-8">
                            <h6 class="mb-3">Key Insights</h6>
                            <div id="insightsList">
                                @foreach($insights as $insight)
                                <div class="alert alert-{{ $insight['type'] }} alert-dismissible fade show" role="alert">
                                    <i class="mdi mdi-{{ $insight['icon'] }} me-2"></i>
                                    <strong>{{ $insight['title'] }}:</strong> {{ $insight['message'] }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Quick Stats -->
                        <div class="col-lg-4">
                            <h6 class="mb-3">Quick Statistics</h6>
                            <div class="list-group list-group-flush">
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    Peak Attendance Day
                                    <span class="badge bg-primary">{{ $quickStats['peak_day'] ?? 'N/A' }}</span>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    Most Active Hour
                                    <span class="badge bg-success">{{ $quickStats['peak_hour'] ?? 'N/A' }}</span>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    Avg Daily Attendance
                                    <span class="badge bg-info">{{ $quickStats['avg_daily'] ?? 0 }}</span>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    Punctuality Rate
                                    <span class="badge bg-warning">{{ $quickStats['punctuality_rate'] ?? 0 }}%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Performers & Concerns -->
    <div class="row">
        <!-- Top Performers -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-trophy me-2"></i>
                        Top Performers
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Department</th>
                                    <th>Attendance Rate</th>
                                    <th>Punctuality</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topPerformers as $performer)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-xs me-2">
                                                <img src="{{ $performer['avatar'] ?? asset('assets/images/users/default.jpg') }}" 
                                                     class="avatar-img rounded-circle" alt="">
                                            </div>
                                            <div>
                                                <h6 class="mb-0">{{ $performer['name'] }}</h6>
                                                <small class="text-muted">{{ $performer['employee_id'] }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $performer['department'] }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1 me-2" style="height: 6px;">
                                                <div class="progress-bar bg-success" style="width: {{ $performer['attendance_rate'] }}%"></div>
                                            </div>
                                            <small>{{ $performer['attendance_rate'] }}%</small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-success">{{ $performer['punctuality'] }}%</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance Concerns -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-alert-circle me-2"></i>
                        Attendance Concerns
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Department</th>
                                    <th>Attendance Rate</th>
                                    <th>Issues</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($attendanceConcerns as $concern)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-xs me-2">
                                                <img src="{{ $concern['avatar'] ?? asset('assets/images/users/default.jpg') }}" 
                                                     class="avatar-img rounded-circle" alt="">
                                            </div>
                                            <div>
                                                <h6 class="mb-0">{{ $concern['name'] }}</h6>
                                                <small class="text-muted">{{ $concern['employee_id'] }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $concern['department'] }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1 me-2" style="height: 6px;">
                                                <div class="progress-bar bg-danger" style="width: {{ $concern['attendance_rate'] }}%"></div>
                                            </div>
                                            <small>{{ $concern['attendance_rate'] }}%</small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-danger">{{ $concern['issues'] }}</span>
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

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Export Analytics Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="exportForm">
                    <div class="mb-3">
                        <label for="export_format" class="form-label">Export Format</label>
                        <select class="form-select" id="export_format" name="format" required>
                            <option value="pdf">PDF Report</option>
                            <option value="excel">Excel Spreadsheet</option>
                            <option value="csv">CSV Data</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="export_sections" class="form-label">Include Sections</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="include_summary" name="sections[]" value="summary" checked>
                            <label class="form-check-label" for="include_summary">Summary Statistics</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="include_trends" name="sections[]" value="trends" checked>
                            <label class="form-check-label" for="include_trends">Attendance Trends</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="include_departments" name="sections[]" value="departments" checked>
                            <label class="form-check-label" for="include_departments">Department Analysis</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="include_patterns" name="sections[]" value="patterns">
                            <label class="form-check-label" for="include_patterns">Attendance Patterns</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="include_performers" name="sections[]" value="performers">
                            <label class="form-check-label" for="include_performers">Top Performers & Concerns</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="processExport()">
                    <i class="mdi mdi-download me-1"></i>Export Report
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let attendanceTrendChart, departmentChart, timeAnalysisChart, weeklyPatternChart, hourlyPatternChart;

$(document).ready(function() {
    // Initialize date range picker
    $('#date_range').daterangepicker({
        locale: {
            format: 'YYYY-MM-DD'
        }
    });
    
    // Initialize charts
    initializeCharts();
    
    // Form submission
    $('#analyticsFilters').on('submit', function(e) {
        e.preventDefault();
        refreshAnalytics();
    });
    
    // Trend type change
    $('input[name="trendType"]').on('change', function() {
        updateTrendChart($(this).val());
    });
    
    // Auto-refresh every 5 minutes
    setInterval(function() {
        refreshAnalytics();
    }, 300000);
});

function initializeCharts() {
    // Attendance Trend Chart
    const trendCtx = document.getElementById('attendanceTrendChart').getContext('2d');
    attendanceTrendChart = new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: @json($trendData['labels'] ?? []),
            datasets: [{
                label: 'Present',
                data: @json($trendData['present'] ?? []),
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                tension: 0.4
            }, {
                label: 'Absent',
                data: @json($trendData['absent'] ?? []),
                borderColor: '#dc3545',
                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                tension: 0.4
            }, {
                label: 'Late',
                data: @json($trendData['late'] ?? []),
                borderColor: '#ffc107',
                backgroundColor: 'rgba(255, 193, 7, 0.1)',
                tension: 0.4
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

    // Department Chart
    const deptCtx = document.getElementById('departmentChart').getContext('2d');
    departmentChart = new Chart(deptCtx, {
        type: 'doughnut',
        data: {
            labels: @json($departmentData['labels'] ?? []),
            datasets: [{
                data: @json($departmentData['data'] ?? []),
                backgroundColor: [
                    '#007bff', '#28a745', '#ffc107', '#dc3545', 
                    '#6f42c1', '#fd7e14', '#20c997', '#6c757d'
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

    // Time Analysis Chart
    const timeCtx = document.getElementById('timeAnalysisChart').getContext('2d');
    timeAnalysisChart = new Chart(timeCtx, {
        type: 'bar',
        data: {
            labels: @json($timeData['labels'] ?? []),
            datasets: [{
                label: 'Check-ins',
                data: @json($timeData['checkins'] ?? []),
                backgroundColor: '#007bff'
            }, {
                label: 'Check-outs',
                data: @json($timeData['checkouts'] ?? []),
                backgroundColor: '#28a745'
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

    // Weekly Pattern Chart
    const weeklyCtx = document.getElementById('weeklyPatternChart').getContext('2d');
    weeklyPatternChart = new Chart(weeklyCtx, {
        type: 'radar',
        data: {
            labels: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
            datasets: [{
                label: 'Attendance Rate',
                data: @json($weeklyPattern ?? []),
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                pointBackgroundColor: '#007bff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                r: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });

    // Hourly Pattern Chart
    const hourlyCtx = document.getElementById('hourlyPatternChart').getContext('2d');
    hourlyPatternChart = new Chart(hourlyCtx, {
        type: 'line',
        data: {
            labels: @json($hourlyPattern['labels'] ?? []),
            datasets: [{
                label: 'Check-ins',
                data: @json($hourlyPattern['data'] ?? []),
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                fill: true,
                tension: 0.4
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
}

function refreshAnalytics() {
    const formData = $('#analyticsFilters').serialize();
    
    $.ajax({
        url: '{{ route("biometric.analytics.data") }}',
        method: 'GET',
        data: formData,
        success: function(response) {
            if (response.success) {
                updateMetrics(response.metrics);
                updateCharts(response.chartData);
                updateInsights(response.insights);
                updateTables(response.tables);
            }
        },
        error: function(xhr) {
            console.error('Failed to refresh analytics:', xhr);
            toastr.error('Failed to refresh analytics data');
        }
    });
}

function updateMetrics(metrics) {
    $('#totalAttendance').text(metrics.total_attendance || 0);
    $('#avgAttendanceRate').text((metrics.avg_attendance_rate || 0) + '%');
    $('#lateArrivals').text(metrics.late_arrivals || 0);
    $('#absentees').text(metrics.absentees || 0);
}

function updateCharts(chartData) {
    // Update trend chart
    if (chartData.trend) {
        attendanceTrendChart.data.labels = chartData.trend.labels;
        attendanceTrendChart.data.datasets[0].data = chartData.trend.present;
        attendanceTrendChart.data.datasets[1].data = chartData.trend.absent;
        attendanceTrendChart.data.datasets[2].data = chartData.trend.late;
        attendanceTrendChart.update();
    }
    
    // Update department chart
    if (chartData.department) {
        departmentChart.data.labels = chartData.department.labels;
        departmentChart.data.datasets[0].data = chartData.department.data;
        departmentChart.update();
    }
    
    // Update other charts similarly...
}

function updateTrendChart(type) {
    $.ajax({
        url: '{{ route("biometric.analytics.trend") }}',
        method: 'GET',
        data: {
            type: type,
            ...Object.fromEntries(new FormData($('#analyticsFilters')[0]))
        },
        success: function(response) {
            if (response.success) {
                attendanceTrendChart.data.labels = response.data.labels;
                attendanceTrendChart.data.datasets[0].data = response.data.present;
                attendanceTrendChart.data.datasets[1].data = response.data.absent;
                attendanceTrendChart.data.datasets[2].data = response.data.late;
                attendanceTrendChart.update();
            }
        }
    });
}

function updateInsights(insights) {
    const insightsList = $('#insightsList');
    insightsList.empty();
    
    insights.forEach(function(insight) {
        const alertHtml = `
            <div class="alert alert-${insight.type} alert-dismissible fade show" role="alert">
                <i class="mdi mdi-${insight.icon} me-2"></i>
                <strong>${insight.title}:</strong> ${insight.message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        insightsList.append(alertHtml);
    });
}

function updateTables(tables) {
    // Update device stats table
    if (tables.deviceStats) {
        const tbody = $('#deviceStatsTable');
        tbody.empty();
        
        tables.deviceStats.forEach(function(stat) {
            const row = `
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="avatar-xs me-2">
                                <span class="avatar-title bg-${stat.status === 'online' ? 'success' : 'danger'} rounded-circle">
                                    <i class="mdi mdi-${stat.status === 'online' ? 'check' : 'close'}"></i>
                                </span>
                            </div>
                            <div>
                                <h6 class="mb-0">${stat.name}</h6>
                                <small class="text-muted">${stat.location}</small>
                            </div>
                        </div>
                    </td>
                    <td>${stat.total_scans.toLocaleString()}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="progress flex-grow-1 me-2" style="height: 6px;">
                                <div class="progress-bar bg-${stat.success_rate >= 90 ? 'success' : (stat.success_rate >= 70 ? 'warning' : 'danger')}" 
                                     style="width: ${stat.success_rate}%"></div>
                            </div>
                            <small>${stat.success_rate}%</small>
                        </div>
                    </td>
                    <td>${stat.avg_response}ms</td>
                    <td>
                        <span class="badge bg-${stat.status === 'online' ? 'success' : 'danger'}">
                            ${stat.status.charAt(0).toUpperCase() + stat.status.slice(1)}
                        </span>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    }
}

function exportAnalytics() {
    $('#exportModal').modal('show');
}

function processExport() {
    const format = $('#export_format').val();
    const sections = $('input[name="sections[]"]:checked').map(function() {
        return $(this).val();
    }).get();
    
    const formData = $('#analyticsFilters').serialize();
    const exportData = {
        format: format,
        sections: sections,
        filters: Object.fromEntries(new URLSearchParams(formData))
    };
    
    // Create form and submit
    const form = $('<form>', {
        method: 'POST',
        action: '{{ route("biometric.analytics.export") }}'
    });
    
    form.append($('<input>', {
        type: 'hidden',
        name: '_token',
        value: '{{ csrf_token() }}'
    }));
    
    Object.keys(exportData).forEach(function(key) {
        if (Array.isArray(exportData[key])) {
            exportData[key].forEach(function(value) {
                form.append($('<input>', {
                    type: 'hidden',
                    name: key + '[]',
                    value: value
                }));
            });
        } else if (typeof exportData[key] === 'object') {
            Object.keys(exportData[key]).forEach(function(subKey) {
                form.append($('<input>', {
                    type: 'hidden',
                    name: key + '[' + subKey + ']',
                    value: exportData[key][subKey]
                }));
            });
        } else {
            form.append($('<input>', {
                type: 'hidden',
                name: key,
                value: exportData[key]
            }));
        }
    });
    
    $('body').append(form);
    form.submit();
    form.remove();
    
    $('#exportModal').modal('hide');
    toastr.success('Export started. Download will begin shortly.');
}
</script>
@endpush