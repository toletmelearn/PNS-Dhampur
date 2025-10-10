@extends('layouts.app')

@section('title', 'Security Monitoring Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                            <li class="breadcrumb-item active">Security Monitoring</li>
                        </ol>
                    </nav>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-shield-alt text-primary me-2"></i>Security Monitoring Dashboard
                    </h1>
                    <p class="text-muted">Real-time security monitoring and threat detection</p>
                </div>
                <div>
                    <button type="button" class="btn btn-outline-primary me-2" onclick="refreshDashboard()">
                        <i class="fas fa-sync"></i> Refresh
                    </button>
                    <button type="button" class="btn btn-success" onclick="exportSecurityReport()">
                        <i class="fas fa-download"></i> Export Report
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Security Status Alert -->
    <div class="row mb-4">
        <div class="col-12">
            <div id="securityAlert" class="alert alert-info d-flex align-items-center" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                <div>
                    <strong>Security Status:</strong> <span id="securityStatusText">Monitoring active</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Security Overview Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Security Events (24h)
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="securityEventsCount">
                                {{ $securityStats['events_24h'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Blocked IPs
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="blockedIpsCount">
                                {{ $securityStats['blocked_ips'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-ban fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Failed Logins (24h)
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="failedLoginsCount">
                                {{ $securityStats['failed_logins_24h'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-times fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Active Sessions
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="activeSessionsCount">
                                {{ $securityStats['active_sessions'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Real-time Threat Detection -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-radar-alt me-2"></i>Real-time Threat Detection
                    </h6>
                    <div class="dropdown no-arrow">
                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink"
                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in"
                            aria-labelledby="dropdownMenuLink">
                            <div class="dropdown-header">Actions:</div>
                            <a class="dropdown-item" href="#" onclick="pauseThreatDetection()">Pause Detection</a>
                            <a class="dropdown-item" href="#" onclick="clearThreatLog()">Clear Log</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div id="threatDetectionLog" style="height: 300px; overflow-y: auto; background-color: #1a1a1a; color: #00ff00; font-family: 'Courier New', monospace; padding: 15px; border-radius: 5px;">
                        <div class="threat-log-entry">
                            <span class="timestamp">[{{ now()->format('H:i:s') }}]</span>
                            <span class="status text-success">SYSTEM</span>
                            Threat detection system initialized and monitoring...
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-cog me-2"></i>Detection Settings
                    </h6>
                </div>
                <div class="card-body">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="detectBruteForce" checked>
                        <label class="form-check-label" for="detectBruteForce">
                            Brute Force Detection
                        </label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="detectSqlInjection" checked>
                        <label class="form-check-label" for="detectSqlInjection">
                            SQL Injection Detection
                        </label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="detectXss" checked>
                        <label class="form-check-label" for="detectXss">
                            XSS Attack Detection
                        </label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="detectSuspiciousActivity" checked>
                        <label class="form-check-label" for="detectSuspiciousActivity">
                            Suspicious Activity
                        </label>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="detectRateLimit" checked>
                        <label class="form-check-label" for="detectRateLimit">
                            Rate Limit Violations
                        </label>
                    </div>
                    <button class="btn btn-primary btn-sm" onclick="updateDetectionSettings()">
                        <i class="fas fa-save"></i> Update Settings
                    </button>
                </div>
            </div>

            <!-- System Health -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">
                        <i class="fas fa-heartbeat me-2"></i>System Health
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <small class="text-muted">Database</small>
                        <div class="progress progress-sm">
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $systemHealth['database'] ?? 100 }}%"></div>
                        </div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Cache</small>
                        <div class="progress progress-sm">
                            <div class="progress-bar bg-info" role="progressbar" style="width: {{ $systemHealth['cache'] ?? 100 }}%"></div>
                        </div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Storage</small>
                        <div class="progress progress-sm">
                            <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $systemHealth['storage'] ?? 100 }}%"></div>
                        </div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Security Middleware</small>
                        <div class="progress progress-sm">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $systemHealth['security'] ?? 100 }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Security Events -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-history me-2"></i>Recent Security Events
                    </h6>
                    <div>
                        <button class="btn btn-outline-primary btn-sm me-2" onclick="refreshSecurityEvents()">
                            <i class="fas fa-sync"></i> Refresh
                        </button>
                        <a href="{{ route('admin.security.logs') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-list"></i> View All Logs
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="securityEventsTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Timestamp</th>
                                    <th>Event Type</th>
                                    <th>Severity</th>
                                    <th>IP Address</th>
                                    <th>User</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentEvents ?? [] as $event)
                                <tr>
                                    <td>{{ $event['timestamp'] ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge badge-{{ $event['type_color'] ?? 'secondary' }}">
                                            {{ $event['type'] ?? 'Unknown' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $event['severity_color'] ?? 'secondary' }}">
                                            {{ $event['severity'] ?? 'Unknown' }}
                                        </span>
                                    </td>
                                    <td>{{ $event['ip_address'] ?? 'N/A' }}</td>
                                    <td>{{ $event['user'] ?? 'Anonymous' }}</td>
                                    <td>{{ $event['description'] ?? 'No description' }}</td>
                                    <td>
                                        @if(isset($event['ip_address']) && $event['ip_address'] !== 'N/A')
                                        <button class="btn btn-danger btn-sm" onclick="blockIP('{{ $event['ip_address'] }}')">
                                            <i class="fas fa-ban"></i> Block IP
                                        </button>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No recent security events</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Rate Limiting & IP Management -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-tachometer-alt me-2"></i>Rate Limiting Statistics
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="text-center">
                                <div class="h4 mb-0 text-primary">{{ $rateLimitStats['violations_today'] ?? 0 }}</div>
                                <small class="text-muted">Violations Today</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <div class="h4 mb-0 text-success">{{ $rateLimitStats['efficiency'] ?? 0 }}%</div>
                                <small class="text-muted">Efficiency</small>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="mb-2">
                        <small class="text-muted">API Rate Limit Usage</small>
                        <div class="progress">
                            <div class="progress-bar" role="progressbar" style="width: {{ $rateLimitStats['api_usage'] ?? 0 }}%"></div>
                        </div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Web Rate Limit Usage</small>
                        <div class="progress">
                            <div class="progress-bar bg-info" role="progressbar" style="width: {{ $rateLimitStats['web_usage'] ?? 0 }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-shield-virus me-2"></i>Blocked IPs Management
                    </h6>
                    <button class="btn btn-outline-primary btn-sm" onclick="showBlockIPModal()">
                        <i class="fas fa-plus"></i> Block IP
                    </button>
                </div>
                <div class="card-body">
                    <div id="blockedIPsList" style="max-height: 200px; overflow-y: auto;">
                        @forelse($blockedIPs ?? [] as $ip)
                        <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                            <span class="font-monospace">{{ $ip['address'] }}</span>
                            <div>
                                <small class="text-muted me-2">{{ $ip['reason'] ?? 'Manual block' }}</small>
                                <button class="btn btn-success btn-sm" onclick="unblockIP('{{ $ip['address'] }}')">
                                    <i class="fas fa-unlock"></i>
                                </button>
                            </div>
                        </div>
                        @empty
                        <div class="text-center text-muted">No blocked IPs</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Suspicious Activities -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>Suspicious Activities
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="suspiciousActivitiesTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Timestamp</th>
                                    <th>Activity Type</th>
                                    <th>Risk Level</th>
                                    <th>IP Address</th>
                                    <th>User Agent</th>
                                    <th>Details</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($suspiciousActivities ?? [] as $activity)
                                <tr>
                                    <td>{{ $activity['timestamp'] ?? 'N/A' }}</td>
                                    <td>{{ $activity['type'] ?? 'Unknown' }}</td>
                                    <td>
                                        <span class="badge badge-{{ $activity['risk_color'] ?? 'secondary' }}">
                                            {{ $activity['risk_level'] ?? 'Unknown' }}
                                        </span>
                                    </td>
                                    <td class="font-monospace">{{ $activity['ip_address'] ?? 'N/A' }}</td>
                                    <td class="text-truncate" style="max-width: 200px;" title="{{ $activity['user_agent'] ?? 'N/A' }}">
                                        {{ $activity['user_agent'] ?? 'N/A' }}
                                    </td>
                                    <td>{{ $activity['details'] ?? 'No details' }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-info btn-sm" onclick="investigateActivity('{{ $activity['id'] ?? '' }}')">
                                                <i class="fas fa-search"></i>
                                            </button>
                                            @if(isset($activity['ip_address']) && $activity['ip_address'] !== 'N/A')
                                            <button class="btn btn-danger btn-sm" onclick="blockIP('{{ $activity['ip_address'] }}')">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No suspicious activities detected</td>
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

<!-- Block IP Modal -->
<div class="modal fade" id="blockIPModal" tabindex="-1" role="dialog" aria-labelledby="blockIPModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="blockIPModalLabel">Block IP Address</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="blockIPForm">
                    <div class="form-group">
                        <label for="ipAddress">IP Address</label>
                        <input type="text" class="form-control" id="ipAddress" placeholder="192.168.1.1" required>
                    </div>
                    <div class="form-group">
                        <label for="blockReason">Reason</label>
                        <select class="form-control" id="blockReason">
                            <option value="manual">Manual Block</option>
                            <option value="brute_force">Brute Force Attack</option>
                            <option value="sql_injection">SQL Injection Attempt</option>
                            <option value="suspicious_activity">Suspicious Activity</option>
                            <option value="rate_limit">Rate Limit Violation</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="blockDuration">Duration</label>
                        <select class="form-control" id="blockDuration">
                            <option value="1">1 Hour</option>
                            <option value="24">24 Hours</option>
                            <option value="168">1 Week</option>
                            <option value="permanent">Permanent</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="confirmBlockIP()">Block IP</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
// Real-time threat detection
let threatDetectionActive = true;
let threatLogContainer = document.getElementById('threatDetectionLog');

// Initialize dashboard
document.addEventListener('DOMContentLoaded', function() {
    initializeDashboard();
    startRealTimeMonitoring();
});

function initializeDashboard() {
    // Load initial data
    refreshSecurityStats();
    refreshSecurityEvents();
    refreshSuspiciousActivities();
    
    // Set up auto-refresh
    setInterval(refreshSecurityStats, 30000); // Every 30 seconds
    setInterval(refreshSecurityEvents, 60000); // Every minute
}

function startRealTimeMonitoring() {
    if (!threatDetectionActive) return;
    
    // Simulate real-time threat detection
    setInterval(function() {
        if (Math.random() < 0.1) { // 10% chance of new threat
            addThreatLogEntry(generateMockThreat());
        }
    }, 5000);
}

function generateMockThreat() {
    const threats = [
        { type: 'BRUTE_FORCE', severity: 'HIGH', message: 'Multiple failed login attempts detected from IP 192.168.1.100' },
        { type: 'SQL_INJECTION', severity: 'CRITICAL', message: 'SQL injection attempt blocked on /api/users endpoint' },
        { type: 'RATE_LIMIT', severity: 'MEDIUM', message: 'Rate limit exceeded for IP 10.0.0.50' },
        { type: 'SUSPICIOUS', severity: 'LOW', message: 'Unusual user agent detected: "BadBot/1.0"' }
    ];
    
    return threats[Math.floor(Math.random() * threats.length)];
}

function addThreatLogEntry(threat) {
    const timestamp = new Date().toLocaleTimeString();
    const severityClass = {
        'LOW': 'text-info',
        'MEDIUM': 'text-warning', 
        'HIGH': 'text-danger',
        'CRITICAL': 'text-danger blink'
    };
    
    const entry = document.createElement('div');
    entry.className = 'threat-log-entry';
    entry.innerHTML = `
        <span class="timestamp">[${timestamp}]</span>
        <span class="status ${severityClass[threat.severity]}">${threat.type}</span>
        ${threat.message}
    `;
    
    threatLogContainer.appendChild(entry);
    threatLogContainer.scrollTop = threatLogContainer.scrollHeight;
    
    // Keep only last 50 entries
    while (threatLogContainer.children.length > 50) {
        threatLogContainer.removeChild(threatLogContainer.firstChild);
    }
}

function refreshDashboard() {
    refreshSecurityStats();
    refreshSecurityEvents();
    refreshSuspiciousActivities();
    showToast('Dashboard refreshed successfully', 'success');
}

function refreshSecurityStats() {
    fetch('/admin/security/stats')
        .then(response => response.json())
        .then(data => {
            document.getElementById('securityEventsCount').textContent = data.events_24h || 0;
            document.getElementById('blockedIpsCount').textContent = data.blocked_ips || 0;
            document.getElementById('failedLoginsCount').textContent = data.failed_logins_24h || 0;
            document.getElementById('activeSessionsCount').textContent = data.active_sessions || 0;
        })
        .catch(error => console.error('Error refreshing stats:', error));
}

function refreshSecurityEvents() {
    fetch('/admin/security/events')
        .then(response => response.json())
        .then(data => {
            updateSecurityEventsTable(data.events || []);
        })
        .catch(error => console.error('Error refreshing events:', error));
}

function refreshSuspiciousActivities() {
    fetch('/admin/security/suspicious-activities')
        .then(response => response.json())
        .then(data => {
            updateSuspiciousActivitiesTable(data.activities || []);
        })
        .catch(error => console.error('Error refreshing suspicious activities:', error));
}

function updateSecurityEventsTable(events) {
    const tbody = document.querySelector('#securityEventsTable tbody');
    tbody.innerHTML = '';
    
    if (events.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No recent security events</td></tr>';
        return;
    }
    
    events.forEach(event => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${event.timestamp}</td>
            <td><span class="badge badge-${event.type_color}">${event.type}</span></td>
            <td><span class="badge badge-${event.severity_color}">${event.severity}</span></td>
            <td>${event.ip_address}</td>
            <td>${event.user}</td>
            <td>${event.description}</td>
            <td>
                ${event.ip_address !== 'N/A' ? `<button class="btn btn-danger btn-sm" onclick="blockIP('${event.ip_address}')"><i class="fas fa-ban"></i> Block IP</button>` : ''}
            </td>
        `;
        tbody.appendChild(row);
    });
}

function updateSuspiciousActivitiesTable(activities) {
    const tbody = document.querySelector('#suspiciousActivitiesTable tbody');
    tbody.innerHTML = '';
    
    if (activities.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No suspicious activities detected</td></tr>';
        return;
    }
    
    activities.forEach(activity => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${activity.timestamp}</td>
            <td>${activity.type}</td>
            <td><span class="badge badge-${activity.risk_color}">${activity.risk_level}</span></td>
            <td class="font-monospace">${activity.ip_address}</td>
            <td class="text-truncate" style="max-width: 200px;" title="${activity.user_agent}">${activity.user_agent}</td>
            <td>${activity.details}</td>
            <td>
                <div class="btn-group" role="group">
                    <button class="btn btn-info btn-sm" onclick="investigateActivity('${activity.id}')"><i class="fas fa-search"></i></button>
                    ${activity.ip_address !== 'N/A' ? `<button class="btn btn-danger btn-sm" onclick="blockIP('${activity.ip_address}')"><i class="fas fa-ban"></i></button>` : ''}
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function blockIP(ipAddress) {
    document.getElementById('ipAddress').value = ipAddress;
    $('#blockIPModal').modal('show');
}

function showBlockIPModal() {
    document.getElementById('ipAddress').value = '';
    $('#blockIPModal').modal('show');
}

function confirmBlockIP() {
    const ipAddress = document.getElementById('ipAddress').value;
    const reason = document.getElementById('blockReason').value;
    const duration = document.getElementById('blockDuration').value;
    
    if (!ipAddress) {
        showToast('Please enter an IP address', 'error');
        return;
    }
    
    fetch('/admin/security/block-ip', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            ip_address: ipAddress,
            reason: reason,
            duration: duration
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('IP address blocked successfully', 'success');
            $('#blockIPModal').modal('hide');
            refreshDashboard();
        } else {
            showToast(data.message || 'Failed to block IP address', 'error');
        }
    })
    .catch(error => {
        console.error('Error blocking IP:', error);
        showToast('Failed to block IP address', 'error');
    });
}

function unblockIP(ipAddress) {
    if (!confirm(`Are you sure you want to unblock IP address ${ipAddress}?`)) {
        return;
    }
    
    fetch('/admin/security/unblock-ip', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            ip_address: ipAddress
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('IP address unblocked successfully', 'success');
            refreshDashboard();
        } else {
            showToast(data.message || 'Failed to unblock IP address', 'error');
        }
    })
    .catch(error => {
        console.error('Error unblocking IP:', error);
        showToast('Failed to unblock IP address', 'error');
    });
}

function investigateActivity(activityId) {
    // Open investigation modal or redirect to detailed view
    window.open(`/admin/security/investigate/${activityId}`, '_blank');
}

function pauseThreatDetection() {
    threatDetectionActive = !threatDetectionActive;
    const status = threatDetectionActive ? 'resumed' : 'paused';
    addThreatLogEntry({
        type: 'SYSTEM',
        severity: 'LOW',
        message: `Threat detection ${status}`
    });
    showToast(`Threat detection ${status}`, 'info');
}

function clearThreatLog() {
    threatLogContainer.innerHTML = '<div class="threat-log-entry"><span class="timestamp">[' + new Date().toLocaleTimeString() + ']</span><span class="status text-info">SYSTEM</span> Threat log cleared</div>';
}

function updateDetectionSettings() {
    const settings = {
        brute_force: document.getElementById('detectBruteForce').checked,
        sql_injection: document.getElementById('detectSqlInjection').checked,
        xss: document.getElementById('detectXss').checked,
        suspicious_activity: document.getElementById('detectSuspiciousActivity').checked,
        rate_limit: document.getElementById('detectRateLimit').checked
    };
    
    fetch('/admin/security/update-detection-settings', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(settings)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Detection settings updated successfully', 'success');
        } else {
            showToast(data.message || 'Failed to update settings', 'error');
        }
    })
    .catch(error => {
        console.error('Error updating settings:', error);
        showToast('Failed to update settings', 'error');
    });
}

function exportSecurityReport() {
    window.open('/admin/security/export-report?format=pdf', '_blank');
}

function showToast(message, type) {
    // Simple toast notification
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    toast.innerHTML = `
        ${message}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
        }
    }, 5000);
}

// CSS for blinking critical alerts
const style = document.createElement('style');
style.textContent = `
    .blink {
        animation: blink 1s linear infinite;
    }
    
    @keyframes blink {
        0% { opacity: 1; }
        50% { opacity: 0.5; }
        100% { opacity: 1; }
    }
    
    .threat-log-entry {
        margin-bottom: 5px;
        font-size: 12px;
    }
    
    .timestamp {
        color: #888;
    }
    
    .status {
        font-weight: bold;
        margin: 0 10px;
    }
`;
document.head.appendChild(style);
</script>
@endsection