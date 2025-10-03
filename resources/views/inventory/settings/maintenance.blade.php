@extends('layouts.app')

@section('title', 'System Maintenance Settings')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">Settings</a></li>
                            <li class="breadcrumb-item active">System Maintenance</li>
                        </ol>
                    </nav>
                    <h1 class="h3 mb-0">System Maintenance Settings</h1>
                    <p class="text-muted">Configure automated maintenance tasks, system health monitoring, and performance optimization</p>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary" onclick="runSystemDiagnostics()">
                        <i class="fas fa-stethoscope"></i> Run Diagnostics
                    </button>
                    <button type="button" class="btn btn-outline-success" onclick="runMaintenanceNow()">
                        <i class="fas fa-tools"></i> Run Maintenance Now
                    </button>
                    <button type="button" class="btn btn-primary" onclick="saveAllMaintenanceSettings()">
                        <i class="fas fa-save"></i> Save All Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="row mb-4">
        <div class="col-12">
            <ul class="nav nav-tabs" id="maintenanceTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="scheduled-tab" data-bs-toggle="tab" data-bs-target="#scheduled" type="button" role="tab">
                        <i class="fas fa-clock"></i> Scheduled Tasks
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="system-health-tab" data-bs-toggle="tab" data-bs-target="#system-health" type="button" role="tab">
                        <i class="fas fa-heartbeat"></i> System Health
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="performance-tab" data-bs-toggle="tab" data-bs-target="#performance" type="button" role="tab">
                        <i class="fas fa-tachometer-alt"></i> Performance
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="cleanup-tab" data-bs-toggle="tab" data-bs-target="#cleanup" type="button" role="tab">
                        <i class="fas fa-broom"></i> Cleanup & Optimization
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="monitoring-tab" data-bs-toggle="tab" data-bs-target="#monitoring" type="button" role="tab">
                        <i class="fas fa-chart-line"></i> Monitoring & Alerts
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="logs-tab" data-bs-toggle="tab" data-bs-target="#logs" type="button" role="tab">
                        <i class="fas fa-file-alt"></i> Maintenance Logs
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <!-- Tab Content -->
    <div class="tab-content" id="maintenanceTabContent">
        <!-- Scheduled Tasks Tab -->
        <div class="tab-pane fade show active" id="scheduled" role="tabpanel">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Automated Maintenance Tasks</h5>
                        </div>
                        <div class="card-body">
                            <!-- Database Maintenance -->
                            <div class="mb-4">
                                <h6 class="fw-bold">Database Maintenance</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="enableDbOptimization" checked>
                                            <label class="form-check-label" for="enableDbOptimization">
                                                Enable Database Optimization
                                            </label>
                                        </div>
                                        <div class="mb-3">
                                            <label for="dbOptimizationFrequency" class="form-label">Optimization Frequency</label>
                                            <select class="form-select" id="dbOptimizationFrequency">
                                                <option value="daily">Daily</option>
                                                <option value="weekly" selected>Weekly</option>
                                                <option value="monthly">Monthly</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="dbOptimizationTime" class="form-label">Optimization Time</label>
                                            <input type="time" class="form-control" id="dbOptimizationTime" value="02:00">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="optimizeTables" checked>
                                            <label class="form-check-label" for="optimizeTables">Optimize Tables</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="repairTables" checked>
                                            <label class="form-check-label" for="repairTables">Repair Tables</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="analyzeQueries">
                                            <label class="form-check-label" for="analyzeQueries">Analyze Slow Queries</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="updateStatistics" checked>
                                            <label class="form-check-label" for="updateStatistics">Update Statistics</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <!-- File System Maintenance -->
                            <div class="mb-4">
                                <h6 class="fw-bold">File System Maintenance</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="enableFileCleanup" checked>
                                            <label class="form-check-label" for="enableFileCleanup">
                                                Enable File Cleanup
                                            </label>
                                        </div>
                                        <div class="mb-3">
                                            <label for="fileCleanupFrequency" class="form-label">Cleanup Frequency</label>
                                            <select class="form-select" id="fileCleanupFrequency">
                                                <option value="daily" selected>Daily</option>
                                                <option value="weekly">Weekly</option>
                                                <option value="monthly">Monthly</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="tempFileAge" class="form-label">Delete Temp Files Older Than (days)</label>
                                            <input type="number" class="form-control" id="tempFileAge" value="7" min="1" max="365">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="cleanTempFiles" checked>
                                            <label class="form-check-label" for="cleanTempFiles">Clean Temporary Files</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="cleanLogFiles" checked>
                                            <label class="form-check-label" for="cleanLogFiles">Clean Old Log Files</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="cleanCacheFiles" checked>
                                            <label class="form-check-label" for="cleanCacheFiles">Clean Cache Files</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="cleanSessionFiles">
                                            <label class="form-check-label" for="cleanSessionFiles">Clean Session Files</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <!-- System Updates -->
                            <div class="mb-4">
                                <h6 class="fw-bold">System Updates</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="enableAutoUpdates">
                                            <label class="form-check-label" for="enableAutoUpdates">
                                                Enable Automatic Updates
                                            </label>
                                        </div>
                                        <div class="mb-3">
                                            <label for="updateCheckFrequency" class="form-label">Check for Updates</label>
                                            <select class="form-select" id="updateCheckFrequency">
                                                <option value="daily" selected>Daily</option>
                                                <option value="weekly">Weekly</option>
                                                <option value="monthly">Monthly</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="updateSecurity" checked>
                                            <label class="form-check-label" for="updateSecurity">Security Updates</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="updateCore">
                                            <label class="form-check-label" for="updateCore">Core System Updates</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="updatePlugins">
                                            <label class="form-check-label" for="updatePlugins">Plugin Updates</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Task Status -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Task Status</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span>Database Optimization</span>
                                <span class="badge bg-success">Completed</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span>File Cleanup</span>
                                <span class="badge bg-primary">Running</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span>System Updates</span>
                                <span class="badge bg-secondary">Scheduled</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span>Cache Optimization</span>
                                <span class="badge bg-warning">Pending</span>
                            </div>
                            <hr>
                            <div class="text-center">
                                <small class="text-muted">Last maintenance: 2 hours ago</small><br>
                                <small class="text-muted">Next maintenance: In 22 hours</small>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Quick Actions</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-primary btn-sm" onclick="optimizeDatabase()">
                                    <i class="fas fa-database"></i> Optimize Database
                                </button>
                                <button class="btn btn-outline-success btn-sm" onclick="cleanTempFiles()">
                                    <i class="fas fa-broom"></i> Clean Temp Files
                                </button>
                                <button class="btn btn-outline-info btn-sm" onclick="clearCache()">
                                    <i class="fas fa-trash"></i> Clear Cache
                                </button>
                                <button class="btn btn-outline-warning btn-sm" onclick="checkUpdates()">
                                    <i class="fas fa-sync"></i> Check Updates
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Health Tab -->
        <div class="tab-pane fade" id="system-health" role="tabpanel">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">System Health Monitoring</h5>
                        </div>
                        <div class="card-body">
                            <!-- Health Check Configuration -->
                            <div class="mb-4">
                                <h6 class="fw-bold">Health Check Configuration</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="enableHealthChecks" checked>
                                            <label class="form-check-label" for="enableHealthChecks">
                                                Enable Health Monitoring
                                            </label>
                                        </div>
                                        <div class="mb-3">
                                            <label for="healthCheckInterval" class="form-label">Check Interval (minutes)</label>
                                            <select class="form-select" id="healthCheckInterval">
                                                <option value="5" selected>5 minutes</option>
                                                <option value="10">10 minutes</option>
                                                <option value="15">15 minutes</option>
                                                <option value="30">30 minutes</option>
                                                <option value="60">1 hour</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="checkCpuUsage" checked>
                                            <label class="form-check-label" for="checkCpuUsage">Monitor CPU Usage</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="checkMemoryUsage" checked>
                                            <label class="form-check-label" for="checkMemoryUsage">Monitor Memory Usage</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="checkDiskSpace" checked>
                                            <label class="form-check-label" for="checkDiskSpace">Monitor Disk Space</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="checkDbConnection" checked>
                                            <label class="form-check-label" for="checkDbConnection">Monitor Database Connection</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <!-- Alert Thresholds -->
                            <div class="mb-4">
                                <h6 class="fw-bold">Alert Thresholds</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="cpuThreshold" class="form-label">CPU Usage Alert (%)</label>
                                            <input type="range" class="form-range" id="cpuThreshold" min="50" max="100" value="80">
                                            <div class="d-flex justify-content-between">
                                                <small>50%</small>
                                                <small id="cpuThresholdValue">80%</small>
                                                <small>100%</small>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="memoryThreshold" class="form-label">Memory Usage Alert (%)</label>
                                            <input type="range" class="form-range" id="memoryThreshold" min="50" max="100" value="85">
                                            <div class="d-flex justify-content-between">
                                                <small>50%</small>
                                                <small id="memoryThresholdValue">85%</small>
                                                <small>100%</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="diskThreshold" class="form-label">Disk Space Alert (%)</label>
                                            <input type="range" class="form-range" id="diskThreshold" min="70" max="100" value="90">
                                            <div class="d-flex justify-content-between">
                                                <small>70%</small>
                                                <small id="diskThresholdValue">90%</small>
                                                <small>100%</small>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="responseTimeThreshold" class="form-label">Response Time Alert (ms)</label>
                                            <input type="number" class="form-control" id="responseTimeThreshold" value="5000" min="1000" max="30000">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Current System Status -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Current System Status</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span>CPU Usage</span>
                                    <span class="text-success" id="currentCpuUsage">45%</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-success" id="cpuProgressBar" style="width: 45%"></div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span>Memory Usage</span>
                                    <span class="text-warning" id="currentMemoryUsage">72%</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-warning" id="memoryProgressBar" style="width: 72%"></div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span>Disk Space</span>
                                    <span class="text-success" id="currentDiskUsage">68%</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-success" id="diskProgressBar" style="width: 68%"></div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>Database</span>
                                    <span class="badge bg-success">Online</span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>Web Server</span>
                                    <span class="badge bg-success">Running</span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>Cache Service</span>
                                    <span class="badge bg-success">Active</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Alerts -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Recent Alerts</h6>
                        </div>
                        <div class="card-body">
                            <div id="recentAlertsList">
                                <!-- Alerts will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Tab -->
        <div class="tab-pane fade" id="performance" role="tabpanel">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Performance Optimization</h5>
                        </div>
                        <div class="card-body">
                            <!-- Cache Settings -->
                            <div class="mb-4">
                                <h6 class="fw-bold">Cache Configuration</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="enableCaching" checked>
                                            <label class="form-check-label" for="enableCaching">
                                                Enable Application Caching
                                            </label>
                                        </div>
                                        <div class="mb-3">
                                            <label for="cacheDriver" class="form-label">Cache Driver</label>
                                            <select class="form-select" id="cacheDriver">
                                                <option value="file">File</option>
                                                <option value="redis" selected>Redis</option>
                                                <option value="memcached">Memcached</option>
                                                <option value="database">Database</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="cacheTimeout" class="form-label">Default Cache Timeout (minutes)</label>
                                            <input type="number" class="form-control" id="cacheTimeout" value="60" min="1" max="1440">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="cacheViews" checked>
                                            <label class="form-check-label" for="cacheViews">Cache Views</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="cacheRoutes" checked>
                                            <label class="form-check-label" for="cacheRoutes">Cache Routes</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="cacheConfig" checked>
                                            <label class="form-check-label" for="cacheConfig">Cache Configuration</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="cacheQueries">
                                            <label class="form-check-label" for="cacheQueries">Cache Database Queries</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <!-- Database Optimization -->
                            <div class="mb-4">
                                <h6 class="fw-bold">Database Performance</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="enableQueryOptimization" checked>
                                            <label class="form-check-label" for="enableQueryOptimization">
                                                Enable Query Optimization
                                            </label>
                                        </div>
                                        <div class="mb-3">
                                            <label for="slowQueryThreshold" class="form-label">Slow Query Threshold (seconds)</label>
                                            <input type="number" class="form-control" id="slowQueryThreshold" value="2" min="0.1" max="60" step="0.1">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="logSlowQueries" checked>
                                            <label class="form-check-label" for="logSlowQueries">Log Slow Queries</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="optimizeIndexes" checked>
                                            <label class="form-check-label" for="optimizeIndexes">Auto-optimize Indexes</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="enableQueryCache">
                                            <label class="form-check-label" for="enableQueryCache">Enable Query Cache</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <!-- Asset Optimization -->
                            <div class="mb-4">
                                <h6 class="fw-bold">Asset Optimization</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="enableAssetOptimization" checked>
                                            <label class="form-check-label" for="enableAssetOptimization">
                                                Enable Asset Optimization
                                            </label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="minifyCSS" checked>
                                            <label class="form-check-label" for="minifyCSS">Minify CSS</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="minifyJS" checked>
                                            <label class="form-check-label" for="minifyJS">Minify JavaScript</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="compressImages" checked>
                                            <label class="form-check-label" for="compressImages">Compress Images</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="enableGzip" checked>
                                            <label class="form-check-label" for="enableGzip">Enable Gzip Compression</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="enableCDN">
                                            <label class="form-check-label" for="enableCDN">Enable CDN</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Performance Metrics -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Performance Metrics</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span>Page Load Time</span>
                                    <span class="text-success">1.2s</span>
                                </div>
                                <small class="text-muted">Average over last 24 hours</small>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span>Database Queries</span>
                                    <span class="text-info">45/page</span>
                                </div>
                                <small class="text-muted">Average queries per page</small>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span>Cache Hit Rate</span>
                                    <span class="text-success">87%</span>
                                </div>
                                <small class="text-muted">Cache effectiveness</small>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span>Memory Usage</span>
                                    <span class="text-warning">256 MB</span>
                                </div>
                                <small class="text-muted">Peak memory usage</small>
                            </div>
                        </div>
                    </div>

                    <!-- Performance Chart -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Response Time Trend</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="performanceChart" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cleanup & Optimization Tab -->
        <div class="tab-pane fade" id="cleanup" role="tabpanel">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">System Cleanup & Optimization</h5>
                        </div>
                        <div class="card-body">
                            <!-- Storage Cleanup -->
                            <div class="mb-4">
                                <h6 class="fw-bold">Storage Cleanup</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="enableAutoCleanup" checked>
                                            <label class="form-check-label" for="enableAutoCleanup">
                                                Enable Automatic Cleanup
                                            </label>
                                        </div>
                                        <div class="mb-3">
                                            <label for="cleanupFrequency" class="form-label">Cleanup Frequency</label>
                                            <select class="form-select" id="cleanupFrequency">
                                                <option value="daily" selected>Daily</option>
                                                <option value="weekly">Weekly</option>
                                                <option value="monthly">Monthly</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="logRetentionDays" class="form-label">Log Retention (days)</label>
                                            <input type="number" class="form-control" id="logRetentionDays" value="30" min="1" max="365">
                                        </div>
                                        <div class="mb-3">
                                            <label for="tempFileRetentionDays" class="form-label">Temp File Retention (days)</label>
                                            <input type="number" class="form-control" id="tempFileRetentionDays" value="7" min="1" max="30">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <!-- Manual Cleanup Actions -->
                            <div class="mb-4">
                                <h6 class="fw-bold">Manual Cleanup Actions</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="d-grid gap-2">
                                            <button class="btn btn-outline-primary" onclick="cleanupTempFiles()">
                                                <i class="fas fa-broom"></i> Clean Temporary Files
                                                <small class="d-block text-muted">~245 MB</small>
                                            </button>
                                            <button class="btn btn-outline-success" onclick="cleanupLogFiles()">
                                                <i class="fas fa-file-alt"></i> Clean Old Log Files
                                                <small class="d-block text-muted">~89 MB</small>
                                            </button>
                                            <button class="btn btn-outline-info" onclick="cleanupCacheFiles()">
                                                <i class="fas fa-database"></i> Clean Cache Files
                                                <small class="d-block text-muted">~156 MB</small>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-grid gap-2">
                                            <button class="btn btn-outline-warning" onclick="cleanupSessionFiles()">
                                                <i class="fas fa-users"></i> Clean Session Files
                                                <small class="d-block text-muted">~12 MB</small>
                                            </button>
                                            <button class="btn btn-outline-danger" onclick="cleanupErrorLogs()">
                                                <i class="fas fa-exclamation-triangle"></i> Clean Error Logs
                                                <small class="d-block text-muted">~34 MB</small>
                                            </button>
                                            <button class="btn btn-outline-secondary" onclick="optimizeDatabase()">
                                                <i class="fas fa-database"></i> Optimize Database
                                                <small class="d-block text-muted">Defragment tables</small>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <!-- Disk Space Analysis -->
                            <div class="mb-4">
                                <h6 class="fw-bold">Disk Space Analysis</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Directory</th>
                                                <th>Size</th>
                                                <th>Files</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="diskAnalysisTable">
                                            <!-- Data will be loaded here -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Storage Overview -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Storage Overview</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span>Total Space</span>
                                    <span>50 GB</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span>Used Space</span>
                                    <span>34 GB</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span>Free Space</span>
                                    <span class="text-success">16 GB</span>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar bg-primary" style="width: 68%"></div>
                                </div>
                                <small class="text-muted">68% used</small>
                            </div>
                            <hr>
                            <div class="mb-2">
                                <div class="d-flex justify-content-between">
                                    <span>Application Files</span>
                                    <span>12 GB</span>
                                </div>
                            </div>
                            <div class="mb-2">
                                <div class="d-flex justify-content-between">
                                    <span>Database</span>
                                    <span>8 GB</span>
                                </div>
                            </div>
                            <div class="mb-2">
                                <div class="d-flex justify-content-between">
                                    <span>User Uploads</span>
                                    <span>6 GB</span>
                                </div>
                            </div>
                            <div class="mb-2">
                                <div class="d-flex justify-content-between">
                                    <span>Logs</span>
                                    <span>4 GB</span>
                                </div>
                            </div>
                            <div class="mb-2">
                                <div class="d-flex justify-content-between">
                                    <span>Cache</span>
                                    <span>2 GB</span>
                                </div>
                            </div>
                            <div class="mb-2">
                                <div class="d-flex justify-content-between">
                                    <span>Temporary Files</span>
                                    <span>2 GB</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Cleanup History -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Recent Cleanup</h6>
                        </div>
                        <div class="card-body">
                            <div id="cleanupHistoryList">
                                <!-- History will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monitoring & Alerts Tab -->
        <div class="tab-pane fade" id="monitoring" role="tabpanel">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Monitoring & Alert Configuration</h5>
                        </div>
                        <div class="card-body">
                            <!-- Alert Settings -->
                            <div class="mb-4">
                                <h6 class="fw-bold">Alert Settings</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="enableAlerts" checked>
                                            <label class="form-check-label" for="enableAlerts">
                                                Enable System Alerts
                                            </label>
                                        </div>
                                        <div class="mb-3">
                                            <label for="alertMethod" class="form-label">Alert Method</label>
                                            <select class="form-select" id="alertMethod">
                                                <option value="email" selected>Email</option>
                                                <option value="sms">SMS</option>
                                                <option value="both">Email & SMS</option>
                                                <option value="webhook">Webhook</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="alertEmail" class="form-label">Alert Email</label>
                                            <input type="email" class="form-control" id="alertEmail" value="admin@example.com">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="alertCritical" checked>
                                            <label class="form-check-label" for="alertCritical">Critical Alerts</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="alertWarning" checked>
                                            <label class="form-check-label" for="alertWarning">Warning Alerts</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="alertInfo">
                                            <label class="form-check-label" for="alertInfo">Info Alerts</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="alertMaintenance" checked>
                                            <label class="form-check-label" for="alertMaintenance">Maintenance Alerts</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <!-- Monitoring Metrics -->
                            <div class="mb-4">
                                <h6 class="fw-bold">Monitoring Metrics</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Metric</th>
                                                <th>Current</th>
                                                <th>Threshold</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="monitoringMetricsTable">
                                            <!-- Metrics will be loaded here -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Alert Summary -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Alert Summary</h6>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="border-end">
                                        <h4 class="text-danger mb-0">3</h4>
                                        <small class="text-muted">Critical</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <h4 class="text-warning mb-0">7</h4>
                                    <small class="text-muted">Warning</small>
                                </div>
                            </div>
                            <hr>
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="border-end">
                                        <h4 class="text-info mb-0">12</h4>
                                        <small class="text-muted">Info</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <h4 class="text-success mb-0">45</h4>
                                    <small class="text-muted">Resolved</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- System Uptime -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">System Uptime</h6>
                        </div>
                        <div class="card-body">
                            <div class="text-center">
                                <h3 class="text-success mb-0">99.8%</h3>
                                <small class="text-muted">Last 30 days</small>
                            </div>
                            <hr>
                            <div class="mb-2">
                                <div class="d-flex justify-content-between">
                                    <span>Current Uptime</span>
                                    <span>15d 8h 23m</span>
                                </div>
                            </div>
                            <div class="mb-2">
                                <div class="d-flex justify-content-between">
                                    <span>Last Restart</span>
                                    <span>Jan 1, 2024</span>
                                </div>
                            </div>
                            <div class="mb-2">
                                <div class="d-flex justify-content-between">
                                    <span>Total Downtime</span>
                                    <span>2h 15m</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Maintenance Logs Tab -->
        <div class="tab-pane fade" id="logs" role="tabpanel">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Maintenance Activity Logs</h5>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-outline-primary btn-sm" onclick="refreshMaintenanceLogs()">
                                        <i class="fas fa-sync"></i> Refresh
                                    </button>
                                    <button class="btn btn-outline-success btn-sm" onclick="exportMaintenanceLogs()">
                                        <i class="fas fa-download"></i> Export
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Log Filters -->
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <select class="form-select form-select-sm" id="logLevelFilter">
                                        <option value="">All Levels</option>
                                        <option value="info">Info</option>
                                        <option value="warning">Warning</option>
                                        <option value="error">Error</option>
                                        <option value="success">Success</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select form-select-sm" id="logTypeFilter">
                                        <option value="">All Types</option>
                                        <option value="maintenance">Maintenance</option>
                                        <option value="cleanup">Cleanup</option>
                                        <option value="optimization">Optimization</option>
                                        <option value="monitoring">Monitoring</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <input type="date" class="form-control form-control-sm" id="logDateFilter">
                                </div>
                                <div class="col-md-3">
                                    <input type="text" class="form-control form-control-sm" id="logSearchInput" placeholder="Search logs...">
                                </div>
                            </div>

                            <!-- Logs Table -->
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Timestamp</th>
                                            <th>Level</th>
                                            <th>Type</th>
                                            <th>Message</th>
                                            <th>Duration</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="maintenanceLogsTableBody">
                                        <!-- Logs will be loaded here -->
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <nav aria-label="Logs pagination">
                                <ul class="pagination pagination-sm justify-content-center" id="logsPagination">
                                    <!-- Pagination will be loaded here -->
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Maintenance Progress Modal -->
<div class="modal fade" id="maintenanceProgressModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">System Maintenance in Progress</h5>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span id="modalCurrentTask">Initializing maintenance...</span>
                        <span id="modalMaintenancePercent">0%</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" id="modalMaintenanceProgressBar" style="width: 0%"></div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <small class="text-muted">Time Elapsed:</small><br>
                        <span id="modalTimeElapsed">0 seconds</span>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">Estimated Remaining:</small><br>
                        <span id="modalTimeRemaining">Calculating...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger" onclick="cancelMaintenance()">Cancel</button>
            </div>
        </div>
    </div>
</div>

<style>
/* Custom styles for maintenance settings */
.form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.form-range::-webkit-slider-thumb {
    background-color: #0d6efd;
}

.form-range::-moz-range-thumb {
    background-color: #0d6efd;
    border: none;
}

.progress {
    background-color: #e9ecef;
}

.progress-bar {
    transition: width 0.3s ease;
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
}

.nav-tabs .nav-link {
    border: 1px solid transparent;
    border-top-left-radius: 0.375rem;
    border-top-right-radius: 0.375rem;
}

.nav-tabs .nav-link.active {
    color: #495057;
    background-color: #fff;
    border-color: #dee2e6 #dee2e6 #fff;
}

.badge {
    font-size: 0.75em;
}

.btn-group-sm > .btn, .btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    border-radius: 0.25rem;
}

.table th {
    border-top: none;
    font-weight: 600;
    color: #495057;
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.025);
}

.alert {
    border: none;
    border-radius: 0.5rem;
}

.modal-content {
    border-radius: 0.5rem;
    border: none;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.breadcrumb {
    background-color: transparent;
    padding: 0;
    margin-bottom: 0.5rem;
}

.breadcrumb-item + .breadcrumb-item::before {
    content: ">";
    color: #6c757d;
}

.text-success { color: #198754 !important; }
.text-warning { color: #ffc107 !important; }
.text-danger { color: #dc3545 !important; }
.text-info { color: #0dcaf0 !important; }

.bg-success { background-color: #198754 !important; }
.bg-warning { background-color: #ffc107 !important; }
.bg-danger { background-color: #dc3545 !important; }
.bg-info { background-color: #0dcaf0 !important; }
.bg-primary { background-color: #0d6efd !important; }
.bg-secondary { background-color: #6c757d !important; }

/* Responsive adjustments */
@media (max-width: 768px) {
    .d-flex.gap-2 {
        flex-direction: column;
        gap: 0.5rem !important;
    }
    
    .btn-group {
        flex-direction: column;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
}

/* Animation for progress bars */
@keyframes progress-bar-stripes {
    0% { background-position: 1rem 0; }
    100% { background-position: 0 0; }
}

.progress-bar-animated {
    animation: progress-bar-stripes 1s linear infinite;
}

/* Custom scrollbar for tables */
.table-responsive::-webkit-scrollbar {
    height: 8px;
}

.table-responsive::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
</style>

<script>
// System Maintenance Settings JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the page
    initializeMaintenanceSettings();
    loadSystemStatus();
    loadRecentAlerts();
    loadMonitoringMetrics();
    loadDiskAnalysis();
    loadCleanupHistory();
    loadMaintenanceLogs();
    
    // Set up event listeners
    setupMaintenanceEventListeners();
    
    // Start real-time updates
    startRealTimeUpdates();
});

// Initialize maintenance settings
function initializeMaintenanceSettings() {
    // Load saved settings from localStorage or server
    const savedSettings = JSON.parse(localStorage.getItem('maintenanceSettings') || '{}');
    
    // Apply saved settings to form elements
    if (savedSettings.enableDbOptimization !== undefined) {
        document.getElementById('enableDbOptimization').checked = savedSettings.enableDbOptimization;
    }
    
    if (savedSettings.dbOptimizationFrequency) {
        document.getElementById('dbOptimizationFrequency').value = savedSettings.dbOptimizationFrequency;
    }
    
    // Initialize threshold sliders
    updateThresholdValues();
}

// Setup event listeners
function setupMaintenanceEventListeners() {
    // Threshold sliders
    document.getElementById('cpuThreshold').addEventListener('input', function() {
        document.getElementById('cpuThresholdValue').textContent = this.value + '%';
    });
    
    document.getElementById('memoryThreshold').addEventListener('input', function() {
        document.getElementById('memoryThresholdValue').textContent = this.value + '%';
    });
    
    document.getElementById('diskThreshold').addEventListener('input', function() {
        document.getElementById('diskThresholdValue').textContent = this.value + '%';
    });
    
    // Log filters
    document.getElementById('logLevelFilter').addEventListener('change', applyMaintenanceLogFilters);
    document.getElementById('logTypeFilter').addEventListener('change', applyMaintenanceLogFilters);
    document.getElementById('logDateFilter').addEventListener('change', applyMaintenanceLogFilters);
    document.getElementById('logSearchInput').addEventListener('input', applyMaintenanceLogFilters);
}

// Update threshold values
function updateThresholdValues() {
    document.getElementById('cpuThresholdValue').textContent = document.getElementById('cpuThreshold').value + '%';
    document.getElementById('memoryThresholdValue').textContent = document.getElementById('memoryThreshold').value + '%';
    document.getElementById('diskThresholdValue').textContent = document.getElementById('diskThreshold').value + '%';
}

// Run system diagnostics
function runSystemDiagnostics() {
    showMaintenanceAlert('Running comprehensive system diagnostics...', 'info');
    
    setTimeout(() => {
        const diagnostics = [
            'CPU Performance: Excellent',
            'Memory Usage: Normal',
            'Disk Health: Good',
            'Database Connection: Stable',
            'Network Connectivity: Good',
            'Security Status: Secure'
        ];
        
        showMaintenanceAlert('System diagnostics completed successfully!', 'success');
        console.log('Diagnostics:', diagnostics);
    }, 3000);
}

// Run maintenance now
function runMaintenanceNow() {
    const modal = new bootstrap.Modal(document.getElementById('maintenanceProgressModal'));
    modal.show();
    
    simulateMaintenanceProgress();
}

// Simulate maintenance progress
function simulateMaintenanceProgress() {
    const tasks = [
        'Optimizing database tables...',
        'Cleaning temporary files...',
        'Updating system cache...',
        'Checking system integrity...',
        'Finalizing maintenance...'
    ];
    
    let currentTask = 0;
    let progress = 0;
    const startTime = Date.now();
    
    const interval = setInterval(() => {
        progress += Math.random() * 20;
        if (progress > 100) progress = 100;
        
        document.getElementById('modalMaintenanceProgressBar').style.width = progress + '%';
        document.getElementById('modalMaintenancePercent').textContent = Math.round(progress) + '%';
        
        if (currentTask < tasks.length) {
            document.getElementById('modalCurrentTask').textContent = tasks[currentTask];
        }
        
        const elapsed = Math.round((Date.now() - startTime) / 1000);
        document.getElementById('modalTimeElapsed').textContent = elapsed + ' seconds';
        
        if (progress >= 100) {
            clearInterval(interval);
            setTimeout(() => {
                bootstrap.Modal.getInstance(document.getElementById('maintenanceProgressModal')).hide();
                showMaintenanceAlert('System maintenance completed successfully!', 'success');
            }, 1000);
        } else if (Math.random() > 0.7 && currentTask < tasks.length - 1) {
            currentTask++;
        }
    }, 500);
}

// Save all maintenance settings
function saveAllMaintenanceSettings() {
    const settings = {
        enableDbOptimization: document.getElementById('enableDbOptimization').checked,
        dbOptimizationFrequency: document.getElementById('dbOptimizationFrequency').value,
        dbOptimizationTime: document.getElementById('dbOptimizationTime').value,
        enableFileCleanup: document.getElementById('enableFileCleanup').checked,
        fileCleanupFrequency: document.getElementById('fileCleanupFrequency').value,
        enableHealthChecks: document.getElementById('enableHealthChecks').checked,
        healthCheckInterval: document.getElementById('healthCheckInterval').value,
        cpuThreshold: document.getElementById('cpuThreshold').value,
        memoryThreshold: document.getElementById('memoryThreshold').value,
        diskThreshold: document.getElementById('diskThreshold').value,
        enableCaching: document.getElementById('enableCaching').checked,
        cacheDriver: document.getElementById('cacheDriver').value,
        enableAlerts: document.getElementById('enableAlerts').checked,
        alertMethod: document.getElementById('alertMethod').value,
        alertEmail: document.getElementById('alertEmail').value
    };
    
    localStorage.setItem('maintenanceSettings', JSON.stringify(settings));
    showMaintenanceAlert('Maintenance settings saved successfully!', 'success');
}

// Load system status
function loadSystemStatus() {
    // Simulate loading current system metrics
    setTimeout(() => {
        updateSystemMetric('currentCpuUsage', 'cpuProgressBar', 45, 'success');
        updateSystemMetric('currentMemoryUsage', 'memoryProgressBar', 72, 'warning');
        updateSystemMetric('currentDiskUsage', 'diskProgressBar', 68, 'success');
    }, 1000);
}

// Update system metric
function updateSystemMetric(textId, progressId, value, colorClass) {
    document.getElementById(textId).textContent = value + '%';
    const progressBar = document.getElementById(progressId);
    progressBar.style.width = value + '%';
    progressBar.className = `progress-bar bg-${colorClass}`;
}

// Load recent alerts
function loadRecentAlerts() {
    const alerts = [
        { type: 'warning', message: 'High memory usage detected', time: '2 hours ago' },
        { type: 'info', message: 'Database optimization completed', time: '4 hours ago' },
        { type: 'success', message: 'System backup completed', time: '6 hours ago' }
    ];
    
    const alertsList = document.getElementById('recentAlertsList');
    if (alertsList) {
        alertsList.innerHTML = alerts.map(alert => `
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <span class="badge bg-${alert.type === 'warning' ? 'warning' : alert.type === 'info' ? 'info' : 'success'} me-2">${alert.type.toUpperCase()}</span>
                    <small>${alert.message}</small>
                </div>
                <small class="text-muted">${alert.time}</small>
            </div>
        `).join('');
    }
}

// Load monitoring metrics
function loadMonitoringMetrics() {
    const metrics = [
        { name: 'CPU Usage', current: '45%', threshold: '80%', status: 'Normal', color: 'success' },
        { name: 'Memory Usage', current: '72%', threshold: '85%', status: 'Warning', color: 'warning' },
        { name: 'Disk Space', current: '68%', threshold: '90%', status: 'Normal', color: 'success' },
        { name: 'Response Time', current: '1.2s', threshold: '5.0s', status: 'Good', color: 'success' },
        { name: 'Database Connections', current: '25', threshold: '100', status: 'Normal', color: 'success' }
    ];
    
    const tableBody = document.getElementById('monitoringMetricsTable');
    if (tableBody) {
        tableBody.innerHTML = metrics.map(metric => `
            <tr>
                <td>${metric.name}</td>
                <td>${metric.current}</td>
                <td>${metric.threshold}</td>
                <td><span class="badge bg-${metric.color}">${metric.status}</span></td>
                <td>
                    <button class="btn btn-outline-primary btn-sm" onclick="viewMetricDetails('${metric.name}')">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    }
}

// Load disk analysis
function loadDiskAnalysis() {
    const diskData = [
        { directory: '/var/log', size: '4.2 GB', files: '1,234', action: 'cleanup' },
        { directory: '/tmp', size: '2.1 GB', files: '567', action: 'cleanup' },
        { directory: '/var/cache', size: '1.8 GB', files: '890', action: 'cleanup' },
        { directory: '/uploads', size: '6.5 GB', files: '2,345', action: 'archive' },
        { directory: '/backups', size: '12.3 GB', files: '45', action: 'review' }
    ];
    
    const tableBody = document.getElementById('diskAnalysisTable');
    if (tableBody) {
        tableBody.innerHTML = diskData.map(item => `
            <tr>
                <td>${item.directory}</td>
                <td>${item.size}</td>
                <td>${item.files}</td>
                <td>
                    <button class="btn btn-outline-primary btn-sm" onclick="performDiskAction('${item.directory}', '${item.action}')">
                        ${item.action.charAt(0).toUpperCase() + item.action.slice(1)}
                    </button>
                </td>
            </tr>
        `).join('');
    }
}

// Load cleanup history
function loadCleanupHistory() {
    const history = [
        { task: 'Temporary files cleanup', time: '2 hours ago', saved: '245 MB' },
        { task: 'Log files rotation', time: '1 day ago', saved: '89 MB' },
        { task: 'Cache optimization', time: '2 days ago', saved: '156 MB' },
        { task: 'Database optimization', time: '3 days ago', saved: '0 MB' }
    ];
    
    const historyList = document.getElementById('cleanupHistoryList');
    if (historyList) {
        historyList.innerHTML = history.map(item => `
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                    <div class="fw-bold">${item.task}</div>
                    <small class="text-muted">${item.time}</small>
                </div>
                <span class="badge bg-success">${item.saved}</span>
            </div>
        `).join('');
    }
}

// Load maintenance logs
function loadMaintenanceLogs() {
    const logs = [
        { timestamp: '2024-01-15 14:30:25', level: 'success', type: 'maintenance', message: 'Database optimization completed successfully', duration: '2m 15s' },
        { timestamp: '2024-01-15 12:15:10', level: 'info', type: 'cleanup', message: 'Temporary files cleanup started', duration: '45s' },
        { timestamp: '2024-01-15 10:45:33', level: 'warning', type: 'monitoring', message: 'High memory usage detected', duration: '-' },
        { timestamp: '2024-01-15 08:20:17', level: 'success', type: 'optimization', message: 'Cache optimization completed', duration: '1m 30s' },
        { timestamp: '2024-01-15 06:00:00', level: 'info', type: 'maintenance', message: 'Scheduled maintenance started', duration: '15m 22s' }
    ];
    
    const tableBody = document.getElementById('maintenanceLogsTableBody');
    if (tableBody) {
        tableBody.innerHTML = logs.map(log => `
            <tr>
                <td>${log.timestamp}</td>
                <td><span class="badge bg-${log.level === 'success' ? 'success' : log.level === 'warning' ? 'warning' : 'info'}">${log.level.toUpperCase()}</span></td>
                <td>${log.type}</td>
                <td>${log.message}</td>
                <td>${log.duration}</td>
                <td>
                    <button class="btn btn-outline-primary btn-sm" onclick="viewLogDetails('${log.timestamp}')">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    }
}

// Apply maintenance log filters
function applyMaintenanceLogFilters() {
    const levelFilter = document.getElementById('logLevelFilter').value;
    const typeFilter = document.getElementById('logTypeFilter').value;
    const dateFilter = document.getElementById('logDateFilter').value;
    const searchFilter = document.getElementById('logSearchInput').value.toLowerCase();
    
    // This would typically filter the logs based on the selected criteria
    console.log('Applying filters:', { levelFilter, typeFilter, dateFilter, searchFilter });
    
    // Reload logs with filters applied
    loadMaintenanceLogs();
}

// Start real-time updates
function startRealTimeUpdates() {
    setInterval(() => {
        // Update system metrics with slight variations
        const cpuUsage = Math.max(30, Math.min(90, 45 + (Math.random() - 0.5) * 10));
        const memoryUsage = Math.max(50, Math.min(95, 72 + (Math.random() - 0.5) * 8));
        const diskUsage = Math.max(60, Math.min(85, 68 + (Math.random() - 0.5) * 5));
        
        updateSystemMetric('currentCpuUsage', 'cpuProgressBar', Math.round(cpuUsage), 
            cpuUsage > 80 ? 'danger' : cpuUsage > 60 ? 'warning' : 'success');
        updateSystemMetric('currentMemoryUsage', 'memoryProgressBar', Math.round(memoryUsage), 
            memoryUsage > 85 ? 'danger' : memoryUsage > 70 ? 'warning' : 'success');
        updateSystemMetric('currentDiskUsage', 'diskProgressBar', Math.round(diskUsage), 
            diskUsage > 90 ? 'danger' : diskUsage > 75 ? 'warning' : 'success');
    }, 30000); // Update every 30 seconds
}

// Utility functions
function showMaintenanceAlert(message, type = 'info') {
    // Create and show a toast notification
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// Quick action functions
function optimizeDatabase() {
    showMaintenanceAlert('Database optimization started...', 'info');
    setTimeout(() => {
        showMaintenanceAlert('Database optimization completed!', 'success');
    }, 3000);
}

function cleanTempFiles() {
    showMaintenanceAlert('Cleaning temporary files...', 'info');
    setTimeout(() => {
        showMaintenanceAlert('Temporary files cleaned successfully!', 'success');
    }, 2000);
}

function clearCache() {
    showMaintenanceAlert('Clearing application cache...', 'info');
    setTimeout(() => {
        showMaintenanceAlert('Cache cleared successfully!', 'success');
    }, 1500);
}

function checkUpdates() {
    showMaintenanceAlert('Checking for system updates...', 'info');
    setTimeout(() => {
        showMaintenanceAlert('System is up to date!', 'success');
    }, 2500);
}

// Cleanup functions
function cleanupTempFiles() {
    showMaintenanceAlert('Cleaning temporary files...', 'info');
    setTimeout(() => {
        showMaintenanceAlert('Temporary files cleaned! Freed 245 MB', 'success');
    }, 2000);
}

function cleanupLogFiles() {
    showMaintenanceAlert('Cleaning old log files...', 'info');
    setTimeout(() => {
        showMaintenanceAlert('Log files cleaned! Freed 89 MB', 'success');
    }, 1800);
}

function cleanupCacheFiles() {
    showMaintenanceAlert('Cleaning cache files...', 'info');
    setTimeout(() => {
        showMaintenanceAlert('Cache files cleaned! Freed 156 MB', 'success');
    }, 1500);
}

function cleanupSessionFiles() {
    showMaintenanceAlert('Cleaning session files...', 'info');
    setTimeout(() => {
        showMaintenanceAlert('Session files cleaned! Freed 12 MB', 'success');
    }, 1200);
}

function cleanupErrorLogs() {
    showMaintenanceAlert('Cleaning error logs...', 'info');
    setTimeout(() => {
        showMaintenanceAlert('Error logs cleaned! Freed 34 MB', 'success');
    }, 1600);
}

// Other utility functions
function cancelMaintenance() {
    bootstrap.Modal.getInstance(document.getElementById('maintenanceProgressModal')).hide();
    showMaintenanceAlert('Maintenance cancelled by user', 'warning');
}

function refreshMaintenanceLogs() {
    showMaintenanceAlert('Refreshing maintenance logs...', 'info');
    loadMaintenanceLogs();
}

function exportMaintenanceLogs() {
    showMaintenanceAlert('Exporting maintenance logs...', 'info');
    setTimeout(() => {
        showMaintenanceAlert('Logs exported successfully!', 'success');
    }, 1000);
}

function viewMetricDetails(metricName) {
    showMaintenanceAlert(`Viewing details for ${metricName}...`, 'info');
}

function performDiskAction(directory, action) {
    showMaintenanceAlert(`Performing ${action} on ${directory}...`, 'info');
    setTimeout(() => {
        showMaintenanceAlert(`${action.charAt(0).toUpperCase() + action.slice(1)} completed for ${directory}!`, 'success');
    }, 2000);
}

function viewLogDetails(timestamp) {
    showMaintenanceAlert(`Viewing log details for ${timestamp}...`, 'info');
}
</script>

@endsection