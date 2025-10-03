@extends('layouts.app')

@section('title', 'Assignment Analytics')

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">Assignment Analytics</h2>
                    <p class="text-muted mb-0">Comprehensive analysis of assignment distribution, performance, and trends</p>
                </div>
                <div>
                    <a href="{{ route('learning.admin.analytics.dashboard') }}" class="btn btn-outline-secondary me-2">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                    <button class="btn btn-primary" onclick="exportData()">
                        <i class="fas fa-download me-2"></i>Export Data
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
                        <div class="col-md-3">
                            <label class="form-label">Date Range</label>
                            <select class="form-select" name="date_range">
                                <option value="last_30_days">Last 30 Days</option>
                                <option value="last_3_months">Last 3 Months</option>
                                <option value="last_6_months">Last 6 Months</option>
                                <option value="last_year">Last Year</option>
                                <option value="custom">Custom Range</option>
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
                            <label class="form-label">Class</label>
                            <select class="form-select" name="class_id">
                                <option value="">All Classes</option>
                                @foreach($classes ?? [] as $class)
                                    <option value="{{ $class->id }}">{{ $class->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Teacher</label>
                            <select class="form-select" name="teacher_id">
                                <option value="">All Teachers</option>
                                @foreach($teachers ?? [] as $teacher)
                                    <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="">All Status</option>
                                <option value="published">Published</option>
                                <option value="draft">Draft</option>
                                <option value="overdue">Overdue</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3 class="mb-1">{{ $assignmentsBySubject->sum('total_assignments') ?? 0 }}</h3>
                            <p class="mb-0">Total Assignments</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-tasks fa-2x opacity-75"></i>
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
                            <h3 class="mb-1">{{ $publishedCount ?? 0 }}</h3>
                            <p class="mb-0">Published</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle fa-2x opacity-75"></i>
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
                            <h3 class="mb-1">{{ $draftCount ?? 0 }}</h3>
                            <p class="mb-0">Drafts</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-edit fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3 class="mb-1">{{ $overdueCount ?? 0 }}</h3>
                            <p class="mb-0">Overdue</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-exclamation-triangle fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="row mb-4">
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie text-primary me-2"></i>
                        Assignments by Subject
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="assignmentsBySubjectChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar text-success me-2"></i>
                        Assignments by Class
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="assignmentsByClassChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-8 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line text-info me-2"></i>
                        Monthly Assignment Trends
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="monthlyTrendChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-doughnut text-warning me-2"></i>
                        Assignment Types
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="assignmentTypesChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Performing Assignments -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-trophy text-warning me-2"></i>
                        Top Performing Assignments (by Submission Rate)
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Assignment Title</th>
                                    <th>Subject</th>
                                    <th>Class</th>
                                    <th>Teacher</th>
                                    <th>Total Students</th>
                                    <th>Submissions</th>
                                    <th>Submission Rate</th>
                                    <th>Average Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topAssignments ?? [] as $assignment)
                                <tr>
                                    <td>
                                        <strong>{{ $assignment->title }}</strong>
                                        <br>
                                        <small class="text-muted">Due: {{ $assignment->due_datetime->format('M d, Y') }}</small>
                                    </td>
                                    <td>{{ $assignment->subject->name ?? 'N/A' }}</td>
                                    <td>{{ $assignment->class->name ?? 'N/A' }}</td>
                                    <td>{{ $assignment->teacher->name ?? 'N/A' }}</td>
                                    <td>{{ $assignment->total_students ?? 0 }}</td>
                                    <td>
                                        <span class="badge bg-primary">{{ $assignment->submissions_count ?? 0 }}</span>
                                    </td>
                                    <td>
                                        @php
                                            $rate = $assignment->total_students > 0 ? 
                                                round(($assignment->submissions_count / $assignment->total_students) * 100, 1) : 0;
                                        @endphp
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-success" role="progressbar" 
                                                 style="width: {{ $rate }}%" 
                                                 aria-valuenow="{{ $rate }}" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                                {{ $rate }}%
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($assignment->average_grade)
                                            <span class="badge bg-{{ $assignment->average_grade >= 80 ? 'success' : ($assignment->average_grade >= 60 ? 'warning' : 'danger') }}">
                                                {{ round($assignment->average_grade, 1) }}%
                                            </span>
                                        @else
                                            <span class="text-muted">Not graded</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-2x mb-2"></i>
                                        <br>No assignments found
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

    <!-- Assignment Calendar -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar text-primary me-2"></i>
                        Assignment Calendar
                    </h5>
                </div>
                <div class="card-body">
                    <div id="assignmentCalendar"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css" rel="stylesheet">
<style>
.card {
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
}

.progress {
    background-color: #e9ecef;
}

.fc-event {
    cursor: pointer;
}

.fc-event-title {
    font-weight: 500;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
    initializeCalendar();
    
    // Filter form submission
    document.getElementById('filterForm').addEventListener('submit', function(e) {
        e.preventDefault();
        applyFilters();
    });
});

function initializeCharts() {
    // Assignments by Subject Chart
    const subjectData = @json($assignmentsBySubject ?? []);
    const subjectCtx = document.getElementById('assignmentsBySubjectChart').getContext('2d');
    new Chart(subjectCtx, {
        type: 'pie',
        data: {
            labels: subjectData.map(item => item.subject_name),
            datasets: [{
                data: subjectData.map(item => item.total_assignments),
                backgroundColor: [
                    '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                    '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
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

    // Assignments by Class Chart
    const classData = @json($assignmentsByClass ?? []);
    const classCtx = document.getElementById('assignmentsByClassChart').getContext('2d');
    new Chart(classCtx, {
        type: 'bar',
        data: {
            labels: classData.map(item => item.class_name),
            datasets: [{
                label: 'Total Assignments',
                data: classData.map(item => item.total_assignments),
                backgroundColor: '#36A2EB',
                borderColor: '#36A2EB',
                borderWidth: 1
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

    // Monthly Trend Chart
    const monthlyData = @json($monthlyTrend ?? []);
    const trendCtx = document.getElementById('monthlyTrendChart').getContext('2d');
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: monthlyData.map(item => `${item.year}-${String(item.month).padStart(2, '0')}`),
            datasets: [{
                label: 'Assignments Created',
                data: monthlyData.map(item => item.total_assignments),
                borderColor: '#4BC0C0',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                tension: 0.4,
                fill: true
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

    // Assignment Types Chart
    const typeData = @json($assignmentsByType ?? []);
    const typeCtx = document.getElementById('assignmentTypesChart').getContext('2d');
    new Chart(typeCtx, {
        type: 'doughnut',
        data: {
            labels: typeData.map(item => item.type),
            datasets: [{
                data: typeData.map(item => item.total_assignments),
                backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0']
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

function initializeCalendar() {
    const calendarEl = document.getElementById('assignmentCalendar');
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,listWeek'
        },
        events: function(fetchInfo, successCallback, failureCallback) {
            fetch('/learning/admin/analytics/assignment-calendar?' + new URLSearchParams({
                start: fetchInfo.startStr,
                end: fetchInfo.endStr
            }))
            .then(response => response.json())
            .then(data => {
                successCallback(data.events || []);
            })
            .catch(error => {
                console.error('Error loading calendar events:', error);
                failureCallback(error);
            });
        },
        eventClick: function(info) {
            // Show assignment details
            showAssignmentDetails(info.event.id);
        },
        eventDidMount: function(info) {
            // Add tooltips
            info.el.setAttribute('title', info.event.extendedProps.description || info.event.title);
        }
    });
    
    calendar.render();
}

function applyFilters() {
    const formData = new FormData(document.getElementById('filterForm'));
    const params = new URLSearchParams(formData);
    
    // Reload page with filters
    window.location.href = '{{ route("learning.admin.analytics.assignments") }}?' + params.toString();
}

function exportData() {
    const formData = new FormData(document.getElementById('filterForm'));
    formData.append('export', 'true');
    
    fetch('{{ route("learning.admin.export.assignments") }}', {
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
        a.download = 'assignment-analytics.xlsx';
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
    })
    .catch(error => {
        console.error('Error exporting data:', error);
        alert('Error exporting data. Please try again.');
    });
}

function showAssignmentDetails(assignmentId) {
    // Implement assignment details modal
    fetch(`/learning/assignments/${assignmentId}`)
        .then(response => response.json())
        .then(data => {
            // Show modal with assignment details
            console.log('Assignment details:', data);
        })
        .catch(error => console.error('Error loading assignment details:', error));
}
</script>
@endpush