@extends('layouts.app')

@section('title', 'Security Logs - ' . $examPaper->title)

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
                                <i class="fas fa-shield-alt mr-2"></i>Security & Audit Logs
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
                <!-- Security Overview -->
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">
                                <i class="fas fa-chart-bar mr-2"></i>Security Overview
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="info-box bg-info">
                                        <span class="info-box-icon"><i class="fas fa-list"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Total Events</span>
                                            <span class="info-box-number">{{ $securityLogs->count() }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-box bg-warning">
                                        <span class="info-box-icon"><i class="fas fa-exclamation-triangle"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">High Risk</span>
                                            <span class="info-box-number">{{ $securityLogs->where('risk_level', 'high')->count() }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-box bg-danger">
                                        <span class="info-box-icon"><i class="fas fa-ban"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Suspicious</span>
                                            <span class="info-box-number">{{ $securityLogs->where('is_suspicious', true)->count() }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-box bg-success">
                                        <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Resolved</span>
                                            <span class="info-box-number">{{ $securityLogs->where('investigation_status', 'resolved')->count() }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mt-3">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fas fa-filter mr-2"></i>Filters
                    </h4>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('exam-papers.security-logs', $examPaper) }}">
                        <div class="row">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="action">Action:</label>
                                    <select name="action" id="action" class="form-control">
                                        <option value="">All Actions</option>
                                        <option value="access" {{ request('action') === 'access' ? 'selected' : '' }}>Access</option>
                                        <option value="view" {{ request('action') === 'view' ? 'selected' : '' }}>View</option>
                                        <option value="edit" {{ request('action') === 'edit' ? 'selected' : '' }}>Edit</option>
                                        <option value="delete" {{ request('action') === 'delete' ? 'selected' : '' }}>Delete</option>
                                        <option value="download" {{ request('action') === 'download' ? 'selected' : '' }}>Download</option>
                                        <option value="export" {{ request('action') === 'export' ? 'selected' : '' }}>Export</option>
                                        <option value="approve" {{ request('action') === 'approve' ? 'selected' : '' }}>Approve</option>
                                        <option value="reject" {{ request('action') === 'reject' ? 'selected' : '' }}>Reject</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="severity">Severity:</label>
                                    <select name="severity" id="severity" class="form-control">
                                        <option value="">All Severities</option>
                                        <option value="low" {{ request('severity') === 'low' ? 'selected' : '' }}>Low</option>
                                        <option value="medium" {{ request('severity') === 'medium' ? 'selected' : '' }}>Medium</option>
                                        <option value="high" {{ request('severity') === 'high' ? 'selected' : '' }}>High</option>
                                        <option value="critical" {{ request('severity') === 'critical' ? 'selected' : '' }}>Critical</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="risk_level">Risk Level:</label>
                                    <select name="risk_level" id="risk_level" class="form-control">
                                        <option value="">All Risk Levels</option>
                                        <option value="low" {{ request('risk_level') === 'low' ? 'selected' : '' }}>Low</option>
                                        <option value="medium" {{ request('risk_level') === 'medium' ? 'selected' : '' }}>Medium</option>
                                        <option value="high" {{ request('risk_level') === 'high' ? 'selected' : '' }}>High</option>
                                        <option value="critical" {{ request('risk_level') === 'critical' ? 'selected' : '' }}>Critical</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="date_from">Date From:</label>
                                    <input type="date" name="date_from" id="date_from" class="form-control" value="{{ request('date_from') }}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="date_to">Date To:</label>
                                    <input type="date" name="date_to" id="date_to" class="form-control" value="{{ request('date_to') }}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search mr-1"></i>Filter
                                        </button>
                                        <a href="{{ route('exam-papers.security-logs', $examPaper) }}" class="btn btn-secondary">
                                            <i class="fas fa-times mr-1"></i>Clear
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input type="checkbox" name="suspicious_only" id="suspicious_only" class="form-check-input" value="1" {{ request('suspicious_only') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="suspicious_only">
                                        Show suspicious activities only
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input type="checkbox" name="under_investigation" id="under_investigation" class="form-check-input" value="1" {{ request('under_investigation') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="under_investigation">
                                        Show items under investigation
                                    </label>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Security Logs Table -->
            <div class="card mt-3">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fas fa-list mr-2"></i>Security Events
                    </h4>
                </div>
                <div class="card-body">
                    @if($securityLogs->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Timestamp</th>
                                        <th>User</th>
                                        <th>Action</th>
                                        <th>Resource</th>
                                        <th>Description</th>
                                        <th>Severity</th>
                                        <th>Risk Level</th>
                                        <th>IP Address</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($securityLogs as $log)
                                        <tr class="{{ $log->is_suspicious ? 'table-warning' : '' }}">
                                            <td>{{ $log->created_at->format('M d, Y H:i:s') }}</td>
                                            <td>
                                                @if($log->user)
                                                    {{ $log->user->name }}
                                                    <br><small class="text-muted">{{ $log->user->email }}</small>
                                                @else
                                                    <span class="text-muted">System</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge badge-{{ $log->getBadgeClass() }}">
                                                    {{ ucfirst($log->action) }}
                                                </span>
                                            </td>
                                            <td>
                                                {{ ucfirst($log->resource_type) }}
                                                @if($log->resource_id)
                                                    <br><small class="text-muted">ID: {{ $log->resource_id }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                {{ $log->description }}
                                                @if($log->is_suspicious)
                                                    <br><span class="badge badge-warning">
                                                        <i class="fas fa-exclamation-triangle mr-1"></i>Suspicious
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge badge-{{ $log->severity === 'critical' ? 'danger' : ($log->severity === 'high' ? 'warning' : ($log->severity === 'medium' ? 'info' : 'secondary')) }}">
                                                    {{ ucfirst($log->severity) }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-{{ $log->risk_level === 'critical' ? 'danger' : ($log->risk_level === 'high' ? 'warning' : ($log->risk_level === 'medium' ? 'info' : 'secondary')) }}">
                                                    {{ ucfirst($log->risk_level) }}
                                                </span>
                                            </td>
                                            <td>
                                                {{ $log->ip_address }}
                                                @if($log->user_agent)
                                                    <br><small class="text-muted" title="{{ $log->user_agent }}">
                                                        {{ Str::limit($log->user_agent, 30) }}
                                                    </small>
                                                @endif
                                            </td>
                                            <td>
                                                @if($log->investigation_status)
                                                    <span class="badge badge-{{ $log->investigation_status === 'resolved' ? 'success' : ($log->investigation_status === 'investigating' ? 'warning' : 'info') }}">
                                                        {{ ucfirst($log->investigation_status) }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">Normal</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-info" onclick="showLogDetails({{ $log->id }})" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    @if($log->is_suspicious && !$log->investigation_status)
                                                        <button type="button" class="btn btn-sm btn-warning" onclick="startInvestigation({{ $log->id }})" title="Start Investigation">
                                                            <i class="fas fa-search"></i>
                                                        </button>
                                                    @endif
                                                    @if($log->investigation_status === 'investigating')
                                                        <button type="button" class="btn btn-sm btn-success" onclick="resolveInvestigation({{ $log->id }})" title="Mark as Resolved">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        @if($securityLogs instanceof \Illuminate\Pagination\LengthAwarePaginator)
                            <div class="d-flex justify-content-center">
                                {{ $securityLogs->appends(request()->query())->links() }}
                            </div>
                        @endif
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i>
                            No security logs found for the selected criteria.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Log Details Modal -->
<div class="modal fade" id="logDetailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Security Log Details</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="logDetailsContent">
                <!-- Content will be loaded via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Investigation Modal -->
<div class="modal fade" id="investigationModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Start Investigation</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="investigationForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="investigation_notes">Investigation Notes:</label>
                        <textarea name="investigation_notes" id="investigation_notes" class="form-control" rows="4" placeholder="Describe the investigation process and findings..." required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="assigned_investigator">Assign to Investigator:</label>
                        <select name="assigned_investigator" id="assigned_investigator" class="form-control">
                            <option value="">Self-assigned</option>
                            @foreach($investigators ?? [] as $investigator)
                                <option value="{{ $investigator->id }}">{{ $investigator->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Start Investigation</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function showLogDetails(logId) {
    $('#logDetailsContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
    $('#logDetailsModal').modal('show');
    
    // AJAX call to load log details
    fetch(`/exam-papers/security-logs/${logId}/details`)
        .then(response => response.json())
        .then(data => {
            $('#logDetailsContent').html(data.html);
        })
        .catch(error => {
            $('#logDetailsContent').html('<div class="alert alert-danger">Error loading log details.</div>');
        });
}

function startInvestigation(logId) {
    $('#investigationForm').attr('action', `/exam-papers/security-logs/${logId}/investigate`);
    $('#investigationModal').modal('show');
}

function resolveInvestigation(logId) {
    if (confirm('Are you sure you want to mark this investigation as resolved?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/exam-papers/security-logs/${logId}/resolve`;
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        
        form.appendChild(csrfToken);
        document.body.appendChild(form);
        form.submit();
    }
}

// Auto-refresh every 30 seconds for real-time monitoring
setInterval(function() {
    if (!$('.modal').hasClass('show')) {
        location.reload();
    }
}, 30000);
</script>
@endpush
@endsection