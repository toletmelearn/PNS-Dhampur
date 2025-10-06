@extends('layouts.app')

@section('title', 'Audit Logs')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-shield-alt mr-2"></i>
                        Audit Logs
                    </h3>
                    <div class="btn-group">
                        <button type="button" class="btn btn-info" onclick="loadStatistics()">
                            <i class="fas fa-chart-bar mr-1"></i>
                            Statistics
                        </button>
                        <a href="{{ route('admin.audit.sessions') }}" class="btn btn-secondary">
                            <i class="fas fa-users mr-1"></i>
                            User Sessions
                        </a>
                        <button type="button" class="btn btn-success" onclick="exportLogs()">
                            <i class="fas fa-download mr-1"></i>
                            Export
                        </button>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card-body border-bottom">
                    <form method="GET" action="{{ route('admin.audit.index') }}" class="row g-3">
                        <div class="col-md-2">
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
                            <label for="event" class="form-label">Event</label>
                            <select name="event" id="event" class="form-select">
                                <option value="">All Events</option>
                                @foreach($events as $event)
                                    <option value="{{ $event }}" {{ ($filters['event'] ?? '') == $event ? 'selected' : '' }}>
                                        {{ ucfirst(str_replace('_', ' ', $event)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label for="model_type" class="form-label">Model Type</label>
                            <select name="model_type" id="model_type" class="form-select">
                                <option value="">All Models</option>
                                @foreach($modelTypes as $modelType)
                                    <option value="{{ $modelType }}" {{ ($filters['model_type'] ?? '') == $modelType ? 'selected' : '' }}>
                                        {{ class_basename($modelType) }}
                                    </option>
                                @endforeach
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

                        <div class="col-md-2">
                            <label for="search" class="form-label">Search</label>
                            <div class="input-group">
                                <input type="text" name="search" id="search" class="form-control" 
                                       placeholder="IP, URL, User..." value="{{ $filters['search'] ?? '' }}">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="mt-3">
                        <a href="{{ route('admin.audit.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-times mr-1"></i>
                            Clear Filters
                        </a>
                    </div>
                </div>

                <!-- Audit Logs Table -->
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Event</th>
                                    <th>Model</th>
                                    <th>IP Address</th>
                                    <th>URL</th>
                                    <th>Changes</th>
                                    <th>Date/Time</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($auditLogs as $log)
                                    <tr>
                                        <td>
                                            <span class="badge bg-secondary">#{{ $log->id }}</span>
                                        </td>
                                        <td>
                                            @if($log->user)
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar avatar-sm me-2">
                                                        <span class="avatar-initial rounded-circle bg-primary">
                                                            {{ substr($log->user->name, 0, 1) }}
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <div class="fw-semibold">{{ $log->user->name }}</div>
                                                        <small class="text-muted">{{ $log->user->email }}</small>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-muted">System</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge {{ $log->status_badge }}">
                                                <i class="{{ $log->event_icon }} mr-1"></i>
                                                {{ ucfirst(str_replace('_', ' ', $log->event)) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($log->auditable_type)
                                                <div>
                                                    <strong>{{ class_basename($log->auditable_type) }}</strong>
                                                    @if($log->auditable_id)
                                                        <br><small class="text-muted">ID: {{ $log->auditable_id }}</small>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <code>{{ $log->ip_address }}</code>
                                        </td>
                                        <td>
                                            @if($log->url)
                                                <small class="text-truncate d-block" style="max-width: 200px;" title="{{ $log->url }}">
                                                    {{ $log->url }}
                                                </small>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($log->changed_fields && count($log->changed_fields) > 0)
                                                <span class="badge bg-info">
                                                    {{ count($log->changed_fields) }} field(s)
                                                </span>
                                                <div class="mt-1">
                                                    @foreach(array_slice($log->changed_fields, 0, 3) as $field)
                                                        <small class="badge bg-light text-dark me-1">{{ $field }}</small>
                                                    @endforeach
                                                    @if(count($log->changed_fields) > 3)
                                                        <small class="text-muted">+{{ count($log->changed_fields) - 3 }} more</small>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div>{{ $log->created_at->format('M d, Y') }}</div>
                                            <small class="text-muted">{{ $log->created_at->format('H:i:s') }}</small>
                                            <div><small class="text-muted">{{ $log->created_at->diffForHumans() }}</small></div>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.audit.show', $log) }}" 
                                               class="btn btn-sm btn-outline-primary" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-search fa-2x mb-3"></i>
                                                <p>No audit logs found matching your criteria.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                @if($auditLogs->hasPages())
                    <div class="card-footer">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-muted">
                                Showing {{ $auditLogs->firstItem() }} to {{ $auditLogs->lastItem() }} 
                                of {{ $auditLogs->total() }} results
                            </div>
                            {{ $auditLogs->appends(request()->query())->links() }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Statistics Modal -->
<div class="modal fade" id="statisticsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-chart-bar mr-2"></i>
                    Audit Statistics
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="statisticsContent">
                    <div class="text-center py-4">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading statistics...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function loadStatistics() {
    const modal = new bootstrap.Modal(document.getElementById('statisticsModal'));
    modal.show();
    
    // Get current filter values
    const filters = {
        date_from: document.getElementById('date_from').value,
        date_to: document.getElementById('date_to').value
    };
    
    fetch('{{ route("admin.audit.statistics") }}?' + new URLSearchParams(filters))
        .then(response => response.json())
        .then(data => {
            document.getElementById('statisticsContent').innerHTML = generateStatisticsHTML(data);
        })
        .catch(error => {
            document.getElementById('statisticsContent').innerHTML = 
                '<div class="alert alert-danger">Error loading statistics: ' + error.message + '</div>';
        });
}

function generateStatisticsHTML(data) {
    return `
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h3>${data.stats.total_activities}</h3>
                        <p class="mb-0">Total Activities</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h3>${data.stats.unique_users}</h3>
                        <p class="mb-0">Unique Users</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <h3>${data.stats.successful_logins}</h3>
                        <p class="mb-0">Successful Logins</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <h3>${data.stats.failed_logins}</h3>
                        <p class="mb-0">Failed Logins</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Top Active Users</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            ${data.top_users.map(user => `
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>${user.user ? user.user.name : 'Unknown'}</strong>
                                        <br><small class="text-muted">${user.user ? user.user.email : ''}</small>
                                    </div>
                                    <span class="badge bg-primary rounded-pill">${user.activity_count}</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Event Distribution</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            ${data.event_distribution.map(event => `
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>${event.event.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}</span>
                                    <span class="badge bg-secondary rounded-pill">${event.count}</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function exportLogs() {
    // Get current filter values
    const form = document.createElement('form');
    form.method = 'GET';
    form.action = '{{ route("admin.audit.export") }}';
    
    const filters = ['user_id', 'event', 'model_type', 'date_from', 'date_to', 'search'];
    filters.forEach(filter => {
        const value = document.getElementById(filter).value;
        if (value) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = filter;
            input.value = value;
            form.appendChild(input);
        }
    });
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

// Auto-refresh every 30 seconds if no filters are applied
@if(empty(array_filter($filters)))
setInterval(() => {
    if (!document.querySelector('.modal.show')) {
        window.location.reload();
    }
}, 30000);
@endif
</script>
@endpush