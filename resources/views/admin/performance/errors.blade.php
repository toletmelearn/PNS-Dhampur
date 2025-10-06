@extends('layouts.app')

@section('title', 'Error Logs')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Error Logs</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.performance.index') }}">Performance</a></li>
                        <li class="breadcrumb-item active">Error Logs</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Overview -->
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm rounded-circle bg-danger">
                                <span class="avatar-title">
                                    <i class="mdi mdi-alert-circle font-20 text-white"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0 font-14">Total Errors</h6>
                            <h4 class="mt-0 mb-1">{{ number_format($totalErrors) }}</h4>
                            <p class="text-muted mb-0 font-12">
                                <span class="text-danger">{{ $errorsToday }}</span> today
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm rounded-circle bg-warning">
                                <span class="avatar-title">
                                    <i class="mdi mdi-clock-alert font-20 text-white"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0 font-14">Unresolved</h6>
                            <h4 class="mt-0 mb-1">{{ $unresolvedErrors }}</h4>
                            <p class="text-muted mb-0 font-12">
                                {{ number_format(($unresolvedErrors / max($totalErrors, 1)) * 100, 1) }}% of total
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm rounded-circle bg-dark">
                                <span class="avatar-title">
                                    <i class="mdi mdi-alert-octagon font-20 text-white"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0 font-14">Critical Errors</h6>
                            <h4 class="mt-0 mb-1">{{ $criticalErrors }}</h4>
                            <p class="text-muted mb-0 font-12">
                                Requires immediate attention
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm rounded-circle bg-success">
                                <span class="avatar-title">
                                    <i class="mdi mdi-check-circle font-20 text-white"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0 font-14">Resolution Rate</h6>
                            <h4 class="mt-0 mb-1">{{ number_format($resolutionRate, 1) }}%</h4>
                            <p class="text-muted mb-0 font-12">
                                Last 7 days
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Trends -->
    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="header-title">Error Trends</h4>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                Last 24 Hours
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" data-period="1h">Last Hour</a></li>
                                <li><a class="dropdown-item" href="#" data-period="24h">Last 24 Hours</a></li>
                                <li><a class="dropdown-item" href="#" data-period="7d">Last 7 Days</a></li>
                                <li><a class="dropdown-item" href="#" data-period="30d">Last 30 Days</a></li>
                            </ul>
                        </div>
                    </div>
                    <div id="error-trends-chart" style="height: 350px;"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title mb-3">Error Distribution by Level</h4>
                    <div id="error-level-chart" style="height: 350px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Error Level</label>
                            <select name="level" class="form-select">
                                <option value="">All Levels</option>
                                <option value="emergency" {{ request('level') === 'emergency' ? 'selected' : '' }}>Emergency</option>
                                <option value="alert" {{ request('level') === 'alert' ? 'selected' : '' }}>Alert</option>
                                <option value="critical" {{ request('level') === 'critical' ? 'selected' : '' }}>Critical</option>
                                <option value="error" {{ request('level') === 'error' ? 'selected' : '' }}>Error</option>
                                <option value="warning" {{ request('level') === 'warning' ? 'selected' : '' }}>Warning</option>
                                <option value="notice" {{ request('level') === 'notice' ? 'selected' : '' }}>Notice</option>
                                <option value="info" {{ request('level') === 'info' ? 'selected' : '' }}>Info</option>
                                <option value="debug" {{ request('level') === 'debug' ? 'selected' : '' }}>Debug</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="unresolved" {{ request('status') === 'unresolved' ? 'selected' : '' }}>Unresolved</option>
                                <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Resolved</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date Range</label>
                            <select name="date_range" class="form-select">
                                <option value="">All Time</option>
                                <option value="today" {{ request('date_range') === 'today' ? 'selected' : '' }}>Today</option>
                                <option value="yesterday" {{ request('date_range') === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                                <option value="week" {{ request('date_range') === 'week' ? 'selected' : '' }}>This Week</option>
                                <option value="month" {{ request('date_range') === 'month' ? 'selected' : '' }}>This Month</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Search</label>
                            <div class="input-group">
                                <input type="text" name="search" class="form-control" placeholder="Search errors..." value="{{ request('search') }}">
                                <button class="btn btn-primary" type="submit">
                                    <i class="mdi mdi-magnify"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Logs Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="header-title">Error Logs</h4>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-success" onclick="bulkResolve()" id="bulk-resolve-btn" style="display: none;">
                                <i class="mdi mdi-check-all me-1"></i> Resolve Selected
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="bulkDelete()" id="bulk-delete-btn" style="display: none;">
                                <i class="mdi mdi-delete me-1"></i> Delete Selected
                            </button>
                            <button class="btn btn-sm btn-outline-primary" onclick="refreshErrors()">
                                <i class="mdi mdi-refresh me-1"></i> Refresh
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="exportErrors()">
                                <i class="mdi mdi-download me-1"></i> Export
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover" id="error-logs-table">
                            <thead class="table-dark">
                                <tr>
                                    <th width="30">
                                        <input type="checkbox" id="select-all" class="form-check-input">
                                    </th>
                                    <th>Level</th>
                                    <th>Message</th>
                                    <th>Exception</th>
                                    <th>File</th>
                                    <th>User</th>
                                    <th>IP Address</th>
                                    <th>Status</th>
                                    <th>Occurred At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($errorLogs as $error)
                                <tr class="{{ $error->isCritical() ? 'table-danger' : '' }}">
                                    <td>
                                        <input type="checkbox" class="form-check-input error-checkbox" value="{{ $error->id }}">
                                    </td>
                                    <td>
                                        <span class="badge" style="background-color: {{ $error->getLevelColorAttribute() }}">
                                            {{ strtoupper($error->level) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="mdi mdi-{{ $error->isCritical() ? 'alert-octagon text-danger' : 'information-outline text-info' }} me-2"></i>
                                            <div>
                                                <div class="fw-medium">{{ $error->getShortMessageAttribute() }}</div>
                                                @if($error->url)
                                                <small class="text-muted">
                                                    <code>{{ $error->method }} {{ Str::limit($error->url, 40) }}</code>
                                                </small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($error->exception_class)
                                        <code class="text-danger">{{ class_basename($error->exception_class) }}</code>
                                        @else
                                        <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($error->file)
                                        <small class="text-muted">
                                            {{ basename($error->file) }}:{{ $error->line }}
                                        </small>
                                        @else
                                        <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($error->user)
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-xs me-2">
                                                <span class="avatar-title rounded-circle bg-primary text-white">
                                                    {{ substr($error->user->name, 0, 1) }}
                                                </span>
                                            </div>
                                            <div>
                                                <div class="fw-medium">{{ $error->user->name }}</div>
                                                <small class="text-muted">{{ $error->user->email }}</small>
                                            </div>
                                        </div>
                                        @else
                                        <span class="text-muted">Guest</span>
                                        @endif
                                    </td>
                                    <td>
                                        <code class="text-muted">{{ $error->ip_address }}</code>
                                    </td>
                                    <td>
                                        @if($error->isResolved())
                                        <span class="badge bg-success">Resolved</span>
                                        @if($error->resolver)
                                        <br><small class="text-muted">by {{ $error->resolver->name }}</small>
                                        @endif
                                        @else
                                        <span class="badge bg-warning">Unresolved</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div>{{ $error->created_at->format('M d, H:i') }}</div>
                                        <small class="text-muted">{{ $error->created_at->diffForHumans() }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" onclick="viewErrorDetails({{ $error->id }})" title="View Details">
                                                <i class="mdi mdi-eye"></i>
                                            </button>
                                            @if(!$error->isResolved())
                                            <button class="btn btn-outline-success" onclick="resolveError({{ $error->id }})" title="Mark as Resolved">
                                                <i class="mdi mdi-check"></i>
                                            </button>
                                            @endif
                                            <button class="btn btn-outline-danger" onclick="deleteError({{ $error->id }})" title="Delete">
                                                <i class="mdi mdi-delete"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-4">
                                        <i class="mdi mdi-check-circle-outline me-2"></i>
                                        No error logs found
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($errorLogs->hasPages())
                    <div class="d-flex justify-content-center mt-3">
                        {{ $errorLogs->appends(request()->query())->links() }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Top Errors -->
    <div class="row">
        <div class="col-xl-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title mb-3">Most Frequent Errors</h4>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Exception</th>
                                    <th>Count</th>
                                    <th>Last Occurrence</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topErrors as $topError)
                                <tr>
                                    <td>
                                        <code class="text-danger">{{ class_basename($topError->exception_class) }}</code>
                                    </td>
                                    <td>
                                        <span class="badge bg-danger">{{ $topError->count }}</span>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $topError->latest_occurrence->diffForHumans() }}</small>
                                    </td>
                                    <td>
                                        @if($topError->resolved_count > 0)
                                        <span class="badge bg-success">{{ $topError->resolved_count }} resolved</span>
                                        @else
                                        <span class="badge bg-warning">All unresolved</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">No error data available</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title mb-3">Error Hotspots</h4>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>File</th>
                                    <th>Errors</th>
                                    <th>Critical</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($errorHotspots as $hotspot)
                                <tr>
                                    <td>
                                        <code class="text-dark">{{ basename($hotspot->file) }}</code>
                                        <br>
                                        <small class="text-muted">Line {{ $hotspot->most_common_line }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-danger">{{ $hotspot->error_count }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-dark">{{ $hotspot->critical_count }}</span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="analyzeFile('{{ $hotspot->file }}')">
                                            <i class="mdi mdi-magnify"></i>
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">No hotspots identified</td>
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

<!-- Error Details Modal -->
<div class="modal fade" id="errorDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Error Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="error-details-content">
                    <!-- Content will be loaded dynamically -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" id="resolve-error-btn" onclick="resolveErrorFromModal()">
                    <i class="mdi mdi-check me-1"></i> Mark as Resolved
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@latest"></script>
<script>
let currentErrorId = null;

document.addEventListener('DOMContentLoaded', function() {
    initErrorCharts();
    initEventListeners();
});

function initErrorCharts() {
    // Error Trends Chart
    const trendsOptions = {
        chart: {
            type: 'line',
            height: 350,
            toolbar: { show: true }
        },
        series: [{
            name: 'Total Errors',
            data: @json($chartData['error_counts'] ?? [])
        }, {
            name: 'Critical Errors',
            data: @json($chartData['critical_counts'] ?? [])
        }],
        xaxis: {
            categories: @json($chartData['labels'] ?? [])
        },
        colors: ['#dc3545', '#6f42c1'],
        stroke: { curve: 'smooth', width: 2 },
        legend: { position: 'top' },
        grid: { borderColor: '#f1f3fa' }
    };
    new ApexCharts(document.querySelector("#error-trends-chart"), trendsOptions).render();

    // Error Level Distribution Chart
    const levelOptions = {
        chart: {
            type: 'donut',
            height: 350
        },
        series: @json(array_values($levelDistribution ?? [])),
        labels: @json(array_map('strtoupper', array_keys($levelDistribution ?? []))),
        colors: ['#dc3545', '#fd7e14', '#ffc107', '#20c997', '#0d6efd', '#6f42c1', '#6c757d', '#198754'],
        legend: { position: 'bottom' },
        dataLabels: {
            enabled: true,
            formatter: function (val) {
                return Math.round(val) + "%"
            }
        }
    };
    new ApexCharts(document.querySelector("#error-level-chart"), levelOptions).render();
}

function initEventListeners() {
    // Select all checkbox
    document.getElementById('select-all').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.error-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        toggleBulkActions();
    });

    // Individual checkboxes
    document.querySelectorAll('.error-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', toggleBulkActions);
    });

    // Period dropdown
    document.querySelectorAll('[data-period]').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const period = this.dataset.period;
            loadErrorTrends(period);
        });
    });
}

function toggleBulkActions() {
    const checkedBoxes = document.querySelectorAll('.error-checkbox:checked');
    const bulkResolveBtn = document.getElementById('bulk-resolve-btn');
    const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
    
    if (checkedBoxes.length > 0) {
        bulkResolveBtn.style.display = 'inline-block';
        bulkDeleteBtn.style.display = 'inline-block';
    } else {
        bulkResolveBtn.style.display = 'none';
        bulkDeleteBtn.style.display = 'none';
    }
}

function loadErrorTrends(period) {
    fetch(`/api/performance/errors/trends?period=${period}`)
        .then(response => response.json())
        .then(data => {
            // Update charts with new data
            console.log('Error trends loaded for period:', period);
        })
        .catch(error => {
            console.error('Error loading error trends:', error);
        });
}

function refreshErrors() {
    window.location.reload();
}

function exportErrors() {
    const params = new URLSearchParams(window.location.search);
    window.open(`/admin/performance/errors/export?${params.toString()}`, '_blank');
}

function viewErrorDetails(errorId) {
    currentErrorId = errorId;
    
    fetch(`/api/performance/errors/${errorId}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('error-details-content').innerHTML = `
                <div class="row">
                    <div class="col-md-8">
                        <h6>Error Information</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Level:</strong></td><td><span class="badge" style="background-color: ${data.level_color}">${data.level.toUpperCase()}</span></td></tr>
                            <tr><td><strong>Message:</strong></td><td>${data.message}</td></tr>
                            <tr><td><strong>Exception:</strong></td><td><code class="text-danger">${data.exception_class || 'N/A'}</code></td></tr>
                            <tr><td><strong>File:</strong></td><td><code>${data.file || 'N/A'}</code></td></tr>
                            <tr><td><strong>Line:</strong></td><td>${data.line || 'N/A'}</td></tr>
                            <tr><td><strong>URL:</strong></td><td><code>${data.url || 'N/A'}</code></td></tr>
                            <tr><td><strong>Method:</strong></td><td><span class="badge bg-primary">${data.method || 'N/A'}</span></td></tr>
                            <tr><td><strong>IP Address:</strong></td><td><code>${data.ip_address}</code></td></tr>
                            <tr><td><strong>User Agent:</strong></td><td><small>${data.user_agent || 'N/A'}</small></td></tr>
                            <tr><td><strong>Occurred At:</strong></td><td>${data.created_at}</td></tr>
                        </table>
                        
                        ${data.stack_trace ? `
                        <h6 class="mt-3">Stack Trace</h6>
                        <pre class="bg-light p-2 rounded" style="max-height: 300px; overflow-y: auto;"><code>${data.stack_trace}</code></pre>
                        ` : ''}
                    </div>
                    <div class="col-md-4">
                        <h6>Additional Information</h6>
                        ${data.user ? `
                        <div class="mb-3">
                            <strong>User:</strong><br>
                            <div class="d-flex align-items-center mt-1">
                                <div class="avatar-xs me-2">
                                    <span class="avatar-title rounded-circle bg-primary text-white">
                                        ${data.user.name.charAt(0)}
                                    </span>
                                </div>
                                <div>
                                    <div class="fw-medium">${data.user.name}</div>
                                    <small class="text-muted">${data.user.email}</small>
                                </div>
                            </div>
                        </div>
                        ` : ''}
                        
                        ${data.is_resolved ? `
                        <div class="alert alert-success">
                            <i class="mdi mdi-check-circle me-1"></i>
                            <strong>Resolved</strong><br>
                            ${data.resolver ? `By: ${data.resolver.name}<br>` : ''}
                            At: ${data.resolved_at}
                        </div>
                        ` : ''}
                        
                        ${data.context_data ? `
                        <h6 class="mt-3">Context Data</h6>
                        <pre class="bg-light p-2 rounded" style="max-height: 200px; overflow-y: auto;"><code>${JSON.stringify(data.context_data, null, 2)}</code></pre>
                        ` : ''}
                        
                        ${data.request_data ? `
                        <h6 class="mt-3">Request Data</h6>
                        <pre class="bg-light p-2 rounded" style="max-height: 200px; overflow-y: auto;"><code>${JSON.stringify(data.request_data, null, 2)}</code></pre>
                        ` : ''}
                    </div>
                </div>
            `;
            
            // Show/hide resolve button
            const resolveBtn = document.getElementById('resolve-error-btn');
            if (data.is_resolved) {
                resolveBtn.style.display = 'none';
            } else {
                resolveBtn.style.display = 'inline-block';
            }
            
            const modal = new bootstrap.Modal(document.getElementById('errorDetailsModal'));
            modal.show();
        })
        .catch(error => {
            console.error('Error loading error details:', error);
        });
}

function resolveError(errorId) {
    if (confirm('Are you sure you want to mark this error as resolved?')) {
        fetch(`/api/performance/errors/${errorId}/resolve`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error resolving the error log');
            }
        })
        .catch(error => {
            console.error('Error resolving error:', error);
            alert('Error resolving the error log');
        });
    }
}

function resolveErrorFromModal() {
    if (currentErrorId) {
        resolveError(currentErrorId);
    }
}

function deleteError(errorId) {
    if (confirm('Are you sure you want to delete this error log? This action cannot be undone.')) {
        fetch(`/api/performance/errors/${errorId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error deleting the error log');
            }
        })
        .catch(error => {
            console.error('Error deleting error:', error);
            alert('Error deleting the error log');
        });
    }
}

function bulkResolve() {
    const checkedBoxes = document.querySelectorAll('.error-checkbox:checked');
    const errorIds = Array.from(checkedBoxes).map(cb => cb.value);
    
    if (errorIds.length === 0) return;
    
    if (confirm(`Are you sure you want to resolve ${errorIds.length} error(s)?`)) {
        fetch('/api/performance/errors/bulk-resolve', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ error_ids: errorIds })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error resolving error logs');
            }
        })
        .catch(error => {
            console.error('Error bulk resolving errors:', error);
            alert('Error resolving error logs');
        });
    }
}

function bulkDelete() {
    const checkedBoxes = document.querySelectorAll('.error-checkbox:checked');
    const errorIds = Array.from(checkedBoxes).map(cb => cb.value);
    
    if (errorIds.length === 0) return;
    
    if (confirm(`Are you sure you want to delete ${errorIds.length} error log(s)? This action cannot be undone.`)) {
        fetch('/api/performance/errors/bulk-delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ error_ids: errorIds })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error deleting error logs');
            }
        })
        .catch(error => {
            console.error('Error bulk deleting errors:', error);
            alert('Error deleting error logs');
        });
    }
}

function analyzeFile(filePath) {
    fetch(`/api/performance/errors/analyze-file?file=${encodeURIComponent(filePath)}`)
        .then(response => response.json())
        .then(data => {
            // Show file analysis
            console.log('File analysis:', data);
        })
        .catch(error => {
            console.error('Error analyzing file:', error);
        });
}
</script>
@endpush
@endsection