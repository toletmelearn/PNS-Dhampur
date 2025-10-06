@extends('layouts.app')

@section('title', 'Rate Limit Monitor')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-shield-alt"></i>
                        Rate Limit Monitor
                    </h3>
                    <div class="btn-group">
                        <button type="button" class="btn btn-primary" onclick="refreshDashboard()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                        <button type="button" class="btn btn-info" onclick="exportLogs()">
                            <i class="fas fa-download"></i> Export Logs
                        </button>
                        <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#configModal">
                            <i class="fas fa-cog"></i> Configuration
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-lg-3 col-md-6">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4>{{ $stats['login_blocks_hour'] }}</h4>
                                            <p class="mb-0">Login Blocks (1h)</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-sign-in-alt fa-2x"></i>
                                        </div>
                                    </div>
                                    <small>{{ $stats['login_blocks_day'] }} in last 24h</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4>{{ $stats['api_blocks_hour'] }}</h4>
                                            <p class="mb-0">API Blocks (1h)</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-code fa-2x"></i>
                                        </div>
                                    </div>
                                    <small>{{ $stats['api_blocks_day'] }} in last 24h</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4>{{ $stats['form_blocks_hour'] }}</h4>
                                            <p class="mb-0">Form Blocks (1h)</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-edit fa-2x"></i>
                                        </div>
                                    </div>
                                    <small>{{ $stats['form_blocks_day'] }} in last 24h</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="card bg-danger text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4>{{ $stats['download_blocks_hour'] }}</h4>
                                            <p class="mb-0">Download Blocks (1h)</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-download fa-2x"></i>
                                        </div>
                                    </div>
                                    <small>{{ $stats['download_blocks_day'] }} in last 24h</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Active Limits Summary -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-clock"></i>
                                        Active Rate Limits: {{ $stats['total_active_limits'] }}
                                    </h5>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Blocks and Top Offenders -->
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-history"></i>
                                        Recent Rate Limit Blocks
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Type</th>
                                                    <th>Identifier</th>
                                                    <th>Blocked At</th>
                                                    <th>Attempts</th>
                                                    <th>Expires At</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($recentBlocks as $block)
                                                <tr>
                                                    <td>
                                                        <span class="badge badge-{{ $block['type'] === 'Login' ? 'primary' : ($block['type'] === 'API' ? 'success' : ($block['type'] === 'Form' ? 'warning' : 'danger')) }}">
                                                            {{ $block['type'] }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <code>{{ Str::limit($block['identifier'], 30) }}</code>
                                                    </td>
                                                    <td>{{ $block['blocked_at']->format('M d, Y H:i:s') }}</td>
                                                    <td>
                                                        <span class="badge badge-secondary">{{ $block['attempts'] }}</span>
                                                    </td>
                                                    <td>
                                                        @if($block['expires_at'])
                                                            {{ $block['expires_at']->format('M d, Y H:i:s') }}
                                                            <br>
                                                            <small class="text-muted">
                                                                ({{ $block['expires_at']->diffForHumans() }})
                                                            </small>
                                                        @else
                                                            <span class="text-muted">N/A</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                onclick="clearRateLimit('{{ $block['identifier'] }}', '{{ strtolower($block['type']) }}')">
                                                            <i class="fas fa-times"></i> Clear
                                                        </button>
                                                    </td>
                                                </tr>
                                                @empty
                                                <tr>
                                                    <td colspan="6" class="text-center text-muted">
                                                        <i class="fas fa-info-circle"></i>
                                                        No recent rate limit blocks found
                                                    </td>
                                                </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        Top Offenders
                                    </h5>
                                </div>
                                <div class="card-body">
                                    @forelse($topOffenders as $offender)
                                    <div class="d-flex justify-content-between align-items-center mb-3 p-2 border rounded">
                                        <div>
                                            <strong>{{ Str::limit($offender['identifier'], 20) }}</strong>
                                            <br>
                                            <small class="text-muted">
                                                Types: {{ implode(', ', $offender['types']) }}
                                            </small>
                                        </div>
                                        <div class="text-right">
                                            <span class="badge badge-danger">{{ $offender['total_blocks'] }}</span>
                                            <br>
                                            <button type="button" class="btn btn-sm btn-outline-warning mt-1" 
                                                    onclick="clearRateLimit('{{ $offender['identifier'] }}', 'all')">
                                                Clear All
                                            </button>
                                        </div>
                                    </div>
                                    @empty
                                    <div class="text-center text-muted">
                                        <i class="fas fa-info-circle"></i>
                                        <p>No offenders found</p>
                                    </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Configuration Modal -->
<div class="modal fade" id="configModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rate Limit Configuration</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="configContent">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin"></i>
                        Loading configuration...
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Clear Rate Limit Modal -->
<div class="modal fade" id="clearModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Clear Rate Limit</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to clear the rate limit for <strong id="clearIdentifier"></strong>?</p>
                <input type="hidden" id="clearType">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="confirmClearRateLimit()">Clear</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function refreshDashboard() {
    location.reload();
}

function exportLogs() {
    window.open('{{ route("learning.admin.rate-limit.export-logs") }}', '_blank');
}

function clearRateLimit(identifier, type) {
    $('#clearIdentifier').text(identifier);
    $('#clearType').val(type);
    $('#clearModal').modal('show');
}

function confirmClearRateLimit() {
    const identifier = $('#clearIdentifier').text();
    const type = $('#clearType').val();
    
    $.ajax({
        url: '{{ route("learning.admin.rate-limit.clear") }}',
        method: 'POST',
        data: {
            identifier: identifier,
            type: type,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                $('#clearModal').modal('hide');
                setTimeout(() => location.reload(), 1000);
            } else {
                toastr.error('Failed to clear rate limit');
            }
        },
        error: function() {
            toastr.error('An error occurred while clearing rate limit');
        }
    });
}

$('#configModal').on('show.bs.modal', function() {
    $.get('{{ route("learning.admin.rate-limit.config") }}')
        .done(function(config) {
            let html = '<div class="row">';
            
            // Login Configuration
            html += '<div class="col-md-6"><div class="card"><div class="card-header"><h6>Login Rate Limits</h6></div><div class="card-body">';
            html += '<ul class="list-unstyled">';
            html += '<li><strong>IP Limit:</strong> ' + config.login.ip_limit + ' attempts per ' + config.login.ip_window + ' minutes</li>';
            html += '<li><strong>Email Limit:</strong> ' + config.login.email_limit + ' attempts per ' + config.login.email_window + ' minutes</li>';
            html += '<li><strong>Global Limit:</strong> ' + config.login.global_limit + ' attempts per ' + config.login.global_window + ' minute</li>';
            html += '<li><strong>Rapid Limit:</strong> ' + config.login.rapid_limit + ' attempts per ' + (config.login.rapid_window * 60) + ' seconds</li>';
            html += '</ul></div></div></div>';
            
            // API Configuration
            html += '<div class="col-md-6"><div class="card"><div class="card-header"><h6>API Rate Limits</h6></div><div class="card-body">';
            html += '<ul class="list-unstyled">';
            Object.keys(config.api).forEach(role => {
                if (role !== 'window') {
                    html += '<li><strong>' + role.replace('_', ' ').toUpperCase() + ':</strong> ' + config.api[role] + ' requests per minute</li>';
                }
            });
            html += '</ul></div></div></div>';
            
            html += '</div><div class="row">';
            
            // Form Configuration
            html += '<div class="col-md-6"><div class="card"><div class="card-header"><h6>Form Rate Limits</h6></div><div class="card-body">';
            html += '<ul class="list-unstyled">';
            html += '<li><strong>Default Limit:</strong> ' + config.form.default_limit + ' submissions per minute</li>';
            html += '<li><strong>Critical Forms:</strong> ' + config.form.critical_limit + ' submissions per minute</li>';
            html += '<li><strong>Rapid Detection:</strong> ' + config.form.rapid_limit + ' submissions per ' + (config.form.rapid_window * 60) + ' seconds</li>';
            html += '</ul></div></div></div>';
            
            // Download Configuration
            html += '<div class="col-md-6"><div class="card"><div class="card-header"><h6>Download Rate Limits</h6></div><div class="card-body">';
            html += '<ul class="list-unstyled">';
            Object.keys(config.download).forEach(role => {
                if (typeof config.download[role] === 'object' && config.download[role].count) {
                    html += '<li><strong>' + role.replace('_', ' ').toUpperCase() + ':</strong> ' + config.download[role].count + ' downloads, ' + config.download[role].bandwidth + ' per hour</li>';
                }
            });
            html += '<li><strong>Global Limit:</strong> ' + config.download.global_limit + ' downloads per hour</li>';
            html += '<li><strong>Rapid Detection:</strong> ' + config.download.rapid_limit + ' downloads per minute</li>';
            html += '</ul></div></div></div>';
            
            html += '</div>';
            
            $('#configContent').html(html);
        })
        .fail(function() {
            $('#configContent').html('<div class="alert alert-danger">Failed to load configuration</div>');
        });
});

// Auto-refresh every 30 seconds
setInterval(function() {
    if (!$('.modal').hasClass('show')) {
        refreshDashboard();
    }
}, 30000);
</script>
@endpush