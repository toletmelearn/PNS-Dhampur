@extends('layouts.app')

@section('title', 'Approval Status - ' . $examPaper->title)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="card-title mb-0">
                                <i class="fas fa-check-circle mr-2"></i>Approval Status
                            </h3>
                            <p class="text-muted mb-0">{{ $examPaper->title }} ({{ $examPaper->paper_code }})</p>
                        </div>
                        <div>
                            <a href="{{ route('exam-papers.show', $examPaper) }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-1"></i>Back to Paper
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <!-- Current Approval Status -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">
                                <i class="fas fa-clipboard-check mr-2"></i>Current Approval Workflow
                            </h4>
                        </div>
                        <div class="card-body">
                            @if($currentApproval)
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-{{ $currentApproval->status === 'approved' ? 'success' : ($currentApproval->status === 'rejected' ? 'danger' : 'warning') }}">
                                                <i class="fas fa-{{ $currentApproval->status === 'approved' ? 'check' : ($currentApproval->status === 'rejected' ? 'times' : 'clock') }}"></i>
                                            </span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Status</span>
                                                <span class="info-box-number">{{ ucfirst($currentApproval->status) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-info">
                                                <i class="fas fa-user"></i>
                                            </span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Assigned To</span>
                                                <span class="info-box-number">{{ $currentApproval->approver->name ?? 'Unassigned' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-12">
                                        <table class="table table-bordered">
                                            <tr>
                                                <th width="200">Submitted At:</th>
                                                <td>{{ $currentApproval->created_at->format('M d, Y H:i A') }}</td>
                                            </tr>
                                            <tr>
                                                <th>Priority Level:</th>
                                                <td>
                                                    <span class="badge badge-{{ $currentApproval->priority === 'high' ? 'danger' : ($currentApproval->priority === 'medium' ? 'warning' : 'info') }}">
                                                        {{ ucfirst($currentApproval->priority) }}
                                                    </span>
                                                </td>
                                            </tr>
                                            @if($currentApproval->approval_deadline)
                                                <tr>
                                                    <th>Deadline:</th>
                                                    <td>
                                                        {{ $currentApproval->approval_deadline->format('M d, Y H:i A') }}
                                                        @if($currentApproval->approval_deadline->isPast())
                                                            <span class="badge badge-danger ml-2">Overdue</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endif
                                            @if($currentApproval->comments)
                                                <tr>
                                                    <th>Comments:</th>
                                                    <td>{{ $currentApproval->comments }}</td>
                                                </tr>
                                            @endif
                                            @if($currentApproval->feedback)
                                                <tr>
                                                    <th>Feedback:</th>
                                                    <td>{{ $currentApproval->feedback }}</td>
                                                </tr>
                                            @endif
                                            @if($currentApproval->digital_signature)
                                                <tr>
                                                    <th>Digital Signature:</th>
                                                    <td>
                                                        <i class="fas fa-certificate text-success mr-1"></i>
                                                        Digitally Signed
                                                        <small class="text-muted">({{ $currentApproval->updated_at->format('M d, Y H:i A') }})</small>
                                                    </td>
                                                </tr>
                                            @endif
                                        </table>
                                    </div>
                                </div>

                                <!-- Approval Actions -->
                                @if($currentApproval->status === 'pending' && auth()->user()->id === $currentApproval->approver_id)
                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <div class="card bg-light">
                                                <div class="card-header">
                                                    <h5 class="card-title mb-0">
                                                        <i class="fas fa-gavel mr-2"></i>Approval Actions
                                                    </h5>
                                                </div>
                                                <div class="card-body">
                                                    <form id="approvalForm" method="POST">
                                                        @csrf
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <div class="form-group">
                                                                    <label for="feedback">Feedback:</label>
                                                                    <textarea name="feedback" id="feedback" class="form-control" rows="3" placeholder="Provide feedback for the submitter..."></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="form-group">
                                                                    <label for="comments">Internal Comments:</label>
                                                                    <textarea name="comments" id="comments" class="form-control" rows="3" placeholder="Internal comments (not visible to submitter)..."></textarea>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-12">
                                                                <div class="form-check mb-3">
                                                                    <input type="checkbox" class="form-check-input" id="digital_signature" name="digital_signature" value="1">
                                                                    <label class="form-check-label" for="digital_signature">
                                                                        <i class="fas fa-certificate mr-1"></i>Apply Digital Signature
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="btn-group" role="group">
                                                            <button type="button" class="btn btn-success" onclick="submitApproval('approve')">
                                                                <i class="fas fa-check mr-1"></i>Approve
                                                            </button>
                                                            <button type="button" class="btn btn-danger" onclick="submitApproval('reject')">
                                                                <i class="fas fa-times mr-1"></i>Reject
                                                            </button>
                                                            <button type="button" class="btn btn-info" onclick="showDelegateModal()">
                                                                <i class="fas fa-user-friends mr-1"></i>Delegate
                                                            </button>
                                                            <button type="button" class="btn btn-warning" onclick="showExtendDeadlineModal()">
                                                                <i class="fas fa-clock mr-1"></i>Extend Deadline
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @else
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    This exam paper has not been submitted for approval yet.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Approval Statistics -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">
                                <i class="fas fa-chart-pie mr-2"></i>Approval Statistics
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="info-box bg-info mb-3">
                                <span class="info-box-icon"><i class="fas fa-list"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Approvals</span>
                                    <span class="info-box-number">{{ $approvalHistory->count() }}</span>
                                </div>
                            </div>
                            <div class="info-box bg-success mb-3">
                                <span class="info-box-icon"><i class="fas fa-check"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Approved</span>
                                    <span class="info-box-number">{{ $approvalHistory->where('status', 'approved')->count() }}</span>
                                </div>
                            </div>
                            <div class="info-box bg-danger mb-3">
                                <span class="info-box-icon"><i class="fas fa-times"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Rejected</span>
                                    <span class="info-box-number">{{ $approvalHistory->where('status', 'rejected')->count() }}</span>
                                </div>
                            </div>
                            <div class="info-box bg-warning">
                                <span class="info-box-icon"><i class="fas fa-clock"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Pending</span>
                                    <span class="info-box-number">{{ $approvalHistory->where('status', 'pending')->count() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Approval History -->
            <div class="card mt-3">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fas fa-history mr-2"></i>Approval History
                    </h4>
                </div>
                <div class="card-body">
                    @if($approvalHistory->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Version</th>
                                        <th>Approver</th>
                                        <th>Status</th>
                                        <th>Priority</th>
                                        <th>Submitted</th>
                                        <th>Processed</th>
                                        <th>Feedback</th>
                                        <th>Digital Signature</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($approvalHistory as $approval)
                                        <tr>
                                            <td>
                                                <span class="badge badge-primary">v{{ $approval->examPaperVersion->version_number }}</span>
                                            </td>
                                            <td>{{ $approval->approver->name ?? 'Unassigned' }}</td>
                                            <td>
                                                <span class="badge badge-{{ $approval->status === 'approved' ? 'success' : ($approval->status === 'rejected' ? 'danger' : 'warning') }}">
                                                    {{ ucfirst($approval->status) }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-{{ $approval->priority === 'high' ? 'danger' : ($approval->priority === 'medium' ? 'warning' : 'info') }}">
                                                    {{ ucfirst($approval->priority) }}
                                                </span>
                                            </td>
                                            <td>{{ $approval->created_at->format('M d, Y H:i') }}</td>
                                            <td>
                                                @if($approval->status !== 'pending')
                                                    {{ $approval->updated_at->format('M d, Y H:i') }}
                                                @else
                                                    <span class="text-muted">Pending</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($approval->feedback)
                                                    <button type="button" class="btn btn-sm btn-info" data-toggle="tooltip" title="{{ $approval->feedback }}">
                                                        <i class="fas fa-comment"></i>
                                                    </button>
                                                @else
                                                    <span class="text-muted">No feedback</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($approval->digital_signature)
                                                    <i class="fas fa-certificate text-success" title="Digitally Signed"></i>
                                                @else
                                                    <span class="text-muted">No</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i>
                            No approval history found for this exam paper.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delegate Modal -->
<div class="modal fade" id="delegateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delegate Approval</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="delegateForm" method="POST" action="{{ route('exam-papers.delegate-approval', $examPaper) }}">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="delegate_to">Delegate to:</label>
                        <select name="delegate_to" id="delegate_to" class="form-control" required>
                            <option value="">Select approver...</option>
                            @foreach($availableApprovers as $approver)
                                <option value="{{ $approver->id }}">{{ $approver->name }} ({{ $approver->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="delegation_reason">Reason for delegation:</label>
                        <textarea name="delegation_reason" id="delegation_reason" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">Delegate</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Extend Deadline Modal -->
<div class="modal fade" id="extendDeadlineModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Extend Approval Deadline</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="extendDeadlineForm" method="POST" action="{{ route('exam-papers.extend-deadline', $examPaper) }}">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="new_deadline">New deadline:</label>
                        <input type="datetime-local" name="new_deadline" id="new_deadline" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="extension_reason">Reason for extension:</label>
                        <textarea name="extension_reason" id="extension_reason" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Extend Deadline</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function submitApproval(action) {
    const form = document.getElementById('approvalForm');
    const actionUrl = action === 'approve' 
        ? '{{ route("exam-papers.approve", $examPaper) }}' 
        : '{{ route("exam-papers.reject", $examPaper) }}';
    
    if (confirm(`Are you sure you want to ${action} this exam paper?`)) {
        form.action = actionUrl;
        form.submit();
    }
}

function showDelegateModal() {
    $('#delegateModal').modal('show');
}

function showExtendDeadlineModal() {
    $('#extendDeadlineModal').modal('show');
}

// Initialize tooltips
$(document).ready(function() {
    $('[data-toggle="tooltip"]').tooltip();
});
</script>
@endpush
@endsection