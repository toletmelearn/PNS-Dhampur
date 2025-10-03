@extends('layouts.app')

@section('title', $assignment->title)

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('student.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('student.assignments') }}">Assignments</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ Str::limit($assignment->title, 30) }}</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-800">{{ $assignment->title }}</h1>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('student.assignments') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back to Assignments
            </a>
            @if($assignment->attachments && count($assignment->attachments) > 0)
                <div class="dropdown">
                    <button class="btn btn-outline-info dropdown-toggle" type="button" 
                            data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-download me-1"></i>Download
                    </button>
                    <ul class="dropdown-menu">
                        @foreach($assignment->attachments as $attachment)
                            <li>
                                <a class="dropdown-item" href="{{ route('student.assignments.download', [$assignment, 'file' => $attachment]) }}">
                                    <i class="fas fa-file me-2"></i>{{ basename($attachment) }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>

    <div class="row">
        <!-- Assignment Details -->
        <div class="col-lg-8">
            <!-- Assignment Info Card -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Assignment Details</h6>
                    <div class="d-flex gap-2">
                        @if($assignment->isOverdue() && !$submission)
                            <span class="badge bg-danger">Overdue</span>
                        @elseif($assignment->isDueSoon() && !$submission)
                            <span class="badge bg-warning">Due Soon</span>
                        @elseif($submission)
                            <span class="badge bg-{{ $submission->status == 'graded' ? 'success' : ($submission->status == 'submitted' ? 'warning' : 'info') }}">
                                {{ ucfirst($submission->status) }}
                            </span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="info-item mb-3">
                                <label class="text-muted small">Subject</label>
                                <div class="fw-bold">{{ $assignment->subject->name ?? 'N/A' }}</div>
                            </div>
                            <div class="info-item mb-3">
                                <label class="text-muted small">Type</label>
                                <div>
                                    <span class="badge bg-secondary">{{ ucfirst($assignment->type) }}</span>
                                </div>
                            </div>
                            <div class="info-item mb-3">
                                <label class="text-muted small">Total Marks</label>
                                <div class="fw-bold text-primary">{{ $assignment->total_marks ?? 'N/A' }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item mb-3">
                                <label class="text-muted small">Due Date</label>
                                <div class="fw-bold text-{{ $assignment->isOverdue() ? 'danger' : 'dark' }}">
                                    @if($assignment->due_date)
                                        {{ $assignment->due_date->format('l, F j, Y') }}
                                        @if($assignment->due_time)
                                            at {{ $assignment->due_time->format('g:i A') }}
                                        @endif
                                    @else
                                        No due date
                                    @endif
                                </div>
                            </div>
                            <div class="info-item mb-3">
                                <label class="text-muted small">Difficulty</label>
                                <div>
                                    @if($assignment->difficulty)
                                        <span class="badge bg-{{ $assignment->difficulty == 'easy' ? 'success' : ($assignment->difficulty == 'medium' ? 'warning' : 'danger') }}">
                                            {{ ucfirst($assignment->difficulty) }}
                                        </span>
                                    @else
                                        <span class="text-muted">Not specified</span>
                                    @endif
                                </div>
                            </div>
                            <div class="info-item mb-3">
                                <label class="text-muted small">Estimated Duration</label>
                                <div class="fw-bold">{{ $assignment->estimated_duration ?? 'Not specified' }}</div>
                            </div>
                        </div>
                    </div>

                    @if($assignment->description)
                        <div class="mb-3">
                            <label class="text-muted small">Description</label>
                            <div class="mt-1">
                                {!! nl2br(e($assignment->description)) !!}
                            </div>
                        </div>
                    @endif

                    @if($assignment->instructions)
                        <div class="mb-3">
                            <label class="text-muted small">Instructions</label>
                            <div class="mt-1 p-3 bg-light rounded">
                                {!! nl2br(e($assignment->instructions)) !!}
                            </div>
                        </div>
                    @endif

                    @if($assignment->tags)
                        <div class="mb-3">
                            <label class="text-muted small">Tags</label>
                            <div class="mt-1">
                                @foreach(explode(',', $assignment->tags) as $tag)
                                    <span class="badge bg-light text-dark me-1">#{{ trim($tag) }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($assignment->attachments && count($assignment->attachments) > 0)
                        <div class="mb-3">
                            <label class="text-muted small">Attachments</label>
                            <div class="mt-1">
                                @foreach($assignment->attachments as $attachment)
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-file me-2 text-muted"></i>
                                        <a href="{{ route('student.assignments.download', [$assignment, 'file' => $attachment]) }}" 
                                           class="text-decoration-none">
                                            {{ basename($attachment) }}
                                        </a>
                                        <small class="text-muted ms-2">
                                            ({{ number_format(Storage::size($attachment) / 1024, 1) }} KB)
                                        </small>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Submission Section -->
            @if($submission)
                <!-- Existing Submission -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-success">Your Submission</h6>
                        <div class="d-flex gap-2">
                            @if($submission->status == 'graded')
                                <span class="badge bg-success">Graded</span>
                            @elseif($submission->status == 'submitted')
                                <span class="badge bg-warning">Under Review</span>
                            @else
                                <span class="badge bg-info">{{ ucfirst($submission->status) }}</span>
                            @endif
                            @if($submission->is_late)
                                <span class="badge bg-danger">Late Submission</span>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-item mb-3">
                                    <label class="text-muted small">Submitted On</label>
                                    <div class="fw-bold">
                                        {{ $submission->submitted_at ? $submission->submitted_at->format('M d, Y g:i A') : 'Not submitted' }}
                                    </div>
                                </div>
                                @if($submission->status == 'graded')
                                    <div class="info-item mb-3">
                                        <label class="text-muted small">Grade</label>
                                        <div class="h5 mb-0 text-{{ $submission->final_marks >= ($assignment->total_marks * 0.7) ? 'success' : ($submission->final_marks >= ($assignment->total_marks * 0.5) ? 'warning' : 'danger') }}">
                                            {{ $submission->final_marks }}/{{ $assignment->total_marks }}
                                            <small class="text-muted">
                                                ({{ number_format(($submission->final_marks / $assignment->total_marks) * 100, 1) }}%)
                                            </small>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <div class="col-md-6">
                                @if($submission->status == 'graded' && $submission->graded_at)
                                    <div class="info-item mb-3">
                                        <label class="text-muted small">Graded On</label>
                                        <div class="fw-bold">{{ $submission->graded_at->format('M d, Y g:i A') }}</div>
                                    </div>
                                @endif
                                @if($submission->status == 'graded' && $submission->graded_by)
                                    <div class="info-item mb-3">
                                        <label class="text-muted small">Graded By</label>
                                        <div class="fw-bold">{{ $submission->gradedBy->name ?? 'N/A' }}</div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if($submission->content)
                            <div class="mb-3">
                                <label class="text-muted small">Submission Content</label>
                                <div class="mt-1 p-3 bg-light rounded">
                                    {!! nl2br(e($submission->content)) !!}
                                </div>
                            </div>
                        @endif

                        @if($submission->attachments && count($submission->attachments) > 0)
                            <div class="mb-3">
                                <label class="text-muted small">Submitted Files</label>
                                <div class="mt-1">
                                    @foreach($submission->attachments as $attachment)
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-file me-2 text-muted"></i>
                                            <a href="{{ route('student.submissions.download', [$submission, 'file' => $attachment]) }}" 
                                               class="text-decoration-none">
                                                {{ basename($attachment) }}
                                            </a>
                                            <small class="text-muted ms-2">
                                                ({{ number_format(Storage::size($attachment) / 1024, 1) }} KB)
                                            </small>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if($submission->feedback)
                            <div class="mb-3">
                                <label class="text-muted small">Teacher Feedback</label>
                                <div class="mt-1 p-3 bg-info bg-opacity-10 rounded border-start border-info border-4">
                                    {!! nl2br(e($submission->feedback)) !!}
                                </div>
                            </div>
                        @endif

                        <div class="d-flex gap-2">
                            <a href="{{ route('student.submissions.show', $submission) }}" 
                               class="btn btn-outline-primary">
                                <i class="fas fa-eye me-1"></i>View Full Submission
                            </a>
                            @if($submission->status == 'draft' || ($assignment->allow_resubmission && $submission->status != 'graded'))
                                <a href="{{ route('student.assignments.submit', $assignment) }}" 
                                   class="btn btn-warning">
                                    <i class="fas fa-edit me-1"></i>Edit Submission
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @else
                <!-- Submit Assignment -->
                @if(!$assignment->isOverdue() || $assignment->allow_late_submission)
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-primary">Submit Assignment</h6>
                        </div>
                        <div class="card-body text-center py-5">
                            <i class="fas fa-upload fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted mb-3">Ready to submit your assignment?</h5>
                            <p class="text-muted mb-4">
                                @if($assignment->isOverdue())
                                    <span class="text-danger">This assignment is overdue, but late submissions are allowed.</span>
                                    @if($assignment->late_penalty > 0)
                                        <br><small>Late penalty: {{ $assignment->late_penalty }}% per day</small>
                                    @endif
                                @else
                                    Make sure you have read all instructions and prepared your submission.
                                @endif
                            </p>
                            <a href="{{ route('student.assignments.submit', $assignment) }}" 
                               class="btn btn-primary btn-lg">
                                <i class="fas fa-upload me-2"></i>Start Submission
                            </a>
                        </div>
                    </div>
                @else
                    <div class="card mb-4">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-times-circle fa-3x text-danger mb-3"></i>
                            <h5 class="text-danger mb-3">Submission Closed</h5>
                            <p class="text-muted">
                                This assignment was due on {{ $assignment->due_date->format('M d, Y') }}
                                @if($assignment->due_time)
                                    at {{ $assignment->due_time->format('g:i A') }}
                                @endif
                                and late submissions are not allowed.
                            </p>
                        </div>
                    </div>
                @endif
            @endif
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Assignment Status -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Status</h6>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        @if($submission)
                            @if($submission->status == 'graded')
                                <div class="status-circle bg-success text-white mb-2">
                                    <i class="fas fa-check"></i>
                                </div>
                                <h6 class="text-success">Graded</h6>
                                <p class="text-muted small mb-0">
                                    Grade: {{ $submission->final_marks }}/{{ $assignment->total_marks }}
                                </p>
                            @elseif($submission->status == 'submitted')
                                <div class="status-circle bg-warning text-white mb-2">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <h6 class="text-warning">Under Review</h6>
                                <p class="text-muted small mb-0">
                                    Submitted on {{ $submission->submitted_at->format('M d, Y') }}
                                </p>
                            @else
                                <div class="status-circle bg-info text-white mb-2">
                                    <i class="fas fa-edit"></i>
                                </div>
                                <h6 class="text-info">Draft</h6>
                                <p class="text-muted small mb-0">
                                    Last saved {{ $submission->updated_at->diffForHumans() }}
                                </p>
                            @endif
                        @else
                            @if($assignment->isOverdue())
                                <div class="status-circle bg-danger text-white mb-2">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <h6 class="text-danger">Overdue</h6>
                                <p class="text-muted small mb-0">
                                    Due {{ $assignment->due_date->diffForHumans() }}
                                </p>
                            @else
                                <div class="status-circle bg-warning text-white mb-2">
                                    <i class="fas fa-hourglass-half"></i>
                                </div>
                                <h6 class="text-warning">Pending</h6>
                                <p class="text-muted small mb-0">
                                    @if($assignment->due_date)
                                        Due {{ $assignment->due_date->diffForHumans() }}
                                    @else
                                        No due date set
                                    @endif
                                </p>
                            @endif
                        @endif
                    </div>
                </div>
            </div>

            <!-- Assignment Info -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Information</h6>
                </div>
                <div class="card-body">
                    <div class="info-item mb-3">
                        <label class="text-muted small">Teacher</label>
                        <div class="fw-bold">{{ $assignment->teacher->name ?? 'N/A' }}</div>
                    </div>
                    <div class="info-item mb-3">
                        <label class="text-muted small">Class</label>
                        <div class="fw-bold">{{ $assignment->class->name ?? 'N/A' }}</div>
                    </div>
                    <div class="info-item mb-3">
                        <label class="text-muted small">Created</label>
                        <div class="fw-bold">{{ $assignment->created_at->format('M d, Y') }}</div>
                    </div>
                    @if($assignment->submission_type)
                        <div class="info-item mb-3">
                            <label class="text-muted small">Submission Type</label>
                            <div class="fw-bold">{{ ucfirst($assignment->submission_type) }}</div>
                        </div>
                    @endif
                    @if($assignment->allow_late_submission)
                        <div class="info-item mb-3">
                            <label class="text-muted small">Late Submission</label>
                            <div class="text-success">
                                <i class="fas fa-check me-1"></i>Allowed
                                @if($assignment->late_penalty > 0)
                                    <br><small class="text-muted">Penalty: {{ $assignment->late_penalty }}% per day</small>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @if(!$submission && (!$assignment->isOverdue() || $assignment->allow_late_submission))
                            <a href="{{ route('student.assignments.submit', $assignment) }}" 
                               class="btn btn-primary">
                                <i class="fas fa-upload me-2"></i>Submit Assignment
                            </a>
                        @endif
                        @if($submission)
                            <a href="{{ route('student.submissions.show', $submission) }}" 
                               class="btn btn-outline-success">
                                <i class="fas fa-eye me-2"></i>View Submission
                            </a>
                        @endif
                        <a href="{{ route('student.assignments') }}" 
                           class="btn btn-outline-secondary">
                            <i class="fas fa-list me-2"></i>All Assignments
                        </a>
                        <button type="button" class="btn btn-outline-info" onclick="printAssignment()">
                            <i class="fas fa-print me-2"></i>Print Assignment
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.status-circle {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    font-size: 1.5rem;
}

.info-item label {
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.bg-light {
    background-color: #f8f9fc !important;
}

.border-start {
    border-left: 0.25rem solid !important;
}

.border-4 {
    border-width: 0.25rem !important;
}

.bg-opacity-10 {
    background-color: rgba(13, 202, 240, 0.1) !important;
}

@media print {
    .btn, .card-header, nav, .sidebar {
        display: none !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
    }
}
</style>
@endpush

@push('scripts')
<script>
function printAssignment() {
    window.print();
}

// Auto-refresh submission status every 30 seconds if submitted but not graded
@if($submission && $submission->status == 'submitted')
setInterval(function() {
    checkSubmissionStatus();
}, 30000);

function checkSubmissionStatus() {
    $.get('{{ route("student.submissions.status", $submission) }}', function(data) {
        if (data.status !== '{{ $submission->status }}') {
            location.reload();
        }
    }).fail(function() {
        console.log('Failed to check submission status');
    });
}
@endif
</script>
@endpush