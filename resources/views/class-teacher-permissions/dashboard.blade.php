@extends('layouts.app')

@section('title', 'Class Teacher Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-gradient-primary text-white">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <h2 class="mb-1">Welcome, {{ Auth::user()->name }}!</h2>
                            <p class="mb-0">Class Teacher Dashboard - Manage your assigned classes and student records</p>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chalkboard-teacher fa-3x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Activity Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3>{{ $activitySummary['total_activities'] }}</h3>
                            <p class="mb-0">Total Activities</p>
                            <small>Last 30 days</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-chart-line fa-2x"></i>
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
                            <h3>{{ $permissions->count() }}</h3>
                            <p class="mb-0">Active Permissions</p>
                            <small>Current assignments</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-key fa-2x"></i>
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
                            <h3>{{ $pendingCorrections->count() }}</h3>
                            <p class="mb-0">Pending Approvals</p>
                            <small>Corrections awaiting review</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3>{{ $activitySummary['by_event']['updated'] ?? 0 }}</h3>
                            <p class="mb-0">Records Updated</p>
                            <small>Last 30 days</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-edit fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- My Permissions -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-key text-success"></i> My Permissions
                    </h5>
                    <a href="{{ route('class-teacher-permissions.index') }}" class="btn btn-sm btn-outline-primary">
                        View All
                    </a>
                </div>
                <div class="card-body">
                    @forelse($permissions as $permission)
                        <div class="permission-item mb-3 p-3 border rounded">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h6 class="mb-1">
                                        <span class="badge bg-info">{{ $permission->class->name }}</span>
                                        @if($permission->subject)
                                            <span class="badge bg-success">{{ $permission->subject->name }}</span>
                                        @else
                                            <span class="badge bg-secondary">All Subjects</span>
                                        @endif
                                    </h6>
                                    <small class="text-muted">
                                        Academic Year: {{ $permission->academic_year }} | 
                                        Valid until: {{ $permission->valid_until ? $permission->valid_until->format('d/m/Y') : 'Indefinite' }}
                                    </small>
                                    <div class="mt-2">
                                        @if($permission->can_view_records)
                                            <span class="badge bg-primary me-1">View</span>
                                        @endif
                                        @if($permission->can_edit_records)
                                            <span class="badge bg-warning me-1">Edit</span>
                                        @endif
                                        @if($permission->can_add_records)
                                            <span class="badge bg-success me-1">Add</span>
                                        @endif
                                        @if($permission->can_delete_records)
                                            <span class="badge bg-danger me-1">Delete</span>
                                        @endif
                                        @if($permission->can_export_reports)
                                            <span class="badge bg-info me-1">Export</span>
                                        @endif
                                        @if($permission->can_approve_corrections)
                                            <span class="badge bg-purple me-1">Approve</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4 text-end">
                                    @if($permission->isCurrentlyValid())
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-warning">Inactive</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4">
                            <i class="fas fa-key fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No active permissions assigned</p>
                            <small>Contact administrator to get permissions for managing student records</small>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-history text-info"></i> Recent Activities
                    </h5>
                    <a href="{{ route('class-teacher-permissions.audit-trail') }}" class="btn btn-sm btn-outline-primary">
                        View All
                    </a>
                </div>
                <div class="card-body">
                    @forelse($recentActivities as $activity)
                        <div class="activity-item d-flex mb-3 pb-3 border-bottom">
                            <div class="activity-icon me-3">
                                <i class="{{ $activity->event_icon }}"></i>
                            </div>
                            <div class="activity-content flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">{{ ucfirst($activity->event) }} {{ class_basename($activity->auditable_type) }}</h6>
                                        @if($activity->student)
                                            <p class="mb-1 text-muted">Student: {{ $activity->student->name }}</p>
                                        @endif
                                        @if($activity->class)
                                            <small class="text-muted">
                                                Class: {{ $activity->class->name }}
                                                @if($activity->subject)
                                                    | Subject: {{ $activity->subject->name }}
                                                @endif
                                            </small>
                                        @endif
                                        @if($activity->correction_reason)
                                            <br><small class="text-warning">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                {{ Str::limit($activity->correction_reason, 50) }}
                                            </small>
                                        @endif
                                    </div>
                                    <div class="text-end">
                                        {!! $activity->status_badge !!}
                                        <br><small class="text-muted">{{ $activity->created_at->diffForHumans() }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4">
                            <i class="fas fa-history fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No recent activities</p>
                            <small>Your activities will appear here once you start managing student records</small>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Corrections (if user can approve) -->
    @if($pendingCorrections->count() > 0)
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-exclamation-triangle text-warning"></i> Pending Corrections
                        </h5>
                        <a href="{{ route('class-teacher-permissions.audit-trail', ['status' => 'pending_approval']) }}" 
                           class="btn btn-sm btn-outline-warning">
                            View All Pending
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Teacher</th>
                                        <th>Student</th>
                                        <th>Changes</th>
                                        <th>Reason</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pendingCorrections as $correction)
                                        <tr>
                                            <td>
                                                <small>{{ $correction->created_at->format('d/m/Y H:i') }}</small>
                                            </td>
                                            <td>{{ $correction->user ? $correction->user->name : 'System' }}</td>
                                            <td>{{ $correction->student ? $correction->student->name : 'N/A' }}</td>
                                            <td>
                                                <small>{{ Str::limit($correction->formatted_changes, 50) }}</small>
                                            </td>
                                            <td>
                                                <small>{{ Str::limit($correction->correction_reason, 30) }}</small>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-outline-success" 
                                                            onclick="quickApprove({{ $correction->id }})" title="Approve">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            onclick="quickReject({{ $correction->id }})" title="Reject">
                                                        <i class="fas fa-times"></i>
                                                    </button>
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
        </div>
    @endif

    <!-- Quick Actions -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-bolt text-primary"></i> Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <a href="{{ route('sr-register.index') }}" class="btn btn-outline-primary btn-lg w-100 mb-3">
                                <i class="fas fa-book fa-2x mb-2"></i>
                                <br>Student Records
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('biometric-attendance.index') }}" class="btn btn-outline-success btn-lg w-100 mb-3">
                                <i class="fas fa-fingerprint fa-2x mb-2"></i>
                                <br>Attendance
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('exam-papers.index') }}" class="btn btn-outline-warning btn-lg w-100 mb-3">
                                <i class="fas fa-file-alt fa-2x mb-2"></i>
                                <br>Exam Papers
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('class-teacher-permissions.audit-trail') }}" class="btn btn-outline-info btn-lg w-100 mb-3">
                                <i class="fas fa-history fa-2x mb-2"></i>
                                <br>Audit Trail
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Approve Modal -->
<div class="modal fade" id="quickApproveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Quick Approve</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="quickApproveForm" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        Approve this correction?
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes (Optional)</label>
                        <textarea name="approval_notes" class="form-control" rows="2" 
                                  placeholder="Quick approval notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Approve</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Quick Reject Modal -->
<div class="modal fade" id="quickRejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Quick Reject</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="quickRejectForm" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        Reject this correction?
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" class="form-control" rows="2" required
                                  placeholder="Reason for rejection..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.bg-gradient-primary {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
}

.permission-item {
    transition: all 0.3s ease;
}

.permission-item:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transform: translateY(-1px);
}

.activity-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background-color: #f8f9fa;
}

.bg-purple {
    background-color: #6f42c1 !important;
}

.btn-lg {
    padding: 1rem;
    text-align: center;
}

.opacity-75 {
    opacity: 0.75;
}
</style>
@endpush

@push('scripts')
<script>
function quickApprove(correctionId) {
    const modal = new bootstrap.Modal(document.getElementById('quickApproveModal'));
    const form = document.getElementById('quickApproveForm');
    form.action = `/audit-trail/${correctionId}/approve`;
    modal.show();
}

function quickReject(correctionId) {
    const modal = new bootstrap.Modal(document.getElementById('quickRejectModal'));
    const form = document.getElementById('quickRejectForm');
    form.action = `/audit-trail/${correctionId}/reject`;
    modal.show();
}

// Auto-refresh dashboard every 5 minutes
setInterval(function() {
    location.reload();
}, 300000);
</script>
@endpush