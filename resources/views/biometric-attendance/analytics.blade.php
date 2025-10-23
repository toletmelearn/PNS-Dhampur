@extends('layouts.app')

@section('title', 'Biometric Attendance Analytics')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Biometric Attendance Analytics</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard.redirect') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('biometric-attendance.index') }}">Biometric Attendance</a></li>
                        <li class="breadcrumb-item active">Analytics</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Controls -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form id="analyticsFilterForm" class="row g-3">
                        <div class="col-md-3">
                            <label for="monthFilter" class="form-label">Month</label>
                            <input type="month" class="form-control" id="monthFilter" name="month" value="{{ date('Y-m') }}">
                        </div>
                        <div class="col-md-3">
                            <label for="teacherFilter" class="form-label">Teacher</label>
                            <select class="form-select" id="teacherFilter" name="teacher_id">
                                <option value="">All Teachers</option>
                                <!-- Teachers will be loaded via AJAX -->
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="departmentFilter" class="form-label">Department</label>
                            <select class="form-select" id="departmentFilter" name="department">
                                <option value="">All Departments</option>
                                <option value="Primary">Primary</option>
                                <option value="Secondary">Secondary</option>
                                <option value="Higher Secondary">Higher Secondary</option>
                                <option value="Administration">Administration</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search"></i> Apply Filters
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="exportBtn">
                                <i class="fas fa-download"></i> Export
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Dashboard Summary Cards -->
    <div class="row mb-4" id="summaryCards">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Average Attendance</p>
                        </div>
                        <div class="flex-shrink-0">
                            <h5 class="text-success fs-14 mb-0">
                                <i class="ri-percent-line align-middle"></i>
                            </h5>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-2">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-4" id="avgAttendance">--</h4>
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
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Punctuality Score</p>
                        </div>
                        <div class="flex-shrink-0">
                            <h5 class="text-info fs-14 mb-0">
                                <i class="ri-time-line align-middle"></i>
                            </h5>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-2">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-4" id="punctualityScore">--</h4>
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
                            <h4 class="fs-22 fw-semibold ff-secondary mb-4" id="lateArrivals">--</h4>
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
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Working Hours</p>
                        </div>
                        <div class="flex-shrink-0">
                            <h5 class="text-primary fs-14 mb-0">
                                <i class="ri-time-fill align-middle"></i>
                            </h5>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-2">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-4" id="avgWorkingHours">--</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Attendance Trends</h5>
                </div>
                <div class="card-body">
                    <canvas id="attendanceTrendChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Attendance Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="attendanceDistributionChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Metrics and Leave Patterns -->
    <div class="row mb-4">
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Top Performers</h5>
                </div>
                <div class="card-body">
                    <div id="topPerformersTable">
                        <!-- Top performers will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Leave Pattern Analysis</h5>
                </div>
                <div class="card-body">
                    <canvas id="leavePatternChart" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Analytics Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Detailed Analytics</h5>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-primary btn-sm" id="calculateAnalyticsBtn">
                            <i class="fas fa-calculator"></i> Calculate Analytics
                        </button>
                        <button type="button" class="btn btn-outline-success btn-sm" id="refreshDataBtn">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="analyticsTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>Teacher</th>
                                    <th>Employee ID</th>
                                    <th>Department</th>
                                    <th>Present Days</th>
                                    <th>Absent Days</th>
                                    <th>Attendance %</th>
                                    <th>Punctuality Score</th>
                                    <th>Late Arrivals</th>
                                    <th>Early Departures</th>
                                    <th>Avg Working Hours</th>
                                    <th>Performance Grade</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="analyticsTableBody">
                                <!-- Data will be loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Detailed View Modal -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailModalLabel">Teacher Analytics Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="detailModalBody">
                <!-- Detailed analytics will be loaded here -->
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
let attendanceTrendChart, attendanceDistributionChart, leavePatternChart;

$(document).ready(function() {
    // Initialize charts
    initializeCharts();
    
    // Load initial data
    loadAnalyticsData();
    
    // Load teachers for filter
    loadTeachers();
    
    // Event handlers
    $('#analyticsFilterForm').on('submit', function(e) {
        e.preventDefault();
        loadAnalyticsData();
    });
    
    $('#calculateAnalyticsBtn').on('click', function() {
        calculateMonthlyAnalytics();
    });
    
    $('#refreshDataBtn').on('click', function() {
        loadAnalyticsData();
    });
    
    $('#exportBtn').on('click', function() {
        exportAnalyticsData();
    });
});

function initializeCharts() {
    // Attendance Trend Chart
    const trendCtx = document.getElementById('attendanceTrendChart').getContext('2d');
    attendanceTrendChart = new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Attendance %',
                data: [],
                borderColor: 'rgb(37, 99, 235)',
                backgroundColor: 'rgba(37, 99, 235, 0.1)',
                tension: 0.4
            }, {
                label: 'Punctuality Score',
                data: [],
                borderColor: 'rgb(16, 185, 129)',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
    
    // Attendance Distribution Chart
    const distCtx = document.getElementById('attendanceDistributionChart').getContext('2d');
    attendanceDistributionChart = new Chart(distCtx, {
        type: 'doughnut',
        data: {
            labels: ['Present', 'Absent', 'Late', 'Early Departure'],
            datasets: [{
                data: [0, 0, 0, 0],
                backgroundColor: [
                    'rgb(16, 185, 129)',
                    'rgb(239, 68, 68)',
                    'rgb(245, 158, 11)',
                    'rgb(168, 85, 247)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
    
    // Leave Pattern Chart
    const leaveCtx = document.getElementById('leavePatternChart').getContext('2d');
    leavePatternChart = new Chart(leaveCtx, {
        type: 'bar',
        data: {
            labels: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
            datasets: [{
                label: 'Absences',
                data: [0, 0, 0, 0, 0, 0],
                backgroundColor: 'rgba(239, 68, 68, 0.8)'
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

function loadAnalyticsData() {
    const formData = new FormData($('#analyticsFilterForm')[0]);
    const params = new URLSearchParams(formData);
    
    $.ajax({
        url: '/biometric-attendance/analytics-dashboard?' + params.toString(),
        method: 'GET',
        success: function(response) {
            if (response.success) {
                updateDashboard(response.data);
            } else {
                showAlert('Error loading analytics data', 'error');
            }
        },
        error: function() {
            showAlert('Failed to load analytics data', 'error');
        }
    });
}

function updateDashboard(data) {
    // Update summary cards
    if (data.dashboard_summary) {
        $('#avgAttendance').text(data.dashboard_summary.avg_attendance + '%');
        $('#punctualityScore').text(data.dashboard_summary.avg_punctuality_score);
        $('#lateArrivals').text(data.dashboard_summary.total_late_arrivals);
        $('#avgWorkingHours').text(data.dashboard_summary.avg_working_hours + 'h');
    }
    
    // Update charts
    updateCharts(data);
    
    // Update top performers table
    updateTopPerformers(data.top_performers);
    
    // Update analytics table
    updateAnalyticsTable(data.analytics);
}

function updateCharts(data) {
    // Update trend chart
    if (data.analytics && data.analytics.length > 0) {
        const labels = data.analytics.map(item => item.teacher.name);
        const attendanceData = data.analytics.map(item => item.attendance_percentage);
        const punctualityData = data.analytics.map(item => item.punctuality_score);
        
        attendanceTrendChart.data.labels = labels.slice(0, 10); // Show top 10
        attendanceTrendChart.data.datasets[0].data = attendanceData.slice(0, 10);
        attendanceTrendChart.data.datasets[1].data = punctualityData.slice(0, 10);
        attendanceTrendChart.update();
    }
    
    // Update distribution chart
    if (data.dashboard_summary) {
        const summary = data.dashboard_summary;
        attendanceDistributionChart.data.datasets[0].data = [
            summary.total_present || 0,
            summary.total_absent || 0,
            summary.total_late_arrivals || 0,
            summary.total_early_departures || 0
        ];
        attendanceDistributionChart.update();
    }
    
    // Update leave pattern chart
    if (data.leave_patterns && data.leave_patterns.by_day_of_week) {
        const dayData = data.leave_patterns.by_day_of_week;
        leavePatternChart.data.datasets[0].data = [
            dayData.Monday || 0,
            dayData.Tuesday || 0,
            dayData.Wednesday || 0,
            dayData.Thursday || 0,
            dayData.Friday || 0,
            dayData.Saturday || 0
        ];
        leavePatternChart.update();
    }
}

function updateTopPerformers(performers) {
    let html = '<div class="list-group">';
    
    if (performers && performers.length > 0) {
        performers.slice(0, 5).forEach((performer, index) => {
            const badgeClass = index === 0 ? 'bg-warning' : index === 1 ? 'bg-secondary' : index === 2 ? 'bg-info' : 'bg-light text-dark';
            html += `
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge ${badgeClass} me-2">${index + 1}</span>
                        <strong>${performer.teacher.name}</strong>
                        <br>
                        <small class="text-muted">${performer.teacher.employee_id} - ${performer.teacher.department}</small>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold text-success">${performer.attendance_percentage}%</div>
                        <small class="text-muted">Score: ${performer.punctuality_score}</small>
                    </div>
                </div>
            `;
        });
    } else {
        html += '<div class="text-center text-muted py-3">No data available</div>';
    }
    
    html += '</div>';
    $('#topPerformersTable').html(html);
}

function updateAnalyticsTable(analytics) {
    let html = '';
    
    if (analytics && analytics.length > 0) {
        analytics.forEach(item => {
            const gradeClass = getGradeClass(item.attendance_grade);
            html += `
                <tr>
                    <td>${item.teacher.name}</td>
                    <td>${item.teacher.employee_id}</td>
                    <td>${item.teacher.department}</td>
                    <td>${item.present_days}</td>
                    <td>${item.absent_days}</td>
                    <td>
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar ${getProgressBarClass(item.attendance_percentage)}" 
                                 style="width: ${item.attendance_percentage}%">
                                ${item.attendance_percentage}%
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge bg-info">${item.punctuality_score}</span>
                    </td>
                    <td>${item.late_arrivals}</td>
                    <td>${item.early_departures}</td>
                    <td>${item.average_working_hours}h</td>
                    <td>
                        <span class="badge ${gradeClass}">${item.attendance_grade}</span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="viewDetails(${item.teacher_id})">
                            <i class="fas fa-eye"></i> View
                        </button>
                    </td>
                </tr>
            `;
        });
    } else {
        html = '<tr><td colspan="12" class="text-center text-muted">No analytics data available</td></tr>';
    }
    
    $('#analyticsTableBody').html(html);
}

function getProgressBarClass(percentage) {
    if (percentage >= 90) return 'bg-success';
    if (percentage >= 75) return 'bg-info';
    if (percentage >= 60) return 'bg-warning';
    return 'bg-danger';
}

function getGradeClass(grade) {
    switch(grade) {
        case 'A+': case 'A': return 'bg-success';
        case 'B+': case 'B': return 'bg-info';
        case 'C+': case 'C': return 'bg-warning';
        default: return 'bg-danger';
    }
}

function loadTeachers() {
    $.ajax({
        url: '/api/teachers',
        method: 'GET',
        success: function(response) {
            let options = '<option value="">All Teachers</option>';
            if (response.data) {
                response.data.forEach(teacher => {
                    options += `<option value="${teacher.id}">${teacher.name} (${teacher.employee_id})</option>`;
                });
            }
            $('#teacherFilter').html(options);
        }
    });
}

function calculateMonthlyAnalytics() {
    const month = $('#monthFilter').val();
    
    $.ajax({
        url: '/biometric-attendance/calculate-analytics',
        method: 'POST',
        data: {
            month: month,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                showAlert('Analytics calculated successfully', 'success');
                loadAnalyticsData();
            } else {
                showAlert('Failed to calculate analytics', 'error');
            }
        },
        error: function() {
            showAlert('Failed to calculate analytics', 'error');
        }
    });
}

function viewDetails(teacherId) {
    const month = $('#monthFilter').val();
    
    $.ajax({
        url: `/biometric-attendance/detailed-report?teacher_id=${teacherId}&month=${month}`,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                showDetailModal(response.data);
            }
        }
    });
}

function showDetailModal(data) {
    // Implementation for detailed modal content
    $('#detailModal').modal('show');
}

function exportAnalyticsData() {
    const formData = new FormData($('#analyticsFilterForm')[0]);
    const params = new URLSearchParams(formData);
    
    window.open('/biometric-attendance/export-analytics?' + params.toString(), '_blank');
}

function showAlert(message, type) {
    // Implementation for showing alerts
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const alert = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // Prepend to container
    $('.container-fluid').prepend(alert);
    
    // Auto dismiss after 5 seconds
    setTimeout(() => {
        $('.alert').alert('close');
    }, 5000);
}
</script>
@endsection