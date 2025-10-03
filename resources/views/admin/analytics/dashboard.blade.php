@extends('layouts.app')

@section('title', 'Admin Analytics Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-gradient-primary text-white">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <h2 class="mb-1">Analytics Dashboard</h2>
                            <p class="mb-0">Digital Learning Management Portal - Comprehensive Analytics & Insights</p>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-3x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="text-primary mb-2">
                        <i class="fas fa-tasks fa-2x"></i>
                    </div>
                    <h3 class="mb-1" id="total-assignments">0</h3>
                    <p class="text-muted mb-0">Total Assignments</p>
                    <small class="text-success">
                        <i class="fas fa-arrow-up"></i>
                        <span id="assignments-growth">0%</span> this month
                    </small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="text-info mb-2">
                        <i class="fas fa-file-alt fa-2x"></i>
                    </div>
                    <h3 class="mb-1" id="total-syllabi">0</h3>
                    <p class="text-muted mb-0">Total Syllabi</p>
                    <small class="text-success">
                        <i class="fas fa-arrow-up"></i>
                        <span id="syllabi-growth">0%</span> this month
                    </small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="text-success mb-2">
                        <i class="fas fa-paper-plane fa-2x"></i>
                    </div>
                    <h3 class="mb-1" id="total-submissions">0</h3>
                    <p class="text-muted mb-0">Total Submissions</p>
                    <small class="text-warning">
                        <i class="fas fa-clock"></i>
                        <span id="pending-grading">0</span> pending grading
                    </small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="text-warning mb-2">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                    <h3 class="mb-1" id="active-students">0</h3>
                    <p class="text-muted mb-0">Active Students</p>
                    <small class="text-info">
                        <i class="fas fa-graduation-cap"></i>
                        <span id="engagement-rate">0%</span> engagement rate
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white">
                    <ul class="nav nav-tabs card-header-tabs" id="analyticsTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">
                                <i class="fas fa-tachometer-alt me-2"></i>Overview
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="assignments-tab" data-bs-toggle="tab" data-bs-target="#assignments" type="button" role="tab">
                                <i class="fas fa-tasks me-2"></i>Assignments
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="syllabi-tab" data-bs-toggle="tab" data-bs-target="#syllabi" type="button" role="tab">
                                <i class="fas fa-file-alt me-2"></i>Syllabi
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="performance-tab" data-bs-toggle="tab" data-bs-target="#performance" type="button" role="tab">
                                <i class="fas fa-chart-bar me-2"></i>Performance
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="reports-tab" data-bs-toggle="tab" data-bs-target="#reports" type="button" role="tab">
                                <i class="fas fa-file-export me-2"></i>Reports
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="analyticsTabContent">
                        <!-- Overview Tab -->
                        <div class="tab-pane fade show active" id="overview" role="tabpanel">
                            <div class="row">
                                <div class="col-lg-8 mb-4">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h5 class="mb-0">
                                                <i class="fas fa-chart-line text-primary me-2"></i>
                                                Activity Trends (Last 12 Months)
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <canvas id="activityTrendsChart" height="300"></canvas>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4 mb-4">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h5 class="mb-0">
                                                <i class="fas fa-pie-chart text-success me-2"></i>
                                                Content Distribution
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <canvas id="contentDistributionChart" height="300"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-6 mb-4">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h5 class="mb-0">
                                                <i class="fas fa-users text-info me-2"></i>
                                                Top Performing Classes
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <div id="topClassesChart"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6 mb-4">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h5 class="mb-0">
                                                <i class="fas fa-book text-warning me-2"></i>
                                                Subject Engagement
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <div id="subjectEngagementChart"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Assignments Tab -->
                        <div class="tab-pane fade" id="assignments" role="tabpanel">
                            <div class="row">
                                <div class="col-lg-6 mb-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0">Assignment Status Distribution</h5>
                                        </div>
                                        <div class="card-body">
                                            <canvas id="assignmentStatusChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6 mb-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0">Submission Rate by Subject</h5>
                                        </div>
                                        <div class="card-body">
                                            <canvas id="submissionRateChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0">Assignment Calendar View</h5>
                                        </div>
                                        <div class="card-body">
                                            <div id="assignmentCalendar"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Syllabi Tab -->
                        <div class="tab-pane fade" id="syllabi" role="tabpanel">
                            <div class="row">
                                <div class="col-lg-8 mb-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0">Syllabus Usage Analytics</h5>
                                        </div>
                                        <div class="card-body">
                                            <canvas id="syllabusUsageChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4 mb-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0">File Type Distribution</h5>
                                        </div>
                                        <div class="card-body">
                                            <canvas id="fileTypeChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Performance Tab -->
                        <div class="tab-pane fade" id="performance" role="tabpanel">
                            <div class="row">
                                <div class="col-lg-6 mb-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0">Student Performance Trends</h5>
                                        </div>
                                        <div class="card-body">
                                            <canvas id="performanceTrendsChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6 mb-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0">Teacher Grading Efficiency</h5>
                                        </div>
                                        <div class="card-body">
                                            <canvas id="gradingEfficiencyChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Reports Tab -->
                        <div class="tab-pane fade" id="reports" role="tabpanel">
                            <div class="row">
                                <div class="col-lg-4 mb-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0">Generate Reports</h5>
                                        </div>
                                        <div class="card-body">
                                            <form id="reportForm">
                                                <div class="mb-3">
                                                    <label class="form-label">Report Type</label>
                                                    <select class="form-select" name="report_type" required>
                                                        <option value="">Select Report Type</option>
                                                        <option value="assignments">Assignment Analytics</option>
                                                        <option value="syllabi">Syllabus Analytics</option>
                                                        <option value="student_performance">Student Performance</option>
                                                        <option value="teacher_performance">Teacher Performance</option>
                                                        <option value="engagement">Engagement Report</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Date Range</label>
                                                    <div class="row">
                                                        <div class="col-6">
                                                            <input type="date" class="form-control" name="start_date" required>
                                                        </div>
                                                        <div class="col-6">
                                                            <input type="date" class="form-control" name="end_date" required>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Format</label>
                                                    <select class="form-select" name="format" required>
                                                        <option value="pdf">PDF</option>
                                                        <option value="excel">Excel</option>
                                                        <option value="csv">CSV</option>
                                                    </select>
                                                </div>
                                                <button type="submit" class="btn btn-primary w-100">
                                                    <i class="fas fa-download me-2"></i>Generate Report
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-8 mb-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0">Recent Reports</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th>Report Name</th>
                                                            <th>Type</th>
                                                            <th>Generated</th>
                                                            <th>Size</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="recentReportsTable">
                                                        <!-- Reports will be loaded here -->
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Real-time Updates Modal -->
<div class="modal fade" id="realTimeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Real-time Analytics</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body text-center">
                                <h3 id="liveSubmissions">0</h3>
                                <p class="text-muted">Submissions Today</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body text-center">
                                <h3 id="liveViews">0</h3>
                                <p class="text-muted">Syllabus Views Today</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.card {
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
}

.nav-tabs .nav-link {
    border: none;
    color: #6c757d;
    font-weight: 500;
}

.nav-tabs .nav-link.active {
    background-color: #007bff;
    color: white;
    border-radius: 0.375rem;
}

.stats-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 15px;
    padding: 20px;
    color: white;
    text-align: center;
    margin-bottom: 20px;
}

.chart-container {
    position: relative;
    height: 300px;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize dashboard
    initializeDashboard();
    
    // Load initial data
    loadDashboardStats();
    
    // Initialize charts
    initializeCharts();
    
    // Set up real-time updates
    setInterval(loadDashboardStats, 30000); // Update every 30 seconds
});

function initializeDashboard() {
    // Tab switching
    const tabTriggerList = document.querySelectorAll('#analyticsTab button');
    tabTriggerList.forEach(tabTrigger => {
        tabTrigger.addEventListener('click', function() {
            const target = this.getAttribute('data-bs-target');
            loadTabContent(target);
        });
    });
    
    // Report form submission
    document.getElementById('reportForm').addEventListener('submit', function(e) {
        e.preventDefault();
        generateReport(new FormData(this));
    });
}

function loadDashboardStats() {
    fetch('/learning/api/dashboard-stats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateStatsCards(data.data);
            }
        })
        .catch(error => console.error('Error loading stats:', error));
}

function updateStatsCards(stats) {
    document.getElementById('total-assignments').textContent = stats.total_assignments || 0;
    document.getElementById('total-syllabi').textContent = stats.total_syllabi || 0;
    document.getElementById('total-submissions').textContent = stats.total_submissions || 0;
    document.getElementById('active-students').textContent = stats.active_students || 0;
    document.getElementById('pending-grading').textContent = stats.pending_grading || 0;
    
    // Calculate engagement rate
    const engagementRate = stats.total_assignments > 0 ? 
        Math.round((stats.total_submissions / stats.total_assignments) * 100) : 0;
    document.getElementById('engagement-rate').textContent = engagementRate;
}

function initializeCharts() {
    // Activity Trends Chart
    const activityCtx = document.getElementById('activityTrendsChart').getContext('2d');
    new Chart(activityCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Assignments',
                data: [12, 19, 15, 25, 22, 30, 28, 35, 32, 40, 38, 45],
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                tension: 0.4
            }, {
                label: 'Submissions',
                data: [8, 15, 12, 20, 18, 25, 23, 30, 28, 35, 33, 40],
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // Content Distribution Chart
    const contentCtx = document.getElementById('contentDistributionChart').getContext('2d');
    new Chart(contentCtx, {
        type: 'doughnut',
        data: {
            labels: ['Assignments', 'Syllabi', 'Submissions', 'Grades'],
            datasets: [{
                data: [35, 25, 30, 10],
                backgroundColor: ['#007bff', '#28a745', '#ffc107', '#dc3545']
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
}

function loadTabContent(target) {
    // Load specific content based on tab
    switch(target) {
        case '#assignments':
            loadAssignmentAnalytics();
            break;
        case '#syllabi':
            loadSyllabusAnalytics();
            break;
        case '#performance':
            loadPerformanceAnalytics();
            break;
        case '#reports':
            loadRecentReports();
            break;
    }
}

function loadAssignmentAnalytics() {
    // Load assignment-specific charts and data
    fetch('/learning/admin/analytics/assignments-data')
        .then(response => response.json())
        .then(data => {
            // Update assignment charts
            updateAssignmentCharts(data);
        })
        .catch(error => console.error('Error loading assignment analytics:', error));
}

function loadSyllabusAnalytics() {
    // Load syllabus-specific charts and data
    fetch('/learning/admin/analytics/syllabus-data')
        .then(response => response.json())
        .then(data => {
            // Update syllabus charts
            updateSyllabusCharts(data);
        })
        .catch(error => console.error('Error loading syllabus analytics:', error));
}

function loadPerformanceAnalytics() {
    // Load performance-specific charts and data
    fetch('/learning/admin/analytics/performance-data')
        .then(response => response.json())
        .then(data => {
            // Update performance charts
            updatePerformanceCharts(data);
        })
        .catch(error => console.error('Error loading performance analytics:', error));
}

function generateReport(formData) {
    const submitBtn = document.querySelector('#reportForm button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Generating...';
    
    fetch('/learning/admin/export/generate-report', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.blob())
    .then(blob => {
        // Create download link
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.style.display = 'none';
        a.href = url;
        a.download = 'analytics-report.' + formData.get('format');
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        
        // Reset button
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-download me-2"></i>Generate Report';
        
        // Show success message
        showAlert('Report generated successfully!', 'success');
    })
    .catch(error => {
        console.error('Error generating report:', error);
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-download me-2"></i>Generate Report';
        showAlert('Error generating report. Please try again.', 'error');
    });
}

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.container-fluid');
    container.insertBefore(alertDiv, container.firstChild);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}
</script>
@endpush