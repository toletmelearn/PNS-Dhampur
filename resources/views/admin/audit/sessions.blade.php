@extends('layouts.app')

@section('title', 'User Sessions')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-users mr-2"></i>
                        User Sessions
                    </h3>
                    <div class="btn-group">
                        <button type="button" class="btn btn-info" onclick="loadSessionStatistics()">
                            <i class="fas fa-chart-pie mr-1"></i>
                            Statistics
                        </button>
                        <a href="{{ route('admin.audit.index') }}" class="btn btn-secondary">
                            <i class="fas fa-shield-alt mr-1"></i>
                            Audit Logs
                        </a>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card-body border-bottom">
                    <form method="GET" action="{{ route('admin.audit.sessions') }}" class="row g-3">
                        <div class="col-md-3">
                            <label for="user_id" class="form-label">User</label>
                            <select name="user_id" id="user_id" class="form-select">
                                <option value="">All Users</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ ($filters['user_id'] ?? '') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label for="is_active" class="form-label">Status</label>
                            <select name="is_active" id="is_active" class="form-select">
                                <option value="">All Sessions</option>
                                <option value="1" {{ ($filters['is_active'] ?? '') === '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ ($filters['is_active'] ?? '') === '0' ? 'selected' : '' }}>Ended</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label for="date_from" class="form-label">From Date</label>
                            <input type="date" name="date_from" id="date_from" class="form-control" 
                                   value="{{ $filters['date_from'] ?? '' }}">
                        </div>

                        <div class="col-md-2">
                            <label for="date_to" class="form-label">To Date</label>
                            <input type="date" name="date_to" id="date_to" class="form-control" 
                                   value="{{ $filters['date_to'] ?? '' }}">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search mr-1"></i>
                                    Filter
                                </button>
                                <a href="{{ route('admin.audit.sessions') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times mr-1"></i>
                                    Clear
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Sessions Table -->
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>User</th>
                                    <th>Session</th>
                                    <th>Login Details</th>
                                    <th>Device Info</th>
                                    <th>Location</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($sessions as $session)
                                    <tr>
                                        <td>
                                            @if($session->user)
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar avatar-sm me-2">
                                                        <span class="avatar-initial rounded-circle bg-primary">
                                                            {{ substr($session->user->name, 0, 1) }}
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <div class="fw-semibold">{{ $session->user->name }}</div>
                                                        <small class="text-muted">{{ $session->user->email }}</small>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-muted">Unknown User</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div>
                                                <code class="small">{{ substr($session->session_id, 0, 8) }}...</code>
                                            </div>
                                            <small class="text-muted">IP: {{ $session->ip_address }}</small>
                                        </td>
                                        <td>
                                            <div>
                                                <strong>Login:</strong> {{ $session->login_at->format('M d, H:i') }}
                                            </div>
                                            @if($session->logout_at)
                                                <div>
                                                    <strong>Logout:</strong> {{ $session->logout_at->format('M d, H:i') }}
                                                </div>
                                            @endif
                                            <div>
                                                <span class="badge bg-info">{{ ucfirst($session->login_method) }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="{{ $session->device_icon }} mr-2"></i>
                                                <div>
                                                    <div>{{ ucfirst($session->device_type) }}</div>
                                                    @if($session->browser)
                                                        <small class="text-muted">{{ $session->browser }}</small>
                                                    @endif
                                                    @if($session->platform)
                                                        <br><small class="text-muted">{{ $session->platform }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if($session->location)
                                                <small class="text-muted">{{ $session->location }}</small>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($session->session_duration)
                                                <div>{{ $session->session_duration }}</div>
                                            @else
                                                <div class="text-muted">
                                                    @if($session->is_active)
                                                        <span class="text-success">Active</span>
                                                        <br><small>{{ $session->login_at->diffForHumans() }}</small>
                                                    @else
                                                        <span class="text-muted">Unknown</span>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge {{ $session->status_badge }}">
                                                @if($session->is_active)
                                                    <i class="fas fa-circle mr-1"></i>
                                                    Active
                                                @else
                                                    <i class="fas fa-circle mr-1"></i>
                                                    Ended
                                                @endif
                                            </span>
                                            @if($session->logout_reason)
                                                <br><small class="text-muted">{{ ucfirst(str_replace('_', ' ', $session->logout_reason)) }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-info" 
                                                        onclick="showSessionDetails('{{ $session->id }}')" 
                                                        title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                @if($session->is_active)
                                                    <button type="button" class="btn btn-outline-danger" 
                                                            onclick="terminateSession('{{ $session->id }}')" 
                                                            title="Terminate Session">
                                                        <i class="fas fa-sign-out-alt"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-users fa-2x mb-3"></i>
                                                <p>No user sessions found matching your criteria.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                @if($sessions->hasPages())
                    <div class="card-footer">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-muted">
                                Showing {{ $sessions->firstItem() }} to {{ $sessions->lastItem() }} 
                                of {{ $sessions->total() }} results
                            </div>
                            {{ $sessions->appends(request()->query())->links() }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Session Statistics Modal -->
<div class="modal fade" id="sessionStatisticsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-chart-pie mr-2"></i>
                    Session Statistics
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="sessionStatisticsContent">
                    <div class="text-center py-4">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading session statistics...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Session Details Modal -->
<div class="modal fade" id="sessionDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle mr-2"></i>
                    Session Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="sessionDetailsContent">
                    <div class="text-center py-4">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading session details...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function loadSessionStatistics() {
    const modal = new bootstrap.Modal(document.getElementById('sessionStatisticsModal'));
    modal.show();
    
    // Get current filter values
    const filters = {
        date_from: document.getElementById('date_from').value,
        date_to: document.getElementById('date_to').value
    };
    
    fetch('{{ route("admin.audit.session-statistics") }}?' + new URLSearchParams(filters))
        .then(response => response.json())
        .then(data => {
            document.getElementById('sessionStatisticsContent').innerHTML = generateSessionStatisticsHTML(data);
        })
        .catch(error => {
            document.getElementById('sessionStatisticsContent').innerHTML = 
                '<div class="alert alert-danger">Error loading statistics: ' + error.message + '</div>';
        });
}

function generateSessionStatisticsHTML(data) {
    return `
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h3>${data.stats.total_sessions}</h3>
                        <p class="mb-0">Total Sessions</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h3>${data.stats.active_sessions}</h3>
                        <p class="mb-0">Active Sessions</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h3>${data.stats.unique_users}</h3>
                        <p class="mb-0">Unique Users</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <h3>${data.average_duration || 'N/A'}</h3>
                        <p class="mb-0">Avg Duration</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Device Types</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            ${data.device_stats.map(device => `
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>${device.device_type || 'Unknown'}</span>
                                    <span class="badge bg-primary rounded-pill">${device.count}</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Login Methods</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            ${data.login_methods.map(method => `
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>${method.login_method || 'Unknown'}</span>
                                    <span class="badge bg-secondary rounded-pill">${method.count}</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function showSessionDetails(sessionId) {
    const modal = new bootstrap.Modal(document.getElementById('sessionDetailsModal'));
    modal.show();
    
    // In a real implementation, you would fetch session details from the server
    document.getElementById('sessionDetailsContent').innerHTML = `
        <div class="alert alert-info">
            <i class="fas fa-info-circle mr-2"></i>
            Session details for ID: ${sessionId}
            <br><small>This feature would show detailed session information including user agent, activities, etc.</small>
        </div>
    `;
}

function terminateSession(sessionId) {
    if (confirm('Are you sure you want to terminate this session? The user will be logged out immediately.')) {
        // In a real implementation, you would make an API call to terminate the session
        alert('Session termination feature would be implemented here.');
    }
}

// Auto-refresh active sessions every 60 seconds
setInterval(() => {
    if (!document.querySelector('.modal.show')) {
        // Only refresh if showing active sessions
        const isActiveFilter = document.getElementById('is_active').value;
        if (isActiveFilter === '1' || isActiveFilter === '') {
            window.location.reload();
        }
    }
}, 60000);
</script>
@endpush