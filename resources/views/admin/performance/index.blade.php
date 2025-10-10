@extends('layouts.app')

@section('title', 'Performance Monitoring Dashboard')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Performance Monitoring</h4>
                <div class="page-title-right">
                    <div class="btn-group me-2">
                        <a href="{{ route('admin.performance.dashboard') }}" class="btn btn-sm btn-primary">
                            <i class="mdi mdi-view-dashboard"></i> Dashboard
                        </a>
                        <a href="{{ route('admin.performance.alerts') }}" class="btn btn-sm btn-outline-warning">
                            <i class="mdi mdi-alert-circle"></i> Alerts
                        </a>
                        <a href="{{ route('admin.performance.metrics') }}" class="btn btn-sm btn-outline-info">
                            <i class="mdi mdi-chart-line"></i> Metrics
                        </a>
                    </div>
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item active">Performance Monitoring</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Overview Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-6">
                            <h5 class="text-muted fw-normal mt-0 text-truncate" title="System Health">System Health</h5>
                            <h3 class="my-2 py-1">
                                @if($systemHealth['critical'] > 0)
                                    <span class="text-danger">Critical</span>
                                @elseif($systemHealth['warning'] > 0)
                                    <span class="text-warning">Warning</span>
                                @else
                                    <span class="text-success">Healthy</span>
                                @endif
                            </h3>
                            <p class="mb-0 text-muted">
                                <span class="text-success me-2">{{ $systemHealth['healthy'] }} Healthy</span>
                                <span class="text-warning me-2">{{ $systemHealth['warning'] }} Warning</span>
                                <span class="text-danger">{{ $systemHealth['critical'] }} Critical</span>
                            </p>
                        </div>
                        <div class="col-6">
                            <div class="text-end">
                                <div id="system-health-chart" data-colors="#00d2ff,#ffc107,#dc3545"></div>
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
                            <h5 class="text-muted fw-normal mt-0 text-truncate" title="Performance">Performance</h5>
                            <h3 class="my-2 py-1">{{ $performanceMetrics['avg_response_time'] }}ms</h3>
                            <p class="mb-0 text-muted">
                                <span class="text-success me-2">{{ number_format($performanceMetrics['total_requests']) }} Requests</span>
                                <span class="text-warning">{{ $performanceMetrics['slow_requests'] }} Slow</span>
                            </p>
                        </div>
                        <div class="col-6">
                            <div class="text-end">
                                <div id="performance-chart" data-colors="#28a745"></div>
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
                            <h5 class="text-muted fw-normal mt-0 text-truncate" title="Error Logs">Error Logs</h5>
                            <h3 class="my-2 py-1">{{ $errorLogs['unresolved'] }}</h3>
                            <p class="mb-0 text-muted">
                                <span class="text-danger me-2">{{ $errorLogs['critical'] }} Critical</span>
                                <span class="text-info">{{ $errorLogs['recent'] }} Recent</span>
                            </p>
                        </div>
                        <div class="col-6">
                            <div class="text-end">
                                <div id="error-chart" data-colors="#dc3545"></div>
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
                            <h5 class="text-muted fw-normal mt-0 text-truncate" title="User Activity">User Activity</h5>
                            <h3 class="my-2 py-1">{{ $userActivity['unique_users_today'] }}</h3>
                            <p class="mb-0 text-muted">
                                <span class="text-primary me-2">{{ $userActivity['logins_today'] }} Logins</span>
                                <span class="text-info">{{ $userActivity['recent_activities'] }} Recent</span>
                            </p>
                        </div>
                        <div class="col-6">
                            <div class="text-end">
                                <div id="activity-chart" data-colors="#6f42c1"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title mb-3">Quick Actions</h4>
                    <div class="row">
                        <div class="col-md-3">
                            <a href="{{ route('admin.performance.system-health') }}" class="btn btn-outline-primary btn-block mb-2">
                                <i class="mdi mdi-heart-pulse me-1"></i> System Health
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('admin.performance.metrics') }}" class="btn btn-outline-success btn-block mb-2">
                                <i class="mdi mdi-chart-line me-1"></i> Performance Metrics
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('admin.performance.error-logs') }}" class="btn btn-outline-danger btn-block mb-2">
                                <i class="mdi mdi-alert-circle me-1"></i> Error Logs
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('admin.performance.user-activity') }}" class="btn btn-outline-info btn-block mb-2">
                                <i class="mdi mdi-account-multiple me-1"></i> User Activity
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Alerts -->
    <div class="row">
        <div class="col-xl-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title mb-3">Recent System Alerts</h4>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Message</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><small>{{ now()->subMinutes(5)->format('H:i') }}</small></td>
                                    <td><span class="badge bg-info">CPU</span></td>
                                    <td><span class="badge bg-warning">Warning</span></td>
                                    <td>High CPU usage detected</td>
                                </tr>
                                <tr>
                                    <td><small>{{ now()->subMinutes(15)->format('H:i') }}</small></td>
                                    <td><span class="badge bg-primary">Memory</span></td>
                                    <td><span class="badge bg-success">Healthy</span></td>
                                    <td>Memory usage normalized</td>
                                </tr>
                                <tr>
                                    <td><small>{{ now()->subMinutes(30)->format('H:i') }}</small></td>
                                    <td><span class="badge bg-secondary">Disk</span></td>
                                    <td><span class="badge bg-success">Healthy</span></td>
                                    <td>Disk space available</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title mb-3">Performance Trends</h4>
                    <div id="performance-trend-chart" style="height: 300px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Real-time Status -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title mb-3">Real-time System Status</h4>
                    <div class="row">
                        <div class="col-md-2">
                            <div class="text-center">
                                <div class="mb-2">
                                    <div class="spinner-grow text-success" role="status" style="width: 1rem; height: 1rem;">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                                <h6 class="mb-0">Database</h6>
                                <small class="text-muted">Connected</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="text-center">
                                <div class="mb-2">
                                    <div class="spinner-grow text-success" role="status" style="width: 1rem; height: 1rem;">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                                <h6 class="mb-0">Cache</h6>
                                <small class="text-muted">Active</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="text-center">
                                <div class="mb-2">
                                    <div class="spinner-grow text-warning" role="status" style="width: 1rem; height: 1rem;">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                                <h6 class="mb-0">Queue</h6>
                                <small class="text-muted">Processing</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="text-center">
                                <div class="mb-2">
                                    <div class="spinner-grow text-success" role="status" style="width: 1rem; height: 1rem;">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                                <h6 class="mb-0">Storage</h6>
                                <small class="text-muted">Available</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="text-center">
                                <div class="mb-2">
                                    <div class="spinner-grow text-success" role="status" style="width: 1rem; height: 1rem;">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                                <h6 class="mb-0">Mail</h6>
                                <small class="text-muted">Online</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="text-center">
                                <div class="mb-2">
                                    <div class="spinner-grow text-success" role="status" style="width: 1rem; height: 1rem;">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                                <h6 class="mb-0">API</h6>
                                <small class="text-muted">Responsive</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@latest"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize mini charts for overview cards
    initMiniCharts();
    
    // Initialize performance trend chart
    initPerformanceTrendChart();
    
    // Auto-refresh data every 30 seconds
    setInterval(refreshDashboardData, 30000);
});

function initMiniCharts() {
    // System Health Mini Chart
    const systemHealthOptions = {
        chart: {
            type: 'donut',
            height: 60,
            sparkline: { enabled: true }
        },
        series: [{{ $systemHealth['healthy'] }}, {{ $systemHealth['warning'] }}, {{ $systemHealth['critical'] }}],
        colors: ['#28a745', '#ffc107', '#dc3545'],
        legend: { show: false },
        dataLabels: { enabled: false }
    };
    new ApexCharts(document.querySelector("#system-health-chart"), systemHealthOptions).render();

    // Performance Mini Chart
    const performanceOptions = {
        chart: {
            type: 'area',
            height: 60,
            sparkline: { enabled: true }
        },
        series: [{
            data: [30, 40, 35, 50, 49, 60, 70, 91, 125]
        }],
        stroke: { curve: 'smooth' },
        colors: ['#28a745']
    };
    new ApexCharts(document.querySelector("#performance-chart"), performanceOptions).render();

    // Error Mini Chart
    const errorOptions = {
        chart: {
            type: 'bar',
            height: 60,
            sparkline: { enabled: true }
        },
        series: [{
            data: [25, 66, 41, 89, 63, 25, 44, 12, 36, 9, 54]
        }],
        colors: ['#dc3545']
    };
    new ApexCharts(document.querySelector("#error-chart"), errorOptions).render();

    // Activity Mini Chart
    const activityOptions = {
        chart: {
            type: 'line',
            height: 60,
            sparkline: { enabled: true }
        },
        series: [{
            data: [12, 14, 2, 47, 42, 15, 47, 75, 65, 19, 14]
        }],
        stroke: { curve: 'smooth' },
        colors: ['#6f42c1']
    };
    new ApexCharts(document.querySelector("#activity-chart"), activityOptions).render();
}

function initPerformanceTrendChart() {
    const options = {
        chart: {
            type: 'line',
            height: 300,
            toolbar: { show: false }
        },
        series: [{
            name: 'Response Time (ms)',
            data: [120, 132, 101, 134, 90, 230, 210, 120, 132, 101, 134, 90]
        }, {
            name: 'Requests/min',
            data: [76, 85, 101, 98, 87, 105, 91, 114, 94, 86, 115, 35]
        }],
        xaxis: {
            categories: ['00:00', '02:00', '04:00', '06:00', '08:00', '10:00', '12:00', '14:00', '16:00', '18:00', '20:00', '22:00']
        },
        colors: ['#28a745', '#007bff'],
        stroke: { curve: 'smooth' },
        legend: { position: 'top' }
    };
    new ApexCharts(document.querySelector("#performance-trend-chart"), options).render();
}

function refreshDashboardData() {
    // Fetch updated dashboard data via AJAX
    fetch('/api/performance/dashboard-stats')
        .then(response => response.json())
        .then(data => {
            // Update overview cards with new data
            console.log('Dashboard data refreshed', data);
        })
        .catch(error => {
            console.error('Error refreshing dashboard data:', error);
        });
}
</script>
@endpush
@endsection