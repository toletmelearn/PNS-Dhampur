@extends('layouts.app')

@section('title', 'Teacher Performance Analytics')

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">Teacher Performance Analytics</h2>
                    <p class="text-muted mb-0">Comprehensive analysis of teacher engagement and contribution to the Digital Learning Portal</p>
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
                            <label class="form-label">Department</label>
                            <select class="form-select" name="department">
                                <option value="">All Departments</option>
                                <option value="science">Science</option>
                                <option value="mathematics">Mathematics</option>
                                <option value="english">English</option>
                                <option value="social_studies">Social Studies</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Performance Level</label>
                            <select class="form-select" name="performance_level">
                                <option value="">All Levels</option>
                                <option value="excellent">Excellent (80%+)</option>
                                <option value="good">Good (60-79%)</option>
                                <option value="average">Average (40-59%)</option>
                                <option value="needs_improvement">Needs Improvement (<40%)</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Activity Level</label>
                            <select class="form-select" name="activity_level">
                                <option value="">All Activity Levels</option>
                                <option value="high">High Activity</option>
                                <option value="medium">Medium Activity</option>
                                <option value="low">Low Activity</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date Range</label>
                            <select class="form-select" name="date_range">
                                <option value="30">Last 30 Days</option>
                                <option value="90">Last 3 Months</option>
                                <option value="180">Last 6 Months</option>
                                <option value="365">Last Year</option>
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
                            <h3 class="mb-1">{{ $totalTeachers ?? 0 }}</h3>
                            <p class="mb-0">Total Teachers</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-chalkboard-teacher fa-2x opacity-75"></i>
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
                            <h3 class="mb-1">{{ $activeTeachers ?? 0 }}</h3>
                            <p class="mb-0">Active Teachers</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-user-check fa-2x opacity-75"></i>
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
                            <h3 class="mb-1">{{ number_format($averageEngagement ?? 0, 1) }}%</h3>
                            <p class="mb-0">Avg. Engagement</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-chart-line fa-2x opacity-75"></i>
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
                            <h3 class="mb-1">{{ $totalContributions ?? 0 }}</h3>
                            <p class="mb-0">Total Contributions</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-upload fa-2x opacity-75"></i>
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
                        <i class="fas fa-chart-pie text-primary me-2"></i>
                        Performance Distribution
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="performanceDistributionChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-doughnut text-success me-2"></i>
                        Activity Level Distribution
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="activityLevelChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Contribution Trends -->
    <div class="row mb-4">
        <div class="col-lg-8 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line text-info me-2"></i>
                        Monthly Contribution Trends
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="contributionTrendChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar text-warning me-2"></i>
                        Department Performance
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="departmentPerformanceChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Performers -->
    <div class="row mb-4">
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-trophy me-2"></i>
                        Top Performing Teachers
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Teacher</th>
                                    <th>Contributions</th>
                                    <th>Engagement</th>
                                    <th>Score</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topPerformers ?? [] as $index => $teacher)
                                <tr>
                                    <td>
                                        @if($index < 3)
                                            <i class="fas fa-medal text-{{ $index == 0 ? 'warning' : ($index == 1 ? 'secondary' : 'warning') }}"></i>
                                        @else
                                            <span class="badge bg-primary">{{ $index + 1 }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                                {{ substr($teacher->name, 0, 1) }}
                                            </div>
                                            <div>
                                                <strong>{{ Str::limit($teacher->name, 20) }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $teacher->department ?? 'N/A' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">{{ $teacher->total_contributions ?? 0 }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success">{{ number_format($teacher->engagement_rate ?? 0, 1) }}%</span>
                                    </td>
                                    <td>
                                        <strong class="text-success">{{ number_format($teacher->performance_score ?? 0, 1) }}</strong>
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
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Teachers Needing Support
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Teacher</th>
                                    <th>Last Activity</th>
                                    <th>Contributions</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($needsSupport ?? [] as $teacher)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center me-2">
                                                {{ substr($teacher->name, 0, 1) }}
                                            </div>
                                            <div>
                                                <strong>{{ Str::limit($teacher->name, 20) }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $teacher->department ?? 'N/A' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($teacher->last_activity)
                                            {{ \Carbon\Carbon::parse($teacher->last_activity)->diffForHumans() }}
                                        @else
                                            <span class="text-muted">Never</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $teacher->total_contributions ?? 0 }}</span>
                                    </td>
                                    <td>
                                        @if(($teacher->total_contributions ?? 0) == 0)
                                            <span class="badge bg-danger">Inactive</span>
                                        @else
                                            <span class="badge bg-warning">Low Activity</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="contactTeacher({{ $teacher->id }})">
                                            <i class="fas fa-envelope"></i>
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">
                                        All teachers are performing well!
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

    <!-- Detailed Teacher Performance -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list text-primary me-2"></i>
                        Detailed Teacher Performance
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="teacherPerformanceTable">
                            <thead>
                                <tr>
                                    <th>Teacher</th>
                                    <th>Department</th>
                                    <th>Assignments Created</th>
                                    <th>Syllabi Uploaded</th>
                                    <th>Student Engagement</th>
                                    <th>Avg. Grade Given</th>
                                    <th>Response Time</th>
                                    <th>Last Activity</th>
                                    <th>Performance Score</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($teacherPerformance ?? [] as $teacher)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                                {{ substr($teacher->name, 0, 1) }}
                                            </div>
                                            <div>
                                                <strong>{{ $teacher->name }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $teacher->employee_id ?? 'N/A' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $teacher->department ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge bg-primary">{{ $teacher->assignments_created ?? 0 }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ $teacher->syllabi_uploaded ?? 0 }}</span>
                                    </td>
                                    <td>
                                        @php
                                            $engagement = $teacher->student_engagement ?? 0;
                                        @endphp
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-{{ $engagement >= 70 ? 'success' : ($engagement >= 40 ? 'warning' : 'danger') }}" 
                                                 role="progressbar" 
                                                 style="width: {{ $engagement }}%" 
                                                 aria-valuenow="{{ $engagement }}" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                                {{ number_format($engagement, 1) }}%
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($teacher->average_grade)
                                            <span class="badge bg-{{ $teacher->average_grade >= 80 ? 'success' : ($teacher->average_grade >= 60 ? 'warning' : 'danger') }}">
                                                {{ number_format($teacher->average_grade, 1) }}%
                                            </span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($teacher->avg_response_time)
                                            {{ $teacher->avg_response_time }} hrs
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($teacher->last_activity)
                                            {{ \Carbon\Carbon::parse($teacher->last_activity)->diffForHumans() }}
                                        @else
                                            <span class="text-muted">Never</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $score = $teacher->performance_score ?? 0;
                                        @endphp
                                        <div class="d-flex align-items-center">
                                            <div class="progress me-2" style="width: 60px; height: 20px;">
                                                <div class="progress-bar bg-{{ $score >= 80 ? 'success' : ($score >= 60 ? 'warning' : 'danger') }}" 
                                                     role="progressbar" 
                                                     style="width: {{ $score }}%" 
                                                     aria-valuenow="{{ $score }}" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100">
                                                </div>
                                            </div>
                                            <span class="fw-bold">{{ number_format($score, 1) }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" onclick="viewTeacherDetails({{ $teacher->id }})" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-success" onclick="viewTeacherAnalytics({{ $teacher->id }})" title="View Analytics">
                                                <i class="fas fa-chart-bar"></i>
                                            </button>
                                            <button class="btn btn-outline-info" onclick="contactTeacher({{ $teacher->id }})" title="Contact">
                                                <i class="fas fa-envelope"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-4">
                                        <i class="fas fa-chalkboard-teacher fa-2x mb-2"></i>
                                        <br>No teacher data available
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

.fa-medal {
    font-size: 1.2em;
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
    // Performance Distribution Chart
    const performanceData = @json($performanceDistribution ?? []);
    const performanceCtx = document.getElementById('performanceDistributionChart').getContext('2d');
    new Chart(performanceCtx, {
        type: 'pie',
        data: {
            labels: ['Excellent (80%+)', 'Good (60-79%)', 'Average (40-59%)', 'Needs Improvement (<40%)'],
            datasets: [{
                data: [
                    performanceData.excellent || 0,
                    performanceData.good || 0,
                    performanceData.average || 0,
                    performanceData.needs_improvement || 0
                ],
                backgroundColor: ['#28a745', '#ffc107', '#17a2b8', '#dc3545']
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

    // Activity Level Chart
    const activityData = @json($activityDistribution ?? []);
    const activityCtx = document.getElementById('activityLevelChart').getContext('2d');
    new Chart(activityCtx, {
        type: 'doughnut',
        data: {
            labels: ['High Activity', 'Medium Activity', 'Low Activity', 'Inactive'],
            datasets: [{
                data: [
                    activityData.high || 0,
                    activityData.medium || 0,
                    activityData.low || 0,
                    activityData.inactive || 0
                ],
                backgroundColor: ['#28a745', '#ffc107', '#fd7e14', '#dc3545']
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

    // Contribution Trend Chart
    const contributionData = @json($contributionTrend ?? []);
    const contributionCtx = document.getElementById('contributionTrendChart').getContext('2d');
    new Chart(contributionCtx, {
        type: 'line',
        data: {
            labels: contributionData.map(item => `${item.year}-${String(item.month).padStart(2, '0')}`),
            datasets: [{
                label: 'Assignments',
                data: contributionData.map(item => item.assignments),
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                tension: 0.4,
                fill: false
            }, {
                label: 'Syllabi',
                data: contributionData.map(item => item.syllabi),
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
                    beginAtZero: true
                }
            }
        }
    });

    // Department Performance Chart
    const departmentData = @json($departmentPerformance ?? []);
    const departmentCtx = document.getElementById('departmentPerformanceChart').getContext('2d');
    new Chart(departmentCtx, {
        type: 'bar',
        data: {
            labels: departmentData.map(item => item.department),
            datasets: [{
                label: 'Avg. Performance',
                data: departmentData.map(item => item.avg_performance),
                backgroundColor: '#ffc107',
                borderColor: '#ffc107',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            scales: {
                x: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
}

function applyFilters() {
    const formData = new FormData(document.getElementById('filterForm'));
    const params = new URLSearchParams(formData);
    
    window.location.href = '{{ route("learning.admin.analytics.teachers") }}?' + params.toString();
}

function exportData() {
    const formData = new FormData(document.getElementById('filterForm'));
    formData.append('export', 'true');
    
    fetch('{{ route("learning.admin.export.teachers") }}', {
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
        a.download = 'teacher-performance-report.xlsx';
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
    })
    .catch(error => {
        console.error('Error exporting data:', error);
        alert('Error exporting data. Please try again.');
    });
}

function viewTeacherDetails(teacherId) {
    window.location.href = `/teachers/${teacherId}`;
}

function viewTeacherAnalytics(teacherId) {
    window.location.href = `/learning/teachers/${teacherId}/analytics`;
}

function contactTeacher(teacherId) {
    // Open contact modal or redirect to messaging system
    window.location.href = `/teachers/${teacherId}/contact`;
}
</script>
@endpush