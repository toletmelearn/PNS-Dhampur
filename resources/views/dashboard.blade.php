@extends('layouts.app')

@section('title', 'Dashboard - PNS Dhampur')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
    :root {
        --primary-color: #4f46e5;
        --secondary-color: #06b6d4;
        --success-color: #10b981;
        --warning-color: #f59e0b;
        --danger-color: #ef4444;
        --info-color: #3b82f6;
        --light-bg: #f8fafc;
        --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        --card-shadow-hover: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        --border-radius: 12px;
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    body {
        background-color: var(--light-bg);
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    }

    .dashboard-header {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        border-radius: var(--border-radius);
        color: white;
        padding: 2rem;
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
    }

    .dashboard-header::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 200px;
        height: 200px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        transform: translate(50px, -50px);
    }

    .dashboard-header::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 150px;
        height: 150px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 50%;
        transform: translate(-50px, 50px);
    }

    .dashboard-header .content {
        position: relative;
        z-index: 2;
    }

    .stats-card {
        background: white;
        border-radius: var(--border-radius);
        padding: 1.5rem;
        box-shadow: var(--card-shadow);
        transition: var(--transition);
        border: 1px solid rgba(0, 0, 0, 0.05);
        position: relative;
        overflow: hidden;
    }

    .stats-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--card-shadow-hover);
    }

    .stats-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: var(--primary-color);
        transition: var(--transition);
    }

    .stats-card:nth-child(2)::before { background: var(--success-color); }
    .stats-card:nth-child(3)::before { background: var(--warning-color); }
    .stats-card:nth-child(4)::before { background: var(--info-color); }

    .stats-card .icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 1rem;
    }

    .stats-card:nth-child(1) .icon {
        background: rgba(79, 70, 229, 0.1);
        color: var(--primary-color);
    }

    .stats-card:nth-child(2) .icon {
        background: rgba(16, 185, 129, 0.1);
        color: var(--success-color);
    }

    .stats-card:nth-child(3) .icon {
        background: rgba(245, 158, 11, 0.1);
        color: var(--warning-color);
    }

    .stats-card:nth-child(4) .icon {
        background: rgba(59, 130, 246, 0.1);
        color: var(--info-color);
    }

    .stats-number {
        font-size: 2rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 0.5rem;
    }

    .stats-label {
        color: #6b7280;
        font-size: 0.875rem;
        font-weight: 500;
        margin-bottom: 0.25rem;
    }

    .stats-change {
        font-size: 0.75rem;
        font-weight: 500;
        padding: 0.25rem 0.5rem;
        border-radius: 6px;
        display: inline-block;
    }

    .stats-change.positive {
        background: rgba(16, 185, 129, 0.1);
        color: var(--success-color);
    }

    .stats-change.neutral {
        background: rgba(107, 114, 128, 0.1);
        color: #6b7280;
    }

    .quick-actions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .quick-action-card {
        background: white;
        border-radius: var(--border-radius);
        padding: 1.25rem;
        text-decoration: none;
        color: #374151;
        transition: var(--transition);
        box-shadow: var(--card-shadow);
        border: 1px solid rgba(0, 0, 0, 0.05);
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .quick-action-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--card-shadow-hover);
        text-decoration: none;
        color: var(--primary-color);
    }

    .quick-action-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        transform: scaleX(0);
        transition: var(--transition);
    }

    .quick-action-card:hover::before {
        transform: scaleX(1);
    }

    .quick-action-card .icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        margin: 0 auto 0.75rem;
        background: rgba(79, 70, 229, 0.1);
        color: var(--primary-color);
        transition: var(--transition);
    }

    .quick-action-card:hover .icon {
        background: var(--primary-color);
        color: white;
    }

    .chart-card {
        background: white;
        border-radius: var(--border-radius);
        padding: 1.5rem;
        box-shadow: var(--card-shadow);
        border: 1px solid rgba(0, 0, 0, 0.05);
        margin-bottom: 1.5rem;
    }

    .chart-title {
        font-size: 1.125rem;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .activity-card {
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--card-shadow);
        border: 1px solid rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }

    .activity-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #e5e7eb;
        background: #f9fafb;
    }

    .activity-item {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #f3f4f6;
        transition: var(--transition);
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .activity-item:hover {
        background: #f9fafb;
    }

    .activity-item:last-child {
        border-bottom: none;
    }

    .activity-icon {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.875rem;
        flex-shrink: 0;
    }

    .activity-text {
        font-weight: 500;
        color: #374151;
        margin-bottom: 0.25rem;
    }

    .activity-time {
        font-size: 0.75rem;
        color: #6b7280;
    }

    .notification-item {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #f3f4f6;
        transition: var(--transition);
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .notification-item:hover {
        background: #f9fafb;
    }

    .notification-item:last-child {
        border-bottom: none;
    }

    .notification-icon {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.875rem;
        flex-shrink: 0;
    }

    .notification-text {
        font-weight: 500;
        color: #374151;
        margin-bottom: 0.25rem;
    }

    .notification-time {
        font-size: 0.75rem;
        color: #6b7280;
    }

    .notification-list {
        padding: 0;
    }

    .notification-card {
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--card-shadow);
        border: 1px solid rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }

    .notification-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #e5e7eb;
        background: #f9fafb;
    }

    .alert-modern {
        border: none;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
        border-left: 4px solid;
    }

    .alert-modern.alert-warning {
        background: rgba(245, 158, 11, 0.1);
        border-left-color: var(--warning-color);
        color: #92400e;
    }

    .alert-modern.alert-info {
        background: rgba(59, 130, 246, 0.1);
        border-left-color: var(--info-color);
        color: #1e40af;
    }

    .alert-modern.alert-success {
        background: rgba(16, 185, 129, 0.1);
        border-left-color: var(--success-color);
        color: #065f46;
    }

    .summary-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
        margin-top: 1rem;
    }

    .summary-item {
        text-align: center;
        padding: 1rem;
        border-radius: 8px;
        background: #f9fafb;
    }

    .summary-number {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
    }

    .summary-label {
        font-size: 0.75rem;
        color: #6b7280;
        font-weight: 500;
    }

    .attendance-circle {
        position: relative;
        width: 120px;
        height: 120px;
        margin: 0 auto;
    }

    .attendance-percentage {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        text-align: center;
    }

    .attendance-percentage .number {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--success-color);
    }

    .attendance-percentage .label {
        font-size: 0.75rem;
        color: #6b7280;
    }

    @media (max-width: 768px) {
        .dashboard-header {
            padding: 1.5rem;
        }
        
        .quick-actions-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .stats-number {
            font-size: 1.5rem;
        }
        
        .summary-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }
    }

    @media (max-width: 576px) {
        .quick-actions-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Welcome Header -->
    <div class="dashboard-header">
        <div class="content">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <img src="{{ asset('images/school-logo.svg') }}" alt="PNS Logo" style="height: 60px; width: 60px; margin-right: 20px; filter: brightness(0) invert(1);">
                    <div>
                        <h1 class="h2 mb-2">Welcome back, {{ Auth::user()->name }}!</h1>
                        <p class="mb-0 opacity-90">Here's what's happening at Pushp Niketan School today</p>
                    </div>
                </div>
                <div class="text-end">
                    <div class="opacity-75 small">{{ now()->format('l, F j, Y') }}</div>
                    <div class="fw-bold" style="font-size: 1.1rem;">{{ now()->format('g:i A') }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="stats-card">
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stats-number" id="total-students">1,247</div>
                <div class="stats-label">Total Students</div>
                <div class="stats-change positive">+12 this month</div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="stats-card">
                <div class="icon">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="stats-number" id="total-teachers">89</div>
                <div class="stats-label">Teaching Staff</div>
                <div class="stats-change positive">+3 this month</div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="stats-card">
                <div class="icon">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stats-number" id="total-classes">42</div>
                <div class="stats-label">Active Classes</div>
                <div class="stats-change neutral">All grades</div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="stats-card">
                <div class="icon">
                    <i class="fas fa-rupee-sign"></i>
                </div>
                <div class="stats-number" id="monthly-revenue">₹2.4L</div>
                <div class="stats-label">Monthly Revenue</div>
                <div class="stats-change positive">+8% from last month</div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-3 fw-semibold">Quick Actions</h4>
            <div class="quick-actions-grid">
                <a href="{{ route('students.index') }}" class="quick-action-card">
                    <div class="icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <span class="fw-semibold">Add Student</span>
                </a>
                <a href="{{ route('teachers.index') }}" class="quick-action-card">
                    <div class="icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <span class="fw-semibold">Manage Teachers</span>
                </a>
                <a href="{{ route('fees.index') }}" class="quick-action-card">
                    <div class="icon">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <span class="fw-semibold">Fee Collection</span>
                </a>
                <a href="{{ route('biometric-attendance.index') }}" class="quick-action-card">
                    <div class="icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <span class="fw-semibold">Mark Attendance</span>
                </a>
                <a href="{{ route('exams.index') }}" class="quick-action-card">
                    <div class="icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <span class="fw-semibold">Exam Management</span>
                </a>
                <a href="{{ route('reports.index') }}" class="quick-action-card">
                    <div class="icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <span class="fw-semibold">View Reports</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Charts and Analytics -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="chart-card">
                <h5 class="chart-title">
                    <i class="fas fa-chart-line text-primary me-2"></i>
                    Student Enrollment Trends
                </h5>
                <canvas id="enrollmentChart" height="100"></canvas>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="chart-card">
                <h5 class="chart-title">
                    <i class="fas fa-user-check text-success me-2"></i>
                    Attendance Overview
                </h5>
                <div class="text-center">
                    <div class="attendance-circle">
                        <svg class="progress-ring" width="120" height="120">
                            <circle class="progress-ring-circle" stroke="#e5e7eb" stroke-width="8" fill="transparent" r="48" cx="60" cy="60"/>
                            <circle class="progress-ring-circle" stroke="#10b981" stroke-width="8" fill="transparent" r="48" cx="60" cy="60" stroke-dasharray="301.6" stroke-dashoffset="45.24"/>
                        </svg>
                        <div class="attendance-percentage">
                            <div class="number">85%</div>
                            <div class="label">Today's Attendance</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity and Notifications -->
    <div class="row">
        <div class="col-lg-6">
            <div class="activity-card">
                <div class="activity-header">
                    <h5 class="mb-0">
                        <i class="fas fa-clock text-info me-2"></i>
                        Recent Activity
                    </h5>
                </div>
                <div class="activity-feed">
                    <div class="activity-item">
                        <div class="activity-icon bg-success text-white">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="activity-text">New student <strong>John Doe</strong> enrolled in Class 10-A</div>
                            <div class="activity-time">2 hours ago</div>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon bg-primary text-white">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="activity-text">Attendance marked for Class 9-B</div>
                            <div class="activity-time">4 hours ago</div>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon bg-warning text-white">
                            <i class="fas fa-receipt"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="activity-text">Fee payment received from <strong>Sarah Smith</strong></div>
                            <div class="activity-time">6 hours ago</div>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon bg-info text-white">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="activity-text">Exam schedule updated for Class 12</div>
                            <div class="activity-time">1 day ago</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="notification-card">
                <div class="notification-header">
                    <h5 class="mb-0">
                        <i class="fas fa-bell text-warning me-2"></i>
                        Notifications
                    </h5>
                </div>
                <div class="notification-list">
                    <div class="notification-item">
                        <div class="notification-icon bg-danger text-white">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="notification-text">5 students have pending fee payments</div>
                            <div class="notification-time">Today</div>
                        </div>
                    </div>
                    <div class="notification-item">
                        <div class="notification-icon bg-info text-white">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="notification-text">Parent-teacher meeting scheduled for tomorrow</div>
                            <div class="notification-time">Tomorrow</div>
                        </div>
                    </div>
                    <div class="notification-item">
                        <div class="notification-icon bg-success text-white">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="notification-text">School won district science competition</div>
                            <div class="notification-time">2 days ago</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="summary-card mt-3">
                <h5 class="summary-title">
                    <i class="fas fa-calendar-day text-primary me-2"></i>
                    Today's Summary
                </h5>
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="summary-icon bg-success">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div class="summary-content">
                            <div class="summary-number">1,058</div>
                            <div class="summary-label">Present Today</div>
                        </div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-icon bg-danger">
                            <i class="fas fa-user-times"></i>
                        </div>
                        <div class="summary-content">
                            <div class="summary-number">189</div>
                            <div class="summary-label">Absent Today</div>
                        </div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-icon bg-warning">
                            <i class="fas fa-rupee-sign"></i>
                        </div>
                        <div class="summary-content">
                            <div class="summary-number">₹45,200</div>
                            <div class="summary-label">Fees Collected</div>
                        </div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-icon bg-info">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div class="summary-content">
                            <div class="summary-number">12</div>
                            <div class="summary-label">New Admissions</div>
                        </div>
                    </div>
                </div>
                <div class="current-time">
                    <i class="fas fa-clock me-2"></i>
                    <span id="currentTime"></span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Enrollment Trends Chart
    const enrollmentCtx = document.getElementById('enrollmentChart').getContext('2d');
    new Chart(enrollmentCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'New Enrollments',
                data: [45, 52, 38, 67, 73, 89, 95, 102, 87, 76, 69, 58],
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4
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
                    grid: {
                        color: 'rgba(0,0,0,0.05)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });

    // Animate counter numbers
    function animateCounter(element, target) {
        let current = 0;
        const increment = target / 100;
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            
            if (element.id === 'monthly-revenue') {
                element.textContent = '₹' + (current / 100000).toFixed(1) + 'L';
            } else {
                element.textContent = Math.floor(current).toLocaleString();
            }
        }, 20);
    }

    // Animate all counters
    setTimeout(() => {
        animateCounter(document.getElementById('total-students'), 1247);
        animateCounter(document.getElementById('total-teachers'), 89);
        animateCounter(document.getElementById('total-classes'), 42);
        animateCounter(document.getElementById('monthly-revenue'), 240000);
    }, 500);

    // Real-time clock update
    function updateClock() {
        const now = new Date();
        const timeElement = document.querySelector('.text-primary.fw-bold');
        if (timeElement) {
            timeElement.textContent = now.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
        }
    }

    setInterval(updateClock, 1000);
});
</script>
@endpush