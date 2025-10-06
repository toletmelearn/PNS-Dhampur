@extends('layouts.app')

@section('title', 'System Health Monitoring')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">System Health Monitoring</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.performance.index') }}">Performance</a></li>
                        <li class="breadcrumb-item active">System Health</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Health Status Overview -->
    <div class="row">
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
                            <h6 class="mb-0 font-14">Healthy Services</h6>
                            <h4 class="mt-0 mb-1">{{ $healthyCount }}</h4>
                            <p class="text-muted mb-0 font-12">{{ number_format(($healthyCount / $totalServices) * 100, 1) }}% of total</p>
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
                                    <i class="mdi mdi-alert-triangle font-20 text-white"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0 font-14">Warning Services</h6>
                            <h4 class="mt-0 mb-1">{{ $warningCount }}</h4>
                            <p class="text-muted mb-0 font-12">{{ number_format(($warningCount / $totalServices) * 100, 1) }}% of total</p>
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
                            <div class="avatar-sm rounded-circle bg-danger">
                                <span class="avatar-title">
                                    <i class="mdi mdi-close-circle font-20 text-white"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0 font-14">Critical Services</h6>
                            <h4 class="mt-0 mb-1">{{ $criticalCount }}</h4>
                            <p class="text-muted mb-0 font-12">{{ number_format(($criticalCount / $totalServices) * 100, 1) }}% of total</p>
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
                                    <i class="mdi mdi-clock-outline font-20 text-white"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0 font-14">Uptime</h6>
                            <h4 class="mt-0 mb-1">99.9%</h4>
                            <p class="text-muted mb-0 font-12">Last 30 days</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- System Health Chart -->
    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="header-title">System Health Trends</h4>
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
                    <div id="health-trends-chart" style="height: 350px;"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title mb-3">Health Distribution</h4>
                    <div id="health-distribution-chart" style="height: 350px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Health Metrics -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="header-title">System Health Details</h4>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-primary" onclick="refreshHealthData()">
                                <i class="mdi mdi-refresh me-1"></i> Refresh
                            </button>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    Filter by Status
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#" data-filter="all">All</a></li>
                                    <li><a class="dropdown-item" href="#" data-filter="healthy">Healthy</a></li>
                                    <li><a class="dropdown-item" href="#" data-filter="warning">Warning</a></li>
                                    <li><a class="dropdown-item" href="#" data-filter="critical">Critical</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="health-metrics-table">
                            <thead class="table-dark">
                                <tr>
                                    <th>Service Type</th>
                                    <th>Status</th>
                                    <th>Value</th>
                                    <th>Threshold</th>
                                    <th>Last Updated</th>
                                    <th>Trend</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($healthMetrics as $metric)
                                <tr data-status="{{ $metric->status }}">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($metric->type === 'cpu')
                                                <i class="mdi mdi-chip text-info me-2"></i>
                                            @elseif($metric->type === 'memory')
                                                <i class="mdi mdi-memory text-warning me-2"></i>
                                            @elseif($metric->type === 'disk')
                                                <i class="mdi mdi-harddisk text-secondary me-2"></i>
                                            @elseif($metric->type === 'database')
                                                <i class="mdi mdi-database text-primary me-2"></i>
                                            @else
                                                <i class="mdi mdi-server text-muted me-2"></i>
                                            @endif
                                            <span class="fw-medium">{{ ucfirst($metric->type) }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        @if($metric->isHealthy())
                                            <span class="badge bg-success">Healthy</span>
                                        @elseif($metric->isWarning())
                                            <span class="badge bg-warning">Warning</span>
                                        @else
                                            <span class="badge bg-danger">Critical</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="fw-medium">{{ $metric->getFormattedValueAttribute() }}</span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            @if(isset($metric->metadata['threshold']))
                                                {{ $metric->metadata['threshold'] }}
                                            @else
                                                N/A
                                            @endif
                                        </small>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $metric->recorded_at->diffForHumans() }}</small>
                                    </td>
                                    <td>
                                        <div class="mini-trend-chart" data-metric-id="{{ $metric->id }}"></div>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" onclick="viewMetricDetails({{ $metric->id }})" title="View Details">
                                                <i class="mdi mdi-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-secondary" onclick="downloadMetricHistory({{ $metric->id }})" title="Download History">
                                                <i class="mdi mdi-download"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="mdi mdi-information-outline me-2"></i>
                                        No health metrics available
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($healthMetrics->hasPages())
                    <div class="d-flex justify-content-center mt-3">
                        {{ $healthMetrics->links() }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Real-time Monitoring -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="header-title">Real-time System Monitor</h4>
                        <div class="d-flex align-items-center">
                            <span class="badge bg-success me-2" id="connection-status">Connected</span>
                            <small class="text-muted">Auto-refresh: <span id="refresh-countdown">30</span>s</small>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3">
                            <div class="card border">
                                <div class="card-body text-center">
                                    <div class="mb-2">
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-info" role="progressbar" style="width: 65%" id="cpu-progress"></div>
                                        </div>
                                    </div>
                                    <h5 class="mb-1" id="cpu-usage">65%</h5>
                                    <p class="text-muted mb-0 font-12">CPU Usage</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="card border">
                                <div class="card-body text-center">
                                    <div class="mb-2">
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-warning" role="progressbar" style="width: 78%" id="memory-progress"></div>
                                        </div>
                                    </div>
                                    <h5 class="mb-1" id="memory-usage">78%</h5>
                                    <p class="text-muted mb-0 font-12">Memory Usage</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="card border">
                                <div class="card-body text-center">
                                    <div class="mb-2">
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-secondary" role="progressbar" style="width: 45%" id="disk-progress"></div>
                                        </div>
                                    </div>
                                    <h5 class="mb-1" id="disk-usage">45%</h5>
                                    <p class="text-muted mb-0 font-12">Disk Usage</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="card border">
                                <div class="card-body text-center">
                                    <div class="mb-2">
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: 92%" id="uptime-progress"></div>
                                        </div>
                                    </div>
                                    <h5 class="mb-1" id="uptime">99.9%</h5>
                                    <p class="text-muted mb-0 font-12">Uptime</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Metric Details Modal -->
<div class="modal fade" id="metricDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Metric Details</h5>
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
let refreshInterval;
let countdownInterval;
let refreshCountdown = 30;

document.addEventListener('DOMContentLoaded', function() {
    initHealthCharts();
    startAutoRefresh();
    initEventListeners();
});

function initHealthCharts() {
    // Health Trends Chart
    const trendsOptions = {
        chart: {
            type: 'line',
            height: 350,
            toolbar: { show: true }
        },
        series: [{
            name: 'Healthy',
            data: @json($chartData['healthy'] ?? [])
        }, {
            name: 'Warning',
            data: @json($chartData['warning'] ?? [])
        }, {
            name: 'Critical',
            data: @json($chartData['critical'] ?? [])
        }],
        xaxis: {
            categories: @json($chartData['labels'] ?? [])
        },
        colors: ['#28a745', '#ffc107', '#dc3545'],
        stroke: { curve: 'smooth', width: 2 },
        legend: { position: 'top' },
        grid: { borderColor: '#f1f3fa' }
    };
    new ApexCharts(document.querySelector("#health-trends-chart"), trendsOptions).render();

    // Health Distribution Chart
    const distributionOptions = {
        chart: {
            type: 'donut',
            height: 350
        },
        series: [{{ $healthyCount }}, {{ $warningCount }}, {{ $criticalCount }}],
        labels: ['Healthy', 'Warning', 'Critical'],
        colors: ['#28a745', '#ffc107', '#dc3545'],
        legend: { position: 'bottom' },
        dataLabels: {
            enabled: true,
            formatter: function (val) {
                return Math.round(val) + "%"
            }
        }
    };
    new ApexCharts(document.querySelector("#health-distribution-chart"), distributionOptions).render();
}

function startAutoRefresh() {
    refreshInterval = setInterval(refreshHealthData, 30000);
    countdownInterval = setInterval(updateCountdown, 1000);
}

function updateCountdown() {
    refreshCountdown--;
    document.getElementById('refresh-countdown').textContent = refreshCountdown;
    
    if (refreshCountdown <= 0) {
        refreshCountdown = 30;
    }
}

function refreshHealthData() {
    fetch('/api/performance/system-health')
        .then(response => response.json())
        .then(data => {
            updateRealTimeMetrics(data);
            document.getElementById('connection-status').textContent = 'Connected';
            document.getElementById('connection-status').className = 'badge bg-success me-2';
        })
        .catch(error => {
            console.error('Error refreshing health data:', error);
            document.getElementById('connection-status').textContent = 'Disconnected';
            document.getElementById('connection-status').className = 'badge bg-danger me-2';
        });
}

function updateRealTimeMetrics(data) {
    // Update CPU usage
    if (data.cpu) {
        document.getElementById('cpu-usage').textContent = data.cpu.value + '%';
        document.getElementById('cpu-progress').style.width = data.cpu.value + '%';
    }
    
    // Update Memory usage
    if (data.memory) {
        document.getElementById('memory-usage').textContent = data.memory.value + '%';
        document.getElementById('memory-progress').style.width = data.memory.value + '%';
    }
    
    // Update Disk usage
    if (data.disk) {
        document.getElementById('disk-usage').textContent = data.disk.value + '%';
        document.getElementById('disk-progress').style.width = data.disk.value + '%';
    }
}

function initEventListeners() {
    // Filter dropdown
    document.querySelectorAll('[data-filter]').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const filter = this.dataset.filter;
            filterHealthMetrics(filter);
        });
    });

    // Period dropdown
    document.querySelectorAll('[data-period]').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const period = this.dataset.period;
            loadHealthTrends(period);
        });
    });
}

function filterHealthMetrics(status) {
    const rows = document.querySelectorAll('#health-metrics-table tbody tr');
    rows.forEach(row => {
        if (status === 'all' || row.dataset.status === status) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function loadHealthTrends(period) {
    fetch(`/api/performance/system-health/trends?period=${period}`)
        .then(response => response.json())
        .then(data => {
            // Update chart with new data
            console.log('Health trends loaded for period:', period);
        })
        .catch(error => {
            console.error('Error loading health trends:', error);
        });
}

function viewMetricDetails(metricId) {
    fetch(`/api/performance/system-health/${metricId}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('metric-details-content').innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Metric Information</h6>
                        <table class="table table-sm">
                            <tr><td>Type:</td><td>${data.type}</td></tr>
                            <tr><td>Status:</td><td><span class="badge bg-${data.status === 'healthy' ? 'success' : data.status === 'warning' ? 'warning' : 'danger'}">${data.status}</span></td></tr>
                            <tr><td>Value:</td><td>${data.formatted_value}</td></tr>
                            <tr><td>Recorded:</td><td>${data.recorded_at}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Historical Trend</h6>
                        <div id="metric-detail-chart" style="height: 200px;"></div>
                    </div>
                </div>
            `;
            
            const modal = new bootstrap.Modal(document.getElementById('metricDetailsModal'));
            modal.show();
        })
        .catch(error => {
            console.error('Error loading metric details:', error);
        });
}

function downloadMetricHistory(metricId) {
    window.open(`/api/performance/system-health/${metricId}/export`, '_blank');
}
</script>
@endpush
@endsection