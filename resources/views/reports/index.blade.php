@extends('layouts.app')

@section('title', 'Reports & Analytics')

@section('content')
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    .reports-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 15px;
        padding: 20px;
        color: white;
        margin-bottom: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
    }
    
    .reports-card:hover {
        transform: translateY(-5px);
    }
    
    .report-category {
        background: white;
        border-radius: 12px;
        padding: 25px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        margin-bottom: 20px;
        border-top: 4px solid;
        height: 100%;
    }
    
    .report-category:hover {
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        transform: translateY(-3px);
    }
    
    .report-category.academic { border-top-color: #3498db; }
    .report-category.financial { border-top-color: #e74c3c; }
    .report-category.attendance { border-top-color: #2ecc71; }
    .report-category.performance { border-top-color: #f39c12; }
    .report-category.administrative { border-top-color: #9b59b6; }
    .report-category.custom { border-top-color: #1abc9c; }
    
    .report-icon {
        font-size: 3rem;
        margin-bottom: 15px;
        opacity: 0.8;
    }
    
    .report-category.academic .report-icon { color: #3498db; }
    .report-category.financial .report-icon { color: #e74c3c; }
    .report-category.attendance .report-icon { color: #2ecc71; }
    .report-category.performance .report-icon { color: #f39c12; }
    .report-category.administrative .report-icon { color: #9b59b6; }
    .report-category.custom .report-icon { color: #1abc9c; }
    
    .report-title {
        font-size: 1.5rem;
        font-weight: bold;
        margin-bottom: 10px;
        color: #2c3e50;
    }
    
    .report-description {
        color: #7f8c8d;
        margin-bottom: 20px;
        line-height: 1.6;
    }
    
    .report-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .btn-report {
        padding: 8px 16px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    
    .btn-primary { background: #3498db; color: white; }
    .btn-success { background: #2ecc71; color: white; }
    .btn-warning { background: #f39c12; color: white; }
    .btn-secondary { background: #95a5a6; color: white; }
    
    .btn-report:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .analytics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .analytics-card {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        text-align: center;
        transition: transform 0.3s ease;
    }
    
    .analytics-card:hover {
        transform: translateY(-3px);
    }
    
    .analytics-number {
        font-size: 2.5rem;
        font-weight: bold;
        margin-bottom: 5px;
    }
    
    .analytics-label {
        color: #7f8c8d;
        font-size: 0.9rem;
    }
    
    .chart-container {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    
    .chart-title {
        font-size: 1.2rem;
        font-weight: bold;
        margin-bottom: 15px;
        color: #2c3e50;
    }
    
    .filters-section {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    
    .filters-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-top: 15px;
    }
    
    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    
    .filter-group label {
        font-weight: 500;
        color: #2c3e50;
    }
    
    .filter-group select,
    .filter-group input {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 0.9rem;
    }
    
    .quick-reports {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    
    .quick-reports-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-top: 15px;
    }
    
    .quick-report-btn {
        padding: 15px;
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        text-align: center;
    }
    
    .quick-report-btn:hover {
        background: #e9ecef;
        transform: translateY(-2px);
    }
    
    /* Loading overlay styles */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }
    
    .loading-content {
        background: white;
        padding: 30px;
        border-radius: 10px;
        text-align: center;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }
    
    .spinner {
        border: 4px solid #f3f3f3;
        border-top: 4px solid #3498db;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        animation: spin 1s linear infinite;
        margin: 0 auto 15px;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    /* Notification styles */
    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 5px;
        color: white;
        font-weight: bold;
        z-index: 10000;
        transform: translateX(400px);
        transition: transform 0.3s ease;
    }
    
    .notification.show {
        transform: translateX(0);
    }
    
    .notification.success {
        background: #2ecc71;
    }
    
    .notification.error {
        background: #e74c3c;
    }
    
    /* Report modal styles */
    .report-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }
    
    .report-modal .modal-content {
        background: white;
        border-radius: 10px;
        max-width: 90%;
        max-height: 90%;
        overflow-y: auto;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }
    
    .report-modal .modal-header {
        padding: 20px;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .report-modal .modal-body {
        padding: 20px;
        max-height: 60vh;
        overflow-y: auto;
    }
    
    .report-modal .modal-footer {
        padding: 20px;
        border-top: 1px solid #eee;
        text-align: right;
    }
    
    .close-btn {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #999;
    }
    
    .close-btn:hover {
        color: #333;
    }
    
    .stat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin: 20px 0;
    }
    
    .stat-item {
        border: 1px solid #ddd;
        padding: 15px;
        border-radius: 5px;
        background: #f9f9f9;
    }
    
    .checkbox-group {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-top: 10px;
    }
    
    .checkbox-group label {
        display: flex;
        align-items: center;
        gap: 5px;
        cursor: pointer;
    }
</style>

<div class="container-fluid">
    <!-- Header Card -->
    <div class="reports-card">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="mb-2">📊 Reports & Analytics Dashboard</h1>
                <p class="mb-0">Comprehensive insights into your school's performance, finances, and operations</p>
            </div>
            <div class="col-md-4 text-end">
                <button class="btn btn-light" onclick="exportAllData()">
                    <i class="fas fa-download"></i> Export All Data
                </button>
            </div>
        </div>
    </div>

    <!-- Analytics Overview -->
    <div class="analytics-grid">
        <div class="analytics-card">
            <div class="analytics-number text-primary" id="totalStudents">0</div>
            <div class="analytics-label">Total Students</div>
        </div>
        <div class="analytics-card">
            <div class="analytics-number text-success" id="totalTeachers">0</div>
            <div class="analytics-label">Total Teachers</div>
        </div>
        <div class="analytics-card">
            <div class="analytics-number text-warning" id="totalRevenue">₹0</div>
            <div class="analytics-label">Total Revenue</div>
        </div>
        <div class="analytics-card">
            <div class="analytics-number text-info" id="attendanceRate">0%</div>
            <div class="analytics-label">Attendance Rate</div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="filters-section">
        <h4><i class="fas fa-filter"></i> Report Filters</h4>
        <div class="filters-grid">
            <div class="filter-group">
                <label>Date Range</label>
                <select id="dateRange" onchange="applyFilters()">
                    <option value="today">Today</option>
                    <option value="week">This Week</option>
                    <option value="month" selected>This Month</option>
                    <option value="quarter">This Quarter</option>
                    <option value="year">This Year</option>
                    <option value="custom">Custom Range</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Class</label>
                <select id="classFilter" onchange="applyFilters()">
                    <option value="">All Classes</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Subject</label>
                <select id="subjectFilter" onchange="applyFilters()">
                    <option value="">All Subjects</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Report Type</label>
                <select id="reportType" onchange="applyFilters()">
                    <option value="">All Reports</option>
                    <option value="academic">Academic</option>
                    <option value="financial">Financial</option>
                    <option value="attendance">Attendance</option>
                    <option value="performance">Performance</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="row">
        <div class="col-md-6">
            <div class="chart-container">
                <div class="chart-title">Student Enrollment Trends</div>
                <canvas id="enrollmentChart" width="400" height="200"></canvas>
            </div>
        </div>
        <div class="col-md-6">
            <div class="chart-container">
                <div class="chart-title">Financial Overview</div>
                <canvas id="financialChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="chart-container">
                <div class="chart-title">Attendance Trends</div>
                <canvas id="attendanceChart" width="400" height="200"></canvas>
            </div>
        </div>
        <div class="col-md-6">
            <div class="chart-container">
                <div class="chart-title">Performance Distribution</div>
                <canvas id="performanceChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>

    <!-- Quick Reports -->
    <div class="quick-reports">
        <h4><i class="fas fa-bolt"></i> Quick Reports</h4>
        <div class="quick-reports-grid">
            <div class="quick-report-btn" onclick="generateQuickReport('daily-attendance')">
                <i class="fas fa-calendar-day"></i><br>
                Daily Attendance
            </div>
            <div class="quick-report-btn" onclick="generateQuickReport('fee-collection')">
                <i class="fas fa-money-bill-wave"></i><br>
                Fee Collection
            </div>
            <div class="quick-report-btn" onclick="generateQuickReport('exam-results')">
                <i class="fas fa-chart-line"></i><br>
                Exam Results
            </div>
            <div class="quick-report-btn" onclick="generateQuickReport('teacher-performance')">
                <i class="fas fa-chalkboard-teacher"></i><br>
                Teacher Performance
            </div>
        </div>
    </div>

    <!-- Report Categories -->
    <div class="row">
        <!-- Academic Reports -->
        <div class="col-lg-6 col-xl-4 mb-4">
            <div class="report-category academic">
                <div class="report-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="report-title">Academic Reports</div>
                <div class="report-description">
                    Student performance, exam results, grade analysis, and academic progress tracking
                </div>
                <div class="report-actions">
                    <button class="btn-report btn-primary" onclick="generateAcademicReport()">
                        <i class="fas fa-chart-bar"></i> Generate
                    </button>
                    <button class="btn-report btn-success" onclick="viewReport('academic')">
                        <i class="fas fa-eye"></i> View
                    </button>
                    <button class="btn-report btn-warning" onclick="exportReport('academic')">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>
        </div>

        <!-- Financial Reports -->
        <div class="col-lg-6 col-xl-4 mb-4">
            <div class="report-category financial">
                <div class="report-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="report-title">Financial Reports</div>
                <div class="report-description">
                    Fee collection, expenses, revenue analysis, and financial health monitoring
                </div>
                <div class="report-actions">
                    <button class="btn-report btn-primary" onclick="generateFinancialReport()">
                        <i class="fas fa-chart-bar"></i> Generate
                    </button>
                    <button class="btn-report btn-success" onclick="viewReport('financial')">
                        <i class="fas fa-eye"></i> View
                    </button>
                    <button class="btn-report btn-warning" onclick="exportReport('financial')">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>
        </div>

        <!-- Attendance Reports -->
        <div class="col-lg-6 col-xl-4 mb-4">
            <div class="report-category attendance">
                <div class="report-icon">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="report-title">Attendance Reports</div>
                <div class="report-description">
                    Student and teacher attendance patterns, trends, and absenteeism analysis
                </div>
                <div class="report-actions">
                    <button class="btn-report btn-primary" onclick="generateAttendanceReport()">
                        <i class="fas fa-chart-bar"></i> Generate
                    </button>
                    <button class="btn-report btn-success" onclick="viewReport('attendance')">
                        <i class="fas fa-eye"></i> View
                    </button>
                    <button class="btn-report btn-warning" onclick="exportReport('attendance')">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>
        </div>

        <!-- Performance Reports -->
        <div class="col-lg-6 col-xl-4 mb-4">
            <div class="report-category performance">
                <div class="report-icon">
                    <i class="fas fa-trophy"></i>
                </div>
                <div class="report-title">Performance Reports</div>
                <div class="report-description">
                    Individual and class performance metrics, improvement tracking, and benchmarking
                </div>
                <div class="report-actions">
                    <button class="btn-report btn-primary" onclick="generatePerformanceReport()">
                        <i class="fas fa-chart-bar"></i> Generate
                    </button>
                    <button class="btn-report btn-success" onclick="viewReport('performance')">
                        <i class="fas fa-eye"></i> View
                    </button>
                    <button class="btn-report btn-warning" onclick="exportReport('performance')">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>
        </div>

        <!-- Administrative Reports -->
        <div class="col-lg-6 col-xl-4 mb-4">
            <div class="report-category administrative">
                <div class="report-icon">
                    <i class="fas fa-cogs"></i>
                </div>
                <div class="report-title">Administrative Reports</div>
                <div class="report-description">
                    Staff management, resource utilization, operational efficiency, and compliance reports
                </div>
                <div class="report-actions">
                    <button class="btn-report btn-primary" onclick="generateAdministrativeReport()">
                        <i class="fas fa-chart-bar"></i> Generate
                    </button>
                    <button class="btn-report btn-success" onclick="viewReport('administrative')">
                        <i class="fas fa-eye"></i> View
                    </button>
                    <button class="btn-report btn-warning" onclick="exportReport('administrative')">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>
        </div>

        <!-- Custom Reports -->
        <div class="col-lg-6 col-xl-4 mb-4">
            <div class="report-category custom">
                <div class="report-icon">
                    <i class="fas fa-puzzle-piece"></i>
                </div>
                <div class="report-title">Custom Reports</div>
                <div class="report-description">
                    Build personalized reports with custom parameters, filters, and data combinations
                </div>
                <div class="report-actions">
                    <button class="btn-report btn-primary" onclick="openCustomReportBuilder()">
                        <i class="fas fa-plus"></i> Build
                    </button>
                    <button class="btn-report btn-success" onclick="viewReport('custom')">
                        <i class="fas fa-eye"></i> View
                    </button>
                    <button class="btn-report btn-secondary" onclick="scheduleReport()">
                        <i class="fas fa-clock"></i> Schedule
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom Report Builder Modal -->
<div class="modal fade" id="customReportModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Custom Report Builder</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="customReportForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Report Name</label>
                                <input type="text" class="form-control" id="reportName" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Report Type</label>
                                <select class="form-control" id="customReportType" required>
                                    <option value="">Select Type</option>
                                    <option value="academic">Academic</option>
                                    <option value="financial">Financial</option>
                                    <option value="attendance">Attendance</option>
                                    <option value="performance">Performance</option>
                                    <option value="administrative">Administrative</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Data Fields</label>
                        <div class="checkbox-group">
                            <label><input type="checkbox" name="fields[]" value="student_info"> Student Information</label>
                            <label><input type="checkbox" name="fields[]" value="grades"> Grades</label>
                            <label><input type="checkbox" name="fields[]" value="attendance"> Attendance</label>
                            <label><input type="checkbox" name="fields[]" value="fees"> Fee Information</label>
                            <label><input type="checkbox" name="fields[]" value="subjects"> Subject Details</label>
                            <label><input type="checkbox" name="fields[]" value="teachers"> Teacher Information</label>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Date From</label>
                                <input type="date" class="form-control" id="dateFrom">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Date To</label>
                                <input type="date" class="form-control" id="dateTo">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Class Filter</label>
                                <select class="form-control" id="customClassFilter">
                                    <option value="">All Classes</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Format</label>
                                <select class="form-control" id="reportFormat">
                                    <option value="html">HTML</option>
                                    <option value="pdf">PDF</option>
                                    <option value="excel">Excel</option>
                                    <option value="csv">CSV</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="generateCustomReport()">Generate Report</button>
            </div>
        </div>
    </div>
</div>

<script>
// Global variables for charts
let enrollmentChart, financialChart, attendanceChart, performanceChart;
let currentReportData = null;

// Initialize dashboard
document.addEventListener('DOMContentLoaded', function() {
    // Add loading overlay to body
    const loadingOverlay = document.createElement('div');
    loadingOverlay.className = 'loading-overlay';
    loadingOverlay.innerHTML = `
        <div class="loading-content">
            <div class="spinner"></div>
            <p>Loading report data...</p>
        </div>
    `;
    document.body.appendChild(loadingOverlay);
    
    // Add report modal to body
    const reportModal = document.createElement('div');
    reportModal.className = 'report-modal';
    reportModal.id = 'reportModal';
    reportModal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Report</h3>
                <button class="close-btn" onclick="closeReportModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Report content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeReportModal()">Close</button>
                <button class="btn btn-primary" onclick="exportCurrentReport()">Export</button>
            </div>
        </div>
    `;
    document.body.appendChild(reportModal);
    
    // Initialize dashboard
    loadDashboardData();
    initializeCharts();
});

// Load dashboard data
async function loadDashboardData() {
    try {
        showLoading();
        
        // Load academic data
        const academicResponse = await fetch('/api/reports/academic');
        const academicData = await academicResponse.json();
        
        // Load financial data
        const financialResponse = await fetch('/api/reports/financial');
        const financialData = await financialResponse.json();
        
        // Load attendance data
        const attendanceResponse = await fetch('/api/reports/attendance');
        const attendanceData = await attendanceResponse.json();
        
        // Load performance data
        const performanceResponse = await fetch('/api/reports/performance');
        const performanceData = await performanceResponse.json();
        
        // Update analytics cards
        updateAnalyticsCards(academicData, financialData, attendanceData);
        
        // Update charts
        updateCharts(academicData, financialData, attendanceData, performanceData);
        
        hideLoading();
        showNotification('Dashboard data loaded successfully', 'success');
    } catch (error) {
        console.error('Error loading dashboard data:', error);
        hideLoading();
        showNotification('Error loading dashboard data', 'error');
    }
}

// Update analytics cards
function updateAnalyticsCards(academic, financial, attendance) {
    animateCounter('totalStudents', academic.total_students || 0);
    animateCounter('totalTeachers', academic.total_teachers || 0);
    document.getElementById('totalRevenue').textContent = `₹${(financial.total_revenue || 0).toLocaleString()}`;
    document.getElementById('attendanceRate').textContent = `${attendance.overall_attendance || 0}%`;
}

// Animate counter
function animateCounter(elementId, targetValue) {
    const element = document.getElementById(elementId);
    const startValue = 0;
    const duration = 1000;
    const startTime = performance.now();
    
    function updateCounter(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const currentValue = Math.floor(startValue + (targetValue - startValue) * progress);
        
        element.textContent = currentValue.toLocaleString();
        
        if (progress < 1) {
            requestAnimationFrame(updateCounter);
        }
    }
    
    requestAnimationFrame(updateCounter);
}

// Initialize charts
function initializeCharts() {
    // Enrollment Chart
    const enrollmentCtx = document.getElementById('enrollmentChart').getContext('2d');
    enrollmentChart = new Chart(enrollmentCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Student Enrollment',
                data: [],
                borderColor: '#3498db',
                backgroundColor: 'rgba(52, 152, 219, 0.1)',
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
    
    // Financial Chart
    const financialCtx = document.getElementById('financialChart').getContext('2d');
    financialChart = new Chart(financialCtx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Revenue',
                data: [],
                backgroundColor: '#2ecc71'
            }, {
                label: 'Expenses',
                data: [],
                backgroundColor: '#e74c3c'
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
    
    // Attendance Chart
    const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
    attendanceChart = new Chart(attendanceCtx, {
        type: 'doughnut',
        data: {
            labels: ['Present', 'Absent'],
            datasets: [{
                data: [0, 0],
                backgroundColor: ['#2ecc71', '#e74c3c']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
    
    // Performance Chart
    const performanceCtx = document.getElementById('performanceChart').getContext('2d');
    performanceChart = new Chart(performanceCtx, {
        type: 'radar',
        data: {
            labels: [],
            datasets: [{
                label: 'Performance',
                data: [],
                borderColor: '#f39c12',
                backgroundColor: 'rgba(243, 156, 18, 0.2)'
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
}

// Update charts with data
function updateCharts(academic, financial, attendance, performance) {
    // Update enrollment chart
    if (academic.enrollment_trends) {
        enrollmentChart.data.labels = academic.enrollment_trends.map(item => item.month);
        enrollmentChart.data.datasets[0].data = academic.enrollment_trends.map(item => item.count);
        enrollmentChart.update();
    }
    
    // Update financial chart
    if (financial.monthly_data) {
        financialChart.data.labels = financial.monthly_data.map(item => item.month);
        financialChart.data.datasets[0].data = financial.monthly_data.map(item => item.revenue);
        financialChart.data.datasets[1].data = financial.monthly_data.map(item => item.expenses);
        financialChart.update();
    }
    
    // Update attendance chart
    if (attendance.present_today !== undefined && attendance.absent_today !== undefined) {
        attendanceChart.data.datasets[0].data = [attendance.present_today, attendance.absent_today];
        attendanceChart.update();
    }
    
    // Update performance chart
    if (performance.subject_averages) {
        performanceChart.data.labels = performance.subject_averages.map(item => item.subject);
        performanceChart.data.datasets[0].data = performance.subject_averages.map(item => item.average);
        performanceChart.update();
    }
}

// Apply filters
function applyFilters() {
    const dateRange = document.getElementById('dateRange').value;
    const classFilter = document.getElementById('classFilter').value;
    const subjectFilter = document.getElementById('subjectFilter').value;
    const reportType = document.getElementById('reportType').value;
    
    // Reload data with filters
    loadDashboardData();
}

// Change chart period
function changeChartPeriod(period) {
    // Implementation for changing chart time period
    loadDashboardData();
}

// Generate Academic Report
async function generateAcademicReport() {
    try {
        showLoading();
        const response = await fetch('/api/reports/academic');
        const data = await response.json();
        
        if (data.success) {
            currentReportData = { type: 'academic', data: data.data };
            showNotification('Academic report generated successfully', 'success');
        } else {
            showNotification('Error generating academic report', 'error');
        }
        hideLoading();
    } catch (error) {
        console.error('Error generating academic report:', error);
        hideLoading();
        showNotification('Error generating academic report', 'error');
    }
}

// Generate Financial Report
async function generateFinancialReport() {
    try {
        showLoading();
        const response = await fetch('/api/reports/financial');
        const data = await response.json();
        
        if (data.success) {
            currentReportData = { type: 'financial', data: data.data };
            showNotification('Financial report generated successfully', 'success');
        } else {
            showNotification('Error generating financial report', 'error');
        }
        hideLoading();
    } catch (error) {
        console.error('Error generating financial report:', error);
        hideLoading();
        showNotification('Error generating financial report', 'error');
    }
}

// Generate Attendance Report
async function generateAttendanceReport() {
    try {
        showLoading();
        const response = await fetch('/api/reports/attendance');
        const data = await response.json();
        
        if (data.success) {
            currentReportData = { type: 'attendance', data: data.data };
            showNotification('Attendance report generated successfully', 'success');
        } else {
            showNotification('Error generating attendance report', 'error');
        }
        hideLoading();
    } catch (error) {
        console.error('Error generating attendance report:', error);
        hideLoading();
        showNotification('Error generating attendance report', 'error');
    }
}

// Generate Performance Report
async function generatePerformanceReport() {
    try {
        showLoading();
        const response = await fetch('/api/reports/performance');
        const data = await response.json();
        
        if (data.success) {
            currentReportData = { type: 'performance', data: data.data };
            showNotification('Performance report generated successfully', 'success');
        } else {
            showNotification('Error generating performance report', 'error');
        }
        hideLoading();
    } catch (error) {
        console.error('Error generating performance report:', error);
        hideLoading();
        showNotification('Error generating performance report', 'error');
    }
}

// Generate Administrative Report
async function generateAdministrativeReport() {
    try {
        showLoading();
        const response = await fetch('/api/reports/administrative');
        const data = await response.json();
        
        if (data.success) {
            currentReportData = { type: 'administrative', data: data.data };
            showNotification('Administrative report generated successfully', 'success');
        } else {
            showNotification('Error generating administrative report', 'error');
        }
        hideLoading();
    } catch (error) {
        console.error('Error generating administrative report:', error);
        hideLoading();
        showNotification('Error generating administrative report', 'error');
    }
}

// Open Custom Report Builder
function openCustomReportBuilder() {
    const modal = new bootstrap.Modal(document.getElementById('customReportModal'));
    modal.show();
}

// Generate Custom Report
function generateCustomReport() {
    const form = document.getElementById('customReportForm');
    const formData = new FormData(form);
    
    // Implementation for custom report generation
    showNotification('Custom report generation started', 'success');
}

// Generate Custom Report from Form
function generateCustomReportFromForm() {
    // Implementation for generating custom report from form data
    showNotification('Custom report generated successfully', 'success');
}

// View Report
async function viewReport(type) {
    try {
        showLoading();
        const response = await fetch(`/api/reports/${type}`);
        const data = await response.json();
        
        if (data.success) {
            showReportModal(type, data.data);
        } else {
            showNotification(`Error loading ${type} report`, 'error');
        }
        hideLoading();
    } catch (error) {
        console.error(`Error loading ${type} report:`, error);
        hideLoading();
        showNotification(`Error loading ${type} report`, 'error');
    }
}

// Export Report
async function exportReport(type, format = 'pdf') {
    try {
        showLoading();
        const response = await fetch(`/api/reports/export?type=${type}&format=${format}`);
        
        if (response.ok) {
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${type}_report.${format}`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            
            showNotification(`${type} report exported successfully`, 'success');
        } else {
            showNotification(`Error exporting ${type} report`, 'error');
        }
        hideLoading();
    } catch (error) {
        console.error(`Error exporting ${type} report:`, error);
        hideLoading();
        showNotification(`Error exporting ${type} report`, 'error');
    }
}

// Download Report
function downloadReport(type) {
    exportReport(type, 'pdf');
}

// Export All Data
function exportAllData() {
    exportReport('all', 'excel');
}

// Schedule Report
function scheduleReport() {
    showNotification('Report scheduling feature coming soon', 'success');
}

// Email Report
function emailReport() {
    showNotification('Email report feature coming soon', 'success');
}

// Generate Quick Report
async function generateQuickReport(type) {
    try {
        showLoading();
        const response = await fetch(`/api/reports/quick/${type}`);
        const data = await response.json();
        
        if (data.success) {
            showReportModal(type, data.data);
            showNotification(`${type} report generated successfully`, 'success');
        } else {
            showNotification(`Error generating ${type} report`, 'error');
        }
        hideLoading();
    } catch (error) {
        console.error(`Error generating ${type} report:`, error);
        hideLoading();
        showNotification(`Error generating ${type} report`, 'error');
    }
}

// Show/Hide Loading
function showLoading() {
    document.querySelector('.loading-overlay').style.display = 'flex';
}

function hideLoading() {
    document.querySelector('.loading-overlay').style.display = 'none';
}

// Show Notification
function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}

// Show Report Modal
function showReportModal(type, data) {
    const modal = document.getElementById('reportModal');
    const title = document.getElementById('modalTitle');
    const body = document.getElementById('modalBody');
    
    title.textContent = `${type.charAt(0).toUpperCase() + type.slice(1)} Report`;
    
    let content = '';
    switch (type) {
        case 'academic':
            content = formatAcademicReport(data);
            break;
        case 'financial':
            content = formatFinancialReport(data);
            break;
        case 'attendance':
            content = formatAttendanceReport(data);
            break;
        case 'performance':
            content = formatPerformanceReport(data);
            break;
        case 'administrative':
            content = formatAdministrativeReport(data);
            break;
        default:
            content = '<p>Report data not available</p>';
    }
    
    body.innerHTML = content;
    modal.style.display = 'flex';
    currentReportData = { type, data };
}

// Close Report Modal
function closeReportModal() {
    document.getElementById('reportModal').style.display = 'none';
}

// Export Current Report
function exportCurrentReport() {
    if (currentReportData) {
        exportReport(currentReportData.type);
    }
}

// Format Academic Report
function formatAcademicReport(data) {
    return `
        <div class="stat-grid">
            <div class="stat-item">
                <strong>Total Students:</strong> ${data.total_students || 0}
            </div>
            <div class="stat-item">
                <strong>Total Classes:</strong> ${data.total_classes || 0}
            </div>
            <div class="stat-item">
                <strong>Average Grade:</strong> ${data.average_grade || 'N/A'}
            </div>
            <div class="stat-item">
                <strong>Pass Rate:</strong> ${data.pass_rate || 0}%
            </div>
        </div>
        
        <h4>Class Performance</h4>
        <div class="table-responsive"><table class="table">
            <thead>
                <tr><th>Class</th><th>Students</th><th>Average Score</th><th>Pass Rate</th></tr>
            </thead>
            <tbody>
                ${(data.class_performance || []).map(item => 
                    `<tr><td>${item.class_name}</td><td>${item.student_count}</td><td>${item.average_score}%</td><td>${item.pass_rate}%</td></tr>`
                ).join('')}
            </tbody>
        </table></div>
        
        <h4>Subject Performance</h4>
        <div class="table-responsive"><table class="table">
            <thead>
                <tr><th>Subject</th><th>Average Score</th><th>Highest Score</th><th>Lowest Score</th></tr>
            </thead>
            <tbody>
                ${(data.subject_performance || []).map(item => 
                    `<tr><td>${item.subject_name}</td><td>${item.average_score}%</td><td>${item.highest_score}%</td><td>${item.lowest_score}%</td></tr>`
                ).join('')}
            </tbody>
        </table></div>
    `;
}

// Format Financial Report
function formatFinancialReport(data) {
    return `
        <div class="stat-grid">
            <div class="stat-item">
                <strong>Total Revenue:</strong> ₹${(data.total_revenue || 0).toLocaleString()}
            </div>
            <div class="stat-item">
                <strong>Pending Fees:</strong> ₹${(data.pending_fees || 0).toLocaleString()}
            </div>
            <div class="stat-item">
                <strong>Monthly Collection:</strong> ₹${(data.monthly_collection || 0).toLocaleString()}
            </div>
            <div class="stat-item">
                <strong>Total Expenses:</strong> ₹${(data.total_expenses || 0).toLocaleString()}
            </div>
        </div>
        
        <h4>Class-wise Collection</h4>
        <div class="table-responsive"><table class="table">
            <thead>
                <tr><th>Class</th><th>Total Collected</th></tr>
            </thead>
            <tbody>
                ${(data.class_wise_collection || []).map(item => 
                    `<tr><td>${item.class_name}</td><td>₹${item.total_collected.toLocaleString()}</td></tr>`
                ).join('')}
            </tbody>
        </table></div>
        
        <h4>Payment Methods</h4>
        <div class="table-responsive"><table class="table">
            <thead>
                <tr><th>Method</th><th>Amount</th><th>Percentage</th></tr>
            </thead>
            <tbody>
                ${(data.payment_methods || []).map(item => 
                    `<tr><td>${item.method}</td><td>₹${item.amount.toLocaleString()}</td><td>${item.percentage}%</td></tr>`
                ).join('')}
            </tbody>
        </table></div>
    `;
}

// Format Attendance Report
function formatAttendanceReport(data) {
    return `
        <div class="stat-grid">
            <div class="stat-item">
                <strong>Overall Attendance:</strong> ${data.overall_attendance || 0}%
            </div>
            <div class="stat-item">
                <strong>Present Today:</strong> ${data.present_today || 0}
            </div>
            <div class="stat-item">
                <strong>Absent Today:</strong> ${data.absent_today || 0}
            </div>
            <div class="stat-item">
                <strong>Total Students:</strong> ${data.total_students || 0}
            </div>
        </div>
        
        <h4>Class-wise Attendance</h4>
        <div class="table-responsive"><table class="table">
            <thead>
                <tr><th>Class</th><th>Attendance Rate</th><th>Present</th><th>Total</th></tr>
            </thead>
            <tbody>
                ${(data.class_wise_attendance || []).map(item => 
                    `<tr><td>${item.class}</td><td>${item.attendance_rate}%</td><td>${item.present}</td><td>${item.total}</td></tr>`
                ).join('')}
            </tbody>
        </table></div>
        
        <h4>Low Attendance Students</h4>
        <div class="table-responsive"><table class="table">
            <thead>
                <tr><th>Student Name</th><th>Class</th><th>Attendance Rate</th></tr>
            </thead>
            <tbody>
                ${(data.low_attendance_students || []).map(item => 
                    `<tr><td>${item.name}</td><td>${item.class}</td><td>${item.attendance_rate}%</td></tr>`
                ).join('')}
            </tbody>
        </table></div>
    `;
}

// Format Performance Report
function formatPerformanceReport(data) {
    return `
        <h4>Top Performers</h4>
        <div class="table-responsive"><table class="table">
            <thead>
                <tr><th>Student Name</th><th>Class</th><th>Average Score</th></tr>
            </thead>
            <tbody>
                ${(data.top_performers || []).map(item => 
                    `<tr><td>${item.name}</td><td>${item.class}</td><td>${item.average}%</td></tr>`
                ).join('')}
            </tbody>
        </table></div>
        
        <h4>Subject Averages</h4>
        <div class="table-responsive"><table class="table">
            <thead>
                <tr><th>Subject</th><th>Average Score</th></tr>
            </thead>
            <tbody>
                ${(data.subject_averages || []).map(item => 
                    `<tr><td>${item.subject}</td><td>${item.average}%</td></tr>`
                ).join('')}
            </tbody>
        </table></div>
        
        <h4>Grade Distribution</h4>
        <div class="table-responsive"><table class="table">
            <thead>
                <tr><th>Grade</th><th>Count</th></tr>
            </thead>
            <tbody>
                ${(data.grade_distribution || []).map(item => 
                    `<tr><td>${item.grade}</td><td>${item.count}</td></tr>`
                ).join('')}
            </tbody>
        </table></div>
    `;
}

// Format Administrative Report
function formatAdministrativeReport(data) {
    return `
        <div class="stat-grid">
            <div class="stat-item">
                <strong>Total Teachers:</strong> ${data.total_teachers || 0}
            </div>
            <div class="stat-item">
                <strong>Total Classes:</strong> ${data.total_classes || 0}
            </div>
            <div class="stat-item">
                <strong>Total Subjects:</strong> ${data.total_subjects || 0}
            </div>
            <div class="stat-item">
                <strong>Active Students:</strong> ${data.active_students || 0}
            </div>
        </div>
        
        <h4>Teacher Information</h4>
        <table class="table">
            <thead>
                <tr><th>Teacher Name</th><th>Subjects</th><th>Classes</th><th>Experience</th></tr>
            </thead>
            <tbody>
                ${(data.teachers || []).map(item => 
                    `<tr><td>${item.name}</td><td>${item.subjects}</td><td>${item.classes}</td><td>${item.experience} years</td></tr>`
                ).join('')}
            </tbody>
        </table>
    `;
}
</script>
@endsection