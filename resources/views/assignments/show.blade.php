@extends('layouts.app')

@section('title', 'Assignment Details - ' . $assignment->title)

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('assignments.index') }}">Assignments</a></li>
                            <li class="breadcrumb-item active">{{ $assignment->title }}</li>
                        </ol>
                    </nav>
                    <h2 class="mb-1">{{ $assignment->title }}</h2>
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge bg-info">{{ ucfirst($assignment->type) }}</span>
                        @if($assignment->difficulty)
                            <span class="badge bg-{{ $assignment->difficulty == 'easy' ? 'success' : ($assignment->difficulty == 'medium' ? 'warning' : 'danger') }}">
                                {{ ucfirst($assignment->difficulty) }}
                            </span>
                        @endif
                        @if($assignment->is_published)
                            <span class="badge bg-success">Published</span>
                        @else
                            <span class="badge bg-secondary">Draft</span>
                        @endif
                        @if($assignment->isOverdue())
                            <span class="badge bg-danger">Overdue</span>
                        @endif
                    </div>
                </div>
                <div class="btn-group" role="group">
                    <a href="{{ route('assignments.edit', $assignment) }}" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    @if($assignment->is_published)
                        <button type="button" class="btn btn-secondary" onclick="togglePublish(false)">
                            <i class="fas fa-eye-slash"></i> Unpublish
                        </button>
                    @else
                        <button type="button" class="btn btn-success" onclick="togglePublish(true)">
                            <i class="fas fa-eye"></i> Publish
                        </button>
                    @endif
                    <button type="button" class="btn btn-danger" onclick="deleteAssignment()">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Assignment Details -->
        <div class="col-lg-8">
            <!-- Basic Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Assignment Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Class:</strong></td>
                                    <td>{{ $assignment->class->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Subject:</strong></td>
                                    <td>{{ $assignment->subject->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Teacher:</strong></td>
                                    <td>{{ $assignment->teacher->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Type:</strong></td>
                                    <td>{{ ucfirst($assignment->type) }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Total Marks:</strong></td>
                                    <td>{{ $assignment->total_marks ?? 'Not specified' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Due Date:</strong></td>
                                    <td>
                                        @if($assignment->due_date)
                                            {{ $assignment->due_date->format('M d, Y h:i A') }}
                                        @else
                                            No due date
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Estimated Duration:</strong></td>
                                    <td>{{ $assignment->estimated_duration ? $assignment->estimated_duration . ' minutes' : 'Not specified' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Created:</strong></td>
                                    <td>{{ $assignment->created_at->format('M d, Y h:i A') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if($assignment->description)
                    <div class="mt-3">
                        <h6>Description</h6>
                        <p class="text-muted">{{ $assignment->description }}</p>
                    </div>
                    @endif

                    @if($assignment->instructions)
                    <div class="mt-3">
                        <h6>Instructions</h6>
                        <div class="bg-light p-3 rounded">
                            {!! nl2br(e($assignment->instructions)) !!}
                        </div>
                    </div>
                    @endif

                    @if($assignment->attachment_path)
                    <div class="mt-3">
                        <h6>Attachment</h6>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-paperclip me-2"></i>
                            <a href="{{ route('assignments.download-attachment', $assignment) }}" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-download"></i> {{ $assignment->original_filename ?? 'Download Attachment' }}
                            </a>
                            <small class="text-muted ms-2">{{ $assignment->getFormattedFileSize() }}</small>
                        </div>
                    </div>
                    @endif

                    @if($assignment->tags)
                    <div class="mt-3">
                        <h6>Tags</h6>
                        @foreach(explode(',', $assignment->tags) as $tag)
                            <span class="badge bg-light text-dark me-1">{{ trim($tag) }}</span>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>

            <!-- Submissions -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Submissions</h5>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="refreshSubmissions()">
                            <i class="fas fa-sync"></i> Refresh
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="exportSubmissions()">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Submission Filters -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <select class="form-select" id="submissionStatusFilter">
                                <option value="">All Submissions</option>
                                <option value="submitted">Submitted</option>
                                <option value="pending">Pending</option>
                                <option value="graded">Graded</option>
                                <option value="late">Late Submissions</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="text" class="form-control" id="studentSearch" placeholder="Search students...">
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" id="sortSubmissions">
                                <option value="name">Sort by Name</option>
                                <option value="submitted_at">Sort by Submission Time</option>
                                <option value="marks">Sort by Marks</option>
                            </select>
                        </div>
                    </div>

                    <!-- Submissions Table -->
                    <div class="table-responsive">
                        <table class="table table-hover" id="submissionsTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Student</th>
                                    <th>Submission Status</th>
                                    <th>Submitted At</th>
                                    <th>Marks</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($submissions as $submission)
                                <tr data-student-id="{{ $submission->student_id }}">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                                {{ substr($submission->student->name, 0, 1) }}
                                            </div>
                                            <div>
                                                <strong>{{ $submission->student->name }}</strong>
                                                <br><small class="text-muted">{{ $submission->student->roll_number ?? 'N/A' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($submission->status == 'submitted')
                                            <span class="badge bg-success">Submitted</span>
                                            @if($submission->is_late)
                                                <span class="badge bg-warning">Late</span>
                                            @endif
                                        @elseif($submission->status == 'graded')
                                            <span class="badge bg-info">Graded</span>
                                        @else
                                            <span class="badge bg-secondary">Pending</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($submission->submitted_at)
                                            {{ $submission->submitted_at->format('M d, Y h:i A') }}
                                        @else
                                            <span class="text-muted">Not submitted</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($submission->marks_obtained !== null)
                                            <strong>{{ $submission->marks_obtained }}/{{ $assignment->total_marks }}</strong>
                                            <small class="text-muted">({{ number_format(($submission->marks_obtained / $assignment->total_marks) * 100, 1) }}%)</small>
                                        @else
                                            <span class="text-muted">Not graded</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            @if($submission->status == 'submitted' || $submission->status == 'graded')
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        onclick="viewSubmission({{ $submission->id }})" title="View Submission">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-success" 
                                                        onclick="gradeSubmission({{ $submission->id }})" title="Grade">
                                                    <i class="fas fa-star"></i>
                                                </button>
                                                @if($submission->attachment_path)
                                                    <a href="{{ route('assignments.download-submission', $submission) }}" 
                                                       class="btn btn-sm btn-outline-info" title="Download">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                @endif
                                            @else
                                                <button type="button" class="btn btn-sm btn-outline-secondary" 
                                                        onclick="sendReminder({{ $submission->student_id }})" title="Send Reminder">
                                                    <i class="fas fa-bell"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Sidebar -->
        <div class="col-lg-4">
            <!-- Submission Statistics -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Submission Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border-end">
                                <h3 class="text-primary mb-0">{{ $submissionStats['submitted'] }}</h3>
                                <small class="text-muted">Submitted</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <h3 class="text-warning mb-0">{{ $submissionStats['pending'] }}</h3>
                            <small class="text-muted">Pending</small>
                        </div>
                    </div>
                    <hr>
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border-end">
                                <h3 class="text-success mb-0">{{ $submissionStats['graded'] }}</h3>
                                <small class="text-muted">Graded</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <h3 class="text-danger mb-0">{{ $submissionStats['late'] }}</h3>
                            <small class="text-muted">Late</small>
                        </div>
                    </div>
                    <hr>
                    <div class="text-center">
                        <h4 class="text-info mb-0">{{ number_format($assignment->getSubmissionRate(), 1) }}%</h4>
                        <small class="text-muted">Submission Rate</small>
                    </div>
                    @if($submissionStats['graded'] > 0)
                    <hr>
                    <div class="text-center">
                        <h4 class="text-success mb-0">{{ number_format($assignment->getAverageGrade(), 1) }}%</h4>
                        <small class="text-muted">Average Grade</small>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-primary" onclick="sendBulkReminder()">
                            <i class="fas fa-bell"></i> Send Reminder to All Pending
                        </button>
                        <button type="button" class="btn btn-outline-success" onclick="autoGrade()">
                            <i class="fas fa-magic"></i> Auto Grade (if applicable)
                        </button>
                        <button type="button" class="btn btn-outline-info" onclick="generateReport()">
                            <i class="fas fa-chart-bar"></i> Generate Report
                        </button>
                        <button type="button" class="btn btn-outline-warning" onclick="extendDeadline()">
                            <i class="fas fa-clock"></i> Extend Deadline
                        </button>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recent Activity</h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        @foreach($recentSubmissions as $recent)
                        <div class="timeline-item">
                            <div class="timeline-marker bg-primary"></div>
                            <div class="timeline-content">
                                <h6 class="mb-1">{{ $recent->student->name }}</h6>
                                <p class="mb-1 text-muted">Submitted assignment</p>
                                <small class="text-muted">{{ $recent->submitted_at->diffForHumans() }}</small>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Grade Submission Modal -->
<div class="modal fade" id="gradeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Grade Submission</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="gradeForm">
                @csrf
                <input type="hidden" id="submissionId" name="submission_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="marks_obtained" class="form-label">Marks Obtained</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="marks_obtained" name="marks_obtained" 
                                   min="0" max="{{ $assignment->total_marks }}" step="0.5" required>
                            <span class="input-group-text">/ {{ $assignment->total_marks }}</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="feedback" class="form-label">Feedback</label>
                        <textarea class="form-control" id="feedback" name="feedback" rows="4" 
                                  placeholder="Provide feedback to the student..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Grade</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Submission Modal -->
<div class="modal fade" id="viewSubmissionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">View Submission</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="submissionContent">
                    <!-- Content will be loaded dynamically -->
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
}

.timeline {
    position: relative;
    padding-left: 20px;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -25px;
    top: 5px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
}

.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: -21px;
    top: 15px;
    width: 2px;
    height: calc(100% + 5px);
    background-color: #dee2e6;
}

.timeline-content h6 {
    font-size: 14px;
}

.timeline-content p {
    font-size: 13px;
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Filter submissions
    $('#submissionStatusFilter, #studentSearch, #sortSubmissions').on('change keyup', function() {
        filterSubmissions();
    });

    // Grade form submission
    $('#gradeForm').submit(function(e) {
        e.preventDefault();
        
        let submissionId = $('#submissionId').val();
        let formData = $(this).serialize();
        
        $.ajax({
            url: `/assignments/submissions/${submissionId}/grade`,
            method: 'POST',
            data: formData,
            success: function(response) {
                if(response.success) {
                    $('#gradeModal').modal('hide');
                    location.reload();
                } else {
                    alert('Error grading submission: ' + response.message);
                }
            },
            error: function(xhr) {
                alert('Error grading submission');
            }
        });
    });
});

function togglePublish(publish) {
    $.ajax({
        url: `/assignments/{{ $assignment->id }}/toggle-published`,
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            is_published: publish
        },
        success: function(response) {
            if(response.success) {
                location.reload();
            } else {
                alert('Error updating assignment status');
            }
        }
    });
}

function deleteAssignment() {
    if(confirm('Are you sure you want to delete this assignment? This action cannot be undone.')) {
        $.ajax({
            url: `/assignments/{{ $assignment->id }}`,
            method: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if(response.success) {
                    window.location.href = '{{ route("assignments.index") }}';
                } else {
                    alert('Error deleting assignment');
                }
            }
        });
    }
}

function gradeSubmission(submissionId) {
    // Load submission data and show modal
    $.ajax({
        url: `/assignments/submissions/${submissionId}`,
        method: 'GET',
        success: function(response) {
            $('#submissionId').val(submissionId);
            $('#marks_obtained').val(response.marks_obtained || '');
            $('#feedback').val(response.feedback || '');
            $('#gradeModal').modal('show');
        }
    });
}

function viewSubmission(submissionId) {
    $('#submissionContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
    $('#viewSubmissionModal').modal('show');
    
    $.ajax({
        url: `/assignments/submissions/${submissionId}/view`,
        method: 'GET',
        success: function(response) {
            $('#submissionContent').html(response);
        },
        error: function() {
            $('#submissionContent').html('<div class="alert alert-danger">Error loading submission</div>');
        }
    });
}

function sendReminder(studentId) {
    $.ajax({
        url: `/assignments/{{ $assignment->id }}/send-reminder`,
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            student_id: studentId
        },
        success: function(response) {
            if(response.success) {
                alert('Reminder sent successfully');
            } else {
                alert('Error sending reminder');
            }
        }
    });
}

function sendBulkReminder() {
    if(confirm('Send reminder to all students who haven\'t submitted?')) {
        $.ajax({
            url: `/assignments/{{ $assignment->id }}/send-bulk-reminder`,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if(response.success) {
                    alert(`Reminders sent to ${response.count} students`);
                } else {
                    alert('Error sending reminders');
                }
            }
        });
    }
}

function filterSubmissions() {
    let status = $('#submissionStatusFilter').val();
    let search = $('#studentSearch').val().toLowerCase();
    let sort = $('#sortSubmissions').val();
    
    let rows = $('#submissionsTable tbody tr');
    
    rows.each(function() {
        let row = $(this);
        let studentName = row.find('td:first strong').text().toLowerCase();
        let submissionStatus = row.find('.badge').first().text().toLowerCase();
        
        let showRow = true;
        
        // Filter by status
        if(status && !submissionStatus.includes(status)) {
            showRow = false;
        }
        
        // Filter by search
        if(search && !studentName.includes(search)) {
            showRow = false;
        }
        
        row.toggle(showRow);
    });
}

function refreshSubmissions() {
    location.reload();
}

function exportSubmissions() {
    window.open(`/assignments/{{ $assignment->id }}/export-submissions`, '_blank');
}

function generateReport() {
    window.open(`/assignments/{{ $assignment->id }}/report`, '_blank');
}

function extendDeadline() {
    let newDate = prompt('Enter new due date (YYYY-MM-DD HH:MM):');
    if(newDate) {
        $.ajax({
            url: `/assignments/{{ $assignment->id }}/extend-deadline`,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                due_date: newDate
            },
            success: function(response) {
                if(response.success) {
                    location.reload();
                } else {
                    alert('Error extending deadline');
                }
            }
        });
    }
}
</script>
@endpush