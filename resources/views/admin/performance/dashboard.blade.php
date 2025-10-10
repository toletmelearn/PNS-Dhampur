@extends('layouts.app')

@section('title', 'Performance Dashboard')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Performance Dashboard</h4>
                <div class="page-title-right">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="refreshDashboard()">
                            <i class="mdi mdi-refresh"></i> Refresh
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-info" onclick="exportReport()">
                            <i class="mdi mdi-download"></i> Export
                        </button>
                    </div>
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.performance.index') }}">Performance</a></li>
                        <li class="breadcrumb-item active">Dashboard</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Real-time Status Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-6">
                            <h5 class="text-muted fw-normal mt-0 text-truncate">Memory Usage</h5>
                            <h3 class="my-2 py-1" id="memory-usage">{{ $metrics['memory_usage'] ?? 0 }}%</h3>
                            <p class="mb-0 text-muted">
                                <span class="text-{{ $metrics['memory_usage'] > 85 ? 'danger' : 'success' }} me-2">
                                    <i class="mdi mdi-arrow-{{ $metrics['memory_usage'] > 85 ? 'up' : 'down' }}-bold"></i>
                                    {{ $metrics['memory_usage'] > 85 ? 'High' : 'Normal' }}
                                </span>
                            </p>
                        </div>
                        <div class="col-6">
                            <div class="text-end">
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-{{ $metrics['memory_usage'] > 85 ? 'danger' : 'primary' }}" 
                                         style="width: {{ $metrics['memory_usage'] ?? 0 }}%"></div>
                                </div>
                                <small class="text-muted">of {{ $metrics['total_memory'] ?? 'N/A' }} GB</small>
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
                            <h5 class="text-muted fw-normal mt-0 text-truncate">CPU Usage</h5>
                            <h3 class="my-2 py-1" id="cpu-usage">{{ $metrics['cpu_usage'] ?? 0 }}%</h3>
                            <p class="mb-0 text-muted">
                                <span class="text-{{ $metrics['cpu_usage'] > 80 ? 'danger' : 'success' }} me-2">
                                    <i class="mdi mdi-arrow-{{ $metrics['cpu_usage'] > 80 ? 'up' : 'down' }}-bold"></i>
                                    {{ $metrics['cpu_usage'] > 80 ? 'High' : 'Normal' }}
                                </span>
                            </p>
                        </div>
                        <div class="col-6">
                            <div class="text-end">
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-{{ $metrics['cpu_usage'] > 80 ? 'danger' : 'success' }}" 
                                         style="width: {{ $metrics['cpu_usage'] ?? 0 }}%"></div>
                                </div>
                                <small class="text-muted">{{ $metrics['cpu_cores'] ?? 'N/A' }} cores</small>
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
                            <h5 class="text-muted fw-normal mt-0 text-truncate">Disk Space</h5>
                            <h3 class="my-2 py-1" id="disk-space">{{ $metrics['disk_space'] ?? 0 }} GB</h3>
                            <p class="mb-0 text-muted">
                                <span class="text-{{ $metrics['disk_space'] < 10 ? 'danger' : 'success' }} me-2">
                                    <i class="mdi mdi-harddisk"></i>
                                    {{ $metrics['disk_space'] < 10 ? 'Low' : 'Available' }}
                                </span>
                            </p>
                        </div>
                        <div class="col-6">
                            <div class="text-end">
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-{{ $metrics['disk_space'] < 10 ? 'danger' : 'info' }}" 
                                         style="width: {{ ($metrics['disk_space'] / ($metrics['total_disk'] ?? 100)) * 100 }}%"></div>
                                </div>
                                <small class="text-muted">of {{ $metrics['total_disk'] ?? 'N/A' }} GB</small>
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
                            <h5 class="text-muted fw-normal mt-0 text-truncate">Response Time</h5>
                            <h3 class="my-2 py-1" id="response-time">{{ $metrics['avg_response_time'] ?? 0 }}ms</h3>
                            <p class="mb-0 text-muted">
                                <span class="text-{{ $metrics['avg_response_time'] > 5000 ? 'danger' : 'success' }} me-2">
                                    <i class="mdi mdi-speedometer"></i>
                                    {{ $metrics['avg_response_time'] > 5000 ? 'Slow' : 'Fast' }}
                                </span>
                            </p>
                        </div>
                        <div class="col-6">
                            <div class="text-end">
                                <i class="mdi mdi-timer widget-icon bg-warning-lighten text-warning"></i>
                            </div>
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
                <div class="card-header">
                    <h4 class="header-title">Performance Trends (Last 24 Hours)</h4>
                    <div class="card-header-actions">
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-primary active" data-period="24h">24H</button>
                            <button type="button" class="btn btn-outline-primary" data-period="7d">7D</button>
                            <button type="button" class="btn btn-outline-primary" data-period="30d">30D</button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="performance-chart" height="300"></canvas>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="header-title">System Health Status</h4>
                </div>
                <div class="card-body">
                    <div class="text-center">
                        <div class="mb-3">
                            <div class="avatar-lg mx-auto">
                                <div class="avatar-title rounded-circle bg-{{ $systemStatus['overall'] === 'healthy' ? 'success' : ($systemStatus['overall'] === 'warning' ? 'warning' : 'danger') }}-lighten text-{{ $systemStatus['overall'] === 'healthy' ? 'success' : ($systemStatus['overall'] === 'warning' ? 'warning' : 'danger') }}">
                                    <i class="mdi mdi-{{ $systemStatus['overall'] === 'healthy' ? 'check-circle' : ($systemStatus['overall'] === 'warning' ? 'alert-circle' : 'close-circle') }} h1"></i>
                                </div>
                            </div>
                        </div>
                        <h4 class="text-{{ $systemStatus['overall'] === 'healthy' ? 'success' : ($systemStatus['overall'] === 'warning' ? 'warning' : 'danger') }}">
                            {{ ucfirst($systemStatus['overall'] ?? 'unknown') }}
                        </h4>
                        <p class="text-muted">Overall System Status</p>
                    </div>

                    <div class="mt-4">
                        <h5>Service Status</h5>
                        <div class="list-group list-group-flush">
                            @foreach($serviceStatus ?? [] as $service => $status)
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span>{{ ucfirst(str_replace('_', ' ', $service)) }}</span>
                                <span class="badge bg-{{ $status === 'available' ? 'success' : 'danger' }}">
                                    {{ ucfirst($status) }}
                                </span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Alerts and Activities -->
    <div class="row">
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="header-title">Recent Alerts</h4>
                    <div class="card-header-actions">
                        <a href="{{ route('admin.performance.alerts') }}" class="btn btn-sm btn-outline-primary">
                            View All <i class="mdi mdi-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @forelse($recentAlerts ?? [] as $alert)
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm">
                                <div class="avatar-title rounded-circle bg-{{ $alert['severity'] }}-lighten text-{{ $alert['severity'] }}">
                                    <i class="mdi mdi-{{ $alert['icon'] ?? 'alert-circle' }}"></i>
                                </div>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1">{{ $alert['title'] }}</h6>
                            <p class="mb-0 text-muted">{{ $alert['message'] }}</p>
                            <small class="text-muted">{{ $alert['time_ago'] }}</small>
                        </div>
                        <div class="flex-shrink-0">
                            <span class="badge bg-{{ $alert['severity'] }}">{{ ucfirst($alert['severity']) }}</span>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-4">
                        <i class="mdi mdi-check-circle-outline h1 text-success"></i>
                        <p class="text-muted mb-0">No recent alerts</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="header-title">Performance Activities</h4>
                    <div class="card-header-actions">
                        <a href="{{ route('admin.performance.activities') }}" class="btn btn-sm btn-outline-primary">
                            View All <i class="mdi mdi-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @forelse($recentActivities ?? [] as $activity)
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm">
                                <div class="avatar-title rounded-circle bg-info-lighten text-info">
                                    <i class="mdi mdi-{{ $activity['icon'] ?? 'information' }}"></i>
                                </div>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1">{{ $activity['title'] }}</h6>
                            <p class="mb-0 text-muted">{{ $activity['description'] }}</p>
                            <small class="text-muted">{{ $activity['time_ago'] }}</small>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-4">
                        <i class="mdi mdi-information-outline h1 text-info"></i>
                        <p class="text-muted mb-0">No recent activities</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Metrics Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="header-title">Detailed Metrics</h4>
                    <div class="card-header-actions">
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-primary" onclick="refreshMetrics()">
                                <i class="mdi mdi-refresh"></i> Refresh
                            </button>
                            <button type="button" class="btn btn-outline-info" onclick="exportMetrics()">
                                <i class="mdi mdi-download"></i> Export
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-centered table-nowrap mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Metric</th>
                                    <th>Current Value</th>
                                    <th>Threshold</th>
                                    <th>Status</th>
                                    <th>Last Updated</th>
                                    <th>Trend</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($detailedMetrics ?? [] as $metric)
                                <tr>
                                    <td>
                                        <i class="mdi mdi-{{ $metric['icon'] }} text-muted me-2"></i>
                                        {{ $metric['name'] }}
                                    </td>
                                    <td>
                                        <span class="fw-semibold">{{ $metric['value'] }}</span>
                                        <small class="text-muted">{{ $metric['unit'] }}</small>
                                    </td>
                                    <td>{{ $metric['threshold'] }}</td>
                                    <td>
                                        <span class="badge bg-{{ $metric['status_color'] }}">
                                            {{ ucfirst($metric['status']) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span title="{{ $metric['updated_at'] }}">
                                            {{ $metric['updated_ago'] }}
                                        </span>
                                    </td>
                                    <td>
                                        <i class="mdi mdi-trending-{{ $metric['trend'] }} text-{{ $metric['trend'] === 'up' ? 'danger' : 'success' }}"></i>
                                        <small class="text-muted">{{ $metric['trend_percentage'] }}%</small>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Performance Chart
const ctx = document.getElementById('performance-chart').getContext('2d');
const performanceChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: @json($chartData['labels'] ?? []),
        datasets: [{
            label: 'Memory Usage (%)',
            data: @json($chartData['memory'] ?? []),
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.1)',
            tension: 0.1
        }, {
            label: 'CPU Usage (%)',
            data: @json($chartData['cpu'] ?? []),
            borderColor: 'rgb(255, 99, 132)',
            backgroundColor: 'rgba(255, 99, 132, 0.1)',
            tension: 0.1
        }, {
            label: 'Response Time (ms)',
            data: @json($chartData['response_time'] ?? []),
            borderColor: 'rgb(255, 205, 86)',
            backgroundColor: 'rgba(255, 205, 86, 0.1)',
            tension: 0.1,
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                max: 100,
                title: {
                    display: true,
                    text: 'Percentage (%)'
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                title: {
                    display: true,
                    text: 'Response Time (ms)'
                },
                grid: {
                    drawOnChartArea: false,
                }
            }
        },
        plugins: {
            legend: {
                display: true,
                position: 'top'
            }
        }
    }
});

// Real-time updates
function updateDashboard() {
    fetch('{{ route("admin.performance.dashboard.data") }}')
        .then(response => response.json())
        .then(data => {
            // Update metric cards
            document.getElementById('memory-usage').textContent = data.metrics.memory_usage + '%';
            document.getElementById('cpu-usage').textContent = data.metrics.cpu_usage + '%';
            document.getElementById('disk-space').textContent = data.metrics.disk_space + ' GB';
            document.getElementById('response-time').textContent = data.metrics.avg_response_time + 'ms';
            
            // Update chart
            performanceChart.data.labels = data.chartData.labels;
            performanceChart.data.datasets[0].data = data.chartData.memory;
            performanceChart.data.datasets[1].data = data.chartData.cpu;
            performanceChart.data.datasets[2].data = data.chartData.response_time;
            performanceChart.update();
        })
        .catch(error => console.error('Dashboard update error:', error));
}

function refreshDashboard() {
    updateDashboard();
    location.reload();
}

function refreshMetrics() {
    updateDashboard();
}

function exportReport() {
    window.open('{{ route("admin.performance.export") }}', '_blank');
}

function exportMetrics() {
    window.open('{{ route("admin.performance.metrics.export") }}', '_blank');
}

// Period selection for charts
document.querySelectorAll('[data-period]').forEach(button => {
    button.addEventListener('click', function() {
        const period = this.dataset.period;
        
        // Update active button
        document.querySelectorAll('[data-period]').forEach(btn => btn.classList.remove('active'));
        this.classList.add('active');
        
        // Fetch new data
        fetch(`{{ route("admin.performance.dashboard.data") }}?period=${period}`)
            .then(response => response.json())
            .then(data => {
                performanceChart.data.labels = data.chartData.labels;
                performanceChart.data.datasets[0].data = data.chartData.memory;
                performanceChart.data.datasets[1].data = data.chartData.cpu;
                performanceChart.data.datasets[2].data = data.chartData.response_time;
                performanceChart.update();
            })
            .catch(error => console.error('Period update error:', error));
    });
});

// Auto-refresh every 30 seconds
setInterval(updateDashboard, 30000);

// Initialize tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
});
</script>
@endsection