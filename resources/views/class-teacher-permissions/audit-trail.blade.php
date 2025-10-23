@extends('layouts.app')

@section('title', 'Audit Trail')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-history"></i> Audit Trail
                    </h3>
                    <div>
                        <a href="{{ route('class-teacher-permissions.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Permissions
                        </a>
                        <button type="button" class="btn btn-success" onclick="exportReport()">
                            <i class="fas fa-download"></i> Export Report
                        </button>
                        @can('approve-corrections')
                            <button type="button" class="btn btn-primary" onclick="bulkApprove()" id="bulkApproveBtn" style="display: none;">
                                <i class="fas fa-check-double"></i> Bulk Approve
                            </button>
                        @endcan
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4>{{ $auditTrails->total() }}</h4>
                                            <p class="mb-0">Total Activities</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-list fa-2x"></i>
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
                                            <h4>{{ $auditTrails->where('status', 'pending_approval')->count() }}</h4>
                                            <p class="mb-0">Pending Approval</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-clock fa-2x"></i>
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
                                            <h4>{{ $auditTrails->where('status', 'approved')->count() }}</h4>
                                            <p class="mb-0">Approved</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-check fa-2x"></i>
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
                                            <h4>{{ $auditTrails->where('status', 'rejected')->count() }}</h4>
                                            <p class="mb-0">Rejected</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-times fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <form method="GET" class="row g-3 mb-4" id="filterForm">
                        <div class="col-md-2">
                            <label class="form-label">Teacher</label>
                            <select name="user_id" class="form-select">
                                <option value="">All Teachers</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher->id }}" {{ request('user_id') == $teacher->id ? 'selected' : '' }}>
                                        {{ $teacher->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Class</label>
                            <select name="class_id" class="form-select">
                                <option value="">All Classes</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                                        {{ $class->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Subject</label>
                            <select name="subject_id" class="form-select">
                                <option value="">All Subjects</option>
                                @foreach($subjects as $subject)
                                    <option value="{{ $subject->id }}" {{ request('subject_id') == $subject->id ? 'selected' : '' }}>
                                        {{ $subject->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Event</label>
                            <select name="event" class="form-select">
                                <option value="">All Events</option>
                                @foreach($events as $event)
                                    <option value="{{ $event }}" {{ request('event') == $event ? 'selected' : '' }}>
                                        {{ ucfirst($event) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                @foreach($statuses as $status)
                                    <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                                        {{ ucfirst(str_replace('_', ' ', $status)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date From</label>
                            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date To</label>
                            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Academic Year</label>
                            <select name="academic_year" class="form-select">
                                <option value="">All Years</option>
                                @foreach($academicYears as $year)
                                    <option value="{{ $year }}" {{ request('academic_year') == $year ? 'selected' : '' }}>
                                        {{ $year }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" class="form-control" placeholder="Search..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                                <a href="{{ route('class-teacher-permissions.audit-trail') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            </div>
                        </div>
                    </form>

                    <!-- Audit Trail Table -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    @can('approve-corrections')
                                        <th width="30">
                                            <input type="checkbox" id="selectAll" class="form-check-input">
                                        </th>
                                    @endcan
                                    <th>Date/Time</th>
                                    <th>User</th>
                                    <th>Event</th>
                                    <th>Details</th>
                                    <th>Changes</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($auditTrails as $trail)
                                    <tr class="{{ $trail->status === 'pending_approval' ? 'table-warning' : '' }}">
                                        @can('approve-corrections')
                                            <td>
                                                @if($trail->status === 'pending_approval')
                                                    <input type="checkbox" class="form-check-input trail-checkbox" value="{{ $trail->id }}">
                                                @endif
                                            </td>
                                        @endcan
                                        <td>
                                            <small>
                                                {{ $trail->created_at->format('d/m/Y') }}<br>
                                                {{ $trail->created_at->format('H:i:s') }}
                                            </small>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="{{ $trail->event_icon }} me-2"></i>
                                                <div>
                                                    <strong>{{ $trail->user ? $trail->user->name : 'System' }}</strong>
                                                    @if($trail->user)
                                                        <br><small class="text-muted">{{ $trail->user->email }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $trail->event === 'created' ? 'success' : ($trail->event === 'updated' ? 'primary' : ($trail->event === 'deleted' ? 'danger' : 'warning')) }}">
                                                {{ ucfirst($trail->event) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div>
                                                <strong>Model:</strong> {{ class_basename($trail->auditable_type) }}<br>
                                                @if($trail->student)
                                                    <strong>Student:</strong> {{ $trail->student->name }}<br>
                                                @endif
                                                @if($trail->class)
                                                    <strong>Class:</strong> {{ $trail->class->name }}<br>
                                                @endif
                                                @if($trail->subject)
                                                    <strong>Subject:</strong> {{ $trail->subject->name }}<br>
                                                @endif
                                                @if($trail->academic_year)
                                                    <strong>Year:</strong> {{ $trail->academic_year }}
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            @if($trail->formatted_changes)
                                                <small class="text-muted">{{ Str::limit($trail->formatted_changes, 100) }}</small>
                                            @else
                                                <small class="text-muted">No changes recorded</small>
                                            @endif
                                            @if($trail->correction_reason)
                                                <br><strong>Reason:</strong> <small>{{ Str::limit($trail->correction_reason, 50) }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge {{ $trail->status_badge_class }}">{{ $trail->status_badge }}</span>
                                            @if($trail->approved_at)
                                                <br><small class="text-success">
                                                    Approved by {{ $trail->approvedBy->name ?? 'Unknown' }}
                                                    <br>{{ $trail->approved_at->format('d/m/Y H:i') }}
                                                </small>
                                            @elseif($trail->rejected_at)
                                                <br><small class="text-danger">
                                                    Rejected by {{ $trail->rejectedBy->name ?? 'Unknown' }}
                                                    <br>{{ $trail->rejected_at->format('d/m/Y H:i') }}
                                                </small>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-info" 
                                                        onclick="viewDetails({{ $trail->id }})" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                @can('approve-corrections')
                                                    @if($trail->status === 'pending_approval')
                                                        <button type="button" class="btn btn-sm btn-outline-success" 
                                                                onclick="approveCorrection({{ $trail->id }})" title="Approve">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                onclick="rejectCorrection({{ $trail->id }})" title="Reject">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    @endif
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ can('approve-corrections') ? '8' : '7' }}" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-history fa-3x mb-3"></i>
                                                <p>No audit trail records found</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                            Showing {{ $auditTrails->firstItem() ?? 0 }} to {{ $auditTrails->lastItem() ?? 0 }} 
                            of {{ $auditTrails->total() }} records
                        </div>
                        {{ $auditTrails->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Audit Trail Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailsContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Approve Correction Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Approve Correction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="approveForm" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        Are you sure you want to approve this correction?
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Approval Notes (Optional)</label>
                        <textarea name="approval_notes" class="form-control" rows="3" 
                                  placeholder="Add any notes about this approval..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Approve Correction</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Correction Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Correction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="rejectForm" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        Are you sure you want to reject this correction?
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" class="form-control" rows="3" required
                                  placeholder="Please provide a reason for rejecting this correction..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Correction</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Select all functionality
document.getElementById('selectAll')?.addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.trail-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
    toggleBulkApproveButton();
});

// Individual checkbox change
document.querySelectorAll('.trail-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', toggleBulkApproveButton);
});

function toggleBulkApproveButton() {
    const checkedBoxes = document.querySelectorAll('.trail-checkbox:checked');
    const bulkBtn = document.getElementById('bulkApproveBtn');
    if (bulkBtn) {
        bulkBtn.style.display = checkedBoxes.length > 0 ? 'inline-block' : 'none';
    }
}

function viewDetails(trailId) {
    // Implementation for viewing details
    const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
    modal.show();
}

function approveCorrection(trailId) {
    const modal = new bootstrap.Modal(document.getElementById('approveModal'));
    const form = document.getElementById('approveForm');
    form.action = '{{ route("class-teacher-permissions.audit-trail.approve", ["auditTrail" => "__ID__"]) }}'.replace('__ID__', trailId);
    modal.show();
}

function rejectCorrection(trailId) {
    const modal = new bootstrap.Modal(document.getElementById('rejectModal'));
    const form = document.getElementById('rejectForm');
    form.action = '{{ route("class-teacher-permissions.audit-trail.reject", ["auditTrail" => "__ID__"]) }}'.replace('__ID__', trailId);
    modal.show();
}

function bulkApprove() {
    const checkedBoxes = document.querySelectorAll('.trail-checkbox:checked');
    const ids = Array.from(checkedBoxes).map(cb => cb.value);
    
    if (ids.length === 0) {
        alert('Please select at least one correction to approve.');
        return;
    }

    if (confirm(`Are you sure you want to approve ${ids.length} correction(s)?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("class-teacher-permissions.audit-trail.bulk-approve") }}';
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        form.appendChild(csrfToken);

        ids.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'audit_trail_ids[]';
            input.value = id;
            form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
    }
}

function exportReport() {
    const form = document.getElementById('filterForm');
    const exportForm = form.cloneNode(true);
    exportForm.action = '{{ route("class-teacher-permissions.audit-trail.export") }}';
    exportForm.style.display = 'none';
    document.body.appendChild(exportForm);
    exportForm.submit();
    document.body.removeChild(exportForm);
}

// Auto-submit form on filter change
document.querySelectorAll('#filterForm select, #filterForm input[type="date"]').forEach(element => {
    element.addEventListener('change', function() {
        this.form.submit();
    });
});
</script>
@endpush