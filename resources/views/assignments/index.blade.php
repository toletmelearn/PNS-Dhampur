@extends('layouts.app')

@section('title', 'Assignment Management')

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">Assignment Management</h2>
                    <p class="text-muted">Create, manage, and track assignments</p>
                </div>
                <div>
                    <a href="{{ route('assignments.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Assignment
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-1">{{ $stats['total'] ?? 0 }}</h4>
                            <p class="mb-0">Total Assignments</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-tasks fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-1">{{ $stats['published'] ?? 0 }}</h4>
                            <p class="mb-0">Published</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-eye fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-1">{{ $stats['draft'] ?? 0 }}</h4>
                            <p class="mb-0">Drafts</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-edit fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-1">{{ $stats['overdue'] ?? 0 }}</h4>
                            <p class="mb-0">Overdue</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clock fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="filterForm" method="GET">
                <div class="row">
                    <div class="col-md-3">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               placeholder="Search assignments..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <label for="class_filter" class="form-label">Class</label>
                        <select class="form-select" id="class_filter" name="class_id">
                            <option value="">All Classes</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                                    {{ $class->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="subject_filter" class="form-label">Subject</label>
                        <select class="form-select" id="subject_filter" name="subject_id">
                            <option value="">All Subjects</option>
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}" {{ request('subject_id') == $subject->id ? 'selected' : '' }}>
                                    {{ $subject->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="status_filter" class="form-label">Status</label>
                        <select class="form-select" id="status_filter" name="status">
                            <option value="">All Status</option>
                            <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="published" {{ request('status') == 'published' ? 'selected' : '' }}>Published</option>
                            <option value="archived" {{ request('status') == 'archived' ? 'selected' : '' }}>Archived</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="type_filter" class="form-label">Type</label>
                        <select class="form-select" id="type_filter" name="type">
                            <option value="">All Types</option>
                            <option value="homework" {{ request('type') == 'homework' ? 'selected' : '' }}>Homework</option>
                            <option value="project" {{ request('type') == 'project' ? 'selected' : '' }}>Project</option>
                            <option value="quiz" {{ request('type') == 'quiz' ? 'selected' : '' }}>Quiz</option>
                            <option value="exam" {{ request('type') == 'exam' ? 'selected' : '' }}>Exam</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="fas fa-search"></i>
                            </button>
                            <a href="{{ route('assignments.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Assignments Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Assignments List</h5>
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="bulkAction('publish')">
                    <i class="fas fa-eye"></i> Publish Selected
                </button>
                <button type="button" class="btn btn-sm btn-outline-warning" onclick="bulkAction('unpublish')">
                    <i class="fas fa-eye-slash"></i> Unpublish Selected
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="bulkAction('delete')">
                    <i class="fas fa-trash"></i> Delete Selected
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="40">
                                <input type="checkbox" id="selectAll" class="form-check-input">
                            </th>
                            <th>Title</th>
                            <th>Class</th>
                            <th>Subject</th>
                            <th>Type</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Submissions</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($assignments as $assignment)
                        <tr>
                            <td>
                                <input type="checkbox" class="form-check-input assignment-checkbox" 
                                       value="{{ $assignment->id }}">
                            </td>
                            <td>
                                <div>
                                    <strong>{{ $assignment->title }}</strong>
                                    @if($assignment->difficulty)
                                        <span class="badge badge-sm bg-{{ $assignment->difficulty == 'easy' ? 'success' : ($assignment->difficulty == 'medium' ? 'warning' : 'danger') }}">
                                            {{ ucfirst($assignment->difficulty) }}
                                        </span>
                                    @endif
                                </div>
                                <small class="text-muted">{{ Str::limit($assignment->description, 50) }}</small>
                            </td>
                            <td>{{ $assignment->class->name ?? 'N/A' }}</td>
                            <td>{{ $assignment->subject->name ?? 'N/A' }}</td>
                            <td>
                                <span class="badge bg-info">{{ ucfirst($assignment->type) }}</span>
                            </td>
                            <td>
                                <div>{{ $assignment->due_date ? $assignment->due_date->format('M d, Y') : 'No due date' }}</div>
                                @if($assignment->due_date)
                                    <small class="text-muted">{{ $assignment->due_date->format('h:i A') }}</small>
                                @endif
                                @if($assignment->isOverdue())
                                    <span class="badge bg-danger">Overdue</span>
                                @endif
                            </td>
                            <td>
                                @if($assignment->is_published)
                                    <span class="badge bg-success">Published</span>
                                @else
                                    <span class="badge bg-secondary">Draft</span>
                                @endif
                            </td>
                            <td>
                                <div>{{ $assignment->getSubmissionCount() }}/{{ $assignment->class->students_count ?? 0 }}</div>
                                <small class="text-muted">{{ number_format($assignment->getSubmissionRate(), 1) }}%</small>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('assignments.show', $assignment) }}" 
                                       class="btn btn-sm btn-outline-primary" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('assignments.edit', $assignment) }}" 
                                       class="btn btn-sm btn-outline-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-info" 
                                            onclick="viewSubmissions({{ $assignment->id }})" title="Submissions">
                                        <i class="fas fa-list"></i>
                                    </button>
                                    @if($assignment->is_published)
                                        <button type="button" class="btn btn-sm btn-outline-secondary" 
                                                onclick="togglePublish({{ $assignment->id }}, false)" title="Unpublish">
                                            <i class="fas fa-eye-slash"></i>
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-sm btn-outline-success" 
                                                onclick="togglePublish({{ $assignment->id }}, true)" title="Publish">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    @endif
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            onclick="deleteAssignment({{ $assignment->id }})" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="fas fa-tasks fa-3x mb-3"></i>
                                    <p>No assignments found</p>
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAssignmentModal">
                                        Create First Assignment
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($assignments->hasPages())
        <div class="card-footer">
            {{ $assignments->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Create Assignment Modal -->
<div class="modal fade" id="createAssignmentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Assignment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createAssignmentForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="title" class="form-label">Title *</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="type" class="form-label">Type *</label>
                                <select class="form-select" id="type" name="type" required>
                                    <option value="">Select Type</option>
                                    <option value="homework">Homework</option>
                                    <option value="project">Project</option>
                                    <option value="quiz">Quiz</option>
                                    <option value="exam">Exam</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="instructions" class="form-label">Instructions</label>
                        <textarea class="form-control" id="instructions" name="instructions" rows="4"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="class_id" class="form-label">Class *</label>
                                <select class="form-select" id="class_id" name="class_id" required>
                                    <option value="">Select Class</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="subject_id" class="form-label">Subject *</label>
                                <select class="form-select" id="subject_id" name="subject_id" required>
                                    <option value="">Select Subject</option>
                                    @foreach($subjects as $subject)
                                        <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="due_date" class="form-label">Due Date</label>
                                <input type="date" class="form-control" id="due_date" name="due_date">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="due_time" class="form-label">Due Time</label>
                                <input type="time" class="form-control" id="due_time" name="due_time">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="total_marks" class="form-label">Total Marks</label>
                                <input type="number" class="form-control" id="total_marks" name="total_marks" min="0">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="difficulty" class="form-label">Difficulty</label>
                                <select class="form-select" id="difficulty" name="difficulty">
                                    <option value="">Select Difficulty</option>
                                    <option value="easy">Easy</option>
                                    <option value="medium">Medium</option>
                                    <option value="hard">Hard</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="estimated_duration" class="form-label">Estimated Duration (minutes)</label>
                                <input type="number" class="form-control" id="estimated_duration" name="estimated_duration" min="0">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="attachment" class="form-label">Attachment</label>
                        <input type="file" class="form-control" id="attachment" name="attachment">
                        <small class="text-muted">Supported formats: PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, ZIP</small>
                    </div>

                    <div class="mb-3">
                        <label for="tags" class="form-label">Tags</label>
                        <input type="text" class="form-control" id="tags" name="tags" 
                               placeholder="Enter tags separated by commas">
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_published" name="is_published">
                                <label class="form-check-label" for="is_published">
                                    Publish immediately
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="allow_late_submission" name="allow_late_submission">
                                <label class="form-check-label" for="allow_late_submission">
                                    Allow late submissions
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Assignment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Submissions Modal -->
<div class="modal fade" id="submissionsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assignment Submissions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="submissionsContent">
                    <!-- Content will be loaded dynamically -->
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
.badge-sm {
    font-size: 0.7em;
}
.table th {
    border-top: none;
    font-weight: 600;
}
.btn-group .btn {
    border-radius: 0.25rem;
    margin-right: 2px;
}
.opacity-75 {
    opacity: 0.75;
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Select all checkbox functionality
    $('#selectAll').change(function() {
        $('.assignment-checkbox').prop('checked', $(this).prop('checked'));
    });

    // Create assignment form submission
    $('#createAssignmentForm').submit(function(e) {
        e.preventDefault();
        
        let formData = new FormData(this);
        
        $.ajax({
            url: '{{ route("assignments.store") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if(response.success) {
                    $('#createAssignmentModal').modal('hide');
                    location.reload();
                } else {
                    alert('Error creating assignment: ' + response.message);
                }
            },
            error: function(xhr) {
                let errors = xhr.responseJSON.errors;
                let errorMessage = 'Please fix the following errors:\n';
                for(let field in errors) {
                    errorMessage += '- ' + errors[field][0] + '\n';
                }
                alert(errorMessage);
            }
        });
    });

    // Auto-submit filter form on change
    $('#filterForm select, #filterForm input').change(function() {
        $('#filterForm').submit();
    });
});

function togglePublish(assignmentId, publish) {
    $.ajax({
        url: `/assignments/${assignmentId}/toggle-published`,
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

function deleteAssignment(assignmentId) {
    if(confirm('Are you sure you want to delete this assignment? This action cannot be undone.')) {
        $.ajax({
            url: `/assignments/${assignmentId}`,
            method: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if(response.success) {
                    location.reload();
                } else {
                    alert('Error deleting assignment');
                }
            }
        });
    }
}

function viewSubmissions(assignmentId) {
    $('#submissionsContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
    $('#submissionsModal').modal('show');
    
    $.ajax({
        url: `/assignments/${assignmentId}/submissions`,
        method: 'GET',
        success: function(response) {
            $('#submissionsContent').html(response);
        },
        error: function() {
            $('#submissionsContent').html('<div class="alert alert-danger">Error loading submissions</div>');
        }
    });
}

function bulkAction(action) {
    let selectedIds = $('.assignment-checkbox:checked').map(function() {
        return $(this).val();
    }).get();
    
    if(selectedIds.length === 0) {
        alert('Please select at least one assignment');
        return;
    }
    
    let confirmMessage = `Are you sure you want to ${action} ${selectedIds.length} assignment(s)?`;
    if(action === 'delete') {
        confirmMessage += ' This action cannot be undone.';
    }
    
    if(confirm(confirmMessage)) {
        $.ajax({
            url: '{{ route("assignments.bulk-action") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                action: action,
                assignment_ids: selectedIds
            },
            success: function(response) {
                if(response.success) {
                    location.reload();
                } else {
                    alert('Error performing bulk action');
                }
            }
        });
    }
}
</script>
@endpush