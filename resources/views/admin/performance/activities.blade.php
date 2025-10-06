@extends('layouts.app')

@section('title', 'User Activities')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">User Activities</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.performance.index') }}">Performance</a></li>
                        <li class="breadcrumb-item active">User Activities</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Activity Overview -->
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm rounded-circle bg-primary">
                                <span class="avatar-title">
                                    <i class="mdi mdi-account-multiple font-20 text-white"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0 font-14">Total Activities</h6>
                            <h4 class="mt-0 mb-1">{{ number_format($totalActivities) }}</h4>
                            <p class="text-muted mb-0 font-12">
                                <span class="text-success">{{ $activitiesToday }}</span> today
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
                                    <i class="mdi mdi-login font-20 text-white"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0 font-14">Active Users</h6>
                            <h4 class="mt-0 mb-1">{{ $activeUsers }}</h4>
                            <p class="text-muted mb-0 font-12">
                                {{ $uniqueUsersToday }} unique today
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
                            <div class="avatar-sm rounded-circle bg-info">
                                <span class="avatar-title">
                                    <i class="mdi mdi-clock font-20 text-white"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0 font-14">Avg Session Time</h6>
                            <h4 class="mt-0 mb-1">{{ $avgSessionTime }}m</h4>
                            <p class="text-muted mb-0 font-12">
                                Per user session
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
                                    <i class="mdi mdi-speedometer font-20 text-white"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0 font-14">Avg Response Time</h6>
                            <h4 class="mt-0 mb-1">{{ $avgResponseTime }}ms</h4>
                            <p class="text-muted mb-0 font-12">
                                User interactions
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Activity Charts -->
    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="header-title">Activity Trends</h4>
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
                    <div id="activity-trends-chart" style="height: 350px;"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title mb-3">Activity Types Distribution</h4>
                    <div id="activity-types-chart" style="height: 350px;"></div>
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
                        <div class="col-md-2">
                            <label class="form-label">Activity Type</label>
                            <select name="activity_type" class="form-select">
                                <option value="">All Types</option>
                                <option value="login" {{ request('activity_type') === 'login' ? 'selected' : '' }}>Login</option>
                                <option value="logout" {{ request('activity_type') === 'logout' ? 'selected' : '' }}>Logout</option>
                                <option value="view" {{ request('activity_type') === 'view' ? 'selected' : '' }}>View</option>
                                <option value="create" {{ request('activity_type') === 'create' ? 'selected' : '' }}>Create</option>
                                <option value="update" {{ request('activity_type') === 'update' ? 'selected' : '' }}>Update</option>
                                <option value="delete" {{ request('activity_type') === 'delete' ? 'selected' : '' }}>Delete</option>
                                <option value="download" {{ request('activity_type') === 'download' ? 'selected' : '' }}>Download</option>
                                <option value="upload" {{ request('activity_type') === 'upload' ? 'selected' : '' }}>Upload</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">User</label>
                            <select name="user_id" class="form-select">
                                <option value="">All Users</option>
                                @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Subject Type</label>
                            <select name="subject_type" class="form-select">
                                <option value="">All Subjects</option>
                                @foreach($subjectTypes as $type)
                                <option value="{{ $type }}" {{ request('subject_type') === $type ? 'selected' : '' }}>
                                    {{ class_basename($type) }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date Range</label>
                            <select name="date_range" class="form-select">
                                <option value="">All Time</option>
                                <option value="today" {{ request('date_range') === 'today' ? 'selected' : '' }}>Today</option>
                                <option value="yesterday" {{ request('date_range') === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                                <option value="week" {{ request('date_range') === 'week' ? 'selected' : '' }}>This Week</option>
                                <option value="month" {{ request('date_range') === 'month' ? 'selected' : '' }}>This Month</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">IP Address</label>
                            <input type="text" name="ip_address" class="form-control" placeholder="IP Address" value="{{ request('ip_address') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Search</label>
                            <div class="input-group">
                                <input type="text" name="search" class="form-control" placeholder="Search..." value="{{ request('search') }}">
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

    <!-- User Activities Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="header-title">User Activities</h4>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-primary" onclick="refreshActivities()">
                                <i class="mdi mdi-refresh me-1"></i> Refresh
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="exportActivities()">
                                <i class="mdi mdi-download me-1"></i> Export
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover" id="activities-table">
                            <thead class="table-dark">
                                <tr>
                                    <th>User</th>
                                    <th>Activity</th>
                                    <th>Description</th>
                                    <th>Subject</th>
                                    <th>IP Address</th>
                                    <th>Response Time</th>
                                    <th>Status</th>
                                    <th>Performed At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($userActivities as $activity)
                                <tr>
                                    <td>
                                        @if($activity->user)
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-xs me-2">
                                                <span class="avatar-title rounded-circle bg-primary text-white">
                                                    {{ substr($activity->user->name, 0, 1) }}
                                                </span>
                                            </div>
                                            <div>
                                                <div class="fw-medium">{{ $activity->user->name }}</div>
                                                <small class="text-muted">{{ $activity->user->email }}</small>
                                            </div>
                                        </div>
                                        @else
                                        <span class="text-muted">Guest User</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="mdi mdi-{{ $activity->getActivityIconAttribute() }} me-2" style="color: {{ $activity->getActivityColorAttribute() }}"></i>
                                            <span class="badge" style="background-color: {{ $activity->getActivityColorAttribute() }}">
                                                {{ strtoupper($activity->activity_type) }}
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-medium">{{ $activity->description }}</div>
                                        @if($activity->url)
                                        <small class="text-muted">
                                            <code>{{ $activity->method }} {{ Str::limit($activity->url, 40) }}</code>
                                        </small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($activity->subject_type && $activity->subject_id)
                                        <div>
                                            <span class="badge bg-info">{{ class_basename($activity->subject_type) }}</span>
                                            <br>
                                            <small class="text-muted">ID: {{ $activity->subject_id }}</small>
                                        </div>
                                        @else
                                        <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <code class="text-muted">{{ $activity->ip_address }}</code>
                                    </td>
                                    <td>
                                        @if($activity->response_time)
                                        <span class="fw-medium text-{{ $activity->response_time > 1000 ? 'danger' : ($activity->response_time > 500 ? 'warning' : 'success') }}">
                                            {{ $activity->getFormattedResponseTimeAttribute() }}
                                        </span>
                                        @else
                                        <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($activity->status_code)
                                        @if($activity->status_code >= 200 && $activity->status_code < 300)
                                            <span class="badge bg-success">{{ $activity->status_code }}</span>
                                        @elseif($activity->status_code >= 300 && $activity->status_code < 400)
                                            <span class="badge bg-info">{{ $activity->status_code }}</span>
                                        @elseif($activity->status_code >= 400 && $activity->status_code < 500)
                                            <span class="badge bg-warning">{{ $activity->status_code }}</span>
                                        @else
                                            <span class="badge bg-danger">{{ $activity->status_code }}</span>
                                        @endif
                                        @else
                                        <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div>{{ $activity->performed_at->format('M d, H:i') }}</div>
                                        <small class="text-muted">{{ $activity->performed_at->diffForHumans() }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" onclick="viewActivityDetails({{ $activity->id }})" title="View Details">
                                                <i class="mdi mdi-eye"></i>
                                            </button>
                                            @if($activity->user)
                                            <button class="btn btn-outline-info" onclick="viewUserActivities({{ $activity->user->id }})" title="User Activities">
                                                <i class="mdi mdi-account-details"></i>
                                            </button>
                                            @endif
                                            <button class="btn btn-outline-secondary" onclick="analyzeSession('{{ $activity->session_id }}')" title="Session Analysis">
                                                <i class="mdi mdi-chart-timeline-variant"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        <i class="mdi mdi-information-outline me-2"></i>
                                        No user activities found
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($userActivities->hasPages())
                    <div class="d-flex justify-content-center mt-3">
                        {{ $userActivities->appends(request()->query())->links() }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Activity Statistics -->
    <div class="row">
        <div class="col-xl-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title mb-3">Most Active Users</h4>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Activities</th>
                                    <th>Last Activity</th>
                                    <th>Avg Response Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($mostActiveUsers as $activeUser)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-xs me-2">
                                                <span class="avatar-title rounded-circle bg-primary text-white">
                                                    {{ substr($activeUser->name, 0, 1) }}
                                                </span>
                                            </div>
                                            <div>
                                                <div class="fw-medium">{{ $activeUser->name }}</div>
                                                <small class="text-muted">{{ $activeUser->email }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">{{ $activeUser->activities_count }}</span>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $activeUser->last_activity->diffForHumans() }}</small>
                                    </td>
                                    <td>
                                        <span class="text-muted">{{ number_format($activeUser->avg_response_time) }}ms</span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">No data available</td>
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
                    <h4 class="header-title mb-3">Popular Pages</h4>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Page</th>
                                    <th>Views</th>
                                    <th>Unique Users</th>
                                    <th>Avg Response Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($popularPages as $page)
                                <tr>
                                    <td>
                                        <code class="text-dark">{{ Str::limit($page->url, 30) }}</code>
                                    </td>
                                    <td>
                                        <span class="badge bg-success">{{ $page->view_count }}</span>
                                    </td>
                                    <td>
                                        <span class="text-muted">{{ $page->unique_users }}</span>
                                    </td>
                                    <td>
                                        <span class="text-muted">{{ number_format($page->avg_response_time) }}ms</span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">No data available</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Real-time Activity Feed -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="header-title">Real-time Activity Feed</h4>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="auto-refresh" checked>
                            <label class="form-check-label" for="auto-refresh">Auto Refresh</label>
                        </div>
                    </div>
                    
                    <div id="activity-feed" style="max-height: 400px; overflow-y: auto;">
                        @foreach($recentActivities as $activity)
                        <div class="d-flex align-items-start mb-3 activity-item" data-activity-id="{{ $activity->id }}">
                            <div class="avatar-xs me-3">
                                <span class="avatar-title rounded-circle" style="background-color: {{ $activity->getActivityColorAttribute() }}">
                                    <i class="mdi mdi-{{ $activity->getActivityIconAttribute() }} text-white"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="mb-1">
                                            @if($activity->user)
                                                {{ $activity->user->name }}
                                            @else
                                                Guest User
                                            @endif
                                            <span class="badge ms-2" style="background-color: {{ $activity->getActivityColorAttribute() }}">
                                                {{ strtoupper($activity->activity_type) }}
                                            </span>
                                        </h6>
                                        <p class="mb-1 text-muted">{{ $activity->description }}</p>
                                        @if($activity->url)
                                        <small class="text-muted">
                                            <code>{{ $activity->method }} {{ $activity->url }}</code>
                                        </small>
                                        @endif
                                    </div>
                                    <small class="text-muted">{{ $activity->performed_at->diffForHumans() }}</small>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Activity Details Modal -->
<div class="modal fade" id="activityDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Activity Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="activity-details-content">
                    <!-- Content will be loaded dynamically -->
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@latest"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    initActivityCharts();
    initEventListeners();
    initAutoRefresh();
});

function initActivityCharts() {
    // Activity Trends Chart
    const trendsOptions = {
        chart: {
            type: 'area',
            height: 350,
            toolbar: { show: true }
        },
        series: [{
            name: 'Total Activities',
            data: @json($chartData['activity_counts'] ?? [])
        }, {
            name: 'Unique Users',
            data: @json($chartData['user_counts'] ?? [])
        }],
        xaxis: {
            categories: @json($chartData['labels'] ?? [])
        },
        colors: ['#007bff', '#28a745'],
        stroke: { curve: 'smooth', width: 2 },
        fill: { opacity: 0.3 },
        legend: { position: 'top' },
        grid: { borderColor: '#f1f3fa' }
    };
    new ApexCharts(document.querySelector("#activity-trends-chart"), trendsOptions).render();

    // Activity Types Distribution Chart
    const typesOptions = {
        chart: {
            type: 'donut',
            height: 350
        },
        series: @json(array_values($activityTypeDistribution ?? [])),
        labels: @json(array_map('strtoupper', array_keys($activityTypeDistribution ?? []))),
        colors: ['#007bff', '#28a745', '#ffc107', '#dc3545', '#17a2b8', '#6f42c1', '#fd7e14', '#20c997'],
        legend: { position: 'bottom' },
        dataLabels: {
            enabled: true,
            formatter: function (val) {
                return Math.round(val) + "%"
            }
        }
    };
    new ApexCharts(document.querySelector("#activity-types-chart"), typesOptions).render();
}

function initEventListeners() {
    // Period dropdown
    document.querySelectorAll('[data-period]').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const period = this.dataset.period;
            loadActivityTrends(period);
        });
    });
}

function initAutoRefresh() {
    const autoRefreshCheckbox = document.getElementById('auto-refresh');
    let refreshInterval;

    function startAutoRefresh() {
        refreshInterval = setInterval(() => {
            loadRecentActivities();
        }, 30000); // Refresh every 30 seconds
    }

    function stopAutoRefresh() {
        if (refreshInterval) {
            clearInterval(refreshInterval);
        }
    }

    autoRefreshCheckbox.addEventListener('change', function() {
        if (this.checked) {
            startAutoRefresh();
        } else {
            stopAutoRefresh();
        }
    });

    // Start auto-refresh if checkbox is checked
    if (autoRefreshCheckbox.checked) {
        startAutoRefresh();
    }
}

function loadActivityTrends(period) {
    fetch(`/api/performance/activities/trends?period=${period}`)
        .then(response => response.json())
        .then(data => {
            // Update charts with new data
            console.log('Activity trends loaded for period:', period);
        })
        .catch(error => {
            console.error('Error loading activity trends:', error);
        });
}

function loadRecentActivities() {
    const lastActivityId = document.querySelector('.activity-item')?.dataset.activityId || 0;
    
    fetch(`/api/performance/activities/recent?since=${lastActivityId}`)
        .then(response => response.json())
        .then(data => {
            if (data.activities && data.activities.length > 0) {
                const activityFeed = document.getElementById('activity-feed');
                
                data.activities.forEach(activity => {
                    const activityHtml = `
                        <div class="d-flex align-items-start mb-3 activity-item" data-activity-id="${activity.id}">
                            <div class="avatar-xs me-3">
                                <span class="avatar-title rounded-circle" style="background-color: ${activity.activity_color}">
                                    <i class="mdi mdi-${activity.activity_icon} text-white"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="mb-1">
                                            ${activity.user ? activity.user.name : 'Guest User'}
                                            <span class="badge ms-2" style="background-color: ${activity.activity_color}">
                                                ${activity.activity_type.toUpperCase()}
                                            </span>
                                        </h6>
                                        <p class="mb-1 text-muted">${activity.description}</p>
                                        ${activity.url ? `<small class="text-muted"><code>${activity.method} ${activity.url}</code></small>` : ''}
                                    </div>
                                    <small class="text-muted">${activity.performed_at_human}</small>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    activityFeed.insertAdjacentHTML('afterbegin', activityHtml);
                });

                // Remove old activities to prevent overflow
                const activityItems = activityFeed.querySelectorAll('.activity-item');
                if (activityItems.length > 50) {
                    for (let i = 50; i < activityItems.length; i++) {
                        activityItems[i].remove();
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error loading recent activities:', error);
        });
}

function refreshActivities() {
    window.location.reload();
}

function exportActivities() {
    const params = new URLSearchParams(window.location.search);
    window.open(`/admin/performance/activities/export?${params.toString()}`, '_blank');
}

function viewActivityDetails(activityId) {
    fetch(`/api/performance/activities/${activityId}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('activity-details-content').innerHTML = `
                <div class="row">
                    <div class="col-md-8">
                        <h6>Activity Information</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Activity Type:</strong></td><td><span class="badge" style="background-color: ${data.activity_color}">${data.activity_type.toUpperCase()}</span></td></tr>
                            <tr><td><strong>Description:</strong></td><td>${data.description}</td></tr>
                            <tr><td><strong>URL:</strong></td><td><code>${data.url || 'N/A'}</code></td></tr>
                            <tr><td><strong>Method:</strong></td><td><span class="badge bg-primary">${data.method || 'N/A'}</span></td></tr>
                            <tr><td><strong>IP Address:</strong></td><td><code>${data.ip_address}</code></td></tr>
                            <tr><td><strong>User Agent:</strong></td><td><small>${data.user_agent || 'N/A'}</small></td></tr>
                            <tr><td><strong>Session ID:</strong></td><td><code>${data.session_id}</code></td></tr>
                            <tr><td><strong>Response Time:</strong></td><td>${data.formatted_response_time || 'N/A'}</td></tr>
                            <tr><td><strong>Status Code:</strong></td><td>${data.status_code ? `<span class="badge bg-${data.status_code < 400 ? 'success' : 'danger'}">${data.status_code}</span>` : 'N/A'}</td></tr>
                            <tr><td><strong>Performed At:</strong></td><td>${data.performed_at}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-4">
                        <h6>User Information</h6>
                        ${data.user ? `
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-sm me-3">
                                <span class="avatar-title rounded-circle bg-primary text-white">
                                    ${data.user.name.charAt(0)}
                                </span>
                            </div>
                            <div>
                                <div class="fw-medium">${data.user.name}</div>
                                <small class="text-muted">${data.user.email}</small>
                            </div>
                        </div>
                        ` : '<p class="text-muted">Guest User</p>'}
                        
                        ${data.subject_type && data.subject_id ? `
                        <h6 class="mt-3">Subject Information</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Type:</strong></td><td><span class="badge bg-info">${data.subject_type_name}</span></td></tr>
                            <tr><td><strong>ID:</strong></td><td>${data.subject_id}</td></tr>
                        </table>
                        ` : ''}
                        
                        ${data.properties ? `
                        <h6 class="mt-3">Properties</h6>
                        <pre class="bg-light p-2 rounded" style="max-height: 200px; overflow-y: auto;"><code>${JSON.stringify(data.properties, null, 2)}</code></pre>
                        ` : ''}
                        
                        ${data.request_data ? `
                        <h6 class="mt-3">Request Data</h6>
                        <pre class="bg-light p-2 rounded" style="max-height: 200px; overflow-y: auto;"><code>${JSON.stringify(data.request_data, null, 2)}</code></pre>
                        ` : ''}
                    </div>
                </div>
            `;
            
            const modal = new bootstrap.Modal(document.getElementById('activityDetailsModal'));
            modal.show();
        })
        .catch(error => {
            console.error('Error loading activity details:', error);
        });
}

function viewUserActivities(userId) {
    window.location.href = `?user_id=${userId}`;
}

function analyzeSession(sessionId) {
    fetch(`/api/performance/activities/session/${sessionId}`)
        .then(response => response.json())
        .then(data => {
            // Show session analysis
            console.log('Session analysis:', data);
        })
        .catch(error => {
            console.error('Error analyzing session:', error);
        });
}
</script>
@endpush
@endsection