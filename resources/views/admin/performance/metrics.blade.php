@extends('layouts.app')

@section('title', 'Performance Metrics')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Performance Metrics</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.performance.index') }}">Performance</a></li>
                        <li class="breadcrumb-item active">Metrics</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Overview -->
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm rounded-circle bg-primary">
                                <span class="avatar-title">
                                    <i class="mdi mdi-speedometer font-20 text-white"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0 font-14">Avg Response Time</h6>
                            <h4 class="mt-0 mb-1">{{ $avgResponseTime }}ms</h4>
                            <p class="text-muted mb-0 font-12">
                                @if($responseTimeTrend > 0)
                                    <span class="text-danger">+{{ $responseTimeTrend }}%</span>
                                @else
                                    <span class="text-success">{{ $responseTimeTrend }}%</span>
                                @endif
                                from yesterday
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
                                    <i class="mdi mdi-chart-line font-20 text-white"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0 font-14">Total Requests</h6>
                            <h4 class="mt-0 mb-1">{{ number_format($totalRequests) }}</h4>
                            <p class="text-muted mb-0 font-12">
                                <span class="text-success">{{ number_format($requestsToday) }}</span> today
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
                            <h6 class="mb-0 font-14">Slow Requests</h6>
                            <h4 class="mt-0 mb-1">{{ $slowRequests }}</h4>
                            <p class="text-muted mb-0 font-12">
                                {{ number_format(($slowRequests / $totalRequests) * 100, 1) }}% of total
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
                                    <i class="mdi mdi-memory font-20 text-white"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0 font-14">Avg Memory Usage</h6>
                            <h4 class="mt-0 mb-1">{{ $avgMemoryUsage }}MB</h4>
                            <p class="text-muted mb-0 font-12">
                                Peak: {{ $peakMemoryUsage }}MB
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Charts -->
    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="header-title">Response Time Trends</h4>
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
                    <div id="response-time-chart" style="height: 350px;"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title mb-3">Request Status Distribution</h4>
                    <div id="status-distribution-chart" style="height: 350px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Endpoint Performance -->
    <div class="row">
        <div class="col-xl-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title mb-3">Top Slowest Endpoints</h4>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Endpoint</th>
                                    <th>Method</th>
                                    <th>Avg Response Time</th>
                                    <th>Requests</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($slowestEndpoints as $endpoint)
                                <tr>
                                    <td>
                                        <code class="text-dark">{{ Str::limit($endpoint->endpoint, 30) }}</code>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $endpoint->method === 'GET' ? 'primary' : ($endpoint->method === 'POST' ? 'success' : 'warning') }}">
                                            {{ $endpoint->method }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="fw-medium text-{{ $endpoint->avg_response_time > 1000 ? 'danger' : ($endpoint->avg_response_time > 500 ? 'warning' : 'success') }}">
                                            {{ number_format($endpoint->avg_response_time) }}ms
                                        </span>
                                    </td>
                                    <td>{{ number_format($endpoint->request_count) }}</td>
                                    <td>
                                        @if($endpoint->avg_response_time > 1000)
                                            <span class="badge bg-danger">Critical</span>
                                        @elseif($endpoint->avg_response_time > 500)
                                            <span class="badge bg-warning">Slow</span>
                                        @else
                                            <span class="badge bg-success">Good</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">No data available</td>
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
                    <h4 class="header-title mb-3">Memory Usage Trends</h4>
                    <div id="memory-usage-chart" style="height: 300px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Database Performance -->
    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="header-title">Database Query Performance</h4>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-primary" onclick="refreshMetrics()">
                                <i class="mdi mdi-refresh me-1"></i> Refresh
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="exportMetrics()">
                                <i class="mdi mdi-download me-1"></i> Export
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover" id="performance-metrics-table">
                            <thead class="table-dark">
                                <tr>
                                    <th>Endpoint</th>
                                    <th>Method</th>
                                    <th>Response Time</th>
                                    <th>Memory</th>
                                    <th>DB Queries</th>
                                    <th>DB Time</th>
                                    <th>Status</th>
                                    <th>Recorded At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($performanceMetrics as $metric)
                                <tr>
                                    <td>
                                        <code class="text-dark">{{ Str::limit($metric->endpoint, 25) }}</code>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $metric->method === 'GET' ? 'primary' : ($metric->method === 'POST' ? 'success' : 'warning') }}">
                                            {{ $metric->method }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="fw-medium text-{{ $metric->isSlowRequest() ? 'danger' : 'success' }}">
                                            {{ $metric->getFormattedResponseTimeAttribute() }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-muted">{{ $metric->getFormattedMemoryUsageAttribute() }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $metric->database_queries > 10 ? 'warning' : 'info' }}">
                                            {{ $metric->database_queries }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-muted">{{ number_format($metric->database_time, 2) }}ms</span>
                                    </td>
                                    <td>
                                        @if($metric->isSuccessful())
                                            <span class="badge bg-success">{{ $metric->status_code }}</span>
                                        @elseif($metric->isError())
                                            <span class="badge bg-danger">{{ $metric->status_code }}</span>
                                        @else
                                            <span class="badge bg-warning">{{ $metric->status_code }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $metric->recorded_at->format('H:i:s') }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" onclick="viewMetricDetails({{ $metric->id }})" title="View Details">
                                                <i class="mdi mdi-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-info" onclick="analyzeEndpoint('{{ $metric->endpoint }}')" title="Analyze Endpoint">
                                                <i class="mdi mdi-chart-line"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        <i class="mdi mdi-information-outline me-2"></i>
                                        No performance metrics available
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($performanceMetrics->hasPages())
                    <div class="d-flex justify-content-center mt-3">
                        {{ $performanceMetrics->links() }}
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title mb-3">Performance Insights</h4>
                    
                    <div class="alert alert-info">
                        <h6 class="alert-heading">
                            <i class="mdi mdi-lightbulb-outline me-1"></i>
                            Performance Tips
                        </h6>
                        <ul class="mb-0 ps-3">
                            <li>Optimize endpoints with response time > 1000ms</li>
                            <li>Consider caching for frequently accessed data</li>
                            <li>Review database queries for N+1 problems</li>
                            <li>Monitor memory usage during peak hours</li>
                        </ul>
                    </div>

                    <div class="mt-3">
                        <h6>Quick Stats</h6>
                        <div class="row">
                            <div class="col-6">
                                <div class="text-center p-2 border rounded">
                                    <h5 class="mb-1 text-success">{{ number_format($successRate, 1) }}%</h5>
                                    <small class="text-muted">Success Rate</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center p-2 border rounded">
                                    <h5 class="mb-1 text-info">{{ $avgDbQueries }}</h5>
                                    <small class="text-muted">Avg DB Queries</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <h6>Performance Alerts</h6>
                        <div class="list-group list-group-flush">
                            @if($slowRequests > 10)
                            <div class="list-group-item border-0 px-0">
                                <div class="d-flex align-items-center">
                                    <i class="mdi mdi-alert-circle text-warning me-2"></i>
                                    <div>
                                        <small class="fw-medium">High number of slow requests</small>
                                        <br>
                                        <small class="text-muted">{{ $slowRequests }} requests > 1000ms</small>
                                    </div>
                                </div>
                            </div>
                            @endif
                            
                            @if($avgDbQueries > 15)
                            <div class="list-group-item border-0 px-0">
                                <div class="d-flex align-items-center">
                                    <i class="mdi mdi-database-alert text-danger me-2"></i>
                                    <div>
                                        <small class="fw-medium">High database query count</small>
                                        <br>
                                        <small class="text-muted">Average {{ $avgDbQueries }} queries per request</small>
                                    </div>
                                </div>
                            </div>
                            @endif
                            
                            @if($avgMemoryUsage > 100)
                            <div class="list-group-item border-0 px-0">
                                <div class="d-flex align-items-center">
                                    <i class="mdi mdi-memory text-warning me-2"></i>
                                    <div>
                                        <small class="fw-medium">High memory usage</small>
                                        <br>
                                        <small class="text-muted">Average {{ $avgMemoryUsage }}MB per request</small>
                                    </div>
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

<!-- Metric Details Modal -->
<div class="modal fade" id="metricDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Performance Metric Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="metric-details-content">
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
    initPerformanceCharts();
    initEventListeners();
});

function initPerformanceCharts() {
    // Response Time Trends Chart
    const responseTimeOptions = {
        chart: {
            type: 'line',
            height: 350,
            toolbar: { show: true }
        },
        series: [{
            name: 'Response Time (ms)',
            data: @json($chartData['response_times'] ?? [])
        }, {
            name: 'Memory Usage (MB)',
            data: @json($chartData['memory_usage'] ?? [])
        }],
        xaxis: {
            categories: @json($chartData['labels'] ?? [])
        },
        yaxis: [{
            title: { text: 'Response Time (ms)' },
            seriesName: 'Response Time (ms)'
        }, {
            opposite: true,
            title: { text: 'Memory Usage (MB)' },
            seriesName: 'Memory Usage (MB)'
        }],
        colors: ['#007bff', '#28a745'],
        stroke: { curve: 'smooth', width: 2 },
        legend: { position: 'top' },
        grid: { borderColor: '#f1f3fa' }
    };
    new ApexCharts(document.querySelector("#response-time-chart"), responseTimeOptions).render();

    // Status Distribution Chart
    const statusOptions = {
        chart: {
            type: 'donut',
            height: 350
        },
        series: @json(array_values($statusDistribution ?? [])),
        labels: @json(array_keys($statusDistribution ?? [])),
        colors: ['#28a745', '#ffc107', '#dc3545', '#6c757d'],
        legend: { position: 'bottom' },
        dataLabels: {
            enabled: true,
            formatter: function (val) {
                return Math.round(val) + "%"
            }
        }
    };
    new ApexCharts(document.querySelector("#status-distribution-chart"), statusOptions).render();

    // Memory Usage Chart
    const memoryOptions = {
        chart: {
            type: 'area',
            height: 300,
            sparkline: { enabled: false }
        },
        series: [{
            name: 'Memory Usage (MB)',
            data: @json($chartData['memory_trend'] ?? [])
        }],
        xaxis: {
            categories: @json($chartData['memory_labels'] ?? [])
        },
        colors: ['#17a2b8'],
        stroke: { curve: 'smooth' },
        fill: { opacity: 0.3 }
    };
    new ApexCharts(document.querySelector("#memory-usage-chart"), memoryOptions).render();
}

function initEventListeners() {
    // Period dropdown
    document.querySelectorAll('[data-period]').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const period = this.dataset.period;
            loadPerformanceTrends(period);
        });
    });
}

function loadPerformanceTrends(period) {
    fetch(`/api/performance/metrics/trends?period=${period}`)
        .then(response => response.json())
        .then(data => {
            // Update charts with new data
            console.log('Performance trends loaded for period:', period);
        })
        .catch(error => {
            console.error('Error loading performance trends:', error);
        });
}

function refreshMetrics() {
    window.location.reload();
}

function exportMetrics() {
    window.open('/admin/performance/metrics/export', '_blank');
}

function viewMetricDetails(metricId) {
    fetch(`/api/performance/metrics/${metricId}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('metric-details-content').innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Request Information</h6>
                        <table class="table table-sm">
                            <tr><td>Endpoint:</td><td><code>${data.endpoint}</code></td></tr>
                            <tr><td>Method:</td><td><span class="badge bg-primary">${data.method}</span></td></tr>
                            <tr><td>Status Code:</td><td><span class="badge bg-${data.status_code < 400 ? 'success' : 'danger'}">${data.status_code}</span></td></tr>
                            <tr><td>User Agent:</td><td><small>${data.user_agent || 'N/A'}</small></td></tr>
                            <tr><td>IP Address:</td><td>${data.ip_address}</td></tr>
                            <tr><td>Recorded At:</td><td>${data.recorded_at}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Performance Metrics</h6>
                        <table class="table table-sm">
                            <tr><td>Response Time:</td><td><strong>${data.formatted_response_time}</strong></td></tr>
                            <tr><td>Memory Usage:</td><td><strong>${data.formatted_memory_usage}</strong></td></tr>
                            <tr><td>CPU Usage:</td><td>${data.cpu_usage}%</td></tr>
                            <tr><td>Database Queries:</td><td>${data.database_queries}</td></tr>
                            <tr><td>Database Time:</td><td>${data.database_time}ms</td></tr>
                        </table>
                    </div>
                </div>
                ${data.additional_data ? `
                <div class="row mt-3">
                    <div class="col-12">
                        <h6>Additional Data</h6>
                        <pre class="bg-light p-2 rounded"><code>${JSON.stringify(data.additional_data, null, 2)}</code></pre>
                    </div>
                </div>
                ` : ''}
            `;
            
            const modal = new bootstrap.Modal(document.getElementById('metricDetailsModal'));
            modal.show();
        })
        .catch(error => {
            console.error('Error loading metric details:', error);
        });
}

function analyzeEndpoint(endpoint) {
    fetch(`/api/performance/metrics/analyze?endpoint=${encodeURIComponent(endpoint)}`)
        .then(response => response.json())
        .then(data => {
            // Show endpoint analysis
            console.log('Endpoint analysis:', data);
        })
        .catch(error => {
            console.error('Error analyzing endpoint:', error);
        });
}
</script>
@endpush
@endsection