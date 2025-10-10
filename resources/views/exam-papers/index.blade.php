@extends('layouts.app')

@section('title', 'Exam Papers Management')

@section('content')
<div class="container-fluid">
    <!-- Dashboard Statistics -->
    @if(isset($statistics))
    <div class="row mb-4">
        <div class="col-lg-2 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $statistics['total_papers'] }}</h3>
                    <p>Total Papers</p>
                </div>
                <div class="icon">
                    <i class="fas fa-file-alt"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $statistics['draft_papers'] }}</h3>
                    <p>Draft Papers</p>
                </div>
                <div class="icon">
                    <i class="fas fa-edit"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $statistics['pending_approval'] }}</h3>
                    <p>Pending Approval</p>
                </div>
                <div class="icon">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $statistics['approved_papers'] }}</h3>
                    <p>Approved Papers</p>
                </div>
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $statistics['rejected_papers'] }}</h3>
                    <p>Rejected Papers</p>
                </div>
                <div class="icon">
                    <i class="fas fa-times-circle"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-6">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3>{{ $statistics['recent_activity']->count() }}</h3>
                    <p>Recent Activities</p>
                </div>
                <div class="icon">
                    <i class="fas fa-history"></i>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-file-alt mr-2"></i>Exam Papers Management
                        <small class="text-muted ml-2">with Version Control & Approval Workflow</small>
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('exam-papers.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus mr-1"></i>Create New Paper
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Filters -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <form method="GET" action="{{ route('exam-papers.index') }}" class="form-inline">
                                <div class="form-group mr-3">
                                    <label for="subject_id" class="mr-2">Subject:</label>
                                    <select name="subject_id" id="subject_id" class="form-control">
                                        <option value="">All Subjects</option>
                                        @foreach($subjects as $subject)
                                            <option value="{{ $subject->id }}" {{ request('subject_id') == $subject->id ? 'selected' : '' }}>
                                                {{ $subject->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group mr-3">
                                    <label for="class_id" class="mr-2">Class:</label>
                                    <select name="class_id" id="class_id" class="form-control">
                                        <option value="">All Classes</option>
                                        @foreach($classes as $class)
                                            <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                                                {{ $class->name }} {{ $class->section }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group mr-3">
                                    <label for="exam_id" class="mr-2">Exam:</label>
                                    <select name="exam_id" id="exam_id" class="form-control">
                                        <option value="">All Exams</option>
                                        @foreach($exams as $exam)
                                            <option value="{{ $exam->id }}" {{ request('exam_id') == $exam->id ? 'selected' : '' }}>
                                                {{ $exam->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group mr-3">
                                    <label for="status" class="mr-2">Status:</label>
                                    <select name="status" id="status" class="form-control">
                                        <option value="">All Status</option>
                                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                                        <option value="submitted" {{ request('status') == 'submitted' ? 'selected' : '' }}>Submitted</option>
                                        <option value="published" {{ request('status') == 'published' ? 'selected' : '' }}>Published</option>
                                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                    </select>
                                </div>

                                <div class="form-group mr-3">
                                    <label for="search" class="mr-2">Search:</label>
                                    <input type="text" name="search" id="search" class="form-control" 
                                           placeholder="Title, code, subject..." value="{{ request('search') }}">
                                </div>

                                <button type="submit" class="btn btn-info mr-2">
                                    <i class="fas fa-search mr-1"></i>Filter
                                </button>
                                <a href="{{ route('exam-papers.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times mr-1"></i>Clear
                                </a>
                            </form>
                        </div>
                    </div>

                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="info-box bg-info">
                                <span class="info-box-icon"><i class="fas fa-file-alt"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Papers</span>
                                    <span class="info-box-number">{{ $examPapers->total() }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-warning">
                                <span class="info-box-icon"><i class="fas fa-edit"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Draft Papers</span>
                                    <span class="info-box-number">{{ $examPapers->where('status', 'draft')->count() }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-success">
                                <span class="info-box-icon"><i class="fas fa-check"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Published Papers</span>
                                    <span class="info-box-number">{{ $examPapers->where('status', 'published')->count() }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-primary">
                                <span class="info-box-icon"><i class="fas fa-thumbs-up"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Approved Papers</span>
                                    <span class="info-box-number">{{ $examPapers->where('status', 'approved')->count() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Exam Papers Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Paper Code</th>
                                    <th>Title</th>
                                    <th>Subject</th>
                                    <th>Class</th>
                                    <th>Exam</th>
                                    <th>Teacher</th>
                                    <th>Version</th>
                                    <th>Questions</th>
                                    <th>Marks</th>
                                    <th>Duration</th>
                                    <th>Type</th>
                                    <th>Difficulty</th>
                                    <th>Status</th>
                                    <th>Approval Status</th>
                                    <th>Deadline</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($examPapers as $paper)
                                    <tr class="{{ $paper->is_overdue ? 'table-danger' : '' }}">
                                        <td>
                                            <code>{{ $paper->paper_code }}</code>
                                            @if($paper->is_overdue)
                                                <br><small class="text-danger"><i class="fas fa-exclamation-triangle"></i> Overdue</small>
                                            @endif
                                        </td>
                                        <td>
                                            <strong>{{ $paper->title }}</strong>
                                            @if($paper->rejection_reason)
                                                <br><small class="text-danger">
                                                    <i class="fas fa-info-circle"></i> {{ $paper->rejection_reason }}
                                                </small>
                                            @endif
                                        </td>
                                        <td>{{ $paper->subject->name ?? 'N/A' }}</td>
                                        <td>{{ $paper->class->name ?? 'N/A' }} {{ $paper->class->section ?? '' }}</td>
                                        <td>{{ $paper->exam->name ?? 'N/A' }}</td>
                                        <td>{{ $paper->teacher->name ?? 'N/A' }}</td>
                                        <td>
                                            @if($paper->currentVersion)
                                                <span class="badge badge-primary">v{{ $paper->currentVersion->version_number }}</span>
                                                <br><small class="text-muted">{{ $paper->currentVersion->created_at->format('M d, Y') }}</small>
                                            @else
                                                <span class="badge badge-secondary">No Version</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-info">{{ $paper->questions_count }}</span>
                                        </td>
                                        <td>
                                            {{ $paper->total_marks }}
                                            @if($paper->calculated_total_marks !== $paper->total_marks)
                                                <br><small class="text-warning">
                                                    <i class="fas fa-exclamation-triangle"></i> 
                                                    Calculated: {{ $paper->calculated_total_marks }}
                                                </small>
                                            @endif
                                        </td>
                                        <td>{{ $paper->formatted_duration }}</td>
                                        <td><span class="badge {{ $paper->paper_type_badge_class }}">{{ $paper->paper_type_badge_text }}</span></td>
                                        <td><span class="badge {{ $paper->difficulty_badge_class }}">{{ $paper->difficulty_badge_text }}</span></td>
                                        <td><span class="badge {{ $paper->status_badge_class }}">{{ $paper->status_badge_text }}</span></td>
                                        <td>
                                            @if($paper->currentVersion && $paper->currentVersion->latestApproval)
                                                @php
                                                    $approval = $paper->currentVersion->latestApproval;
                                                    $approvalClass = match($approval->status) {
                                                        'pending' => 'badge-warning',
                                                        'approved' => 'badge-success',
                                                        'rejected' => 'badge-danger',
                                                        'delegated' => 'badge-info',
                                                        default => 'badge-secondary'
                                                    };
                                                @endphp
                                                <span class="badge {{ $approvalClass }}">
                                                    {{ ucfirst($approval->status) }}
                                                </span>
                                                @if($approval->approver)
                                                    <br><small class="text-muted">by {{ $approval->approver->name }}</small>
                                                @endif
                                            @else
                                                <span class="badge badge-light">No Approval</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($paper->submission_deadline)
                                                {{ $paper->submission_deadline->format('M d, Y H:i') }}
                                                @if($paper->is_overdue)
                                                    <br><small class="text-danger">
                                                        {{ $paper->submission_deadline->diffForHumans() }}
                                                    </small>
                                                @endif
                                            @else
                                                <span class="text-muted">No deadline</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('exam-papers.show', $paper) }}" 
                                                   class="btn btn-sm btn-info" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                
                                                @if($paper->canBeEditedBy(auth()->user()))
                                                    <a href="{{ route('exam-papers.edit', $paper) }}" 
                                                       class="btn btn-sm btn-warning" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                @endif
                                                
                                                @if($paper->canBeSubmittedBy(auth()->user()))
                                                    <form method="POST" action="{{ route('exam-papers.submit', $paper) }}" 
                                                          style="display: inline;" 
                                                          onsubmit="return confirm('Are you sure you want to submit this paper for review?')">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-primary" title="Submit for Review">
                                                            <i class="fas fa-paper-plane"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                                
                                                @if($paper->canBePublishedBy(auth()->user()))
                                                    <form method="POST" action="{{ route('exam-papers.publish', $paper) }}" 
                                                          style="display: inline;" 
                                                          onsubmit="return confirm('Are you sure you want to publish this paper?')">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-success" title="Publish">
                                                            <i class="fas fa-globe"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                                
                                                @if($paper->canBeApprovedBy(auth()->user()))
                                                    <form method="POST" action="{{ route('exam-papers.approve', $paper) }}" 
                                                          style="display: inline;" 
                                                          onsubmit="return confirm('Are you sure you want to approve this paper?')">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-success" title="Approve">
                                                            <i class="fas fa-thumbs-up"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                                
                                                @if($paper->canBeRejectedBy(auth()->user()))
                                                    <button type="button" class="btn btn-sm btn-danger" 
                                                            title="Reject" onclick="showRejectModal({{ $paper->id }})">
                                                        <i class="fas fa-thumbs-down"></i>
                                                    </button>
                                                @endif
                                                
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-secondary dropdown-toggle" 
                                                            data-toggle="dropdown" title="More Actions">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <div class="dropdown-menu">
                                                        <!-- Version Control Actions -->
                                                        <h6 class="dropdown-header">Version Control</h6>
                                                        <a class="dropdown-item" href="{{ route('exam-papers.version-history', $paper) }}">
                                                            <i class="fas fa-history mr-2"></i>Version History
                                                        </a>
                                                        @if($paper->currentVersion)
                                                            <a class="dropdown-item" href="{{ route('exam-papers.download-version', $paper->currentVersion) }}">
                                                                <i class="fas fa-download mr-2"></i>Download Current Version
                                                            </a>
                                                        @endif
                                                        
                                                        <!-- Approval Workflow Actions -->
                                                        <div class="dropdown-divider"></div>
                                                        <h6 class="dropdown-header">Approval Workflow</h6>
                                                        @if($paper->currentVersion && $paper->currentVersion->status === 'draft')
                                                            <a class="dropdown-item" href="{{ route('exam-papers.submit-for-approval', $paper) }}">
                                                                <i class="fas fa-paper-plane mr-2"></i>Submit for Approval
                                                            </a>
                                                        @endif
                                                        <a class="dropdown-item" href="{{ route('exam-papers.approval-status', $paper) }}">
                                                            <i class="fas fa-check-circle mr-2"></i>Approval Status
                                                        </a>
                                                        
                                                        <!-- Security & Audit -->
                                                        <div class="dropdown-divider"></div>
                                                        <h6 class="dropdown-header">Security & Audit</h6>
                                                        <a class="dropdown-item" href="{{ route('exam-papers.security-logs', $paper) }}">
                                                            <i class="fas fa-shield-alt mr-2"></i>Security Logs
                                                        </a>
                                                        
                                                        <!-- Standard Actions -->
                                                        <div class="dropdown-divider"></div>
                                                        <h6 class="dropdown-header">Standard Actions</h6>
                                                        <a class="dropdown-item" href="{{ route('exam-papers.duplicate', $paper) }}">
                                                            <i class="fas fa-copy mr-2"></i>Duplicate
                                                        </a>
                                                        <a class="dropdown-item" href="{{ route('exam-papers.export-pdf', $paper) }}">
                                                            <i class="fas fa-file-pdf mr-2"></i>Export PDF
                                                        </a>
                                                        @if($paper->canBeDeletedBy(auth()->user()))
                                                            <div class="dropdown-divider"></div>
                                                            <form method="POST" action="{{ route('exam-papers.destroy', $paper) }}" 
                                                                  onsubmit="return confirm('Are you sure you want to delete this paper?')">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="dropdown-item text-danger">
                                                                    <i class="fas fa-trash mr-2"></i>Delete
                                                                </button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="14" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-file-alt fa-3x mb-3"></i>
                                                <h5>No exam papers found</h5>
                                                <p>Create your first exam paper to get started.</p>
                                                <a href="{{ route('exam-papers.create') }}" class="btn btn-primary">
                                                    <i class="fas fa-plus mr-1"></i>Create Exam Paper
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($examPapers->hasPages())
                        <div class="d-flex justify-content-center mt-4">
                            {{ $examPapers->appends(request()->query())->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Exam Paper</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="rejectForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="rejection_reason">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" id="rejection_reason" class="form-control" 
                                  rows="4" required placeholder="Please provide a reason for rejection..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-thumbs-down mr-1"></i>Reject Paper
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function showRejectModal(paperId) {
    const form = document.getElementById('rejectForm');
    form.action = `/exam-papers/${paperId}/reject`;
    document.getElementById('rejection_reason').value = '';
    $('#rejectModal').modal('show');
}

// Auto-submit form on filter change
$(document).ready(function() {
    $('#subject_id, #class_id, #exam_id, #status').change(function() {
        $(this).closest('form').submit();
    });
    
    // Search with delay
    let searchTimeout;
    $('#search').on('input', function() {
        clearTimeout(searchTimeout);
        const form = $(this).closest('form');
        searchTimeout = setTimeout(function() {
            form.submit();
        }, 500);
    });
});
</script>
@endpush

@push('styles')
<style>
.info-box {
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.table-responsive {
    border-radius: 8px;
    overflow: hidden;
}

.btn-group .dropdown-menu {
    min-width: 150px;
}

.table-danger {
    background-color: rgba(220, 53, 69, 0.1);
}

code {
    font-size: 0.9em;
    padding: 2px 4px;
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 3px;
}
</style>
@endpush