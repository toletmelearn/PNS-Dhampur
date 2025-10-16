@extends('layouts.app')

@section('title', 'Audit Details')

@section('content')
@php
    $breadcrumbs = [
        ['title' => 'Dashboard', 'url' => route('dashboard')],
        ['title' => 'Class Data Audit', 'url' => route('class-data-audit.index')],
        ['title' => 'Audit #'.($audit->id ?? ''), 'url' => '']
    ];
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">
        <i class="fas fa-search me-2"></i>Audit #{{ $audit->id ?? '' }}
    </h2>
    <div class="d-flex gap-2">
        @if(auth()->user()->hasPermission('view-class-audit'))
            <a href="{{ route('class-data-audit.versions', $audit) }}" class="btn btn-outline-secondary">
                <i class="fas fa-code-branch me-1"></i> Versions
            </a>
        @endif
        @if(auth()->user()->hasPermission('approve_audit_changes') && ($audit->approval_status ?? 'pending') === 'pending')
            <form action="{{ route('class-data-audit.approve', $audit) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-check me-1"></i> Approve
                </button>
            </form>
            <form action="{{ route('class-data-audit.reject') }}" method="POST" class="d-inline ms-1">
                @csrf
                <input type="hidden" name="audit_id" value="{{ $audit->id }}">
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-times me-1"></i> Reject
                </button>
            </form>
        @endif
        @if(auth()->user()->hasPermission('manage-class-audit'))
            <form action="{{ route('class-data-audit.rollback', $audit) }}" method="POST" class="d-inline ms-1">
                @csrf
                <button type="submit" class="btn btn-warning">
                    <i class="fas fa-undo me-1"></i> Rollback
                </button>
            </form>
        @endif
        @if(auth()->user()->hasPermission('view_audit_statistics'))
            <a href="{{ route('class-data-audit.analytics') }}" class="btn btn-outline-primary">
                <i class="fas fa-chart-line me-1"></i> Analytics
            </a>
        @endif
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header bg-white fw-bold">
                <i class="fas fa-info-circle me-2"></i>Audit Summary
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="text-muted small">Entity</div>
                        <div>{{ class_basename($audit->auditable_type ?? '') }} #{{ $audit->auditable_id ?? '' }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">Event</div>
                        <div>{{ ucfirst($audit->event_type ?? '') }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">Risk</div>
                        <span class="badge bg-{{ ($audit->risk_level ?? '') === 'critical' ? 'danger' : (($audit->risk_level ?? '') === 'high' ? 'warning' : 'secondary') }}">
                            {{ ucfirst($audit->risk_level ?? '') }}
                        </span>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">Approval</div>
                        <span class="badge bg-{{ ($audit->approval_status ?? 'pending') === 'approved' ? 'success' : (($audit->approval_status ?? 'pending') === 'rejected' ? 'danger' : 'warning') }}">
                            {{ ucfirst($audit->approval_status ?? 'pending') }}
                        </span>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">User</div>
                        <div>{{ $audit->user->name ?? ($audit->user_name ?? 'Unknown') }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">Created</div>
                        <div>{{ $audit->created_at ? $audit->created_at->format('Y-m-d H:i') : '' }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">IP Address</div>
                        <div>{{ $audit->ip_address ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-white fw-bold">
                <i class="fas fa-diff me-2"></i>Changed Fields
            </div>
            <div class="card-body">
                @php
                    $changed = is_array($audit->changed_fields ?? null) ? $audit->changed_fields : [];
                    $old = is_array($audit->old_values ?? null) ? $audit->old_values : [];
                    $new = is_array($audit->new_values ?? null) ? $audit->new_values : [];
                @endphp
                @if(empty($changed))
                    <div class="text-muted">No field-level changes recorded.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Field</th>
                                    <th>Previous</th>
                                    <th>New</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($changed as $field)
                                    <tr>
                                        <td>{{ $field }}</td>
                                        <td><code>{{ is_array($old[$field] ?? null) ? json_encode($old[$field]) : ($old[$field] ?? '') }}</code></td>
                                        <td><code>{{ is_array($new[$field] ?? null) ? json_encode($new[$field]) : ($new[$field] ?? '') }}</code></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        @if(isset($versions) && $versions->count())
        <div class="card mb-4">
            <div class="card-header bg-white fw-bold">
                <i class="fas fa-code-branch me-2"></i>Version History
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Type</th>
                                <th>Created By</th>
                                <th>Created At</th>
                                <th>Current</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($versions as $version)
                                <tr>
                                    <td>{{ $version->version_number }}</td>
                                    <td>{{ $version->version_type_display ?? ucfirst($version->version_type) }}</td>
                                    <td>{{ $version->creator->name ?? 'System' }}</td>
                                    <td>{{ $version->created_at ? $version->created_at->format('Y-m-d H:i') : '' }}</td>
                                    <td>
                                        @if($version->is_current_version)
                                            <span class="badge bg-success">Yes</span>
                                        @else
                                            <span class="badge bg-secondary">No</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        @if(isset($relatedAudits) && $relatedAudits->count())
        <div class="card mb-4">
            <div class="card-header bg-white fw-bold">
                <i class="fas fa-link me-2"></i>Related Audits
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    @foreach($relatedAudits as $rel)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>#{{ $rel->id }}</strong> — {{ ucfirst($rel->event_type) }} • {{ class_basename($rel->auditable_type) }} #{{ $rel->auditable_id }}
                            </div>
                            <a href="{{ route('class-data-audit.show', $rel) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye me-1"></i> View
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header bg-white fw-bold">
                <i class="fas fa-shield-alt me-2"></i>Approval Workflow
            </div>
            <div class="card-body">
                @if(isset($approvals) && $approvals->count())
                    <ul class="list-group list-group-flush">
                        @foreach($approvals as $ap)
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <div class="small text-muted">Status</div>
                                        <div class="fw-bold">{{ $ap->status_display ?? ucfirst($ap->status) }}</div>
                                    </div>
                                    <div>
                                        <div class="small text-muted">Type</div>
                                        <div>{{ $ap->approval_type_display ?? ucfirst($ap->approval_type) }}</div>
                                    </div>
                                </div>
                                <div class="mt-2 small text-muted">
                                    Requested by: {{ $ap->requester->name ?? 'System' }}
                                </div>
                                <div class="small text-muted">Assigned to: {{ $ap->assignee->name ?? '-' }}</div>
                                @if($ap->rejection_reason)
                                    <div class="mt-2 text-danger small">Reason: {{ $ap->rejection_reason }}</div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="text-muted">No approval history.</div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white fw-bold">
                <i class="fas fa-tags me-2"></i>Metadata & Tags
            </div>
            <div class="card-body">
                @php
                    $metadata = is_array($audit->metadata ?? null) ? $audit->metadata : [];
                    $tags = is_array($audit->tags ?? null) ? $audit->tags : [];
                @endphp
                @if(!empty($metadata))
                    <div class="mb-2">
                        <div class="small text-muted">Metadata</div>
                        <pre class="mb-0" style="white-space: pre-wrap;">{{ json_encode($metadata, JSON_PRETTY_PRINT) }}</pre>
                    </div>
                @else
                    <div class="text-muted">No metadata.</div>
                @endif
                <hr>
                <div class="small text-muted">Tags</div>
                @if(!empty($tags))
                    @foreach($tags as $tag)
                        <span class="badge bg-secondary me-1">{{ $tag }}</span>
                    @endforeach
                @else
                    <div class="text-muted">No tags.</div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection