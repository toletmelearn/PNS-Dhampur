@extends('layouts.app')

@section('title', 'My Assignments')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">My Assignments</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('student.dashboard') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
            </a>
            <a href="{{ route('student.assignments.calendar') }}" class="btn btn-outline-primary">
                <i class="fas fa-calendar me-1"></i>Calendar View
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-left-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Assignments
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $stats['total'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tasks fa-2x text-gray-300"></i>
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
                                Pending
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $stats['pending'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
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
                                Submitted
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $stats['submitted'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check fa-2x text-gray-300"></i>
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
    </div>

    <!-- Filters and Search -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('student.assignments') }}" id="filterForm">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="{{ request('search') }}" placeholder="Search assignments...">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="subject" class="form-label">Subject</label>
                            <select class="form-control" id="subject" name="subject">
                                <option value="">All Subjects</option>
                                @foreach($subjects as $subject)
                                    <option value="{{ $subject->id }}" {{ request('subject') == $subject->id ? 'selected' : '' }}>
                                        {{ $subject->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="type" class="form-label">Type</label>
                            <select class="form-control" id="type" name="type">
                                <option value="">All Types</option>
                                <option value="homework" {{ request('type') == 'homework' ? 'selected' : '' }}>Homework</option>
                                <option value="project" {{ request('type') == 'project' ? 'selected' : '' }}>Project</option>
                                <option value="quiz" {{ request('type') == 'quiz' ? 'selected' : '' }}>Quiz</option>
                                <option value="exam" {{ request('type') == 'exam' ? 'selected' : '' }}>Exam</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="">All Status</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="submitted" {{ request('status') == 'submitted' ? 'selected' : '' }}>Submitted</option>
                                <option value="graded" {{ request('status') == 'graded' ? 'selected' : '' }}>Graded</option>
                                <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Overdue</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="sort" class="form-label">Sort By</label>
                            <select class="form-control" id="sort" name="sort">
                                <option value="due_date" {{ request('sort') == 'due_date' ? 'selected' : '' }}>Due Date</option>
                                <option value="created_at" {{ request('sort') == 'created_at' ? 'selected' : '' }}>Created Date</option>
                                <option value="title" {{ request('sort') == 'title' ? 'selected' : '' }}>Title</option>
                                <option value="subject" {{ request('sort') == 'subject' ? 'selected' : '' }}>Subject</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-flex gap-1">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fas fa-search"></i>
                                </button>
                                <a href="{{ route('student.assignments') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-times"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Assignments List -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                Assignments 
                @if($assignments->total() > 0)
                    ({{ $assignments->firstItem() }}-{{ $assignments->lastItem() }} of {{ $assignments->total() }})
                @endif
            </h6>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" 
                        data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-cog me-1"></i>Options
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#" onclick="exportAssignments('pdf')">
                        <i class="fas fa-file-pdf me-2"></i>Export as PDF
                    </a></li>
                    <li><a class="dropdown-item" href="#" onclick="exportAssignments('excel')">
                        <i class="fas fa-file-excel me-2"></i>Export as Excel
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="{{ route('student.assignments.calendar') }}">
                        <i class="fas fa-calendar me-2"></i>Calendar View
                    </a></li>
                </ul>
            </div>
        </div>
        <div class="card-body p-0">
            @forelse($assignments as $assignment)
                <div class="assignment-row border-bottom p-3 {{ $assignment->isOverdue() && !$assignment->hasSubmission(auth()->id()) ? 'bg-light-danger' : '' }}">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <div class="d-flex align-items-start">
                                <div class="assignment-icon me-3">
                                    <i class="fas fa-{{ $assignment->type == 'quiz' ? 'question-circle' : ($assignment->type == 'exam' ? 'graduation-cap' : ($assignment->type == 'project' ? 'project-diagram' : 'file-alt')) }} fa-2x text-{{ $assignment->isOverdue() && !$assignment->hasSubmission(auth()->id()) ? 'danger' : 'primary' }}"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">
                                        <a href="{{ route('student.assignments.show', $assignment) }}" 
                                           class="text-decoration-none text-dark">
                                            {{ $assignment->title }}
                                        </a>
                                    </h6>
                                    <div class="mb-2">
                                        <span class="badge bg-primary me-1">{{ $assignment->subject->name ?? 'N/A' }}</span>
                                        <span class="badge bg-secondary me-1">{{ ucfirst($assignment->type) }}</span>
                                        @if($assignment->total_marks)
                                            <span class="badge bg-info me-1">{{ $assignment->total_marks }} marks</span>
                                        @endif
                                        @if($assignment->difficulty)
                                            <span class="badge bg-{{ $assignment->difficulty == 'easy' ? 'success' : ($assignment->difficulty == 'medium' ? 'warning' : 'danger') }}">
                                                {{ ucfirst($assignment->difficulty) }}
                                            </span>
                                        @endif
                                    </div>
                                    <p class="mb-0 text-muted small">
                                        {{ Str::limit($assignment->description, 120) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                @if($assignment->due_date)
                                    <div class="mb-1">
                                        <strong class="text-{{ $assignment->isOverdue() ? 'danger' : ($assignment->isDueSoon() ? 'warning' : 'dark') }}">
                                            {{ $assignment->due_date->format('M d, Y') }}
                                        </strong>
                                        @if($assignment->due_time)
                                            <br><small class="text-muted">{{ $assignment->due_time->format('H:i') }}</small>
                                        @endif
                                    </div>
                                    @if($assignment->isOverdue() && !$assignment->hasSubmission(auth()->id()))
                                        <span class="badge bg-danger">Overdue</span>
                                    @elseif($assignment->isDueSoon())
                                        <span class="badge bg-warning">Due Soon</span>
                                    @endif
                                @else
                                    <span class="text-muted">No due date</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                @php
                                    $submission = $assignment->submissions()->where('student_id', auth()->id())->first();
                                @endphp
                                
                                @if($submission)
                                    <div class="mb-2">
                                        @if($submission->status == 'graded')
                                            <div class="h6 mb-0 text-{{ $submission->final_marks >= ($assignment->total_marks * 0.7) ? 'success' : ($submission->final_marks >= ($assignment->total_marks * 0.5) ? 'warning' : 'danger') }}">
                                                {{ $submission->final_marks }}/{{ $assignment->total_marks }}
                                            </div>
                                            <small class="text-muted">
                                                {{ number_format(($submission->final_marks / $assignment->total_marks) * 100, 1) }}%
                                            </small>
                                        @else
                                            <span class="badge bg-{{ $submission->status == 'submitted' ? 'warning' : 'info' }}">
                                                {{ ucfirst($submission->status) }}
                                            </span>
                                            @if($submission->is_late)
                                                <br><span class="badge bg-danger mt-1">Late</span>
                                            @endif
                                        @endif
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('student.assignments.show', $assignment) }}" 
                                           class="btn btn-outline-primary">View</a>
                                        <a href="{{ route('student.submissions.show', $submission) }}" 
                                           class="btn btn-outline-success">Submission</a>
                                    </div>
                                @else
                                    <div class="mb-2">
                                        <span class="badge bg-warning">Not Submitted</span>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('student.assignments.show', $assignment) }}" 
                                           class="btn btn-outline-primary">View</a>
                                        @if(!$assignment->isOverdue() || $assignment->allow_late_submission)
                                            <a href="{{ route('student.assignments.submit', $assignment) }}" 
                                               class="btn btn-primary">Submit</a>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-5">
                    <i class="fas fa-tasks fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">No assignments found</h5>
                    <p class="text-muted">
                        @if(request()->hasAny(['search', 'subject', 'type', 'status']))
                            Try adjusting your filters or search terms.
                        @else
                            No assignments have been assigned yet.
                        @endif
                    </p>
                    @if(request()->hasAny(['search', 'subject', 'type', 'status']))
                        <a href="{{ route('student.assignments') }}" class="btn btn-outline-primary">
                            Clear Filters
                        </a>
                    @endif
                </div>
            @endforelse
        </div>
        
        @if($assignments->hasPages())
            <div class="card-footer">
                {{ $assignments->appends(request()->query())->links() }}
            </div>
        @endif
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

.bg-light-danger {
    background-color: #f8d7da !important;
}

.assignment-row:hover {
    background-color: #f8f9fc !important;
}

.assignment-icon {
    min-width: 50px;
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
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Auto-submit form on filter change
    $('#subject, #type, #status, #sort').change(function() {
        $('#filterForm').submit();
    });
    
    // Search with debounce
    let searchTimeout;
    $('#search').on('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            $('#filterForm').submit();
        }, 500);
    });
});

function exportAssignments(format) {
    const params = new URLSearchParams(window.location.search);
    params.set('export', format);
    
    window.location.href = '{{ route("student.assignments") }}?' + params.toString();
}
</script>
@endpush