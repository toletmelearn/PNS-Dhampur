@extends('layouts.app')

@section('title', 'Attendance Analytics')

@section('content')
<style>
    .gradient-bg {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .glass-effect {
        background: rgba(255, 255, 255, 0.25);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.18);
    }
    
    .metric-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .metric-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    
    .trend-up {
        color: #28a745;
    }
    
    .trend-down {
        color: #dc3545;
    }
    
    .avatar {
        width: 40px;
        height: 40px;
    }
    
    .avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* Loading States */
    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.9);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        border-radius: 0.375rem;
    }

    .loading-spinner {
        width: 40px;
        height: 40px;
        border: 4px solid #f3f3f3;
        border-top: 4px solid #007bff;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .skeleton {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: loading 1.5s infinite;
        border-radius: 0.375rem;
        height: 20px;
        margin-bottom: 10px;
    }

    @keyframes loading {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }

    /* Error States */
    .error-state {
        text-align: center;
        padding: 2rem;
        color: #6c757d;
    }

    .error-icon {
        font-size: 3rem;
        color: #dc3545;
        margin-bottom: 1rem;
    }

    .retry-btn {
        margin-top: 1rem;
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .container-fluid {
            padding: 0.5rem;
        }

        .card {
            margin-bottom: 1rem;
        }

        .metric-card .card-body {
            padding: 1rem;
        }

        .metric-value {
            font-size: 1.5rem !important;
        }

        .btn-group {
            flex-wrap: wrap;
        }

        .btn-group .btn {
            font-size: 0.875rem;
            padding: 0.375rem 0.75rem;
        }

        .filters-section .row > div {
            margin-bottom: 0.5rem;
        }

        .header-actions {
            flex-direction: column;
            gap: 0.5rem;
        }

        .header-actions .btn {
            width: 100%;
        }

        .list-group-item {
            padding: 0.75rem 0;
        }

        .avatar {
            width: 32px;
            height: 32px;
        }

        canvas {
            max-height: 250px !important;
        }
    }

    @media (max-width: 576px) {
        .metric-cards .col-lg-3 {
            margin-bottom: 1rem;
        }

        .chart-container {
            margin-bottom: 1.5rem;
        }

        .filters-section .col-md-3,
        .filters-section .col-md-2 {
            width: 100%;
            margin-bottom: 0.75rem;
        }

        .btn-group-toggle .btn {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }

        .d-flex.gap-2 {
            flex-direction: column;
            gap: 0.5rem !important;
        }

        .d-flex.gap-2 .btn {
            width: 100%;
        }
    }

    /* Toast Notifications */
    .toast-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1055;
    }

    .toast {
        min-width: 300px;
    }

    .toast.show {
        opacity: 1;
    }

    /* Form Validation */
    .is-invalid {
        border-color: #dc3545;
    }

    .invalid-feedback {
        display: block;
        width: 100%;
        margin-top: 0.25rem;
        font-size: 0.875em;
        color: #dc3545;
    }

    /* Accessibility */
    .sr-only {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }

    /* Focus indicators */
    .btn:focus,
    .form-control:focus,
    .form-select:focus {
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    /* Chart loading states */
    .chart-loading {
        position: relative;
        min-height: 300px;
    }

    .chart-skeleton {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        padding: 20px;
    }

    .chart-skeleton .skeleton-bar {
        height: 20px;
        margin-bottom: 15px;
        border-radius: 4px;
    }

    .chart-skeleton .skeleton-bar:nth-child(1) { width: 80%; }
    .chart-skeleton .skeleton-bar:nth-child(2) { width: 65%; }
    .chart-skeleton .skeleton-bar:nth-child(3) { width: 90%; }
    .chart-skeleton .skeleton-bar:nth-child(4) { width: 45%; }
    .chart-skeleton .skeleton-bar:nth-child(5) { width: 70%; }

    .avatar-sm {
        width: 32px;
        height: 32px;
    }

    .card {
        transition: transform 0.2s ease-in-out;
    }

    .card:hover {
        transform: translateY(-2px);
    }

    .btn-group .btn-check:checked + .btn {
        background-color: var(--bs-primary);
        border-color: var(--bs-primary);
        color: white;
    }

    .list-group-item {
        transition: background-color 0.2s ease-in-out;
    }

    .list-group-item:hover {
        background-color: rgba(0, 0, 0, 0.02);
    }

    .alert {
        transition: all 0.2s ease-in-out;
    }

    .alert:hover {
        transform: translateY(-1px);
    }
</style>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<div class="container-fluid py-4">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-lg" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="card-body text-white p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h3 mb-2 fw-bold">
                                <i class="fas fa-chart-line me-2"></i>
                                Attendance Analytics
                            </h1>
                            <p class="mb-0 opacity-75">Comprehensive insights and trends for student attendance</p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('attendance.index') }}" class="btn btn-light btn-sm">
                                <i class="fas fa-arrow-left me-1"></i>
                                Back to Attendance
                            </a>
                            <a href="{{ route('attendance.reports') }}" class="btn btn-outline-light btn-sm">
                                <i class="fas fa-file-alt me-1"></i>
                                Reports
                            </a>
                            <button class="btn btn-outline-light btn-sm" onclick="exportAnalytics()">
                                <i class="fas fa-download me-1"></i>
                                Export
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h5 class="card-title fw-bold mb-3">
                        <i class="fas fa-filter text-primary me-2"></i>
                        Analytics Filters
                    </h5>
                    <form id="analyticsFilters" class="row g-3">
                        <div class="col-md-3">
                            <label for="date_range" class="form-label fw-semibold">Date Range</label>
                            <select class="form-select" id="date_range" name="date_range">
                                <option value="7">Last 7 days</option>
                                <option value="30" selected>Last 30 days</option>
                                <option value="90">Last 3 months</option>
                                <option value="365">Last year</option>
                                <option value="custom">Custom Range</option>
                            </select>
                        </div>
                        <div class="col-md-3" id="custom_dates" style="display: none;">
                            <label for="start_date" class="form-label fw-semibold">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date">
                        </div>
                        <div class="col-md-3" id="custom_dates_end" style="display: none;">
                            <label for="end_date" class="form-label fw-semibold">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date">
                        </div>
                        <div class="col-md-3">
                            <label for="class_filter" class="form-label fw-semibold">Class</label>
                            <select class="form-select" id="class_filter" name="class_id">
                                <option value="">All Classes</option>
                                <option value="1">Class 1</option>
                                <option value="2">Class 2</option>
                                <option value="3">Class 3</option>
                                <option value="4">Class 4</option>
                                <option value="5">Class 5</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="section_filter" class="form-label fw-semibold">Section</label>
                            <select class="form-select" id="section_filter" name="section">
                                <option value="">All Sections</option>
                                <option value="A">Section A</option>
                                <option value="B">Section B</option>
                                <option value="C">Section C</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <button type="button" class="btn btn-primary" onclick="updateAnalytics()">
                                <i class="fas fa-sync-alt me-1"></i>
                                Update Analytics
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="resetFilters()">
                                <i class="fas fa-undo me-1"></i>
                                Reset
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Key Metrics Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <div class="card-body text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title opacity-75 mb-1">Average Attendance</h6>
                            <h2 class="mb-0 fw-bold" id="avg_attendance">87.5%</h2>
                            <small class="opacity-75">
                                <i class="fas fa-arrow-up me-1"></i>
                                +2.3% from last period
                            </small>
                        </div>
                        <div class="text-end">
                            <i class="fas fa-percentage fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                <div class="card-body text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title opacity-75 mb-1">Total Present Days</h6>
                            <h2 class="mb-0 fw-bold" id="total_present">2,847</h2>
                            <small class="opacity-75">
                                <i class="fas fa-arrow-up me-1"></i>
                                +156 from last period
                            </small>
                        </div>
                        <div class="text-end">
                            <i class="fas fa-check-circle fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                <div class="card-body text-white position-relative">
                    <div class="loading-overlay d-none" id="metric3-loading">
                        <div class="loading-spinner"></div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title opacity-75 mb-1">Total Absent Days</h6>
                            <h2 class="mb-0 fw-bold metric-value" id="total_absent">412</h2>
                            <small class="opacity-75">
                                <i class="fas fa-arrow-down me-1"></i>
                                -23 from last period
                            </small>
                        </div>
                        <div class="text-end">
                            <i class="fas fa-times-circle fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);">
                <div class="card-body text-dark position-relative">
                    <div class="loading-overlay d-none" id="metric4-loading">
                        <div class="loading-spinner"></div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title opacity-75 mb-1">Late Arrivals</h6>
                            <h2 class="mb-0 fw-bold metric-value" id="total_late">89</h2>
                            <small class="opacity-75">
                                <i class="fas fa-arrow-down me-1"></i>
                                -12 from last period
                            </small>
                        </div>
                        <div class="text-end">
                            <i class="fas fa-clock fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="row mb-4">
        <!-- Attendance Trend Chart -->
        <div class="col-lg-8 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pb-0">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <h5 class="card-title fw-bold mb-0">
                            <i class="fas fa-chart-line text-primary me-2"></i>
                            Attendance Trends
                        </h5>
                        <div class="btn-group btn-group-sm" role="group">
                            <input type="radio" class="btn-check" name="trend_period" id="daily" value="daily" checked>
                            <label class="btn btn-outline-primary" for="daily">Daily</label>
                            <input type="radio" class="btn-check" name="trend_period" id="weekly" value="weekly">
                            <label class="btn btn-outline-primary" for="weekly">Weekly</label>
                            <input type="radio" class="btn-check" name="trend_period" id="monthly" value="monthly">
                            <label class="btn btn-outline-primary" for="monthly">Monthly</label>
                        </div>
                    </div>
                </div>
                <div class="card-body position-relative chart-container">
                    <div class="loading-overlay d-none" id="chart1-loading">
                        <div class="loading-spinner"></div>
                    </div>
                    <div class="error-state d-none" id="chart1-error">
                        <div class="error-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h5>Failed to Load Chart</h5>
                        <p>Unable to load attendance trends data.</p>
                        <button class="btn btn-primary retry-btn" onclick="retryChart('chart1')">
                            <i class="fas fa-redo me-1"></i>
                            Retry
                        </button>
                    </div>
                    <canvas id="attendanceTrendChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Class-wise Attendance Chart -->
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pb-0">
                    <h5 class="card-title fw-bold mb-0">
                        <i class="fas fa-chart-pie text-success me-2"></i>
                        Class-wise Distribution
                    </h5>
                </div>
                <div class="card-body position-relative chart-container">
                    <div class="loading-overlay d-none" id="chart2-loading">
                        <div class="loading-spinner"></div>
                    </div>
                    <div class="error-state d-none" id="chart2-error">
                        <div class="error-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h5>Failed to Load Chart</h5>
                        <p>Unable to load class distribution data.</p>
                        <button class="btn btn-primary retry-btn" onclick="retryChart('chart2')">
                            <i class="fas fa-redo me-1"></i>
                            Retry
                        </button>
                    </div>
                    <canvas id="classWiseChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Analytics -->
    <div class="row mb-4">
        <!-- Top Performers -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pb-0">
                    <h5 class="card-title fw-bold mb-0">
                        <i class="fas fa-trophy text-warning me-2"></i>
                        Top Performers (Attendance)
                    </h5>
                </div>
                <div class="card-body position-relative">
                    <div class="loading-overlay d-none" id="performers-loading">
                        <div class="loading-spinner"></div>
                    </div>
                    <div class="list-group list-group-flush" id="top-performers-list">
                        <div class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-sm me-3">
                                    <img src="https://ui-avatars.com/api/?name=Rahul+Sharma&background=4facfe&color=fff" 
                                         class="rounded-circle" alt="Student">
                                </div>
                                <div>
                                    <h6 class="mb-0">Rahul Sharma</h6>
                                    <small class="text-muted">Class 5-A</small>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-success">98.5%</span>
                            </div>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-sm me-3">
                                    <img src="https://ui-avatars.com/api/?name=Priya+Singh&background=43e97b&color=fff" 
                                         class="rounded-circle" alt="Student">
                                </div>
                                <div>
                                    <h6 class="mb-0">Priya Singh</h6>
                                    <small class="text-muted">Class 4-B</small>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-success">97.8%</span>
                            </div>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-sm me-3">
                                    <img src="https://ui-avatars.com/api/?name=Amit+Kumar&background=fa709a&color=fff" 
                                         class="rounded-circle" alt="Student">
                                </div>
                                <div>
                                    <h6 class="mb-0">Amit Kumar</h6>
                                    <small class="text-muted">Class 3-A</small>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-success">96.2%</span>
                            </div>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-sm me-3">
                                    <img src="https://ui-avatars.com/api/?name=Sneha+Patel&background=a8edea&color=333" 
                                         class="rounded-circle" alt="Student">
                                </div>
                                <div>
                                    <h6 class="mb-0">Sneha Patel</h6>
                                    <small class="text-muted">Class 2-C</small>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-success">95.7%</span>
                            </div>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-sm me-3">
                                    <img src="https://ui-avatars.com/api/?name=Vikash+Gupta&background=667eea&color=fff" 
                                         class="rounded-circle" alt="Student">
                                </div>
                                <div>
                                    <h6 class="mb-0">Vikash Gupta</h6>
                                    <small class="text-muted">Class 1-A</small>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-success">94.9%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance Patterns -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pb-0">
                    <h5 class="card-title fw-bold mb-0">
                        <i class="fas fa-calendar-alt text-info me-2"></i>
                        Weekly Attendance Patterns
                    </h5>
                </div>
                <div class="card-body position-relative chart-container">
                    <div class="loading-overlay d-none" id="chart3-loading">
                        <div class="loading-spinner"></div>
                    </div>
                    <div class="error-state d-none" id="chart3-error">
                        <div class="error-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h5>Failed to Load Chart</h5>
                        <p>Unable to load weekly patterns data.</p>
                        <button class="btn btn-primary retry-btn" onclick="retryChart('chart3')">
                            <i class="fas fa-redo me-1"></i>
                            Retry
                        </button>
                    </div>
                    <canvas id="weeklyPatternChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Insights -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pb-0">
                    <h5 class="card-title fw-bold mb-0">
                        <i class="fas fa-lightbulb text-warning me-2"></i>
                        Attendance Insights & Recommendations
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-4 mb-3">
                            <div class="alert alert-info border-0 shadow-sm">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-info-circle fa-2x text-info me-3"></i>
                                    <div>
                                        <h6 class="alert-heading mb-1">Peak Absence Days</h6>
                                        <p class="mb-0 small">Mondays and Fridays show 15% higher absence rates. Consider implementing Monday motivation programs.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 mb-3">
                            <div class="alert alert-success border-0 shadow-sm">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-check-circle fa-2x text-success me-3"></i>
                                    <div>
                                        <h6 class="alert-heading mb-1">Improvement Trend</h6>
                                        <p class="mb-0 small">Overall attendance has improved by 5.2% compared to the previous period. Great progress!</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 mb-3">
                            <div class="alert alert-warning border-0 shadow-sm">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-exclamation-triangle fa-2x text-warning me-3"></i>
                                    <div>
                                        <h6 class="alert-heading mb-1">Attention Required</h6>
                                        <p class="mb-0 small">Class 3-B has the lowest attendance rate (78%). Consider parent meetings and intervention programs.</p>
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

<style>
.avatar {
    width: 40px;
    height: 40px;
}

.avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-sm {
    width: 32px;
    height: 32px;
}

.card {
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
}

.btn-group .btn-check:checked + .btn {
    background-color: var(--bs-primary);
    border-color: var(--bs-primary);
    color: white;
}

.list-group-item {
    transition: background-color 0.2s ease-in-out;
}

.list-group-item:hover {
    background-color: rgba(0, 0, 0, 0.02);
}

.alert {
    transition: all 0.2s ease-in-out;
}

.alert:hover {
    transform: translateY(-1px);
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Initialize all attendance modules
const notifications = new AttendanceNotifications();
const validator = new AttendanceValidator();
const accessibility = new AttendanceAccessibility();
const performance = new AttendancePerformance();
const loading = new AttendanceLoading();

// Initialize modules
validator.init();
accessibility.init();
performance.init();

// Store instances globally for other scripts to use
window.attendanceModules = {
    notifications,
    validator,
    accessibility,
    performance,
    loading
};

// Chart configurations
let attendanceTrendChart, classWiseChart, weeklyPatternChart;
let isLoading = false;

document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
    setupEventListeners();
    loadAnalyticsData();
});

function setupEventListeners() {
    // Date range change handler
    document.getElementById('date_range').addEventListener('change', function() {
        const customDates = document.getElementById('custom_dates');
        const customDatesEnd = document.getElementById('custom_dates_end');
        
        if (this.value === 'custom') {
            customDates.style.display = 'block';
            customDatesEnd.style.display = 'block';
        } else {
            customDates.style.display = 'none';
            customDatesEnd.style.display = 'none';
        }
        
        // Clear validation errors
        clearValidationErrors();
    });

    // Trend period change handlers
    document.querySelectorAll('input[name="trend_period"]').forEach(radio => {
        radio.addEventListener('change', function() {
            updateTrendChart(this.value);
        });
    });

    // Form validation
    document.getElementById('start_date').addEventListener('change', validateDateRange);
    document.getElementById('end_date').addEventListener('change', validateDateRange);
}

function validateDateRange() {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    const startDateEl = document.getElementById('start_date');
    const endDateEl = document.getElementById('end_date');
    
    clearValidationErrors();
    
    if (startDate && endDate) {
        const start = new Date(startDate);
        const end = new Date(endDate);
        
        if (start > end) {
            showValidationError('start_date', 'Start date must be before end date');
            showValidationError('end_date', 'End date must be after start date');
            return false;
        }
        
        // Check if date range is too large (more than 2 years)
        const diffTime = Math.abs(end - start);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        if (diffDays > 730) {
            showValidationError('start_date', 'Date range cannot exceed 2 years');
            showValidationError('end_date', 'Date range cannot exceed 2 years');
            return false;
        }
    }
    
    return true;
}

function showValidationError(fieldId, message) {
    const field = document.getElementById(fieldId);
    const feedback = document.getElementById(fieldId + '_feedback');
    
    field.classList.add('is-invalid');
    feedback.textContent = message;
}

function clearValidationErrors() {
    document.querySelectorAll('.is-invalid').forEach(el => {
        el.classList.remove('is-invalid');
    });
    document.querySelectorAll('.invalid-feedback').forEach(el => {
        el.textContent = '';
    });
}

function showLoading(elementId) {
    const loadingEl = document.getElementById(elementId + '-loading');
    if (loadingEl) {
        loadingEl.classList.remove('d-none');
    }
}

function hideLoading(elementId) {
    const loadingEl = document.getElementById(elementId + '-loading');
    if (loadingEl) {
        loadingEl.classList.add('d-none');
    }
}

function showError(elementId, message) {
    const errorEl = document.getElementById(elementId + '-error');
    if (errorEl) {
        errorEl.classList.remove('d-none');
        const messageEl = errorEl.querySelector('p');
        if (messageEl) {
            messageEl.textContent = message;
        }
    }
}

function hideError(elementId) {
    const errorEl = document.getElementById(elementId + '-error');
    if (errorEl) {
        errorEl.classList.add('d-none');
    }
}

function showToast(message, type = 'success') {
    const toastContainer = document.getElementById('toastContainer');
    const toastId = 'toast-' + Date.now();
    
    const toastHtml = `
        <div class="toast" id="${toastId}" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <i class="fas fa-${type === 'success' ? 'check-circle text-success' : type === 'error' ? 'exclamation-circle text-danger' : 'info-circle text-info'} me-2"></i>
                <strong class="me-auto">${type === 'success' ? 'Success' : type === 'error' ? 'Error' : 'Info'}</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement);
    toast.show();
    
    // Remove toast element after it's hidden
    toastElement.addEventListener('hidden.bs.toast', function() {
        toastElement.remove();
    });
}

function initializeCharts() {
    try {
        // Attendance Trend Chart
        const trendCtx = document.getElementById('attendanceTrendChart').getContext('2d');
        attendanceTrendChart = new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
                datasets: [{
                    label: 'Present',
                    data: [85, 88, 92, 87, 83, 90],
                    borderColor: '#4facfe',
                    backgroundColor: 'rgba(79, 172, 254, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Absent',
                    data: [15, 12, 8, 13, 17, 10],
                    borderColor: '#fa709a',
                    backgroundColor: 'rgba(250, 112, 154, 0.1)',
                    tension: 0.4,
                    fill: true
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
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });

        // Class-wise Chart
        const classCtx = document.getElementById('classWiseChart').getContext('2d');
        classWiseChart = new Chart(classCtx, {
            type: 'doughnut',
            data: {
                labels: ['Class 1', 'Class 2', 'Class 3', 'Class 4', 'Class 5'],
                datasets: [{
                    data: [92, 88, 85, 90, 87],
                    backgroundColor: [
                        '#4facfe',
                        '#43e97b',
                        '#fa709a',
                        '#fee140',
                        '#a8edea'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });

        // Weekly Pattern Chart
        const weeklyCtx = document.getElementById('weeklyPatternChart').getContext('2d');
        weeklyPatternChart = new Chart(weeklyCtx, {
            type: 'bar',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
                datasets: [{
                    label: 'Attendance %',
                    data: [82, 88, 92, 89, 78, 85],
                    backgroundColor: [
                        '#4facfe',
                        '#43e97b',
                        '#fa709a',
                        '#fee140',
                        '#a8edea',
                        '#667eea'
                    ],
                    borderRadius: 4,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });
    } catch (error) {
        console.error('Error initializing charts:', error);
        showToast('Failed to initialize charts', 'error');
    }
}

function updateAnalytics() {
    if (isLoading) {
        showToast('Please wait for the current operation to complete', 'info');
        return;
    }

    // Validate form if custom date range is selected
    if (document.getElementById('date_range').value === 'custom') {
        if (!validateDateRange()) {
            showToast('Please fix the validation errors before updating', 'error');
            return;
        }
    }

    isLoading = true;
    const updateBtn = document.getElementById('updateBtn');
    const btnText = updateBtn.querySelector('.btn-text');
    const originalText = btnText.textContent;
    
    // Update button state
    updateBtn.disabled = true;
    btnText.textContent = 'Updating...';
    updateBtn.querySelector('i').className = 'fas fa-spinner fa-spin me-1';
    
    // Show loading states
    showLoading('metric1');
    showLoading('metric2');
    showLoading('metric3');
    showLoading('metric4');
    showLoading('chart1');
    showLoading('chart2');
    showLoading('chart3');
    showLoading('performers');

    // Simulate API call
    setTimeout(() => {
        try {
            loadAnalyticsData();
            showToast('Analytics updated successfully!', 'success');
        } catch (error) {
            console.error('Error updating analytics:', error);
            showToast('Failed to update analytics. Please try again.', 'error');
        } finally {
            // Reset button state
            updateBtn.disabled = false;
            btnText.textContent = originalText;
            updateBtn.querySelector('i').className = 'fas fa-sync-alt me-1';
            
            // Hide loading states
            hideLoading('metric1');
            hideLoading('metric2');
            hideLoading('metric3');
            hideLoading('metric4');
            hideLoading('chart1');
            hideLoading('chart2');
            hideLoading('chart3');
            hideLoading('performers');
            
            isLoading = false;
        }
    }, 2000);
}

function loadAnalyticsData() {
    // Simulate loading analytics data
    // In a real application, this would make AJAX calls to fetch data
    
    // Update metrics with animation
    animateValue('avg_attendance', 87.5, 89.2, '%');
    animateValue('total_present', 2847, 2903, '');
    animateValue('total_absent', 412, 389, '');
    animateValue('total_late', 89, 76, '');
}

function animateValue(elementId, start, end, suffix) {
    const element = document.getElementById(elementId);
    const duration = 1000;
    const startTime = performance.now();
    
    function update(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        
        const current = start + (end - start) * progress;
        const displayValue = suffix === '%' ? current.toFixed(1) : Math.floor(current).toLocaleString();
        
        element.textContent = displayValue + suffix;
        
        if (progress < 1) {
            requestAnimationFrame(update);
        }
    }
    
    requestAnimationFrame(update);
}

function updateTrendChart(period) {
    if (!attendanceTrendChart) return;
    
    let labels, data1, data2;
    
    switch(period) {
        case 'daily':
            labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            data1 = [85, 88, 92, 87, 83, 90];
            data2 = [15, 12, 8, 13, 17, 10];
            break;
        case 'weekly':
            labels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
            data1 = [87, 89, 85, 91];
            data2 = [13, 11, 15, 9];
            break;
        case 'monthly':
            labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
            data1 = [88, 85, 90, 87, 89, 92];
            data2 = [12, 15, 10, 13, 11, 8];
            break;
    }
    
    attendanceTrendChart.data.labels = labels;
    attendanceTrendChart.data.datasets[0].data = data1;
    attendanceTrendChart.data.datasets[1].data = data2;
    attendanceTrendChart.update('active');
}

function resetFilters() {
    document.getElementById('analyticsFilters').reset();
    document.getElementById('custom_dates').style.display = 'none';
    document.getElementById('custom_dates_end').style.display = 'none';
    clearValidationErrors();
    updateAnalytics();
}

function retryChart(chartId) {
    hideError(chartId);
    showLoading(chartId);
    
    // Simulate retry
    setTimeout(() => {
        hideLoading(chartId);
        showToast('Chart loaded successfully!', 'success');
    }, 1000);
}

function exportAnalytics() {
    Swal.fire({
        title: 'Export Analytics',
        text: 'Choose export format:',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-file-pdf me-1"></i> PDF Report',
        cancelButtonText: '<i class="fas fa-file-excel me-1"></i> Excel Data',
        reverseButtons: true,
        customClass: {
            confirmButton: 'btn btn-danger me-2',
            cancelButton: 'btn btn-success'
        },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed) {
            exportToPDF();
        } else if (result.dismiss === Swal.DismissReason.cancel) {
            exportToExcel();
        }
    });
}

function exportToPDF() {
    Swal.fire({
        title: 'Generating PDF...',
        text: 'Please wait while we prepare your report',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    setTimeout(() => {
        Swal.fire({
            icon: 'success',
            title: 'PDF Generated!',
            text: 'Your analytics report has been downloaded',
            timer: 2000,
            showConfirmButton: false
        });
        
        // Simulate file download
        const link = document.createElement('a');
        link.href = 'data:application/pdf;base64,'; // In real app, this would be the PDF URL
        link.download = 'attendance-analytics-report.pdf';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }, 2000);
}

function exportToExcel() {
    Swal.fire({
        title: 'Generating Excel...',
        text: 'Please wait while we prepare your data',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    setTimeout(() => {
        Swal.fire({
            icon: 'success',
            title: 'Excel Generated!',
            text: 'Your analytics data has been downloaded',
            timer: 2000,
            showConfirmButton: false
        });
        
        // Simulate file download
        const link = document.createElement('a');
        link.href = 'data:application/vnd.ms-excel;base64,'; // In real app, this would be the Excel URL
        link.download = 'attendance-analytics-data.xlsx';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }, 2000);
}

// Handle window resize for charts
window.addEventListener('resize', function() {
    if (attendanceTrendChart) attendanceTrendChart.resize();
    if (classWiseChart) classWiseChart.resize();
    if (weeklyPatternChart) weeklyPatternChart.resize();
});

// Keyboard accessibility
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        // Close any open modals or dropdowns
        const openModals = document.querySelectorAll('.modal.show');
        openModals.forEach(modal => {
            const modalInstance = bootstrap.Modal.getInstance(modal);
            if (modalInstance) modalInstance.hide();
        });
    }
});
</script>
@endsection