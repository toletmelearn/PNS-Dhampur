@extends('layouts.app')

@section('title', 'Syllabus Analytics')

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">Syllabus Analytics</h2>
                    <p class="text-muted mb-0">Comprehensive analysis of syllabus distribution, usage, and engagement</p>
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
                            <label class="form-label">File Type</label>
                            <select class="form-select" name="file_type">
                                <option value="">All Types</option>
                                <option value="pdf">PDF</option>
                                <option value="doc">DOC/DOCX</option>
                                <option value="ppt">PPT/PPTX</option>
                                <option value="other">Other</option>
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
                            <h3 class="mb-1">{{ $totalSyllabi ?? 0 }}</h3>
                            <p class="mb-0">Total Syllabi</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-book fa-2x opacity-75"></i>
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
                            <h3 class="mb-1">{{ $totalViews ?? 0 }}</h3>
                            <p class="mb-0">Total Views</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-eye fa-2x opacity-75"></i>
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
                            <h3 class="mb-1">{{ $totalDownloads ?? 0 }}</h3>
                            <p class="mb-0">Total Downloads</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-download fa-2x opacity-75"></i>
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
    </div>

    <!-- Distribution Charts -->
    <div class="row mb-4">
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie text-primary me-2"></i>
                        Syllabi by Subject
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="syllabiBySubjectChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-doughnut text-success me-2"></i>
                        File Type Distribution
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="fileTypeChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Usage Trends -->
    <div class="row mb-4">
        <div class="col-lg-8 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line text-info me-2"></i>
                        Monthly Usage Trends
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="usageTrendChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar text-warning me-2"></i>
                        Top Classes by Usage
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="classUsageChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Most Popular Syllabi -->
    <div class="row mb-4">
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-star me-2"></i>
                        Most Viewed Syllabi
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Title</th>
                                    <th>Subject</th>
                                    <th>Views</th>
                                    <th>Downloads</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($mostViewed ?? [] as $index => $syllabus)
                                <tr>
                                    <td>
                                        <span class="badge bg-warning text-dark">{{ $index + 1 }}</span>
                                    </td>
                                    <td>
                                        <strong>{{ Str::limit($syllabus->title, 30) }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $syllabus->class->name ?? 'N/A' }}</small>
                                    </td>
                                    <td>{{ $syllabus->subject->name ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge bg-primary">{{ $syllabus->views_count ?? 0 }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success">{{ $syllabus->downloads_count ?? 0 }}</span>
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
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-download me-2"></i>
                        Most Downloaded Syllabi
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Title</th>
                                    <th>Subject</th>
                                    <th>Downloads</th>
                                    <th>Size</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($mostDownloaded ?? [] as $index => $syllabus)
                                <tr>
                                    <td>
                                        <span class="badge bg-warning text-dark">{{ $index + 1 }}</span>
                                    </td>
                                    <td>
                                        <strong>{{ Str::limit($syllabus->title, 30) }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $syllabus->class->name ?? 'N/A' }}</small>
                                    </td>
                                    <td>{{ $syllabus->subject->name ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge bg-success">{{ $syllabus->downloads_count ?? 0 }}</span>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $syllabus->file_size_formatted ?? 'N/A' }}</small>
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
    </div>

    <!-- Teacher Performance -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chalkboard-teacher text-primary me-2"></i>
                        Teacher Syllabus Contribution
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Teacher</th>
                                    <th>Total Syllabi</th>
                                    <th>Published</th>
                                    <th>Total Views</th>
                                    <th>Total Downloads</th>
                                    <th>Avg. Engagement</th>
                                    <th>Last Upload</th>
                                    <th>Performance</th>
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
                                    <td>{{ $teacher->total_syllabi ?? 0 }}</td>
                                    <td>
                                        <span class="badge bg-success">{{ $teacher->published_syllabi ?? 0 }}</span>
                                    </td>
                                    <td>{{ $teacher->total_views ?? 0 }}</td>
                                    <td>{{ $teacher->total_downloads ?? 0 }}</td>
                                    <td>
                                        @php
                                            $engagement = $teacher->average_engagement ?? 0;
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
                                        @if($teacher->last_upload)
                                            {{ \Carbon\Carbon::parse($teacher->last_upload)->diffForHumans() }}
                                        @else
                                            <span class="text-muted">No uploads</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($engagement >= 70)
                                            <span class="badge bg-success">Excellent</span>
                                        @elseif($engagement >= 40)
                                            <span class="badge bg-warning">Good</span>
                                        @else
                                            <span class="badge bg-danger">Needs Improvement</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
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

    <!-- Detailed Syllabus List -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list text-primary me-2"></i>
                        Detailed Syllabus Analytics
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="syllabusAnalyticsTable">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Subject</th>
                                    <th>Class</th>
                                    <th>Teacher</th>
                                    <th>File Type</th>
                                    <th>Views</th>
                                    <th>Downloads</th>
                                    <th>Engagement Rate</th>
                                    <th>Upload Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($syllabusAnalytics ?? [] as $syllabus)
                                <tr>
                                    <td>
                                        <div>
                                            <strong>{{ Str::limit($syllabus->title, 40) }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $syllabus->description ? Str::limit($syllabus->description, 50) : 'No description' }}</small>
                                        </div>
                                    </td>
                                    <td>{{ $syllabus->subject->name ?? 'N/A' }}</td>
                                    <td>{{ $syllabus->class->name ?? 'N/A' }}</td>
                                    <td>{{ $syllabus->teacher->name ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge bg-secondary">{{ strtoupper($syllabus->file_type ?? 'N/A') }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">{{ $syllabus->views_count ?? 0 }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success">{{ $syllabus->downloads_count ?? 0 }}</span>
                                    </td>
                                    <td>
                                        @php
                                            $totalStudents = $syllabus->class->students_count ?? 1;
                                            $engagementRate = $totalStudents > 0 ? 
                                                round((($syllabus->views_count ?? 0) / $totalStudents) * 100, 1) : 0;
                                        @endphp
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-{{ $engagementRate >= 70 ? 'success' : ($engagementRate >= 40 ? 'warning' : 'danger') }}" 
                                                 role="progressbar" 
                                                 style="width: {{ min($engagementRate, 100) }}%" 
                                                 aria-valuenow="{{ $engagementRate }}" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                                {{ $engagementRate }}%
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        {{ $syllabus->created_at ? $syllabus->created_at->format('M d, Y') : 'N/A' }}
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" onclick="viewSyllabusDetails({{ $syllabus->id }})" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-info" onclick="downloadSyllabus({{ $syllabus->id }})" title="Download">
                                                <i class="fas fa-download"></i>
                                            </button>
                                            <button class="btn btn-outline-success" onclick="viewAnalytics({{ $syllabus->id }})" title="View Analytics">
                                                <i class="fas fa-chart-bar"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-4">
                                        <i class="fas fa-book fa-2x mb-2"></i>
                                        <br>No syllabi found
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
    // Syllabi by Subject Chart
    const subjectData = @json($syllabiBySubject ?? []);
    const subjectCtx = document.getElementById('syllabiBySubjectChart').getContext('2d');
    new Chart(subjectCtx, {
        type: 'pie',
        data: {
            labels: subjectData.map(item => item.subject_name),
            datasets: [{
                data: subjectData.map(item => item.total_syllabi),
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

    // File Type Chart
    const fileTypeData = @json($fileTypeDistribution ?? []);
    const fileTypeCtx = document.getElementById('fileTypeChart').getContext('2d');
    new Chart(fileTypeCtx, {
        type: 'doughnut',
        data: {
            labels: fileTypeData.map(item => item.file_type.toUpperCase()),
            datasets: [{
                data: fileTypeData.map(item => item.count),
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

    // Usage Trend Chart
    const usageData = @json($usageTrend ?? []);
    const usageCtx = document.getElementById('usageTrendChart').getContext('2d');
    new Chart(usageCtx, {
        type: 'line',
        data: {
            labels: usageData.map(item => `${item.year}-${String(item.month).padStart(2, '0')}`),
            datasets: [{
                label: 'Views',
                data: usageData.map(item => item.total_views),
                borderColor: '#17a2b8',
                backgroundColor: 'rgba(23, 162, 184, 0.1)',
                tension: 0.4,
                fill: false
            }, {
                label: 'Downloads',
                data: usageData.map(item => item.total_downloads),
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

    // Class Usage Chart
    const classData = @json($classUsage ?? []);
    const classCtx = document.getElementById('classUsageChart').getContext('2d');
    new Chart(classCtx, {
        type: 'bar',
        data: {
            labels: classData.map(item => item.class_name),
            datasets: [{
                label: 'Total Usage',
                data: classData.map(item => item.total_usage),
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
                    beginAtZero: true
                }
            }
        }
    });
}

function applyFilters() {
    const formData = new FormData(document.getElementById('filterForm'));
    const params = new URLSearchParams(formData);
    
    window.location.href = '{{ route("learning.admin.analytics.syllabus") }}?' + params.toString();
}

function exportData() {
    const formData = new FormData(document.getElementById('filterForm'));
    formData.append('export', 'true');
    
    fetch('{{ route("learning.admin.export.syllabus") }}', {
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
        a.download = 'syllabus-analytics-report.xlsx';
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
    })
    .catch(error => {
        console.error('Error exporting data:', error);
        alert('Error exporting data. Please try again.');
    });
}

function viewSyllabusDetails(syllabusId) {
    window.location.href = `/learning/syllabi/${syllabusId}`;
}

function downloadSyllabus(syllabusId) {
    window.location.href = `/learning/syllabi/${syllabusId}/download`;
}

function viewAnalytics(syllabusId) {
    window.location.href = `/learning/syllabi/${syllabusId}/analytics`;
}
</script>
@endpush