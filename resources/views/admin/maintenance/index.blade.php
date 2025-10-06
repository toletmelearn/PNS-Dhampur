@extends('layouts.admin')

@section('title', 'System Maintenance')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">System Maintenance</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">System Maintenance</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- System Information Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-1">
                            <h5 class="text-muted fw-normal mt-0 text-truncate" title="System Info">System Info</h5>
                            <h3 class="my-2 py-1">{{ $systemInfo['php_version'] }}</h3>
                            <p class="mb-0 text-muted">
                                <span class="text-success me-2">PHP Version</span>
                            </p>
                        </div>
                        <div class="align-self-center">
                            <div class="avatar-sm rounded-circle bg-primary mini-stat-icon">
                                <span class="avatar-title rounded-circle bg-primary">
                                    <i class="bx bx-server font-size-20"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-1">
                            <h5 class="text-muted fw-normal mt-0 text-truncate" title="Database">Database</h5>
                            <h3 class="my-2 py-1">{{ $databaseInfo['table_count'] ?? 0 }}</h3>
                            <p class="mb-0 text-muted">
                                <span class="text-success me-2">Tables</span>
                                {{ number_format($databaseInfo['size_mb'] ?? 0, 1) }} MB
                            </p>
                        </div>
                        <div class="align-self-center">
                            <div class="avatar-sm rounded-circle bg-success mini-stat-icon">
                                <span class="avatar-title rounded-circle bg-success">
                                    <i class="bx bx-data font-size-20"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-1">
                            <h5 class="text-muted fw-normal mt-0 text-truncate" title="Cache Status">Cache Status</h5>
                            <h3 class="my-2 py-1">
                                @if($cacheInfo['config_cached'] || $cacheInfo['routes_cached'] || $cacheInfo['views_cached'])
                                    <span class="text-success">Active</span>
                                @else
                                    <span class="text-warning">Inactive</span>
                                @endif
                            </h3>
                            <p class="mb-0 text-muted">
                                <span class="text-info me-2">{{ $cacheInfo['driver'] }}</span>
                            </p>
                        </div>
                        <div class="align-self-center">
                            <div class="avatar-sm rounded-circle bg-info mini-stat-icon">
                                <span class="avatar-title rounded-circle bg-info">
                                    <i class="bx bx-layer font-size-20"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-1">
                            <h5 class="text-muted fw-normal mt-0 text-truncate" title="Log Files">Log Files</h5>
                            <h3 class="my-2 py-1">{{ $logInfo['file_count'] ?? 0 }}</h3>
                            <p class="mb-0 text-muted">
                                <span class="text-warning me-2">{{ $logInfo['total_size_mb'] ?? 0 }} MB</span>
                            </p>
                        </div>
                        <div class="align-self-center">
                            <div class="avatar-sm rounded-circle bg-warning mini-stat-icon">
                                <span class="avatar-title rounded-circle bg-warning">
                                    <i class="bx bx-file font-size-20"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Maintenance Tools -->
    <div class="row">
        <!-- Cache Management -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Cache Management</h4>
                </div>
                <div class="card-body">
                    <p class="text-muted">Clear application caches to improve performance and resolve issues.</p>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="cache_config" value="config" checked>
                            <label class="form-check-label" for="cache_config">
                                Configuration Cache
                                @if($cacheInfo['config_cached'])
                                    <span class="badge bg-success ms-2">Cached</span>
                                @else
                                    <span class="badge bg-secondary ms-2">Not Cached</span>
                                @endif
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="cache_route" value="route" checked>
                            <label class="form-check-label" for="cache_route">
                                Route Cache
                                @if($cacheInfo['routes_cached'])
                                    <span class="badge bg-success ms-2">Cached</span>
                                @else
                                    <span class="badge bg-secondary ms-2">Not Cached</span>
                                @endif
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="cache_view" value="view" checked>
                            <label class="form-check-label" for="cache_view">
                                View Cache
                                @if($cacheInfo['views_cached'])
                                    <span class="badge bg-success ms-2">Cached</span>
                                @else
                                    <span class="badge bg-secondary ms-2">Not Cached</span>
                                @endif
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="cache_application" value="application" checked>
                            <label class="form-check-label" for="cache_application">
                                Application Cache
                            </label>
                        </div>
                    </div>
                    
                    <button type="button" class="btn btn-primary" id="clearCacheBtn">
                        <i class="bx bx-refresh me-1"></i> Clear Selected Caches
                    </button>
                </div>
            </div>
        </div>

        <!-- Database Optimization -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Database Optimization</h4>
                </div>
                <div class="card-body">
                    <p class="text-muted">Optimize database tables for better performance.</p>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="db_optimize" value="optimize" checked>
                            <label class="form-check-label" for="db_optimize">
                                Optimize Tables
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="db_analyze" value="analyze" checked>
                            <label class="form-check-label" for="db_analyze">
                                Analyze Tables
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="db_repair" value="repair">
                            <label class="form-check-label" for="db_repair">
                                Repair Tables
                            </label>
                        </div>
                    </div>
                    
                    <button type="button" class="btn btn-success" id="optimizeDbBtn">
                        <i class="bx bx-cog me-1"></i> Optimize Database
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Log Viewer and System Updates -->
    <div class="row">
        <!-- Log Viewer -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">Log Viewer</h4>
                        <div class="d-flex gap-2">
                            <select class="form-select form-select-sm" id="logFileSelect" style="width: auto;">
                                <option value="laravel.log">laravel.log</option>
                            </select>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="refreshLogsBtn">
                                <i class="bx bx-refresh"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" id="clearLogsBtn">
                                <i class="bx bx-trash"></i> Clear
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" id="logLevel">
                                <option value="all">All Levels</option>
                                <option value="error">Error</option>
                                <option value="warning">Warning</option>
                                <option value="info">Info</option>
                                <option value="debug">Debug</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" id="logLines">
                                <option value="50">50 lines</option>
                                <option value="100" selected>100 lines</option>
                                <option value="200">200 lines</option>
                                <option value="500">500 lines</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <input type="text" class="form-control form-control-sm" id="logSearch" placeholder="Search logs...">
                        </div>
                    </div>
                    
                    <div id="logContent" style="height: 400px; overflow-y: auto; background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace; font-size: 12px;">
                        <div class="text-center text-muted">
                            <i class="bx bx-loader bx-spin"></i> Loading logs...
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Updates & Quick Actions -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Quick Actions</h4>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-primary" id="runMaintenanceBtn">
                            <i class="bx bx-wrench me-1"></i> Run Full Maintenance
                        </button>
                        <button type="button" class="btn btn-outline-info" id="checkUpdatesBtn">
                            <i class="bx bx-cloud-download me-1"></i> Check Updates
                        </button>
                        <button type="button" class="btn btn-outline-warning" id="systemInfoBtn">
                            <i class="bx bx-info-circle me-1"></i> System Information
                        </button>
                    </div>
                </div>
            </div>

            <!-- System Information -->
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">System Information</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <tbody>
                                <tr>
                                    <td>Laravel Version</td>
                                    <td>{{ $systemInfo['laravel_version'] }}</td>
                                </tr>
                                <tr>
                                    <td>PHP Version</td>
                                    <td>{{ $systemInfo['php_version'] }}</td>
                                </tr>
                                <tr>
                                    <td>Memory Limit</td>
                                    <td>{{ $systemInfo['memory_limit'] }}</td>
                                </tr>
                                <tr>
                                    <td>Max Execution Time</td>
                                    <td>{{ $systemInfo['max_execution_time'] }}s</td>
                                </tr>
                                <tr>
                                    <td>Upload Max Size</td>
                                    <td>{{ $systemInfo['upload_max_filesize'] }}</td>
                                </tr>
                                <tr>
                                    <td>Free Disk Space</td>
                                    <td>{{ number_format($systemInfo['disk_space']['free'] / 1024 / 1024 / 1024, 2) }} GB</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Modal -->
<div class="modal fade" id="loadingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 mb-0">Processing...</p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Load log files on page load
    loadLogFiles();
    loadLogs();

    // Clear Cache
    $('#clearCacheBtn').click(function() {
        const cacheTypes = [];
        $('input[type="checkbox"]:checked').each(function() {
            if ($(this).val() !== 'optimize' && $(this).val() !== 'analyze' && $(this).val() !== 'repair') {
                cacheTypes.push($(this).val());
            }
        });

        if (cacheTypes.length === 0) {
            Swal.fire('Warning', 'Please select at least one cache type to clear.', 'warning');
            return;
        }

        $('#loadingModal').modal('show');
        
        $.ajax({
            url: '{{ route("admin.maintenance.clear-cache") }}',
            method: 'POST',
            data: {
                cache_types: cacheTypes,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                $('#loadingModal').modal('hide');
                if (response.success) {
                    Swal.fire('Success', response.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function(xhr) {
                $('#loadingModal').modal('hide');
                Swal.fire('Error', 'Failed to clear cache', 'error');
            }
        });
    });

    // Optimize Database
    $('#optimizeDbBtn').click(function() {
        const operations = [];
        $('#db_optimize:checked, #db_analyze:checked, #db_repair:checked').each(function() {
            operations.push($(this).val());
        });

        if (operations.length === 0) {
            Swal.fire('Warning', 'Please select at least one operation.', 'warning');
            return;
        }

        $('#loadingModal').modal('show');
        
        $.ajax({
            url: '{{ route("admin.maintenance.optimize-database") }}',
            method: 'POST',
            data: {
                operations: operations,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                $('#loadingModal').modal('hide');
                if (response.success) {
                    Swal.fire('Success', response.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function(xhr) {
                $('#loadingModal').modal('hide');
                Swal.fire('Error', 'Failed to optimize database', 'error');
            }
        });
    });

    // Load Log Files
    function loadLogFiles() {
        $.ajax({
            url: '{{ route("admin.maintenance.log-files") }}',
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    const select = $('#logFileSelect');
                    select.empty();
                    response.data.forEach(function(file) {
                        select.append(`<option value="${file.name}">${file.name}</option>`);
                    });
                }
            }
        });
    }

    // Load Logs
    function loadLogs() {
        const file = $('#logFileSelect').val();
        const level = $('#logLevel').val();
        const lines = $('#logLines').val();
        const search = $('#logSearch').val();

        $.ajax({
            url: '{{ route("admin.maintenance.view-logs") }}',
            method: 'GET',
            data: {
                file: file,
                level: level,
                lines: lines,
                search: search
            },
            success: function(response) {
                if (response.success) {
                    const logContent = $('#logContent');
                    if (response.data.lines.length > 0) {
                        logContent.html(response.data.lines.map(line => 
                            `<div style="margin-bottom: 2px; word-wrap: break-word;">${escapeHtml(line)}</div>`
                        ).join(''));
                    } else {
                        logContent.html('<div class="text-center text-muted">No log entries found</div>');
                    }
                } else {
                    $('#logContent').html('<div class="text-center text-danger">Failed to load logs</div>');
                }
            },
            error: function() {
                $('#logContent').html('<div class="text-center text-danger">Error loading logs</div>');
            }
        });
    }

    // Log controls
    $('#logFileSelect, #logLevel, #logLines').change(loadLogs);
    $('#logSearch').on('input', debounce(loadLogs, 500));
    $('#refreshLogsBtn').click(loadLogs);

    // Clear Logs
    $('#clearLogsBtn').click(function() {
        Swal.fire({
            title: 'Clear Log Files?',
            text: 'This will clear the selected log file. This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, clear it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("admin.maintenance.clear-logs") }}',
                    method: 'POST',
                    data: {
                        files: [$('#logFileSelect').val()],
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Cleared!', response.message, 'success');
                            loadLogs();
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    }
                });
            }
        });
    });

    // Run Full Maintenance
    $('#runMaintenanceBtn').click(function() {
        Swal.fire({
            title: 'Run Full Maintenance?',
            text: 'This will clear caches, optimize database, and clean large log files.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, run maintenance!'
        }).then((result) => {
            if (result.isConfirmed) {
                $('#loadingModal').modal('show');
                
                $.ajax({
                    url: '{{ route("admin.maintenance.run-maintenance") }}',
                    method: 'POST',
                    data: {
                        tasks: ['cache', 'database', 'logs'],
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        $('#loadingModal').modal('hide');
                        if (response.success) {
                            Swal.fire('Success', response.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    },
                    error: function() {
                        $('#loadingModal').modal('hide');
                        Swal.fire('Error', 'Failed to run maintenance', 'error');
                    }
                });
            }
        });
    });

    // Check Updates
    $('#checkUpdatesBtn').click(function() {
        $('#loadingModal').modal('show');
        
        $.ajax({
            url: '{{ route("admin.maintenance.check-updates") }}',
            method: 'GET',
            success: function(response) {
                $('#loadingModal').modal('hide');
                if (response.success) {
                    Swal.fire({
                        title: 'System Updates',
                        html: `
                            <div class="text-start">
                                <p><strong>Laravel:</strong> ${response.data.laravel.current}</p>
                                <p><strong>Last Check:</strong> ${response.data.last_check}</p>
                                <p class="text-success">System is up to date!</p>
                            </div>
                        `,
                        icon: 'info'
                    });
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function() {
                $('#loadingModal').modal('hide');
                Swal.fire('Error', 'Failed to check updates', 'error');
            }
        });
    });

    // Utility functions
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
});
</script>
@endsection