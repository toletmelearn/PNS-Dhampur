@extends('layouts.app')

@section('title', 'Performance Alerts')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Performance Alerts</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.performance.index') }}">Performance</a></li>
                        <li class="breadcrumb-item active">Alerts</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Statistics -->
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-6">
                            <h5 class="text-muted fw-normal mt-0 text-truncate">Active Alerts</h5>
                            <h3 class="my-2 py-1 text-danger">{{ $alertStats['active'] ?? 0 }}</h3>
                            <p class="mb-0 text-muted">
                                <span class="text-danger me-2"><i class="mdi mdi-arrow-up-bold"></i> Critical</span>
                            </p>
                        </div>
                        <div class="col-6">
                            <div class="text-end">
                                <i class="mdi mdi-alert-circle widget-icon bg-danger-lighten text-danger"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-6">
                            <h5 class="text-muted fw-normal mt-0 text-truncate">Resolved Today</h5>
                            <h3 class="my-2 py-1 text-success">{{ $alertStats['resolved_today'] ?? 0 }}</h3>
                            <p class="mb-0 text-muted">
                                <span class="text-success me-2"><i class="mdi mdi-arrow-down-bold"></i> Resolved</span>
                            </p>
                        </div>
                        <div class="col-6">
                            <div class="text-end">
                                <i class="mdi mdi-check-circle widget-icon bg-success-lighten text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-6">
                            <h5 class="text-muted fw-normal mt-0 text-truncate">Avg Response Time</h5>
                            <h3 class="my-2 py-1">{{ $alertStats['avg_response_time'] ?? 0 }}min</h3>
                            <p class="mb-0 text-muted">
                                <span class="text-info me-2"><i class="mdi mdi-clock-outline"></i> Response</span>
                            </p>
                        </div>
                        <div class="col-6">
                            <div class="text-end">
                                <i class="mdi mdi-timer widget-icon bg-info-lighten text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-6">
                            <h5 class="text-muted fw-normal mt-0 text-truncate">Alert Rate</h5>
                            <h3 class="my-2 py-1">{{ $alertStats['alert_rate'] ?? 0 }}%</h3>
                            <p class="mb-0 text-muted">
                                <span class="text-warning me-2"><i class="mdi mdi-trending-up"></i> 24h Rate</span>
                            </p>
                        </div>
                        <div class="col-6">
                            <div class="text-end">
                                <i class="mdi mdi-chart-line widget-icon bg-warning-lighten text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Configuration -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="header-title">Alert Configuration</h4>
                    <p class="text-muted mb-0">Configure performance alert thresholds and notification settings</p>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.performance.alerts.config') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="memory_threshold" class="form-label">Memory Usage Threshold (%)</label>
                                    <input type="number" class="form-control" id="memory_threshold" name="memory_threshold" 
                                           value="{{ $config['memory_threshold'] ?? 85 }}" min="1" max="100">
                                    <small class="form-text text-muted">Alert when memory usage exceeds this percentage</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="cpu_threshold" class="form-label">CPU Usage Threshold (%)</label>
                                    <input type="number" class="form-control" id="cpu_threshold" name="cpu_threshold" 
                                           value="{{ $config['cpu_threshold'] ?? 80 }}" min="1" max="100">
                                    <small class="form-text text-muted">Alert when CPU usage exceeds this percentage</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="disk_threshold" class="form-label">Disk Space Threshold (GB)</label>
                                    <input type="number" class="form-control" id="disk_threshold" name="disk_threshold" 
                                           value="{{ $config['disk_threshold'] ?? 10 }}" min="1">
                                    <small class="form-text text-muted">Alert when free disk space falls below this value</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="response_time_threshold" class="form-label">Response Time Threshold (ms)</label>
                                    <input type="number" class="form-control" id="response_time_threshold" name="response_time_threshold" 
                                           value="{{ $config['response_time_threshold'] ?? 5000 }}" min="100">
                                    <small class="form-text text-muted">Alert when average response time exceeds this value</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="error_rate_threshold" class="form-label">Error Rate Threshold (%)</label>
                                    <input type="number" class="form-control" id="error_rate_threshold" name="error_rate_threshold" 
                                           value="{{ $config['error_rate_threshold'] ?? 5 }}" min="1" max="100">
                                    <small class="form-text text-muted">Alert when error rate exceeds this percentage</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="alert_cooldown" class="form-label">Alert Cooldown (minutes)</label>
                                    <input type="number" class="form-control" id="alert_cooldown" name="alert_cooldown" 
                                           value="{{ $config['alert_cooldown'] ?? 15 }}" min="1">
                                    <small class="form-text text-muted">Minimum time between duplicate alerts</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="email_notifications" 
                                               name="email_notifications" {{ ($config['email_notifications'] ?? true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="email_notifications">
                                            Enable Email Notifications
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="mdi mdi-content-save"></i> Save Configuration
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Alerts -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="header-title">Recent Alerts</h4>
                    <div class="card-header-actions">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="refreshAlerts()">
                            <i class="mdi mdi-refresh"></i> Refresh
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearAllAlerts()">
                            <i class="mdi mdi-delete-sweep"></i> Clear All
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-centered table-nowrap mb-0" id="alerts-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Alert Type</th>
                                    <th>Metric</th>
                                    <th>Current Value</th>
                                    <th>Threshold</th>
                                    <th>Severity</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($alerts ?? [] as $alert)
                                <tr>
                                    <td>
                                        <i class="mdi mdi-{{ $alert['icon'] ?? 'alert-circle' }} text-{{ $alert['severity'] }}"></i>
                                        {{ $alert['type'] }}
                                    </td>
                                    <td>{{ $alert['metric'] }}</td>
                                    <td>
                                        <span class="badge bg-{{ $alert['severity'] }}-lighten text-{{ $alert['severity'] }}">
                                            {{ $alert['current_value'] }}
                                        </span>
                                    </td>
                                    <td>{{ $alert['threshold'] }}</td>
                                    <td>
                                        <span class="badge bg-{{ $alert['severity'] }}">
                                            {{ ucfirst($alert['severity']) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span title="{{ $alert['created_at'] }}">
                                            {{ $alert['time_ago'] }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($alert['status'] === 'active')
                                            <span class="badge bg-danger">Active</span>
                                        @else
                                            <span class="badge bg-success">Resolved</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary" 
                                                    onclick="viewAlert({{ $alert['id'] }})">
                                                <i class="mdi mdi-eye"></i>
                                            </button>
                                            @if($alert['status'] === 'active')
                                            <button type="button" class="btn btn-outline-success" 
                                                    onclick="resolveAlert({{ $alert['id'] }})">
                                                <i class="mdi mdi-check"></i>
                                            </button>
                                            @endif
                                            <button type="button" class="btn btn-outline-danger" 
                                                    onclick="deleteAlert({{ $alert['id'] }})">
                                                <i class="mdi mdi-delete"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="mdi mdi-check-circle-outline h1 text-success"></i>
                                        <p class="mb-0">No alerts found. System is running smoothly!</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alert Details Modal -->
<div class="modal fade" id="alertDetailsModal" tabindex="-1" aria-labelledby="alertDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="alertDetailsModalLabel">Alert Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="alertDetailsContent">
                <!-- Alert details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function refreshAlerts() {
    location.reload();
}

function clearAllAlerts() {
    if (confirm('Are you sure you want to clear all alerts? This action cannot be undone.')) {
        fetch('{{ route("admin.performance.alerts.clear") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error clearing alerts: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while clearing alerts');
        });
    }
}

function viewAlert(alertId) {
    fetch(`{{ route("admin.performance.alerts.show", ":id") }}`.replace(':id', alertId))
        .then(response => response.json())
        .then(data => {
            document.getElementById('alertDetailsContent').innerHTML = data.html;
            new bootstrap.Modal(document.getElementById('alertDetailsModal')).show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading alert details');
        });
}

function resolveAlert(alertId) {
    if (confirm('Mark this alert as resolved?')) {
        fetch(`{{ route("admin.performance.alerts.resolve", ":id") }}`.replace(':id', alertId), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error resolving alert: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while resolving the alert');
        });
    }
}

function deleteAlert(alertId) {
    if (confirm('Are you sure you want to delete this alert? This action cannot be undone.')) {
        fetch(`{{ route("admin.performance.alerts.destroy", ":id") }}`.replace(':id', alertId), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error deleting alert: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the alert');
        });
    }
}

// Auto-refresh alerts every 30 seconds
setInterval(function() {
    const table = document.getElementById('alerts-table');
    if (table && !document.querySelector('.modal.show')) {
        fetch('{{ route("admin.performance.alerts.data") }}')
            .then(response => response.json())
            .then(data => {
                // Update alert counts and table content
                updateAlertsTable(data);
            })
            .catch(error => console.error('Auto-refresh error:', error));
    }
}, 30000);

function updateAlertsTable(data) {
    // Update statistics
    document.querySelector('[data-stat="active"]').textContent = data.stats.active;
    document.querySelector('[data-stat="resolved_today"]').textContent = data.stats.resolved_today;
    
    // Update table body
    const tbody = document.querySelector('#alerts-table tbody');
    if (data.alerts.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center text-muted py-4">
                    <i class="mdi mdi-check-circle-outline h1 text-success"></i>
                    <p class="mb-0">No alerts found. System is running smoothly!</p>
                </td>
            </tr>
        `;
    } else {
        // Update with new alert rows
        tbody.innerHTML = data.html;
    }
}
</script>
@endsection