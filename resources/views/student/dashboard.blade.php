@extends('layouts.app')

@section('title', 'Student Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-gradient-primary text-white">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h3 class="mb-1">Welcome back, {{ auth()->user()->name }}!</h3>
                            <p class="mb-0 opacity-75">{{ $class->name ?? 'Student' }} - {{ date('l, F j, Y') }}</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="d-flex justify-content-end gap-3">
                                <div class="text-center">
                                    <h4 class="mb-0">{{ $stats['pending_assignments'] ?? 0 }}</h4>
                                    <small class="opacity-75">Pending</small>
                                </div>
                                <div class="text-center">
                                    <h4 class="mb-0">{{ $stats['submitted_assignments'] ?? 0 }}</h4>
                                    <small class="opacity-75">Submitted</small>
                                </div>
                                <div class="text-center">
                                    <h4 class="mb-0">{{ number_format($stats['average_grade'] ?? 0, 1) }}%</h4>
                                    <small class="opacity-75">Avg Grade</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-left-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Assignments Due Today
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $stats['due_today'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Due This Week
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $stats['due_this_week'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-week fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-danger">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Overdue
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $stats['overdue'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Completed
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $stats['completed'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Upcoming Assignments -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Upcoming Assignments</h6>
                    <a href="{{ route('student.assignments') }}" class="btn btn-sm btn-outline-primary">
                        View All
                    </a>
                </div>
                <div class="card-body">
                    @forelse($upcoming_assignments as $assignment)
                        <div class="assignment-item border-bottom pb-3 mb-3">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h6 class="mb-1">
                                        <a href="{{ route('student.assignments.show', $assignment) }}" class="text-decoration-none">
                                            {{ $assignment->title }}
                                        </a>
                                    </h6>
                                    <div class="small text-muted mb-2">
                                        <span class="badge bg-primary me-2">{{ $assignment->subject->name ?? 'N/A' }}</span>
                                        <span class="badge bg-secondary">{{ ucfirst($assignment->type) }}</span>
                                        @if($assignment->total_marks)
                                            <span class="badge bg-info">{{ $assignment->total_marks }} marks</span>
                                        @endif
                                    </div>
                                    <p class="mb-0 small text-muted">
                                        {{ Str::limit($assignment->description, 100) }}
                                    </p>
                                </div>
                                <div class="col-md-4 text-end">
                                    <div class="mb-2">
                                        @if($assignment->due_date)
                                            <div class="small">
                                                <strong>Due:</strong> {{ $assignment->due_date->format('M d, Y') }}
                                                @if($assignment->due_time)
                                                    at {{ $assignment->due_time->format('H:i') }}
                                                @endif
                                            </div>
                                            @if($assignment->isOverdue())
                                                <span class="badge bg-danger">Overdue</span>
                                            @elseif($assignment->isDueSoon())
                                                <span class="badge bg-warning">Due Soon</span>
                                            @endif
                                        @endif
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('student.assignments.show', $assignment) }}" 
                                           class="btn btn-outline-primary">View</a>
                                        @if(!$assignment->submissions()->where('student_id', auth()->id())->exists())
                                            <a href="{{ route('student.assignments.submit', $assignment) }}" 
                                               class="btn btn-primary">Submit</a>
                                        @else
                                            <span class="btn btn-success btn-sm">Submitted</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4">
                            <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                            <h6 class="text-muted">No upcoming assignments</h6>
                            <p class="text-muted small">You're all caught up!</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Recent Submissions -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Submissions</h6>
                    <a href="{{ route('student.submissions') }}" class="btn btn-sm btn-outline-primary">
                        View All
                    </a>
                </div>
                <div class="card-body">
                    @forelse($recent_submissions as $submission)
                        <div class="submission-item border-bottom pb-3 mb-3">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h6 class="mb-1">{{ $submission->assignment->title }}</h6>
                                    <div class="small text-muted mb-2">
                                        <span class="badge bg-primary me-2">{{ $submission->assignment->subject->name ?? 'N/A' }}</span>
                                        <span class="badge bg-{{ $submission->status == 'graded' ? 'success' : ($submission->status == 'submitted' ? 'warning' : 'secondary') }}">
                                            {{ ucfirst($submission->status) }}
                                        </span>
                                        @if($submission->is_late)
                                            <span class="badge bg-danger">Late</span>
                                        @endif
                                    </div>
                                    <div class="small text-muted">
                                        Submitted: {{ $submission->submitted_at->format('M d, Y H:i') }}
                                    </div>
                                </div>
                                <div class="col-md-4 text-end">
                                    @if($submission->status == 'graded')
                                        <div class="mb-2">
                                            <h5 class="mb-0 text-{{ $submission->final_marks >= ($submission->assignment->total_marks * 0.7) ? 'success' : ($submission->final_marks >= ($submission->assignment->total_marks * 0.5) ? 'warning' : 'danger') }}">
                                                {{ $submission->final_marks }}/{{ $submission->assignment->total_marks }}
                                            </h5>
                                            <small class="text-muted">
                                                {{ number_format(($submission->final_marks / $submission->assignment->total_marks) * 100, 1) }}%
                                            </small>
                                        </div>
                                    @endif
                                    <a href="{{ route('student.submissions.show', $submission) }}" 
                                       class="btn btn-sm btn-outline-primary">View</a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4">
                            <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                            <h6 class="text-muted">No submissions yet</h6>
                            <p class="text-muted small">Start working on your assignments!</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Calendar Widget -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Assignment Calendar</h6>
                </div>
                <div class="card-body">
                    <div id="assignmentCalendar"></div>
                </div>
            </div>

            <!-- Quick Access -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Access</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('student.assignments') }}" class="btn btn-outline-primary">
                            <i class="fas fa-tasks me-2"></i>All Assignments
                        </a>
                        <a href="{{ route('student.syllabi') }}" class="btn btn-outline-info">
                            <i class="fas fa-book me-2"></i>Syllabi
                        </a>
                        <a href="{{ route('student.submissions') }}" class="btn btn-outline-success">
                            <i class="fas fa-file-alt me-2"></i>My Submissions
                        </a>
                        <a href="{{ route('student.progress') }}" class="btn btn-outline-warning">
                            <i class="fas fa-chart-line me-2"></i>Progress Report
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Syllabi -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Syllabi</h6>
                    <a href="{{ route('student.syllabi') }}" class="btn btn-sm btn-outline-primary">
                        View All
                    </a>
                </div>
                <div class="card-body">
                    @forelse($recent_syllabi as $syllabus)
                        <div class="syllabus-item border-bottom pb-2 mb-2">
                            <h6 class="mb-1">
                                <a href="{{ route('student.syllabi.show', $syllabus) }}" class="text-decoration-none">
                                    {{ $syllabus->subject }}
                                </a>
                            </h6>
                            <div class="small text-muted mb-1">
                                <span class="badge bg-info">{{ $syllabus->class->name ?? 'N/A' }}</span>
                            </div>
                            <div class="small text-muted">
                                Updated: {{ $syllabus->updated_at->format('M d, Y') }}
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-3">
                            <i class="fas fa-book fa-2x text-muted mb-2"></i>
                            <p class="text-muted small mb-0">No syllabi available</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}

.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}

.border-left-danger {
    border-left: 0.25rem solid #e74a3b !important;
}

.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}

.bg-gradient-primary {
    background: linear-gradient(87deg, #5e72e4 0, #825ee4 100%) !important;
}

.assignment-item:last-child,
.submission-item:last-child,
.syllabus-item:last-child {
    border-bottom: none !important;
    margin-bottom: 0 !important;
    padding-bottom: 0 !important;
}

.text-xs {
    font-size: 0.7rem;
}

.font-weight-bold {
    font-weight: 700 !important;
}

.text-gray-800 {
    color: #5a5c69 !important;
}

.text-gray-300 {
    color: #dddfeb !important;
}

#assignmentCalendar {
    height: 250px;
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize assignment calendar
    initializeCalendar();
    
    // Auto-refresh stats every 5 minutes
    setInterval(function() {
        refreshStats();
    }, 300000);
});

function initializeCalendar() {
    // Simple calendar implementation
    const today = new Date();
    const currentMonth = today.getMonth();
    const currentYear = today.getFullYear();
    
    // This would typically use a calendar library like FullCalendar
    $('#assignmentCalendar').html(`
        <div class="text-center">
            <h6>${getMonthName(currentMonth)} ${currentYear}</h6>
            <p class="text-muted small">Calendar integration coming soon</p>
        </div>
    `);
}

function getMonthName(monthIndex) {
    const months = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];
    return months[monthIndex];
}

function refreshStats() {
    // AJAX call to refresh dashboard stats
    $.get('{{ route("student.dashboard.stats") }}', function(data) {
        // Update stats if needed
    }).fail(function() {
        console.log('Failed to refresh stats');
    });
}
</script>
@endpush