@extends('layouts.app')

@section('title', 'Student Performance Analytics')

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">Student Performance Analytics</h2>
                    <p class="text-muted mb-0">Comprehensive analysis of student performance, engagement, and progress</p>
                </div>
                <div>
                    <a href="{{ route('learning.admin.analytics.dashboard') }}" class="btn btn-outline-secondary me-2">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                    <button class="btn btn-primary" onclick="exportData()">
                        <i class="fas fa-download me-2"></i>Export Report
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form id="filterForm" class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label">Academic Year</label>
                            <select class="form-select" name="academic_year">
                                <option value="">Current Year</option>
                                <option value="2023-24">2023-24</option>
                                <option value="2022-23">2022-23</option>
                                <option value="2021-22">2021-22</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Class</label>
                            <select class="form-select" name="class_id">
                                <option value="">All Classes</option>
                                @foreach($classes ?? [] as $class)
                                    <option value="{{ $class->id }}">{{ $class->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Subject</label>
                            <select class="form-select" name="subject_id">
                                <option value="">All Subjects</option>
                                @foreach($subjects ?? [] as $subject)
                                    <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Performance Level</label>
                            <select class="form-select" name="performance_level">
                                <option value="">All Levels</option>
                                <option value="excellent">Excellent (90-100%)</option>
                                <option value="good">Good (75-89%)</option>
                                <option value="average">Average (60-74%)</option>
                                <option value="below_average">Below Average (<60%)</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Engagement Level</label>
                            <select class="form-select" name="engagement_level">
                                <option value="">All Levels</option>
                                <option value="high">High (>80% submissions)</option>
                                <option value="medium">Medium (50-80%)</option>
                                <option value="low">Low (<50%)</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-2"></i>Apply Filters
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
            <div class="card border-0 shadow-sm bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3 class="mb-1">{{ $totalStudents ?? 0 }}</h3>
                            <p class="mb-0">Total Students</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-users fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3 class="mb-1">{{ number_format($averageGrade ?? 0, 1) }}%</h3>
                            <p class="mb-0">Average Grade</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-chart-line fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3 class="mb-1">{{ number_format($submissionRate ?? 0, 1) }}%</h3>
                            <p class="mb-0">Submission Rate</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-paper-plane fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3 class="mb-1">{{ $atRiskStudents ?? 0 }}</h3>
                            <p class="mb-0">At-Risk Students</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-exclamation-triangle fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Distribution Charts -->
    <div class="row mb-4">
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar text-primary me-2"></i>
                        Grade Distribution
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="gradeDistributionChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie text-success me-2"></i>
                        Performance by Subject
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="subjectPerformanceChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Engagement and Progress Charts -->
    <div class="row mb-4">
        <div class="col-lg-8 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line text-info me-2"></i>
                        Monthly Performance Trends
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="performanceTrendChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-doughnut text-warning me-2"></i>
                        Engagement Levels
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="engagementChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Performers and At-Risk Students -->
    <div class="row mb-4">
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-trophy me-2"></i>
                        Top Performers
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Student</th>
                                    <th>Class</th>
                                    <th>Average Grade</th>
                                    <th>Submissions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topPerformers ?? [] as $index => $student)
                                <tr>
                                    <td>
                                        <span class="badge bg-warning text-dark">{{ $index + 1 }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                                {{ substr($student->name, 0, 1) }}
                                            </div>
                                            <div>
                                                <strong>{{ $student->name }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $student->roll_number }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $student->class->name ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge bg-success">{{ number_format($student->average_grade, 1) }}%</span>
                                    </td>
                                    <td>
                                        <span class="text-success">{{ $student->submissions_count }}/{{ $student->total_assignments }}</span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">
                                        No data available
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Students Needing Attention
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Class</th>
                                    <th>Average Grade</th>
                                    <th>Missing</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($atRiskStudentsList ?? [] as $student)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-danger text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                                {{ substr($student->name, 0, 1) }}
                                            </div>
                                            <div>
                                                <strong>{{ $student->name }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $student->roll_number }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $student->class->name ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge bg-danger">{{ number_format($student->average_grade ?? 0, 1) }}%</span>
                                    </td>
                                    <td>
                                        <span class="text-danger">{{ $student->missing_assignments ?? 0 }}</span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="contactParent({{ $student->id }})">
                                            <i class="fas fa-phone"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-info" onclick="viewDetails({{ $student->id }})">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">
                                        No students at risk
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

    <!-- Class Performance Comparison -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar text-primary me-2"></i>
                        Class Performance Comparison
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="classComparisonChart" height="400"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Student List -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list text-primary me-2"></i>
                        Detailed Student Performance
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="studentPerformanceTable">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Class</th>
                                    <th>Total Assignments</th>
                                    <th>Submitted</th>
                                    <th>Submission Rate</th>
                                    <th>Average Grade</th>
                                    <th>Last Activity</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($studentPerformance ?? [] as $student)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-{{ $student->performance_color ?? 'secondary' }} text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                                {{ substr($student->name, 0, 1) }}
                                            </div>
                                            <div>
                                                <strong>{{ $student->name }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $student->roll_number }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $student->class->name ?? 'N/A' }}</td>
                                    <td>{{ $student->total_assignments ?? 0 }}</td>
                                    <td>{{ $student->submitted_assignments ?? 0 }}</td>
                                    <td>
                                        @php
                                            $rate = $student->total_assignments > 0 ? 
                                                round(($student->submitted_assignments / $student->total_assignments) * 100, 1) : 0;
                                        @endphp
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-{{ $rate >= 80 ? 'success' : ($rate >= 60 ? 'warning' : 'danger') }}" 
                                                 role="progressbar" 
                                                 style="width: {{ $rate }}%" 
                                                 aria-valuenow="{{ $rate }}" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                                {{ $rate }}%
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ ($student->average_grade ?? 0) >= 80 ? 'success' : (($student->average_grade ?? 0) >= 60 ? 'warning' : 'danger') }}">
                                            {{ number_format($student->average_grade ?? 0, 1) }}%
                                        </span>
                                    </td>
                                    <td>
                                        @if($student->last_activity)
                                            {{ \Carbon\Carbon::parse($student->last_activity)->diffForHumans() }}
                                        @else
                                            <span class="text-muted">No activity</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(($student->average_grade ?? 0) >= 80 && $rate >= 80)
                                            <span class="badge bg-success">Excellent</span>
                                        @elseif(($student->average_grade ?? 0) >= 60 && $rate >= 60)
                                            <span class="badge bg-warning">Good</span>
                                        @else
                                            <span class="badge bg-danger">Needs Attention</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" onclick="viewStudentDetails({{ $student->id }})" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-success" onclick="sendEncouragement({{ $student->id }})" title="Send Encouragement">
                                                <i class="fas fa-thumbs-up"></i>
                                            </button>
                                            <button class="btn btn-outline-info" onclick="contactParent({{ $student->id }})" title="Contact Parent">
                                                <i class="fas fa-phone"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        <i class="fas fa-users fa-2x mb-2"></i>
                                        <br>No students found
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
@endsection

@push('styles')
<style>
.avatar-sm {
    width: 32px;
    height: 32px;
    font-size: 14px;
    font-weight: 600;
}

.card {
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
}

.progress {
    background-color: #e9ecef;
}

.table th {
    border-top: none;
    font-weight: 600;
    color: #495057;
}

.btn-group-sm > .btn {
    padding: 0.25rem 0.5rem;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
    
    // Filter form submission
    document.getElementById('filterForm').addEventListener('submit', function(e) {
        e.preventDefault();
        applyFilters();
    });
});

function initializeCharts() {
    // Grade Distribution Chart
    const gradeData = @json($gradeDistribution ?? []);
    const gradeCtx = document.getElementById('gradeDistributionChart').getContext('2d');
    new Chart(gradeCtx, {
        type: 'bar',
        data: {
            labels: ['90-100%', '80-89%', '70-79%', '60-69%', '50-59%', 'Below 50%'],
            datasets: [{
                label: 'Number of Students',
                data: [
                    gradeData.excellent || 0,
                    gradeData.very_good || 0,
                    gradeData.good || 0,
                    gradeData.average || 0,
                    gradeData.below_average || 0,
                    gradeData.poor || 0
                ],
                backgroundColor: [
                    '#28a745', '#20c997', '#ffc107', '#fd7e14', '#dc3545', '#6c757d'
                ]
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

    // Subject Performance Chart
    const subjectData = @json($subjectPerformance ?? []);
    const subjectCtx = document.getElementById('subjectPerformanceChart').getContext('2d');
    new Chart(subjectCtx, {
        type: 'radar',
        data: {
            labels: subjectData.map(item => item.subject_name),
            datasets: [{
                label: 'Average Grade',
                data: subjectData.map(item => item.average_grade),
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                pointBackgroundColor: 'rgba(54, 162, 235, 1)',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: 'rgba(54, 162, 235, 1)'
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

    // Performance Trend Chart
    const trendData = @json($performanceTrend ?? []);
    const trendCtx = document.getElementById('performanceTrendChart').getContext('2d');
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: trendData.map(item => `${item.year}-${String(item.month).padStart(2, '0')}`),
            datasets: [{
                label: 'Average Grade',
                data: trendData.map(item => item.average_grade),
                borderColor: '#17a2b8',
                backgroundColor: 'rgba(23, 162, 184, 0.1)',
                tension: 0.4,
                fill: true
            }, {
                label: 'Submission Rate',
                data: trendData.map(item => item.submission_rate),
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                tension: 0.4,
                fill: false
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

    // Engagement Chart
    const engagementData = @json($engagementLevels ?? []);
    const engagementCtx = document.getElementById('engagementChart').getContext('2d');
    new Chart(engagementCtx, {
        type: 'doughnut',
        data: {
            labels: ['High Engagement', 'Medium Engagement', 'Low Engagement'],
            datasets: [{
                data: [
                    engagementData.high || 0,
                    engagementData.medium || 0,
                    engagementData.low || 0
                ],
                backgroundColor: ['#28a745', '#ffc107', '#dc3545']
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

    // Class Comparison Chart
    const classData = @json($classComparison ?? []);
    const classCtx = document.getElementById('classComparisonChart').getContext('2d');
    new Chart(classCtx, {
        type: 'bar',
        data: {
            labels: classData.map(item => item.class_name),
            datasets: [{
                label: 'Average Grade',
                data: classData.map(item => item.average_grade),
                backgroundColor: '#36A2EB',
                yAxisID: 'y'
            }, {
                label: 'Submission Rate',
                data: classData.map(item => item.submission_rate),
                backgroundColor: '#4BC0C0',
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    beginAtZero: true,
                    max: 100
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    beginAtZero: true,
                    max: 100,
                    grid: {
                        drawOnChartArea: false,
                    },
                }
            }
        }
    });
}

function applyFilters() {
    const formData = new FormData(document.getElementById('filterForm'));
    const params = new URLSearchParams(formData);
    
    window.location.href = '{{ route("learning.admin.analytics.students") }}?' + params.toString();
}

function exportData() {
    const formData = new FormData(document.getElementById('filterForm'));
    formData.append('export', 'true');
    
    fetch('{{ route("learning.admin.export.students") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.blob())
    .then(blob => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.style.display = 'none';
        a.href = url;
        a.download = 'student-performance-report.xlsx';
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
    })
    .catch(error => {
        console.error('Error exporting data:', error);
        alert('Error exporting data. Please try again.');
    });
}

function viewStudentDetails(studentId) {
    window.location.href = `/learning/students/${studentId}/performance`;
}

function sendEncouragement(studentId) {
    if (confirm('Send encouragement message to this student?')) {
        fetch(`/learning/students/${studentId}/encourage`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Encouragement message sent successfully!');
            } else {
                alert('Error sending message. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error sending message. Please try again.');
        });
    }
}

function contactParent(studentId) {
    window.location.href = `/learning/students/${studentId}/contact-parent`;
}
</script>
@endpush