@extends('layouts.app')

@section('title', 'Audit Details - ' . $audit->id)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-shield-alt"></i> Audit Details - #{{ $audit->id }}
                    </h3>
                    <div>
                        <a href="{{ route('class-data-audit.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Audit Trail
                        </a>
                        @if($audit->requires_approval && $audit->approval_status == 'pending')
                            @can('approve_audit_changes')
                                <button type="button" class="btn btn-success" onclick="approveChange({{ $audit->id }})">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                                <button type="button" class="btn btn-danger" onclick="rejectChange({{ $audit->id }})">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            @endcan
                        @endif
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <!-- Main Audit Information -->
                        <div class="col-md-8">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Audit Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <table class="table table-borderless">
                                                <tr>
                                                    <td><strong>Audit ID:</strong></td>
                                                    <td>{{ $audit->id }}</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Entity Type:</strong></td>
                                                    <td>
                                                        <span class="badge bg-info">
                                                            {{ ucfirst(str_replace('App\\Models\\', '', $audit->auditable_type)) }}
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Entity ID:</strong></td>
                                                    <td>{{ $audit->auditable_id }}</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Event Type:</strong></td>
                                                    <td>
                                                        <span class="badge bg-{{ $audit->event_type == 'created' ? 'success' : ($audit->event_type == 'deleted' ? 'danger' : 'warning') }}">
                                                            {{ ucfirst(str_replace('_', ' ', $audit->event_type)) }}
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Risk Level:</strong></td>
                                                    <td>
                                                        <span class="badge bg-{{ $audit->risk_level == 'critical' ? 'danger' : ($audit->risk_level == 'high' ? 'warning' : ($audit->risk_level == 'medium' ? 'info' : 'secondary')) }}">
                                                            {{ ucfirst($audit->risk_level) }}
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Created At:</strong></td>
                                                    <td>{{ $audit->created_at->format('Y-m-d H:i:s') }}</td>
                                                </tr>
                                            </table>
                                        </div>
                                        <div class="col-md-6">
                                            <table class="table table-borderless">
                                                <tr>
                                                    <td><strong>User:</strong></td>
                                                    <td>
                                                        @if($audit->user)
                                                            <strong>{{ $audit->user->name }}</strong>
                                                        @else
                                                            <span class="text-muted">{{ $audit->user_name ?? 'System' }}</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><strong>IP Address:</strong></td>
                                                    <td>{{ $audit->ip_address }}</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>User Agent:</strong></td>
                                                    <td>
                                                        <div class="text-truncate" style="max-width: 200px;" title="{{ $audit->user_agent }}">
                                                            {{ $audit->user_agent }}
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Session ID:</strong></td>
                                                    <td>
                                                        <code>{{ substr($audit->session_id, 0, 10) }}...</code>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Checksum:</strong></td>
                                                    <td>
                                                        <code>{{ substr($audit->checksum, 0, 10) }}...</code>
                                                        @if($audit->verifyIntegrity())
                                                            <i class="fas fa-check-circle text-success" title="Integrity verified"></i>
                                                        @else
                                                            <i class="fas fa-exclamation-triangle text-danger" title="Integrity check failed"></i>
                                                        @endif
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>

                                    @if($audit->description)
                                        <div class="mt-3">
                                            <strong>Description:</strong>
                                            <p class="mt-2">{{ $audit->description }}</p>
                                        </div>
                                    @endif

                                    @if($audit->tags && is_array($audit->tags) && count($audit->tags) > 0)
                                        <div class="mt-3">
                                            <strong>Tags:</strong>
                                            <div class="mt-2">
                                                @foreach($audit->tags as $tag)
                                                    <span class="badge bg-secondary me-1">{{ $tag }}</span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Data Changes -->
                            @if($audit->old_values || $audit->new_values || $audit->changed_fields)
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-exchange-alt"></i> Data Changes</h5>
                                    </div>
                                    <div class="card-body">
                                        @if($audit->changed_fields && is_array($audit->changed_fields) && count($audit->changed_fields) > 0)
                                            <div class="mb-3">
                                                <strong>Changed Fields:</strong>
                                                <div class="mt-2">
                                                    @foreach(array_keys($audit->changed_fields) as $field)
                                                        <span class="badge bg-info me-1">{{ $field }}</span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        @if($audit->old_values || $audit->new_values)
                                            <div class="row">
                                                @if($audit->old_values)
                                                    <div class="col-md-6">
                                                        <h6><i class="fas fa-minus-circle text-danger"></i> Old Values</h6>
                                                        <pre class="bg-light p-3 rounded"><code>{{ json_encode($audit->old_values, JSON_PRETTY_PRINT) }}</code></pre>
                                                    </div>
                                                @endif
                                                @if($audit->new_values)
                                                    <div class="col-md-6">
                                                        <h6><i class="fas fa-plus-circle text-success"></i> New Values</h6>
                                                        <pre class="bg-light p-3 rounded"><code>{{ json_encode($audit->new_values, JSON_PRETTY_PRINT) }}</code></pre>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <!-- Metadata -->
                            @if($audit->metadata && is_array($audit->metadata) && count($audit->metadata) > 0)
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-tags"></i> Metadata</h5>
                                    </div>
                                    <div class="card-body">
                                        <pre class="bg-light p-3 rounded"><code>{{ json_encode($audit->metadata, JSON_PRETTY_PRINT) }}</code></pre>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Sidebar -->
                        <div class="col-md-4">
                            <!-- Approval Status -->
                            @if($audit->requires_approval)
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-clipboard-check"></i> Approval Status</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="text-center mb-3">
                                            <span class="badge bg-{{ $audit->approval_status == 'approved' ? 'success' : ($audit->approval_status == 'rejected' ? 'danger' : 'warning') }} fs-6">
                                                {{ ucfirst(str_replace('_', ' ', $audit->approval_status)) }}
                                            </span>
                                        </div>

                                        @if($audit->approved_by && $audit->approved_at)
                                            <p><strong>Approved By:</strong> {{ $audit->approvedBy->name ?? 'Unknown' }}</p>
                                            <p><strong>Approved At:</strong> {{ $audit->approved_at->format('Y-m-d H:i:s') }}</p>
                                        @endif

                                        @if($approvals->count() > 0)
                                            <h6 class="mt-3">Approval Workflow:</h6>
                                            @foreach($approvals as $approval)
                                                <div class="border-start border-3 ps-3 mb-3">
                                                    <div class="d-flex justify-content-between">
                                                        <span class="badge bg-{{ $approval->status == 'approved' ? 'success' : ($approval->status == 'rejected' ? 'danger' : 'warning') }}">
                                                            {{ ucfirst($approval->status) }}
                                                        </span>
                                                        <small class="text-muted">{{ $approval->created_at->diffForHumans() }}</small>
                                                    </div>
                                                    <p class="mb-1"><strong>Type:</strong> {{ ucfirst(str_replace('_', ' ', $approval->approval_type)) }}</p>
                                                    @if($approval->assignedTo)
                                                        <p class="mb-1"><strong>Assigned To:</strong> {{ $approval->assignedTo->name }}</p>
                                                    @endif
                                                    @if($approval->approvedBy)
                                                        <p class="mb-1"><strong>Approved By:</strong> {{ $approval->approvedBy->name }}</p>
                                                    @endif
                                                    @if($approval->request_reason)
                                                        <p class="mb-0"><strong>Reason:</strong> {{ $approval->request_reason }}</p>
                                                    @endif
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <!-- Version History -->
                            @if($versions->count() > 0)
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-code-branch"></i> Version History</h5>
                                    </div>
                                    <div class="card-body">
                                        @foreach($versions->take(5) as $version)
                                            <div class="border-start border-3 ps-3 mb-3">
                                                <div class="d-flex justify-content-between">
                                                    <span class="badge bg-info">v{{ $version->version_number }}</span>
                                                    <small class="text-muted">{{ $version->created_at->diffForHumans() }}</small>
                                                </div>
                                                <p class="mb-1"><strong>Type:</strong> {{ ucfirst($version->version_type) }}</p>
                                                @if($version->createdBy)
                                                    <p class="mb-1"><strong>Created By:</strong> {{ $version->createdBy->name }}</p>
                                                @endif
                                                @if($version->changes_summary)
                                                    <p class="mb-0"><strong>Summary:</strong> {{ $version->changes_summary }}</p>
                                                @endif
                                                <div class="mt-2">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewVersion({{ $version->id }})">
                                                        <i class="fas fa-eye"></i> View
                                                    </button>
                                                    @if($version->canRollback())
                                                        <button type="button" class="btn btn-sm btn-outline-warning" onclick="rollbackToVersion({{ $version->id }})">
                                                            <i class="fas fa-undo"></i> Rollback
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach

                                        @if($versions->count() > 5)
                                            <div class="text-center">
                                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="showAllVersions()">
                                                    <i class="fas fa-ellipsis-h"></i> Show All ({{ $versions->count() }})
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <!-- Related Audits -->
                            @if($relatedAudits->count() > 0)
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-link"></i> Related Audits</h5>
                                    </div>
                                    <div class="card-body">
                                        @foreach($relatedAudits as $relatedAudit)
                                            <div class="border-start border-3 ps-3 mb-3">
                                                <div class="d-flex justify-content-between">
                                                    <span class="badge bg-{{ $relatedAudit->event_type == 'created' ? 'success' : ($relatedAudit->event_type == 'deleted' ? 'danger' : 'warning') }}">
                                                        {{ ucfirst(str_replace('_', ' ', $relatedAudit->event_type)) }}
                                                    </span>
                                                    <small class="text-muted">{{ $relatedAudit->created_at->diffForHumans() }}</small>
                                                </div>
                                                <p class="mb-1"><strong>User:</strong> {{ $relatedAudit->user->name ?? $relatedAudit->user_name ?? 'System' }}</p>
                                                @if($relatedAudit->description)
                                                    <p class="mb-0">{{ Str::limit($relatedAudit->description, 100) }}</p>
                                                @endif
                                                <div class="mt-2">
                                                    <a href="{{ route('class-data-audit.show', $relatedAudit) }}" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
@include('class-data-audit.modals.approval-actions')
@include('class-data-audit.modals.version-details')
@include('class-data-audit.modals.rollback-confirmation')

@endsection

@push('scripts')
<script>
function approveChange(auditId) {
    $('#approvalModal').modal('show');
    $('#approvalForm').data('audit-id', auditId);
    $('#approvalForm').data('action', 'approve');
    $('#approvalModalTitle').text('Approve Change');
    $('#approvalReasonLabel').text('Approval Reason (Optional)');
    $('#approvalReason').prop('required', false);
}

function rejectChange(auditId) {
    $('#approvalModal').modal('show');
    $('#approvalForm').data('audit-id', auditId);
    $('#approvalForm').data('action', 'reject');
    $('#approvalModalTitle').text('Reject Change');
    $('#approvalReasonLabel').text('Rejection Reason (Required)');
    $('#approvalReason').prop('required', true);
}

function viewVersion(versionId) {
    $('#versionDetailsModal').modal('show');
    loadVersionDetails(versionId);
}

function loadVersionDetails(versionId) {
    $.get(`/class-data-audit/versions/${versionId}`)
        .done(function(response) {
            if (response.success) {
                renderVersionDetails(response.data);
            }
        })
        .fail(function() {
            showAlert('Error loading version details', 'error');
        });
}

function rollbackToVersion(versionId) {
    $('#rollbackModal').modal('show');
    $('#rollbackForm').data('version-id', versionId);
}

function showAllVersions() {
    window.location.href = `{{ route('class-data-audit.show', $audit) }}?tab=versions`;
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