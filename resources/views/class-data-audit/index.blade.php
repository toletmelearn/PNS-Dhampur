@extends('layouts.app')

@section('title', 'Class Data Audit System')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-shield-alt"></i> Class Data Audit System
                    </h3>
                    <div>
                        <button type="button" class="btn btn-info" onclick="refreshData()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                        <button type="button" class="btn btn-success" onclick="exportAuditData()">
                            <i class="fas fa-download"></i> Export
                        </button>
                        @can('approve_audit_changes')
                            <button type="button" class="btn btn-primary" onclick="bulkApprove()" id="bulkApproveBtn" style="display: none;">
                                <i class="fas fa-check-double"></i> Bulk Approve
                            </button>
                        @endcan
                    </div>
                </div>

                <!-- Dashboard Statistics -->
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-2">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4>{{ $statistics['total_audits'] ?? 0 }}</h4>
                                            <p class="mb-0">Total Audits</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-list fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4>{{ $statistics['pending_approvals'] ?? 0 }}</h4>
                                            <p class="mb-0">Pending Approvals</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-clock fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4>{{ $statistics['approved_changes'] ?? 0 }}</h4>
                                            <p class="mb-0">Approved</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-check fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-danger text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4>{{ $statistics['rejected_changes'] ?? 0 }}</h4>
                                            <p class="mb-0">Rejected</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-times fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-dark text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4>{{ $statistics['high_risk_changes'] ?? 0 }}</h4>
                                            <p class="mb-0">High Risk</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4>{{ $statistics['total_versions'] ?? 0 }}</h4>
                                            <p class="mb-0">Versions</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-code-branch fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-filter"></i> Filters
                                <button class="btn btn-sm btn-outline-secondary float-end" type="button" onclick="clearFilters()">
                                    <i class="fas fa-times"></i> Clear All
                                </button>
                            </h5>
                        </div>
                        <div class="card-body">
                            <form id="filterForm" method="GET">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label for="auditable_type" class="form-label">Entity Type</label>
                                        <select name="auditable_type" id="auditable_type" class="form-select">
                                            <option value="">All Types</option>
                                            @foreach($auditableTypes as $type)
                                                <option value="{{ $type }}" {{ request('auditable_type') == $type ? 'selected' : '' }}>
                                                    {{ ucfirst(str_replace('App\\Models\\', '', $type)) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="event_type" class="form-label">Event Type</label>
                                        <select name="event_type" id="event_type" class="form-select">
                                            <option value="">All Events</option>
                                            @foreach($eventTypes as $event)
                                                <option value="{{ $event }}" {{ request('event_type') == $event ? 'selected' : '' }}>
                                                    {{ ucfirst(str_replace('_', ' ', $event)) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="user_id" class="form-label">User</label>
                                        <select name="user_id" id="user_id" class="form-select">
                                            <option value="">All Users</option>
                                            @foreach($users as $user)
                                                <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                                    {{ $user->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="risk_level" class="form-label">Risk Level</label>
                                        <select name="risk_level" id="risk_level" class="form-select">
                                            <option value="">All Levels</option>
                                            <option value="low" {{ request('risk_level') == 'low' ? 'selected' : '' }}>Low</option>
                                            <option value="medium" {{ request('risk_level') == 'medium' ? 'selected' : '' }}>Medium</option>
                                            <option value="high" {{ request('risk_level') == 'high' ? 'selected' : '' }}>High</option>
                                            <option value="critical" {{ request('risk_level') == 'critical' ? 'selected' : '' }}>Critical</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-3">
                                        <label for="approval_status" class="form-label">Approval Status</label>
                                        <select name="approval_status" id="approval_status" class="form-select">
                                            <option value="">All Statuses</option>
                                            <option value="pending" {{ request('approval_status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="approved" {{ request('approval_status') == 'approved' ? 'selected' : '' }}>Approved</option>
                                            <option value="rejected" {{ request('approval_status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                            <option value="not_required" {{ request('approval_status') == 'not_required' ? 'selected' : '' }}>Not Required</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="date_from" class="form-label">Date From</label>
                                        <input type="date" name="date_from" id="date_from" class="form-control" value="{{ request('date_from') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="date_to" class="form-label">Date To</label>
                                        <input type="date" name="date_to" id="date_to" class="form-control" value="{{ request('date_to') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="search" class="form-label">Search</label>
                                        <input type="text" name="search" id="search" class="form-control" placeholder="Search description, user..." value="{{ request('search') }}">
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search"></i> Apply Filters
                                        </button>
                                        <button type="button" class="btn btn-secondary" onclick="clearFilters()">
                                            <i class="fas fa-times"></i> Clear
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Audit Trail Table -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="auditTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>
                                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                                    </th>
                                    <th>ID</th>
                                    <th>Entity</th>
                                    <th>Event</th>
                                    <th>User</th>
                                    <th>Description</th>
                                    <th>Risk Level</th>
                                    <th>Approval Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($audits as $audit)
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="audit-checkbox" value="{{ $audit->id }}" onchange="updateBulkActions()">
                                        </td>
                                        <td>{{ $audit->id }}</td>
                                        <td>
                                            <span class="badge bg-info">
                                                {{ ucfirst(str_replace('App\\Models\\', '', $audit->auditable_type)) }}
                                            </span>
                                            <br>
                                            <small class="text-muted">ID: {{ $audit->auditable_id }}</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $audit->event_type == 'created' ? 'success' : ($audit->event_type == 'deleted' ? 'danger' : 'warning') }}">
                                                {{ ucfirst(str_replace('_', ' ', $audit->event_type)) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($audit->user)
                                                <strong>{{ $audit->user->name }}</strong>
                                            @else
                                                <span class="text-muted">{{ $audit->user_name ?? 'System' }}</span>
                                            @endif
                                            <br>
                                            <small class="text-muted">{{ $audit->ip_address }}</small>
                                        </td>
                                        <td>
                                            <div class="text-truncate" style="max-width: 200px;" title="{{ $audit->description }}">
                                                {{ $audit->description }}
                                            </div>
                                            @if($audit->changed_fields && is_array($audit->changed_fields) && count($audit->changed_fields) > 0)
                                                <small class="text-muted">
                                                    Fields: {{ implode(', ', array_keys($audit->changed_fields)) }}
                                                </small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $audit->risk_level == 'critical' ? 'danger' : ($audit->risk_level == 'high' ? 'warning' : ($audit->risk_level == 'medium' ? 'info' : 'secondary')) }}">
                                                {{ ucfirst($audit->risk_level) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($audit->requires_approval)
                                                <span class="badge bg-{{ $audit->approval_status == 'approved' ? 'success' : ($audit->approval_status == 'rejected' ? 'danger' : 'warning') }}">
                                                    {{ ucfirst(str_replace('_', ' ', $audit->approval_status)) }}
                                                </span>
                                            @else
                                                <span class="badge bg-secondary">Not Required</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span title="{{ $audit->created_at->format('Y-m-d H:i:s') }}">
                                                {{ $audit->created_at->diffForHumans() }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('class-data-audit.show', $audit) }}" class="btn btn-sm btn-outline-primary" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @if($audit->versions_count > 0)
                                                    <button type="button" class="btn btn-sm btn-outline-info" onclick="showVersionHistory({{ $audit->id }})" title="Version History">
                                                        <i class="fas fa-code-branch"></i>
                                                    </button>
                                                @endif
                                                @if($audit->requires_approval && $audit->approval_status == 'pending')
                                                    @can('approve_audit_changes')
                                                        <button type="button" class="btn btn-sm btn-outline-success" onclick="approveChange({{ $audit->id }})" title="Approve">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="rejectChange({{ $audit->id }})" title="Reject">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    @endcan
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center py-4">
                                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">No audit records found</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($audits->hasPages())
                        <div class="d-flex justify-content-center">
                            {{ $audits->appends(request()->query())->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
@include('class-data-audit.modals.version-history')
@include('class-data-audit.modals.approval-actions')
@include('class-data-audit.modals.export-options')

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize tooltips
    $('[title]').tooltip();
    
    // Auto-refresh every 30 seconds if there are pending approvals
    @if($statistics['pending_approvals'] > 0)
        setInterval(function() {
            if (!$('.modal').hasClass('show')) {
                refreshData();
            }
        }, 30000);
    @endif
});

function refreshData() {
    window.location.reload();
}

function clearFilters() {
    $('#filterForm')[0].reset();
    window.location.href = '{{ route("class-data-audit.index") }}';
}

function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.audit-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
    
    updateBulkActions();
}

function updateBulkActions() {
    const checkedBoxes = document.querySelectorAll('.audit-checkbox:checked');
    const bulkApproveBtn = document.getElementById('bulkApproveBtn');
    
    if (checkedBoxes.length > 0) {
        bulkApproveBtn.style.display = 'inline-block';
    } else {
        bulkApproveBtn.style.display = 'none';
    }
}

function showVersionHistory(auditId) {
    // Load version history modal
    $('#versionHistoryModal').modal('show');
    loadVersionHistory(auditId);
}

function loadVersionHistory(auditId) {
    $.get(`/class-data-audit/${auditId}/version-history`)
        .done(function(response) {
            if (response.success) {
                renderVersionHistory(response.data);
            }
        })
        .fail(function() {
            showAlert('Error loading version history', 'error');
        });
}

function approveChange(auditId) {
    $('#approvalModal').modal('show');
    $('#approvalForm').data('audit-id', auditId);
    $('#approvalForm').data('action', 'approve');
    $('#approvalModalTitle').text('Approve Change');
    $('#approvalReasonLabel').text('Approval Reason (Optional)');
}

function rejectChange(auditId) {
    $('#approvalModal').modal('show');
    $('#approvalForm').data('audit-id', auditId);
    $('#approvalForm').data('action', 'reject');
    $('#approvalModalTitle').text('Reject Change');
    $('#approvalReasonLabel').text('Rejection Reason (Required)');
    $('#approvalReason').prop('required', true);
}

function bulkApprove() {
    const checkedBoxes = document.querySelectorAll('.audit-checkbox:checked');
    const auditIds = Array.from(checkedBoxes).map(cb => cb.value);
    
    if (auditIds.length === 0) {
        showAlert('Please select at least one audit record', 'warning');
        return;
    }
    
    Swal.fire({
        title: 'Bulk Approve Changes',
        text: `Are you sure you want to approve ${auditIds.length} selected changes?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Approve All',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            performBulkApproval(auditIds);
        }
    });
}

function performBulkApproval(auditIds) {
    $.post('/class-data-audit/bulk-approve', {
        audit_ids: auditIds,
        _token: $('meta[name="csrf-token"]').attr('content')
    })
    .done(function(response) {
        if (response.success) {
            showAlert('Changes approved successfully', 'success');
            refreshData();
        } else {
            showAlert(response.message || 'Failed to approve changes', 'error');
        }
    })
    .fail(function() {
        showAlert('Error performing bulk approval', 'error');
    });
}

function exportAuditData() {
    $('#exportModal').modal('show');
}

function showAlert(message, type) {
    Swal.fire({
        title: type === 'success' ? 'Success' : (type === 'error' ? 'Error' : 'Warning'),
        text: message,
        icon: type,
        timer: 3000,
        showConfirmButton: false
    });
}
</script>
@endpush